<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Logger;
use App\Models\Notificacao;
use App\Models\NotificacaoConfigAlerta;
use App\Services\NotificacaoService;

/**
 * NotificacoesController
 *
 * Endpoints AJAX para o sistema de notificações do header.
 * Todas as rotas exigem autenticação (middleware Auth).
 */
class NotificacoesController extends Controller
{
    private Notificacao $notifModel;
    private NotificacaoConfigAlerta $configModel;
    private Logger $logger;

    public function __construct()
    {
        $this->notifModel  = new Notificacao();
        $this->configModel = new NotificacaoConfigAlerta();
        $this->logger      = new Logger();
    }

    private function uid(): int
    {
        return (int) (Auth::user()->id ?? 0);
    }

    // ----------------------------------------------------------------
    // GET /api/notificacoes/count
    // Retorna o número de notificações não lidas (para o badge)
    // ----------------------------------------------------------------
    public function count(): void
    {
        header('Content-Type: application/json');
        try {
            $count = $this->notifModel->countNaoLidas($this->uid());
            echo json_encode(['count' => $count]);
        } catch (\Throwable $e) {
            $this->logger->error('[Notificacoes] Erro ao contar: ' . $e->getMessage());
            echo json_encode(['count' => 0]);
        }
        exit();
    }

    // ----------------------------------------------------------------
    // GET /api/notificacoes/recentes
    // Retorna as últimas 20 notificações para o dropdown do header
    // ----------------------------------------------------------------
    public function recentes(): void
    {
        header('Content-Type: application/json');
        try {
            $uid    = $this->uid();
            $items  = $this->notifModel->findRecentes($uid, 20);
            $count  = $this->notifModel->countNaoLidas($uid);

            // Formatar datas para exibição
            $formatted = array_map(function ($n) {
                $n->created_at_fmt = $this->formatarData($n->created_at);
                return $n;
            }, $items);

            echo json_encode([
                'success'       => true,
                'count_nao_lidas' => $count,
                'notificacoes'  => $formatted,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Notificacoes] Erro ao listar recentes: ' . $e->getMessage());
            echo json_encode(['success' => false, 'notificacoes' => [], 'count_nao_lidas' => 0]);
        }
        exit();
    }

    // ----------------------------------------------------------------
    // POST /api/notificacoes/marcar-lida/{id}
    // Marca uma notificação específica como lida
    // ----------------------------------------------------------------
    public function marcarLida(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $ok = $this->notifModel->marcarLida($id, $this->uid());
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('[Notificacoes] Erro ao marcar lida: ' . $e->getMessage());
            echo json_encode(['success' => false]);
        }
        exit();
    }

    // ----------------------------------------------------------------
    // POST /api/notificacoes/marcar-todas-lidas
    // Marca todas as notificações do usuário como lidas
    // ----------------------------------------------------------------
    public function marcarTodasLidas(): void
    {
        header('Content-Type: application/json');
        try {
            $ok = $this->notifModel->marcarTodasLidas($this->uid());
            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('[Notificacoes] Erro ao marcar todas lidas: ' . $e->getMessage());
            echo json_encode(['success' => false]);
        }
        exit();
    }

    // ----------------------------------------------------------------
    // POST /api/notificacoes/gerar
    // Gera notificações para o usuário atual (pode ser chamado via cron)
    // ----------------------------------------------------------------
    public function gerar(): void
    {
        header('Content-Type: application/json');
        try {
            $service = new NotificacaoService();
            $total   = $service->gerarParaUsuario($this->uid());
            echo json_encode(['success' => true, 'geradas' => $total]);
        } catch (\Throwable $e) {
            $this->logger->error('[Notificacoes] Erro ao gerar: ' . $e->getMessage());
            echo json_encode(['success' => false, 'geradas' => 0]);
        }
        exit();
    }

    // ----------------------------------------------------------------
    // POST /configuracoes/notificacoes/salvar
    // Salva as configurações de alertas do usuário
    // ----------------------------------------------------------------
    public function salvarConfiguracoes(): void
    {
        $uid = $this->uid();

        try {
            $configs = [];
            foreach (Notificacao::TIPOS as $tipo => $info) {
                $configs[$tipo] = [
                    'ativo' => isset($_POST['ativo'][$tipo]) ? 1 : 0,
                    'dias'  => (int) ($_POST['dias'][$tipo] ?? 3),
                ];
            }
            $ok = $this->configModel->salvarLote($uid, $configs);

            if ($ok) {
                $this->logger->info('[Notificacoes] Configurações salvas', ['usuario_id' => $uid]);
                header('Location: /configuracoes?tab=notificacoes&success=salvo');
            } else {
                header('Location: /configuracoes?tab=notificacoes&error=falha');
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Notificacoes] Erro ao salvar configurações: ' . $e->getMessage());
            header('Location: /configuracoes?tab=notificacoes&error=falha');
        }
        exit();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------
    private function formatarData(string $datetime): string
    {
        $ts   = strtotime($datetime);
        $diff = time() - $ts;

        if ($diff < 60)        return 'agora mesmo';
        if ($diff < 3600)      return floor($diff / 60) . ' min atrás';
        if ($diff < 86400)     return floor($diff / 3600) . 'h atrás';
        if ($diff < 172800)    return 'ontem';
        if ($diff < 604800)    return floor($diff / 86400) . ' dias atrás';
        return date('d/m/Y', $ts);
    }
}
