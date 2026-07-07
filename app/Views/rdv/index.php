<?php
$statusCfg = [
    'aberto'    => ['label' => 'Aberto',    'color' => 'primary',   'icon' => 'fa-folder-open'],
    'iniciado'  => ['label' => 'Iniciado',  'color' => 'info',      'icon' => 'fa-plane-departure'],
    'concluido' => ['label' => 'Concluído', 'color' => 'success',   'icon' => 'fa-check-circle'],
    'cancelado' => ['label' => 'Cancelado', 'color' => 'danger',    'icon' => 'fa-times-circle'],
];
$aprovCfg = [
    'pendente'  => ['label' => 'Pend. Aprovação', 'color' => 'warning'],
    'aprovado'  => ['label' => 'Aprovado',         'color' => 'success'],
    'reprovado' => ['label' => 'Reprovado',        'color' => 'danger'],
];
$viagens = $viagens ?? [];
$kpis    = $kpis    ?? (object)[];
$rotas   = $rotas   ?? [];
$filtros = $filtros  ?? [];
?>
<style>
.rdv-kpi-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rdv-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.rdv-kpi-label{font-size:.75rem;color:#6b7280;margin-bottom:.25rem}
.rdv-kpi-val{font-size:1.4rem;font-weight:700;color:#1e293b}
.rdv-table-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.rdv-table-header{display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;gap:.75rem}
.rdv-row:hover{background:#f8fafc}
.badge-status{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.empty-state{text-align:center;padding:3.5rem 1rem;color:#94a3b8}
.empty-state i{font-size:3rem;margin-bottom:.75rem;display:block}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
  <?= htmlspecialchars($_SESSION['flash_success']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
  <?= htmlspecialchars($_SESSION['flash_error']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid">
  <!-- KPIs -->
  <div class="rdv-kpi-bar">
    <div class="rdv-kpi">
      <div class="rdv-kpi-label">Total de Viagens</div>
      <div class="rdv-kpi-val"><?= (int)($kpis->total ?? 0) ?></div>
    </div>
    <div class="rdv-kpi">
      <div class="rdv-kpi-label"><span class="badge bg-primary-subtle text-primary">Abertas</span></div>
      <div class="rdv-kpi-val"><?= (int)($kpis->abertas ?? 0) ?></div>
    </div>
    <div class="rdv-kpi">
      <div class="rdv-kpi-label"><span class="badge bg-info-subtle text-info">Em Andamento</span></div>
      <div class="rdv-kpi-val"><?= (int)($kpis->iniciadas ?? 0) ?></div>
    </div>
    <div class="rdv-kpi">
      <div class="rdv-kpi-label"><span class="badge bg-success-subtle text-success">Concluídas</span></div>
      <div class="rdv-kpi-val"><?= (int)($kpis->concluidas ?? 0) ?></div>
    </div>
    <div class="rdv-kpi">
      <div class="rdv-kpi-label">Despesas do Mês</div>
      <div class="rdv-kpi-val" style="color:#1a56db">R$ <?= number_format((float)($kpis->valor_mes ?? 0), 2, ',', '.') ?></div>
    </div>
    <div class="rdv-kpi">
      <div class="rdv-kpi-label">Total Previsto</div>
      <div class="rdv-kpi-val">R$ <?= number_format((float)($kpis->total_previsto ?? 0), 2, ',', '.') ?></div>
    </div>
    <div class="rdv-kpi">
      <div class="rdv-kpi-label">Total Realizado</div>
      <div class="rdv-kpi-val" style="color:#059669">R$ <?= number_format((float)($kpis->total_real ?? 0), 2, ',', '.') ?></div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="rdv-table-card">
    <div class="rdv-table-header">
      <div class="d-flex align-items-center gap-2">
        <i class="fas fa-route text-primary"></i>
        <strong>Viagens / RDV</strong>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <form method="GET" action="/rdv/viagens" class="d-flex gap-2 align-items-center flex-wrap">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar viagem, código..." value="<?= htmlspecialchars($filtros['q'] ?? '') ?>" style="width:200px">
          <select name="status" class="form-select form-select-sm" style="width:140px">
            <option value="">Todos os status</option>
            <?php foreach ($statusCfg as $k => $s): ?>
            <option value="<?= $k ?>" <?= ($filtros['status'] ?? '') === $k ? 'selected' : '' ?>><?= $s['label'] ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($rotas): ?>
          <select name="rota_id" class="form-select form-select-sm" style="width:160px">
            <option value="">Todas as rotas</option>
            <?php foreach ($rotas as $r): ?>
            <option value="<?= $r->id ?>" <?= ((int)($filtros['rota_id'] ?? 0)) === (int)$r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->nome) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
          <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-filter me-1"></i> Filtrar</button>
          <?php if (array_filter($filtros)): ?>
          <a href="/rdv/viagens" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="/rdv/viagens/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> Nova Viagem</a>
        <a href="/rdv/rotas" class="btn btn-sm btn-outline-primary"><i class="fas fa-map-marked-alt me-1"></i> Rotas</a>
      </div>
    </div>

    <?php if (empty($viagens)): ?>
    <div class="empty-state">
      <i class="fas fa-route"></i>
      <p>Nenhuma viagem encontrada.</p>
      <a href="/rdv/viagens/create" class="btn btn-primary btn-sm">+ Nova Viagem</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:.875rem">
        <thead class="table-light">
          <tr>
            <th>Código</th>
            <th>Nome / Rota</th>
            <th>Vendedor</th>
            <th>Período</th>
            <th>Despesas</th>
            <th>Valor Real</th>
            <th>Status</th>
            <th>Aprovação</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($viagens as $v): ?>
          <?php $sc = $statusCfg[$v->status] ?? ['label' => $v->status, 'color' => 'secondary', 'icon' => 'fa-circle']; ?>
          <tr class="rdv-row">
            <td><a href="/rdv/viagens/<?= $v->id ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($v->codigo) ?></a></td>
            <td>
              <div><?= htmlspecialchars($v->nome) ?></div>
              <?php if ($v->rota_nome): ?>
              <small class="text-muted"><i class="fas fa-map-marked-alt me-1"></i><?= htmlspecialchars($v->rota_nome) ?></small>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($v->vendedor_nome ?? '—') ?></td>
            <td>
              <small><?= date('d/m/Y', strtotime($v->periodo_inicio)) ?></small><br>
              <small class="text-muted">até <?= date('d/m/Y', strtotime($v->periodo_fim)) ?></small>
            </td>
            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary"><?= (int)$v->total_despesas ?></span></td>
            <td class="fw-semibold" style="color:#059669">R$ <?= number_format((float)$v->valor_real, 2, ',', '.') ?></td>
            <td><span class="badge bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?> badge-status"><i class="fas <?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?></span></td>
            <td>
              <?php if ($v->aprovacao_status): ?>
              <?php $ac = $aprovCfg[$v->aprovacao_status] ?? ['label' => $v->aprovacao_status, 'color' => 'secondary']; ?>
              <span class="badge bg-<?= $ac['color'] ?>-subtle text-<?= $ac['color'] ?> badge-status"><?= $ac['label'] ?></span>
              <?php else: ?>
              <span class="text-muted" style="font-size:.75rem">—</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="/rdv/viagens/<?= $v->id ?>" class="btn btn-xs btn-outline-primary" title="Ver"><i class="fas fa-eye"></i></a>
              <a href="/rdv/viagens/<?= $v->id ?>/edit" class="btn btn-xs btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
