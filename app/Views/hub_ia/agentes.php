<?php
$moduloLabels = [
    'crm' => 'CRM', 'financeiro' => 'Financeiro', 'rdv' => 'RDV', 'marketing' => 'Marketing',
    'cnes' => 'CNES', 'estoque' => 'Estoque', 'rh' => 'RH', 'configuracoes' => 'Configurações',
];
?>
<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-agente-card{border:1px solid #e2e8f0;border-radius:.6rem;padding:1rem;height:100%}
.hubia-agente-avatar{font-size:1.8rem}
.hubia-perm-badge{font-size:.65rem;padding:.2em .55em;border-radius:12px;margin:0 .2em .2em 0;display:inline-block}
.hubia-perm-badge.has{background:#d1fae5;color:#065f46}
.hubia-perm-badge.no{background:#f1f5f9;color:#94a3b8;text-decoration:line-through}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-robot me-2 text-primary"></i>Robôs IA</h4>
    <button class="btn btn-primary btn-sm" id="btnNovoAgente"><i class="fas fa-plus me-1"></i> Novo Agente</button>
  </div>

  <div class="row g-3">
    <?php if (empty($agentes)): ?>
    <div class="col-12"><div class="hubia-card text-center text-muted py-4">Nenhum agente cadastrado ainda. Crie a EVA para começar.</div></div>
    <?php endif; ?>
    <?php foreach ($agentes as $a): ?>
    <div class="col-md-6 col-lg-4">
      <div class="hubia-agente-card">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="hubia-agente-avatar"><?= htmlspecialchars($a->avatar ?: '🤖') ?></span>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($a->nome) ?> <?php if (!$a->ativo): ?><span class="badge bg-secondary">Inativo</span><?php endif; ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($a->conector_nome ?? 'Sem conector') ?></div>
          </div>
        </div>
        <p class="text-muted small mb-2"><?= htmlspecialchars($a->descricao ?: '—') ?></p>
        <div class="mb-2">
          <?php foreach ($modulos as $m): ?>
          <span class="hubia-perm-badge <?= !empty($permissoesPorAgente[$a->id][$m]) ? 'has' : 'no' ?>"><?= $moduloLabels[$m] ?></span>
          <?php endforeach; ?>
        </div>
        <div class="d-flex gap-1">
          <button class="btn btn-sm btn-outline-secondary btn-editar flex-grow-1" data-id="<?= $a->id ?>"><i class="fas fa-edit me-1"></i> Editar</button>
          <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $a->id ?>"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="modal fade" id="modalAgente" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAgenteTitulo">Novo Agente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="aId" value="">
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label fw-semibold">Avatar</label>
            <input type="text" class="form-control text-center" id="aAvatar" value="🤖" maxlength="4">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Nome</label>
            <input type="text" class="form-control" id="aNome" placeholder="Ex: EVA">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Idioma</label>
            <input type="text" class="form-control" id="aIdioma" value="pt-BR">
          </div>
        </div>
        <div class="mb-2 mt-2">
          <label class="form-label fw-semibold">Descrição</label>
          <input type="text" class="form-control" id="aDescricao" placeholder="Assistente Oficial do ERP InLaudo">
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Conector de IA</label>
            <select class="form-select" id="aConector">
              <option value="">— Nenhum —</option>
              <?php foreach ($conectores as $c): ?>
              <option value="<?= $c->id ?>"><?= htmlspecialchars($c->nome) ?> (<?= $c->provider ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Prompt (cadastrado)</label>
            <select class="form-select" id="aPrompt">
              <option value="">— Usar prompt inline abaixo —</option>
              <?php foreach ($prompts as $p): ?>
              <option value="<?= $p->id ?>"><?= htmlspecialchars($p->nome) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-2 mt-2">
          <label class="form-label fw-semibold">Prompt Base (usado se nenhum prompt cadastrado for selecionado)</label>
          <textarea class="form-control" id="aPromptBase" rows="3" placeholder="Você é a EVA, assistente oficial do ERP InLaudo..."></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Personalidade</label>
          <input type="text" class="form-control" id="aPersonalidade" placeholder="Ex: objetiva, cordial, direta ao ponto">
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Temperatura</label>
            <input type="number" class="form-control" id="aTemperatura" step="0.1" min="0" max="2" placeholder="do conector">
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" id="aConsultaBanco">
              <label class="form-check-label" for="aConsultaBanco">Permite consultar o banco de dados</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" id="aAtivo" checked>
              <label class="form-check-label" for="aAtivo">Ativo</label>
            </div>
          </div>
        </div>

        <hr>
        <label class="form-label fw-semibold">Permissões por Módulo</label>
        <div class="row g-2">
          <?php foreach ($modulos as $m): ?>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input perm-check" type="checkbox" id="perm_<?= $m ?>" value="<?= $m ?>">
              <label class="form-check-label" for="perm_<?= $m ?>"><?= $moduloLabels[$m] ?></label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div id="alertAgente" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarAgente">Salvar</button>
      </div>
    </div>
  </div>
</div>

<script>
const agentesData = <?= json_encode($agentes) ?>;
const permissoesData = <?= json_encode($permissoesPorAgente) ?>;

function abrirModalAgente(id = null) {
  document.getElementById('alertAgente').innerHTML = '';
  document.querySelectorAll('.perm-check').forEach(el => el.checked = false);

  if (id) {
    const a = agentesData.find(x => x.id == id);
    document.getElementById('modalAgenteTitulo').textContent = 'Editar Agente';
    document.getElementById('aId').value = a.id;
    document.getElementById('aAvatar').value = a.avatar || '🤖';
    document.getElementById('aNome').value = a.nome;
    document.getElementById('aIdioma').value = a.idioma || 'pt-BR';
    document.getElementById('aDescricao').value = a.descricao || '';
    document.getElementById('aConector').value = a.conector_id || '';
    document.getElementById('aPrompt').value = a.prompt_id || '';
    document.getElementById('aPromptBase').value = a.prompt_base || '';
    document.getElementById('aPersonalidade').value = a.personalidade || '';
    document.getElementById('aTemperatura').value = a.temperatura || '';
    document.getElementById('aConsultaBanco').checked = a.permite_consulta_banco == 1;
    document.getElementById('aAtivo').checked = a.ativo == 1;
    const perms = permissoesData[id] || {};
    Object.keys(perms).forEach(m => {
      const el = document.getElementById('perm_' + m);
      if (el) el.checked = !!perms[m];
    });
  } else {
    document.getElementById('modalAgenteTitulo').textContent = 'Novo Agente';
    document.getElementById('aId').value = '';
    document.getElementById('aAvatar').value = '🤖';
    document.getElementById('aNome').value = '';
    document.getElementById('aIdioma').value = 'pt-BR';
    document.getElementById('aDescricao').value = '';
    document.getElementById('aConector').value = '';
    document.getElementById('aPrompt').value = '';
    document.getElementById('aPromptBase').value = '';
    document.getElementById('aPersonalidade').value = '';
    document.getElementById('aTemperatura').value = '';
    document.getElementById('aConsultaBanco').checked = false;
    document.getElementById('aAtivo').checked = true;
  }
  new bootstrap.Modal(document.getElementById('modalAgente')).show();
}

document.getElementById('btnNovoAgente').addEventListener('click', () => abrirModalAgente());
document.querySelectorAll('.btn-editar').forEach(btn => btn.addEventListener('click', () => abrirModalAgente(btn.dataset.id)));

document.getElementById('btnSalvarAgente').addEventListener('click', async function() {
  const id = document.getElementById('aId').value;
  const nome = document.getElementById('aNome').value.trim();
  if (!nome) {
    document.getElementById('alertAgente').innerHTML = '<div class="alert alert-danger py-2 mb-0">Informe o nome do agente.</div>';
    return;
  }
  const fd = new FormData();
  fd.append('nome', nome);
  fd.append('avatar', document.getElementById('aAvatar').value.trim() || '🤖');
  fd.append('idioma', document.getElementById('aIdioma').value.trim() || 'pt-BR');
  fd.append('descricao', document.getElementById('aDescricao').value.trim());
  fd.append('conector_id', document.getElementById('aConector').value);
  fd.append('prompt_id', document.getElementById('aPrompt').value);
  fd.append('prompt_base', document.getElementById('aPromptBase').value.trim());
  fd.append('personalidade', document.getElementById('aPersonalidade').value.trim());
  fd.append('temperatura', document.getElementById('aTemperatura').value);
  fd.append('permite_consulta_banco', document.getElementById('aConsultaBanco').checked ? '1' : '0');
  fd.append('ativo', document.getElementById('aAtivo').checked ? '1' : '0');
  document.querySelectorAll('.perm-check').forEach(el => {
    if (el.checked) fd.append('perm_' + el.value, '1');
  });

  this.disabled = true;
  try {
    const url = id ? `/hub-ia/agentes/${id}/update` : '/hub-ia/agentes/store';
    const resp = await fetch(url, { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else document.getElementById('alertAgente').innerHTML = '<div class="alert alert-danger py-2 mb-0">' + (data.error || 'Erro ao salvar.') + '</div>';
  } catch (e) {
    document.getElementById('alertAgente').innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
  }
});

document.querySelectorAll('.btn-excluir').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Excluir este agente?')) return;
    const resp = await fetch(`/hub-ia/agentes/${this.dataset.id}/delete`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else alert('Erro ao excluir.');
  });
});
</script>
