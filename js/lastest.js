/**
 * WASOM UPFY v2.0 — lastest.js
 * Ficheiro principal de interactividade do painel admin.
 * Autor: José Mbenga da Costa
 *
 * Todos os selectores são protegidos com null-checks.
 * Seguro em qualquer página — login, invite, lockscreen, dashboard.
 */

(function () {
  'use strict';

  // ─── Referências globais (podem ser null em páginas de auth) ──────────
  const sidebar         = document.getElementById('sidebar');
  const sidebarOverlay  = document.getElementById('sidebarOverlay');
  const mainContent     = document.getElementById('mainContent');
  const sidebarToggle   = document.getElementById('sidebarToggle');
  const sidebarCollapse = document.getElementById('sidebarCollapse');
  const pageLoader      = document.getElementById('pageLoader');

  // ─── Instância única do loader (exposta globalmente) ─────────────────
  let _loaderActive = false;

  window.showLoader = function () {
    if (pageLoader && !_loaderActive) {
      pageLoader.classList.add('active');
      _loaderActive = true;
    }
  };

  window.hideLoader = function () {
    if (pageLoader && _loaderActive) {
      pageLoader.classList.remove('active');
      _loaderActive = false;
    }
  };

  // Mostrar loader imediatamente (antes do DOMContentLoaded)
  window.showLoader();

  // ─── Dark Mode ────────────────────────────────────────────────────────
  /**
   * Aplica o dark mode e actualiza o ícone do botão toggle.
   * Seguro mesmo que o botão não exista na página actual.
   */
  function applyDarkMode(enable) {
    document.body.classList.toggle('dark-mode', enable);
    localStorage.setItem('darkMode', enable ? 'true' : 'false');

    // Actualizar ícone — selector robusto (funciona com data-attr ou onclick)
    const btn = document.querySelector('[data-dark-toggle], [onclick="toggleDarkMode()"]');
    if (btn) {
      btn.innerHTML = enable
        ? '<i class="bi bi-sun"></i>'
        : '<i class="bi bi-moon-stars"></i>';
    }

    // Actualizar tiles do mapa Leaflet se existir
    const mapEl = document.getElementById('clientMap');
    if (mapEl && mapEl._leaflet_map && typeof L !== 'undefined') {
      const map = mapEl._leaflet_map;
      map.eachLayer(function (layer) {
        if (layer instanceof L.TileLayer) {
          layer.setUrl(
            enable
              ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
              : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
          );
        }
      });
    }
  }

  window.toggleDarkMode = function () {
    applyDarkMode(!document.body.classList.contains('dark-mode'));
  };

  // Aplicar preferência guardada (executa imediato para evitar flash)
  applyDarkMode(localStorage.getItem('darkMode') === 'true');

  // ─── Sidebar ──────────────────────────────────────────────────────────
function toggleSidebar() {
  if (!sidebar) return;
 
  if (window.innerWidth <= 768) {
    // Mobile — slide in/out
    sidebar.classList.toggle('active');
    if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
  } else {
    // Desktop — colapsar para ícones
    const isCollapsed = sidebar.classList.toggle('collapsed');
    if (mainContent) mainContent.classList.toggle('collapsed', isCollapsed);
 
    // Actualizar ícone da seta — sempre visível
    if (sidebarCollapse) {
      sidebarCollapse.classList.toggle('bi-chevron-left',  !isCollapsed);
      sidebarCollapse.classList.toggle('bi-chevron-right',  isCollapsed);
    }
 
    // Fechar todos os flyouts ao expandir
    if (!isCollapsed) closeAllFlyouts();
 
    localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
  }
}
 
function closeSidebar() {
  if (!sidebar) return;
  sidebar.classList.remove('active');
  if (sidebarOverlay) sidebarOverlay.classList.remove('active');
}
 
// Restaurar estado collapsed no desktop
if (sidebar && window.innerWidth > 768) {
  const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (wasCollapsed) {
    sidebar.classList.add('collapsed');
    if (mainContent) mainContent.classList.add('collapsed');
    if (sidebarCollapse) {
      sidebarCollapse.classList.remove('bi-chevron-left');
      sidebarCollapse.classList.add('bi-chevron-right');
    }
  }
}
 
if (sidebarToggle)   sidebarToggle.addEventListener('click', toggleSidebar);
if (sidebarCollapse) sidebarCollapse.addEventListener('click', toggleSidebar);
if (sidebarOverlay)  sidebarOverlay.addEventListener('click', closeSidebar);
 
// Fechar sidebar mobile ao clicar em link normal
document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
  link.addEventListener('click', function () {
    if (window.innerWidth <= 768 && !link.hasAttribute('data-bs-toggle')) {
      closeSidebar();
    }
  });
});
 
window.addEventListener('resize', function () {
  if (window.innerWidth > 768) { closeSidebar(); closeAllFlyouts(); }
});
 
 
// ─── Flyout lateral (dropdown em modo collapsed) ───────────────────────
 
var _activeFlyout = null;
var _activeNavItem = null;
 
function closeAllFlyouts() {
  document.querySelectorAll('.sidebar-flyout').forEach(function (f) {
    f.classList.remove('active');
  });
  _activeFlyout = null;
  _activeNavItem = null;
}
 
function buildFlyout(link) {
  // Obter o collapse target
  const targetId = link.getAttribute('href') || link.dataset.bsTarget;
  if (!targetId || targetId === '#') return null;
 
  const collapseEl = document.querySelector(targetId);
  if (!collapseEl) return null;
 
  // Obter sub-links dentro do collapse
  const subLinks = collapseEl.querySelectorAll('a.nav-link');
  if (!subLinks.length) return null;
 
  // Criar flyout
  const flyout = document.createElement('div');
  flyout.className = 'sidebar-flyout';
 
  // Título (texto do link pai)
  const title = document.createElement('div');
  title.className = 'flyout-title';
  const spanEl = link.querySelector('span');
  title.textContent = spanEl ? spanEl.textContent.trim() : 'Menu';
  flyout.appendChild(title);
 
  // Sub-itens
  subLinks.forEach(function (sub) {
    const a = document.createElement('a');
    a.href = sub.href;
    const icon = sub.querySelector('i');
    const text = sub.querySelector('span');
    if (icon) a.appendChild(icon.cloneNode(true));
    if (text) {
      const t = document.createTextNode(text.textContent.trim());
      a.appendChild(t);
    }
    // Fechar flyout ao navegar
    a.addEventListener('click', closeAllFlyouts);
    flyout.appendChild(a);
  });
 
  return flyout;
}
 
function positionFlyout(flyout, navItem) {
  const rect = navItem.getBoundingClientRect();
  flyout.style.top  = rect.top + 'px';
  // left já definido no CSS (var(--sidebar-collapsed))
}
 
// Interceptar cliques nos links com dropdown quando sidebar collapsed
document.querySelectorAll('.sidebar .nav-link[data-bs-toggle="collapse"]').forEach(function (link) {
  link.addEventListener('click', function (e) {
    if (!sidebar || !sidebar.classList.contains('collapsed')) return;
    // Em modo collapsed: impedir o collapse normal, mostrar flyout
    e.preventDefault();
    e.stopPropagation();
 
    const navItem = link.closest('.nav-item');
 
    // Se já está aberto para este item, fechar
    if (_activeNavItem === navItem && _activeFlyout) {
      closeAllFlyouts();
      return;
    }
 
    // Fechar flyout anterior
    closeAllFlyouts();
 
    // Construir flyout
    const flyout = buildFlyout(link);
    if (!flyout) return;
 
    document.body.appendChild(flyout);
    positionFlyout(flyout, navItem);
 
    // Pequeno delay para o browser calcular posição
    requestAnimationFrame(function () {
      flyout.classList.add('active');
    });
 
    _activeFlyout  = flyout;
    _activeNavItem = navItem;
  });
});
 
// Fechar flyout ao clicar fora
document.addEventListener('click', function (e) {
  if (!_activeFlyout) return;
  if (!_activeFlyout.contains(e.target) && !sidebar.contains(e.target)) {
    closeAllFlyouts();
  }
});
 
// Reposicionar flyout ao fazer scroll (para manter alinhamento)
window.addEventListener('scroll', function () {
  if (_activeFlyout && _activeNavItem) {
    positionFlyout(_activeFlyout, _activeNavItem);
  }
}, { passive: true });
 
// Mesmo no sidebar scroll
if (sidebar) {
  sidebar.addEventListener('scroll', function () {
    if (_activeFlyout && _activeNavItem) {
      positionFlyout(_activeFlyout, _activeNavItem);
    }
  }, { passive: true });
}

  // ─── Bottom Navigation ────────────────────────────────────────────────
  /**
   * Marca o link activo na bottom nav com base no URL actual.
   * Compara o path completo para evitar falsos positivos.
   */
  function setActiveBottomLink() {
    const currentPath = window.location.pathname;
    document.querySelectorAll('.bottom-nav a').forEach(function (link) {
      const href = link.getAttribute('href') || '';
      const isActive = href !== '#' && currentPath.includes(href.split('?')[0]);
      link.classList.toggle('active', isActive);
    });
  }

  function setupMobileMenu() {
    const bottomNav = document.querySelector('.bottom-nav');
    const content   = document.querySelector('.content');
    if (!bottomNav) return;

    function applyBottomNav() {
      const isMobileSize = window.innerWidth <= 992;
      bottomNav.style.display = isMobileSize ? 'block' : 'none';
      if (content) content.style.paddingBottom = isMobileSize ? '70px' : '';
    }

    applyBottomNav();
    window.addEventListener('resize', applyBottomNav);
  }

  // ─── Animação de entrada dos cards ───────────────────────────────────
  function animateCards() {
    document.querySelectorAll('.card.fade-in-custom').forEach(function (card, i) {
      card.style.animationDelay = (i * 0.07) + 's';
    });
  }

  // ─── Mapa Leaflet (inicialização) ─────────────────────────────────────
  /**
   * initClientMap() deve ser definida na página que tem o mapa.
   * Aqui apenas chamamos se existir, sem lançar erro caso não exista.
   */
  function tryInitMap() {
    if (typeof window.initClientMap === 'function') {
      window.initClientMap();
    }
  }

  // ─── Gestor de Conexão ────────────────────────────────────────────────
  class ConnectionManager {
    constructor() {
      this.statusEl  = document.getElementById('connectionStatus');
      this.notifEl   = document.getElementById('statusNotification');
      // Só inicializar se os elementos existirem
      if (!this.statusEl || !this.notifEl) return;
      this.lastStatus = navigator.onLine;
      this._init();
    }

    _init() {
      window.addEventListener('online',  () => this._change(true));
      window.addEventListener('offline', () => this._change(false));
      // Verificação periódica (30s) — útil em PWA
      setInterval(() => {
        if (this.lastStatus !== navigator.onLine) this._change(navigator.onLine);
      }, 30000);
      // Estado inicial silencioso (não mostrar "conectado" ao carregar)
      if (!navigator.onLine) this._change(false);
    }

    _change(isOnline) {
      this.lastStatus = isOnline;

      if (isOnline) {
        this.statusEl.classList.remove('offline');
        this.statusEl.classList.add('pulse');
        this.statusEl.style.opacity = '1';
        this._notify('Conexão restabelecida');
        if (typeof window.updateAppData === 'function') window.updateAppData();
        setTimeout(() => {
          this.statusEl.classList.remove('pulse');
          if (navigator.onLine) this.statusEl.style.opacity = '0';
        }, 3000);
      } else {
        this.statusEl.classList.add('offline', 'pulse');
        this.statusEl.style.opacity = '1';
        this._notify('Estás sem ligação à internet', true);
      }
    }

    _notify(msg, persistent = false) {
      if (!this.notifEl) return;
      this.notifEl.textContent = msg;
      this.notifEl.classList.add('show');
      if (!persistent) {
        setTimeout(() => this.notifEl.classList.remove('show'), 3000);
      }
    }
  }

  // ─── PJAX (se usado) ─────────────────────────────────────────────────
  document.addEventListener('pjax:send',     window.showLoader);
  document.addEventListener('pjax:complete', function () {
    setTimeout(window.hideLoader, 400);
  });

  // ─── DOMContentLoaded — ponto único de inicialização ─────────────────
  document.addEventListener('DOMContentLoaded', function () {
    // Esconder loader quando DOM estiver pronto
    window.hideLoader();

    // Inicializar componentes
    setActiveBottomLink();
    setupMobileMenu();
    animateCards();
    tryInitMap();
    new ConnectionManager();

    // Tooltips Bootstrap (se Bootstrap estiver carregado)
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
      });
    }
  });

  // Fallback: esconder loader quando a página carrega completamente
  window.addEventListener('load', function () {
    setTimeout(window.hideLoader, 200);
  });

})();