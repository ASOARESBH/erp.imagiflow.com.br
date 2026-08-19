<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Models\MobileReadRepository;

class MobileContractsController extends MobileController
{
    public function contracts(): void
    {
        $this->requirePermission('view_faturamento');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->contracts(
            $this->cleanString($this->query('q', ''), 100),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function contract(int $id): void
    {
        $this->requirePermission('view_faturamento');
        $contract = (new MobileReadRepository())->contract($id);
        if (!$contract) {
            $this->error('Contrato não encontrado.', [], 404);
        }
        $this->success(['contrato' => $contract]);
    }

    public function apuracoes(string $type): void
    {
        $this->requirePermission('view_faturamento');
        if (!in_array($type, ['cliente', 'prestador'], true)) {
            $this->error('Tipo de apuração inválido.', ['type' => ['Use cliente ou prestador.']], 422);
        }
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->apuracoes($type, $pagination['page'], $pagination['per_page']);
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }
}
