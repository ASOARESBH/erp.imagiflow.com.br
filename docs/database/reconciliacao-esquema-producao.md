# Reconciliação do Esquema de Produção

## Fonte analisada e proteção de dados

O arquivo recebido tinha 234 MB compactados e continha dados operacionais, embora a intenção declarada fosse uma exportação estrutural. Ele possuía 43.143 blocos `INSERT` e aproximadamente 7,7 milhões de linhas de dados. Esses comandos foram descartados localmente, sem execução, e não foram adicionados ao repositório.

A análise utilizou exclusivamente o DDL extraído. O baseline gerado em `database/schema/2026-08-12_imagiflow_baseline.sql` possui 232 KB, contém 122 comandos `CREATE TABLE` e não possui `INSERT`, `REPLACE`, `LOAD DATA`, anexos, credenciais ou registros de negócio.

> O arquivo bruto e a área de análise local são ignorados por Git. Somente o baseline estrutural higienizado foi preparado para versionamento.

## Inventário estrutural

| Elemento | Produção extraída | Observação |
|---|---:|---|
| Tabelas | 122 | Estrutura utilizada como linha de base do Imagiflow. |
| Chaves estrangeiras | 77 | Preservadas no baseline. |
| Chaves primárias | 116 | Preservadas em `ALTER TABLE` posteriores. |
| Índices/uniques declarados | 346 | Preservados no baseline. |
| Procedures, functions, triggers e events ativos | 0 | Havia somente comando de remoção de procedure legado, excluído do baseline. |
| Blocos de dados removidos | 43.143 | Não foram usados, analisados como registros ou versionados. |

## Confronto com `database/migrations`

As migrations versionadas referenciam 110 tabelas, enquanto o DDL de produção contém 122. Há 15 tabelas presentes em produção sem DDL correlato nos arquivos de migration e 3 referências históricas sem tabela correspondente na produção atual.

| Situação | Tabelas |
|---|---|
| Produção sem DDL versionado | `_cnes_equip_staging`, `_cnes_estab_staging`, `_cnes_prof_staging`, `_cnes_vinculo_staging`, `audit_logs`, `clientes_anexos`, `clientes_contatos`, `contas_receber_anexos`, `dispositivos_controlid`, `dispositivos_controlid_leituras`, `dispositivos_controlid_sync_log`, `historico_importacoes_ofx`, `mkt_campanha_contatos`, `mkt_campanhas`, `movimentacoes_bancarias` |
| Migration sem tabela na produção atual | `contasreceber`, `dda_boletos`, `est_pedidos_venda_historico` |

Essas diferenças justificam o uso do baseline estrutural real antes da aplicação de migrations novas. As migrations históricas foram preservadas para rastreabilidade, mas não são suficientes como mecanismo de bootstrap de uma base nova.

## Decisões aplicadas

A fundação multitenant adiciona `tenant_id` a 106 tabelas de negócio/configuração e mantém 16 tabelas globais. Foram tratadas explicitamente três tabelas de sequência sem coluna `id` (`est_pedido_seq`, `manut_os_seq`, `produto_codigo_seq`) para que a migration não gere `AFTER id` inválido.

Os arquivos de produção que precisam de continuidade de manutenção devem partir deste baseline e receber migrations incrementais posteriores. Nenhum dado real foi copiado para o projeto novo nesta etapa.
