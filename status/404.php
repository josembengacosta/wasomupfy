<?php
// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — 404 Not Found (Site Público)
// Ficheiro: status/404.php  (profundidade: ../)
// NÃO chamar checkPlatformStatus() aqui — evita loop infinito
// ══════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

// HTTP 404 obrigatório
http_response_code(404);

// ── Dados da plataforma ───────────────────────────────────────────────
$site_name     = cfg('site_name',     'Wasom Upfy');
$support_email = cfg('support_email', 'suporte@wasomupfy.com');
$platform_ver  = getPlatform()['version'] ?? '2.0';

// ── URL que não foi encontrada ────────────────────────────────────────
$requested_url = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES);

// ── Sugestões de páginas populares ───────────────────────────────────
$suggestions = [
    ['href' => '../home',                        'icon' => 'bi-house-heart',     'label' => 'Início',            'desc' => 'Página principal'],
    ['href' => '../plan/all-plans',              'icon' => 'bi-grid-1x2',        'label' => 'Planos',            'desc' => 'Ver todos os planos'],
    ['href' => '../page/services/music-distribution', 'icon' => 'bi-music-note-list', 'label' => 'Distribuição',  'desc' => 'Como funciona'],
    ['href' => '../page/support/faq',            'icon' => 'bi-question-circle', 'label' => 'FAQ',               'desc' => 'Perguntas frequentes'],
    ['href' => '../page/support/help',           'icon' => 'bi-life-preserver',  'label' => 'Ajuda',             'desc' => 'Centro de ajuda'],
    ['href' => '../page/support/support',        'icon' => 'bi-headset',         'label' => 'Suporte',           'desc' => 'Falar com a equipa'],
];

// ── Tracking do 404 — registar pageview para análise ─────────────────
// Útil para detectar links quebrados internos ou externos
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    trackVisitor($requested_url, '404 — ' . $requested_url);
}
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
    <title>404 — Página Não Encontrada · <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="shortcut icon" href="https://wasomupfy.rf.gd/assets/img/icones/wasomupfy_fiv.png"
        type="image/x-icon" />
    <link rel="apple-touch-icon" href="https://wasomupfy.rf.gd/assets/img/icones/wasomupfy_fiv_512.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        :root {
            --teal: #2dd4bf;
            --teal-dark: #0f766e;
            --teal-glow: rgba(45, 212, 191, .22);
            --pink: #FF0089;
            --bg: #08080f;
            --card: rgba(255, 255, 255, .04);
            --border: rgba(255, 255, 255, .08);
            --border-teal: rgba(45, 212, 191, .18);
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
            opacity: .14;
            animation: floatOrb 16s ease-in-out infinite;
        }

        .bg-orb:nth-child(1) {
            width: 500px;
            height: 500px;
            background: #0f766e;
            top: -150px;
            left: -120px;
            animation-delay: 0s;
        }

        .bg-orb:nth-child(2) {
            width: 370px;
            height: 370px;
            background: #134e4a;
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
            border: 1px solid var(--border-teal);
            border-radius: 28px;
            padding: 2.8rem 2.4rem;
            max-width: 620px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 0 60px rgba(45, 212, 191, .06), 0 24px 64px rgba(0, 0, 0, .45);
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
            color: var(--teal);
            letter-spacing: -.04em;
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 40px rgba(45, 212, 191, .3);
            margin-bottom: .3rem;
            animation: drift 12s ease-in-out infinite;
        }

        @keyframes drift {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        /* ── Badge ── */
        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(45, 212, 191, .08);
            border: 1px solid rgba(45, 212, 191, .22);
            border-radius: 999px;
            padding: 4px 14px;
            font-size: .71rem;
            font-weight: 800;
            color: var(--teal);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 1rem;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--teal);
            animation: pulseTeal 1.8s ease-in-out infinite;
        }

        @keyframes pulseTeal {

            0%,
            100% {
                box-shadow: 0 0 4px var(--teal);
                opacity: 1;
            }

            50% {
                box-shadow: 0 0 10px var(--teal);
                opacity: .55;
            }
        }

        /* ── Ícone ── */
        .error-icon {
            width: 82px;
            height: 82px;
            border-radius: 22px;
            background: rgba(45, 212, 191, .08);
            border: 1.5px solid rgba(45, 212, 191, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.4rem;
            font-size: 2.2rem;
            color: var(--teal);
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            40% {
                transform: translateY(-8px) rotate(-3deg);
            }

            70% {
                transform: translateY(-4px) rotate(2deg);
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
            color: var(--teal);
        }

        .error-desc {
            font-size: .87rem;
            color: var(--muted);
            line-height: 1.75;
            margin-bottom: .3rem;
        }

        /* ── URL não encontrada ── */
        .url-box {
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .5rem 1rem;
            margin-bottom: 1.6rem;
            font-family: 'Courier New', monospace;
            font-size: .78rem;
            color: rgba(255, 255, 255, .3);
            word-break: break-all;
            text-align: left;
        }

        .url-box span {
            color: rgba(45, 212, 191, .6);
        }

        /* ── Pesquisa ── */
        .search-section {
            margin-bottom: 1.6rem;
        }

        .search-label {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .3);
            margin-bottom: .5rem;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .search-group {
            display: flex;
            gap: 8px;
        }

        .search-input {
            flex: 1;
            background: rgba(255, 255, 255, .06);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: .58rem 1rem;
            color: #fff;
            font-size: .83rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, .22);
        }

        .search-input:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(45, 212, 191, .12);
        }

        .btn-search {
            background: var(--teal);
            border: none;
            color: #08080f;
            border-radius: 12px;
            padding: .58rem 1.2rem;
            font-size: .85rem;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s;
            flex-shrink: 0;
        }

        .btn-search:hover {
            background: #5eead4;
            transform: translateY(-1px);
        }

        /* ── Sugestões de páginas ── */
        .sug-title {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .3);
            margin-bottom: .6rem;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sug-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .5rem;
            margin-bottom: 1.6rem;
        }

        @media(max-width:480px) {
            .sug-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .sug-card {
            background: rgba(255, 255, 255, .04);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .75rem .8rem;
            text-decoration: none;
            text-align: left;
            transition: all .2s;
            display: block;
        }

        .sug-card:hover {
            background: rgba(45, 212, 191, .07);
            border-color: rgba(45, 212, 191, .22);
            transform: translateY(-2px);
        }

        .sug-card-icon {
            font-size: 1.15rem;
            color: var(--teal);
            margin-bottom: .35rem;
            display: block;
        }

        .sug-card-label {
            font-size: .82rem;
            font-weight: 700;
            color: #fff;
            display: block;
        }

        .sug-card-desc {
            font-size: .7rem;
            color: var(--muted);
        }

        /* ── Separador ── */
        .divider {
            height: 1px;
            background: var(--border);
            margin: 1.4rem 0;
        }

        /* ── Botões principais ── */
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
        }

        .btn-home:hover {
            background: #c8006e;
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-back {
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

        .btn-back:hover {
            background: rgba(45, 212, 191, .08);
            border-color: var(--teal);
            color: var(--teal);
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

        <!-- Logo -->
        <a class="brand-logo" href="https://wasomupfy.rf.gd/home">
            <img src="https://wasomupfy.rf.gd/assets/img/brand/wasomupfy_brand.png"
                alt="<?php echo htmlspecialchars($site_name); ?>" height="32" style="filter:brightness(0) invert(1)"
                onerror="this.style.display='none';this.nextElementSibling.style.display='inline'" />
            <span style="display:none">
                <span class="brand-dot"></span><?php echo strtoupper(htmlspecialchars($site_name)); ?>
            </span>
        </a>

        <div class="main-card">

            <div class="error-code">404</div>

            <div class="error-badge">
                <span class="pulse-dot"></span>
                Página Não Encontrada
            </div>

            <div class="error-icon">
                <i class="bi bi-map"></i>
            </div>

            <h1 class="error-title">
                Perdeste-te no <span>espaço</span>
            </h1>

            <p class="error-desc">
                A página que procuras não existe, foi movida ou o endereço
                foi introduzido incorrectamente.
            </p>

            <!-- URL que não foi encontrada -->
            <div class="url-box">
                <span style="opacity:.4">wasomupfy.com</span><span><?php echo $requested_url; ?></span>
            </div>

            <!-- Pesquisa rápida -->
            <div class="search-section">
                <div class="search-label">
                    <i class="bi bi-search"></i>Pesquisar no site
                </div>
                <div class="search-group">
                    <input type="text" id="searchInput" class="search-input"
                        placeholder="Ex: distribuição, royalties, planos…" />
                    <button class="btn-search" id="btnSearch" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <!-- Sugestões de páginas -->
            <div class="sug-title">
                <i class="bi bi-compass"></i>Talvez estejas à procura de…
            </div>
            <div class="sug-grid">
                <?php foreach ($suggestions as $s): ?>
                    <a href="<?php echo htmlspecialchars($s['href']); ?>" class="sug-card">
                        <i class="bi <?php echo $s['icon']; ?> sug-card-icon"></i>
                        <span class="sug-card-label"><?php echo htmlspecialchars($s['label']); ?></span>
                        <span class="sug-card-desc"><?php echo htmlspecialchars($s['desc']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="divider"></div>

            <!-- Acções -->
            <div class="action-row">
                <a href="https://wasomupfy.rf.gd/home" class="btn-home">
                    <i class="bi bi-house"></i> Voltar ao Início
                </a>
                <a href="javascript:history.back()" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Página Anterior
                </a>
            </div>

        </div><!-- /main-card -->

        <div class="page-footer">
            <p>
                <?php echo htmlspecialchars($site_name); ?> &nbsp;·&nbsp; Luanda, Angola
                &nbsp;·&nbsp; v<?php echo htmlspecialchars($platform_ver); ?>
            </p>
            <p>
                <a href="https://wasomupfy.rf.gd/page/politicies/terms">Termos de Uso</a>
                &nbsp;·&nbsp;
                <a href="https://wasomupfy.rf.gd/page/politicies/privacy">Política de Privacidade</a>
                &nbsp;·&nbsp;
                <a
                    href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a>
            </p>
        </div>

    </div><!-- /page-wrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ── Mapa de pesquisa → URL destino ────────────────────────────────
        var searchMap = [{
                keywords: ['distribuição', 'distribuicao', 'distribui', 'lançamento', 'lancamento', 'release',
                    'upload'
                ],
                url: 'https://wasomupfy.rf.gd/page/services/music-distribution',
                label: 'Distribuição Musical'
            },
            {
                keywords: ['plano', 'planos', 'preço', 'preco', 'prémio', 'premium', 'single', 'album', 'artista',
                    'label', 'gravadora'
                ],
                url: 'https://wasomupfy.rf.gd/plan/all-plans',
                label: 'Planos'
            },
            {
                keywords: ['royalt', 'pagamento', 'pagar', 'dinheiro', 'ganho', 'receber', 'retirar', 'saldo'],
                url: 'https://wasomupfy.rf.gd/plan/all-plans',
                label: 'Planos e Royalties'
            },
            {
                keywords: ['faq', 'pergunta', 'duvida', 'dúvida', 'frequente'],
                url: 'https://wasomupfy.rf.gd/page/support/faq',
                label: 'FAQ'
            },
            {
                keywords: ['ajuda', 'help', 'tutorial', 'como', 'guia'],
                url: 'https://wasomupfy.rf.gd/page/support/help',
                label: 'Centro de Ajuda'
            },
            {
                keywords: ['suporte', 'support', 'contacto', 'contato', 'problema', 'erro', 'reclamação'],
                url: 'https://wasomupfy.rf.gd/page/support/support',
                label: 'Suporte'
            },
            {
                keywords: ['sobre', 'about', 'equipa', 'empresa', 'quem'],
                url: 'https://wasomupfy.rf.gd/about',
                label: 'Sobre Nós'
            },
            {
                keywords: ['contato', 'contacto', 'contact', 'email', 'mensagem'],
                url: 'https://wasomupfy.rf.gd/contact',
                label: 'Contacto'
            },
            {
                keywords: ['servico', 'serviço', 'personalizado', 'custom', 'promoc', 'promoção'],
                url: 'https://wasomupfy.rf.gd/page/services/customized-services',
                label: 'Serviços Personalizados'
            },
            {
                keywords: ['termos', 'termo', 'privacidade', 'cookies', 'politica', 'política'],
                url: 'https://wasomupfy.rf.gd/page/politicies/terms',
                label: 'Termos de Uso'
            },
            {
                keywords: ['parceria', 'parceiro', 'partnership', 'recursos', 'recursos'],
                url: 'https://wasomupfy.rf.gd/partnership',
                label: 'Parcerias'
            },
        ];

        function normalizeStr(s) {
            return s.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s]/g, '').trim();
        }

        function doSearch() {
            var query = normalizeStr(document.getElementById('searchInput').value);
            if (!query) return;

            var best = null;
            for (var i = 0; i < searchMap.length; i++) {
                for (var j = 0; j < searchMap[i].keywords.length; j++) {
                    if (query.indexOf(searchMap[i].keywords[j]) !== -1) {
                        best = searchMap[i];
                        break;
                    }
                }
                if (best) break;
            }

            if (best) {
                window.location.href = best.url;
            } else {
                // Nada encontrado — ir para a home com o query como hash
                window.location.href = 'https://wasomupfy.rf.gd/home#' + encodeURIComponent(query);
            }
        }

        document.getElementById('btnSearch').addEventListener('click', doSearch);
        document.getElementById('searchInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') doSearch();
        });

        // Focar automaticamente na pesquisa
        document.getElementById('searchInput').focus();
    </script>
</body>

</html>