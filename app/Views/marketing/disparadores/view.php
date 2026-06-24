<?php
/**
 * ERP InLaudo — Marketing: Detalhe do Disparador
 */
use App\Core\UI;

$disparador = $disparador ?? null;
$envios     = $envios     ?? [];
$countMap   = $countMap   ?? [];

if (!$disparador) { header('Location: /marketing/disparadores'); exit(); }

$statusLabels = [
    'rascunho'    => ['label' => 'Rascunho',     'color' => 'secondary'],
    'agendado'    => ['label' => 'Agendado',      'color' => 'info'],
    'em_andamento'=> ['label' => 'Em Andamento',  'color' => 'warning'],
    'concluido'   => ['label' => 'Concluído',     'color' => 'success'],
    'pausado'     => ['label' => 'Pausado',       'color' => 'danger'],
    'cancelado'   => ['label' => 'Cancelado',     'color' => 'dark'],
];
$envioStatusLabels = [
    'pendente'      => ['label' => 'Pendente',      'color' => 'secondary'],
    'enviado'       => ['label' => 'Enviado',        'color' => 'success'],
    'erro'          => ['label' => 'Erro',           'color' => 'danger'],
    'aberto'        => ['label' => 'Aberto',         'color' => 'info'],
    'clicado'       => ['label' => 'Clicado',        'color' => 'primary'],
    'descadastrado' => ['label' => 'Descadastrado',  'color' => 'dark'],
];

$st = $statusLabels[$disparador->status] ?? ['label' => $disparador->status, 'color' => 'secondary'];

$acoes = [];
if (in_array($disparador->status, ['rascunho', 'pausado'])) {
    $acoes[] = ['type' => 'button', 'id' => 'btnIniciar', 'label' => 'Iniciar Envio', 'icon' => 'fas fa-play', 'color' => 'success'];
}
if ($disparador->status === 'em_andamento') {
    $acoes[] = ['type' => 'button', 'id' => 'btnPausar', 'label' => 'Pausar', 'icon' => 'fas fa-pause', 'color' => 'warning'];
}
$acoes[] = ['url' => '/marketing/disparadores', 'label' => 'Voltar', 'icon' => 'fas fa-arrow-left', 'color' => 'light'];

UI::sectionHeader(
    'Disparador: ' . htmlspecialchars($disparador->nome),
    'Campanha: ' . htmlspecialchars($disparador->campanha_nome ?? '—'),
    $acoes
);
?>

<!-- Status e progresso -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <span class="badge bg-<?php echo $st['color']; ?> fs-6 mb-1"><?php echo $st['label']; ?></span>
      <div class="text-muted small">Status</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-primary"><?php echo (int)$disparador->total_destinatarios; ?></div>
      <div class="text-muted small">Destinatários</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-success"><?php echo (int)$disparador->total_enviados; ?></div>
      <div class="text-muted small">Enviados</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-danger"><?php echo (int)$disparador->total_erros; ?></div>
      <div class="text-muted small">Erros</div>
    </div>
  </div>
</div>

<!-- Barra de progresso -->
<?php if ($disparador->total_destinatarios > 0): ?>
<?php $pct = min(100, round($disparador->total_enviados / $disparador->total_destinatarios * 100)); ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-1">
      <span class="small fw-semibold">Progresso do Envio</span>
      <span class="small text-muted"><?php echo $pct; ?>%</span>
    </div>
    <div class="progress" style="height:12px;">
      <div class="progress-bar bg-success" style="width:<?php echo $pct; ?>%;"></div>
    </div>
    <div class="d-flex justify-content-between mt-1 small text-muted">
      <span><?php echo (int)$disparador->total_enviados; ?> enviados</span>
      <span><?php echo (int)$disparador->total_destinatarios - (int)$disparador->total_enviados; ?> pendentes</span>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Painel de controle de envio (visível quando em andamento) -->
<?php if ($disparador->status === 'em_andamento'): ?>
<div class="card border-warning border-2 shadow-sm mb-4" id="painelEnvio">
  <div class="card-header bg-warning bg-opacity-10 fw-semibold">
    <i class="fas fa-spinner fa-spin me-2 text-warning"></i> Envio em Andamento
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      O sistema está processando os envios em lotes de <strong><?php echo (int)$disparador->lote_tamanho; ?></strong>
      com intervalo de <strong><?php echo (int)$disparador->intervalo_envio; ?>s</strong> entre lotes.
    </p>
    <div id="logEnvio" class="bg-dark text-success font-monospace small p-3 rounded mb-3" style="min-height:80px;max-height:200px;overflow-y:auto;">
      Aguardando próximo lote...
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-success" id="btnProcessarLote" onclick="processarProximoLote()">
        <i class="fas fa-forward me-1"></i> Processar Próximo Lote
      </button>
      <button type="button" class="btn btn-warning" onclick="pausarDisparador()">
        <i class="fas fa-pause me-1"></i> Pausar
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Distribuição por status -->
<div class="row g-3 mb-4">
  <?php foreach ($envioStatusLabels as $k => $v): ?>
  <div class="col-4 col-md-2">
    <div class="card border-0 shadow-sm text-center py-2">
      <div class="fw-bold text-<?php echo $v['color']; ?>"><?php echo (int)($countMap[$k] ?? 0); ?></div>
      <div class="text-muted" style="font-size:.7rem;"><?php echo $v['label']; ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Lista de envios -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="fas fa-list me-2"></i> Registros de Envio</span>
    <span class="badge bg-secondary"><?php echo count($envios); ?></span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($envios)): ?>
    <div class="text-center py-4 text-muted small">Nenhum envio registrado ainda.</div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Destinatário</th>
            <th>E-mail / Tel</th>
            <th>Tipo</th>
            <th>Status</th>
            <th>Enviado em</th>
            <th>Erro</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($envios as $e): ?>
          <?php $es = $envioStatusLabels[$e->status] ?? ['label' => $e->status, 'color' => 'secondary']; ?>
          <tr>
            <td class="small fw-semibold"><?php echo htmlspecialchars($e->destinatario_nome ?? '—'); ?></td>
            <td class="small text-muted"><?php echo htmlspecialchars($e->destinatario_email ?: ($e->destinatario_tel ?? '—')); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo ucfirst($e->destinatario_tipo); ?></span></td>
            <td><span class="badge bg-<?php echo $es['color']; ?>"><?php echo $es['label']; ?></span></td>
            <td class="small text-muted"><?php echo $e->enviado_em ? date('d/m H:i', strtotime($e->enviado_em)) : '—'; ?></td>
            <td class="small text-danger"><?php echo htmlspecialchars(mb_substr($e->erro_msg ?? '', 0, 60)); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const CSRF_TOKEN    = '<?php echo \App\Core\View::csrfToken(); ?>';
const DISPARADOR_ID = <?php echo (int)$disparador->id; ?>;

document.getElementById('btnIniciar')?.addEventListener('click', () => {
  if (!confirm('Iniciar o envio agora?')) return;
  fetch('/marketing/disparadores/iniciar/' + DISPARADOR_ID, {
    method: 'POST',
    body: new URLSearchParams({ csrf_token: CSRF_TOKEN }),
  })
  .then(r => r.json())
  .then(data => {
    alert(data.success ? '✅ ' + data.message : '❌ ' + data.message);
    if (data.success) location.reload();
  });
});

document.getElementById('btnPausar')?.addEventListener('click', () => pausarDisparador());

function pausarDisparador() {
  fetch('/marketing/disparadores/pausar/' + DISPARADOR_ID, {
    method: 'POST',
    body: new URLSearchParams({ csrf_token: CSRF_TOKEN }),
  })
  .then(r => r.json())
  .then(data => { if (data.success) location.reload(); });
}

function processarProximoLote() {
  const btn = document.getElementById('btnProcessarLote');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processando...';

  fetch('/marketing/disparadores/processar-lote/' + DISPARADOR_ID, {
    method: 'POST',
    body: new URLSearchParams({ csrf_token: CSRF_TOKEN }),
  })
  .then(r => r.json())
  .then(data => {
    const log = document.getElementById('logEnvio');
    const ts  = new Date().toLocaleTimeString('pt-BR');
    log.innerHTML += `<div>[${ts}] ${data.message || ''} — Enviados: ${data.enviados||0}, Erros: ${data.erros||0}, Pendentes: ${data.pendentes||0}</div>`;
    log.scrollTop = log.scrollHeight;

    if (data.concluido) {
      log.innerHTML += '<div class="text-warning">✅ Envio concluído!</div>';
      setTimeout(() => location.reload(), 2000);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-forward me-1"></i> Processar Próximo Lote';
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-forward me-1"></i> Processar Próximo Lote';
  });
}
</script>
