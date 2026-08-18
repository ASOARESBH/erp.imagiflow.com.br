<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$view = (string) file_get_contents($root . '/app/Views/configuracoes/index.php');
$controller = (string) file_get_contents($root . '/app/Controllers/ConfiguracoesController.php');
$header = (string) file_get_contents($root . '/app/Views/layout/erp_header.php');
$routes = (string) file_get_contents($root . '/routes/web.php');

function assertConfiguracoesPrivadas(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach (['php_uname(', 'PHP_VERSION', 'Informações do Sistema', 'Integrações Disponíveis', 'tab-geral'] as $vazamento) {
    assertConfiguracoesPrivadas(strpos($view, $vazamento) === false, 'A aba de configurações não deve expor: ' . $vazamento);
}
assertConfiguracoesPrivadas(strpos($controller, "?? 'geral'") === false, 'A aba Geral não pode ser o destino padrão de Configurações.');
assertConfiguracoesPrivadas(strpos($controller, "'geral'") === false, 'A aba Geral não deve ser uma aba permitida no controlador.');
assertConfiguracoesPrivadas(strpos($header, '/configuracoes?tab=geral') === false, 'O menu não deve direcionar para a aba Geral removida.');
assertConfiguracoesPrivadas(strpos($routes, '/diagnostico/upload-info') === false, 'A rota de diagnóstico técnico não pode ficar publicada.');
assertConfiguracoesPrivadas(!is_file($root . '/app/Controllers/DiagnosticoController.php'), 'O controlador de diagnóstico técnico deve ser removido.');

echo "OK: configurações do tenant não expõem detalhes técnicos ou diagnóstico.\n";
