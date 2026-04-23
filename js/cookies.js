/**
 * Wasom Upfy — Cookie Consent Banner
 * Apenas cookies essenciais/funcionais. Sem rastreamento publicitário.
 */
(function() {
    'use strict';

    const COOKIE_NAME = 'wasomupfy_cookie_ack';
    const COOKIE_DAYS = 180; // 6 meses

    const banner = document.getElementById('cookie-consent');
    const acceptBtn = document.getElementById('cookie-accept');

    if (!banner || !acceptBtn) return;

    // Verificar se já aceitou anteriormente
    function hasAcknowledged() {
        const match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
        return match ? match[2] === '1' : false;
    }

    // Mostrar o banner se ainda não foi aceite
    function showBannerIfNeeded() {
        if (!hasAcknowledged()) {
            // Pequeno delay para melhor UX
            setTimeout(() => {
                banner.classList.add('visible');
                banner.setAttribute('aria-hidden', 'false');
            }, 800);
        } else {
            banner.style.display = 'none';
        }
    }

    // Guardar aceitação no cookie
    function setAcknowledged() {
        const date = new Date();
        date.setTime(date.getTime() + (COOKIE_DAYS * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + date.toUTCString();
        
        // Cookie seguro: HttpOnly não é possível via JS, mas usamos Secure e SameSite
        const secure = location.protocol === 'https:' ? 'Secure;' : '';
        document.cookie = `${COOKIE_NAME}=1; ${expires}; path=/; SameSite=Lax; ${secure}`;
    }

    // Ocultar banner com animação
    function hideBanner() {
        banner.classList.remove('visible');
        banner.setAttribute('aria-hidden', 'true');
        
        // Remover do DOM após a animação (opcional)
        setTimeout(() => {
            banner.style.display = 'none';
        }, 500);
    }

    // Evento de aceitação
    acceptBtn.addEventListener('click', function(e) {
        e.preventDefault();
        setAcknowledged();
        hideBanner();
        
        // Opcional: pequeno feedback visual
        console.log('[Wasom Upfy] Preferência de cookies guardada.');
    });

    // Inicializar
    showBannerIfNeeded();

    // Expor API simples para debug (apenas desenvolvimento)
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
        window.resetCookieConsent = function() {
            document.cookie = COOKIE_NAME + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            banner.style.display = 'block';
            banner.classList.add('visible');
            console.log('Cookie consent reset.');
        };
    }
})();