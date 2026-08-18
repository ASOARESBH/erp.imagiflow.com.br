<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Lib/fpdf/fpdf.php';
require_once $root . '/app/Services/RelatorioPdfTemplate.php';

use App\Services\RelatorioPdfTemplate;

function assertRelatorioPdf(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$saida = sys_get_temp_dir() . '/relatorio_pdf_template_' . bin2hex(random_bytes(5)) . '.pdf';
$empresa = [
    'razao_social' => 'ORIX TELERRADIOLOGIA LTDA',
    'cpf_cnpj' => '10526487000102',
    'email' => 'financeiro@orix.com.br',
];

$pdf = new RelatorioPdfTemplate($empresa, 'RELATÓRIO FINANCEIRO');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(277, 8, 'Conteúdo de teste do relatório financeiro.', 0, 1, 'L');
$pdf->Output('F', $saida);

assertRelatorioPdf(is_file($saida), 'O template deve gerar um arquivo PDF.');
$conteudo = file_get_contents($saida);
assertRelatorioPdf(is_string($conteudo) && str_starts_with($conteudo, '%PDF'), 'O arquivo gerado deve possuir assinatura PDF.');
assertRelatorioPdf(filesize($saida) > 1000, 'O PDF institucional deve conter o template de cabeçalho e rodapé.');
unlink($saida);

echo "OK: template PDF institucional do tenant validado.\n";
