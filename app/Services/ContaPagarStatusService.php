<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

/**
 * Centraliza regras de status que dependem das datas de uma conta a pagar.
 */
final class ContaPagarStatusService
{
    /**
     * Mantém o status solicitado, exceto quando houver pagamento na data de
     * vencimento ou depois dela: nesse caso a conta deve ser persistida como paga.
     *
     * Datas inválidas não alteram o status; a validação do formulário continua
     * responsável por tratar os campos obrigatórios e o banco persiste a data
     * recebida conforme seu contrato atual.
     */
    public function resolve(string $statusSolicitado, ?string $dataVencimento, ?string $dataPagamento): string
    {
        $status = trim($statusSolicitado) ?: 'aberta';
        $vencimento = $this->parseIsoDate($dataVencimento);
        $pagamento = $this->parseIsoDate($dataPagamento);

        if ($vencimento !== null && $pagamento !== null && $pagamento >= $vencimento) {
            return 'paga';
        }

        return $status;
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function apply(array $dados): array
    {
        $dados['status'] = $this->resolve(
            (string) ($dados['status'] ?? 'aberta'),
            $this->nullableString($dados['data_vencimento'] ?? null),
            $this->nullableString($dados['data_pagamento'] ?? null)
        );

        return $dados;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function parseIsoDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }
}
