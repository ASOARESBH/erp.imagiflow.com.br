# Mapa de Módulos — Controller → Model → View → Rota

Este arquivo existe para você não precisar abrir `routes/web.php` (52KB, 451+ rotas) nem
percorrer `app/Controllers` (53 arquivos) / `app/Models` (73 arquivos) inteiros toda vez
que for tocar em um módulo. Achou o domínio → confirme o detalhe exato (parâmetros,
middlewares específicos) com um `grep` pontual no arquivo indicado, em vez de reler tudo.

Convenção geral de rota (quando o módulo segue o padrão REST do projeto, documentado em
`docs/PADROES_TECNICOS.md`): `GET /modulo` lista, `GET /modulo/create` formulário de
criação, `POST /modulo` cria, `GET /modulo/edit/{id}` formulário de edição,
`POST /modulo/update/{id}` atualiza, `POST /modulo/delete/{id}` remove. Não há verbos
HTTP `PUT`/`DELETE` reais — tudo é `GET`/`POST` (ver `App\Core\Router`).

## Autenticação, sessão e acesso

| Item | Caminho |
|---|---|
| Controller | `AuthController.php` (rotas `/login`, `/forgot-password`, `/reset-password/{token}`, `/2fa/verify`, `/2fa/resend`, `/primeiro-acesso`, `/logout`) — **login unificado**: serve tanto usuários internos do ERP quanto clientes do Portal |
| Model | `Models/User.php`, `Models/PasswordResetToken.php`, `Models/Tenant.php` |
| Service | `Services/TwoFactorService.php` |
| Views | `Views/auth/*` (login, forgot_password, reset_password, primeiro_acesso, verify_2fa) |
| Observação | `PortalClienteAuthController.php` **existe no código mas não está roteado em `routes/web.php`** — é código órfão/legado. O acesso do portal do cliente passa pelo mesmo `AuthController`/`/login`. Não assuma que alterar `PortalClienteAuthController` terá efeito. |

## Usuários internos (gestão de contas do ERP)

| Item | Caminho |
|---|---|
| Controller real | `ConfiguracoesController.php`, métodos `usuariosCreate/usuariosStore/usuariosEdit/usuariosUpdate/usuariosResetPassword/usuariosToggleStatus`, sob `/configuracoes/usuarios/*` |
| Views reais | `Views/configuracoes/usuarios/create.php`, `edit.php` |
| Observação importante | **`UsuariosController.php` e `Views/usuarios/*` (create/edit/index/tabs) existem no repositório mas não aparecem em nenhuma rota de `routes/web.php`** — é código órfão/legado de uma implementação anterior. A gestão de usuários vigente é via `ConfiguracoesController`. Não invista tempo entendendo `UsuariosController` a menos que a tarefa seja explicitamente "reviver"/remover esse código morto. |

## Perfil do usuário logado

| Item | Caminho |
|---|---|
| Controller | `PerfilController.php` → `/perfil` |
| Views | `Views/perfil/index.php` + `Views/perfil/tabs/{geral,empresa,seguranca}.php` |

## Dashboard / Home

| Item | Caminho |
|---|---|
| Controllers | `HomeController.php` (`/` redireciona), `DashboardController.php` (`/dashboard`) |
| Views | `Views/home/index.php`, `Views/dashboard/index.php` (29KB — dashboard rico com KPIs) |

## Clientes

| Item | Caminho |
|---|---|
| Controller | `ClientesController.php` → `/clientes` |
| Models | `Models/Cliente.php`, `ClienteAnexo.php`, `ClienteContato.php` |
| Views | `Views/clientes/{index,cadastro,form,form-enterprise}.php` + `tabs/{geral,geral-enterprise,contatos,contatos-enterprise,anexos-enterprise,equipamentos,historico}.php` |
| Permissões | `view_clients`, `create_clients`, `edit_clients`, `delete_clients` |
| Padrão de referência | Este é o módulo de referência oficial do padrão Enterprise com abas (`docs/form-layout-standard.md` foi escrito a partir dele) — copie a estrutura daqui para módulos novos com formulário complexo |
| Nota | Há duas cópias de views de clientes: `app/Views/clientes/*` (ativa) e um resíduo em `app/Models/app/Models/Cliente.php` / `app/Views/app/Views/clientes/*` (caminho duplicado aninhado, provável artefato de cópia/deploy — confirme antes de editar qual arquivo está de fato sendo `require`ado pelo `View::render`) |

## Fornecedores

| Item | Caminho |
|---|---|
| Controller | `FornecedoresController.php` → `/fornecedores` |
| Model | `Models/Fornecedor.php` |
| Views | `Views/fornecedores/{index,form-enterprise}.php` + `tabs/{geral-enterprise,historico}.php` |

## Colaboradores (equipe interna, não confundir com Médicos)

| Item | Caminho |
|---|---|
| Controller | `ColaboradorController.php` → `/colaboradores` |
| Models | `Models/Colaborador.php`, `ColaboradorAnexo.php`, `ColaboradorComissao.php` |
| Views | `Views/colaboradores/{index,form}.php` + `tabs/{geral,anexos,comissoes,faturamento,usuario}.php` |

## Médicos / Corpo Clínico / Especialidades / Exames-Tabela

| Item | Caminho |
|---|---|
| Controllers | `MedicosController.php` (`/medicos`), `CorpoClinicoController.php` (`/escalas`, `/exames-tabela/*`), `EspecialidadesController.php` (`/especialidades`) |
| Models | `Models/Medico.php`, `MedicoExame.php`, `Especialidade.php`, `TabelaExame.php` |
| Views | `Views/medicos/*`, `Views/escalas/index.php`, `Views/exames_tabela/index.php`, `Views/especialidades/*` |
| Observação | `/exames-tabela` (preços/config de exames por médico) é servido por `CorpoClinicoController`, não por um `TabelaExameController` dedicado — fácil de procurar no lugar errado |

## CNES (Cadastro Nacional de Estabelecimentos de Saúde — dados globais do DataSUS)

| Item | Caminho |
|---|---|
| Controller | `CnesController.php` → `/cnes`, `/cnes/importar` |
| Service | `Services/CnesImportService.php` (56KB — importação/parse dos arquivos do DataSUS) |
| Models | `Models/CnesEstabelecimento.php`, `CnesEquipamento.php`, `CnesProfissional.php` |
| Views | `Views/cnes/{index,importar,show}.php` |
| Tenant | Estas tabelas (`cnes_*`) são **globais**, não têm `tenant_id` — são catálogo público, não dado de negócio do tenant (ver `reference/multitenancy-e-banco.md`) |

## Contratos & Apuração (o núcleo do negócio: exames realizados por contrato/convênio)

| Item | Caminho |
|---|---|
| Controllers | `ContratosController.php` (`/contratos`), `ApuracaoController.php` (`/faturamento/apuracao-prestador`, `/faturamento/apuracao-cliente`) — **65KB, é o maior/mais complexo Controller do sistema** |
| Models | `Models/Contrato.php`, `ContratoAnexo.php`, `ContratoExame.php`, `Apuracao.php`, `ApuracaoItem.php` |
| Views | `Views/contratos/{index,form}.php`, `Views/apuracao/{cliente,prestador,visualizar,visualizar_cliente}.php` |
| Conceito de domínio | "Apuração" = reconciliação/fechamento de quanto foi feito (exames) e quanto é devido, por prestador ou por cliente, dentro de um contrato — é o coração do faturamento do negócio (empresa de diagnóstico por imagem cobrando por exame realizado) |

## Financeiro — Plano de Contas, Contas a Pagar/Receber, Contas Bancárias

| Item | Caminho |
|---|---|
| Controllers | `PlanoContasController.php` (`/financeiro/plano-contas`), `ContasPagarController.php` (`/financeiro/pagar`), `ContasReceberController.php` (`/financeiro/receber` — 71KB, o segundo maior Controller), `ContasBancariasController.php` (`/financeiro/contas`, extrato/movimentação/Open Finance) |
| Models | `PlanoConta.php`, `ContaPagar.php`, `ContaPagarAnexo.php`, `ContaReceber.php`, `ContaReceberAnexo.php`, `ContaBancaria.php`, `ContaMovimentacao.php`, `DdaBoleto.php`, `ConfiguracaoFinanceira.php` |
| Services | `Services/ContaReceberRecorrenciaService.php` (recorrência de cobranças), `Services/OfxImportService.php` (importação de extrato), `Services/OpenFinanceService.php` + `PluggyService.php` (integração bancária) |
| Views | `Views/contas_pagar/*`, `Views/contas_receber/*` (com `tabs/parcelas.php` — parcelamento), `Views/contas_bancarias/*` (inclui `openfinance.php`) |
| Nota | `FinanceiroController.php` é um **stub de 351 bytes sem nenhuma rota registrada** — código morto, não é o entry point financeiro |

## Faturamento / Notas Fiscais

| Item | Caminho |
|---|---|
| Controller | `NotasFiscaisController.php` (52KB) → `/faturamento/notas-fiscais`; `FaturamentoController.php` (stub pequeno, mas **roteado** em `/faturamento` como página guarda-chuva) |
| Models | `NotaFiscal.php`, `NotaFiscalAnexo.php`, `NotaFiscalImportacao.php` |
| Views | `Views/notas_fiscais/{index,show,importar,form-enterprise}.php` + `tabs/*` |
| Integração | Emissão via Asaas (NFS-e) — ver `Services/AsaasService.php`, campos de NF/Asaas nas migrations `2026-02-27_notas_fiscais_asaas_nfs.sql`, `2026-06-19_nf_asaas_campos.sql` |

## Estoque / Produtos / Movimentações (compra e venda)

| Item | Caminho |
|---|---|
| Controllers | `ProdutosController.php` → `/estoque/produtos`; `MovimentacoesController.php` (70KB) → `/estoque/movimentacoes` (compras e vendas de estoque) |
| Models | `Produto.php`, `ProdutoComissao.php`, `ProdutoComponente.php`, `MovimentacaoEstoque.php`, `PedidoCompra.php`, `PedidoVenda.php` |
| Views | `Views/estoque/produtos/*`, `Views/estoque/movimentacoes/*` (compra_form/show, venda_form/show/faturar/print, importar_xml) |

## CRM completo (Leads → Oportunidades → Propostas → Fechamento)

| Item | Caminho |
|---|---|
| Controllers | `CrmLeadsController.php` (`/crm/leads`), `CrmOportunidadesController.php` (`/crm/oportunidades`), `CrmPropostasController.php` (60KB, `/crm/propostas` + rota **pública** `/proposta/aceite/{token}` para o cliente aceitar sem login), `CrmFunilController.php` (`/crm/funil`, kanban), `CrmRelatoriosController.php` (`/crm/relatorios`) |
| Models | `CrmLead.php`, `CrmOportunidade.php`, `CrmOportunidadeModalidade.php`, `CrmProposta.php`, `CrmInteracao.php`, `CrmAnexo.php`, `CrmTransferencia.php`, `CrmRelatorio.php` |
| Views | `Views/crm/{funil,leads,oportunidades,propostas,relatorios}/*` — nota: `Views/crm/propostas/aceite_publico.php` e `aceite_invalido.php` usam layout público (a rota é pública, sem sessão) |
| Especificação original | `md/CRM_ESPECIFICACAO.md` |

## Marketing

| Item | Caminho |
|---|---|
| Controllers | `MarketingCampanhasController.php` (`/marketing/campanhas`), `MarketingDisparadorController.php` (`/marketing/disparadores`, disparo em massa) |
| Models | `MarketingCampanha.php`, `MarketingDisparador.php`, `MarketingEnvio.php`, `MarketingInteracaoCrm.php` |
| Views | `Views/marketing/campanhas/{index,form,personalizar}.php`, `Views/marketing/disparadores/{index,form,view,dashboard}.php` |

## Manutenção (Ordens de Serviço de equipamentos)

| Item | Caminho |
|---|---|
| Controller | `ManutencaoController.php` (41KB) → `/manutencao/ordens` |
| Model | `Models/OrdemServico.php` |
| Views | `Views/manutencao/ordens/{index,form,show,print}.php` |
| Permissões | `create_os`, `edit_os`, `delete_os`, `faturar_os` |

## RDV (Registro de Despesas de Viagem)

| Item | Caminho |
|---|---|
| Controller | `RdvController.php` (51KB) → `/rdv/viagens` |
| Models | `RdvViagem.php`, `RdvRota.php`, `RdvDespesa.php`, `RdvCategoria.php`, `RdvHistorico.php`, `RdvOcrLog.php` |
| Views | `Views/rdv/{index,form,show}.php`, `Views/rdv/rotas/*` |
| OCR | Tesseract.js no navegador é o motor primário; fallback server-side opcional configurado via `.env` (`OCR_SPACE_API_KEY`, `OPENAI_API_KEY`, `RDV_OCR_ENGINES`) — ver `RdvOcrLog` model para auditoria de cada tentativa |

## Portal do Cliente

| Item | Caminho |
|---|---|
| Controllers | `PortalClienteController.php` (dashboard, perfil, propostas, pedidos), `PortalContasPagarController.php` (33KB), `PortalFaturamentoController.php` (41KB), `PortalApuracoesController.php` |
| Model | `Models/PortalCliente.php` |
| Views | `Views/portal/{dashboard,perfil}.php`, `Views/portal/{apuracoes,auth,contas-a-pagar,faturamento,negociacoes,pagamentos}/*` |
| Middleware | Todas as rotas `/portal/*` (exceto login/primeiro-acesso, que são públicas) ficam dentro de `Router::group(["middleware" => ["PortalCliente"]], ...)` em `routes/web.php` — **não** usam `Auth`/`Permission` (esses são para o ERP interno) |
| Layout | `_layout => 'portal'` (ou `'portal_public'` para aceite público de proposta) |

## Hub IA (agentes de IA internos, RAG, WhatsApp bot)

| Item | Caminho |
|---|---|
| Controllers | `HubIaController.php` (`/hub-ia`, dashboard), `HubIaAgentesController.php` (`/hub-ia/agentes`), `HubIaBancoController.php` (`/hub-ia/banco` — acesso a dados via IA, com `SqlGuard`), `HubIaChatController.php` (`/hub-ia/chat`), `HubIaConectoresController.php` (`/hub-ia/conectores`), `HubIaConhecimentoController.php` (`/hub-ia/conhecimento` — base de conhecimento/RAG), `HubIaPromptsController.php` (`/hub-ia/prompts`), `HubIaWhatsappController.php` (`/hub-ia/whatsapp`) |
| Services | `Services/AI/AIService.php`, `AIProviderFactory.php` (+ `Providers/{ClaudeProvider,GeminiProvider,OpenAICompatibleProvider}.php`), `KnowledgeBaseService.php`, `EmbeddingService.php`, `PromptManager.php`, `AgentManager.php`, `CostEstimator.php`, `DatabaseAI.php`, `SqlGuard.php` (impede a IA de rodar SQL perigoso), `WhatsAppAI.php` |
| Models | `HubIaAgente.php`, `HubIaAgentePermissao.php`, `HubIaBancoConfig.php`, `HubIaChunk.php`, `HubIaConector.php`, `HubIaDocumento.php`, `HubIaHistorico.php`, `HubIaLog.php`, `HubIaPrompt.php`, `HubIaWhatsappConfig.php` |
| Permissões | `view_hub_ia`, `manage_hub_ia` |
| API externa (bot WhatsApp) | `routes/api.php` — `Api\V1\Whatsapp{Auth,Faturas,Logs,NotasFiscais,Resumo}Controller`, protegida por `WhatsappApiAuthMiddleware` (header `X-API-Key`) — é a integração que o bot de WhatsApp consome para responder clientes |

## Integrações (pagamento, e-mail, WhatsApp)

| Item | Caminho |
|---|---|
| Controllers | `IntegracaoController.php` (79KB — o maior Controller do sistema; `/integracao/asaas`, `/integracao/cora`, webhooks `/api/webhooks/{asaas,cora}` **públicos**), `IntegracaoWhatsappController.php` (`/integracao/whatsapp`) |
| Models | `Integracao.php` |
| Services | `AsaasService.php` (39KB), `CoraService.php` (18KB), `WhatsappLogger.php` |
| Views | `Views/integracoes/{asaas,cora,email,whatsapp}.php` |
| Nota | `IntegracoesController.php` (com S, 258 bytes) é um **stub sem rota** — código morto, não confundir com `IntegracaoController.php` (sem S), que é o real e enorme |

## Configurações gerais / Notificações / Log / Manual / Diagnóstico

| Item | Caminho |
|---|---|
| Controllers | `ConfiguracoesController.php` (107KB de view associada — o maior módulo de configuração, cobre empresa, usuários, layout de NF, financeiro), `NotificacoesController.php` (config de alertas + API `/api/notificacoes/*` para o sino de notificações da UI), `LogController.php` (`/api/log/error`, recebe erro de JS do frontend), `ManualController.php` (`/manual`, artigos de ajuda internos — CRUD de manual do sistema), `DiagnosticoController.php` (`/diagnostico/upload-info`, ferramenta de diagnóstico) |
| Models | `EmpresaConfig.php`, `ConfigNfs.php`, `Notificacao.php`, `NotificacaoConfigAlerta.php`, `ManualSistema.php` |
| Services | `Services/NotificacaoService.php`, `Services/EmailAlertaService.php` |
| Views | `Views/configuracoes/*`, `Views/manual/*` |

## Cron / Jobs agendados

| Item | Caminho |
|---|---|
| Controller | `CronController.php` → `GET /api/cron/{alertas,alertas-crm,notificacoes}?key=CRON_KEY` |
| Script direto (fora do MVC) | `cron/processar_alertas.php` |
| Segurança | Protegido por `CRON_KEY` do `.env` via query string — **não** pelo `Auth`/`Permission` normal, porque quem chama é o crontab do servidor, sem sessão |

## Controllers auxiliares / de suporte (não são módulos de negócio próprios)

`Api/V1/WhatsappBaseController.php` é a classe base abstrata usada pelos outros
controllers da API do bot WhatsApp (não é instanciada diretamente por rota).
