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
<body class="portal-public-body">
    <div class="container-fluid d-flex justify-content-end px-3 pt-3">
        <?php require dirname(__DIR__) . '/partials/language_selector.php'; ?>
    </div>
