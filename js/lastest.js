const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const mainContent = document.getElementById("mainContent");
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebarCollapse = document.getElementById("sidebarCollapse");

// Toggle Dark Mode
function toggleDarkMode() {
  document.body.classList.toggle("dark-mode");
  localStorage.setItem(
    "darkMode",
    document.body.classList.contains("dark-mode")
  );
  document.querySelector('[onclick="toggleDarkMode()"]').innerHTML =
    document.body.classList.contains("dark-mode")
      ? '<i class="bi bi-sun"></i>'
      : '<i class="bi bi-moon"></i>';

  // Update map tiles
  const map = document.getElementById("clientMap")._leaflet_map;
  if (map) {
    map.eachLayer((layer) => {
      if (layer instanceof L.TileLayer) {
        layer.setUrl(
          document.body.classList.contains("dark-mode")
            ? "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
            : "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        );
      }
    });
  }
}

// Check dark mode preference
if (localStorage.getItem("darkMode") === "true") {
  document.body.classList.add("dark-mode");
  document.querySelector('[onclick="toggleDarkMode()"]').innerHTML =
    '<i class="bi bi-sun"></i>';
}

// Sidebar Toggle (Mobile and Desktop)
sidebarToggle.addEventListener("click", () => {
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle("active");
    sidebarOverlay.classList.toggle("active");
  } else {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("collapsed");
    sidebarCollapse.classList.toggle("bi-chevron-left");
    sidebarCollapse.classList.toggle("bi-chevron-right");
  }
});

// Sidebar Collapse/Expand (Desktop)
sidebarCollapse.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
  mainContent.classList.toggle("collapsed");
  sidebarCollapse.classList.toggle("bi-chevron-left");
  sidebarCollapse.classList.toggle("bi-chevron-right");
});

// Close sidebar on overlay click
sidebarOverlay.addEventListener("click", () => {
  sidebar.classList.remove("active");
  sidebarOverlay.classList.remove("active");
});

// Handle nav link clicks to prevent closing sidebar on submenu toggle
document.querySelectorAll(".sidebar .nav-link").forEach((link) => {
  link.addEventListener("click", (e) => {
    if (window.innerWidth <= 768 && !link.hasAttribute("data-bs-toggle")) {
      sidebar.classList.remove("active");
      sidebarOverlay.classList.remove("active");
    }
  });
});

// Prevent sidebar from closing when submenu is toggled
document
  .querySelectorAll('.sidebar .nav-link[data-bs-toggle="collapse"]')
  .forEach((link) => {
    link.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  });

// Handle window resize
window.addEventListener("resize", () => {
  if (window.innerWidth > 768) {
    sidebar.classList.remove("active");
    sidebarOverlay.classList.remove("active");
  }
});

// Quick action
function showQuickAction() {
  alert("Ações rápidas: Adicionar música, artista ou relatório.");
}

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  initClientMap();
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
  });
});

document.addEventListener('pjax:start', () => {
    document.querySelector('.page-loader').classList.add('active');
    window.scrollTo(0, 0);
});

document.addEventListener('pjax:end', () => {
    setTimeout(() => {
        document.querySelector('.page-loader').classList.remove('active');
    }, 500); // Delay para efeito suave
});    
// Gerenciador de conexão profissional
class ConnectionManager {
    constructor() {
        this.statusElement = document.getElementById('connectionStatus');
        this.notificationElement = document.getElementById('statusNotification');
        this.lastOnlineStatus = navigator.onLine;
        this.init();
    }

    init() {
        // Event listeners para mudanças de conexão
        window.addEventListener('online', () => this.handleConnectionChange(true));
        window.addEventListener('offline', () => this.handleConnectionChange(false));
        
        // Verificação inicial
        this.handleConnectionChange(navigator.onLine);
        
        // Verificação periódica para PWA (a cada 30 segundos)
        setInterval(() => {
            if (this.lastOnlineStatus !== navigator.onLine) {
                this.handleConnectionChange(navigator.onLine);
            }
        }, 30000);
    }

    handleConnectionChange(isOnline) {
        this.lastOnlineStatus = isOnline;
        
        if (isOnline) {
            // Conexão restabelecida
            this.statusElement.classList.remove('offline');
            this.statusElement.classList.add('pulse');
            this.showNotification('Conexão restabelecida');
            
            // Atualiza dados se necessário
            if (typeof window.updateAppData === 'function') {
                window.updateAppData();
            }
            
            // Remove o pulso após 3 segundos
            setTimeout(() => {
                this.statusElement.classList.remove('pulse');
            }, 3000);
        } else {
            // Conexão perdida
            this.statusElement.classList.add('offline', 'pulse');
            this.showNotification('Você está offline', true);
        }
        
        // Mostra o indicador
        this.statusElement.style.opacity = '1';
        
        // Esconde após 5 segundos (exceto se offline)
        if (isOnline) {
            setTimeout(() => {
                if (navigator.onLine) { // Verifica novamente para evitar race condition
                    this.statusElement.style.opacity = '0';
                }
            }, 5000);
        }
    }

    showNotification(message, persistent = false) {
        this.notificationElement.textContent = message;
        this.notificationElement.classList.add('show');
        
        if (!persistent) {
            setTimeout(() => {
                this.notificationElement.classList.remove('show');
            }, 3000);
        }
    }
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Gerenciador de conexão
    new ConnectionManager();

    setActiveLink();
    setupMobileMenu();
});

// Função para marcar o link ativo 
function setActiveLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'home.html';
    const navLinks = document.querySelectorAll('.bottom-nav a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        if (currentPage === linkPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Configurações do menu mobile
function setupMobileMenu() {
    if (!isMobile() && window.innerWidth > 992) {
        document.querySelector('.bottom-nav').style.display = 'none';
        document.querySelector('.content').style.paddingBottom = '0';
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            document.querySelector('.bottom-nav').style.display = 'none';
            document.querySelector('.content').style.paddingBottom = '0';
        } else {
            document.querySelector('.bottom-nav').style.display = 'block';
            document.querySelector('.content').style.paddingBottom = '70px';
        }
    });
}

// Função auxiliar para detectar mobile (mantida)
function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
};

class PageLoader {
    constructor() {
        this.loader = document.getElementById('pageLoader');
        this.isActive = false;
        this.initLoader();
    }
    
    initLoader() {
        // Simula o carregamento (remova em produção)
        this.show();
        setTimeout(() => this.hide(), 3000);
        
        // Event listeners reais para o seu aplicativo
        document.addEventListener('DOMContentLoaded', () => this.hide());
        window.addEventListener('load', () => this.hide());
        
        // Para PJAX ou carregamentos AJAX:
        document.addEventListener('pjax:send', () => this.show());
        document.addEventListener('pjax:complete', () => this.hide());
    }
    
    show() {
        if (!this.isActive) {
            this.loader.classList.add('active');
            this.isActive = true;
        }
    }
    
    hide() {
        if (this.isActive) {
            this.loader.classList.remove('active');
            this.isActive = false;
        }
    }
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new PageLoader();
    
    // Para controle manual em operações AJAX:
    window.showLoader = () => new PageLoader().show();
    window.hideLoader = () => new PageLoader().hide();
});
