<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<!-- EasyMDE (Markdown editor) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-<?php echo $artigo ? 'edit' : 'plus'; ?> me-2 text-primary"></i>
                <?php echo $artigo ? 'Editar Artigo' : 'Novo Artigo'; ?>
            </h2>
            <small class="text-muted">
                <?php echo $artigo ? htmlspecialchars($artigo['titulo']) : 'Criar novo artigo no manual'; ?>
            </small>
        </div>
        <a href="/manual/admin" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="/manual/artigo/salvar" id="formArtigo">
                        <?php if ($artigo): ?>
                            <input type="hidden" name="id" value="<?php echo $artigo['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control"
                                   value="<?php echo htmlspecialchars($artigo['titulo'] ?? ''); ?>"
                                   required placeholder="Ex: Como emitir uma Nota Fiscal">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Resumo</label>
                            <input type="text" name="resumo" class="form-control"
                                   value="<?php echo htmlspecialchars($artigo['resumo'] ?? ''); ?>"
                                   placeholder="Breve descrição exibida nos resultados de busca">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Conteúdo <span class="text-danger">*</span></label>
                            <textarea name="conteudo" id="editorConteudo" class="form-control"
                                      rows="20"><?php echo htmlspecialchars($artigo['conteudo'] ?? ''); ?></textarea>
                            <small class="text-muted">Suporta HTML. Use as ferramentas do editor para formatar.</small>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="/manual/admin" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" name="publicado" value="0" class="btn btn-outline-secondary">
                                <i class="fas fa-save me-1"></i> Salvar como Rascunho
                            </button>
                            <button type="submit" name="publicado" value="1" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Publicar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Configurações do artigo -->
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Configurações</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                        <select name="categoria_id" class="form-select" form="formArtigo" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo (($artigo['categoria_id'] ?? ($_GET['categoria_id'] ?? '')) == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['titulo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ordem</label>
                        <input type="number" name="ordem" class="form-control" form="formArtigo"
                               value="<?php echo $artigo['ordem'] ?? 0; ?>" min="0">
                        <small class="text-muted">Menor número = aparece primeiro</small>
                    </div>
                </div>
            </div>

            <!-- Histórico de versões -->
            <?php if (!empty($historico)): ?>
            <div class="card">
                <div class="card-header py-2"><strong>Histórico de Versões</strong></div>
                <div class="card-body p-0">
                    <div style="max-height:280px;overflow-y:auto;">
                        <?php foreach ($historico as $h): ?>
                            <div style="padding:.6rem 1rem;border-bottom:1px solid #f3f4f6;font-size:.8rem;">
                                <div class="fw-semibold"><?php echo htmlspecialchars($h['titulo']); ?></div>
                                <div class="text-muted">
                                    <?php echo $h['usuario_nome'] ?? 'Sistema'; ?> —
                                    <?php echo date('d/m/Y H:i', strtotime($h['criado_em'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Editor rico com EasyMDE
var easyMDE = new EasyMDE({
    element: document.getElementById('editorConteudo'),
    spellChecker: false,
    autosave: { enabled: true, uniqueId: 'manual-artigo-<?php echo $artigo['id'] ?? 'novo'; ?>' },
    toolbar: [
        'bold', 'italic', 'heading', '|',
        'quote', 'unordered-list', 'ordered-list', '|',
        'link', 'table', 'horizontal-rule', '|',
        'preview', 'side-by-side', 'fullscreen', '|',
        'guide'
    ],
    placeholder: 'Escreva o conteúdo do artigo aqui...\n\n## Como acessar\n\nDescreva como acessar o módulo...\n\n## Como usar\n\nDescreva passo a passo...',
    renderingConfig: { singleLineBreaks: false },
});

// Ao submeter, converter Markdown para HTML
document.getElementById('formArtigo').addEventListener('submit', function(e) {
    // O EasyMDE já salva o valor no textarea automaticamente
    // Mas garantimos que o valor está atualizado
    easyMDE.codemirror.save();
});
</script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
