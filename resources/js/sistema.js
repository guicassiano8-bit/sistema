/**
 * SISTEMA — interações dos componentes (JS puro, sem dependências)
 * Em resources/js/app.js:   import './sistema';
 *
 * Contrato com o Laravel (rotas de toggle respondem JSON quando o pedido é via fetch):
 *   PATCH missoes/{id}/toggle  →  {
 *     done: true,
 *     player: { xp: 1290, xp_max: 2000, level: 27, gold: 3500 },
 *     leveled_up: false,
 *     toast: { type: 'success', title: 'Missão concluída', message: 'Leitura', value: '+50 XP' }
 *   }
 *   PATCH inventario/{id}/toggle → { done: true }
 *   No controller:  if ($request->expectsJson()) return response()->json([...]); else return back()->with('sys_toast', [...]);
 */
const Sistema = (() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fmt = (n) => Number(n).toLocaleString('pt-BR');
  const csrf = () => $('meta[name="csrf-token"]')?.content ?? '';

  /* ─────────────── TOAST ─────────────── */
  function dismiss(el) {
    if (!el || el.dataset.leaving !== undefined) return;
    el.dataset.leaving = '';
    setTimeout(() => el.remove(), reduced() ? 0 : 180);
  }
  // M8 · o toast some quando a barra de tempo termina (ela pausa com hover/foco).
  // Erros (danger) não têm barra: ficam até fechar.
  function arm(el) {
    const timer = $('[data-toast-timer]', el);
    if (timer) timer.addEventListener('animationend', () => dismiss(el), { once: true });
  }
  function toast({ type = 'info', title = 'Sistema', message = '', value = '' } = {}) {
    const stack = $('[data-toast-stack]');
    const tpl = $(`[data-toast-template="${type}"]`) ?? $('[data-toast-template="info"]');
    if (!stack || !tpl) return;
    const el = tpl.content.firstElementChild.cloneNode(true);
    $('[data-toast-title]', el).textContent = title;
    const msg = $('[data-toast-message]', el);
    const val = $('[data-toast-value]', el);
    msg.textContent = message; msg.classList.toggle('hidden', !message);
    val.textContent = value;   val.classList.toggle('hidden', !value);
    stack.prepend(el);
    while (stack.children.length > 3) dismiss(stack.lastElementChild); // no máx. 3 na tela
    arm(el);
    return el;
  }

  /* ─────────────── NÚMEROS QUE CONTAM (Etapa 5 · M7) ─────────────── */
  const parseNum = (t) => Number(String(t).replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
  function countTo(el, to, from = null, ms = 600) {
    from = from ?? parseNum(el.textContent);
    to = Number(to);
    if (from === to) return;
    if (!reduced()) { el.classList.remove('sys-bump'); void el.offsetWidth; el.classList.add('sys-bump'); }
    if (reduced()) { el.textContent = fmt(to); return; }
    const t0 = performance.now();
    const step = (now) => {
      const p = Math.min(1, (now - t0) / ms);
      const e = 1 - Math.pow(1 - p, 3);                       // ease-out cúbico
      el.textContent = fmt(Math.round(from + (to - from) * e));
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  /* ─────────────── "+XP" FLUTUANTE (Etapa 5 · M6) ─────────────── */
  function floatXp(anchor, xpGanho, ouro = 0) {
    if (reduced() || !xpGanho) return;
    const r = anchor.getBoundingClientRect();
    const el = document.createElement('span');
    el.className = 'sys-float-xp';
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML = `+${xpGanho} XP${ouro > 0 ? `<small>+${ouro} OURO</small>` : ''}`;
    el.style.left = `${r.left + r.width / 2}px`;
    el.style.top = `${r.top + r.height / 2}px`;
    document.body.append(el);
    el.addEventListener('animationend', () => el.remove(), { once: true });
  }

  /* ─────────────── CONTADORES DA TELA (Etapa 5) ───────────────
     Todo [data-progress-scope] acima do card recebe ±1 em [data-progress-done],
     ±xp em [data-progress-xp] e recalcula [data-progress-bar] / [data-progress-fill]. */
  function bumpProgress(card, done) {
    // itens do Inventário dentro do card de uma missão não contam como missão concluída
    if (!card.matches('[data-mission]')) return;
    const d = done ? 1 : -1;
    const xpCard = Number(card.dataset.xp || 0);
    let scope = card.closest('[data-progress-scope]');
    while (scope) {
      const nDone = $('[data-progress-done]', scope);
      const nTotal = $('[data-progress-total]', scope);
      const nXp = $('[data-progress-xp]', scope);
      const feitos = nDone ? Math.max(0, parseNum(nDone.dataset.alvo ?? nDone.textContent) + d) : 0;
      if (nDone) { nDone.dataset.alvo = feitos; countTo(nDone, feitos, null, 300); }
      if (nXp) {
        const alvoXp = Math.max(0, parseNum(nXp.dataset.alvo ?? nXp.textContent) + d * xpCard);
        nXp.dataset.alvo = alvoXp; countTo(nXp, alvoXp, null, 500);
      }
      if (nDone && nTotal) {
        const total = parseNum(nTotal.textContent) || 1;
        const bar = $('[data-progress-bar]', scope);
        if (bar) xp.set(bar, feitos, total);
        const fill = $('[data-progress-fill]', scope);
        if (fill) fill.style.width = `${Math.round((feitos / total) * 100)}%`;
      }
      scope = scope.parentElement?.closest('[data-progress-scope]');
    }
  }

  /* ─────────────── BARRA DE XP ─────────────── */
  const xp = {
    set(bar, current, max = null) {
      const pb = $('[role="progressbar"]', bar);
      max = max ?? Number(pb.getAttribute('aria-valuemax'));
      const pct = max > 0 ? Math.min(100, Math.max(0, (current / max) * 100)) : 0;
      $('[data-xp-fill]', bar).style.setProperty('--xp', `${pct}%`);
      pb.setAttribute('aria-valuenow', current);
      pb.setAttribute('aria-valuemax', max);
      const set = (s, v) => { const n = $(s, bar); if (n) n.textContent = fmt(v); };
      set('[data-xp-current]', current); set('[data-xp-max]', max); set('[data-xp-left]', Math.max(0, max - current));
      const shine = $('[data-xp-shine]', bar);
      if (shine && !reduced()) {
        shine.style.animation = 'none'; void shine.offsetWidth;          // reinicia
        shine.style.animation = 'xp-shine 1.2s cubic-bezier(0.16,1,0.3,1) 250ms 1';
      }
    },
    /** Atualiza tudo que mostra dados do jogador: [data-player-xp] barras, [data-player-level], [data-player-gold] */
    player(p) {
      if (!p) return;
      $$('[data-player-xp]').forEach((b) => xp.set(b, p.xp, p.xp_max));
      $$('[data-player-level]').forEach((n) => (n.textContent = p.level));
      $$('[data-player-gold]').forEach((n) => countTo(n, p.gold));
    },
  };

  /* ─────────────── MODAL ─────────────── */
  function openModal(id, opener) {
    const dlg = document.getElementById(id);
    if (!dlg || dlg.open) return;
    delete dlg.dataset.closing;
    dlg.showModal();
    dlg.dispatchEvent(new CustomEvent('sys:open', { detail: { opener } }));
    $('[autofocus]', dlg)?.focus();
  }
  function closeModal(dlg) {
    if (!dlg?.open || dlg.dataset.closing !== undefined) return;
    dlg.dataset.closing = '';
    setTimeout(() => { dlg.close(); delete dlg.dataset.closing; }, reduced() ? 0 : 180);
  }

  /* ─────────────── CONFIRMAÇÃO ─────────────── */
  function confirmThen(form) {
    const dlg = document.getElementById('sys-confirm');
    if (!dlg) return form.dataset.confirmed = '1', form.requestSubmit();
    const okText = form.dataset.confirmOk ?? 'Confirmar';
    const danger = /excluir|apagar|remover/i.test(okText + form.dataset.confirm);
    $('[data-confirm-message]', dlg).textContent = form.dataset.confirm;
    const [def, dang] = [$('[data-confirm-accept="default"]', dlg), $('[data-confirm-accept="danger"]', dlg)];
    def.classList.toggle('hidden', danger); dang.classList.toggle('hidden', !danger);
    const btn = danger ? dang : def;
    $(':scope > span:not(.sys-spinner)', btn).textContent = danger && !form.dataset.confirmOk ? 'Excluir' : okText;
    btn.onclick = () => { form.dataset.confirmed = '1'; closeModal(dlg); form.requestSubmit(); };
    openModal('sys-confirm');
  }

  /* ─────────────── TOGGLES (missão / item) ─────────────── */
  async function toggle(form, card, kind) {
    if (card.dataset.busy !== undefined) return;     // ignora toque duplo
    card.dataset.busy = '';
    const wasDone = card.dataset.state === 'done';
    const btn = $('button[aria-pressed]', form);
    // missão "Fazer Compras": concluir marca todos os itens do card; o estado anterior serve ao rollback
    const itens = kind === 'mission' ? $$('[data-inventory-item]', card) : [];
    const estadoItens = itens.map((it) => it.dataset.state);
    const setItens = (estados) => itens.forEach((it, i) => {
      it.dataset.state = estados[i];
      $('button[aria-pressed]', it)?.setAttribute('aria-pressed', String(estados[i] === 'done'));
    });
    const apply = (done) => {
      card.dataset.state = done ? 'done' : 'pending';
      btn.setAttribute('aria-pressed', String(done));
      setItens(itens.map(() => (done ? 'done' : 'pending')));
      if (done) { card.dataset.justDone = ''; setTimeout(() => delete card.dataset.justDone, 650); }
    };
    apply(!wasDone);                                   // otimista: resposta visual em 0 ms
    bumpProgress(card, !wasDone);
    if (!wasDone) {
      if (navigator.vibrate) navigator.vibrate(12);
      if (kind === 'mission') floatXp(btn, Number(card.dataset.xp || 0), Number(card.dataset.gold || 0));
    }

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
      });
      if (!res.ok) throw new Error(res.status);
      const data = await res.json().catch(() => ({}));
      if (typeof data.done === 'boolean' && data.done === wasDone) { apply(data.done); bumpProgress(card, data.done); }
      if (kind === 'mission') {
        xp.player(data.player);
        if (data.toast) toast(data.toast);
        else if (!wasDone) toast({ type: 'success', title: 'Missão concluída', message: card.dataset.title, value: `+${card.dataset.xp} XP` });
        if (data.leveled_up) document.dispatchEvent(new CustomEvent('sistema:levelup', { detail: data.player }));
      } else if (data.mission) {
        // item que completou (ou desfez) a missão "Fazer Compras" do card
        const missao = card.closest('[data-mission]');
        if (missao && (missao.dataset.state === 'done') !== data.mission.done) {
          missao.dataset.state = data.mission.done ? 'done' : 'pending';
          $('[data-mission-toggle] button[aria-pressed]', missao)?.setAttribute('aria-pressed', String(data.mission.done));
          bumpProgress(missao, data.mission.done);
          if (data.mission.done) floatXp($('[data-mission-toggle] button', missao), Number(missao.dataset.xp || 0), Number(missao.dataset.gold || 0));
        }
        xp.player(data.player);
        if (data.toast) toast(data.toast);
        if (data.leveled_up) document.dispatchEvent(new CustomEvent('sistema:levelup', { detail: data.player }));
      }
    } catch (e) {
      apply(wasDone);                                  // desfaz
      setItens(estadoItens);
      bumpProgress(card, wasDone);
      toast({ type: 'danger', title: 'Falha na conexão', message: 'Não foi possível salvar. Tente de novo.' });
    } finally {
      delete card.dataset.busy;
    }
  }

  /* ─────────────── LEVEL UP (Etapa 3) ─────────────── */
  let levelTimer;
  function levelUp(d = {}) {
    const dlg = $('[data-levelup]');
    if (!dlg || !d.level) return;
    $('[data-levelup-from]', dlg).textContent = d.level - 1;
    $('[data-levelup-to]', dlg).textContent = d.level;
    const rankBox = $('[data-levelup-rank]', dlg);
    rankBox.classList.toggle('hidden', !d.rank_changed);
    rankBox.classList.toggle('flex', !!d.rank_changed);
    if (d.rank_changed) {
      const tpl = $(`[data-rank-template="${d.rank}"]`, dlg);
      $('[data-levelup-rank-badge]', dlg).replaceChildren(tpl.content.cloneNode(true));
    }
    const bar = $('[data-levelup-xp]', dlg);
    xp.set(bar, 0, d.xp_max ?? 100);
    if (!dlg.open) dlg.showModal();
    setTimeout(() => xp.set(bar, d.xp ?? 0, d.xp_max ?? 100), reduced() ? 0 : 950);   // enche depois que a barra aparece (M9)
    if (navigator.vibrate) navigator.vibrate([20, 60, 40]);
    clearTimeout(levelTimer);
    levelTimer = setTimeout(() => dlg.open && dlg.close(), 6000);
  }

  /* ─────────────── TOOLTIP DE GRÁFICO (Etapa 3) ─────────────── */
  let tip, tipTimer;
  function showTip(el) {
    if (!tip) {
      tip = document.createElement('div');
      tip.setAttribute('role', 'tooltip');
      tip.className = 'pointer-events-none fixed z-60 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-sm border border-line-strong bg-surface-raised px-2 py-1 text-xs tabular text-ink shadow-elev';
      document.body.append(tip);
    }
    const r = el.getBoundingClientRect();
    tip.textContent = el.dataset.tip;
    tip.style.left = `${Math.min(Math.max(r.left + r.width / 2, 60), innerWidth - 60)}px`;
    tip.style.top = `${Math.max(r.top - 6, 28)}px`;
    tip.hidden = false;
  }
  const hideTip = () => { if (tip) tip.hidden = true; };

  /* ─────────────── LIGAÇÕES ─────────────── */
  function init() {
    // toasts vindos do servidor
    $$('[data-toast-stack] [data-toast]').forEach(arm);
    // números que mudaram no último redirect (ex.: Ouro após trocar recompensa)
    $$('[data-count-from]').forEach((n) => countTo(n, parseNum(n.textContent), parseNum(n.dataset.countFrom), 700));

    // level up vindo do redirect + evento do toggle
    const lu = $('[data-levelup][data-levelup-initial]');
    if (lu) levelUp(JSON.parse(lu.dataset.levelupInitial));
    document.addEventListener('sistema:levelup', (e) => levelUp(e.detail));

    // tooltips: mouse = hover; toque = mostra 2 s
    document.addEventListener('pointerover', (e) => { const el = e.target.closest('[data-tip]'); if (el && e.pointerType === 'mouse') showTip(el); });
    document.addEventListener('pointerout', (e) => { if (e.pointerType === 'mouse' && e.target.closest('[data-tip]')) hideTip(); });
    document.addEventListener('pointerdown', (e) => {
      const el = e.target.closest('[data-tip]');
      if (!el || e.pointerType === 'mouse') return;
      showTip(el); clearTimeout(tipTimer); tipTimer = setTimeout(hideTip, 2000);
    });
    addEventListener('scroll', hideTip, { passive: true });
    // modais que precisam abrir ao carregar (erro de validação)
    $$('dialog[data-open-on-load]').forEach((d) => openModal(d.id));

    document.addEventListener('click', (e) => {
      const t = e.target;
      // ações rápidas: foca o campo se ele existe nesta tela (ex.: "Novo item" no Inventário)
      const focusEl = t.closest('[data-focus-target]');
      if (focusEl && $(focusEl.dataset.focusTarget)) {
        e.preventDefault();
        const alvo = $(focusEl.dataset.focusTarget);
        alvo.scrollIntoView({ block: 'center', behavior: reduced() ? 'auto' : 'smooth' });
        return alvo.focus({ preventScroll: true });
      }
      const opener = t.closest('[data-modal-open]');
      // só intercepta se o modal existe nesta tela; senão o link segue para ?acao=… (Etapa 4)
      if (opener && document.getElementById(opener.dataset.modalOpen)) {
        e.preventDefault();
        opener.closest('details')?.removeAttribute('open');
        // modal de transferir recebe a URL da missão clicada
        if (opener.dataset.transferUrl) {
          const dlg = document.getElementById(opener.dataset.modalOpen);
          const f = dlg && $('form[data-transfer-form]', dlg);
          if (f) f.action = opener.dataset.transferUrl;
          const name = dlg && $('[data-transfer-title]', dlg);
          if (name) name.textContent = opener.dataset.transferTitle ?? '';
        }
        return openModal(opener.dataset.modalOpen, opener);
      }
      const pw = t.closest('[data-toggle-password]');
      if (pw) {
        const input = $(pw.dataset.togglePassword);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        pw.setAttribute('aria-pressed', String(show));
        pw.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
        return;
      }
      if (t.closest('[data-levelup-close]')) return t.closest('dialog').close();
      // atalhos de data: <button data-set-date="1" data-target="#campo"> (+N dias) ou "segunda"
      const sd = t.closest('[data-set-date]');
      if (sd) {
        const d = new Date();
        if (sd.dataset.setDate === 'segunda') d.setDate(d.getDate() + (((8 - d.getDay()) % 7) || 7));
        else d.setDate(d.getDate() + Number(sd.dataset.setDate));
        const iso = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        const input = $(sd.dataset.target);
        if (input) { input.value = iso; input.dispatchEvent(new Event('change', { bubbles: true })); }
        return;
      }
      if (t.closest('[data-modal-close]')) return closeModal(t.closest('dialog'));
      if (t.matches('dialog[data-sys-modal]')) return closeModal(t);          // clique no fundo escuro
      if (t.closest('[data-toast-close]')) return dismiss(t.closest('[data-toast]'));
      // fecha menus <details> ao clicar fora
      $$('details[data-close-outside][open]').forEach((d) => { if (!d.contains(t)) d.removeAttribute('open'); });
    });

    // Esc: fecha com animação (o <dialog> fecharia seco)
    document.addEventListener('cancel', (e) => {
      if (e.target.matches('dialog[data-sys-modal]')) { e.preventDefault(); closeModal(e.target); }
    }, true);
    // atalhos de teclado (desktop): N missão · G gasto · I item · 1–5 telas
    document.addEventListener('keydown', (e) => {
      if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.altKey || e.repeat) return;
      const el = document.activeElement;
      if (el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName))) return;
      if ($('dialog[open]')) return;
      const alvo = $(`[data-shortcut="${e.key.toLowerCase()}"]`);
      if (alvo) { e.preventDefault(); alvo.click(); }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      $$('details[data-close-outside][open]').forEach((d) => { d.removeAttribute('open'); $('summary', d).focus(); });
    });

    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (form.dataset.confirm && !form.dataset.confirmed) { e.preventDefault(); return confirmThen(form); }
      delete form.dataset.confirmed;
      if (form.matches('[data-mission-toggle]')) { e.preventDefault(); return toggle(form, form.closest('[data-mission]'), 'mission'); }
      if (form.matches('[data-item-toggle]'))    { e.preventDefault(); return toggle(form, form.closest('[data-inventory-item]'), 'item'); }
      if (form.matches('[data-sys-form]')) {
        const btn = e.submitter ?? $('[type="submit"]', form) ?? document.querySelector(`[type="submit"][form="${form.id}"]`);
        if (btn) { btn.dataset.loading = ''; btn.setAttribute('aria-busy', 'true'); if (!$('.sys-spinner', btn)) btn.insertAdjacentHTML('afterbegin', '<span class="sys-spinner" aria-hidden="true"></span>'); }
      }
    });
  }

  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
  return { toast, xp, levelUp, countTo, floatXp, openModal, closeModal: (id) => closeModal(document.getElementById(id)) };
})();

window.Sistema = Sistema;
export default Sistema;
