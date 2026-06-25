<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Notificacao;
use App\Models\NotificacaoConfigAlerta;
use App\Models\User;
use PDO;

/**
 * NotificacaoService
 *
 * Engine de geração automática de notificações.
 * Chamado pelo CronController ou manualmente para gerar
 * notificações de todos os usuários ativos do sistema.
 */
class NotificacaoService
{
    private PDO $pdo;
    private Notificacao $notifModel;
    private NotificacaoConfigAlerta $configModel;

    public function __construct()
    {
        $this->pdo         = Database::getInstance();
        $this->notifModel  = new Notificacao();
        $this->configModel = new NotificacaoConfigAlerta();
    }

    /**
     * Executa todos os geradores de notificação para todos os usuários ativos.
     * Retorna o total de notificações criadas.
     */
    public function gerarTodasNotificacoes(): int
    {
        try {
            $total = 0;

            // Buscar todos os usuários ativos
            $stmt  = $this->pdo->query("SELECT id FROM users WHERE status = 'active' OR status = 1 OR status IS NULL");
            $users = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($users as $user) {
                $uid    = (int) $user->id;
                $total += $this->gerarParaUsuario($uid);
            }

            // Limpar notificações antigas (>60 dias, já lidas)
            $this->notifModel->limparAntigas(60);

            return $total;
        } catch (\Throwable $e) {
            error_log('[NotificacaoService] gerarTodasNotificacoes: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Gera notificações para um único usuário.
     */
    public function gerarParaUsuario(int $usuarioId): int
    {
        $total = 0;
        try { $total += $this->gerarCrmRetornoOportunidades($usuarioId); } catch (\Throwable $e) { error_log('[Notif] crm_retorno: ' . $e->getMessage()); }
        try { $total += $this->gerarCrmRetornoLeads($usuarioId); } catch (\Throwable $e) { error_log('[Notif] crm_leads: ' . $e->getMessage()); }
        try { $total += $this->gerarContasPagarVencendo($usuarioId); } catch (\Throwable $e) { error_log('[Notif] cp_vencendo: ' . $e->getMessage()); }
        try { $total += $this->gerarContasPagarVencidas($usuarioId); } catch (\Throwable $e) { error_log('[Notif] cp_vencida: ' . $e->getMessage()); }
        try { $total += $this->gerarContasReceberVencendo($usuarioId); } catch (\Throwable $e) { error_log('[Notif] cr_vencendo: ' . $e->getMessage()); }
        try { $total += $this->gerarContasReceberVencidas($usuarioId); } catch (\Throwable $e) { error_log('[Notif] cr_vencida: ' . $e->getMessage()); }
        try { $total += $this->gerarOportunidadesFechamento($usuarioId); } catch (\Throwable $e) { error_log('[Notif] op_fechamento: ' . $e->getMessage()); }
        try { $total += $this->gerarContratosVencendo($usuarioId); } catch (\Throwable $e) { error_log('[Notif] contratos: ' . $e->getMessage()); }
        return $total;
    }

    // ----------------------------------------------------------------
    // CRM — Oportunidades com retorno vencendo
    // ----------------------------------------------------------------
    private function gerarCrmRetornoOportunidades(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'crm_retorno_vencendo')) return 0;
        $dias = $this->configModel->getDiasAntecedencia($uid, 'crm_retorno_vencendo');

        $stmt = $this->pdo->prepare(
            "SELECT id, titulo_oportunidade, data_proximo_contato
             FROM crm_oportunidades
             WHERE usuario_id = ?
               AND status_oportunidade = 'aberta'
               AND data_proximo_contato IS NOT NULL
               AND data_proximo_contato BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY data_proximo_contato ASC"
        );
        $stmt->execute([$uid, $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $op) {
            $dataFmt = date('d/m/Y', strtotime($op->data_proximo_contato));
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'crm_retorno_vencendo',
                'titulo'          => "Retorno previsto: {$op->titulo_oportunidade}",
                'mensagem'        => "A oportunidade \"{$op->titulo_oportunidade}\" tem retorno agendado para {$dataFmt}.",
                'link'            => "/crm/oportunidades/edit/{$op->id}",
                'referencia_tipo' => 'oportunidade',
                'referencia_id'   => $op->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // CRM — Leads com retorno vencendo
    // ----------------------------------------------------------------
    private function gerarCrmRetornoLeads(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'crm_lead_retorno_vencendo')) return 0;
        $dias = $this->configModel->getDiasAntecedencia($uid, 'crm_lead_retorno_vencendo');

        $stmt = $this->pdo->prepare(
            "SELECT id, nome_lead, data_proximo_contato
             FROM crm_leads
             WHERE usuario_id = ?
               AND status_lead NOT IN ('convertido','perdido','inativo')
               AND data_proximo_contato IS NOT NULL
               AND data_proximo_contato BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY data_proximo_contato ASC"
        );
        $stmt->execute([$uid, $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $lead) {
            $dataFmt = date('d/m/Y', strtotime($lead->data_proximo_contato));
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'crm_lead_retorno_vencendo',
                'titulo'          => "Retorno previsto: {$lead->nome_lead}",
                'mensagem'        => "O lead \"{$lead->nome_lead}\" tem retorno agendado para {$dataFmt}.",
                'link'            => "/crm/leads/edit/{$lead->id}",
                'referencia_tipo' => 'lead',
                'referencia_id'   => $lead->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Contas a Pagar — vencendo em breve
    // ----------------------------------------------------------------
    private function gerarContasPagarVencendo(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'conta_pagar_vencendo')) return 0;
        $dias = $this->configModel->getDiasAntecedencia($uid, 'conta_pagar_vencendo');

        $stmt = $this->pdo->prepare(
            "SELECT id, descricao, valor, data_vencimento
             FROM contas_pagar
             WHERE usuario_id = ?
               AND status = 'aberta'
               AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY data_vencimento ASC"
        );
        $stmt->execute([$uid, $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $cp) {
            $dataFmt  = date('d/m/Y', strtotime($cp->data_vencimento));
            $valorFmt = 'R$ ' . number_format((float)$cp->valor, 2, ',', '.');
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'conta_pagar_vencendo',
                'titulo'          => "Conta a pagar vence em {$dataFmt}",
                'mensagem'        => "{$cp->descricao} — {$valorFmt} vence em {$dataFmt}.",
                'link'            => "/financeiro/pagar",
                'referencia_tipo' => 'conta_pagar',
                'referencia_id'   => $cp->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Contas a Pagar — vencidas (em atraso)
    // ----------------------------------------------------------------
    private function gerarContasPagarVencidas(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'conta_pagar_vencida')) return 0;

        $stmt = $this->pdo->prepare(
            "SELECT id, descricao, valor, data_vencimento
             FROM contas_pagar
             WHERE usuario_id = ?
               AND status = 'aberta'
               AND data_vencimento < CURDATE()
             ORDER BY data_vencimento ASC
             LIMIT 20"
        );
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $cp) {
            $dataFmt  = date('d/m/Y', strtotime($cp->data_vencimento));
            $valorFmt = 'R$ ' . number_format((float)$cp->valor, 2, ',', '.');
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'conta_pagar_vencida',
                'titulo'          => "Conta a pagar VENCIDA desde {$dataFmt}",
                'mensagem'        => "{$cp->descricao} — {$valorFmt} está vencida desde {$dataFmt}.",
                'link'            => "/financeiro/pagar",
                'referencia_tipo' => 'conta_pagar',
                'referencia_id'   => $cp->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Contas a Receber — vencendo em breve
    // ----------------------------------------------------------------
    private function gerarContasReceberVencendo(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'conta_receber_vencendo')) return 0;
        $dias = $this->configModel->getDiasAntecedencia($uid, 'conta_receber_vencendo');

        $stmt = $this->pdo->prepare(
            "SELECT id, descricao, valor, data_vencimento
             FROM contas_receber
             WHERE usuario_id = ?
               AND status = 'aberta'
               AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY data_vencimento ASC"
        );
        $stmt->execute([$uid, $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $cr) {
            $dataFmt  = date('d/m/Y', strtotime($cr->data_vencimento));
            $valorFmt = 'R$ ' . number_format((float)$cr->valor, 2, ',', '.');
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'conta_receber_vencendo',
                'titulo'          => "Conta a receber vence em {$dataFmt}",
                'mensagem'        => "{$cr->descricao} — {$valorFmt} vence em {$dataFmt}.",
                'link'            => "/financeiro/receber",
                'referencia_tipo' => 'conta_receber',
                'referencia_id'   => $cr->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Contas a Receber — vencidas (em atraso)
    // ----------------------------------------------------------------
    private function gerarContasReceberVencidas(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'conta_receber_vencida')) return 0;

        $stmt = $this->pdo->prepare(
            "SELECT id, descricao, valor, data_vencimento
             FROM contas_receber
             WHERE usuario_id = ?
               AND status = 'aberta'
               AND data_vencimento < CURDATE()
             ORDER BY data_vencimento ASC
             LIMIT 20"
        );
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $cr) {
            $dataFmt  = date('d/m/Y', strtotime($cr->data_vencimento));
            $valorFmt = 'R$ ' . number_format((float)$cr->valor, 2, ',', '.');
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'conta_receber_vencida',
                'titulo'          => "Conta a receber VENCIDA desde {$dataFmt}",
                'mensagem'        => "{$cr->descricao} — {$valorFmt} está vencida desde {$dataFmt}.",
                'link'            => "/financeiro/receber",
                'referencia_tipo' => 'conta_receber',
                'referencia_id'   => $cr->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // CRM — Oportunidades com data de fechamento prevista próxima
    // ----------------------------------------------------------------
    private function gerarOportunidadesFechamento(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'oportunidade_fechamento')) return 0;
        $dias = $this->configModel->getDiasAntecedencia($uid, 'oportunidade_fechamento');

        $stmt = $this->pdo->prepare(
            "SELECT id, titulo_oportunidade, data_fechamento_prevista, valor_estimado
             FROM crm_oportunidades
             WHERE usuario_id = ?
               AND status_oportunidade = 'aberta'
               AND data_fechamento_prevista IS NOT NULL
               AND data_fechamento_prevista BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY data_fechamento_prevista ASC"
        );
        $stmt->execute([$uid, $dias]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = 0;
        foreach ($rows as $op) {
            $dataFmt = date('d/m/Y', strtotime($op->data_fechamento_prevista));
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'oportunidade_fechamento',
                'titulo'          => "Fechamento previsto: {$op->titulo_oportunidade}",
                'mensagem'        => "A oportunidade \"{$op->titulo_oportunidade}\" tem fechamento previsto para {$dataFmt}.",
                'link'            => "/crm/oportunidades/edit/{$op->id}",
                'referencia_tipo' => 'oportunidade',
                'referencia_id'   => $op->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Contratos — vencendo em breve
    // ----------------------------------------------------------------
    private function gerarContratosVencendo(int $uid): int
    {
        if (!$this->configModel->isAtivo($uid, 'contrato_vencendo')) return 0;
        $dias = $this->configModel->getDiasAntecedencia($uid, 'contrato_vencendo');

        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.id, c.numero_contrato, c.data_fim, cl.nome_fantasia AS cliente_nome
                 FROM contratos c
                 LEFT JOIN clientes cl ON cl.id = c.cliente_id
                 WHERE c.usuario_id = ?
                   AND c.status = 'ativo'
                   AND c.data_fim IS NOT NULL
                   AND c.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                 ORDER BY c.data_fim ASC"
            );
            $stmt->execute([$uid, $dias]);
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return 0; // tabela pode não ter usuario_id
        }

        $count = 0;
        foreach ($rows as $ct) {
            $dataFmt = date('d/m/Y', strtotime($ct->data_fim));
            $id = $this->notifModel->criar([
                'usuario_id'      => $uid,
                'tipo'            => 'contrato_vencendo',
                'titulo'          => "Contrato vencendo: #{$ct->numero_contrato}",
                'mensagem'        => "O contrato #{$ct->numero_contrato} ({$ct->cliente_nome}) vence em {$dataFmt}.",
                'link'            => "/contratos",
                'referencia_tipo' => 'contrato',
                'referencia_id'   => $ct->id,
            ]);
            if ($id) $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------------
    // Método público para criar notificação avulsa (ex: marketing)
    // ----------------------------------------------------------------
    public function criar(array $data): int|false
    {
        return $this->notifModel->criar($data);
    }
}
