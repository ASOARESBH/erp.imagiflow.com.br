<?php

namespace App\Models;

use App\Core\Model;
use App\Services\CryptoService;
use PDO;

/**
 * Conectores de IA (provedores configurados: OpenAI, Claude, Gemini, DeepSeek, Mistral, Ollama).
 * A chave de API é sempre criptografada em repouso via CryptoService (AES-256-GCM) —
 * nunca fica em texto puro no banco.
 */
class HubIaConector extends Model
{
    protected string $table = 'hub_ia_conectores';

    public const PROVIDERS = ['openai', 'claude', 'gemini', 'deepseek', 'mistral', 'ollama'];

    /** Sentinela usado pela tela para indicar "manter a chave já cadastrada" */
    public const MASK = '********';

    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY provider ASC, nome ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findAtivoPorProvider(string $provider): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE provider = :p AND status = 'ativo' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':p' => $provider]);
        return $stmt->fetch();
    }

    public function create(int $usuarioId, array $d): int|false
    {
        try {
            $apiKeyEnc = null;
            if (!empty($d['api_key']) && $d['api_key'] !== self::MASK) {
                $apiKeyEnc = (new CryptoService())->encryptString($d['api_key']);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, nome, provider, api_key_enc, endpoint, modelo, temperatura, max_tokens, timeout_segundos, status)
                 VALUES (:usuario_id, :nome, :provider, :api_key_enc, :endpoint, :modelo, :temperatura, :max_tokens, :timeout, :status)"
            );
            $stmt->execute([
                ':usuario_id'  => $usuarioId,
                ':nome'        => trim($d['nome']),
                ':provider'    => $d['provider'],
                ':api_key_enc' => $apiKeyEnc,
                ':endpoint'    => $d['endpoint']    ?? null,
                ':modelo'      => $d['modelo']      ?? '',
                ':temperatura' => (float) ($d['temperatura'] ?? 0.3),
                ':max_tokens'  => (int) ($d['max_tokens'] ?? 2000),
                ':timeout'     => (int) ($d['timeout_segundos'] ?? 30),
                ':status'      => $d['status'] ?? 'ativo',
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaConector::create] ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        try {
            $setApiKey = '';
            $params = [
                ':id'          => $id,
                ':nome'        => trim($d['nome']),
                ':provider'    => $d['provider'],
                ':endpoint'    => $d['endpoint']    ?? null,
                ':modelo'      => $d['modelo']      ?? '',
                ':temperatura' => (float) ($d['temperatura'] ?? 0.3),
                ':max_tokens'  => (int) ($d['max_tokens'] ?? 2000),
                ':timeout'     => (int) ($d['timeout_segundos'] ?? 30),
                ':status'      => $d['status'] ?? 'ativo',
            ];

            // Só re-criptografa/atualiza a chave se um valor novo (não a máscara) foi enviado
            if (!empty($d['api_key']) && $d['api_key'] !== self::MASK) {
                $setApiKey = ', api_key_enc = :api_key_enc';
                $params[':api_key_enc'] = (new CryptoService())->encryptString($d['api_key']);
            }

            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    nome = :nome, provider = :provider, endpoint = :endpoint, modelo = :modelo,
                    temperatura = :temperatura, max_tokens = :max_tokens, timeout_segundos = :timeout,
                    status = :status{$setApiKey}
                 WHERE id = :id"
            );
            return $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log('[HubIaConector::update] ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getApiKeyPlain(object $conector): ?string
    {
        if (empty($conector->api_key_enc)) {
            return null;
        }
        try {
            return (new CryptoService())->decryptString($conector->api_key_enc);
        } catch (\Throwable $e) {
            error_log('[HubIaConector::getApiKeyPlain] ' . $e->getMessage());
            return null;
        }
    }

    public function updateTesteResultado(int $id, bool $ok, string $mensagem): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET ultimo_teste_em = NOW(), ultimo_teste_status = :status, ultimo_teste_mensagem = :msg
             WHERE id = :id"
        );
        return $stmt->execute([
            ':status' => $ok ? 'ok' : 'erro',
            ':msg'    => mb_substr($mensagem, 0, 500),
            ':id'     => $id,
        ]);
    }
}
