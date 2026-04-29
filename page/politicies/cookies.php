<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Política de Cookies
// Arquivo: page/politicies/cookies.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('cookies');
trackVisitor('/page/politicies/cookies', 'Política de Cookies — Wasom Upfy');

$plans       = getPlans();
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];

$siteName  = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl   = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum  = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));
$csrf_page = getSiteCsrf();
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords"
        content="<?php echo $siteName; ?>, Política de Cookies, cookies, rastreamento, sessão, privacidade, Angola" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Política de Cookies" />
    <meta property="og:description"
        content="Saiba quais cookies a <?php echo $siteName; ?> utiliza, para que servem e como os pode gerir ou desactivar. Transparência total sobre tecnologias de rastreamento." />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/politicies/cookies" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Política de Cookies</title>
    <script>
    window.addEventListener("load", function() {
        setTimeout(function() {
            document.querySelector("body").classList.add("loaded");
        }, 200);
    });
    </script>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/theme.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/framework.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/main.css" />
    <style>
    /* ── Progress bar ── */
    #reading-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #f39c12, #e67e22);
        z-index: 9999;
        transition: width .1s linear
    }

    /* ── Layout ── */
    .terms-layout {
        display: flex;
        gap: 2rem;
        align-items: flex-start
    }

    .terms-index {
        position: sticky;
        top: 90px;
        width: 240px;
        flex-shrink: 0;
        background: var(--bs-body-bg, #fff);
        border-radius: 12px;
        box-shadow: 0 2px 14px rgba(0, 0, 0, .08);
        padding: 1.25rem 1rem;
        max-height: calc(100vh - 110px);
        overflow-y: auto;
        scrollbar-width: thin
    }

    .terms-index h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #f39c12;
        margin-bottom: 1rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid rgba(243, 156, 18, .2)
    }

    .terms-index ul {
        list-style: none;
        padding: 0;
        margin: 0
    }

    .terms-index li {
        margin-bottom: .3rem
    }

    .terms-index a {
        display: flex;
        align-items: flex-start;
        gap: .4rem;
        font-size: .82rem;
        color: var(--bs-body-color, #444);
        text-decoration: none;
        line-height: 1.4;
        padding: .3rem .4rem;
        border-radius: 6px;
        transition: background .15s, color .15s
    }

    .terms-index a:hover,
    .terms-index a.active {
        background: rgba(243, 156, 18, .1);
        color: #e67e22
    }

    .terms-index .num {
        flex-shrink: 0;
        font-weight: 700;
        color: #f39c12;
        min-width: 22px
    }

    .terms-index .idx-divider {
        margin: .6rem 0;
        border-color: rgba(243, 156, 18, .15)
    }

    .terms-content {
        flex: 1;
        min-width: 0
    }

    /* ── Sections ── */
    .term-section {
        background: var(--bs-body-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .07);
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
        scroll-margin-top: 90px;
        border-left: 4px solid transparent;
        transition: border-color .3s
    }

    .term-section:hover {
        border-left-color: rgba(243, 156, 18, .4)
    }

    .term-section:target {
        border-left-color: #f39c12
    }

    .term-section h2 {
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: 1.3rem;
        font-weight: 700;
        color: #e67e22;
        margin-bottom: 1rem;
        padding-bottom: .6rem;
        border-bottom: 1px solid rgba(243, 156, 18, .15)
    }

    .sec-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: #fff;
        font-weight: 700;
        font-size: .85rem;
        border-radius: 8px;
        flex-shrink: 0
    }

    .term-section h3 {
        font-size: 1rem;
        font-weight: 700;
        margin-top: 1.25rem;
        margin-bottom: .5rem
    }

    .term-section p {
        line-height: 1.75
    }

    .term-section ul {
        padding-left: 1.2rem
    }

    .term-section ul li {
        margin-bottom: .45rem;
        line-height: 1.7
    }

    /* ── Callout boxes ── */
    .term-box {
        border-radius: 10px;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        font-size: .93rem;
        line-height: 1.65
    }

    .term-box strong {
        display: block;
        margin-bottom: .35rem
    }

    .term-box.danger {
        background: rgba(220, 53, 69, .08);
        border-left: 4px solid #dc3545
    }

    .term-box.warning {
        background: rgba(243, 156, 18, .1);
        border-left: 4px solid #f39c12
    }

    .term-box.success {
        background: rgba(25, 135, 84, .08);
        border-left: 4px solid #198754
    }

    .term-box.info {
        background: rgba(13, 202, 240, .08);
        border-left: 4px solid #0dcaf0
    }

    .term-box.pink {
        background: rgba(255, 0, 157, .07);
        border-left: 4px solid #ff009d
    }

    .term-box.neutral {
        background: rgba(108, 117, 125, .07);
        border-left: 4px solid #6c757d
    }

    /* ── Cookie table ── */
    .cookie-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .88rem;
        margin: 1rem 0;
        border-radius: 10px;
        overflow: hidden
    }

    .cookie-table thead tr {
        background: rgba(243, 156, 18, .12)
    }

    .cookie-table thead th {
        padding: .7rem 1rem;
        font-weight: 700;
        text-align: left;
        white-space: nowrap
    }

    .cookie-table tbody tr {
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        transition: background .15s
    }

    .cookie-table tbody tr:last-child {
        border-bottom: none
    }

    .cookie-table tbody tr:hover {
        background: rgba(243, 156, 18, .04)
    }

    .cookie-table td {
        padding: .65rem 1rem;
        vertical-align: top
    }

    .cookie-table code {
        background: rgba(243, 156, 18, .1);
        padding: .15rem .4rem;
        border-radius: 4px;
        font-size: .82rem;
        color: #e67e22
    }

    .cookie-badge {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: 50px;
        font-size: .75rem;
        font-weight: 700;
        white-space: nowrap
    }

    .badge-essential {
        background: rgba(220, 53, 69, .12);
        color: #dc3545
    }

    .badge-functional {
        background: rgba(13, 110, 253, .1);
        color: #0d6efd
    }

    .badge-security {
        background: rgba(25, 135, 84, .1);
        color: #198754
    }

    .badge-analytics {
        background: rgba(243, 156, 18, .15);
        color: #cc8400
    }

    .badge-none {
        background: rgba(108, 117, 125, .1);
        color: #6c757d
    }

    /* ── Browser guide ── */
    .browser-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: .75rem;
        margin: 1rem 0
    }

    .browser-card {
        background: var(--bs-body-bg, #fff);
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        transition: box-shadow .2s, transform .2s
    }

    .browser-card:hover {
        box-shadow: 0 4px 16px rgba(243, 156, 18, .15);
        transform: translateY(-2px)
    }

    .browser-card a {
        text-decoration: none;
        color: inherit;
        display: block
    }

    .browser-card i {
        font-size: 2rem;
        margin-bottom: .5rem;
        display: block
    }

    .browser-card span {
        font-size: .82rem;
        font-weight: 600
    }

    /* ── Consent banner preview ── */
    .consent-preview {
        background: rgba(243, 156, 18, .06);
        border: 2px dashed rgba(243, 156, 18, .3);
        border-radius: 12px;
        padding: 1.25rem;
        margin: 1rem 0
    }

    .consent-preview-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        justify-content: space-between;
        font-size: .88rem
    }

    .consent-preview-bar .btns {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap
    }

    /* ── Action buttons ── */
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: 2rem
    }

    .action-buttons .btn {
        border-radius: 50px;
        font-size: .88rem;
        padding: .5rem 1.4rem
    }

    /* ── Hero badges ── */
    .terms-badges a {
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        transition: transform .2s, opacity .2s
    }

    .terms-badges a:hover {
        transform: scale(1.06);
        opacity: .85
    }

    /* ── Policy cards ── */
    .policy-cards .card {
        border-radius: 12px;
        transition: transform .2s, box-shadow .2s;
        text-decoration: none
    }

    .policy-cards .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(243, 156, 18, .15) !important
    }

    /* ── Dark mode ── */
    [data-bs-theme="dark"] .term-section,
    [data-bs-theme="dark"] .terms-index,
    [data-bs-theme="dark"] .browser-card {
        background: var(--bs-body-bg);
        box-shadow: 0 2px 14px rgba(0, 0, 0, .25)
    }

    [data-bs-theme="dark"] .cookie-table tbody tr:hover {
        background: rgba(243, 156, 18, .06)
    }

    /* ── Responsive ── */
    @media(max-width:991.98px) {
        .terms-index {
            display: none !important
        }

        .terms-layout {
            display: block
        }

        .term-section {
            padding: 1.25rem
        }
    }

    @media(max-width:575.98px) {
        .term-section h2 {
            font-size: 1.1rem
        }

        .browser-grid {
            grid-template-columns: repeat(3, 1fr)
        }
    }
    </style>
</head>

<body data-base-path="../..">

    <div id="reading-progress"></div>

    <!-- Preloader -->
    <div class="preloader">
        <img src="../../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="Loading-<?php echo $siteName; ?>" />
    </div>

    <!-- ══ Navbar ══════════════════════════════════════════════════════════════ -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="../../home" title="Home">
                    <img src="../../assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo <?php echo $siteName; ?>" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button"><i class="bi bi-list"></i></button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width:20rem">
                    <div class="offcanvas-header">
                        <a href="../../home"><img width="65" src="../../assets/img/brand/wasomupfy_brand.png"
                                alt="Logo <?php echo $siteName; ?>" /></a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item"><a class="nav-link" href="../../home">Início</a></li>
                            <li class="nav-item"><a class="nav-link" href="../../about">Sobre</a></li>
                            <li class="nav-item"><a class="nav-link" href="../../blog/" target="_blank"
                                    rel="external">Blogue</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" data-bs-toggle="dropdown" aria-expanded="false">Planos <i
                                        data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-md">
                                    <?php
                                    $navIcons = ['single' => 'fa-music', 'album' => 'fa-compact-disc', 'artist' => 'fa-microphone-lines', 'label' => 'fa-tags'];
                                    foreach ($plans as $p):
                                        $nSlug = $p['slug_plan'];
                                        $nIcon = $navIcons[$nSlug] ?? 'fa-music';
                                        $nPrc = number_format($p['price_plan'], 0, ',', '.');
                                        $nPer = $p['type_plan'] === 'subscription' ? '/ano' : '';
                                    ?>
                                    <a class="dropdown-item mb-3 text-body" href="../../plan/<?php echo $nSlug; ?>">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid <?php echo $nIcon; ?> text-wasomupfy fs-3"
                                                style="width:35px"></i>
                                            <div class="ms-3 lh-1">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                                <p class="mb-0 fs-6">Plano
                                                    <?php echo htmlspecialchars($p['name_plan']); ?> —
                                                    <?php echo $nPrc; ?> Kz<?php echo $nPer; ?></p>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                    <a class="dropdown-item mb-3 text-body" href="../../plan/all-plans">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-layer-group text-wasomupfy fs-3"
                                                style="width:35px"></i>
                                            <div class="ms-3 lh-1">
                                                <h5 class="mb-1">Todos os planos</h5>
                                                <p class="mb-0 fs-6">Ver todos os planos disponíveis</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link active" href="#" data-bs-toggle="dropdown"
                                    aria-expanded="false">Páginas <i data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a class="dropdown-item" href="../../blog/">Novidades</a>
                                            <a class="dropdown-item" href="../../blog/">Passatempo</a>
                                            <a class="dropdown-item" href="#!">Indisponível <span
                                                    class="badge bg-warning">Indisponível</span></a>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a class="dropdown-item" href="../../about?#nossamarca">A nossa
                                                    marca</a>
                                                <a class="dropdown-item" href="../../partnership">Parcerias</a>
                                                <a class="dropdown-item" href="../../about#nossa-historia">Quem
                                                    somos</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Serviços</div>
                                                <a class="dropdown-item"
                                                    href="../../page/services/music-distribution">Distribuição de
                                                    música</a>
                                                <a class="dropdown-item"
                                                    href="../../page/services/music-promotion">Promoção de música <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a class="dropdown-item"
                                                    href="../../page/services/customized-services">Serviços
                                                    personalizados <span
                                                        class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052"
                                                    target="_blank" rel="external noopener noreferrer">Atendimento</a>
                                                <a class="dropdown-item" href="../../contact">Contacta-nos</a>
                                                <a class="dropdown-item"
                                                    href="<?php echo htmlspecialchars(cfg('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04')); ?>"
                                                    target="_blank" rel="external noopener noreferrer">Canal
                                                    WhatsApp</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Sugestões</div>
                                                <a class="dropdown-item" href="../support/help">Ajuda <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                    data-bs-target="#modalFeedback">Feedback</a>
                                                <a class="dropdown-item" href="#!">Indisponível <span
                                                        class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Ajuda</div>
                                                <a class="dropdown-item" href="../support/tutorial">Tutorial <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a class="dropdown-item" href="../support/support">Suporte técnico</a>
                                                <a class="dropdown-item" href="../support/faq">Perguntas frequentes</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="../../resources">Recursos</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" data-bs-toggle="dropdown" aria-expanded="false">Contactar
                                    <i data-feather="chevron-down"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../../contact">Caixa de mensagem</a></li>
                                    <?php if (cfg('support_email')): ?><li><a class="dropdown-item"
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                    </li><?php endif; ?>
                                    <?php if ($whatsNum): ?><li><a class="dropdown-item"
                                            href="https://wa.me/<?php echo $whatsNum; ?>">WhatsApp</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        </ul>
                        <div class="mt-3 mt-lg-0 d-flex align-items-center">
                            <a href="<?php echo APP_URL  ?>/login" class="btn btn-secondary mx-2">Entrar <i
                                    data-feather="log-in"></i></a>
                            <?php if ($canRegister): ?><a href="<?php echo APP_URL  ?>/register"
                                class="btn btn-wasomupfy">Inscreva-se</a><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>

        <!-- ── Hero ─────────────────────────────────────────────────────────── -->
        <section class="jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/cookies.png"
                alt="Política de Cookies <?php echo $siteName; ?>" loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Política de Cookies</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-3 text-white-stable fw-bold">
                            <i class="fa-solid fa-cookie-bite me-3 text-warning"></i>Política de Cookies
                        </h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Explicamos de forma clara e honesta quais cookies utilizamos na plataforma
                            <?php echo $siteName; ?>, para que servem, quanto tempo ficam activos
                            e como os pode gerir ou eliminar a qualquer momento.
                        </p>
                        <p class="text-white-stable small opacity-80 mb-4">
                            <i class="fa-regular fa-calendar me-2"></i>Última actualização: 14 de Fevereiro de 2026
                            &nbsp;·&nbsp; <i class="fa-regular fa-file-lines me-2"></i>10 secções
                            &nbsp;·&nbsp; <i class="fa-regular fa-clock me-2"></i>Leitura: ~6 minutos
                        </p>
                        <div class="terms-badges d-flex justify-content-center gap-2 flex-wrap mb-4">
                            <a href="#s2" class="badge bg-danger text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-lock me-1"></i>Cookies Essenciais</a>
                            <a href="#s3" class="badge bg-primary text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-sliders me-1"></i>Cookies Funcionais</a>
                            <a href="#s5" class="badge bg-success text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-gear me-1"></i>Gerir Cookies</a>
                            <a href="#s8" class="badge bg-warning text-dark py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-ban me-1"></i>Sem Publicidade</a>
                        </div>
                        <a href="#cookies-conteudo" class="btn btn-warning btn-lg mt-1 fw-bold smooth-scroll">
                            Ler a política <i class="fa-solid fa-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Conteúdo ──────────────────────────────────────────────────────── -->
        <section id="cookies-conteudo" class="py-6 bg-light-100">
            <div class="container" data-cue="fadeIn">

                <div class="action-buttons">
                    <a href="../../assets/docs/Politica-de-Cookies-WasomUpfy140226.pdf"
                        class="btn btn-outline-warning" download><i class="fa-solid fa-file-pdf me-2"></i>Baixar PDF</a>
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i
                            class="fa-solid fa-print me-2"></i>Imprimir</button>
                    <a href="privacy" class="btn btn-outline-secondary"><i
                            class="fa-solid fa-shield-halved me-2"></i>Privacidade</a>
                    <a href="terms" class="btn btn-outline-secondary"><i
                            class="fa-solid fa-file-contract me-2"></i>Termos de Uso</a>
                </div>

                <div class="terms-layout">

                    <!-- ── ÍNDICE ──────────────────────────────────────────── -->
                    <div class="terms-index d-none d-lg-block">
                        <h3><i class="fa-solid fa-cookie-bite me-2"></i>Índice</h3>
                        <ul>
                            <li><a href="#s1"><span class="num">1.</span>O que são Cookies?</a></li>
                            <li><a href="#s2"><span class="num">2.</span>Cookies Essenciais</a></li>
                            <li><a href="#s3"><span class="num">3.</span>Cookies Funcionais</a></li>
                            <li><a href="#s4"><span class="num">4.</span>Cookies de Segurança</a></li>
                            <li><a href="#s5"><span class="num">5.</span>Como Gerir os Cookies</a></li>
                            <li><a href="#s6"><span class="num">6.</span>Cookies de Terceiros</a></li>
                            <li><a href="#s7"><span class="num">7.</span>Local Storage e Session Storage</a></li>
                            <li><a href="#s8"><span class="num">8.</span>O que NÃO fazemos</a></li>
                            <li><a href="#s9"><span class="num">9.</span>Actualizações desta Política</a></li>
                            <li><a href="#s10"><span class="num">10.</span>Contacto</a></li>
                        </ul>
                        <hr class="idx-divider" />
                        <div class="text-center">
                            <small class="text-muted d-block mb-2">Outras políticas</small>
                            <a href="privacy" class="btn btn-sm btn-outline-secondary w-100 mb-1 rounded-pill"><i
                                    class="fa-solid fa-shield-halved me-1"></i> Privacidade</a>
                            <a href="terms" class="btn btn-sm btn-outline-secondary w-100 rounded-pill"><i
                                    class="fa-solid fa-file-contract me-1"></i> Termos de Uso</a>
                        </div>
                    </div>

                    <!-- ══ CONTEÚDO ════════════════════════════════════════ -->
                    <div class="terms-content">

                        <!-- INTRO BOX -->
                        <div class="term-box warning mb-3">
                            <strong><i class="fa-solid fa-circle-info me-2"></i>Resumo em 30 segundos</strong>
                            A <?php echo $siteName; ?> utiliza apenas cookies <strong>estritamente necessários</strong>
                            para o funcionamento da plataforma (sessão, segurança, preferências). <strong>Não
                                usamos publicidade, pixels de rastreamento nem cookies de redes sociais.</strong>
                            Pode gerir ou eliminar os cookies a qualquer momento nas configurações do seu navegador.
                        </div>

                        <!-- ── 1 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s1">
                            <h2><span class="sec-num">1</span>O que são Cookies?</h2>
                            <p>
                                Cookies são pequenos ficheiros de texto que um website armazena no seu dispositivo
                                (computador, telemóvel ou tablet) quando o visita. Estes ficheiros permitem que o
                                website "recorde" as suas preferências e informações de sessão entre páginas e visitas.
                            </p>
                            <p>
                                Existem diferentes tipos de cookies, classificados de acordo com a sua
                                <strong>origem</strong> (primários ou de terceiros), <strong>duração</strong>
                                (de sessão ou persistentes) e <strong>finalidade</strong> (essenciais, funcionais,
                                analíticos ou publicitários). A <?php echo $siteName; ?> utiliza <em>exclusivamente</em>
                                cookies primários de sessão, funcionais e de segurança — categorias detalhadas
                                nas secções seguintes.
                            </p>

                            <h3>1.1 Tipos de cookies por duração</h3>
                            <ul>
                                <li><strong>Cookies de sessão:</strong> existem apenas durante a sessão activa no
                                    browser — são eliminados automaticamente quando fecha o separador ou o navegador;
                                </li>
                                <li><strong>Cookies persistentes:</strong> permanecem armazenados por um período
                                    definido (dias, meses ou anos), mesmo após fechar o browser, até serem eliminados
                                    manualmente ou expirarem.</li>
                            </ul>

                            <h3>1.2 Tipos de cookies por origem</h3>
                            <ul>
                                <li><strong>Cookies primários (first-party):</strong> criados directamente pelo website
                                    que está a visitar — no caso da <?php echo $siteName; ?>, todos os cookies são
                                    primários;</li>
                                <li><strong>Cookies de terceiros (third-party):</strong> criados por domínios externos
                                    ao website. A <?php echo $siteName; ?> <strong>não utiliza</strong> cookies de
                                    terceiros para fins de rastreamento ou publicidade.</li>
                            </ul>
                        </div>

                        <!-- ── 2 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s2">
                            <h2><span class="sec-num">2</span>Cookies Essenciais</h2>
                            <p>
                                Os cookies essenciais são <strong>indispensáveis</strong> para o funcionamento
                                básico da plataforma. Sem eles, funcionalidades fundamentais como o login, a
                                navegação entre páginas protegidas e a segurança dos formulários deixam de
                                funcionar correctamente. Estes cookies não podem ser desactivados.
                            </p>

                            <div class="table-responsive">
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Tipo</th>
                                            <th>Finalidade</th>
                                            <th>Duração</th>
                                            <th>Obrigatório</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>PHPSESSID</code></td>
                                            <td><span class="cookie-badge badge-essential">Essencial</span></td>
                                            <td>Identificador único da sessão PHP — mantém o utilizador autenticado
                                                entre páginas</td>
                                            <td>Sessão (eliminado ao fechar o browser)</td>
                                            <td><i class="fa-solid fa-check text-success"></i> Sim</td>
                                        </tr>
                                        <tr>
                                            <td><code>remember_token</code></td>
                                            <td><span class="cookie-badge badge-essential">Essencial</span></td>
                                            <td>Token cifrado para a funcionalidade "Lembrar-me" — permite o login
                                                automático em visitas seguintes sem nova autenticação</td>
                                            <td>30 dias</td>
                                            <td>Apenas se activar "Lembrar-me"</td>
                                        </tr>
                                        <tr>
                                            <td><code>csrf_token</code></td>
                                            <td><span class="cookie-badge badge-security">Segurança</span></td>
                                            <td>Token de protecção CSRF — garante que os formulários submetidos são
                                                legítimos e originados na plataforma</td>
                                            <td>Sessão</td>
                                            <td><i class="fa-solid fa-check text-success"></i> Sim</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="term-box neutral">
                                <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Nota importante</strong>
                                Os cookies essenciais não recolhem qualquer informação pessoal identificável além do
                                necessário para manter a sessão activa e segura. A desactivação destes cookies no
                                browser impedirá o acesso à plataforma autenticada.
                            </div>
                        </div>

                        <!-- ── 3 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s3">
                            <h2><span class="sec-num">3</span>Cookies Funcionais</h2>
                            <p>
                                Os cookies funcionais guardam as preferências que o utilizador define na plataforma,
                                melhorando a experiência sem recolher informação pessoal identificável.
                                São opcionais e podem ser eliminados sem impacto na capacidade de login.
                            </p>

                            <div class="table-responsive">
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Tipo</th>
                                            <th>Finalidade</th>
                                            <th>Duração</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>wasomupfy_theme</code></td>
                                            <td><span class="cookie-badge badge-functional">Funcional</span></td>
                                            <td>Guarda a preferência de tema do site (claro, escuro ou automático) para
                                                que seja mantida em visitas futuras</td>
                                            <td>1 ano</td>
                                        </tr>
                                        <tr>
                                            <td><code>wasomupfy_lang</code></td>
                                            <td><span class="cookie-badge badge-functional">Funcional</span></td>
                                            <td>Guarda a preferência de idioma da interface seleccionada pelo utilizador
                                            </td>
                                            <td>1 ano</td>
                                        </tr>
                                        <tr>
                                            <td><code>wasomupfy_cookieconsent</code></td>
                                            <td><span class="cookie-badge badge-functional">Funcional</span></td>
                                            <td>Regista se o utilizador já leu e fechou o aviso de cookies, para não ser
                                                exibido novamente na mesma visita</td>
                                            <td>6 meses</td>
                                        </tr>
                                        <tr>
                                            <td><code>wasomupfy_sidebar</code></td>
                                            <td><span class="cookie-badge badge-functional">Funcional</span></td>
                                            <td>Guarda o estado do menu lateral do dashboard (expandido ou recolhido)
                                            </td>
                                            <td>Sessão</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ── 4 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s4">
                            <h2><span class="sec-num">4</span>Cookies de Segurança</h2>
                            <p>
                                Os cookies de segurança complementam os cookies essenciais na protecção da conta
                                do utilizador contra acessos não autorizados, ataques e comportamentos suspeitos.
                            </p>

                            <div class="table-responsive">
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Tipo</th>
                                            <th>Finalidade</th>
                                            <th>Duração</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>wasomupfy_2fa_verified</code></td>
                                            <td><span class="cookie-badge badge-security">Segurança</span></td>
                                            <td>Regista que o utilizador completou com sucesso a verificação 2FA nesta
                                                sessão, evitando novos pedidos de código OTP desnecessários</td>
                                            <td>Sessão</td>
                                        </tr>
                                        <tr>
                                            <td><code>wasomupfy_device_id</code></td>
                                            <td><span class="cookie-badge badge-security">Segurança</span></td>
                                            <td>Identificador anónimo do dispositivo — permite detectar logins de
                                                dispositivos novos ou não reconhecidos e alertar o utilizador</td>
                                            <td>90 dias</td>
                                        </tr>
                                        <tr>
                                            <td><code>wasomupfy_ratelimit</code></td>
                                            <td><span class="cookie-badge badge-security">Segurança</span></td>
                                            <td>Controla o número de tentativas de login ou envio de formulários num
                                                determinado período, prevenindo ataques de força bruta</td>
                                            <td>15 minutos</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="term-box success">
                                <strong><i class="fa-solid fa-shield-halved me-2"></i>Sem dados pessoais em cookies de
                                    segurança</strong>
                                Todos os valores armazenados nestes cookies são tokens aleatórios e cifrados — nunca
                                contêm o nome, e-mail, palavra-passe ou qualquer informação pessoal identificável do
                                utilizador.
                            </div>
                        </div>

                        <!-- ── 5 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s5">
                            <h2><span class="sec-num">5</span>Como Gerir e Eliminar os Cookies</h2>
                            <p>
                                O utilizador pode gerir, bloquear ou eliminar cookies a qualquer momento
                                directamente nas configurações do seu navegador. Abaixo encontra o guia
                                para os navegadores mais comuns:
                            </p>

                            <div class="browser-grid">
                                <div class="browser-card">
                                    <a href="https://support.google.com/chrome/answer/95647" target="_blank"
                                        rel="noopener noreferrer">
                                        <i class="fa-brands fa-chrome" style="color:#4285F4"></i>
                                        <span>Google Chrome</span>
                                    </a>
                                </div>
                                <div class="browser-card">
                                    <a href="https://support.mozilla.org/pt-PT/kb/cookies-informacao-que-os-websites-guardam-no-seu-computador"
                                        target="_blank" rel="noopener noreferrer">
                                        <i class="fa-brands fa-firefox-browser" style="color:#FF7139"></i>
                                        <span>Mozilla Firefox</span>
                                    </a>
                                </div>
                                <div class="browser-card">
                                    <a href="https://support.microsoft.com/pt-pt/microsoft-edge/eliminar-cookies-no-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09"
                                        target="_blank" rel="noopener noreferrer">
                                        <i class="fa-brands fa-edge" style="color:#0078D7"></i>
                                        <span>Microsoft Edge</span>
                                    </a>
                                </div>
                                <div class="browser-card">
                                    <a href="https://support.apple.com/pt-pt/guide/safari/sfri11471/mac" target="_blank"
                                        rel="noopener noreferrer">
                                        <i class="fa-brands fa-safari" style="color:#1C9CF6"></i>
                                        <span>Safari</span>
                                    </a>
                                </div>
                                <div class="browser-card">
                                    <a href="https://help.opera.com/en/latest/web-preferences/#cookies" target="_blank"
                                        rel="noopener noreferrer">
                                        <i class="fa-brands fa-opera" style="color:#FF1B2D"></i>
                                        <span>Opera</span>
                                    </a>
                                </div>
                                <div class="browser-card">
                                    <a href="https://www.samsung.com/uk/support/mobile-devices/clear-the-cache-on-your-samsung-phone/"
                                        target="_blank" rel="noopener noreferrer">
                                        <i class="fa-brands fa-android" style="color:#3DDC84"></i>
                                        <span>Android (Chrome)</span>
                                    </a>
                                </div>
                            </div>

                            <h3>5.1 Consequências da desactivação de cookies</h3>
                            <ul>
                                <li><strong>Eliminar <code>PHPSESSID</code>:</strong> a sessão de login é terminada e
                                    será necessário autenticar-se novamente;</li>
                                <li><strong>Eliminar <code>remember_token</code>:</strong> o login automático
                                    ("lembrar-me") é desactivado e terá de inserir as credenciais em cada visita;</li>
                                <li><strong>Eliminar <code>csrf_token</code>:</strong> os formulários da plataforma
                                    podem deixar de funcionar correctamente;</li>
                                <li><strong>Eliminar cookies funcionais:</strong> as preferências de tema e idioma serão
                                    redefinidas para os valores padrão a cada visita;</li>
                                <li><strong>Bloquear todos os cookies:</strong> a plataforma <strong>não funcionará
                                        correctamente</strong> — o acesso autenticado requer os cookies essenciais.</li>
                            </ul>

                            <h3>5.2 Eliminação directa na plataforma</h3>
                            <p>
                                Para terminar a sessão activa e eliminar todos os cookies da plataforma sem
                                aceder às configurações do browser, utilize a opção
                                <strong>Sair</strong> (logout) disponível no menu do utilizador.
                                Para revogar sessões activas em outros dispositivos, aceda a
                                <em>Definições → Segurança → Sessões Activas</em>.
                            </p>
                        </div>

                        <!-- ── 6 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s6">
                            <h2><span class="sec-num">6</span>Cookies de Terceiros</h2>
                            <p>
                                A <?php echo $siteName; ?> não instala nem autoriza cookies de terceiros para fins
                                de rastreamento, publicidade ou análise de comportamento dos utilizadores.
                            </p>
                            <p>
                                Contudo, a plataforma carrega alguns recursos externos de serviços de confiança
                                que podem, por sua iniciativa, armazenar cookies de acordo com as suas próprias
                                políticas. Estes serviços incluem:
                            </p>

                            <div class="table-responsive">
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>Serviço</th>
                                            <th>Finalidade</th>
                                            <th>Cookies de terceiros?</th>
                                            <th>Política</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Google Tag Manager</strong></td>
                                            <td>Gestão de scripts de análise internos</td>
                                            <td><span class="cookie-badge badge-analytics">Possível</span></td>
                                            <td><a href="https://policies.google.com/privacy" target="_blank"
                                                    rel="noopener noreferrer" class="small">Google Privacy</a></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Cloudflare</strong></td>
                                            <td>CDN e protecção contra DDoS</td>
                                            <td><span class="cookie-badge badge-security">Segurança</span></td>
                                            <td><a href="https://www.cloudflare.com/privacypolicy/" target="_blank"
                                                    rel="noopener noreferrer" class="small">Cloudflare Privacy</a></td>
                                        </tr>
                                        <tr>
                                            <td><strong>jQuery / Bootstrap CDN</strong></td>
                                            <td>Carregamento de bibliotecas de interface</td>
                                            <td><span class="cookie-badge badge-none">Não</span></td>
                                            <td>—</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Font Awesome CDN</strong></td>
                                            <td>Ícones visuais da interface</td>
                                            <td><span class="cookie-badge badge-none">Não</span></td>
                                            <td>—</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="term-box info">
                                <strong><i class="fa-solid fa-circle-info me-2"></i>Nota sobre serviços
                                    externos</strong>
                                A <?php echo $siteName; ?> não controla as cookies eventualmente instaladas por
                                estes serviços externos. O utilizador deve consultar as respectivas políticas de
                                privacidade para informação detalhada. A nossa integração com estes serviços é
                                limitada ao mínimo necessário para o funcionamento técnico da plataforma.
                            </div>
                        </div>

                        <!-- ── 7 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s7">
                            <h2><span class="sec-num">7</span>Local Storage e Session Storage</h2>
                            <p>
                                Para além dos cookies, a plataforma utiliza tecnologias de armazenamento local
                                do browser (<em>Web Storage API</em>) para guardar determinadas preferências
                                de interface e dados temporários de sessão:
                            </p>

                            <div class="table-responsive">
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>Chave</th>
                                            <th>Tipo</th>
                                            <th>Finalidade</th>
                                            <th>Duração</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>wu_theme</code></td>
                                            <td>localStorage</td>
                                            <td>Preferência de tema (claro/escuro/auto) — complementa o cookie de tema
                                            </td>
                                            <td>Permanente (até eliminação manual)</td>
                                        </tr>
                                        <tr>
                                            <td><code>wu_notif_read</code></td>
                                            <td>localStorage</td>
                                            <td>IDs de notificações já lidas, para evitar re-destacá-las</td>
                                            <td>Permanente (até eliminação manual)</td>
                                        </tr>
                                        <tr>
                                            <td><code>wu_sidebar_state</code></td>
                                            <td>localStorage</td>
                                            <td>Estado actual do menu lateral (expandido/colapsado)</td>
                                            <td>Permanente (até eliminação manual)</td>
                                        </tr>
                                        <tr>
                                            <td><code>wu_draft_release</code></td>
                                            <td>sessionStorage</td>
                                            <td>Rascunho temporário de um lançamento em criação — evita perda de dados
                                                em caso de actualização acidental da página</td>
                                            <td>Sessão (eliminado ao fechar o tab)</td>
                                        </tr>
                                        <tr>
                                            <td><code>wu_csrf_cache</code></td>
                                            <td>sessionStorage</td>
                                            <td>Cache do token CSRF para sincronização eficiente em chamadas AJAX</td>
                                            <td>Sessão</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p>
                                Ao contrário dos cookies, os dados de Local Storage e Session Storage
                                <strong>não são enviados automaticamente ao servidor</strong> em cada pedido HTTP —
                                existem apenas no browser do utilizador. Para eliminar estes dados, utilize a
                                opção <em>"Limpar dados de navegação"</em> do seu browser, incluindo
                                "Dados de sites armazenados".
                            </p>
                        </div>

                        <!-- ── 8 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s8">
                            <h2><span class="sec-num">8</span>O que NÃO Fazemos com Cookies</h2>
                            <p>
                                A <?php echo $siteName; ?> adopta uma postura de <strong>privacidade por
                                    defeito</strong>.
                                É importante ser claro sobre o que não fazemos:
                            </p>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <div class="term-box danger h-100 mb-0">
                                        <strong><i class="fa-solid fa-ban me-2"></i>Sem publicidade</strong>
                                        Não utilizamos cookies publicitários nem redes de anúncios. Não existe qualquer
                                        forma de publicidade baseada no comportamento do utilizador na plataforma.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="term-box danger h-100 mb-0">
                                        <strong><i class="fa-solid fa-ban me-2"></i>Sem pixels de rastreamento</strong>
                                        Não instalamos pixels do Facebook, TikTok, Google Ads ou qualquer outra
                                        plataforma publicitária que rastreie o utilizador entre websites.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="term-box danger h-100 mb-0">
                                        <strong><i class="fa-solid fa-ban me-2"></i>Sem partilha com
                                            anunciantes</strong>
                                        Os dados de comportamento do utilizador na plataforma não são partilhados com
                                        nenhum anunciante externo nem com empresas de análise de mercado.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="term-box danger h-100 mb-0">
                                        <strong><i class="fa-solid fa-ban me-2"></i>Sem perfis comportamentais
                                            externos</strong>
                                        Não construímos perfis de comportamento do utilizador para fins comerciais
                                        externos à plataforma. A análise interna é usada exclusivamente para melhorar os
                                        nossos próprios serviços.
                                    </div>
                                </div>
                            </div>

                            <div class="term-box success mt-3">
                                <strong><i class="fa-solid fa-check-circle me-2"></i>O nosso compromisso</strong>
                                A <?php echo $siteName; ?> é uma plataforma sem anúncios. A nossa receita provém
                                exclusivamente das subscrições dos artistas — não dos seus dados. Esta é a nossa
                                promessa e o fundamento do nosso modelo de negócio.
                            </div>
                        </div>

                        <!-- ── 9 ─────────────────────────────────────────── -->
                        <div class="term-section" id="s9">
                            <h2><span class="sec-num">9</span>Actualizações desta Política</h2>
                            <p>
                                A <?php echo $siteName; ?> reserva-se o direito de actualizar esta Política de
                                Cookies sempre que introduzir novas funcionalidades que impliquem novos cookies,
                                ou quando as boas práticas de privacidade assim o exijam.
                            </p>
                            <p>
                                Em caso de alterações relevantes — como a introdução de um novo tipo de cookie —
                                o utilizador será notificado com pelo menos <strong>15 dias de antecedência</strong>
                                através de:
                            </p>
                            <ul>
                                <li>Aviso no painel de notificações da plataforma;</li>
                                <li>E-mail para o endereço registado na conta (caso tenha uma conta activa);</li>
                                <li>Aviso em destaque no banner de cookies na primeira visita após a alteração.</li>
                            </ul>
                            <p>A data da última actualização é sempre indicada no topo desta página.</p>
                        </div>

                        <!-- ── 10 ────────────────────────────────────────── -->
                        <div class="term-section" id="s10">
                            <h2><span class="sec-num">10</span>Contacto</h2>
                            <p>
                                Se tiver dúvidas sobre esta Política de Cookies ou sobre as tecnologias de
                                armazenamento utilizadas pela <?php echo $siteName; ?>, pode contactar-nos através de:
                            </p>
                            <ul>
                                <li><strong>Pedido de suporte na plataforma:</strong> <a href="../support/support"
                                        class="fw-bold" style="color:#e67e22">Enviar pedido de suporte</a> — resposta em
                                    até 48 horas úteis;</li>
                                <?php if (cfg('info_email')): ?><li><strong>E-mail:</strong> <a
                                        href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                        style="color:#e67e22"><?php echo htmlspecialchars(cfg('info_email')); ?></a>;
                                </li><?php endif; ?>
                                <li><strong>Horário de atendimento:</strong> Segunda a Sexta, das 08h às 17h (WAT).</li>
                            </ul>

                            <div class="term-box warning" style="margin-top:1.5rem">
                                <strong><i class="fa-solid fa-cookie-bite me-2"></i>Sobre a gestão de
                                    consentimento</strong>
                                A plataforma <?php echo $siteName; ?> exibe um banner de cookies na primeira visita
                                onde o utilizador pode tomar conhecimento dos cookies utilizados. Como todos os cookies
                                são essenciais ou funcionais (sem fins publicitários), o acesso à plataforma implica
                                a aceitação dos cookies necessários ao seu funcionamento. Pode sempre gerir ou eliminar
                                esses cookies nas configurações do seu navegador conforme descrito na
                                <a href="#s5" class="fw-bold smooth-scroll">Secção 5</a>.
                            </div>
                        </div>

                        <!-- ── Cards de políticas relacionadas ─────────── -->
                        <div class="row g-3 mt-2 policy-cards">
                            <div class="col-md-6">
                                <a href="privacy" class="card border-0 shadow-sm h-100 p-3 text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-shape bg-light rounded-circle p-3"><i
                                                class="fa-solid fa-shield-halved text-wasomupfy fs-4"></i></div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Política de Privacidade</h6>
                                            <p class="small text-muted mb-0">Como recolhemos, usamos e protegemos os
                                                seus dados pessoais.</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="terms" class="card border-0 shadow-sm h-100 p-3 text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-shape bg-light rounded-circle p-3"><i
                                                class="fa-solid fa-file-contract text-wasomupfy fs-4"></i></div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Termos de Uso</h6>
                                            <p class="small text-muted mb-0">Condições de utilização, planos, royalties
                                                e regras da plataforma.</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                    </div><!-- /terms-content -->
                </div><!-- /terms-layout -->
            </div>
        </section>

    </main>

    <div class="divider-fade"></div>

    <!-- ══ Footer ══════════════════════════════════════════════════════════════ -->
    <footer class="bg-light-100 pt-7" role="contentinfo" aria-label="Rodapé do site">
        <div class="container">
            <div class="row align-items-center mb-7 border-bottom border-white-10 pb-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h3 class="fw-bold mb-1">Junte-se a +10.000 Artistas</h3>
                    <p class="lead text-muted mb-0">Receba dicas de marketing, novidades da indústria e ofertas
                        exclusivas.</p>
                </div>
                <div class="col-lg-6">
                    <form action="#" class="row g-2">
                        <div class="col-sm-8"><input type="email" class="form-control border-0 text-muted py-3"
                                autocomplete="email" required placeholder="Seu melhor e-mail" /></div>
                        <div class="col-sm-4"><button class="btn btn-wasomupfy w-100 py-3 fw-bold">Inscrever</button>
                        </div>
                    </form>
                </div>
            </div>
            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">
                    <div class="col-lg-3 col-12">
                        <a href="../../home" class="d-inline-block mb-4 navbar-brand"><img
                                src="../../assets/img/brand/wasomupfy_brand.png" alt="<?php echo $siteName; ?>"
                                width="65" class="img-logo" height="60" /></a>
                        <p class="lead text-muted small mb-4">Levamos a música angolana para o mundo. Distribuição
                            digital, marketing e gestão de carreira num só lugar.</p>
                        <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
                            <?php if (cfg('instagram_url')): ?><a
                                href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="Instagram"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2"><i
                                    class="fa-brands fa-instagram"></i></a><?php endif; ?>
                            <?php if (cfg('facebook_url')): ?><a
                                href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="Facebook"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2"><i
                                    class="fa-brands fa-facebook-f"></i></a><?php endif; ?>
                            <?php if (cfg('youtube_url')): ?><a
                                href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="YouTube"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2"><i
                                    class="fa-brands fa-youtube"></i></a><?php endif; ?>
                            <?php if (cfg('linkedin_url')): ?><a
                                href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="LinkedIn"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2"><i
                                    class="fa-brands fa-linkedin-in"></i></a><?php endif; ?>
                            <?php if ($whatsNum): ?><a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="WhatsApp"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2"><i
                                    class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Empresa</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="../../about"
                                    class="text-reset text-decoration-none hover-white">Sobre</a></li>
                            <li class="mb-2"><a href="../../about#nossamarca"
                                    class="text-reset text-decoration-none hover-white">A nossa marca</a></li>
                            <li class="mb-2"><a href="../../plan/all-plans"
                                    class="text-reset text-decoration-none hover-white">Planos</a></li>
                            <li class="mb-2"><a href="../../page/services/customized-services"
                                    class="text-reset text-decoration-none hover-white">Serviços Premium</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Suporte</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento</a></li>
                            <li class="mb-2"><a href="../support/help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="../../contact"
                                    class="text-reset text-decoration-none hover-white">Contacta-nos</a></li>
                            <?php if ($whatsNum): ?><li class="mb-2"><a href="https://wa.me/<?php echo $whatsNum; ?>"
                                    class="text-reset text-decoration-none hover-white">WhatsApp</a></li><?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-12">
                        <h3 class="fw-bold mb-3">Contacto</h3>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-3"><span><?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?> —
                                    <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?></span></li>
                            <?php if (cfg('info_email')): ?><li class="mb-3"><a
                                    href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                    class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('info_email')); ?></a>
                            </li><?php endif; ?>
                            <?php if (cfg('support_email')): ?><li class="mb-3"><a
                                    href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                    class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                            </li><?php endif; ?>
                            <li><span>Seg — Sex: 08h às 17h</span></li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="row py-4 mt-6 border-top border-white-10 align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. Todos os
                        direitos reservados.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 small">
                        <li class="list-inline-item"><a href="privacy" class="text-reset text-decoration-none">Política
                                de Privacidade</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="terms" class="text-reset text-decoration-none">Termos de
                                Uso</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="cookies" class="text-reset text-decoration-none fw-bold"
                                style="color:#f39c12">Cookies</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <div class="btn-scroll-top"><svg class="progress-square svg-content" width="100%" height="100%" viewBox="0 0 40 40">
            <path
                d="M8 1H32C35.866 1 39 4.13401 39 8V32C39 35.866 35.866 39 32 39H8C4.13401 39 1 35.866 1 32V8C1 4.13401 4.13401 1 8 1Z" />
        </svg></div>
    <div class="customizer_1">
        <div class="position-absolute end-0 bottom-0 m-4 fixed">
            <div class="dropdown"><button class="btn btn-wasomupfy rounded-circle d-flex align-items-center"
                    type="button" data-bs-toggle="dropdown" aria-label="Toggle theme"><i
                        class="fa-solid fa-circle-half-stroke"></i><span
                        class="visually-hidden bs-theme-text">Tema</span></button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><button type="button" class="dropdown-item d-flex align-items-center"
                            data-bs-theme-value="light"><i class="fa-solid fa-sun"></i><span
                                class="ms-2">Claro</span></button></li>
                    <li><button type="button" class="dropdown-item d-flex align-items-center"
                            data-bs-theme-value="dark"><i class="fa-solid fa-moon"></i><span
                                class="ms-2">Escuro</span></button></li>
                    <li><button type="button" class="dropdown-item d-flex align-items-center active"
                            data-bs-theme-value="auto"><i class="fa-solid fa-display"></i><span
                                class="ms-2">Sistema</span></button></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal Feedback -->
    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-labelledby="modalFeedbackLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-wasomupfy text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalFeedbackLabel"><i class="fa-solid fa-bullhorn me-2"></i>A
                        sua opinião importa!</h5><button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">Como tem sido a sua experiência com a
                        <strong><?php echo $siteName; ?></strong>?
                    </p>
                    <div id="feedback-modal-msg" class="alert d-none mb-3" role="alert"></div>
                    <form id="formFeedback" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_page); ?>" />
                        <div class="mb-3"><label class="form-label fw-semibold text-dark">Seu Nome</label><input
                                type="text" class="form-control" name="name_fb" placeholder="Ex: André Wasom"
                                required /></div>
                        <div class="mb-3"><label class="form-label fw-semibold text-dark">Assunto</label><select
                                class="form-select" name="subject_fb">
                                <option>Sugestão de melhoria</option>
                                <option>Elogio</option>
                                <option>Relatar um problema</option>
                                <option>Outros</option>
                            </select></div>
                        <div class="mb-3"><label class="form-label fw-semibold text-dark">A sua
                                Mensagem</label><textarea class="form-control" rows="4" name="message_fb"
                                placeholder="Conte-nos em detalhes..." required></textarea></div>
                        <div class="d-grid mt-4"><button type="submit" class="btn btn-wasomupfy btn-lg"
                                id="btn-feedback-modal">Enviar Feedback <i
                                    class="fa-solid fa-paper-plane ms-2"></i></button></div>
                    </form>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4"><small class="text-muted">A
                        <?php echo $siteName; ?> agradece a sua parceria!</small></div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/headhesive@1.2.4/dist/headhesive.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/vendors/color-modes.js"></script>
    <script src="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/vendors/scrollcue.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    <script src="https://unpkg.com/in-view@0.6.1/dist/in-view.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sticky-kit/1.1.3/sticky-kit.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/cookies.js"></script>
    <script>
    feather.replace({
        width: "1em",
        height: "1em"
    });
    </script>
    <script>
    !(function(e, t, a, n, g) {
        (e[n] = e[n] || []), e[n].push({
            "gtm.start": new Date().getTime(),
            event: "gtm.js"
        });
        var m = t.getElementsByTagName(a)[0],
            r = t.createElement(a);
        (r.async = !0), (r.src = "https://www.googletagmanager.com/gtm.js?id=GTM-MF4DZVH"), m.parentNode
            .insertBefore(r, m);
    })(window, document, "script", "dataLayer");
    </script>
    <script>
    (function() {
        /* ── Progress bar ── */
        var bar = document.getElementById('reading-progress');
        window.addEventListener('scroll', function() {
            var st = document.documentElement.scrollTop || document.body.scrollTop,
                sh = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            if (bar) bar.style.width = (sh > 0 ? (st / sh) * 100 : 0) + '%';
        }, {
            passive: true
        });

        /* ── Scroll spy ── */
        var sections = document.querySelectorAll('.term-section[id]'),
            links = document.querySelectorAll('.terms-index a[href^="#"]');

        function spy() {
            var sy = window.pageYOffset + 120,
                cur = '';
            sections.forEach(function(s) {
                if (s.offsetTop <= sy) cur = s.id;
            });
            links.forEach(function(a) {
                a.classList.toggle('active', a.getAttribute('href') === '#' + cur);
            });
        }
        window.addEventListener('scroll', spy, {
            passive: true
        });
        spy();

        /* ── Smooth scroll ── */
        document.querySelectorAll('.terms-index a[href^="#"], .smooth-scroll').forEach(function(a) {
            a.addEventListener('click', function(e) {
                var t = document.querySelector(this.getAttribute('href'));
                if (t) {
                    e.preventDefault();
                    window.scrollTo({
                        top: t.offsetTop - 90,
                        behavior: 'smooth'
                    });
                }
            });
        });

        /* ── Feedback modal ── */
        function syncCsrf(token) {
            if (!token) return;
            document.querySelectorAll('[name="csrf_token"]').forEach(function(el) {
                el.value = token;
            });
        }
        var fm = document.getElementById('formFeedback');
        if (fm) {
            fm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!fm.checkValidity()) {
                    fm.classList.add('was-validated');
                    return;
                }
                var btn = document.getElementById('btn-feedback-modal'),
                    msg = document.getElementById('feedback-modal-msg'),
                    base = document.body.dataset.basePath || '../..';
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';
                fetch(base + '/ajax/feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf: fm.querySelector('[name="csrf_token"]').value,
                            name: fm.querySelector('[name="name_fb"]').value.trim(),
                            subject: fm.querySelector('[name="subject_fb"]').value.trim(),
                            message: fm.querySelector('[name="message_fb"]').value.trim(),
                            page: window.location.pathname
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    }).then(function(d) {
                        msg.className = 'alert ' + (d.success ? 'alert-success' : 'alert-danger');
                        msg.textContent = d.message || (d.success ? 'Obrigado!' : 'Erro.');
                        msg.classList.remove('d-none');
                        if (d.new_csrf) syncCsrf(d.new_csrf);
                        if (d.success) {
                            fm.reset();
                            setTimeout(function() {
                                var m = bootstrap.Modal.getInstance(document.getElementById(
                                    'modalFeedback'));
                                if (m) m.hide();
                            }, 2500);
                        }
                    })
                    .catch(function() {
                        msg.className = 'alert alert-danger';
                        msg.textContent = 'Erro de ligação. Tenta novamente.';
                        msg.classList.remove('d-none');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.innerHTML = 'Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>';
                    });
            });
        }
    })();
    </script>
</body>

</html>