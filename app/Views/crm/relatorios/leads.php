<?php
$isAdmin        = $isAdmin        ?? false;
$filtros        = $filtros        ?? [];
$leads          = $leads          ?? [];
$kpis           = $kpis           ?? [];
$evolucao       = $evolucao       ?? [];
$usuariosAtivos = $usuariosAtivos ?? [];
$statusList     = $statusList     ?? [];
$origensList    = $origensList    ?? [];
$segmentosList  = $segmentosList  ?? [];

$statusColors = ['novo' => 'secondary', 'contatado' => 'info', 'qualificado' => 'success', 'descartado' => 'danger'];

function leadFmtMes(string $ym): string {
    [$y, $m] = explode('-', $ym);
    $ms = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return ($ms[(int)$m] ?? $m) . '/' . substr($y, 2);
}

$exportParams = http_build_query(array_merge($_GET, ['tipo' => 'leads']));
?>
<style>
.rel-nav-pills{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rel-nav-pills a{padding:.4rem .9rem;border-radius:.4rem;font-size:.8rem;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;transition:.15s}
.rel-nav-pills a.active{background:#0d6efd;color:#fff;border-color:#0d6efd}
.rel-nav-pills a:hover:not(.active){background:#e2e8f0}
.rel-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:.875rem;margin-bottom:1.25rem}
.rel-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:.9rem 1.1rem;display:flex;align-items:center;gap:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.rel-kpi-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.rel-kpi-val{font-size:1.35rem;font-weight:700;line-height:1.1;color:#1e293b}
.rel-kpi-lbl{font-size:.72rem;color:#64748b;margin-top:1px}
.rel-section{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:1.25rem}
.rel-section-title{font-size:.8125rem;font-weight:700;color:#374151;margin-bottom:.875rem;display:flex;align-items:center;gap:.4rem;border-bottom:1px solid #f1f5f9;padding-bottom:.5rem}
.rel-filter-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:.875rem 1.1rem;margin-bottom:1.25rem}
.rel-filter-bar .form-label{font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.2rem}
.rel-filter-bar .form-control,.rel-filter-bar .form-select{font-size:.8rem;height:32px;padding:.25rem .55rem}
.rel-table th{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc;padding:.5rem .75rem}
.rel-table td{font-size:.8rem;vertical-align:middle;padding:.5rem .75rem}
.vencido-txt{color:#dc2626;font-weight:600}
</style>

<div class="container-fluid">

  <!-- Navegação -->
  <div class="rel-nav-pills">
    <a href="/crm/relatorios"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
    <a href="/crm/relatorios/leads" class="active"><i class="fas fa-user-plus me-1"></i>Leads</a>
    <a href="/crm/relatorios/oportunidades"><i class="fas fa-chart-line me-1"></i>Oportunidades</a>
    <a href="/crm/relatorios/interacoes"><i class="fas fa-comments me-1"></i>Interações</a>
  </div>

  <!-- Filtros -->
  <div class="rel-filter-bar">
    <form method="GET" action="/crm/relatorios/leads" class="row g-2 align-items-end">
      <?php if ($isAdmin && !empty($usuariosAtivos)): ?>
      <div class="col-auto">
        <label class="form-label">Responsável</label>
        <select name="usuario_id" class="form-select">
          <option value="0">Todos</option>
          <?php foreach ($usuariosAtivos as $u): ?>
          <option value="<?php echo $u->id; ?>" <?php echo ($filtros['usuario_id'] ?? 0) == $u->id ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($u->name); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-auto">
        <label class="form-label">Status</label>
        <select name="status_lead" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($statusList as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['status_lead'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label">Origem</label>
        <select name="origem" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($origensList as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['origem'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label">Segmento</label>
        <select name="segmento" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($segmentosList as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['segmento'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label">De</label>
        <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? ''); ?>">
      </div>
      <div class="col-auto">
        <label class="form-label">Até</label>
        <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? ''); ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
        <a href="/crm/relatorios/leads" class="btn btn-outline-secondary btn-sm ms-1">Limpar</a>
        <a href="/crm/relatorios/exportar?<?php echo $exportParams; ?>" class="btn btn-success btn-sm ms-1">
          <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </a>
      </div>
    </form>
  </div>

  <!-- KPIs -->
  <div class="rel-kpi-grid">
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-users"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpis['total'] ?? 0; ?></div><div class="rel-kpi-lbl">Total de Leads</div></div>
    </div>
    <?php foreach ($statusList as $k => $v): ?>
    <div class="rel-kpi">
      <div><div class="rel-kpi-val"><span class="badge bg-<?php echo $statusColors[$k] ?? 'secondary'; ?> fs-6"><?php echo $kpis['porStatus'][$k] ?? 0; ?></span></div><div class="rel-kpi-lbl"><?php echo $v; ?></div></div>
    </div>
    <?php endforeach; ?>
    <div class="rel-kpi" style="border-color:#fee2e2">
      <div class="rel-kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-clock"></i></div>
      <div><div class="rel-kpi-val text-danger"><?php echo $kpis['vencidos'] ?? 0; ?></div><div class="rel-kpi-lbl">Contato Vencido</div></div>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="row g-3 mb-3">
    <div class="col-md-5">
      <div class="rel-section h-100">
        <div class="rel-section-title"><i class="fas fa-chart-pie text-info"></i> Leads por Origem</div>
        <canvas id="chartOrigem" height="220"></canvas>
      </div>
    </div>
    <div class="col-md-7">
      <div class="rel-section h-100">
        <div class="rel-section-title"><i class="fas fa-chart-area text-primary"></i> Evolução Mensal</div>
        <canvas id="chartEvolucao" height="220"></canvas>
      </div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="rel-section">
    <div class="rel-section-title">
      <i class="fas fa-list text-primary"></i> Listagem de Leads
      <span class="badge bg-secondary ms-1"><?php echo count($leads); ?></span>
    </div>
    <?php if (empty($leads)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>Nenhum lead encontrado.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover rel-table mb-0">
        <thead>
          <tr>
            <th>Nome / Empresa</th><th>Contato</th><th>Status</th><th>Origem</th>
            <th>Segmento</th><th class="text-center">Interações</th>
            <th>Última Interação</th><th>Próx. Contato</th><th>Responsável</th><th>Cadastrado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $l): ?>
          <?php $vencido = !empty($l->data_proximo_contato) && $l->data_proximo_contato < date('Y-m-d'); ?>
          <tr>
            <td>
              <a href="/crm/leads/edit/<?php echo $l->id; ?>" class="fw-semibold text-decoration-none text-dark">
                <?php echo htmlspecialchars($l->nome_lead ?? '—'); ?>
              </a>
              <?php if (!empty($l->razao_social)): ?>
              <div class="text-muted" style="font-size:.7rem"><?php echo htmlspecialchars($l->razao_social); ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($l->email)): ?><div style="font-size:.72rem"><i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($l->email); ?></div><?php endif; ?>
              <?php if (!empty($l->telefone)): ?><div style="font-size:.72rem"><i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($l->telefone); ?></div><?php endif; ?>
            </td>
            <td><span class="badge bg-<?php echo $statusColors[$l->status_lead] ?? 'secondary'; ?>"><?php echo htmlspecialchars($statusList[$l->status_lead] ?? $l->status_lead); ?></span></td>
            <td><?php echo htmlspecialchars($origensList[$l->origem] ?? ($l->origem ?? '—')); ?></td>
            <td><?php echo htmlspecialchars($segmentosList[$l->segmento_principal] ?? ($l->segmento_principal ?? '—')); ?></td>
            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $l->total_interacoes; ?></span></td>
            <td><small class="text-muted"><?php echo !empty($l->ultima_interacao) ? date('d/m/Y', strtotime($l->ultima_interacao)) : '—'; ?></small></td>
            <td>
              <?php if (!empty($l->data_proximo_contato)): ?>
              <span class="<?php echo $vencido ? 'vencido-txt' : ''; ?>">
                <?php echo date('d/m/Y', strtotime($l->data_proximo_contato)); ?>
                <?php if ($vencido): ?><i class="fas fa-exclamation-circle ms-1"></i><?php endif; ?>
              </span>
              <?php else: ?><small class="text-muted">—</small><?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($l->responsavel ?? '—'); ?></td>
            <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($l->created_at)); ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  const origemLabels = <?php echo json_encode(array_map(fn($r) => $r->origem, $kpis['porOrigem'] ?? [])); ?>;
  const origemData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $kpis['porOrigem'] ?? [])); ?>;
  const colors = ['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316'];

  if (origemLabels.length) new Chart(document.getElementById('chartOrigem'), {
    type: 'doughnut',
    data: { labels: origemLabels, datasets: [{ data: origemData, backgroundColor: colors, borderWidth: 2 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
  });

  const evolLabels = <?php echo json_encode(array_map(fn($r) => leadFmtMes($r->mes), $evolucao)); ?>;
  const evolData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolucao)); ?>;

  if (evolLabels.length) new Chart(document.getElementById('chartEvolucao'), {
    type: 'bar',
    data: { labels: evolLabels, datasets: [{ label: 'Leads', data: evolData, backgroundColor: '#3b82f6', borderRadius: 4 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
})();
</script>
