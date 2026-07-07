<?php
$rota          = $rota          ?? null;
$clientes      = $clientes      ?? [];
$todosClientes = $todosClientes ?? [];
$todosLeads    = $todosLeads    ?? [];
?>
<style>
.rota-show-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.cliente-row{display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid #f1f5f9}
.cliente-row:last-child{border-bottom:none}
.cliente-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;background:#eff6ff;color:#3b82f6}
.cliente-avatar.lead{background:#f0fdf4;color:#16a34a}
.drag-handle{cursor:grab;color:#94a3b8;padding:0 .25rem}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3 mt-2" role="alert">
  <?= htmlspecialchars($_SESSION['flash_success']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid">
  <!-- Header da rota -->
  <div class="rota-show-card">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="fas fa-map-marked-alt text-primary fa-lg"></i>
          <h5 class="mb-0 fw-bold"><?= htmlspecialchars($rota->nome) ?></h5>
          <?php if ($rota->tipo === 'livre'): ?>
          <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.7rem">Rota Livre</span>
          <?php else: ?>
          <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.7rem">Rota Padrão</span>
          <?php endif; ?>
        </div>
        <?php if ($rota->descricao): ?>
        <div class="text-muted" style="font-size:.875rem"><?= htmlspecialchars($rota->descricao) ?></div>
        <?php endif; ?>
        <div class="mt-1 d-flex gap-3 flex-wrap" style="font-size:.8rem;color:#64748b">
          <?php if ($rota->regiao): ?><span><i class="fas fa-globe-americas me-1"></i><?= htmlspecialchars($rota->regiao) ?></span><?php endif; ?>
          <?php if ($rota->estado): ?><span><i class="fas fa-map-pin me-1"></i><?= htmlspecialchars($rota->estado) ?></span><?php endif; ?>
          <span><i class="fas fa-users me-1"></i><?= count($clientes) ?> cliente(s)/lead(s)</span>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="/rdv/viagens/create?rota_id=<?= $rota->id ?>" class="btn btn-sm btn-success">
          <i class="fas fa-plane-departure me-1"></i> Nova Viagem nesta Rota
        </a>
        <a href="/rdv/rotas/<?= $rota->id ?>/edit" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-edit me-1"></i> Editar Rota
        </a>
        <a href="/rdv/rotas" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
      </div>
    </div>
  </div>

  <?php if ($rota->tipo === 'livre'): ?>
  <!-- Rota Livre -->
  <div class="rota-show-card">
    <div class="text-center py-4">
      <i class="fas fa-random fa-3x text-warning mb-3 d-block"></i>
      <h6 class="fw-bold">Rota Livre</h6>
      <p class="text-muted">Esta rota não possui controle de clientes, leads ou oportunidades.<br>
      Ela serve como agrupador livre para viagens sem destinos pré-definidos.</p>
      <a href="/rdv/viagens/create?rota_id=<?= $rota->id ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Criar Viagem nesta Rota
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- Rota Padrão — Gerenciar clientes/leads -->
  <div class="rota-show-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="fw-bold"><i class="fas fa-users me-2 text-primary"></i>Clientes e Leads da Rota</div>
      <button class="btn btn-sm btn-primary" id="btnAddCliente">
        <i class="fas fa-plus me-1"></i> Adicionar
      </button>
    </div>

    <?php if (empty($clientes)): ?>
    <div class="text-center py-4 text-muted">
      <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
      <p>Nenhum cliente ou lead adicionado a esta rota.</p>
      <button class="btn btn-primary btn-sm" id="btnAddCliente2">+ Adicionar Cliente/Lead</button>
    </div>
    <?php else: ?>
    <div id="listaClientes">
      <?php foreach ($clientes as $c): ?>
      <div class="cliente-row" id="cli-<?= $c->id ?>">
        <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
        <div class="cliente-avatar <?= $c->lead_id ? 'lead' : '' ?>">
          <i class="fas <?= $c->lead_id ? 'fa-user-tag' : 'fa-building' ?>"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-semibold" style="font-size:.875rem">
            <?= htmlspecialchars($c->cliente_nome ?? $c->lead_nome ?? '—') ?>
            <?php if ($c->lead_id): ?>
            <span class="badge bg-success-subtle text-success ms-1" style="font-size:.65rem">Lead</span>
            <?php else: ?>
            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.65rem">Cliente</span>
            <?php endif; ?>
          </div>
          <div style="font-size:.75rem;color:#64748b">
            <?php if ($c->cliente_cidade): ?><span class="me-2"><i class="fas fa-map-pin me-1"></i><?= htmlspecialchars($c->cliente_cidade) ?><?= $c->cliente_estado ? ', ' . $c->cliente_estado : '' ?></span><?php endif; ?>
            <?php if ($c->oportunidade_titulo): ?><span><i class="fas fa-handshake me-1"></i><?= htmlspecialchars($c->oportunidade_titulo) ?></span><?php endif; ?>
            <?php if ($c->observacoes): ?><span class="ms-2 text-muted"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($c->observacoes) ?></span><?php endif; ?>
          </div>
        </div>
        <div class="text-muted" style="font-size:.75rem;min-width:30px;text-align:center">#<?= $c->ordem ?: '—' ?></div>
        <button class="btn btn-xs btn-outline-danger btn-rem-cliente" data-id="<?= $c->id ?>" title="Remover">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL: Adicionar Cliente/Lead -->
<div class="modal fade" id="modalAddCliente" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Adicionar à Rota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Tipo</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipoAdd" id="tipoCliente" value="cliente" checked>
              <label class="form-check-label" for="tipoCliente"><i class="fas fa-building me-1"></i>Cliente</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipoAdd" id="tipoLead" value="lead">
              <label class="form-check-label" for="tipoLead"><i class="fas fa-user-tag me-1"></i>Lead</label>
            </div>
          </div>
        </div>

        <div id="blocoCliente">
          <label class="form-label fw-semibold">Cliente</label>
          <select class="form-select" id="selectCliente">
            <option value="">— Selecionar cliente —</option>
            <?php foreach ($todosClientes as $c): ?>
            <option value="<?= $c->id ?>"><?= htmlspecialchars($c->nome) ?><?= $c->cidade ? ' — ' . $c->cidade : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="blocoLead" style="display:none">
          <label class="form-label fw-semibold">Lead</label>
          <select class="form-select" id="selectLead">
            <option value="">— Selecionar lead —</option>
            <?php foreach ($todosLeads as $l): ?>
            <option value="<?= $l->id ?>"><?= htmlspecialchars($l->nome) ?><?= $l->empresa ? ' (' . $l->empresa . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mt-3 row g-2">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Ordem</label>
            <input type="number" class="form-control" id="inputOrdem" value="<?= count($clientes) + 1 ?>" min="1">
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold">Observações</label>
            <input type="text" class="form-control" id="inputObs" placeholder="Contato, objetivo da visita...">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarCliente">
          <i class="fas fa-plus me-1"></i> Adicionar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const ROTA_ID = <?= $rota->id ?>;

// Abrir modal
document.querySelectorAll('#btnAddCliente, #btnAddCliente2').forEach(b => {
  b && b.addEventListener('click', () => new bootstrap.Modal(document.getElementById('modalAddCliente')).show());
});

// Tipo toggle
document.querySelectorAll('input[name="tipoAdd"]').forEach(r => {
  r.addEventListener('change', function() {
    document.getElementById('blocoCliente').style.display = this.value === 'cliente' ? 'block' : 'none';
    document.getElementById('blocoLead').style.display    = this.value === 'lead'    ? 'block' : 'none';
  });
});

// Salvar cliente/lead
document.getElementById('btnSalvarCliente').addEventListener('click', async function() {
  const tipo = document.querySelector('input[name="tipoAdd"]:checked').value;
  const fd   = new FormData();
  fd.append('ordem',       document.getElementById('inputOrdem').value);
  fd.append('observacoes', document.getElementById('inputObs').value);

  if (tipo === 'cliente') {
    const cid = document.getElementById('selectCliente').value;
    if (!cid) { alert('Selecione um cliente.'); return; }
    fd.append('cliente_id', cid);
  } else {
    const lid = document.getElementById('selectLead').value;
    if (!lid) { alert('Selecione um lead.'); return; }
    fd.append('lead_id', lid);
  }

  this.disabled = true;
  try {
    const resp = await fetch(`/rdv/rotas/${ROTA_ID}/clientes/add`, { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalAddCliente')).hide();
      window.location.reload();
    } else {
      alert('Erro: ' + (data.error || ''));
    }
  } catch (e) {
    alert('Erro de comunicação: ' + e.message);
  } finally {
    this.disabled = false;
  }
});

// Remover cliente
document.querySelectorAll('.btn-rem-cliente').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Remover este item da rota?')) return;
    const resp = await fetch(`/rdv/rotas/${ROTA_ID}/clientes/${this.dataset.id}/remove`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) document.getElementById('cli-' + this.dataset.id)?.remove();
    else alert('Erro: ' + (data.error || ''));
  });
});
</script>
