<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RdvOcrLog
{
    private \PDO   $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    public function registrar(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rdv_ocr_logs
                 (viagem_id, usuario_id, arquivo, engine, sucesso, confianca, tempo_ms, erro)
                 VALUES (:viagem_id, :usuario_id, :arquivo, :engine, :sucesso, :confianca, :tempo_ms, :erro)"
            );
            $stmt->execute([
                ':viagem_id'  => (int) $d['viagem_id'],
                ':usuario_id' => $d['usuario_id'] ?? null,
                ':arquivo'    => $d['arquivo']    ?? null,
                ':engine'     => $d['engine'],
                ':sucesso'    => (int) ($d['sucesso'] ?? 0),
                ':confianca'  => $d['confianca'] ?? null,
                ':tempo_ms'   => $d['tempo_ms']  ?? null,
                ':erro'       => $d['erro']      ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[RdvOcrLog::registrar] ' . $e->getMessage());
            return false;
        }
    }

    public function listarPorViagem(int $viagemId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM rdv_ocr_logs WHERE viagem_id = :vid ORDER BY created_at DESC LIMIT 50"
        );
        $stmt->execute([':vid' => $viagemId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
