<?php
/**
 * ERP InLaudo — Marketing: Dashboard
 */
use App\Core\UI;

$stats        = $stats        ?? null;
$envioStats   = $envioStats   ?? null;
$disparadores = $disparadores ?? [];
$recentes     = $recentes     ?? [];

$taxaAbertura = 0;
$taxaClique   = 0;
$taxaErro     = 0;
if (!empty($envioStats) && $envioStats->enviados > 0) {
    $taxaAbertura = round($envioStats->abertos  / $envioStats->enviados * 100, 1);
    $taxaClique   = round($envioStats->clicados / $envioStats->enviados * 100, 1);
}
if (!empty($envioStats) && $envioStats->total > 0) {
    $taxaErro = round($envioStats->erros / $envioStats->total * 100, 1);
}

UI::sectionHeader('Dashboard de Marketing', 'Visão geral de todas as campanhas e disparos', [
    ['url' => '/marketing/campanhas',           'label' => 'Campanhas',       'icon' => 'fas fa-bullhorn',  'color' => 'outline-primary'],
    ['url' => '/marketing/disparadores/create', 'label' => 'Novo Disparador', 'icon' => 'fas fa-plus',      'color' => 'primary'],
]);
?>

<!-- KPIs principais -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-4">
        <div class="fw-bold display-6 text-primary"><?php echo (int)($envioStats->total ?? 0); ?></div>
        <div class="text-muted small mt-1">Total de Envios</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-4">
        <div class="fw-bold display-6 text-success"><?php echo (int)($envioStats->enviados ?? 0); ?></div>
        <div class="text-muted small mt-1">Entregues</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-4">
        <div class="fw-bold display-6 text-info"><?php echo $taxaAbertura; ?>%</div>
        <div class="text-muted small mt-1">Taxa de Abertura</div>
        <div class="text-muted" style="font-size:.7rem;"><?php echo (int)($envioStats->abertos ?? 0); ?> abertos</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-4">
        <div class="fw-bold display-6 text-warning"><?php echo $taxaClique; ?>%</div>
        <div class="text-muted small mt-1">Taxa de Clique</div>
        <div class="text-muted" style="font-size:.7rem;"><?php echo (int)($envioStats->clicados ?? 0); ?> cliques</div>
      </div>
    </div>
  </div>
</div>

<!-- Segunda linha de KPIs -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-3 text-secondary"><?php echo (int)($stats->total_disparadores ?? 0); ?></div>
        <div class="text-muted small">Disparadores</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-3 text-success"><?php echo (int)($stats->concluidos ?? 0); ?></div>
        <div class="text-muted small">Concluídos</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-3 text-warning"><?php echo (int)($stats->em_andamento ?? 0); ?></div>
        <div class="text-muted small">Em Andamento</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-3 text-danger"><?php echo $taxaErro; ?>%</div>
        <div class="text-muted small">Taxa de Erro</div>
        <div class="text-muted" style="font-size:.7rem;"><?php echo (int)($envioStats->erros ?? 0); ?> erros</div>
      </div>
    </div>
  </div>
</div>

<!-- Gráfico de funil de conversão -->
<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom fw-semibold">
        <i class="fas fa-funnel-dollar me-2 text-primary"></i> Funil de Engajamento
      </div>
      <div class="card-body">
        <?php
        $total    = max(1, (int)($envioStats->total    ?? 1));
        $enviados = (int)($envioStats->enviados ?? 0);
        $abertos  = (int)($envioStats->abertos  ?? 0);
        $clicados = (int)($envioStats->clicados ?? 0);
        $funil = [
            ['label' => 'Total de Envios', 'valor' => $total,    'pct' => 100,                          'color' => 'primary'],
            ['label' => 'Entregues',        'valor' => $enviados, 'pct' => round($enviados/$total*100),  'color' => 'success'],
            ['label' => 'Abertos',          'valor' => $abertos,  'pct' => round($abertos/$total*100),   'color' => 'info'],
            ['label' => 'Clicados',         'valor' => $clicados, 'pct' => round($clicados/$total*100),  'color' => 'warning'],
        ];
        foreach ($funil as $f):
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small fw-semibold"><?php echo $f['label']; ?></span>
            <span class="small text-muted"><?php echo $f['valor']; ?> (<?php echo $f['pct']; ?>%)</span>
          </div>
          <div class="progress" style="height:10px;">
            <div class="progress-bar bg-<?php echo $f['color']; ?>" style="width:<?php echo $f['pct']; ?>%;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Disparadores recentes -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom fw-semibold">
        <i class="fas fa-history me-2 text-secondary"></i> Disparadores Recentes
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentes)): ?>
        <div class="text-center py-4 text-muted small">Nenhum disparador ainda.</div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php
          $statusColors = [
            'rascunho'=>'secondary','agendado'=>'info','em_andamento'=>'warning',
            'concluido'=>'success','pausado'=>'danger','cancelado'=>'dark',
          ];
          foreach (array_slice($recentes, 0, 8) as $d):
          ?>
          <a href="/marketing/disparadores/view/<?php echo $d->id; ?>"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3">
            <div>
              <div class="small fw-semibold"><?php echo htmlspecialchars($d->nome); ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($d->campanha_nome ?? '—'); ?></div>
            </div>
            <div class="text-end">
              <span class="badge bg-<?php echo $statusColors[$d->status] ?? 'secondary'; ?> mb-1">
                <?php echo ucfirst(str_replace('_', ' ', $d->status)); ?>
              </span>
              <div class="text-muted" style="font-size:.7rem;"><?php echo (int)$d->total_enviados; ?> enviados</div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Benchmarks de referência -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom fw-semibold">
    <i class="fas fa-chart-bar me-2 text-info"></i> Benchmarks do Setor (Saúde / B2B)
  </div>
  <div class="card-body">
    <div class="row g-3 text-center">
      <div class="col-md-3">
        <div class="fw-bold text-success fs-4">~25%</div>
        <div class="text-muted small">Taxa de abertura média (e-mail B2B saúde)</div>
        <div class="small mt-1 <?php echo $taxaAbertura >= 25 ? 'text-success' : 'text-danger'; ?>">
          Sua taxa: <?php echo $taxaAbertura; ?>%
          <i class="fas fa-<?php echo $taxaAbertura >= 25 ? 'arrow-up' : 'arrow-down'; ?>"></i>
        </div>
      </div>
      <div class="col-md-3">
        <div class="fw-bold text-info fs-4">~3%</div>
        <div class="text-muted small">Taxa de clique média (e-mail B2B)</div>
        <div class="small mt-1 <?php echo $taxaClique >= 3 ? 'text-success' : 'text-danger'; ?>">
          Sua taxa: <?php echo $taxaClique; ?>%
          <i class="fas fa-<?php echo $taxaClique >= 3 ? 'arrow-up' : 'arrow-down'; ?>"></i>
        </div>
      </div>
      <div class="col-md-3">
        <div class="fw-bold text-warning fs-4">&lt;2%</div>
        <div class="text-muted small">Taxa de erro aceitável</div>
        <div class="small mt-1 <?php echo $taxaErro <= 2 ? 'text-success' : 'text-danger'; ?>">
          Sua taxa: <?php echo $taxaErro; ?>%
          <i class="fas fa-<?php echo $taxaErro <= 2 ? 'check' : 'exclamation-triangle'; ?>"></i>
        </div>
      </div>
      <div class="col-md-3">
        <div class="fw-bold text-primary fs-4">5/5s</div>
        <div class="text-muted small">Lote recomendado (anti-blacklist)</div>
        <div class="small mt-1 text-muted">5 e-mails a cada 5 segundos</div>
      </div>
    </div>
  </div>
</div>
