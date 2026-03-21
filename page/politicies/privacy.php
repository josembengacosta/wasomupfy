<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Política de Privacidade
// Arquivo: page/politicies/privacy.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('privacy');
trackVisitor('/page/politicies/privacy', 'Política de Privacidade — Wasom Upfy');

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
        content="<?php echo $siteName; ?>, Política de Privacidade, dados pessoais, LGPD, Angola, privacidade" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Política de Privacidade" />
    <meta property="og:description"
        content="Conheça como a <?php echo $siteName; ?> recolhe, utiliza, armazena e protege os seus dados pessoais. Transparência total sobre o tratamento de dados dos nossos artistas." />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/politicies/privacy" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Política de Privacidade</title>
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
        #reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #ff009d, #9b59b6);
            z-index: 9999;
            transition: width .1s linear
        }

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
            color: #ff009d;
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 2px solid rgba(255, 0, 157, .15)
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
            background: rgba(255, 0, 157, .08);
            color: #ff009d
        }

        .terms-index .num {
            flex-shrink: 0;
            font-weight: 700;
            color: #ff009d;
            min-width: 22px
        }

        .terms-index .idx-divider {
            margin: .6rem 0;
            border-color: rgba(255, 0, 157, .12)
        }

        .terms-content {
            flex: 1;
            min-width: 0
        }

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
            border-left-color: rgba(255, 0, 157, .35)
        }

        .term-section:target {
            border-left-color: #ff009d
        }

        .term-section h2 {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: #ff009d;
            margin-bottom: 1rem;
            padding-bottom: .6rem;
            border-bottom: 1px solid rgba(255, 0, 157, .12)
        }

        .sec-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #ff009d, #9b59b6);
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
            background: rgba(255, 193, 7, .1);
            border-left: 4px solid #ffc107
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

        .term-box.purple {
            background: rgba(155, 89, 182, .07);
            border-left: 4px solid #9b59b6
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: .75rem;
            margin: 1rem 0
        }

        .data-card {
            background: rgba(255, 0, 157, .04);
            border: 1px solid rgba(255, 0, 157, .1);
            border-radius: 10px;
            padding: .85rem 1rem;
            display: flex;
            gap: .6rem;
            align-items: flex-start
        }

        .data-card i {
            color: #ff009d;
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-top: .1rem
        }

        .data-card p {
            margin: 0;
            font-size: .88rem;
            line-height: 1.5
        }

        .rights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: .75rem;
            margin: 1rem 0
        }

        .right-card {
            background: var(--bs-body-bg, #fff);
            border-radius: 10px;
            box-shadow: 0 1px 8px rgba(0, 0, 0, .06);
            padding: 1rem;
            text-align: center;
            border-top: 3px solid #ff009d
        }

        .right-card i {
            font-size: 1.5rem;
            color: #ff009d;
            margin-bottom: .5rem;
            display: block
        }

        .right-card h6 {
            font-size: .88rem;
            font-weight: 700;
            margin-bottom: .25rem
        }

        .right-card p {
            font-size: .8rem;
            color: var(--bs-secondary-color, #666);
            margin: 0
        }

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

        .policy-cards .card {
            border-radius: 12px;
            transition: transform .2s, box-shadow .2s;
            text-decoration: none
        }

        .policy-cards .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(255, 0, 157, .15) !important
        }

        [data-bs-theme="dark"] .term-section,
        [data-bs-theme="dark"] .terms-index {
            background: var(--bs-body-bg);
            box-shadow: 0 2px 14px rgba(0, 0, 0, .25)
        }

        [data-bs-theme="dark"] .data-card {
            background: rgba(255, 0, 157, .06)
        }

        [data-bs-theme="dark"] .right-card {
            background: var(--bs-body-bg)
        }

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

            .data-grid,
            .rights-grid {
                grid-template-columns: 1fr 1fr
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
                                                <p class="mb-0 fs-6">Todos os nossos planos</p>
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
                            <a href="/wasomupfy/login" class="btn btn-secondary mx-2">Entrar <i
                                    data-feather="log-in"></i></a>
                            <?php if ($canRegister): ?><a href="/wasomupfy/register"
                                    class="btn btn-wasomupfy">Inscreva-se</a><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>

        <!-- ── Hero ─────────────────────────────────────────────────────────── -->
        <section class="privacy-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/privacy.png"
                alt="Política de Privacidade <?php echo $siteName; ?>" loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Política de Privacidade</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-3 text-white-stable fw-bold">Política de Privacidade</h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Comprometemo-nos com a total transparência no tratamento dos seus dados pessoais.
                            Saiba exactamente o que recolhemos, para que usamos e como protegemos as suas informações.
                        </p>
                        <p class="text-white-stable small opacity-80 mb-4">
                            <i class="fa-regular fa-calendar me-2"></i>Última actualização: 14 de Fevereiro de 2026
                            &nbsp;·&nbsp; <i class="fa-regular fa-file-lines me-2"></i>13 secções
                            &nbsp;·&nbsp; <i class="fa-regular fa-clock me-2"></i>Leitura: ~10 minutos
                        </p>
                        <div class="terms-badges d-flex justify-content-center gap-2 flex-wrap mb-4">
                            <a href="#s1" class="badge bg-primary text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-database me-1"></i>Dados Recolhidos</a>
                            <a href="#s7" class="badge bg-success text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-user-shield me-1"></i>Os seus Direitos</a>
                            <a href="#s4" class="badge bg-wasomupfy text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-lock me-1"></i>Segurança dos Dados</a>
                            <a href="#s5" class="badge bg-info text-dark py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-share-nodes me-1"></i>Partilha de Dados</a>
                        </div>
                        <a href="#privacy-conteudo" class="btn btn-wasomupfy btn-lg mt-1 smooth-scroll">
                            Ler a política <i class="fa-solid fa-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Conteúdo ──────────────────────────────────────────────────────── -->
        <section id="privacy-conteudo" class="py-6 bg-light-100">
            <div class="container" data-cue="fadeIn">

                <div class="action-buttons">
                    <a href="privacy.pdf" class="btn btn-outline-wasomupfy" download><i
                            class="fa-solid fa-file-pdf me-2"></i>Baixar PDF</a>
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i
                            class="fa-solid fa-print me-2"></i>Imprimir</button>
                    <a href="terms" class="btn btn-outline-secondary"><i
                            class="fa-solid fa-file-contract me-2"></i>Termos de Uso</a>
                    <a href="cookies" class="btn btn-outline-secondary"><i
                            class="fa-solid fa-cookie-bite me-2"></i>Cookies</a>
                </div>

                <div class="terms-layout">

                    <!-- ── ÍNDICE ──────────────────────────────────────────── -->
                    <div class="terms-index d-none d-lg-block">
                        <h3><i class="bi bi-list-ol me-2"></i>Índice</h3>
                        <ul>
                            <li><a href="#s1"><span class="num">1.</span>Dados que Recolhemos</a></li>
                            <li><a href="#s2"><span class="num">2.</span>Finalidade do Tratamento</a></li>
                            <li><a href="#s3"><span class="num">3.</span>Base Legal</a></li>
                            <li><a href="#s4"><span class="num">4.</span>Segurança e Armazenamento</a></li>
                            <li><a href="#s5"><span class="num">5.</span>Partilha de Dados</a></li>
                            <li><a href="#s6"><span class="num">6.</span>Dados Financeiros e Royalties</a></li>
                            <li><a href="#s7"><span class="num">7.</span>Os seus Direitos</a></li>
                            <li><a href="#s8"><span class="num">8.</span>Segurança da Conta</a></li>
                            <li><a href="#s9"><span class="num">9.</span>Retenção e Eliminação</a></li>
                            <li><a href="#s10"><span class="num">10.</span>Menores de Idade</a></li>
                            <li><a href="#s11"><span class="num">11.</span>Dados de Terceiros</a></li>
                            <li><a href="#s12"><span class="num">12.</span>Alterações à Política</a></li>
                            <li><a href="#s13"><span class="num">13.</span>Contacto</a></li>
                        </ul>
                        <hr class="idx-divider" />
                        <div class="text-center">
                            <small class="text-muted d-block mb-2">Outras políticas</small>
                            <a href="terms" class="btn btn-sm btn-outline-wasomupfy w-100 mb-1 rounded-pill"><i
                                    class="fa-solid fa-file-contract me-1"></i> Termos de Uso</a>
                            <a href="cookies" class="btn btn-sm btn-outline-secondary w-100 rounded-pill"><i
                                    class="fa-solid fa-cookie-bite me-1"></i> Cookies</a>
                        </div>
                    </div>

                    <!-- ══ CONTEÚDO ════════════════════════════════════════ -->
                    <div class="terms-content">

                        <!-- INTRO -->
                        <div class="term-box purple mb-3">
                            <strong><i class="fa-solid fa-shield-halved me-2"></i>O nosso compromisso com a sua
                                privacidade</strong>
                            A <?php echo $siteName; ?> trata a privacidade dos seus dados com a máxima seriedade.
                            Esta política descreve, de forma clara e transparente, como recolhemos, utilizamos,
                            armazenamos e protegemos as suas informações pessoais, em conformidade com a legislação
                            angolana aplicável (Lei n.º 22/22 de 3 de Agosto — Lei de Protecção de Dados Pessoais).
                        </div>

                        <!-- 1 -->
                        <div class="term-section" id="s1">
                            <h2><span class="sec-num">1</span>Dados que Recolhemos</h2>
                            <p>A <?php echo $siteName; ?> recolhe os seguintes dados pessoais dos utilizadores para
                                garantir a segurança, qualidade e eficiência dos nossos serviços:</p>

                            <div class="data-grid">
                                <div class="data-card"><i class="fa-solid fa-user"></i>
                                    <p><strong>Identidade</strong><br>Nome completo, nome artístico, foto de perfil</p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-envelope"></i>
                                    <p><strong>Contacto</strong><br>Endereço de e-mail principal e alternativo</p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-phone"></i>
                                    <p><strong>Telefone</strong><br>Número de telefone (opcional, para suporte)</p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-globe"></i>
                                    <p><strong>Técnicos</strong><br>Endereço IP, navegador, sistema operativo, device
                                    </p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-building-columns"></i>
                                    <p><strong>Bancários</strong><br>IBAN, nome do banco, dados para levantamentos</p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-music"></i>
                                    <p><strong>Artísticos</strong><br>Músicas, capas, metadados, UPC, ISRC, streams</p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-chart-line"></i>
                                    <p><strong>Analíticos</strong><br>Estatísticas de acesso, comportamento na
                                        plataforma</p>
                                </div>
                                <div class="data-card"><i class="fa-solid fa-clock-rotate-left"></i>
                                    <p><strong>Histórico</strong><br>Lançamentos, transacções, levantamentos, tickets
                                    </p>
                                </div>
                            </div>

                            <h3>1.1 Como os dados são recolhidos</h3>
                            <ul>
                                <li><strong>Directamente pelo utilizador:</strong> no momento do registo, ao actualizar
                                    o perfil, ao submeter lançamentos, ao efectuar levantamentos ou ao contactar o
                                    suporte;</li>
                                <li><strong>Automaticamente:</strong> ao utilizar a plataforma, através de cookies,
                                    registos de acesso (logs) e tecnologias de análise;</li>
                                <li><strong>De terceiros:</strong> informações de streams e receitas provenientes das
                                    plataformas de distribuição parceiras (Spotify, Apple Music, Deezer, etc.).</li>
                            </ul>
                        </div>

                        <!-- 2 -->
                        <div class="term-section" id="s2">
                            <h2><span class="sec-num">2</span>Finalidade do Tratamento de Dados</h2>
                            <p>Os dados pessoais recolhidos são utilizados exclusivamente para as seguintes finalidades:
                            </p>
                            <ul>
                                <li><strong>Prestação dos serviços contratados:</strong> criação e gestão de conta,
                                    distribuição musical, processamento de royalties e levantamentos;</li>
                                <li><strong>Comunicações relacionadas com o serviço:</strong> e-mails de verificação de
                                    conta, notificações de lançamento aprovado/rejeitado, alertas de pagamento, avisos
                                    de expiração de plano e notificações de segurança (ex.: novo login detectado);</li>
                                <li><strong>Segurança e prevenção de fraude:</strong> detecção de acessos não
                                    autorizados, monitorização de actividades suspeitas, prevenção de manipulação de
                                    streams e bloqueio de contas duplicadas;</li>
                                <li><strong>Melhoria contínua da plataforma:</strong> análise anónima de padrões de uso
                                    para melhorar a experiência do utilizador, identificar erros e desenvolver novas
                                    funcionalidades;</li>
                                <li><strong>Cumprimento de obrigações legais:</strong> conservação de registos fiscais,
                                    resposta a ordens judiciais e cumprimento de legislação aplicável;</li>
                                <li><strong>Suporte ao cliente:</strong> resolução de tickets de suporte, identificação
                                    do utilizador e histórico de interacções;</li>
                                <li><strong>Comunicações de marketing (opcional):</strong> envio de newsletters,
                                    novidades da plataforma e promoções, apenas para utilizadores que deram o seu
                                    consentimento expresso.</li>
                            </ul>
                            <div class="term-box info">
                                <strong><i class="fa-solid fa-hand me-2"></i>Sem fins publicitários externos</strong>
                                A <?php echo $siteName; ?> <strong>não vende, não aluga e não cede</strong> os seus
                                dados pessoais a terceiros para fins publicitários ou de marketing externo. Os seus
                                dados servem exclusivamente para os fins aqui descritos.
                            </div>
                        </div>

                        <!-- 3 -->
                        <div class="term-section" id="s3">
                            <h2><span class="sec-num">3</span>Base Legal para o Tratamento de Dados</h2>
                            <p>O tratamento dos dados pessoais dos utilizadores da <?php echo $siteName; ?> assenta nas
                                seguintes bases legais, nos termos da Lei n.º 22/22 de 3 de Agosto:</p>
                            <ul>
                                <li><strong>Execução do contrato:</strong> o tratamento é necessário para a execução dos
                                    serviços acordados no momento do registo e da aceitação dos Termos de Uso;</li>
                                <li><strong>Consentimento:</strong> para comunicações de marketing e newsletter, apenas
                                    quando o utilizador dá consentimento explícito — revogável a qualquer momento;</li>
                                <li><strong>Interesse legítimo:</strong> para fins de segurança da plataforma, prevenção
                                    de fraude e melhoria dos serviços, desde que não prevaleçam os direitos fundamentais
                                    do utilizador;</li>
                                <li><strong>Obrigação legal:</strong> quando a conservação ou partilha de dados for
                                    exigida por lei ou por ordem de autoridade competente.</li>
                            </ul>
                        </div>

                        <!-- 4 -->
                        <div class="term-section" id="s4">
                            <h2><span class="sec-num">4</span>Segurança e Armazenamento dos Dados</h2>
                            <p>A <?php echo $siteName; ?> implementa medidas técnicas e organizativas para proteger os
                                seus dados pessoais contra acesso não autorizado, perda, alteração ou destruição:</p>

                            <h3>4.1 Medidas Técnicas</h3>
                            <ul>
                                <li><strong>Comunicações cifradas:</strong> toda a comunicação entre o seu navegador e a
                                    plataforma é efectuada via <strong>HTTPS/TLS</strong>;</li>
                                <li><strong>Palavras-passe com hash:</strong> as palavras-passe são armazenadas com
                                    algoritmo de hash seguro (bcrypt) — nunca em texto simples, nunca visíveis pela
                                    equipa;</li>
                                <li><strong>Autenticação em dois factores (2FA):</strong> disponível e altamente
                                    recomendada para todas as contas;</li>
                                <li><strong>Detecção de IP suspeito:</strong> sistema automatizado de bloqueio de IPs
                                    com comportamento anómalo;</li>
                                <li><strong>Backups regulares:</strong> cópias de segurança encriptadas dos dados
                                    armazenados;</li>
                                <li><strong>Acesso restrito:</strong> apenas colaboradores da <?php echo $siteName; ?>
                                    com necessidade operacional específica têm acesso a dados pessoais, com autenticação
                                    individual obrigatória.</li>
                            </ul>

                            <h3>4.2 Localização dos Dados</h3>
                            <p>Os dados são armazenados em servidores seguros. A <?php echo $siteName; ?> compromete-se
                                a garantir que qualquer transferência de dados para fora de Angola obedece às
                                salvaguardas exigidas pela legislação aplicável.</p>

                            <div class="term-box warning">
                                <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Aviso Importante</strong>
                                Embora adoptemos as melhores práticas de segurança disponíveis, nenhum sistema é
                                absolutamente imune a riscos. Em caso de violação de segurança que afecte os seus dados,
                                seremos notificados o mais rapidamente possível e tomaremos as medidas necessárias.
                            </div>
                        </div>

                        <!-- 5 -->
                        <div class="term-section" id="s5">
                            <h2><span class="sec-num">5</span>Partilha e Divulgação de Dados</h2>
                            <p>A <?php echo $siteName; ?> <strong>não partilha</strong> os seus dados pessoais com
                                terceiros para fins comerciais. A partilha de dados pode ocorrer apenas nos seguintes
                                cenários limitados:</p>

                            <h3>5.1 Plataformas de Distribuição</h3>
                            <p>Os <strong>metadados dos lançamentos</strong> (nome do artista, título da música, UPC,
                                ISRC, género, etc.) são partilhados com as plataformas de streaming parceiras (Spotify,
                                Apple Music, Deezer, TikTok, etc.) exclusivamente para fins de distribuição e indexação.
                                Nenhum dado financeiro ou pessoal identificável do utilizador é transmitido a estas
                                plataformas.</p>

                            <h3>5.2 Obrigação Legal</h3>
                            <p>A <?php echo $siteName; ?> pode divulgar dados pessoais quando tal seja exigido por lei,
                                por ordem judicial ou por pedido fundamentado de autoridade pública competente angolana.
                                Em tais casos, o utilizador será notificado sempre que legalmente possível.</p>

                            <h3>5.3 Colaboradores com Acesso Autorizado</h3>
                            <p>O titular da conta pode convidar colaboradores (Visualizador, Editor ou Administrador)
                                para aceder a partes da conta. O utilizador é responsável pela gestão e revogação dessas
                                permissões em <em>Definições → Colaboradores</em>.</p>

                            <h3>5.4 Prestadores de Serviços Técnicos</h3>
                            <p>A <?php echo $siteName; ?> pode utilizar fornecedores técnicos de confiança (ex.:
                                serviços de e-mail transaccional, análise de segurança) que processam dados em nosso
                                nome, sob contrato de confidencialidade e com acesso mínimo necessário.</p>
                        </div>

                        <!-- 6 -->
                        <div class="term-section" id="s6">
                            <h2><span class="sec-num">6</span>Dados Financeiros e Royalties</h2>
                            <p>Os dados financeiros dos utilizadores são tratados com nível máximo de confidencialidade:
                            </p>
                            <ul>
                                <li>Os dados bancários (IBAN, número de conta, banco) são utilizados
                                    <strong>exclusivamente</strong> para processar levantamentos solicitados pelo
                                    utilizador;
                                </li>
                                <li>Nenhum colaborador da conta (Visualizador, Editor) tem acesso ao IBAN ou aos dados
                                    bancários completos do titular — apenas o titular e o Administrador têm acesso
                                    parcial;</li>
                                <li>Os dados de streams, receitas e royalties de cada utilizador são estritamente
                                    privados e visíveis apenas pelo titular e pelos colaboradores autorizados por ele;
                                </li>
                                <li>Os comprovantes de pagamento submetidos pelo utilizador são conservados internamente
                                    para efeitos de verificação e auditoria, com acesso restrito à equipa
                                    administrativa;</li>
                                <li>O histórico de transacções financeiras da plataforma é conservado por um período
                                    mínimo de <strong>5 anos</strong> para cumprimento de obrigações fiscais e legais
                                    angolanas.</li>
                            </ul>
                            <div class="term-box success">
                                <strong><i class="bi bi-check-circle-fill me-2"></i>Transparência total</strong>
                                O utilizador pode exportar em qualquer momento o histórico completo das suas transacções
                                e royalties em formato CSV através de <em>Finanças → Exportar Relatório</em>.
                            </div>
                        </div>

                        <!-- 7 -->
                        <div class="term-section" id="s7">
                            <h2><span class="sec-num">7</span>Os seus Direitos sobre os Dados</h2>
                            <p>Nos termos da Lei n.º 22/22 de 3 de Agosto (Lei de Protecção de Dados Pessoais de
                                Angola), o utilizador tem os seguintes direitos:</p>

                            <div class="rights-grid">
                                <div class="right-card">
                                    <i class="fa-solid fa-eye"></i>
                                    <h6>Direito de Acesso</h6>
                                    <p>Saber quais os dados que temos sobre si e como os utilizamos.</p>
                                </div>
                                <div class="right-card">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <h6>Direito de Rectificação</h6>
                                    <p>Corrigir dados incorrectos ou incompletos a qualquer momento.</p>
                                </div>
                                <div class="right-card">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <h6>Direito de Apagamento</h6>
                                    <p>Solicitar a eliminação dos seus dados pessoais ("direito a ser esquecido").</p>
                                </div>
                                <div class="right-card">
                                    <i class="fa-solid fa-hand"></i>
                                    <h6>Direito de Oposição</h6>
                                    <p>Opor-se ao tratamento dos seus dados para fins de marketing.</p>
                                </div>
                                <div class="right-card">
                                    <i class="fa-solid fa-file-export"></i>
                                    <h6>Direito à Portabilidade</h6>
                                    <p>Receber os seus dados num formato estruturado e legível por máquina.</p>
                                </div>
                                <div class="right-card">
                                    <i class="fa-solid fa-pause"></i>
                                    <h6>Direito de Limitação</h6>
                                    <p>Solicitar a suspensão temporária do tratamento em casos específicos.</p>
                                </div>
                            </div>

                            <h3>Como exercer os seus direitos</h3>
                            <p>Para exercer qualquer um dos direitos acima, o utilizador pode:</p>
                            <ul>
                                <li>Aceder directamente a <em>Definições → Conta</em> para actualizar, exportar ou
                                    eliminar os seus dados;</li>
                                <li>Enviar um <a href="../support/support" class="text-wasomupfy fw-bold">pedido de
                                        suporte</a> com o assunto "Protecção de Dados — [direito pretendido]";</li>
                                <?php if (cfg('info_email')): ?><li>Contactar-nos por e-mail: <a
                                            href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                            class="text-wasomupfy"><?php echo htmlspecialchars(cfg('info_email')); ?></a>.
                                    </li><?php endif; ?>
                            </ul>
                            <p>Os pedidos serão respondidos no prazo máximo de <strong>30 dias úteis</strong> a contar
                                da recepção. Em casos complexos, este prazo pode ser prorrogado por mais 30 dias, com
                                notificação ao utilizador.</p>
                        </div>

                        <!-- 8 -->
                        <div class="term-section" id="s8">
                            <h2><span class="sec-num">8</span>Segurança da Conta — Responsabilidades</h2>
                            <p>A segurança da conta é uma responsabilidade partilhada entre a <?php echo $siteName; ?> e
                                o utilizador:</p>

                            <h3>8.1 Responsabilidade da Plataforma</h3>
                            <ul>
                                <li>Manter a infraestrutura técnica actualizada e segura;</li>
                                <li>Detectar e bloquear automaticamente acessos suspeitos;</li>
                                <li>Notificar o utilizador em caso de acesso de novo dispositivo ou localização;</li>
                                <li>Nunca solicitar a palavra-passe do utilizador por e-mail, chat ou telefone.</li>
                            </ul>

                            <h3>8.2 Responsabilidade do Utilizador</h3>
                            <ul>
                                <li>Utilizar uma palavra-passe forte e única (mínimo 8 caracteres, com letras, números e
                                    símbolos);</li>
                                <li>Activar a autenticação em dois factores (2FA) em <em>Definições → Segurança</em>;
                                </li>
                                <li>Nunca partilhar credenciais de acesso com terceiros;</li>
                                <li>Fazer sessão de saída (<em>logout</em>) após utilizar dispositivos partilhados ou
                                    públicos;</li>
                                <li>Notificar imediatamente o <a href="../support/support"
                                        class="text-wasomupfy fw-bold">suporte</a> em caso de suspeita de acesso não
                                    autorizado.</li>
                            </ul>

                            <h3>8.3 Recuperação de Conta</h3>
                            <p>Em caso de perda de acesso à conta, o utilizador pode:</p>
                            <ul>
                                <li>Utilizar a opção <strong>"Esqueci a palavra-passe"</strong> na página de login — um
                                    link de redefinição é enviado para o e-mail registado, válido por <strong>2
                                        horas</strong>;</li>
                                <li>Contactar o <a href="../support/support" class="text-wasomupfy fw-bold">suporte</a>
                                    com documentação de identificação para verificação manual.</li>
                            </ul>
                        </div>

                        <!-- 9 -->
                        <div class="term-section" id="s9">
                            <h2><span class="sec-num">9</span>Retenção e Eliminação de Dados</h2>
                            <p>A <?php echo $siteName; ?> conserva os dados pessoais apenas pelo tempo necessário para
                                cumprir as finalidades para as quais foram recolhidos:</p>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm" style="font-size:.9rem">
                                    <thead class="table-wasomupfy" style="background:rgba(255,0,157,.1)">
                                        <tr>
                                            <th>Tipo de Dado</th>
                                            <th>Período de Retenção</th>
                                            <th>Justificação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Dados de conta activa</td>
                                            <td>Durante toda a vigência da conta</td>
                                            <td>Prestação dos serviços</td>
                                        </tr>
                                        <tr>
                                            <td>Dados financeiros e royalties</td>
                                            <td>Mínimo 5 anos após encerramento</td>
                                            <td>Obrigação legal / fiscal</td>
                                        </tr>
                                        <tr>
                                            <td>Registos de acesso (logs)</td>
                                            <td>12 meses</td>
                                            <td>Segurança e auditoria</td>
                                        </tr>
                                        <tr>
                                            <td>Tickets de suporte</td>
                                            <td>3 anos após resolução</td>
                                            <td>Histórico de litígios</td>
                                        </tr>
                                        <tr>
                                            <td>Contas desactivadas voluntariamente</td>
                                            <td>Até reactivação ou eliminação</td>
                                            <td>Dados preservados e invisíveis</td>
                                        </tr>
                                        <tr>
                                            <td>Conta eliminada pelo utilizador</td>
                                            <td>30 dias após o pedido</td>
                                            <td>Janela de recuperação</td>
                                        </tr>
                                        <tr>
                                            <td>Conta eliminada por violação</td>
                                            <td>Imediatamente (dados bloqueados)</td>
                                            <td>Encerramento por decisão da plataforma</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h3>9.1 Processo de Eliminação de Conta</h3>
                            <p>Ao solicitar a eliminação da conta, o utilizador deve saber que:</p>
                            <ul>
                                <li>A conta entra em período de <strong>30 dias de recuperação</strong> — durante este
                                    período é possível cancelar o pedido iniciando sessão;</li>
                                <li>Após os 30 dias, os dados pessoais identificáveis são eliminados de forma definitiva
                                    e irreversível;</li>
                                <li>Os dados financeiros (histórico de transacções e royalties) são anonimizados e
                                    conservados pelo período legal obrigatório;</li>
                                <li>Os lançamentos nas plataformas de streaming podem demorar até <strong>30 dias
                                        adicionais</strong> a ser removidos, dependendo de cada plataforma.</li>
                            </ul>
                        </div>

                        <!-- 10 -->
                        <div class="term-section" id="s10">
                            <h2><span class="sec-num">10</span>Menores de Idade</h2>
                            <p>A plataforma <?php echo $siteName; ?> destina-se exclusivamente a utilizadores com
                                <strong>18 anos ou mais</strong> (ou a partir da maioridade legal aplicável). A
                                <?php echo $siteName; ?> não recolhe intencionalmente dados pessoais de menores de
                                idade.
                            </p>
                            <p>Se tomarmos conhecimento de que um menor nos forneceu dados pessoais sem o consentimento
                                do seu representante legal, procederemos imediatamente à eliminação desses dados e ao
                                encerramento da conta. Os pais ou tutores legais que suspeitem que o seu filho menor
                                criou uma conta na plataforma devem contactar-nos imediatamente através do <a
                                    href="../support/support" class="text-wasomupfy fw-bold">suporte</a>.</p>
                        </div>

                        <!-- 11 -->
                        <div class="term-section" id="s11">
                            <h2><span class="sec-num">11</span>Dados Provenientes de Plataformas Terceiras</h2>
                            <p>A <?php echo $siteName; ?> recebe dados de plataformas de streaming parceiras (Spotify,
                                Apple Music, YouTube Music, Deezer, TikTok, etc.) relativos ao desempenho dos
                                lançamentos dos utilizadores. Estes dados incluem:</p>
                            <ul>
                                <li>Número de streams e reproduções por lançamento, por país e por período;</li>
                                <li>Receitas geradas por cada lançamento em cada plataforma;</li>
                                <li>Posicionamento em playlists editoriais e algoritmos de recomendação;</li>
                                <li>Dados demográficos agregados e anónimos dos ouvintes.</li>
                            </ul>
                            <p>Estes dados são disponibilizados no painel analítico do utilizador e são tratados em
                                conformidade com a presente Política de Privacidade. A <?php echo $siteName; ?> não
                                controla a política de privacidade destas plataformas terceiras — o utilizador deve
                                consultar directamente cada plataforma para informações sobre o tratamento dos seus
                                dados como ouvinte.</p>
                            <div class="term-box info">
                                <strong><i class="fa-solid fa-cookie-bite me-2"></i>Cookies e tecnologias de
                                    rastreamento</strong>
                                Para informação detalhada sobre os cookies que utilizamos e como os pode gerir, consulte
                                a nossa <a href="cookies" class="text-wasomupfy fw-bold">Política de Cookies</a>.
                            </div>
                        </div>

                        <!-- 12 -->
                        <div class="term-section" id="s12">
                            <h2><span class="sec-num">12</span>Alterações à Política de Privacidade</h2>
                            <p>A <?php echo $siteName; ?> reserva-se o direito de actualizar esta Política de
                                Privacidade a qualquer momento para reflectir alterações nos nossos serviços, na
                                tecnologia ou na legislação aplicável. Em caso de alterações significativas, o
                                utilizador será notificado com pelo menos <strong>15 dias de antecedência</strong>
                                através de:</p>
                            <ul>
                                <li>Notificação no painel de notificações do dashboard da plataforma;</li>
                                <li>Notificação por e-mail para o endereço registado na conta;</li>
                                <li>Aviso em destaque na página inicial do site e na página de login.</li>
                            </ul>
                            <p>O uso continuado da plataforma após a entrada em vigor da nova versão constitui aceitação
                                tácita das alterações. A data da última actualização é sempre indicada no topo desta
                                página.</p>
                        </div>

                        <!-- 13 -->
                        <div class="term-section" id="s13">
                            <h2><span class="sec-num">13</span>Contacto e Responsável pelo Tratamento</h2>
                            <p>O responsável pelo tratamento dos dados pessoais dos utilizadores é a
                                <strong><?php echo $siteName; ?></strong>, com sede em Luanda, Angola.
                            </p>
                            <p>Para questões relacionadas com a sua privacidade, o exercício dos seus direitos ou
                                qualquer preocupação sobre o tratamento dos seus dados pessoais, pode contactar-nos
                                através:</p>
                            <ul>
                                <li><strong>Pedido de suporte na plataforma:</strong> <a href="../support/support"
                                        class="text-wasomupfy fw-bold">Enviar pedido de suporte</a> — resposta em até 30
                                    dias úteis;</li>
                                <?php if (cfg('info_email')): ?><li><strong>E-mail:</strong> <a
                                            href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                            class="text-wasomupfy"><?php echo htmlspecialchars(cfg('info_email')); ?></a>;
                                    </li><?php endif; ?>
                                <?php if (cfg('support_email')): ?><li><strong>E-mail de suporte:</strong> <a
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                            class="text-wasomupfy"><?php echo htmlspecialchars(cfg('support_email')); ?></a>;
                                    </li><?php endif; ?>
                                <li><strong>Localização:</strong>
                                    <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?>,
                                    <?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?>;</li>
                                <li><strong>Horário de atendimento:</strong> Segunda a Sexta, das 08h às 17h (WAT).</li>
                            </ul>

                            <div class="term-box success" style="margin-top:1.5rem">
                                <strong><i class="bi bi-check-circle-fill me-2"></i>O nosso compromisso</strong>
                                A <?php echo $siteName; ?> está comprometida com a protecção e o respeito pelos seus
                                dados pessoais. A sua privacidade não é um obstáculo ao nosso negócio — é parte
                                integrante dos nossos valores e da nossa relação de confiança com cada artista que
                                confia na nossa plataforma.
                            </div>
                        </div>

                        <!-- Cards de políticas relacionadas -->
                        <div class="row g-3 mt-2 policy-cards">
                            <div class="col-md-6">
                                <a href="terms" class="card border-0 shadow-sm h-100 p-3 text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-shape bg-light-primary rounded-circle p-3"><i
                                                class="fa-solid fa-file-contract text-wasomupfy fs-4"></i></div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Termos de Uso</h6>
                                            <p class="small text-muted mb-0">Condições de utilização, planos, royalties
                                                e regras da plataforma.</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="cookies" class="card border-0 shadow-sm h-100 p-3 text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-shape bg-light-warning rounded-circle p-3"><i
                                                class="fa-solid fa-cookie-bite text-warning fs-4"></i></div>
                                        <div>
                                            <h6 class="fw-bold mb-1">Política de Cookies</h6>
                                            <p class="small text-muted mb-0">Que cookies utilizamos, para quê e como os
                                                pode gerir.</p>
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
                        <li class="list-inline-item"><a href="privacy"
                                class="text-reset text-decoration-none fw-bold text-wasomupfy">Política de
                                Privacidade</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="terms" class="text-reset text-decoration-none">Termos de
                                Uso</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="cookies"
                                class="text-reset text-decoration-none">Cookies</a></li>
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
            var bar = document.getElementById('reading-progress');
            window.addEventListener('scroll', function() {
                var st = document.documentElement.scrollTop || document.body.scrollTop,
                    sh = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                if (bar) bar.style.width = (sh > 0 ? (st / sh) * 100 : 0) + '%';
            }, {
                passive: true
            });
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