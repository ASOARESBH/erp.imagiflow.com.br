<?php
/**
 * Script de upload de arquivo único para correção emergencial
 * Acesso: POST https://erp.inlaudo.com.br/upload_fix.php
 * Parâmetros: token, path (relativo à raiz do projeto), file (multipart)
 * REMOVER APÓS USO
 */
$token = $_POST['token'] ?? $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') { http_response_code(403); die('Acesso negado'); }

$baseDir = dirname(__DIR__);

// Upload de arquivo via multipart
if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $relPath   = trim($_POST['path'] ?? '', '/');
    if (empty($relPath)) { http_response_code(400); die('path obrigatório'); }
    $localPath = $baseDir . '/' . $relPath;
    $dir       = dirname($localPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $localPath)) {
        if (function_exists('opcache_invalidate')) opcache_invalidate($localPath, true);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'path' => $relPath, 'size' => filesize($localPath)]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Falha ao mover arquivo']);
    }
    exit;
}

// Listagem simples para confirmar que o script está ativo
header('Content-Type: application/json');
echo json_encode(['status' => 'ready', 'usage' => 'POST com file + path + token']);
