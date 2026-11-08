<?php
final class MailService
{
    public static function settings(): array
    {
        $settings = is_file(BASE_PATH . '/.private/mail.php') ? require BASE_PATH . '/.private/mail.php' : [];
        if (!is_array($settings)) throw new RuntimeException('Configuração de e-mail inválida.');
        return array_replace(['transport' => 'local', 'host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '', 'password' => '', 'from' => '', 'from_name' => 'PokéLab', 'app_url' => '', 'outbox_dir' => BASE_PATH . '/.private/outbox'], $settings,
            getenv('POKELAB_MAIL_TRANSPORT') ? ['transport' => getenv('POKELAB_MAIL_TRANSPORT')] : [],
            getenv('POKELAB_APP_URL') ? ['app_url' => getenv('POKELAB_APP_URL')] : [],
            getenv('POKELAB_OUTBOX_DIR') ? ['outbox_dir' => getenv('POKELAB_OUTBOX_DIR')] : []);
    }
    public static function isLocal(): bool { return self::settings()['transport'] === 'local'; }
    public function sendReset(array $user, string $token): void
    {
        $settings = self::settings();
        $origin = rtrim($settings['app_url'], '/');
        if (!$origin && $settings['transport'] === 'local') {
            // Origem local fixa: nunca confiar no Host recebido para gerar links.
            $port = (int)($_SERVER['SERVER_PORT'] ?? 80);
            $origin = ($port === 443 ? 'https' : 'http') . '://localhost' . (in_array($port, [80,443], true) ? '' : ':' . $port) . BASE_URL;
        }
        if (!$origin || !filter_var($origin, FILTER_VALIDATE_URL) || !in_array(parse_url($origin, PHP_URL_SCHEME), ['http','https'], true)) throw new RuntimeException('Configure uma URL pública válida para recuperar senhas.');
        $url = $origin . '/index.php?' . http_build_query(['route' => 'reset', 'token' => $token]);
        $subject = 'Recupere sua senha do PokéLab';
        $body = "Olá, " . $user['name'] . ".\n\nRecebemos um pedido para redefinir a senha da sua conta PokéLab.\n\nAbra o link abaixo para escolher uma nova senha:\n" . $url . "\n\nO link expira em 30 minutos e só pode ser usado uma vez. Se você não fez este pedido, ignore esta mensagem. Sua senha continua igual.\n\nEquipe PokéLab\n";
        if ($settings['transport'] === 'local') {
            $directory = $settings['outbox_dir'];
            if (!is_dir($directory) && !mkdir($directory, 0770, true)) throw new RuntimeException('Caixa local indisponível.');
            // Arquivo privado, equivalente a uma caixa de e-mail de desenvolvimento.
            $now = microtime(true);
            $fraction = sprintf('%06d', (int)(($now - floor($now)) * 1000000));
            $path = $directory . '/' . date('Ymd-His') . '-' . $fraction . '-' . bin2hex(random_bytes(8)) . '.json';
            $contents = json_encode(['to' => $user['email'], 'subject' => $subject, 'body' => $body, 'created_at' => date(DATE_ATOM)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (file_put_contents($path, $contents, LOCK_EX) === false) throw new RuntimeException('Não foi possível gravar a mensagem local.');
            @chmod($path, 0660);
            return;
        }
        if ($settings['transport'] !== 'smtp') throw new RuntimeException('Transporte de e-mail não reconhecido.');
        require_once BASE_PATH . '/vendor/PHPMailer/Exception.php';
        require_once BASE_PATH . '/vendor/PHPMailer/SMTP.php';
        require_once BASE_PATH . '/vendor/PHPMailer/PHPMailer.php';
        if (!filter_var($settings['from'], FILTER_VALIDATE_EMAIL) || !$settings['host'] || !in_array($settings['encryption'], ['tls', 'ssl'], true)) throw new RuntimeException('Complete a configuração SMTP com TLS.');
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $settings['host'];
        $mail->Port = (int)$settings['port'];
        $mail->SMTPSecure = $settings['encryption'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'];
        $mail->Password = $settings['password'];
        $mail->Timeout = 10;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($settings['from'], $settings['from_name']);
        $mail->addAddress($user['email'], $user['name']);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    }
}
