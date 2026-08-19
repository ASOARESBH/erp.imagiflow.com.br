<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Logger;
use App\Models\ColaboradorLocalizacao;
use App\Models\MobileReadRepository;
use App\Models\RdvDespesa;
use App\Models\RdvViagem;

class MobileRdvController extends MobileController
{
    public function trips(): void
    {
        $this->requirePermission('view_rdv');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->trips(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('status', ''), 30),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function storeTrip(): void
    {
        $this->requirePermission('create_rdv');
        $input = $this->input();
        $name = $this->cleanString($input['nome'] ?? '', 255);
        $start = $this->dateOrNull($input['periodo_inicio'] ?? null);
        $end = $this->dateOrNull($input['periodo_fim'] ?? null);
        if ($name === '' || !$start || !$end || $end < $start) {
            $this->error('Informe nome e período válido da viagem.', ['viagem' => ['Dados inválidos.']], 422);
        }

        $model = new RdvViagem();
        $userId = $this->currentUserId();
        try {
            $id = $model->create([
                'usuario_id' => $userId,
                'rota_id' => !empty($input['rota_id']) ? (int) $input['rota_id'] : null,
                'codigo' => $model->gerarCodigo($userId),
                'nome' => $name,
                'periodo_inicio' => $start,
                'periodo_fim' => $end,
                'motivo' => $this->cleanString($input['motivo'] ?? '', 1000),
                'cidade' => $this->cleanString($input['cidade'] ?? '', 100),
                'estado' => strtoupper($this->cleanString($input['estado'] ?? '', 2)),
                'pais' => $this->cleanString($input['pais'] ?? 'Brasil', 100),
                'valor_previsto' => max(0, (float) ($input['valor_previsto'] ?? 0)),
                'observacoes' => $this->cleanString($input['observacoes'] ?? '', 5000),
            ]);
            if (!$id) {
                $this->error('Não foi possível criar a viagem.', [], 500);
            }
            $this->audit('mobile_rdv_trip_created', ['viagem_id' => $id]);
            $this->success(['id' => $id], 'Viagem criada.', 201);
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao criar viagem RDV mobile', ['user_id' => $userId, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível criar a viagem.', [], 500);
        }
    }

    public function storeExpense(int $tripId): void
    {
        $this->requirePermission('create_rdv');
        $trip = (new RdvViagem())->findById($tripId);
        if (!$trip || (int) ($trip->tenant_id ?? 0) !== $this->currentTenantId()) {
            $this->error('Viagem não encontrada.', [], 404);
        }
        $input = $this->input();
        $description = $this->cleanString($input['descricao'] ?? '', 1000);
        $value = (float) ($input['valor'] ?? 0);
        if ($description === '' || $value <= 0) {
            $this->error('Descrição e valor positivo são obrigatórios.', ['despesa' => ['Dados inválidos.']], 422);
        }

        try {
            $id = (new RdvDespesa())->create([
                'viagem_id' => $tripId,
                'categoria_id' => !empty($input['categoria_id']) ? (int) $input['categoria_id'] : null,
                'forma_pagamento_id' => !empty($input['forma_pagamento_id']) ? (int) $input['forma_pagamento_id'] : null,
                'descricao' => $description,
                'valor' => $value,
                'data_documento' => $this->dateOrNull($input['data_documento'] ?? date('Y-m-d')),
                'numero_documento' => $this->cleanString($input['numero_documento'] ?? '', 255),
                'fornecedor' => $this->cleanString($input['fornecedor'] ?? '', 255),
                'cnpj_fornecedor' => $this->digits($input['cnpj_fornecedor'] ?? ''),
                'cidade' => $this->cleanString($input['cidade'] ?? '', 100),
                'hora_documento' => $this->cleanString($input['hora_documento'] ?? '', 10),
                'ocr_json' => isset($input['ocr_json']) ? json_encode($input['ocr_json']) : null,
                'ocr_status' => $this->cleanString($input['ocr_status'] ?? 'manual', 30),
                'tipo' => 'simples',
            ]);
            if (!$id) {
                $this->error('Não foi possível registrar a despesa.', [], 500);
            }
            (new RdvViagem())->recalcularValorReal($tripId);
            $this->recordLocation($input, $tripId, $id);
            $this->audit('mobile_rdv_expense_created', ['viagem_id' => $tripId, 'despesa_id' => $id]);
            $this->success(['id' => $id], 'Despesa registrada.', 201);
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao criar despesa RDV mobile', ['viagem_id' => $tripId, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível registrar a despesa.', [], 500);
        }
    }

    public function uploadReceipt(int $expenseId): void
    {
        $this->requirePermission('create_rdv');
        $expense = (new RdvDespesa())->findById($expenseId);
        if (!$expense) {
            $this->error('Despesa não encontrada.', [], 404);
        }
        $trip = (new RdvViagem())->findById((int) $expense->viagem_id);
        if (!$trip || (int) ($trip->tenant_id ?? 0) !== $this->currentTenantId()) {
            $this->error('Despesa não encontrada.', [], 404);
        }
        $file = $_FILES['receipt'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
            $this->error('Envie um comprovante válido de até 10 MB.', ['receipt' => ['Arquivo inválido.']], 422);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
        if (!isset($extensions[$mime])) {
            $this->error('Use imagem JPG, PNG, WEBP ou PDF.', ['receipt' => ['Formato inválido.']], 422);
        }
        $directory = BASE_PATH . '/storage/uploads/rdv/' . $this->currentTenantId() . '/' . $trip->id;
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            $this->error('Não foi possível preparar o envio do comprovante.', [], 500);
        }
        $relative = '/storage/uploads/rdv/' . $this->currentTenantId() . '/' . $trip->id . '/despesa-' . $expenseId . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], BASE_PATH . $relative)) {
            $this->error('Não foi possível salvar o comprovante.', [], 500);
        }
        try {
            if (!(new RdvDespesa())->updateArquivo($expenseId, (int) $trip->id, $relative)) {
                @unlink(BASE_PATH . $relative);
                $this->error('Não foi possível registrar o comprovante.', [], 500);
            }
            $this->audit('mobile_rdv_receipt_uploaded', ['despesa_id' => $expenseId]);
            $this->success(['url' => $relative], 'Comprovante enviado.');
        } catch (\Throwable $exception) {
            @unlink(BASE_PATH . $relative);
            (new Logger())->error('Falha ao registrar comprovante RDV mobile', ['despesa_id' => $expenseId, 'error' => $exception->getMessage()]);
            $this->error('Não foi possível concluir o envio.', [], 500);
        }
    }

    private function recordLocation(array $input, int $tripId, int $expenseId): void
    {
        if (!isset($input['latitude'], $input['longitude'])) return;
        $latitude = filter_var($input['latitude'], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($input['longitude'], FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) return;
        (new ColaboradorLocalizacao())->create([
            'user_id' => $this->currentUserId(),
            'latitude' => number_format((float) $latitude, 7, '.', ''),
            'longitude' => number_format((float) $longitude, 7, '.', ''),
            'accuracy_meters' => isset($input['accuracy_meters']) ? (float) $input['accuracy_meters'] : null,
            'contexto' => 'rdv_visita',
            'referencia_tabela' => 'rdv_despesas',
            'referencia_id' => $expenseId,
        ]);
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
