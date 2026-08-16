<?php

declare(strict_types=1);

use App\Controllers\PerfilController;

require_once dirname(__DIR__) . '/app/Core/Controller.php';
require_once dirname(__DIR__) . '/app/Controllers/PerfilController.php';

function assertWebsiteForm(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$controllerReflection = new ReflectionClass(PerfilController::class);
/** @var PerfilController $controller */
$controller = $controllerReflection->newInstanceWithoutConstructor();
$normalize = $controllerReflection->getMethod('normalizeCompanyWebsite');
$normalize->setAccessible(true);

assertWebsiteForm(
    $normalize->invoke($controller, 'www.orixmed.com') === 'https://www.orixmed.com',
    'O domínio sem protocolo deve receber https:// antes de salvar.'
);
assertWebsiteForm(
    $normalize->invoke($controller, 'http://intranet.exemplo.local') === 'http://intranet.exemplo.local',
    'Uma URL com protocolo válido não deve ser alterada.'
);
assertWebsiteForm(
    $normalize->invoke($controller, '   ') === '',
    'Um campo Site vazio deve continuar opcional.'
);

$form = file_get_contents(dirname(__DIR__) . '/app/Views/perfil/tabs/empresa.php');
assertWebsiteForm($form !== false, 'A view do formulário deve estar disponível.');
assertWebsiteForm(
    strpos($form, 'type="url" name="site"') === false,
    'O campo Site não pode usar type=url, pois isso bloqueia domínios sem protocolo antes do submit.'
);
assertWebsiteForm(
    strpos($form, 'id="siteEmpresa"') !== false,
    'O campo Site deve continuar identificado de forma acessível.'
);

echo "OK: campo Site aceita domínio sem protocolo e o normaliza no servidor.\n";
