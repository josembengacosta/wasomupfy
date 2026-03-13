// help.js — Wasom Upfy Central de Ajuda
// Caminho: js/help.js

(function () {
  'use strict';

  // ── Mapa de termos → secção + item de accordion a abrir ──────────────────────
  var TERM_MAP = [
    // Financeiro
    { terms: ['royalt', 'ganhos', 'receber', 'pagamento', 'saque', 'levantamento', 'percentagem', '90%', 'quanto ganho'],
      section: '#financeiro', target: '#fin1' },
    { terms: ['calendário', 'calendario', 'quando recebo', 'relatório', 'relatorio', 'mês', 'mes'],
      section: '#financeiro', target: '#fin2' },
    { terms: ['saque mínimo', 'saque minimo', 'valor mínimo', 'mínimo para sacar'],
      section: '#financeiro', target: '#fin3' },
    // Distribuição
    { terms: ['upload', 'áudio', 'audio', 'wav', 'formato', 'mp3', 'aac', '16-bit', '24-bit', '44.1'],
      section: '#distribuicao', target: '#distro1' },
    { terms: ['capa', 'arte', 'artwork', '3000', 'jpg', 'png', 'imagem da capa'],
      section: '#distribuicao', target: '#distro2' },
    { terms: ['prazo', 'demora', 'dias úteis', 'tempo', 'quando fica online'],
      section: '#distribuicao', target: '#distro3' },
    { terms: ['loja', 'lojas', 'spotify', 'apple', 'deezer', 'tiktok', 'amazon', 'boomplay', 'tidal', 'audiomack'],
      section: '#distribuicao', target: '#distro4' },
    { terms: ['isrc', 'metadado', 'compositor', 'produtor', 'feat', 'co-autor', 'letra', 'explicit'],
      section: '#distribuicao', target: '#distro5' },
    { terms: ['agendar', 'data de lançamento', 'agendamento', 'release date'],
      section: '#distribuicao', target: '#distro6' },
    // Conta
    { terms: ['spotify for artists', 's4a', 'verificar perfil', 'perfil spotify', 'reivindicar'],
      section: '#conta', target: '#acc1' },
    { terms: ['senha', 'password', 'esqueci', 'redefinir', 'forgot'],
      section: '#conta', target: '#acc2' },
    { terms: ['2fa', 'dois factores', 'dois fatores', 'autenticação', 'autenticacao', 'segurança', 'otp', 'código'],
      section: '#conta', target: '#acc3' },
    { terms: ['verificar email', 'verificar e-mail', 'verificação', 'verificacao', 'confirmar email'],
      section: '#conta', target: '#acc4' },
    { terms: ['desactivar', 'desativar', 'suspender conta', 'pausar'],
      section: '#conta', target: '#acc5' },
    { terms: ['colaborador', 'equipa', 'manager', 'editor', 'visualizador', 'convidar'],
      section: '#conta', target: '#acc6' },
    // Marketing
    { terms: ['playlist', 'playlists', 'editorial', 'pitching', 'pitch', 'curador'],
      section: '#promocao', target: '#mkt1' },
    { terms: ['anúncio', 'anuncio', 'ads', 'facebook ads', 'instagram ads', 'publicidade', 'campanha'],
      section: '#promocao', target: '#mkt2' },
    { terms: ['tiktok', 'viral', 'reels', 'shorts', 'vídeo curto'],
      section: '#promocao', target: '#mkt3' },
    // FAQ geral
    { terms: ['cover', 'covers', 'música cover', 'licença mecânica', 'covers de musica'],
      section: '#faq-geral' },
    { terms: ['editar', 'corrigir', 'alterar lançamento', 'erro no lançamento'],
      section: '#faq-geral' },
    { terms: ['remover', 'apagar música', 'tirar das lojas', 'retirar'],
      section: '#faq-geral' },
  ];

  // ── Sugestões dropdown ────────────────────────────────────────────────────────
  var SUGGESTIONS = [
    { icon: 'fa-regular fa-file-audio',    text: 'Formato de áudio aceito' },
    { icon: 'fa-regular fa-circle-dollar', text: 'Como receber os meus royalties' },
    { icon: 'fa-regular fa-image',         text: 'Requisitos da arte da capa' },
    { icon: 'fa-regular fa-clock',         text: 'Prazo de distribuição' },
    { icon: 'fa-solid fa-shield-halved',   text: 'Como activar o 2FA' },
    { icon: 'fa-regular fa-store',         text: 'Para quais lojas distribuem' },
    { icon: 'fa-solid fa-music',           text: 'Como fazer pitching em playlists' },
    { icon: 'fa-regular fa-calendar',      text: 'Calendário de pagamentos' },
  ];

  // ── Utils ─────────────────────────────────────────────────────────────────────

  function debounce(fn, delay) {
    var t;
    return function () {
      var args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, delay);
    };
  }

  function smoothScrollTo(el, offset) {
    if (!el) return;
    offset = offset || 110;
    var top = el.getBoundingClientRect().top + window.pageYOffset - offset;
    window.scrollTo({ top: top, behavior: 'smooth' });
  }

  function openAccordionItem(targetId) {
    if (!targetId) return;
    var el = document.querySelector(targetId);
    if (!el) return;
    var parent = el.closest('.accordion');
    if (parent) {
      parent.querySelectorAll('.accordion-collapse.show').forEach(function (open) {
        if (open !== el) {
          var inst = bootstrap.Collapse.getInstance(open);
          if (inst) inst.hide();
        }
      });
    }
    var bsCol = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
    bsCol.show();
  }

  function findMapping(term) {
    term = term.toLowerCase().trim();
    for (var i = 0; i < TERM_MAP.length; i++) {
      var entry = TERM_MAP[i];
      for (var j = 0; j < entry.terms.length; j++) {
        if (term.includes(entry.terms[j]) || entry.terms[j].includes(term)) {
          return entry;
        }
      }
    }
    return null;
  }

  // ── Pesquisa ──────────────────────────────────────────────────────────────────

  function performSearch(term) {
    term = (term || '').trim().toLowerCase();
    var infoBar  = document.getElementById('searchResultsInfo');
    var countEl  = document.getElementById('resultsCount');
    var termEl   = document.getElementById('searchTerm');
    var items    = document.querySelectorAll('.search-item');
    var sections = document.querySelectorAll('.category-section');

    if (!term) { clearSearch(false); return; }

    var found = 0;
    items.forEach(function (item) {
      var btnText  = ((item.querySelector('.accordion-button') || {}).textContent || '').toLowerCase();
      var bodyText = ((item.querySelector('.accordion-body')   || {}).textContent || '').toLowerCase();
      var match = btnText.includes(term) || bodyText.includes(term);
      item.style.display = match ? '' : 'none';
      if (match) found++;
    });

    sections.forEach(function (sec) {
      var hasVisible = Array.from(sec.querySelectorAll('.search-item')).some(function (i) {
        return i.style.display !== 'none';
      });
      sec.style.display = hasVisible ? '' : 'none';
    });

    if (infoBar) {
      if (countEl) countEl.textContent = found;
      if (termEl)  termEl.textContent  = term;
      infoBar.classList.toggle('d-none', found === 0 && !term);
      infoBar.classList.remove('d-none');
    }

    // Scroll + open
    var mapped = findMapping(term);
    if (mapped) {
      setTimeout(function () {
        var secEl = document.querySelector(mapped.section);
        if (secEl) smoothScrollTo(secEl, 90);
        if (mapped.target) setTimeout(function () { openAccordionItem(mapped.target); }, 350);
      }, 100);
    } else {
      // Scrolla para a primeira secção visível
      for (var i = 0; i < sections.length; i++) {
        if (sections[i].style.display !== 'none') {
          smoothScrollTo(sections[i], 90);
          break;
        }
      }
    }
  }

  function clearSearch(focusInput) {
    document.querySelectorAll('.search-item').forEach(function (i) { i.style.display = ''; });
    document.querySelectorAll('.category-section').forEach(function (s) { s.style.display = ''; });
    var infoBar = document.getElementById('searchResultsInfo');
    if (infoBar) infoBar.classList.add('d-none');
    var input = document.getElementById('searchInput');
    if (input && focusInput !== false) { input.value = ''; input.focus(); }
    hideSuggestions();
  }

  // ── Quick-search (pills & sugestões) ─────────────────────────────────────────

  function triggerQuickSearch(text) {
    var input = document.getElementById('searchInput');
    if (input) input.value = text;
    hideSuggestions();
    performSearch(text);
    // Scrolla para a secção de artigos
    var art = document.getElementById('help-articles');
    if (art) setTimeout(function () { smoothScrollTo(art, 80); }, 50);
  }

  // ── Sugestões ─────────────────────────────────────────────────────────────────

  function buildSuggestions() {
    var box = document.getElementById('searchSuggestions');
    if (!box) return;
    box.innerHTML = '';
    SUGGESTIONS.forEach(function (s) {
      var div = document.createElement('div');
      div.className = 'suggestion-item p-2 d-flex align-items-center gap-2';
      div.innerHTML = '<i class="' + s.icon + ' text-wasomupfy"></i><span class="small">' + s.text + '</span>';
      div.addEventListener('mousedown', function (e) {
        e.preventDefault();
        triggerQuickSearch(s.text);
      });
      box.appendChild(div);
    });
  }

  function showSuggestions() {
    var box = document.getElementById('searchSuggestions');
    if (box) box.classList.remove('d-none');
  }

  function hideSuggestions() {
    var box = document.getElementById('searchSuggestions');
    if (box) box.classList.add('d-none');
  }

  // ── Pills populares ───────────────────────────────────────────────────────────

  function initPopularPills() {
    document.querySelectorAll('.popular-pill').forEach(function (pill) {
      pill.addEventListener('click', function (e) {
        e.preventDefault();
        triggerQuickSearch(this.dataset.search || this.textContent.trim());
      });
    });
  }

  // ── Category cards ────────────────────────────────────────────────────────────

  function initCategoryCards() {
    document.querySelectorAll('.category-card').forEach(function (card) {
      card.addEventListener('click', function (e) {
        var href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
          e.preventDefault();
          var target = document.querySelector(href);
          if (target) smoothScrollTo(target, 80);
        }
      });
    });
  }

  // ── Sidebar nav active highlight on scroll ────────────────────────────────────

  function initScrollSpy() {
    var sections = ['#distribuicao', '#financeiro', '#conta', '#promocao', '#faq-geral'];
    var navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    if (!navLinks.length) return;

    function updateActive() {
      var scrollY = window.pageYOffset + 150;
      var current = sections[0];
      sections.forEach(function (id) {
        var el = document.querySelector(id);
        if (el && el.offsetTop <= scrollY) current = id;
      });
      navLinks.forEach(function (link) {
        link.classList.toggle('active', link.getAttribute('href') === current);
      });
    }

    window.addEventListener('scroll', updateActive, { passive: true });
    updateActive();
  }

  // ── Init ──────────────────────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', function () {
    buildSuggestions();
    initPopularPills();
    initCategoryCards();
    initScrollSpy();

    var input = document.getElementById('searchInput');
    if (input) {
      input.addEventListener('focus', function () {
        if (!this.value.trim()) showSuggestions();
      });
      input.addEventListener('blur', function () {
        setTimeout(hideSuggestions, 200);
      });
      input.addEventListener('input', debounce(function () {
        var v = this.value.trim();
        if (v.length >= 2) { hideSuggestions(); performSearch(v); }
        else if (v.length === 0) { clearSearch(false); showSuggestions(); }
      }, 300));
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); hideSuggestions(); performSearch(this.value); }
        if (e.key === 'Escape') { clearSearch(false); this.blur(); }
      });
    }

    var clearBtn = document.getElementById('clearSearch');
    if (clearBtn) clearBtn.addEventListener('click', function () { clearSearch(); });

    document.addEventListener('click', function (e) {
      var c = document.querySelector('.search-container');
      if (c && !c.contains(e.target)) hideSuggestions();
    });
  });

})();