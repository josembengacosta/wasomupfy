// Cookie Manager - Gerencia cookies de forma segura
class CookieManager {
    constructor(options = {}) {
        this.options = {
            cookieName: 'wasomupfy_cookie_consent',
            expirationDays: 365,
            secure: window.location.protocol === 'https:',
            sameSite: 'Strict',
            ...options
        };
    }

    // Define um cookie de forma segura
    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        
        let cookieString = `${encodeURIComponent(name)}=${encodeURIComponent(value)};`;
        cookieString += `expires=${date.toUTCString()};`;
        cookieString += `path=/;`;
        cookieString += `SameSite=${this.options.sameSite};`;
        
        if (this.options.secure) {
            cookieString += 'Secure;';
        }
        
        document.cookie = cookieString;
        
        // Dispara evento personalizado
        const event = new CustomEvent('cookieChanged', { 
            detail: { name, value, action: 'set' }
        });
        document.dispatchEvent(event);
    }

    // Obtém o valor de um cookie
    getCookie(name) {
        const nameEQ = encodeURIComponent(name) + "=";
        const cookies = document.cookie.split(';');
        
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i];
            while (cookie.charAt(0) === ' ') {
                cookie = cookie.substring(1);
            }
            if (cookie.indexOf(nameEQ) === 0) {
                return decodeURIComponent(cookie.substring(nameEQ.length));
            }
        }
        return null;
    }

    // Remove um cookie
    deleteCookie(name) {
        this.setCookie(name, '', -1);
    }

    // Verifica consentimento de cookies
    hasConsent() {
        const consent = this.getCookie(this.options.cookieName);
        return consent === 'true';
    }

    // Obtém preferências detalhadas (para futura implementação de cookies por categoria)
    getPreferences() {
        const preferences = this.getCookie(`${this.options.cookieName}_prefs`);
        return preferences ? JSON.parse(preferences) : null;
    }

    // Salva preferências detalhadas
    setPreferences(preferences) {
        this.setCookie(
            `${this.options.cookieName}_prefs`,
            JSON.stringify(preferences),
            this.options.expirationDays
        );
    }
}

// Cookie Banner Manager
class CookieBanner {
    constructor() {
        this.cookieManager = new CookieManager();
        this.banner = document.getElementById('cookie-alert');
        this.initialized = false;
        
        // Estados do banner
        this.states = {
            HIDDEN: 'hidden',
            VISIBLE: 'visible',
            ACCEPTED: 'accepted',
            REJECTED: 'rejected'
        };
        
        this.currentState = this.states.HIDDEN;
    }

    // Inicializa o banner
    init() {
        if (this.initialized) return;
        
        this.bindEvents();
        this.checkConsent();
        this.initialized = true;
    }

    // Vincula eventos
    bindEvents() {
        // Aceitar cookies
        document.getElementById('accept-cookies').addEventListener('click', () => {
            this.acceptAll();
        });

        // Rejeitar cookies
        document.getElementById('reject-cookies').addEventListener('click', () => {
            this.rejectAll();
        });

        // Fechar banner
        const closeBtn = document.getElementById('cookie-alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hide();
            });
        }

        // Prevenir fechamento acidental com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.currentState === this.states.VISIBLE) {
                e.preventDefault();
            }
        });

        // Evento personalizado para quando o consentimento mudar
        document.addEventListener('cookieChanged', (e) => {
            if (e.detail.name === 'wasomupfy_cookie_consent') {
                this.logConsentChange(e.detail.value);
            }
        });
    }

    // Verifica se já existe consentimento
    checkConsent() {
        const hasConsent = this.cookieManager.hasConsent();
        
        if (hasConsent === null) {
            // Primeira visita - mostrar banner
            setTimeout(() => {
                this.show();
            }, 1000); // Delay para melhor UX
        } else if (hasConsent) {
            this.currentState = this.states.ACCEPTED;
            this.initializeAcceptedCookies();
        } else {
            this.currentState = this.states.REJECTED;
            this.cleanupNonEssentialCookies();
        }
    }

    // Mostra o banner
    show() {
        if (this.currentState === this.states.VISIBLE) return;
        
        this.banner.classList.add('visible');
        this.banner.classList.remove('hidden');
        this.banner.setAttribute('aria-hidden', 'false');
        this.currentState = this.states.VISIBLE;
        
        // Focar no primeiro botão para acessibilidade
        setTimeout(() => {
            document.getElementById('accept-cookies').focus();
        }, 100);
        
        // Análise de exibição (sem cookies)
        this.trackBannerDisplay();
    }

    // Esconde o banner
    hide() {
        this.banner.classList.remove('visible');
        this.banner.classList.add('hidden');
        this.banner.setAttribute('aria-hidden', 'true');
        this.currentState = this.states.HIDDEN;
    }

    // Aceita todos os cookies
    acceptAll() {
        this.cookieManager.setCookie(
            this.cookieManager.options.cookieName,
            'true',
            this.cookieManager.options.expirationDays
        );
        
        // Salvar preferências completas
        this.cookieManager.setPreferences({
            necessary: true,
            analytics: true,
            marketing: true,
            preferences: true,
            timestamp: new Date().toISOString(),
            version: '1.0'
        });
        
        this.hide();
        this.initializeAcceptedCookies();
        
        // Feedback visual
        this.showFeedback('Cookies aceitos com sucesso!');
    }

    // Rejeita todos os cookies
    rejectAll() {
        this.cookieManager.setCookie(
            this.cookieManager.options.cookieName,
            'false',
            this.cookieManager.options.expirationDays
        );
        
        // Salvar preferências de rejeição
        this.cookieManager.setPreferences({
            necessary: true, // Cookies necessários sempre ativos
            analytics: false,
            marketing: false,
            preferences: false,
            timestamp: new Date().toISOString(),
            version: '1.0'
        });
        
        this.hide();
        this.cleanupNonEssentialCookies();
        
        // Feedback visual
        this.showFeedback('Preferências salvas. Cookies não essenciais desativados.');
    }

    // Inicializa cookies aceitos
    initializeAcceptedCookies() {
        // Aqui você pode inicializar serviços de terceiros
        // como Google Analytics, Facebook Pixel, etc.
        console.log('Cookies aceitos - inicializando serviços...');
        
        // Exemplo: Inicializar Google Analytics se consentido
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted',
                'ad_storage': 'granted'
            });
        }
    }

    // Remove cookies não essenciais
    cleanupNonEssentialCookies() {
        // Remover cookies de analytics, marketing, etc.
        const cookies = document.cookie.split(';');
        
        cookies.forEach(cookie => {
            const [name] = cookie.trim().split('=');
            // Mantém apenas o cookie de consentimento e cookies essenciais
            if (!name.includes('wasomupfy') && 
                !name.includes('PHPSESSID') && 
                !name.includes('XSRF-TOKEN')) {
                this.cookieManager.deleteCookie(name);
            }
        });
        
        // Desativar serviços de terceiros
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'denied',
                'ad_storage': 'denied'
            });
        }
    }

    // Mostra feedback temporário
    showFeedback(message) {
        const feedback = document.createElement('div');
        feedback.className = 'cookie-feedback';
        feedback.textContent = message;
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('aria-live', 'polite');
        
        document.body.appendChild(feedback);
        
        setTimeout(() => {
            feedback.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            feedback.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(feedback);
            }, 300);
        }, 3000);
    }

    // Registra exibição do banner (sem cookies)
    trackBannerDisplay() {
        // Usar localStorage apenas para métricas internas
        try {
            const displays = localStorage.getItem('cookie_banner_displays') || 0;
            localStorage.setItem('cookie_banner_displays', parseInt(displays) + 1);
            localStorage.setItem('last_cookie_banner_display', new Date().toISOString());
        } catch (e) {
            // Silenciosamente falha se localStorage não estiver disponível
        }
    }

    // Log de mudanças de consentimento
    logConsentChange(value) {
        console.log(`Consentimento de cookies alterado para: ${value}`);
        
        // Aqui você pode enviar para seu backend (com consentimento apropriado)
        if (value === 'true') {
            console.log('Serviços de terceiros podem ser inicializados');
        } else {
            console.log('Serviços de terceiros devem ser desativados');
        }
    }
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar Feather Icons se disponível
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    // Inicializar banner de cookies
    const cookieBanner = new CookieBanner();
    cookieBanner.init();
    
    // Expor para uso global se necessário
    window.WasomCookieBanner = cookieBanner;
    
    // Para desenvolvedores: Verificar consentimento
    console.log('Cookie consent:', cookieBanner.cookieManager.hasConsent());
});

// Fallback para caso o DOMContentLoaded já tenha acontecido
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCookieBanner);
} else {
    setTimeout(initCookieBanner, 100);
}

function initCookieBanner() {
    if (!window.WasomCookieBanner) {
        const cookieBanner = new CookieBanner();
        cookieBanner.init();
        window.WasomCookieBanner = cookieBanner;
    }
}

// API pública para outros scripts verificarem consentimento
window.checkCookieConsent = function(category = 'all') {
    const banner = window.WasomCookieBanner;
    if (!banner) return false;
    
    const hasConsent = banner.cookieManager.hasConsent();
    
    if (category === 'all') {
        return hasConsent;
    }
    
    const prefs = banner.cookieManager.getPreferences();
    return prefs ? prefs[category] || false : false;
};