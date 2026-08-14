# Correção do autoload do Composer no HostGator

## Diagnóstico

A aplicação falha antes de conectar ao banco porque `app/bootstrap.php` requer `vendor/autoload.php`, mas a pasta `vendor/` não foi enviada ao servidor. O pacote anexo contém exclusivamente as dependências de produção do Composer, incluindo `vlucas/phpdotenv` e o arquivo `vendor/autoload.php` requerido pela aplicação.

## Implantação

No cPanel, abra **File Manager** e navegue para a raiz da aplicação:

```text
/home2/inlaud99/erp.imagiflow.com.br/
```

Essa pasta é a que contém os diretórios `app/`, `public/` e `database/`. **Não** envie o arquivo para dentro de `public/`.

Envie `imagiflow_vendor_composer_2026-08-12.zip` para essa pasta raiz. Em seguida, selecione o ZIP e use **Extract** na própria raiz. A extração deve produzir exatamente:

```text
/home2/inlaud99/erp.imagiflow.com.br/vendor/autoload.php
/home2/inlaud99/erp.imagiflow.com.br/vendor/vlucas/phpdotenv/
/home2/inlaud99/erp.imagiflow.com.br/composer.lock
```

Se já houver uma pasta `vendor/` incompleta, renomeie-a para `vendor_bkp_20260812` antes de extrair o pacote. Não altere `app/bootstrap.php` e não mova o diretório `vendor/` para a pasta pública.

## Validação

Depois da extração, recarregue `https://erp.imagiflow.com.br` em janela anônima. O erro referente a `vendor/autoload.php` deve desaparecer. Se surgir uma mensagem diferente, envie um print completo: ela será o próximo erro real de configuração, não uma repetição deste problema.

## Segurança

O pacote não inclui `.env`, senhas, dados de banco, dumps, logs ou arquivos de usuários. O arquivo `.env` deve permanecer exclusivamente no servidor, fora de qualquer repositório ou pacote compartilhado.
