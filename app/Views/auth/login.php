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
    <h1><?php echo htmlspecialchars(t('common.app_name')); ?></h1>

    <?php if (!empty($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars(t('auth.password_reset_success')); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['reset']) && $_GET['reset'] === 'invalid'): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars(t('auth.reset_invalid')); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <?php $erros = [
            '1'               => t('auth.login_invalid'),
            'credenciais'     => t('auth.login_invalid'),
            'conta_inativa'   => t('auth.account_inactive'),
            'sessao_expirada' => t('auth.session_expired'),
        ]; ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($erros[$_GET['error']] ?? t('auth.login_invalid')); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['logout'])): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars(t('auth.logged_out')); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['primeiro_acesso']) && $_GET['primeiro_acesso'] === 'ok'): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars(t('auth.password_created')); ?>
        </div>
    <?php endif; ?>

    <?php Form::start('loginForm', '/login'); ?>
    <div class="mb-3">
        <label class="form-label"><?php echo htmlspecialchars(t('auth.email')); ?> <span class="text-danger">*</span></label>
        <?php Form::input('email', '', 'email', '', [
            'placeholder'  => t('auth.email_placeholder'),
            'required'     => true,
            'class'        => 'form-control',
            'autofocus'    => true,
            'autocomplete' => 'email',
        ]); ?>
    </div>
    <div class="mb-4">
        <label class="form-label"><?php echo htmlspecialchars(t('auth.password')); ?> <span class="text-danger">*</span></label>
        <div class="position-relative">
            <?php Form::input('password', '', 'password', '', [
                'id'           => 'loginPassword',
                'placeholder'  => '••••••••',
                'required'     => true,
                'class'        => 'form-control pe-5',
                'autocomplete' => 'current-password',
            ]); ?>
            <button type="button"
                    onclick="toggleLoginSenha()"
                    tabindex="-1"
                    style="position:absolute;top:50%;right:.75rem;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;">
                <i class="fa fa-eye" id="loginEyeIcon"></i>
            </button>
        </div>
    </div>
    <?php Form::button(t('auth.sign_in'), 'submit', 'btn btn-primary'); ?>
    <?php Form::end(); ?>

    <!-- Links de suporte abaixo do botão -->
    <div class="login-links-group">
        <a href="/forgot-password" class="forgot-password"><?php echo htmlspecialchars(t('auth.forgot_password')); ?></a>
        <a href="/primeiro-acesso" class="forgot-password primeiro-acesso-link">
            <i class="fa fa-user-plus" style="margin-right:.3rem;"></i><?php echo htmlspecialchars(t('auth.first_access')); ?>
        </a>
    </div>

    <p class="login-footer">© <?php echo date('Y'); ?> <?php echo htmlspecialchars(t('common.app_name')); ?>. <?php echo htmlspecialchars(t('common.copyright')); ?></p>
</div>

<script>
function toggleLoginSenha() {
    // O Form::input gera id="password" (igual ao name), ignorando o atributo id passado
    var input = document.getElementById('password');
    var icon  = document.getElementById('loginEyeIcon');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
    // Mantém o foco no campo após o clique
    input.focus();
}
</script>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
