<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
</style>

<div class="container-fluid">
  <h4 class="mb-3"><i class="fab fa-whatsapp me-2 text-success"></i>WhatsApp</h4>

  <div class="alert alert-info py-2" style="font-size:.85rem">
    <i class="fas fa-info-circle me-1"></i>
    Esta tela prepara a arquitetura para a EVA responder futuramente pelo WhatsApp
    (Cliente → WhatsApp → Webhook → HUB I.A → Modelo IA → Banco → Resposta → WhatsApp).
    Não há envio/recebimento real nesta fase — apenas o cadastro das credenciais.
  </div>

  <div class="hubia-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="hubia-card-title mb-0">Configuração</div>
      <span class="badge bg-<?= $config->status === 'conectado' ? 'success' : 'secondary' ?>">
        <?= $config->status === 'conectado' ? 'Conectado' : 'Desconectado' ?>
      </span>
    </div>

    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Número</label>
        <input type="text" class="form-control" id="wNumero" value="<?= htmlspecialchars($config->numero ?? '') ?>" placeholder="+55 31 90000-0000">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Token</label>
        <input type="password" class="form-control" id="wToken" placeholder="********">
        <small class="text-muted">Deixe em branco para manter o token atual.</small>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Webhook URL</label>
        <input type="text" class="form-control" id="wWebhook" value="<?= htmlspecialchars($config->webhook_url ?? '') ?>" placeholder="https://erp.inlaudo.com.br/hub-ia/whatsapp/webhook">
      </div>
    </div>

    <div id="alertWhatsapp" class="mt-2"></div>
    <button class="btn btn-primary mt-3" id="btnSalvarWhatsapp"><i class="fas fa-save me-1"></i> Salvar</button>
    <button class="btn btn-outline-secondary mt-3" disabled title="Não implementado nesta fase"><i class="fas fa-vial me-1"></i> Testar</button>
  </div>
</div>

<script>
document.getElementById('btnSalvarWhatsapp').addEventListener('click', async function() {
  const fd = new FormData();
  fd.append('numero', document.getElementById('wNumero').value.trim());
  fd.append('token', document.getElementById('wToken').value || '********');
  fd.append('webhook_url', document.getElementById('wWebhook').value.trim());

  this.disabled = true;
  try {
    const resp = await fetch('/hub-ia/whatsapp/salvar', { method: 'POST', body: fd });
    const data = await resp.json();
    document.getElementById('alertWhatsapp').innerHTML = data.success
      ? '<div class="alert alert-success py-2 mb-0">Configuração salva.</div>'
      : '<div class="alert alert-danger py-2 mb-0">Erro ao salvar.</div>';
  } catch (e) {
    document.getElementById('alertWhatsapp').innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
  }
});
</script>
