<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\RdvViagem;
use App\Models\RdvRota;
use App\Models\RdvDespesa;
use App\Models\RdvCategoria;
use App\Models\RdvHistorico;
use App\Models\RdvOcrLog;
use App\Models\ContaPagar;
use App\Models\User;

class RdvController extends Controller
{
    private RdvViagem   $viagemModel;
    private RdvRota     $rotaModel;
    private RdvDespesa  $despesaModel;
    private RdvCategoria $categoriaModel;
    private RdvHistorico $historicoModel;
    private RdvOcrLog   $ocrLogModel;
    private ContaPagar  $contaPagarModel;
    private User        $userModel;
    private Logger      $logger;

    public function __construct()
    {
        $this->viagemModel    = new RdvViagem();
        $this->rotaModel      = new RdvRota();
        $this->despesaModel   = new RdvDespesa();
        $this->categoriaModel = new RdvCategoria();
        $this->historicoModel = new RdvHistorico();
        $this->ocrLogModel    = new RdvOcrLog();
        $this->contaPagarModel = new ContaPagar();
        $this->userModel      = new User();
        $this->logger         = new Logger();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    private function uid(): int  { return (int)($_SESSION['user_id']   ?? 0); }
    private function uNome(): string { return $_SESSION['user_name'] ?? 'Sistema'; }
    private function isAdmin(): bool
    {
        return in_array(strtolower($_SESSION['user_role'] ?? ''), ['admin','superadmin'], true);
    }
    private function jsonOk(array $data = []): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit();
    }
    private function jsonErr(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit();
    }
    private function redirect(string $url): void
    {
        header("Location: {$url}");
        exit();
    }
    private function toFloat(string $v): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $v);
    }

    // =========================================================================
    // INDEX — Lista de viagens
    // =========================================================================
    public function index(): void
    {
        $filtros = [
            'q'              => trim($_GET['q']              ?? ''),
            'status'         => trim($_GET['status']         ?? ''),
            'rota_id'        => (int)($_GET['rota_id']       ?? 0),
            'periodo_inicio' => trim($_GET['periodo_inicio'] ?? ''),
            'periodo_fim'    => trim($_GET['periodo_fim']    ?? ''),
            'vendedor_id'    => (int)($_GET['vendedor_id']   ?? 0),
        ];

        $viagens = $this->viagemModel->listar($this->uid(), $this->isAdmin(), $filtros);
        $kpis    = $this->viagemModel->kpis($this->uid(), $this->isAdmin());
        $rotas   = $this->rotaModel->listarParaSelect($this->uid(), $this->isAdmin());
        $users   = $this->isAdmin() ? $this->userModel->findAll() : [];

        View::render('rdv.index', [
            '_layout' => 'erp',
            'title'   => 'RDV — Viagens',
            'viagens' => $viagens,
            'kpis'    => $kpis,
            'rotas'   => $rotas,
            'users'   => $users,
            'filtros' => $filtros,
        ]);
    }

    // =========================================================================
    // CREATE — Formulário nova viagem
    // =========================================================================
    public function create(): void
    {
        $rotas      = $this->rotaModel->listarParaSelect($this->uid(), $this->isAdmin());
        $categorias = $this->categoriaModel->listar($this->uid());

        View::render('rdv.form', [
            '_layout'    => 'erp',
            'title'      => 'Nova Viagem',
            'viagem'     => null,
            'rotas'      => $rotas,
            'categorias' => $categorias,
        ]);
    }

    // =========================================================================
    // STORE — Salvar nova viagem
    // =========================================================================
    public function store(): void
    {
        $nome  = trim($_POST['nome'] ?? '');
        $pIni  = trim($_POST['periodo_inicio'] ?? '');
        $pFim  = trim($_POST['periodo_fim']    ?? '');

        if (!$nome || !$pIni || !$pFim) {
            $_SESSION['flash_error'] = 'Preencha nome, período início e período fim.';
            $this->redirect('/rdv/viagens/create');
        }

        if ($pFim < $pIni) {
            $_SESSION['flash_error'] = 'Período fim não pode ser anterior ao início.';
            $this->redirect('/rdv/viagens/create');
        }

        $codigo = $this->viagemModel->gerarCodigo($this->uid());

        // Nome automático se vazio: "Registro de Despesas + período"
        if ($nome === '') {
            $nome = 'Registro de Despesas ' . date('d/m/Y', strtotime($pIni))
                  . ' até ' . date('d/m/Y', strtotime($pFim));
        }

        $id = $this->viagemModel->create([
            'usuario_id'     => $this->uid(),
            'rota_id'        => $_POST['rota_id']        ?? null,
            'codigo'         => $codigo,
            'nome'           => $nome,
            'periodo_inicio' => $pIni,
            'periodo_fim'    => $pFim,
            'motivo'         => $_POST['motivo']         ?? null,
            'cidade'         => $_POST['cidade']         ?? null,
            'estado'         => $_POST['estado']         ?? null,
            'pais'           => $_POST['pais']           ?? 'Brasil',
            'valor_previsto' => $this->toFloat($_POST['valor_previsto'] ?? '0'),
            'observacoes'    => $_POST['observacoes']    ?? null,
        ]);

        if (!$id) {
            $_SESSION['flash_error'] = 'Erro ao criar viagem. Tente novamente.';
            $this->redirect('/rdv/viagens/create');
        }

        $this->historicoModel->add($id, $this->uid(), $this->uNome(), 'criacao', "Viagem {$codigo} criada.");
        $_SESSION['flash_success'] = "Viagem {$codigo} criada com sucesso!";
        $this->redirect("/rdv/viagens/{$id}");
    }

    // =========================================================================
    // SHOW — Detalhes da viagem (com abas)
    // =========================================================================
    public function show(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $_SESSION['flash_error'] = 'Viagem não encontrada.';
            $this->redirect('/rdv/viagens');
        }

        // Permissão: admin ou dono
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $_SESSION['flash_error'] = 'Acesso negado.';
            $this->redirect('/rdv/viagens');
        }

        $despesas          = $this->despesaModel->listarPorViagem($id);
        $resumoCategoria   = $this->despesaModel->resumoPorCategoria($id);
        $resumoForma       = $this->despesaModel->resumoPorForma($id);
        $resumoDia         = $this->despesaModel->resumoPorDia($id);
        $historico         = $this->historicoModel->listar($id);
        $categorias        = $this->categoriaModel->listar($this->uid());
        $rotaClientes      = $viagem->rota_id ? $this->rotaModel->getClientes((int)$viagem->rota_id) : [];

        // Formas de pagamento
        $formas = $this->_getFormas();

        View::render('rdv.show', [
            '_layout'        => 'erp',
            'title'          => "RDV — {$viagem->codigo}",
            'viagem'         => $viagem,
            'despesas'       => $despesas,
            'resumoCategoria'=> $resumoCategoria,
            'resumoForma'    => $resumoForma,
            'resumoDia'      => $resumoDia,
            'historico'      => $historico,
            'categorias'     => $categorias,
            'formas'         => $formas,
            'rotaClientes'   => $rotaClientes,
            'isAdmin'        => $this->isAdmin(),
            'aba'            => $_GET['aba'] ?? 'resumo',
        ]);
    }

    // =========================================================================
    // EDIT — Formulário de edição
    // =========================================================================
    public function edit(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->redirect('/rdv/viagens');
        }
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $this->redirect('/rdv/viagens');
        }

        $rotas = $this->rotaModel->listarParaSelect($this->uid(), $this->isAdmin());

        View::render('rdv.form', [
            '_layout' => 'erp',
            'title'   => "Editar Viagem — {$viagem->codigo}",
            'viagem'  => $viagem,
            'rotas'   => $rotas,
        ]);
    }

    // =========================================================================
    // UPDATE — Salvar edição
    // =========================================================================
    public function update(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->redirect('/rdv/viagens');
        }
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $this->redirect('/rdv/viagens');
        }

        $nome = trim($_POST['nome'] ?? '');
        $pIni = trim($_POST['periodo_inicio'] ?? '');
        $pFim = trim($_POST['periodo_fim']    ?? '');

        if (!$nome || !$pIni || !$pFim) {
            $_SESSION['flash_error'] = 'Preencha nome, período início e período fim.';
            $this->redirect("/rdv/viagens/{$id}/edit");
        }

        $ok = $this->viagemModel->update($id, [
            'usuario_id'     => $this->uid(),
            'rota_id'        => $_POST['rota_id']        ?? null,
            'nome'           => $nome,
            'periodo_inicio' => $pIni,
            'periodo_fim'    => $pFim,
            'motivo'         => $_POST['motivo']         ?? null,
            'cidade'         => $_POST['cidade']         ?? null,
            'estado'         => $_POST['estado']         ?? null,
            'pais'           => $_POST['pais']           ?? 'Brasil',
            'valor_previsto' => $this->toFloat($_POST['valor_previsto'] ?? '0'),
            'observacoes'    => $_POST['observacoes']    ?? null,
        ]);

        if ($ok) {
            $this->historicoModel->add($id, $this->uid(), $this->uNome(), 'edicao', 'Dados da viagem atualizados.');
            $_SESSION['flash_success'] = 'Viagem atualizada com sucesso!';
        } else {
            $_SESSION['flash_error'] = 'Erro ao atualizar viagem.';
        }
        $this->redirect("/rdv/viagens/{$id}");
    }

    // =========================================================================
    // MUDAR STATUS (AJAX)
    // =========================================================================
    public function mudarStatus(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $this->jsonErr('Acesso negado.');
        }

        $novoStatus = trim($_POST['status'] ?? '');
        $statusValidos = ['aberto', 'iniciado', 'concluido', 'cancelado'];
        if (!in_array($novoStatus, $statusValidos, true)) {
            $this->jsonErr('Status inválido.');
        }

        $ok = $this->viagemModel->updateStatus($id, $novoStatus);
        if ($ok) {
            $this->historicoModel->add(
                $id, $this->uid(), $this->uNome(),
                'status_change',
                "Status alterado de '{$viagem->status}' para '{$novoStatus}'."
            );
            $this->jsonOk(['status' => $novoStatus]);
        }
        $this->jsonErr('Erro ao atualizar status.');
    }

    // =========================================================================
    // REGISTRAR DESPESA (AJAX)
    // =========================================================================
    public function addDespesa(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $this->jsonErr('Acesso negado.');
        }
        if ($viagem->status === 'cancelado') {
            $this->jsonErr('Não é possível adicionar despesas a uma viagem cancelada.');
        }

        $descricao = trim($_POST['descricao'] ?? '');
        $valor     = $this->toFloat($_POST['valor'] ?? '0');
        if (!$descricao || $valor <= 0) {
            $this->jsonErr('Descrição e valor são obrigatórios.');
        }

        // Processar comprovante: reaproveita upload já feito durante o fluxo de
        // OCR (arquivo_path) ou salva um novo arquivo enviado agora.
        $arquivoPath = null;
        $arquivoPathPost = trim($_POST['arquivo_path'] ?? '');
        if ($arquivoPathPost && preg_match('#^/uploads/rdv/' . $id . '/[A-Za-z0-9_.]+\.(jpg|jpeg|png|gif|webp|pdf)$#i', $arquivoPathPost)) {
            $arquivoPath = $arquivoPathPost;
        } elseif (!empty($_FILES['arquivo']['tmp_name'])) {
            $arquivoPath = $this->_salvarComprovante($_FILES['arquivo'], $id);
        }

        $ocrJson   = $_POST['ocr_json']   ?? null;
        $ocrStatus = $_POST['ocr_status'] ?? null;
        if ($ocrStatus && !in_array($ocrStatus, ['pendente', 'processando', 'concluido', 'erro'], true)) {
            $ocrStatus = null;
        }

        // Validar período
        $dataDoc    = $_POST['data_documento'] ?? null;
        $foraPeriodo = 0;
        $logFora     = null;
        if ($dataDoc) {
            if ($dataDoc < $viagem->periodo_inicio || $dataDoc > $viagem->periodo_fim) {
                $foraPeriodo = 1;
                $logFora     = "Despesa registrada em {$dataDoc} fora do período {$viagem->periodo_inicio} a {$viagem->periodo_fim}. Confirmado pelo usuário.";
            }
        }

        // Classificação automática de categoria
        $categoriaId = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        if (!$categoriaId && $descricao) {
            $cats = $this->categoriaModel->listar($this->uid());
            $categoriaId = RdvCategoria::classificarPorTexto($descricao, $cats);
        }

        $despesaId = $this->despesaModel->create([
            'viagem_id'          => $id,
            'categoria_id'       => $categoriaId,
            'forma_pagamento_id' => $_POST['forma_pagamento_id'] ?? null,
            'descricao'          => $descricao,
            'valor'              => $valor,
            'data_documento'     => $dataDoc,
            'numero_documento'   => $_POST['numero_documento']  ?? null,
            'fornecedor'         => $_POST['fornecedor']        ?? null,
            'cnpj_fornecedor'    => $_POST['cnpj_fornecedor']   ?? null,
            'cidade'             => $_POST['cidade']            ?? null,
            'hora_documento'     => $_POST['hora_documento']    ?? null,
            'arquivo'            => $arquivoPath,
            'ocr_json'           => $ocrJson,
            'ocr_status'         => $ocrStatus,
            'fora_periodo'       => $foraPeriodo,
            'log_fora_periodo'   => $logFora,
            'tipo'               => $_POST['tipo']              ?? 'simples',
        ]);

        if (!$despesaId) {
            $this->jsonErr('Erro ao registrar despesa.');
        }

        // Recalcular valor real da viagem
        $this->viagemModel->recalcularValorReal($id);

        // Se viagem estava aberta, muda para iniciada
        if ($viagem->status === 'aberto') {
            $this->viagemModel->updateStatus($id, 'iniciado');
            $this->historicoModel->add($id, $this->uid(), $this->uNome(), 'status_change', 'Viagem iniciada com o registro da primeira despesa.');
        }

        $this->historicoModel->add(
            $id, $this->uid(), $this->uNome(),
            'despesa_add',
            "Despesa registrada: {$descricao} — R$ " . number_format($valor, 2, ',', '.'),
            ['despesa_id' => $despesaId, 'valor' => $valor]
        );

        // Buscar resumo atualizado
        $resumo = $this->despesaModel->resumoPorCategoria($id);
        $viagem = $this->viagemModel->findById($id);

        $this->jsonOk([
            'despesa_id'  => $despesaId,
            'valor_real'  => number_format((float)$viagem->valor_real, 2, ',', '.'),
            'fora_periodo'=> $foraPeriodo,
            'resumo'      => $resumo,
        ]);
    }

    // =========================================================================
    // EDITAR DESPESA (AJAX)
    // =========================================================================
    public function editDespesa(int $id, int $despesaId): void
    {
        $viagem  = $this->viagemModel->findById($id);
        $despesa = $this->despesaModel->findById($despesaId);
        if (!$viagem || !$despesa || (int)$despesa->viagem_id !== $id) {
            $this->jsonErr('Despesa não encontrada.');
        }
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $this->jsonErr('Acesso negado.');
        }

        $ok = $this->despesaModel->update($despesaId, [
            'viagem_id'          => $id,
            'categoria_id'       => $_POST['categoria_id']       ?? null,
            'forma_pagamento_id' => $_POST['forma_pagamento_id'] ?? null,
            'descricao'          => trim($_POST['descricao']     ?? ''),
            'valor'              => $this->toFloat($_POST['valor'] ?? '0'),
            'data_documento'     => $_POST['data_documento']     ?? null,
            'numero_documento'   => $_POST['numero_documento']   ?? null,
            'fornecedor'         => $_POST['fornecedor']         ?? null,
            'cnpj_fornecedor'    => $_POST['cnpj_fornecedor']    ?? null,
            'cidade'             => $_POST['cidade']             ?? null,
            'hora_documento'     => $_POST['hora_documento']     ?? null,
        ]);

        if ($ok) {
            $this->viagemModel->recalcularValorReal($id);
            $this->jsonOk();
        }
        $this->jsonErr('Erro ao atualizar despesa.');
    }

    // =========================================================================
    // EXCLUIR DESPESA (AJAX)
    // =========================================================================
    public function deleteDespesa(int $id, int $despesaId): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }
        if (!$this->isAdmin() && (int)$viagem->usuario_id !== $this->uid()) {
            $this->jsonErr('Acesso negado.');
        }

        $ok = $this->despesaModel->delete($despesaId, $id);
        if ($ok) {
            $this->viagemModel->recalcularValorReal($id);
            $this->historicoModel->add($id, $this->uid(), $this->uNome(), 'despesa_del', "Despesa #{$despesaId} excluída.");
            $viagem = $this->viagemModel->findById($id);
            $this->jsonOk(['valor_real' => number_format((float)$viagem->valor_real, 2, ',', '.')]);
        }
        $this->jsonErr('Erro ao excluir despesa.');
    }

    // =========================================================================
    // APROVAÇÃO (AJAX — apenas admin)
    // =========================================================================
    public function aprovar(int $id): void
    {
        if (!$this->isAdmin()) {
            $this->jsonErr('Apenas administradores podem aprovar viagens.');
        }

        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }

        $status = trim($_POST['status'] ?? '');
        if (!in_array($status, ['aprovado', 'reprovado'], true)) {
            $this->jsonErr('Status de aprovação inválido.');
        }

        $obs = trim($_POST['observacao'] ?? '');

        $ok = $this->viagemModel->updateAprovacao($id, $status, $this->uid());
        if ($ok) {
            // Registrar na tabela de aprovações
            try {
                $pdo = \App\Core\Database::getInstance();
                $pdo->prepare(
                    "INSERT INTO rdv_aprovacoes (viagem_id, usuario_id, status, observacao)
                     VALUES (:vid, :uid, :s, :obs)"
                )->execute([':vid' => $id, ':uid' => $this->uid(), ':s' => $status, ':obs' => $obs ?: null]);
            } catch (\Throwable $e) {
                $this->logger->error('[RdvController::aprovar] ' . $e->getMessage());
            }

            $this->historicoModel->add(
                $id, $this->uid(), $this->uNome(),
                'aprovacao',
                "Viagem {$status} pelo aprovador. " . ($obs ? "Obs: {$obs}" : '')
            );
            $this->jsonOk(['aprovacao_status' => $status]);
        }
        $this->jsonErr('Erro ao registrar aprovação.');
    }

    // =========================================================================
    // INTEGRAÇÃO FINANCEIRA — Gerar conta a pagar (AJAX)
    // =========================================================================
    public function gerarContaPagar(int $id): void
    {
        if (!$this->isAdmin()) {
            $this->jsonErr('Apenas administradores podem gerar contas a pagar.');
        }

        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }
        if ($viagem->aprovacao_status !== 'aprovado') {
            $this->jsonErr('A viagem precisa estar aprovada para gerar conta a pagar.');
        }
        if ($viagem->conta_pagar_id) {
            $this->jsonErr('Conta a pagar já foi gerada para esta viagem.');
        }

        $descricao = "RDV — {$viagem->codigo}: {$viagem->nome}";
        if ($viagem->rota_nome) {
            $descricao .= " | Rota: {$viagem->rota_nome}";
        }

        $cpId = $this->contaPagarModel->create([
            'usuario_id'          => $this->uid(),
            'plano_conta_id'      => null,
            'fornecedor_id'       => null,
            'descricao'           => $descricao,
            'valor'               => $viagem->valor_real,
            'data_vencimento'     => date('Y-m-d'),
            'data_pagamento'      => null,
            'codigo_barras'       => null,
            'recorrente'          => 0,
            'recorrencia_tipo'    => null,
            'recorrencia_intervalo' => null,
            'status'              => 'pendente',
            'observacoes'         => "Gerado automaticamente pelo módulo RDV. Vendedor: {$viagem->vendedor_nome}. Período: {$viagem->periodo_inicio} a {$viagem->periodo_fim}.",
        ]);

        if (!$cpId) {
            $this->jsonErr('Erro ao criar conta a pagar.');
        }

        $this->viagemModel->vincularContaPagar($id, (int)$cpId);
        $this->historicoModel->add(
            $id, $this->uid(), $this->uNome(),
            'financeiro',
            "Conta a pagar #{$cpId} gerada no financeiro. Valor: R$ " . number_format((float)$viagem->valor_real, 2, ',', '.')
        );

        $this->jsonOk(['conta_pagar_id' => $cpId]);
    }

    // =========================================================================
    // OCR — Upload rápido do comprovante (sem processar OCR)
    // =========================================================================
    // Chamado imediatamente ao selecionar o arquivo, em paralelo ao OCR
    // client-side (Tesseract.js), para garantir que o comprovante fique salvo
    // e possa ser anexado à despesa mesmo que todo o pipeline de OCR falhe.
    public function uploadComprovante(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }
        if (empty($_FILES['arquivo']['tmp_name'])) {
            $this->jsonErr('Nenhum arquivo enviado.');
        }

        $arquivoPath = $this->_salvarComprovante($_FILES['arquivo'], $id);
        if (!$arquivoPath) {
            $this->jsonErr('Erro ao salvar arquivo. Extensões permitidas: jpg, jpeg, png, gif, webp, pdf.');
        }

        $this->jsonOk(['arquivo' => $arquivoPath]);
    }

    // =========================================================================
    // OCR — Pipeline de fallback server-side (AJAX)
    // =========================================================================
    // Executado pelo frontend somente quando o OCR client-side (Tesseract.js)
    // não obtém confiança/campos suficientes. Tenta, em ordem configurável
    // (RDV_OCR_ENGINES), os motores disponíveis — só retorna erro ao chamador
    // quando TODOS os motores (cliente + servidor) falharem.
    public function processarOcr(int $id): void
    {
        $viagem = $this->viagemModel->findById($id);
        if (!$viagem) {
            $this->jsonErr('Viagem não encontrada.');
        }

        // Resolve o arquivo: reaproveita upload já feito (arquivo_path) ou
        // salva um novo (compatibilidade com chamada direta em um único passo).
        $arquivoPath = trim($_POST['arquivo_path'] ?? '');
        if ($arquivoPath) {
            if (!preg_match('#^/uploads/rdv/' . $id . '/[A-Za-z0-9_.]+\.(jpg|jpeg|png|gif|webp|pdf)$#i', $arquivoPath)) {
                $this->jsonErr('Caminho de arquivo inválido.');
            }
        } elseif (!empty($_FILES['arquivo']['tmp_name'])) {
            $arquivoPath = $this->_salvarComprovante($_FILES['arquivo'], $id);
            if (!$arquivoPath) {
                $this->jsonErr('Erro ao salvar arquivo.');
            }
        } else {
            $this->jsonErr('Nenhum arquivo enviado.');
        }

        $fullPath = __DIR__ . '/../../public' . $arquivoPath;
        if (!file_exists($fullPath)) {
            $this->jsonErr('Arquivo não encontrado no servidor.');
        }
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

        // Tentativas já realizadas no cliente (ex.: Tesseract.js)
        $tentativas = [];
        $decoded = json_decode($_POST['tentativas_cliente'] ?? '', true);
        if (is_array($decoded)) {
            foreach ($decoded as $t) {
                $tentativas[] = [
                    'engine'    => $t['engine']    ?? 'desconhecido',
                    'sucesso'   => !empty($t['sucesso']),
                    'confianca' => $t['confianca'] ?? null,
                    'tempo_ms'  => $t['tempo_ms']  ?? null,
                    'erro'      => $t['erro']      ?? null,
                    'origem'    => 'cliente',
                ];
            }
        }

        $ocrFinal = null;

        // Se o cliente já obteve resultado satisfatório (Tesseract.js), não
        // repete o processamento no servidor — apenas registra o log.
        $pularEngines = !empty($_POST['pular_engines']);
        if (!$pularEngines) {
            $ordem = array_filter(array_map('trim', explode(',', getenv('RDV_OCR_ENGINES') ?: 'ocrspace,openai')));
            foreach ($ordem as $engine) {
                $resultado = match ($engine) {
                    'ocrspace' => $this->_executarOcrSpace($fullPath, $mimeType),
                    'openai'   => $this->_executarOcrOpenAI($fullPath),
                    default    => null,
                };
                if (!$resultado) {
                    continue;
                }

                $tentativas[] = [
                    'engine'    => $resultado['engine'],
                    'sucesso'   => $resultado['sucesso'],
                    'confianca' => $resultado['confianca'] ?? null,
                    'tempo_ms'  => $resultado['tempo_ms']  ?? null,
                    'erro'      => $resultado['erro']      ?? null,
                    'origem'    => 'servidor',
                ];

                if ($resultado['sucesso']) {
                    $ocrFinal = $resultado;
                    break;
                }
            }
        }

        // Se o cliente pediu para pular os motores do servidor porque já teve
        // sucesso (Tesseract.js), usa o resultado enviado por ele como final.
        if (!$ocrFinal && $pularEngines) {
            $ocrCliente = json_decode($_POST['ocr_cliente'] ?? '', true);
            if (is_array($ocrCliente)) {
                $ocrFinal = $ocrCliente;
            }
        }

        // ETAPA 15 — Logs: registra todas as tentativas (cliente + servidor)
        foreach ($tentativas as $t) {
            try {
                $this->ocrLogModel->registrar([
                    'viagem_id'  => $id,
                    'usuario_id' => $this->uid(),
                    'arquivo'    => $arquivoPath,
                    'engine'     => $t['engine'],
                    'sucesso'    => $t['sucesso'] ? 1 : 0,
                    'confianca'  => $t['confianca'],
                    'tempo_ms'   => $t['tempo_ms'],
                    'erro'       => $t['erro'],
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('[RdvController::processarOcr] Falha ao registrar log de OCR: ' . $e->getMessage());
            }
        }

        if (!$ocrFinal) {
            $motivos = array_map(fn ($t) => "{$t['engine']}: " . ($t['erro'] ?: 'falhou'), $tentativas);
            $this->jsonOk([
                'arquivo'    => $arquivoPath,
                'ocr'        => ['erro' => 'Todos os mecanismos de OCR falharam. ' . implode(' | ', $motivos)],
                'tentativas' => $tentativas,
            ]);
        }

        $this->jsonOk([
            'arquivo'    => $arquivoPath,
            'ocr'        => $ocrFinal,
            'tentativas' => $tentativas,
        ]);
    }

    // =========================================================================
    // PRIVADOS — Helpers internos
    // =========================================================================
    private function _salvarComprovante(array $file, int $viagemId): string|false
    {
        $dir = __DIR__ . '/../../public/uploads/rdv/' . $viagemId . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            return false;
        }

        $filename = uniqid('comp_', true) . '.' . $ext;
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return false;
        }

        return '/uploads/rdv/' . $viagemId . '/' . $filename;
    }

    // ─── Motor 1 (fallback servidor): OCR.space — gratuito, requer chave grátis ─
    // Obtenha uma chave gratuita (25.000 requisições/mês) em https://ocr.space/ocrapi/freekey
    private function _executarOcrSpace(string $fullPath, string $mimeType): array
    {
        $t0 = microtime(true);
        $apiKey = getenv('OCR_SPACE_API_KEY');
        if (!$apiKey) {
            return ['engine' => 'ocrspace', 'sucesso' => false, 'erro' => 'OCR_SPACE_API_KEY não configurada.', 'tempo_ms' => 0];
        }

        try {
            $ch = curl_init('https://apipro1.ocr.space/parse/image');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'apikey'    => $apiKey,
                    'file'      => new \CURLFile($fullPath, $mimeType, basename($fullPath)),
                    'language'  => 'por',
                    'OCREngine' => '2',
                    'scale'     => 'true',
                    'isTable'   => 'false',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
            $resp    = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);
            $tempoMs = (int) round((microtime(true) - $t0) * 1000);

            if ($curlErr) {
                return ['engine' => 'ocrspace', 'sucesso' => false, 'erro' => "Erro de conexão: {$curlErr}", 'tempo_ms' => $tempoMs];
            }

            $data = json_decode((string) $resp, true);
            if (!$data || !empty($data['IsErroredOnProcessing'])) {
                $msgRaw = $data['ErrorMessage'] ?? null;
                $msg = is_array($msgRaw) ? implode('; ', $msgRaw) : ($msgRaw ?: 'Resposta inválida do OCR.space.');
                return ['engine' => 'ocrspace', 'sucesso' => false, 'erro' => $msg, 'tempo_ms' => $tempoMs];
            }

            $texto = trim($data['ParsedResults'][0]['ParsedText'] ?? '');
            if ($texto === '') {
                return ['engine' => 'ocrspace', 'sucesso' => false, 'erro' => 'Nenhum texto reconhecido.', 'tempo_ms' => $tempoMs];
            }

            // OCR.space (engine 2) não expõe confiança numérica na resposta —
            // estimamos heuristicamente pelo volume de texto reconhecido.
            $confianca = min(90, 40 + strlen(preg_replace('/\s+/', '', $texto)) * 0.3);

            $campos = $this->_extrairCamposTexto($texto);
            if (empty($campos['valor']) && empty($campos['data'])) {
                return ['engine' => 'ocrspace', 'sucesso' => false, 'erro' => 'Nenhum campo relevante (data/valor) identificado no texto.', 'tempo_ms' => $tempoMs, 'texto' => $texto];
            }

            return array_merge($campos, [
                'engine'    => 'ocrspace',
                'sucesso'   => true,
                'texto'     => $texto,
                'confianca' => round($confianca, 1),
                'tempo_ms'  => $tempoMs,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvController::_executarOcrSpace] ' . $e->getMessage());
            return ['engine' => 'ocrspace', 'sucesso' => false, 'erro' => $e->getMessage(), 'tempo_ms' => (int) round((microtime(true) - $t0) * 1000)];
        }
    }

    // ─── Motor 2 (fallback servidor, opcional/pago): OpenAI Vision ──────────────
    private function _executarOcrOpenAI(string $fullPath): array
    {
        $t0 = microtime(true);
        $apiKey  = getenv('OPENAI_API_KEY');
        $apiBase = getenv('OPENAI_API_BASE') ?: 'https://api.openai.com/v1';

        if (!$apiKey) {
            return ['engine' => 'openai', 'sucesso' => false, 'erro' => 'OPENAI_API_KEY não configurada.', 'tempo_ms' => 0];
        }

        try {
            $imageData = base64_encode(file_get_contents($fullPath));
            $mimeType  = mime_content_type($fullPath) ?: 'image/jpeg';

            $payload = [
                'model'    => 'gpt-4o-mini',
                'messages' => [[
                    'role'    => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analise este comprovante/nota fiscal e extraia em JSON: data (YYYY-MM-DD), hora (HH:MM), valor (número decimal), fornecedor (nome), cnpj (apenas números), cpf (apenas números, se houver), cidade, forma_pagamento (Dinheiro/PIX/Cartão Crédito/Cartão Débito/Transferência/Boleto), categoria_sugerida (Alimentação/Combustível/Hospedagem/Uber/Táxi/Pedágio/Passagem Aérea/Estacionamento/Outros), numero_documento, chave_nfce (44 dígitos, se houver), bandeira_cartao (Visa/Mastercard/Elo/Amex, se houver). Retorne APENAS o JSON, sem explicações.',
                        ],
                        [
                            'type'      => 'image_url',
                            'image_url' => ['url' => "data:{$mimeType};base64,{$imageData}"],
                        ],
                    ],
                ]],
                'max_tokens' => 500,
            ];

            $ch = curl_init("{$apiBase}/chat/completions");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    "Authorization: Bearer {$apiKey}",
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT    => 30,
            ]);
            $resp    = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);
            $tempoMs = (int) round((microtime(true) - $t0) * 1000);

            if ($curlErr) {
                return ['engine' => 'openai', 'sucesso' => false, 'erro' => "Erro de conexão: {$curlErr}", 'tempo_ms' => $tempoMs];
            }

            $data = json_decode((string) $resp, true);
            $text = $data['choices'][0]['message']['content'] ?? '';

            preg_match('/\{.*\}/s', $text, $matches);
            $ocrData = $matches ? json_decode($matches[0], true) : null;

            if (!is_array($ocrData)) {
                $errMsg = $data['error']['message'] ?? 'Não foi possível extrair JSON da resposta do modelo.';
                return ['engine' => 'openai', 'sucesso' => false, 'erro' => $errMsg, 'tempo_ms' => $tempoMs];
            }

            $temCampos = !empty($ocrData['valor']) || !empty($ocrData['data']) || !empty($ocrData['fornecedor']);
            if (!$temCampos) {
                return ['engine' => 'openai', 'sucesso' => false, 'erro' => 'Nenhum campo relevante identificado pelo modelo.', 'tempo_ms' => $tempoMs];
            }

            return array_merge($ocrData, [
                'engine'    => 'openai',
                'sucesso'   => true,
                'confianca' => 88.0, // OpenAI não retorna confiança numérica — valor nominal para modelo Vision
                'tempo_ms'  => $tempoMs,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvController::_executarOcrOpenAI] ' . $e->getMessage());
            return ['engine' => 'openai', 'sucesso' => false, 'erro' => $e->getMessage(), 'tempo_ms' => (int) round((microtime(true) - $t0) * 1000)];
        }
    }

    // ─── Extração inteligente de campos a partir de texto bruto (OCR.space) ────
    private function _extrairCamposTexto(string $texto): array
    {
        $campos = [
            'data' => null, 'hora' => null, 'valor' => null, 'fornecedor' => null,
            'cnpj' => null, 'cpf' => null, 'cidade' => null, 'forma_pagamento' => null,
            'categoria_sugerida' => null, 'numero_documento' => null,
            'chave_nfce' => null, 'bandeira_cartao' => null,
        ];

        if (preg_match('/(\d{2})[\/\-.](\d{2})[\/\-.](\d{2,4})/', $texto, $m)) {
            $ano = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            if (checkdate((int) $m[2], (int) $m[1], (int) $ano)) {
                $campos['data'] = sprintf('%04d-%02d-%02d', (int) $ano, (int) $m[2], (int) $m[1]);
            }
        }

        if (preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?/', $texto, $m)) {
            $campos['hora'] = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        if (preg_match('/total[^\d]{0,20}(\d{1,3}(?:\.\d{3})*,\d{2})/i', $texto, $m)) {
            $campos['valor'] = (float) str_replace(['.', ','], ['', '.'], $m[1]);
        } elseif (preg_match('/R\$\s*(\d{1,3}(?:\.\d{3})*,\d{2})/', $texto, $m)) {
            $campos['valor'] = (float) str_replace(['.', ','], ['', '.'], $m[1]);
        }

        if (preg_match('/\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}/', $texto, $m)) {
            $campos['cnpj'] = preg_replace('/\D/', '', $m[0]);
        }

        if (preg_match('/(?<!\d)\d{3}\.?\d{3}\.?\d{3}-?\d{2}(?!\d)/', $texto, $m)) {
            $cpfDigits = preg_replace('/\D/', '', $m[0]);
            if ($cpfDigits !== $campos['cnpj']) {
                $campos['cpf'] = $cpfDigits;
            }
        }

        if (preg_match('/(?:\d[\s.]?){44}/', $texto, $m)) {
            $chave = preg_replace('/\D/', '', $m[0]);
            if (strlen($chave) === 44) {
                $campos['chave_nfce'] = $chave;
            }
        }

        // Casamento por palavra inteira (\b) — evita falsos positivos como
        // "ELO" dentro de "BELO Horizonte" ao usar apenas str_contains().
        $matchPalavra = static fn (string $texto, string $palavra): bool =>
            (bool) preg_match('/\b' . preg_quote($palavra, '/') . '\b/u', $texto);

        $textoUp = mb_strtoupper($texto);
        $formaMap = [
            'PIX' => 'PIX', 'DINHEIRO' => 'Dinheiro', 'CRÉDITO' => 'Cartão Crédito', 'CREDITO' => 'Cartão Crédito',
            'DÉBITO' => 'Cartão Débito', 'DEBITO' => 'Cartão Débito', 'TRANSFERÊNCIA' => 'Transferência',
            'TRANSFERENCIA' => 'Transferência', 'BOLETO' => 'Boleto',
        ];
        foreach ($formaMap as $k => $v) {
            if ($matchPalavra($textoUp, $k)) { $campos['forma_pagamento'] = $v; break; }
        }

        foreach (['VISA', 'MASTERCARD', 'ELO', 'AMEX', 'HIPERCARD', 'DINERS'] as $bandeira) {
            if ($matchPalavra($textoUp, $bandeira)) { $campos['bandeira_cartao'] = ucfirst(strtolower($bandeira)); break; }
        }

        $catMap = [
            'HOTEL' => 'Hospedagem', 'POUSADA' => 'Hospedagem', 'HOSTEL' => 'Hospedagem', 'UBER' => 'Uber', '99' => '99',
            'CABIFY' => 'Uber', 'IFOOD' => 'Alimentação', 'RESTAURANTE' => 'Alimentação', 'LANCHONETE' => 'Alimentação',
            'PADARIA' => 'Alimentação', 'PIZZARIA' => 'Alimentação', 'CHURRASCARIA' => 'Alimentação', 'POSTO' => 'Combustível',
            'SHELL' => 'Combustível', 'PETROBRAS' => 'Combustível', 'IPIRANGA' => 'Combustível', 'PEDAGIO' => 'Pedágio',
            'PEDÁGIO' => 'Pedágio', 'AUTOPASS' => 'Pedágio', 'AZUL' => 'Passagem Aérea', 'LATAM' => 'Passagem Aérea',
            'GOL' => 'Passagem Aérea', 'AVIANCA' => 'Passagem Aérea', 'PARK' => 'Estacionamento', 'ESTACIONAMENTO' => 'Estacionamento',
            'FARMACIA' => 'Farmácia', 'FARMÁCIA' => 'Farmácia', 'DROGARIA' => 'Farmácia', 'LAVANDERIA' => 'Lavanderia',
            'TAXI' => 'Táxi', 'TÁXI' => 'Táxi',
        ];
        foreach ($catMap as $k => $v) {
            if ($matchPalavra($textoUp, $k)) { $campos['categoria_sugerida'] = $v; break; }
        }

        if (preg_match('/n[ºo°.]?\s*(?:documento|cupom|nf-?e)?\s*[:\-]?\s*(\d{3,12})/i', $texto, $m)) {
            $campos['numero_documento'] = $m[1];
        }

        foreach (preg_split('/\r\n|\r|\n/', $texto) as $linha) {
            $linha = trim($linha);
            if (mb_strlen($linha) >= 4 && preg_match('/[A-Za-zÀ-ú]{3,}/', $linha) && !preg_match('/^\d+$/', $linha)) {
                $campos['fornecedor'] = mb_substr($linha, 0, 100);
                break;
            }
        }

        if (preg_match('/([A-ZÀ-Ú][a-zà-ú]+(?:\s[A-ZÀ-Ú][a-zà-ú]+)*)\s*[\/\-]\s*([A-Z]{2})\b/u', $texto, $m)) {
            $campos['cidade'] = trim($m[1]);
        }

        return $campos;
    }

    private function _getFormas(): array
    {
        try {
            $pdo  = \App\Core\Database::getInstance();
            $stmt = $pdo->query("SELECT * FROM rdv_formas_pagamento WHERE ativo = 1 ORDER BY nome ASC");
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // =========================================================================
    // ROTAS — CRUD de rotas comerciais
    // =========================================================================
    public function rotasIndex(): void
    {
        $rotas = $this->rotaModel->listar($this->uid(), $this->isAdmin());
        View::render('rdv.rotas.index', [
            '_layout' => 'erp',
            'title'   => 'RDV — Rotas Comerciais',
            'rotas'   => $rotas,
        ]);
    }

    public function rotasCreate(): void
    {
        View::render('rdv.rotas.form', [
            '_layout' => 'erp',
            'title'   => 'Nova Rota Comercial',
            'rota'    => null,
        ]);
    }

    public function rotasStore(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        if (!$nome) {
            $_SESSION['flash_error'] = 'Nome da rota é obrigatório.';
            $this->redirect('/rdv/rotas/create');
        }

        $id = $this->rotaModel->create([
            'usuario_id' => $this->uid(),
            'nome'       => $nome,
            'descricao'  => $_POST['descricao'] ?? null,
            'tipo'       => $_POST['tipo']      ?? 'padrao',
            'regiao'     => $_POST['regiao']    ?? null,
            'estado'     => $_POST['estado']    ?? null,
        ]);

        if ($id) {
            $_SESSION['flash_success'] = 'Rota criada com sucesso!';
            $this->redirect("/rdv/rotas/{$id}");
        }
        $_SESSION['flash_error'] = 'Erro ao criar rota.';
        $this->redirect('/rdv/rotas/create');
    }

    public function rotasShow(int $id): void
    {
        $rota     = $this->rotaModel->findById($id);
        if (!$rota) {
            $this->redirect('/rdv/rotas');
        }
        $clientes = $this->rotaModel->getClientes($id);

        // Buscar clientes e leads para o select de adição
        $todosClientes = $this->_getClientes();
        $todosLeads    = $this->_getLeads();

        View::render('rdv.rotas.show', [
            '_layout'       => 'erp',
            'title'         => "Rota — {$rota->nome}",
            'rota'          => $rota,
            'clientes'      => $clientes,
            'todosClientes' => $todosClientes,
            'todosLeads'    => $todosLeads,
        ]);
    }

    public function rotasEdit(int $id): void
    {
        $rota = $this->rotaModel->findById($id);
        if (!$rota) {
            $this->redirect('/rdv/rotas');
        }
        View::render('rdv.rotas.form', [
            '_layout' => 'erp',
            'title'   => "Editar Rota — {$rota->nome}",
            'rota'    => $rota,
        ]);
    }

    public function rotasUpdate(int $id): void
    {
        $nome = trim($_POST['nome'] ?? '');
        if (!$nome) {
            $_SESSION['flash_error'] = 'Nome da rota é obrigatório.';
            $this->redirect("/rdv/rotas/{$id}/edit");
        }
        $ok = $this->rotaModel->update($id, [
            'nome'      => $nome,
            'descricao' => $_POST['descricao'] ?? null,
            'tipo'      => $_POST['tipo']      ?? 'padrao',
            'regiao'    => $_POST['regiao']    ?? null,
            'estado'    => $_POST['estado']    ?? null,
        ]);
        if ($ok) {
            $_SESSION['flash_success'] = 'Rota atualizada!';
            $this->redirect("/rdv/rotas/{$id}");
        }
        $_SESSION['flash_error'] = 'Erro ao atualizar rota.';
        $this->redirect("/rdv/rotas/{$id}/edit");
    }

    public function rotasDelete(int $id): void
    {
        $this->rotaModel->delete($id);
        $this->jsonOk();
    }

    public function rotasAddCliente(int $id): void
    {
        $rota = $this->rotaModel->findById($id);
        if (!$rota) {
            $this->jsonErr('Rota não encontrada.');
        }
        if ($rota->tipo === 'livre') {
            $this->jsonErr('Rota livre não suporta controle de clientes.');
        }

        $itemId = $this->rotaModel->addCliente($id, [
            'cliente_id'      => $_POST['cliente_id']      ?? null,
            'lead_id'         => $_POST['lead_id']         ?? null,
            'oportunidade_id' => $_POST['oportunidade_id'] ?? null,
            'ordem'           => $_POST['ordem']           ?? 0,
            'observacoes'     => $_POST['observacoes']     ?? null,
        ]);

        if ($itemId) {
            $this->jsonOk(['item_id' => $itemId]);
        }
        $this->jsonErr('Erro ao adicionar cliente à rota.');
    }

    public function rotasRemoveCliente(int $id, int $itemId): void
    {
        $ok = $this->rotaModel->removeCliente($itemId, $id);
        if ($ok) {
            $this->jsonOk();
        }
        $this->jsonErr('Erro ao remover cliente da rota.');
    }

    private function _getClientes(): array
    {
        try {
            $pdo  = \App\Core\Database::getInstance();
            $uid  = $this->uid();
            $stmt = $pdo->prepare(
                "SELECT id, COALESCE(nome_fantasia, razao_social) AS nome, cidade, estado
                 FROM clientes
                 WHERE usuario_id = ? AND status != 'inativo'
                 ORDER BY razao_social ASC LIMIT 500"
            );
            $stmt->execute([$uid]);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvController::_getClientes] ' . $e->getMessage());
            return [];
        }
    }

    private function _getLeads(): array
    {
        try {
            $pdo  = \App\Core\Database::getInstance();
            $uid  = $this->uid();
            $stmt = $pdo->prepare(
                "SELECT id, nome_lead AS nome, razao_social AS empresa, cidade
                 FROM crm_leads
                 WHERE usuario_id = ?
                 ORDER BY nome_lead ASC LIMIT 500"
            );
            $stmt->execute([$uid]);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvController::_getLeads] ' . $e->getMessage());
            return [];
        }
    }
}
