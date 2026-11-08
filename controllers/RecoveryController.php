<?php
final class RecoveryController
{
    public function forgot(): void
    {
        $error = null;
        $sent = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? null)) { http_response_code(403); $error = 'Sua sessão expirou. Atualize a página e tente novamente.'; }
            else {
                $email = strtolower(trim(posted_text('email')));
                $users = new UserRepository();
                if (!$users->consumeAttempt('forgot', 10) || !$users->consumeAttempt('forgot-email-' . hash('sha256', $email), 3)) { http_response_code(429); $error = 'Muitos pedidos. Aguarde 15 minutos antes de tentar novamente.'; }
                elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) { http_response_code(422); $error = 'Informe um e-mail válido.'; }
                else {
                    $user = $users->findByEmail($email);
                    if ($user) {
                        $resets = new PasswordResetRepository();
                        $token = $resets->issue($user);
                        try { (new MailService())->sendReset($user, $token); }
                        catch (Throwable $exception) {
                            $resets->revoke($token);
                            // Não registrar token, senha, credenciais ou endereço de recuperação.
                            error_log('PokéLab: falha no transporte de recuperação. Verifique a configuração do e-mail.');
                        }
                    }
                    // Resposta idêntica para e-mails conhecidos e desconhecidos.
                    $_SESSION['recovery_requested'] = true;
                    redirect_to('forgot');
                }
            }
        }
        if (isset($_SESSION['recovery_requested'])) { $sent = true; unset($_SESSION['recovery_requested']); }
        render_view('recovery', ['title' => 'Recuperar senha', 'recoveryMode' => 'forgot', 'error' => $error, 'sent' => $sent, 'localMail' => MailService::isLocal()]);
    }
    public function reset(): void
    {
        header('Referrer-Policy: no-referrer');
        $token = $_SERVER['REQUEST_METHOD'] === 'POST' ? posted_text('token') : (is_string($_GET['token'] ?? null) ? $_GET['token'] : '');
        $repository = new PasswordResetRepository();
        $valid = (bool)$repository->find($token);
        $error = null;
        $complete = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? null)) { http_response_code(403); $error = 'Sua sessão expirou. Atualize a página e tente novamente.'; }
            elseif (!(new UserRepository())->consumeAttempt('reset', 10)) { http_response_code(429); $error = 'Muitas tentativas. Aguarde 15 minutos.'; }
            elseif (!$valid) { http_response_code(400); }
            else {
                $error = password_error(posted_text('new_password'), posted_text('confirm_password'));
                if ($error) http_response_code(422);
                elseif ($repository->consume($token, posted_text('new_password'))) {
                    $_SESSION = [];
                    session_regenerate_id(true);
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    $complete = true;
                } else { $valid = false; http_response_code(400); }
            }
        }
        render_view('recovery', ['title' => $complete ? 'Senha atualizada' : 'Nova senha', 'recoveryMode' => 'reset', 'error' => $error, 'validToken' => $valid, 'token' => $token, 'complete' => $complete]);
    }
}
