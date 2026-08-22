# Homologação da integração ImagiFlow / VOXEL PACS

## Pré-requisitos

A migration `database/migrations/2026-08-21_voxel_pacs_apuracao_imports.sql` deve ser aplicada antes de habilitar a integração. Faça backup do banco e valide previamente que a tabela `apuracao_voxel_imports` não exista. O ambiente do ERP precisa ter HTTPS e `allow_url_fopen` ou a extensão PHP cURL habilitados.

No VOXEL PACS, um superadmin deve abrir **Plataforma → Negócios → [Negócio] → Conector ImagiFlow**, gerar credencial temporária e transferir de forma segura apenas o código e segredo ao administrador do tenant correspondente no ImagiFlow.

## Configuração no ImagiFlow

Acesse **Integrações → ImagiFlow / VOXEL PACS**. Informe a URL HTTPS do VOXEL, o código de integração e o segredo. O segredo é cifrado antes de persistir em `integracoes.config_json`; não deve ser registrado em ticket, e-mail, print, JavaScript ou log.

Use um CRM ativo do negócio para o teste. A mensagem de sucesso confirma o HMAC e a comunicação, mas uma resposta sem médico único é esperada quando o CRM não existir ou for ambíguo.

## Cenários obrigatórios

| Cenário | Resultado esperado |
|---|---|
| Credencial válida e CRM único | Teste de conexão retorna sucesso e a consulta de apuração apresenta estudos assinados/liberados. |
| Segredo incorreto, revogado ou timestamp rejeitado | O ImagiFlow mostra falha de credencial, sem exibir assinatura, segredo ou payload. |
| Médico VOXEL sem vínculo local por CRM | O estudo é contado como pendente de conciliação e não entra no CSV interno nem no cálculo. |
| Consulta repetida | Estudos já reservados/importados por `usuario_id + source_reference` são classificados como já existentes. |
| Período maior que 93 dias | O ImagiFlow bloqueia antes de chamar o VOXEL. |
| Outro tenant | A credencial VOXEL limita o retorno ao negócio configurado; o ImagiFlow mantém a configuração por usuário/tenant. |
| Execução da apuração | Mantém o matching DICOM, preços contratuais e sub-apurações de prestador do fluxo existente. |

## Operação de apuração

Na aba **Apuração** do contrato, crie uma apuração em rascunho e selecione o botão de consulta VOXEL. Informe o período, opcionalmente médico e unidade. Para contratos de prestador, selecione também o cliente/unidade do ERP. Após revisar a prévia, execute a apuração normalmente.

O arquivo CSV produzido internamente não é um upload do usuário; ele é somente uma ponte de compatibilidade que preserva o cálculo de preços já consolidado no ERP. Não contém corpo de laudo, PDF, DICOM ou token público de resultado.

## Segurança e suporte

Auditorias armazenam apenas IDs, contagens, endpoint e `request_id`. Em qualquer falha, verifique o log do ERP e a auditoria do VOXEL pelo `request_id`, sem habilitar logs de segredo, assinatura HMAC ou corpo clínico.
