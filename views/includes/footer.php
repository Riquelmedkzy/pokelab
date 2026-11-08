<footer class="site-footer"><a class="brand footer-brand" href="<?= e(route()) ?>"><?= icon('ball') ?> pokélab</a><p>Feito para explorar. Criado para jogar junto.</p><small>Projeto de estudo independente · Pokémon pertence à Nintendo, Creatures e GAME FREAK.</small></footer>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<?php if (isset($pokemons)): ?>
<script type="application/json" id="pokemon-data"><?= json_encode($pokemons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif ?>
<?php
$sessionUser = current_user();
$accountTeam = $sessionUser ? (new TeamRepository())->get((int)$sessionUser['id']) : null;
?>
<script type="application/json" id="session-data"><?= json_encode(['authenticated' => (bool)$sessionUser, 'team' => $accountTeam, 'csrf' => csrf_token(), 'endpoint' => route('team_api'), 'builder' => !empty($isBuilder)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</body></html>
