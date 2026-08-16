<?php

use App\Core\Router;

// Rotas públicas (sem autenticação)
Router::get("/login", "AuthController@showLoginForm");
Router::post("/login", "AuthController@login");

// Troca de idioma: pública para funcionar no login e persistida no usuário autenticado.
// A validação CSRF ocorre no LocaleController.
Router::post("/idioma", "LocaleController@update");

Router::get("/forgot-password", "AuthController@showForgotPasswordForm");
Router::post("/forgot-password", "AuthController@forgotPassword");

Router::get("/reset-password/{token}", "AuthController@showResetPasswordForm");
Router::post("/reset-password/{token}", "AuthController@resetPassword");

// Autenticação em Dois Fatores (2FA) — só acessível com login pendente (senha já validada)
Router::get("/2fa/verify", "AuthController@showTwoFactorForm");
Router::post("/2fa/verify", "AuthController@verifyTwoFactor");
Router::post("/2fa/resend", "AuthController@resendTwoFactor");

// Webhooks Públicos
Router::post("/api/webhooks/asaas", "IntegracaoController@webhook");
Router::get("/api/webhooks/asaas/ping", "IntegracaoController@webhookPing");
Router::post("/api/webhooks/cora", "IntegracaoController@webhookCora");
Router::get("/api/webhooks/cora/ping", "IntegracaoController@webhookCoraPing");

// ============================================================
// Cron Jobs (protegidos por CRON_KEY no .env)
// Uso: GET /api/cron/alertas?key=SUA_CRON_KEY
// crontab: 0 8 * * * curl -s "https://erp.inlaudo.com.br/api/cron/alertas?key=KEY" > /dev/null
// ============================================================
Router::get("/api/cron/alertas",         "CronController@alertas");
Router::get("/api/cron/alertas-crm",     "CronController@alertasCrm");
Router::get("/api/cron/notificacoes",    "CronController@notificacoes");

// ============================================================
// Primeiro Acesso — Clientes do Portal (via login unificado /login)
// ============================================================
Router::get("/primeiro-acesso", "AuthController@showPrimeiroAcesso");
Router::post("/primeiro-acesso", "AuthController@processarPrimeiroAcesso");
Router::post("/primeiro-acesso/salvar", "AuthController@salvarPrimeiroAcesso");
// Redireciona /portal/login para /login (compatibilidade)
Router::get("/portal/login", "AuthController@showLoginForm");

// ============================================================
// Portal do Cliente — Rotas Protegidas (requerem sessão do portal)
// ============================================================
Router::group(["middleware" => ["PortalCliente"]], function () {
    Router::post("/portal/logout", "AuthController@logout");

    // Dashboard
    Router::get("/portal/dashboard", "PortalClienteController@dashboard");
    Router::get("/portal", "PortalClienteController@dashboard"); // alias
    Router::get("/portal/pagamentos/dashboard", "PortalClienteController@dashboardPagamentos");

    // Perfil
    Router::get("/portal/perfil", "PortalClienteController@perfil");
    Router::post("/portal/perfil/alterar-senha", "PortalClienteController@alterarSenha");

    // Contas a Pagar
    Router::get("/portal/contas-a-pagar", "PortalContasPagarController@index");
    Router::get("/portal/contas-a-pagar/pagar/{id}", "PortalContasPagarController@pagar");
    Router::get("/portal/contas-a-pagar/status/{id}", "PortalContasPagarController@statusCheck");
    Router::get("/portal/contas-a-pagar/sync/{id}", "PortalContasPagarController@syncStatus");
    Router::get("/portal/contas-a-pagar/link/{id}", "PortalContasPagarController@getLink");
    Router::get("/portal/contas-a-pagar/anexos/download/{id}", "PortalContasPagarController@downloadAnexo");

    // Apurações do Cliente
    Router::get("/portal/apuracoes", "PortalApuracoesController@index");
    Router::get("/portal/apuracoes/{id}", "PortalApuracoesController@show");

    // Faturamento
    Router::get("/portal/faturamento/notas-fiscais", "PortalFaturamentoController@notasFiscais");
    Router::post("/portal/faturamento/emitir-nfs/{id}", "PortalFaturamentoController@emitirNfs");
    Router::post("/portal/faturamento/reemitir-nf/{id}", "PortalFaturamentoController@reemitirNf");
    Router::get("/portal/faturamento/nota-fiscal/pdf/{id}", "PortalFaturamentoController@downloadPdf");
    Router::get("/portal/faturamento/nota-fiscal/xml/{id}", "PortalFaturamentoController@downloadXml");
    Router::get("/portal/faturamento/nota-fiscal/anexo/{id}", "PortalFaturamentoController@downloadAnexo");

    // Negociações
    Router::get("/portal/negociacoes/propostas",                       "PortalClienteController@propostas");
    Router::get("/portal/negociacoes/propostas/{id}/aceitar",          "PortalClienteController@aceitarProposta");
    Router::post("/portal/negociacoes/propostas/{id}/aceitar",         "PortalClienteController@registrarAceiteProposta");
    Router::get("/portal/negociacoes/pedidos-venda",                   "PortalClienteController@pedidosVenda");
});

// ============================================================
// Proposta — Aceite Público (sem autenticação)
// ============================================================
Router::get("/proposta/aceite/{token}",  "CrmPropostasController@aceitePublico");
Router::post("/proposta/aceite/{token}", "CrmPropostasController@registrarAceite");

// Rotas protegidas (requerem autenticação)
Router::group(["middleware" => ["Auth"]], function () {
    // Página inicial redireciona para dashboard
    Router::get("/", "HomeController@index");

    Router::get("/dashboard", "DashboardController@index");
    Router::post("/logout", "AuthController@logout");

    // Módulo Clientes (Enterprise Ready)
    Router::group(["middleware" => ["Permission:view_clients"]], function () {
        Router::get("/clientes", "ClientesController@index");
        Router::get("/clientes/buscar-cnpj", "ClientesController@buscarCnpj"); // API Helper
        Router::get("/clientes/buscar-cep", "ClientesController@buscarCep");   // API Helper
    });

    Router::group(["middleware" => ["Permission:create_clients"]], function () {
        Router::get("/clientes/create", "ClientesController@create");
        Router::post("/clientes", "ClientesController@store");
    });

    Router::group(["middleware" => ["Permission:edit_clients"]], function () {
        Router::get("/clientes/edit/{id}", "ClientesController@edit");
        Router::post("/clientes/update/{id}", "ClientesController@update"); // Same logic for ID

        // Gestão de Contatos (AJAX)
        Router::post("/clientes/add-contato",   "ClientesController@addContato");
        Router::post("/clientes/update-contato", "ClientesController@updateContato");
        Router::get("/clientes/get-contato",     "ClientesController@getContato");
        Router::post("/clientes/remove-contato", "ClientesController@removeContato");

        // Anexos de Clientes
        Router::post("/clientes/anexos/add", "ClientesController@addAnexo");
        Router::get("/clientes/anexos/download/{id}", "ClientesController@downloadAnexo");
        Router::post("/clientes/anexos/remove", "ClientesController@removeAnexo");

        // Equipamentos de Clientes
        Router::get("/clientes/equipamentos/get",    "ClientesController@getEquipamento");
        Router::post("/clientes/equipamentos/save",  "ClientesController@saveEquipamento");
        Router::post("/clientes/equipamentos/remove","ClientesController@removeEquipamento");
        Router::get("/clientes/{id}/equipamentos",   "ClientesController@listarEquipamentos");
    });

    Router::group(["middleware" => ["Permission:delete_clients"]], function () {
        Router::post("/clientes/delete/{id}", "ClientesController@delete");
    });

    // ─── CNES Global ─────────────────────────────────────────────────────────
    Router::get("/cnes",                                  "CnesController@index");
    Router::get("/cnes/importar",                         "CnesController@importarForm");
    Router::post("/cnes/importar/upload",                 "CnesController@importarUpload");
    Router::post("/cnes/importar/servidor",               "CnesController@importarDoServidor");
    Router::get("/cnes/importar/detectar",                "CnesController@detectarCsvs");
    Router::get("/cnes/importar/status",                  "CnesController@importarStatus");
    Router::post("/cnes/importar/diagnostico-zip",         "CnesController@diagnosticarZip");
    Router::post("/cnes/importar/parcial",                 "CnesController@importarParcial");
    Router::get("/cnes/buscar",                           "CnesController@buscar");
    Router::get("/cnes/{cnes}",                           "CnesController@show");
    Router::post("/cnes/{cnes}/importar-cliente",         "CnesController@importarComoCliente");
    Router::post("/cnes/equipamento/{id}/atualizar",      "CnesController@atualizarEquipamento");
    Router::post("/cnes/profissional/{id}/atualizar",     "CnesController@atualizarProfissional");

    // Financeiro
    Router::group(["middleware" => ["Permission:view_finance"]], function () {
    });

    // Plano de Contas (Financeiro)
    Router::group(["middleware" => ["Permission:view_plano_contas"]], function () {
        Router::get("/financeiro/plano-contas", "PlanoContasController@index");
    });

    Router::group(["middleware" => ["Permission:create_plano_contas"]], function () {
        Router::get("/financeiro/plano-contas/create", "PlanoContasController@create");
        Router::post("/financeiro/plano-contas", "PlanoContasController@store");
        Router::post("/financeiro/plano-contas/importar-padrao", "PlanoContasController@importDefault");
    });

    Router::group(["middleware" => ["Permission:edit_plano_contas"]], function () {
        Router::get("/financeiro/plano-contas/edit/{id}", "PlanoContasController@edit");
        Router::post("/financeiro/plano-contas/update/{id}", "PlanoContasController@update");
    });

    Router::group(["middleware" => ["Permission:delete_plano_contas"]], function () {
        Router::post("/financeiro/plano-contas/delete/{id}", "PlanoContasController@delete");
    });

    // Fornecedores
    Router::group(["middleware" => ["Permission:view_fornecedores"]], function () {
        Router::get("/fornecedores", "FornecedoresController@index");
        Router::get("/financeiro/fornecedores", "FornecedoresController@index");
    });

    Router::group(["middleware" => ["Permission:create_fornecedores"]], function () {
        Router::get("/fornecedores/create", "FornecedoresController@create");
        Router::post("/fornecedores", "FornecedoresController@store");
        Router::get("/financeiro/fornecedores/create", "FornecedoresController@create");
        Router::post("/financeiro/fornecedores", "FornecedoresController@store");
    });

    Router::group(["middleware" => ["Permission:edit_fornecedores"]], function () {
        Router::get("/fornecedores/edit/{id}", "FornecedoresController@edit");
        Router::post("/fornecedores/update/{id}", "FornecedoresController@update");
        Router::get("/financeiro/fornecedores/edit/{id}", "FornecedoresController@edit");
        Router::post("/financeiro/fornecedores/update/{id}", "FornecedoresController@update");
    });

    Router::group(["middleware" => ["Permission:delete_fornecedores"]], function () {
        Router::post("/fornecedores/delete/{id}", "FornecedoresController@delete");
        Router::post("/financeiro/fornecedores/delete/{id}", "FornecedoresController@delete");
    });

    // Fornecedores — APIs de consulta (CNPJ, CEP) e histórico
    Router::get("/fornecedores/buscar-cnpj",       "FornecedoresController@buscarCnpj");
    Router::get("/fornecedores/buscar-cep",        "FornecedoresController@buscarCep");
    Router::get("/fornecedores/{id}/historico",    "FornecedoresController@historico");

    // Corpo Clinico
    Router::get("/medicos", "MedicosController@index");
    Router::get("/medicos/create", "MedicosController@create");
    Router::post("/medicos/store", "MedicosController@store");
    Router::get("/medicos/edit/{id}", "MedicosController@edit");
    Router::post("/medicos/update/{id}", "MedicosController@update");
    // CRMs do médico (AJAX)
    Router::get("/medicos/{id}/crms", "MedicosController@getCrms");
    Router::post("/medicos/{id}/crms/save", "MedicosController@saveCrms");
    // Serviços / Exames do médico (AJAX)
    Router::get("/medicos/{id}/exames", "MedicosController@getExames");
    Router::post("/medicos/{id}/exames/save", "MedicosController@saveExame");
    Router::post("/medicos/{id}/exames/delete", "MedicosController@deleteExame");
    Router::get("/medicos/exame-tabela/{id}", "MedicosController@getExameTabela");

    Router::get("/especialidades", "EspecialidadesController@index");
    Router::get("/especialidades/create", "EspecialidadesController@create");
    Router::post("/especialidades/store", "EspecialidadesController@store");

    // ─── Colaboradores ───────────────────────────────────────────────────────
    Router::group(["middleware" => ["Permission:view_colaboradores"]], function () {
        Router::get("/colaboradores", "ColaboradorController@index");
        Router::get("/colaboradores/buscar-cnpj", "ColaboradorController@buscarCnpj");
    });
    Router::group(["middleware" => ["Permission:create_colaboradores"]], function () {
        Router::get("/colaboradores/create", "ColaboradorController@create");
        Router::post("/colaboradores/store", "ColaboradorController@store");
    });
    Router::group(["middleware" => ["Permission:edit_colaboradores"]], function () {
        Router::get("/colaboradores/edit/{id}", "ColaboradorController@edit");
        Router::post("/colaboradores/update/{id}", "ColaboradorController@update");
        // Anexos
        Router::post("/colaboradores/anexos/add", "ColaboradorController@addAnexo");
        Router::get("/colaboradores/anexos/download/{id}", "ColaboradorController@downloadAnexo");
        Router::post("/colaboradores/anexos/remove", "ColaboradorController@removeAnexo");
        // Comissoes
        Router::post("/colaboradores/comissoes/store", "ColaboradorController@storeComissao");
        Router::post("/colaboradores/comissoes/update/{id}", "ColaboradorController@updateComissao");
        Router::post("/colaboradores/comissoes/delete/{id}", "ColaboradorController@deleteComissao");
        // Vinculo de Usuario
        Router::post("/colaboradores/vincular-usuario/{id}", "ColaboradorController@vincularUsuario");
    });
    Router::group(["middleware" => ["Permission:delete_colaboradores"]], function () {
        Router::post("/colaboradores/delete/{id}", "ColaboradorController@delete");
    });

    Router::get("/escalas", "CorpoClinicoController@escalas");

    Router::get("/exames-tabela", "CorpoClinicoController@examesTabela");
    Router::post("/exames-tabela/store", "CorpoClinicoController@storeExameTabela");
    Router::post("/exames-tabela/{id}/update", "CorpoClinicoController@updateExameTabela");
    Router::post("/exames-tabela/{id}/delete", "CorpoClinicoController@deleteExameTabela");
    Router::get("/exames-tabela/{id}/config", "CorpoClinicoController@getConfigExame");
    Router::post("/exames-tabela/{id}/save-precos", "CorpoClinicoController@savePrecos");
    Router::post("/exames-tabela/{id}/save-secao", "CorpoClinicoController@saveSecao");
    Router::post("/exames-tabela/{id}/save-tags", "CorpoClinicoController@saveTags");

    // Contas a Pagar
    Router::group(["middleware" => ["Permission:view_contas_pagar"]], function () {
        Router::get("/financeiro/pagar", "ContasPagarController@index");
        Router::get("/financeiro/contas-a-pagar", "ContasPagarController@index");
        Router::get("/financeiro/contas-a-pagar/anexos/download/{id}", "ContasPagarController@downloadAnexo");
    });

    Router::group(["middleware" => ["Permission:create_contas_pagar"]], function () {
        Router::get("/financeiro/contas-a-pagar/create", "ContasPagarController@create");
        Router::post("/financeiro/contas-a-pagar", "ContasPagarController@store");
    });

    Router::group(["middleware" => ["Permission:edit_contas_pagar"]], function () {
        Router::get("/financeiro/contas-a-pagar/edit/{id}", "ContasPagarController@edit");
        Router::post("/financeiro/contas-a-pagar/update/{id}", "ContasPagarController@update");
        Router::post("/financeiro/contas-a-pagar/anexos/upload", "ContasPagarController@uploadAnexo");
        Router::post("/financeiro/contas-a-pagar/anexos/delete/{id}", "ContasPagarController@deleteAnexo");
    });

    Router::group(["middleware" => ["Permission:delete_contas_pagar"]], function () {
        Router::post("/financeiro/contas-a-pagar/delete/{id}", "ContasPagarController@delete");
    });

    // DDA — Débito Direto Autorizado
    Router::group(["middleware" => ["Permission:view_contas_pagar"]], function () {
        Router::get("/financeiro/contas-a-pagar/dda", "ContasPagarController@ddaIndex");
        Router::get("/financeiro/contas-a-pagar/dda/detalhar/{id}", "ContasPagarController@ddaDetalhar");
    });
    Router::group(["middleware" => ["Permission:edit_contas_pagar"]], function () {
        Router::post("/financeiro/contas-a-pagar/dda/sincronizar", "ContasPagarController@ddaSincronizar");
        Router::post("/financeiro/contas-a-pagar/dda/importar/{id}", "ContasPagarController@ddaImportar");
        Router::post("/financeiro/contas-a-pagar/dda/pagar/{id}", "ContasPagarController@ddaPagar");
        Router::post("/financeiro/contas-a-pagar/dda/ignorar/{id}", "ContasPagarController@ddaIgnorar");
    });

    // Contas a Receber
    Router::group(["middleware" => ["Permission:view_contas_receber"]], function () {
        Router::get("/financeiro/receber", "ContasReceberController@index");
        Router::get("/financeiro/contas-a-receber", "ContasReceberController@index");
        Router::get("/financeiro/contas-a-receber/anexos/download/{id}", "ContasReceberController@downloadAnexo");
    });

    Router::group(["middleware" => ["Permission:create_contas_receber"]], function () {
        Router::get("/financeiro/contas-a-receber/create", "ContasReceberController@create");
        Router::post("/financeiro/contas-a-receber", "ContasReceberController@store");
    });

    Router::group(["middleware" => ["Permission:edit_contas_receber"]], function () {
        Router::get("/financeiro/contas-a-receber/edit/{id}", "ContasReceberController@edit");
        Router::post("/financeiro/contas-a-receber/update/{id}", "ContasReceberController@update");
        Router::post("/financeiro/contas-a-receber/anexos/upload", "ContasReceberController@uploadAnexo");
        Router::post("/financeiro/contas-a-receber/anexos/delete/{id}", "ContasReceberController@deleteAnexo");
    });

    Router::group(["middleware" => ["Permission:delete_contas_receber"]], function () {
        Router::post("/financeiro/contas-a-receber/delete/{id}", "ContasReceberController@delete");
    });
    Router::group(["middleware" => ["Permission:edit_contas_receber"]], function () {
        Router::post("/financeiro/contas-a-receber/receber-manual/{id}", "ContasReceberController@receberManual");
        Router::post("/financeiro/contas-a-receber/sync-asaas", "ContasReceberController@syncAsaas");
    });

    // ─── Contas Bancárias ─────────────────────────────────────────────────────
    Router::get("/financeiro/contas",                                        "ContasBancariasController@index");
    Router::get("/financeiro/contas/create",                                 "ContasBancariasController@create");
    Router::post("/financeiro/contas",                                       "ContasBancariasController@store");
    Router::get("/financeiro/contas/{id}/edit",                              "ContasBancariasController@edit");
    Router::post("/financeiro/contas/{id}/update",                           "ContasBancariasController@update");
    Router::post("/financeiro/contas/{id}/delete",                           "ContasBancariasController@delete");
    // Movimentações (extrato)
    Router::get("/financeiro/contas/{id}/movimentacoes",                     "ContasBancariasController@movimentacoes");
    Router::get("/financeiro/contas/{id}/movimentacoes/nova",                "ContasBancariasController@novaMovimentacao");
    Router::post("/financeiro/contas/{id}/movimentacoes",                    "ContasBancariasController@storeMovimentacao");
    Router::post("/financeiro/contas/{id}/movimentacoes/{mid}/delete",       "ContasBancariasController@deleteMovimentacao");
    Router::get("/financeiro/contas/{id}/movimentacoes/export",              "ContasBancariasController@exportarExtrato");
    // Open Finance
    Router::get("/financeiro/contas/{id}/openfinance",                       "ContasBancariasController@openfinance");
    Router::post("/financeiro/contas/{id}/openfinance/connect-token",        "ContasBancariasController@connectToken");
    Router::post("/financeiro/contas/{id}/openfinance/salvar",               "ContasBancariasController@salvarConexao");
    Router::post("/financeiro/contas/{id}/openfinance/sincronizar",          "ContasBancariasController@sincronizar");
    Router::get("/financeiro/contas/{id}/openfinance/desconectar",           "ContasBancariasController@desconectar");
    // Importação OFX
    Router::post("/financeiro/contas/{id}/importar-ofx",                     "ContasBancariasController@importarOfx");
    // API JSON para gráficos
    Router::get("/api/financeiro/contas/{id}/saldo",                         "ContasBancariasController@apiSaldo");

    // Faturamento
    Router::group(["middleware" => ["Permission:view_faturamento"]], function () {
        Router::get("/faturamento", "FaturamentoController@index");
    });

    // Notas Fiscais (Faturamento)
    Router::group(["middleware" => ["Permission:view_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais", "NotasFiscaisController@index");
        Router::get("/faturamento/notas-fiscais/show/{id}", "NotasFiscaisController@show");
        Router::post("/faturamento/notas-fiscais/consultar-asaas/{id}", "NotasFiscaisController@consultarAsaas");
    });

    Router::group(["middleware" => ["Permission:create_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais/create", "NotasFiscaisController@create");
        Router::post("/faturamento/notas-fiscais", "NotasFiscaisController@store");
    });

    Router::group(["middleware" => ["Permission:edit_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais/edit/{id}", "NotasFiscaisController@edit");
        Router::post("/faturamento/notas-fiscais/update/{id}", "NotasFiscaisController@update");
        Router::post("/faturamento/notas-fiscais/reemitir-asaas/{id}", "NotasFiscaisController@reemitirAsaas");
        Router::get("/faturamento/notas-fiscais/anexos/download/{id}", "NotasFiscaisController@downloadAnexo");
        Router::post("/faturamento/notas-fiscais/anexos/upload", "NotasFiscaisController@uploadAnexo");
        Router::post("/faturamento/notas-fiscais/anexos/delete/{id}", "NotasFiscaisController@deleteAnexo");
    });

    Router::group(["middleware" => ["Permission:delete_notas_fiscais"]], function () {
        Router::post("/faturamento/notas-fiscais/delete/{id}", "NotasFiscaisController@delete");
    });

    Router::group(["middleware" => ["Permission:import_notas_fiscais"]], function () {
        Router::get("/faturamento/notas-fiscais/importar", "NotasFiscaisController@importForm");
        Router::post("/faturamento/notas-fiscais/importar", "NotasFiscaisController@importStore");
    });

    // Integrações
    Router::group(["middleware" => ["Permission:manage_settings"]], function () {
        Router::get("/integracao/asaas", "IntegracaoController@asaas");
        Router::post("/integracao/asaas/save", "IntegracaoController@saveAsaas");
        Router::post("/integracao/asaas/test", "IntegracaoController@testAsaas");
        Router::post("/integracao/asaas/registrar-webhook", "IntegracaoController@registrarWebhookAsaas");
        Router::get("/integracao/asaas/status-webhook", "IntegracaoController@statusWebhookAsaas");

        // Integração Cora — Boletos
        Router::get("/integracao/cora", "IntegracaoController@cora");
        Router::post("/integracao/cora/save", "IntegracaoController@saveCora");
        Router::post("/integracao/cora/test", "IntegracaoController@testCora");

        Router::get("/integracao/email", "IntegracaoController@emailComAlertas");
        Router::post("/integracao/email/save", "IntegracaoController@saveEmail");
        Router::post("/integracao/email/test", "IntegracaoController@testEmail");
        Router::post("/integracao/email/gerar-chave", "IntegracaoController@gerarChaveEmail");
        Router::post("/integracao/email/alertas/toggle", "IntegracaoController@emailAlertasToggle");
        Router::post("/integracao/email/alertas/salvar", "IntegracaoController@emailAlertasSalvar");
        Router::post("/integracao/email/alertas/disparar", "IntegracaoController@emailAlertasDisparar");

        // Integração Bot WhatsApp
        Router::get("/integracao/whatsapp",               "IntegracaoWhatsappController@index");
        Router::post("/integracao/whatsapp/gerar-token",  "IntegracaoWhatsappController@gerarToken");
        Router::post("/integracao/whatsapp/revogar",      "IntegracaoWhatsappController@revogar");
        Router::get("/integracao/whatsapp/logs/export",   "IntegracaoWhatsappController@exportLogs");
    });

    // ===== HUB I.A =====
    // Gate de leitura no grupo (view_hub_ia); ações de escrita são checadas
    // individualmente com manage_hub_ia dentro de cada controller (defesa em profundidade).
    Router::group(["middleware" => ["Permission:view_hub_ia"]], function () {
        Router::get("/hub-ia",             "HubIaController@index");
        Router::get("/hub-ia/relatorios",  "HubIaController@relatorios");

        Router::get("/hub-ia/conectores",              "HubIaConectoresController@index");
        Router::post("/hub-ia/conectores/store",       "HubIaConectoresController@store");
        Router::post("/hub-ia/conectores/{id}/update", "HubIaConectoresController@update");
        Router::post("/hub-ia/conectores/{id}/delete", "HubIaConectoresController@delete");
        Router::post("/hub-ia/conectores/{id}/testar", "HubIaConectoresController@testar");

        Router::get("/hub-ia/prompts",              "HubIaPromptsController@index");
        Router::post("/hub-ia/prompts/store",       "HubIaPromptsController@store");
        Router::post("/hub-ia/prompts/{id}/update", "HubIaPromptsController@update");
        Router::post("/hub-ia/prompts/{id}/delete", "HubIaPromptsController@delete");

        Router::get("/hub-ia/agentes",              "HubIaAgentesController@index");
        Router::post("/hub-ia/agentes/store",       "HubIaAgentesController@store");
        Router::post("/hub-ia/agentes/{id}/update", "HubIaAgentesController@update");
        Router::post("/hub-ia/agentes/{id}/delete", "HubIaAgentesController@delete");

        Router::get("/hub-ia/chat",         "HubIaChatController@index");
        Router::post("/hub-ia/chat/enviar", "HubIaChatController@enviar");

        Router::get("/hub-ia/conhecimento",              "HubIaConhecimentoController@index");
        Router::post("/hub-ia/conhecimento/upload",      "HubIaConhecimentoController@upload");
        Router::post("/hub-ia/conhecimento/{id}/delete", "HubIaConhecimentoController@delete");

        Router::get("/hub-ia/banco",                "HubIaBancoController@index");
        Router::post("/hub-ia/banco/salvar",        "HubIaBancoController@salvar");
        Router::post("/hub-ia/banco/testar-consulta", "HubIaBancoController@testarConsulta");

        Router::get("/hub-ia/whatsapp",         "HubIaWhatsappController@index");
        Router::post("/hub-ia/whatsapp/salvar", "HubIaWhatsappController@salvar");
    });

    // ===== Módulo Estoque =====
    Router::group(["middleware" => ["Permission:view_crm"]], function () {
        // Listagem e visualização
        Router::get("/estoque/produtos",                         "ProdutosController@index");
        Router::get("/estoque/produtos/buscar",                  "ProdutosController@buscar");
        Router::get("/estoque/produtos/kpis",                    "ProdutosController@kpis");
        Router::get("/estoque/produtos/exportar",                "ProdutosController@exportar");
        Router::get("/estoque/produtos/create",                  "ProdutosController@create");
        Router::post("/estoque/produtos",                        "ProdutosController@store");
        Router::get("/estoque/produtos/{id}/edit",               "ProdutosController@edit");
        Router::post("/estoque/produtos/{id}/update",            "ProdutosController@update");
        Router::post("/estoque/produtos/{id}/delete",            "ProdutosController@delete");
        Router::post("/estoque/produtos/{id}/duplicar",          "ProdutosController@duplicar");
        Router::post("/estoque/produtos/{id}/toggle-status",     "ProdutosController@toggleStatus");
        Router::get("/estoque/produtos/{id}",                    "ProdutosController@show");
        // Componentes (AJAX)
        Router::post("/estoque/produtos/{id}/componente/add",    "ProdutosController@addComponente");
        Router::post("/estoque/produtos/componente/delete/{id}", "ProdutosController@deleteComponente");
        // Comissões (AJAX)
        Router::post("/estoque/produtos/{id}/comissao/add",      "ProdutosController@addComissao");
        Router::post("/estoque/produtos/comissao/delete/{id}",   "ProdutosController@deleteComissao");
        Router::post("/estoque/produtos/comissao/toggle/{id}",   "ProdutosController@toggleComissao");
        // Movimentação de estoque
        Router::post("/estoque/produtos/{id}/movimentacao",      "ProdutosController@movimentacao");
        // Upload de imagem
        Router::post("/estoque/produtos/{id}/upload-imagem",     "ProdutosController@uploadImagem");

        // ── Movimentações de Estoque ──────────────────────────────────────────
        Router::get("/estoque/movimentacoes",                          "MovimentacoesController@index");
        Router::get("/estoque/movimentacoes/create",                   "MovimentacoesController@create");
        Router::post("/estoque/movimentacoes",                         "MovimentacoesController@store");
        Router::get("/estoque/movimentacoes/importar-xml",             "MovimentacoesController@importarXmlForm");
        Router::post("/estoque/movimentacoes/importar-xml",            "MovimentacoesController@importarXmlProcess");
        Router::post("/estoque/movimentacoes/importar-xml/confirmar",  "MovimentacoesController@importarXmlConfirmar");
        Router::get("/estoque/movimentacoes/buscar-produto",           "MovimentacoesController@buscarProduto");
        Router::get("/estoque/movimentacoes/{id}",                     "MovimentacoesController@show");

        // ── Pedidos de Compra ─────────────────────────────────────────────────
        Router::get("/estoque/compras",                                "MovimentacoesController@comprasIndex");
        Router::get("/estoque/compras/create",                         "MovimentacoesController@comprasCreate");
        Router::post("/estoque/compras",                               "MovimentacoesController@comprasStore");
        Router::get("/estoque/compras/{id}",                           "MovimentacoesController@comprasShow");
        Router::post("/estoque/compras/{id}/receber",                  "MovimentacoesController@comprasReceber");
        Router::post("/estoque/compras/{id}/cancelar",                 "MovimentacoesController@comprasCancelar");

        // ── Pedidos de Venda ──────────────────────────────────────────────────
        Router::get("/estoque/vendas",                                 "MovimentacoesController@vendasIndex");
        Router::get("/estoque/vendas/create",                          "MovimentacoesController@vendasCreate");
        Router::post("/estoque/vendas",                                "MovimentacoesController@vendasStore");
        Router::get("/estoque/vendas/{id}",                            "MovimentacoesController@vendasShow");
        Router::get("/estoque/vendas/{id}/edit",                        "MovimentacoesController@vendaEdit");
        Router::post("/estoque/vendas/{id}/update",                     "MovimentacoesController@vendaUpdate");
        Router::post("/estoque/vendas/{id}/expedir",                   "MovimentacoesController@vendasExpedir");
        Router::post("/estoque/vendas/{id}/cancelar",                  "MovimentacoesController@vendasCancelar");
        Router::get("/estoque/vendas/{id}/faturar",                    "MovimentacoesController@vendaFaturarForm");
        Router::post("/estoque/vendas/{id}/faturar",                   "MovimentacoesController@vendaFaturar");
        Router::post("/estoque/vendas/{id}/abrir",                     "MovimentacoesController@vendaAbrir");
        Router::get("/estoque/vendas/{id}/imprimir",                    "MovimentacoesController@vendaImprimir");
    });

    // ===== Módulo CRM =====
    Router::group(["middleware" => ["Permission:view_crm"]], function () {
        Router::get("/crm/funil",         "CrmFunilController@index");
        Router::get("/crm/leads",         "CrmLeadsController@index");
        Router::get("/crm/oportunidades", "CrmOportunidadesController@index");
    });
    Router::group(["middleware" => ["Permission:manage_leads"]], function () {
        Router::get("/crm/leads/create",                  "CrmLeadsController@create");
        Router::post("/crm/leads",                        "CrmLeadsController@store");
        Router::get("/crm/leads/edit/{id}",               "CrmLeadsController@edit");
        Router::post("/crm/leads/update/{id}",            "CrmLeadsController@update");
        Router::post("/crm/leads/delete/{id}",            "CrmLeadsController@delete");
        Router::get("/crm/leads/converter/{id}",          "CrmLeadsController@converter");
        Router::post("/crm/leads/interacao/add",          "CrmLeadsController@addInteracao");
        Router::post("/crm/leads/interacao/delete/{id}",  "CrmLeadsController@deleteInteracao");
        Router::get("/crm/leads/buscar-cnpj",             "CrmLeadsController@buscarCnpj");
        Router::post("/crm/leads/transferir/{id}",        "CrmLeadsController@transferir");
        // Anexos de Leads
        Router::post("/crm/leads/anexo/upload",           "CrmLeadsController@uploadAnexo");
        Router::get("/crm/leads/anexo/download/{id}",     "CrmLeadsController@downloadAnexo");
        Router::post("/crm/leads/anexo/delete/{id}",      "CrmLeadsController@deleteAnexo");
    });
    Router::group(["middleware" => ["Permission:manage_oportunidades"]], function () {
        Router::get("/crm/oportunidades/create",                   "CrmOportunidadesController@create");
        Router::post("/crm/oportunidades",                         "CrmOportunidadesController@store");
        Router::get("/crm/oportunidades/edit/{id}",                "CrmOportunidadesController@edit");
        Router::post("/crm/oportunidades/update/{id}",             "CrmOportunidadesController@update");
        Router::post("/crm/oportunidades/delete/{id}",             "CrmOportunidadesController@delete");
        Router::post("/crm/oportunidades/mover",                   "CrmOportunidadesController@moverEtapa");
        Router::post("/crm/oportunidades/interacao/add",           "CrmOportunidadesController@addInteracao");
        Router::post("/crm/oportunidades/interacao/delete/{id}",   "CrmOportunidadesController@deleteInteracao");
        Router::post("/crm/oportunidades/update-retorno/{id}",       "CrmOportunidadesController@updateRetorno");
        Router::post("/crm/oportunidades/transferir/{id}",          "CrmOportunidadesController@transferir");
        // Anexos de Oportunidades
        Router::post("/crm/oportunidades/anexo/upload",            "CrmOportunidadesController@uploadAnexo");
        Router::get("/crm/oportunidades/anexo/download/{id}",      "CrmOportunidadesController@downloadAnexo");
        Router::post("/crm/oportunidades/anexo/delete/{id}",       "CrmOportunidadesController@deleteAnexo");
    });

    // ===== Módulo CRM — Propostas =====
    Router::group(["middleware" => ["Permission:view_crm"]], function () {
        Router::get("/crm/propostas",                          "CrmPropostasController@index");
        Router::get("/crm/propostas/create",                   "CrmPropostasController@create");
        Router::post("/crm/propostas",                         "CrmPropostasController@store");
        Router::get("/crm/propostas/buscar-oportunidade",      "CrmPropostasController@buscarOportunidade");
        Router::get("/crm/propostas/buscar-cliente",           "CrmPropostasController@buscarCliente");
        Router::get("/crm/propostas/buscar-produto",           "CrmPropostasController@buscarProduto");
        Router::get("/crm/propostas/{id}",                     "CrmPropostasController@show");
        Router::get("/crm/propostas/{id}/edit",                "CrmPropostasController@edit");
        Router::post("/crm/propostas/{id}/update",             "CrmPropostasController@update");
        Router::post("/crm/propostas/{id}/delete",             "CrmPropostasController@delete");
        Router::get("/crm/propostas/{id}/pdf",                 "CrmPropostasController@pdf");
        Router::post("/crm/propostas/{id}/enviar",             "CrmPropostasController@enviar");
        Router::post("/crm/propostas/{id}/status",             "CrmPropostasController@atualizarStatus");
    });

    // CRM — Relatórios
    Router::group(["middleware" => ["Permission:view_crm"]], function () {
        Router::get("/crm/relatorios",               "CrmRelatoriosController@index");
        Router::get("/crm/relatorios/leads",         "CrmRelatoriosController@leads");
        Router::get("/crm/relatorios/oportunidades", "CrmRelatoriosController@oportunidades");
        Router::get("/crm/relatorios/interacoes",    "CrmRelatoriosController@interacoes");
        Router::get("/crm/relatorios/exportar",      "CrmRelatoriosController@exportar");
    });

    // Logging de Erros do Frontend
    Router::post("/api/log/error", "LogController@saveClientError");

    // Diagnóstico temporário (REMOVER APÓS USO)
    Router::get("/diagnostico/upload-info", "DiagnosticoController@uploadInfo");

    // Perfil do usuário
    Router::group(["middleware" => ["Permission:view_profile"]], function () {
        Router::get("/perfil", "PerfilController@index");
        Router::post("/perfil/update", "PerfilController@update");
        Router::post("/perfil/change-password", "PerfilController@changePassword");
        Router::post("/perfil/2fa/toggle", "PerfilController@toggleTwoFactor");
        // Layout de Exames (Padronização de Importação)
        Router::post("/perfil/layout-exame/store", "PerfilController@layoutExameStore");
        Router::get("/perfil/layout-exame/delete/{id}", "PerfilController@layoutExameDelete");
        // Empresa
        Router::post("/perfil/empresa/save", "PerfilController@empresaSave");
        Router::post("/perfil/empresa/logo", "PerfilController@empresaLogoUpload");
    });

    // Contratos (Operacional)
    Router::get("/contratos", "ContratosController@index");
    Router::get("/contratos/create", "ContratosController@create");
    Router::post("/contratos/store", "ContratosController@store");
    Router::get("/contratos/edit/{id}", "ContratosController@edit");
    Router::post("/contratos/update/{id}", "ContratosController@update");
    Router::get("/contratos/delete/{id}", "ContratosController@delete");
    Router::post("/contratos/upload-anexo", "ContratosController@uploadAnexo");
    Router::get("/contratos/delete-anexo/{id}", "ContratosController@deleteAnexo");
    Router::post("/contratos/nova-apuracao", "ContratosController@novaApuracao");
    Router::post("/contratos/importar-apuracao", "ContratosController@importarApuracao");
    Router::post("/contratos/executar-apuracao", "ContratosController@executarApuracao");
    // Cobranças vinculadas ao contrato
    Router::post("/contratos/gerar-cobrancas/{id}", "ContratosController@gerarCobrancas");
    Router::get("/contratos/cobrancas/{id}", "ContratosController@listarCobrancas");
    // Exames do contrato (Serviços/Exames) — AJAX
    Router::post("/contratos/exames/salvar", "ContratosController@salvarExameContrato");
    Router::post("/contratos/exames/remover", "ContratosController@removerExameContrato");
    Router::get("/contratos/exames/buscar-tabela", "ContratosController@buscarExameTabela");

    // Apuração — Faturamento
    Router::get("/faturamento/apuracao-prestador", "ApuracaoController@prestador");
    Router::get("/faturamento/apuracao-cliente", "ApuracaoController@cliente");
    Router::get("/faturamento/apuracao-prestador/visualizar/{id}", "ApuracaoController@visualizar");
    Router::get("/faturamento/apuracao-cliente/visualizar/{id}", "ApuracaoController@visualizar");
    Router::get("/faturamento/apuracao/faturar/{id}", "ApuracaoController@faturar");
    Router::get("/faturamento/apuracao/delete/{id}", "ApuracaoController@delete");
    // Exclusão forçada com cascata — SOMENTE superadmin (validação no controller)
    Router::get("/faturamento/apuracao/superadmin-delete/{id}", "ApuracaoController@deleteSuperAdmin");
    Router::post("/faturamento/apuracao/recalcular/{id}", "ApuracaoController@recalcular");
    Router::post("/faturamento/apuracao/revincular-medico/{id}", "ApuracaoController@revincularMedico");

    // ── Módulo de Manutenção ─────────────────────────────────────────────────
    Router::get("/manutencao/ordens",                          "ManutencaoController@index");
    Router::get("/manutencao/ordens/create",                   "ManutencaoController@create");
    Router::post("/manutencao/ordens/store",                   "ManutencaoController@store");
    Router::get("/manutencao/ordens/{id}",                     "ManutencaoController@show");
    Router::get("/manutencao/ordens/{id}/edit",                "ManutencaoController@edit");
    Router::post("/manutencao/ordens/{id}/update",             "ManutencaoController@update");
    Router::post("/manutencao/ordens/{id}/status",             "ManutencaoController@alterarStatus");
    Router::post("/manutencao/ordens/{id}/cancelar",           "ManutencaoController@cancelar");
    Router::post("/manutencao/ordens/{id}/enviar",             "ManutencaoController@enviar");
    Router::get("/manutencao/ordens/{id}/imprimir",            "ManutencaoController@imprimir");
    Router::post("/manutencao/ordens/{id}/troca/add",          "ManutencaoController@addTroca");
    Router::post("/manutencao/ordens/{id}/troca/{tid}/delete", "ManutencaoController@deleteTroca");
    // API: buscar equipamentos do cliente (AJAX)
    Router::get("/manutencao/api/equipamentos/{cliente_id}",   "ManutencaoController@apiEquipamentos");
    // API: buscar produtos para autocomplete (AJAX)
    Router::get("/manutencao/api/produtos",                    "ManutencaoController@apiProdutos");

    // ── Módulo de Marketing ──────────────────────────────────────────────────
    // Campanhas
    Router::get("/marketing/campanhas",                         "MarketingCampanhasController@index");
    Router::get("/marketing/campanhas/create",                  "MarketingCampanhasController@create");
    Router::post("/marketing/campanhas",                        "MarketingCampanhasController@store");
    Router::get("/marketing/campanhas/edit/{id}",               "MarketingCampanhasController@edit");
    Router::post("/marketing/campanhas/update/{id}",            "MarketingCampanhasController@update");
    Router::post("/marketing/campanhas/delete/{id}",            "MarketingCampanhasController@delete");
    Router::get("/marketing/campanhas/personalizar/{id}",       "MarketingCampanhasController@personalizar");
    Router::post("/marketing/campanhas/personalizar/{id}",      "MarketingCampanhasController@salvarPersonalizacao");
    Router::post("/marketing/campanhas/envio-teste/{id}",       "MarketingCampanhasController@envioTeste");
    // Disparadores
    Router::get("/marketing/disparadores",                      "MarketingDisparadorController@index");
    Router::get("/marketing/disparadores/create",               "MarketingDisparadorController@create");
    Router::post("/marketing/disparadores",                     "MarketingDisparadorController@store");
    Router::get("/marketing/disparadores/view/{id}",            "MarketingDisparadorController@view");
    Router::post("/marketing/disparadores/delete/{id}",         "MarketingDisparadorController@delete");
    Router::post("/marketing/disparadores/iniciar/{id}",        "MarketingDisparadorController@iniciar");
    Router::post("/marketing/disparadores/pausar/{id}",         "MarketingDisparadorController@pausar");
    Router::post("/marketing/disparadores/processar-lote/{id}", "MarketingDisparadorController@processarLote");
    Router::get("/marketing/disparadores/segmento-count",       "MarketingDisparadorController@segmentoCount");
    // Dashboard de Marketing
    Router::get("/marketing/dashboard",                         "MarketingDisparadorController@dashboard");

    // Configurações (inclui gestão de usuários)
    Router::group(["middleware" => ["Permission:manage_settings"]], function () {
        Router::get("/configuracoes", "ConfiguracoesController@index");
        Router::get("/configuracoes/usuarios/create", "ConfiguracoesController@usuariosCreate");
        Router::post("/configuracoes/usuarios", "ConfiguracoesController@usuariosStore");
        Router::get("/configuracoes/usuarios/edit/{id}", "ConfiguracoesController@usuariosEdit");
        Router::post("/configuracoes/usuarios/update/{id}", "ConfiguracoesController@usuariosUpdate");
        Router::post("/configuracoes/usuarios/reset-password/{id}", "ConfiguracoesController@usuariosResetPassword");
        Router::post("/configuracoes/usuarios/toggle-status/{id}", "ConfiguracoesController@usuariosToggleStatus");
        // Configurações NFS-e Nacional
        Router::post("/configuracoes/nfs/salvar", "ConfiguracoesController@nfsSalvar");
        // Configurações Financeiras
        Router::post("/configuracoes/financeiro/salvar", "ConfiguracoesController@financeiroSalvar");
        // Configurações de Notificações
        Router::post("/configuracoes/notificacoes/salvar", "NotificacoesController@salvarConfiguracoes");
    });

    // ============================================================
    // Notificações — API AJAX (autenticado)
    // ============================================================
    Router::get("/api/notificacoes/count",              "NotificacoesController@count");
    Router::get("/api/notificacoes/recentes",           "NotificacoesController@recentes");
    Router::post("/api/notificacoes/marcar-lida/{id}",  "NotificacoesController@marcarLida");
    Router::post("/api/notificacoes/marcar-todas-lidas","NotificacoesController@marcarTodasLidas");
    Router::post("/api/notificacoes/gerar",             "NotificacoesController@gerar");

    // ============================================================
    // RDV — Registro de Despesas de Viagem
    // ============================================================
    // Viagens
    Router::get("/rdv/viagens",                                   "RdvController@index");
    Router::get("/rdv/viagens/create",                            "RdvController@create");
    Router::post("/rdv/viagens/store",                            "RdvController@store");
    Router::get("/rdv/viagens/{id}",                              "RdvController@show");
    Router::get("/rdv/viagens/{id}/edit",                         "RdvController@edit");
    Router::post("/rdv/viagens/{id}/update",                      "RdvController@update");
    Router::post("/rdv/viagens/{id}/status",                      "RdvController@mudarStatus");
    Router::post("/rdv/viagens/{id}/aprovar",                     "RdvController@aprovar");
    Router::post("/rdv/viagens/{id}/gerar-conta-pagar",           "RdvController@gerarContaPagar");
    // Despesas
    Router::post("/rdv/viagens/{id}/despesas/add",                "RdvController@addDespesa");
    Router::post("/rdv/viagens/{id}/despesas/{did}/edit",         "RdvController@editDespesa");
    Router::post("/rdv/viagens/{id}/despesas/{did}/delete",       "RdvController@deleteDespesa");
    // OCR
    Router::post("/rdv/viagens/{id}/upload-comprovante",         "RdvController@uploadComprovante");
    Router::post("/rdv/viagens/{id}/ocr",                        "RdvController@processarOcr");
    // Rotas comerciais
    Router::get("/rdv/rotas",                                     "RdvController@rotasIndex");
    Router::get("/rdv/rotas/create",                              "RdvController@rotasCreate");
    Router::post("/rdv/rotas/store",                              "RdvController@rotasStore");
    Router::get("/rdv/rotas/{id}",                                "RdvController@rotasShow");
    Router::get("/rdv/rotas/{id}/edit",                           "RdvController@rotasEdit");
    Router::post("/rdv/rotas/{id}/update",                        "RdvController@rotasUpdate");
    Router::post("/rdv/rotas/{id}/delete",                        "RdvController@rotasDelete");
    Router::post("/rdv/rotas/{id}/clientes/add",                  "RdvController@rotasAddCliente");
    Router::post("/rdv/rotas/{id}/clientes/{cid}/remove",         "RdvController@rotasRemoveCliente");

    // ── Manual do Sistema (Wiki) ──────────────────────────────────────────────
    Router::get("/manual",                                        "ManualController@index");
    Router::get("/manual/buscar",                                 "ManualController@buscar");
    Router::get("/manual/admin",                                  "ManualController@admin");
    Router::get("/manual/artigo/novo",                            "ManualController@novoArtigo");
    Router::post("/manual/artigo/salvar",                         "ManualController@salvarArtigo");
    Router::get("/manual/artigo/{slug}/editar",                   "ManualController@editarArtigo");
    Router::post("/manual/artigo/{id}/deletar",                   "ManualController@deletarArtigo");
    Router::get("/manual/artigo/{slug}",                          "ManualController@artigo");
    Router::get("/manual/categoria/{slug}",                       "ManualController@categoria");
});


// ============================================================================
// PAINEL SAAS — control-plane no mesmo domínio ERP, isolado pelo tenant configurado em SAAS_CONTROL_TENANT_ID
// ============================================================================
Router::group(["middleware" => ["Auth", "SaasAdmin"]], function () {
    Router::get("/painel", "SaasDashboardController@index");

    Router::group(["middleware" => ["Permission:view_saas_tenants"]], function () {
        Router::get("/painel/empresas", "SaasEmpresasController@index");
        Router::get("/painel/empresas/buscar-cnpj", "SaasEmpresasController@buscarCnpj");
        Router::get("/painel/empresas/buscar-cep", "SaasEmpresasController@buscarCep");
        Router::get("/painel/empresas/edit/{id}", "SaasEmpresasController@edit");
    });
    Router::group(["middleware" => ["Permission:create_saas_tenants"]], function () {
        Router::get("/painel/empresas/create", "SaasEmpresasController@create");
        Router::post("/painel/empresas", "SaasEmpresasController@store");
    });
    Router::group(["middleware" => ["Permission:edit_saas_tenants"]], function () {
        Router::post("/painel/empresas/update/{id}", "SaasEmpresasController@update");
        Router::post("/painel/empresas/{id}/reenviar-convite", "SaasEmpresasController@resendInvite");
    });
    Router::group(["middleware" => ["Permission:suspend_saas_tenants"]], function () {
        Router::post("/painel/empresas/{id}/status", "SaasEmpresasController@toggleStatus");
    });
    Router::group(["middleware" => ["Permission:impersonate_saas_tenant"]], function () {
        Router::post("/painel/empresas/{id}/impersonar", "SaasEmpresasController@impersonar");
    });

    Router::group(["middleware" => ["Permission:view_saas_plans"]], function () {
        Router::get("/painel/planos", "SaasPlanosController@index");
    });
    Router::group(["middleware" => ["Permission:manage_saas_plans"]], function () {
        Router::get("/painel/planos/create", "SaasPlanosController@create");
        Router::post("/painel/planos", "SaasPlanosController@store");
        Router::get("/painel/planos/edit/{id}", "SaasPlanosController@edit");
        Router::post("/painel/planos/update/{id}", "SaasPlanosController@update");
    });
    Router::group(["middleware" => ["Permission:view_saas_impersonation_logs"]], function () {
        Router::get("/painel/impersonacao/logs", "SaasImpersonacaoController@logs");
    });
});

// A entrada é protegida por token de uso único no domínio ERP compartilhado.
Router::get("/painel/impersonacao/entrar/{token}", "SaasImpersonacaoController@entrar");
// A saída e o retorno preservam a sessão original no mesmo domínio ERP.
Router::group(["middleware" => ["Auth"]], function () {
    Router::post("/painel/impersonacao/sair", "SaasImpersonacaoController@sair");
    Router::get("/painel/impersonacao/retornar/{token}", "SaasImpersonacaoController@retornar");
});
