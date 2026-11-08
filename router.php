<?php
// Router do servidor de desenvolvimento: publica somente a aplicação e seus assets.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$relative = ltrim($path, '/');
$real = realpath(__DIR__ . '/' . $relative);
$assetRoot = realpath(__DIR__ . '/views/assets');
if ($real && $assetRoot && str_starts_with($real, $assetRoot . DIRECTORY_SEPARATOR) && is_file($real)
    && in_array(strtolower(pathinfo($real, PATHINFO_EXTENSION)), ['css','js','json','png','svg','woff2'], true)) return false;
if ($path === '/' || $path === '/index.php') { require __DIR__ . '/index.php'; return true; }
http_response_code(404);
require __DIR__ . '/config.php';
render_view('404', ['title' => 'Página não encontrada']);
