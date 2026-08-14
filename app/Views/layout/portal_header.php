<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(\App\Core\Lang::instance()->htmlLocale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a56db">
    <title><?php echo htmlspecialchars($title ?? t('portal.title')); ?> | <?php echo htmlspecialchars(t('common.app_name')); ?></title>
    <meta name="application-name" content="ERP IMAGINIFLOW">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-imaginiflow-32.png?v=20260814">
    <link rel="icon" type="image/png" sizes="48x48" href="/assets/favicon-imaginiflow-48.png?v=20260814">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=20260814">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body class="portal-body">

<?php
$portalNome  = $_SESSION['portal_cliente_nome']  ?? t('common.user');
$portalEmail = $_SESSION['portal_cliente_email'] ?? '';
$currentUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!-- Top Navbar -->
<nav class="portal-navbar">
    <div class="portal-navbar-brand">
        <?php
        $logoPath = '/assets/logo-erp-imaginiflow.png';
        $uploadLogoDir = BASE_PATH . '/public/uploads/logo';
        if (is_dir($uploadLogoDir)) {
            $files = array_diff(scandir($uploadLogoDir), ['.', '..']);
            if (!empty($files)) {
                $logoFile = reset($files);
                $logoPath = '/uploads/logo/' . $logoFile;
            }
        }
        ?>
        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="ERP IMAGINIFLOW" class="portal-logo">
        <span class="portal-brand-text"><?php echo htmlspecialchars(t('portal.title')); ?></span>
    </div>
    <div class="portal-navbar-actions">
        <?php require dirname(__DIR__) . '/partials/language_selector.php'; ?>
        <div class="dropdown">
            <button class="portal-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="portal-user-avatar">
                    <?php echo strtoupper(substr($portalNome, 0, 1)); ?>
                </span>
                <span class="portal-user-name d-none d-md-inline">
                    <?php echo htmlspecialchars(mb_substr($portalNome, 0, 20)); ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end portal-user-menu">
                <li class="dropdown-header px-3 py-2">
                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($portalNome); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($portalEmail); ?></div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item" href="/portal/perfil">
                        <i class="fa fa-user-circle me-2 text-primary"></i> <?php echo htmlspecialchars(t('common.my_profile')); ?>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="/portal/logout" method="POST" class="m-0">
                        <?php echo \App\Core\View::csrfField(); ?>
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa fa-sign-out-alt me-2"></i> <?php echo htmlspecialchars(t('portal.logout')); ?>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar + Content wrapper -->
<div class="portal-wrapper">

    <!-- Sidebar (desktop) / Bottom Nav (mobile) -->
    <aside class="portal-sidebar" id="portalSidebar">
        <nav class="portal-sidenav">
            <a href="/portal/dashboard" class="portal-nav-item <?php echo $currentUri === '/portal/dashboard' ? 'active' : ''; ?>">
                <i class="fa fa-home"></i>
                <span><?php echo htmlspecialchars(t('portal.dashboard')); ?></span>
            </a>
            <a href="/portal/contas-a-pagar" class="portal-nav-item <?php echo str_starts_with($currentUri, '/portal/contas-a-pagar') ? 'active' : ''; ?>">
                <i class="fa fa-file-invoice-dollar"></i>
                <span><?php echo htmlspecialchars(t('portal.my_accounts')); ?></span>
            </a>
            <a href="/portal/pagamentos/dashboard" class="portal-nav-item <?php echo str_starts_with($currentUri, '/portal/pagamentos') ? 'active' : ''; ?>">
                <i class="fa fa-chart-pie"></i>
                <span><?php echo htmlspecialchars(t('portal.my_financial')); ?></span>
            </a>
            <a href="/portal/apuracoes" class="portal-nav-item <?php echo str_starts_with($currentUri, '/portal/apuracoes') ? 'active' : ''; ?>">
                <i class="fa fa-chart-bar"></i>
                <span><?php echo htmlspecialchars(t('portal.settlements')); ?></span>
            </a>
            <div class="portal-nav-group">
                <div class="portal-nav-group-label" onclick="toggleNavGroup(this)" style="cursor:pointer">
                    <i class="fa fa-handshake"></i>
                    <span><?php echo htmlspecialchars(t('portal.negotiations')); ?></span>
                    <i class="fa fa-chevron-down portal-nav-arrow ms-auto"></i>
                </div>
                <div class="portal-nav-submenu <?php echo str_starts_with($currentUri, '/portal/negociacoes') ? 'open' : ''; ?>">
                    <a href="/portal/negociacoes/propostas" class="portal-nav-subitem <?php echo str_starts_with($currentUri, '/portal/negociacoes/propostas') ? 'active' : ''; ?>">
                        <i class="fa fa-file-contract"></i>
                        <span><?php echo htmlspecialchars(t('portal.proposals')); ?></span>
                    </a>
                    <a href="/portal/negociacoes/pedidos-venda" class="portal-nav-subitem <?php echo str_starts_with($currentUri, '/portal/negociacoes/pedidos-venda') ? 'active' : ''; ?>">
                        <i class="fa fa-shopping-cart"></i>
                        <span><?php echo htmlspecialchars(t('portal.sales_orders')); ?></span>
                    </a>
                </div>
            </div>
            <div class="portal-nav-group">
                <div class="portal-nav-group-label">
                    <i class="fa fa-receipt"></i>
                    <span><?php echo htmlspecialchars(t('common.billing')); ?></span>
                    <i class="fa fa-chevron-down portal-nav-arrow ms-auto"></i>
                </div>
                <div class="portal-nav-submenu <?php echo str_starts_with($currentUri, '/portal/faturamento') ? 'open' : ''; ?>">
                    <a href="/portal/faturamento/notas-fiscais" class="portal-nav-subitem <?php echo str_starts_with($currentUri, '/portal/faturamento/notas-fiscais') ? 'active' : ''; ?>">
                        <i class="fa fa-file-alt"></i>
                        <span><?php echo htmlspecialchars(t('portal.my_invoices')); ?></span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="portal-main">
