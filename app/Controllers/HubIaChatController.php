<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Models\HubIaAgente;
use App\Models\HubIaConector;
use App\Models\HubIaHistorico;
use App\Services\AI\AIService;
use App\Services\AI\DatabaseAI;
use App\Services\AI\KnowledgeBaseService;

class HubIaChatController extends Controller
{
    private HubIaAgente $agenteModel;
    private HubIaHistorico $historicoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->agenteModel    = new HubIaAgente();
        $this->historicoModel = new HubIaHistorico();
        $this->logger         = new Logger();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        $agentes = array_values(array_filter($this->agenteModel->listar(), fn ($a) => (bool) $a->ativo));

        View::render('hub_ia.chat', [
            'title'      => 'EVA — Assistente do HUB I.A',
            'agentes'    => $agentes,
            'breadcrumb' => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Chat' => '/hub-ia/chat'],
            '_layout'    => 'erp',
        ]);
    }

    /**
     * Envia uma pergunta ao agente selecionado. Se o agente tiver
     * "permite_consulta_banco" habilitado e a pergunta parecer uma consulta
     * de dados, tenta primeiro o caminho NL→SQL (DatabaseAI); caso contrário,
     * ou se o DatabaseAI não retornar dados, cai no chat comum (AIService).
     */
    public function enviar(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('view_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $agenteId = (int) ($_POST['agente_id'] ?? 0);
        $pergunta = trim($_POST['pergunta'] ?? '');

        if ($agenteId <= 0 || $pergunta === '') {
            echo json_encode(['success' => false, 'error' => 'Selecione um agente e digite uma pergunta.']);
            exit();
        }

        $agente = $this->agenteModel->findById($agenteId);
        if (!$agente || !$agente->ativo) {
            echo json_encode(['success' => false, 'error' => 'Agente não encontrado ou inativo.']);
            exit();
        }

        // Caminho NL→SQL: só tentado se o agente tem a permissão explícita
        if (!empty($agente->permite_consulta_banco) && !empty($agente->conector_id)) {
            $conectorModel = new HubIaConector();
            $conector = $conectorModel->findById((int) $agente->conector_id);
            $apiKey = $conector ? ($conectorModel->getApiKeyPlain($conector) ?? '') : '';

            if ($conector && ($apiKey !== '' || $conector->provider === 'ollama')) {
                try {
                    $resultadoSql = (new DatabaseAI())->perguntar($pergunta, $conector, $apiKey);
                } catch (\Throwable $e) {
                    $this->logger->error('[HubIaChatController::enviar] DatabaseAI: ' . $e->getMessage());
                    $resultadoSql = ['sucesso' => false, 'erro' => 'Erro interno ao consultar o banco.'];
                }

                if ($resultadoSql['sucesso']) {
                    $historicoId = $this->historicoModel->registrar([
                        'agente_id'             => $agenteId,
                        'usuario_id'            => Auth::user()->id ?? null,
                        'modulo_origem'         => 'hub_ia_chat',
                        'pergunta'              => $pergunta,
                        'resposta'              => 'Consulta ao banco de dados — ' . $resultadoSql['total_linhas'] . ' linha(s) retornada(s).',
                        'sql_gerado'            => $resultadoSql['sql_gerado'],
                        'sql_linhas_retornadas' => $resultadoSql['total_linhas'],
                        'provider'              => $conector->provider,
                        'modelo'                => $conector->modelo,
                        'tokens_prompt'         => $resultadoSql['tokens_prompt']   ?? null,
                        'tokens_resposta'       => $resultadoSql['tokens_resposta'] ?? null,
                        'tokens_total'          => $resultadoSql['tokens_total']    ?? null,
                        'ip_address'            => $_SERVER['REMOTE_ADDR'] ?? null,
                        'status'                => 'sucesso',
                    ]);

                    echo json_encode([
                        'success'      => true,
                        'tipo'         => 'sql',
                        'sql_gerado'   => $resultadoSql['sql_gerado'],
                        'linhas'       => $resultadoSql['linhas'],
                        'total_linhas' => $resultadoSql['total_linhas'],
                        'historico_id' => $historicoId,
                    ]);
                    exit();
                }
                // Se falhou (fora do escopo, tabela não liberada etc.), cai no chat comum abaixo,
                // devolvendo a pergunta original em linguagem natural.
            }
        }

        $contextoExtra = $this->buscarContextoConhecimento($agente, $pergunta);

        try {
            $resultado = (new AIService())->perguntarAgente($agenteId, $pergunta, [
                'usuario_id'     => Auth::user()->id ?? null,
                'modulo_origem'  => 'hub_ia_chat',
                'contexto_extra' => $contextoExtra,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[HubIaChatController::enviar] ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erro interno ao processar a pergunta.']);
            exit();
        }

        echo json_encode([
            'success'      => $resultado['sucesso'],
            'tipo'         => 'texto',
            'resposta'     => $resultado['texto'] ?? null,
            'erro'         => $resultado['erro']  ?? null,
            'tokens_total' => $resultado['tokens_total'] ?? null,
            'tempo_ms'     => $resultado['tempo_ms'] ?? null,
            'historico_id' => $resultado['historico_id'] ?? null,
        ]);
        exit();
    }

    /**
     * Busca trechos relevantes da Base de Conhecimento (RAG) para injetar como
     * contexto extra no prompt — apenas quando o conector do agente for OpenAI
     * (único provedor com embeddings entre os implementados nesta fase).
     */
    private function buscarContextoConhecimento(object $agente, string $pergunta): ?string
    {
        if (empty($agente->conector_id)) {
            return null;
        }
        $conectorModel = new HubIaConector();
        $conector = $conectorModel->findById((int) $agente->conector_id);
        if (!$conector || $conector->provider !== 'openai') {
            return null;
        }
        $apiKey = $conectorModel->getApiKeyPlain($conector);
        if (!$apiKey) {
            return null;
        }

        try {
            $trechos = (new KnowledgeBaseService())->buscarRelevante($pergunta, $apiKey, 4);
        } catch (\Throwable $e) {
            $this->logger->error('[HubIaChatController::buscarContextoConhecimento] ' . $e->getMessage());
            return null;
        }
        if (empty($trechos)) {
            return null;
        }

        $partes = [];
        foreach ($trechos as $t) {
            if ($t['score'] < 0.7) {
                continue; // muito pouco relevante — não polui o prompt
            }
            $partes[] = "[{$t['documento']}] " . $t['conteudo'];
        }
        return $partes ? implode("\n\n", $partes) : null;
    }
}
