<?php
// Configuração, autoload e renderização compartilhados.
if (session_status() === PHP_SESSION_NONE) {
    session_name('pokelab_session');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/.') ?: '/']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
date_default_timezone_set('America/Sao_Paulo');
define('BASE_PATH', str_replace('\\', '/', __DIR__));
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
define('BASE_URL', rtrim(dirname($script), '/.'));
define('MODELS_PATH', BASE_PATH . '/models');
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('VIEWS_PATH', BASE_PATH . '/views');
define('INCLUDES_PATH', VIEWS_PATH . '/includes');
function css($arquivo) { return BASE_URL . '/views/assets/css/' . $arquivo . '.css'; }
function js($arquivo) { return BASE_URL . '/views/assets/js/' . $arquivo . '.js'; }
function img($arquivo) { return BASE_URL . '/views/assets/img/' . $arquivo; }
function e($valor) { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function route($name = 'home', $params = []) { return BASE_URL . '/index.php?' . http_build_query(['route' => $name] + $params); }
function role_label($role) { return ['Attacker'=>'Atacante','Defender'=>'Defensor','Supporter'=>'Suporte','Speedster'=>'Velocista','All-rounder'=>'Versátil'][$role] ?? $role; }
function level_label($level) { return [1=>'Iniciante',2=>'Intermediário',3=>'Avançado'][$level] ?? 'Não informado'; }
function icon($name, $class = '') {
    $paths = [
         'flame'=>'<path d="M13 3c1 5-4 5-2 9 2-1 3-3 3-5 5 4 6 8 3 12a7 7 0 0 1-11-1C2 12 9 8 9 5c1 2 1 4 2 4 1-2 2-3 2-6Z"/>',
        'drop'=>'<path d="M12 3S5 11 5 15a7 7 0 0 0 14 0c0-4-7-12-7-12Z"/>',
        'leaf'=>'<path d="M20 4C4 2 2 12 6 17s15 2 14-13Z M5 20 16 9"/>',
        'snow'=>'<path d="M12 2v20M3.5 7l17 10M3.5 17l17-10M9 4l3 3 3-3M9 20l3-3 3 3"/>',
        'mountain'=>'<path d="m2 20 7-14 5 9 3-6 5 11Z M7 10l2 2 2-2"/>',
        'wing'=>'<path d="M3 19C7 5 15 4 21 4c-1 7-6 15-15 14M7 16 17 8M12 12l5 1"/>',
        'eye'=>'<path d="M2 12S6 5 12 5s10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'bug'=>'<path d="m8 3 2 3m6-3-2 3M4 9h3m10 0h3M3 15h4m10 0h4M12 7v14"/><rect x="7" y="6" width="10" height="15" rx="5"/>',
        'ghost'=>'<path d="M5 21V10a7 7 0 0 1 14 0v11l-4-3-3 3-3-3Z M9 11v2m6-2v2"/>',
        'moon'=>'<path d="M19 15A8 8 0 0 1 9 5a8 8 0 1 0 10 10Z"/>',
        'ball'=>'<circle cx="12" cy="12" r="9"/><path d="M3 12h6m6 0h6"/><circle cx="12" cy="12" r="3"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
        'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'scan'=>'<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5M4 12h16"/>',
        'close'=>'<path d="m6 6 12 12M6 18 18 6"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'shield'=>'<path d="m12 3 8 3v5c0 5-8 10-8 10S4 16 4 11V6z"/>',
        'bolt'=>'<path d="m14 2-9 12h6l-1 8 9-12h-6z"/>',
        'heart'=>'<path d="M20 5c-3-3-6-1-8 1C8 1 2 4 3 9c1 5 9 11 9 11s8-6 9-11c.3-2-.2-3-1-4Z"/>',
        'spark'=>'<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>',
        'team'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m2-16a3 3 0 0 1 0 6m2 4c2 1 2 3 2 6"/>',
    ];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['ball']) . '</svg>';
}
spl_autoload_register(function ($class) {
    if (!preg_match('/^[A-Za-z]+$/D', $class)) return;
    foreach ([MODELS_PATH, CONTROLLERS_PATH] as $dir) {
        $path = $dir . '/' . $class . '.php';
        if (is_file($path)) { require_once $path; return; }
    }
});
function render_view($viewName, $data = [], $layout = 'header') {
    $allowed = ['home', 'team', 'auth', 'profile', 'recovery', 'pokemon', '404'];
    if (!in_array($viewName, $allowed, true)) { http_response_code(404); $viewName = '404'; }
    extract($data, EXTR_SKIP);
    if ($layout) require INCLUDES_PATH . '/header.php';
    require VIEWS_PATH . '/' . $viewName . '.php';
    if ($layout) require INCLUDES_PATH . '/footer.php';
}

function current_user(): ?array {
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id)) return null;
    $user = (new UserRepository())->findById($id);
    if (!$user || (int)$user['session_version'] !== (int)($_SESSION['auth_version'] ?? 0)) {
        unset($_SESSION['user_id'], $_SESSION['auth_version']);
        return null;
    }
    return $user;
}
function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function verify_csrf($token): bool {
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
function redirect_to(string $name): void { header('Location: ' . route($name), true, 303); exit; }
function type_labels(): array {
    return ['fire'=>'Fogo','water'=>'Água','grass'=>'Planta','electric'=>'Elétrico','ice'=>'Gelo','fighting'=>'Lutador','poison'=>'Veneno','ground'=>'Terra','flying'=>'Voador','psychic'=>'Psíquico','bug'=>'Inseto','rock'=>'Pedra','ghost'=>'Fantasma','dragon'=>'Dragão','dark'=>'Sombrio','steel'=>'Aço','fairy'=>'Fada','normal'=>'Normal'];
}
function pokemon_image(array $pokemon): string { return img('stat/stat-' . $pokemon['avatar'] . '.png'); }

function selected_element(): ?string {
    $type = $_GET['element'] ?? null;
    return is_string($type) && array_key_exists($type, type_labels()) ? $type : null;
}
function element_icon(string $type): string {
    return ['fire'=>'flame','water'=>'drop','grass'=>'leaf','electric'=>'bolt','ice'=>'snow','fighting'=>'shield','poison'=>'drop','ground'=>'mountain','flying'=>'wing','psychic'=>'eye','bug'=>'bug','rock'=>'mountain','ghost'=>'ghost','dragon'=>'spark','dark'=>'moon','steel'=>'shield','fairy'=>'spark','normal'=>'ball'][$type] ?? 'ball';
}

function user_avatar(array $user): array {
    return (new PokemonRepository())->getPokemonById((int)($user['avatar_id'] ?? 3)) ?? (new PokemonRepository())->getPokemonById(3);
}
function password_error(string $password, string $confirmation): ?string {
    if (mb_strlen($password) < 8) return 'Use uma senha com pelo menos 8 caracteres.';
    if (strlen($password) > 72) return 'A senha está muito longa. Use uma versão mais curta.';
    if ($password !== $confirmation) return 'As duas senhas precisam ser iguais.';
    return null;
}
function posted_text(string $key): string { return is_string($_POST[$key] ?? null) ? $_POST[$key] : ''; }
