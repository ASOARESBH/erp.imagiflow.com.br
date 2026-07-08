<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;

/**
 * Adapter genérico para qualquer provedor que fale o formato "OpenAI Chat
 * Completions" (POST {base}/chat/completions). Cobre, de fato, OpenAI,
 * DeepSeek, Mistral e Ollama (via sua camada de compatibilidade OpenAI) —
 * os quatro compartilham exatamente o mesmo shape de request/response.
 */
class OpenAICompatibleProvider implements AIProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private string $modeloPadrao
    ) {
    }

    public function chat(array $mensagens, array $opcoes = []): array
    {
        $t0 = microtime(true);
        $modelo = $opcoes['modelo'] ?? $this->modeloPadrao;

        $payload = [
            'model'       => $modelo,
            'messages'    => $mensagens,
            'temperature' => (float) ($opcoes['temperatura'] ?? 0.3),
            'max_tokens'  => (int) ($opcoes['max_tokens'] ?? 2000),
        ];

        $headers = ['Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $ch = curl_init(rtrim($this->baseUrl, '/') . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => (int) ($opcoes['timeout'] ?? 30),
        ]);
        $resp       = curl_exec($ch);
        $statusHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr    = curl_error($ch);
        curl_close($ch);
        $tempoMs = (int) round((microtime(true) - $t0) * 1000);

        if ($curlErr) {
            return $this->resultado(false, null, null, null, null, $tempoMs, null, "Erro de conexão: {$curlErr}");
        }

        $data = json_decode((string) $resp, true);
        if ($statusHttp < 200 || $statusHttp >= 300 || !is_array($data)) {
            $msg = $data['error']['message'] ?? "HTTP {$statusHttp}";
            return $this->resultado(false, null, null, null, null, $tempoMs, $statusHttp, $msg);
        }

        $texto = $data['choices'][0]['message']['content'] ?? null;
        $usage = $data['usage'] ?? [];

        return $this->resultado(
            true,
            $texto,
            $usage['prompt_tokens']     ?? null,
            $usage['completion_tokens'] ?? null,
            $usage['total_tokens']      ?? null,
            $tempoMs,
            $statusHttp,
            null
        );
    }

    public function testar(): array
    {
        $r = $this->chat([['role' => 'user', 'content' => 'Responda apenas "ok".']], ['max_tokens' => 10]);
        return [
            'sucesso'  => $r['sucesso'],
            'mensagem' => $r['sucesso'] ? 'Conexão bem-sucedida.' : ($r['erro'] ?? 'Falha desconhecida.'),
        ];
    }

    private function resultado(bool $sucesso, ?string $texto, ?int $tp, ?int $tr, ?int $tt, int $tempoMs, ?int $statusHttp, ?string $erro): array
    {
        return [
            'sucesso'         => $sucesso,
            'texto'           => $texto,
            'tokens_prompt'   => $tp,
            'tokens_resposta' => $tr,
            'tokens_total'    => $tt,
            'tempo_ms'        => $tempoMs,
            'status_http'     => $statusHttp,
            'erro'            => $erro,
        ];
    }
}
