<?php
$counts = $tenantCounts ?? ['active' => 0, 'inactive' => 0, 'suspended' => 0];
?>
<div class="container-fluid py-4 saas-admin">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-cloud me-2 text-primary"></i>Painel SaaS</h1>
            <p class="text-muted mb-0">Administração central de empresas, planos e acessos da plataforma.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/painel/empresas" class="btn btn-outline-primary"><i class="fas fa-building me-1"></i> Empresas Cadastradas</a>
            <a href="/painel/empresas/create" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Nova Empresa</a>
            <a href="/painel/planos" class="btn btn-outline-primary"><i class="fas fa-layer-group me-1"></i> Gerenciar Planos</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><a href="/painel/empresas" class="text-decoration-none text-reset"><div class="card border-0 shadow-sm h-100"><div class="card-body"><span class="text-muted small">Empresas cadastradas</span><div class="display-6 fw-bold"><?= (int) $totalTenants ?></div><small class="text-primary">Consultar empresas <i class="fas fa-arrow-right ms-1"></i></small></div></div></a></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><span class="text-muted small">Empresas ativas</span><div class="display-6 fw-bold text-success"><?= (int) $counts['active'] ?></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><span class="text-muted small">Empresas suspensas</span><div class="display-6 fw-bold text-warning"><?= (int) $counts['suspended'] ?></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><span class="text-muted small">Planos configurados</span><div class="display-6 fw-bold text-primary"><?= count($planos ?? []) ?></div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Planos da plataforma</strong><a href="/painel/planos" class="small">Ver todos</a></div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Plano</th><th>Limite</th><th>Módulos</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($planos as $plano): ?>
                    <tr><td><strong><?= htmlspecialchars($plano->nome) ?></strong><br><small class="text-muted"><?= htmlspecialchars($plano->slug) ?></small></td><td><?= $plano->limite_usuarios === null ? 'Ilimitado' : (int) $plano->limite_usuarios ?></td><td><?= (int) $plano->total_modulos_ativos ?></td><td><span class="badge <?= $plano->status === 'ativo' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($plano->status) ?></span></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Impersonações recentes</strong><a href="/painel/impersonacao/logs" class="small">Auditoria completa</a></div>
                <div class="list-group list-group-flush">
                <?php if (empty($recentImpersonations)): ?>
                    <div class="list-group-item text-muted">Nenhuma impersonação registrada.</div>
                <?php else: foreach ($recentImpersonations as $log): ?>
                    <div class="list-group-item"><div class="d-flex justify-content-between gap-2"><strong><?= htmlspecialchars($log->target_tenant_name) ?></strong><small class="text-muted"><?= htmlspecialchars($log->started_at) ?></small></div><small class="text-muted">Por <?= htmlspecialchars($log->saas_admin_name ?? 'Usuário removido') ?> · <?= htmlspecialchars($log->end_reason ?: 'em andamento') ?></small></div>
                <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
