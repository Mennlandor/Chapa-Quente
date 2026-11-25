<?php
session_start();
require __DIR__ . '/cart-data.php';

$clienteId = (int)($_SESSION['idCliente'] ?? 0);
$primeiroNome = preg_split('/\s+/u', trim($_SESSION['nomeCliente'] ?? ''), 2)[0] ?? '';
$erroCarrinho = false;

const ENTREGA_CENTAVOS = 590;

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reais($centavos) {
    return 'R$ ' . number_format($centavos / 100, 2, ',', '.');
}

function foto($produto) {
    $caminho = trim($produto['caminhoImagemProduto'] ?? '');
    if ($caminho === '' || preg_match('~^(?:https?://|/|\.\./)~i', $caminho)) {
        return $caminho;
    }

    return '../' . $caminho;
}

$produtos = [];
$catalogoIndisponivel = false;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require __DIR__ . '/../bd.php';
    $sql = 'SELECT p.*, c.nomeCategoria
            FROM produto p
            LEFT JOIN categoria c ON c.idCategoria = p.categoriaProduto_FK
            ORDER BY c.nomeCategoria, p.nomeProduto';

    foreach ($conn->query($sql) as $produto) {
        $produto['centavos'] = (int)round((float)$produto['precoProduto'] * 100);
        $produtos[(int)$produto['idProduto']] = $produto;
    }
} catch (Throwable $erro) {
    $catalogoIndisponivel = true;
    $produtos = [];
}

$itens = [];
$subtotal = 0;
$carrinho = $_SESSION['carrinho'] ?? [];
if ($clienteId && !$catalogoIndisponivel) {
    try {
        if ($carrinho) {
            $conn->begin_transaction();
            try {
                foreach (array_intersect_key($carrinho, $produtos) as $id => $quantidade) {
                    adicionarAoCarrinho($conn, $clienteId, $id, $quantidade);
                }
                $conn->commit();
                unset($_SESSION['carrinho']);
            } catch (mysqli_sql_exception $erro) {
                $conn->rollback();
                throw $erro;
            }
        }
        $carrinho = lerCarrinho($conn, $clienteId);
    } catch (mysqli_sql_exception $erro) {
        error_log('Falha ao abrir carrinho: ' . $erro->getMessage());
        $erroCarrinho = true;
        $carrinho = [];
    }
}

$quantidades = array_intersect_key($carrinho, $produtos);
$contador = array_sum($quantidades);

foreach ($quantidades as $id => $quantidade) {
    $produto = $produtos[$id];
    $itens[$id] = $produto + ['quantidade' => $quantidade];
    $subtotal += $produto['centavos'] * $quantidade;
}
