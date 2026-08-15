<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Plano;
use App\Models\Tenant;
use App\Models\TenantImpersonationLog;

class SaasDashboardController extends Controller
{
    private Tenant $tenantModel;
    private Plano $planoModel;
    private TenantImpersonationLog $impersonationLogModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->planoModel = new Plano();
        $this->impersonationLogModel = new TenantImpersonationLog();
    }

    public function index(): void
    {
        if (!Auth::can('view_saas_dashboard')) {
            http_response_code(403);
            exit('403 - Acesso Negado');
        }

        $tenants = $this->tenantModel->listAllWithPlan();
        $counts = ['active' => 0, 'inactive' => 0, 'suspended' => 0];
        foreach ($tenants as $tenant) {
            $status = (string) ($tenant->status ?? 'inactive');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        View::render('saas_admin/dashboard', [
            'title' => 'Painel SaaS',
            'breadcrumb' => ['Painel SaaS' => '/saas-admin'],
            'totalTenants' => count($tenants),
            'tenantCounts' => $counts,
            'planos' => $this->planoModel->listAll(),
            'recentImpersonations' => array_slice($this->impersonationLogModel->listRecent(10), 0, 5),
            '_layout' => 'erp',
        ]);
    }
}
