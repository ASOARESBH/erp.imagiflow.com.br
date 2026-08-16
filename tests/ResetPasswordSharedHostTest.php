<?php

declare(strict_types=1);

use App\Core\TenantContext;
use App\Models\PasswordResetToken;

require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/TenantContext.php';
require_once dirname(__DIR__) . '/app/Models/PasswordResetToken.php';

final class ResetTokenTestStatement extends PDOStatement
{
    /** @var array<int, object|false> */
    private array $records;
    private int $affectedRows;
    /** @var array<int, array<string, mixed>|null> */
    public array $executions = [];

    /** @param array<int, object|false> $records */
    public function __construct(array $records = [], int $affectedRows = 0)
    {
        $this->records = $records;
        $this->affectedRows = $affectedRows;
    }

    /** @param array<string, mixed>|null $params */
    public function execute(?array $params = null): bool
    {
        $this->executions[] = $params;
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return array_shift($this->records) ?? false;
    }

    public function rowCount(): int
    {
        return $this->affectedRows;
    }
}

final class ResetTokenTestPdo extends PDO
{
    /** @var array<int, ResetTokenTestStatement> */
    private array $statements;
    /** @var array<int, string> */
    public array $queries = [];

    /** @param array<int, ResetTokenTestStatement> $statements */
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries[] = $query;
        return array_shift($this->statements) ?? false;
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function newTokenModel(PDO $pdo): PasswordResetToken
{
    $reflection = new ReflectionClass(PasswordResetToken::class);
    /** @var PasswordResetToken $model */
    $model = $reflection->newInstanceWithoutConstructor();
    $property = $reflection->getParentClass()->getProperty('pdo');
    $property->setAccessible(true);
    $property->setValue($model, $pdo);

    return $model;
}

$_SESSION = [];
TenantContext::set((object) ['id' => 27, 'slug' => 'empresa-teste']);

$validRecord = (object) [
    'id' => 15,
    'user_id' => 9,
    'tenant_id' => 27,
    'token_hash' => hash('sha256', 'token-de-teste'),
];

$globalLookup = new ResetTokenTestStatement([$validRecord]);
$consumeOnce = new ResetTokenTestStatement([], 1);
$model = newTokenModel(new ResetTokenTestPdo([$globalLookup, $consumeOnce]));

$found = $model->findValidGlobalByTokenHash(hash('sha256', 'token-de-teste'));
assertTrue($found !== false && (int) $found->tenant_id === 27, 'O lookup global deve retornar o tenant gravado no token.');
assertTrue($globalLookup->executions[0][':token_hash'] === hash('sha256', 'token-de-teste'), 'O lookup deve consultar somente o hash do token.');
assertTrue($model->markAsUsed(15), 'O primeiro consumo do token deve afetar exatamente um registro.');
assertTrue($consumeOnce->executions[0][':tenant_id'] === 27, 'O consumo deve usar o tenant definido pelo token.');

$alreadyUsed = new ResetTokenTestStatement([], 0);
$model = newTokenModel(new ResetTokenTestPdo([$alreadyUsed]));
assertTrue(!$model->markAsUsed(15), 'Um token já consumido não pode ser aceito novamente.');

echo "OK: fluxo isolado de token compartilhado validado.\n";
