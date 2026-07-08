<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;

/**
 * Stub — não implementado nesta fase (fundação + 2 provedores reais).
 * A interface já está pronta; para ativar, implementar seguindo o mesmo
 * padrão de ClaudeProvider (a Google Generative Language API tem outro
 * shape de request/response, incompatível com o formato OpenAI).
 */
class GeminiProvider implements AIProviderInterface
{
    private const MSG = 'Provedor Gemini ainda não implementado nesta fase. '
        . 'A interface AIProviderInterface já está pronta — implemente GeminiProvider '
        . 'seguindo o padrão de ClaudeProvider/OpenAICompatibleProvider para ativá-lo.';

    public function chat(array $mensagens, array $opcoes = []): array
    {
        return [
            'sucesso' => false, 'texto' => null, 'tokens_prompt' => null, 'tokens_resposta' => null,
            'tokens_total' => null, 'tempo_ms' => 0, 'status_http' => null, 'erro' => self::MSG,
        ];
    }

    public function testar(): array
    {
        return ['sucesso' => false, 'mensagem' => self::MSG];
    }
}
