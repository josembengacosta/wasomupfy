const APP_VERSION = "2.0";

(function ensurePresenceScript() {
  "use strict";

  if (window.__wuPresenceScriptRequested) {
    return;
  }

  const currentScript = document.currentScript;
  if (!currentScript || !currentScript.src) {
    return;
  }

  const script = document.createElement("script");
  script.src = currentScript.src.replace(/\/js\/[^/]+$/, "/js/presence.wp.js");
  script.defer = true;
  script.dataset.wuPresence = "1";

  window.__wuPresenceScriptRequested = true;
  (document.head || document.documentElement).appendChild(script);
})();

document.addEventListener("DOMContentLoaded", () => {
  // Tela de Carregamento
  const loadingScreen = document.getElementById("loadingScreen");
  function hideLoadingScreen() {
    const loader = document.getElementById('loadingScreen');
    if (!loader) return; // sai se não existir
    loader.style.display = 'none';
};
  setTimeout(hideLoadingScreen, 3000);
  window.addEventListener("load", hideLoadingScreen);
const versionDropdown = document.getElementById("versionDropdown");
  if (versionDropdown) {
    versionDropdown.textContent = `Versão ${APP_VERSION} (2026)`;
  }


  // Gerenciar Status de Conexão
  const connectionStatus = document.getElementById("connectionStatus");
  const connectionToast = document.getElementById("connectionToast");
  const toast = new bootstrap.Toast(connectionToast);

  function updateConnectionStatus() {
    const isOnline = navigator.onLine;
    connectionStatus.textContent = isOnline ? "Online" : "Offline";
    connectionStatus.classList.toggle("online", isOnline);
    connectionStatus.classList.toggle("offline", !isOnline);
    connectionStatus.setAttribute(
      "title",
      isOnline ? "Você está online" : "Você está offline"
    );
    bootstrap.Tooltip.getOrCreateInstance(connectionStatus).setContent({
      ".tooltip-inner": isOnline ? "Você está online" : "Você está offline",
    });

    if (!isOnline) {
      connectionToast.querySelector(".toast-body").innerHTML = `
                    Você está offline. Alguns dados podem estar desatualizados.
                    <div class="mt-2">
                        <button class="btn btn-pink btn-sm" onclick="tryReconnect()">Tentar Reconectar</button>
                    </div>
                `;
      toast.show();
    } else {
      connectionToast.querySelector(".toast-body").innerHTML = `
                    Você está online. Todos os dados estão atualizados.
                `;
      toast.show();
    }
  }

  // Tentar Reconectar
  function tryReconnect() {
    if (navigator.onLine) {
      updateConnectionStatus();
    } else {
      fetch("/")
        .then(() => {
          updateConnectionStatus();
        })
        .catch(() => {
          alert("Ainda sem conexão. Tente novamente mais tarde.");
        });
    }
  }

  // Inicializar Status de Conexão
  document.addEventListener("DOMContentLoaded", () => {
    updateConnectionStatus();
    window.addEventListener("online", updateConnectionStatus);
    window.addEventListener("offline", updateConnectionStatus);
  });
});

// Highlight active link in bottom navigation and offcanvas
document.addEventListener("DOMContentLoaded", () => {
  const navLinks = document.querySelectorAll(
    ".bottom-nav .nav-link, .offcanvas-body .nav-link"
  );
  const currentPath = window.location.pathname;

  // Detecta página actual para active nav
  const pathParts = currentPath.split("/").filter(Boolean);
  
  // Regra 1: últimas 2 partes = página pai (ex: /dashboard/artists/add-artist → "add-artist")
  let currentPage = pathParts.slice(-2)[0] || pathParts[pathParts.length - 1] || "painel";
  
  // Regra 2: Subpáginas de artists → ativa artists-list
  if (currentPath.includes('/artists/')) {
    currentPage = 'artists-list';
  }




  navLinks.forEach((link) => {
    // Remover a classe active de todos os links
    link.classList.remove("active");

    // Obter o href do link e normalizá-lo
    let href = link.getAttribute("href");
    if (href) {
      // Resolver caminhos relativos (ex.: '../painel')
      const absoluteHref = new URL(href, window.location.origin).pathname;
      const hrefPage = absoluteHref
        .split("/")
        .filter((segment) => segment)
        .pop();

      // Comparar o último segmento do href com o da página atual
      if (hrefPage === currentPage) {
        link.classList.add("active");
      }
    }
  });
});

// Currency Change
function setMoeda(moeda) {
  const balance = document.getElementById("balance");
  const btnAOA = document.getElementById("btnAOA");
  const btnUSD = document.getElementById("btnUSD");

  if (moeda === "USD") {
    balance.innerText = "$200,000.00";
    btnUSD.classList.add("active-balance");
    btnAOA.classList.remove("active-balance");
  } else {
    balance.innerText = "kz200.000,00";
    btnAOA.classList.add("active-balance");
    btnUSD.classList.remove("active-balance");
  }
}
