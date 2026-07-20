<?php
use App\Core\View;

$isAdmin        = $isAdmin        ?? false;
$filtros        = $filtros        ?? [];
$oportunidades  = $oportunidades  ?? [];
$kpis           = $kpis           ?? [];
$evolucao       = $evolucao       ?? [];
$ranking        = $ranking        ?? [];
$usuariosAtivos = $usuariosAtivos ?? [];
$etapasList     = $etapasList     ?? [];
$statusList     = $statusList     ?? [];
$tiposContrato  = $tiposContrato  ?? [];
$modalidades    = $modalidades    ?? [];

function fmtMoeda3(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}
function fmtMes3(string $ym): string {
    [$y, $m] = explode('-', $ym);
    $meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return ($meses[(int)$m] ?? $m) . '/' . substr($y, 2);
}

$etapaColors = [
    'qualificacao' => '#3b82f6',
    'proposta'     => '#f59e0b',
    'negociacao'   => '#8b5cf6',
    'fechamento'   => '#22c55e',
];
$exportParams = http_build_query(array_merge($_GET, ['tipo' => 'oportunidades']));
?>
<style>
.rel-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rel-tabs a{padding:.45rem 1rem;border-radius:.5rem;font-size:.8125rem;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;transition:.15s}
.rel-tabs a.active,.rel-tabs a:hover{background:#3b82f6;color:#fff}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.kpi-card .kpi-val{font-size:1.4rem;font-weight:700;line-height:1.1;color:#1e293b}
.kpi-card .kpi-lbl{font-size:.75rem;color:#64748b;margin-top:2px}
.section-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:1.5rem}
.section-title{font-size:.875rem;font-weight:700;color:#1e293b;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.filter-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;margin-bottom:1.5rem}
.filter-bar .form-label{font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.25rem}
.filter-bar .form-control,.filter-bar .form-select{font-size:.8125rem;height:34px;padding:.3rem .6rem}
.table-rel th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc}
.table-rel td{font-size:.8rem;vertical-align:middle}
.badge-aberta{background:#dbeafe;color:#1d4ed8}
.badge-ganha{background:#d1fae5;color:#065f46}
.badge-perdida{background:#fee2e2;color:#991b1b}
.rank-num{width:26px;height:26px;border-radius:50%;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:#475569}
</style>

<div class="container-fluid">
  <!-- Tabs -->
  <div class="rel-tabs">
    <a href="/crm/relatorios"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
    <a href="/crm/relatorios/leads"><i class="fas fa-user-plus me-1"></i>Leads</a>
    <a href="/crm/relatorios/oportunidades" class="active"><i class="fas fa-chart-line me-1"></i>Oportunidades</a>
    <a href="/crm/relatorios/interacoes"><i class="fas fa-comments me-1"></i>Interações</a>
  </div>

  <!-- Filtros -->
  <div class="filter-bar">
    <form method="GET" action="/crm/relatorios/oportunidades" class="row g-2 align-items-end">
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
        <label class="form-label">Etapa</label>
        <select name="etapa_funil" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($etapasList as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['etapa_funil'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label">Status</label>
        <select name="status_oportunidade" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($statusList as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['status_oportunidade'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label">Tipo Contrato</label>
        <select name="tipo_contrato" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($tiposContrato as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['tipo_contrato'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
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
        <a href="/crm/relatorios/oportunidades" class="btn btn-outline-secondary btn-sm ms-1">Limpar</a>
        <a href="/crm/relatorios/exportar?<?php echo $exportParams; ?>" class="btn btn-success btn-sm ms-1">
          <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </a>
      </div>
    </form>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-val"><?php echo $kpis['totalGeral'] ?? 0; ?></div>
      <div class="kpi-lbl">Total Geral</div>
    </div>
    <div class="kpi-card" style="border-color:#dbeafe">
      <div class="kpi-val text-primary"><?php echo $kpis['totalAberta'] ?? 0; ?></div>
      <div class="kpi-lbl">Em Aberto</div>
    </div>
    <div class="kpi-card" style="border-color:#d1fae5">
      <div class="kpi-val text-success"><?php echo $kpis['totalGanha'] ?? 0; ?></div>
      <div class="kpi-lbl">Ganhas</div>
    </div>
    <div class="kpi-card" style="border-color:#fee2e2">
      <div class="kpi-val text-danger"><?php echo $kpis['totalPerdida'] ?? 0; ?></div>
      <div class="kpi-lbl">Perdidas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val" style="font-size:1rem"><?php echo fmtMoeda3((float)($kpis['valorGanha'] ?? 0)); ?></div>
      <div class="kpi-lbl">Valor Ganho</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val" style="font-size:1rem"><?php echo fmtMoeda3((float)($kpis['valorAberta'] ?? 0)); ?></div>
      <div class="kpi-lbl">Pipeline Aberto</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val"><?php echo ($kpis['taxaConversao'] ?? 0); ?>%</div>
      <div class="kpi-lbl">Taxa de Conversão</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-val" style="font-size:1rem"><?php echo fmtMoeda3((float)($kpis['ticketMedio'] ?? 0)); ?></div>
      <div class="kpi-lbl">Ticket Médio</div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <!-- Gráfico Funil por Etapa -->
    <div class="col-md-4">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-filter text-warning"></i> Pipeline por Etapa</div>
        <canvas id="chartEtapa" height="240"></canvas>
      </div>
    </div>
    <!-- Gráfico Evolução Mensal -->
    <div class="col-md-8">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-chart-bar text-success"></i> Evolução Mensal</div>
        <canvas id="chartEvolucao" height="240"></canvas>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <!-- Motivos de Perda -->
    <?php if (!empty($kpis['motivosPerda'])): ?>
    <div class="col-md-4">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-times-circle text-danger"></i> Motivos de Perda</div>
        <canvas id="chartMotivos" height="200"></canvas>
      </div>
    </div>
    <?php endif; ?>
    <!-- Tipos de Contrato -->
    <?php if (!empty($kpis['porTipoContrato'])): ?>
    <div class="col-md-<?php echo !empty($kpis['motivosPerda']) ? '8' : '12'; ?>">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-file-contract text-primary"></i> Por Tipo de Contrato</div>
        <div class="table-responsive">
          <table class="table table-sm table-rel mb-0">
            <thead><tr><th>Tipo</th><th class="text-center">Qtd</th><th class="text-end">Valor Total</th></tr></thead>
            <tbody>
              <?php foreach ($kpis['porTipoContrato'] as $tc): ?>
              <tr>
                <td><?php echo htmlspecialchars($tiposContrato[$tc->tipo_contrato] ?? $tc->tipo_contrato); ?></td>
                <td class="text-center"><?php echo $tc->total; ?></td>
                <td class="text-end fw-semibold"><?php echo fmtMoeda3((float)$tc->valor_total); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Ranking de Responsáveis -->
  <?php if (!empty($ranking)): ?>
  <div class="section-card">
    <div class="section-title"><i class="fas fa-medal text-warning"></i> Ranking de Responsáveis</div>
    <div class="table-responsive">
      <table class="table table-hover table-rel mb-0">
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
          <?php foreach ($ranking as $i => $r): ?>
          <?php $fechadas = $r->ganhas + $r->perdidas; $conv = $fechadas > 0 ? round(($r->ganhas / $fechadas) * 100, 1) : 0; ?>
          <tr>
            <td><span class="rank-num"><?php echo $i + 1; ?></span></td>
            <td><strong><?php echo htmlspecialchars($r->responsavel ?? '—'); ?></strong></td>
            <td class="text-center"><?php echo $r->total_oportunidades; ?></td>
            <td class="text-center"><span class="badge bg-success"><?php echo $r->ganhas; ?></span></td>
            <td class="text-center"><span class="badge bg-danger"><?php echo $r->perdidas; ?></span></td>
            <td class="text-center"><span class="badge bg-primary"><?php echo $r->abertas; ?></span></td>
            <td class="text-end fw-bold text-success"><?php echo fmtMoeda3((float)$r->valor_ganho); ?></td>
            <td class="text-end text-primary"><?php echo fmtMoeda3((float)$r->valor_pipeline); ?></td>
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

  <!-- Tabela de Oportunidades -->
  <div class="section-card">
    <div class="section-title">
      <i class="fas fa-list text-primary"></i> Listagem de Oportunidades
      <span class="badge bg-secondary ms-1"><?php echo count($oportunidades); ?></span>
    </div>
    <?php if (empty($oportunidades)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
      Nenhuma oportunidade encontrada com os filtros selecionados.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover table-rel mb-0">
        <thead>
          <tr>
            <th>Título</th>
            <th>Contato</th>
            <th>Etapa</th>
            <th>Status</th>
            <th class="text-end">Valor (R$)</th>
            <th class="text-center">Prob.%</th>
            <th>Tipo Contrato</th>
            <th class="text-center">Interações</th>
            <th>Fechamento Prev.</th>
            <th>Responsável</th>
            <th>Criado em</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($oportunidades as $op): ?>
          <tr>
            <td>
              <a href="/crm/oportunidades/edit/<?php echo $op->id; ?>" class="fw-semibold text-decoration-none text-dark">
                <?php echo htmlspecialchars($op->titulo_oportunidade ?? '—'); ?>
              </a>
            </td>
            <td><?php echo htmlspecialchars($op->nome_contato ?? '—'); ?></td>
            <td>
              <?php $ec = $etapaColors[$op->etapa_funil] ?? '#94a3b8'; ?>
              <span class="badge" style="background:<?php echo $ec; ?>20;color:<?php echo $ec; ?>;border:1px solid <?php echo $ec; ?>40">
                <?php echo htmlspecialchars($etapasList[$op->etapa_funil] ?? $op->etapa_funil); ?>
              </span>
            </td>
            <td>
              <?php $sc = ['aberta' => 'badge-aberta', 'ganha' => 'badge-ganha', 'perdida' => 'badge-perdida'][$op->status_oportunidade] ?? ''; ?>
              <span class="badge <?php echo $sc; ?>"><?php echo htmlspecialchars($statusList[$op->status_oportunidade] ?? $op->status_oportunidade); ?></span>
            </td>
            <td class="text-end fw-semibold">
              <?php echo !empty($op->valor_estimado) ? fmtMoeda3((float)$op->valor_estimado) : '—'; ?>
            </td>
            <td class="text-center">
              <?php if (!empty($op->probabilidade_sucesso)): ?>
              <div class="progress" style="height:5px"><div class="progress-bar" style="width:<?php echo $op->probabilidade_sucesso; ?>%"></div></div>
              <small><?php echo $op->probabilidade_sucesso; ?>%</small>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($tiposContrato[$op->tipo_contrato] ?? ($op->tipo_contrato ?? '—')); ?></td>
            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $op->total_interacoes; ?></span></td>
            <td>
              <?php echo !empty($op->data_fechamento_prevista) ? date('d/m/Y', strtotime($op->data_fechamento_prevista)) : '—'; ?>
            </td>
            <td><?php echo htmlspecialchars($op->responsavel ?? '—'); ?></td>
            <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($op->created_at)); ?></small></td>
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
  function fmtMes3(ym) {
    const [y, m] = ym.split('-');
    const meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return (meses[parseInt(m)] || m) + '/' + y.slice(2);
  }

  // Gráfico Etapa (Doughnut)
  const etapasData = <?php
    $etapas = $kpis['porEtapa'] ?? [];
    $labels = [];
    $vals   = [];
    $cols   = [];
    foreach ($etapasList as $k => $v) {
        $labels[] = $v;
        $vals[]   = (int)($etapas[$k]->total ?? 0);
        $cols[]   = $etapaColors[$k] ?? '#94a3b8';
    }
    echo json_encode(['labels' => $labels, 'data' => $vals, 'colors' => $cols]);
  ?>;
  new Chart(document.getElementById('chartEtapa'), {
    type: 'doughnut',
    data: { labels: etapasData.labels, datasets: [{ data: etapasData.data, backgroundColor: etapasData.colors, borderWidth: 2 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
  });

  // Gráfico Evolução
  const evolLabels  = <?php echo json_encode(array_map(fn($r) => fmtMes3($r->mes), $evolucao)); ?>;
  const evolTotal   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolucao)); ?>;
  const evolGanhas  = <?php echo json_encode(array_map(fn($r) => (int)$r->ganhas, $evolucao)); ?>;
  const evolPerdidas= <?php echo json_encode(array_map(fn($r) => (int)$r->perdidas, $evolucao)); ?>;

  new Chart(document.getElementById('chartEvolucao'), {
    type: 'bar',
    data: {
      labels: evolLabels,
      datasets: [
        { label: 'Total', data: evolTotal, backgroundColor: '#94a3b8', borderRadius: 3 },
        { label: 'Ganhas', data: evolGanhas, backgroundColor: '#22c55e', borderRadius: 3 },
        { label: 'Perdidas', data: evolPerdidas, backgroundColor: '#ef4444', borderRadius: 3 },
      ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });

  // Gráfico Motivos de Perda
  <?php if (!empty($kpis['motivosPerda'])): ?>
  const motivosLabels = <?php echo json_encode(array_map(fn($r) => $r->motivo_perda, $kpis['motivosPerda'])); ?>;
  const motivosData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $kpis['motivosPerda'])); ?>;
  new Chart(document.getElementById('chartMotivos'), {
    type: 'bar',
    data: { labels: motivosLabels, datasets: [{ data: motivosData, backgroundColor: '#ef4444', borderRadius: 3 }] },
    options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
  <?php endif; ?>
})();
</script>
