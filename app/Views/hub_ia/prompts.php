<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-prompt-row{display:flex;align-items:flex-start;gap:.75rem;padding:.85rem 0;border-bottom:1px solid #f1f5f9}
.hubia-prompt-row:last-child{border-bottom:none}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Prompts</h4>
    <button class="btn btn-primary btn-sm" id="btnNovoPrompt"><i class="fas fa-plus me-1"></i> Novo Prompt</button>
  </div>

  <div class="hubia-card">
    <?php if (empty($prompts)): ?>
    <p class="text-muted text-center py-4 mb-0">Nenhum prompt cadastrado ainda.</p>
    <?php else: ?>
    <?php foreach ($prompts as $p): ?>
    <div class="hubia-prompt-row">
      <div class="flex-grow-1">
        <div class="fw-semibold"><?= htmlspecialchars($p->nome) ?>
          <?php if ($p->categoria): ?><span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($p->categoria) ?></span><?php endif; ?>
          <?php if (!$p->ativo): ?><span class="badge bg-secondary ms-1">Inativo</span><?php endif; ?>
        </div>
        <div class="text-muted" style="font-size:.8rem;max-width:700px;white-space:pre-wrap"><?= htmlspecialchars(mb_substr($p->conteudo, 0, 200)) ?><?= mb_strlen($p->conteudo) > 200 ? '…' : '' ?></div>
      </div>
      <button class="btn btn-sm btn-outline-secondary btn-editar"
              data-id="<?= $p->id ?>" data-nome="<?= htmlspecialchars($p->nome) ?>" data-categoria="<?= htmlspecialchars($p->categoria ?? '') ?>"
              data-conteudo="<?= htmlspecialchars($p->conteudo) ?>" data-ativo="<?= $p->ativo ?>">
        <i class="fas fa-edit"></i>
      </button>
      <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $p->id ?>"><i class="fas fa-trash"></i></button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="modalPrompt" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPromptTitulo">Novo Prompt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pId" value="">
        <div class="row g-2 mb-2">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Nome</label>
            <input type="text" class="form-control" id="pNome" placeholder="Ex: Análise Comercial">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Categoria</label>
            <input type="text" class="form-control" id="pCategoria" placeholder="Ex: CRM">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Conteúdo</label>
          <textarea class="form-control" id="pConteudo" rows="8" placeholder="Você é especialista em vendas... Use {{variavel}} para valores dinâmicos."></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="pAtivo" checked>
          <label class="form-check-label" for="pAtivo">Ativo</label>
        </div>
        <div id="alertPrompt" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarPrompt">Salvar</button>
      </div>
    </div>
  </div>
</div>

<script>
function abrirModalPrompt(dados = null) {
  document.getElementById('alertPrompt').innerHTML = '';
  document.getElementById('pId').value = dados ? dados.id : '';
  document.getElementById('pNome').value = dados ? dados.nome : '';
  document.getElementById('pCategoria').value = dados ? dados.categoria : '';
  document.getElementById('pConteudo').value = dados ? dados.conteudo : '';
  document.getElementById('pAtivo').checked = dados ? dados.ativo === '1' : true;
  document.getElementById('modalPromptTitulo').textContent = dados ? 'Editar Prompt' : 'Novo Prompt';
  new bootstrap.Modal(document.getElementById('modalPrompt')).show();
}

document.getElementById('btnNovoPrompt').addEventListener('click', () => abrirModalPrompt());
document.querySelectorAll('.btn-editar').forEach(btn => btn.addEventListener('click', () => abrirModalPrompt(btn.dataset)));

document.getElementById('btnSalvarPrompt').addEventListener('click', async function() {
  const id = document.getElementById('pId').value;
  const nome = document.getElementById('pNome').value.trim();
  const conteudo = document.getElementById('pConteudo').value.trim();
  if (!nome || !conteudo) {
    document.getElementById('alertPrompt').innerHTML = '<div class="alert alert-danger py-2 mb-0">Informe nome e conteúdo.</div>';
    return;
  }
  const fd = new FormData();
  fd.append('nome', nome);
  fd.append('categoria', document.getElementById('pCategoria').value.trim());
  fd.append('conteudo', conteudo);
  fd.append('ativo', document.getElementById('pAtivo').checked ? '1' : '0');

  this.disabled = true;
  try {
    const url = id ? `/hub-ia/prompts/${id}/update` : '/hub-ia/prompts/store';
    const resp = await fetch(url, { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else document.getElementById('alertPrompt').innerHTML = '<div class="alert alert-danger py-2 mb-0">' + (data.error || 'Erro ao salvar.') + '</div>';
  } catch (e) {
    document.getElementById('alertPrompt').innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
  }
});

document.querySelectorAll('.btn-excluir').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Excluir este prompt?')) return;
    const resp = await fetch(`/hub-ia/prompts/${this.dataset.id}/delete`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else alert('Erro ao excluir.');
  });
});
</script>
