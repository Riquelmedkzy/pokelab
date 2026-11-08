<main id="main" class="page">
<section class="hero" aria-labelledby="hero-title">
<div class="hero-copy"><h1 id="hero-title">Grandes jogadas.<br><span>Começam na escolha.</span></h1><p>Todo treinador tem um estilo. Encontre seus parceiros,<br class="desktop-break"> combine suas forças e monte uma equipe só sua.</p><a class="button button-dark" href="#catalog">Explorar Pokémon <?= icon('arrow') ?></a><span class="hero-note"><?= count($pokemons) ?> possibilidades. A sua combinação.</span></div>
<div class="hero-art" aria-label="Pikachu, Charizard e Gengar"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="hero-cross cross-one">+</div><div class="hero-cross cross-two">+</div><span class="art-label">PARCEIROS DE BATALHA</span><img class="hero-gengar" src="<?= e(img('stat/stat-gengar.png')) ?>" alt="Gengar"><img class="hero-charizard" src="<?= e(img('stat/stat-charizard.png')) ?>" alt="Charizard"><img class="hero-pikachu" src="<?= e(img('stat/stat-pikachu.png')) ?>" alt="Pikachu"><span class="hero-sticker"><?= icon('bolt') ?> Encontre sua sinergia</span></div>
</section>
<?php require INCLUDES_PATH . '/catalog.php'; ?>
<div class="closing-note"><?= icon('ball') ?><p>Uma boa equipe começa com seus favoritos.<br><strong>O resto, vocês descobrem juntos.</strong></p></div>
</main>
