<?php

$action = $isEdit ? '/financeiro/contas-a-pagar/update/' . ($conta->id ?? '') : '/financeiro/contas-a-pagar';
$planos = $planos ?? [];
$fornecedores = $fornecedores ?? [];
$fornecedorSelecionadoId = (int) ($conta->fornecedor_id ?? 0);
$fornecedorSelecionadoNome = '';
foreach ($fornecedores as $fornecedor) {
    if ((int) $fornecedor->id === $fornecedorSelecionadoId) {
        $fornecedorSelecionadoNome = (string) $fornecedor->nome;
        break;
    }
}
?>

<form id="contaPagarFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">
    <?php echo \App\Core\View::csrfField(); ?>

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-file-invoice-dollar section-icon"></i>
            Dados Principais
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="plano_conta_id" class="form-label required">Plano de Conta</label>
                <select name="plano_conta_id" id="plano_conta_id" class="form-select" required>
                    <option value="" disabled <?php echo empty($conta->plano_conta_id) ? 'selected' : ''; ?>>Selecione...</option>
                    <?php foreach ($planos as $p): ?>
                        <option value="<?php echo (int)$p->id; ?>" <?php echo ((int)($conta->plano_conta_id ?? 0) === (int)$p->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($p->codigo ?? '') . ' - ' . ($p->nome ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group position-relative" data-fornecedor-picker>
                <div class="d-flex justify-content-between align-items-center">
                    <label for="fornecedor_busca" class="form-label mb-1">Fornecedor</label>
                    <?php if (\App\Core\Auth::can('create_fornecedores')): ?>
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalNovoFornecedor">
                            <i class="fas fa-plus me-1"></i>Novo fornecedor
                        </button>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="fornecedor_id" id="fornecedor_id" value="<?php echo $fornecedorSelecionadoId ?: ''; ?>">
                <input type="search" id="fornecedor_busca" class="form-control" autocomplete="off"
                    placeholder="Digite nome, CNPJ, e-mail ou telefone"
                    value="<?php echo htmlspecialchars($fornecedorSelecionadoNome); ?>"
                    aria-autocomplete="list" aria-controls="fornecedor_resultados" aria-expanded="false">
                <div id="fornecedor_resultados" class="dropdown-menu w-100 shadow" role="listbox"></div>
                <div class="form-text">Digite para buscar. Os fornecedores mais recentes aparecem primeiro.</div>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="aberta" <?php echo ($conta->status ?? 'aberta') === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                    <option value="paga" <?php echo ($conta->status ?? '') === 'paga' ? 'selected' : ''; ?>>Paga</option>
                    <option value="cancelada" <?php echo ($conta->status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="descricao" class="form-label required">Descrição</label>
                <input type="text" name="descricao" id="descricao" class="form-control" placeholder="Ex.: Aluguel" value="<?php echo htmlspecialchars($conta->descricao ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="valor" class="form-label required">Valor</label>
                <input type="number" step="0.01" name="valor" id="valor" class="form-control" placeholder="0,00" value="<?php echo htmlspecialchars($conta->valor ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-grid form-grid-4">
            <div class="form-group">
                <label for="data_vencimento" class="form-label required">Data de Vencimento</label>
                <input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?php echo htmlspecialchars($conta->data_vencimento ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="data_pagamento" class="form-label">Data de Pagamento</label>
                <input type="date" name="data_pagamento" id="data_pagamento" class="form-control" value="<?php echo htmlspecialchars($conta->data_pagamento ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="codigo_barras" class="form-label">Código de Barras</label>
                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" value="<?php echo htmlspecialchars($conta->codigo_barras ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Recorrente</label>
                <div class="form-input-group">
                    <input type="checkbox" name="recorrente" id="recorrente" value="1" <?php echo !empty($conta->recorrente) ? 'checked' : ''; ?>>
                    <label for="recorrente" class="ms-2">Sim</label>
                </div>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="recorrencia_tipo" class="form-label">Tipo de Recorrência</label>
                <select name="recorrencia_tipo" id="recorrencia_tipo" class="form-select">
                    <option value="">(Opcional)</option>
                    <option value="mensal" <?php echo ($conta->recorrencia_tipo ?? '') === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                    <option value="semanal" <?php echo ($conta->recorrencia_tipo ?? '') === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                    <option value="anual" <?php echo ($conta->recorrencia_tipo ?? '') === 'anual' ? 'selected' : ''; ?>>Anual</option>
                    <option value="customizada" <?php echo ($conta->recorrencia_tipo ?? '') === 'customizada' ? 'selected' : ''; ?>>Customizada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="recorrencia_intervalo" class="form-label">Intervalo</label>
                <input type="number" name="recorrencia_intervalo" id="recorrencia_intervalo" class="form-control" value="<?php echo htmlspecialchars($conta->recorrencia_intervalo ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?php echo htmlspecialchars($conta->observacoes ?? ''); ?></textarea>
            </div>
        </div>
    </section>

</form>

<?php if (\App\Core\Auth::can('create_fornecedores')): ?>
    <div class="modal fade" id="modalNovoFornecedor" tabindex="-1" aria-labelledby="modalNovoFornecedorTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalNovoFornecedorTitulo"><i class="fas fa-truck me-2"></i>Novo fornecedor</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formNovoFornecedorRapido" novalidate>
                    <div class="modal-body">
                        <div id="fornecedor_rapido_feedback" class="alert d-none" role="alert"></div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="fornecedor_rapido_tipo" class="form-label">Tipo</label>
                                <select id="fornecedor_rapido_tipo" class="form-select">
                                    <option value="PJ">Pessoa jurídica</option>
                                    <option value="PF">Pessoa física</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="fornecedor_rapido_nome" class="form-label required">Razão social / Nome</label>
                                <input type="text" id="fornecedor_rapido_nome" class="form-control" required maxlength="255" autocomplete="organization">
                            </div>
                            <div class="col-md-6">
                                <label for="fornecedor_rapido_documento" class="form-label">CPF / CNPJ</label>
                                <input type="text" id="fornecedor_rapido_documento" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label for="fornecedor_rapido_nome_fantasia" class="form-label">Nome fantasia</label>
                                <input type="text" id="fornecedor_rapido_nome_fantasia" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="fornecedor_rapido_email" class="form-label">E-mail</label>
                                <input type="email" id="fornecedor_rapido_email" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="fornecedor_rapido_telefone" class="form-label">Telefone</label>
                                <input type="text" id="fornecedor_rapido_telefone" class="form-control" maxlength="30">
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Após salvar, o fornecedor é selecionado automaticamente nesta conta a pagar. Os dados complementares podem ser incluídos depois em Cadastros → Fornecedores.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSalvarFornecedorRapido"><i class="fas fa-save me-1"></i>Salvar e selecionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="/assets/js/fornecedor-rapido.js"></script>
