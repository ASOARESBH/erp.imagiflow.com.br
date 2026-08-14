<?php
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
    <h1><?php echo htmlspecialchars(t('auth.first_access_title')); ?></h1>
    <p style="color:#6b7280;font-size:.9rem;margin-bottom:1.5rem;">
        <?php echo htmlspecialchars(t('auth.first_access_text')); ?>
    </p>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($erro); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <?php $erros = [
            'email_nao_encontrado' => t('auth.email_not_found'),
            'token_invalido'       => t('auth.first_access_invalid'),
            'senhas_diferentes'    => t('auth.passwords_do_not_match'),
            'senha_curta'          => t('auth.password_minimum'),
            'salvar_falhou'        => t('auth.first_access_save_failed'),
        ]; ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            <?php echo htmlspecialchars($erros[$_GET['error']] ?? t('auth.unexpected_error')); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($token)): ?>
        <!-- ETAPA 1: Informar e-mail -->
        <form method="POST" action="/primeiro-acesso">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div class="mb-4">
                <label class="form-label"><?php echo htmlspecialchars(t('auth.primary_email')); ?> <span class="text-danger">*</span></label>
                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="<?php echo htmlspecialchars(t('auth.email_placeholder')); ?>"
                       required
                       autofocus
                       autocomplete="email"
                       value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('auth.continue')); ?></button>
        </form>

    <?php else: ?>
        <!-- ETAPA 2: Criar senha (token válido) -->
        <p style="color:#374151;font-size:.85rem;margin-bottom:1.25rem;">
            <?php echo htmlspecialchars(t('auth.greeting', ['nome' => $nomeCliente ?? t('common.user')])); ?>
            <?php echo htmlspecialchars(t('auth.create_password_portal')); ?>
        </p>
        <form method="POST" action="/primeiro-acesso/salvar" id="formCriarSenha">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label class="form-label"><?php echo htmlspecialchars(t('auth.new_password')); ?> <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password"
                           id="novaSenha"
                           name="senha"
                           class="form-control pe-5"
                           placeholder="<?php echo htmlspecialchars(t('auth.password_minimum')); ?>"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           oninput="paCheckSenha()">
                    <button type="button" onclick="paToggle('novaSenha','paEye1')" tabindex="-1"
                            style="position:absolute;top:50%;right:.75rem;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;">
                        <i class="fa fa-eye" id="paEye1"></i>
                    </button>
                </div>
                <!-- Indicador de força -->
                <div style="margin-top:.4rem;">
                    <div style="height:4px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                        <div id="paStrengthFill" style="height:100%;width:0;background:#e02424;transition:width .3s,background .3s;border-radius:4px;"></div>
                    </div>
                    <span id="paStrengthLabel" style="font-size:.75rem;color:#6b7280;"></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label"><?php echo htmlspecialchars(t('auth.confirm_password')); ?> <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password"
                           id="confirmarSenha"
                           name="senha_confirmacao"
                           class="form-control pe-5"
                           placeholder="<?php echo htmlspecialchars(t('auth.password_repeat_placeholder')); ?>"
                           required
                           autocomplete="new-password"
                           oninput="paCheckConfirm()">
                    <button type="button" onclick="paToggle('confirmarSenha','paEye2')" tabindex="-1"
                            style="position:absolute;top:50%;right:.75rem;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;padding:0;">
                        <i class="fa fa-eye" id="paEye2"></i>
                    </button>
                </div>
                <span id="paMatchMsg" style="font-size:.78rem;display:none;"></span>
            </div>
            <button type="submit" class="btn btn-primary" id="paBtnSalvar"><?php echo htmlspecialchars(t('auth.create_password_and_sign_in')); ?></button>
        </form>
    <?php endif; ?>

    <div class="login-links-group" style="margin-top:1.25rem;">
        <a href="/login" class="forgot-password">
            <i class="fa fa-arrow-left" style="margin-right:.3rem;"></i><?php echo htmlspecialchars(t('auth.back_to_login')); ?>
        </a>
    </div>

    <p class="login-footer">© <?php echo date('Y'); ?> <?php echo htmlspecialchars(t('common.app_name')); ?>. <?php echo htmlspecialchars(t('common.copyright')); ?></p>
</div>

<script>
const I18N = <?php echo json_encode([
    'veryWeak' => t('auth.password_strength_very_weak'),
    'weak' => t('auth.password_strength_weak'),
    'fair' => t('auth.password_strength_fair'),
    'strong' => t('auth.password_strength_strong'),
    'veryStrong' => t('auth.password_strength_very_strong'),
    'passwordsMatch' => t('auth.passwords_match'),
    'passwordsDoNotMatch' => t('auth.passwords_do_not_match'),
    'passwordMinimum' => t('auth.password_minimum'),
    'saving' => t('auth.saving'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function paToggle(fieldId, iconId) {
    var input = document.getElementById(fieldId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}

function paCheckSenha() {
    var v = document.getElementById('novaSenha').value;
    var fill  = document.getElementById('paStrengthFill');
    var label = document.getElementById('paStrengthLabel');
    if (!fill) return;
    var score = 0;
    if (v.length >= 8)  score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var levels = [
        {pct:'20%',color:'#e02424',text:I18N.veryWeak},
        {pct:'40%',color:'#d97706',text:I18N.weak},
        {pct:'60%',color:'#f59e0b',text:I18N.fair},
        {pct:'80%',color:'#0e9f6e',text:I18N.strong},
        {pct:'100%',color:'#065f46',text:I18N.veryStrong},
    ];
    var lvl = levels[Math.max(0, score - 1)];
    fill.style.width = v.length > 0 ? lvl.pct : '0';
    fill.style.background = lvl.color;
    label.textContent = v.length > 0 ? lvl.text : '';
    label.style.color = lvl.color;
}

function paCheckConfirm() {
    var s = document.getElementById('novaSenha').value;
    var c = document.getElementById('confirmarSenha').value;
    var msg = document.getElementById('paMatchMsg');
    if (!msg) return;
    if (c.length === 0) { msg.style.display = 'none'; return; }
    msg.style.display = 'inline';
    if (s === c) {
        msg.textContent = '✓ ' + I18N.passwordsMatch;
        msg.style.color = '#0e9f6e';
    } else {
        msg.textContent = '✗ ' + I18N.passwordsDoNotMatch;
        msg.style.color = '#e02424';
    }
}

var formCriarSenha = document.getElementById('formCriarSenha');
if (formCriarSenha) {
    formCriarSenha.addEventListener('submit', function(e) {
        var s = document.getElementById('novaSenha').value;
        var c = document.getElementById('confirmarSenha').value;
        if (s.length < 8) {
            e.preventDefault();
            alert(I18N.passwordMinimum);
            return;
        }
        if (s !== c) {
            e.preventDefault();
            alert(I18N.passwordsDoNotMatch);
            return;
        }
        var btn = document.getElementById('paBtnSalvar');
        if (btn) { btn.disabled = true; btn.textContent = I18N.saving; }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
