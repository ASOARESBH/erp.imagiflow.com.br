<?php
/**
 * ERP InLaudo — Marketing: Listagem de Disparadores
 */
use App\Core\UI;

$disparadores = $disparadores ?? [];
$stats        = $stats        ?? null;
$envioStats   = $envioStats   ?? null;
$filtros      = $filtros      ?? [];
$campanhas    = $campanhas    ?? [];

$statusLabels = [
    'rascunho'    => ['label' => 'Rascunho',     'color' => 'secondary'],
    'agendado'    => ['label' => 'Agendado',      'color' => 'info'],
    'em_andamento'=> ['label' => 'Em Andamento',  'color' => 'warning'],
    'concluido'   => ['label' => 'Concluído',     'color' => 'success'],
    'pausado'     => ['label' => 'Pausado',       'color' => 'danger'],
    'cancelado'   => ['label' => 'Cancelado',     'color' => 'dark'],
];
$publicoLabels = [
    'clientes'      => 'Clientes',
    'leads'         => 'Leads',
    'oportunidades' => 'Oportunidades',
];

UI::sectionHeader('Disparadores de Marketing', 'Gerencie e monitore os disparos de campanhas', [
    ['url' => '/marketing/disparadores/create', 'label' => 'Novo Disparador', 'icon' => 'fas fa-plus',       'color' => 'primary'],
    ['url' => '/marketing/campanhas',           'label' => 'Campanhas',       'icon' => 'fas fa-bullhorn',   'color' => 'outline-secondary'],
    ['url' => '/marketing/dashboard',           'label' => 'Dashboard',       'icon' => 'fas fa-chart-bar',  'color' => 'outline-info'],
]);
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-3">
  <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-3">
  <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Cards de estatísticas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-primary"><?php echo (int)($stats->total_disparadores ?? 0); ?></div>
      <div class="text-muted small">Total de Disparadores</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-success"><?php echo (int)($stats->total_enviados ?? 0); ?></div>
      <div class="text-muted small">Total Enviados</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-info"><?php echo (int)($envioStats->abertos ?? 0); ?></div>
      <div class="text-muted small">Abertos</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="fw-bold fs-3 text-danger"><?php echo (int)($stats->total_erros ?? 0); ?></div>
      <div class="text-muted small">Erros</div>
    </div>
  </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Campanha</label>
        <select name="campanha_id" class="form-select form-select-sm">
          <option value="">Todas as campanhas</option>
          <?php foreach ($campanhas as $c): ?>
          <option value="<?php echo $c->id; ?>" <?php echo ($filtros['campanha_id'] ?? '') == $c->id ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($c->nome); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($statusLabels as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['status'] ?? '') === $k ? 'selected' : ''; ?>>
            <?php echo $v['label']; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-search me-1"></i> Filtrar
        </button>
        <a href="/marketing/disparadores" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-times"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Tabela de disparadores -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($disparadores)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-rocket fa-3x mb-3 opacity-25"></i>
      <p class="mb-2">Nenhum disparador encontrado.</p>
      <a href="/marketing/disparadores/create" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Criar primeiro disparador
      </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nome</th>
            <th>Campanha</th>
            <th>Público</th>
            <th>Status</th>
            <th class="text-center">Destinatários</th>
            <th class="text-center">Enviados</th>
            <th class="text-center">Erros</th>
            <th>Criado em</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($disparadores as $d): ?>
          <?php
            $st = $statusLabels[$d->status] ?? ['label' => $d->status, 'color' => 'secondary'];
          ?>
          <tr>
            <td>
              <a href="/marketing/disparadores/view/<?php echo $d->id; ?>" class="fw-semibold text-decoration-none">
                <?php echo htmlspecialchars($d->nome); ?>
              </a>
            </td>
            <td class="small"><?php echo htmlspecialchars($d->campanha_nome ?? '—'); ?></td>
            <td class="small"><?php echo $publicoLabels[$d->publico] ?? $d->publico; ?></td>
            <td>
              <span class="badge bg-<?php echo $st['color']; ?>"><?php echo $st['label']; ?></span>
            </td>
            <td class="text-center"><?php echo (int)$d->total_destinatarios; ?></td>
            <td class="text-center text-success fw-semibold"><?php echo (int)$d->total_enviados; ?></td>
            <td class="text-center text-danger"><?php echo (int)$d->total_erros; ?></td>
            <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($d->created_at)); ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="/marketing/disparadores/view/<?php echo $d->id; ?>"
                   class="btn btn-outline-primary" title="Ver detalhes">
                  <i class="fas fa-eye"></i>
                </a>
                <?php if (in_array($d->status, ['rascunho', 'pausado'])): ?>
                <button type="button" class="btn btn-outline-success"
                        onclick="iniciarDisparador(<?php echo $d->id; ?>, '<?php echo htmlspecialchars(addslashes($d->nome)); ?>')"
                        title="Iniciar envio">
                  <i class="fas fa-play"></i>
                </button>
                <?php elseif ($d->status === 'em_andamento'): ?>
                <button type="button" class="btn btn-outline-warning"
                        onclick="pausarDisparador(<?php echo $d->id; ?>)"
                        title="Pausar">
                  <i class="fas fa-pause"></i>
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-danger"
                        onclick="confirmarExclusao(<?php echo $d->id; ?>, '<?php echo htmlspecialchars(addslashes($d->nome)); ?>')"
                        title="Excluir">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<form id="formExcluir" method="POST" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?php echo \App\Core\View::csrfToken(); ?>">
</form>

<script>
const CSRF_TOKEN = '<?php echo \App\Core\View::csrfToken(); ?>';

function iniciarDisparador(id, nome) {
  if (!confirm('Iniciar o envio do disparador "' + nome + '"?')) return;
  fetch('/marketing/disparadores/iniciar/' + id, {
    method: 'POST',
    body: new URLSearchParams({ csrf_token: CSRF_TOKEN }),
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      location.reload();
    } else {
      alert('❌ ' + data.message);
    }
  });
}

function pausarDisparador(id) {
  fetch('/marketing/disparadores/pausar/' + id, {
    method: 'POST',
    body: new URLSearchParams({ csrf_token: CSRF_TOKEN }),
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) location.reload();
    else alert('❌ ' + data.message);
  });
}

function confirmarExclusao(id, nome) {
  if (!confirm('Excluir o disparador "' + nome + '"?\nTodos os registros de envio serão removidos.')) return;
  const f = document.getElementById('formExcluir');
  f.action = '/marketing/disparadores/delete/' + id;
  f.submit();
}
</script>
