<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Logger;
use App\Models\ColaboradorLocalizacao;
use App\Models\CrmInteracao;
use App\Models\CrmLead;
use App\Models\CrmOportunidade;
use App\Models\MobileReadRepository;

class MobileCrmController extends MobileController
{
    public function leads(): void
    {
        $this->requirePermission('view_crm');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->leads($this->cleanString($this->query('q', ''), 100), $pagination['page'], $pagination['per_page']);
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function storeLead(): void
    {
        $this->requirePermission('manage_leads');
        $input = $this->input();
        $name = $this->cleanString($input['nome_lead'] ?? '', 255);
        if ($name === '') {
            $this->error('Informe o nome do lead.', ['nome_lead' => ['Campo obrigatório.']], 422);
        }

        $data = [
            'usuario_id' => $this->currentUserId(),
            'nome_lead' => $name,
            'email' => strtolower($this->cleanString($input['email'] ?? '', 255)),
            'telefone' => $this->digits($input['telefone'] ?? ''),
            'celular' => $this->digits($input['celular'] ?? ''),
            'cnpj' => $this->digits($input['cnpj'] ?? ''),
            'cpf' => $this->digits($input['cpf'] ?? ''),
            'tipo_pessoa' => strtoupper($this->cleanString($input['tipo_pessoa'] ?? 'PJ', 2)),
            'razao_social' => $this->cleanString($input['razao_social'] ?? '', 255),
            'nome_fantasia' => $this->cleanString($input['nome_fantasia'] ?? '', 255),
            'origem' => $this->enum($input['origem'] ?? 'outro', ['indicacao', 'site', 'evento', 'linkedin', 'prospeccao_ativa', 'parceiro', 'outro'], 'outro'),
            'status_lead' => $this->enum($input['status_lead'] ?? 'novo', ['novo', 'contatado', 'qualificado', 'descartado'], 'novo'),
            'cidade' => $this->cleanString($input['cidade'] ?? '', 100),
            'estado' => strtoupper($this->cleanString($input['estado'] ?? '', 2)),
            'observacoes' => $this->cleanString($input['observacoes'] ?? '', 5000),
            'data_proximo_contato' => $this->dateOrNull($input['data_proximo_contato'] ?? null),
        ];

        try {
            $id = (new CrmLead())->create($data);
            if (!$id) {
                $this->error('Não foi possível criar o lead.', [], 500);
            }
            $this->recordLocation($input, 'crm_interacao', 'crm_leads', (int) $id);
            $this->audit('mobile_crm_lead_created', ['lead_id' => (int) $id]);
            $this->success(['id' => (int) $id], 'Lead criado.', 201);
        } catch (\Throwable $exception) {
            $this->failure('criar lead móvel', $exception);
        }
    }

    public function opportunities(): void
    {
        $this->requirePermission('view_crm');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->opportunities(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('stage', ''), 30),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function storeOpportunity(): void
    {
        $this->requirePermission('manage_oportunidades');
        $input = $this->input();
        $title = $this->cleanString($input['titulo_oportunidade'] ?? '', 255);
        if ($title === '') {
            $this->error('Informe o título da oportunidade.', ['titulo_oportunidade' => ['Campo obrigatório.']], 422);
        }
        $data = [
            'usuario_id' => $this->currentUserId(),
            'lead_id' => !empty($input['lead_id']) ? (int) $input['lead_id'] : null,
            'cliente_id' => !empty($input['cliente_id']) ? (int) $input['cliente_id'] : null,
            'titulo_oportunidade' => $title,
            'etapa_funil' => $this->enum($input['etapa_funil'] ?? 'qualificacao', ['qualificacao', 'proposta', 'negociacao', 'fechamento'], 'qualificacao'),
            'valor_estimado' => isset($input['valor_estimado']) ? max(0, (float) $input['valor_estimado']) : null,
            'data_fechamento_prevista' => $this->dateOrNull($input['data_fechamento_prevista'] ?? null),
            'probabilidade_sucesso' => isset($input['probabilidade_sucesso']) ? min(100, max(0, (int) $input['probabilidade_sucesso'])) : null,
            'status_oportunidade' => $this->enum($input['status_oportunidade'] ?? 'aberta', ['aberta', 'ganha', 'perdida'], 'aberta'),
            'modalidade_principal' => $this->cleanString($input['modalidade_principal'] ?? '', 50),
            'tipo_contrato' => $this->cleanString($input['tipo_contrato'] ?? '', 50),
            'volume_estimado_mes' => isset($input['volume_estimado_mes']) ? max(0, (int) $input['volume_estimado_mes']) : null,
            'observacoes' => $this->cleanString($input['observacoes'] ?? '', 5000),
            'data_proximo_contato' => $this->dateOrNull($input['data_proximo_contato'] ?? null),
        ];
        try {
            $id = (new CrmOportunidade())->create($data);
            if (!$id) {
                $this->error('Não foi possível criar a oportunidade.', [], 500);
            }
            $this->audit('mobile_crm_opportunity_created', ['oportunidade_id' => (int) $id]);
            $this->success(['id' => (int) $id], 'Oportunidade criada.', 201);
        } catch (\Throwable $exception) {
            $this->failure('criar oportunidade móvel', $exception);
        }
    }

    public function pipeline(): void
    {
        $this->requirePermission('view_crm');
        $this->success(['stages' => (new MobileReadRepository())->crmPipeline()]);
    }

    public function proposals(): void
    {
        $this->requirePermission('view_crm');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->proposals($this->cleanString($this->query('q', ''), 100), $pagination['page'], $pagination['per_page']);
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function storeInteraction(): void
    {
        $this->requirePermission('view_crm');
        $input = $this->input();
        $relatedType = $this->enum($input['related_type'] ?? '', ['lead', 'oportunidade'], '');
        $relatedId = (int) ($input['related_id'] ?? 0);
        $summary = $this->cleanString($input['resumo'] ?? '', 5000);
        if ($relatedType === '' || $relatedId <= 0 || $summary === '') {
            $this->error('Tipo, registro relacionado e resumo são obrigatórios.', ['interacao' => ['Dados incompletos.']], 422);
        }

        try {
            $id = (new CrmInteracao())->create([
                'usuario_id' => $this->currentUserId(),
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'data_interacao' => $this->dateTimeOrNow($input['data_interacao'] ?? null),
                'tipo_interacao' => $this->enum($input['tipo_interacao'] ?? 'outro', ['email', 'telefone', 'whatsapp', 'reuniao_presencial', 'reuniao_online', 'visita_tecnica', 'proposta_enviada', 'contrato_enviado', 'transferencia', 'outro'], 'outro'),
                'resumo' => $summary,
                'data_retorno' => $this->dateOrNull($input['data_retorno'] ?? null),
            ]);
            if (!$id) {
                $this->error('Não foi possível registrar a interação.', [], 500);
            }
            $this->recordLocation($input, 'crm_interacao', 'crm_' . $relatedType . 's', $relatedId);
            $this->audit('mobile_crm_interaction_created', ['interacao_id' => (int) $id, 'related_type' => $relatedType, 'related_id' => $relatedId]);
            $this->success(['id' => (int) $id], 'Interação registrada.', 201);
        } catch (\Throwable $exception) {
            $this->failure('criar interação CRM móvel', $exception);
        }
    }

    private function recordLocation(array $input, string $context, string $referenceTable, int $referenceId): void
    {
        if (!isset($input['latitude'], $input['longitude'])) {
            return;
        }
        $latitude = filter_var($input['latitude'], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($input['longitude'], FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return;
        }
        (new ColaboradorLocalizacao())->create([
            'user_id' => $this->currentUserId(),
            'latitude' => number_format((float) $latitude, 7, '.', ''),
            'longitude' => number_format((float) $longitude, 7, '.', ''),
            'accuracy_meters' => isset($input['accuracy_meters']) ? (float) $input['accuracy_meters'] : null,
            'contexto' => $context,
            'referencia_tabela' => $referenceTable,
            'referencia_id' => $referenceId,
        ]);
    }

    private function enum(mixed $value, array $allowed, string $default): string
    {
        $value = $this->cleanString($value, 50);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function dateOrNull(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') return null;
        $time = strtotime((string) $value);
        return $time === false ? null : date('Y-m-d', $time);
    }

    private function dateTimeOrNow(mixed $value): string
    {
        $time = $value ? strtotime((string) $value) : false;
        return $time === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $time);
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    private function failure(string $operation, \Throwable $exception): never
    {
        (new Logger())->error('Falha ao ' . $operation, ['user_id' => $this->currentUserId(), 'error' => $exception->getMessage()]);
        $this->error('Não foi possível concluir a operação.', [], 500);
    }
}
