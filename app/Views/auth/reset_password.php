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

$tokenValue = $tokenValue ?? '';
$errorMsg = '';
if (!empty($_GET['error'])) {
    if ($_GET['error'] === 'short') {
        $errorMsg = t('auth.password_minimum');
    } elseif ($_GET['error'] === 'mismatch') {
        $errorMsg = t('auth.passwords_do_not_match');
    } else {
        $errorMsg = t('auth.password_reset_failed');
    }
}
?>

<div class="login-card">
    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="ERP IMAGINIFLOW" class="logo">

    <h1><?php echo htmlspecialchars(t('auth.reset_title')); ?></h1>

    <?php if ($errorMsg): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <?php Form::start('resetForm', '/reset-password/' . htmlspecialchars($tokenValue)); ?>
    <div class="mb-3">
        <label class="form-label"><?php echo htmlspecialchars(t('auth.new_password')); ?> <span class="text-danger">*</span></label>
        <?php
        $resetPasswordToggle = sprintf(
            '<button class="btn btn-outline-secondary" type="button" data-password-toggle="password" data-show-label="%1$s" data-hide-label="%2$s" aria-label="%1$s" title="%1$s" aria-pressed="false"><i class="fa-solid fa-eye" data-password-toggle-icon aria-hidden="true"></i><span class="visually-hidden">%1$s</span></button>',
            htmlspecialchars(t('auth.show_password'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(t('auth.hide_password'), ENT_QUOTES, 'UTF-8')
        );
        Form::input('password', '', 'password', '', [
            'placeholder' => t('auth.password_minimum'),
            'required' => true,
            'class' => 'form-control',
            'autofocus' => true,
            'minlength' => 8,
            'append' => $resetPasswordToggle,
        ]);
        ?>
    </div>

    <div class="mb-4">
        <label class="form-label"><?php echo htmlspecialchars(t('auth.confirm_password')); ?> <span class="text-danger">*</span></label>
        <?php
        $resetPasswordConfirmToggle = sprintf(
            '<button class="btn btn-outline-secondary" type="button" data-password-toggle="password_confirm" data-show-label="%1$s" data-hide-label="%2$s" aria-label="%1$s" title="%1$s" aria-pressed="false"><i class="fa-solid fa-eye" data-password-toggle-icon aria-hidden="true"></i><span class="visually-hidden">%1$s</span></button>',
            htmlspecialchars(t('auth.show_password'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(t('auth.hide_password'), ENT_QUOTES, 'UTF-8')
        );
        Form::input('password_confirm', '', 'password', '', [
            'placeholder' => t('auth.password_repeat_placeholder'),
            'required' => true,
            'class' => 'form-control',
            'minlength' => 8,
            'append' => $resetPasswordConfirmToggle,
        ]);
        ?>
    </div>

    <?php Form::button(t('auth.reset_password'), 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <a href="/login" class="forgot-password"><?php echo htmlspecialchars(t('auth.back_to_login')); ?></a>

    <p class="login-footer">© <?php echo date('Y'); ?> <?php echo htmlspecialchars(t('common.app_name')); ?>. <?php echo htmlspecialchars(t('common.copyright')); ?></p>
</div>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
