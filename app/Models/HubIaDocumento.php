<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class HubIaDocumento extends Model
{
    protected string $table = 'hub_ia_conhecimento_documentos';

    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (usuario_id, nome_original, file_path, tipo, categoria, tamanho_bytes, status)
                 VALUES (:usuario_id, :nome, :path, :tipo, :categoria, :tamanho, :status)"
            );
            $stmt->execute([
                ':usuario_id' => $d['usuario_id'],
                ':nome'       => $d['nome_original'],
                ':path'       => $d['file_path'],
                ':tipo'       => $d['tipo'],
                ':categoria'  => $d['categoria'] ?? null,
                ':tamanho'    => $d['tamanho_bytes'] ?? 0,
                ':status'     => $d['status'] ?? 'processando',
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaDocumento::create] ' . $e->getMessage());
            return false;
        }
    }

    public function atualizarStatus(int $id, string $status, ?string $erro = null, int $totalChunks = 0): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET status = :status, mensagem_erro = :erro, total_chunks = :chunks WHERE id = :id"
        );
        return $stmt->execute([':status' => $status, ':erro' => $erro, ':chunks' => $totalChunks, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $this->pdo->prepare("DELETE FROM hub_ia_conhecimento_chunks WHERE documento_id = ?")->execute([$id]);
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
