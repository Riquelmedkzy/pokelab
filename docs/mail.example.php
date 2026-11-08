<?php
// Para ativar SMTP no futuro, copie este arquivo para .private/mail.php.
// Nunca versionar credenciais. O modo padrão, sem configuração, é local.
return [
    'transport' => 'local', // 'smtp' para envio externo
    'app_url' => 'http://localhost/2350/pokemon', // use sua URL HTTPS ao publicar
    'host' => 'smtp.example.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => '',
    'password' => '',
    'from' => 'pokelab@example.com',
    'from_name' => 'PokéLab',
];
