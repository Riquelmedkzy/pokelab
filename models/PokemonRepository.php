<?php
class PokemonRepository {
    private array $pokemons;
    public function __construct() {
        $raw = file_get_contents(VIEWS_PATH . '/assets/js/pokemon.json');
        if ($raw === false) throw new RuntimeException('Catálogo indisponível');
        $this->pokemons = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
    public function getAll(): array { return $this->pokemons; }
    public function getPokemonById(int $id): ?array {
        foreach ($this->pokemons as $pokemon) if ($pokemon['id'] === $id) return $pokemon;
        return null;
    }
    public function getPokemonBySlug(string $slug): ?array {
        foreach ($this->pokemons as $pokemon) if ($pokemon['uri'] === $slug) return $pokemon;
        return null;
    }
}
