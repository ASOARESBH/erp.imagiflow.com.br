<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-book me-2 text-primary"></i>Gerenciar Manual</h2>
            <small class="text-muted">Crie e edite artigos do manual do sistema</small>
        </div>
        <div class="d-flex gap-2">
            <a href="/manual/artigo/novo" class="btn btn-warning">
                <i class="fas fa-plus me-1"></i> Novo Artigo
            </a>
            <a href="/manual" class="btn btn-outline-secondary">
                <i class="fas fa-eye me-1"></i> Ver Manual
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php foreach ($categorias as $cat): ?>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="background:<?php echo htmlspecialchars($cat['cor']); ?>">
                        <i class="<?php echo htmlspecialchars($cat['icone']); ?>"></i>
                    </span>
                    <strong><?php echo htmlspecialchars($cat['titulo']); ?></strong>
                    <span class="badge bg-secondary"><?php echo count($cat['artigos']); ?> artigos</span>
                    <?php if (!$cat['ativo']): ?>
                        <span class="badge bg-warning text-dark">Inativa</span>
                    <?php endif; ?>
                </div>
                <a href="/manual/artigo/novo?categoria_id=<?php echo $cat['id']; ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> Artigo nesta categoria
                </a>
            </div>
            <?php if (!empty($cat['artigos'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th width="80">Ordem</th>
                                <th width="100">Status</th>
                                <th width="130">Atualizado</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cat['artigos'] as $art): ?>
                                <tr>
                                    <td>
                                        <a href="/manual/artigo/<?php echo htmlspecialchars($art['slug']); ?>"
                                           target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($art['titulo']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $art['ordem']; ?></td>
                                    <td>
                                        <?php if ($art['publicado']): ?>
                                            <span class="badge bg-success">Publicado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Rascunho</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted" style="font-size:.8rem;">
                                        <?php echo date('d/m/Y', strtotime($art['atualizado_em'])); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="/manual/artigo/<?php echo htmlspecialchars($art['slug']); ?>/editar"
                                               class="btn btn-xs btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="/manual/artigo/<?php echo $art['id']; ?>/deletar"
                                                  onsubmit="return confirm('Remover este artigo?')">
                                                <button class="btn btn-xs btn-outline-danger" title="Remover">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body text-muted text-center py-3" style="font-size:.875rem;">
                    Nenhum artigo nesta categoria.
                    <a href="/manual/artigo/novo?categoria_id=<?php echo $cat['id']; ?>">Criar o primeiro</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</div>

<style>
.btn-xs { padding: .2rem .45rem; font-size: .75rem; line-height: 1.4; }
</style>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
