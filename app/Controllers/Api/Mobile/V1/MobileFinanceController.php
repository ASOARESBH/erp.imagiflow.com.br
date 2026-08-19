<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Logger;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\MobileReadRepository;

class MobileFinanceController extends MobileController
{
    public function payables(): void
    {
        $this->requirePermission('view_contas_pagar');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->payables(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('status', 'aberta'), 30),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function receivables(): void
    {
        $this->requirePermission('view_contas_receber');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->receivables(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('status', 'aberta'), 30),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function summary(): void
    {
        $this->requirePermission('view_finance');
        $this->success((new MobileReadRepository())->financialSummary());
    }

    public function markPayablePaid(int $id): void
    {
        $this->requirePermission('edit_contas_pagar');
        $model = new ContaPagar();
        $account = $model->findByIdForTenant($id, $this->currentTenantId());
        if (!$account) {
            $this->error('Conta a pagar não encontrada.', [], 404);
        }
        if ((string) $account->status === 'paga') {
            $this->success(['id' => $id], 'Esta conta já está paga.');
        }

        $input = $this->input();
        $date = $this->date($input['data_pagamento'] ?? date('Y-m-d'));
        try {
            if (!$model->updateForTenant($id, $this->currentTenantId(), ['status' => 'paga', 'data_pagamento' => $date])) {
                $this->error('Não foi possível baixar a conta a pagar.', [], 500);
            }
            $this->audit('mobile_payable_marked_paid', ['conta_pagar_id' => $id, 'data_pagamento' => $date]);
            $this->success(['id' => $id, 'status' => 'paga'], 'Conta marcada como paga.');
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao baixar conta a pagar pelo app móvel', ['conta_pagar_id' => $id, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível baixar a conta a pagar.', [], 500);
        }
    }

    public function markReceivableReceived(int $id): void
    {
        $this->requirePermission('edit_contas_receber');
        $model = new ContaReceber();
        $account = $model->findById($id);
        if (!$account || (int) ($account->tenant_id ?? 0) !== $this->currentTenantId()) {
            $this->error('Conta a receber não encontrada.', [], 404);
        }
        if ((string) $account->status === 'recebida') {
            $this->success(['id' => $id], 'Esta conta já está recebida.');
        }

        $input = $this->input();
        $date = $this->date($input['data_recebimento'] ?? date('Y-m-d'));
        try {
            // O Model atual aceita somente atualização própria do registro. A conferência de tenant ocorreu antes.
            if (!$model->update($id, ['status' => 'recebida', 'data_recebimento' => $date])) {
                $this->error('Não foi possível baixar a conta a receber.', [], 500);
            }
            $this->audit('mobile_receivable_marked_received', ['conta_receber_id' => $id, 'data_recebimento' => $date]);
            $this->success(['id' => $id, 'status' => 'recebida'], 'Conta marcada como recebida.');
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao baixar conta a receber pelo app móvel', ['conta_receber_id' => $id, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível baixar a conta a receber.', [], 500);
        }
    }

    private function date(mixed $value): string
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            $this->error('Data inválida.', ['date' => ['Informe uma data válida.']], 422);
        }
        return date('Y-m-d', $timestamp);
    }
}
