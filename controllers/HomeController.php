<?php
class HomeController {
    public function index(): void {
        $pokemons = (new PokemonRepository())->getAll();
        usort($pokemons, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        render_view('home', ['title' => 'Explore por elemento', 'pokemons' => $pokemons]);
    }
}
