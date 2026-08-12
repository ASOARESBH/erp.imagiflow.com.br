#!/usr/bin/env bash
# Exporta somente a estrutura do banco: tabelas, colunas, índices, FKs,
# views, procedures/functions, triggers e events. Não exporta registros.
# Compatível com clientes mysqldump do MySQL 5.7 e MariaDB.

set -euo pipefail
IFS=$'\n\t'

PROGRAMA="$(basename "$0")"
DATA_UTC="$(date -u +%Y%m%dT%H%M%SZ)"
SAIDA=""
BANCO=""
ARQ_CNF=""
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-mysqldump}"

uso() {
    cat <<EOF
Uso:
  $PROGRAMA --database=NOME_DO_BANCO --defaults-file=/caminho/seguro/cliente.cnf [--out=/caminho/saida]

Parâmetros:
  --database=...       Banco de origem que será inspecionado.
  --defaults-file=...  Arquivo de credenciais do cliente MySQL. Deve ter permissão 600.
  --out=...            Diretório de saída. Padrão: diretório atual.
  --help               Exibe esta ajuda.

Exemplo de arquivo cliente.cnf (não envie este arquivo):
  [client]
  host=localhost
  user=USUARIO_COM_LEITURA
  password=SENHA
  port=3306

A saída contém somente DDL e metadados de estrutura. Nenhum registro das tabelas é exportado.
EOF
}

falhar() {
    printf 'ERRO: %s\n' "$*" >&2
    exit 1
}

for argumento in "$@"; do
    case "$argumento" in
        --database=*) BANCO="${argumento#*=}" ;;
        --defaults-file=*) ARQ_CNF="${argumento#*=}" ;;
        --out=*) SAIDA="${argumento#*=}" ;;
        --help|-h) uso; exit 0 ;;
        *) falhar "Parâmetro desconhecido: $argumento. Use --help." ;;
    esac
done

[ -n "$BANCO" ] || falhar "Informe --database=NOME_DO_BANCO."
[ -n "$ARQ_CNF" ] || falhar "Informe --defaults-file=/caminho/cliente.cnf."
[ -r "$ARQ_CNF" ] || falhar "Não foi possível ler o arquivo de credenciais: $ARQ_CNF"
command -v "$MYSQLDUMP_BIN" >/dev/null 2>&1 || falhar "Comando não encontrado: $MYSQLDUMP_BIN"

if [ -z "$SAIDA" ]; then
    SAIDA="$(pwd)"
fi
mkdir -p "$SAIDA"
[ -w "$SAIDA" ] || falhar "Diretório sem permissão de escrita: $SAIDA"

# Evita injeção pelo nome do banco e aceita apenas o padrão de identificador MySQL.
case "$BANCO" in
    *[!A-Za-z0-9_]*|'') falhar "Nome de banco inválido. Use somente letras, números e sublinhado." ;;
esac

NOME_BASE="schema_${BANCO}_${DATA_UTC}"
ARQ_SQL="$SAIDA/${NOME_BASE}.sql"
ARQ_GZ="$ARQ_SQL.gz"
ARQ_MANIFESTO="$SAIDA/${NOME_BASE}.manifesto.txt"
ARQ_SHA="$SAIDA/${NOME_BASE}.sha256"
ARQ_LOG="$SAIDA/${NOME_BASE}.log"
ARQ_TEMP="$ARQ_SQL.tmp"

cleanup() {
    rm -f "$ARQ_TEMP"
}
trap cleanup EXIT

printf 'Início da exportação estrutural: %s\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" | tee "$ARQ_LOG"
printf 'Banco informado: %s\n' "$BANCO" | tee -a "$ARQ_LOG"
printf 'Cliente utilizado: %s\n' "$($MYSQLDUMP_BIN --version 2>/dev/null || true)" | tee -a "$ARQ_LOG"

# --no-data impede a exportação de registros. Índices e chaves estrangeiras
# estão nos CREATE TABLE; --routines, --triggers e --events preservam objetos SQL.
# --single-transaction e --skip-lock-tables reduzem interferência na produção.
"$MYSQLDUMP_BIN" \
    "--defaults-extra-file=$ARQ_CNF" \
    --single-transaction \
    --skip-lock-tables \
    --no-data \
    --routines \
    --triggers \
    --events \
    --skip-add-drop-table \
    --skip-comments \
    "$BANCO" > "$ARQ_TEMP"

[ -s "$ARQ_TEMP" ] || falhar "O arquivo de esquema foi gerado vazio."

# O mysqldump pode registrar usuário e host em cláusulas DEFINER de views,
# rotinas, triggers e events. Para o arquivo de auditoria ser compartilhável,
# esses identificadores são substituídos por CURRENT_USER. O dump não deve ser
# importado diretamente em produção.
DEFINERS_REDACTED="$(grep -Eoc 'DEFINER=`[^`]+`@`[^`]+`' "$ARQ_TEMP" || true)"
sed -E -i 's/DEFINER=`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' "$ARQ_TEMP"

mv "$ARQ_TEMP" "$ARQ_SQL"
gzip -9 "$ARQ_SQL"

TAMANHO_BYTES="$(wc -c < "$ARQ_GZ" | tr -d ' ')"
TABELAS="$(gzip -cd "$ARQ_GZ" | grep -Eic '^CREATE TABLE ' || true)"
VIEWS="$(gzip -cd "$ARQ_GZ" | grep -Eic '^(CREATE|/\*![0-9]+ CREATE) .*VIEW' || true)"
ROTINAS="$(gzip -cd "$ARQ_GZ" | grep -Eic 'PROCEDURE|FUNCTION' || true)"
TRIGGERS="$(gzip -cd "$ARQ_GZ" | grep -Eic 'TRIGGER' || true)"
EVENTOS="$(gzip -cd "$ARQ_GZ" | grep -Eic 'EVENT' || true)"
INDICES="$(gzip -cd "$ARQ_GZ" | grep -Eic '^[[:space:]]*(PRIMARY KEY|UNIQUE KEY|KEY|CONSTRAINT .*FOREIGN KEY)' || true)"

cat > "$ARQ_MANIFESTO" <<EOF
EXPORTAÇÃO ESTRUTURAL DO BANCO
==============================
Gerado em UTC: $(date -u '+%Y-%m-%dT%H:%M:%SZ')
Banco: $BANCO
Arquivo: $(basename "$ARQ_GZ")
Tamanho compactado (bytes): $TAMANHO_BYTES

ESCOPO
------
Incluído: DDL de tabelas, colunas, chaves primárias, índices, uniques,
chaves estrangeiras, views, procedures/functions, triggers e events.
Excluído: INSERTs, registros de tabelas, anexos, uploads, credenciais e .env.

CONTAGENS INDICATIVAS
---------------------
CREATE TABLE: $TABELAS
Referências a VIEW: $VIEWS
Referências a PROCEDURE/FUNCTION: $ROTINAS
Referências a TRIGGER: $TRIGGERS
Referências a EVENT: $EVENTOS
Linhas de índices/chaves: $INDICES
Cláusulas DEFINER anonimizadas: $DEFINERS_REDACTED

OBSERVAÇÕES
-----------
1. Este arquivo é destinado a auditoria e comparação do esquema; não o importe
   diretamente em produção sem revisão de definidores, dependências e ordem de DDL.
2. O arquivo cliente.cnf usado para acesso NÃO faz parte da exportação e não deve
   ser compartilhado.
3. Antes do envio, confira o hash SHA-256 no arquivo correspondente.
EOF

if command -v sha256sum >/dev/null 2>&1; then
    (cd "$SAIDA" && sha256sum "$(basename "$ARQ_GZ")") > "$ARQ_SHA"
elif command -v shasum >/dev/null 2>&1; then
    (cd "$SAIDA" && shasum -a 256 "$(basename "$ARQ_GZ")") > "$ARQ_SHA"
else
    printf 'AVISO: sha256sum/shasum não encontrado; hash não gerado.\n' | tee -a "$ARQ_LOG"
fi

printf 'Concluído sem exportar dados.\n' | tee -a "$ARQ_LOG"
printf 'Arquivo de esquema: %s\n' "$ARQ_GZ" | tee -a "$ARQ_LOG"
printf 'Manifesto: %s\n' "$ARQ_MANIFESTO" | tee -a "$ARQ_LOG"
[ -f "$ARQ_SHA" ] && printf 'Hash: %s\n' "$ARQ_SHA" | tee -a "$ARQ_LOG"
printf 'Log: %s\n' "$ARQ_LOG" | tee -a "$ARQ_LOG"
