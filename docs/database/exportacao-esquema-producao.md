# Exportação Estrutural do Banco de Produção

Este procedimento produz uma cópia **somente do esquema** do banco de produção. O objetivo é permitir a auditoria e a reconciliação das migrações do ERP Imagiflow com a estrutura que está efetivamente em uso no ERP InLaudo, sem transferir os quase 2 GB de registros operacionais.

> O arquivo gerado não contém linhas de tabelas, anexos, imagens, documentos, credenciais de aplicação ou o arquivo `.env`. Ele contém o DDL necessário para inspecionar tabelas, colunas, chaves, índices, relacionamentos e objetos SQL persistentes.

| Item | Incluído | Motivo |
|---|---:|---|
| Tabelas, colunas e tipos | Sim | Definem a base da migração. |
| Chaves primárias, índices e `UNIQUE` | Sim | Preservam integridade e desempenho das consultas. |
| Chaves estrangeiras | Sim | Evidenciam dependências e a ordem de criação. |
| Views | Sim | Podem ser dependências de relatórios ou integrações. |
| Procedures, functions, triggers e events | Sim | São catalogados para revisão; não devem ser implantados cegamente no ambiente compartilhado. |
| Dados (`INSERT`/registros) | **Não** | Reduz volume e evita transferir dados de clientes, pacientes ou financeiros. |
| Uploads, anexos, `.env` e credenciais | **Não** | Não pertencem ao esquema e não devem ser enviados. |

## Pré-requisitos

A exportação deve ser feita pelo servidor ou computador que consiga se conectar ao MySQL/MariaDB de produção e tenha o executável `mysqldump`. Utilize um usuário de banco com permissão de leitura no banco alvo. **Não informe a senha diretamente na linha de comando**, pois ela pode ficar gravada no histórico do shell.

Crie um arquivo local de credenciais, fora do diretório público do site. Por exemplo, em `~/cliente-schema.cnf`:

```ini
[client]
host=localhost
user=USUARIO_COM_LEITURA
password=SENHA_DO_BANCO
port=3306
```

Restrinja a leitura do arquivo e mantenha-o exclusivamente sob sua posse:

```bash
chmod 600 ~/cliente-schema.cnf
```

## Execução

Copie o arquivo `tools/exportar_esquema_migracao.sh` para um local seguro, conceda permissão de execução e rode o comando abaixo. Substitua apenas o nome do banco e os caminhos.

```bash
chmod 700 tools/exportar_esquema_migracao.sh
mkdir -p ~/exportacao-schema-inlaudo

./tools/exportar_esquema_migracao.sh \
  --database=NOME_DO_BANCO_DE_PRODUCAO \
  --defaults-file="$HOME/cliente-schema.cnf" \
  --out="$HOME/exportacao-schema-inlaudo"
```

A exportação usa `--no-data`, portanto o `mysqldump` produz somente definições de objetos. Também usa transação de leitura e evita bloqueios explícitos, reduzindo interferência no banco em funcionamento.

## Arquivos gerados

| Arquivo | Conteúdo | Deve ser enviado? |
|---|---|---:|
| `schema_<banco>_<utc>.sql.gz` | DDL compactado do banco, sem dados. | Sim |
| `schema_<banco>_<utc>.manifesto.txt` | Escopo, contagens indicativas e observações. | Sim |
| `schema_<banco>_<utc>.sha256` | Hash de integridade do arquivo compactado. | Sim |
| `schema_<banco>_<utc>.log` | Registro da execução, sem senha. | Opcional |
| `cliente-schema.cnf` | Credenciais de banco. | **Nunca** |

Antes de compartilhar, confirme que os três primeiros arquivos existem e que o `.sql.gz` não contém dados. A verificação abaixo deve retornar `0`:

```bash
zcat ~/exportacao-schema-inlaudo/schema_*.sql.gz \
  | grep -Ec '^INSERT INTO `[^`]+` VALUES'
```

## O que será feito com o arquivo

O esquema será comparado com os arquivos em `database/migrations/` do repositório. A revisão vai identificar tabelas e colunas ausentes, índices, chaves estrangeiras, diferenças de tipos/defaults/charset e objetos persistentes que exijam decisão explícita. O resultado será uma lista de divergências e migrações incrementais, revisáveis e compatíveis com MySQL 5.7/MariaDB 5.7.

> Não importe este dump diretamente no novo ambiente. Ele é uma fotografia do esquema de produção para auditoria; a implantação será realizada por migrações revisadas, em ordem e com validação/rollback definidos.

## Observação sobre procedures e demais objetos SQL

O exportador os inclui para que possam ser analisados. Entretanto, o ambiente definido para o projeto não deve depender de procedures, triggers ou events como mecanismo de migração. Caso algum objeto seja encontrado, será classificado como: necessário à aplicação, legado não utilizado ou lógica que precisa ser transferida para a camada PHP/Service antes da implantação no novo ambiente.
