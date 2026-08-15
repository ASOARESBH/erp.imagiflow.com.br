<?php use App\Core\Auth; ?>
<div class="container-fluid py-4 saas-admin">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div><h1 class="h3 mb-1">Empresas SaaS</h1><p class="text-muted mb-0">Tenants cadastrados, seus planos e usuários master.</p></div>
        <?php if (Auth::can('create_saas_tenants')): ?><a href="/saas-admin/empresas/create" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Nova Empresa</a><?php endif; ?>
    </div>
    <?php if (!empty($_GET['error'])): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $_GET['error']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['success'])): ?><div class="alert alert-success">Operação concluída com sucesso.</div><?php endif; ?>

    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Empresa</th><th>Plano</th><th>Domínio</th><th>Usuário master</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
        <?php foreach ($empresas as $empresa): ?>
            <?php $isControl = $empresa->slug === 'imagiflow-saas-admin'; ?>
            <tr>
                <td><strong><?= htmlspecialchars($empresa->nome_fantasia ?: $empresa->name) ?></strong><br><small class="text-muted"><?= htmlspecialchars($empresa->cnpj ?: $empresa->slug) ?></small></td>
                <td><?= htmlspecialchars($empresa->plano_nome ?: 'Sem plano') ?></td>
                <td><small><?= htmlspecialchars($empresa->domain ?: $empresa->subdomain ?: 'Não configurado') ?></small></td>
                <td><?= htmlspecialchars($empresa->master_user_name ?: 'Não definido') ?><br><small class="text-muted"><?= htmlspecialchars($empresa->master_user_email ?: '') ?></small></td>
                <td><span class="badge <?= $empresa->status === 'active' ? 'bg-success' : ($empresa->status === 'suspended' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= htmlspecialchars($empresa->status) ?></span></td>
                <td class="text-end"><div class="btn-group btn-group-sm">
                    <?php if (Auth::can('edit_saas_tenants')): ?><a class="btn btn-outline-primary" href="/saas-admin/empresas/edit/<?= (int) $empresa->id ?>"><i class="fas fa-pen"></i></a><?php endif; ?>
                    <?php if (!$isControl && Auth::can('impersonate_saas_tenant') && $empresa->status === 'active' && $empresa->master_user_id): ?>
                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#impersonateModal" data-tenant-id="<?= (int) $empresa->id ?>" data-tenant-name="<?= htmlspecialchars($empresa->nome_fantasia ?: $empresa->name) ?>"><i class="fas fa-user-secret"></i></button>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div>

<div class="modal fade" id="impersonateModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" id="impersonateForm" class="modal-content"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><div class="modal-header"><h5 class="modal-title">Iniciar impersonação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Você entrará no ambiente de <strong id="impersonateTenantName"></strong> como usuário master.</p><label class="form-label" for="reason">Motivo do suporte</label><input class="form-control" id="reason" name="reason" maxlength="255" required></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Confirmar impersonação</button></div></form></div></div>
<script src="/assets/js/saas-admin.js?v=20260816"></script>
