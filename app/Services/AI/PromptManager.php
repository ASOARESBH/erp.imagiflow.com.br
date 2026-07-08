<?php

namespace App\Services\AI;

use App\Models\HubIaPrompt;

/**
 * Facade sobre HubIaPrompt para módulos que precisam reaproveitar prompts
 * cadastrados no HUB I.A (ex.: RDV chamando o prompt "Classificação de Despesa").
 */
class PromptManager
{
    private HubIaPrompt $model;

    public function __construct()
    {
        $this->model = new HubIaPrompt();
    }

    public function listarAtivos(): array
    {
        return $this->model->listar(true);
    }

    public function buscarPorNome(string $nome): ?object
    {
        foreach ($this->model->listar(true) as $p) {
            if (strcasecmp($p->nome, $nome) === 0) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Retorna o conteúdo do prompt já com as variáveis {{nome}} substituídas.
     */
    public function renderizar(int $promptId, array $variaveis = []): ?string
    {
        $prompt = $this->model->findById($promptId);
        if (!$prompt) {
            return null;
        }
        return HubIaPrompt::interpolar($prompt->conteudo, $variaveis);
    }
}
