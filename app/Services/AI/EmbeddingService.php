<?php

namespace App\Services\AI;

/**
 * Geração de embeddings via OpenAI (único provedor, entre os implementados
 * nesta fase, com endpoint dedicado de embeddings) + similaridade de cosseno
 * calculada em PHP — MySQL 5.7 não tem tipo/índice vetorial nativo.
 */
class EmbeddingService
{
    public static function gerar(string $texto, string $apiKey, string $modelo = 'text-embedding-3-small'): ?array
    {
        if (trim($texto) === '' || $apiKey === '') {
            return null;
        }

        $ch = curl_init('https://api.openai.com/v1/embeddings');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_POSTFIELDS     => json_encode(['model' => $modelo, 'input' => $texto]),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err || $status < 200 || $status >= 300) {
            return null;
        }

        $data = json_decode((string) $resp, true);
        $embedding = $data['data'][0]['embedding'] ?? null;
        return is_array($embedding) ? $embedding : null;
    }

    public static function similaridadeCosseno(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }
        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
