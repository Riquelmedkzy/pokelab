<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#ee414d"><meta name="description" content="Seu laboratório Pokémon Unite. Explore o catálogo, escolha cinco parceiros e descubra a composição da sua equipe.">
<title><?= e($title ?? 'PokéLab') ?> · PokéLab</title>
<link rel="icon" href="<?= e(img('favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(css('global')) ?>">
<link rel="stylesheet" href="<?= e(css(isset($pokemon) ? 'pokemon' : 'index')) ?>">
<script>try{if(localStorage.getItem('pokelab.motion')==='off')document.documentElement.classList.add('motion-off')}catch(e){}</script>
<script src="<?= e(js('index')) ?>" defer></script>
<link rel="stylesheet" href="<?= e(css('features')) ?>">
<link rel="stylesheet" href="<?= e(css('profile')) ?>">
</head>
<body class="<?= isset($pokemon) ? 'pokemon-type-' . e($pokemon['uri']) : 'home' ?>" data-base="<?= e(BASE_URL) ?>">
<a class="skip-link" href="#main">Pular para o conteúdo</a>
<header class="topbar"><div class="topbar-inner">
<a class="brand" href="<?= e(route()) ?>" aria-label="PokéLab, início"><span class="brand-symbol"><?= icon('ball') ?></span><span>poké<span class="brand-light">lab</span><small>SEU PRÓXIMO TIME COMEÇA AQUI</small></span></a>
<?php $user = current_user(); $currentRoute = is_string($_GET['route'] ?? null) ? $_GET['route'] : 'home'; ?>
<nav aria-label="Navegação principal"><a class="nav-link <?= $currentRoute === 'home' ? 'active' : '' ?>" href="<?= e(route()) ?>#catalog"><?= icon('ball') ?> Catálogo</a><a class="nav-link <?= $currentRoute === 'team' ? 'active' : '' ?>" href="<?= e(route('team')) ?>"><?= icon('team') ?> Minha equipe <span class="nav-count" data-team-count>0</span></a></nav>
<div class="header-controls"><button type="button" class="motion-toggle" id="motion-toggle" aria-pressed="false" aria-label="Desativar animações"><?= icon('spark') ?><span>Animações</span><span class="toggle-track" aria-hidden="true"></span></button>
<?php if ($user): $headerAvatar = user_avatar($user); ?><details class="profile-menu" id="profile-menu"><summary aria-label="Menu de perfil de <?= e($user['name']) ?>"><span class="header-avatar"><img src="<?= e(pokemon_image($headerAvatar)) ?>" alt=""></span><span class="account-name"><?= e($user['name']) ?></span><svg class="icon profile-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg></summary><div class="profile-dropdown"><div class="profile-dropdown-identity"><strong><?= e($user['name']) ?></strong><span><?= e($user['email']) ?></span></div><a href="<?= e(route('profile')) ?>"><?= icon('team') ?> Meu perfil</a><a href="<?= e(route('team')) ?>"><?= icon('ball') ?> Minha equipe</a><form action="<?= e(route('logout')) ?>" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button><?= icon('arrow') ?> Sair da conta</button></form></div></details><?php else: ?><a class="button button-dark login-link" href="<?= e(route('login')) ?>">Entrar</a><?php endif ?></div>
</div></header>
