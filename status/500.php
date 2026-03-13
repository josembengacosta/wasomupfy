<?php
// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — 500 Internal Server Error (Site Público)
// Ficheiro: status/500.php  (profundidade: ../)
// NÃO chamar checkPlatformStatus() aqui — evita loop infinito
// ══════════════════════════════════════════════════════════════════════

// HTTP 500 antes de qualquer output
http_response_code(500);

// Tentar carregar o site.php para ter acesso às configs e funções.
// Se falhar (o erro pode ser exactamente no include), usar fallbacks.
$site_loaded = false;
try {
    require_once __DIR__ . '/../include/site.php';
    $site_loaded = true;
} catch (Throwable $e) {
    // site.php está com problemas — continuar sem ele
    error_log('[500] site.php não carregou: ' . $e->getMessage());
}

// ── Dados com fallback total caso a BD esteja indisponível ────────────
$site_name     = 'Wasom Upfy';
$support_email = 'suporte@wasomupfy.com';
$platform_ver  = '2.0';

if ($site_loaded) {
    try {
        $site_name     = cfg('site_name',     $site_name);
        $support_email = cfg('support_email', $support_email);
        $platform_ver  = getPlatform()['version'] ?? $platform_ver;
    } catch (Throwable $e) {
        // BD indisponível — manter fallbacks
        error_log('[500] cfg/getPlatform falhou: ' . $e->getMessage());
    }
}

// ── Referência do erro para o utilizador reportar ao suporte ──────────
// Gerada do timestamp + IP para ser rastreável nos logs sem expor detalhes
$error_ref = strtoupper(substr(md5(time() . ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '')), 0, 8));
$error_time = date('d/m/Y H:i:s') . ' (WAT)';

// ── URL que causou o erro (para contexto no suporte) ──────────────────
$error_url = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="author" content="José Mbenga da Costa" />
    <title>500 — Erro Interno · <?php echo htmlspecialchars($site_name); ?></title>
    <!-- Usar CDN directamente — assets locais podem estar inacessíveis -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    :root {
        --purple: #a855f7;
        --purple-dark: #7c3aed;
        --purple-glow: rgba(168, 85, 247, .25);
        --pink: #FF0089;
        --bg: #08080f;
        --card: rgba(255, 255, 255, .04);
        --border: rgba(255, 255, 255, .08);
        --border-purp: rgba(168, 85, 247, .2);
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
        opacity: .15;
        animation: floatOrb 16s ease-in-out infinite;
    }

    .bg-orb:nth-child(1) {
        width: 500px;
        height: 500px;
        background: #7c3aed;
        top: -150px;
        left: -120px;
        animation-delay: 0s;
    }

    .bg-orb:nth-child(2) {
        width: 380px;
        height: 380px;
        background: #4c1d95;
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

    /* ── Logo ── */
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
        border: 1px solid var(--border-purp);
        border-radius: 28px;
        padding: 2.8rem 2.4rem;
        max-width: 580px;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        box-shadow: 0 0 60px rgba(124, 58, 237, .08), 0 24px 64px rgba(0, 0, 0, .45);
    }

    @media(max-width:576px) {
        .main-card {
            padding: 2rem 1.4rem;
            border-radius: 20px;
        }
    }

    /* ── Código de erro ── */
    .error-code {
        font-size: 5.5rem;
        font-weight: 900;
        line-height: 1;
        color: var(--purple);
        letter-spacing: -.04em;
        font-family: 'Courier New', monospace;
        text-shadow: 0 0 40px rgba(168, 85, 247, .35);
        margin-bottom: .3rem;
        animation: crash 10s ease-in-out infinite;
    }

    @keyframes crash {

        0%,
        85%,
        100% {
            transform: none;
            filter: none;
        }

        86% {
            transform: translateX(-3px) skewX(2deg);
            filter: blur(1px);
        }

        87% {
            transform: translateX(3px) skewX(-2deg);
            filter: none;
        }

        88% {
            transform: translateX(-2px);
        }

        89% {
            transform: none;
        }

        92% {
            transform: translateX(2px) skewX(1deg);
            filter: blur(.5px);
        }

        93% {
            transform: none;
            filter: none;
        }
    }

    /* ── Badge ── */
    .error-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(168, 85, 247, .1);
        border: 1px solid rgba(168, 85, 247, .28);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .71rem;
        font-weight: 800;
        color: var(--purple);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 1rem;
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--purple);
        animation: pulsePurp 1.4s ease-in-out infinite;
    }

    @keyframes pulsePurp {

        0%,
        100% {
            box-shadow: 0 0 4px var(--purple);
            opacity: 1;
        }

        50% {
            box-shadow: 0 0 12px var(--purple);
            opacity: .6;
        }
    }

    /* ── Ícone ── */
    .error-icon {
        width: 82px;
        height: 82px;
        border-radius: 22px;
        background: rgba(168, 85, 247, .09);
        border: 1.5px solid rgba(168, 85, 247, .2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.4rem;
        font-size: 2.2rem;
        color: var(--purple);
        animation: iconPulse 3s ease-in-out infinite;
    }

    @keyframes iconPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(168, 85, 247, 0);
        }

        50% {
            box-shadow: 0 0 0 12px rgba(168, 85, 247, .08);
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
        color: var(--purple);
    }

    .error-desc {
        font-size: .87rem;
        color: var(--muted);
        line-height: 1.75;
        margin-bottom: 1.4rem;
    }

    /* ── Referência do erro ── */
    .ref-box {
        background: rgba(168, 85, 247, .07);
        border: 1px solid rgba(168, 85, 247, .15);
        border-radius: 14px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.6rem;
        text-align: left;
    }

    .ref-label {
        font-size: .7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(255, 255, 255, .3);
        margin-bottom: .5rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ref-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ref-code {
        font-family: 'Courier New', monospace;
        font-size: 1.05rem;
        font-weight: 900;
        color: var(--purple);
        letter-spacing: .12em;
    }

    .ref-meta {
        font-size: .73rem;
        color: var(--muted);
        line-height: 1.5;
    }

    .ref-meta span {
        display: block;
    }

    .btn-copy {
        background: rgba(168, 85, 247, .12);
        border: 1px solid rgba(168, 85, 247, .2);
        color: var(--purple);
        border-radius: 8px;
        padding: .3rem .75rem;
        font-size: .72rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-copy:hover {
        background: rgba(168, 85, 247, .22);
    }

    .btn-copy.copied {
        background: rgba(34, 197, 94, .12);
        border-color: rgba(34, 197, 94, .2);
        color: #22c55e;
    }

    /* ── O que tentar ── */
    .suggestions {
        background: rgba(255, 255, 255, .03);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.6rem;
        text-align: left;
    }

    .sug-title {
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

    .sug-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sug-list li {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        font-size: .82rem;
        color: var(--muted);
        padding: .3rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, .04);
        line-height: 1.5;
    }

    .sug-list li:last-child {
        border-bottom: none;
    }

    .sug-list li i {
        color: var(--purple);
        margin-top: .1rem;
        flex-shrink: 0;
    }

    /* ── Separador ── */
    .divider {
        height: 1px;
        background: var(--border);
        margin: 1.4rem 0;
    }

    /* ── Botões ── */
    .action-row {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-home {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--pink);
        border: none;
        color: #fff;
        border-radius: 12px;
        padding: .65rem 1.5rem;
        font-size: .85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
        cursor: pointer;
    }

    .btn-home:hover {
        background: #c8006e;
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-support {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--border);
        color: var(--text);
        border-radius: 12px;
        padding: .65rem 1.4rem;
        font-size: .84rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-support:hover {
        background: rgba(168, 85, 247, .1);
        border-color: var(--purple);
        color: var(--purple);
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .04);
        border: 1px solid var(--border);
        color: rgba(255, 255, 255, .4);
        border-radius: 12px;
        padding: .65rem 1.2rem;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-back:hover {
        border-color: rgba(255, 255, 255, .18);
        color: rgba(255, 255, 255, .7);
    }

    /* ── Footer ── */
    .page-footer {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 1.8rem;
        font-size: .7rem;
        color: rgba(255, 255, 255, .25);
    }

    .page-footer a {
        color: inherit;
        text-decoration: none;
    }

    .page-footer a:hover {
        color: var(--pink);
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

        <!-- Logo — fallback puro em texto caso assets não carreguem -->
        <a class="brand-logo" href="../home">
            <img src="../assets/img/brand/wasomupfy_brand.png" alt="<?php echo htmlspecialchars($site_name); ?>"
                height="32" style="filter:brightness(0) invert(1)"
                onerror="this.style.display='none';this.nextElementSibling.style.display='inline'" />
            <span style="display:none">
                <span class="brand-dot"></span><?php echo strtoupper(htmlspecialchars($site_name)); ?>
            </span>
        </a>

        <div class="main-card">

            <div class="error-code">500</div>

            <div class="error-badge">
                <span class="pulse-dot"></span>
                Erro Interno do Servidor
            </div>

            <div class="error-icon">
                <i class="bi bi-bug"></i>
            </div>

            <h1 class="error-title">
                Algo correu <span>mal</span><br>do nosso lado
            </h1>

            <p class="error-desc">
                Ocorreu um erro inesperado no servidor. Não foi causado por nada
                que tenhas feito — a nossa equipa técnica foi notificada
                e está a investigar o problema.
            </p>

            <!-- Referência do erro -->
            <div class="ref-box">
                <div class="ref-label">
                    <i class="bi bi-hash"></i>Referência do erro
                </div>
                <div class="ref-row">
                    <div>
                        <div class="ref-code" id="errorRef"><?php echo $error_ref; ?></div>
                        <div class="ref-meta">
                            <span><?php echo $error_time; ?></span>
                            <span style="font-size:.68rem;opacity:.6"><?php echo $error_url; ?></span>
                        </div>
                    </div>
                    <button class="btn-copy" id="btnCopy" type="button" title="Copiar referência">
                        <i class="bi bi-clipboard me-1"></i>Copiar
                    </button>
                </div>
            </div>

            <!-- Sugestões -->
            <div class="suggestions">
                <div class="sug-title">
                    <i class="bi bi-lightbulb"></i>O que podes fazer
                </div>
                <ul class="sug-list">
                    <li>
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Recarrega a página — erros pontuais resolvem-se sozinhos.</span>
                    </li>
                    <li>
                        <i class="bi bi-clock-history"></i>
                        <span>Aguarda alguns minutos e tenta novamente.</span>
                    </li>
                    <li>
                        <i class="bi bi-house"></i>
                        <span>Volta à página inicial e retoma a navegação a partir daí.</span>
                    </li>
                    <li>
                        <i class="bi bi-headset"></i>
                        <span>Se o erro persistir, contacta o suporte com a referência <strong
                                style="color:var(--purple)"><?php echo $error_ref; ?></strong>.</span>
                    </li>
                </ul>
            </div>

            <div class="divider"></div>

            <!-- Acções -->
            <div class="action-row">
                <a href="../home" class="btn-home">
                    <i class="bi bi-house"></i> Início
                </a>
                <a href="../page/support/support?ref=<?php echo urlencode($error_ref); ?>" class="btn-support">
                    <i class="bi bi-headset"></i> Reportar ao Suporte
                </a>
                <a href="javascript:location.reload()" class="btn-back">
                    <i class="bi bi-arrow-clockwise"></i> Recarregar
                </a>
            </div>

        </div><!-- /main-card -->

        <div class="page-footer">
            <p>
                <?php echo htmlspecialchars($site_name); ?> &nbsp;·&nbsp; Luanda, Angola
                &nbsp;·&nbsp; v<?php echo htmlspecialchars($platform_ver); ?>
            </p>
            <p>
                <a href="../page/politicies/terms">Termos de Uso</a>
                &nbsp;·&nbsp;
                <a href="../page/politicies/privacy">Política de Privacidade</a>
                &nbsp;·&nbsp;
                <a
                    href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a>
            </p>
        </div>

    </div><!-- /page-wrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // ── Copiar referência do erro ─────────────────────────────────────
    document.getElementById('btnCopy').addEventListener('click', function() {
        var ref = document.getElementById('errorRef').textContent.trim();
        var btn = this;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(ref).then(function() {
                showCopied(btn);
            }).catch(function() {
                fallbackCopy(ref, btn);
            });
        } else {
            fallbackCopy(ref, btn);
        }
    });

    function fallbackCopy(text, btn) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            showCopied(btn);
        } catch (e) {}
        document.body.removeChild(ta);
    }

    function showCopied(btn) {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copiado';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copiar';
        }, 2500);
    }

    // ── Auto-reload silencioso após 30s ───────────────────────────────
    // Se o erro foi pontual, a página recarrega automaticamente uma vez.
    // sessionStorage garante que só acontece uma vez por sessão/visita.
    (function() {
        var key = 'wu_500_reloaded_<?php echo $error_ref; ?>';
        if (!sessionStorage.getItem(key)) {
            sessionStorage.setItem(key, '1');
            var countdown = 30;
            var notice = document.createElement('div');
            notice.style.cssText =
                'position:fixed;bottom:1.2rem;right:1.2rem;z-index:9999;background:rgba(168,85,247,.12);border:1px solid rgba(168,85,247,.22);border-radius:12px;padding:.55rem 1rem;font-size:.75rem;color:rgba(255,255,255,.5);font-family:Segoe UI,Arial,sans-serif';
            notice.id = 'autoReloadNotice';
            document.body.appendChild(notice);

            var t = setInterval(function() {
                countdown--;
                notice.textContent = 'Recarga automática em ' + countdown + 's…';
                if (countdown <= 0) {
                    clearInterval(t);
                    window.location.reload();
                }
            }, 1000);
            notice.textContent = 'Recarga automática em ' + countdown + 's…';

            // Cancelar se o utilizador interagir
            ['click', 'keydown', 'scroll'].forEach(function(ev) {
                document.addEventListener(ev, function cancel() {
                    clearInterval(t);
                    if (notice.parentNode) notice.parentNode.removeChild(notice);
                    document.removeEventListener(ev, cancel);
                }, {
                    once: true
                });
            });
        }
    })();
    </script>
</body>

</html>