<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Consultas de leitura para relatórios financeiros.
 * Todas as consultas são obrigatoriamente limitadas ao tenant informado.
 */
class RelatorioFinanceiro extends Model
{
    private const TIPOS_RELATORIO = ['pagar', 'receber', 'comparativo'];
    private const TIPOS_DATA = ['vencimento', 'efetivo', 'emissao'];
    private const AGRUPAMENTOS = ['detalhado', 'plano', 'entidade', 'status'];

    /**
     * @return array{linhas:array<int,object>,grupos:array<int,array<string,mixed>>,totais:array<string,float|int>,total_registros:int,pagina:int,por_pagina:int}
     */
    public function buscar(int $tenantId, array $filtros, int $pagina = 1, int $porPagina = 50, bool $completo = false): array
    {
        $tipo = $this->normalizarTipo((string) ($filtros['tipo_relatorio'] ?? 'pagar'));
        $linhas = $this->buscarLinhas($tenantId, $tipo, $filtros);
        $totais = $this->calcularTotais($linhas, $tipo);
        $grupos = $this->agrupar($linhas, (string) ($filtros['agrupamento'] ?? 'detalhado'));
        $total = count($linhas);

        $pagina = max(1, $pagina);
        $porPagina = min(100, max(1, $porPagina));
        if (!$completo) {
            $offset = ($pagina - 1) * $porPagina;
            $linhas = array_slice($linhas, $offset, $porPagina);
            $grupos = $this->agrupar($linhas, (string) ($filtros['agrupamento'] ?? 'detalhado'));
        }

        return [
            'linhas' => $linhas,
            'grupos' => $grupos,
            'totais' => $totais,
            'total_registros' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
        ];
    }

    /** @return object[] */
    public function listarPlanos(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, codigo, nome, tipo
             FROM plano_contas
             WHERE tenant_id = :tenant_id AND status = 'ativo'
             ORDER BY tipo ASC, codigo ASC, nome ASC"
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** @return object[] */
    public function listarFornecedores(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nome, documento
             FROM fornecedores
             WHERE tenant_id = :tenant_id AND status = 'ativo'
             ORDER BY nome ASC"
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** @return object[] */
    public function listarClientes(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, razao_social, nome_fantasia, cpf_cnpj
             FROM clientes
             WHERE tenant_id = :tenant_id AND status = 'ativo'
             ORDER BY COALESCE(nome_fantasia, razao_social) ASC"
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** @return object[] */
    private function buscarLinhas(int $tenantId, string $tipo, array $filtros): array
    {
        if ($tipo === 'comparativo') {
            $pagar = $this->buscarPorTipo($tenantId, 'pagar', $filtros);
            $receber = $this->buscarPorTipo($tenantId, 'receber', $filtros);
            $linhas = array_merge($pagar, $receber);
            usort($linhas, static function (object $a, object $b): int {
                return strcmp((string) $a->data_referencia, (string) $b->data_referencia)
                    ?: strcmp((string) $a->tipo_lancamento, (string) $b->tipo_lancamento)
                    ?: ((int) $a->id <=> (int) $b->id);
            });
            return $linhas;
        }

        return $this->buscarPorTipo($tenantId, $tipo, $filtros);
    }

    /** @return object[] */
    private function buscarPorTipo(int $tenantId, string $tipo, array $filtros): array
    {
        $isPagar = $tipo === 'pagar';
        $tabela = $isPagar ? 'contas_pagar' : 'contas_receber';
        $alias = $isPagar ? 'cp' : 'cr';
        $dataEfetiva = $isPagar ? 'data_pagamento' : 'data_recebimento';
        $tipoData = $this->normalizarTipoData((string) ($filtros['tipo_data'] ?? 'vencimento'));
        $colunaData = $tipoData === 'efetivo'
            ? "{$alias}.{$dataEfetiva}"
            : ($tipoData === 'emissao' ? "DATE({$alias}.created_at)" : "{$alias}.data_vencimento");

        $entidadeSql = $isPagar
            ? "COALESCE(f.nome, 'Sem fornecedor')"
            : "COALESCE(NULLIF(c.nome_fantasia, ''), c.razao_social, 'Sem cliente')";
        $joinEntidade = $isPagar
            ? "LEFT JOIN fornecedores f ON f.id = {$alias}.fornecedor_id AND f.tenant_id = {$alias}.tenant_id"
            : "LEFT JOIN clientes c ON c.id = {$alias}.cliente_id AND c.tenant_id = {$alias}.tenant_id";

        $sql = "SELECT
                    {$alias}.id,
                    '" . ($isPagar ? 'pagar' : 'receber') . "' AS tipo_lancamento,
                    {$alias}.descricao,
                    {$alias}.valor,
                    {$alias}.status,
                    {$alias}.data_vencimento,
                    {$alias}.{$dataEfetiva} AS data_efetiva,
                    DATE({$alias}.created_at) AS data_emissao,
                    {$colunaData} AS data_referencia,
                    {$alias}.plano_conta_id,
                    {$alias}." . ($isPagar ? 'fornecedor_id' : 'cliente_id') . " AS entidade_id,
                    {$entidadeSql} AS entidade_nome,
                    COALESCE(pc.codigo, '') AS plano_codigo,
                    COALESCE(pc.nome, 'Sem plano de contas') AS plano_nome
                FROM {$tabela} {$alias}
                {$joinEntidade}
                LEFT JOIN plano_contas pc ON pc.id = {$alias}.plano_conta_id AND pc.tenant_id = {$alias}.tenant_id
                WHERE {$alias}.tenant_id = :tenant_id";

        $params = [':tenant_id' => $tenantId];
        $sql .= $this->aplicarFiltros($sql, $params, $alias, $colunaData, $isPagar, $filtros);
        $sql .= " ORDER BY {$colunaData} ASC, {$alias}.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function aplicarFiltros(string $sql, array &$params, string $alias, string $colunaData, bool $isPagar, array $filtros): string
    {
        $extra = '';
        $inicio = $this->normalizarData((string) ($filtros['data_inicio'] ?? ''));
        $fim = $this->normalizarData((string) ($filtros['data_fim'] ?? ''));
        if ($inicio !== '') {
            $extra .= " AND {$colunaData} >= :data_inicio";
            $params[':data_inicio'] = $inicio;
        }
        if ($fim !== '') {
            $extra .= " AND {$colunaData} <= :data_fim";
            $params[':data_fim'] = $fim;
        }

        $this->adicionarFiltroLista($extra, $params, "{$alias}.plano_conta_id", $filtros['plano_ids'] ?? [], 'plano');
        $campoEntidade = $isPagar ? "{$alias}.fornecedor_id" : "{$alias}.cliente_id";
        $this->adicionarFiltroLista($extra, $params, $campoEntidade, $isPagar ? ($filtros['fornecedor_ids'] ?? []) : ($filtros['cliente_ids'] ?? []), $isPagar ? 'fornecedor' : 'cliente');
        $this->adicionarFiltroLista($extra, $params, "{$alias}.status", $filtros['status'] ?? [], 'status', false);

        $valorMin = $this->normalizarValor($filtros['valor_min'] ?? null);
        $valorMax = $this->normalizarValor($filtros['valor_max'] ?? null);
        if ($valorMin !== null) {
            $extra .= " AND {$alias}.valor >= :valor_min";
            $params[':valor_min'] = $valorMin;
        }
        if ($valorMax !== null) {
            $extra .= " AND {$alias}.valor <= :valor_max";
            $params[':valor_max'] = $valorMax;
        }

        return $extra;
    }

    private function adicionarFiltroLista(string &$sql, array &$params, string $campo, mixed $valores, string $prefixo, bool $inteiro = true): void
    {
        if (!is_array($valores)) {
            $valores = $valores === '' || $valores === null ? [] : [$valores];
        }
        $valores = array_values(array_filter($valores, static fn($valor): bool => $valor !== '' && $valor !== null));
        if ($valores === []) {
            return;
        }

        $placeholders = [];
        foreach ($valores as $indice => $valor) {
            $chave = ':' . $prefixo . '_' . $indice;
            $placeholders[] = $chave;
            $params[$chave] = $inteiro ? (int) $valor : (string) $valor;
        }
        $sql .= " AND {$campo} IN (" . implode(', ', $placeholders) . ')';
    }

    /** @return array<string,float|int> */
    private function calcularTotais(array $linhas, string $tipo): array
    {
        $pagar = 0.0;
        $receber = 0.0;
        foreach ($linhas as $linha) {
            if (($linha->tipo_lancamento ?? '') === 'pagar') {
                $pagar += (float) ($linha->valor ?? 0);
            } else {
                $receber += (float) ($linha->valor ?? 0);
            }
        }

        return [
            'quantidade' => count($linhas),
            'total_pagar' => $pagar,
            'total_receber' => $receber,
            'total_geral' => $tipo === 'pagar' ? $pagar : ($tipo === 'receber' ? $receber : $receber - $pagar),
            'saldo' => $receber - $pagar,
        ];
    }

    /** @return array<int,array{titulo:string,total:float,quantidade:int,itens:array<int,object>}> */
    private function agrupar(array $linhas, string $agrupamento): array
    {
        $agrupamento = in_array($agrupamento, self::AGRUPAMENTOS, true) ? $agrupamento : 'detalhado';
        if ($agrupamento === 'detalhado') {
            return [];
        }

        $grupos = [];
        foreach ($linhas as $linha) {
            $chave = match ($agrupamento) {
                'plano' => trim((string) $linha->plano_codigo . ' - ' . (string) $linha->plano_nome),
                'entidade' => (string) $linha->entidade_nome,
                'status' => ucfirst((string) $linha->status),
                default => 'Detalhado',
            };
            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['titulo' => $chave, 'total' => 0.0, 'quantidade' => 0, 'itens' => []];
            }
            $grupos[$chave]['total'] += (float) ($linha->valor ?? 0);
            $grupos[$chave]['quantidade']++;
            $grupos[$chave]['itens'][] = $linha;
        }

        return array_values($grupos);
    }

    private function normalizarTipo(string $tipo): string
    {
        return in_array($tipo, self::TIPOS_RELATORIO, true) ? $tipo : 'pagar';
    }

    private function normalizarTipoData(string $tipo): string
    {
        return in_array($tipo, self::TIPOS_DATA, true) ? $tipo : 'vencimento';
    }

    private function normalizarData(string $data): string
    {
        $data = trim($data);
        if ($data === '') {
            return '';
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data);
        return $date && $date->format('Y-m-d') === $data ? $data : '';
    }

    private function normalizarValor(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $normalizado = str_replace(['R$', '.', ','], ['', '', '.'], trim((string) $valor));
        return is_numeric($normalizado) && (float) $normalizado >= 0 ? number_format((float) $normalizado, 2, '.', '') : null;
    }
}
