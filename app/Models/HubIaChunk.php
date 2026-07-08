<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Trechos (chunks) de documentos da Base de Conhecimento, com embedding
 * armazenado como JSON (MySQL 5.7 não tem tipo vetorial nativo — a busca
 * por similaridade de cosseno é calculada em PHP, ver KnowledgeBaseService).
 */
class HubIaChunk extends Model
{
    protected string $table = 'hub_ia_conhecimento_chunks';

    public function create(int $documentoId, int $ordem, string $conteudo, ?array $embedding): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (documento_id, ordem, conteudo, embedding)
                 VALUES (:doc, :ordem, :conteudo, :embedding)"
            );
            $stmt->execute([
                ':doc'       => $documentoId,
                ':ordem'     => $ordem,
                ':conteudo'  => $conteudo,
                ':embedding' => $embedding !== null ? json_encode($embedding) : null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaChunk::create] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna todos os chunks com embedding não nulo (para busca por similaridade).
     * Em bases pequenas/médias (até alguns milhares de chunks) trazer tudo para
     * o PHP e comparar em memória é suficiente; não há índice vetorial no MySQL 5.7.
     */
    public function listarComEmbedding(): array
    {
        $stmt = $this->pdo->query(
            "SELECT c.*, d.nome_original AS documento_nome
             FROM {$this->table} c
             JOIN hub_ia_conhecimento_documentos d ON d.id = c.documento_id
             WHERE c.embedding IS NOT NULL"
        );
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function deletePorDocumento(int $documentoId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE documento_id = ?");
        return $stmt->execute([$documentoId]);
    }
}
