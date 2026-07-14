<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Models\ManualSistema;

class ManualController
{
    private ManualSistema $model;

    public function __construct()
    {
        $this->model = new ManualSistema();
    }

    // ─── LEITURA ─────────────────────────────────────────────────────────────

    /**
     * GET /manual
     * Página inicial do manual estilo wiki
     */
    public function index(): void
    {
        $categorias  = $this->model->getCategorias();
        $estatisticas = $this->model->getEstatisticas();

        // Para cada categoria, busca os artigos
        foreach ($categorias as &$cat) {
            $cat['artigos'] = $this->model->getArtigosByCategoria($cat['id']);
        }
        unset($cat);

        View::render('manual/index', [
            'titulo'       => 'Manual do Sistema',
            'categorias'   => $categorias,
            'estatisticas' => $estatisticas,
        ]);
    }

    /**
     * GET /manual/categoria/{slug}
     * Lista artigos de uma categoria
     */
    public function categoria(string $slug): void
    {
        $categoria = $this->model->getCategoriaBySlug($slug);
        if (!$categoria) {
            http_response_code(404);
            View::render('errors/404', ['titulo' => 'Categoria não encontrada']);
            return;
        }

        $artigos = $this->model->getArtigosByCategoria($categoria['id']);
        $categorias = $this->model->getCategorias();

        View::render('manual/categoria', [
            'titulo'     => $categoria['titulo'] . ' — Manual',
            'categoria'  => $categoria,
            'artigos'    => $artigos,
            'categorias' => $categorias,
        ]);
    }

    /**
     * GET /manual/artigo/{slug}
     * Exibe um artigo do manual
     */
    public function artigo(string $slug): void
    {
        $artigo = $this->model->getArtigoBySlug($slug);
        if (!$artigo) {
            http_response_code(404);
            View::render('errors/404', ['titulo' => 'Artigo não encontrado']);
            return;
        }

        $nav        = $this->model->getArtigoAnteriorProximo($artigo['categoria_id'], $artigo['ordem']);
        $categorias = $this->model->getCategorias();
        $artigos    = $this->model->getArtigosByCategoria($artigo['categoria_id']);

        View::render('manual/artigo', [
            'titulo'     => $artigo['titulo'] . ' — Manual',
            'artigo'     => $artigo,
            'nav'        => $nav,
            'categorias' => $categorias,
            'artigos'    => $artigos,
        ]);
    }

    /**
     * GET /manual/buscar?q=...
     * Busca full-text nos artigos
     */
    public function buscar(): void
    {
        $q          = trim($_GET['q'] ?? '');
        $resultados = [];

        if (strlen($q) >= 2) {
            $resultados = $this->model->buscar($q);
        }

        $categorias = $this->model->getCategorias();

        View::render('manual/buscar', [
            'titulo'     => 'Busca no Manual',
            'q'          => $q,
            'resultados' => $resultados,
            'categorias' => $categorias,
        ]);
    }

    // ─── ADMINISTRAÇÃO (apenas admin) ────────────────────────────────────────

    private function requireAdmin(): bool
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado.']);
            return false;
        }
        return true;
    }

    /**
     * GET /manual/admin
     * Painel de administração do manual
     */
    public function admin(): void
    {
        if (!$this->requireAdmin()) return;

        $categorias = $this->model->getCategorias(false);
        foreach ($categorias as &$cat) {
            $cat['artigos'] = $this->model->getArtigosByCategoria($cat['id'], false);
        }
        unset($cat);

        View::render('manual/admin', [
            'titulo'     => 'Administrar Manual',
            'categorias' => $categorias,
        ]);
    }

    /**
     * GET /manual/artigo/{slug}/editar
     * Formulário de edição de artigo
     */
    public function editarArtigo(string $slug): void
    {
        if (!$this->requireAdmin()) return;

        $artigo     = $this->model->getArtigoBySlug($slug);
        $categorias = $this->model->getCategorias(false);
        $historico  = $artigo ? $this->model->getHistorico($artigo['id']) : [];

        View::render('manual/form-artigo', [
            'titulo'     => $artigo ? 'Editar: ' . $artigo['titulo'] : 'Novo Artigo',
            'artigo'     => $artigo,
            'categorias' => $categorias,
            'historico'  => $historico,
        ]);
    }

    /**
     * GET /manual/artigo/novo
     * Formulário de novo artigo
     */
    public function novoArtigo(): void
    {
        if (!$this->requireAdmin()) return;

        $categorias = $this->model->getCategorias(false);

        View::render('manual/form-artigo', [
            'titulo'     => 'Novo Artigo',
            'artigo'     => null,
            'categorias' => $categorias,
            'historico'  => [],
        ]);
    }

    /**
     * POST /manual/artigo/salvar
     * Salva (cria ou atualiza) um artigo
     */
    public function salvarArtigo(): void
    {
        if (!$this->requireAdmin()) return;

        $id         = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $titulo     = trim($_POST['titulo'] ?? '');
        $resumo     = trim($_POST['resumo'] ?? '');
        $conteudo   = $_POST['conteudo'] ?? '';
        $catId      = (int)($_POST['categoria_id'] ?? 0);
        $ordem      = (int)($_POST['ordem'] ?? 0);
        $publicado  = isset($_POST['publicado']) ? 1 : 0;
        $usuarioId  = Auth::id();

        if (!$titulo || !$conteudo || !$catId) {
            $_SESSION['flash_error'] = 'Título, conteúdo e categoria são obrigatórios.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/manual/admin'));
            exit;
        }

        // Gerar slug único
        $slug = $this->model->gerarSlug($titulo);
        $slugBase = $slug;
        $i = 1;
        while ($this->model->slugExiste($slug, $id)) {
            $slug = $slugBase . '-' . $i++;
        }

        $data = [
            ':categoria_id'   => $catId,
            ':slug'           => $slug,
            ':titulo'         => $titulo,
            ':resumo'         => $resumo,
            ':conteudo'       => $conteudo,
            ':ordem'          => $ordem,
            ':publicado'      => $publicado,
            ':criado_por'     => $usuarioId,
            ':atualizado_por' => $usuarioId,
        ];

        $artigoId = $this->model->salvarArtigo($data, $id);

        if ($artigoId) {
            $_SESSION['flash_success'] = 'Artigo salvo com sucesso.';
            header('Location: /manual/artigo/' . $slug);
        } else {
            $_SESSION['flash_error'] = 'Erro ao salvar o artigo.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/manual/admin'));
        }
        exit;
    }

    /**
     * POST /manual/artigo/{id}/deletar
     * Remove um artigo
     */
    public function deletarArtigo(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->model->deletarArtigo($id);
        $_SESSION['flash_success'] = 'Artigo removido.';
        header('Location: /manual/admin');
        exit;
    }
}
