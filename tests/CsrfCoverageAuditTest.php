<?php

declare(strict_types=1);

function auditAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$base = dirname(__DIR__);
$router = (string) file_get_contents($base . '/app/Core/Router.php');
$routes = (string) file_get_contents($base . '/routes/web.php');
$middleware = (string) file_get_contents($base . '/app/Middlewares/CsrfMiddleware.php');
$erpHeader = (string) file_get_contents($base . '/app/Views/layout/erp_header.php');
$erpFooter = (string) file_get_contents($base . '/app/Views/layout/erp_footer.php');
$portalHeader = (string) file_get_contents($base . '/app/Views/layout/portal_header.php');
$portalFooter = (string) file_get_contents($base . '/app/Views/layout/portal_footer.php');
$fornecedorForm = (string) file_get_contents($base . '/app/Views/fornecedores/form-enterprise.php');

// Rotas internas e portal devem passar pelo middleware; APIs/webhooks externos não são adicionados a esses grupos.
auditAssert(strpos($router, "'Csrf'            => \\App\\Middlewares\\CsrfMiddleware::class") !== false, 'CSRF não registrado no Router.');
auditAssert(strpos($routes, 'Router::group(["middleware" => ["Auth", "Csrf"]]') !== false, 'Grupo autenticado do ERP sem CSRF.');
auditAssert(strpos($routes, 'Router::group(["middleware" => ["PortalCliente", "Csrf"]]') !== false, 'Grupo autenticado do portal sem CSRF.');

// Tokens devem ser aceitos em formulário e AJAX, e injetados nas duas áreas autenticadas.
auditAssert(strpos($middleware, "HTTP_X_CSRF_TOKEN") !== false, 'Cabeçalho CSRF para AJAX ausente.');
auditAssert(strpos($middleware, "csrf_token") !== false, 'Campo CSRF de formulário ausente.');
auditAssert(strpos($erpHeader, 'meta name="csrf-token"') !== false, 'Meta CSRF do ERP ausente.');
auditAssert(strpos($erpFooter, '/assets/js/csrf-protection.js') !== false, 'Script CSRF do ERP ausente.');
auditAssert(strpos($portalHeader, 'meta name="csrf-token"') !== false, 'Meta CSRF do portal ausente.');
auditAssert(strpos($portalFooter, '/assets/js/csrf-protection.js') !== false, 'Script CSRF do portal ausente.');

// Achado de confiabilidade visual: fornecedor não pode usar a confirmação de cliente.
auditAssert(strpos($fornecedorForm, '"success_entity" => "Fornecedor"') !== false, 'Mensagem de fornecedor não está contextualizada.');

echo "OK: cobertura CSRF e mensagem contextual de fornecedor validadas.\n";
