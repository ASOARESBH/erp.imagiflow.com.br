<?php $rotas = $rotas ?? []; ?>
<style>
.rota-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:.75rem;display:flex;align-items:center;gap:1rem;transition:box-shadow .15s}
.rota-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.1)}
.rota-icon{width:44px;height:44px;border-radius:50%;background:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.rota-icon.livre{background:#fef3c7;color:#d97706}
.badge-tipo{font-size:.7rem;padding:.25em .6em;border-radius:20px;font-weight:600}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3 mt-2" role="alert">
  <?= htmlspecialchars($_SESSION['flash_success']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h5 class="mb-0 fw-bold"><i class="fas fa-map-marked-alt text-primary me-2"></i>Rotas Comerciais</h5>
      <small class="text-muted">Agrupe clientes e leads por região para suas viagens</small>
    </div>
    <div class="d-flex gap-2">
      <a href="/rdv/viagens" class="btn btn-sm btn-outline-secondary"><i class="fas fa-route me-1"></i> Viagens</a>
      <a href="/rdv/rotas/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> Nova Rota</a>
    </div>
  </div>

  <?php if (empty($rotas)): ?>
  <div class="text-center py-5 text-muted">
    <i class="fas fa-map-marked-alt fa-3x mb-3 d-block"></i>
    <p>Nenhuma rota criada ainda.</p>
    <a href="/rdv/rotas/create" class="btn btn-primary btn-sm">+ Criar primeira rota</a>
  </div>
  <?php else: ?>
  <?php foreach ($rotas as $r): ?>
  <div class="rota-card">
    <div class="rota-icon <?= $r->tipo === 'livre' ? 'livre' : '' ?>">
      <i class="fas <?= $r->tipo === 'livre' ? 'fa-random' : 'fa-map-marker-alt' ?>"></i>
    </div>
    <div class="flex-grow-1">
      <div class="d-flex align-items-center gap-2">
        <strong><?= htmlspecialchars($r->nome) ?></strong>
        <?php if ($r->tipo === 'livre'): ?>
        <span class="badge-tipo" style="background:#fef3c7;color:#92400e">Livre</span>
        <?php else: ?>
        <span class="badge-tipo" style="background:#eff6ff;color:#1d4ed8">Padrão</span>
        <?php endif; ?>
        <?php if ($r->regiao): ?>
        <span class="text-muted" style="font-size:.8rem"><i class="fas fa-globe-americas me-1"></i><?= htmlspecialchars($r->regiao) ?></span>
        <?php endif; ?>
        <?php if ($r->estado): ?>
        <span class="badge bg-secondary-subtle text-secondary" style="font-size:.7rem"><?= htmlspecialchars($r->estado) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($r->descricao): ?>
      <div class="text-muted" style="font-size:.8rem"><?= htmlspecialchars($r->descricao) ?></div>
      <?php endif; ?>
      <div class="mt-1 d-flex gap-3" style="font-size:.75rem;color:#94a3b8">
        <span><i class="fas fa-users me-1"></i><?= (int)$r->total_clientes ?> cliente(s)/lead(s)</span>
        <span><i class="fas fa-route me-1"></i><?= (int)$r->total_viagens ?> viagem(ns)</span>
        <span><i class="fas fa-calendar me-1"></i>Criada em <?= date('d/m/Y', strtotime($r->created_at)) ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="/rdv/rotas/<?= $r->id ?>" class="btn btn-sm btn-outline-primary" title="Gerenciar"><i class="fas fa-cog"></i></a>
      <a href="/rdv/viagens/create?rota_id=<?= $r->id ?>" class="btn btn-sm btn-outline-success" title="Nova viagem nesta rota"><i class="fas fa-plane-departure"></i></a>
      <a href="/rdv/rotas/<?= $r->id ?>/edit" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
      <button class="btn btn-sm btn-outline-danger btn-del-rota" data-id="<?= $r->id ?>" title="Excluir"><i class="fas fa-trash"></i></button>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-del-rota').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Excluir esta rota? As viagens vinculadas não serão excluídas.')) return;
    const resp = await fetch(`/rdv/rotas/${this.dataset.id}/delete`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else alert('Erro ao excluir: ' + (data.error || ''));
  });
});
</script>
