<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_plano_contas')) {
    $actions[] = [
        'text' => 'Nova Conta',
        'link' => '/financeiro/plano-contas/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Plano de Contas', 'Cadastre e organize suas contas de Receita e Despesa', $actions);

$success = (string) ($_GET['success'] ?? '');
$error = (string) ($_GET['error'] ?? '');
$successMessages = [
    'default_imported' => 'Plano de contas padrão processado com sucesso.',
    'created' => 'Conta criada com sucesso.',
    'updated' => 'Conta atualizada com sucesso.',
    'deleted' => 'Conta inativada com sucesso.',
];
$errorMessages = [
    'default_import_failed' => 'Não foi possível importar o plano padrão. Confirme se a migration foi aplicada.',
    'duplicate_code' => 'Já existe uma conta com este código neste tenant.',
    'db_failure' => 'Não foi possível salvar a conta. Tente novamente.',
    'unauthorized' => 'Você não tem acesso a esta conta.',
    'not_found' => 'Conta não encontrada.',
];
?>

<?php if (isset($successMessages[$success])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessages[$success]); ?>
        <?php if ($success === 'default_imported'): ?>
            <span class="ms-1">Contas inseridas nesta operação: <strong><?php echo (int) ($_GET['inserted'] ?? 0); ?></strong>.</span>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php elseif (isset($errorMessages[$error])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($errorMessages[$error]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<?php if (Auth::can('create_plano_contas')): ?>
    <div class="card border-primary-subtle shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h2 class="h6 mb-1"><i class="fas fa-file-import text-primary me-2"></i>Modelo básico para saúde, tecnologia e equipamentos médicos</h2>
                <p class="text-muted small mb-0">Inclui receitas assistenciais, software, venda de equipamentos e despesas operacionais. A importação só acrescenta contas padrão ausentes e preserva as contas já criadas pelo tenant.</p>
            </div>
            <form method="POST" action="/financeiro/plano-contas/importar-padrao" class="flex-shrink-0">
                <?php echo \App\Core\View::csrfField(); ?>
                <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Importar as contas padrão ausentes para este tenant? Contas existentes não serão alteradas.');">
                    <i class="fas fa-download me-1"></i> Importar modelo básico
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/financeiro/plano-contas" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Código ou Nome..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="" <?php echo ($filtros['tipo'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="Receita" <?php echo ($filtros['tipo'] ?? '') === 'Receita' ? 'selected' : ''; ?>>Receita</option>
                    <option value="Despesa" <?php echo ($filtros['tipo'] ?? '') === 'Despesa' ? 'selected' : ''; ?>>Despesa</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="ativo" <?php echo ($filtros['status'] ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($filtros['status'] ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $headers = ['Código', 'Nome', 'Tipo', 'Nível', 'Status', 'Ações'];

        $rowRenderer = function ($conta) {
            $acoes = '';

            if (Auth::can('edit_plano_contas')) {
                $acoes .= '<a href="/financeiro/plano-contas/edit/' . (int)$conta->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (Auth::can('delete_plano_contas')) {
                $acoes .= '<a href="#" class="text-danger" title="Excluir" onclick="confirmDelete(' . (int)$conta->id . '); return false;"><i class="fas fa-trash"></i></a>';
            }

            $statusBadge = ($conta->status ?? 'ativo') === 'ativo'
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Inativo</span>';

            $codigo = htmlspecialchars($conta->codigo ?? '');
            $nome = htmlspecialchars($conta->nome ?? '');
            $tipo = htmlspecialchars($conta->tipo ?? '');
            $nivel = (int)($conta->nivel ?? 1);

            return '<tr>'
                . '<td><strong>' . $codigo . '</strong></td>'
                . '<td>' . $nome . '</td>'
                . '<td>' . $tipo . '</td>'
                . '<td>' . $nivel . '</td>'
                . '<td>' . $statusBadge . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $contas ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhuma conta encontrada com os filtros aplicados.',
        ]);
        ?>
    </div>
</div>

<script>
const planoContasCsrfToken = <?php echo json_encode((string) ($_SESSION['csrf_token'] ?? '')); ?>;
function confirmDelete(id) {
    if (confirm('Deseja realmente inativar esta conta?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/financeiro/plano-contas/delete/' + id;
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = planoContasCsrfToken;
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
