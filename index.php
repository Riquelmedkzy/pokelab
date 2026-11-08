<?php
require_once __DIR__ . '/config.php';
// Somente endpoints públicos explicitamente registrados podem ser executados.
$routes = ['home' => [HomeController::class, 'index'], 'pokemon_detalhes' => [PokemonController::class, 'detalhes'], 'team' => [TeamController::class, 'index'], 'team_api' => [TeamController::class, 'api'], 'login' => [AuthController::class, 'login'], 'register' => [AuthController::class, 'register'], 'logout' => [AuthController::class, 'logout'], 'profile' => [ProfileController::class, 'index'], 'forgot' => [RecoveryController::class, 'forgot'], 'reset' => [RecoveryController::class, 'reset']];
$requested = $_GET['route'] ?? 'home';
try {
    if (!is_string($requested) || !isset($routes[$requested])) {
        (new PaginaController())->index();
    } else {
        [$class, $method] = $routes[$requested];
        (new $class())->$method();
    }
} catch (Throwable $error) {
    error_log($error->getMessage());
    if (($requested ?? '') === 'team_api') { http_response_code(500); header('Content-Type: application/json'); echo json_encode(['error' => 'Não foi possível acessar o banco. Tente novamente.']); return; }
    http_response_code(500);
    render_view('404', ['title' => 'Sinal interrompido', 'errorMessage' => 'Não conseguimos carregar o catálogo. Tente novamente em instantes.']);
}
