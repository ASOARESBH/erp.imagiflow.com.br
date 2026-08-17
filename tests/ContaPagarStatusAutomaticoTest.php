<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/ContaPagarStatusService.php';

use App\Services\ContaPagarStatusService;

function assertAutomaticPaymentStatus(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$service = new ContaPagarStatusService();

assertAutomaticPaymentStatus(
    $service->resolve('aberta', '2026-02-12', null) === 'aberta',
    'Sem data de pagamento, o status informado deve ser preservado.'
);
assertAutomaticPaymentStatus(
    $service->resolve('aberta', '2026-02-12', '2026-02-11') === 'aberta',
    'Pagamento anterior ao vencimento não atende à regra solicitada.'
);
assertAutomaticPaymentStatus(
    $service->resolve('aberta', '2026-02-12', '2026-02-12') === 'paga',
    'Pagamento na data de vencimento deve marcar a conta como paga.'
);
assertAutomaticPaymentStatus(
    $service->resolve('cancelada', '2026-02-12', '2026-02-13') === 'paga',
    'Pagamento posterior ao vencimento deve marcar a conta como paga.'
);
assertAutomaticPaymentStatus(
    $service->resolve('aberta', 'data-invalida', '2026-02-13') === 'aberta',
    'Datas inválidas não devem alterar o status.'
);

$dados = $service->apply([
    'status' => 'aberta',
    'data_vencimento' => '2026-02-12',
    'data_pagamento' => '2026-02-15',
]);
assertAutomaticPaymentStatus($dados['status'] === 'paga', 'O método apply deve atualizar o status que será persistido.');

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/Controllers/ContasPagarController.php');
$view = file_get_contents($root . '/app/Views/contas_pagar/tabs/geral-enterprise.php');
$script = file_get_contents($root . '/public/assets/js/conta-pagar-status-automatico.js');

assertAutomaticPaymentStatus(substr_count($controller, 'statusService->apply($dados)') === 2, 'A regra deve ser aplicada tanto ao criar quanto ao editar.');
assertAutomaticPaymentStatus(strpos($view, 'aviso_status_pago_automatico') !== false, 'A tela deve informar o status automático.');
assertAutomaticPaymentStatus(strpos($script, "paid >= due") !== false, 'A interface deve refletir a comparação de datas solicitada.');

echo "OK: status automático de conta a pagar validado.\n";
