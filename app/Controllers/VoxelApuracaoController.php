<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Models\Apuracao;
use App\Models\ApuracaoVoxelImport;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Medico;
use App\Services\VoxelPacsService;

class VoxelApuracaoController extends Controller
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function importar(): void
    {
        $usuarioId = (int) Auth::user()->id;
        $apuracaoId = (int) ($_POST['apuracao_id'] ?? 0);
        $inicio = trim((string) ($_POST['periodo_inicio'] ?? ''));
        $fim = trim((string) ($_POST['periodo_fim'] ?? ''));
        $medicoId = (int) ($_POST['medico_id'] ?? 0);
        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $unidade = trim((string) ($_POST['unidade'] ?? ''));

        try {
            $apuracao = (new Apuracao())->findById($apuracaoId);
            if (!$apuracao || (int) $apuracao->usuario_id !== $usuarioId) {
                throw new \RuntimeException('Apuração não encontrada.');
            }
            if (($apuracao->status ?? '') !== 'rascunho') {
                throw new \RuntimeException('A importação VOXEL só é permitida em apurações em rascunho.');
            }
            $contrato = (new Contrato())->findById((int) $apuracao->contrato_id);
            if (!$contrato || (int) $contrato->usuario_id !== $usuarioId) {
                throw new \RuntimeException('Contrato da apuração não encontrado.');
            }
            $this->validatePeriod($inicio, $fim);
            if (($contrato->tipo_parte ?? '') === 'medico') {
                if ($clienteId <= 0) {
                    throw new \RuntimeException('Selecione o cliente/unidade para a apuração de prestador.');
                }
                $cliente = (new Cliente())->findById($clienteId);
                if (!$cliente || (int) ($cliente->usuario_id ?? 0) !== $usuarioId) {
                    throw new \RuntimeException('Cliente/unidade selecionado não encontrado.');
                }
            }

            if (($contrato->tipo_parte ?? '') === 'medico'
                && $medicoId > 0
                && (int) ($contrato->medico_id ?? 0) > 0
                && $medicoId !== (int) $contrato->medico_id) {
                throw new \RuntimeException('A apuração de prestador deve utilizar o médico vinculado ao contrato.');
            }

            $medico = null;
            if ($medicoId > 0) {
                $medico = (new Medico())->findById($medicoId);
                if (!$medico || (int) $medico->usuario_id !== $usuarioId) {
                    throw new \RuntimeException('Médico selecionado não encontrado.');
                }
            } elseif ((int) ($apuracao->medico_id ?? 0) > 0) {
                $candidate = (new Medico())->findById((int) $apuracao->medico_id);
                if ($candidate && (int) $candidate->usuario_id === $usuarioId) {
                    $medico = $candidate;
                }
            }
            $crm = $medico ? (preg_replace('/\D+/', '', (string) ($medico->crm ?? '')) ?: '') : '';
            if (($apuracao->tipo ?? '') === 'prestador' && $crm === '') {
                throw new \RuntimeException('Selecione um médico com CRM para importar uma apuração de prestador.');
            }

            $voxel = VoxelPacsService::forUser($usuarioId);
            if ($crm !== '') {
                $doctorResponse = $voxel->consultarMedico($crm, (string) ($medico->nome ?? ''));
                $doctorData = is_array($doctorResponse['data'] ?? null) ? $doctorResponse['data'] : [];
                if (($doctorData['found'] ?? false) !== true) {
                    throw new \RuntimeException('O médico não possui um vínculo único e ativo no VOXEL PACS.');
                }
            }

            $result = $voxel->listarEstudos($inicio, $fim, $crm, $unidade);
            $importModel = new ApuracaoVoxelImport();
            $importModel->releasePendingForApuracao($usuarioId, $apuracaoId);

            $accepted = [];
            $existing = 0;
            $pendingDoctor = 0;
            $localMedicoModel = new Medico();
            foreach ($result['itens'] as $study) {
                $sourceReference = trim((string) ($study['source_reference'] ?? ''));
                $studyCrm = preg_replace('/\D+/', '', (string) ($study['medico_crm'] ?? '')) ?: '';
                if ($sourceReference === '' || $studyCrm === '') {
                    $pendingDoctor++;
                    continue;
                }
                if (!$medico && !$localMedicoModel->findByCrm($usuarioId, $studyCrm)) {
                    $pendingDoctor++;
                    continue;
                }
                if ($importModel->existsForUser($usuarioId, $sourceReference)) {
                    $existing++;
                    continue;
                }
                $study['_source_reference'] = $sourceReference;
                $accepted[] = $study;
            }
            if (empty($accepted)) {
                $this->jsonSuccess('Nenhum estudo novo elegível foi encontrado.', [
                    'preview' => [], 'total_linhas' => 0, 'ja_existentes' => $existing, 'pendentes_medico' => $pendingDoctor,
                ]);
            }

            $requestId = (string) ($result['request_id'] ?? '');
            $reserved = [];
            foreach ($accepted as $study) {
                $reservedOk = $importModel->reserve(
                    $usuarioId,
                    $apuracaoId,
                    (string) $study['_source_reference'],
                    $requestId,
                    hash('sha256', json_encode($study, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                );
                if ($reservedOk) {
                    $reserved[] = $study;
                } else {
                    $existing++;
                }
            }
            if (empty($reserved)) {
                $this->jsonSuccess('Nenhum estudo novo elegível foi encontrado.', [
                    'preview' => [], 'total_linhas' => 0, 'ja_existentes' => $existing, 'pendentes_medico' => $pendingDoctor,
                ]);
            }
            try {
                $relativePath = $this->writeInternalCsv($usuarioId, $apuracaoId, $reserved);
            } catch (\Throwable $writeException) {
                $importModel->releasePendingForApuracao($usuarioId, $apuracaoId);
                throw $writeException;
            }

            $updateData = [
                'usuario_id' => $usuarioId,
                'arquivo_import' => $relativePath,
                'periodo_inicio' => $inicio,
                'periodo_fim' => $fim,
                'origem' => 'pacs',
                'status' => 'rascunho',
            ];
            if ($clienteId > 0) {
                $updateData['cliente_id'] = $clienteId;
            }
            (new Apuracao())->update($apuracaoId, $updateData);
            AuditLogger::log('apuracao_voxel_consultada', [
                'apuracao_id' => $apuracaoId,
                'request_id' => $requestId,
                'importados_pendentes' => count($reserved),
                'ja_existentes' => $existing,
                'pendentes_medico' => $pendingDoctor,
            ]);
            $this->jsonSuccess('Estudos VOXEL consultados. Revise a prévia e execute a apuração.', [
                'preview' => array_slice(array_map([$this, 'preview'], $reserved), 0, 5),
                'total_linhas' => count($reserved),
                'ja_existentes' => $existing,
                'pendentes_medico' => $pendingDoctor,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Falha na importação de apuração VOXEL', [
                'apuracao_id' => $apuracaoId,
                'usuario_id' => $usuarioId,
                'error' => $exception->getMessage(),
            ]);
            $this->jsonError($exception->getMessage());
        }
    }

    /** @param array<string,mixed> $study */
    private function preview(array $study): array
    {
        return [
            'linha_original' => null,
            'medico' => (string) ($study['medico_nome'] ?? ''),
            'modalidade' => (string) ($study['modalidade'] ?? ''),
            'study_description' => (string) ($study['study_description'] ?? ''),
            'prioridade' => (string) ($study['prioridade'] ?? 'ROUTINE'),
            'data_conclusao' => (string) ($study['data_conclusao'] ?? ''),
        ];
    }

    /** @param array<int,array<string,mixed>> $studies */
    private function writeInternalCsv(int $usuarioId, int $apuracaoId, array $studies): string
    {
        $dir = BASE_PATH . '/storage/uploads/apuracoes/' . $usuarioId . '/' . $apuracaoId;
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('Não foi possível preparar a importação VOXEL.');
        }
        $name = 'voxel_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.csv';
        $path = $dir . '/' . $name;
        $handle = fopen($path, 'wb');
        if (!$handle) {
            throw new \RuntimeException('Não foi possível preparar os estudos VOXEL para apuração.');
        }
        fputcsv($handle, ['Seq','Unidade','ID','Médico','CRM','Revisor','Data Revisão','Modalidade','Descrição','Paciente','Paciente ID','Prioridade','Origem','Registro','Data Estudo','Data Conclusão','SLA','Accession','Visita','Convênio','Valor','Valor Exame'], ';');
        foreach ($studies as $index => $study) {
            fputcsv($handle, [
                $index + 1,
                $study['unidade'] ?? '',
                $study['estudo_id'] ?? '',
                $study['medico_nome'] ?? '',
                preg_replace('/\D+/', '', (string) ($study['medico_crm'] ?? '')) ?: '',
                '',
                $this->normalizeDate($study['assinado_em'] ?? ''),
                $this->normalizeModality((string) ($study['modalidade'] ?? '')),
                $study['study_description'] ?? '',
                $study['paciente_nome'] ?? '',
                $study['paciente_id'] ?? '',
                $this->normalizePriority((string) ($study['prioridade'] ?? 'ROUTINE')),
                'voxel_pacs',
                $study['_source_reference'],
                $this->normalizeDate($study['data_estudo'] ?? ''),
                $this->normalizeDate($study['data_conclusao'] ?? ''),
                $study['sla_minutos'] ?? '',
                $study['accession_number'] ?? '',
                '', '', '0', '0',
            ], ';');
        }
        fclose($handle);
        return 'storage/uploads/apuracoes/' . $usuarioId . '/' . $apuracaoId . '/' . $name;
    }

    private function normalizePriority(string $priority): string
    {
        $priority = strtoupper(trim($priority));
        return in_array($priority, ['HIGH', 'STAT', 'URGENT', 'URGENTE'], true) ? 'Urgente' : 'Normal';
    }

    private function normalizeModality(string $modality): string
    {
        $parts = preg_split('/[\\\\,;|\\/]+/', strtoupper(trim($modality))) ?: [];
        return trim((string) ($parts[0] ?? ''));
    }

    private function normalizeDate(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    private function validatePeriod(string $inicio, string $fim): void
    {
        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $inicio);
        $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $fim);
        if (!$start || !$end || $start->format('Y-m-d') !== $inicio || $end->format('Y-m-d') !== $fim) {
            throw new \RuntimeException('Informe o período no formato YYYY-MM-DD.');
        }
        if ($end < $start || $end->diff($start)->days > 92) {
            throw new \RuntimeException('O período deve ter no máximo 93 dias.');
        }
    }

    private function jsonSuccess(string $message, array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => $message] + $data);
        exit();
    }

    private function jsonError(string $message): void
    {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
}
