<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Logger;
use App\Core\View;
use App\Models\Integracao;
use App\Services\CryptoService;
use App\Services\VoxelPacsService;

class IntegracaoVoxelController extends \App\Core\Controller
{
    private const PROVIDER = 'voxel_pacs';
    private Integracao $integracaoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->integracaoModel = new Integracao();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        $this->requirePermission();
        $usuarioId = (int) Auth::user()->id;
        $row = $this->integracaoModel->findByNomeAndUsuarioId(self::PROVIDER, $usuarioId);
        $config = $row ? $this->integracaoModel->getDecodedConfig($row) : [];
        $config['secret_configured'] = !empty($config['secret_enc']);
        unset($config['secret_enc']);

        View::render('integracoes/voxel_pacs', [
            'title' => 'ImagiFlow / VOXEL PACS',
            'config' => $config,
            'row' => $row,
            'crypto_configured' => CryptoService::isConfigured(),
            'breadcrumb' => [
                'Configurações' => '/configuracoes',
                'Integrações' => '#',
                'ImagiFlow / VOXEL PACS' => '/integracao/imagiflow',
            ],
            '_layout' => 'erp',
        ]);
    }

    public function save(): void
    {
        $this->requirePermission(true);
        try {
            if (!CryptoService::isConfigured()) {
                throw new \RuntimeException('Criptografia não configurada. Defina APP_KEY ou APP_ENCRYPTION_KEY no ambiente.');
            }
            $usuarioId = (int) Auth::user()->id;
            $baseUrl = rtrim(trim((string) ($_POST['base_url'] ?? 'https://server.voxelpacs.com.br')), '/');
            $code = trim((string) ($_POST['integration_code'] ?? ''));
            $secret = (string) ($_POST['secret'] ?? '');
            $status = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

            if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) !== 'https') {
                throw new \RuntimeException('Informe uma URL HTTPS válida do VOXEL PACS.');
            }
            if (!preg_match('/^[A-Za-z0-9_-]{8,128}$/', $code)) {
                throw new \RuntimeException('O código de integração VOXEL é inválido.');
            }

            $row = $this->integracaoModel->findByNomeAndUsuarioId(self::PROVIDER, $usuarioId);
            $existing = $row ? $this->integracaoModel->getDecodedConfig($row) : [];
            if ($secret === '********') {
                $secretEnc = (string) ($existing['secret_enc'] ?? '');
            } elseif ($secret === '') {
                $secretEnc = (string) ($existing['secret_enc'] ?? '');
            } else {
                if (strlen($secret) < 32) {
                    throw new \RuntimeException('O segredo VOXEL informado é inválido.');
                }
                $secretEnc = (new CryptoService())->encryptString($secret);
            }
            if ($secretEnc === '') {
                throw new \RuntimeException('Informe o segredo gerado no VOXEL PACS.');
            }

            $config = array_merge($existing, [
                'base_url' => $baseUrl,
                'integration_code' => $code,
                'secret_enc' => $secretEnc,
                'last_test_at' => $existing['last_test_at'] ?? null,
            ]);
            $ok = $this->integracaoModel->upsertConfigJson(self::PROVIDER, $usuarioId, [
                'tipo' => 'PACS',
                'status' => $status,
                'config' => $config,
            ]);
            if (!$ok) {
                throw new \RuntimeException('Não foi possível salvar a configuração VOXEL PACS.');
            }

            AuditLogger::log('voxel_pacs_config_saved', [
                'status' => $status,
                'base_url_host' => (string) parse_url($baseUrl, PHP_URL_HOST),
                'integration_code_hash' => hash('sha256', $code),
            ]);
            $this->jsonSuccess('Configuração ImagiFlow / VOXEL PACS salva com sucesso.');
        } catch (\Throwable $exception) {
            $this->logger->error('Falha ao salvar configuração VOXEL PACS', ['error' => $exception->getMessage()]);
            $this->jsonError($exception->getMessage());
        }
    }

    public function test(): void
    {
        $this->requirePermission(true);
        try {
            $usuarioId = (int) Auth::user()->id;
            $crm = preg_replace('/\D+/', '', (string) ($_POST['crm'] ?? '')) ?: '';
            if ($crm === '') {
                throw new \RuntimeException('Informe um CRM para validar a conexão com o VOXEL PACS.');
            }
            $response = VoxelPacsService::forUser($usuarioId)->consultarMedico($crm);
            $data = is_array($response['data'] ?? null) ? $response['data'] : [];

            $this->integracaoModel->updateLastTest(self::PROVIDER);
            AuditLogger::log('voxel_pacs_connection_tested', [
                'found' => (bool) ($data['found'] ?? false),
                'request_id' => (string) ($response['request_id'] ?? ''),
            ]);
            $this->jsonSuccess('Conexão VOXEL PACS validada.', [
                'found' => (bool) ($data['found'] ?? false),
                'matches' => $data['matches'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Falha no teste VOXEL PACS', ['error' => $exception->getMessage()]);
            $this->jsonError($exception->getMessage());
        }
    }

    private function requirePermission(bool $json = false): void
    {
        if (Auth::can('manage_settings')) {
            return;
        }
        if ($json) {
            $this->jsonError('Não autorizado.', 403);
        }
        header('Location: /dashboard?error=unauthorized');
        exit();
    }

    private function jsonSuccess(string $message, array $data = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
        exit();
    }

    private function jsonError(string $message, int $status = 422): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
}
