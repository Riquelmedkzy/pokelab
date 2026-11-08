<?php
final class TeamController
{
    public function index(): void
    {
        $pokemons = (new PokemonRepository())->getAll();
        usort($pokemons, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        render_view('team', ['title' => 'Minha equipe', 'pokemons' => $pokemons, 'isBuilder' => true]);
    }
    public function api(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $user = current_user();
        if (!$user) { $this->respond(401, ['error' => 'Entre novamente para salvar sua equipe.']); return; }
        $repository = new TeamRepository();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') { $this->respond(200, $repository->get((int)$user['id'])); return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: GET, POST'); $this->respond(405, ['error' => 'Método não permitido.']); return; }
        if (!verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) { $this->respond(403, ['error' => 'Sessão expirada. Recarregue a página antes de salvar.']); return; }
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) { $this->respond(413, ['error' => 'Requisição muito grande.']); return; }
        try { $data = json_decode(file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR); }
        catch (JsonException $error) { $this->respond(400, ['error' => 'Dados inválidos.']); return; }
        $members = is_array($data) ? ($data['members'] ?? null) : null;
        $revision = is_array($data) ? ($data['revision'] ?? null) : null;
        if (!is_array($members) || !array_is_list($members) || count($members) !== 5 || !is_int($revision) || $revision < 0) {
            $this->respond(422, ['error' => 'A equipe deve ter cinco espaços e uma versão válida.']); return;
        }
        $seen = [];
        $catalog = new PokemonRepository();
        foreach ($members as $id) {
            if ($id === null) continue;
            if (!is_int($id) || !$catalog->getPokemonById($id) || in_array($id, $seen, true)) {
                $this->respond(422, ['error' => 'Escolha Pokémon válidos e sem repetições.']); return;
            }
            $seen[] = $id;
        }
        if (!$repository->save((int)$user['id'], $members, $revision)) {
            $this->respond(409, ['error' => 'A equipe mudou em outra aba. Recarregue para usar a versão salva.']); return;
        }
        $this->respond(200, ['members' => $members, 'revision' => $revision + 1]);
    }
    private function respond(int $status, array $data): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); }
}
