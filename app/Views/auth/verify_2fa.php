<?php
require_once dirname(__DIR__) . '/layout/public_header.php';
$logoPath = '/assets/logo-inlaudo.png';
$uploadLogoDir = BASE_PATH . '/public/uploads/logo';
if (is_dir($uploadLogoDir)) {
    $files = array_diff(scandir($uploadLogoDir), ['.', '..']);
    if (!empty($files)) {
        $logoFile = reset($files);
        $logoPath = '/uploads/logo/' . $logoFile;
    }
}
$bloqueado        = $bloqueado        ?? false;
$segundosBloqueio = $segundosBloqueio ?? 0;
$segundosReenvio  = $segundosReenvio  ?? 0;
?>
<div class="login-card">
    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="InLaudo" class="logo">
    <h1><i class="fa fa-shield-alt" style="font-size:.85em;margin-right:.3rem;color:#00529B"></i>Verificação em Dois Fatores</h1>

    <?php if ($bloqueado): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 px-3 mb-3 rounded-3">
            Muitas tentativas incorretas. Sua verificação está temporariamente bloqueada.
            Aguarde alguns minutos e tente novamente.
        </div>
        <a href="/login" class="forgot-password">Voltar ao login</a>
    <?php else: ?>

        <p style="color:#64748b;font-size:.9rem;margin-bottom:1.25rem">
            Enviamos um código de segurança para:<br>
            <strong><?php echo htmlspecialchars($emailMascarado); ?></strong>
        </p>

        <div id="alert2fa"></div>

        <form id="form2fa" onsubmit="return false;">
            <label class="form-label">Código</label>
            <div class="codigo-2fa-wrap" id="codigoWrap">
                <input type="text" inputmode="numeric" maxlength="1" class="codigo-digit" data-idx="0" autofocus>
                <input type="text" inputmode="numeric" maxlength="1" class="codigo-digit" data-idx="1">
                <input type="text" inputmode="numeric" maxlength="1" class="codigo-digit" data-idx="2">
                <input type="text" inputmode="numeric" maxlength="1" class="codigo-digit" data-idx="3">
            </div>

            <button type="button" class="btn btn-primary w-100 mt-3" id="btnVerificar">
                <span id="btnVerificarTexto">Verificar Código</span>
            </button>
        </form>

        <div class="text-center mt-3" style="font-size:.85rem">
            <span id="cooldownWrap" style="color:#94a3b8;<?php echo $segundosReenvio <= 0 ? 'display:none' : ''; ?>">
                Reenviar em: <strong id="cooldownTimer">00:<?php echo str_pad((string) $segundosReenvio, 2, '0', STR_PAD_LEFT); ?></strong>
            </span>
            <button type="button" id="btnReenviar" class="btn btn-link p-0" style="font-size:.85rem;<?php echo $segundosReenvio > 0 ? 'display:none' : ''; ?>">
                Reenviar Código
            </button>
            <div id="reenviarStatus" style="margin-top:.5rem;color:#64748b"></div>
        </div>

        <a href="/login" class="forgot-password">Cancelar e voltar ao login</a>
    <?php endif; ?>

    <p class="login-footer">© <?php echo date('Y'); ?> InLaudo. Todos os direitos reservados.</p>
</div>

<style>
.codigo-2fa-wrap{display:flex;gap:.6rem;justify-content:center;margin-top:.4rem}
.codigo-digit{width:52px;height:60px;text-align:center;font-size:1.6rem;font-weight:700;border:1px solid #cbd5e1;border-radius:.5rem;color:#1e293b}
.codigo-digit:focus{outline:none;border-color:#00529B;box-shadow:0 0 0 3px rgba(0,82,155,.15)}
</style>

<?php if (!$bloqueado): ?>
<script>
(function () {
    let segundosRestantes = <?php echo (int) $segundosReenvio; ?>;
    let cooldownInterval = null;

    const digitInputs   = Array.from(document.querySelectorAll('.codigo-digit'));
    const btnVerificar   = document.getElementById('btnVerificar');
    const btnVerificarTexto = document.getElementById('btnVerificarTexto');
    const btnReenviar    = document.getElementById('btnReenviar');
    const cooldownWrap   = document.getElementById('cooldownWrap');
    const cooldownTimer  = document.getElementById('cooldownTimer');
    const reenviarStatus = document.getElementById('reenviarStatus');
    const alertBox       = document.getElementById('alert2fa');

    function showAlert(msg, type) {
        alertBox.innerHTML = '<div class="alert alert-' + (type === 'error' ? 'danger' : 'success') +
            ' border-0 shadow-sm py-2 px-3 mb-3 rounded-3">' + msg + '</div>';
    }

    function codigoAtual() {
        return digitInputs.map(i => i.value).join('');
    }

    digitInputs.forEach((input, idx) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            if (this.value && idx < digitInputs.length - 1) {
                digitInputs[idx + 1].focus();
            }
            if (codigoAtual().length === 4) {
                verificarCodigo();
            }
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                digitInputs[idx - 1].focus();
            }
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const texto = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
            texto.split('').forEach((d, i) => { if (digitInputs[i]) digitInputs[i].value = d; });
            if (texto.length === 4) verificarCodigo();
        });
    });

    async function verificarCodigo() {
        const codigo = codigoAtual();
        if (codigo.length !== 4) {
            showAlert('Informe os 4 dígitos do código.', 'error');
            return;
        }

        btnVerificar.disabled = true;
        btnVerificarTexto.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verificando...';

        try {
            const fd = new FormData();
            fd.append('codigo', codigo);
            const resp = await fetch('/2fa/verify', { method: 'POST', body: fd });
            const data = await resp.json();

            if (data.success) {
                showAlert('Código verificado com sucesso! Redirecionando...', 'success');
                window.location.href = data.redirect || '/dashboard';
                return;
            }

            showAlert(data.message || 'Código inválido.', 'error');
            digitInputs.forEach(i => i.value = '');
            digitInputs[0].focus();

            if (data.error === 'locked') {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (e) {
            showAlert('Erro de comunicação. Tente novamente.', 'error');
        } finally {
            btnVerificar.disabled = false;
            btnVerificarTexto.textContent = 'Verificar Código';
        }
    }

    btnVerificar.addEventListener('click', verificarCodigo);

    function iniciarCooldown(segundos) {
        segundosRestantes = segundos;
        cooldownWrap.style.display = 'inline';
        btnReenviar.style.display = 'none';
        clearInterval(cooldownInterval);
        atualizarCooldown();
        cooldownInterval = setInterval(() => {
            segundosRestantes--;
            atualizarCooldown();
            if (segundosRestantes <= 0) {
                clearInterval(cooldownInterval);
                cooldownWrap.style.display = 'none';
                btnReenviar.style.display = 'inline';
            }
        }, 1000);
    }

    function atualizarCooldown() {
        const m = Math.floor(segundosRestantes / 60).toString().padStart(2, '0');
        const s = Math.max(0, segundosRestantes % 60).toString().padStart(2, '0');
        cooldownTimer.textContent = m + ':' + s;
    }

    btnReenviar.addEventListener('click', async function () {
        btnReenviar.disabled = true;
        reenviarStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando código...';

        try {
            const resp = await fetch('/2fa/resend', { method: 'POST' });
            const data = await resp.json();

            if (data.success) {
                reenviarStatus.textContent = 'Código enviado com sucesso.';
                iniciarCooldown(60);
                digitInputs.forEach(i => i.value = '');
                digitInputs[0].focus();
            } else if (data.error === 'cooldown') {
                iniciarCooldown(data.seconds_left || 60);
                reenviarStatus.textContent = '';
            } else if (data.error === 'locked') {
                showAlert(data.message, 'error');
                reenviarStatus.textContent = '';
            } else {
                reenviarStatus.textContent = data.message || 'Não foi possível enviar o código. Tente novamente.';
            }
        } catch (e) {
            reenviarStatus.textContent = 'Não foi possível enviar o código. Tente novamente.';
        } finally {
            btnReenviar.disabled = false;
            setTimeout(() => { if (cooldownWrap.style.display === 'none') reenviarStatus.textContent = ''; }, 4000);
        }
    });

    if (segundosRestantes > 0) {
        iniciarCooldown(segundosRestantes);
    }
})();
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/layout/public_footer.php'; ?>
