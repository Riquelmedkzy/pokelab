'use strict';
(() => {
  const base = document.body.dataset.base || '';
  const session = JSON.parse(document.querySelector('#session-data')?.textContent || '{}');
  let toastTimer;
  function notify(message) {
    const toast = document.querySelector('#toast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('visible'), 3800);
  }
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  let motionWanted = true;
  try { motionWanted = localStorage.getItem('pokelab.motion') !== 'off'; } catch {}
  const canAnimate = () => motionWanted && !reducedMotion.matches;
  function syncMotion() {
    const enabled = canAnimate();
    document.documentElement.classList.toggle('motion-off', !enabled);
    if (!enabled) document.querySelectorAll('.pokemon-playing').forEach(el => el.classList.remove('pokemon-playing'));
    const toggle = document.querySelector('#motion-toggle');
    toggle?.setAttribute('aria-pressed', String(enabled));
    toggle?.setAttribute('aria-label', enabled ? 'Desativar animações' : 'Ativar animações');
    toggle?.setAttribute('title', reducedMotion.matches ? 'Seu dispositivo está configurado para reduzir movimentos.' : (enabled ? 'Animações ativadas' : 'Animações desativadas'));
  }
  document.querySelector('#motion-toggle')?.addEventListener('click', () => {
    if (reducedMotion.matches) {
      motionWanted = false;
      notify('A preferência de movimento reduzido do seu dispositivo está sendo respeitada.');
    } else { motionWanted = !motionWanted; notify(motionWanted ? 'Animações ativadas.' : 'Animações desativadas.'); }
    try { localStorage.setItem('pokelab.motion', motionWanted ? 'on' : 'off'); } catch {}
    syncMotion();
  });
  reducedMotion.addEventListener('change', syncMotion);
  syncMotion();
  document.querySelector('#show-password')?.addEventListener('click', event => {
    const field = document.querySelector('#password');
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    event.currentTarget.textContent = show ? 'Ocultar' : 'Mostrar';
    event.currentTarget.setAttribute('aria-pressed', String(show));
  });
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-animate]');
    if (!button) return;
    const target = button.closest('.pokemon-card') || document.querySelector('[data-motion-target]');
    if (!target) return;
    if (!canAnimate()) { notify('Animações desativadas. Você pode ativá-las no topo da página.'); return; }
    target.classList.remove('pokemon-playing');
    void target.offsetWidth;
    target.classList.add('pokemon-playing');
    target.addEventListener('animationend', () => target.classList.remove('pokemon-playing'), {once:true});
  });

  const profileMenu = document.querySelector('#profile-menu');
  document.addEventListener('click', event => {
    if (profileMenu?.open && !profileMenu.contains(event.target)) profileMenu.open = false;
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && profileMenu?.open) {
      profileMenu.open = false;
      profileMenu.querySelector('summary').focus();
    }
  });
  const avatarSearch = document.querySelector('#avatar-search');
  avatarSearch?.addEventListener('input', () => {
    const normalizeAvatar = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const query = normalizeAvatar(avatarSearch.value.trim());
    let visible = 0;
    document.querySelectorAll('[data-avatar-name]').forEach(choice => {
      choice.hidden = !normalizeAvatar(choice.dataset.avatarName).includes(query);
      if (!choice.hidden) visible++;
    });
    document.querySelector('#avatar-no-results').hidden = visible > 0;
  });
  document.querySelectorAll('input[name="avatar_id"]').forEach(input => input.addEventListener('change', () => {
    const preview = document.querySelector('#avatar-preview img');
    preview.src = input.dataset.avatarSrc;
    preview.alt = `Seu avatar: ${input.dataset.avatarLabel}`;
    document.querySelector('#avatar-name').textContent = input.dataset.avatarLabel;
    document.querySelector('#avatar-selection-status').textContent = `Selecionado: ${input.dataset.avatarLabel}. Salve para aplicar.`;
  }));

  const dataElement = document.querySelector('#pokemon-data');
  if (!dataElement) return;
  const pokemons = JSON.parse(dataElement.textContent);
  const byId = new Map(pokemons.map(p => [p.id, p]));
  const storageKey = 'pokelab.main-team.v1';
  const roles = {Attacker:'Atacante', Defender:'Defensor', 'All-rounder':'Versátil', Speedster:'Velocista', Supporter:'Suporte'};
  const svgPaths = {
    plus:'<path d="M12 5v14M5 12h14"/>', close:'<path d="m6 6 12 12M6 18 18 6"/>',
    ball:'<circle cx="12" cy="12" r="9"/><path d="M3 12h6m6 0h6"/><circle cx="12" cy="12" r="3"/>', check:'<path d="m5 12 4 4L19 6"/>'
  };
  const icon = (name, extra='') => `<svg class="icon ${extra}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${svgPaths[name]}</svg>`;
  const escape = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function validTeam(value) {
    const seen = new Set();
    return Array.from({length:5}, (_,i) => {
      const id = Array.isArray(value) ? value[i] : null;
      if (!Number.isInteger(id) || !byId.has(id) || seen.has(id)) return null;
      seen.add(id); return id;
    });
  }
  let guestTeam = Array(5).fill(null), storageAvailable = true;
  try { guestTeam = validTeam(JSON.parse(localStorage.getItem(storageKey) || 'null')); }
  catch { storageAvailable = false; }
  let team = session.authenticated ? validTeam(session.team?.members) : [...guestTeam];
  let revision = session.team?.revision || 0;
  const requestedSlot = new URLSearchParams(location.search).get('slot');
  let selectedSlot = requestedSlot !== null && /^[0-4]$/.test(requestedSlot) ? Number(requestedSlot) : null;
  let activeSave = Promise.resolve();
  let scanTimer, saveTimer, dirty = false, saving = false, saveBlocked = false, saveError = '';
  const slots = document.querySelector('#team-slots');
  const analyze = document.querySelector('#analyze');
  const errorNode = document.querySelector('#save-error');
  const retryButton = document.querySelector('#retry-save');
  const importButton = document.querySelector('#import-guest');
  if (importButton) importButton.hidden = !session.authenticated || !guestTeam.some(Boolean);
  function saveStatus() {
    const node = document.querySelector('#save-state');
    if (node) {
      node.textContent = session.authenticated
        ? (saveError ? 'Alterações não salvas' : saving || dirty ? 'Salvando na sua conta…' : 'Salva na sua conta')
        : (storageAvailable ? 'Rascunho neste navegador' : 'Apenas nesta sessão');
      node.dataset.state = saveError ? 'error' : saving || dirty ? 'pending' : 'saved';
    }
    if (errorNode) { errorNode.textContent = saveError; errorNode.hidden = !saveError; }
    if (retryButton) { retryButton.hidden = !saveError; retryButton.textContent = saveBlocked ? 'Recarregar equipe da conta' : 'Tentar salvar novamente'; }
  }
  async function saveToAccount() {
    if (!session.authenticated || saving || saveBlocked || !dirty) return;
    saving = true; saveError = ''; saveStatus();
    try {
      while (dirty && !saveBlocked) {
        dirty = false;
        const snapshot = [...team];
        const response = await fetch(session.endpoint, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf},body:JSON.stringify({members:snapshot,revision})});
        const payload = await response.json();
        if (!response.ok) {
          dirty = true;
          saveBlocked = response.status === 409;
          throw new Error(payload.error || 'Não foi possível salvar. Tente novamente.');
        }
        revision = payload.revision;
      }
    } catch (error) {
      dirty = true;
      saveError = error.message === 'Failed to fetch' ? 'Sem conexão com o servidor. Sua alteração ainda não foi salva. Tente novamente.' : error.message;
    } finally { saving = false; saveStatus(); }
  }
  function flushSave() {
    clearTimeout(saveTimer);
    if (!saving) activeSave = saveToAccount();
    return activeSave;
  }
  document.addEventListener('click', async event => {
    const link = event.target.closest('a[href]');
    if (!link || !session.authenticated || (!dirty && !saving) || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    const destination = new URL(link.href, location.href);
    if (destination.origin !== location.origin || (destination.pathname === location.pathname && destination.search === location.search)) return;
    event.preventDefault();
    await flushSave();
    if (dirty) notify('Sua equipe ainda não foi salva. Resolva o aviso antes de sair.');
    else location.assign(destination.href);
  }, true);
  function persist() {
    if (session.authenticated) {
      dirty = true; clearTimeout(saveTimer); saveTimer = setTimeout(flushSave, 200); saveStatus();
    } else {
      try { localStorage.setItem(storageKey, JSON.stringify(team)); storageAvailable = true; }
      catch { storageAvailable = false; notify('O navegador não permitiu guardar seu rascunho.'); }
      saveStatus();
    }
  }
  retryButton?.addEventListener('click', async () => {
    if (saveBlocked) {
      try {
        const response = await fetch(session.endpoint, {credentials:'same-origin'});
        const data = await response.json();
        if (!response.ok) throw new Error(data.error);
        team = validTeam(data.members); revision = data.revision; dirty = false; saveError = ''; saveBlocked = false;
        invalidateAnalysis(); update(); notify('Equipe recarregada da conta.');
      } catch (error) { saveError = error.message; saveStatus(); }
    } else flushSave();
  });
  importButton?.addEventListener('click', () => {
    // Importa somente nos espaços vazios, preservando os integrantes da conta.
    let imported = 0;
    for (const id of guestTeam.filter(Boolean)) {
      if (team.includes(id)) continue;
      const target = team.indexOf(null);
      if (target < 0) break;
      team[target] = id; imported++;
    }
    if (imported) { invalidateAnalysis(); persist(); update(); notify(`${imported} parceiro(s) importado(s) para sua equipe.`); }
    else notify('Nenhum espaço vazio ou parceiro novo para importar.');
  });
  function update() {
    const count = team.filter(Boolean).length;
    document.querySelectorAll('[data-team-count]').forEach(el => el.textContent = count);
    if (slots) {
      slots.innerHTML = team.map((id,i) => {
        const p = byId.get(id);
        if (!p) return `<button class="team-slot empty ${selectedSlot === i ? 'selected' : ''}" data-slot="${i}" aria-label="Escolher Pokémon para o espaço ${i+1}" aria-pressed="${selectedSlot === i}"><span class="slot-number">0${i+1}</span>${icon('ball','slot-ball')}${icon('plus','slot-plus')}<span class="slot-label">Escolher Pokémon</span></button>`;
        const role = p.stats_battle[0];
        return `<div class="team-slot occupied role-${escape(role)} ${selectedSlot === i ? 'selected' : ''}"><span class="slot-number">0${i+1}</span><img class="slot-image" src="${base}/views/assets/img/stat/stat-${escape(p.avatar)}.png" alt=""><strong class="slot-name">${escape(p.name)}</strong><span class="slot-role">${roles[role]}</span><button class="slot-change" data-slot="${i}" aria-label="Trocar ${escape(p.name)} no espaço ${i+1}" aria-pressed="${selectedSlot === i}"></button><button class="slot-remove" data-remove="${i}" aria-label="Remover ${escape(p.name)}">${icon('close')}</button></div>`;
      }).join('');
      document.querySelector('#team-ready').textContent = count === 5 ? 'PRONTA PARA SUA ANÁLISE' : `${5-count} ${5-count === 1 ? 'ESPAÇO DISPONÍVEL' : 'ESPAÇOS DISPONÍVEIS'}`;
      document.querySelector('#team-hint').textContent = selectedSlot !== null ? `Escolha abaixo quem ocupará o espaço ${selectedSlot+1}.` : count === 5 ? 'Equipe completa! Clique em um integrante para trocá-lo.' : 'Escolha um elemento para encontrar seus parceiros.';
      analyze.disabled = count === 0;
    }
    document.querySelectorAll('[data-add]').forEach(button => {
      const p = byId.get(Number(button.dataset.add));
      const added = team.includes(p.id);
      button.classList.toggle('in-team', added);
      button.setAttribute('aria-label', added ? `${p.name} já está na equipe` : `Adicionar ${p.name} à equipe`);
      button.disabled = added;
      button.innerHTML = icon(added ? 'check' : 'plus');
    });
    document.querySelectorAll('.element-directory a, .element-switcher a, .element-back').forEach(link => {
      if (!session.builder) return;
      const url = new URL(link.href, location.href);
      if (selectedSlot === null) url.searchParams.delete('slot');
      else url.searchParams.set('slot', selectedSlot);
      link.href = url.href;
    });
    saveStatus();
  }
  function clearSlotUrl() {
    const url = new URL(location.href);
    url.searchParams.delete('slot'); url.searchParams.delete('pick');
    history.replaceState(null, '', url);
  }
  function invalidateAnalysis() {
    clearTimeout(scanTimer);
    document.querySelector('.team-console')?.classList.remove('scanning');
    const panel = document.querySelector('#analysis');
    if (panel) { panel.hidden = true; panel.removeAttribute('aria-busy'); }
  }
  document.addEventListener('click', event => {
    const add = event.target.closest('[data-add]');
    if (add) {
      const id = Number(add.dataset.add), p = byId.get(id);
      if (!p || team.includes(id)) return;
      const target = selectedSlot !== null ? selectedSlot : team.indexOf(null);
      if (target < 0) { notify('Equipe completa! Clique em um integrante para escolher quem trocar.'); document.querySelector('#team')?.scrollIntoView({behavior:canAnimate()?'smooth':'instant'}); return; }
      const previous = byId.get(team[target]);
      team[target] = id; selectedSlot = null; clearSlotUrl(); invalidateAnalysis(); persist(); update();
      notify(previous ? `${p.name} entrou no lugar de ${previous.name}.` : `${p.name} entrou na sua equipe!`);
      document.querySelector(`.pokemon-card[data-id="${id}"] .pokemon-name`)?.focus({preventScroll:true});
      return;
    }
    const remove = event.target.closest('[data-remove]');
    if (remove) {
      const index = Number(remove.dataset.remove), name = byId.get(team[index])?.name;
      team[index] = null; selectedSlot = null; clearSlotUrl(); invalidateAnalysis(); persist(); update();
      document.querySelector(`[data-slot="${index}"]`)?.focus({preventScroll:true});
      notify(`${name} saiu da equipe.`); return;
    }
    const slot = event.target.closest('[data-slot]');
    if (slot) {
      const index = Number(slot.dataset.slot);
      selectedSlot = selectedSlot === index ? null : index; update();
      if (selectedSlot !== null) {
        document.querySelector('#catalog')?.scrollIntoView({behavior:canAnimate()?'smooth':'instant'});
        document.querySelector('#search')?.focus({preventScroll:true});
        notify(`Escolha um Pokémon para o espaço ${selectedSlot+1}.`);
      }
    }
  });
  const search = document.querySelector('#search');
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  function filter() {
    const query = normalize(search.value.trim());
    let count = 0;
    document.querySelectorAll('.pokemon-card').forEach(card => {
      const show = normalize(card.dataset.name).includes(query);
      card.hidden = !show; if (show) count++;
    });
    document.querySelector('#result-count').textContent = `${count} ${count === 1 ? 'Pokémon encontrado' : 'Pokémon neste elemento'}`;
    document.querySelector('#empty-results').hidden = count > 0;
  }
  search?.addEventListener('input', filter);
  document.querySelector('#reset-filters')?.addEventListener('click', () => { search.value = ''; filter(); search.focus(); });
  analyze?.addEventListener('click', () => {
    const members = team.filter(Boolean).map(id => byId.get(id));
    if (!members.length) return;
    const panel = document.querySelector('#analysis');
    panel.hidden = false; panel.setAttribute('aria-busy','true'); panel.innerHTML = '<div><h3>Escaneando sua equipe…</h3><p>Identificando as funções dos seus parceiros.</p></div>';
    document.querySelector('.team-console').classList.add('scanning');
    clearTimeout(scanTimer);
    scanTimer = setTimeout(() => {
      const counts = Object.fromEntries(Object.keys(roles).map(role => [role,members.filter(p => p.stats_battle[0] === role).length]));
      const missing = Object.keys(roles).filter(role => counts[role] === 0);
      const tips = [];
      if (members.length < 5) tips.push(`Sua equipe tem ${members.length} de 5 integrantes. Complete os espaços para explorar mais combinações.`);
      if (missing.includes('Defender')) tips.push('Sem defensor: considere um parceiro com foco em proteger a equipe.');
      if (missing.includes('Supporter')) tips.push('Sem suporte: experimente um parceiro que ajude os demais integrantes.');
      if (missing.includes('Attacker') && missing.includes('All-rounder')) tips.push('Experimente um atacante ou versátil para diversificar as funções ofensivas.');
      if (new Set(members.map(p=>p.stats_battle[1])).size === 1) tips.push('Todos têm o mesmo alcance. Misturar corpo a corpo e longo alcance pode abrir outras possibilidades.');
      if (!tips.length) tips.push('Você reuniu funções e alcances diferentes. Agora é experimentar como esses parceiros jogam juntos!');
      panel.innerHTML = `<div><h3>${members.length === 5 ? 'Equipe convocada!' : 'Sua equipe está tomando forma.'}</h3><div class="analysis-roles">${Object.entries(counts).filter(([,count])=>count).map(([role,count])=>`<span class="role-${role}">${count} ${roles[role]}</span>`).join('')}</div><small>Leitura das funções do catálogo. Não é uma previsão de vitória ou ranking competitivo.</small></div><div><h3>Para experimentar</h3><ul>${tips.map(t=>`<li>${t}</li>`).join('')}</ul></div>`;
      panel.removeAttribute('aria-busy'); document.querySelector('.team-console').classList.remove('scanning');
    }, canAnimate() ? 850 : 0);
  });
  window.addEventListener('beforeunload', event => {
    if (session.authenticated && (dirty || saving)) { event.preventDefault(); event.returnValue = ''; }
  });
  window.addEventListener('storage', event => {
    if (event.key === 'pokelab.motion') { motionWanted = event.newValue !== 'off'; syncMotion(); }
  });
  update();
  const pick = Number(new URLSearchParams(location.search).get('pick'));
  if (pick && byId.has(pick)) {
    const card = document.querySelector(`.pokemon-card[data-id="${pick}"]`);
    if (card) { card.classList.add('suggested-card'); card.querySelector('[data-add]')?.focus({preventScroll:true}); }
  }
})();
