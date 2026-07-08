<?php

namespace App\Services\AI;

/**
 * Validador de segurança para SQL gerado por IA. Esta é a camada de defesa
 * em nível de aplicação; a camada de defesa em nível de banco é o
 * `SET SESSION TRANSACTION READ ONLY` aplicado em DatabaseAI antes de
 * executar a consulta. As duas juntas formam o guardrail — nenhuma delas
 * sozinha deve ser considerada suficiente.
 *
 * Não substitui um usuário de banco dedicado e somente-leitura — recomenda-se
 * fortemente configurar um usuário MySQL com GRANT SELECT apenas nas tabelas
 * liberadas, como camada adicional (fora do escopo desta aplicação PHP).
 */
class SqlGuard
{
    private const PALAVRAS_PROIBIDAS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 'GRANT', 'REVOKE',
        'CREATE', 'REPLACE', 'RENAME', 'LOCK', 'UNLOCK', 'CALL', 'EXEC', 'EXECUTE',
        'SET', 'HANDLER', 'PREPARE', 'DEALLOCATE', 'OUTFILE', 'DUMPFILE', 'LOAD_FILE',
    ];

    /**
     * @param string $sql               SQL bruto retornado pela IA
     * @param array  $tabelasPermitidas Allowlist de nomes de tabelas/views (minúsculas)
     * @return array{valido:bool, motivo:?string, sql_limitado:?string}
     */
    public static function validar(string $sql, array $tabelasPermitidas): array
    {
        $sqlSemFinal = rtrim(trim($sql), "; \t\n\r");

        if ($sqlSemFinal === '') {
            return self::erro('SQL vazio.');
        }

        // Multi-statement (";" no meio do comando) — rejeita
        if (str_contains($sqlSemFinal, ';')) {
            return self::erro('Múltiplos comandos SQL não são permitidos.');
        }

        // Deve começar com SELECT
        if (!preg_match('/^\s*SELECT\s/i', $sqlSemFinal)) {
            return self::erro('Somente comandos SELECT são permitidos.');
        }

        // Blacklist de palavras-chave de escrita/DDL/controle
        foreach (self::PALAVRAS_PROIBIDAS as $palavra) {
            if (preg_match('/\b' . preg_quote($palavra, '/') . '\b/i', $sqlSemFinal)) {
                return self::erro("Comando não permitido detectado: {$palavra}.");
            }
        }

        // Allowlist de tabelas referenciadas (heurística via FROM/JOIN)
        $tabelasPermitidas = array_map('strtolower', $tabelasPermitidas);
        $tabelasUsadas     = self::extrairTabelas($sqlSemFinal);
        $naoPermitidas     = array_diff($tabelasUsadas, $tabelasPermitidas);
        if (!empty($naoPermitidas)) {
            return self::erro('Tabela(s) não liberada(s) para consulta: ' . implode(', ', $naoPermitidas) . '.');
        }

        // Garante um LIMIT (evita full scans gigantes em tabelas grandes)
        if (!preg_match('/\bLIMIT\s+\d+/i', $sqlSemFinal)) {
            $sqlSemFinal .= ' LIMIT 500';
        }

        return ['valido' => true, 'motivo' => null, 'sql_limitado' => $sqlSemFinal];
    }

    private static function extrairTabelas(string $sql): array
    {
        $tabelas = [];
        if (preg_match_all('/\b(?:FROM|JOIN)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $sql, $m)) {
            $tabelas = array_map('strtolower', $m[1]);
        }
        return array_values(array_unique($tabelas));
    }

    private static function erro(string $motivo): array
    {
        return ['valido' => false, 'motivo' => $motivo, 'sql_limitado' => null];
    }
}
