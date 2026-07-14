<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<style>
.manual-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 768px) {
    .manual-layout { grid-template-columns: 1fr; }
    .manual-sidebar { display: none; }
}
/* Sidebar */
.manual-sidebar {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 80px;
}
.manual-sidebar-header {
    padding: .85rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-size: .8rem;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.manual-sidebar-cat {
    padding: .5rem 0;
    border-bottom: 1px solid #f3f4f6;
}
.manual-sidebar-cat-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem 1rem;
    font-size: .8rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.manual-sidebar-cat-title i { font-size: .75rem; }
.manual-sidebar-cat a {
    display: block;
    padding: .4rem 1rem .4rem 2rem;
    font-size: .83rem;
    color: #374151;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all .15s;
}
.manual-sidebar-cat a:hover { background: #f9fafb; color: #1d4ed8; }
.manual-sidebar-cat a.active {
    color: #1d4ed8;
    font-weight: 600;
    border-left-color: #1d4ed8;
    background: #eff6ff;
}
/* Conteúdo do artigo */
.manual-article {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 2rem;
}
.manual-article-breadcrumb {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .8rem;
    color: #9ca3af;
    margin-bottom: 1.25rem;
}
.manual-article-breadcrumb a { color: #6b7280; text-decoration: none; }
.manual-article-breadcrumb a:hover { color: #1d4ed8; }
.manual-article-breadcrumb i { font-size: .65rem; }
.manual-article-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: .5rem;
}
.manual-article-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: .8rem;
    color: #9ca3af;
    margin-bottom: 1.75rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    flex-wrap: wrap;
}
.manual-article-meta .cat-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: #eff6ff;
    color: #1d4ed8;
    padding: .2rem .65rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: .78rem;
}
/* Conteúdo wiki */
.manual-content { line-height: 1.75; color: #374151; }
.manual-content h2 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
    margin: 1.75rem 0 .75rem;
    padding-bottom: .4rem;
    border-bottom: 2px solid #e5e7eb;
}
.manual-content h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a5f;
    margin: 1.25rem 0 .5rem;
}
.manual-content p { margin-bottom: .9rem; }
.manual-content ul, .manual-content ol {
    padding-left: 1.5rem;
    margin-bottom: .9rem;
}
.manual-content li { margin-bottom: .35rem; }
.manual-content code {
    background: #f3f4f6;
    padding: .15rem .4rem;
    border-radius: 4px;
    font-size: .875em;
    color: #be185d;
}
.manual-content pre {
    background: #1e293b;
    color: #e2e8f0;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    overflow-x: auto;
    font-size: .875rem;
    margin-bottom: 1rem;
}
.manual-content strong { color: #111827; }
.manual-content a { color: #1d4ed8; }
.manual-content blockquote {
    border-left: 4px solid #1d4ed8;
    background: #eff6ff;
    padding: .75rem 1rem;
    border-radius: 0 8px 8px 0;
    margin: 1rem 0;
    color: #1e40af;
}
/* Navegação anterior/próximo */
.manual-nav {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f3f4f6;
}
.manual-nav a {
    display: flex;
    flex-direction: column;
    padding: .85rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    transition: all .15s;
}
.manual-nav a:hover { border-color: #1d4ed8; background: #eff6ff; }
.manual-nav a .nav-label { font-size: .72rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .2rem; }
.manual-nav a .nav-title { font-size: .875rem; font-weight: 600; color: #374151; }
.manual-nav a.nav-next { text-align: right; }
.manual-nav a.nav-prev .nav-label::before { content: '← '; }
.manual-nav a.nav-next .nav-label::after { content: ' →'; }
/* Admin bar */
.manual-edit-bar {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: .6rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .83rem;
    color: #92400e;
}
</style>

<div class="container-fluid py-4">

    <!-- Breadcrumb global -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb" style="font-size:.82rem;">
            <li class="breadcrumb-item"><a href="/manual">Manual</a></li>
            <li class="breadcrumb-item">
                <a href="/manual/categoria/<?php echo htmlspecialchars($artigo['categoria_slug']); ?>">
                    <?php echo htmlspecialchars($artigo['categoria_titulo']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($artigo['titulo']); ?></li>
        </ol>
    </nav>

    <div class="manual-layout">

        <!-- Sidebar de navegação -->
        <aside class="manual-sidebar">
            <div class="manual-sidebar-header">
                <i class="fas fa-list me-1"></i> Nesta categoria
            </div>
            <div class="manual-sidebar-cat">
                <?php foreach ($artigos as $art): ?>
                    <a href="/manual/artigo/<?php echo htmlspecialchars($art['slug']); ?>"
                       class="<?php echo $art['slug'] === $artigo['slug'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($art['titulo']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div style="padding:.75rem 1rem;border-top:1px solid #f3f4f6;">
                <a href="/manual" style="font-size:.8rem;color:#6b7280;text-decoration:none;">
                    <i class="fas fa-arrow-left me-1"></i> Todos os módulos
                </a>
            </div>
        </aside>

        <!-- Conteúdo do artigo -->
        <article class="manual-article">

            <?php
            $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
            if ($isAdmin):
            ?>
            <div class="manual-edit-bar">
                <span><i class="fas fa-pencil-alt me-1"></i> Modo administrador</span>
                <div class="d-flex gap-2">
                    <a href="/manual/artigo/<?php echo htmlspecialchars($artigo['slug']); ?>/editar"
                       class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <form method="POST" action="/manual/artigo/<?php echo $artigo['id']; ?>/deletar"
                          onsubmit="return confirm('Remover este artigo permanentemente?')">
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> Remover
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="manual-article-breadcrumb">
                <a href="/manual">Manual</a>
                <i class="fas fa-chevron-right"></i>
                <a href="/manual/categoria/<?php echo htmlspecialchars($artigo['categoria_slug']); ?>">
                    <?php echo htmlspecialchars($artigo['categoria_titulo']); ?>
                </a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($artigo['titulo']); ?></span>
            </div>

            <h1 class="manual-article-title"><?php echo htmlspecialchars($artigo['titulo']); ?></h1>

            <div class="manual-article-meta">
                <span class="cat-badge">
                    <i class="<?php echo htmlspecialchars($artigo['categoria_icone']); ?>"></i>
                    <?php echo htmlspecialchars($artigo['categoria_titulo']); ?>
                </span>
                <span><i class="fas fa-clock me-1"></i> Atualizado em <?php echo date('d/m/Y', strtotime($artigo['atualizado_em'])); ?></span>
            </div>

            <?php if ($artigo['resumo']): ?>
                <div style="background:#f0f9ff;border-left:4px solid #0ea5e9;padding:.75rem 1rem;border-radius:0 8px 8px 0;margin-bottom:1.5rem;color:#0369a1;font-size:.9rem;">
                    <?php echo htmlspecialchars($artigo['resumo']); ?>
                </div>
            <?php endif; ?>

            <div class="manual-content">
                <?php echo $artigo['conteudo']; // HTML sanitizado salvo pelo admin ?>
            </div>

            <!-- Navegação anterior/próximo -->
            <?php if ($nav['anterior'] || $nav['proximo']): ?>
            <div class="manual-nav">
                <?php if ($nav['anterior']): ?>
                    <a href="/manual/artigo/<?php echo htmlspecialchars($nav['anterior']['slug']); ?>" class="nav-prev">
                        <span class="nav-label">Anterior</span>
                        <span class="nav-title"><?php echo htmlspecialchars($nav['anterior']['titulo']); ?></span>
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                <?php if ($nav['proximo']): ?>
                    <a href="/manual/artigo/<?php echo htmlspecialchars($nav['proximo']['slug']); ?>" class="nav-next">
                        <span class="nav-label">Próximo</span>
                        <span class="nav-title"><?php echo htmlspecialchars($nav['proximo']['titulo']); ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </article>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
