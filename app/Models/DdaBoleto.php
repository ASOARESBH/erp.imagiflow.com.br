<?php
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Model para boletos DDA recebidos via Asaas.
 * Tabela: dda_boletos
 */
class DdaBoleto extends Model
{
    protected string $table = 'dda_boletos';

    // -------------------------------------------------------------------------
    // Constantes de status
    // -------------------------------------------------------------------------
    const STATUS_PENDENTE      = 'pendente';
    const STATUS_IMPORTADO     = 'importado';
    const STATUS_PAGO_ASAAS    = 'pago_asaas';
    const STATUS_PAGO_INLAUDO  = 'pago_inlaudo';
    const STATUS_CANCELADO     = 'cancelado';
    const STATUS_IGNORADO      = 'ignorado';

    const ASAAS_STATUS_LABELS = [
        'PENDING'          => 'Pendente',
        'BANK_PROCESSING'  => 'Em processamento',
        'PAID'             => 'Pago',
        'CANCELLED'        => 'Cancelado',
        'FAILED'           => 'Falhou',
        'SCHEDULED'        => 'Agendado',
    ];

    // -------------------------------------------------------------------------
    // Leitura
    // -------------------------------------------------------------------------

    public function findById(int $id): object|false
    {
        $sql = "SELECT d.*, cp.descricao AS conta_pagar_descricao
                FROM {$this->table} d
                LEFT JOIN contas_pagar cp ON cp.id = d.conta_pagar_id
                WHERE d.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByAsaasId(string $asaasId, int $usuarioId): object|false
    {
        $sql = "SELECT * FROM {$this->table} WHERE asaas_id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asaasId, $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Lista boletos DDA de um usuário com filtros opcionais.
     */
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['d.usuario_id = :usuario_id'];
        $params = [':usuario_id' => $usuarioId];

        // Filtro de status interno
        $statusInterno = $filtros['status_interno'] ?? '';
        if ($statusInterno !== '') {
            $where[]                    = 'd.status_interno = :status_interno';
            $params[':status_interno']  = $statusInterno;
        }

        // Filtro de pesquisa (beneficiário ou CPF/CNPJ)
        $q = trim($filtros['pesquisa'] ?? '');
        if ($q !== '') {
            $where[]    = '(d.beneficiario_nome LIKE :q OR d.beneficiario_cpf_cnpj LIKE :q OR d.descricao LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        // Filtro de vencimento
        if (!empty($filtros['venc_de'])) {
            $where[]            = 'd.data_vencimento >= :venc_de';
            $params[':venc_de'] = $filtros['venc_de'];
        }
        if (!empty($filtros['venc_ate'])) {
            $where[]             = 'd.data_vencimento <= :venc_ate';
            $params[':venc_ate'] = $filtros['venc_ate'];
        }

        $sql = "SELECT d.*, cp.descricao AS conta_pagar_descricao
                FROM {$this->table} d
                LEFT JOIN contas_pagar cp ON cp.id = d.conta_pagar_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY d.data_vencimento ASC, d.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Conta boletos por status para o dashboard.
     */
    public function countByStatus(int $usuarioId): array
    {
        $sql = "SELECT status_interno, COUNT(*) as total, SUM(valor_final) as soma
                FROM {$this->table}
                WHERE usuario_id = ?
                GROUP BY status_interno";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        foreach ($rows as $row) {
            $result[$row->status_interno] = [
                'total' => (int)$row->total,
                'soma'  => (float)$row->soma,
            ];
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Escrita
    // -------------------------------------------------------------------------

    /**
     * Insere ou atualiza um boleto DDA (upsert por asaas_id + usuario_id).
     */
    public function upsert(array $dados): int
    {
        $existing = $this->findByAsaasId($dados['asaas_id'], $dados['usuario_id']);
        if ($existing) {
            // Atualiza apenas campos do Asaas (não sobrescreve status_interno se já foi importado/pago)
            $updateFields = [
                'asaas_status'          => $dados['asaas_status'] ?? $existing->asaas_status,
                'valor'                 => $dados['valor'] ?? $existing->valor,
                'valor_desconto'        => $dados['valor_desconto'] ?? $existing->valor_desconto,
                'valor_juros'           => $dados['valor_juros'] ?? $existing->valor_juros,
                'valor_multa'           => $dados['valor_multa'] ?? $existing->valor_multa,
                'valor_final'           => $dados['valor_final'] ?? $existing->valor_final,
                'data_vencimento'       => $dados['data_vencimento'] ?? $existing->data_vencimento,
                'data_limite_pagamento' => $dados['data_limite_pagamento'] ?? $existing->data_limite_pagamento,
                'beneficiario_nome'     => $dados['beneficiario_nome'] ?? $existing->beneficiario_nome,
                'beneficiario_cpf_cnpj' => $dados['beneficiario_cpf_cnpj'] ?? $existing->beneficiario_cpf_cnpj,
                'beneficiario_banco'    => $dados['beneficiario_banco'] ?? $existing->beneficiario_banco,
                'codigo_barras'         => $dados['codigo_barras'] ?? $existing->codigo_barras,
                'linha_digitavel'       => $dados['linha_digitavel'] ?? $existing->linha_digitavel,
                'descricao'             => $dados['descricao'] ?? $existing->descricao,
                'asaas_raw'             => $dados['asaas_raw'] ?? $existing->asaas_raw,
            ];
            // Se Asaas marcou como PAID e status interno ainda é pendente, atualiza
            if (($dados['asaas_status'] ?? '') === 'PAID'
                && in_array($existing->status_interno, [self::STATUS_PENDENTE, self::STATUS_IMPORTADO])) {
                $updateFields['status_interno'] = self::STATUS_PAGO_ASAAS;
                $updateFields['pago_em']        = date('Y-m-d H:i:s');
                $updateFields['pago_por']       = 'asaas';
            }
            $this->update((int)$existing->id, $updateFields);
            return (int)$existing->id;
        }

        // Insert novo
        $sql = "INSERT INTO {$this->table}
                    (usuario_id, asaas_id, asaas_status, beneficiario_nome, beneficiario_cpf_cnpj,
                     beneficiario_banco, codigo_barras, linha_digitavel, valor, valor_desconto,
                     valor_juros, valor_multa, valor_final, data_vencimento, data_limite_pagamento,
                     descricao, status_interno, asaas_raw, created_at, updated_at)
                VALUES
                    (:usuario_id, :asaas_id, :asaas_status, :beneficiario_nome, :beneficiario_cpf_cnpj,
                     :beneficiario_banco, :codigo_barras, :linha_digitavel, :valor, :valor_desconto,
                     :valor_juros, :valor_multa, :valor_final, :data_vencimento, :data_limite_pagamento,
                     :descricao, :status_interno, :asaas_raw, NOW(), NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id'            => $dados['usuario_id'],
            ':asaas_id'              => $dados['asaas_id'],
            ':asaas_status'          => $dados['asaas_status'] ?? 'PENDING',
            ':beneficiario_nome'     => $dados['beneficiario_nome'] ?? null,
            ':beneficiario_cpf_cnpj' => $dados['beneficiario_cpf_cnpj'] ?? null,
            ':beneficiario_banco'    => $dados['beneficiario_banco'] ?? null,
            ':codigo_barras'         => $dados['codigo_barras'] ?? null,
            ':linha_digitavel'       => $dados['linha_digitavel'] ?? null,
            ':valor'                 => $dados['valor'] ?? 0,
            ':valor_desconto'        => $dados['valor_desconto'] ?? 0,
            ':valor_juros'           => $dados['valor_juros'] ?? 0,
            ':valor_multa'           => $dados['valor_multa'] ?? 0,
            ':valor_final'           => $dados['valor_final'] ?? $dados['valor'] ?? 0,
            ':data_vencimento'       => $dados['data_vencimento'],
            ':data_limite_pagamento' => $dados['data_limite_pagamento'] ?? null,
            ':descricao'             => $dados['descricao'] ?? null,
            ':status_interno'        => $dados['status_interno'] ?? self::STATUS_PENDENTE,
            ':asaas_raw'             => $dados['asaas_raw'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Marca boleto como importado para contas_pagar.
     */
    public function marcarImportado(int $id, int $contaPagarId): bool
    {
        $sql = "UPDATE {$this->table}
                SET status_interno = :status, conta_pagar_id = :cp_id, importado_em = NOW(), updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => self::STATUS_IMPORTADO,
            ':cp_id'  => $contaPagarId,
            ':id'     => $id,
        ]);
    }

    /**
     * Confirma pagamento (por Asaas ou InLaudo).
     */
    public function confirmarPagamento(int $id, string $pagoPor, ?string $dataPagamento = null): bool
    {
        $statusInterno = ($pagoPor === 'asaas') ? self::STATUS_PAGO_ASAAS : self::STATUS_PAGO_INLAUDO;
        $sql = "UPDATE {$this->table}
                SET status_interno = :status, pago_por = :pago_por,
                    pago_em = NOW(), data_pagamento = :data_pag, updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status'   => $statusInterno,
            ':pago_por' => $pagoPor,
            ':data_pag' => $dataPagamento ?? date('Y-m-d'),
            ':id'       => $id,
        ]);
    }

    /**
     * Ignora um boleto DDA.
     */
    public function ignorar(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET status_interno = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':status' => self::STATUS_IGNORADO, ':id' => $id]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Mapeia status Asaas para badge CSS.
     */
    public static function statusBadge(string $statusInterno): string
    {
        return match ($statusInterno) {
            'pendente'      => '<span class="badge bg-warning text-dark">Pendente</span>',
            'importado'     => '<span class="badge bg-info text-dark">Importado</span>',
            'pago_asaas'    => '<span class="badge bg-success">Pago (Asaas)</span>',
            'pago_inlaudo'  => '<span class="badge bg-success">Pago (InLaudo)</span>',
            'cancelado'     => '<span class="badge bg-secondary">Cancelado</span>',
            'ignorado'      => '<span class="badge bg-light text-muted">Ignorado</span>',
            default         => '<span class="badge bg-secondary">' . htmlspecialchars($statusInterno) . '</span>',
        };
    }

    /**
     * Badge de vencimento (vencido / próximo / ok).
     */
    public static function vencimentoBadge(string $dataVencimento): string
    {
        $hoje  = new \DateTime('today');
        $venc  = new \DateTime($dataVencimento);
        $diff  = (int)$hoje->diff($venc)->format('%r%a');

        if ($diff < 0) {
            return '<span class="badge bg-danger">Vencido</span>';
        } elseif ($diff <= 5) {
            return '<span class="badge bg-warning text-dark">Vence em ' . $diff . 'd</span>';
        }
        return '<span class="badge bg-light text-dark border">Pendente</span>';
    }
}
