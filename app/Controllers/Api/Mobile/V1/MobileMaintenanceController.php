<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Logger;
use App\Models\MobileReadRepository;
use App\Models\OrdemServico;

class MobileMaintenanceController extends MobileController
{
    public function orders(): void
    {
        $this->requirePermission('view_manutencao');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->maintenanceOrders(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('status', ''), 40),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function storeOrder(): void
    {
        $this->requirePermission('create_os');
        $input = $this->input();
        $reason = $this->cleanString($input['motivo_chamado'] ?? '', 255);
        $clientName = $this->cleanString($input['cliente_nome'] ?? '', 255);
        if ($reason === '' || $clientName === '') {
            $this->error('Cliente e motivo do chamado são obrigatórios.', ['ordem' => ['Dados incompletos.']], 422);
        }

        $model = new OrdemServico();
        $userId = $this->currentUserId();
        try {
            $id = $model->create([
                'usuario_id' => $userId,
                'numero' => $model->gerarNumero($userId),
                'tipo' => $this->enum($input['tipo'] ?? 'corretiva', ['corretiva', 'preventiva', 'instalacao', 'vistoria'], 'corretiva'),
                'status' => 'aberta',
                'cliente_id' => !empty($input['cliente_id']) ? (int) $input['cliente_id'] : null,
                'cliente_nome' => $clientName,
                'cliente_cpf_cnpj' => $this->digits($input['cliente_cpf_cnpj'] ?? ''),
                'cliente_email' => strtolower($this->cleanString($input['cliente_email'] ?? '', 255)),
                'cliente_telefone' => $this->digits($input['cliente_telefone'] ?? ''),
                'cliente_endereco' => $this->cleanString($input['cliente_endereco'] ?? '', 500),
                'cliente_cidade' => $this->cleanString($input['cliente_cidade'] ?? '', 100),
                'cliente_estado' => strtoupper($this->cleanString($input['cliente_estado'] ?? '', 2)),
                'equipamento_id' => !empty($input['equipamento_id']) ? (int) $input['equipamento_id'] : null,
                'produto_id' => !empty($input['produto_id']) ? (int) $input['produto_id'] : null,
                'produto_nome' => $this->cleanString($input['produto_nome'] ?? '', 255),
                'numero_serie' => $this->cleanString($input['numero_serie'] ?? '', 255),
                'marca' => $this->cleanString($input['marca'] ?? '', 255),
                'modelo' => $this->cleanString($input['modelo'] ?? '', 255),
                'motivo_chamado' => $reason,
                'descricao_servico' => $this->cleanString($input['descricao_servico'] ?? '', 5000),
                'data_abertura' => $this->date($input['data_abertura'] ?? date('Y-m-d')),
                'data_previsao' => $this->dateOrNull($input['data_previsao'] ?? null),
                'tecnico_responsavel' => $this->cleanString($input['tecnico_responsavel'] ?? '', 255),
                'prioridade' => $this->enum($input['prioridade'] ?? 'normal', ['baixa', 'normal', 'alta', 'urgente'], 'normal'),
                'valor_servico' => max(0, (float) ($input['valor_servico'] ?? 0)),
                'valor_pecas' => max(0, (float) ($input['valor_pecas'] ?? 0)),
                'valor_total' => max(0, (float) ($input['valor_total'] ?? 0)),
                'observacoes' => $this->cleanString($input['observacoes'] ?? '', 5000),
            ]);
            if (!$id) {
                $this->error('Não foi possível criar a ordem de serviço.', [], 500);
            }
            $this->audit('mobile_maintenance_order_created', ['ordem_servico_id' => $id]);
            $this->success(['id' => $id], 'Ordem de serviço criada.', 201);
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao criar ordem de serviço mobile', ['user_id' => $userId, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível criar a ordem de serviço.', [], 500);
        }
    }

    public function updateOrder(int $id): void
    {
        $this->requirePermission('edit_os');
        $model = new OrdemServico();
        $order = $model->findById($id);
        if (!$order || (int) ($order->tenant_id ?? 0) !== $this->currentTenantId()) {
            $this->error('Ordem de serviço não encontrada.', [], 404);
        }
        $input = $this->input();
        $allowed = ['tipo', 'status', 'motivo_chamado', 'descricao_servico', 'evolucao', 'data_previsao', 'data_conclusao', 'tecnico_responsavel', 'prioridade', 'valor_servico', 'valor_pecas', 'valor_total', 'observacoes'];
        $data = [];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input)) continue;
            $data[$field] = in_array($field, ['valor_servico', 'valor_pecas', 'valor_total'], true)
                ? max(0, (float) $input[$field])
                : $this->cleanString($input[$field], 5000);
        }
        if (empty($data)) {
            $this->error('Nenhum campo de OS foi informado.', [], 422);
        }
        try {
            if (!$model->update($id, $data)) {
                $this->error('Não foi possível atualizar a ordem de serviço.', [], 500);
            }
            $this->audit('mobile_maintenance_order_updated', ['ordem_servico_id' => $id]);
            $this->success(['id' => $id], 'Ordem de serviço atualizada.');
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao atualizar ordem de serviço mobile', ['ordem_servico_id' => $id, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível atualizar a ordem de serviço.', [], 500);
        }
    }

    private function enum(mixed $value, array $allowed, string $default): string
    {
        $value = $this->cleanString($value, 50);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function date(mixed $value): string
    {
        $time = strtotime((string) $value);
        return $time === false ? date('Y-m-d') : date('Y-m-d', $time);
    }

    private function dateOrNull(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') return null;
        $time = strtotime((string) $value);
        return $time === false ? null : date('Y-m-d', $time);
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }
}
