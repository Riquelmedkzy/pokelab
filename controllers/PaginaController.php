<?php
class PaginaController {
    public function index(): void { http_response_code(404); render_view('404', ['title' => 'Pokémon não encontrado']); }
}
