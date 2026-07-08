<?php

namespace App\Services\AI;

/**
 * Estimativa de custo em USD por chamada, a partir de uma tabela de preços
 * aproximada por modelo (USD por 1.000 tokens). Valores de referência —
 * conferir/atualizar periodicamente na página de preços de cada provedor.
 * Modelos ausentes da tabela (ex.: rodando via Ollama local) retornam null
 * (sem custo monetário associado).
 */
class CostEstimator
{
    private const PRECOS_POR_1K = [
        // OpenAI
        'gpt-4o'                     => ['in' => 0.0025,  'out' => 0.01],
        'gpt-4o-mini'                 => ['in' => 0.00015, 'out' => 0.0006],
        'gpt-4-turbo'                 => ['in' => 0.01,    'out' => 0.03],
        // Anthropic Claude
        'claude-3-5-sonnet-20241022' => ['in' => 0.003,   'out' => 0.015],
        'claude-3-5-haiku-20241022'  => ['in' => 0.0008,  'out' => 0.004],
        'claude-3-opus-20240229'     => ['in' => 0.015,   'out' => 0.075],
        // DeepSeek
        'deepseek-chat'               => ['in' => 0.00027, 'out' => 0.0011],
        // Mistral
        'mistral-small-latest'       => ['in' => 0.001,   'out' => 0.003],
        'mistral-large-latest'       => ['in' => 0.002,   'out' => 0.006],
    ];

    public static function estimar(string $modelo, ?int $tokensPrompt, ?int $tokensResposta): ?float
    {
        if ($tokensPrompt === null && $tokensResposta === null) {
            return null;
        }
        $preco = self::PRECOS_POR_1K[$modelo] ?? null;
        if (!$preco) {
            return null;
        }
        $custo = (($tokensPrompt ?? 0) / 1000 * $preco['in']) + (($tokensResposta ?? 0) / 1000 * $preco['out']);
        return round($custo, 6);
    }
}
