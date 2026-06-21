<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\MarketingCampanha;
use App\Models\MarketingGatilho;

class MarketingController extends Controller
{
    private MarketingCampanha $campanhaModel;
    private MarketingGatilho  $gatilhoModel;
    private Logger            $logger;

    public function __construct()
    {
        $this->campanhaModel = new MarketingCampanha();
        $this->gatilhoModel  = new MarketingGatilho();
        $this->logger        = new Logger();
    }

    private function usuarioId(): int
    {
        return (int)(Auth::user()->id ?? 0);
    }

    // ---------------------------------------------------------------
    // GET /marketing — Dashboard
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid = $this->usuarioId();

        try {
            $statusCampanhas  = $this->campanhaModel->countByStatus($uid);
            $totalCampanhas   = array_sum($statusCampanhas);
            $gatilhosAtivos   = $this->gatilhoModel->countAtivos($uid);
            $ultimasCampanhas = $this->campanhaModel->findByUsuarioId($uid, []);
            $ultimasCampanhas = array_slice($ultimasCampanhas, 0, 5);
        } catch (\Throwable $e) {
            $this->logger->error('Marketing dashboard error: ' . $e->getMessage());
            $statusCampanhas  = [];
            $totalCampanhas   = 0;
            $gatilhosAtivos   = 0;
            $ultimasCampanhas = [];
        }

        View::render('marketing/index', [
            '_layout'         => 'erp',
            'title'           => 'Marketing',
            'breadcrumb'      => [0 => 'Marketing'],
            'statusCampanhas' => $statusCampanhas,
            'totalCampanhas'  => $totalCampanhas,
            'gatilhosAtivos'  => $gatilhosAtivos,
            'ultimasCampanhas'=> $ultimasCampanhas,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /marketing/campanhas — Lista de campanhas
    // ---------------------------------------------------------------
    public function campanhas(): void
    {
        $uid = $this->usuarioId();

        $filtros = [
            'status' => $_GET['status'] ?? '',
            'tipo'   => $_GET['tipo']   ?? '',
            'q'      => $_GET['q']      ?? '',
        ];

        try {
            $campanhas = $this->campanhaModel->findByUsuarioId($uid, $filtros);
        } catch (\Throwable $e) {
            $this->logger->error('Marketing campanhas error: ' . $e->getMessage());
            $campanhas = [];
        }

        View::render('marketing/campanhas/index', [
            '_layout'   => 'erp',
            'title'     => 'Campanhas',
            'breadcrumb'=> [
                'Marketing' => '/marketing',
                0           => 'Campanhas',
            ],
            'campanhas' => $campanhas,
            'filtros'   => $filtros,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas — Criar campanha
    // ---------------------------------------------------------------
    public function campanhaStore(): void
    {
        $uid = $this->usuarioId();

        $nome    = trim($_POST['nome']    ?? '');
        $tipo    = $_POST['tipo']         ?? 'email';
        $assunto = trim($_POST['assunto'] ?? '');
        $conteudo= trim($_POST['conteudo']?? '');
        $dataAge = $_POST['data_agendamento'] ?? null;

        if ($nome === '') {
            header('Location: /marketing/campanhas?error=nome_obrigatorio');
            exit();
        }

        try {
            $id = $this->campanhaModel->create([
                'usuario_id'       => $uid,
                'nome'             => $nome,
                'tipo'             => $tipo,
                'assunto'          => $assunto ?: null,
                'conteudo'         => $conteudo ?: null,
                'data_agendamento' => ($dataAge !== '' && $dataAge !== null) ? $dataAge : null,
            ]);
            AuditLogger::log('marketing_campanha_created', ['id' => $id, 'nome' => $nome]);
            header("Location: /marketing/campanhas?success=created");
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar campanha: ' . $e->getMessage());
            header('Location: /marketing/campanhas?error=db_failure');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/campanhas/delete/{id}
    // ---------------------------------------------------------------
    public function campanhaDelete($id): void
    {
        $uid = $this->usuarioId();
        try {
            $c = $this->campanhaModel->findById((int)$id);
            if (!$c || (int)$c->usuario_id !== $uid) {
                header('Location: /marketing/campanhas?error=not_found');
                exit();
            }
            $this->campanhaModel->delete((int)$id);
            AuditLogger::log('marketing_campanha_deleted', ['id' => (int)$id]);
            header('Location: /marketing/campanhas?success=deleted');
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao excluir campanha: ' . $e->getMessage());
            header('Location: /marketing/campanhas?error=db_failure');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /marketing/gatilhos — Lista de gatilhos
    // ---------------------------------------------------------------
    public function gatilhos(): void
    {
        $uid = $this->usuarioId();

        $filtros = [
            'canal' => $_GET['canal'] ?? '',
            'ativo' => $_GET['ativo'] ?? '',
            'q'     => $_GET['q']     ?? '',
        ];

        try {
            $gatilhos = $this->gatilhoModel->findByUsuarioId($uid, $filtros);
        } catch (\Throwable $e) {
            $this->logger->error('Marketing gatilhos error: ' . $e->getMessage());
            $gatilhos = [];
        }

        View::render('marketing/gatilhos/index', [
            '_layout'    => 'erp',
            'title'      => 'Gatilhos de Automação',
            'breadcrumb' => [
                'Marketing' => '/marketing',
                0           => 'Gatilhos',
            ],
            'gatilhos'   => $gatilhos,
            'filtros'    => $filtros,
            'tiposGatilho' => MarketingGatilho::TIPOS,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/gatilhos — Criar gatilho
    // ---------------------------------------------------------------
    public function gatilhoStore(): void
    {
        $uid = $this->usuarioId();

        $nome     = trim($_POST['nome']    ?? '');
        $tipo     = $_POST['tipo']         ?? 'novo_cliente';
        $canal    = $_POST['canal']        ?? 'email';
        $assunto  = trim($_POST['assunto_email'] ?? '');
        $conteudo = trim($_POST['conteudo_mensagem'] ?? '');
        $delay    = (int)($_POST['delay_dias'] ?? 0);

        if ($nome === '' || $conteudo === '') {
            header('Location: /marketing/gatilhos?error=campos_obrigatorios');
            exit();
        }

        try {
            $id = $this->gatilhoModel->create([
                'usuario_id'          => $uid,
                'nome'                => $nome,
                'tipo'                => $tipo,
                'canal'               => $canal,
                'ativo'               => 1,
                'assunto_email'       => $assunto ?: null,
                'conteudo_mensagem'   => $conteudo,
                'delay_dias'          => $delay,
            ]);
            AuditLogger::log('marketing_gatilho_created', ['id' => $id, 'nome' => $nome, 'tipo' => $tipo]);
            header('Location: /marketing/gatilhos?success=created');
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar gatilho: ' . $e->getMessage());
            header('Location: /marketing/gatilhos?error=db_failure');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/gatilhos/toggle/{id} — Ativar/desativar
    // ---------------------------------------------------------------
    public function gatilhoToggle($id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $uid = $this->usuarioId();
        try {
            $g = $this->gatilhoModel->findById((int)$id);
            if (!$g || (int)$g->usuario_id !== $uid) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Gatilho não encontrado.']);
                exit();
            }
            $this->gatilhoModel->toggleAtivo((int)$id);
            $updated = $this->gatilhoModel->findById((int)$id);
            AuditLogger::log('marketing_gatilho_toggle', ['id' => (int)$id, 'ativo' => $updated->ativo ?? null]);
            echo json_encode(['success' => true, 'ativo' => (int)($updated->ativo ?? 0)]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao alternar gatilho: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno.']);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/gatilhos/delete/{id}
    // ---------------------------------------------------------------
    public function gatilhoDelete($id): void
    {
        $uid = $this->usuarioId();
        try {
            $g = $this->gatilhoModel->findById((int)$id);
            if (!$g || (int)$g->usuario_id !== $uid) {
                header('Location: /marketing/gatilhos?error=not_found');
                exit();
            }
            $this->gatilhoModel->delete((int)$id);
            AuditLogger::log('marketing_gatilho_deleted', ['id' => (int)$id]);
            header('Location: /marketing/gatilhos?success=deleted');
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao excluir gatilho: ' . $e->getMessage());
            header('Location: /marketing/gatilhos?error=db_failure');
        }
        exit();
    }
}
