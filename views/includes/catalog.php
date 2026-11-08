<?php
$element = selected_element();
$labels = type_labels();
$catalogRoute = !empty($isBuilder) ? 'team' : 'home';
$visiblePokemons = $element ? array_values(array_filter($pokemons, fn($p) => in_array($element, $p['types'], true))) : [];
?>
<section id="catalog" class="catalog <?= $element ? 'element-open type-' . e($element) : '' ?>" aria-labelledby="catalog-title">
<?php if (!$element): ?>
<div class="section-heading"><div><h2 id="catalog-title"><?= !empty($isBuilder) ? 'De qual elemento vem seu próximo parceiro?' : 'Cada elemento, novas possibilidades.' ?></h2><p>Escolha um elemento. Explore e pesquise os Pokémon dentro dele.</p></div><span class="catalog-total"><?= count($pokemons) ?> POKÉMON · <?= count($labels) ?> ELEMENTOS</span></div>
<div class="element-directory">
<?php foreach ($labels as $type => $label):
    $matches = array_values(array_filter($pokemons, fn($p) => in_array($type, $p['types'], true)));
    $example = $matches[0] ?? null;
?>
<a class="element-tile type-<?= e($type) ?>" href="<?= e(route($catalogRoute, ['element'=>$type])) ?>#catalog"><div><span class="element-symbol"><?= icon(element_icon($type)) ?></span><h3><?= e($label) ?></h3><span class="element-count"><?= count($matches) ?> Pokémon</span></div><?php if ($example): ?><img src="<?= e(pokemon_image($example)) ?>" alt="" loading="lazy" width="120" height="120"><?php endif ?><span class="element-go"><?= icon('arrow') ?></span></a>
<?php endforeach ?>
</div><p class="catalog-footnote">Pokémon com dois tipos aparecem nos dois elementos.</p>
<?php else: ?>
<a class="text-link element-back" href="<?= e(route($catalogRoute)) ?>#catalog">← Explorar outros elementos</a>
<div class="section-heading"><div><h2 id="catalog-title"><span class="element-title-icon"><?= icon(element_icon($element)) ?></span> Pokémon de <?= e($labels[$element]) ?></h2><p><?= !empty($isBuilder) ? 'Escolha um parceiro e adicione à sua equipe.' : 'Clique na ilustração para dar vida ao Pokémon. Abra o nome para conhecê-lo.' ?></p></div><span class="catalog-total"><?= count($visiblePokemons) ?> PARCEIROS</span></div>
<nav class="element-switcher" aria-label="Escolher elemento"><?php foreach ($labels as $type=>$label): ?><a class="type-<?= e($type) ?> <?= $type === $element ? 'selected' : '' ?>" href="<?= e(route($catalogRoute, ['element'=>$type])) ?>#catalog" <?= $type === $element ? 'aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach ?></nav>
<div class="catalog-toolbar"><div class="search-wrap"><?= icon('search') ?><label class="sr-only" for="search">Pesquisar Pokémon de <?= e($labels[$element]) ?></label><input id="search" type="search" placeholder="Pesquisar no elemento <?= e($labels[$element]) ?>…" autocomplete="off"></div></div>
<div class="results-line"><span id="result-count" role="status" aria-live="polite"><?= count($visiblePokemons) ?> Pokémon neste elemento</span><span><?= icon('spark') ?> Clique na ilustração para animar</span></div>
<div class="pokemon-grid" id="pokemon-grid">
<?php foreach ($visiblePokemons as $p): $role = $p['stats_battle'][0]; ?>
<article class="pokemon-card role-<?= e($role) ?> type-<?= e($element) ?>" data-id="<?= e($p['id']) ?>" data-name="<?= e($p['name']) ?>">
<div class="card-art"><span class="pokemon-number">Nº <?= e(str_pad($p['national_id'] ?? $p['id'], 3, '0', STR_PAD_LEFT)) ?></span><span class="difficulty-dot" title="<?= e(level_label($p['level'])) ?>" aria-label="<?= e(level_label($p['level'])) ?>"><?= str_repeat('•', $p['level']) ?></span><button class="card-image-link animate-pokemon" data-animate="<?= e($p['id']) ?>" aria-label="Animar <?= e($p['name']) ?>"><span class="card-orbit"></span><img src="<?= e(pokemon_image($p)) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="448" height="448"><span class="click-burst" aria-hidden="true"></span></button><span class="role-badge"><?= e(role_label($role)) ?></span></div>
<div class="card-info"><a class="pokemon-name" href="<?= e(route('pokemon_detalhes', ['id'=>$p['id']])) ?>"><?= e($p['name']) ?></a><span class="range-label"><?php foreach ($p['types'] as $i=>$type): ?><?= $i ? ' · ' : '' ?><?= e($labels[$type]) ?><?php endforeach ?></span>
<?php if (!empty($isBuilder)): ?><button class="add-button" data-add="<?= e($p['id']) ?>" aria-label="Adicionar <?= e($p['name']) ?> à equipe"><?= icon('plus') ?></button><?php else: ?><a class="add-button" href="<?= e(route('team', ['element'=>$element,'pick'=>$p['id']])) ?>#catalog" aria-label="Escalar <?= e($p['name']) ?> no montador"><?= icon('team') ?></a><?php endif ?>
</div></article>
<?php endforeach ?>
</div><div class="empty-results" id="empty-results" hidden><?= icon('search') ?><h3>Nenhum parceiro com esse nome.</h3><p>Experimente outro nome dentro deste elemento.</p><button class="button button-dark" id="reset-filters">Limpar pesquisa</button></div>
<?php endif ?>
</section>
