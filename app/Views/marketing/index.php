<?php
use App\Core\UI;
use App\Core\Auth;

UI::sectionHeader('Marketing', 'Campanhas, automações e comunicação com clientes', []);

$totalCampanhas   = $totalCampanhas   ?? 0;
$gatilhosAtivos   = $gatilhosAtivos   ?? 0;
$statusCampanhas  = $statusCampanhas  ?? [];
$ultimasCampanhas = $ultimasCampanhas ?? [];

$statusMap = [
    'rascunho'  => ['label' => 'Rascunho',   'cls' => 'secondary', 'icon' => 'fas fa-pencil-alt'],
    'agendada'  => ['label' => 'Agendada',   'cls' => 'primary',   'icon' => 'fas fa-clock'],
    'enviando'  => ['label' => 'Enviando',   'cls' => 'info',      'icon' => 'fas fa-paper-plane'],
    'concluida' => ['label' => 'Concluída',  'cls' => 'success',   'icon' => 'fas fa-check-circle'],
    'cancelada' => ['label' => 'Cancelada',  'cls' => 'danger',    'icon' => 'fas fa-ban'],
];
?>

<!-- KPIs -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="fas fa-bullhorn fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="fw-bold fs-3 lh-1"><?php echo $totalCampanhas; ?></div>
                    <div class="text-muted small">Campanhas</div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-2 px-3">
                <a href="/marketing/campanhas" class="small text-primary text-decoration-none">
                    Ver campanhas <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="fas fa-bolt fa-lg text-success"></i>
                </div>
                <div>
                    <div class="fw-bold fs-3 lh-1"><?php echo $gatilhosAtivos; ?></div>
                    <div class="text-muted small">Gatilhos Ativos</div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-2 px-3">
                <a href="/marketing/gatilhos" class="small text-success text-decoration-none">
                    Ver gatilhos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-warning bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="fas fa-check-circle fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="fw-bold fs-3 lh-1"><?php echo $statusCampanhas['concluida'] ?? 0; ?></div>
                    <div class="text-muted small">Concluídas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-info bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="fas fa-paper-plane fa-lg text-info"></i>
                </div>
                <div>
                    <div class="fw-bold fs-3 lh-1"><?php echo ($statusCampanhas['enviando'] ?? 0) + ($statusCampanhas['agendada'] ?? 0); ?></div>
                    <div class="text-muted small">Em Andamento</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Últimas campanhas -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-bullhorn me-2 text-primary"></i>Últimas Campanhas</h6>
                <a href="/marketing/campanhas" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasCampanhas)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-bullhorn fa-2x mb-2 d-block opacity-30"></i>
                    <div class="small">Nenhuma campanha criada ainda.</div>
                    <a href="/marketing/campanhas" class="btn btn-sm btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i>Criar Primeira Campanha
                    </a>
                </div>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="small text-muted fw-semibold px-3">Campanha</th>
                            <th class="small text-muted fw-semibold">Tipo</th>
                            <th class="small text-muted fw-semibold">Status</th>
                            <th class="small text-muted fw-semibold">Criada em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasCampanhas as $c):
                            $sm = $statusMap[$c->status ?? 'rascunho'] ?? $statusMap['rascunho'];
                        ?>
                        <tr>
                            <td class="px-3 fw-semibold"><?php echo htmlspecialchars($c->nome); ?></td>
                            <td>
                                <?php if (($c->tipo ?? '') === 'email'): ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-envelope me-1"></i>E-mail</span>
                                <?php elseif (($c->tipo ?? '') === 'whatsapp'): ?>
                                    <span class="badge bg-success"><i class="fab fa-whatsapp me-1"></i>WhatsApp</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-sms me-1"></i>SMS</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $sm['cls']; ?>">
                                    <i class="<?php echo $sm['icon']; ?> me-1"></i><?php echo $sm['label']; ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?php echo $c->created_at ? date('d/m/Y', strtotime($c->created_at)) : '—'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Atalhos -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-rocket me-2 text-warning"></i>Ações Rápidas</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/marketing/campanhas" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <div class="rounded-2 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0">
                        <i class="fas fa-bullhorn text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small">Gerenciar Campanhas</div>
                        <div class="text-muted" style="font-size:.75rem">Email, WhatsApp, SMS</div>
                    </div>
                </a>
                <a href="/marketing/gatilhos" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <div class="rounded-2 bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0">
                        <i class="fas fa-bolt text-success"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small">Automação de Gatilhos</div>
                        <div class="text-muted" style="font-size:.75rem">Disparo automático por evento</div>
                    </div>
                </a>
                <a href="/integracao/whatsapp" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <div class="rounded-2 bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0">
                        <i class="fab fa-whatsapp text-success"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small">Configurar WhatsApp</div>
                        <div class="text-muted" style="font-size:.75rem">Integração de mensagens</div>
                    </div>
                </a>
                <a href="/integracao/email" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <div class="rounded-2 bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0">
                        <i class="fas fa-envelope text-info"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small">Configurar E-mail</div>
                        <div class="text-muted" style="font-size:.75rem">SMTP para disparo de emails</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
