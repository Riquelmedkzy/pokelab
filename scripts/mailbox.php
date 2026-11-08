<?php
// Acesso administrativo somente por terminal. Nunca expõe links na interface pública.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/config.php';
$settings = MailService::settings();
$files = glob($settings['outbox_dir'] . '/*.json') ?: [];
rsort($files);
if (!$files) { echo "A caixa local está vazia. Solicite a recuperação pelo site primeiro.\n"; exit; }
foreach ($files as $file) {
    $message = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (isset($argv[1]) && strcasecmp($message['to'], $argv[1]) !== 0) continue;
    echo "Para: ", $message['to'], "\nAssunto: ", $message['subject'], "\n\n", $message['body'], "\n";
    exit;
}
echo "Nenhuma mensagem encontrada para esse endereço.\n";
