<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<style>
/* ── Manual Wiki — Estilos ── */
.manual-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%);
    border-radius: 16px;
    padding: 2.5rem 2rem;
    color: #fff;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.manual-hero::before {
    content: '\f02d';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 8rem;
    opacity: .08;
}
.manual-hero h1 { font-size: 2rem; font-weight: 700; margin-bottom: .5rem; }
.manual-hero p  { opacity: .85; margin-bottom: 1.25rem; }
.manual-search-box {
    display: flex;
    gap: .5rem;
    max-width: 560px;
}
.manual-search-box input {
    flex: 1;
    border-radius: 8px;
    border: none;
    padding: .65rem 1rem;
    font-size: .95rem;
    outline: none;
}
.manual-search-box button {
    background: #f59e0b;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: .65rem 1.2rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}
.manual-stats {
    display: flex;
    gap: 1.5rem;
    margin-top: 1.25rem;
    font-size: .85rem;
    opacity: .8;
}
.manual-stats span { display: flex; align-items: center; gap: .4rem; }

/* Categorias grid */
.manual-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.25rem;
}
.manual-cat-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
}
.manual-cat-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
    transform: translateY(-2px);
}
.manual-cat-header {
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .85rem;
    border-bottom: 1px solid #f3f4f6;
}
.manual-cat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #fff;
    flex-shrink: 0;
}
.manual-cat-header h3 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    color: #111827;
}
.manual-cat-header p {
    font-size: .8rem;
    color: #6b7280;
    margin: .15rem 0 0;
}
.manual-cat-articles { padding: .5rem 0; }
.manual-cat-articles a {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .55rem 1.25rem;
    font-size: .875rem;
    color: #374151;
    text-decoration: none;
    transition: background .15s;
}
.manual-cat-articles a:hover {
    background: #f9fafb;
    color: #1d4ed8;
}
.manual-cat-articles a i { color: #9ca3af; font-size: .75rem; flex-shrink: 0; }
.manual-cat-footer {
    padding: .65rem 1.25rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.manual-cat-footer a {
    font-size: .8rem;
    color: #1d4ed8;
    text-decoration: none;
    font-weight: 500;
}
.manual-cat-footer span {
    font-size: .75rem;
    color: #9ca3af;
}

/* Admin badge */
.manual-admin-bar {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: .75rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.manual-admin-bar span { font-size: .875rem; color: #92400e; }
</style>

<div class="container-fluid py-4">

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    if ($isAdmin):
    ?>
    <div class="manual-admin-bar">
        <span><i class="fas fa-shield-alt me-2"></i>Você está no modo <strong>Administrador</strong>. Pode criar e editar artigos.</span>
        <div class="d-flex gap-2">
            <a href="/manual/artigo/novo" class="btn btn-sm btn-warning">
                <i class="fas fa-plus me-1"></i> Novo Artigo
            </a>
            <a href="/manual/admin" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Gerenciar Manual
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="manual-hero">
        <h1><i class="fas fa-book-open me-2"></i>Manual do Sistema</h1>
        <p>Encontre tutoriais, guias e documentação completa de todos os módulos do ERP InLaudo.</p>
        <form action="/manual/buscar" method="GET" class="manual-search-box">
            <input type="text" name="q" placeholder="Buscar no manual... (ex: emitir nota fiscal, cadastrar cliente)" autocomplete="off">
            <button type="submit"><i class="fas fa-search me-1"></i> Buscar</button>
        </form>
        <div class="manual-stats">
            <span><i class="fas fa-layer-group"></i> <?php echo $estatisticas['categorias']; ?> módulos</span>
            <span><i class="fas fa-file-alt"></i> <?php echo $estatisticas['artigos']; ?> artigos</span>
        </div>
    </div>

    <!-- Grid de categorias -->
    <div class="manual-grid">
        <?php foreach ($categorias as $cat): ?>
            <?php if (empty($cat['artigos'])) continue; ?>
            <div class="manual-cat-card">
                <div class="manual-cat-header">
                    <div class="manual-cat-icon" style="background:<?php echo htmlspecialchars($cat['cor']); ?>">
                        <i class="<?php echo htmlspecialchars($cat['icone']); ?>"></i>
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($cat['titulo']); ?></h3>
                        <?php if ($cat['descricao']): ?>
                            <p><?php echo htmlspecialchars($cat['descricao']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="manual-cat-articles">
                    <?php foreach (array_slice($cat['artigos'], 0, 5) as $art): ?>
                        <a href="/manual/artigo/<?php echo htmlspecialchars($art['slug']); ?>">
                            <i class="fas fa-chevron-right"></i>
                            <?php echo htmlspecialchars($art['titulo']); ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($cat['artigos']) > 5): ?>
                        <a href="/manual/categoria/<?php echo htmlspecialchars($cat['slug']); ?>" style="color:#1d4ed8;font-weight:500;">
                            <i class="fas fa-ellipsis-h"></i>
                            Ver mais <?php echo count($cat['artigos']) - 5; ?> artigos...
                        </a>
                    <?php endif; ?>
                </div>
                <div class="manual-cat-footer">
                    <a href="/manual/categoria/<?php echo htmlspecialchars($cat['slug']); ?>">
                        Ver todos →
                    </a>
                    <span><?php echo count($cat['artigos']); ?> artigo<?php echo count($cat['artigos']) !== 1 ? 's' : ''; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
