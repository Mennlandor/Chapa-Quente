<?php
require __DIR__ . '/loja.php';
$busca = trim((string)($_GET['busca'] ?? ''));
$categoria = (string)($_GET['categoria'] ?? '');
$categorias = array_unique(array_filter(array_column($produtos, 'nomeCategoria')));
$produtosDaCategoria = array_filter($produtos, function ($produto) use ($categoria) {
    return $categoria === '' || $produto['nomeCategoria'] === $categoria;
});
$visiveis = array_filter($produtosDaCategoria, function ($produto) use ($busca) {
    return $busca === '' || stripos($produto['nomeProduto'], $busca) !== false;
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cardápio | Chapa Quente</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Poppins:wght@800&display=swap">
    <link rel="stylesheet" href="loja.css?v=<?= filemtime(__DIR__ . '/loja.css') ?>">
    <script src="loja.js?v=<?= filemtime(__DIR__ . '/loja.js') ?>" defer></script>
</head>
<body>
<?php require __DIR__ . '/header.php'; ?>
<main class="wrapper">
    <section class="hero">
        <div>
            <h1>Fome de quê?<br><span>Chega voando!</span></h1>
            <p>Dos smash burgers às pizzas. Peça seus favoritos.</p>
            <a class="button" href="#products">Pedir Agora <img src="../assets/arrow.svg" alt=""></a>
        </div>
    </section>
    <section class="menu-section" id="products">
        <div class="menu-toolbar">
            <h2>Categorias</h2>
            <form class="search" method="get" action="menu.php#products">
                <?php if ($categoria !== ''): ?><input type="hidden" name="categoria" value="<?= h($categoria) ?>"><?php endif; ?>
                <div class="search-field">
                    <input type="search" autocomplete="off" name="busca" title="Buscar prato" placeholder="Busque por prato" value="<?= h($busca) ?>">
                    <ul class="search-suggestions" id="search-suggestions" hidden></ul>
                </div>
                <button class="button" type="submit">Buscar</button>
            </form>
        </div>
        <nav class="categories">
            <?php foreach (array_merge([''], $categorias) as $nome): ?>
                <a class="pill <?= $nome === $categoria ? 'active' : '' ?>" href="menu.php?<?= h(http_build_query(['categoria' => $nome, 'busca' => $busca])) ?>#products"><?= h($nome ?: 'Todos') ?></a>
            <?php endforeach; ?>
        </nav>
        <h2>Cardápio</h2>
        <?php if ($catalogoIndisponivel): ?>
            <p class="muted">Cardápio indisponível no momento.</p>
        <?php else: ?>
            <p class="muted" id="search-empty" <?= $visiveis ? 'hidden' : '' ?>>Nenhum prato encontrado.</p>
        <?php endif; ?>
        <div class="product-grid">
            <?php foreach ($produtosDaCategoria as $id => $produto): ?>
                <a class="product-card" href="produto.php?id=<?= (int)$produto['idProduto'] ?>" <?= isset($visiveis[$id]) ? '' : 'hidden' ?>>
                    <div class="product-card-image">
                        <?php if ($imagem = foto($produto)): ?><img src="<?= h($imagem) ?>" alt="" loading="lazy"><?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <h3><?= h($produto['nomeProduto']) ?></h3>
                        <p><?= h($produto['descricaoProduto'] ?? '') ?></p>
                        <div><strong class="price"><?= reais($produto['centavos']) ?></strong><span class="add-icon"><img src="../assets/loja/plus.svg" width="18" height="18" alt=""></span></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
