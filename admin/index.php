<?php
date_default_timezone_set('America/Sao_Paulo');
session_name('FoodstockAdmin');
session_start();
header('Cache-Control: no-store');
require __DIR__ . '/data.php';

$view = $_GET['view'] ?? 'overview';
if (!in_array($view, ['overview', 'orders', 'menu'], true)) {
    $view = 'overview';
}

$action = $_POST['action'] ?? '';
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$databaseReady = true;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require __DIR__ . '/../bd.php';
    $conn->set_charset('utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$_POST && !empty($_SERVER['CONTENT_LENGTH'])) {
            throw new InvalidArgumentException('O arquivo ultrapassa o limite de envio do servidor.');
        }
        if ($action === 'update-order') {
            $order = filter_input(INPUT_POST, 'order', FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? '';
            if (!$order || !in_array($status, ['Concluido', 'Cancelado'], true)) {
                throw new InvalidArgumentException('Pedido ou estado inválido.');
            }
            $save = $conn->prepare("UPDATE pedido SET statusPedido = ? WHERE idPedido = ? AND statusPedido = 'Preparando'");
            $save->bind_param('si', $status, $order);
            $save->execute();
            $_SESSION['message'] = $save->affected_rows ? "Pedido #$order atualizado." : "O pedido #$order já foi atualizado.";
        } elseif ($action === 'add-category') {
            $view = 'menu';
            $name = trim((string)($_POST['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                throw new InvalidArgumentException('Informe um nome de categoria de até 100 caracteres.');
            }
            $save = $conn->prepare('INSERT INTO categoria (nomeCategoria) VALUES (?)');
            $save->bind_param('s', $name);
            $save->execute();
            $_SESSION['message'] = 'Categoria criada.';
        } elseif ($action === 'add-product') {
            $name = trim((string)($_POST['name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $category = filter_input(INPUT_POST, 'category', FILTER_VALIDATE_INT);
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            if ($name === '' || mb_strlen($name) > 100 || $description === '' || !$category || $price === false || $price === null || $price < 0.01 || $price > 999.99) {
                throw new InvalidArgumentException('Preencha nome, descrição, categoria e um preço de R$ 0,01 a R$ 999,99.');
            }
            $amount = number_format($price, 2, '.', '');
            $save = $conn->prepare('INSERT INTO produto (nomeProduto, descricaoProduto, precoProduto, caminhoImagemProduto, categoriaProduto_FK) VALUES (?, ?, ?, ?, ?)');
            $image = saveProductImage($_FILES['image'] ?? []);
            try {
                $save->bind_param('ssssi', $name, $description, $amount, $image, $category);
                $save->execute();
            } catch (mysqli_sql_exception $failure) {
                if ($image !== '') {
                    unlink(dirname(__DIR__) . '/' . $image);
                }
                throw $failure;
            }
            $_SESSION['message'] = 'Prato adicionado ao cardápio.';
            $view = 'menu';
        }
        header('Location: index.php?view=' . $view, true, 303);
        exit;
    }

    $preparingCount = (int)$conn->query("SELECT COUNT(*) FROM pedido WHERE statusPedido = 'Preparando'")->fetch_row()[0];
    $categories = $conn->query('SELECT idCategoria, nomeCategoria FROM categoria ORDER BY nomeCategoria')->fetch_all(MYSQLI_ASSOC);
    if ($view === 'menu') {
        $products = $conn->query('SELECT p.idProduto, p.nomeProduto, p.precoProduto, p.statusProduto, c.nomeCategoria FROM produto p JOIN categoria c ON c.idCategoria = p.categoriaProduto_FK ORDER BY c.nomeCategoria, p.nomeProduto')->fetch_all(MYSQLI_ASSOC);
    } else {
        $orders = recentOrders($conn);
        if ($view === 'overview') {
            $dashboard = dashboardData($conn);
            $today = $dashboard['today'];
            $revenue = (float)$today['total'];
            $average = $today['concluidos'] ? $revenue / $today['concluidos'] : 0;
            $variation = $dashboard['yesterday'] > 0 ? ($revenue / $dashboard['yesterday'] - 1) * 100 : null;
        }
    }
} catch (InvalidArgumentException $failure) {
    $_SESSION['error'] = $failure->getMessage();
    header('Location: index.php?view=' . $view, true, 303);
    exit;
} catch (mysqli_sql_exception $failure) {
    error_log('Painel Foodstock: ' . $failure->getMessage());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['error'] = $action === 'add-category' && $failure->getCode() === 1062
            ? 'Já existe uma categoria com esse nome.'
            : 'Não foi possível salvar a alteração. Confira os dados e tente novamente.';
        header('Location: index.php?view=' . $view, true, 303);
        exit;
    }
    $databaseReady = false;
}

$titles = ['overview' => 'Visão Geral', 'orders' => 'Pedidos', 'menu' => 'Cardápio'];
$statuses = ['Preparando' => ['Em preparo', 'preparing'], 'Concluido' => ['Concluído', 'completed'], 'Cancelado' => ['Cancelado', 'canceled']];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titles[$view] ?> | Chapa Quente</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">
    <link rel="stylesheet" href="dashboard.css?v=<?= filemtime(__DIR__ . '/dashboard.css') ?>">
    <script src="dashboard.js?v=<?= filemtime(__DIR__ . '/dashboard.js') ?>" defer></script>
    <?php if ($view === 'overview'): ?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js" async></script><?php endif; ?>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="?view=overview"><img src="../assets/logo.svg" width="36" height="36" alt=""><span>Chapa <b>Quente</b></span></a>
        <nav>
            <a class="<?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview"><span class="nav-icon">▦</span>Visão Geral</a>
            <a class="<?= $view === 'orders' ? 'active' : '' ?>" href="?view=orders"><span class="nav-icon">▤</span>Pedidos <?php if (!empty($preparingCount)): ?><b class="count"><?= $preparingCount ?></b><?php endif; ?></a>
            <a class="<?= $view === 'menu' ? 'active' : '' ?>" href="?view=menu"><span class="nav-icon">☰</span>Cardápio</a>
        </nav>
    </aside>
    <div class="shell">
        <header class="topbar">
            <button type="button" class="icon-button mobile-menu" id="menu-button" title="Abrir ou fechar menu">☰</button>
            <strong class="page-name"><?= $titles[$view] ?></strong>
            <?php if ($databaseReady && $view !== 'menu'): ?>
                <input class="search" id="order-search" type="search" placeholder="Buscar por #ID ou cliente" title="Buscar nos pedidos exibidos">
            <?php endif; ?>
            <a class="icon-button notification" href="?view=orders&status=Preparando" title="Ver pedidos em preparo">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4"/></svg>
                <?php if (!empty($preparingCount)): ?><span class="notification-dot"></span><?php endif; ?>
            </a>
        </header>
        <main class="content">
            <div class="page-intro">
                <div>
                    <h1><?= $view === 'overview' ? 'Olá, administrador!' : $titles[$view] ?></h1>
                    <?php if ($view === 'overview'): ?><p class="muted"><?= (new DateTimeImmutable())->format('d/m/Y') ?></p><?php endif; ?>
                </div>
                <div class="page-actions">
                    <?php if ($databaseReady && $view === 'menu'): ?>
                        <button class="quiet" id="add-category" type="button">+ Nova Categoria</button>
                    <?php endif; ?>
                    <?php if (!empty($categories)): ?>
                        <button class="primary" id="add-product" type="button">+ Novo Prato</button>
                    <?php endif; ?>
                    <!-- card -->
                </div>
            </div>
            <?php if ($message): ?><p class="notice"><?= escape($message) ?></p><?php endif; ?>
            <?php if ($error): ?><p class="notice error"><?= escape($error) ?></p><?php endif; ?>
            <?php if (!$databaseReady): ?>
                <section class="panel"><p>O banco está indisponível. Tente novamente em instantes.</p></section>
            <?php elseif ($view === 'menu'): ?>
                <section class="panel">
                    <h2>Categorias cadastradas</h2>
                    <?php if ($categories): ?>
                        <ul class="category-list">
                            <?php foreach ($categories as $category): ?>
                                <li><?= escape($category['nomeCategoria']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted">Crie uma categoria para começar a cadastrar pratos.</p>
                    <?php endif; ?>
                </section>
                <section class="panel table-panel">
                    <h2>Produtos cadastrados</h2>
                    <?php if (!$products): ?><p class="empty">Nenhum produto cadastrado.</p><?php endif; ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Prato</th><th>Categoria</th><th>Preço</th><th>Situação</th></tr></thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td data-label="Prato"><?= escape($product['nomeProduto']) ?></td>
                                        <td data-label="Categoria"><?= escape($product['nomeCategoria']) ?></td>
                                        <td data-label="Preço"><?= money($product['precoProduto']) ?></td>
                                        <td data-label="Situação"><?= $product['statusProduto'] === 'Disponivel' ? 'Disponível' : 'Indisponível' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <!-- visão geral -->
            <?php else: ?>
                <?php if ($view === 'overview'): ?>
                    <section class="metrics">
                        <article class="panel metric"><span>Valor concluído hoje</span><strong><?= money($revenue) ?></strong>
                            <small class="<?= $variation !== null && $variation > 0 ? 'gold-text' : 'muted' ?>">
                                <?= $variation === null ? 'Sem base de comparação ontem' : ($variation > 0 ? '+' : '') . number_format($variation, 1, ',', '.') . '% em relação a ontem' ?>
                            </small>
                        </article>
                        <article class="panel metric"><span>Pedidos recebidos hoje</span><strong><?= (int)$today['pedidos'] ?></strong><small class="muted"></small></article>
                        <article class="panel metric"><span>Ganho médio de hoje</span><strong><?= money($average) ?></strong><small class="muted">Pedidos concluídos</small></article>
                        <article class="panel metric"><span>Pedidos em preparo</span><strong><?= $preparingCount ?></strong><small class="muted">Aguardando conclusão</small></article>
                    </section>
                    <div class="charts">
                        <section class="panel">
                            <h2>Vendas da Semana</h2><p class="muted">Valor dos pedidos concluídos nos últimos 7 dias</p>
                            <?php $peak = max(array_column($dashboard['sales'], 'total')); ?>
                            <div class="chart" id="sales-chart">
                                <?php if ($peak > 0): ?>
                                    <canvas id="sales" hidden data-series="<?= escape(json_encode($dashboard['sales'])) ?>"></canvas>
                                <?php else: ?>
                                    <p class="chart-empty">Nenhum pedido concluído nesse período.</p>
                                <?php endif; ?>
                                <div id="fallback-chart" class="bars">
                                    <?php foreach ($dashboard['sales'] as $day): ?>
                                        <div title="<?= escape($day['label'] . ' · ' . money($day['total'])) ?>">
                                            <div class="bar" style="height: <?= round($day['total'] / max(1, $peak) * 128) ?>px"></div>
                                            <span><?= escape($day['label']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                        <section class="panel">
                            <h2>Pratos Mais Pedidos</h2><p class="muted">Últimos 7 dias, sem cancelamentos</p>
                            <?php if (!$dashboard['top']): ?><p class="empty">Nenhum prato pedido nesse período.</p><?php endif; ?>
                            <ul class="ranking">
                                <?php foreach ($dashboard['top'] as $dish): ?>
                                    <li><div><strong><?= escape($dish['nomeProduto']) ?></strong><span><?= (int)$dish['quantidade'] ?> un.</span></div>
                                        <progress value="<?= (int)$dish['quantidade'] ?>" max="<?= (int)$dashboard['top'][0]['quantidade'] ?>"></progress>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                      <!-- Pedidos -->
                    </div>
                <?php endif; ?>
                <section class="panel table-panel" id="orders">
                    <div class="table-title"><h2><?= $view === 'overview' ? 'Pedidos Recentes' : 'Últimos 100 pedidos' ?></h2><a class="quiet" href="?view=<?= $view ?>#orders">Atualizar</a></div>
                    <div class="filters" id="order-filters">
                        <button type="button" class="active" data-filter="">Todos</button>
                        <?php foreach ($statuses as $key => [$label, $tone]): ?>
                            <button type="button" data-filter="<?= $key ?>"><?= $label ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Pedido</th><th>Cliente</th><th>Itens</th><th>Total</th><th>Estado</th><th>Ações</th></tr></thead>
                            <tbody id="order-rows">
                                <?php foreach ($orders as $order): ?>
                                    <?php [$label, $tone] = $statuses[$order['statusPedido']] ?? ['Sem estado', '']; ?>
                                    <tr data-status="<?= escape($order['statusPedido']) ?>" data-search="<?= escape('#' . $order['idPedido'] . ' ' . $order['nomeCliente']) ?>">
                                        <td data-label="Pedido"><strong class="gold-text">#<?= (int)$order['idPedido'] ?></strong><small><?= date('d/m H:i', strtotime($order['dataPedido'])) ?></small></td>
                                        <td data-label="Cliente"><?= escape($order['nomeCliente']) ?></td>
                                        <td data-label="Itens" class="muted"><?= escape($order['itens'] ?: '—') ?></td>
                                        <td data-label="Total" class="total"><?= money($order['totalPedido']) ?></td>
                                        <td data-label="Estado"><span class="status <?= $tone ?>"><?= $label ?></span></td>
                                        <?php if ($order['statusPedido'] === 'Preparando'): ?>
                                            <td data-label="Ações">
                                                <form method="post" class="order-actions">
                                                    <input type="hidden" name="action" value="update-order">
                                                    <input type="hidden" name="order" value="<?= (int)$order['idPedido'] ?>">
                                                    <button class="primary" name="status" value="Concluido">Concluir</button>
                                                    <button class="quiet danger" name="status" value="Cancelado">Cancelar</button>
                                                </form>
                                            </td>
                                        <?php else: ?>
                                            <td data-label="Ações">—</td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="empty" id="empty-orders" <?= $orders ? 'hidden' : '' ?>>Nenhum pedido encontrado.</p>
                </section>
            <?php endif; ?>
        </main>
        <!-- f p -->
    </div>
    <?php if ($databaseReady && $view === 'menu'): ?>
        <?php require __DIR__ . '/category-form.html'; ?>
    <?php endif; ?>
    <?php if (!empty($categories)): ?>
        <dialog id="product-dialog">
            <div class="table-title"><h2>Novo Prato</h2><button type="button" class="quiet" id="close-product">Fechar</button></div>
            <form method="post" enctype="multipart/form-data">
                <label>Nome<input name="name" maxlength="100" required></label>
                <label>Descrição<textarea name="description" rows="3" required></textarea></label>
                <label>Preço em R$<input type="number" name="price" min="0.01" max="999.99" step="0.01" required></label>
                <label>Categoria<select name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['idCategoria'] ?>"><?= escape($category['nomeCategoria']) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Imagem do prato
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG ou WebP, até 2 MB.</small>
                </label>
                <button class="primary" name="action" value="add-product">Adicionar Prato</button>
            </form>
        </dialog>
    <?php endif; ?>
</body>
</html>
