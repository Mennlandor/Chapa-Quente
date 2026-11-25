<?php
require __DIR__ . '/loja.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['produto'] ?? 0);
    if (isset($itens[$id])) {
        if ($clienteId) {
            try {
                removerDoCarrinho($conn, $clienteId, $id);
            } catch (mysqli_sql_exception $erro) {
                $_SESSION['cart_error'] = 'Não foi possível remover o produto do carrinho.';
            }
        } else {
            unset($_SESSION['carrinho'][$id]);
        }
    }
    header('Location: carrinho.php', true, 303);
    exit;
}
$mensagemCarrinho = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_error']);
$voltar = 'menu.php';
$textoVoltar = 'Voltar ao Cardápio';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrinho | Chapa Quente</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@800&display=swap">
    <link rel="stylesheet" href="loja.css?v=<?= filemtime(__DIR__ . '/loja.css') ?>">
    <script src="loja.js" defer></script>
</head>
<body>
<?php require __DIR__ . '/header.php'; ?>
<main class="wrapper">
    <h1 class="page-title">Carrinho</h1>
    <section class="panel summary cart-list">
        <?php if ($mensagemCarrinho): ?>
            <p class="notice"><?= h($mensagemCarrinho) ?></p>
        <?php endif; ?>
        <?php if ($catalogoIndisponivel || $erroCarrinho): ?>
            <p class="muted">Carrinho indisponível no momento.</p>
        <?php elseif (!$itens): ?>
            <p class="muted">Seu carrinho está vazio.</p>
            <a class="button" href="menu.php">Ver cardápio</a>
        <?php else: ?>
            <?php foreach ($itens as $id => $item): ?>
                <div class="item">
                    <span><?= $item['quantidade'] ?>×</span>
                    <div><a href="produto.php?id=<?= $id ?>"><?= h($item['nomeProduto']) ?></a><small><?= reais($item['centavos']) ?> / un.</small></div>
                    <strong><?= reais($item['centavos'] * $item['quantidade']) ?></strong>
                    <form method="post"><button class="remove" name="produto" value="<?= $id ?>" type="submit">Remover</button></form>
                </div>
            <?php endforeach; ?>
            <div class="total-row"><span>Subtotal</span><strong><?= reais($subtotal) ?></strong></div>
            <a class="button" href="checkout.php">Ir para o checkout</a>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
