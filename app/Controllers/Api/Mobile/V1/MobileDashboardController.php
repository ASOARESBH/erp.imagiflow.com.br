<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Auth;
use App\Core\Permission;
use App\Models\MobileReadRepository;

class MobileDashboardController extends MobileController
{
    public function summary(): void
    {
        $repository = new MobileReadRepository();
        $summary = $repository->dashboard();
        $role = (string) (Auth::user()?->role ?? 'user');
        $permissions = (new Permission())->getPermissionsForRole($role);

        $this->success([
            'kpis' => $summary,
            'quick_actions' => $this->quickActions($permissions),
        ]);
    }

    public function search(): void
    {
        $query = $this->cleanString($this->query('q', ''), 100);
        if (mb_strlen($query) < 2) {
            $this->error('Informe pelo menos dois caracteres para pesquisar.', ['q' => ['Mínimo de dois caracteres.']], 422);
        }
        $role = (string) (Auth::user()?->role ?? 'user');
        $permissions = (new Permission())->getPermissionsForRole($role);
        $items = (new MobileReadRepository())->search($query, $permissions, (int) $this->query('limit', 5));
        $this->success(['query' => $query, 'results' => $items]);
    }

    private function quickActions(array $permissions): array
    {
        $actions = [];
        if (in_array('create_clients', $permissions, true)) {
            $actions[] = ['id' => 'cliente', 'label' => 'Novo cliente', 'route' => '/clientes/novo'];
        }
        if (in_array('manage_leads', $permissions, true)) {
            $actions[] = ['id' => 'lead', 'label' => 'Novo lead', 'route' => '/crm/leads/novo'];
        }
        if (in_array('create_rdv', $permissions, true)) {
            $actions[] = ['id' => 'rdv', 'label' => 'Nova despesa RDV', 'route' => '/rdv/despesas/nova'];
        }
        if (in_array('create_os', $permissions, true)) {
            $actions[] = ['id' => 'os', 'label' => 'Nova ordem de serviço', 'route' => '/manutencao/ordens/nova'];
        }
        return $actions;
    }
}
