<?php

namespace App\Services\AI;

/**
 * Contrato único que todo provedor de IA deve implementar. Nenhum módulo do
 * ERP deve chamar a API de um provedor diretamente — sempre através de
 * AIService, que resolve o provider correto via AIProviderFactory.
 */
interface AIProviderInterface
{
    /**
     * @param array $mensagens Lista de ['role' => 'system'|'user'|'assistant', 'content' => string]
     * @param array $opcoes    modelo, temperatura, max_tokens, timeout
     * @return array{sucesso:bool, texto:?string, tokens_prompt:?int, tokens_resposta:?int,
     *               tokens_total:?int, tempo_ms:int, status_http:?int, erro:?string}
     */
    public function chat(array $mensagens, array $opcoes = []): array;

    /**
     * @return array{sucesso:bool, mensagem:string}
     */
    public function testar(): array;
}
