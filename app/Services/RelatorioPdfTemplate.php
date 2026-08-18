<?php

namespace App\Services;

/**
 * Template institucional reutilizável para documentos PDF do ERP.
 * Recebe os dados do tenant já resolvidos pelo TenantCompanyProfileService.
 */
class RelatorioPdfTemplate extends \FPDF
{
    private array $empresa;
    private string $titulo;
    private ?string $logoAbsoluto;

    public function __construct(array $empresa, string $titulo, ?string $logoAbsoluto = null)
    {
        parent::__construct('L', 'mm', 'A4');
        $this->empresa = $empresa;
        $this->titulo = $titulo;
        $this->logoAbsoluto = $logoAbsoluto;
        $this->SetMargins(10, 41, 10);
        $this->SetAutoPageBreak(true, 24);
        $this->AliasNbPages();
    }

    public function Header(): void
    {
        $larguraPagina = 297;
        $this->SetFillColor(0, 89, 162);
        $this->Rect(0, 0, $larguraPagina, 36, 'F');

        $textoX = 12;
        if ($this->logoAbsoluto && is_file($this->logoAbsoluto)) {
            try {
                $this->Image($this->logoAbsoluto, 12, 5, 25, 0);
                $textoX = 41;
            } catch (\Throwable $e) {
                $textoX = 12;
            }
        }

        $razaoSocial = $this->texto((string) ($this->empresa['razao_social'] ?? 'Empresa não identificada'));
        $cnpj = $this->formatarDocumento((string) ($this->empresa['cpf_cnpj'] ?? ''));
        $email = $this->texto((string) ($this->empresa['email'] ?? ''));

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY($textoX, 7);
        $this->Cell(188, 7, $razaoSocial, 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetXY($textoX, 16);
        $linhaEmpresa = implode('  |  ', array_filter([$cnpj, $email]));
        if ($linhaEmpresa !== '') {
            $this->Cell(188, 5, $this->texto($linhaEmpresa), 0, 0, 'L');
        }

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 89, 162);
        $this->Rect(239, 7, 46, 21, 'F');
        $this->SetXY(239, 10);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(46, 5, $this->texto('ERP IMAGINIFLOW'), 0, 2, 'C');
        $this->SetFont('Arial', '', 7);
        $this->Cell(46, 4, $this->texto('RELATÓRIO'), 0, 2, 'C');
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(46, 5, $this->texto($this->titulo), 0, 0, 'C');

        $this->SetTextColor(45, 45, 45);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, 38);
        $this->Cell(277, 6, $this->texto($this->titulo), 0, 0, 'L');
        $this->Ln(2);
    }

    public function Footer(): void
    {
        $this->SetY(-19);
        $this->SetDrawColor(210, 215, 220);
        $this->Line(10, $this->GetY(), 287, $this->GetY());
        $this->SetY(-16);
        $this->SetTextColor(85, 85, 85);
        $this->SetFont('Arial', '', 7);

        $empresa = implode('  |  ', array_filter([
            (string) ($this->empresa['razao_social'] ?? ''),
            $this->formatarDocumento((string) ($this->empresa['cpf_cnpj'] ?? '')),
            (string) ($this->empresa['email'] ?? ''),
        ]));
        $this->Cell(210, 5, $this->texto($empresa), 0, 0, 'L');
        $this->Cell(67, 5, $this->texto('ERP IMAGINIFLOW | www.imaginiflow.com.br'), 0, 1, 'R');
        $this->Cell(210, 4, $this->texto('Documento gerado em ' . date('d/m/Y H:i')), 0, 0, 'L');
        $this->Cell(67, 4, $this->texto('Página ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
    }

    private function texto(string $texto): string
    {
        return (string) iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $texto);
    }

    private function formatarDocumento(string $documento): string
    {
        $digitos = preg_replace('/\D/', '', $documento) ?? '';
        if (strlen($digitos) === 14) {
            return 'CNPJ: ' . substr($digitos, 0, 2) . '.' . substr($digitos, 2, 3) . '.' . substr($digitos, 5, 3) . '/' . substr($digitos, 8, 4) . '-' . substr($digitos, 12, 2);
        }
        if (strlen($digitos) === 11) {
            return 'CPF: ' . substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
        }
        return $digitos === '' ? '' : 'Documento: ' . $digitos;
    }
}
