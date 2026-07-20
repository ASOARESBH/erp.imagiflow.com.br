<?php
use App\Core\View;

$isAdmin        = $isAdmin        ?? false;
$filtros        = $filtros        ?? [];
$usuariosAtivos = $usuariosAtivos ?? [];
$kpisLeads      = $kpisLeads      ?? [];
$kpisOps        = $kpisOportunidades ?? [];
$kpisInt        = $kpisInteracoes ?? [];
$rankingResp    = $rankingResp    ?? [];
$evolLeads      = $evolLeads      ?? [];
$evolOps        = $evolOps        ?? [];

// Helpers de formatação
function fmtMoeda(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}
function fmtMes(string $ym): string {
    [$y, $m] = explode('-', $ym);
    $meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return ($meses[(int)$m] ?? $m) . '/' . substr($y, 2);
}
?>
<style>
.rel-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem}
.rel-tabs{display:flex;gap:.5rem;flex-wrap:wrap}
.rel-tabs a{padding:.45rem 1rem;border-radius:.5rem;font-size:.8125rem;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;transition:.15s}
.rel-tabs a.active,.rel-tabs a:hover{background:#3b82f6;color:#fff}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.kpi-card .kpi-val{font-size:1.6rem;font-weight:700;line-height:1.1;color:#1e293b}
.kpi-card .kpi-lbl{font-size:.75rem;color:#64748b;margin-top:2px}
.kpi-card .kpi-icon{float:right;width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.section-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:1.5rem}
.section-title{font-size:.875rem;font-weight:700;color:#1e293b;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.filter-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;margin-bottom:1.5rem}
.filter-bar .form-label{font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.25rem}
.filter-bar .form-control,.filter-bar .form-select{font-size:.8125rem;height:34px;padding:.3rem .6rem}
.table-rel th{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc}
.table-rel td{font-size:.8125rem;vertical-align:middle}
.badge-etapa{font-size:.65rem;padding:.25em .6em;border-radius:20px;font-weight:700}
.badge-aberta{background:#dbeafe;color:#1d4ed8}
.badge-ganha{background:#d1fae5;color:#065f46}
.badge-perdida{background:#fee2e2;color:#991b1b}
.rank-num{width:28px;height:28px;border-radius:50%;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#475569}
</style>

<div class="container-fluid">

  <!-- Header + Tabs -->
  <div class="rel-header">
    <div>
      <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar text-primary me-2"></i>Relatórios CRM</h5>
      <small class="text-muted">Análise de Leads, Oportunidades e Interações</small>
    </div>
    <div class="rel-tabs">
      <a href="/crm/relatorios" class="active"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
      <a href="/crm/relatorios/leads"><i class="fas fa-user-plus me-1"></i>Leads</a>
      <a href="/crm/relatorios/oportunidades"><i class="fas fa-chart-line me-1"></i>Oportunidades</a>
      <a href="/crm/relatorios/interacoes"><i class="fas fa-comments me-1"></i>Interações</a>
    </div>
  </div>

  <!-- Filtros -->
  <div class="filter-bar">
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
  <div class="section-title"><i class="fas fa-user-plus text-info"></i> Leads</div>
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-users"></i></div>
      <div class="kpi-val"><?php echo $kpisLeads['total'] ?? 0; ?></div>
      <div class="kpi-lbl">Total de Leads</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-check-circle"></i></div>
      <div class="kpi-val"><?php echo $kpisLeads['porStatus']['qualificado'] ?? 0; ?></div>
      <div class="kpi-lbl">Qualificados</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-phone"></i></div>
      <div class="kpi-val"><?php echo $kpisLeads['porStatus']['contatado'] ?? 0; ?></div>
      <div class="kpi-lbl">Contatados</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-clock"></i></div>
      <div class="kpi-val"><?php echo $kpisLeads['vencidos'] ?? 0; ?></div>
      <div class="kpi-lbl">Contato Vencido</div>
    </div>
  </div>

  <!-- KPIs Oportunidades -->
  <div class="section-title"><i class="fas fa-chart-line text-success"></i> Oportunidades</div>
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-folder-open"></i></div>
      <div class="kpi-val"><?php echo $kpisOps['totalAberta'] ?? 0; ?></div>
      <div class="kpi-lbl">Em Aberto</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-trophy"></i></div>
      <div class="kpi-val"><?php echo $kpisOps['totalGanha'] ?? 0; ?></div>
      <div class="kpi-lbl">Ganhas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-dollar-sign"></i></div>
      <div class="kpi-val" style="font-size:1.1rem"><?php echo fmtMoeda((float)($kpisOps['valorGanha'] ?? 0)); ?></div>
      <div class="kpi-lbl">Valor Ganho</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-percentage"></i></div>
      <div class="kpi-val"><?php echo ($kpisOps['taxaConversao'] ?? 0); ?>%</div>
      <div class="kpi-lbl">Taxa de Conversão</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-wallet"></i></div>
      <div class="kpi-val" style="font-size:1.1rem"><?php echo fmtMoeda((float)($kpisOps['valorAberta'] ?? 0)); ?></div>
      <div class="kpi-lbl">Pipeline Aberto</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-times-circle"></i></div>
      <div class="kpi-val"><?php echo $kpisOps['totalPerdida'] ?? 0; ?></div>
      <div class="kpi-lbl">Perdidas</div>
    </div>
  </div>

  <!-- KPIs Interações -->
  <div class="section-title"><i class="fas fa-comments text-warning"></i> Interações</div>
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-comments"></i></div>
      <div class="kpi-val"><?php echo $kpisInt['total'] ?? 0; ?></div>
      <div class="kpi-lbl">Total de Interações</div>
    </div>
    <?php foreach (array_slice($kpisInt['porTipo'] ?? [], 0, 3) as $tipo): ?>
    <div class="kpi-card">
      <div class="kpi-val"><?php echo $tipo->total; ?></div>
      <div class="kpi-lbl"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $tipo->tipo_interacao))); ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <!-- Gráfico: Evolução de Leads -->
    <div class="col-md-6">
      <div class="section-card">
        <div class="section-title"><i class="fas fa-chart-area text-info"></i> Evolução Mensal de Leads</div>
        <canvas id="chartLeads" height="200"></canvas>
      </div>
    </div>
    <!-- Gráfico: Evolução de Oportunidades -->
    <div class="col-md-6">
      <div class="section-card">
        <div class="section-title"><i class="fas fa-chart-bar text-success"></i> Evolução Mensal de Oportunidades</div>
        <canvas id="chartOps" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Ranking de Responsáveis -->
  <?php if (!empty($rankingResp)): ?>
  <div class="section-card mt-3">
    <div class="section-title"><i class="fas fa-medal text-warning"></i> Ranking de Responsáveis</div>
    <div class="table-responsive">
      <table class="table table-hover table-rel mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Responsável</th>
            <th class="text-center">Total Ops</th>
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
          <?php
            $fechadas = ($r->ganhas + $r->perdidas);
            $conv = $fechadas > 0 ? round(($r->ganhas / $fechadas) * 100, 1) : 0;
          ?>
          <tr>
            <td><span class="rank-num"><?php echo $i + 1; ?></span></td>
            <td><strong><?php echo htmlspecialchars($r->responsavel ?? 'N/A'); ?></strong></td>
            <td class="text-center"><?php echo $r->total_oportunidades; ?></td>
            <td class="text-center"><span class="badge bg-success"><?php echo $r->ganhas; ?></span></td>
            <td class="text-center"><span class="badge bg-danger"><?php echo $r->perdidas; ?></span></td>
            <td class="text-center"><span class="badge bg-primary"><?php echo $r->abertas; ?></span></td>
            <td class="text-end fw-bold text-success"><?php echo fmtMoeda((float)$r->valor_ganho); ?></td>
            <td class="text-end text-primary"><?php echo fmtMoeda((float)$r->valor_pipeline); ?></td>
            <td class="text-center">
              <div class="progress" style="height:6px;min-width:60px">
                <div class="progress-bar bg-success" style="width:<?php echo $conv; ?>%"></div>
              </div>
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
  // Dados: Evolução de Leads
  const leadsLabels = <?php echo json_encode(array_map(fn($r) => fmtMes($r->mes), $evolLeads)); ?>;
  const leadsData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolLeads)); ?>;

  // Dados: Evolução de Oportunidades
  const opsLabels  = <?php echo json_encode(array_map(fn($r) => fmtMes($r->mes), $evolOps)); ?>;
  const opsTotal   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolOps)); ?>;
  const opsGanhas  = <?php echo json_encode(array_map(fn($r) => (int)$r->ganhas, $evolOps)); ?>;
  const opsPerdidas= <?php echo json_encode(array_map(fn($r) => (int)$r->perdidas, $evolOps)); ?>;

  const defaults = { responsive: true, plugins: { legend: { position: 'bottom' } } };

  new Chart(document.getElementById('chartLeads'), {
    type: 'bar',
    data: {
      labels: leadsLabels,
      datasets: [{ label: 'Leads Criados', data: leadsData, backgroundColor: '#3b82f6', borderRadius: 4 }]
    },
    options: { ...defaults, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });

  new Chart(document.getElementById('chartOps'), {
    type: 'bar',
    data: {
      labels: opsLabels,
      datasets: [
        { label: 'Total', data: opsTotal, backgroundColor: '#94a3b8', borderRadius: 4 },
        { label: 'Ganhas', data: opsGanhas, backgroundColor: '#22c55e', borderRadius: 4 },
        { label: 'Perdidas', data: opsPerdidas, backgroundColor: '#ef4444', borderRadius: 4 },
      ]
    },
    options: { ...defaults, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
})();
</script>
