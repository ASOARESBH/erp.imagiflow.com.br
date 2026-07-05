<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\NotaFiscal;
use App\Models\NotaFiscalAnexo;
use App\Models\NotaFiscalImportacao;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\ConfigNfs;
use App\Services\AsaasService;

class NotasFiscaisController extends Controller
{
    private NotaFiscal $model;
    private NotaFiscalAnexo $anexoModel;
    private NotaFiscalImportacao $importModel;
    private Cliente $clienteModel;
    private Logger $logger;
    private ?AsaasService $asaasService = null;

    public function __construct()
    {
        $this->model        = new NotaFiscal();
        $this->anexoModel   = new NotaFiscalAnexo();
        $this->importModel  = new NotaFiscalImportacao();
        $this->clienteModel = new Cliente();
        $this->logger       = new Logger();
    }

    private function getAsaasService(): AsaasService
    {
        if ($this->asaasService === null) {
            $authUser  = Auth::user();
            $usuarioId = $authUser ? (int)$authUser->id : 0;
            $apiKey    = null;
            $env       = null;
            if ($usuarioId > 0) {
                $integracaoModel = new \App\Models\Integracao();
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

    // ---------------------------------------------------------------
    // GET /faturamento/notas-fiscais/show/{id}
    // Visualiza detalhes da NF com PDF/XML do Asaas e histórico de erros
    // ---------------------------------------------------------------
    public function show($id): void
    {
        $usuarioId = Auth::user()->id;
        $nota      = $this->model->findById((int)$id);

        if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
            header('Location: /faturamento/notas-fiscais?error=not_found');
            exit();
        }

        $anexos = $this->anexoModel->findByNotaId((int)$id, $usuarioId);

        View::render('notas_fiscais/show', [
            '_layout'    => 'erp',
            'title'      => 'NF ' . ($nota->numero_nf ? '#' . $nota->numero_nf : '#' . $id),
            'breadcrumb' => [
                'Faturamento'    => '/faturamento/notas-fiscais',
                'Notas Fiscais'  => '/faturamento/notas-fiscais',
                0                => 'Visualizar NF',
            ],
            'nota'   => $nota,
            'anexos' => $anexos,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /faturamento/notas-fiscais/consultar-asaas/{id}
    // Consulta o status atual da NF no Asaas e atualiza o banco
    // Retorna JSON: success, asaas_status, pdf_url, xml_url, error_desc
    // ---------------------------------------------------------------
    public function consultarAsaas($id): void
    {
        ob_start();
        try {
            $usuarioId = Auth::user()->id;
            $nota      = $this->model->findById((int)$id);

            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                ob_end_clean();
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'NF não encontrada.']);
                exit();
            }

            if (empty($nota->asaas_invoice_id)) {
                ob_end_clean();
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Esta NF não possui ID Asaas vinculado.']);
                exit();
            }

            $asaas   = $this->getAsaasService();
            $invoice = $asaas->consultarNotaFiscal((string)$nota->asaas_invoice_id);

            $asaasStatus = (string)($invoice['status'] ?? '');
            $pdfUrl      = $invoice['pdfUrl']      ?? $invoice['invoiceUrl'] ?? null;
            $xmlUrl      = $invoice['xmlUrl']       ?? null;
            $numero      = $invoice['number']       ?? null;
            $errorDesc   = $invoice['observations'] ?? null;

            $updateData = [];
            if ($asaasStatus !== '') {
                $updateData['asaas_status'] = $asaasStatus;
                $updateData['status']       = AsaasService::mapearStatusNfsParaBanco($asaasStatus);
            }
            if ($pdfUrl) {
                $updateData['asaas_pdf_url'] = $pdfUrl;
            }
            if ($xmlUrl) {
                $updateData['asaas_xml_url'] = $xmlUrl;
            }
            if ($numero !== null && $numero !== '' && empty($nota->numero_nf)) {
                $updateData['numero_nf'] = (string)$numero;
            }
            $updateData['asaas_error_desc'] = $errorDesc;

            if (!empty($updateData)) {
                $this->model->update((int)$id, $updateData);
            }

            AuditLogger::log('asaas_consultar_nf', [
                'nota_id'      => (int)$id,
                'invoice_id'   => $nota->asaas_invoice_id,
                'asaas_status' => $asaasStatus,
            ]);

            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'           => true,
                'asaas_status'      => $asaasStatus,
                'asaas_status_label'=> AsaasService::mapearStatusNfs($asaasStatus),
                'status'            => $updateData['status'] ?? $nota->status,
                'pdf_url'           => $pdfUrl,
                'xml_url'           => $xmlUrl,
                'numero_nf'         => $numero ?: $nota->numero_nf,
                'error_desc'        => $errorDesc,
                'message'           => 'Status atualizado com sucesso.',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao consultar NF no Asaas: ' . $e->getMessage(), ['nota_id' => $id]);
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao consultar Asaas: ' . $e->getMessage(),
            ]);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /faturamento/notas-fiscais/reemitir-asaas/{id}
    // Recria a NF no Asaas (erro_emissao → nova emissão com mesmo payload)
    // Retorna JSON: success, asaas_invoice_id, asaas_status, asaas_status_label, message
    // ---------------------------------------------------------------
    public function reemitirAsaas($id): void
    {
        ob_start();
        try {
            $usuarioId = Auth::user()->id;
            $nota      = $this->model->findById((int)$id);

            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                ob_end_clean();
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'NF não encontrada.']);
                exit();
            }

            if (($nota->origem_emissao ?? 'manual') !== 'asaas') {
                ob_end_clean();
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Apenas NFs originadas no Asaas podem ser reemitidas por este fluxo.']);
                exit();
            }

            $isErroLocal  = ($nota->status ?? '') === 'erro_emissao';
            $isErroAsaas  = strtoupper((string)($nota->asaas_status ?? '')) === 'ERROR';

            if (!$isErroLocal && !$isErroAsaas) {
                ob_end_clean();
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Apenas NFs com erro de emissão no Asaas podem ser reemitidas.']);
                exit();
            }

            // Carrega configurações de NFS-e do tenant
            $configNfsModel = new ConfigNfs();
            $configNfs      = $configNfsModel->findByUsuarioId($usuarioId);

            $asaas       = $this->getAsaasService();
            $dataHoje    = date('Y-m-d');
            $valor       = (float)($nota->valor_total ?? 0);
            $descricao   = $nota->servico_descricao ?? ($configNfs->service_description ?? 'SERVIÇOS PRESTADOS');

            // Monta taxes
            $taxes = ['retainIss' => (bool)($configNfs->retain_iss ?? false)];
            foreach (['iss' => 'iss_aliquota', 'pis' => 'pis_aliquota', 'cofins' => 'cofins_aliquota',
                      'csll' => 'csll_aliquota', 'inss' => 'inss_aliquota', 'ir' => 'ir_aliquota'] as $k => $field) {
                $v = (float)($configNfs->$field ?? 0);
                if ($v > 0) { $taxes[$k] = $v; }
            }

            // Payload base
            $payload = [
                'serviceDescription'   => $configNfs->service_description ?? $descricao,
                'observations'         => $configNfs->observations
                                         ?? ('NF-s reemitida — Referência: ' . $descricao),
                'value'                => $valor,
                'deductions'           => (float)($configNfs->deductions ?? 0),
                'effectiveDate'        => $dataHoje,
                'municipalServiceName' => $configNfs->municipal_service_name ?? 'Serviços de Saúde / Radiologia',
                'taxes'                => $taxes,
                'externalReference'    => 'reemissao|nf:' . (int)$id . '|u:' . $usuarioId,
            ];

            // Código de serviço municipal (prioridade: ID > code armazenado na NF > config)
            if (!empty($nota->servico_id_asaas)) {
                $payload['municipalServiceId'] = $nota->servico_id_asaas;
            } elseif (!empty($nota->servico_codigo)) {
                $payload['municipalServiceCode'] = $nota->servico_codigo;
            } elseif (!empty($configNfs->municipal_service_id)) {
                $payload['municipalServiceId'] = $configNfs->municipal_service_id;
            } elseif (!empty($configNfs->municipal_service_code)) {
                $payload['municipalServiceCode'] = $configNfs->municipal_service_code;
            }

            // CNAE
            if (!empty($configNfs->cnae)) {
                $payload['cnae'] = preg_replace('/\D/', '', (string)$configNfs->cnae);
            }

            // Série da NF
            if (!empty($nota->serie)) {
                $payload['serie'] = $nota->serie;
            } elseif (!empty($configNfs->serie_nf)) {
                $payload['serie'] = $configNfs->serie_nf;
            }

            // Vínculo: cobrança Asaas (payment) → prioridade máxima
            $asaasPaymentId = null;
            if (!empty($nota->conta_receber_id)) {
                $crModel = new ContaReceber();
                $cr      = $crModel->findById((int)$nota->conta_receber_id);
                if ($cr && !empty($cr->asaas_payment_id)) {
                    $asaasPaymentId = $cr->asaas_payment_id;
                }
            }

            if ($asaasPaymentId) {
                $payload['payment'] = $asaasPaymentId;
            } else {
                // Tenta localizar o customer no Asaas pelo CPF/CNPJ do cliente
                $cliente = $this->clienteModel->findById((int)$nota->cliente_id);
                if ($cliente && !empty($cliente->cpf_cnpj)) {
                    $doc = preg_replace('/\D/', '', (string)$cliente->cpf_cnpj);
                    if ($doc !== '') {
                        $clienteAsaas = $asaas->buscarCliente($doc);
                        if (!empty($clienteAsaas['id'])) {
                            $payload['customer'] = $clienteAsaas['id'];
                        }
                    }
                }
            }

            // Cria nova NF no Asaas
            $response       = $asaas->agendarNotaFiscal($payload);
            $novoInvoiceId  = $response['id']     ?? null;
            $asaasStatus    = $response['status']  ?? 'SCHEDULED';
            $pdfUrl         = $response['pdfUrl']  ?? $response['invoiceUrl'] ?? null;
            $numeroNf       = $response['number']  ?? null;
            $statusBanco    = AsaasService::mapearStatusNfsParaBanco($asaasStatus);

            $this->model->update((int)$id, [
                'asaas_invoice_id'  => $novoInvoiceId,
                'asaas_status'      => $asaasStatus,
                'asaas_error_desc'  => null,
                'status'            => $statusBanco,
                'asaas_pdf_url'     => $pdfUrl,
                'numero_nf'         => $numeroNf ? (string)$numeroNf : ($nota->numero_nf ?? ''),
                'data_emissao'      => $dataHoje,
            ]);

            AuditLogger::log('reemitir_nf_asaas', [
                'nota_id'           => (int)$id,
                'novo_invoice_id'   => $novoInvoiceId,
                'asaas_status'      => $asaasStatus,
            ]);

            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'           => true,
                'asaas_invoice_id'  => $novoInvoiceId,
                'asaas_status'      => $asaasStatus,
                'asaas_status_label'=> AsaasService::mapearStatusNfs($asaasStatus),
                'message'           => 'NF reemitida com sucesso no Asaas.',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao reemitir NF no Asaas: ' . $e->getMessage(), ['nota_id' => $id]);
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao reemitir: ' . $e->getMessage(),
            ]);
        }
        exit();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $filtros = [
                'status' => $_GET['status'] ?? '',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $notas = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('notas_fiscais/index', [
                '_layout' => 'erp',
                'title' => 'Notas Fiscais',
                'breadcrumb' => [
                    'Faturamento' => '/faturamento/notas-fiscais',
                    0 => 'Notas Fiscais',
                ],
                'notas' => $notas,
                'filtros' => $filtros,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar notas fiscais: ' . $e->getMessage());
            View::render('notas_fiscais/index', [
                '_layout' => 'erp',
                'title' => 'Notas Fiscais',
                'breadcrumb' => ['Faturamento' => '/faturamento/notas-fiscais', 0 => 'Notas Fiscais'],
                'notas' => [],
                'filtros' => [],
                'erro_carregamento' => 'Não foi possível carregar as notas fiscais. Verifique se as migrations foram executadas.',
            ]);
        }
    }

    public function create(): void
    {
        $usuarioId = Auth::user()->id;
        $clientes  = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);
        $configNfs = (new ConfigNfs())->findByUsuarioId($usuarioId);

        View::render('notas_fiscais/form-enterprise', [
            '_layout'   => 'erp',
            'title'     => 'Nova Nota Fiscal',
            'breadcrumb'=> [
                'Faturamento'   => '/faturamento/notas-fiscais',
                'Notas Fiscais' => '/faturamento/notas-fiscais',
                0               => 'Nova Nota',
            ],
            'nota'      => null,
            'clientes'  => $clientes,
            'configNfs' => $configNfs,
            'anexos'    => [],
            'tab'       => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $clienteId       = (int)($_POST['cliente_id'] ?? 0);
            $valorTotal      = trim($_POST['valor_total'] ?? '');
            $dataEmissao     = $_POST['data_emissao'] ?? '';
            $emitirViaAsaas  = !empty($_POST['emitir_via_asaas']);

            // Validações básicas
            if ($clienteId <= 0 || $valorTotal === '' || $dataEmissao === '') {
                header('Location: /faturamento/notas-fiscais/create?error=missing_fields');
                exit();
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || (int)$cliente->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais/create?error=invalid_cliente');
                exit();
            }

            $numeroNf = trim($_POST['numero_nf'] ?? '');
            $serie    = trim($_POST['serie'] ?? '');

            // Para emissão manual, número e série são obrigatórios
            if (!$emitirViaAsaas && ($numeroNf === '' || $serie === '')) {
                header('Location: /faturamento/notas-fiscais/create?error=missing_fields_manual');
                exit();
            }

            // ── Dados base da NF ─────────────────────────────────────────────
            $dados = [
                'usuario_id'       => $usuarioId,
                'cliente_id'       => $clienteId,
                'numero_nf'        => $numeroNf,
                'serie'            => $serie,
                'valor_total'      => $valorTotal,
                'data_emissao'     => $dataEmissao,
                'status'           => $emitirViaAsaas ? 'pendente' : ($_POST['status'] ?? 'rascunho'),
                'origem_emissao'   => $emitirViaAsaas ? 'asaas' : 'manual',
                'servico_descricao'=> trim($_POST['servico_descricao'] ?? '') ?: null,
                'servico_codigo'   => trim($_POST['servico_codigo'] ?? '') ?: null,
                'observacoes_nf'   => trim($_POST['observacoes_nf'] ?? '') ?: null,
                'conta_receber_id' => !empty($_POST['conta_receber_id']) ? (int)$_POST['conta_receber_id'] : null,
            ];

            $id = $this->model->create($dados);
            if (!$id) {
                header('Location: /faturamento/notas-fiscais/create?error=db_failure');
                exit();
            }

            AuditLogger::log('create_nota_fiscal', [
                'id'          => $id,
                'numero_nf'   => $numeroNf,
                'serie'       => $serie,
                'via_asaas'   => $emitirViaAsaas,
            ]);

            // ── Emissão via Asaas ─────────────────────────────────────────────
            if ($emitirViaAsaas) {
                try {
                    $this->emitirNfViaAsaas((int)$id, $usuarioId, $cliente, $dados);
                    header("Location: /faturamento/notas-fiscais/show/{$id}?success=emitida_asaas");
                } catch (\Throwable $eAsaas) {
                    $this->logger->error('[store] Falha ao emitir NF via Asaas: ' . $eAsaas->getMessage(), ['nota_id' => $id]);
                    // Salva o erro no registro e redireciona para show com aviso
                    $this->model->update((int)$id, [
                        'status'           => 'erro_emissao',
                        'asaas_error_desc' => $eAsaas->getMessage(),
                    ]);
                    header("Location: /faturamento/notas-fiscais/show/{$id}?error=asaas_falhou");
                }
            } else {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?success=created");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar nota fiscal: ' . $e->getMessage());
            header('Location: /faturamento/notas-fiscais/create?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // Emite a NF via Asaas e atualiza o registro no banco
    // Lança \Throwable em caso de falha para o caller tratar
    // ---------------------------------------------------------------
    private function emitirNfViaAsaas(int $notaId, int $usuarioId, object $cliente, array $dados): void
    {
        // Verificar se o Asaas está configurado para este tenant
        $integracaoModel = new \App\Models\Integracao();
        $integracao = $integracaoModel->findByProvider('asaas', $usuarioId);
        if (!$integracao || empty($integracao->api_key)) {
            throw new \RuntimeException('Asaas não configurado para este tenant. Configure a integração em Configurações → Integrações.');
        }

        $asaas     = $this->getAsaasService();
        $configNfs = (new ConfigNfs())->findByUsuarioId($usuarioId);
        $valor     = (float)($dados['valor_total'] ?? 0);
        $dataHoje  = $dados['data_emissao'] ?? date('Y-m-d');
        $descricao = $dados['servico_descricao'] ?? ($configNfs->service_description ?? 'PRESTAÇÃO DE SERVIÇOS');
        $customerId = null;

        // ── 1. Sincronizar cliente no Asaas ──────────────────────────────────
        try {
            $doc = preg_replace('/\D/', '', (string)($cliente->cpf_cnpj ?? ''));
            if ($doc !== '') {
                $asaasCliente = $asaas->buscarCliente($doc, $cliente->email ?? null);
                $clienteData  = [
                    'name'    => $cliente->razao_social ?: ($cliente->nome_fantasia ?? $cliente->nome ?? ''),
                    'email'   => $cliente->email ?? '',
                    'phone'   => AsaasService::formatarTelefone(
                                    $cliente->telefone ?? null,
                                    $cliente->celular  ?? null
                                ),
                    'cpfCnpj' => $doc,
                ];
                $cep = preg_replace('/\D/', '', (string)($cliente->cep ?? ''));
                if (strlen($cep) === 8) {
                    $clienteData['postalCode']    = $cep;
                    $clienteData['address']       = trim((string)($cliente->endereco ?? ''));
                    $clienteData['addressNumber'] = trim((string)($cliente->numero ?? '')) ?: 'S/N';
                    $clienteData['complement']    = trim((string)($cliente->complemento ?? ''));
                    $clienteData['province']      = trim((string)($cliente->bairro ?? ''));
                    $clienteData['city']          = trim((string)($cliente->cidade ?? ''));
                    $clienteData['state']         = strtoupper(trim((string)($cliente->estado ?? '')));
                }
                if ($asaasCliente && !empty($asaasCliente['id'])) {
                    $customerId = $asaasCliente['id'];
                    $asaas->atualizarCliente($customerId, $clienteData);
                } else {
                    $novoCliente = $asaas->criarCliente($clienteData);
                    $customerId  = $novoCliente['id'] ?? null;
                }
            }
        } catch (\Throwable $eSyncCliente) {
            $this->logger->warning('[emitirNfViaAsaas] Falha ao sincronizar cliente no Asaas', [
                'error'      => $eSyncCliente->getMessage(),
                'nota_id'    => $notaId,
                'cliente_id' => $cliente->id ?? null,
            ]);
        }

        // ── 2. Montar payload ─────────────────────────────────────────────────
        $taxes = ['retainIss' => (bool)($configNfs->retain_iss ?? false)];
        foreach (['iss', 'pis', 'cofins', 'csll', 'inss', 'ir'] as $tributo) {
            $aliquota = (float)($configNfs->{$tributo . '_aliquota'} ?? 0);
            if ($aliquota > 0) $taxes[$tributo] = $aliquota;
        }

        $payload = [
            'serviceDescription'   => $descricao,
            'observations'         => $dados['observacoes_nf'] ?? ($configNfs->observations ?? ('NF-s avulsa. Ref: ' . $descricao)),
            'value'                => $valor,
            'deductions'           => (float)($_POST['deducoes'] ?? $configNfs->deductions ?? 0),
            'effectiveDate'        => $dataHoje,
            'municipalServiceName' => $configNfs->municipal_service_name ?? 'Serviços Prestados',
            'taxes'                => $taxes,
            'externalReference'    => 'nf:' . $notaId . '|u:' . $usuarioId,
        ];

        // Código de serviço municipal
        $servicoCodigo = $dados['servico_codigo'] ?? null;
        if (!empty($servicoCodigo)) {
            $payload['municipalServiceCode'] = $servicoCodigo;
        } elseif (!empty($configNfs->municipal_service_id)) {
            $payload['municipalServiceId'] = $configNfs->municipal_service_id;
        } elseif (!empty($configNfs->municipal_service_code)) {
            $payload['municipalServiceCode'] = $configNfs->municipal_service_code;
        }

        // CNAE
        if (!empty($configNfs->cnae)) {
            $payload['cnae'] = preg_replace('/\D/', '', (string)$configNfs->cnae);
        }

        // NBS
        if (!empty($configNfs->nbs_codigo)) {
            $payload['nbs'] = $configNfs->nbs_codigo;
        }

        // Série
        $serie = $dados['serie'] ?? ($configNfs->serie_nf ?? null);
        if (!empty($serie)) {
            $payload['serie'] = $serie;
        }

        // Vincular ao payment Asaas (se conta a receber informada)
        $asaasPaymentId = null;
        $contaReceberId = !empty($dados['conta_receber_id']) ? (int)$dados['conta_receber_id'] : null;
        if ($contaReceberId) {
            $crModel = new ContaReceber();
            $cr      = $crModel->findById($contaReceberId);
            if ($cr && !empty($cr->asaas_payment_id)) {
                $asaasPaymentId = $cr->asaas_payment_id;
            }
        }

        if ($asaasPaymentId) {
            $payload['payment'] = $asaasPaymentId;
        } elseif (!empty($customerId)) {
            $payload['customer'] = $customerId;
        }

        // ── 3. Enviar ao Asaas ────────────────────────────────────────────────
        $this->logger->info('[emitirNfViaAsaas] Enviando NF ao Asaas', [
            'nota_id' => $notaId,
            'payload' => array_diff_key($payload, ['taxes' => '']),
        ]);

        $response       = $asaas->agendarNotaFiscal($payload);
        $asaasInvoiceId = $response['id']     ?? null;
        $asaasStatus    = $response['status']  ?? 'SCHEDULED';
        $pdfUrl         = $response['pdfUrl']  ?? $response['invoiceUrl'] ?? null;
        $xmlUrl         = $response['xmlUrl']  ?? null;
        $numeroNf       = $response['number']  ?? '';
        $statusBanco    = AsaasService::mapearStatusNfsParaBanco($asaasStatus);

        // ── 4. Atualizar registro no banco ────────────────────────────────────
        $this->model->update($notaId, [
            'asaas_invoice_id'  => $asaasInvoiceId,
            'asaas_status'      => $asaasStatus,
            'asaas_pdf_url'     => $pdfUrl,
            'asaas_xml_url'     => $xmlUrl,
            'status'            => $statusBanco,
            'numero_nf'         => $numeroNf ? (string)$numeroNf : '',
            'servico_codigo'    => $servicoCodigo ?? ($configNfs->municipal_service_code ?? null),
            'servico_id_asaas'  => $configNfs->municipal_service_id ?? null,
        ]);

        AuditLogger::log('emitir_nf_asaas', [
            'nota_id'          => $notaId,
            'asaas_invoice_id' => $asaasInvoiceId,
            'asaas_status'     => $asaasStatus,
        ]);

        $this->logger->info('[emitirNfViaAsaas] NF enviada com sucesso', [
            'nota_id'          => $notaId,
            'asaas_invoice_id' => $asaasInvoiceId,
            'status'           => $statusBanco,
        ]);
    }

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $nota = $this->model->findById((int)$id);

        if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
            header('Location: /faturamento/notas-fiscais?error=not_found');
            exit();
        }

        $clientes  = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);
        $anexos    = $this->anexoModel->findByNotaId((int)$id, $usuarioId);
        $configNfs = (new ConfigNfs())->findByUsuarioId($usuarioId);

        View::render('notas_fiscais/form-enterprise', [
            '_layout'   => 'erp',
            'title'     => 'Editar Nota Fiscal',
            'breadcrumb'=> [
                'Faturamento'   => '/faturamento/notas-fiscais',
                'Notas Fiscais' => '/faturamento/notas-fiscais',
                0               => 'Editar NF',
            ],
            'nota'      => $nota,
            'clientes'  => $clientes,
            'configNfs' => $configNfs,
            'anexos'    => $anexos,
            'tab'       => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $nota = $this->model->findById((int)$id);

            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais?error=unauthorized');
                exit();
            }

            $clienteId   = (int)($_POST['cliente_id'] ?? 0);
            $numeroNf    = trim($_POST['numero_nf'] ?? '');
            $serie       = trim($_POST['serie'] ?? '');
            $valorTotal  = trim($_POST['valor_total'] ?? '');
            $dataEmissao = $_POST['data_emissao'] ?? '';

            if ($clienteId <= 0 || $numeroNf === '' || $serie === '' || $valorTotal === '' || $dataEmissao === '') {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?error=missing_fields");
                exit();
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || (int)$cliente->usuario_id !== (int)$usuarioId) {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?error=invalid_cliente");
                exit();
            }

            $dados = [
                'cliente_id'  => $clienteId,
                'numero_nf'   => $numeroNf,
                'serie'       => $serie,
                'valor_total' => $valorTotal,
                'data_emissao'=> $dataEmissao,
                'status'      => $_POST['status'] ?? ($nota->status ?? 'rascunho'),
            ];

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_nota_fiscal', ['id' => (int)$id, 'numero_nf' => $numeroNf, 'serie' => $serie]);
                header("Location: /faturamento/notas-fiscais/edit/{$id}?success=updated");
            } else {
                header("Location: /faturamento/notas-fiscais/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar nota fiscal: ' . $e->getMessage());
            header("Location: /faturamento/notas-fiscais/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $nota = $this->model->findById((int)$id);

            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais?error=unauthorized');
                exit();
            }

            if ($this->model->cancel((int)$id)) {
                AuditLogger::log('delete_nota_fiscal', ['id' => (int)$id, 'numero_nf' => $nota->numero_nf ?? null, 'serie' => $nota->serie ?? null]);
                header('Location: /faturamento/notas-fiscais?success=deleted');
            } else {
                header('Location: /faturamento/notas-fiscais?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao cancelar nota fiscal: ' . $e->getMessage());
            header('Location: /faturamento/notas-fiscais?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /faturamento/notas-fiscais/anexos/upload
    // Upload de anexo (PDF, XML, JPG) — máximo 10 MB
    // ---------------------------------------------------------------
    public function uploadAnexo(): void
    {
        $notaId = 0;
        try {
            $usuarioId = Auth::user()->id;
            $notaId    = (int)($_POST['nota_fiscal_id'] ?? 0);

            // LOG DETALHADO PARA DEBUG
            $this->logger->info('uploadAnexo_NF_debug', [
                'usuario_id'         => $usuarioId,
                'nota_fiscal_id_raw' => $_POST['nota_fiscal_id'] ?? 'NAO_ENVIADO',
                'nota_id_parsed'     => $notaId,
                'files_error'        => $_FILES['anexo']['error'] ?? 'SEM_ARQUIVO',
                'files_size'         => $_FILES['anexo']['size'] ?? 0,
                'files_name'         => $_FILES['anexo']['name'] ?? 'SEM_NOME',
                'post_keys'          => implode(',', array_keys($_POST)),
            ]);

            if ($notaId <= 0) {
                $this->logger->error('uploadAnexo_NF: nota_fiscal_id invalido');
                header('Location: /faturamento/notas-fiscais?error=invalid_nota');
                exit();
            }

            // Verifica se a nota pertence ao tenant
            $nota = $this->model->findById($notaId);
            $this->logger->info('uploadAnexo_NF_findById', [
                'nota_encontrada'    => $nota ? 'SIM' : 'NAO',
                'nota_usuario_id'    => $nota ? $nota->usuario_id : 'N/A',
                'usuario_id_sessao'  => $usuarioId,
                'match'              => $nota ? ((int)$nota->usuario_id === (int)$usuarioId ? 'SIM' : 'NAO') : 'N/A',
            ]);
            if (!$nota || (int)$nota->usuario_id !== (int)$usuarioId) {
                $this->logger->error('uploadAnexo_NF: unauthorized', ['nota_id' => $notaId, 'usuario_id' => $usuarioId]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=unauthorized&tab=anexos");
                exit();
            }

            if (!isset($_FILES['anexo']) || $_FILES['anexo']['error'] !== UPLOAD_ERR_OK) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=upload_failed&tab=anexos");
                exit();
            }

            $file    = $_FILES['anexo'];
            $maxSize = 10 * 1024 * 1024; // 10 MB

            if (($file['size'] ?? 0) > $maxSize) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=file_too_large&tab=anexos");
                exit();
            }

            $tmpPath = $file['tmp_name'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($tmpPath) ?: '';

            $allowed = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/jpg'       => 'jpg',
                'text/xml'        => 'xml',
                'application/xml' => 'xml',
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

            $ext = $allowed[$mime] ?? null;
            if ($ext === null) {
                $origName = (string) ($file['name'] ?? '');
                $origExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (in_array($origExt, $excelExts, true)) {
                    if (in_array($mime, $excelFallbackMimes, true) ||
                        str_starts_with($mime, 'application/vnd.ms-excel') ||
                        str_starts_with($mime, 'application/vnd.openxmlformats')) {
                        $ext = $origExt;
                    }
                }
            }

            if ($ext === null) {
                $this->logger->warning('uploadAnexo_NF: invalid_file_type', [
                    'nota_fiscal_id' => $notaId,
                    'mime' => $mime,
                    'original_name' => $file['name'] ?? '',
                ]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=invalid_file_type&tab=anexos");
                exit();
            }

            $baseDir = BASE_PATH . '/storage/uploads/notas_fiscais_anexos/' . $usuarioId . '/' . $notaId;
            if (!is_dir($baseDir)) {
                if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                    $this->logger->error('Falha ao criar diretório de upload (notas_fiscais_anexos): ' . $baseDir . ' | BASE_PATH=' . BASE_PATH);
                    header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=upload_failed&tab=anexos");
                    exit();
                }
            }

            $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpPath, $destPath)) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=upload_failed&tab=anexos");
                exit();
            }

            $relativePath = 'storage/uploads/notas_fiscais_anexos/' . $usuarioId . '/' . $notaId . '/' . $safeName;

            $anexoId = $this->anexoModel->create([
                'usuario_id'     => $usuarioId,
                'nota_fiscal_id' => $notaId,
                'file_path'      => $relativePath,
                'original_name'  => $file['name'] ?? 'anexo',
                'mime_type'      => $mime,
                'file_size'      => $file['size'] ?? null,
            ]);

            if ($anexoId) {
                AuditLogger::log('upload_nota_fiscal_anexo', ['id' => $anexoId, 'nota_fiscal_id' => $notaId]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?success=upload&tab=anexos");
            } else {
                @unlink($destPath);
                $this->logger->error('Falha ao salvar anexo no banco (nota_fiscal_id=' . $notaId . ')');
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=db_failure&tab=anexos");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar anexo (notas fiscais): ' . $e->getMessage());
            if ($notaId > 0) {
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=fatal&tab=anexos");
            } else {
                header('Location: /faturamento/notas-fiscais?error=fatal');
            }
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /faturamento/notas-fiscais/anexos/delete/{id}
    // ---------------------------------------------------------------
    public function deleteAnexo($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $anexo     = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                header('Location: /faturamento/notas-fiscais?error=unauthorized');
                exit();
            }

            $notaId  = (int)($anexo->nota_fiscal_id ?? 0);
            $filePath = BASE_PATH . '/' . ltrim((string)($anexo->file_path ?? ''), '/');

            if ($this->anexoModel->delete((int)$id)) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                AuditLogger::log('delete_nota_fiscal_anexo', ['id' => (int)$id, 'nota_fiscal_id' => $notaId]);
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?success=deleted_anexo&tab=anexos");
            } else {
                $this->logger->error('Falha ao excluir anexo do banco (id=' . $id . ')');
                header("Location: /faturamento/notas-fiscais/edit/{$notaId}?error=db_failure&tab=anexos");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao remover anexo (notas fiscais): ' . $e->getMessage());
            header('Location: /faturamento/notas-fiscais?error=fatal');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /faturamento/notas-fiscais/anexos/download/{id}
    // ---------------------------------------------------------------
    public function downloadAnexo($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $anexo     = $this->anexoModel->findById((int)$id);

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
            $this->logger->error('Erro ao baixar anexo (notas fiscais): ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro ao baixar arquivo';
            exit();
        }
    }

    public function importForm(): void
    {
        View::render('notas_fiscais/importar', [
            '_layout' => 'erp',
            'title' => 'Importar XML de NF-e',
            'breadcrumb' => [
                'Faturamento' => '/faturamento/notas-fiscais',
                'Notas Fiscais' => '/faturamento/notas-fiscais',
                0 => 'Importar XML',
            ],
        ]);
    }

    public function importStore(): void
    {
        $usuarioId = Auth::user()->id;

        $importId = null;
        $destRel  = null;
        $notaId   = null;

        try {
            if (!isset($_FILES['xml']) || $_FILES['xml']['error'] !== UPLOAD_ERR_OK) {
                header('Location: /faturamento/notas-fiscais/importar?error=upload_failed');
                exit();
            }

            $file    = $_FILES['xml'];
            $maxSize = 5 * 1024 * 1024;
            if (($file['size'] ?? 0) > $maxSize) {
                header('Location: /faturamento/notas-fiscais/importar?error=file_too_large');
                exit();
            }

            $tmpPath = $file['tmp_name'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($tmpPath) ?: '';

            $allowedMimes = [
                'text/xml',
                'application/xml',
                'application/octet-stream',
            ];

            if (!in_array($mime, $allowedMimes, true)) {
                header('Location: /faturamento/notas-fiscais/importar?error=invalid_file_type');
                exit();
            }

            $baseDir = BASE_PATH . '/storage/uploads/notas_fiscais_importacoes/' . $usuarioId;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            $safeName = bin2hex(random_bytes(16)) . '.xml';
            $destAbs  = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpPath, $destAbs)) {
                header('Location: /faturamento/notas-fiscais/importar?error=upload_failed');
                exit();
            }

            $destRel = 'storage/uploads/notas_fiscais_importacoes/' . $usuarioId . '/' . $safeName;

            $importId = $this->importModel->create([
                'usuario_id'       => $usuarioId,
                'arquivo_xml_path' => $destRel,
                'status'           => 'falha',
                'mensagem'         => 'Processando',
            ]);

            $parsed = $this->parseNfeXml($destAbs);

            $doc = preg_replace('/\D/', '', (string)($parsed['documento'] ?? ''));
            if ($doc === '') {
                throw new \RuntimeException('Documento do destinatário não encontrado no XML.');
            }

            $cliente = $this->clienteModel->findByCpfCnpjAndUsuarioId($doc, $usuarioId);
            if (!$cliente) {
                throw new \RuntimeException('Cliente não encontrado para o CPF/CNPJ ' . $doc . '.');
            }

            $dados = [
                'usuario_id'   => $usuarioId,
                'cliente_id'   => (int)$cliente->id,
                'numero_nf'    => $parsed['numero_nf'] ?? '',
                'serie'        => $parsed['serie'] ?? '',
                'valor_total'  => $parsed['valor_total'] ?? '0.00',
                'data_emissao' => $parsed['data_emissao'] ?? '',
                'status'       => 'importada',
                'xml_path'     => $destRel,
            ];

            if (trim($dados['numero_nf']) === '' || trim($dados['serie']) === '' || trim($dados['data_emissao']) === '') {
                throw new \RuntimeException('XML inválido: não foi possível extrair número, série ou data de emissão.');
            }

            $notaId = $this->model->create($dados);
            if (!$notaId) {
                throw new \RuntimeException('Falha ao salvar a Nota Fiscal no banco de dados.');
            }

            if ($importId) {
                $this->importModel->updateStatus((int)$importId, 'sucesso', 'NF importada com sucesso (ID ' . $notaId . ').');
            }

            AuditLogger::log('import_nota_fiscal', [
                'import_id'      => $importId,
                'nota_fiscal_id' => $notaId,
                'cliente_id'     => (int)$cliente->id,
            ]);

            header("Location: /faturamento/notas-fiscais/edit/{$notaId}?success=imported");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao importar XML de nota fiscal: ' . $e->getMessage());

            if ($importId) {
                $this->importModel->updateStatus((int)$importId, 'falha', $e->getMessage());
            }

            if ($destRel) {
                AuditLogger::log('import_nota_fiscal_failed', ['import_id' => $importId, 'xml_path' => $destRel, 'error' => $e->getMessage()]);
            } else {
                AuditLogger::log('import_nota_fiscal_failed', ['import_id' => $importId, 'error' => $e->getMessage()]);
            }

            header('Location: /faturamento/notas-fiscais/importar?error=import_failed');
            exit();
        }
    }

    private function parseNfeXml(string $absPath): array
    {
        $doc = new \DOMDocument();
        $old = libxml_use_internal_errors(true);
        $loaded = $doc->load($absPath);
        libxml_clear_errors();
        libxml_use_internal_errors($old);

        if (!$loaded) {
            throw new \RuntimeException('Não foi possível ler o XML.');
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

        $numero  = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:nNF)'));
        $serie   = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:serie)'));
        $dhEmi   = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:dhEmi)'));
        $dEmi    = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:ide/nfe:dEmi)'));
        $data    = $dhEmi !== '' ? substr($dhEmi, 0, 10) : ($dEmi !== '' ? $dEmi : '');
        $valor   = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:total/nfe:ICMSTot/nfe:vNF)'));
        $docDest = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:dest/nfe:CNPJ)'));
        if ($docDest === '') {
            $docDest = trim((string)$xpath->evaluate('string(//nfe:infNFe/nfe:dest/nfe:CPF)'));
        }

        if ($numero === '') {
            $numero = trim((string)$xpath->evaluate("string(//*[local-name()='nNF'])"));
        }
        if ($serie === '') {
            $serie = trim((string)$xpath->evaluate("string(//*[local-name()='serie'])"));
        }
        if ($data === '') {
            $alt = trim((string)$xpath->evaluate("string(//*[local-name()='dhEmi'])"));
            if ($alt !== '') {
                $data = substr($alt, 0, 10);
            } else {
                $data = trim((string)$xpath->evaluate("string(//*[local-name()='dEmi'])"));
            }
        }
        if ($valor === '') {
            $valor = trim((string)$xpath->evaluate("string(//*[local-name()='vNF'])"));
        }
        if ($docDest === '') {
            $docDest = trim((string)$xpath->evaluate("string(//*[local-name()='dest']/*[local-name()='CNPJ'])"));
            if ($docDest === '') {
                $docDest = trim((string)$xpath->evaluate("string(//*[local-name()='dest']/*[local-name()='CPF'])"));
            }
        }

        $valor = str_replace(',', '.', $valor);
        if ($valor === '') {
            $valor = '0.00';
        }

        return [
            'numero_nf'    => $numero,
            'serie'        => $serie,
            'data_emissao' => $data,
            'valor_total'  => $valor,
            'documento'    => $docDest,
        ];
    }
}
