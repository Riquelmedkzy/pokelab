<?php
class PokemonController {
    public function detalhes(): void {
        $repository = new PokemonRepository();
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $slug = $_GET['slug'] ?? null;
        $pokemon = $id ? $repository->getPokemonById($id) : (is_string($slug) ? $repository->getPokemonBySlug($slug) : null);
        if (!$pokemon) { (new PaginaController())->index(); return; }
        render_view('pokemon', ['title' => $pokemon['name'], 'pokemon' => $pokemon, 'pokemons' => $repository->getAll()]);
    }
}
