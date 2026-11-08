<main id="main" class="page builder-page">
<div class="builder-heading"><div><h1>Seu time. Sua assinatura.</h1><p>Cinco parceiros, uma equipe com a sua cara.</p></div><a class="text-link" href="<?= e(route()) ?>#catalog">Voltar ao catálogo <?= icon('arrow') ?></a></div>
<?php if (!current_user()): ?><div class="account-notice"><?= icon('shield') ?><p><strong>Experimente o montador à vontade.</strong> Entre ou crie uma conta para guardar sua equipe no banco de dados.</p><a class="button button-dark" href="<?= e(route('login')) ?>">Entrar e salvar</a></div><?php endif ?>
<section class="team-section" id="team" aria-labelledby="team-title">
<div class="section-heading"><div><h2 id="team-title">Sua equipe principal.</h2><p>Clique em um espaço para escolher ou trocar um integrante.</p></div><div class="team-heading-actions"><span class="save-state" id="save-state" role="status" aria-live="polite"></span><span class="team-counter"><strong data-team-count>0</strong> / 5</span></div></div>
<div class="team-console"><div class="console-topline"><span><i></i> EQUIPE PRINCIPAL</span><span id="team-ready">AGUARDANDO TREINADOR</span></div><div class="team-slots" id="team-slots"></div><noscript><p>Ative JavaScript para editar sua equipe.</p></noscript><div class="console-bottom"><p><?= icon('ball') ?><span id="team-hint">Escolha um elemento para encontrar seus parceiros.</span></p><button class="button button-yellow" id="analyze" disabled><?= icon('scan') ?> Analisar equipe</button></div></div>
<div class="save-actions"><button class="text-link" id="import-guest" hidden>Importar equipe deste navegador</button><button class="button button-dark" id="retry-save" hidden>Tentar salvar novamente</button><p id="save-error" role="alert" hidden></p></div>
<div id="analysis" class="analysis-panel" hidden aria-live="polite"></div>
</section>
<?php require INCLUDES_PATH . '/catalog.php'; ?>
</main>
