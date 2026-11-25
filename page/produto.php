<?php
require __DIR__ . '/loja.php';
$id = (int)($_GET['id'] ?? 0);
$produto = $produtos[$id] ?? null;
if (!$produto) {
    header('Location: menu.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantidade = max(1, min(99, (int)($_POST['quantidade'] ?? 1)));
    if ($clienteId) {
        try {
            adicionarAoCarrinho($conn, $clienteId, $id, $quantidade);
        } catch (mysqli_sql_exception $erro) {
            $_SESSION['cart_error'] = 'Não foi possível adicionar o produto ao carrinho.';
        }
    } else {
        $_SESSION['carrinho'][$id] = min(99, ($_SESSION['carrinho'][$id] ?? 0) + $quantidade);
    }
    header('Location: carrinho.php', true, 303);
    exit;
}
$titulo = $produto['nomeProduto'];
$voltar = 'menu.php';
$textoVoltar = 'Voltar ao Cardápio';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo) ?> | Chapa Quente</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@800&display=swap">
    <link rel="stylesheet" href="loja.css?v=<?= filemtime(__DIR__ . '/loja.css') ?>">
    <script src="loja.js" defer></script>
</head>
<body>
<?php require __DIR__ . '/header.php'; ?>
<main class="wrapper product-page">
    <div class="product-detail">
        <div class="product-image">
            <?php if ($imagem = foto($produto)): ?>
                <img src="<?= h($imagem) ?>" alt="<?= h($titulo) ?>">
            <?php else: ?>
                <span class="muted">Foto indisponível</span>
            <?php endif; ?>
            <?php if (!empty($produto['nomeCategoria'])): ?><span class="category-tag"><?= h($produto['nomeCategoria']) ?></span><?php endif; ?>
        </div>
        <section class="product-info">
            <span class="availability"><img src="../assets/loja/indicadordisponivel.svg" alt="">Disponível agora</span>
            <h1><?= h($titulo) ?></h1>
            <strong class="price"><?= reais($produto['centavos']) ?></strong>
            <p><?= h($produto['descricaoProduto'] ?? '') ?></p>
        </section>
    </div>
    <form class="purchase panel" method="post" data-preco="<?= $produto['centavos'] ?>">
        <div class="quantity">
            <label for="quantity">Quantidade</label>
            <div class="stepper">
                <button type="button" data-passo="-1" title="Diminuir quantidade" disabled><img src="../assets/loja/minus.svg" alt=""></button>
                <input id="quantity" name="quantidade" type="number" min="1" max="99" value="1" required>
                <button type="button" data-passo="1" title="Aumentar quantidade"><img src="../assets/loja/plus.svg" alt=""></button>
            </div>
        </div>
        <button class="button" type="submit"><img src="../assets/loja/bag.svg" alt="">Adicionar ao Pedido <span data-total>• <?= reais($produto['centavos']) ?></span></button>
    </form>
</main>
</body>
</html>
