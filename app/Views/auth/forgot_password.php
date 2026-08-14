<?php
use App\Core\Form;
require_once dirname(__DIR__) . '/layout/public_header.php';

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

<div class="login-card">
    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="ERP IMAGINIFLOW" class="logo">

    <h1><?php echo htmlspecialchars(t('auth.forgot_title')); ?></h1>

    <?php if (!empty($sent)): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars(t('auth.reset_instructions_sent')); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars(t('auth.valid_email_required')); ?>
        </div>
    <?php endif; ?>

    <?php Form::start('forgotForm', '/forgot-password'); ?>
    <div class="mb-4">
        <label class="form-label"><?php echo htmlspecialchars(t('auth.email')); ?> <span class="text-danger">*</span></label>
        <?php Form::input('email', '', 'email', '', [
            'placeholder' => t('auth.email_placeholder'),
            'required' => true,
            'class' => 'form-control',
            'autofocus' => true
        ]); ?>
    </div>

    <?php Form::button(t('auth.send_reset_link'), 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <a href="/login" class="forgot-password"><?php echo htmlspecialchars(t('auth.back_to_login')); ?></a>

    <p class="login-footer">© <?php echo date('Y'); ?> <?php echo htmlspecialchars(t('common.app_name')); ?>. <?php echo htmlspecialchars(t('common.copyright')); ?></p>
</div>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
