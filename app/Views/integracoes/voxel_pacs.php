<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-x-ray me-2 text-primary"></i>ImagiFlow / VOXEL PACS</h1>
            <p class="text-muted mb-0">Conecte estudos assinados ou liberados do VOXEL à apuração de contratos.</p>
        </div>
        <span class="badge <?= (($row->status ?? 'inativo') === 'ativo') ? 'bg-success' : 'bg-secondary' ?>">
            <?= (($row->status ?? 'inativo') === 'ativo') ? 'Integração ativa' : 'Integração inativa' ?>
        </span>
    </div>

    <?php if (!$crypto_configured): ?>
        <div class="alert alert-warning"><i class="fas fa-shield-alt me-1"></i>Configure <code>APP_KEY</code> ou <code>APP_ENCRYPTION_KEY</code> antes de salvar o segredo do VOXEL PACS.</div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white"><strong>Credenciais por empresa</strong></div>
                <div class="card-body">
                    <form id="voxel-config-form" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="base_url" class="form-label">URL base do VOXEL PACS</label>
                                <input id="base_url" name="base_url" type="url" class="form-control" required value="<?= htmlspecialchars($config['base_url'] ?? 'https://server.voxelpacs.com.br') ?>">
                                <div class="form-text">Somente HTTPS. A URL padrão é o servidor oficial do conector.</div>
                            </div>
                            <div class="col-md-7">
                                <label for="integration_code" class="form-label">Código de integração</label>
                                <input id="integration_code" name="integration_code" type="text" class="form-control" autocomplete="off" required value="<?= htmlspecialchars($config['integration_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="ativo" <?= (($row->status ?? '') !== 'inativo') ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= (($row->status ?? '') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="secret" class="form-label">Segredo HMAC</label>
                                <input id="secret" name="secret" type="password" class="form-control" autocomplete="new-password" placeholder="<?= !empty($config['secret_configured']) ? '********' : 'Cole o segredo gerado no VOXEL PACS' ?>" value="<?= !empty($config['secret_configured']) ? '********' : '' ?>">
                                <div class="form-text">O segredo é cifrado no servidor e nunca será exibido novamente, registrado em log ou enviado ao navegador após o salvamento.</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button class="btn btn-primary" type="submit" <?= !$crypto_configured ? 'disabled' : '' ?>><i class="fas fa-save me-1"></i>Salvar configuração</button>
                            <button class="btn btn-outline-primary" type="button" id="btn-testar" <?= !$crypto_configured ? 'disabled' : '' ?>><i class="fas fa-plug me-1"></i>Testar conexão</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white"><strong>Teste de conexão</strong></div>
                <div class="card-body">
                    <label for="test_crm" class="form-label">CRM de médico no VOXEL</label>
                    <input id="test_crm" type="text" class="form-control" inputmode="numeric" placeholder="Ex.: 123234">
                    <p class="small text-muted mt-3 mb-0">O teste consulta o médico por CRM, assina a chamada HMAC e não cria apuração nem modifica estudos no VOXEL.</p>
                    <?php if (!empty($config['last_test_at'])): ?>
                        <p class="small mt-3 mb-0">Último teste: <?= htmlspecialchars($config['last_test_at']) ?></p>
                    <?php endif; ?>
                    <div id="voxel-test-result" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('voxel-config-form');
    const result = document.getElementById('voxel-test-result');
    const message = (text, success) => {
        result.className = 'mt-3 alert ' + (success ? 'alert-success' : 'alert-danger');
        result.textContent = text;
        result.classList.remove('d-none');
    };
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const formData = new FormData(form);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            const response = await fetch('/integracao/imagiflow/save', { method: 'POST', body: formData });
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Não foi possível salvar.');
            message(data.message, true);
            document.getElementById('secret').value = '********';
        } catch (error) {
            message(error.message || 'Falha na comunicação.', false);
        } finally {
            button.disabled = false;
        }
    });
    document.getElementById('btn-testar')?.addEventListener('click', async () => {
        const crm = document.getElementById('test_crm').value.trim();
        if (!crm) { message('Informe um CRM para testar a conexão.', false); return; }
        try {
            const formData = new FormData();
            formData.append('crm', crm);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            const response = await fetch('/integracao/imagiflow/test', { method: 'POST', body: formData });
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Teste não concluído.');
            const found = data.data?.found ? 'Médico localizado com sucesso.' : 'Conexão válida, mas nenhum médico único foi localizado para este CRM.';
            message(found, true);
        } catch (error) {
            message(error.message || 'Falha na comunicação.', false);
        }
    });
})();
</script>
