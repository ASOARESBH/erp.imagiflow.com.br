<?php

namespace App\Services\AI;

use App\Services\AI\Providers\ClaudeProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAICompatibleProvider;

/**
 * Resolve o adapter correto a partir de um registro de hub_ia_conectores.
 * Ponto único de extensão: para adicionar um novo provedor, criar a classe
 * em Providers/ implementando AIProviderInterface e adicionar um `case` aqui.
 */
class AIProviderFactory
{
    public static function fromConector(object $conector, string $apiKeyPlain): AIProviderInterface
    {
        $endpoint = trim((string) ($conector->endpoint ?? ''));
        $modelo   = (string) ($conector->modelo ?? '');

        return match ($conector->provider) {
            'openai'   => new OpenAICompatibleProvider($apiKeyPlain, $endpoint ?: 'https://api.openai.com/v1', $modelo ?: 'gpt-4o-mini'),
            'deepseek' => new OpenAICompatibleProvider($apiKeyPlain, $endpoint ?: 'https://api.deepseek.com/v1', $modelo ?: 'deepseek-chat'),
            'mistral'  => new OpenAICompatibleProvider($apiKeyPlain, $endpoint ?: 'https://api.mistral.ai/v1', $modelo ?: 'mistral-small-latest'),
            'ollama'   => new OpenAICompatibleProvider($apiKeyPlain, $endpoint ?: 'http://localhost:11434/v1', $modelo ?: 'llama3'),
            'claude'   => new ClaudeProvider($apiKeyPlain, $modelo ?: 'claude-3-5-sonnet-20241022', $endpoint ?: 'https://api.anthropic.com/v1'),
            'gemini'   => new GeminiProvider(),
            default    => throw new \InvalidArgumentException("Provider de IA desconhecido: {$conector->provider}"),
        };
    }
}
