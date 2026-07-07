<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RdvCategoria
{
    private \PDO   $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    public function listar(int $uid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM rdv_categorias
             WHERE ativo = 1 AND (sistema = 1 OR usuario_id = :uid)
             ORDER BY nome ASC"
        );
        $stmt->execute([':uid' => $uid]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function create(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rdv_categorias (usuario_id, nome, icone, cor)
                 VALUES (:uid, :nome, :icone, :cor)"
            );
            $stmt->execute([
                ':uid'   => (int)$d['usuario_id'],
                ':nome'  => trim($d['nome']),
                ':icone' => $d['icone'] ?? 'fa-receipt',
                ':cor'   => $d['cor']   ?? '#6b7280',
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[RdvCategoria::create] ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id, int $uid): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE rdv_categorias SET ativo = 0 WHERE id = :id AND usuario_id = :uid AND sistema = 0"
            )->execute([':id' => $id, ':uid' => $uid]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvCategoria::delete] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Mapeamento de palavras-chave para categoria (classificação automática) ─
    public static function classificarPorTexto(string $texto, array $categorias): ?int
    {
        $mapa = [
            'HOTEL'        => 'Hospedagem',
            'POUSADA'      => 'Hospedagem',
            'HOSTEL'       => 'Hospedagem',
            'UBER'         => 'Uber',
            '99'           => '99',
            'CABIFY'       => 'Uber',
            'IFOOD'        => 'Alimentação',
            'RESTAURANTE'  => 'Alimentação',
            'LANCHONETE'   => 'Alimentação',
            'PADARIA'      => 'Alimentação',
            'PIZZARIA'     => 'Alimentação',
            'CHURRASCARIA' => 'Alimentação',
            'POSTO'        => 'Combustível',
            'SHELL'        => 'Combustível',
            'PETROBRAS'    => 'Combustível',
            'IPIRANGA'     => 'Combustível',
            'PEDAGIO'      => 'Pedágio',
            'PEDÁGIO'      => 'Pedágio',
            'AUTOPASS'     => 'Pedágio',
            'AZUL'         => 'Passagem Aérea',
            'LATAM'        => 'Passagem Aérea',
            'GOL'          => 'Passagem Aérea',
            'AVIANCA'      => 'Passagem Aérea',
            'PARK'         => 'Estacionamento',
            'ESTACIONAMENTO' => 'Estacionamento',
            'FARMACIA'     => 'Farmácia',
            'FARMÁCIA'     => 'Farmácia',
            'DROGARIA'     => 'Farmácia',
            'LAVANDERIA'   => 'Lavanderia',
            'INTERNET'     => 'Internet',
            'WIFI'         => 'Internet',
            'TAXI'         => 'Táxi',
            'TÁXI'         => 'Táxi',
        ];

        $textoUp = strtoupper($texto);
        foreach ($mapa as $palavra => $nomeCat) {
            if (str_contains($textoUp, $palavra)) {
                foreach ($categorias as $cat) {
                    if (strtolower($cat->nome) === strtolower($nomeCat)) {
                        return (int)$cat->id;
                    }
                }
            }
        }
        return null;
    }
}
