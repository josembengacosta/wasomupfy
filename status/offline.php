<!DOCTYPE html>
<?php
// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Offline (Site Público)
// Ficheiro: status/offline.php  (profundidade: ../)
//
// ⚠️  PÁGINA ESPECIAL — servida pelo Service Worker quando não há ligação.
//
// REGRAS OBRIGATÓRIAS:
//   1. NÃO usar require_once — o PHP pode não carregar sem rede
//   2. NÃO usar CDN externos — Bootstrap, BI Icons, etc. ficam inacessíveis
//   3. NÃO depender da BD — getSiteDB() vai falhar sem ligação ao servidor
//   4. Todo o CSS e ícones SVG devem ser inline neste ficheiro
//   5. Este ficheiro deve ser incluído no cache do Service Worker (sw.js)
//
// Como registar no sw.js:
//   const CACHE_URLS = ['/wasomupfy/status/offline.php', ...];
// ══════════════════════════════════════════════════════════════════════
?>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="apple-touch-icon" href="../assets/img/icones/wasomupfy_fiv_512.png" />
    <title>Sem Ligação — Wasom Upfy</title>
    <!-- Sem CDN — tudo inline obrigatoriamente -->
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        :root {
            --cyan: #22d3ee;
            --cyan-dark: #0891b2;
            --cyan-glow: rgba(34, 211, 238, .22);
            --pink: #FF0089;
            --bg: #08080f;
            --card: rgba(255, 255, 255, .04);
            --border: rgba(255, 255, 255, .08);
            --border-cyan: rgba(34, 211, 238, .18);
            --text: #e8e8f0;
            --muted: rgba(255, 255, 255, .45);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow-x: hidden;
        }

        /* ── Orbs ── */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .13;
            animation: floatOrb 16s ease-in-out infinite;
        }

        .bg-orb:nth-child(1) {
            width: 500px;
            height: 500px;
            background: #0891b2;
            top: -150px;
            left: -120px;
            animation-delay: 0s;
        }

        .bg-orb:nth-child(2) {
            width: 370px;
            height: 370px;
            background: #164e63;
            bottom: -110px;
            right: -80px;
            animation-delay: -7s;
        }

        .bg-orb:nth-child(3) {
            width: 260px;
            height: 260px;
            background: #FF0089;
            top: 42%;
            left: 57%;
            animation-delay: -12s;
        }

        @keyframes floatOrb {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(26px, -34px) scale(1.04);
            }

            66% {
                transform: translate(-18px, 22px) scale(.97);
            }
        }

        /* ── Layout ── */
        .page-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem 4rem;
        }

        /* ── Logo texto — sem imagem (pode não estar em cache) ── */
        .brand-logo {
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: .14em;
            color: var(--pink);
            font-family: Arial, sans-serif;
            margin-bottom: 2rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--pink);
            box-shadow: 0 0 10px var(--pink);
            animation: pulsePink 2s ease-in-out infinite;
        }

        @keyframes pulsePink {

            0%,
            100% {
                box-shadow: 0 0 6px var(--pink);
            }

            50% {
                box-shadow: 0 0 18px var(--pink), 0 0 32px rgba(255, 0, 137, .3);
            }
        }

        /* ── Card ── */
        .main-card {
            background: var(--card);
            border: 1px solid var(--border-cyan);
            border-radius: 28px;
            padding: 2.8rem 2.4rem;
            max-width: 560px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 0 60px rgba(34, 211, 238, .05), 0 24px 64px rgba(0, 0, 0, .45);
        }

        @media(max-width:576px) {
            .main-card {
                padding: 2rem 1.4rem;
                border-radius: 20px;
            }
        }

        /* ── Ícone SVG animado ── */
        .wifi-icon {
            width: 90px;
            height: 90px;
            border-radius: 24px;
            background: rgba(34, 211, 238, .08);
            border: 1.5px solid rgba(34, 211, 238, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.6rem;
            animation: iconFloat 4s ease-in-out infinite;
        }

        @keyframes iconFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        /* SVG do ícone de wifi cortado — inline, sem dependência externa */
        .wifi-icon svg {
            width: 48px;
            height: 48px;
        }

        /* Animação das ondas do wifi: aparecem e desaparecem */
        .wave-outer {
            animation: waveOff 2.4s ease-in-out infinite;
        }

        .wave-middle {
            animation: waveOff 2.4s ease-in-out infinite .3s;
        }

        .wave-inner {
            animation: waveOff 2.4s ease-in-out infinite .6s;
        }

        .slash-line {
            animation: slashIn 2.4s ease-in-out infinite;
        }

        @keyframes waveOff {

            0%,
            40%,
            100% {
                opacity: .7;
            }

            60% {
                opacity: .15;
            }
        }

        @keyframes slashIn {

            0%,
            50% {
                opacity: 0;
            }

            60%,
            90% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        /* ── Badge ── */
        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(34, 211, 238, .08);
            border: 1px solid rgba(34, 211, 238, .2);
            border-radius: 999px;
            padding: 4px 14px;
            font-size: .71rem;
            font-weight: 800;
            color: var(--cyan);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 1rem;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--cyan);
            animation: pulseCyan 2s ease-in-out infinite;
        }

        @keyframes pulseCyan {

            0%,
            100% {
                box-shadow: 0 0 4px var(--cyan);
                opacity: 1;
            }

            50% {
                box-shadow: 0 0 12px var(--cyan);
                opacity: .45;
            }
        }

        /* ── Textos ── */
        .error-title {
            font-size: 1.65rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: .5rem;
            line-height: 1.25;
        }

        .error-title span {
            color: var(--cyan);
        }

        .error-desc {
            font-size: .87rem;
            color: var(--muted);
            line-height: 1.75;
            margin-bottom: 1.6rem;
        }

        /* ── Indicador de estado da ligação ── */
        .conn-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.8rem;
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .8rem 1.2rem;
        }

        .conn-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #f87171;
            box-shadow: 0 0 8px rgba(248, 113, 113, .5);
            transition: background .4s, box-shadow .4s;
            flex-shrink: 0;
        }

        .conn-dot.online {
            background: #22c55e;
            box-shadow: 0 0 8px rgba(34, 197, 94, .5);
        }

        .conn-label {
            font-size: .83rem;
            font-weight: 600;
            color: var(--muted);
            transition: color .4s;
        }

        .conn-label.online {
            color: #22c55e;
        }

        /* ── Sugestões ── */
        .tips {
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.6rem;
            text-align: left;
        }

        .tips-title {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .3);
            margin-bottom: .7rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tips-title svg {
            width: 13px;
            height: 13px;
            fill: rgba(255, 255, 255, .3);
        }

        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tips-list li {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            font-size: .82rem;
            color: var(--muted);
            padding: .3rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            line-height: 1.5;
        }

        .tips-list li:last-child {
            border-bottom: none;
        }

        .tips-list li svg {
            flex-shrink: 0;
            margin-top: .15rem;
            fill: var(--cyan);
            width: 14px;
            height: 14px;
        }

        /* ── Botões ── */
        .action-row {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-retry {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--cyan);
            border: none;
            color: #08080f;
            border-radius: 12px;
            padding: .65rem 1.5rem;
            font-size: .85rem;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .btn-retry:hover {
            background: #67e8f9;
            transform: translateY(-1px);
        }

        .btn-retry svg {
            width: 16px;
            height: 16px;
            fill: #08080f;
        }

        .btn-retry.spinning svg {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--border);
            color: rgba(255, 255, 255, .45);
            border-radius: 12px;
            padding: .65rem 1.2rem;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s;
            border: 1px solid var(--border);
        }

        .btn-back:hover {
            border-color: rgba(255, 255, 255, .18);
            color: rgba(255, 255, 255, .7);
        }

        .btn-back svg {
            width: 15px;
            height: 15px;
            fill: currentColor;
        }

        /* ── Cooldown bar ── */
        .retry-bar-wrap {
            margin-top: .8rem;
            display: none;
        }

        .retry-bar-track {
            height: 3px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .07);
            overflow: hidden;
        }

        .retry-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--cyan), var(--cyan-dark));
            width: 100%;
            transition: width 1s linear;
            box-shadow: 0 0 6px rgba(34, 211, 238, .4);
        }

        .retry-bar-label {
            font-size: .68rem;
            color: rgba(255, 255, 255, .25);
            margin-top: .3rem;
            text-align: center;
        }

        /* ── Footer ── */
        .page-footer {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-top: 1.8rem;
            font-size: .7rem;
            color: rgba(255, 255, 255, .2);
        }
    </style>
</head>

<body>

    <div class="bg-canvas">
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
    </div>

    <div class="page-wrap">

        <!-- Logo em texto puro — imagens podem não estar em cache -->
        <a class="brand-logo" href="../home">
            <span class="brand-dot"></span>
            WASOM UPFY
        </a>

        <div class="main-card">

            <!-- Ícone WiFi inline SVG — sem dependência de Bootstrap Icons -->
            <div class="wifi-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Onda exterior -->
                    <path class="wave-outer" d="M1.5 8.5C5.1 4.9 9.8 3 12 3s6.9 1.9 10.5 5.5" stroke="#22d3ee"
                        stroke-width="1.8" stroke-linecap="round" fill="none" />
                    <!-- Onda média -->
                    <path class="wave-middle" d="M4.9 11.9C7.3 9.5 9.8 8.2 12 8.2s4.7 1.3 7.1 3.7" stroke="#22d3ee"
                        stroke-width="1.8" stroke-linecap="round" fill="none" />
                    <!-- Onda interior -->
                    <path class="wave-inner" d="M8.5 15.3C9.8 14 11 13.4 12 13.4s2.2.6 3.5 1.9" stroke="#22d3ee"
                        stroke-width="1.8" stroke-linecap="round" fill="none" />
                    <!-- Ponto de ligação -->
                    <circle cx="12" cy="19" r="1.3" fill="#22d3ee" opacity=".7" />
                    <!-- Linha de corte (sem ligação) -->
                    <line class="slash-line" x1="3" y1="3" x2="21" y2="21" stroke="#f87171" stroke-width="2"
                        stroke-linecap="round" />
                </svg>
            </div>

            <div class="error-badge">
                <span class="pulse-dot"></span>
                Sem Ligação
            </div>

            <h1 class="error-title">
                Estás <span>offline</span>
            </h1>

            <p class="error-desc">
                Não foi possível carregar esta página porque não há
                ligação à internet. Verifica a tua rede e tenta novamente.
            </p>

            <!-- Indicador de estado em tempo real -->
            <div class="conn-indicator">
                <span class="conn-dot" id="connDot"></span>
                <span class="conn-label" id="connLabel">Sem ligação à internet</span>
            </div>

            <!-- Dicas -->
            <div class="tips">
                <div class="tips-title">
                    <!-- ícone lâmpada SVG inline -->
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M9 21h6M12 3a6 6 0 0 1 6 6c0 2.2-1.2 4.1-3 5.2V17H9v-2.8C7.2 13.1 6 11.2 6 9a6 6 0 0 1 6-6z" />
                    </svg>
                    O que podes tentar
                </div>
                <ul class="tips-list">
                    <li>
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"
                                stroke="#22d3ee" stroke-width="2" stroke-linecap="round" fill="none" />
                        </svg>
                        <span>Verifica se o Wi-Fi ou dados móveis estão activos.</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" stroke="#22d3ee"
                                stroke-width="2" stroke-linecap="round" fill="none" />
                            <path d="M3 3v5h5" stroke="#22d3ee" stroke-width="2" stroke-linecap="round" fill="none" />
                        </svg>
                        <span>Reinicia o router ou activa/desactiva o modo de avião.</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="#22d3ee" stroke-width="2" fill="none" />
                            <path d="M12 8v4l3 3" stroke="#22d3ee" stroke-width="2" stroke-linecap="round"
                                fill="none" />
                        </svg>
                        <span>Aguarda alguns segundos — a página recarrega automaticamente quando a ligação
                            regressar.</span>
                    </li>
                </ul>
            </div>

            <!-- Botões -->
            <div class="action-row">
                <button class="btn-retry" id="btnRetry" type="button">
                    <!-- ícone refresh inline -->
                    <svg id="retryIcon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" stroke="#08080f" stroke-width="2.2"
                            stroke-linecap="round" fill="none" />
                        <path d="M3 3v5h5" stroke="#08080f" stroke-width="2.2" stroke-linecap="round" fill="none" />
                    </svg>
                    Tentar novamente
                </button>
                <button class="btn-back" id="btnBack" type="button" onclick="history.back()">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" fill="none" />
                    </svg>
                    Voltar
                </button>
            </div>

            <!-- Barra de cooldown / auto-retry -->
            <div class="retry-bar-wrap" id="retryBarWrap">
                <div class="retry-bar-track">
                    <div class="retry-bar-fill" id="retryBarFill"></div>
                </div>
                <div class="retry-bar-label" id="retryBarLabel"></div>
            </div>

        </div><!-- /main-card -->

        <div class="page-footer">
            <p>Wasom Upfy &nbsp;·&nbsp; Luanda, Angola</p>
        </div>

    </div><!-- /page-wrap -->

    <script>
        (function() {
            'use strict';

            var connDot = document.getElementById('connDot');
            var connLabel = document.getElementById('connLabel');
            var btnRetry = document.getElementById('btnRetry');
            var barWrap = document.getElementById('retryBarWrap');
            var barFill = document.getElementById('retryBarFill');
            var barLabel = document.getElementById('retryBarLabel');

            // ── URL de destino ────────────────────────────────────────────
            // O Service Worker passou a URL original como ?from=
            // Quando a ligação regressar, voltamos exactamente para lá.
            var params = new URLSearchParams(window.location.search);
            var fromUrl = params.get('from') || null;

            // URL de fallback: tentar ir para a home do site
            var HOME_URL = '/wasomupfy/home';

            var retryTimer = null;
            var retryCountdown = 0;
            var isRetrying = false;
            var AUTO_RETRY_SEC = 15;

            // ── Actualizar indicador visual ───────────────────────────────
            function updateConnState(online) {
                if (online) {
                    connDot.classList.add('online');
                    connLabel.classList.add('online');
                    connLabel.textContent = 'Ligação detectada — a redirecionar…';
                } else {
                    connDot.classList.remove('online');
                    connLabel.classList.remove('online');
                    connLabel.textContent = 'Sem ligação à internet';
                }
            }

            // ── Ir para o destino correcto ────────────────────────────────
            function goToTarget() {
                window.location.href = fromUrl || HOME_URL;
            }

            // ── Tentar conectividade ──────────────────────────────────────
            function tryReload() {
                if (isRetrying) return;
                isRetrying = true;

                clearInterval(retryTimer);
                barWrap.style.display = 'none';
                btnRetry.classList.add('spinning');
                btnRetry.disabled = true;

                // Testar com HEAD para a home (leve, sem cache, no-cors)
                fetch(HOME_URL + '?_oc=' + Date.now(), {
                        method: 'HEAD',
                        cache: 'no-store',
                        mode: 'no-cors'
                    })
                    .then(function() {
                        // Rede restaurada — ir para a página original
                        updateConnState(true);
                        setTimeout(goToTarget, 700);
                    })
                    .catch(function() {
                        // Ainda sem rede
                        isRetrying = false;
                        btnRetry.classList.remove('spinning');
                        btnRetry.disabled = false;
                        startCountdown(AUTO_RETRY_SEC);
                    });
            }

            // ── Countdown automático ──────────────────────────────────────
            function startCountdown(seconds) {
                retryCountdown = seconds;
                barWrap.style.display = 'block';
                barFill.style.width = '100%';
                barLabel.textContent = 'Nova tentativa em ' + seconds + 's';

                retryTimer = setInterval(function() {
                    retryCountdown--;
                    var pct = (retryCountdown / seconds) * 100;
                    barFill.style.width = Math.max(0, pct) + '%';
                    barLabel.textContent = retryCountdown > 0 ?
                        'Nova tentativa em ' + retryCountdown + 's' :
                        'A tentar…';

                    if (retryCountdown <= 0) {
                        clearInterval(retryTimer);
                        tryReload();
                    }
                }, 1000);
            }

            // ── Botão manual ──────────────────────────────────────────────
            btnRetry.addEventListener('click', function() {
                clearInterval(retryTimer);
                tryReload();
            });

            // ── Eventos nativos online/offline ────────────────────────────
            window.addEventListener('online', function() {
                updateConnState(true);
                clearInterval(retryTimer);
                // Pequena pausa para garantir que a rede estabilizou
                setTimeout(goToTarget, 700);
            });

            window.addEventListener('offline', function() {
                updateConnState(false);
            });

            // ── Início ────────────────────────────────────────────────────
            updateConnState(navigator.onLine);

            if (!navigator.onLine) {
                startCountdown(AUTO_RETRY_SEC);
            } else {
                // Tem rede mas está na offline page — ir já para o destino
                tryReload();
            }

        })();
    </script>
</body>

</html>