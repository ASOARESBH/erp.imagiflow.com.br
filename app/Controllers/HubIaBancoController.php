<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\View;
use App\Models\HubIaBancoConfig;
use App\Models\HubIaConector;
use App\Services\AI\DatabaseAI;

class HubIaBancoController extends Controller
{
    private HubIaBancoConfig $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model  = new HubIaBancoConfig();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        View::render('hub_ia.banco', [
            'title'          => 'HUB I.A — Banco de Dados',
            'config'         => $this->model->get(),
            'tabelasLiberadas' => $this->model->getTabelasLiberadas(),
            'todasTabelas'   => $this->listarTabelasDisponiveis(),
            'conectores'     => (new HubIaConector())->listar(),
            'breadcrumb'     => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Banco de Dados' => '/hub-ia/banco'],
            '_layout'        => 'erp',
        ]);
    }

    public function salvar(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $tabelas = $_POST['tabelas'] ?? [];
        if (!is_array($tabelas)) {
            $tabelas = [];
        }
        $ativo = !empty($_POST['ativo']);

        $ok = $this->model->salvar($tabelas, $ativo);
        if ($ok) {
            AuditLogger::log('hub_ia_banco_config_salva', ['tabelas' => count($tabelas), 'ativo' => $ativo]);
        }
        echo json_encode(['success' => $ok]);
        exit();
    }

    /**
     * Testa o pipeline NL→SQL de ponta a ponta com uma pergunta de exemplo.
     */
    public function testarConsulta(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $pergunta   = trim($_POST['pergunta']    ?? '');
        $conectorId = (int) ($_POST['conector_id'] ?? 0);
        if ($pergunta === '' || $conectorId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Informe a pergunta e selecione um conector.']);
            exit();
        }

        $conectorModel = new HubIaConector();
        $conector = $conectorModel->findById($conectorId);
        if (!$conector) {
            echo json_encode(['success' => false, 'error' => 'Conector não encontrado.']);
            exit();
        }
        $apiKey = $conectorModel->getApiKeyPlain($conector) ?? '';

        try {
            $resultado = (new DatabaseAI())->perguntar($pergunta, $conector, $apiKey);
        } catch (\Throwable $e) {
            $this->logger->error('[HubIaBancoController::testarConsulta] ' . $e->getMessage());
            $resultado = ['sucesso' => false, 'erro' => 'Erro interno ao processar a consulta.'];
        }

        AuditLogger::log('hub_ia_banco_teste_consulta', ['sucesso' => $resultado['sucesso']]);
        echo json_encode(array_merge(['success' => $resultado['sucesso']], $resultado));
        exit();
    }

    /**
     * Lista as tabelas/views do banco atual (para o multi-select da allowlist).
     */
    private function listarTabelasDisponiveis(): array
    {
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->query(
                "SELECT TABLE_NAME FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME ASC"
            );
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'TABLE_NAME');
        } catch (\Throwable $e) {
            $this->logger->error('[HubIaBancoController::listarTabelasDisponiveis] ' . $e->getMessage());
            return [];
        }
    }
}
