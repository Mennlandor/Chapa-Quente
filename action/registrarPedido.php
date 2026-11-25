<?php
require __DIR__ . '/../page/loja.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../page/checkout.php', true, 303);
    exit;
}

$cliente = $clienteId;
if (!$cliente) {
    header('Location: ../index.html', true, 303);
    exit;
}

$metodos = [
    'pix' => 'PIX',
    'card' => 'Credito',
    'debit' => 'Debito',
    'cash' => 'Em dinheiro'
];

$opcao = $_POST['metodo'] ?? '';
$metodo = is_string($opcao) ? ($metodos[$opcao] ?? null) : null;
$totalCentavos = $subtotal + ENTREGA_CENTAVOS;

if (!$itens) {
    $_SESSION['checkout_erro'] = 'O carrinho está vazio.';
} elseif (!$metodo) {
    $_SESSION['checkout_erro'] = 'Escolha uma forma de pagamento.';
} elseif ($totalCentavos > 99999) {
    $_SESSION['checkout_erro'] = 'O total ultrapassa o limite de R$ 999,99 por pedido.';
} else {
    $total = number_format($totalCentavos / 100, 2, '.', '');
    try {
        $conn->begin_transaction();

        if (lerCarrinho($conn, $cliente, true) !== $quantidades) {
            $conn->rollback();
            $_SESSION['checkout_erro'] = 'O carrinho mudou. Confira os itens antes de confirmar.';
            header('Location: ../page/checkout.php', true, 303);
            exit;
        }

        $pedido = $conn->prepare('INSERT INTO pedido (dataPedido, totalPedido, clientePedido_FK) VALUES (NOW(), ?, ?)');
        $pedido->bind_param('si', $total, $cliente);
        $pedido->execute();
        $idPedido = $conn->insert_id;

        $produtoPedido = $conn->prepare('INSERT INTO produto_pedido
            (quantidadeProduto_pedido, precoUnitarioProduto_pedido, pedidoProduto_pedido_FK, produtoProduto_pedido_FK)
            VALUES (?, ?, ?, ?)');
        foreach ($itens as $idProduto => $item) {
            $quantidade = $item['quantidade'];
            $precoUnitario = number_format($item['centavos'] / 100, 2, '.', '');
            $produtoPedido->bind_param('isii', $quantidade, $precoUnitario, $idPedido, $idProduto);
            $produtoPedido->execute();
        }

        $pagamento = $conn->prepare('INSERT INTO pagamento (metodoPagamento, valorPagamento, pedidoPagamento_FK) VALUES (?, ?, ?)');
        $pagamento->bind_param('ssi', $metodo, $total, $idPedido);
        $pagamento->execute();

        limparCarrinho($conn, $cliente);
        $conn->commit();
        unset($_SESSION['carrinho'], $_SESSION['checkout_erro']);
        $_SESSION['pedido_confirmado'] = $idPedido;
    } catch (mysqli_sql_exception $erro) {
        $conn->rollback();
        error_log('Falha ao registrar pedido: ' . $erro->getMessage());
        $_SESSION['checkout_erro'] = 'Não foi possível registrar o pedido. Tente novamente.';
    }
}

header('Location: ../page/checkout.php', true, 303);
exit;
