<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RdvHistorico
{
    private \PDO   $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    public function add(int $viagemId, int $uid, string $nome, string $tipo, string $descricao, array $extras = []): void
    {
        try {
            $this->pdo->prepare(
                "INSERT INTO rdv_historico (viagem_id, usuario_id, usuario_nome, tipo, descricao, dados_extras)
                 VALUES (:vid, :uid, :nome, :tipo, :desc, :extras)"
            )->execute([
                ':vid'    => $viagemId,
                ':uid'    => $uid,
                ':nome'   => $nome,
                ':tipo'   => $tipo,
                ':desc'   => $descricao,
                ':extras' => $extras ? json_encode($extras) : null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvHistorico::add] ' . $e->getMessage());
        }
    }

    public function listar(int $viagemId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM rdv_historico WHERE viagem_id = :vid ORDER BY created_at ASC"
        );
        $stmt->execute([':vid' => $viagemId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
