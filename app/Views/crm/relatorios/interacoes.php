<?php
use App\Core\View;

$isAdmin        = $isAdmin        ?? false;
$filtros        = $filtros        ?? [];
$interacoes     = $interacoes     ?? [];
$kpis           = $kpis           ?? [];
$usuariosAtivos = $usuariosAtivos ?? [];
$tiposInteracao = $tiposInteracao ?? [];

$tipoIcons = [
    'email'              => 'fa-envelope',
    'telefone'           => 'fa-phone',
    'whatsapp'           => 'fa-whatsapp',
    'reuniao_presencial' => 'fa-handshake',
    'reuniao_online'     => 'fa-video',
    'visita_tecnica'     => 'fa-map-marker-alt',
    'proposta_enviada'   => 'fa-file-alt',
    'contrato_enviado'   => 'fa-file-signature',
    'transferencia'      => 'fa-exchange-alt',
    'outro'              => 'fa-comment',
];
$tipoColors = [
    'email'              => '#3b82f6',
    'telefone'           => '#22c55e',
    'whatsapp'           => '#25d366',
    'reuniao_presencial' => '#f59e0b',
    'reuniao_online'     => '#8b5cf6',
    'visita_tecnica'     => '#06b6d4',
    'proposta_enviada'   => '#f97316',
    'contrato_enviado'   => '#10b981',
    'transferencia'      => '#64748b',
    'outro'              => '#94a3b8',
];

function fmtMes4(string $ym): string {
    [$y, $m] = explode('-', $ym);
    $meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return ($meses[(int)$m] ?? $m) . '/' . substr($y, 2);
}

$exportParams = http_build_query(array_merge($_GET, ['tipo' => 'interacoes']));
?>
<style>
.rel-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rel-tabs a{padding:.45rem 1rem;border-radius:.5rem;font-size:.8125rem;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;transition:.15s}
.rel-tabs a.active,.rel-tabs a:hover{background:#3b82f6;color:#fff}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem}
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
.tipo-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.2em .6em;border-radius:20px;font-size:.7rem;font-weight:600}
.resumo-cell{max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
</style>

<div class="container-fluid">
  <!-- Tabs -->
  <div class="rel-tabs">
    <a href="/crm/relatorios"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
    <a href="/crm/relatorios/leads"><i class="fas fa-user-plus me-1"></i>Leads</a>
    <a href="/crm/relatorios/oportunidades"><i class="fas fa-chart-line me-1"></i>Oportunidades</a>
    <a href="/crm/relatorios/interacoes" class="active"><i class="fas fa-comments me-1"></i>Interações</a>
  </div>

  <!-- Filtros -->
  <div class="filter-bar">
    <form method="GET" action="/crm/relatorios/interacoes" class="row g-2 align-items-end">
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
        <label class="form-label">Tipo</label>
        <select name="tipo_interacao" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($tiposInteracao as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['tipo_interacao'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label">Entidade</label>
        <select name="related_type" class="form-select">
          <option value="">Todas</option>
          <option value="lead" <?php echo ($filtros['related_type'] ?? '') === 'lead' ? 'selected' : ''; ?>>Lead</option>
          <option value="oportunidade" <?php echo ($filtros['related_type'] ?? '') === 'oportunidade' ? 'selected' : ''; ?>>Oportunidade</option>
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
        <a href="/crm/relatorios/interacoes" class="btn btn-outline-secondary btn-sm ms-1">Limpar</a>
        <a href="/crm/relatorios/exportar?<?php echo $exportParams; ?>" class="btn btn-success btn-sm ms-1">
          <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </a>
      </div>
    </form>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card" style="border-color:#fef9c3">
      <div class="kpi-val"><?php echo $kpis['total'] ?? 0; ?></div>
      <div class="kpi-lbl">Total de Interações</div>
    </div>
    <?php foreach (array_slice($kpis['porTipo'] ?? [], 0, 5) as $tipo): ?>
    <?php $cor = $tipoColors[$tipo->tipo_interacao] ?? '#94a3b8'; ?>
    <div class="kpi-card" style="border-left:3px solid <?php echo $cor; ?>">
      <div class="kpi-val"><?php echo $tipo->total; ?></div>
      <div class="kpi-lbl">
        <i class="fab <?php echo $tipoIcons[$tipo->tipo_interacao] ?? 'fa-comment'; ?> me-1" style="color:<?php echo $cor; ?>"></i>
        <?php echo htmlspecialchars($tiposInteracao[$tipo->tipo_interacao] ?? $tipo->tipo_interacao); ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3 mb-3">
    <!-- Gráfico por Tipo -->
    <div class="col-md-5">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-chart-pie text-warning"></i> Interações por Tipo</div>
        <canvas id="chartTipo" height="240"></canvas>
      </div>
    </div>
    <!-- Gráfico Evolução Mensal -->
    <div class="col-md-7">
      <div class="section-card h-100">
        <div class="section-title"><i class="fas fa-chart-bar text-primary"></i> Evolução Mensal</div>
        <canvas id="chartEvolucao" height="240"></canvas>
      </div>
    </div>
  </div>

  <!-- Tabela de Interações -->
  <div class="section-card">
    <div class="section-title">
      <i class="fas fa-list text-primary"></i> Listagem de Interações
      <span class="badge bg-secondary ms-1"><?php echo count($interacoes); ?></span>
    </div>
    <?php if (empty($interacoes)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-comments fa-2x mb-2 d-block"></i>
      Nenhuma interação encontrada com os filtros selecionados.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover table-rel mb-0">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Entidade</th>
            <th>Nome</th>
            <th>Responsável</th>
            <th>Data</th>
            <th>Retorno Prev.</th>
            <th>Resumo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($interacoes as $int): ?>
          <?php $cor = $tipoColors[$int->tipo_interacao] ?? '#94a3b8'; ?>
          <tr>
            <td>
              <span class="tipo-badge" style="background:<?php echo $cor; ?>20;color:<?php echo $cor; ?>;border:1px solid <?php echo $cor; ?>40">
                <i class="fas <?php echo $tipoIcons[$int->tipo_interacao] ?? 'fa-comment'; ?>"></i>
                <?php echo htmlspecialchars($tiposInteracao[$int->tipo_interacao] ?? $int->tipo_interacao); ?>
              </span>
            </td>
            <td>
              <span class="badge <?php echo $int->related_type === 'lead' ? 'bg-info' : 'bg-primary'; ?> bg-opacity-10 text-<?php echo $int->related_type === 'lead' ? 'info' : 'primary'; ?>">
                <?php echo $int->related_type === 'lead' ? 'Lead' : 'Oportunidade'; ?>
              </span>
            </td>
            <td>
              <?php if ($int->related_type === 'lead'): ?>
              <a href="/crm/leads/edit/<?php echo $int->related_id; ?>" class="text-decoration-none text-dark fw-semibold">
                <?php echo htmlspecialchars($int->entidade_nome ?? '—'); ?>
              </a>
              <?php else: ?>
              <a href="/crm/oportunidades/edit/<?php echo $int->related_id; ?>" class="text-decoration-none text-dark fw-semibold">
                <?php echo htmlspecialchars($int->entidade_nome ?? '—'); ?>
              </a>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($int->responsavel ?? '—'); ?></td>
            <td><small><?php echo date('d/m/Y H:i', strtotime($int->data_interacao)); ?></small></td>
            <td>
              <?php if (!empty($int->data_retorno)): ?>
              <?php $ret = $int->data_retorno; $atrasado = $ret < date('Y-m-d'); ?>
              <small class="<?php echo $atrasado ? 'text-danger fw-bold' : 'text-muted'; ?>">
                <?php echo date('d/m/Y', strtotime($ret)); ?>
                <?php if ($atrasado): ?><i class="fas fa-exclamation-circle ms-1"></i><?php endif; ?>
              </small>
              <?php else: ?>
              <small class="text-muted">—</small>
              <?php endif; ?>
            </td>
            <td class="resumo-cell" title="<?php echo htmlspecialchars($int->resumo ?? ''); ?>">
              <small class="text-muted"><?php echo htmlspecialchars($int->resumo ?? '—'); ?></small>
            </td>
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
  function fmtMes4(ym) {
    const [y, m] = ym.split('-');
    const meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return (meses[parseInt(m)] || m) + '/' + y.slice(2);
  }

  // Gráfico por Tipo (Doughnut)
  const tipoLabels = <?php echo json_encode(array_map(fn($r) => $tiposInteracao[$r->tipo_interacao] ?? $r->tipo_interacao, $kpis['porTipo'] ?? [])); ?>;
  const tipoData   = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $kpis['porTipo'] ?? [])); ?>;
  const tipoCores  = <?php echo json_encode(array_map(fn($r) => $tipoColors[$r->tipo_interacao] ?? '#94a3b8', $kpis['porTipo'] ?? [])); ?>;

  if (tipoLabels.length > 0) {
    new Chart(document.getElementById('chartTipo'), {
      type: 'doughnut',
      data: { labels: tipoLabels, datasets: [{ data: tipoData, backgroundColor: tipoCores, borderWidth: 2 }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
    });
  }

  // Gráfico Evolução Mensal
  const evolLabels = <?php echo json_encode(array_map(fn($r) => fmtMes4($r->mes), $kpis['porMes'] ?? [])); ?>;
  const evolTotal  = <?php echo json_encode(array_map(fn($r) => (int)$r->total, $kpis['porMes'] ?? [])); ?>;
  const evolLeads  = <?php echo json_encode(array_map(fn($r) => (int)$r->total_leads, $kpis['porMes'] ?? [])); ?>;
  const evolOps    = <?php echo json_encode(array_map(fn($r) => (int)$r->total_oportunidades, $kpis['porMes'] ?? [])); ?>;

  if (evolLabels.length > 0) {
    new Chart(document.getElementById('chartEvolucao'), {
      type: 'bar',
      data: {
        labels: evolLabels,
        datasets: [
          { label: 'Total', data: evolTotal, backgroundColor: '#94a3b8', borderRadius: 3 },
          { label: 'Leads', data: evolLeads, backgroundColor: '#06b6d4', borderRadius: 3 },
          { label: 'Oportunidades', data: evolOps, backgroundColor: '#8b5cf6', borderRadius: 3 },
        ]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }
})();
</script>
