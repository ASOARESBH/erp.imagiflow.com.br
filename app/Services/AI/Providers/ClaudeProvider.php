<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;

/**
 * Adapter para a Anthropic Messages API (formato diferente do OpenAI:
 * o prompt de sistema vai em um campo `system` separado, não como mensagem).
 */
class ClaudeProvider implements AIProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $modeloPadrao,
        private string $baseUrl = 'https://api.anthropic.com/v1'
    ) {
    }

    public function chat(array $mensagens, array $opcoes = []): array
    {
        $t0 = microtime(true);
        $modelo = $opcoes['modelo'] ?? $this->modeloPadrao;

        $system = null;
        $msgs   = [];
        foreach ($mensagens as $m) {
            if (($m['role'] ?? '') === 'system') {
                $system = ($system !== null ? $system . "\n\n" : '') . $m['content'];
                continue;
            }
            $msgs[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $payload = [
            'model'       => $modelo,
            'max_tokens'  => (int) ($opcoes['max_tokens'] ?? 2000),
            'temperature' => (float) ($opcoes['temperatura'] ?? 0.3),
            'messages'    => $msgs,
        ];
        if ($system !== null) {
            $payload['system'] = $system;
        }

        $ch = curl_init(rtrim($this->baseUrl, '/') . '/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => (int) ($opcoes['timeout'] ?? 30),
        ]);
        $resp       = curl_exec($ch);
        $statusHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr    = curl_error($ch);
        curl_close($ch);
        $tempoMs = (int) round((microtime(true) - $t0) * 1000);

        if ($curlErr) {
            return $this->resultado(false, null, null, null, $tempoMs, null, "Erro de conexão: {$curlErr}");
        }

        $data = json_decode((string) $resp, true);
        if ($statusHttp < 200 || $statusHttp >= 300 || !is_array($data)) {
            $msg = $data['error']['message'] ?? "HTTP {$statusHttp}";
            return $this->resultado(false, null, null, null, $tempoMs, $statusHttp, $msg);
        }

        $texto = $data['content'][0]['text'] ?? null;
        $usage = $data['usage'] ?? [];
        $tp    = $usage['input_tokens']  ?? null;
        $tr    = $usage['output_tokens'] ?? null;

        return $this->resultado(true, $texto, $tp, $tr, $tempoMs, $statusHttp, null);
    }

    public function testar(): array
    {
        $r = $this->chat([['role' => 'user', 'content' => 'Responda apenas "ok".']], ['max_tokens' => 10]);
        return [
            'sucesso'  => $r['sucesso'],
            'mensagem' => $r['sucesso'] ? 'Conexão bem-sucedida.' : ($r['erro'] ?? 'Falha desconhecida.'),
        ];
    }

    private function resultado(bool $sucesso, ?string $texto, ?int $tp, ?int $tr, int $tempoMs, ?int $statusHttp, ?string $erro): array
    {
        return [
            'sucesso'         => $sucesso,
            'texto'           => $texto,
            'tokens_prompt'   => $tp,
            'tokens_resposta' => $tr,
            'tokens_total'    => ($tp !== null && $tr !== null) ? $tp + $tr : null,
            'tempo_ms'        => $tempoMs,
            'status_http'     => $statusHttp,
            'erro'            => $erro,
        ];
    }
}
