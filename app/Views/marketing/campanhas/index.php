<?php
/**
 * ERP InLaudo — Marketing: Listagem de Campanhas
 */
use App\Core\UI;

$campanhas = $campanhas ?? [];
$filtros   = $filtros   ?? [];
$porCanal  = $porCanal  ?? [];

$canalLabels = [
    'email'     => ['label' => 'E-mail',    'icon' => 'fa-envelope',   'color' => 'primary'],
    'whatsapp'  => ['label' => 'WhatsApp',  'icon' => 'fa-whatsapp',   'color' => 'success'],
    'telegram'  => ['label' => 'Telegram',  'icon' => 'fa-paper-plane','color' => 'info'],
    'sdr'       => ['label' => 'SDR',       'icon' => 'fa-phone-alt',  'color' => 'warning'],
];
$statusLabels = [
    'rascunho'  => ['label' => 'Rascunho',  'color' => 'secondary'],
    'ativa'     => ['label' => 'Ativa',     'color' => 'success'],
    'pausada'   => ['label' => 'Pausada',   'color' => 'warning'],
    'arquivada' => ['label' => 'Arquivada', 'color' => 'dark'],
];

UI::sectionHeader('Campanhas de Marketing', 'Crie e gerencie suas campanhas de disparo', [
    ['url' => '/marketing/campanhas/create', 'label' => 'Nova Campanha', 'icon' => 'fas fa-plus', 'color' => 'primary'],
    ['url' => '/marketing/disparadores',     'label' => 'Disparadores',  'icon' => 'fas fa-rocket','color' => 'outline-secondary'],
    ['url' => '/marketing/dashboard',        'label' => 'Dashboard',     'icon' => 'fas fa-chart-bar','color' => 'outline-info'],
]);
?>

<!-- Flash messages -->
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

<!-- Resumo por canal -->
<div class="row g-3 mb-4">
  <?php foreach ($canalLabels as $canal => $info): ?>
  <?php
    $qtd = 0;
    foreach ($porCanal as $pc) { if ($pc->canal === $canal) { $qtd = (int)$pc->total; break; } }
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-<?php echo $info['color']; ?> bg-opacity-10 p-3">
          <i class="fab <?php echo $info['icon']; ?> text-<?php echo $info['color']; ?> fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-4"><?php echo $qtd; ?></div>
          <div class="text-muted small"><?php echo $info['label']; ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Buscar</label>
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Nome da campanha..." value="<?php echo htmlspecialchars($filtros['q'] ?? ''); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Canal</label>
        <select name="canal" class="form-select form-select-sm">
          <option value="">Todos os canais</option>
          <?php foreach ($canalLabels as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($filtros['canal'] ?? '') === $k ? 'selected' : ''; ?>>
            <?php echo $v['label']; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos os status</option>
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
        <a href="/marketing/campanhas" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-times"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Tabela de campanhas -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($campanhas)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-bullhorn fa-3x mb-3 opacity-25"></i>
      <p class="mb-2">Nenhuma campanha encontrada.</p>
      <a href="/marketing/campanhas/create" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Criar primeira campanha
      </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Campanha</th>
            <th>Canal</th>
            <th>Status</th>
            <th class="text-center">Disparadores</th>
            <th class="text-center">Enviados</th>
            <th class="text-center">Abertos</th>
            <th class="text-center">Cliques</th>
            <th>Criada em</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($campanhas as $c): ?>
          <?php
            $canal  = $canalLabels[$c->canal]  ?? ['label' => $c->canal,  'icon' => 'fa-question', 'color' => 'secondary'];
            $status = $statusLabels[$c->status] ?? ['label' => $c->status, 'color' => 'secondary'];
          ?>
          <tr>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($c->nome); ?></div>
              <?php if (!empty($c->descricao)): ?>
              <div class="text-muted small"><?php echo htmlspecialchars(mb_substr($c->descricao, 0, 60)); ?>...</div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-<?php echo $canal['color']; ?> bg-opacity-15 text-<?php echo $canal['color']; ?>">
                <i class="fab <?php echo $canal['icon']; ?> me-1"></i><?php echo $canal['label']; ?>
              </span>
            </td>
            <td>
              <span class="badge bg-<?php echo $status['color']; ?>">
                <?php echo $status['label']; ?>
              </span>
            </td>
            <td class="text-center"><?php echo (int)($c->total_disparadores ?? 0); ?></td>
            <td class="text-center"><?php echo (int)$c->total_enviados; ?></td>
            <td class="text-center">
              <?php echo (int)$c->total_abertos; ?>
              <?php if ($c->total_enviados > 0): ?>
              <small class="text-muted">(<?php echo round($c->total_abertos / $c->total_enviados * 100); ?>%)</small>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php echo (int)$c->total_cliques; ?>
              <?php if ($c->total_abertos > 0): ?>
              <small class="text-muted">(<?php echo round($c->total_cliques / $c->total_abertos * 100); ?>%)</small>
              <?php endif; ?>
            </td>
            <td class="text-muted small"><?php echo date('d/m/Y', strtotime($c->created_at)); ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="/marketing/campanhas/personalizar/<?php echo $c->id; ?>"
                   class="btn btn-outline-primary" title="Personalizar">
                  <i class="fas fa-paint-brush"></i>
                </a>
                <a href="/marketing/campanhas/edit/<?php echo $c->id; ?>"
                   class="btn btn-outline-secondary" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-outline-danger"
                        onclick="confirmarExclusao(<?php echo $c->id; ?>, '<?php echo htmlspecialchars(addslashes($c->nome)); ?>')"
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

<!-- Form oculto para exclusão -->
<form id="formExcluir" method="POST" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?php echo \App\Core\View::csrfToken(); ?>">
</form>

<script>
function confirmarExclusao(id, nome) {
  if (!confirm('Excluir a campanha "' + nome + '"?\nTodos os dados serão removidos permanentemente.')) return;
  const f = document.getElementById('formExcluir');
  f.action = '/marketing/campanhas/delete/' + id;
  f.submit();
}
</script>
