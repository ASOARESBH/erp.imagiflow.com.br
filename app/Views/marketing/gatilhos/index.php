<?php
use App\Core\UI;
use App\Core\Auth;

UI::sectionHeader('Gatilhos de Automação', 'Envios automáticos disparados por eventos do sistema', [
    [
        'text'  => 'Novo Gatilho',
        'link'  => '#',
        'icon'  => 'fas fa-plus',
        'class' => 'btn-primary',
        'attrs' => 'data-bs-toggle="modal" data-bs-target="#modalNovoGatilho"',
    ],
]);

$gatilhos    = $gatilhos    ?? [];
$filtros     = $filtros     ?? [];
$tiposGatilho= $tiposGatilho?? [];
?>

<?php if (!empty($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo match($_GET['success']) {
        'created' => 'Gatilho criado com sucesso!',
        'deleted' => 'Gatilho excluído com sucesso!',
        default   => 'Operação realizada com sucesso.',
    }; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo match($_GET['error']) {
        'campos_obrigatorios' => 'Preencha o nome e o conteúdo do gatilho.',
        'db_failure'          => 'Erro ao salvar. Tente novamente.',
        default               => 'Ocorreu um erro.',
    }; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Info sobre gatilhos -->
<div class="alert alert-info border-info d-flex align-items-start gap-3 mb-4 py-3">
    <i class="fas fa-info-circle fa-lg mt-1 text-info"></i>
    <div class="small">
        <strong>O que são gatilhos?</strong> São mensagens automáticas disparadas quando um evento ocorre no sistema —
        por exemplo: enviar um e-mail de boas-vindas quando um cliente é cadastrado, ou um WhatsApp quando um pagamento é confirmado.
        <strong>O disparo efetivo depende da configuração do canal (SMTP/WhatsApp)</strong> nas Integrações.
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 px-4">
        <form method="GET" action="/marketing/gatilhos" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Nome do gatilho..."
                           value="<?php echo htmlspecialchars($filtros['q'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Canal</label>
                <select name="canal" class="form-select" onchange="this.form.submit()">
                    <option value="" <?php echo ($filtros['canal'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="email"    <?php echo ($filtros['canal'] ?? '') === 'email'    ? 'selected' : ''; ?>>E-mail</option>
                    <option value="whatsapp" <?php echo ($filtros['canal'] ?? '') === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Estado</label>
                <select name="ativo" class="form-select" onchange="this.form.submit()">
                    <option value="" <?php echo ($filtros['ativo'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="1" <?php echo ($filtros['ativo'] ?? '') === '1' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="0" <?php echo ($filtros['ativo'] ?? '') === '0' ? 'selected' : ''; ?>>Inativos</option>
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
        <?php if (empty($gatilhos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-bolt fa-3x mb-3 d-block opacity-25"></i>
            <h5 class="text-muted">Nenhum gatilho configurado</h5>
            <p class="small mb-4">Configure gatilhos para automatizar a comunicação com seus clientes.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoGatilho">
                <i class="fas fa-plus me-2"></i>Criar Primeiro Gatilho
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 small text-muted fw-semibold">Gatilho</th>
                        <th class="small text-muted fw-semibold">Evento</th>
                        <th class="small text-muted fw-semibold">Canal</th>
                        <th class="small text-muted fw-semibold text-center">Delay</th>
                        <th class="small text-muted fw-semibold text-center">Disparos</th>
                        <th class="small text-muted fw-semibold text-center">Ativo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gatilhos as $g):
                        $gid      = (int)$g->id;
                        $isAtivo  = (int)($g->ativo ?? 0) === 1;
                        $tipoLabel= $tiposGatilho[$g->tipo ?? ''] ?? ucfirst($g->tipo ?? '');
                    ?>
                    <tr>
                        <td class="px-4">
                            <div class="fw-semibold"><?php echo htmlspecialchars($g->nome); ?></div>
                            <?php if (!empty($g->conteudo_mensagem)): ?>
                            <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($g->conteudo_mensagem, 0, 55, '...')); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-bolt me-1 text-warning"></i>
                                <?php echo htmlspecialchars($tipoLabel); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (($g->canal ?? '') === 'whatsapp'): ?>
                                <span class="badge bg-success"><i class="fab fa-whatsapp me-1"></i>WhatsApp</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-envelope me-1"></i>E-mail</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-muted small">
                            <?php echo (int)($g->delay_dias ?? 0) > 0
                                ? '+' . (int)$g->delay_dias . ' dia(s)'
                                : 'Imediato'; ?>
                        </td>
                        <td class="text-center fw-bold">
                            <?php echo (int)($g->total_disparos ?? 0); ?>
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm <?php echo $isAtivo ? 'btn-success' : 'btn-outline-secondary'; ?> btn-toggle-gatilho"
                                    data-id="<?php echo $gid; ?>"
                                    title="<?php echo $isAtivo ? 'Desativar' : 'Ativar'; ?>"
                                    style="padding:2px 10px;min-width:70px">
                                <?php if ($isAtivo): ?>
                                    <i class="fas fa-check me-1"></i>Ativo
                                <?php else: ?>
                                    <i class="fas fa-times me-1"></i>Inativo
                                <?php endif; ?>
                            </button>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDeleteGatilho(<?php echo $gid; ?>)"
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

<!-- Modal Novo Gatilho -->
<div class="modal fade" id="modalNovoGatilho" tabindex="-1" aria-labelledby="modalNovoGatilhoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/marketing/gatilhos">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalNovoGatilhoLabel">
                        <i class="fas fa-bolt text-warning me-2"></i>Novo Gatilho de Automação
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Nome do Gatilho <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control"
                                   placeholder="Ex: Boas-vindas para novos clientes" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Canal</label>
                            <select name="canal" class="form-select" id="selectCanalGatilho" onchange="toggleAssuntoGatilho()">
                                <option value="email">📧 E-mail</option>
                                <option value="whatsapp">💬 WhatsApp</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Evento Disparador</label>
                            <select name="tipo" class="form-select">
                                <?php foreach ($tiposGatilho as $k => $v): ?>
                                <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Delay (dias após evento)</label>
                            <input type="number" name="delay_dias" class="form-control" value="0" min="0" max="365">
                        </div>
                        <div class="col-12" id="campoAssuntoGatilho">
                            <label class="form-label fw-semibold small">Assunto do E-mail</label>
                            <input type="text" name="assunto_email" class="form-control"
                                   placeholder="Ex: Seja bem-vindo(a)!">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Conteúdo / Mensagem <span class="text-danger">*</span></label>
                            <textarea name="conteudo_mensagem" class="form-control" rows="5" required
                                      placeholder="Escreva a mensagem que será enviada automaticamente...&#10;&#10;Você pode usar variáveis como: {nome}, {email}, {data}"></textarea>
                            <div class="form-text">Variáveis disponíveis: <code>{nome}</code>, <code>{email}</code>, <code>{data}</code></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fas fa-bolt me-1"></i>Criar Gatilho
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast feedback -->
<div id="toastGatilho" class="position-fixed top-0 end-0 m-3" style="z-index:9999;min-width:280px;display:none">
    <div class="toast show shadow">
        <div class="toast-header" id="toastGatilhoHeader">
            <i class="fas fa-bolt me-2"></i>
            <strong class="me-auto">Gatilho</strong>
            <button type="button" class="btn-close" onclick="document.getElementById('toastGatilho').style.display='none'"></button>
        </div>
        <div class="toast-body" id="toastGatilhoBody"></div>
    </div>
</div>

<script>
function confirmDeleteGatilho(id) {
    if (confirm('Deseja realmente excluir este gatilho?')) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '/marketing/gatilhos/delete/' + id;
        document.body.appendChild(f);
        f.submit();
    }
}

function toggleAssuntoGatilho() {
    const canal = document.getElementById('selectCanalGatilho').value;
    document.getElementById('campoAssuntoGatilho').style.display = canal === 'email' ? '' : 'none';
}

function showToastGatilho(msg, type) {
    const toast  = document.getElementById('toastGatilho');
    const header = document.getElementById('toastGatilhoHeader');
    const body   = document.getElementById('toastGatilhoBody');
    const clsMap = { success: 'bg-success text-white', danger: 'bg-danger text-white', warning: 'bg-warning text-dark' };
    header.className = 'toast-header ' + (clsMap[type] || '');
    body.innerHTML   = msg;
    toast.style.display = 'block';
    setTimeout(function() { toast.style.display = 'none'; }, 3500);
}

// Toggle ativo/inativo via AJAX
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-toggle-gatilho');
    if (!btn) return;

    const id   = btn.dataset.id;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('/marketing/gatilhos/toggle/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            const isAtivo = data.ativo === 1;
            btn.className = 'btn btn-sm ' + (isAtivo ? 'btn-success' : 'btn-outline-secondary') + ' btn-toggle-gatilho';
            btn.innerHTML = isAtivo
                ? '<i class="fas fa-check me-1"></i>Ativo'
                : '<i class="fas fa-times me-1"></i>Inativo';
            btn.title = isAtivo ? 'Desativar' : 'Ativar';
            // Atualiza a linha
            const row = btn.closest('tr');
            if (row) row.style.opacity = isAtivo ? '1' : '0.6';
            showToastGatilho('<i class="fas fa-check me-1"></i> Gatilho ' + (isAtivo ? 'ativado' : 'desativado') + '.', 'success');
        } else {
            btn.disabled = false;
            btn.innerHTML = orig;
            showToastGatilho('<i class="fas fa-exclamation-triangle me-1"></i> Erro ao alterar gatilho.', 'danger');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = orig;
        showToastGatilho('<i class="fas fa-exclamation-triangle me-1"></i> Erro de comunicação.', 'danger');
    });
});
</script>
