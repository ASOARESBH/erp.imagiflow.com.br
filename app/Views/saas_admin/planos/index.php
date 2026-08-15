<?php use App\Core\Auth; ?>
<div class="container-fluid py-4 saas-admin">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4"><div><h1 class="h3 mb-1">Planos SaaS</h1><p class="text-muted mb-0">Defina módulos liberados e limites para cada assinatura.</p></div><?php if (Auth::can('manage_saas_plans')): ?><a class="btn btn-primary" href="/saas-admin/planos/create"><i class="fas fa-plus me-1"></i> Novo Plano</a><?php endif; ?></div>
    <?php if (!empty($_GET['error'])): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $_GET['error']) ?></div><?php endif; ?>
    <div class="row g-4">
    <?php foreach ($planos as $plano): ?>
        <div class="col-md-6 col-xl-4"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between gap-2"><div><h2 class="h5 mb-1"><?= htmlspecialchars($plano->nome) ?></h2><span class="text-muted small"><?= htmlspecialchars($plano->slug) ?></span></div><span class="badge <?= $plano->status === 'ativo' ? 'bg-success' : 'bg-secondary' ?> align-self-start"><?= htmlspecialchars($plano->status) ?></span></div><p class="text-muted mt-3 mb-3 flex-grow-1"><?= htmlspecialchars($plano->descricao ?: 'Sem descrição configurada.') ?></p><div class="border-top pt-3 d-flex justify-content-between small"><span><i class="fas fa-cubes me-1"></i><?= (int) $plano->total_modulos_ativos ?> módulos</span><span><i class="fas fa-users me-1"></i><?= $plano->limite_usuarios === null ? 'Ilimitado' : (int) $plano->limite_usuarios ?></span></div><div class="d-flex justify-content-between align-items-center mt-3"><strong>R$ <?= number_format((float) $plano->preco_mensal, 2, ',', '.') ?>/mês</strong><?php if (Auth::can('manage_saas_plans')): ?><a href="/saas-admin/planos/edit/<?= (int) $plano->id ?>" class="btn btn-sm btn-outline-primary">Configurar</a><?php endif; ?></div></div></div></div>
    <?php endforeach; ?>
    </div>
</div>
