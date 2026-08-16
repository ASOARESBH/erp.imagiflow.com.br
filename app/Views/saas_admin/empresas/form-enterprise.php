<?php
$empresa = $empresa ?? null;
$isEdit = !empty($isEdit);
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$formFlash = is_array($formFlash ?? null) ? $formFlash : null;
$action = $isEdit ? '/painel/empresas/update/' . (int) $empresa->id : '/painel/empresas';

$value = static function (string $field, string $fallback = '') use ($empresa, $oldInput): string {
    if (array_key_exists($field, $oldInput)) {
        return htmlspecialchars((string) $oldInput[$field]);
    }

    return htmlspecialchars((string) ($empresa->{$field} ?? $fallback));
};

$selectedPlanId = array_key_exists('plano_id', $oldInput)
    ? (int) $oldInput['plano_id']
    : (int) ($empresa->plano_id ?? 0);

$trialEndsAt = array_key_exists('trial_ends_at', $oldInput)
    ? (string) $oldInput['trial_ends_at']
    : (!empty($empresa->trial_ends_at) ? date('Y-m-d\TH:i', strtotime($empresa->trial_ends_at)) : '');
?>

<div class="container-fluid py-4 saas-admin">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($title) ?></h1>
            <p class="text-muted mb-0">Cadastro centralizado de tenant, plano e acesso pelo ERP IMAGINIFLOW.</p>
        </div>
        <a href="/painel/empresas" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>

    <?php if (!empty($formFlash['message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i><?= htmlspecialchars((string) $formFlash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle me-1"></i><?= htmlspecialchars((string) $_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (!empty($_GET['success']) && $_GET['success'] === 'created'): ?>
        <?php $inviteFailed = ($_GET['invite'] ?? '') === 'failed'; ?>
        <div class="alert <?= $inviteFailed ? 'alert-warning' : 'alert-success' ?>" role="alert">
            <i class="fas <?= $inviteFailed ? 'fa-exclamation-circle' : 'fa-check-circle' ?> me-1"></i>
            <?php if ($inviteFailed): ?>
                Empresa cadastrada com sucesso, mas o convite por e-mail não foi entregue. Configure o SMTP e gere uma redefinição de senha para o usuário master.
            <?php else: ?>
                Empresa cadastrada com sucesso. O convite de definição de senha foi enviado ao usuário master.
            <?php endif; ?>
        </div>
    <?php elseif (!empty($_GET['success']) && $_GET['success'] === 'updated'): ?>
        <div class="alert alert-success" role="alert"><i class="fas fa-check-circle me-1"></i>Empresa atualizada com sucesso.</div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($action) ?>" id="saasCompanyForm" class="card border-0 shadow-sm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dados" type="button">1. Dados da Empresa</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#plano" type="button">2. Plano &amp; Faturamento</button></li>
                <li class="nav-item"><button class="nav-link <?= $isEdit ? 'disabled' : '' ?>" data-bs-toggle="tab" data-bs-target="#master" type="button">3. Usuário Master</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#acesso" type="button">4. Acesso</button></li>
            </ul>
        </div>

        <div class="card-body tab-content p-4">
            <section class="tab-pane fade show active" id="dados">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Dados da Empresa</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnBuscarCnpj"><i class="fas fa-search me-1"></i> Buscar CNPJ</button>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="cnpj">CNPJ *</label><input class="form-control" name="cnpj" id="cnpj" value="<?= $value('cnpj') ?>" required maxlength="18"></div>
                    <div class="col-md-8"><label class="form-label" for="razao_social">Razão social *</label><input class="form-control" name="razao_social" id="razao_social" value="<?= $value('razao_social') ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="nome_fantasia">Nome fantasia *</label><input class="form-control" name="nome_fantasia" id="nome_fantasia" value="<?= $value('nome_fantasia', $empresa->name ?? '') ?>" required></div>
                    <div class="col-md-3"><label class="form-label" for="email">E-mail</label><input class="form-control" type="email" name="email" id="email" value="<?= $value('email') ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="telefone">Telefone</label><input class="form-control" name="telefone" id="telefone" value="<?= $value('telefone', $empresa->phone ?? '') ?>"></div>
                </div>

                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Endereço</h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBuscarCep">Buscar CEP</button>
                </div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label" for="cep">CEP</label><input class="form-control" name="cep" id="cep" value="<?= $value('cep') ?>"></div>
                    <div class="col-md-7"><label class="form-label" for="endereco">Logradouro</label><input class="form-control" name="endereco" id="endereco" value="<?= $value('endereco') ?>"></div>
                    <div class="col-md-2"><label class="form-label" for="numero">Número</label><input class="form-control" name="numero" id="numero" value="<?= $value('numero') ?>"></div>
                    <div class="col-md-4"><label class="form-label" for="complemento">Complemento</label><input class="form-control" name="complemento" id="complemento" value="<?= $value('complemento') ?>"></div>
                    <div class="col-md-4"><label class="form-label" for="bairro">Bairro</label><input class="form-control" name="bairro" id="bairro" value="<?= $value('bairro') ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="cidade">Cidade</label><input class="form-control" name="cidade" id="cidade" value="<?= $value('cidade') ?>"></div>
                    <div class="col-md-1"><label class="form-label" for="estado">UF</label><input class="form-control" name="estado" id="estado" maxlength="2" value="<?= $value('estado') ?>"></div>
                </div>
            </section>

            <section class="tab-pane fade" id="plano">
                <h2 class="h5 mb-3">Plano &amp; Faturamento</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="plano_id">Plano SaaS *</label><select class="form-select" name="plano_id" id="plano_id" required><option value="">Selecione</option><?php foreach ($planos as $plano): ?><option value="<?= (int) $plano->id ?>" <?= $selectedPlanId === (int) $plano->id ? 'selected' : '' ?>><?= htmlspecialchars($plano->nome) ?><?= $plano->limite_usuarios === null ? ' — ilimitado' : ' — ' . (int) $plano->limite_usuarios . ' usuários' ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="billing_email">E-mail de cobrança</label><input class="form-control" type="email" name="billing_email" id="billing_email" value="<?= $value('billing_email') ?>"></div>
                    <div class="col-md-4"><label class="form-label" for="trial_ends_at">Fim do teste grátis</label><input class="form-control" type="datetime-local" name="trial_ends_at" id="trial_ends_at" value="<?= htmlspecialchars($trialEndsAt) ?>"></div>
                    <div class="col-md-8"><label class="form-label" for="notes">Observações internas</label><textarea class="form-control" name="notes" id="notes" rows="3"><?= $value('notes') ?></textarea></div>
                </div>
            </section>

            <section class="tab-pane fade" id="master">
                <h2 class="h5 mb-2">Usuário Master</h2>
                <p class="text-muted">Será criado com o papel interno <strong>superadmin</strong> apenas no tenant desta empresa e receberá um link seguro para definir a própria senha.</p>
                <?php if ($isEdit): ?>
                    <div class="alert alert-info mb-0">O usuário master já foi criado: <?= htmlspecialchars($empresa->master_user_email ?: 'não informado') ?>.</div>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="master_name">Nome *</label><input class="form-control" name="master_name" id="master_name" value="<?= $value('master_name') ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="master_email">E-mail *</label><input class="form-control" type="email" name="master_email" id="master_email" value="<?= $value('master_email') ?>" required></div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tab-pane fade" id="acesso">
                <h2 class="h5 mb-3">Acesso pelo ERP</h2>
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label" for="slug">Slug interno *</label><input class="form-control" name="slug" id="slug" value="<?= $value('slug') ?>" required pattern="[a-z0-9-]{3,120}"><div class="form-text">Identificador interno único: letras minúsculas, números e hífen.</div></div>
                    <div class="col-md-7"><label class="form-label">URL de acesso</label><div class="form-control bg-light">https://erp.imagiflow.com.br/login</div><div class="form-text">Todos os usuários acessam o mesmo domínio. Após o login, o sistema seleciona a empresa pelo vínculo do usuário.</div></div>
                </div>
            </section>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between align-items-center gap-3">
            <a href="/painel/empresas" class="btn btn-light">Cancelar</a>
            <button class="btn btn-primary" type="submit" id="btnSalvarEmpresa"><i class="fas fa-save me-1"></i> <?= $isEdit ? 'Salvar Alterações' : 'Criar Empresa e Enviar Convite' ?></button>
        </div>
    </form>
</div>
<script src="/assets/js/saas-admin.js?v=20260816"></script>
