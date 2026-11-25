<?php
function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function dashboardData($conn) {
    $today = new DateTimeImmutable('today');
    $start = $today->modify('-6 days')->format('Y-m-d');
    $end = $today->modify('+1 day')->format('Y-m-d');
    $statement = $conn->prepare("SELECT DATE(dataPedido) AS dia, COUNT(*) AS pedidos,
        SUM(CASE WHEN statusPedido = 'Concluido' THEN totalPedido ELSE 0 END) AS total,
        SUM(CASE WHEN statusPedido = 'Concluido' THEN 1 ELSE 0 END) AS concluidos
        FROM pedido WHERE dataPedido >= ? AND dataPedido < ? GROUP BY DATE(dataPedido)");
    $statement->bind_param('ss', $start, $end);
    $statement->execute();
    $daily = array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), null, 'dia');

    $sales = [];
    for ($offset = -6; $offset <= 0; $offset++) {
        $day = $today->modify("$offset days");
        $sales[] = [
            'label' => $day->format('d/m'),
            'total' => (float)($daily[$day->format('Y-m-d')]['total'] ?? 0)
        ];
    }

    $statement = $conn->prepare("SELECT p.nomeProduto, SUM(i.quantidadeProduto_pedido) AS quantidade
        FROM produto_pedido i
        JOIN produto p ON p.idProduto = i.produtoProduto_pedido_FK
        JOIN pedido o ON o.idPedido = i.pedidoProduto_pedido_FK
        WHERE o.statusPedido <> 'Cancelado' AND o.dataPedido >= ? AND o.dataPedido < ?
        GROUP BY p.idProduto, p.nomeProduto
        ORDER BY quantidade DESC, p.nomeProduto
        LIMIT 4");
    $statement->bind_param('ss', $start, $end);
    $statement->execute();

    return [
        'today' => $daily[$today->format('Y-m-d')] ?? ['pedidos' => 0, 'total' => 0, 'concluidos' => 0],
        'yesterday' => (float)($daily[$today->modify('-1 day')->format('Y-m-d')]['total'] ?? 0),
        'sales' => $sales,
        'top' => $statement->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function recentOrders($conn) {
    return $conn->query("SELECT o.idPedido, o.dataPedido, o.statusPedido, o.totalPedido, c.nomeCliente,
        (SELECT GROUP_CONCAT(CONCAT(i.quantidadeProduto_pedido, '× ', p.nomeProduto) ORDER BY i.idProduto_Pedido SEPARATOR ', ')
            FROM produto_pedido i JOIN produto p ON p.idProduto = i.produtoProduto_pedido_FK
            WHERE i.pedidoProduto_pedido_FK = o.idPedido) AS itens
        FROM pedido o JOIN cliente c ON c.idCliente = o.clientePedido_FK
        ORDER BY o.dataPedido DESC, o.idPedido DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
}

function saveProductImage($file) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Envie uma imagem de até 2 MB.');
    }

    $formats = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];
    $dimensions = @getimagesize($file['tmp_name']);
    $extension = $formats[$dimensions[2] ?? 0] ?? null;
    if (!$extension) {
        throw new InvalidArgumentException('Escolha uma imagem JPG, PNG ou WebP.');
    }

    $path = 'assets/products/' . bin2hex(random_bytes(12)) . '.' . $extension;
    $root = dirname(__DIR__);
    $folder = $root . '/assets/products';
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        throw new InvalidArgumentException('Não foi possível criar a pasta de imagens.');
    }
    if (!move_uploaded_file($file['tmp_name'], $root . '/' . $path)) {
        throw new InvalidArgumentException('Não foi possível salvar a imagem.');
    }

    return $path;
}
