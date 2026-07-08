<?php

namespace App\Services\AI;

use App\Models\HubIaAgente;
use App\Models\HubIaConector;
use App\Models\HubIaHistorico;
use App\Models\HubIaLog;
use App\Models\HubIaPrompt;

/**
 * Ponto único de entrada de IA do ERP. Todo módulo (RDV, CRM, CNES,
 * Financeiro, Marketing, Estoque...) deve consumir apenas este serviço —
 * nunca chamar a API de um provedor diretamente.
 *
 * Resolve o agente → conector → provider, envia a pergunta, e registra
 * automaticamente histórico (hub_ia_historico) e log técnico (hub_ia_logs).
 */
class AIService
{
    private HubIaConector $conectorModel;
    private HubIaAgente $agenteModel;
    private HubIaPrompt $promptModel;
    private HubIaHistorico $historicoModel;
    private HubIaLog $logModel;

    public function __construct()
    {
        $this->conectorModel  = new HubIaConector();
        $this->agenteModel    = new HubIaAgente();
        $this->promptModel    = new HubIaPrompt();
        $this->historicoModel = new HubIaHistorico();
        $this->logModel       = new HubIaLog();
    }

    /**
     * Envia uma pergunta a um agente específico.
     *
     * @param array $opcoes usuario_id, modulo_origem, contexto_extra (string, ex.: trechos de RAG),
     *                      sql_gerado/sql_linhas_retornadas (preenchidos externamente pelo DatabaseAI,
     *                      quando aplicável, antes de registrar o histórico via registrarHistoricoExtra()).
     */
    public function perguntarAgente(int $agenteId, string $pergunta, array $opcoes = []): array
    {
        $t0 = microtime(true);

        $agente = $this->agenteModel->findById($agenteId);
        if (!$agente || !$agente->ativo) {
            return $this->falhaSemChamada($agenteId, $opcoes, $pergunta, 'Agente não encontrado ou inativo.');
        }
        if (empty($agente->conector_id)) {
            return $this->falhaSemChamada($agenteId, $opcoes, $pergunta, 'Este agente não tem um conector de IA configurado.');
        }

        $conector = $this->conectorModel->findById((int) $agente->conector_id);
        if (!$conector || $conector->status !== 'ativo') {
            return $this->falhaSemChamada($agenteId, $opcoes, $pergunta, 'O conector de IA deste agente está inativo ou não foi encontrado.');
        }

        $apiKey = $this->conectorModel->getApiKeyPlain($conector) ?? '';
        if ($apiKey === '' && $conector->provider !== 'ollama') {
            return $this->falhaSemChamada($agenteId, $opcoes, $pergunta, 'Chave de API não configurada para este conector.');
        }

        $systemPrompt = $this->resolverPromptSistema($agente, $opcoes['contexto_extra'] ?? null);
        $mensagens = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $pergunta],
        ];

        try {
            $provider = AIProviderFactory::fromConector($conector, $apiKey);
        } catch (\Throwable $e) {
            return $this->falhaSemChamada($agenteId, $opcoes, $pergunta, $e->getMessage());
        }

        $resultado = $provider->chat($mensagens, [
            'modelo'      => $conector->modelo,
            'temperatura' => $agente->temperatura ?? $conector->temperatura,
            'max_tokens'  => $conector->max_tokens,
            'timeout'     => $conector->timeout_segundos,
        ]);

        $tempoMs = (int) round((microtime(true) - $t0) * 1000);
        $custo   = CostEstimator::estimar($conector->modelo, $resultado['tokens_prompt'] ?? null, $resultado['tokens_resposta'] ?? null);

        $historicoId = $this->historicoModel->registrar([
            'agente_id'             => $agenteId,
            'usuario_id'            => $opcoes['usuario_id']      ?? null,
            'modulo_origem'         => $opcoes['modulo_origem']   ?? 'hub_ia',
            'pergunta'              => $pergunta,
            'resposta'              => $resultado['texto'],
            'sql_gerado'            => $opcoes['sql_gerado']              ?? null,
            'sql_linhas_retornadas' => $opcoes['sql_linhas_retornadas']   ?? null,
            'provider'              => $conector->provider,
            'modelo'                => $conector->modelo,
            'tokens_prompt'         => $resultado['tokens_prompt'],
            'tokens_resposta'       => $resultado['tokens_resposta'],
            'tokens_total'          => $resultado['tokens_total'],
            'custo_estimado_usd'    => $custo,
            'tempo_ms'              => $tempoMs,
            'ip_address'            => $_SERVER['REMOTE_ADDR'] ?? null,
            'status'                => $resultado['sucesso'] ? 'sucesso' : 'erro',
        ]);

        $this->logModel->registrar([
            'conector_id'  => $conector->id,
            'agente_id'    => $agenteId,
            'historico_id' => $historicoId ?: null,
            'provider'     => $conector->provider,
            'status_http'  => $resultado['status_http'],
            'erro'         => $resultado['erro'],
            'tempo_ms'     => $tempoMs,
            'tokens_total' => $resultado['tokens_total'],
        ]);

        return array_merge($resultado, [
            'agente_nome'  => $agente->nome,
            'historico_id' => $historicoId,
            'custo_estimado_usd' => $custo,
        ]);
    }

    /** Testa a conexão de um conector (botão "Testar Conexão" da tela de Conectores). */
    public function testarConector(object $conector): array
    {
        $apiKey = $this->conectorModel->getApiKeyPlain($conector) ?? '';
        try {
            $provider = AIProviderFactory::fromConector($conector, $apiKey);
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
        return $provider->testar();
    }

    private function resolverPromptSistema(object $agente, ?string $contextoExtra): string
    {
        $base = '';
        if (!empty($agente->prompt_id)) {
            $p = $this->promptModel->findById((int) $agente->prompt_id);
            $base = $p->conteudo ?? '';
        } elseif (!empty($agente->prompt_base)) {
            $base = $agente->prompt_base;
        }
        if ($base === '') {
            $base = "Você é {$agente->nome}, um assistente de IA do ERP InLaudo.";
        }
        if (!empty($agente->personalidade)) {
            $base .= "\n\nPersonalidade: " . $agente->personalidade;
        }
        if (!empty($contextoExtra)) {
            $base .= "\n\nContexto adicional (use apenas se for relevante para a pergunta):\n" . $contextoExtra;
        }
        $idioma = $agente->idioma ?? 'pt-BR';
        $base .= "\n\nResponda sempre no idioma {$idioma}.";
        return $base;
    }

    private function falhaSemChamada(?int $agenteId, array $opcoes, string $pergunta, string $mensagem): array
    {
        $this->historicoModel->registrar([
            'agente_id'     => $agenteId,
            'usuario_id'    => $opcoes['usuario_id']    ?? null,
            'modulo_origem' => $opcoes['modulo_origem'] ?? 'hub_ia',
            'pergunta'      => $pergunta,
            'resposta'      => null,
            'status'        => 'erro',
        ]);
        return [
            'sucesso' => false, 'texto' => null, 'tokens_prompt' => null, 'tokens_resposta' => null,
            'tokens_total' => null, 'tempo_ms' => 0, 'status_http' => null, 'erro' => $mensagem,
        ];
    }
}
