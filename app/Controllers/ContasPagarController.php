<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\ContaPagar;
use App\Models\ContaPagarAnexo;
use App\Models\PlanoConta;
use App\Models\Fornecedor;
use App\Models\DdaBoleto;
use App\Models\Integracao;
use App\Services\AsaasService;

class ContasPagarController extends Controller
{
    private ContaPagar $model;
    private ContaPagarAnexo $anexoModel;
    private PlanoConta $planoContaModel;
    private Fornecedor $fornecedorModel;
    private Logger $logger;
    private DdaBoleto $ddaModel;
    private ?AsaasService $asaasService = null;

    public function __construct()
    {
        $this->model = new ContaPagar();
        $this->anexoModel = new ContaPagarAnexo();
        $this->planoContaModel = new PlanoConta();
        $this->fornecedorModel = new Fornecedor();
        $this->logger = new Logger();
        $this->ddaModel = new DdaBoleto();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $filtros = [
                'status' => $_GET['status'] ?? 'aberta',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $contas = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('contas_pagar/index', [
                '_layout' => 'erp',
                'title' => 'Contas a Pagar',
                'breadcrumb' => [
                    'Financeiro' => '/financeiro/pagar',
                    0 => 'Contas a Pagar',
                ],
                'contas' => $contas,
                'filtros' => $filtros,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar contas a pagar: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        $usuarioId = Auth::user()->id;

        $planos = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
        $fornecedores = $this->fornecedorModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);

        View::render('contas_pagar/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Nova Conta a Pagar',
            'conta' => null,
            'planos' => $planos,
            'fornecedores' => $fornecedores,
            'anexos' => [],
            'tab' => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $planoContaId = (int)($_POST['plano_conta_id'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            $dataVencimento = $_POST['data_vencimento'] ?? '';

            if ($planoContaId <= 0 || $descricao === '' || $valor === '' || $dataVencimento === '') {
                header('Location: /financeiro/contas-a-pagar/create?error=missing_fields');
                exit();
            }

            $plano = $this->planoContaModel->findById($planoContaId);
            if (!$plano || (int)$plano->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-pagar/create?error=invalid_plano');
                exit();
            }

            $fornecedorId = $_POST['fornecedor_id'] ?? null;
            if ($fornecedorId !== null && $fornecedorId !== '') {
                $forn = $this->fornecedorModel->findById((int)$fornecedorId);
                if (!$forn || (int)$forn->usuario_id !== (int)$usuarioId) {
                    header('Location: /financeiro/contas-a-pagar/create?error=invalid_fornecedor');
                    exit();
                }
            }

            $dados = [
                'usuario_id' => $usuarioId,
                'plano_conta_id' => $planoContaId,
                'fornecedor_id' => $fornecedorId,
                'descricao' => $descricao,
                'valor' => $valor,
                'data_vencimento' => $dataVencimento,
                'data_pagamento' => $_POST['data_pagamento'] ?? null,
                'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
                'recorrente' => isset($_POST['recorrente']) ? 1 : 0,
                'recorrencia_tipo' => $_POST['recorrencia_tipo'] ?? null,
                'recorrencia_intervalo' => $_POST['recorrencia_intervalo'] ?? null,
                'status' => $_POST['status'] ?? 'aberta',
                'observacoes' => trim($_POST['observacoes'] ?? ''),
            ];

            if ($dados['codigo_barras'] === '') $dados['codigo_barras'] = null;
            if ($dados['observacoes'] === '') $dados['observacoes'] = null;

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_conta_pagar', ['id' => $id, 'descricao' => $descricao, 'valor' => $valor]);
                header("Location: /financeiro/contas-a-pagar/edit/{$id}?success=created&tab=anexos");
            } else {
                header('Location: /financeiro/contas-a-pagar/create?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar conta a pagar: ' . $e->getMessage());
            header('Location: /financeiro/contas-a-pagar/create?error=fatal');
        }
        exit();
    }

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $conta = $this->model->findById((int)$id);

        if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
            header('Location: /financeiro/contas-a-pagar?error=not_found');
            exit();
        }

        $planos = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
        $fornecedores = $this->fornecedorModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
        $anexos = $this->anexoModel->findByContaId((int)$conta->id, $usuarioId);

        View::render('contas_pagar/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Editar Conta a Pagar',
            'conta' => $conta,
            'planos' => $planos,
            'fornecedores' => $fornecedores,
            'anexos' => $anexos,
            'tab' => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-pagar?error=unauthorized');
                exit();
            }

            $planoContaId = (int)($_POST['plano_conta_id'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            $dataVencimento = $_POST['data_vencimento'] ?? '';

            if ($planoContaId <= 0 || $descricao === '' || $valor === '' || $dataVencimento === '') {
                header("Location: /financeiro/contas-a-pagar/edit/{$id}?error=missing_fields");
                exit();
            }

            $plano = $this->planoContaModel->findById($planoContaId);
            if (!$plano || (int)$plano->usuario_id !== (int)$usuarioId) {
                header("Location: /financeiro/contas-a-pagar/edit/{$id}?error=invalid_plano");
                exit();
            }

            $fornecedorId = $_POST['fornecedor_id'] ?? null;
            if ($fornecedorId !== null && $fornecedorId !== '') {
                $forn = $this->fornecedorModel->findById((int)$fornecedorId);
                if (!$forn || (int)$forn->usuario_id !== (int)$usuarioId) {
                    header("Location: /financeiro/contas-a-pagar/edit/{$id}?error=invalid_fornecedor");
                    exit();
                }
            }

            $dados = [
                'plano_conta_id' => $planoContaId,
                'fornecedor_id' => $fornecedorId,
                'descricao' => $descricao,
                'valor' => $valor,
                'data_vencimento' => $dataVencimento,
                'data_pagamento' => $_POST['data_pagamento'] ?? null,
                'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
                'recorrente' => isset($_POST['recorrente']) ? 1 : 0,
                'recorrencia_tipo' => $_POST['recorrencia_tipo'] ?? null,
                'recorrencia_intervalo' => $_POST['recorrencia_intervalo'] ?? null,
                'status' => $_POST['status'] ?? 'aberta',
                'observacoes' => trim($_POST['observacoes'] ?? ''),
            ];

            if ($dados['codigo_barras'] === '') $dados['codigo_barras'] = null;
            if ($dados['observacoes'] === '') $dados['observacoes'] = null;

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_conta_pagar', ['id' => (int)$id, 'descricao' => $descricao, 'valor' => $valor]);
                header("Location: /financeiro/contas-a-pagar/edit/{$id}?success=updated&tab=geral");
            } else {
                header("Location: /financeiro/contas-a-pagar/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar conta a pagar: ' . $e->getMessage());
            header("Location: /financeiro/contas-a-pagar/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-pagar?error=unauthorized');
                exit();
            }

            if ($this->model->cancel((int)$id)) {
                AuditLogger::log('delete_conta_pagar', ['id' => (int)$id, 'descricao' => $conta->descricao ?? null]);
                header('Location: /financeiro/contas-a-pagar?success=deleted');
            } else {
                header('Location: /financeiro/contas-a-pagar?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao cancelar conta a pagar: ' . $e->getMessage());
            header('Location: /financeiro/contas-a-pagar?error=fatal');
        }
        exit();
    }

    public function uploadAnexo(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $contaId = (int)($_POST['conta_pagar_id'] ?? 0);

            if ($contaId <= 0) {
                header('Location: /financeiro/contas-a-pagar?error=invalid_request');
                exit();
            }

            $conta = $this->model->findById($contaId);
            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-pagar?error=unauthorized');
                exit();
            }

            if (!isset($_FILES['anexo'])) {
                header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=upload_failed&tab=anexos");
                exit();
            }

            $files = $_FILES['anexo'];
            $maxSize = 5 * 1024 * 1024;
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            $allowed = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                // Excel (legacy + OpenXML)
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-excel.sheet.macroEnabled.12' => 'xlsm',
                'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'xlsb',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'xltx',
                'application/vnd.ms-excel.template.macroEnabled.12' => 'xltm',
            ];

            $excelExts = ['xls', 'xlsx', 'xlsm', 'xlsb', 'xlt', 'xltx', 'xltm'];
            $excelFallbackMimes = [
                'application/zip',
                'application/octet-stream',
                'application/vnd.ms-office',
                'application/x-ole-storage',
                'application/cdfv2',
            ];

            $baseDir = BASE_PATH . '/storage/uploads/contas_pagar/' . $usuarioId . '/' . $contaId;
            if (!is_dir($baseDir)) {
                if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                    $this->logger->error('Falha ao criar diretório de upload (contas_pagar): ' . $baseDir . ' | BASE_PATH=' . BASE_PATH);
                    header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=upload_failed&tab=anexos");
                    exit();
                }
            }

            $isMulti = is_array($files['name'] ?? null);
            $count = $isMulti ? count((array) $files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $error = $isMulti ? ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) : ($files['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($error !== UPLOAD_ERR_OK) {
                    header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=upload_failed&tab=anexos");
                    exit();
                }

                $name = $isMulti ? ($files['name'][$i] ?? '') : ($files['name'] ?? '');
                $size = $isMulti ? ($files['size'][$i] ?? 0) : ($files['size'] ?? 0);
                $tmpPath = $isMulti ? ($files['tmp_name'][$i] ?? '') : ($files['tmp_name'] ?? '');

                if ($size > $maxSize) {
                    header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=file_too_large&tab=anexos");
                    exit();
                }

                $mime = $tmpPath !== '' ? ($finfo->file($tmpPath) ?: '') : '';
                $origExt = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

                $ext = $allowed[$mime] ?? null;
                if ($ext === null && in_array($origExt, $excelExts, true)) {
                    if (in_array($mime, $excelFallbackMimes, true) ||
                        str_starts_with($mime, 'application/vnd.ms-excel') ||
                        str_starts_with($mime, 'application/vnd.openxmlformats')) {
                        $ext = $origExt;
                    }
                }

                if ($ext === null) {
                    $this->logger->warning('Upload anexo (contas_pagar): tipo de arquivo inválido', [
                        'conta_pagar_id' => $contaId,
                        'mime' => $mime,
                        'original_name' => $name,
                        'original_ext' => $origExt,
                    ]);
                    header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=invalid_file_type&tab=anexos");
                    exit();
                }

                $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
                $destPath = $baseDir . '/' . $safeName;

                if (!move_uploaded_file($tmpPath, $destPath)) {
                    header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=upload_failed&tab=anexos");
                    exit();
                }

                $relativePath = 'storage/uploads/contas_pagar/' . $usuarioId . '/' . $contaId . '/' . $safeName;

                $anexoId = $this->anexoModel->create([
                    'usuario_id' => $usuarioId,
                    'conta_pagar_id' => $contaId,
                    'file_path' => $relativePath,
                    'original_name' => $name !== '' ? $name : 'anexo',
                    'mime_type' => $mime,
                    'file_size' => $size ?: null,
                ]);

                if ($anexoId) {
                    AuditLogger::log('upload_conta_pagar_anexo', ['id' => $anexoId, 'conta_pagar_id' => $contaId]);
                } else {
                    @unlink($destPath);
                    header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=db_failure&tab=anexos");
                    exit();
                }
            }
            header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?success=upload&tab=anexos");
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar anexo (contas a pagar): ' . $e->getMessage());
            $contaId = (int)($_POST['conta_pagar_id'] ?? 0);
            if ($contaId > 0) {
                header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=fatal&tab=anexos");
            } else {
                header('Location: /financeiro/contas-a-pagar?error=fatal');
            }
        }
        exit();
    }

    public function deleteAnexo($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $anexo = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-pagar?error=unauthorized');
                exit();
            }

            $contaId = (int)($anexo->conta_pagar_id ?? 0);
            $filePath = BASE_PATH . '/' . ltrim((string)($anexo->file_path ?? ''), '/');

            if ($this->anexoModel->delete((int)$id)) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                AuditLogger::log('delete_conta_pagar_anexo', ['id' => (int)$id, 'conta_pagar_id' => $contaId]);
                header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?success=deleted_anexo&tab=anexos");
            } else {
                header("Location: /financeiro/contas-a-pagar/edit/{$contaId}?error=db_failure&tab=anexos");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao remover anexo (contas a pagar): ' . $e->getMessage());
            header('Location: /financeiro/contas-a-pagar?error=fatal');
        }
        exit();
    }

    public function downloadAnexo($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $anexo = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                http_response_code(403);
                echo '403 - Acesso Negado';
                exit();
            }

            $fileRel = (string)($anexo->file_path ?? '');
            $fileAbs = BASE_PATH . '/' . ltrim($fileRel, '/');
            if (!is_file($fileAbs)) {
                http_response_code(404);
                echo '404 - Arquivo não encontrado';
                exit();
            }

            $mime = $anexo->mime_type ?? 'application/octet-stream';
            $name = $anexo->original_name ?? basename($fileAbs);

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($fileAbs));
            header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
            readfile($fileAbs);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao baixar anexo (contas a pagar): ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro ao baixar arquivo';
            exit();
        }
    }

    // =========================================================================
    // DDA — Débito Direto Autorizado
    // =========================================================================

    private function getAsaasService(): AsaasService
    {
        if ($this->asaasService === null) {
            $usuarioId = (int)(Auth::user()->id ?? 0);
            $apiKey    = null;
            $env       = null;
            if ($usuarioId > 0) {
                $integracaoModel = new Integracao();
                $config = $integracaoModel->findByProvider('asaas', $usuarioId);
                if ($config && !empty($config->api_key)) {
                    $apiKey = $config->api_key;
                    $env    = $config->environment ?? 'sandbox';
                }
            }
            $this->asaasService = new AsaasService($apiKey, $env);
        }
        return $this->asaasService;
    }

    public function ddaIndex(): void
    {
        try {
            $usuarioId = (int)Auth::user()->id;
            $filtros = [
                'status_interno' => $_GET['status'] ?? '',
                'pesquisa'       => $_GET['q'] ?? '',
                'venc_de'        => $_GET['venc_de'] ?? '',
                'venc_ate'       => $_GET['venc_ate'] ?? '',
            ];
            $boletos      = $this->ddaModel->findByUsuarioId($usuarioId, $filtros);
            $contagens    = $this->ddaModel->countByStatus($usuarioId);
            $planos       = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
            $fornecedores = $this->fornecedorModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);

            View::render('contas_pagar/dda_index', [
                '_layout'      => 'erp',
                'title'        => 'Pagamento DDA',
                'breadcrumb'   => ['Financeiro' => '/financeiro/pagar', 0 => 'Pagamento DDA'],
                'boletos'      => $boletos,
                'contagens'    => $contagens,
                'filtros'      => $filtros,
                'planos'       => $planos,
                'fornecedores' => $fornecedores,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[DDA] Erro ao listar: ' . $e->getMessage());
            header('Location: /financeiro/pagar?error=dda');
            exit();
        }
    }

    public function ddaSincronizar(): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = (int)Auth::user()->id;
            $asaas     = $this->getAsaasService();
            $params    = ['limit' => 100, 'offset' => 0];
            $dueDateStart = $_POST['dueDateStart'] ?? '';
            $dueDateEnd   = $_POST['dueDateEnd'] ?? '';
            if ($dueDateStart) $params['dueDateStart'] = $dueDateStart;
            if ($dueDateEnd)   $params['dueDateEnd']   = $dueDateEnd;

            $response    = $asaas->listarDdaBoletos($params);
            $items       = $response['data'] ?? [];
            $novos       = 0;
            $atualizados = 0;

            foreach ($items as $item) {
                $dados     = AsaasService::normalizarDdaItem($item, $usuarioId);
                $existente = $this->ddaModel->findByAsaasId($dados['asaas_id'], $usuarioId);
                $this->ddaModel->upsert($dados);
                $existente ? $atualizados++ : $novos++;
            }

            echo json_encode([
                'success'     => true,
                'total'       => count($items),
                'novos'       => $novos,
                'atualizados' => $atualizados,
                'message'     => "Sincronizado: {$novos} novos, {$atualizados} atualizados.",
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[DDA] Erro ao sincronizar: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function ddaDetalhar($id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = (int)Auth::user()->id;
            $boleto    = $this->ddaModel->findById((int)$id);
            if (!$boleto || (int)$boleto->usuario_id !== $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Boleto não encontrado.']);
                return;
            }
            echo json_encode(['success' => true, 'boleto' => $boleto]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function ddaImportar($id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = (int)Auth::user()->id;
            $boleto    = $this->ddaModel->findById((int)$id);
            if (!$boleto || (int)$boleto->usuario_id !== $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Boleto não encontrado.']);
                return;
            }
            if ($boleto->status_interno !== DdaBoleto::STATUS_PENDENTE) {
                echo json_encode(['success' => false, 'message' => 'Este boleto já foi importado ou não pode ser importado.']);
                return;
            }

            $descricao    = trim($_POST['descricao'] ?? $boleto->descricao ?? 'Boleto DDA');
            $planoId      = (int)($_POST['plano_id'] ?? 0);
            $fornecedorId = (int)($_POST['fornecedor_id'] ?? 0);
            $observacao   = trim($_POST['observacao'] ?? '');

            $contaPagarId = $this->model->create([
                'usuario_id'      => $usuarioId,
                'descricao'       => $descricao,
                'valor'           => $boleto->valor_final,
                'data_vencimento' => $boleto->data_vencimento,
                'status'          => 'aberta',
                'plano_conta_id'  => $planoId ?: null,
                'fornecedor_id'   => $fornecedorId ?: null,
                'observacoes'     => $observacao ?: ('Importado via DDA. Beneficiário: ' . $boleto->beneficiario_nome),
                'codigo_barras'   => $boleto->linha_digitavel ?? $boleto->codigo_barras ?? null,
            ]);

            $this->ddaModel->marcarImportado((int)$boleto->id, $contaPagarId);

            echo json_encode([
                'success'        => true,
                'conta_pagar_id' => $contaPagarId,
                'message'        => 'Boleto importado para Contas a Pagar com sucesso!',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[DDA] Erro ao importar: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function ddaPagar($id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = (int)Auth::user()->id;
            $boleto    = $this->ddaModel->findById((int)$id);
            if (!$boleto || (int)$boleto->usuario_id !== $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Boleto não encontrado.']);
                return;
            }

            $pagoPor       = $_POST['pago_por'] ?? 'inlaudo';
            $dataPagamento = $_POST['data_pagamento'] ?? date('Y-m-d');

            if ($pagoPor === 'asaas') {
                $asaas = $this->getAsaasService();
                $asaas->pagarDdaBoleto($boleto->asaas_id);
            }

            $this->ddaModel->confirmarPagamento((int)$boleto->id, $pagoPor, $dataPagamento);

            if ($boleto->conta_pagar_id) {
                $this->model->update((int)$boleto->conta_pagar_id, [
                    'status'         => 'paga',
                    'data_pagamento' => $dataPagamento,
                ]);
            }

            $label = $pagoPor === 'asaas' ? 'Asaas' : 'InLaudo';
            echo json_encode(['success' => true, 'message' => "Pagamento confirmado via {$label}!"]);
        } catch (\Exception $e) {
            $this->logger->error('[DDA] Erro ao confirmar pagamento: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function ddaIgnorar($id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = (int)Auth::user()->id;
            $boleto    = $this->ddaModel->findById((int)$id);
            if (!$boleto || (int)$boleto->usuario_id !== $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Boleto não encontrado.']);
                return;
            }
            $this->ddaModel->ignorar((int)$boleto->id);
            echo json_encode(['success' => true, 'message' => 'Boleto ignorado.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
