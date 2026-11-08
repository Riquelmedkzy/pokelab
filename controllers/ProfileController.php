<?php
final class ProfileController
{
    public function index(): void
    {
        $user = current_user();
        if (!$user) redirect_to('login');
        $repository = new UserRepository();
        $pokemons = (new PokemonRepository())->getAll();
        usort($pokemons, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        $error = null;
        $success = $_SESSION['profile_message'] ?? null;
        unset($_SESSION['profile_message']);
        $values = ['name' => $user['name'], 'email' => $user['email'], 'avatar_id' => (int)$user['avatar_id']];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = posted_text('action');
            if (!verify_csrf($_POST['csrf'] ?? null)) {
                http_response_code(403);
                $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
            } elseif (!$repository->consumeAttempt('profile', 20)) {
                http_response_code(429);
                $error = 'Muitas tentativas. Aguarde 15 minutos para tentar novamente.';
            } elseif ($action === 'details') {
                $values = ['name' => trim(posted_text('name')), 'email' => strtolower(trim(posted_text('email'))), 'avatar_id' => filter_var($_POST['avatar_id'] ?? null, FILTER_VALIDATE_INT)];
                if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 40) $error = 'Use um nome de treinador entre 2 e 40 caracteres.';
                elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 254) $error = 'Informe um e-mail válido.';
                elseif (!$values['avatar_id'] || !(new PokemonRepository())->getPokemonById($values['avatar_id'])) $error = 'Escolha um dos avatares do catálogo.';
                elseif ($values['email'] !== $user['email'] && !password_verify(posted_text('current_password'), $repository->findByEmail($user['email'])['password_hash'])) $error = 'Para alterar seu e-mail, informe sua senha atual.';
                else {
                    try {
                        $repository->updateProfile((int)$user['id'], $values['name'], $values['email'], $values['avatar_id']);
                        $_SESSION['profile_message'] = 'Perfil atualizado. Seu novo visual já está pronto!';
                        redirect_to('profile');
                    } catch (PDOException $exception) {
                        if ($exception->getCode() !== '23000') throw $exception;
                        $error = 'Este e-mail não está disponível. Escolha outro endereço.';
                    }
                }
            } elseif ($action === 'password') {
                if (!password_verify(posted_text('current_password'), $repository->findByEmail($user['email'])['password_hash'])) $error = 'A senha atual está incorreta.';
                else {
                    $error = password_error(posted_text('new_password'), posted_text('confirm_password'));
                    if (!$error && password_verify(posted_text('new_password'), $repository->findByEmail($user['email'])['password_hash'])) $error = 'Escolha uma senha diferente da atual.';
                    if (!$error) {
                        $version = $repository->changePassword((int)$user['id'], posted_text('new_password'));
                        session_regenerate_id(true);
                        $_SESSION['auth_version'] = $version;
                        $_SESSION['csrf'] = bin2hex(random_bytes(32));
                        $_SESSION['profile_message'] = 'Senha alterada. As outras sessões da sua conta foram encerradas.';
                        redirect_to('profile');
                    }
                }
            } else { $error = 'Escolha uma ação válida para atualizar seu perfil.'; }
            if ($error && http_response_code() === 200) http_response_code(422);
        }
        render_view('profile', ['title' => 'Meu perfil', 'profileUser' => $user, 'pokemons' => $pokemons, 'values' => $values, 'error' => $error, 'success' => $success]);
    }
}
