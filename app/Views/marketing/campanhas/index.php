<?php
use App\Core\UI;
use App\Core\Auth;

UI::sectionHeader('Campanhas', 'Gerencie suas campanhas de e-mail, WhatsApp e SMS', [
    [
        'text'  => 'Nova Campanha',
        'link'  => '#',
        'icon'  => 'fas fa-plus',
        'class' => 'btn-primary',
        'attrs' => 'data-bs-toggle="modal" data-bs-target="#modalNovaCampanha"',
    ],
]);

$campanhas = $campanhas ?? [];
$filtros   = $filtros   ?? [];

$statusMap = [
    'rascunho'  => ['label' => 'Rascunho',   'cls' => 'secondary', 'icon' => 'fas fa-pencil-alt'],
    'agendada'  => ['label' => 'Agendada',   'cls' => 'primary',   'icon' => 'fas fa-clock'],
    'enviando'  => ['label' => 'Enviando',   'cls' => 'info',      'icon' => 'fas fa-paper-plane'],
    'concluida' => ['label' => 'Concluída',  'cls' => 'success',   'icon' => 'fas fa-check-circle'],
    'cancelada' => ['label' => 'Cancelada',  'cls' => 'danger',    'icon' => 'fas fa-ban'],
];
?>

<?php if (!empty($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo match($_GET['success']) {
        'created' => 'Campanha criada com sucesso!',
        'deleted' => 'Campanha excluída com sucesso!',
        default   => 'Operação realizada com sucesso.',
    }; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo match($_GET['error']) {
        'nome_obrigatorio' => 'O nome da campanha é obrigatório.',
        'db_failure'       => 'Erro ao salvar. Tente novamente.',
        default            => 'Ocorreu um erro.',
    }; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" action="/marketing/campanhas" class="row g-3 align-items-end" id="formFiltroCampanhas">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Nome da campanha..."
                           value="<?php echo htmlspecialchars($filtros['q'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tipo</label>
                <select name="tipo" class="form-select" onchange="this.form.submit()">
                    <option value="" <?php echo ($filtros['tipo'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="email"    <?php echo ($filtros['tipo'] ?? '') === 'email'    ? 'selected' : ''; ?>>E-mail</option>
                    <option value="whatsapp" <?php echo ($filtros['tipo'] ?? '') === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                    <option value="sms"      <?php echo ($filtros['tipo'] ?? '') === 'sms'      ? 'selected' : ''; ?>>SMS</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="rascunho"  <?php echo ($filtros['status'] ?? '') === 'rascunho'  ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="agendada"  <?php echo ($filtros['status'] ?? '') === 'agendada'  ? 'selected' : ''; ?>>Agendada</option>
                    <option value="enviando"  <?php echo ($filtros['status'] ?? '') === 'enviando'  ? 'selected' : ''; ?>>Enviando</option>
                    <option value="concluida" <?php echo ($filtros['status'] ?? '') === 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                    <option value="cancelada" <?php echo ($filtros['status'] ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($campanhas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-bullhorn fa-3x mb-3 d-block opacity-25"></i>
            <h5 class="text-muted">Nenhuma campanha encontrada</h5>
            <p class="small mb-4">Crie sua primeira campanha para começar a se comunicar com seus clientes.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaCampanha">
                <i class="fas fa-plus me-2"></i>Nova Campanha
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 small text-muted fw-semibold">Campanha</th>
                        <th class="small text-muted fw-semibold">Tipo</th>
                        <th class="small text-muted fw-semibold">Status</th>
                        <th class="small text-muted fw-semibold">Agendamento</th>
                        <th class="small text-muted fw-semibold text-center">Enviados</th>
                        <th class="small text-muted fw-semibold text-center">Erros</th>
                        <th class="small text-muted fw-semibold">Criada em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campanhas as $c):
                        $sm  = $statusMap[$c->status ?? 'rascunho'] ?? $statusMap['rascunho'];
                        $cid = (int)$c->id;
                    ?>
                    <tr>
                        <td class="px-4">
                            <div class="fw-semibold"><?php echo htmlspecialchars($c->nome); ?></div>
                            <?php if (!empty($c->assunto)): ?>
                            <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($c->assunto, 0, 60, '...')); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($c->tipo ?? '') === 'email'): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-envelope me-1"></i>E-mail</span>
                            <?php elseif (($c->tipo ?? '') === 'whatsapp'): ?>
                                <span class="badge bg-success"><i class="fab fa-whatsapp me-1"></i>WhatsApp</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-sms me-1"></i>SMS</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $sm['cls']; ?>">
                                <i class="<?php echo $sm['icon']; ?> me-1"></i><?php echo $sm['label']; ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?php echo !empty($c->data_agendamento) ? date('d/m/Y H:i', strtotime($c->data_agendamento)) : '—'; ?>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold text-success"><?php echo (int)$c->total_enviados; ?></span>
                            <?php if ((int)$c->total_destinatarios > 0): ?>
                            <br><small class="text-muted">de <?php echo (int)$c->total_destinatarios; ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$c->total_erros > 0): ?>
                                <span class="badge bg-danger"><?php echo (int)$c->total_erros; ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?php echo $c->created_at ? date('d/m/Y', strtotime($c->created_at)) : '—'; ?>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDeleteCampanha(<?php echo $cid; ?>)"
                                    title="Excluir" style="padding:2px 8px">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Campanha -->
<div class="modal fade" id="modalNovaCampanha" tabindex="-1" aria-labelledby="modalNovaCampanhaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/marketing/campanhas">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalNovaCampanhaLabel">
                        <i class="fas fa-bullhorn text-primary me-2"></i>Nova Campanha
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Nome da Campanha <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control" placeholder="Ex: Novidades Junho 2026" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Canal</label>
                            <select name="tipo" class="form-select" id="selectTipoCampanha" onchange="toggleAssunto()">
                                <option value="email">📧 E-mail</option>
                                <option value="whatsapp">💬 WhatsApp</option>
                                <option value="sms">📱 SMS</option>
                            </select>
                        </div>
                        <div class="col-12" id="campoAssunto">
                            <label class="form-label fw-semibold small">Assunto do E-mail</label>
                            <input type="text" name="assunto" class="form-control" placeholder="Ex: Confira nossas novidades deste mês!">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Conteúdo / Mensagem</label>
                            <textarea name="conteudo" class="form-control" rows="5"
                                      placeholder="Escreva o conteúdo da sua campanha..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Agendar Envio (opcional)</label>
                            <input type="datetime-local" name="data_agendamento" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fas fa-save me-1"></i>Salvar Campanha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeleteCampanha(id) {
    if (confirm('Deseja realmente excluir esta campanha?')) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '/marketing/campanhas/delete/' + id;
        document.body.appendChild(f);
        f.submit();
    }
}

function toggleAssunto() {
    const tipo = document.getElementById('selectTipoCampanha').value;
    document.getElementById('campoAssunto').style.display = tipo === 'email' ? '' : 'none';
}
</script>
