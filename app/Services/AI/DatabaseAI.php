<?php

namespace App\Services\AI;

use App\Core\Database;
use App\Models\HubIaBancoConfig;
use PDO;

/**
 * Consulta o banco de dados do ERP a partir de perguntas em linguagem natural.
 * Sempre opera sobre a MESMA conexão configurada em .env (Database::getInstance())
 * — nunca aceita host/usuário/senha alternativos vindos de tela ou de IA.
 *
 * Guardrails (ver também SqlGuard):
 *  1. Só executa se o conector de banco estiver "ativo" (HUB I.A → Banco de Dados).
 *  2. A IA só recebe o schema das tabelas explicitamente liberadas (allowlist).
 *  3. SqlGuard valida: um único comando, começa com SELECT, sem palavras de
 *     escrita/DDL/controle, e só referencia tabelas da allowlist.
 *  4. A consulta roda com `SET SESSION TRANSACTION READ ONLY` (nível banco).
 *  5. LIMIT é forçado (500) caso a IA não tenha incluído um.
 */
class DatabaseAI
{
    private HubIaBancoConfig $configModel;

    public function __construct()
    {
        $this->configModel = new HubIaBancoConfig();
    }

    public function perguntar(string $pergunta, object $conector, string $apiKeyPlain): array
    {
        if (!$this->configModel->isAtivo()) {
            return ['sucesso' => false, 'erro' => 'A consulta ao banco de dados via IA está desativada. Habilite em HUB I.A → Banco de Dados.'];
        }

        $tabelas = $this->configModel->getTabelasLiberadas();
        if (empty($tabelas)) {
            return ['sucesso' => false, 'erro' => 'Nenhuma tabela liberada para consulta. Configure em HUB I.A → Banco de Dados.'];
        }

        $schema = $this->descreverSchema($tabelas);
        if ($schema === '') {
            return ['sucesso' => false, 'erro' => 'Nenhuma das tabelas liberadas foi encontrada no banco.'];
        }

        $systemPrompt =
            "Você é um gerador de SQL para MySQL 5.7. Gere APENAS um único comando SELECT, "
            . "sem explicações, sem markdown, sem ponto e vírgula no final. "
            . "Use SOMENTE as tabelas e colunas abaixo — nunca invente nomes de tabela ou coluna:\n\n"
            . $schema
            . "\n\nRegras: nunca gere INSERT/UPDATE/DELETE/DROP/ALTER ou qualquer comando de escrita. "
            . "Se a pergunta não puder ser respondida com essas tabelas, responda exatamente: "
            . "SELECT 'PERGUNTA_FORA_DO_ESCOPO' AS erro";

        $provider = AIProviderFactory::fromConector($conector, $apiKeyPlain);
        $resultado = $provider->chat(
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $pergunta],
            ],
            ['modelo' => $conector->modelo, 'temperatura' => 0.0, 'max_tokens' => 500, 'timeout' => $conector->timeout_segundos]
        );

        if (!$resultado['sucesso']) {
            return ['sucesso' => false, 'erro' => $resultado['erro'] ?? 'Falha ao gerar SQL.'];
        }

        $sqlBruto = self::limparMarkdown((string) $resultado['texto']);

        $validacao = SqlGuard::validar($sqlBruto, $tabelas);
        if (!$validacao['valido']) {
            return [
                'sucesso'    => false,
                'erro'       => 'SQL gerado foi rejeitado pela validação de segurança: ' . $validacao['motivo'],
                'sql_gerado' => $sqlBruto,
            ];
        }

        return $this->executarSomenteLeitura($validacao['sql_limitado'], $resultado);
    }

    private function executarSomenteLeitura(string $sql, array $resultadoIA): array
    {
        $pdo = Database::getInstance();
        try {
            $pdo->exec('SET SESSION TRANSACTION READ ONLY');
            $stmt   = $pdo->query($sql);
            $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'sucesso'         => true,
                'sql_gerado'      => $sql,
                'linhas'          => $linhas,
                'total_linhas'    => count($linhas),
                'tokens_prompt'   => $resultadoIA['tokens_prompt']   ?? null,
                'tokens_resposta' => $resultadoIA['tokens_resposta'] ?? null,
                'tokens_total'    => $resultadoIA['tokens_total']    ?? null,
            ];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao executar a consulta: ' . $e->getMessage(), 'sql_gerado' => $sql];
        } finally {
            try {
                $pdo->exec('SET SESSION TRANSACTION READ WRITE');
            } catch (\Throwable $ignored) {
                // melhor esforço — não deve derrubar a resposta já obtida
            }
        }
    }

    private function descreverSchema(array $tabelas): string
    {
        $pdo    = Database::getInstance();
        $linhas = [];

        foreach ($tabelas as $tabela) {
            // sanity check: só identificadores válidos chegam ao information_schema
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tabela)) {
                continue;
            }
            try {
                $stmt = $pdo->prepare(
                    "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t ORDER BY ORDINAL_POSITION"
                );
                $stmt->execute([':t' => $tabela]);
                $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!$cols) {
                    continue;
                }
                $colsTxt = implode(', ', array_map(fn ($c) => "{$c['COLUMN_NAME']} ({$c['DATA_TYPE']})", $cols));
                $linhas[] = "- {$tabela}: {$colsTxt}";
            } catch (\Throwable $e) {
                continue;
            }
        }

        return implode("\n", $linhas);
    }

    private static function limparMarkdown(string $sql): string
    {
        $sql = preg_replace('/^```sql\s*/i', '', trim($sql));
        $sql = preg_replace('/^```\s*/', '', (string) $sql);
        $sql = preg_replace('/```\s*$/', '', (string) $sql);
        return trim((string) $sql);
    }
}
