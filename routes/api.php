<?php
use App\Core\Router;

/**
 * Rotas da API do Bot WhatsApp
 *
 * Todas as rotas abaixo são protegidas pelo WhatsappApiAuthMiddleware,
 * que valida o token secreto no cabeçalho X-API-Key.
 *
 * Base URL: /api/v1/whatsapp
 *
 * Endpoints disponíveis:
 *  POST /api/v1/whatsapp/identificar      → Identifica o cliente pelo telefone
 *  POST /api/v1/whatsapp/resumo           → Resumo financeiro do cliente
 *  POST /api/v1/whatsapp/faturas          → Lista faturas do cliente
 *  POST /api/v1/whatsapp/notas-fiscais    → Lista notas fiscais do cliente
 *  POST /api/v1/whatsapp/logs             → Lista logs do bot (auditoria)
 */

Router::group(['middleware' => ['WhatsappApiAuth']], function () {
    Router::post('/api/v1/whatsapp/identificar',   'Api\V1\WhatsappAuthController@identificar');
    Router::post('/api/v1/whatsapp/resumo',        'Api\V1\WhatsappResumoController@index');
    Router::post('/api/v1/whatsapp/faturas',       'Api\V1\WhatsappFaturasController@index');
    Router::post('/api/v1/whatsapp/notas-fiscais', 'Api\V1\WhatsappNotasFiscaisController@index');
    Router::post('/api/v1/whatsapp/logs',          'Api\V1\WhatsappLogsController@index');
});

/**
 * API Mobile v1 — cliente Flutter do ERP Imagiflow.
 * As rotas públicas continuam resolvendo o tenant pelo HTTP_HOST. Nas rotas privadas,
 * ApiToken também exige que o token pertença ao mesmo tenant do host.
 */
Router::get('/api/mobile/v1/tenant/ping', 'Api\\Mobile\\V1\\MobileAuthController@ping');
Router::post('/api/mobile/v1/login', 'Api\\Mobile\\V1\\MobileAuthController@login');
Router::post('/api/mobile/v1/forgot-password', 'Api\\Mobile\\V1\\MobileAuthController@forgotPassword');
Router::post('/api/mobile/v1/2fa/verify', 'Api\\Mobile\\V1\\MobileAuthController@verifyTwoFactor');
Router::post('/api/mobile/v1/2fa/resend', 'Api\\Mobile\\V1\\MobileAuthController@resendTwoFactor');

Router::group(['middleware' => ['ApiToken']], function () {
    Router::post('/api/mobile/v1/logout', 'Api\\Mobile\\V1\\MobileAuthController@logout');
    Router::get('/api/mobile/v1/dispositivos', 'Api\\Mobile\\V1\\MobileAuthController@devices');
    Router::post('/api/mobile/v1/dispositivos/{id}/revogar', 'Api\\Mobile\\V1\\MobileAuthController@revokeDevice');
    Router::post('/api/mobile/v1/dispositivos/push-token', 'Api\\Mobile\\V1\\MobileAuthController@updatePushToken');

    Router::get('/api/mobile/v1/perfil/me', 'Api\\Mobile\\V1\\MobileProfileController@me');
    Router::post('/api/mobile/v1/perfil', 'Api\\Mobile\\V1\\MobileProfileController@update');
    Router::post('/api/mobile/v1/perfil/senha', 'Api\\Mobile\\V1\\MobileProfileController@changePassword');
    Router::post('/api/mobile/v1/perfil/foto', 'Api\\Mobile\\V1\\MobileProfileController@uploadPhoto');

    Router::get('/api/mobile/v1/dashboard/resumo', 'Api\\Mobile\\V1\\MobileDashboardController@summary');
    Router::get('/api/mobile/v1/busca', 'Api\\Mobile\\V1\\MobileDashboardController@search');

    Router::get('/api/mobile/v1/clientes', 'Api\\Mobile\\V1\\MobileDirectoryController@clients');
    Router::post('/api/mobile/v1/clientes', 'Api\\Mobile\\V1\\MobileDirectoryController@storeClient');
    Router::get('/api/mobile/v1/clientes/{id}', 'Api\\Mobile\\V1\\MobileDirectoryController@client');
    Router::post('/api/mobile/v1/clientes/{id}', 'Api\\Mobile\\V1\\MobileDirectoryController@updateClient');
    Router::post('/api/mobile/v1/clientes/{id}/contatos', 'Api\\Mobile\\V1\\MobileDirectoryController@storeClientContact');

    Router::get('/api/mobile/v1/fornecedores', 'Api\\Mobile\\V1\\MobileDirectoryController@vendors');
    Router::post('/api/mobile/v1/fornecedores', 'Api\\Mobile\\V1\\MobileDirectoryController@storeVendor');
    Router::get('/api/mobile/v1/fornecedores/{id}', 'Api\\Mobile\\V1\\MobileDirectoryController@vendor');
    Router::post('/api/mobile/v1/fornecedores/{id}', 'Api\\Mobile\\V1\\MobileDirectoryController@updateVendor');

    Router::get('/api/mobile/v1/financeiro/contas-pagar', 'Api\\Mobile\\V1\\MobileFinanceController@payables');
    Router::get('/api/mobile/v1/financeiro/contas-receber', 'Api\\Mobile\\V1\\MobileFinanceController@receivables');
    Router::get('/api/mobile/v1/financeiro/resumo', 'Api\\Mobile\\V1\\MobileFinanceController@summary');
    Router::post('/api/mobile/v1/financeiro/contas-pagar/{id}/pagar', 'Api\\Mobile\\V1\\MobileFinanceController@markPayablePaid');
    Router::post('/api/mobile/v1/financeiro/contas-receber/{id}/receber', 'Api\\Mobile\\V1\\MobileFinanceController@markReceivableReceived');

    Router::get('/api/mobile/v1/contratos', 'Api\\Mobile\\V1\\MobileContractsController@contracts');
    Router::get('/api/mobile/v1/contratos/{id}', 'Api\\Mobile\\V1\\MobileContractsController@contract');
    Router::get('/api/mobile/v1/apuracao/{type}', 'Api\\Mobile\\V1\\MobileContractsController@apuracoes');

    Router::get('/api/mobile/v1/crm/leads', 'Api\\Mobile\\V1\\MobileCrmController@leads');
    Router::post('/api/mobile/v1/crm/leads', 'Api\\Mobile\\V1\\MobileCrmController@storeLead');
    Router::get('/api/mobile/v1/crm/oportunidades', 'Api\\Mobile\\V1\\MobileCrmController@opportunities');
    Router::post('/api/mobile/v1/crm/oportunidades', 'Api\\Mobile\\V1\\MobileCrmController@storeOpportunity');
    Router::get('/api/mobile/v1/crm/funil', 'Api\\Mobile\\V1\\MobileCrmController@pipeline');
    Router::get('/api/mobile/v1/crm/propostas', 'Api\\Mobile\\V1\\MobileCrmController@proposals');
    Router::post('/api/mobile/v1/crm/interacoes', 'Api\\Mobile\\V1\\MobileCrmController@storeInteraction');

    Router::get('/api/mobile/v1/manutencao/ordens', 'Api\\Mobile\\V1\\MobileMaintenanceController@orders');
    Router::post('/api/mobile/v1/manutencao/ordens', 'Api\\Mobile\\V1\\MobileMaintenanceController@storeOrder');
    Router::post('/api/mobile/v1/manutencao/ordens/{id}', 'Api\\Mobile\\V1\\MobileMaintenanceController@updateOrder');

    Router::get('/api/mobile/v1/rdv/viagens', 'Api\\Mobile\\V1\\MobileRdvController@trips');
    Router::post('/api/mobile/v1/rdv/viagens', 'Api\\Mobile\\V1\\MobileRdvController@storeTrip');
    Router::post('/api/mobile/v1/rdv/viagens/{id}/despesas', 'Api\\Mobile\\V1\\MobileRdvController@storeExpense');
    Router::post('/api/mobile/v1/rdv/despesas/{id}/anexo', 'Api\\Mobile\\V1\\MobileRdvController@uploadReceipt');

    Router::post('/api/mobile/v1/localizacoes', 'Api\\Mobile\\V1\\MobileLocationController@store');
    Router::get('/api/mobile/v1/localizacoes/equipe', 'Api\\Mobile\\V1\\MobileLocationController@team');

    Router::get('/api/mobile/v1/notificacoes', 'Api\\Mobile\\V1\\MobileNotificationController@index');
    Router::post('/api/mobile/v1/notificacoes/{id}/lida', 'Api\\Mobile\\V1\\MobileNotificationController@markRead');
});
