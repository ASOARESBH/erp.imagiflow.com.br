<?php
use App\Core\View;

$isAdmin        = $isAdmin        ?? false;
$filtros        = $filtros        ?? [];
$leads          = $leads          ?? [];
$kpis           = $kpis           ?? [];
$evolucao       = $evolucao       ?? [];
$usuariosAtivos = $usuariosAtivos ?? [];
$statusList     = $statusList     ?? [];
$origensList    = $origensList    ?? [];
$segmentosList  = $segmentosList  ?? [];

$statusColors = [
    'novo'        => 'secondary',
    'contatado'   => 'info',
    'qualificado' => 'success',
    'descartado'  => 'danger',
];

function fmtMes2(string $ym): string {
    [$y, $m] = explode('-', $ym);
    $meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return ($meses[(int)$m] ?? $m) . '/' . substr($y, 2);
}

// Monta query string atual sem a paginação para exportação
$exportParams = http_build_query(array_merge($_GET, ['tipo' => 'leads']));
?>
<style>
.rel-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rel-tabs a{padding:.45rem 1rem;border-radius:.5rem;font-size:.8125rem;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;transition:.15s}
.rel-tabs a.active,.rel-tabs a:hover{background:#3b82f6;color:#fff}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.kpi-card .kpi-val{font-size:1.5rem;font-weight:700;line-height:1.1;color:#1e293b}
.kpi-card .kpi-lbl{font-size:.75rem;color:#64748b;margin-top:2px}
.section-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:1.5rem}
.section-title{font-size:.875rem;font-weight:700;color:#1e293b;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.filter-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;margin-bottom:1.5rem}
.filter-bar .form-label{font-size:.75rem;font-weight:600;color:#475569;margin-bottom:.25rem}
.filter-bar .form-control,.filter-bar .form-select{font-size:.8125rem;height:34px;padding:.3rem .6rem}
.table-rel th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc}
.table-rel td{font-size:.8rem;vertical-align:middle}
.vencido{color:#dc2626;font-weight:600}
</style>

<div class="container-fluid">
  <!-- Tabs -->
  <div class="rel-tabs">
    <a href="/crm/relatorios"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
    <a href="/crm/relatorios/leads" class="active"><i class="fas fa-user-plus me-1"></i>Leads</a>
    <a href="/crm/relatorios/oportunidades"><i class="fas fa-chart-line me-1"></i>Oportunidades</a>
    <a href="/crm/relatorios/interacoes"><i class="fas fa-comments me-1"></i>Interações</a>
  </div>

  <!-- Filtros -->
  <div class="filter-bar">
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
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-val"><?php echo $kpis['total'] ?? 0; ?></div>
      <div class="kpi-lbl">Total de Leads</div>
    </div>
    <?php foreach ($statusList as $k => $v): ?>
    <div class="kpi-card">
      <div class="kpi-val">
        <span class="badge bg-<?php echo $statusColors[$k] ?? 'secondary'; ?> fs-6">
          <?php echo $kpis['porStatus'][$k] ?? 0; ?>
        </span>
      </div>
      <div class="kpi-lbl"><?php echo $v; ?></div>
    </div>
    <?php endforeach; ?>
    <div class="kpi-card" style="border-color:#fee2e2">
      <div class="kpi-val text-danger"><?php echo $kpis['vencidos'] ?? 0; ?></div>
      <div class="kpi-lbl">Contato Vencido</div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <!-- Gráfico Origem -->
    <div class="col-md-5">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-chart-pie text-info"></i> Leads por Origem</div>
        <canvas id="chartOrigem" height="220"></canvas>
      </div>
    </div>
    <!-- Gráfico Evolução -->
    <div class="col-md-7">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-chart-area text-primary"></i> Evolução Mensal</div>
        <canvas id="chartEvolucao" height="220"></canvas>
      </div>
    </div>
  </div>

  <!-- Tabela de Leads -->
  <div class="section-card">
    <div class="section-title">
      <i class="fas fa-list text-primary"></i> Listagem de Leads
      <span class="badge bg-secondary ms-1"><?php echo count($leads); ?></span>
    </div>
    <?php if (empty($leads)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
      Nenhum lead encontrado com os filtros selecionados.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover table-rel mb-0" id="tabelaLeads">
        <thead>
          <tr>
            <th>Nome / Empresa</th>
            <th>Contato</th>
            <th>Status</th>
            <th>Origem</th>
            <th>Segmento</th>
            <th class="text-center">Interações</th>
            <th>Última Interação</th>
            <th>Próx. Contato</th>
            <th>Responsável</th>
            <th>Cadastrado em</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $l): ?>
          <?php
            $vencido = !empty($l->data_proximo_contato) && $l->data_proximo_contato < date('Y-m-d');
          ?>
          <tr>
            <td>
              <a href="/crm/leads/edit/<?php echo $l->id; ?>" class="fw-semibold text-decoration-none text-dark">
                <?php echo htmlspecialchars($l->nome_lead ?? '—'); ?>
              </a>
              <?php if (!empty($l->razao_social)): ?>
              <div class="text-muted" style="font-size:.72rem"><?php echo htmlspecialchars($l->razao_social); ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($l->email)): ?>
              <div style="font-size:.75rem"><i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($l->email); ?></div>
              <?php endif; ?>
              <?php if (!empty($l->telefone)): ?>
              <div style="font-size:.75rem"><i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($l->telefone); ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-<?php echo $statusColors[$l->status_lead] ?? 'secondary'; ?>">
                <?php echo htmlspecialchars($statusList[$l->status_lead] ?? $l->status_lead); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($origensList[$l->origem] ?? ($l->origem ?? '—')); ?></td>
            <td><?php echo htmlspecialchars($segmentosList[$l->segmento_principal] ?? ($l->segmento_principal ?? '—')); ?></td>
            <td class="text-center">
              <span class="badge bg-light text-dark border"><?php echo $l->total_interacoes; ?></span>
            </td>
            <td>
              <?php if (!empty($l->ultima_interacao)): ?>
              <small class="text-muted"><?php echo date('d/m/Y', strtotime($l->ultima_interacao)); ?></small>
              <?php else: ?>
              <small class="text-muted">—</small>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($l->data_proximo_contato)): ?>
              <span class="<?php echo $vencido ? 'vencido' : ''; ?>">
                <?php echo date('d/m/Y', strtotime($l->data_proximo_contato)); ?>
                <?php if ($vencido): ?><i class="fas fa-exclamation-circle ms-1"></i><?php endif; ?>
              </span>
              <?php else: ?>
              <small class="text-muted">—</small>
              <?php endif; ?>
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
  // Gráfico de Origem (Doughnut)
  const origemLabels = <?php echo json_encode(array_map(fn($r) => $r->origem, $kpis['porOrigem'] ?? [])); ?>;
  const origemData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $kpis['porOrigem'] ?? [])); ?>;
  const colors = ['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316'];

  if (origemLabels.length > 0) {
    new Chart(document.getElementById('chartOrigem'), {
      type: 'doughnut',
      data: { labels: origemLabels, datasets: [{ data: origemData, backgroundColor: colors, borderWidth: 2 }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
    });
  }

  // Gráfico de Evolução (Bar)
  const evolLabels = <?php echo json_encode(array_map(fn($r) => fmtMes2($r->mes), $evolucao)); ?>;
  const evolData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $evolucao)); ?>;

  if (evolLabels.length > 0) {
    new Chart(document.getElementById('chartEvolucao'), {
      type: 'bar',
      data: { labels: evolLabels, datasets: [{ label: 'Leads', data: evolData, backgroundColor: '#3b82f6', borderRadius: 4 }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }

  function fmtMes2(ym) {
    const [y, m] = ym.split('-');
    const meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return (meses[parseInt(m)] || m) + '/' + y.slice(2);
  }
})();
</script>
