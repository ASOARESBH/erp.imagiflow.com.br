<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ManualSistema
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── CATEGORIAS ─────────────────────────────────────────────────────────

    public function getCategorias(bool $apenasAtivas = true): array
    {
        $sql = "SELECT * FROM manual_categorias";
        if ($apenasAtivas) {
            $sql .= " WHERE ativo = 1";
        }
        $sql .= " ORDER BY ordem ASC, titulo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoriaBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM manual_categorias WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getCategoriaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM manual_categorias WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function salvarCategoria(array $data, ?int $id = null): bool
    {
        if ($id) {
            $stmt = $this->db->prepare("
                UPDATE manual_categorias
                   SET slug = :slug, titulo = :titulo, descricao = :descricao,
                       icone = :icone, cor = :cor, ordem = :ordem, ativo = :ativo
                 WHERE id = :id
            ");
            return $stmt->execute(array_merge($data, [':id' => $id]));
        }
        $stmt = $this->db->prepare("
            INSERT INTO manual_categorias (slug, titulo, descricao, icone, cor, ordem, ativo)
            VALUES (:slug, :titulo, :descricao, :icone, :cor, :ordem, :ativo)
        ");
        return $stmt->execute($data);
    }

    // ─── ARTIGOS ─────────────────────────────────────────────────────────────

    public function getArtigosByCategoria(int $categoriaId, bool $apenasPublicados = true): array
    {
        $sql = "SELECT id, slug, titulo, resumo, ordem, publicado, atualizado_em
                  FROM manual_artigos
                 WHERE categoria_id = :cat";
        if ($apenasPublicados) {
            $sql .= " AND publicado = 1";
        }
        $sql .= " ORDER BY ordem ASC, titulo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cat' => $categoriaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArtigoBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, c.titulo AS categoria_titulo, c.slug AS categoria_slug,
                   c.icone AS categoria_icone, c.cor AS categoria_cor
              FROM manual_artigos a
              JOIN manual_categorias c ON c.id = a.categoria_id
             WHERE a.slug = :slug
             LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getArtigoById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, c.titulo AS categoria_titulo, c.slug AS categoria_slug
              FROM manual_artigos a
              JOIN manual_categorias c ON c.id = a.categoria_id
             WHERE a.id = :id
             LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getArtigoAnteriorProximo(int $categoriaId, int $ordem): array
    {
        $anterior = $this->db->prepare("
            SELECT id, slug, titulo FROM manual_artigos
             WHERE categoria_id = :cat AND ordem < :ordem AND publicado = 1
             ORDER BY ordem DESC LIMIT 1
        ");
        $anterior->execute([':cat' => $categoriaId, ':ordem' => $ordem]);

        $proximo = $this->db->prepare("
            SELECT id, slug, titulo FROM manual_artigos
             WHERE categoria_id = :cat AND ordem > :ordem AND publicado = 1
             ORDER BY ordem ASC LIMIT 1
        ");
        $proximo->execute([':cat' => $categoriaId, ':ordem' => $ordem]);

        return [
            'anterior' => $anterior->fetch(PDO::FETCH_ASSOC) ?: null,
            'proximo'  => $proximo->fetch(PDO::FETCH_ASSOC) ?: null,
        ];
    }

    public function buscar(string $q, int $limit = 20): array
    {
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare("
            SELECT a.id, a.slug, a.titulo, a.resumo, a.categoria_id,
                   c.titulo AS categoria_titulo, c.slug AS categoria_slug, c.icone AS categoria_icone
              FROM manual_artigos a
              JOIN manual_categorias c ON c.id = a.categoria_id
             WHERE a.publicado = 1
               AND c.ativo = 1
               AND (a.titulo LIKE :q1 OR a.resumo LIKE :q2 OR a.conteudo LIKE :q3)
             ORDER BY
               CASE WHEN a.titulo LIKE :q4 THEN 0 ELSE 1 END,
               a.titulo ASC
             LIMIT :lim
        ");
        $stmt->bindValue(':q1', $like);
        $stmt->bindValue(':q2', $like);
        $stmt->bindValue(':q3', $like);
        $stmt->bindValue(':q4', $like);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvarArtigo(array $data, ?int $id = null): int|false
    {
        if ($id) {
            // Salvar histórico antes de atualizar
            $atual = $this->getArtigoById($id);
            if ($atual) {
                $this->salvarHistorico($id, $atual['conteudo'], $atual['titulo'], $data['atualizado_por'] ?? null);
            }
            $stmt = $this->db->prepare("
                UPDATE manual_artigos
                   SET categoria_id = :categoria_id, slug = :slug, titulo = :titulo,
                       resumo = :resumo, conteudo = :conteudo, ordem = :ordem,
                       publicado = :publicado, atualizado_por = :atualizado_por
                 WHERE id = :id
            ");
            $ok = $stmt->execute(array_merge($data, [':id' => $id]));
            return $ok ? $id : false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO manual_artigos
                (categoria_id, slug, titulo, resumo, conteudo, ordem, publicado, criado_por, atualizado_por)
            VALUES
                (:categoria_id, :slug, :titulo, :resumo, :conteudo, :ordem, :publicado, :criado_por, :atualizado_por)
        ");
        $ok = $stmt->execute($data);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function deletarArtigo(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM manual_artigos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function slugExiste(string $slug, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM manual_artigos WHERE slug = :slug";
        $params = [':slug' => $slug];
        if ($ignorarId) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignorarId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    // ─── HISTÓRICO ───────────────────────────────────────────────────────────

    public function salvarHistorico(int $artigoId, string $conteudo, string $titulo, ?int $usuarioId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO manual_historico (artigo_id, usuario_id, conteudo, titulo)
            VALUES (:artigo_id, :usuario_id, :conteudo, :titulo)
        ");
        $stmt->execute([
            ':artigo_id'  => $artigoId,
            ':usuario_id' => $usuarioId,
            ':conteudo'   => $conteudo,
            ':titulo'     => $titulo,
        ]);
    }

    public function getHistorico(int $artigoId): array
    {
        $stmt = $this->db->prepare("
            SELECT h.*, u.name AS usuario_nome
              FROM manual_historico h
              LEFT JOIN users u ON u.id = h.usuario_id
             WHERE h.artigo_id = :id
             ORDER BY h.criado_em DESC
             LIMIT 20
        ");
        $stmt->execute([':id' => $artigoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── ESTATÍSTICAS ────────────────────────────────────────────────────────

    public function getEstatisticas(): array
    {
        $cats = $this->db->query("SELECT COUNT(*) FROM manual_categorias WHERE ativo = 1")->fetchColumn();
        $arts = $this->db->query("SELECT COUNT(*) FROM manual_artigos WHERE publicado = 1")->fetchColumn();
        return ['categorias' => (int)$cats, 'artigos' => (int)$arts];
    }

    public function gerarSlug(string $titulo): string
    {
        $slug = mb_strtolower($titulo, 'UTF-8');
        $slug = str_replace(
            ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç','ñ'],
            ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', trim($slug));
        return substr($slug, 0, 100);
    }
}
