<?php
function lerCarrinho($conn, $cliente, $bloquear = false) {
    $sql = 'SELECT produto_id, quantidade FROM carrinho_cliente WHERE cliente_id = ? ORDER BY produto_id';
    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }
    $consulta = $conn->prepare($sql);
    $consulta->bind_param('i', $cliente);
    $consulta->execute();

    $carrinho = [];
    foreach ($consulta->get_result() as $item) {
        $carrinho[(int)$item['produto_id']] = (int)$item['quantidade'];
    }
    return $carrinho;
}

function adicionarAoCarrinho($conn, $cliente, $produto, $quantidade) {
    $consulta = $conn->prepare('INSERT INTO carrinho_cliente (cliente_id, produto_id, quantidade)
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantidade = LEAST(99, quantidade + VALUES(quantidade))');
    $consulta->bind_param('iii', $cliente, $produto, $quantidade);
    $consulta->execute();
}

function removerDoCarrinho($conn, $cliente, $produto) {
    $consulta = $conn->prepare('DELETE FROM carrinho_cliente WHERE cliente_id = ? AND produto_id = ?');
    $consulta->bind_param('ii', $cliente, $produto);
    $consulta->execute();
}

function limparCarrinho($conn, $cliente) {
    $consulta = $conn->prepare('DELETE FROM carrinho_cliente WHERE cliente_id = ?');
    $consulta->bind_param('i', $cliente);
    $consulta->execute();
}
