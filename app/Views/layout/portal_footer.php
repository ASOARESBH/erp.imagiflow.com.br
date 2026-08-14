    </main><!-- /.portal-main -->
</div><!-- /.portal-wrapper -->

<!-- Mobile Bottom Navigation -->
<nav class="portal-bottom-nav d-md-none">
    <?php
    $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    ?>
    <a href="/portal/dashboard" class="portal-bottom-item <?php echo $currentUri === '/portal/dashboard' ? 'active' : ''; ?>">
        <i class="fa fa-home"></i>
        <span><?php echo htmlspecialchars(t('portal.dashboard')); ?></span>
    </a>
    <a href="/portal/contas-a-pagar" class="portal-bottom-item <?php echo str_starts_with($currentUri, '/portal/contas-a-pagar') ? 'active' : ''; ?>">
        <i class="fa fa-file-invoice-dollar"></i>
        <span><?php echo htmlspecialchars(t('portal.accounts')); ?></span>
    </a>
    <a href="/portal/pagamentos/dashboard" class="portal-bottom-item <?php echo str_starts_with($currentUri, '/portal/pagamentos') ? 'active' : ''; ?>">
        <i class="fa fa-chart-pie"></i>
        <span><?php echo htmlspecialchars(t('portal.financial')); ?></span>
    </a>
    <a href="/portal/faturamento/notas-fiscais" class="portal-bottom-item <?php echo str_starts_with($currentUri, '/portal/faturamento') ? 'active' : ''; ?>">
        <i class="fa fa-file-alt"></i>
        <span><?php echo htmlspecialchars(t('portal.invoices')); ?></span>
    </a>
    <a href="/portal/perfil" class="portal-bottom-item <?php echo $currentUri === '/portal/perfil' ? 'active' : ''; ?>">
        <i class="fa fa-user"></i>
        <span><?php echo htmlspecialchars(t('portal.profile')); ?></span>
    </a>
</nav>

<!-- Footer info -->
<footer class="portal-footer d-none d-md-block">
    <?php
    $loginTime   = $_SESSION['portal_login_time'] ?? time();
    $elapsed     = time() - $loginTime;
    $h = floor($elapsed / 3600);
    $m = floor(($elapsed % 3600) / 60);
    $tempoSessao = $h > 0 ? "{$h}h {$m}min" : "{$m}min";
    ?>
    <span><?php echo htmlspecialchars(t('portal.portal_version')); ?></span>
    <span class="mx-2">·</span>
    <span><?php echo htmlspecialchars(t('portal.session_active_for', ['tempo' => $tempoSessao])); ?></span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/portal.js"></script>
</body>
</html>
