<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$controller = (string) file_get_contents($root . '/app/Controllers/ConfiguracoesController.php');
$model = (string) file_get_contents($root . '/app/Models/User.php');
$view = (string) file_get_contents($root . '/app/Views/configuracoes/usuarios/create.php');

function assertCadastroUsuario(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertCadastroUsuario(strpos($controller, '$this->userModel->pdo') === false, 'O controller não pode acessar a conexão protegida do model.');
assertCadastroUsuario(strpos($controller, 'findAnyByEmail($email)') !== false, 'O cadastro deve validar e-mail contra a unicidade global.');
assertCadastroUsuario(strpos($controller, 'setStatusForCurrentTenant') !== false, 'O status deve ser atualizado pelo tenant ativo.');
assertCadastroUsuario(strpos($controller, 'catch (\\Throwable $exception)') !== false, 'Exceções e erros devem ser tratados pelo cadastro.');
assertCadastroUsuario(strpos($controller, 'Mail::sendPasswordResetLink') !== false, 'O convite deve usar o envio centralizado e HTTPS.');
assertCadastroUsuario(strpos($controller, 'passwordResetUrl') !== false, 'O convite deve resolver URL HTTPS confiável.');
assertCadastroUsuario(strpos($model, 'function findAnyByEmail') !== false, 'O model deve expor consulta global de e-mail.');
assertCadastroUsuario(strpos($model, 'function setStatusForCurrentTenant') !== false, 'O model deve expor atualização de status isolada por tenant.');
assertCadastroUsuario(strpos($view, "urlParams.get('ref')") !== false, 'A tela deve mostrar o código de correlação quando necessário.');
assertCadastroUsuario(strpos($view, 'invalid_email') !== false, 'A tela deve informar e-mail inválido de forma acionável.');

echo "OK: cadastro de usuário possui validação, correlação e tratamento seguro.\n";
