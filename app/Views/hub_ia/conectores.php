<?php
$providerLabels = [
    'openai' => 'OpenAI', 'claude' => 'Claude', 'gemini' => 'Gemini',
    'deepseek' => 'DeepSeek', 'mistral' => 'Mistral', 'ollama' => 'Ollama Local',
];
?>
<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-conector-row{display:flex;align-items:center;gap:.75rem;padding:.85rem 0;border-bottom:1px solid #f1f5f9}
.hubia-conector-row:last-child{border-bottom:none}
.hubia-status-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.hubia-status-dot.ativo{background:#10b981}
.hubia-status-dot.inativo{background:#cbd5e1}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-plug me-2 text-primary"></i>Conectores de IA</h4>
    <button class="btn btn-primary btn-sm" id="btnNovoConector"><i class="fas fa-plus me-1"></i> Novo Conector</button>
  </div>

  <div class="hubia-card">
    <?php if (empty($conectores)): ?>
    <p class="text-muted text-center py-4 mb-0">Nenhum conector cadastrado ainda.</p>
    <?php else: ?>
    <?php foreach ($conectores as $c): ?>
    <div class="hubia-conector-row">
      <span class="hubia-status-dot <?= $c->status ?>"></span>
      <div class="flex-grow-1">
        <div class="fw-semibold"><?= htmlspecialchars($c->nome) ?> <span class="badge bg-light text-dark border ms-1"><?= $providerLabels[$c->provider] ?? $c->provider ?></span></div>
        <div class="text-muted" style="font-size:.8rem">
          Modelo: <?= htmlspecialchars($c->modelo ?: '—') ?> · Temp: <?= number_format((float) $c->temperatura, 2) ?> · Max tokens: <?= (int) $c->max_tokens ?>
          <?php if ($c->ultimo_teste_em): ?>
            · Último teste: <span class="<?= $c->ultimo_teste_status === 'ok' ? 'text-success' : 'text-danger' ?>"><?= $c->ultimo_teste_status === 'ok' ? 'OK' : 'Erro' ?></span> (<?= date('d/m/Y H:i', strtotime($c->ultimo_teste_em)) ?>)
          <?php endif; ?>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-primary btn-testar" data-id="<?= $c->id ?>"><i class="fas fa-vial me-1"></i> Testar</button>
      <button class="btn btn-sm btn-outline-secondary btn-editar"
              data-id="<?= $c->id ?>" data-nome="<?= htmlspecialchars($c->nome) ?>" data-provider="<?= $c->provider ?>"
              data-endpoint="<?= htmlspecialchars($c->endpoint ?? '') ?>" data-modelo="<?= htmlspecialchars($c->modelo) ?>"
              data-temperatura="<?= $c->temperatura ?>" data-max_tokens="<?= $c->max_tokens ?>" data-timeout="<?= $c->timeout_segundos ?>"
              data-status="<?= $c->status ?>">
        <i class="fas fa-edit"></i>
      </button>
      <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $c->id ?>"><i class="fas fa-trash"></i></button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Conector -->
<div class="modal fade" id="modalConector" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalConectorTitulo">Novo Conector</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cId" value="">
        <div class="mb-2">
          <label class="form-label fw-semibold">Nome</label>
          <input type="text" class="form-control" id="cNome" placeholder="Ex: OpenAI Produção">
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Provedor</label>
            <select class="form-select" id="cProvider">
              <?php foreach ($providers as $p): ?>
              <option value="<?= $p ?>"><?= $providerLabels[$p] ?? $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Modelo</label>
            <input type="text" class="form-control" id="cModelo" placeholder="Ex: gpt-4o-mini">
          </div>
        </div>
        <div class="mb-2 mt-2">
          <label class="form-label fw-semibold">API Key</label>
          <input type="password" class="form-control" id="cApiKey" placeholder="<?= htmlspecialchars($mask) ?>">
          <small class="text-muted">Deixe em branco (ou com os asteriscos) para manter a chave atual.</small>
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Endpoint (opcional — obrigatório para Ollama local)</label>
          <input type="text" class="form-control" id="cEndpoint" placeholder="Ex: http://localhost:11434/v1">
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Temperatura</label>
            <input type="number" class="form-control" id="cTemperatura" step="0.1" min="0" max="2" value="0.3">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Max Tokens</label>
            <input type="number" class="form-control" id="cMaxTokens" value="2000">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Timeout (s)</label>
            <input type="number" class="form-control" id="cTimeout" value="30">
          </div>
        </div>
        <div class="mt-2">
          <label class="form-label fw-semibold">Status</label>
          <select class="form-select" id="cStatus">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
          </select>
        </div>
        <div id="alertConector" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarConector">Salvar</button>
      </div>
    </div>
  </div>
</div>

<script>
const MASK = <?= json_encode($mask) ?>;
let modoEdicao = false;

function abrirModal(dados = null) {
  document.getElementById('alertConector').innerHTML = '';
  if (dados) {
    modoEdicao = true;
    document.getElementById('modalConectorTitulo').textContent = 'Editar Conector';
    document.getElementById('cId').value = dados.id;
    document.getElementById('cNome').value = dados.nome;
    document.getElementById('cProvider').value = dados.provider;
    document.getElementById('cModelo').value = dados.modelo;
    document.getElementById('cApiKey').value = '';
    document.getElementById('cApiKey').placeholder = MASK;
    document.getElementById('cEndpoint').value = dados.endpoint;
    document.getElementById('cTemperatura').value = dados.temperatura;
    document.getElementById('cMaxTokens').value = dados.max_tokens;
    document.getElementById('cTimeout').value = dados.timeout;
    document.getElementById('cStatus').value = dados.status;
  } else {
    modoEdicao = false;
    document.getElementById('modalConectorTitulo').textContent = 'Novo Conector';
    document.getElementById('cId').value = '';
    document.getElementById('cNome').value = '';
    document.getElementById('cProvider').value = 'openai';
    document.getElementById('cModelo').value = '';
    document.getElementById('cApiKey').value = '';
    document.getElementById('cApiKey').placeholder = 'Chave de API';
    document.getElementById('cEndpoint').value = '';
    document.getElementById('cTemperatura').value = '0.3';
    document.getElementById('cMaxTokens').value = '2000';
    document.getElementById('cTimeout').value = '30';
    document.getElementById('cStatus').value = 'ativo';
  }
  new bootstrap.Modal(document.getElementById('modalConector')).show();
}

document.getElementById('btnNovoConector').addEventListener('click', () => abrirModal());

document.querySelectorAll('.btn-editar').forEach(btn => {
  btn.addEventListener('click', () => abrirModal(btn.dataset));
});

document.getElementById('btnSalvarConector').addEventListener('click', async function() {
  const id = document.getElementById('cId').value;
  const fd = new FormData();
  fd.append('nome', document.getElementById('cNome').value.trim());
  fd.append('provider', document.getElementById('cProvider').value);
  fd.append('modelo', document.getElementById('cModelo').value.trim());
  fd.append('api_key', document.getElementById('cApiKey').value || MASK);
  fd.append('endpoint', document.getElementById('cEndpoint').value.trim());
  fd.append('temperatura', document.getElementById('cTemperatura').value);
  fd.append('max_tokens', document.getElementById('cMaxTokens').value);
  fd.append('timeout_segundos', document.getElementById('cTimeout').value);
  fd.append('status', document.getElementById('cStatus').value);

  this.disabled = true;
  try {
    const url = id ? `/hub-ia/conectores/${id}/update` : '/hub-ia/conectores/store';
    const resp = await fetch(url, { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.success) {
      window.location.reload();
    } else {
      document.getElementById('alertConector').innerHTML = '<div class="alert alert-danger py-2 mb-0">' + (data.error || 'Erro ao salvar.') + '</div>';
    }
  } catch (e) {
    document.getElementById('alertConector').innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
  }
});

document.querySelectorAll('.btn-testar').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.dataset.id;
    const original = this.innerHTML;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testando...';
    try {
      const resp = await fetch(`/hub-ia/conectores/${id}/testar`, { method: 'POST' });
      const data = await resp.json();
      alert(data.success ? ('OK: ' + data.message) : ('Falha: ' + data.message));
      window.location.reload();
    } catch (e) {
      alert('Erro de comunicação: ' + e.message);
      this.disabled = false;
      this.innerHTML = original;
    }
  });
});

document.querySelectorAll('.btn-excluir').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Excluir este conector?')) return;
    const id = this.dataset.id;
    const resp = await fetch(`/hub-ia/conectores/${id}/delete`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else alert('Erro ao excluir.');
  });
});
</script>
