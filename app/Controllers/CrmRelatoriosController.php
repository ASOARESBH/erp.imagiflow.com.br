<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\CrmRelatorio;
use App\Models\CrmLead;
use App\Models\CrmOportunidade;
use App\Models\CrmInteracao;
use App\Models\User;

/**
 * CrmRelatoriosController — Relatórios analíticos do CRM.
 *
 * Rotas:
 *   GET  /crm/relatorios              → index()       Dashboard geral
 *   GET  /crm/relatorios/leads        → leads()       Relatório de Leads
 *   GET  /crm/relatorios/oportunidades → oportunidades() Relatório de Oportunidades
 *   GET  /crm/relatorios/interacoes   → interacoes()  Relatório de Interações
 *   GET  /crm/relatorios/exportar     → exportar()    Exportação CSV
 */
class CrmRelatoriosController extends Controller
{
    private CrmRelatorio   $relatorioModel;
    private CrmLead        $leadModel;
    private CrmOportunidade $opModel;
    private CrmInteracao   $interacaoModel;
    private User           $userModel;
    private Logger         $logger;

    public function __construct()
    {
        $this->relatorioModel = new CrmRelatorio();
        $this->leadModel      = new CrmLead();
        $this->opModel        = new CrmOportunidade();
        $this->interacaoModel = new CrmInteracao();
        $this->userModel      = new User();
        $this->logger         = new Logger();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function usuarioId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function isAdmin(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        return in_array(strtolower($role), ['admin', 'superadmin'], true);
    }

    /**
     * Monta os filtros a partir dos parâmetros GET,
     * respeitando a restrição de usuário para não-admins.
     */
    private function getFiltros(array $extra = []): array
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        $filtros = [
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-01'),
            'data_fim'    => $_GET['data_fim']    ?? date('Y-m-d'),
        ];

        // Admin pode filtrar por qualquer usuário; 0 = todos
        if ($isAdmin) {
            $filtroUid = (int) ($_GET['usuario_id'] ?? 0);
            if ($filtroUid > 0) {
                $filtros['usuario_id'] = $filtroUid;
            }
        } else {
            $filtros['usuario_id'] = $uid;
        }

        return array_merge($filtros, $extra);
    }

    // ---------------------------------------------------------------
    // GET /crm/relatorios — Dashboard geral
    // ---------------------------------------------------------------

    public function index(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();
        $filtros = $this->getFiltros();

        $kpisLeads         = $this->relatorioModel->kpisLeads($filtros);
        $kpisOportunidades = $this->relatorioModel->kpisOportunidades($filtros);
        $kpisInteracoes    = $this->relatorioModel->kpisInteracoes($filtros);
        $rankingResp       = $this->relatorioModel->rankingResponsaveis($filtros);
        $evolLeads         = $this->relatorioModel->evolucaoMensalLeads($filtros);
        $evolOps           = $this->relatorioModel->evolucaoMensalOportunidades($filtros);
        $usuariosAtivos    = $isAdmin ? $this->relatorioModel->findUsuariosAtivos() : [];

        $this->logger->info('[CRM] Relatórios — dashboard acessado', ['usuario_id' => $uid]);

        View::render('crm/relatorios/index', [
            'title'             => 'Relatórios CRM',
            'isAdmin'           => $isAdmin,
            'filtros'           => $filtros,
            'usuariosAtivos'    => $usuariosAtivos,
            'kpisLeads'         => $kpisLeads,
            'kpisOportunidades' => $kpisOportunidades,
            'kpisInteracoes'    => $kpisInteracoes,
            'rankingResp'       => $rankingResp,
            'evolLeads'         => $evolLeads,
            'evolOps'           => $evolOps,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /crm/relatorios/leads
    // ---------------------------------------------------------------

    public function leads(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        $filtros = $this->getFiltros([
            'status_lead' => $_GET['status_lead'] ?? '',
            'origem'      => $_GET['origem']      ?? '',
            'segmento'    => $_GET['segmento']    ?? '',
        ]);

        $leads           = $this->relatorioModel->listarLeads($filtros);
        $kpis            = $this->relatorioModel->kpisLeads($filtros);
        $evolucao        = $this->relatorioModel->evolucaoMensalLeads($filtros);
        $usuariosAtivos  = $isAdmin ? $this->relatorioModel->findUsuariosAtivos() : [];

        $this->logger->info('[CRM] Relatório de Leads acessado', [
            'usuario_id' => $uid,
            'filtros'    => $filtros,
            'total'      => count($leads),
        ]);

        View::render('crm/relatorios/leads', [
            'title'          => 'Relatório de Leads',
            'isAdmin'        => $isAdmin,
            'filtros'        => $filtros,
            'leads'          => $leads,
            'kpis'           => $kpis,
            'evolucao'       => $evolucao,
            'usuariosAtivos' => $usuariosAtivos,
            'statusList'     => CrmLead::STATUS,
            'origensList'    => CrmLead::ORIGENS,
            'segmentosList'  => CrmLead::SEGMENTOS,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /crm/relatorios/oportunidades
    // ---------------------------------------------------------------

    public function oportunidades(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        $filtros = $this->getFiltros([
            'etapa_funil'          => $_GET['etapa_funil']          ?? '',
            'status_oportunidade'  => $_GET['status_oportunidade']  ?? '',
            'tipo_contrato'        => $_GET['tipo_contrato']        ?? '',
            'modalidade'           => $_GET['modalidade']           ?? '',
        ]);

        $oportunidades  = $this->relatorioModel->listarOportunidades($filtros);
        $kpis           = $this->relatorioModel->kpisOportunidades($filtros);
        $evolucao       = $this->relatorioModel->evolucaoMensalOportunidades($filtros);
        $ranking        = $this->relatorioModel->rankingResponsaveis($filtros);
        $usuariosAtivos = $isAdmin ? $this->relatorioModel->findUsuariosAtivos() : [];

        $this->logger->info('[CRM] Relatório de Oportunidades acessado', [
            'usuario_id' => $uid,
            'filtros'    => $filtros,
            'total'      => count($oportunidades),
        ]);

        View::render('crm/relatorios/oportunidades', [
            'title'          => 'Relatório de Oportunidades',
            'isAdmin'        => $isAdmin,
            'filtros'        => $filtros,
            'oportunidades'  => $oportunidades,
            'kpis'           => $kpis,
            'evolucao'       => $evolucao,
            'ranking'        => $ranking,
            'usuariosAtivos' => $usuariosAtivos,
            'etapasList'     => CrmOportunidade::ETAPAS,
            'statusList'     => CrmOportunidade::STATUS,
            'tiposContrato'  => CrmOportunidade::TIPOS_CONTRATO,
            'modalidades'    => CrmOportunidade::MODALIDADES,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /crm/relatorios/interacoes
    // ---------------------------------------------------------------

    public function interacoes(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        $filtros = $this->getFiltros([
            'tipo_interacao' => $_GET['tipo_interacao'] ?? '',
            'related_type'   => $_GET['related_type']   ?? '',
        ]);

        $interacoes     = $this->relatorioModel->listarInteracoes($filtros);
        $kpis           = $this->relatorioModel->kpisInteracoes($filtros);
        $usuariosAtivos = $isAdmin ? $this->relatorioModel->findUsuariosAtivos() : [];

        $this->logger->info('[CRM] Relatório de Interações acessado', [
            'usuario_id' => $uid,
            'filtros'    => $filtros,
            'total'      => count($interacoes),
        ]);

        View::render('crm/relatorios/interacoes', [
            'title'          => 'Relatório de Interações',
            'isAdmin'        => $isAdmin,
            'filtros'        => $filtros,
            'interacoes'     => $interacoes,
            'kpis'           => $kpis,
            'usuariosAtivos' => $usuariosAtivos,
            'tiposInteracao' => CrmInteracao::TIPOS,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /crm/relatorios/exportar?tipo=leads|oportunidades|interacoes
    // ---------------------------------------------------------------

    public function exportar(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();
        $tipo    = $_GET['tipo'] ?? 'leads';

        $filtros = $this->getFiltros([
            'status_lead'         => $_GET['status_lead']         ?? '',
            'origem'              => $_GET['origem']              ?? '',
            'segmento'            => $_GET['segmento']            ?? '',
            'etapa_funil'         => $_GET['etapa_funil']         ?? '',
            'status_oportunidade' => $_GET['status_oportunidade'] ?? '',
            'tipo_contrato'       => $_GET['tipo_contrato']       ?? '',
            'modalidade'          => $_GET['modalidade']          ?? '',
            'tipo_interacao'      => $_GET['tipo_interacao']      ?? '',
            'related_type'        => $_GET['related_type']        ?? '',
        ]);

        switch ($tipo) {
            case 'oportunidades':
                $rows    = $this->relatorioModel->listarOportunidades($filtros);
                $headers = [
                    'ID', 'Título', 'Etapa', 'Status', 'Valor Estimado (R$)',
                    'Probabilidade (%)', 'Tipo Contrato', 'Modalidade',
                    'Volume Mensal', 'Fechamento Previsto', 'Próximo Contato',
                    'Contato', 'Responsável', 'Interações', 'Propostas', 'Criado em',
                ];
                $csvRows = array_map(fn($r) => [
                    $r->id, $r->titulo_oportunidade, $r->etapa_funil, $r->status_oportunidade,
                    number_format((float)$r->valor_estimado, 2, ',', '.'),
                    $r->probabilidade_sucesso ?? '',
                    $r->tipo_contrato ?? '', $r->modalidade_principal ?? '',
                    $r->volume_estimado_mes ?? '',
                    $r->data_fechamento_prevista ?? '', $r->data_proximo_contato ?? '',
                    $r->nome_contato ?? '', $r->responsavel ?? '',
                    $r->total_interacoes, $r->total_propostas,
                    $r->created_at,
                ], $rows);
                $filename = 'crm_oportunidades_' . date('Ymd') . '.csv';
                break;

            case 'interacoes':
                $rows    = $this->relatorioModel->listarInteracoes($filtros);
                $headers = [
                    'ID', 'Tipo', 'Entidade', 'Nome Entidade',
                    'Responsável', 'Data Interação', 'Data Retorno', 'Resumo',
                ];
                $csvRows = array_map(fn($r) => [
                    $r->id, $r->tipo_interacao, $r->related_type,
                    $r->entidade_nome ?? '',
                    $r->responsavel ?? '',
                    $r->data_interacao, $r->data_retorno ?? '',
                    str_replace(["\r", "\n"], ' ', $r->resumo ?? ''),
                ], $rows);
                $filename = 'crm_interacoes_' . date('Ymd') . '.csv';
                break;

            default: // leads
                $rows    = $this->relatorioModel->listarLeads($filtros);
                $headers = [
                    'ID', 'Nome Lead', 'Razão Social', 'E-mail', 'Telefone',
                    'Cidade', 'Estado', 'Status', 'Origem', 'Segmento',
                    'Próximo Contato', 'Responsável', 'Interações', 'Última Interação', 'Criado em',
                ];
                $csvRows = array_map(fn($r) => [
                    $r->id, $r->nome_lead ?? '', $r->razao_social ?? '',
                    $r->email ?? '', $r->telefone ?? '',
                    $r->cidade ?? '', $r->estado ?? '',
                    $r->status_lead ?? '', $r->origem ?? '', $r->segmento_principal ?? '',
                    $r->data_proximo_contato ?? '', $r->responsavel ?? '',
                    $r->total_interacoes, $r->ultima_interacao ?? '',
                    $r->created_at,
                ], $rows);
                $filename = 'crm_leads_' . date('Ymd') . '.csv';
                break;
        }

        $this->logger->info('[CRM] Exportação CSV', [
            'usuario_id' => $uid,
            'tipo'       => $tipo,
            'total'      => count($rows),
        ]);

        // Enviar CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $headers, ';');
        foreach ($csvRows as $row) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit();
    }
}
