<?php
/**
 * ERP InLaudo — CRM Leads: Aba Marketing
 * Exibe todas as interações de campanhas de marketing para este lead.
 */
$marketingInteracoes = $marketingInteracoes ?? [];
$marketingStats      = $marketingStats      ?? [];

$eventoLabels = [
    'enviado'       => ['label' => 'Enviado',        'icon' => 'fa-paper-plane', 'color' => 'success'],
    'aberto'        => ['label' => 'Aberto',          'icon' => 'fa-envelope-open','color' => 'info'],
    'clicado'       => ['label' => 'Clicado',         'icon' => 'fa-mouse-pointer','color' => 'primary'],
    'erro'          => ['label' => 'Erro de Envio',   'icon' => 'fa-exclamation-circle','color' => 'danger'],
    'descadastrado' => ['label' => 'Descadastrado',   'icon' => 'fa-user-times',  'color' => 'dark'],
];
$canalIcons = [
    'email'    => 'fa-envelope',
    'whatsapp' => 'fa-whatsapp',
    'telegram' => 'fa-paper-plane',
    'sdr'      => 'fa-phone-alt',
];
?>

<style>
.mkt-timeline { position: relative; padding-left: 2rem; }
.mkt-timeline::before { content: ''; position: absolute; left: .75rem; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.mkt-item { position: relative; margin-bottom: 1.25rem; }
.mkt-dot { position: absolute; left: -1.5rem; top: .25rem; width: 1.25rem; height: 1.25rem; border-radius: 50%; background: #fff; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: .6rem; }
.mkt-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .875rem 1rem; }
.mkt-meta { display: flex; align-items: center; gap: .75rem; margin-bottom: .4rem; flex-wrap: wrap; }
.mkt-badge { font-size: .72rem; font-weight: 600; padding: .2em .6em; border-radius: 10px; }
.mkt-data { font-size: .75rem; color: #94a3b8; }
.mkt-campanha { font-size: .75rem; color: #64748b; }
</style>

<!-- Resumo de engajamento -->
<?php if (!empty($marketingStats)): ?>
<div class="row g-2 mb-4">
  <?php
  $statsMap = [];
  foreach ($marketingStats as $s) { $statsMap[$s->evento] = (int)$s->total; }
  $kpis = [
    'enviado'  => ['label' => 'Campanhas Recebidas', 'color' => 'success'],
    'aberto'   => ['label' => 'E-mails Abertos',     'color' => 'info'],
    'clicado'  => ['label' => 'Cliques',             'color' => 'primary'],
    'erro'     => ['label' => 'Erros',               'color' => 'danger'],
  ];
  foreach ($kpis as $k => $v):
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-2">
      <div class="fw-bold fs-4 text-<?php echo $v['color']; ?>"><?php echo $statsMap[$k] ?? 0; ?></div>
      <div class="text-muted" style="font-size:.72rem;"><?php echo $v['label']; ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Timeline de interações -->
<?php if (empty($marketingInteracoes)): ?>
<div class="text-center py-5 text-muted">
  <i class="fas fa-bullhorn fa-2x mb-3 d-block opacity-25"></i>
  <p class="mb-1">Nenhuma interação de marketing registrada para este lead.</p>
  <p class="small">Quando este lead for incluído em um disparo de campanha, as interações aparecerão aqui.</p>
  <a href="/marketing/campanhas" class="btn btn-sm btn-outline-primary mt-2">
    <i class="fas fa-bullhorn me-1"></i> Ver Campanhas
  </a>
</div>
<?php else: ?>
<div class="mkt-timeline">
  <?php foreach ($marketingInteracoes as $int): ?>
  <?php
    $ev     = $eventoLabels[$int->evento] ?? ['label' => $int->evento, 'icon' => 'fa-circle', 'color' => 'secondary'];
    $canal  = $canalIcons[$int->campanha_canal ?? ''] ?? 'fa-bullhorn';
  ?>
  <div class="mkt-item">
    <div class="mkt-dot">
      <i class="fas <?php echo $ev['icon']; ?> text-<?php echo $ev['color']; ?>" style="font-size:.55rem;"></i>
    </div>
    <div class="mkt-card">
      <div class="mkt-meta">
        <span class="mkt-badge bg-<?php echo $ev['color']; ?> bg-opacity-15 text-<?php echo $ev['color']; ?>">
          <i class="fas <?php echo $ev['icon']; ?> me-1"></i><?php echo $ev['label']; ?>
        </span>
        <span class="mkt-campanha">
          <i class="fas <?php echo $canal; ?> me-1"></i>
          <a href="/marketing/campanhas/personalizar/<?php echo (int)$int->campanha_id; ?>"
             class="text-decoration-none text-muted" target="_blank">
            <?php echo htmlspecialchars($int->campanha_nome ?? 'Campanha #' . $int->campanha_id); ?>
          </a>
        </span>
        <span class="mkt-data">
          <i class="fas fa-clock me-1"></i>
          <?php echo date('d/m/Y H:i', strtotime($int->ocorrido_em)); ?>
        </span>
      </div>
      <?php if (!empty($int->observacao)): ?>
      <div class="small text-muted mt-1"><?php echo nl2br(htmlspecialchars($int->observacao)); ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
