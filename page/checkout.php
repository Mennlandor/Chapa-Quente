<?php
require __DIR__ . '/loja.php';
$pedidoConfirmado = $_SESSION['pedido_confirmado'] ?? null;
$erro = $_SESSION['checkout_erro'] ?? null;
unset($_SESSION['pedido_confirmado'], $_SESSION['checkout_erro']);

if (!$itens && !$pedidoConfirmado) {
    header('Location: carrinho.php');
    exit;
}
$ocultarCarrinho = true;
$voltar = 'carrinho.php';
$textoVoltar = 'Voltar ao Carrinho';
$entrega = ENTREGA_CENTAVOS;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Chapa Quente</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@800&display=swap">
    <link rel="stylesheet" href="loja.css?v=<?= filemtime(__DIR__ . '/loja.css') ?>">
    <script src="loja.js" defer></script>
</head>
<body>
<?php require __DIR__ . '/header.php'; ?>
<main class="wrapper">
    <h1 class="page-title">Checkout</h1>
    <?php if ($pedidoConfirmado): ?>
        <section class="panel summary">
            <h2>Pedido #<?= (int)$pedidoConfirmado ?> registrado</h2>
            <p class="notice">O pedido foi registrado sem cobrança.</p>
            <a class="button" href="menu.php">Voltar ao Cardápio</a>
        </section>
    <?php else: ?>
    <form class="checkout" method="post" action="../action/registrarPedido.php">
        <?php require __DIR__ . '/payment-methods.html'; ?>
        <section class="panel summary">
            <h2>Resumo do Pedido</h2>
            <div class="summary-items">
                <?php foreach ($itens as $item): ?>
                    <div class="item">
                        <span><?= $item['quantidade'] ?>×</span>
                        <div><strong><?= h($item['nomeProduto']) ?></strong><small><?= reais($item['centavos']) ?> / un.</small></div>
                        <strong><?= reais($item['centavos'] * $item['quantidade']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="amounts"><div><span>Subtotal</span><strong><?= reais($subtotal) ?></strong></div><div><span>Entrega estimada</span><strong><?= reais($entrega) ?></strong></div></div>
            <div class="total-row"><span>Valor Total</span><strong><?= reais($subtotal + $entrega) ?></strong></div>
            <?php if ($erro): ?>
                <p class="notice"><?= h($erro) ?></p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['idCliente'])): ?>
                <button class="button" type="submit">Confirmar Pedido</button>
            <?php else: ?>
                <a class="button" href="../index.html">Entrar para confirmar</a>
            <?php endif; ?>
        </section>
    </form>
    <?php endif; ?>
</main>
</body>
</html>
