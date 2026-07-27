<?php
// Variáveis injetadas pelo controller
$isAdmin        = $isAdmin        ?? false;
$filtros        = $filtros        ?? [];
$usuariosAtivos = $usuariosAtivos ?? [];
$kpisLeads      = $kpisLeads      ?? [];
$kpisOps        = $kpisOportunidades ?? [];
$kpisInt        = $kpisInteracoes ?? [];
$rankingResp    = $rankingResp    ?? [];
$evolLeads      = $evolLeads      ?? [];
$evolOps        = $evolOps        ?? [];

function relFmtMoeda(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}
function relFmtMes(string $ym): string {
    [$y, $m] = explode('-', $ym);
    $meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return ($meses[(int)$m] ?? $m) . '/' . substr($y, 2);
}
?>
<style>
.rel-nav-pills{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rel-nav-pills a{padding:.4rem .9rem;border-radius:.4rem;font-size:.8rem;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;transition:.15s}
.rel-nav-pills a.active{background:#0d6efd;color:#fff;border-color:#0d6efd}
.rel-nav-pills a:hover:not(.active){background:#e2e8f0;color:#1e293b}
.rel-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:.875rem;margin-bottom:1.25rem}
.rel-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:.9rem 1.1rem;display:flex;align-items:center;gap:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.rel-kpi-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.rel-kpi-val{font-size:1.4rem;font-weight:700;line-height:1.1;color:#1e293b}
.rel-kpi-lbl{font-size:.72rem;color:#64748b;margin-top:1px}
.rel-section{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:1.25rem}
.rel-section-title{font-size:.8125rem;font-weight:700;color:#374151;margin-bottom:.875rem;display:flex;align-items:center;gap:.4rem;border-bottom:1px solid #f1f5f9;padding-bottom:.5rem}
.rel-filter-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:.875rem 1.1rem;margin-bottom:1.25rem}
.rel-filter-bar .form-label{font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.2rem}
.rel-filter-bar .form-control,.rel-filter-bar .form-select{font-size:.8rem;height:32px;padding:.25rem .55rem}
.rel-table th{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc;padding:.5rem .75rem}
.rel-table td{font-size:.8rem;vertical-align:middle;padding:.5rem .75rem}
.rank-pos{width:24px;height:24px;border-radius:50%;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#475569}
.section-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin:1.25rem 0 .5rem}
</style>

<div class="container-fluid">

  <!-- Navegação entre relatórios -->
  <div class="rel-nav-pills">
    <a href="/crm/relatorios" class="active"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
    <a href="/crm/relatorios/leads"><i class="fas fa-user-plus me-1"></i>Leads</a>
    <a href="/crm/relatorios/oportunidades"><i class="fas fa-chart-line me-1"></i>Oportunidades</a>
    <a href="/crm/relatorios/interacoes"><i class="fas fa-comments me-1"></i>Interações</a>
  </div>

  <!-- Filtros -->
  <div class="rel-filter-bar">
    <form method="GET" action="/crm/relatorios" class="row g-2 align-items-end">
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
        <label class="form-label">De</label>
        <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? ''); ?>">
      </div>
      <div class="col-auto">
        <label class="form-label">Até</label>
        <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? ''); ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
        <a href="/crm/relatorios" class="btn btn-outline-secondary btn-sm ms-1">Limpar</a>
      </div>
    </form>
  </div>

  <!-- KPIs Leads -->
  <div class="section-label"><i class="fas fa-user-plus me-1"></i>Leads</div>
  <div class="rel-kpi-grid">
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-users"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisLeads['total'] ?? 0; ?></div><div class="rel-kpi-lbl">Total de Leads</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-check-circle"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisLeads['porStatus']['qualificado'] ?? 0; ?></div><div class="rel-kpi-lbl">Qualificados</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-phone"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisLeads['porStatus']['contatado'] ?? 0; ?></div><div class="rel-kpi-lbl">Contatados</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-clock"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisLeads['vencidos'] ?? 0; ?></div><div class="rel-kpi-lbl">Contato Vencido</div></div>
    </div>
  </div>

  <!-- KPIs Oportunidades -->
  <div class="section-label"><i class="fas fa-chart-line me-1"></i>Oportunidades</div>
  <div class="rel-kpi-grid">
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-folder-open"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisOps['totalAberta'] ?? 0; ?></div><div class="rel-kpi-lbl">Em Aberto</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-trophy"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisOps['totalGanha'] ?? 0; ?></div><div class="rel-kpi-lbl">Ganhas</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-dollar-sign"></i></div>
      <div><div class="rel-kpi-val" style="font-size:1rem"><?php echo relFmtMoeda((float)($kpisOps['valorGanha'] ?? 0)); ?></div><div class="rel-kpi-lbl">Valor Ganho</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-percentage"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisOps['taxaConversao'] ?? 0; ?>%</div><div class="rel-kpi-lbl">Taxa de Conversão</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-wallet"></i></div>
      <div><div class="rel-kpi-val" style="font-size:1rem"><?php echo relFmtMoeda((float)($kpisOps['valorAberta'] ?? 0)); ?></div><div class="rel-kpi-lbl">Pipeline Aberto</div></div>
    </div>
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-times-circle"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisOps['totalPerdida'] ?? 0; ?></div><div class="rel-kpi-lbl">Perdidas</div></div>
    </div>
  </div>

  <!-- KPIs Interações -->
  <div class="section-label"><i class="fas fa-comments me-1"></i>Interações</div>
  <div class="rel-kpi-grid">
    <div class="rel-kpi">
      <div class="rel-kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-comments"></i></div>
      <div><div class="rel-kpi-val"><?php echo $kpisInt['total'] ?? 0; ?></div><div class="rel-kpi-lbl">Total de Interações</div></div>
    </div>
    <?php foreach (array_slice($kpisInt['porTipo'] ?? [], 0, 4) as $tipo): ?>
    <div class="rel-kpi">
      <div><div class="rel-kpi-val"><?php echo $tipo->total; ?></div><div class="rel-kpi-lbl"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $tipo->tipo_interacao))); ?></div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Gráficos -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="rel-section">
        <div class="rel-section-title"><i class="fas fa-chart-area text-info"></i> Evolução Mensal de Leads</div>
        <canvas id="chartLeads" height="200"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="rel-section">
        <div class="rel-section-title"><i class="fas fa-chart-bar text-success"></i> Evolução Mensal de Oportunidades</div>
        <canvas id="chartOps" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Ranking de Responsáveis -->
  <?php if (!empty($rankingResp)): ?>
  <div class="rel-section">
    <div class="rel-section-title"><i class="fas fa-medal text-warning"></i> Ranking de Responsáveis</div>
    <div class="table-responsive">
      <table class="table table-hover rel-table mb-0">
        <thead>
          <tr>
            <th>#</th><th>Responsável</th>
            <th class="text-center">Total</th>
            <th class="text-center">Ganhas</th>
            <th class="text-center">Perdidas</th>
            <th class="text-center">Abertas</th>
            <th class="text-end">Valor Ganho</th>
            <th class="text-end">Pipeline</th>
            <th class="text-center">Conversão</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rankingResp as $i => $r): ?>
          <?php $fechadas = $r->ganhas + $r->perdidas; $conv = $fechadas > 0 ? round(($r->ganhas / $fechadas) * 100, 1) : 0; ?>
          <tr>
            <td><span class="rank-pos"><?php echo $i + 1; ?></span></td>
            <td><strong><?php echo htmlspecialchars($r->responsavel ?? 'N/A'); ?></strong></td>
            <td class="text-center"><?php echo $r->total_oportunidades; ?></td>
            <td class="text-center"><span class="badge bg-success"><?php echo $r->ganhas; ?></span></td>
            <td class="text-center"><span class="badge bg-danger"><?php echo $r->perdidas; ?></span></td>
            <td class="text-center"><span class="badge bg-primary"><?php echo $r->abertas; ?></span></td>
            <td class="text-end fw-bold text-success"><?php echo relFmtMoeda((float)$r->valor_ganho); ?></td>
            <td class="text-end text-primary"><?php echo relFmtMoeda((float)$r->valor_pipeline); ?></td>
            <td class="text-center">
              <div class="progress" style="height:5px;min-width:50px"><div class="progress-bar bg-success" style="width:<?php echo $conv; ?>%"></div></div>
              <small class="text-muted"><?php echo $conv; ?>%</small>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  function fmtM(ym) {
    const [y, m] = ym.split('-');
    const ms = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return (ms[parseInt(m)] || m) + '/' + y.slice(2);
  }
  const leadsLabels = <?php echo json_encode(array_map(fn($r) => relFmtMes($r->mes), $evolLeads)); ?>;
  const leadsData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolLeads)); ?>;
  const opsLabels   = <?php echo json_encode(array_map(fn($r) => relFmtMes($r->mes), $evolOps)); ?>;
  const opsTotal    = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolOps)); ?>;
  const opsGanhas   = <?php echo json_encode(array_map(fn($r) => (int)$r->ganhas, $evolOps)); ?>;
  const opsPerdidas = <?php echo json_encode(array_map(fn($r) => (int)$r->perdidas, $evolOps)); ?>;

  const opts = { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } };

  if (leadsLabels.length) new Chart(document.getElementById('chartLeads'), {
    type: 'bar',
    data: { labels: leadsLabels, datasets: [{ label: 'Leads', data: leadsData, backgroundColor: '#3b82f6', borderRadius: 4 }] },
    options: opts
  });

  if (opsLabels.length) new Chart(document.getElementById('chartOps'), {
    type: 'bar',
    data: {
      labels: opsLabels,
      datasets: [
        { label: 'Total', data: opsTotal, backgroundColor: '#94a3b8', borderRadius: 3 },
        { label: 'Ganhas', data: opsGanhas, backgroundColor: '#22c55e', borderRadius: 3 },
        { label: 'Perdidas', data: opsPerdidas, backgroundColor: '#ef4444', borderRadius: 3 },
      ]
    },
    options: opts
  });
})();
</script>
