<header class="site-header wrapper">
    <div class="header-navigation">
        <?php if (isset($voltar)): ?>
            <a class="back-link" href="<?= h($voltar) ?>">
                <img src="../assets/loja/back.svg" alt="">
                <?= h($textoVoltar) ?>
            </a>
        <?php elseif (!$clienteId): ?>
            <a class="back-link" href="../index.html">Entrar</a>
        <?php endif; ?>

        <?php if ($clienteId): ?>
            <?php $_SESSION['logout_token'] ??= bin2hex(random_bytes(32)); ?>
            <details class="profile-menu">
                <summary class="customer-profile">
                    <img src="../assets/user.svg" width="18" height="18" alt="">
                    <?= h($primeiroNome ?: 'Cliente') ?>
                </summary>
                <form class="profile-popup" method="post" action="../action/logout.php">
                    <input type="hidden" name="token" value="<?= h($_SESSION['logout_token']) ?>">
                    <button type="submit">Sair da conta</button>
                </form>
            </details>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/brand.html'; ?>
    <?php if (empty($ocultarCarrinho)): ?>
        <a class="button cart-link" href="carrinho.php"><img src="../assets/loja/iconedocarrinho.svg" alt="">Carrinho <b><?= $contador ?></b></a>
    <?php endif; ?>
</header>
