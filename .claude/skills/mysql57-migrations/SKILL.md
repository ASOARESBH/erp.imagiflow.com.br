---
name: mysql57-migrations
description: This skill should be used whenever writing, editing, or reviewing a SQL migration file for this project (database/migrations/*.sql), or any raw SQL (ALTER TABLE, CREATE TABLE, CREATE INDEX, stored SQL in PHP models/controllers) that will run against this project's database. Triggers on "migration", "ALTER TABLE", "CREATE TABLE", "nova coluna", "nova tabela", "banco de dados", or any task that produces a .sql file in this repo. Enforces MySQL 5.7 compatibility — the production database is confirmed plain MySQL 5.7, NOT MariaDB, and several modern "IF NOT EXISTS" conveniences silently fail on it (confirmed via a real #1064 syntax error in phpMyAdmin on 2026-07-08).
---

# Migrações SQL compatíveis com MySQL 5.7

O banco de produção deste projeto (`erp.inlaudo.com.br`, hospedagem cPanel/shared) é **MySQL 5.7 puro — não MariaDB**. Isso foi confirmado por um erro real em produção: `ALTER TABLE users ADD COLUMN IF NOT EXISTS ...` retornou `#1064 - erro de sintaxe`, porque `ADD COLUMN IF NOT EXISTS` é uma extensão do MariaDB (10.0.2+) e do MySQL 8.0.29+ — **inexistente em MySQL 5.7**.

Algumas migrações antigas deste repositório (e comentários nelas) assumiam sintaxe "MariaDB 10.1.4+" — isso está incorreto para este ambiente e não deve ser repetido.

## Regras obrigatórias

1. **NUNCA usar `IF NOT EXISTS` / `IF EXISTS` em `ALTER TABLE`** — nenhuma das formas abaixo funciona em MySQL 5.7:
   - `ALTER TABLE t ADD COLUMN IF NOT EXISTS ...` ❌
   - `ALTER TABLE t DROP COLUMN IF EXISTS ...` ❌
   - `ALTER TABLE t ADD INDEX IF NOT EXISTS ...` ❌
   - `CREATE INDEX IF NOT EXISTS ...` ❌

2. **`CREATE TABLE IF NOT EXISTS ...` FUNCIONA normalmente** — pode ser usado sem restrições (é padrão SQL, suportado desde MySQL 5.0).

3. **Para adicionar coluna de forma idempotente**, sempre usar o padrão `information_schema.COLUMNS` + `PREPARE`/`EXECUTE`/`DEALLOCATE` (funciona em MySQL 5.7 e MariaDB):

   ```sql
   SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nome_tabela' AND COLUMN_NAME = 'nome_coluna');
   SET @sql := IF(@col_exists = 0,
     'ALTER TABLE nome_tabela ADD COLUMN nome_coluna TIPO NULL DEFAULT NULL AFTER outra_coluna',
     'SELECT 1');
   PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
   ```

   Repetir o bloco inteiro (as 3 linhas `SET`/`SET`/`PREPARE...`) para cada coluna nova — não tente empacotar múltiplas colunas em um único `ALTER TABLE ... ADD COLUMN a, ADD COLUMN b` a menos que todas sejam garantidamente novas (nesse caso não há necessidade de idempotência coluna a coluna).

4. **Para adicionar índice de forma idempotente**, mesmo padrão usando `information_schema.STATISTICS`:

   ```sql
   SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nome_tabela' AND INDEX_NAME = 'nome_indice');
   SET @sql := IF(@idx_exists = 0,
     'CREATE INDEX nome_indice ON nome_tabela (coluna)',
     'SELECT 1');
   PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
   ```

5. **Sem CTEs nem window functions** — `WITH ... AS (...)`, `ROW_NUMBER() OVER (...)`, `RANK()`, etc. são MySQL 8.0+. Reescrever com subqueries/variáveis quando necessário.

6. **Renomear coluna**: usar `CHANGE COLUMN nome_antigo nome_novo TIPO ...` (exige repetir o tipo). A sintaxe curta `RENAME COLUMN nome_antigo TO nome_novo` é MySQL 8.0+ e não funciona em 5.7.

7. **JSON**: MySQL 5.7 suporta o tipo `JSON` e funções básicas (`JSON_EXTRACT`, `->`, `->>`, `JSON_OBJECT` etc.), mas não `JSON_TABLE` (8.0+).

8. Seguir a convenção já usada no projeto: charset `utf8` + `utf8_unicode_ci` (tabelas antigas) ou `utf8mb4` + `utf8mb4_unicode_ci` (tabelas novas), `ENGINE=InnoDB`, nome de arquivo `YYYY-MM-DD_descricao_curta.sql`, comentário de cabeçalho com `-- Migration:`, `-- Date:`, `-- Rules: ONLY CREATE TABLE / ADD COLUMN. Never drop/rename existing columns.`, e uma seção final `-- VALIDAÇÃO` com `SHOW COLUMNS`/`SHOW TABLES` para conferência manual pós-execução.

9. Migrações são arquivos `.sql` soltos em `database/migrations/`, aplicados **manualmente** (phpMyAdmin ou `mysql` CLI) — não existe runner automático neste projeto. O SQL deve ser copiável e executável diretamente no editor de SQL do phpMyAdmin sem exigir mudança de `DELIMITER` (por isso o padrão `PREPARE`/`EXECUTE` acima é preferível a stored procedures com `BEGIN...END`).

## Ao revisar/gerar código PHP que faz ALTER TABLE em runtime

O padrão já usado em `app/Models/User.php::update()` (checar coluna via `SHOW COLUMNS FROM tabela LIKE 'coluna'` antes de rodar `ALTER TABLE` simples, sem `IF NOT EXISTS`) é o padrão correto para este projeto — reutilizar essa abordagem em vez de `IF NOT EXISTS` também em código PHP que roda DDL dinamicamente.
