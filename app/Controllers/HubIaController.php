<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Models\HubIaAgente;
use App\Models\HubIaConector;
use App\Models\HubIaHistorico;
use App\Models\HubIaLog;

class HubIaController extends Controller
{
    private HubIaConector $conectorModel;
    private HubIaAgente $agenteModel;
    private HubIaHistorico $historicoModel;
    private HubIaLog $logModel;
    private Logger $logger;

    public function __construct()
    {
        $this->conectorModel  = new HubIaConector();
        $this->agenteModel    = new HubIaAgente();
        $this->historicoModel = new HubIaHistorico();
        $this->logModel       = new HubIaLog();
        $this->logger         = new Logger();
    }

    // =========================================================
    // DASHBOARD
    // =========================================================
    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        $conectores = $this->conectorModel->listar();
        $statusPorProvider = [];
        foreach (HubIaConector::PROVIDERS as $p) {
            $statusPorProvider[$p] = null; // null = não configurado
        }
        foreach ($conectores as $c) {
            if ($c->status === 'ativo') {
                $statusPorProvider[$c->provider] = $c->ultimo_teste_status ?? 'nao_testado';
            }
        }

        View::render('hub_ia.dashboard', [
            'title'              => 'HUB I.A',
            'kpis'               => $this->historicoModel->kpisHoje(),
            'robosAtivos'        => count(array_filter($this->agenteModel->listar(), fn ($a) => (bool) $a->ativo)),
            'statusPorProvider'  => $statusPorProvider,
            'consumoPorModulo'   => $this->historicoModel->consumoPorModulo(30),
            'consumoDiario'      => $this->historicoModel->consumoDiario(14),
            'tempoMedioMs'       => $this->historicoModel->tempoMedioResposta(),
            'breadcrumb'         => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia'],
            '_layout'            => 'erp',
        ]);
    }

    // =========================================================
    // RELATÓRIOS — Histórico / Logs / Custos / Monitoramento (abas)
    // =========================================================
    public function relatorios(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        $aba = $_GET['aba'] ?? 'historico';

        View::render('hub_ia.relatorios', [
            'title'            => 'HUB I.A — Relatórios',
            'aba'              => $aba,
            'historico'        => $aba === 'historico' ? $this->historicoModel->listar($_GET, 100) : [],
            'logs'             => $aba === 'logs' ? $this->logModel->listar(200) : [],
            'custosPorProvider'=> $aba === 'custos' ? $this->historicoModel->custosPorProvider(30) : [],
            'consumoDiario'    => $aba === 'custos' ? $this->historicoModel->consumoDiario(30) : [],
            'taxaFalhas'       => $aba === 'monitoramento' ? $this->logModel->taxaFalhas(7) : 0,
            'tempoMedioMs'     => $aba === 'monitoramento' ? $this->historicoModel->tempoMedioResposta() : 0,
            'agentes'          => $this->agenteModel->listar(),
            'breadcrumb'       => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Relatórios' => '/hub-ia/relatorios'],
            '_layout'          => 'erp',
        ]);
    }
}
