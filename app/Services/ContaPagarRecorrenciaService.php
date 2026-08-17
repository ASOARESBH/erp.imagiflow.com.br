<?php

namespace App\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Logger;
use App\Models\ContaPagar;
use PDO;

/**
 * Gera parcelas futuras de contas a pagar de prazo determinado.
 * A parcela raiz corresponde ao primeiro vencimento; as demais são abertas
 * e calculadas a partir da data raiz, evitando deriva de datas entre parcelas.
 */
class ContaPagarRecorrenciaService
{
    private ContaPagar $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new ContaPagar();
        $this->logger = new Logger();
    }

    /**
     * @return array{geradas:int,ids:int[],erros:string[],grupo:string}
     */
    public function gerarParcelas(
        int $usuarioId,
        int $tenantId,
        int $contaRaizId,
        int $totalParcelas,
        string $tipoRecorrencia
    ): array {
        $resultado = ['geradas' => 0, 'ids' => [], 'erros' => [], 'grupo' => ''];

        if ($totalParcelas <= 1) {
            return $resultado;
        }

        $pdo = $this->model->getPdo();

        try {
            $contaRaiz = $this->model->findByIdForTenant($contaRaizId, $tenantId);
            if (!$contaRaiz || (int) ($contaRaiz->usuario_id ?? 0) !== $usuarioId) {
                $resultado['erros'][] = 'Conta raiz não encontrada ou sem permissão.';
                return $resultado;
            }

            $tipoRecorrencia = $this->normalizarTipo($tipoRecorrencia);
            if ($tipoRecorrencia === '') {
                $resultado['erros'][] = 'Tipo de recorrência inválido.';
                return $resultado;
            }

            $dataBase = (string) ($contaRaiz->data_vencimento ?? '');
            if ($dataBase === '') {
                $resultado['erros'][] = 'A conta raiz não possui data de vencimento.';
                return $resultado;
            }

            $grupo = 'cp:' . $contaRaizId . ':' . date('YmdHis') . ':' . $tenantId;
            $resultado['grupo'] = $grupo;

            $pdo->beginTransaction();

            $this->model->updateForTenant($contaRaizId, $tenantId, [
                'recorrente' => 1,
                'recorrencia_tipo' => $tipoRecorrencia,
                'recorrencia_intervalo' => $totalParcelas,
                'recorrencia_modo' => 'antecipado',
                'numero_parcela' => 1,
                'total_parcelas' => $totalParcelas,
                'grupo_parcelas' => $grupo,
            ]);
            $resultado['ids'][] = $contaRaizId;

            for ($numero = 2; $numero <= $totalParcelas; $numero++) {
                $vencimento = $this->calcularVencimento($dataBase, $tipoRecorrencia, $numero - 1);
                if ($vencimento === '') {
                    throw new \RuntimeException('Não foi possível calcular o vencimento da parcela ' . $numero . '.');
                }

                $descricao = $this->formatarDescricao((string) ($contaRaiz->descricao ?? ''), $numero, $totalParcelas);
                $novoId = $this->model->create([
                    'tenant_id' => $tenantId,
                    'usuario_id' => $usuarioId,
                    'plano_conta_id' => (int) ($contaRaiz->plano_conta_id ?? 0),
                    'fornecedor_id' => $contaRaiz->fornecedor_id ?? null,
                    'descricao' => $descricao,
                    'valor' => (string) ($contaRaiz->valor ?? '0.00'),
                    'data_vencimento' => $vencimento,
                    'data_pagamento' => null,
                    'codigo_barras' => null,
                    'recorrente' => 1,
                    'recorrencia_tipo' => $tipoRecorrencia,
                    'recorrencia_intervalo' => $totalParcelas,
                    'recorrencia_modo' => 'antecipado',
                    'numero_parcela' => $numero,
                    'total_parcelas' => $totalParcelas,
                    'grupo_parcelas' => $grupo,
                    'status' => 'aberta',
                    'observacoes' => $contaRaiz->observacoes ?? null,
                ]);

                if (!$novoId) {
                    throw new \RuntimeException('Falha ao gravar a parcela ' . $numero . '.');
                }

                $resultado['ids'][] = (int) $novoId;
                $resultado['geradas']++;
            }

            $pdo->commit();

            AuditLogger::log('conta_pagar_parcelas_geradas', [
                'usuario_id' => $usuarioId,
                'tenant_id' => $tenantId,
                'conta_raiz_id' => $contaRaizId,
                'grupo_parcelas' => $grupo,
                'total_parcelas' => $totalParcelas,
                'geradas' => $resultado['geradas'],
                'tipo' => $tipoRecorrencia,
            ]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->logger->error('Falha ao gerar parcelas de conta a pagar.', [
                'usuario_id' => $usuarioId,
                'tenant_id' => $tenantId,
                'conta_raiz_id' => $contaRaizId,
                'total_parcelas' => $totalParcelas,
                'error' => $e->getMessage(),
            ]);
            $resultado['erros'][] = 'Não foi possível gerar todas as parcelas.';
        }

        return $resultado;
    }

    private function calcularVencimento(string $dataBase, string $tipo, int $offset): string
    {
        try {
            $data = new \DateTimeImmutable($dataBase);
            switch ($tipo) {
                case 'semanal':
                    return $data->modify('+' . $offset . ' week')->format('Y-m-d');
                case 'mensal':
                    return $data->modify('+' . $offset . ' month')->format('Y-m-d');
                case 'anual':
                    return $data->modify('+' . $offset . ' year')->format('Y-m-d');
                case 'customizada':
                    return $data->modify('+' . $offset . ' day')->format('Y-m-d');
                default:
                    return '';
            }
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipos = ['semanal', 'mensal', 'anual', 'customizada'];
        return in_array($tipo, $tipos, true) ? $tipo : '';
    }

    private function formatarDescricao(string $descricao, int $numero, int $total): string
    {
        $base = preg_replace('/\s*[—\-–]\s*Parcela\s+\d+\/\d+\s*$/iu', '', $descricao);
        $base = trim((string) $base);
        return $base . ' — Parcela ' . $numero . '/' . $total;
    }
}
