# Integração ImagiFlow ↔ VOXEL PACS para apuração

## Decisões de implementação

A integração será **iniciada pelo ImagiFlow** e consumirá somente os endpoints HMAC do VOXEL PACS. A aplicação continuará sendo a autoridade para contratos, tabelas de preço, cálculo de venda, repasse a prestadores, sub-apurações e faturamento. O VOXEL fornecerá apenas estudos assinados ou liberados e seus metadados operacionais mínimos.

| Camada | Decisão |
|---|---|
| Configuração | Entrada `ImagiFlow / VOXEL PACS` no submenu Integrações, protegida por `manage_settings`. A configuração usa `integracoes.nome = voxel_pacs` por usuário/tenant. |
| Credenciais | URL base HTTPS, `integration_code` e `secret`. O segredo será cifrado com `CryptoService`, mascarado na tela e nunca registrado em logs, auditoria ou JavaScript. |
| Transporte | Cliente de serviço usa POST JSON, timeout de 15 segundos, SSL verificado, sem seguir redirecionamentos, cabeçalhos HMAC e `X-Request-Id` novo por chamada. |
| Origem da apuração | O modal de apuração consultará o VOXEL pelo período — e por médico/unidade quando informados —, sem upload manual pelo usuário. |
| Reuso de cálculo | Os estudos VOXEL serão materializados somente em CSV interno temporário no layout já aceito pelo importador. Assim, `executarApuracao()` preserva o matching DICOM, a hierarquia de preços e as sub-apurações de prestador já existentes. |
| Idempotência | Uma tabela de controle guardará `usuario_id`, `apuracao_id`, `source_reference`, status e hash. A chave única será `usuario_id + source_reference`; registros já concluídos não serão importados novamente. |
| Conciliação médica | Estudos sem CRM ou sem vínculo local seguro serão devolvidos ao usuário como pendentes e não entrarão na importação automática. A consulta do médico no VOXEL só aceita um resultado ativo e inequívoco. |
| Auditoria | Registrar somente IDs, contagens, endpoint, request ID e status. Não persistir segredo, assinatura HMAC, corpo de laudo, PDF, DICOM ou dados clínicos completos em logs de integração. |

## Limites de homologação

A implementação deve ser homologada com credencial temporária emitida no VOXEL para um negócio de teste. São obrigatórios os cenários de credencial revogada, assinatura inválida, período acima de 93 dias, médico não encontrado/ambíguo, repetição de importação e isolamento entre empresas.

> A migration deve ser executada antes de habilitar a importação. O recurso permanecerá desabilitado enquanto a configuração VOXEL não estiver ativa e testada.
