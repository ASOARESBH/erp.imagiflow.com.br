<?php require_once dirname(__DIR__) . '/layout/erp_header.php'; ?>

<style>
.manual-search-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%);
    border-radius: 12px;
    padding: 1.75rem 2rem;
    color: #fff;
    margin-bottom: 1.75rem;
}
.manual-search-hero h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: .75rem; }
.manual-search-form { display: flex; gap: .5rem; max-width: 560px; }
.manual-search-form input {
    flex: 1; border-radius: 8px; border: none;
    padding: .65rem 1rem; font-size: .95rem; outline: none;
}
.manual-search-form button {
    background: #f59e0b; color: #fff; border: none;
    border-radius: 8px; padding: .65rem 1.2rem;
    font-weight: 600; cursor: pointer;
}
.result-item {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 1.1rem 1.25rem;
    margin-bottom: .85rem;
    transition: box-shadow .15s;
}
.result-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.result-item h4 { font-size: 1rem; font-weight: 700; margin-bottom: .3rem; }
.result-item h4 a { color: #111827; text-decoration: none; }
.result-item h4 a:hover { color: #1d4ed8; }
.result-item p { font-size: .875rem; color: #6b7280; margin: 0; }
.result-item .cat-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #eff6ff; color: #1d4ed8;
    padding: .15rem .55rem; border-radius: 20px;
    font-size: .75rem; font-weight: 600; margin-bottom: .4rem;
}
.no-results {
    text-align: center; padding: 3rem 1rem; color: #9ca3af;
}
.no-results i { font-size: 3rem; margin-bottom: 1rem; display: block; }
</style>

<div class="container-fluid py-4">

    <div class="manual-search-hero">
        <h2><i class="fas fa-search me-2"></i>Buscar no Manual</h2>
        <form action="/manual/buscar" method="GET" class="manual-search-form">
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>"
                   placeholder="Digite sua dúvida..." autofocus autocomplete="off">
            <button type="submit"><i class="fas fa-search me-1"></i> Buscar</button>
        </form>
    </div>

    <?php if ($q): ?>
        <div class="mb-3" style="font-size:.875rem;color:#6b7280;">
            <?php if (count($resultados) > 0): ?>
                <strong><?php echo count($resultados); ?></strong> resultado<?php echo count($resultados) !== 1 ? 's' : ''; ?>
                para "<strong><?php echo htmlspecialchars($q); ?></strong>"
            <?php else: ?>
                Nenhum resultado para "<strong><?php echo htmlspecialchars($q); ?></strong>"
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($resultados)): ?>
        <?php foreach ($resultados as $r): ?>
            <div class="result-item">
                <div class="cat-badge">
                    <i class="<?php echo htmlspecialchars($r['categoria_icone']); ?>"></i>
                    <?php echo htmlspecialchars($r['categoria_titulo']); ?>
                </div>
                <h4><a href="/manual/artigo/<?php echo htmlspecialchars($r['slug']); ?>">
                    <?php echo htmlspecialchars($r['titulo']); ?>
                </a></h4>
                <?php if ($r['resumo']): ?>
                    <p><?php echo htmlspecialchars($r['resumo']); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <?php elseif ($q): ?>
        <div class="no-results">
            <i class="fas fa-search-minus"></i>
            <h5>Nenhum artigo encontrado</h5>
            <p>Tente outros termos ou <a href="/manual">navegue pelos módulos</a>.</p>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-keyboard"></i>
            <h5>Digite algo para buscar</h5>
            <p>Ou <a href="/manual">veja todos os módulos</a> do manual.</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
