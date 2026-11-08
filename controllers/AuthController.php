<?php
final class AuthController
{
    public function login(): void { $this->form('login'); }
    public function register(): void { $this->form('register'); }

    private function form(string $mode): void
    {
        if (current_user()) redirect_to('team');
        $error = null;
        $values = ['name' => '', 'email' => ''];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? null)) {
                http_response_code(403);
                $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
            } else {
                $values['name'] = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
                $values['email'] = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
                $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                $users = new UserRepository();
                if (!$users->consumeAttempt($mode)) {
                    http_response_code(429);
                    $error = 'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.';
                } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 254) {
                    $error = 'Informe um e-mail válido.';
                } elseif (mb_strlen($password) < 8) {
                    $error = 'Use uma senha com pelo menos 8 caracteres.';
                } elseif (strlen($password) > 72) {
                    $error = 'A senha está muito longa. Use uma versão mais curta.';
                } elseif ($mode === 'register') {
                    if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 40) {
                        $error = 'O nome de treinador precisa ter entre 2 e 40 caracteres.';
                    } elseif ($users->findByEmail($values['email'])) {
                        $error = 'Não foi possível cadastrar este e-mail. Tente entrar ou use outro endereço.';
                    } else {
                        try {
                            $id = $users->create($values['name'], $values['email'], $password);
                            $this->signIn($id);
                        } catch (PDOException $exception) {
                            if ($exception->getCode() !== '23000') throw $exception;
                            $error = 'Não foi possível cadastrar este e-mail. Tente entrar ou use outro endereço.';
                        }
                    }
                } else {
                    $user = $users->findByEmail($values['email']);
                    $dummy = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
                    $valid = password_verify($password, $user['password_hash'] ?? $dummy);
                    if (!$user || !$valid) $error = 'E-mail ou senha incorretos. Confira os dados e tente novamente.';
                    else $this->signIn((int)$user['id']);
                }
            }
            if ($error && http_response_code() === 200) http_response_code(422);
        }
        render_view('auth', ['title' => $mode === 'register' ? 'Criar conta' : 'Entrar', 'mode' => $mode, 'error' => $error, 'values' => $values]);
    }
    private function signIn(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['auth_version'] = (int)(new UserRepository())->findById($id)['session_version'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirect_to('team');
    }
    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); render_view('404', ['title' => 'Use o botão Sair para encerrar a sessão.']); return; }
        if (!verify_csrf($_POST['csrf'] ?? null)) { http_response_code(403); render_view('404', ['title' => 'Sessão expirada. Atualize a página.']); return; }
        $_SESSION = [];
        session_regenerate_id(true);
        redirect_to('home');
    }
}
