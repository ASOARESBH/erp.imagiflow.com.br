<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Core\Database;
use App\Models\MarketingCampanha;
use App\Models\MarketingDisparador;
use App\Models\MarketingEnvio;
use App\Models\MarketingInteracaoCrm;
use App\Services\MailService;
use PDO;

class MarketingDisparadorController extends Controller
{
    private MarketingCampanha $campanhaModel;
    private MarketingDisparador $disparadorModel;
    private MarketingEnvio $envioModel;
    private MarketingInteracaoCrm $interacaoModel;
    private Logger $logger;
    private PDO $pdo;

    public function __construct()
    {
        $this->campanhaModel   = new MarketingCampanha();
        $this->disparadorModel = new MarketingDisparador();
        $this->envioModel      = new MarketingEnvio();
        $this->interacaoModel  = new MarketingInteracaoCrm();
        $this->logger          = new Logger();
        $this->pdo             = Database::getInstance();
    }

    private function uid(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ---------------------------------------------------------------
    // GET /marketing/disparadores
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid        = $this->uid();
        $filtros    = [
            'status'      => $_GET['status']      ?? '',
            'campanha_id' => $_GET['campanha_id'] ?? '',
        ];

        $disparadores = $this->disparadorModel->findByUsuarioId($uid, $filtros);
        $stats        = $this->disparadorModel->dashboardStats($uid);
        $envioStats   = $this->envioModel->statsGerais($uid);
        $campanhas    = $this->campanhaModel->findAtivas($uid);

        View::render('marketing/disparadores/index', [
            'title'       => 'Disparadores de Marketing',
            '_layout'     => 'erp',
            'breadcrumb'  => ['Marketing' => '/marketing/campanhas', 'Disparadores'],
            'disparadores'=> $disparadores,
            'stats'       => $stats,
            'envioStats'  => $envioStats,
            'campanhas'   => $campanhas,
            'filtros'     => $filtros,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /marketing/disparadores/create
    // ---------------------------------------------------------------
    public function create(): void
    {
        $uid      = $this->uid();
        $campanhas = $this->campanhaModel->findAtivas($uid);

        View::render('marketing/disparadores/form', [
            'title'      => 'Novo Disparador',
            '_layout'    => 'erp',
            'breadcrumb' => ['Marketing' => '/marketing/campanhas', 'Disparadores' => '/marketing/disparadores', 'Novo Disparador'],
            'disparador' => null,
            'isEdit'     => false,
            'campanhas'  => $campanhas,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/disparadores
    // ---------------------------------------------------------------
    public function store(): void
    {
        $uid  = $this->uid();
        $data = $this->sanitizeDisparador();

        if (empty($data['campanha_id']) || empty($data['nome']) || empty($data['publico'])) {
            $_SESSION['flash_error'] = 'Preencha todos os campos obrigatórios.';
            header('Location: /marketing/disparadores/create');
            exit();
        }

        // Contar destinatários com base no público e segmentação
        $destinatarios         = $this->buscarDestinatarios($uid, $data['publico'], $data['segmentacao_array']);
        $data['total_destinatarios'] = count($destinatarios);
        $data['usuario_id']    = $uid;
        $data['segmentacao']   = json_encode($data['segmentacao_array']);
        $data['status']        = 'rascunho';

        try {
            $id = $this->disparadorModel->create($data);

            // Pré-popular a fila de envios
            if (!empty($destinatarios)) {
                $registros = [];
                foreach ($destinatarios as $dest) {
                    $registros[] = [
                        'usuario_id'        => $uid,
                        'disparador_id'     => (int) $id,
                        'destinatario_tipo' => $dest['tipo'],
                        'destinatario_id'   => $dest['id'],
                        'destinatario_nome' => $dest['nome'],
                        'destinatario_email'=> $dest['email'],
                        'destinatario_tel'  => $dest['telefone'] ?? null,
                    ];
                }
                $this->envioModel->createBatch($registros);
            }

            AuditLogger::log('marketing_disparador_criado', [
                'id'          => $id,
                'campanha_id' => $data['campanha_id'],
                'publico'     => $data['publico'],
                'total'       => $data['total_destinatarios'],
            ]);

            $_SESSION['flash_success'] = "Disparador criado com {$data['total_destinatarios']} destinatário(s). Revise e inicie o envio.";
            header("Location: /marketing/disparadores/view/{$id}");
        } catch (\Throwable $e) {
            $this->logger->error('[Marketing] Erro ao criar disparador', ['error' => $e->getMessage()]);
            $_SESSION['flash_error'] = 'Erro ao criar disparador: ' . $e->getMessage();
            header('Location: /marketing/disparadores/create');
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /marketing/disparadores/view/{id}
    // Visualização detalhada do disparador com lista de envios
    // ---------------------------------------------------------------
    public function view(int $id): void
    {
        $uid        = $this->uid();
        $disparador = $this->disparadorModel->findById($id);

        if (!$disparador || (int) $disparador->usuario_id !== $uid) {
            $_SESSION['flash_error'] = 'Disparador não encontrado.';
            header('Location: /marketing/disparadores');
            exit();
        }

        $envios    = $this->envioModel->findByDisparadorId($id);
        $contagens = $this->envioModel->countByDisparadorId($id);

        // Organizar contagens por status
        $countMap = [];
        foreach ($contagens as $c) {
            $countMap[$c->status] = (int) $c->total;
        }

        View::render('marketing/disparadores/view', [
            'title'      => 'Disparador — ' . htmlspecialchars($disparador->nome),
            '_layout'    => 'erp',
            'breadcrumb' => ['Marketing' => '/marketing/campanhas', 'Disparadores' => '/marketing/disparadores', 'Detalhe'],
            'disparador' => $disparador,
            'envios'     => $envios,
            'countMap'   => $countMap,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /marketing/disparadores/iniciar/{id}
    // Inicia o envio controlado (lote a lote, anti-blacklist)
    // ---------------------------------------------------------------
    public function iniciar(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        try {
            $uid        = $this->uid();
            $disparador = $this->disparadorModel->findById($id);

            if (!$disparador || (int) $disparador->usuario_id !== $uid) {
                echo json_encode(['success' => false, 'message' => 'Disparador não encontrado.']);
                exit();
            }

            if (!in_array($disparador->status, ['rascunho', 'pausado'], true)) {
                echo json_encode(['success' => false, 'message' => 'Este disparador não pode ser iniciado no status atual.']);
                exit();
            }

            // Marcar como em andamento
            $this->disparadorModel->update($id, [
                'status'      => 'em_andamento',
                'iniciado_em' => date('Y-m-d H:i:s'),
            ]);

            // Processar um lote
            $resultado = $this->processarLote($disparador);

            echo json_encode([
                'success'  => true,
                'enviados' => $resultado['enviados'],
                'erros'    => $resultado['erros'],
                'pendentes'=> $resultado['pendentes'],
                'message'  => "Lote processado: {$resultado['enviados']} enviados, {$resultado['erros']} erros, {$resultado['pendentes']} pendentes.",
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('[Marketing] Erro ao iniciar disparador', ['id' => $id, 'error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/disparadores/processar-lote/{id}
    // Processa o próximo lote (chamado por polling do frontend)
    // ---------------------------------------------------------------
    public function processarLoteAjax(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        try {
            $uid        = $this->uid();
            $disparador = $this->disparadorModel->findById($id);

            if (!$disparador || (int) $disparador->usuario_id !== $uid) {
                echo json_encode(['success' => false, 'message' => 'Disparador não encontrado.']);
                exit();
            }

            if ($disparador->status !== 'em_andamento') {
                echo json_encode(['success' => false, 'message' => 'Disparador não está em andamento.', 'status' => $disparador->status]);
                exit();
            }

            $resultado = $this->processarLote($disparador);

            echo json_encode([
                'success'   => true,
                'enviados'  => $resultado['enviados'],
                'erros'     => $resultado['erros'],
                'pendentes' => $resultado['pendentes'],
                'concluido' => $resultado['pendentes'] === 0,
                'message'   => $resultado['pendentes'] === 0
                    ? 'Envio concluído!'
                    : "Lote enviado. {$resultado['pendentes']} pendentes.",
            ]);

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/disparadores/pausar/{id}
    // ---------------------------------------------------------------
    public function pausar(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        $uid        = $this->uid();
        $disparador = $this->disparadorModel->findById($id);

        if (!$disparador || (int) $disparador->usuario_id !== $uid) {
            echo json_encode(['success' => false, 'message' => 'Disparador não encontrado.']);
            exit();
        }

        $this->disparadorModel->update($id, ['status' => 'pausado']);
        echo json_encode(['success' => true, 'message' => 'Disparador pausado.']);
        exit();
    }

    // ---------------------------------------------------------------
    // POST /marketing/disparadores/delete/{id}
    // ---------------------------------------------------------------
    public function delete(int $id): void
    {
        $uid = $this->uid();
        try {
            $this->envioModel->deleteByDisparadorId($id);
            $this->disparadorModel->delete($id, $uid);
            AuditLogger::log('marketing_disparador_excluido', ['id' => $id]);
            $_SESSION['flash_success'] = 'Disparador excluído.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao excluir: ' . $e->getMessage();
        }
        header('Location: /marketing/disparadores');
        exit();
    }

    // ---------------------------------------------------------------
    // GET /marketing/disparadores/dashboard
    // Dashboard geral de todos os disparos
    // ---------------------------------------------------------------
    public function dashboard(): void
    {
        $uid          = $this->uid();
        $stats        = $this->disparadorModel->dashboardStats($uid);
        $envioStats   = $this->envioModel->statsGerais($uid);
        $disparadores = $this->disparadorModel->findByUsuarioId($uid, ['status' => 'concluido']);
        $recentes     = $this->disparadorModel->findByUsuarioId($uid);

        View::render('marketing/disparadores/dashboard', [
            'title'       => 'Dashboard de Marketing',
            '_layout'     => 'erp',
            'breadcrumb'  => ['Marketing' => '/marketing/campanhas', 'Dashboard'],
            'stats'       => $stats,
            'envioStats'  => $envioStats,
            'disparadores'=> $disparadores,
            'recentes'    => array_slice($recentes, 0, 10),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /marketing/disparadores/segmento-count (AJAX)
    // Retorna a contagem de destinatários para um público+segmentação
    // ---------------------------------------------------------------
    public function segmentoCount(): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');

        $uid         = $this->uid();
        $publico     = $_GET['publico'] ?? 'leads';
        $segmentacao = [
            'status'           => $_GET['status']           ?? '',
            'segmento'         => $_GET['segmento']         ?? '',
            'origem'           => $_GET['origem']           ?? '',
            'etapa_funil'      => $_GET['etapa_funil']      ?? '',
            'estado'           => $_GET['estado']           ?? '',
            'tem_email'        => $_GET['tem_email']        ?? '1',
        ];

        $destinatarios = $this->buscarDestinatarios($uid, $publico, $segmentacao);
        echo json_encode(['success' => true, 'total' => count($destinatarios)]);
        exit();
    }

    // ---------------------------------------------------------------
    // Lógica de processamento de lote (anti-blacklist)
    // ---------------------------------------------------------------
    private function processarLote(object $disparador): array
    {
        $loteSize   = max(1, (int) $disparador->lote_tamanho);
        $intervalo  = max(1, (int) $disparador->intervalo_envio);
        $pendentes  = $this->envioModel->findPendentesByDisparadorId((int) $disparador->id, $loteSize);

        $enviados = 0;
        $erros    = 0;

        foreach ($pendentes as $envio) {
            try {
                $ok = $this->enviarMensagem($disparador, $envio);
                if ($ok) {
                    $this->envioModel->updateStatus((int) $envio->id, 'enviado');
                    $this->disparadorModel->incrementarEnviados((int) $disparador->id);
                    $this->campanhaModel->incrementarContador((int) $disparador->campanha_id, 'total_enviados');

                    // Registrar interação no CRM
                    $this->registrarInteracaoCrm($envio, $disparador, 'enviado');
                    $enviados++;
                } else {
                    $this->envioModel->updateStatus((int) $envio->id, 'erro', 'Falha no envio.');
                    $this->disparadorModel->incrementarErros((int) $disparador->id);
                    $erros++;
                }
            } catch (\Throwable $e) {
                $this->envioModel->updateStatus((int) $envio->id, 'erro', $e->getMessage());
                $this->disparadorModel->incrementarErros((int) $disparador->id);
                $this->logger->error('[Marketing] Erro ao enviar', [
                    'envio_id' => $envio->id,
                    'error'    => $e->getMessage(),
                ]);
                $erros++;
            }

            // Intervalo anti-blacklist entre envios
            if ($enviados < count($pendentes)) {
                usleep($intervalo * 200000); // 20% do intervalo em microsegundos por item
            }
        }

        // Verificar se ainda há pendentes após o lote
        $totalPendentes = count($this->envioModel->findPendentesByDisparadorId((int) $disparador->id, 1));

        // Se não há mais pendentes, marcar como concluído
        if ($totalPendentes === 0) {
            $this->disparadorModel->update((int) $disparador->id, [
                'status'       => 'concluido',
                'concluido_em' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'enviados' => $enviados,
            'erros'    => $erros,
            'pendentes'=> $totalPendentes,
        ];
    }

    private function enviarMensagem(object $disparador, object $envio): bool
    {
        $canal = $disparador->campanha_canal ?? 'email';

        if ($canal === 'email') {
            if (empty($envio->destinatario_email) || !filter_var($envio->destinatario_email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $mail    = new MailService();
            $assunto = $disparador->campanha_assunto ?: 'Mensagem de Marketing';
            $corpo   = $disparador->campanha_corpo   ?: '';

            // Substituir variáveis de personalização
            $corpo = str_replace(
                ['{{nome}}', '{{email}}'],
                [htmlspecialchars($envio->destinatario_nome ?? ''), htmlspecialchars($envio->destinatario_email ?? '')],
                $corpo
            );

            $mail->send(
                $envio->destinatario_email,
                $envio->destinatario_nome ?? '',
                $assunto,
                $corpo
            );
            return true;

        } elseif ($canal === 'whatsapp') {
            // Integração WhatsApp: delegar ao serviço existente se disponível
            // Por ora, registra como enviado (implementação futura via API)
            $this->logger->info('[Marketing] WhatsApp: ' . ($envio->destinatario_tel ?? 'sem tel'));
            return !empty($envio->destinatario_tel);

        } elseif ($canal === 'telegram') {
            $this->logger->info('[Marketing] Telegram: ' . ($envio->destinatario_tel ?? 'sem chat_id'));
            return !empty($envio->destinatario_tel);

        } elseif ($canal === 'sdr') {
            // SDR: registra a tarefa de contato ativo (ligação/e-mail manual)
            $this->logger->info('[Marketing] SDR registrado para: ' . ($envio->destinatario_nome ?? ''));
            return true;
        }

        return false;
    }

    private function registrarInteracaoCrm(object $envio, object $disparador, string $evento): void
    {
        try {
            $tipoMap = [
                'cliente'      => 'cliente',
                'lead'         => 'lead',
                'oportunidade' => 'oportunidade',
            ];
            $tipo = $tipoMap[$envio->destinatario_tipo] ?? null;
            if (!$tipo) return;

            $this->interacaoModel->create([
                'usuario_id'   => (int) $envio->usuario_id,
                'envio_id'     => (int) $envio->id,
                'campanha_id'  => (int) $disparador->campanha_id,
                'related_type' => $tipo,
                'related_id'   => (int) $envio->destinatario_id,
                'evento'       => $evento,
                'observacao'   => 'Campanha: ' . ($disparador->campanha_nome ?? ''),
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('[Marketing] Falha ao registrar interação CRM: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    // Buscar destinatários com segmentação
    // ---------------------------------------------------------------
    private function buscarDestinatarios(int $uid, string $publico, array $seg): array
    {
        $temEmail = ($seg['tem_email'] ?? '1') !== '0';

        if ($publico === 'clientes') {
            return $this->buscarClientes($uid, $seg, $temEmail);
        } elseif ($publico === 'leads') {
            return $this->buscarLeads($uid, $seg, $temEmail);
        } elseif ($publico === 'oportunidades') {
            return $this->buscarOportunidades($uid, $seg, $temEmail);
        }
        return [];
    }

    private function buscarClientes(int $uid, array $seg, bool $temEmail): array
    {
        $where  = ['c.usuario_id = :uid'];
        $params = [':uid' => $uid];

        if ($temEmail) {
            $where[] = "c.email IS NOT NULL AND c.email != ''";
        }
        if (!empty($seg['estado'])) {
            $where[]         = 'c.estado = :estado';
            $params[':estado'] = $seg['estado'];
        }

        $sql = "SELECT c.id, c.razao_social AS nome, c.email, c.telefone,
                       'cliente' AS tipo
                FROM clientes c
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.razao_social ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarLeads(int $uid, array $seg, bool $temEmail): array
    {
        $where  = ['l.usuario_id = :uid', "l.convertido_em IS NULL"];
        $params = [':uid' => $uid];

        if ($temEmail) {
            $where[] = "(l.email IS NOT NULL AND l.email != '') OR (l.responsavel_email IS NOT NULL AND l.responsavel_email != '')";
        }
        if (!empty($seg['status'])) {
            $where[]           = 'l.status_lead = :status';
            $params[':status'] = $seg['status'];
        }
        if (!empty($seg['segmento'])) {
            $where[]             = 'l.segmento_principal = :segmento';
            $params[':segmento'] = $seg['segmento'];
        }
        if (!empty($seg['origem'])) {
            $where[]           = 'l.origem = :origem';
            $params[':origem'] = $seg['origem'];
        }
        if (!empty($seg['estado'])) {
            $where[]           = 'l.estado = :estado';
            $params[':estado'] = $seg['estado'];
        }

        $sql = "SELECT l.id,
                       l.nome_lead AS nome,
                       COALESCE(NULLIF(l.email,''), l.responsavel_email) AS email,
                       COALESCE(NULLIF(l.celular,''), l.telefone) AS telefone,
                       'lead' AS tipo
                FROM crm_leads l
                WHERE " . implode(' AND ', $where) . "
                ORDER BY l.nome_lead ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarOportunidades(int $uid, array $seg, bool $temEmail): array
    {
        $where  = ['o.usuario_id = :uid', "o.status_oportunidade = 'aberta'"];
        $params = [':uid' => $uid];

        if (!empty($seg['etapa_funil'])) {
            $where[]              = 'o.etapa_funil = :etapa';
            $params[':etapa']     = $seg['etapa_funil'];
        }

        $sql = "SELECT o.id,
                       COALESCE(l.nome_lead, c.razao_social, o.titulo_oportunidade) AS nome,
                       COALESCE(l.email, l.responsavel_email, c.email) AS email,
                       COALESCE(l.celular, l.telefone, c.telefone) AS telefone,
                       'oportunidade' AS tipo
                FROM crm_oportunidades o
                LEFT JOIN crm_leads l ON l.id = o.lead_id
                LEFT JOIN clientes  c ON c.id = o.cliente_id
                WHERE " . implode(' AND ', $where);

        if ($temEmail) {
            $sql .= " AND (l.email IS NOT NULL OR l.responsavel_email IS NOT NULL OR c.email IS NOT NULL)";
        }

        $sql .= " ORDER BY o.titulo_oportunidade ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    private function sanitizeDisparador(): array
    {
        $segmentacao = [
            'status'      => trim($_POST['seg_status']      ?? ''),
            'segmento'    => trim($_POST['seg_segmento']    ?? ''),
            'origem'      => trim($_POST['seg_origem']      ?? ''),
            'etapa_funil' => trim($_POST['seg_etapa_funil'] ?? ''),
            'estado'      => trim($_POST['seg_estado']      ?? ''),
            'tem_email'   => ($_POST['seg_tem_email']       ?? '1') === '0' ? '0' : '1',
        ];

        return [
            'campanha_id'       => (int) ($_POST['campanha_id']    ?? 0),
            'nome'              => trim($_POST['nome']              ?? ''),
            'publico'           => in_array($_POST['publico'] ?? '', ['clientes', 'leads', 'oportunidades'])
                                    ? $_POST['publico'] : 'leads',
            'segmentacao_array' => $segmentacao,
            'agendado_para'     => !empty($_POST['agendado_para']) ? $_POST['agendado_para'] : null,
            'intervalo_envio'   => max(1, (int) ($_POST['intervalo_envio'] ?? 5)),
            'lote_tamanho'      => max(1, min(50, (int) ($_POST['lote_tamanho'] ?? 5))),
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
