<?php

namespace App\Services\AI;

use App\Models\HubIaAgente;
use App\Models\HubIaAgentePermissao;

/**
 * Facade sobre HubIaAgente/HubIaAgentePermissao. Módulos que expõem um botão
 * "Perguntar à IA" devem checar podeAcessarModulo() antes de chamar
 * AIService::perguntarAgente(), para respeitar o escopo configurado no HUB I.A.
 */
class AgentManager
{
    private HubIaAgente $agenteModel;
    private HubIaAgentePermissao $permissaoModel;

    public function __construct()
    {
        $this->agenteModel    = new HubIaAgente();
        $this->permissaoModel = new HubIaAgentePermissao();
    }

    public function listarAtivos(): array
    {
        return array_values(array_filter($this->agenteModel->listar(), fn ($a) => (bool) $a->ativo));
    }

    public function buscar(int $agenteId): ?object
    {
        $agente = $this->agenteModel->findById($agenteId);
        return $agente ?: null;
    }

    public function podeAcessarModulo(int $agenteId, string $modulo): bool
    {
        if (!in_array($modulo, HubIaAgentePermissao::MODULOS, true)) {
            return false;
        }
        return $this->permissaoModel->podeAcessarModulo($agenteId, $modulo);
    }

    public function permissoesDoAgente(int $agenteId): array
    {
        return $this->permissaoModel->listarPorAgente($agenteId);
    }
}
