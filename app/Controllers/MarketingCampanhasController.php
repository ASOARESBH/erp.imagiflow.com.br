<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\MarketingCampanha;
use App\Services\MailService;

class MarketingCampanhasController extends Controller
{
    private MarketingCampanha $campanhaModel;
    private Logger $logger;

    public function __construct()
    {
        $this->campanhaModel = new MarketingCampanha();
        $this->logger        = new Logger();
    }

    private function uid(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ---------------------------------------------------------------
    // GET /marketing/campanhas
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid     = $this->uid();
        $filtros = [
            'canal'  => $_GET['canal']  ?? '',
            'status' => $_GET['status'] ?? '',
            'q'      => trim($_GET['q'] ?? ''),
        ];

        $campanhas = $this->campanhaModel->findByUsuarioId($uid, $filtros);
        $porCanal  = $this->campanhaModel->countByCanal($uid);

        View::render('marketing/campanhas/index', [
            'title'      => 'Campanhas de Marketing',
            '_layout'    => 'erp',
            'breadcrumb' => ['Marketing' => '/marketing/campanhas', 'Campanhas'],
            'campanhas'  => $campanhas,
            'porCanal'   => $porCanal,
            'filtros'    => $filtros,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /marketing/campanhas/create
    // ---------------------------------------------------------------
    public function create(): void
    {
        View::render('marketing/campanhas/form', [
            'title'      => 'Nova Campanha',
            '_layout'    => 'erp',
            'breadcrumb' => ['Marketing' => '/marketing/campanhas', 'Campanhas' => '/marketing/campanhas', 'Nova Campanha'],
            'campanha'   => null,
            'isEdit'     => false,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas
    // ---------------------------------------------------------------
    public function store(): void
    {
        $uid  = $this->uid();
        $data = $this->sanitize();

        // Validação básica
        if (empty($data['nome'])) {
            $_SESSION['flash_error'] = 'O nome da campanha é obrigatório.';
            header('Location: /marketing/campanhas/create');
            exit();
        }

        $data['usuario_id'] = $uid;
        $data['status']     = 'rascunho';

        try {
            $id = $this->campanhaModel->create($data);
            AuditLogger::log('marketing_campanha_criada', ['id' => $id, 'nome' => $data['nome']]);
            $_SESSION['flash_success'] = 'Campanha criada com sucesso!';
            header("Location: /marketing/campanhas/personalizar/{$id}");
        } catch (\Throwable $e) {
            $this->logger->error('[Marketing] Erro ao criar campanha', ['error' => $e->getMessage()]);
            $_SESSION['flash_error'] = 'Erro ao criar campanha: ' . $e->getMessage();
            header('Location: /marketing/campanhas/create');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /marketing/campanhas/personalizar/{id}
    // Etapa de personalização do conteúdo da campanha
    // ---------------------------------------------------------------
    public function personalizar(int $id): void
    {
        $uid      = $this->uid();
        $campanha = $this->campanhaModel->findById($id);

        if (!$campanha || (int) $campanha->usuario_id !== $uid) {
            $_SESSION['flash_error'] = 'Campanha não encontrada.';
            header('Location: /marketing/campanhas');
            exit();
        }

        View::render('marketing/campanhas/personalizar', [
            'title'      => 'Personalizar Campanha — ' . htmlspecialchars($campanha->nome),
            '_layout'    => 'erp',
            'breadcrumb' => ['Marketing' => '/marketing/campanhas', 'Campanhas' => '/marketing/campanhas', 'Personalizar'],
            'campanha'   => $campanha,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas/personalizar/{id}
    // Salva o conteúdo personalizado
    // ---------------------------------------------------------------
    public function salvarPersonalizacao(int $id): void
    {
        $uid      = $this->uid();
        $campanha = $this->campanhaModel->findById($id);

        if (!$campanha || (int) $campanha->usuario_id !== $uid) {
            $this->jsonError('Campanha não encontrada.');
            return;
        }

        $data = [
            'assunto_email'   => trim($_POST['assunto_email'] ?? ''),
            'tipo_conteudo'   => in_array($_POST['tipo_conteudo'] ?? '', ['texto', 'html']) ? $_POST['tipo_conteudo'] : 'html',
            'corpo'           => $_POST['corpo'] ?? '',
            'remetente_nome'  => trim($_POST['remetente_nome'] ?? ''),
            'remetente_email' => trim($_POST['remetente_email'] ?? ''),
            'numero_origem'   => trim($_POST['numero_origem'] ?? ''),
            'status'          => 'ativa',
        ];

        try {
            $this->campanhaModel->update($id, $data);
            AuditLogger::log('marketing_campanha_personalizada', ['id' => $id]);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Campanha salva com sucesso!']);
                exit();
            }

            $_SESSION['flash_success'] = 'Campanha salva e ativada com sucesso!';
            header("Location: /marketing/campanhas/personalizar/{$id}");
        } catch (\Throwable $e) {
            $this->logger->error('[Marketing] Erro ao salvar personalização', ['error' => $e->getMessage()]);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit();
            }
            $_SESSION['flash_error'] = 'Erro ao salvar: ' . $e->getMessage();
            header("Location: /marketing/campanhas/personalizar/{$id}");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /marketing/campanhas/edit/{id}
    // ---------------------------------------------------------------
    public function edit(int $id): void
    {
        $uid      = $this->uid();
        $campanha = $this->campanhaModel->findById($id);

        if (!$campanha || (int) $campanha->usuario_id !== $uid) {
            $_SESSION['flash_error'] = 'Campanha não encontrada.';
            header('Location: /marketing/campanhas');
            exit();
        }

        View::render('marketing/campanhas/form', [
            'title'      => 'Editar Campanha — ' . htmlspecialchars($campanha->nome),
            '_layout'    => 'erp',
            'breadcrumb' => ['Marketing' => '/marketing/campanhas', 'Campanhas' => '/marketing/campanhas', 'Editar'],
            'campanha'   => $campanha,
            'isEdit'     => true,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas/update/{id}
    // ---------------------------------------------------------------
    public function update(int $id): void
    {
        $uid      = $this->uid();
        $campanha = $this->campanhaModel->findById($id);

        if (!$campanha || (int) $campanha->usuario_id !== $uid) {
            $_SESSION['flash_error'] = 'Campanha não encontrada.';
            header('Location: /marketing/campanhas');
            exit();
        }

        $data = $this->sanitize();

        if (empty($data['nome'])) {
            $_SESSION['flash_error'] = 'O nome da campanha é obrigatório.';
            header("Location: /marketing/campanhas/edit/{$id}");
            exit();
        }

        try {
            $this->campanhaModel->update($id, $data);
            AuditLogger::log('marketing_campanha_atualizada', ['id' => $id, 'nome' => $data['nome']]);
            $_SESSION['flash_success'] = 'Campanha atualizada com sucesso!';
            header("Location: /marketing/campanhas/personalizar/{$id}");
        } catch (\Throwable $e) {
            $this->logger->error('[Marketing] Erro ao atualizar campanha', ['error' => $e->getMessage()]);
            $_SESSION['flash_error'] = 'Erro ao atualizar: ' . $e->getMessage();
            header("Location: /marketing/campanhas/edit/{$id}");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas/delete/{id}
    // ---------------------------------------------------------------
    public function delete(int $id): void
    {
        $uid = $this->uid();
        try {
            $this->campanhaModel->delete($id, $uid);
            AuditLogger::log('marketing_campanha_excluida', ['id' => $id]);
            $_SESSION['flash_success'] = 'Campanha excluída.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao excluir: ' . $e->getMessage();
        }
        header('Location: /marketing/campanhas');
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas/envio-teste/{id}
    // Envia um e-mail/mensagem de teste para o usuário logado
    // ---------------------------------------------------------------
    public function envioTeste(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        try {
            $uid      = $this->uid();
            $campanha = $this->campanhaModel->findById($id);

            if (!$campanha || (int) $campanha->usuario_id !== $uid) {
                echo json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
                exit();
            }

            $emailTeste = trim($_POST['email_teste'] ?? ($_SESSION['user_email'] ?? ''));
            if (empty($emailTeste) || !filter_var($emailTeste, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Informe um e-mail válido para o teste.']);
                exit();
            }

            if ($campanha->canal === 'email') {
                if (empty($campanha->corpo)) {
                    echo json_encode(['success' => false, 'message' => 'O conteúdo da campanha está vazio. Personalize antes de testar.']);
                    exit();
                }

                $assunto = '[TESTE] ' . ($campanha->assunto_email ?: $campanha->nome);
                $corpo   = $campanha->corpo;

                // Adicionar banner de teste no topo
                $bannerTeste = '<div style="background:#fff3cd;border:2px dashed #ffc107;padding:12px 16px;margin-bottom:20px;border-radius:6px;font-family:sans-serif;">'
                             . '<strong>⚠️ E-MAIL DE TESTE</strong> — Esta é uma prévia da campanha <em>' . htmlspecialchars($campanha->nome) . '</em>. '
                             . 'Não é um envio real.</div>';

                if ($campanha->tipo_conteudo === 'html') {
                    $corpoFinal = $bannerTeste . $corpo;
                } else {
                    $corpoFinal = '<pre style="font-family:sans-serif;">' . $bannerTeste . nl2br(htmlspecialchars($corpo)) . '</pre>';
                }

                $mail = new MailService();
                $mail->send(
                    $emailTeste,
                    $_SESSION['user_name'] ?? 'Usuário',
                    $assunto,
                    $corpoFinal
                );

                AuditLogger::log('marketing_envio_teste', ['campanha_id' => $id, 'email' => $emailTeste]);
                echo json_encode(['success' => true, 'message' => "E-mail de teste enviado para {$emailTeste}!"]);

            } elseif ($campanha->canal === 'whatsapp') {
                echo json_encode(['success' => false, 'message' => 'Envio de teste via WhatsApp: configure o número de origem e use a integração WhatsApp do sistema.']);
            } elseif ($campanha->canal === 'telegram') {
                echo json_encode(['success' => false, 'message' => 'Envio de teste via Telegram: configure o token do bot e o chat ID de destino.']);
            } elseif ($campanha->canal === 'sdr') {
                echo json_encode(['success' => true, 'message' => 'Prévia SDR: ' . mb_substr(strip_tags($campanha->corpo ?? ''), 0, 200) . '...']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Canal não suportado para envio de teste.']);
            }

        } catch (\Throwable $e) {
            $this->logger->error('[Marketing] Erro no envio de teste', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro ao enviar teste: ' . $e->getMessage()]);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas/status/{id}
    // Altera o status da campanha (ativar/pausar/arquivar)
    // ---------------------------------------------------------------
    public function alterarStatus(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        $uid      = $this->uid();
        $campanha = $this->campanhaModel->findById($id);

        if (!$campanha || (int) $campanha->usuario_id !== $uid) {
            echo json_encode(['success' => false, 'message' => 'Campanha não encontrada.']);
            exit();
        }

        $novoStatus = $_POST['status'] ?? '';
        $validos    = ['rascunho', 'ativa', 'pausada', 'arquivada'];

        if (!in_array($novoStatus, $validos, true)) {
            echo json_encode(['success' => false, 'message' => 'Status inválido.']);
            exit();
        }

        $this->campanhaModel->update($id, ['status' => $novoStatus]);
        AuditLogger::log('marketing_campanha_status', ['id' => $id, 'status' => $novoStatus]);
        echo json_encode(['success' => true, 'message' => 'Status atualizado.', 'status' => $novoStatus]);
        exit();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function sanitize(): array
    {
        return [
            'nome'            => trim($_POST['nome'] ?? ''),
            'descricao'       => trim($_POST['descricao'] ?? ''),
            'canal'           => in_array($_POST['canal'] ?? '', ['email', 'whatsapp', 'telegram', 'sdr']) ? $_POST['canal'] : 'email',
            'assunto_email'   => trim($_POST['assunto_email'] ?? ''),
            'tipo_conteudo'   => in_array($_POST['tipo_conteudo'] ?? '', ['texto', 'html']) ? $_POST['tipo_conteudo'] : 'html',
            'corpo'           => $_POST['corpo'] ?? '',
            'remetente_nome'  => trim($_POST['remetente_nome'] ?? ''),
            'remetente_email' => trim($_POST['remetente_email'] ?? ''),
            'numero_origem'   => trim($_POST['numero_origem'] ?? ''),
        ];
    }

    private function jsonError(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
}
