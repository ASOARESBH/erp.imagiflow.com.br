<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(\App\Core\Lang::instance()->htmlLocale()); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? t('common.access_erp')); ?> | <?php echo htmlspecialchars(t('common.app_name')); ?></title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
</head>

<body>
    <div class="container-fluid d-flex justify-content-end px-3 pt-3">
        <?php require dirname(__DIR__) . '/partials/language_selector.php'; ?>
    </div>
