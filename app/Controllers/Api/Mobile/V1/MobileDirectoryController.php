<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Models\Cliente;
use App\Models\ClienteContato;
use App\Models\ColaboradorLocalizacao;
use App\Models\Fornecedor;
use App\Models\MobileReadRepository;

class MobileDirectoryController extends MobileController
{
    public function clients(): void
    {
        $this->requirePermission('view_clients');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->clients(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('status', 'ativo'), 20),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function client(int $id): void
    {
        $this->requirePermission('view_clients');
        $repository = new MobileReadRepository();
        $client = $repository->client($id);
        if (!$client) {
            $this->error('Cliente não encontrado.', [], 404);
        }
        $this->success(['cliente' => $client, 'contatos' => $repository->clientContacts($id)]);
    }

    public function storeClient(): void
    {
        $this->requirePermission('create_clients');
        $input = $this->input();
        $data = $this->clientData($input, true);
        $clientModel = new Cliente();
        if ($clientModel->cpfCnpjExists($data['cpf_cnpj'])) {
            $this->error('Este CPF/CNPJ já está cadastrado.', ['cpf_cnpj' => ['Documento já cadastrado.']], 409);
        }

        try {
            $id = $clientModel->create($data);
            if (!$id) {
                $this->error('Não foi possível criar o cliente.', [], 500);
            }
            $this->recordLocation($input, 'cliente_create', 'clientes', (int) $id);
            $this->audit('create_client', ['client_id' => (int) $id]);
            $this->success(['id' => (int) $id], 'Cliente criado.', 201);
        } catch (\Throwable $exception) {
            $this->writeFailure('criar cliente mobile', $exception, ['cpf_cnpj' => $data['cpf_cnpj']]);
        }
    }

    public function updateClient(int $id): void
    {
        $this->requirePermission('edit_clients');
        $clientModel = new Cliente();
        if (!$clientModel->findById($id)) {
            $this->error('Cliente não encontrado.', [], 404);
        }
        $input = $this->input();
        $data = $this->clientData($input, false);
        if (isset($data['cpf_cnpj']) && $clientModel->cpfCnpjExists($data['cpf_cnpj'], $id)) {
            $this->error('Este CPF/CNPJ já está cadastrado.', ['cpf_cnpj' => ['Documento já cadastrado.']], 409);
        }
        try {
            if (!$clientModel->update($id, $data)) {
                $this->error('Não foi possível atualizar o cliente.', [], 500);
            }
            $this->recordLocation($input, 'cliente_update', 'clientes', $id);
            $this->audit('update_client', ['client_id' => $id]);
            $this->success(['id' => $id], 'Cliente atualizado.');
        } catch (\Throwable $exception) {
            $this->writeFailure('atualizar cliente mobile', $exception, ['client_id' => $id]);
        }
    }

    public function storeClientContact(int $id): void
    {
        $this->requirePermission('edit_clients');
        if (!(new MobileReadRepository())->client($id)) {
            $this->error('Cliente não encontrado.', [], 404);
        }
        $input = $this->input();
        $name = $this->cleanString($input['nome'] ?? '', 255);
        if ($name === '') {
            $this->error('Informe o nome do contato.', ['nome' => ['Campo obrigatório.']], 422);
        }
        try {
            $contactId = (new ClienteContato())->create([
                'cliente_id' => $id,
                'nome' => $name,
                'departamento' => $this->cleanString($input['departamento'] ?? '', 255),
                'email' => $this->cleanString($input['email'] ?? '', 255),
                'celular' => $this->digits($input['celular'] ?? ''),
                'telefone' => $this->digits($input['telefone'] ?? ''),
                'cargo' => $this->cleanString($input['cargo'] ?? '', 255),
                'observacoes' => $this->cleanString($input['observacoes'] ?? '', 5000),
            ]);
            if (!$contactId) {
                $this->error('Não foi possível salvar o contato.', [], 500);
            }
            $this->audit('mobile_client_contact_created', ['client_id' => $id, 'contact_id' => (int) $contactId]);
            $this->success(['id' => (int) $contactId], 'Contato criado.', 201);
        } catch (\Throwable $exception) {
            $this->writeFailure('criar contato de cliente mobile', $exception, ['client_id' => $id]);
        }
    }

    public function vendors(): void
    {
        $this->requirePermission('view_fornecedores');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->vendors(
            $this->cleanString($this->query('q', ''), 100),
            $this->cleanString($this->query('status', 'ativo'), 20),
            $pagination['page'],
            $pagination['per_page']
        );
        $this->success($this->paginated($result['items'], $result['total'], $pagination));
    }

    public function vendor(int $id): void
    {
        $this->requirePermission('view_fornecedores');
        $vendor = (new MobileReadRepository())->vendor($id);
        if (!$vendor) {
            $this->error('Fornecedor não encontrado.', [], 404);
        }
        $this->success(['fornecedor' => $vendor]);
    }

    public function storeVendor(): void
    {
        $this->requirePermission('create_fornecedores');
        $input = $this->input();
        $data = $this->vendorData($input);
        if ($data['nome'] === '') {
            $this->error('Informe o nome do fornecedor.', ['nome' => ['Campo obrigatório.']], 422);
        }
        $model = new Fornecedor();
        if ($model->nameExistsForTenant($data['nome'], $this->currentTenantId()) || ($data['documento'] !== '' && $model->documentoExistsForTenant($data['documento'], $this->currentTenantId()))) {
            $this->error('Já existe fornecedor com o mesmo nome ou documento.', ['fornecedor' => ['Registro duplicado.']], 409);
        }
        try {
            $id = $model->create(array_merge($data, [
                'tenant_id' => $this->currentTenantId(),
                'usuario_id' => $this->currentUserId(),
            ]));
            if (!$id) {
                $this->error('Não foi possível criar o fornecedor.', [], 500);
            }
            $this->audit('mobile_vendor_created', ['fornecedor_id' => (int) $id]);
            $this->success(['id' => (int) $id], 'Fornecedor criado.', 201);
        } catch (\Throwable $exception) {
            $this->writeFailure('criar fornecedor mobile', $exception);
        }
    }

    public function updateVendor(int $id): void
    {
        $this->requirePermission('edit_fornecedores');
        if (!(new MobileReadRepository())->vendor($id)) {
            $this->error('Fornecedor não encontrado.', [], 404);
        }
        $input = $this->input();
        $data = $this->vendorData($input);
        $model = new Fornecedor();
        if ($data['nome'] === '') {
            $this->error('Informe o nome do fornecedor.', ['nome' => ['Campo obrigatório.']], 422);
        }
        if ($model->nameExistsForTenant($data['nome'], $this->currentTenantId(), $id) || ($data['documento'] !== '' && $model->documentoExistsForTenant($data['documento'], $this->currentTenantId(), $id))) {
            $this->error('Já existe fornecedor com o mesmo nome ou documento.', ['fornecedor' => ['Registro duplicado.']], 409);
        }
        try {
            if (!$model->updateForTenant($id, $this->currentTenantId(), $data)) {
                $this->error('Não foi possível atualizar o fornecedor.', [], 500);
            }
            $this->audit('mobile_vendor_updated', ['fornecedor_id' => $id]);
            $this->success(['id' => $id], 'Fornecedor atualizado.');
        } catch (\Throwable $exception) {
            $this->writeFailure('atualizar fornecedor mobile', $exception, ['fornecedor_id' => $id]);
        }
    }

    private function clientData(array $input, bool $creating): array
    {
        $tipo = strtoupper($this->cleanString($input['tipo'] ?? 'PJ', 2));
        $documento = $this->digits($input['cpf_cnpj'] ?? '');
        $razaoSocial = $this->cleanString($input['razao_social'] ?? '', 255);
        $email = strtolower($this->cleanString($input['email'] ?? '', 255));
        if ($creating && ($razaoSocial === '' || $documento === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->error('Razão social, CPF/CNPJ e e-mail são obrigatórios.', [
                'razao_social' => ['Campo obrigatório.'],
                'cpf_cnpj' => ['Campo obrigatório.'],
                'email' => ['Informe um e-mail válido.'],
            ], 422);
        }
        if ($documento !== '' && (($tipo === 'PF' && strlen($documento) !== 11) || ($tipo === 'PJ' && strlen($documento) !== 14))) {
            $this->error('CPF/CNPJ inválido para o tipo informado.', ['cpf_cnpj' => ['Documento inválido.']], 422);
        }

        $fields = ['tipo', 'cpf_cnpj', 'razao_social', 'nome_fantasia', 'email', 'telefone', 'celular', 'website', 'instagram', 'linkedin', 'tiktok', 'facebook', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'cep', 'cnae_principal', 'descricao_cnae', 'segmento_principal', 'especialidades_interesse', 'volume_exames_mes', 'equipamentos_possui', 'sistema_atual', 'num_medicos', 'num_unidades', 'acreditacao', 'responsavel_nome', 'responsavel_cargo', 'responsavel_email', 'responsavel_telefone', 'status'];
        $data = [];
        foreach ($fields as $field) {
            if (!$creating && !array_key_exists($field, $input)) {
                continue;
            }
            $data[$field] = match ($field) {
                'tipo' => $tipo,
                'cpf_cnpj' => $documento,
                'razao_social' => $razaoSocial,
                'email' => $email,
                'telefone', 'celular', 'responsavel_telefone' => $this->digits($input[$field] ?? ''),
                'cep' => $this->digits($input[$field] ?? ''),
                'estado' => strtoupper($this->cleanString($input[$field] ?? '', 2)),
                'volume_exames_mes', 'num_medicos', 'num_unidades' => isset($input[$field]) ? (int) $input[$field] : null,
                default => $this->cleanString($input[$field] ?? '', 5000),
            };
        }
        if ($creating) {
            $data['usuario_id'] = $this->currentUserId();
            $data['status'] = $data['status'] ?: 'ativo';
        }
        return $data;
    }

    private function vendorData(array $input): array
    {
        $fields = ['tipo', 'nome', 'nome_fantasia', 'documento', 'email', 'telefone', 'celular', 'contato_nome', 'website', 'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'inscricao_estadual', 'inscricao_municipal', 'prazo_pagamento', 'cnae_principal', 'descricao_cnae', 'observacoes', 'status'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = match ($field) {
                'documento', 'telefone', 'celular', 'cep' => $this->digits($input[$field] ?? ''),
                'estado' => strtoupper($this->cleanString($input[$field] ?? '', 2)),
                'email' => strtolower($this->cleanString($input[$field] ?? '', 255)),
                default => $this->cleanString($input[$field] ?? '', 5000),
            };
        }
        $data['status'] = $data['status'] ?: 'ativo';
        return $data;
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

    private function digits(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    private function writeFailure(string $operation, \Throwable $exception, array $context = []): never
    {
        (new \App\Core\Logger())->error('Falha ao ' . $operation, array_merge($context, ['error' => $exception->getMessage(), 'user_id' => $this->currentUserId()]));
        $this->error('Não foi possível concluir a operação.', [], 500);
    }
}
