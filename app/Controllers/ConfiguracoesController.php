<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Core\Mail;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\ConfigNfs;
use App\Models\ConfiguracaoFinanceira;
use App\Models\Integracao;
use App\Services\MailService;
use App\Services\CryptoService;
use App\Services\CnesImportService;
use App\Core\Database;
use App\Models\NotificacaoConfigAlerta;

class ConfiguracoesController extends Controller
{
    private User $userModel;
    private PasswordResetToken $passwordResetModel;
    private ConfigNfs $configNfsModel;
    private ConfiguracaoFinanceira $configFinanceiroModel;
    private Integracao $integracaoModel;
    private CnesImportService $cnesImportService;

    public function __construct()
    {
        $this->userModel          = new User();
        $this->passwordResetModel = new PasswordResetToken();
        $this->configNfsModel        = new ConfigNfs();
        $this->configFinanceiroModel = new ConfiguracaoFinanceira();
        $this->integracaoModel    = new Integracao();
        $this->cnesImportService  = new CnesImportService();
    }

    // ================================================================
    // Helpers
    // ================================================================

    /**
     * Cria uma instância do MailService carregando as credenciais SMTP
     * salvas no banco de dados (tabela integracoes) para o usuário atual.
     * Se não houver configuração no banco, usa fallback do .env.
     *
     * @throws \RuntimeException se a integração de e-mail estiver inativa
     *                           ou a senha não puder ser descriptografada.
     */
    private function buildMailService(): MailService
    {
        $usuarioId = (int) Auth::user()->id;
        $row       = $this->integracaoModel->findByNomeAndUsuarioId('email', $usuarioId);

        // Sem configuração no banco → tenta .env (comportamento legado)
        if (!$row) {
            return new MailService();
        }

        if (($row->status ?? 'ativo') !== 'ativo') {
            throw new \RuntimeException('A integração de e-mail está inativa. Ative-a em Integração → E-mail.');
        }

        $config   = $this->integracaoModel->getDecodedConfig($row);
        $password = '';

        if (!empty($config['password_enc'])) {
            $crypto   = new CryptoService();
            $password = $crypto->decryptString((string) $config['password_enc']);
        }

        return new MailService([
            'host'       => $config['host']       ?? '',
            'port'       => $config['port']       ?? 587,
            'username'   => $config['username']   ?? '',
            'password'   => $password,
            'protocol'   => $config['protocol']   ?? 'tls',
            'from_email' => $config['from_email'] ?? ($config['username'] ?? ''),
            'from_name'  => $config['from_name']  ?? 'ERP InLaudo',
        ]);
    }

    // ================================================================
    // PÁGINAS
    // ================================================================

    public function index(): void
    {
        if (!Auth::can('manage_settings')) {
            header("Location: /dashboard?error=unauthorized");
            exit();
        }

        $abasPermitidas = ['usuarios', 'notas-fiscais', 'financeiro', 'cnes', 'notificacoes'];
        $activeTab = $_GET['tab'] ?? 'financeiro';
        if (!in_array($activeTab, $abasPermitidas, true)) {
            $activeTab = 'financeiro';
        }
        $usuarios = Auth::can('manage_users') ? $this->userModel->findAll() : [];

        // Configurações de alertas de notificação
        $notificacaoConfigs = [];
        try {
            $notifConfigModel   = new NotificacaoConfigAlerta();
            $notificacaoConfigs = $notifConfigModel->findByUsuario((int) Auth::user()->id);
        } catch (\Throwable $e) {}
        $configNfs        = $this->configNfsModel->findByUsuarioId((int) Auth::user()->id);
        $configFinanceiro = $this->configFinanceiroModel->findByUsuarioId((int) Auth::user()->id);

        // Dados CNES para a aba de importação
        $cnesHistorico  = [];
        $cnesTotalEstab = 0;
        $cnesTotalEquip = 0;
        $cnesTotalProf  = 0;
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("SELECT * FROM cnes_importacoes ORDER BY iniciado_em DESC LIMIT 12");
            $cnesHistorico = $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {}
        try {
            $pdo = Database::getInstance();
            $cnesTotalEstab = (int)$pdo->query("SELECT COUNT(*) FROM cnes_estabelecimentos")->fetchColumn();
            $cnesTotalEquip = (int)$pdo->query("SELECT COUNT(*) FROM cnes_equipamentos")->fetchColumn();
            $cnesTotalProf  = (int)$pdo->query("SELECT COUNT(*) FROM cnes_profissionais")->fetchColumn();
        } catch (\Throwable $e) {}

        View::render('configuracoes/index', [
            'title'          => 'Configurações',
            'activeTab'      => $activeTab,
            'usuarios'       => $usuarios,
            'currentUser'    => Auth::user(),
            'configNfs'       => $configNfs,
            'configFinanceiro'=> $configFinanceiro,
            'cnesHistorico'  => $cnesHistorico,
            'cnesTotalEstab' => $cnesTotalEstab,
            'cnesTotalEquip' => $cnesTotalEquip,
            'cnesTotalProf'  => $cnesTotalProf,
            'notificacaoConfigs' => $notificacaoConfigs,
        ]);
    }

    // ================================================================
    // USUÁRIOS — CRUD
    // ================================================================

    public function usuariosCreate(): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized");
            exit();
        }
        View::render('configuracoes/usuarios/create', [
            'title'       => 'Novo Usuário',
            'currentUser' => Auth::user(),
        ]);
    }

    public function usuariosStore(): void
    {
        if (!Auth::can('manage_users')) {
            header('Location: /configuracoes?error=unauthorized');
            exit();
        }

        $currentUser = Auth::user();
        $reference = $this->userDiagnosticReference();

        try {
            $nome = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $role = (string) ($_POST['role'] ?? 'operador');
            $status = (string) ($_POST['status'] ?? 'ativo');
            $sendWelcome = isset($_POST['send_welcome']);

            if (strlen($nome) < 3 || $email === '') {
                header('Location: /configuracoes/usuarios/create?error=missing_fields');
                exit();
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header('Location: /configuracoes/usuarios/create?error=invalid_email');
                exit();
            }

            // Papéis de controle global nunca podem ser delegados pela área de um tenant.
            $rolesPermitidos = ['operador', 'financeiro', 'leitura', 'admin'];
            if (!in_array($role, $rolesPermitidos, true)) {
                header('Location: /configuracoes/usuarios/create?error=invalid_role');
                exit();
            }
            if (!in_array($status, ['ativo', 'inativo'], true)) {
                header('Location: /configuracoes/usuarios/create?error=invalid_status');
                exit();
            }
            if ($this->userModel->findAnyByEmail($email)) {
                header('Location: /configuracoes/usuarios/create?error=email_exists');
                exit();
            }

            $hashedPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                throw new \RuntimeException('Não foi possível gerar a credencial inicial.');
            }

            $newId = $this->userModel->create([
                'name' => $nome,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
            ]);

            if (!$newId) {
                AuditLogger::log('user_create_failed', [
                    'reference' => $reference,
                    'created_by' => $currentUser->id,
                    'role' => $role,
                ]);
                header('Location: /configuracoes/usuarios/create?error=create_failed&ref=' . rawurlencode($reference));
                exit();
            }

            if (!$this->userModel->setStatusForCurrentTenant((int) $newId, $status)) {
                AuditLogger::log('user_status_after_create_failed', [
                    'reference' => $reference,
                    'created_by' => $currentUser->id,
                    'new_user_id' => (int) $newId,
                ]);
                header('Location: /configuracoes/usuarios/create?error=status_failed&ref=' . rawurlencode($reference));
                exit();
            }

            AuditLogger::log('create_user', [
                'created_by' => $currentUser->id,
                'new_user_id' => (int) $newId,
                'role' => $role,
                'status' => $status,
            ]);

            if ($sendWelcome) {
                try {
                    $this->passwordResetModel->invalidateUserTokens((int) $newId);
                    $tokenData = $this->passwordResetModel->createForUser((int) $newId);
                    $emailSent = Mail::sendPasswordResetLink(
                        $email,
                        $this->passwordResetUrl($tokenData['raw']),
                        (int) $currentUser->id
                    );
                    if (!$emailSent) {
                        throw new \RuntimeException('O serviço de e-mail não confirmou o envio.');
                    }
                } catch (\Throwable $emailException) {
                    AuditLogger::log('user_welcome_email_failed', [
                        'reference' => $reference,
                        'created_by' => $currentUser->id,
                        'new_user_id' => (int) $newId,
                        'exception' => get_class($emailException),
                        'message' => $emailException->getMessage(),
                    ]);
                    header('Location: /configuracoes?tab=usuarios&success=user_created&warning=welcome_failed&ref=' . rawurlencode($reference));
                    exit();
                }
            }

            header('Location: /configuracoes?tab=usuarios&success=user_created');
        } catch (\Throwable $exception) {
            AuditLogger::log('user_create_exception', [
                'reference' => $reference,
                'created_by' => $currentUser->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            header('Location: /configuracoes/usuarios/create?error=exception&ref=' . rawurlencode($reference));
        }
        exit();
    }

    public function usuariosEdit(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized"); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes?tab=usuarios&error=cannot_edit"); exit();
        }
        View::render('configuracoes/usuarios/edit', [
            'title'       => 'Editar Usuário',
            'usuario'     => $usuario,
            'currentUser' => $currentUser,
        ]);
    }

    public function usuariosUpdate(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized"); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes?tab=usuarios&error=cannot_edit"); exit();
        }
        try {
            $nome   = trim($_POST['name']  ?? '');
            $email  = trim($_POST['email'] ?? '');
            $role   = $_POST['role']   ?? $usuario->role;
            $status = $_POST['status'] ?? 'ativo';
            if (empty($nome) || empty($email)) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=missing_fields"); exit();
            }
            $rolesPermitidos = ['operador', 'financeiro', 'leitura', 'admin'];
            if (!in_array($role, $rolesPermitidos, true)
                || $usuario->role === 'superadmin') {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=invalid_role"); exit();
            }
            $emailExistente = $this->userModel->findByEmail($email);
            if ($emailExistente && $emailExistente->id != $id) {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=email_exists"); exit();
            }
            $success = $this->userModel->update($id, [
                'name' => $nome,
                'email' => $email,
                'role' => $role,
                'status' => $status,
            ]);
            if ($success) {
                AuditLogger::log('update_user', ['updated_by' => $currentUser->id, 'user_id' => $id, 'old_name' => $usuario->name, 'new_name' => $nome, 'old_role' => $usuario->role, 'new_role' => $role]);
                header("Location: /configuracoes?tab=usuarios&success=user_updated");
            } else {
                header("Location: /configuracoes/usuarios/edit/{$id}?error=update_failed");
            }
        } catch (\Exception $e) {
            AuditLogger::log('user_update_exception', ['error' => $e->getMessage(), 'user_id' => $id]);
            header("Location: /configuracoes/usuarios/edit/{$id}?error=exception");
        }
        exit();
    }

    // ================================================================
    // USUÁRIOS — RESET DE SENHA
    // ================================================================

    public function usuariosResetPassword(int $id): void
    {
        if (!Auth::can('manage_users')) {
            header("Location: /configuracoes?error=unauthorized"); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario)) {
            header("Location: /configuracoes?tab=usuarios&error=cannot_reset"); exit();
        }
        $reference = $this->userDiagnosticReference();
        try {
            $this->passwordResetModel->invalidateUserTokens($id);
            $tokenData = $this->passwordResetModel->createForUser($id);
            $emailSent = Mail::sendPasswordResetLink(
                (string) $usuario->email,
                $this->passwordResetUrl($tokenData['raw']),
                (int) $currentUser->id
            );
            if (!$emailSent) {
                throw new \RuntimeException('O serviço de e-mail não confirmou o envio.');
            }

            AuditLogger::log('reset_user_password', [
                'reference' => $reference,
                'reset_by' => $currentUser->id,
                'user_id' => $id,
                'email_sent' => true,
            ]);
            header('Location: /configuracoes?tab=usuarios&success=password_reset');
        } catch (\Throwable $exception) {
            AuditLogger::log('password_reset_exception', [
                'reference' => $reference,
                'reset_by' => $currentUser->id,
                'user_id' => $id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            header('Location: /configuracoes?tab=usuarios&error=reset_failed&ref=' . rawurlencode($reference));
        }
        exit();
    }

    // ================================================================
    // USUÁRIOS — TOGGLE STATUS
    // ================================================================

    public function usuariosToggleStatus(int $id): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        if (!Auth::can('manage_users')) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit();
        }
        $usuario     = $this->userModel->findById($id);
        $currentUser = Auth::user();
        if (!$usuario || !$this->canManageUser($currentUser, $usuario) || $usuario->id == $currentUser->id) {
            echo json_encode(['success' => false, 'error' => 'Cannot toggle this user']); exit();
        }
        $novoStatus = ($usuario->status ?? 'ativo') === 'ativo' ? 'inativo' : 'ativo';
        $ok = $this->userModel->setStatusForCurrentTenant($id, $novoStatus);
        AuditLogger::log('toggle_user_status', ['toggled_by' => $currentUser->id, 'user_id' => $id, 'new_status' => $novoStatus]);
        echo json_encode(['success' => $ok, 'status' => $novoStatus]);
        exit();
    }

    // ================================================================
    // Helpers de permissão
    // ================================================================

    private function userDiagnosticReference(): string
    {
        return 'USR-' . strtoupper(substr(hash('sha256', uniqid('', true)), 0, 10));
    }

    private function passwordResetUrl(string $rawToken): string
    {
        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $parts = parse_url($baseUrl);
        if (!is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host'])) {
            $host = strtolower(trim((string) ($_ENV['SAAS_SHARED_HOST'] ?? 'erp.imagiflow.com.br')));
            $host = preg_replace('/[^a-z0-9.-]/', '', $host) ?: 'erp.imagiflow.com.br';
            $baseUrl = 'https://' . $host;
        }

        return $baseUrl . '/reset-password/' . rawurlencode($rawToken);
    }

    private function canManageUser($currentUser, $targetUser): bool
    {
        // Contas privilegiadas são administradas exclusivamente pelo control-plane SaaS.
        if ($targetUser->role === 'superadmin' || $targetUser->role === 'saas_owner') {
            return false;
        }
        return in_array($currentUser->role, ['admin', 'superadmin'], true);
    }

    // ================================================================
    // CONFIGURAÇÕES DE NOTAS FISCAIS (NFS-e Nacional)
    // ================================================================

    /**
     * POST /configuracoes/nfs/salvar
     * Salva as configurações de emissão de NFS-e (Layout Padrão ou Personalizado).
     */
    public function nfsSalvar(): void
    {
        if (!Auth::can('manage_settings')) {
            header('Location: /configuracoes?error=unauthorized');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /configuracoes?tab=notas-fiscais');
            exit();
        }

        $usuarioId = (int) Auth::user()->id;

        $data = [
            'layout_tipo'            => in_array($_POST['layout_tipo'] ?? '', ['padrao', 'personalizado'])
                                        ? $_POST['layout_tipo']
                                        : 'padrao',
            'service_description'    => trim($_POST['service_description'] ?? 'SERVIÇOS DE LAUDO'),
            'observations'           => trim($_POST['observations'] ?? ''),
            'municipal_service_name' => trim($_POST['municipal_service_name'] ?? 'Serviços de Saúde / Radiologia'),
            'municipal_service_code' => trim($_POST['municipal_service_code'] ?? ''),
            'municipal_service_id'   => trim($_POST['municipal_service_id'] ?? ''),
            'cnae'                   => preg_replace('/\D/', '', $_POST['cnae'] ?? '8640205'),
            'nbs_codigo'             => trim($_POST['nbs_codigo'] ?? ''),
            'deductions'             => (float) ($_POST['deductions'] ?? 0),
            'retain_iss'             => isset($_POST['retain_iss']) ? 1 : 0,
            'iss_aliquota'           => (float) ($_POST['iss_aliquota'] ?? 0),
            'pis_aliquota'           => (float) ($_POST['pis_aliquota'] ?? 0),
            'cofins_aliquota'        => (float) ($_POST['cofins_aliquota'] ?? 0),
            'csll_aliquota'          => (float) ($_POST['csll_aliquota'] ?? 0),
            'inss_aliquota'          => (float) ($_POST['inss_aliquota'] ?? 0),
            'ir_aliquota'            => (float) ($_POST['ir_aliquota'] ?? 0),
            'json_template'          => trim($_POST['json_template'] ?? ''),
            'emite_portal_nacional'  => 1,
            'serie_nf'               => trim($_POST['serie_nf'] ?? ''),
        ];

        // Validar JSON template se layout personalizado
        if ($data['layout_tipo'] === 'personalizado' && !empty($data['json_template'])) {
            $decoded = json_decode($data['json_template'], true);
            if (!is_array($decoded)) {
                header('Location: /configuracoes?tab=notas-fiscais&error=json_invalido');
                exit();
            }
        }

        $ok = $this->configNfsModel->upsert($usuarioId, $data);

        if ($ok) {
            header('Location: /configuracoes?tab=notas-fiscais&success=nfs_salvo');
        } else {
            header('Location: /configuracoes?tab=notas-fiscais&error=save_failed');
        }
        exit();
    }

    // ================================================================
    // FINANCEIRO — Configurações de cobrança
    // ================================================================

    /**
     * POST /configuracoes/financeiro/salvar
     * Salva as configurações financeiras do tenant (juros, multa, desconto, meio padrão).
     */
    public function financeiroSalvar(): void
    {
        if (!Auth::can('manage_settings')) {
            header('Location: /configuracoes?error=unauthorized');
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /configuracoes?tab=financeiro');
            exit();
        }

        $usuarioId = (int) Auth::user()->id;

        $meiosValidos = ['pix', 'boleto', 'checkout', 'cartao', 'dinheiro', 'transferencia', 'outro', ''];
        $meioPadrao   = in_array($_POST['meio_pagamento_padrao'] ?? '', $meiosValidos)
                        ? ($_POST['meio_pagamento_padrao'] ?? 'checkout')
                        : 'checkout';

        // Checkboxes para checkout_meios_habilitados
        $checkoutMeios = [];
        if (isset($_POST['checkout_boleto']))  $checkoutMeios[] = 'BOLETO';
        if (isset($_POST['checkout_pix']))     $checkoutMeios[] = 'PIX';
        if (isset($_POST['checkout_cartao']))  $checkoutMeios[] = 'CREDIT_CARD';
        $checkoutMeiosStr = !empty($checkoutMeios) ? implode(',', $checkoutMeios) : 'BOLETO,PIX,CREDIT_CARD';

        $data = [
            'meio_pagamento_padrao'       => $meioPadrao,
            'juros_tipo'                  => in_array($_POST['juros_tipo'] ?? '', ['PERCENTAGE','FIXED'])
                                             ? $_POST['juros_tipo'] : 'PERCENTAGE',
            'juros_valor'                 => max(0, (float)($_POST['juros_valor'] ?? 1.00)),
            'juros_dias_carencia'         => max(0, (int)($_POST['juros_dias_carencia'] ?? 0)),
            'multa_tipo'                  => in_array($_POST['multa_tipo'] ?? '', ['PERCENTAGE','FIXED'])
                                             ? $_POST['multa_tipo'] : 'PERCENTAGE',
            'multa_valor'                 => max(0, (float)($_POST['multa_valor'] ?? 2.00)),
            'multa_dias_carencia'         => max(0, (int)($_POST['multa_dias_carencia'] ?? 0)),
            'desconto_ativo'              => isset($_POST['desconto_ativo']) ? 1 : 0,
            'desconto_tipo'               => in_array($_POST['desconto_tipo'] ?? '', ['PERCENTAGE','FIXED'])
                                             ? $_POST['desconto_tipo'] : 'PERCENTAGE',
            'desconto_valor'              => max(0, (float)($_POST['desconto_valor'] ?? 0)),
            'desconto_dias_antes'         => max(0, (int)($_POST['desconto_dias_antes'] ?? 0)),
            'desconto_limite_data'        => !empty($_POST['desconto_limite_data'])
                                             ? $_POST['desconto_limite_data'] : null,
            'boleto_dias_vencimento'      => max(1, (int)($_POST['boleto_dias_vencimento'] ?? 3)),
            'boleto_instrucoes'           => substr(trim($_POST['boleto_instrucoes'] ?? ''), 0, 500),
            'boleto_aceite'               => ($_POST['boleto_aceite'] ?? 'N') === 'A' ? 'A' : 'N',
            'boleto_banco'                => trim($_POST['boleto_banco'] ?? ''),
            'pix_expiracao_segundos'      => max(300, (int)($_POST['pix_expiracao_segundos'] ?? 86400)),
            'pix_chave'                   => substr(trim($_POST['pix_chave'] ?? ''), 0, 150),
            'cartao_max_parcelas'         => min(12, max(1, (int)($_POST['cartao_max_parcelas'] ?? 1))),
            'cartao_parcela_minima'       => max(0, (float)($_POST['cartao_parcela_minima'] ?? 50.00)),
            'cartao_juros_parcelamento'   => max(0, (float)($_POST['cartao_juros_parcelamento'] ?? 0)),
            'checkout_meios_habilitados'  => $checkoutMeiosStr,
            'notificar_email'             => isset($_POST['notificar_email']) ? 1 : 0,
            'notificar_sms'               => isset($_POST['notificar_sms']) ? 1 : 0,
            'notificar_whatsapp'          => isset($_POST['notificar_whatsapp']) ? 1 : 0,
            'dias_aviso_vencimento'       => max(0, (int)($_POST['dias_aviso_vencimento'] ?? 3)),
        ];

        $ok = $this->configFinanceiroModel->upsert($usuarioId, $data);

        if ($ok) {
            header('Location: /configuracoes?tab=financeiro&success=financeiro_salvo');
        } else {
            header('Location: /configuracoes?tab=financeiro&error=save_failed');
        }
        exit();
    }
}
