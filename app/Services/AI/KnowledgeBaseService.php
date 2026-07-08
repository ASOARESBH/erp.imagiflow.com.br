<?php

namespace App\Services\AI;

use App\Models\HubIaChunk;
use App\Models\HubIaDocumento;

/**
 * Base de Conhecimento (RAG). Upload → extração de texto → chunking →
 * embeddings → busca por similaridade (calculada em PHP, sem banco vetorial).
 *
 * Extração de texto real nesta fase: apenas .txt. PDF/DOCX/XLSX exigem
 * bibliotecas externas não instaláveis neste ambiente de desenvolvimento
 * (sem acesso à internet confirmado) — os documentos ficam arquivados e
 * marcados com status 'erro' e uma mensagem explicando como habilitar cada
 * formato via composer no servidor de produção.
 */
class KnowledgeBaseService
{
    private const CHUNK_SIZE = 800;
    private const CHUNK_OVERLAP = 100;
    private const EXTENSOES_SUPORTADAS = ['txt', 'pdf', 'docx', 'xlsx'];
    private const TAMANHO_MAXIMO_BYTES = 15 * 1024 * 1024; // 15 MB

    private HubIaDocumento $documentoModel;
    private HubIaChunk $chunkModel;

    public function __construct()
    {
        $this->documentoModel = new HubIaDocumento();
        $this->chunkModel     = new HubIaChunk();
    }

    public function upload(array $file, int $usuarioId, ?string $categoria): array
    {
        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['sucesso' => false, 'erro' => 'Nenhum arquivo enviado ou erro no upload.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXTENSOES_SUPORTADAS, true)) {
            return ['sucesso' => false, 'erro' => 'Formato não suportado. Use: ' . implode(', ', self::EXTENSOES_SUPORTADAS) . '.'];
        }
        if ($file['size'] > self::TAMANHO_MAXIMO_BYTES) {
            return ['sucesso' => false, 'erro' => 'Arquivo excede o limite de 15 MB.'];
        }

        $dir = BASE_PATH . '/public/uploads/hub_ia/conhecimento';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fileName = uniqid('doc_', true) . '.' . $ext;
        $destPath = $dir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['sucesso' => false, 'erro' => 'Falha ao mover arquivo para o servidor.'];
        }

        $documentoId = $this->documentoModel->create([
            'usuario_id'    => $usuarioId,
            'nome_original' => $file['name'],
            'file_path'     => '/uploads/hub_ia/conhecimento/' . $fileName,
            'tipo'          => $ext,
            'categoria'     => $categoria,
            'tamanho_bytes' => (int) $file['size'],
            'status'        => 'processando',
        ]);

        if (!$documentoId) {
            @unlink($destPath);
            return ['sucesso' => false, 'erro' => 'Falha ao registrar documento no banco.'];
        }

        return ['sucesso' => true, 'documento_id' => $documentoId];
    }

    /**
     * Extrai texto, divide em chunks e (se um conector OpenAI configurado for
     * informado) gera embeddings. Sem conector, os chunks são salvos sem
     * embedding — ficam arquivados, mas não entram na busca por similaridade.
     */
    public function processar(int $documentoId, ?object $conectorOpenAI = null, ?string $apiKeyPlain = null): bool
    {
        $doc = $this->documentoModel->findById($documentoId);
        if (!$doc) {
            return false;
        }

        $fullPath = BASE_PATH . '/public' . $doc->file_path;
        $texto    = $this->extrairTexto($fullPath, $doc->tipo);

        if ($texto === null) {
            $this->documentoModel->atualizarStatus($documentoId, 'erro', $this->mensagemFormatoNaoSuportado($doc->tipo));
            return false;
        }

        $pedacos = $this->dividirEmChunks($texto);
        if (empty($pedacos)) {
            $this->documentoModel->atualizarStatus($documentoId, 'erro', 'Nenhum texto extraído do documento.');
            return false;
        }

        $ordem = 0;
        foreach ($pedacos as $pedaco) {
            $embedding = null;
            if ($conectorOpenAI && $apiKeyPlain) {
                $embedding = EmbeddingService::gerar($pedaco, $apiKeyPlain);
            }
            $this->chunkModel->create($documentoId, $ordem++, $pedaco, $embedding);
        }

        $this->documentoModel->atualizarStatus($documentoId, 'pronto', null, count($pedacos));
        return true;
    }

    public function excluir(int $documentoId): bool
    {
        $doc = $this->documentoModel->findById($documentoId);
        if ($doc && !empty($doc->file_path)) {
            $full = BASE_PATH . '/public' . $doc->file_path;
            if (file_exists($full)) {
                @unlink($full);
            }
        }
        return $this->documentoModel->delete($documentoId);
    }

    /**
     * Busca os trechos mais relevantes para uma pergunta (RAG), por
     * similaridade de cosseno calculada em PHP. Adequado até alguns milhares
     * de chunks; acima disso, considerar um banco vetorial dedicado.
     */
    public function buscarRelevante(string $pergunta, string $apiKeyOpenAI, int $topK = 4): array
    {
        $embeddingPergunta = EmbeddingService::gerar($pergunta, $apiKeyOpenAI);
        if (!$embeddingPergunta) {
            return [];
        }

        $chunks    = $this->chunkModel->listarComEmbedding();
        $pontuados = [];
        foreach ($chunks as $chunk) {
            $embeddingChunk = json_decode((string) $chunk->embedding, true);
            if (!is_array($embeddingChunk)) {
                continue;
            }
            $score = EmbeddingService::similaridadeCosseno($embeddingPergunta, $embeddingChunk);
            $pontuados[] = ['score' => $score, 'conteudo' => $chunk->conteudo, 'documento' => $chunk->documento_nome];
        }

        usort($pontuados, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($pontuados, 0, $topK);
    }

    private function extrairTexto(string $path, string $tipo): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        return match ($tipo) {
            'txt'   => (string) (@file_get_contents($path) ?: ''),
            default => null, // pdf/docx/xlsx — ver mensagemFormatoNaoSuportado()
        };
    }

    private function mensagemFormatoNaoSuportado(string $tipo): string
    {
        $libs = [
            'pdf'  => 'composer require smalot/pdfparser',
            'docx' => 'composer require phpoffice/phpword',
            'xlsx' => 'composer require phpoffice/phpspreadsheet',
        ];
        $sugestao = $libs[$tipo] ?? 'uma biblioteca de parsing compatível';
        return "Extração de texto para .{$tipo} requer uma biblioteca externa não instalada neste ambiente "
            . "(sem acesso à internet neste servidor de desenvolvimento). No servidor de produção, rode "
            . "\"{$sugestao}\" e implemente a extração em KnowledgeBaseService::extrairTexto() para habilitar este formato.";
    }

    private function dividirEmChunks(string $texto): array
    {
        $texto = trim((string) preg_replace('/\s+/', ' ', $texto));
        if ($texto === '') {
            return [];
        }

        $chunks  = [];
        $tamanho = mb_strlen($texto);
        $inicio  = 0;

        while ($inicio < $tamanho) {
            $fimJanela      = min($inicio + self::CHUNK_SIZE, $tamanho);
            $pedacoBruto    = mb_substr($texto, $inicio, $fimJanela - $inicio);
            $ehUltimoPedaco = $fimJanela >= $tamanho;

            $pedaco = $pedacoBruto;
            if (!$ehUltimoPedaco) {
                // Evita cortar no meio de uma palavra: recua até o último espaço (se plausível)
                $ultimoEspaco = mb_strrpos($pedacoBruto, ' ');
                if ($ultimoEspaco !== false && $ultimoEspaco > self::CHUNK_SIZE * 0.5) {
                    $pedaco = mb_substr($pedacoBruto, 0, $ultimoEspaco);
                }
            }

            $pedacoLimpo = trim($pedaco);
            if ($pedacoLimpo !== '') {
                $chunks[] = $pedacoLimpo;
            }

            // Chegou ao fim do texto — encerra (não há mais nada para sobrepor)
            if ($ehUltimoPedaco) {
                break;
            }

            // Avança pelo tamanho do pedaço usado menos a sobreposição desejada;
            // sempre pelo menos 1 caractere, para nunca entrar em loop infinito.
            $inicio += max(1, mb_strlen($pedaco) - self::CHUNK_OVERLAP);
        }

        return $chunks;
    }
}
