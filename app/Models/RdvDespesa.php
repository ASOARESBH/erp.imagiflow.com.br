<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RdvDespesa
{
    private \PDO   $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    // =========================================================================
    // CREATE
    // =========================================================================
    public function create(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rdv_despesas
                 (viagem_id, categoria_id, forma_pagamento_id, descricao, valor,
                  data_documento, numero_documento, fornecedor, cnpj_fornecedor,
                  cidade, hora_documento, arquivo, ocr_json, ocr_status,
                  fora_periodo, log_fora_periodo, tipo)
                 VALUES
                 (:viagem_id, :categoria_id, :forma_pagamento_id, :descricao, :valor,
                  :data_documento, :numero_documento, :fornecedor, :cnpj_fornecedor,
                  :cidade, :hora_documento, :arquivo, :ocr_json, :ocr_status,
                  :fora_periodo, :log_fora_periodo, :tipo)"
            );
            $stmt->execute([
                ':viagem_id'         => (int)$d['viagem_id'],
                ':categoria_id'      => !empty($d['categoria_id'])      ? (int)$d['categoria_id']      : null,
                ':forma_pagamento_id'=> !empty($d['forma_pagamento_id'])? (int)$d['forma_pagamento_id']: null,
                ':descricao'         => trim($d['descricao'] ?? ''),
                ':valor'             => (float)($d['valor'] ?? 0),
                ':data_documento'    => $d['data_documento']    ?? null,
                ':numero_documento'  => $d['numero_documento']  ?? null,
                ':fornecedor'        => $d['fornecedor']        ?? null,
                ':cnpj_fornecedor'   => $d['cnpj_fornecedor']   ?? null,
                ':cidade'            => $d['cidade']            ?? null,
                ':hora_documento'    => $d['hora_documento']    ?? null,
                ':arquivo'           => $d['arquivo']           ?? null,
                ':ocr_json'          => $d['ocr_json']          ?? null,
                ':ocr_status'        => $d['ocr_status']        ?? null,
                ':fora_periodo'      => (int)($d['fora_periodo']  ?? 0),
                ':log_fora_periodo'  => $d['log_fora_periodo']  ?? null,
                ':tipo'              => $d['tipo']              ?? 'simples',
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[RdvDespesa::create] ' . $e->getMessage(), $d);
            return false;
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update(int $id, array $d): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE rdv_despesas SET
                 categoria_id       = :categoria_id,
                 forma_pagamento_id = :forma_pagamento_id,
                 descricao          = :descricao,
                 valor              = :valor,
                 data_documento     = :data_documento,
                 numero_documento   = :numero_documento,
                 fornecedor         = :fornecedor,
                 cnpj_fornecedor    = :cnpj_fornecedor,
                 cidade             = :cidade,
                 hora_documento     = :hora_documento
                 WHERE id = :id AND viagem_id = :viagem_id"
            )->execute([
                ':categoria_id'       => !empty($d['categoria_id'])       ? (int)$d['categoria_id']       : null,
                ':forma_pagamento_id' => !empty($d['forma_pagamento_id']) ? (int)$d['forma_pagamento_id'] : null,
                ':descricao'          => trim($d['descricao'] ?? ''),
                ':valor'              => (float)($d['valor'] ?? 0),
                ':data_documento'     => $d['data_documento']    ?? null,
                ':numero_documento'   => $d['numero_documento']  ?? null,
                ':fornecedor'         => $d['fornecedor']        ?? null,
                ':cnpj_fornecedor'    => $d['cnpj_fornecedor']   ?? null,
                ':cidade'             => $d['cidade']            ?? null,
                ':hora_documento'     => $d['hora_documento']    ?? null,
                ':id'                 => $id,
                ':viagem_id'          => (int)$d['viagem_id'],
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvDespesa::update] ' . $e->getMessage());
            return false;
        }
    }

    /** Atualiza somente o caminho do comprovante da despesa. */
    public function updateArquivo(int $id, int $viagemId, string $arquivo): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE rdv_despesas SET arquivo = :arquivo WHERE id = :id AND viagem_id = :viagem_id"
            );
            return $stmt->execute([
                ':arquivo' => $arquivo,
                ':id' => $id,
                ':viagem_id' => $viagemId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvDespesa::updateArquivo] ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    // =========================================================================
    // UPDATE OCR
    // =========================================================================
    public function updateOcr(int $id, string $ocrJson, string $status): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE rdv_despesas SET ocr_json = :j, ocr_status = :s WHERE id = :id"
            )->execute([':j' => $ocrJson, ':s' => $status, ':id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvDespesa::updateOcr] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // DELETE
    // =========================================================================
    public function delete(int $id, int $viagemId): bool
    {
        try {
            $this->pdo->prepare(
                "DELETE FROM rdv_despesas WHERE id = :id AND viagem_id = :vid"
            )->execute([':id' => $id, ':vid' => $viagemId]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvDespesa::delete] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // FIND BY ID
    // =========================================================================
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, c.nome AS categoria_nome, c.icone AS categoria_icone, c.cor AS categoria_cor,
                    f.nome AS forma_nome
             FROM rdv_despesas d
             LEFT JOIN rdv_categorias c ON c.id = d.categoria_id
             LEFT JOIN rdv_formas_pagamento f ON f.id = d.forma_pagamento_id
             WHERE d.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: false;
    }

    // =========================================================================
    // LISTAR POR VIAGEM
    // =========================================================================
    public function listarPorViagem(int $viagemId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, c.nome AS categoria_nome, c.icone AS categoria_icone, c.cor AS categoria_cor,
                    f.nome AS forma_nome
             FROM rdv_despesas d
             LEFT JOIN rdv_categorias c ON c.id = d.categoria_id
             LEFT JOIN rdv_formas_pagamento f ON f.id = d.forma_pagamento_id
             WHERE d.viagem_id = :vid
             ORDER BY d.data_documento ASC, d.created_at ASC"
        );
        $stmt->execute([':vid' => $viagemId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // RESUMO POR CATEGORIA (para cards e gráfico)
    // =========================================================================
    public function resumoPorCategoria(int $viagemId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.nome AS categoria, c.icone, c.cor,
                    SUM(d.valor) AS total,
                    COUNT(d.id) AS quantidade
             FROM rdv_despesas d
             LEFT JOIN rdv_categorias c ON c.id = d.categoria_id
             WHERE d.viagem_id = :vid
             GROUP BY d.categoria_id, c.nome, c.icone, c.cor
             ORDER BY total DESC"
        );
        $stmt->execute([':vid' => $viagemId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // RESUMO POR FORMA DE PAGAMENTO (para gráfico)
    // =========================================================================
    public function resumoPorForma(int $viagemId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.nome AS forma, SUM(d.valor) AS total, COUNT(d.id) AS quantidade
             FROM rdv_despesas d
             LEFT JOIN rdv_formas_pagamento f ON f.id = d.forma_pagamento_id
             WHERE d.viagem_id = :vid
             GROUP BY d.forma_pagamento_id, f.nome
             ORDER BY total DESC"
        );
        $stmt->execute([':vid' => $viagemId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // RESUMO POR DIA (para gráfico de linha)
    // =========================================================================
    public function resumoPorDia(int $viagemId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(data_documento, '%d/%m') AS dia,
                    SUM(valor) AS total
             FROM rdv_despesas
             WHERE viagem_id = :vid AND data_documento IS NOT NULL
             GROUP BY data_documento
             ORDER BY data_documento ASC"
        );
        $stmt->execute([':vid' => $viagemId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
