<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Termos de Uso e de Condições
// Arquivo: page/politicies/terms.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('terms');
trackVisitor('/page/politicies/terms', 'Termos de Uso — Wasom Upfy');

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
        content="<?php echo $siteName; ?>, Termos de Uso, Condições, distribuição musical, royalties" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Termos de Uso e de Condições" />
    <meta property="og:description"
        content="Leia os Termos de Uso da <?php echo $siteName; ?>: distribuição musical, royalties, pagamentos, propriedade intelectual e condições de utilização." />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/politicies/terms" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Termos de Uso e de Condições</title>
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
            background: linear-gradient(90deg, #ff009d, #ff6b35);
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

        .term-section:target,
        .term-section.active-section {
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
            background: linear-gradient(135deg, #ff009d, #ff6b35);
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

        .plan-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: .9rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0, 0, 0, .07)
        }

        .plan-table thead tr {
            background: linear-gradient(90deg, #ff009d, #ff6b35);
            color: #fff
        }

        .plan-table th {
            padding: .75rem 1rem;
            font-weight: 600
        }

        .plan-table td {
            padding: .65rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, .06)
        }

        .plan-table tbody tr:hover {
            background: rgba(255, 0, 157, .04)
        }

        .plan-table tbody tr:last-child td {
            border-bottom: none
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

        <!-- Hero -->
        <section class="privacy-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/terms.png"
                alt="Termos de Uso <?php echo $siteName; ?>" loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Termos de Uso</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-3 text-white-stable fw-bold">Termos de Uso e de Condições</h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Leia atentamente as condições abaixo. Ao utilizar a plataforma <?php echo $siteName; ?>,
                            confirma que leu e aceita estes Termos na íntegra. Veja também a
                            <a href="privacy" class="text-white fw-bold">Política de Privacidade</a> e a
                            <a href="cookies" class="text-white fw-bold">Política de Cookies</a>.
                        </p>
                        <p class="text-white-stable small opacity-80 mb-4">
                            <i class="fa-regular fa-calendar me-2"></i>Última actualização: 14 de Fevereiro de 2026
                            &nbsp;·&nbsp; <i class="fa-regular fa-file-lines me-2"></i>18 secções
                            &nbsp;·&nbsp; <i class="fa-regular fa-clock me-2"></i>Leitura: ~12 minutos
                        </p>
                        <div class="terms-badges d-flex justify-content-center gap-2 flex-wrap mb-4">
                            <a href="#s7" class="badge bg-success text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-percent me-1"></i>90% Royalties</a>
                            <a href="#s5" class="badge bg-danger text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-ban me-1"></i>Sem Reembolsos</a>
                            <a href="#s4" class="badge bg-wasomupfy text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-layer-group me-1"></i>Planos & Preços</a>
                            <a href="#s8" class="badge bg-primary text-white py-2 px-3 rounded-pill smooth-scroll"><i
                                    class="fa-solid fa-shield-halved me-1"></i>Propriedade Intelectual</a>
                        </div>
                        <a href="#termos-conteudo" class="btn btn-wasomupfy btn-lg mt-1 smooth-scroll">
                            Ler os termos <i class="fa-solid fa-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Conteúdo -->
        <section id="termos-conteudo" class="py-6 bg-light-100">
            <div class="container" data-cue="fadeIn">

                <div class="action-buttons">
                    <a href="terms.pdf" class="btn btn-outline-wasomupfy" download><i
                            class="fa-solid fa-file-pdf me-2"></i>Baixar PDF</a>
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i
                            class="fa-solid fa-print me-2"></i>Imprimir</button>
                    <a href="privacy" class="btn btn-outline-secondary"><i
                            class="fa-solid fa-shield me-2"></i>Privacidade</a>
                    <a href="cookies" class="btn btn-outline-secondary"><i
                            class="fa-solid fa-cookie-bite me-2"></i>Cookies</a>
                </div>

                <!-- LAYOUT -->
                <div class="terms-layout">

                    <!-- ÍNDICE (sidebar) -->
                    <div class="terms-index d-none d-lg-block">
                        <h3><i class="bi bi-list-ol me-2"></i>Índice</h3>
                        <ul>
                            <li><a href="#s1"><span class="num">1.</span>Identificação e Serviços</a></li>
                            <li><a href="#s2"><span class="num">2.</span>Aceitação e Elegibilidade</a></li>
                            <li><a href="#s3"><span class="num">3.</span>Registo de Conta</a></li>
                            <li><a href="#s4"><span class="num">4.</span>Planos e Condições de Pagamento</a></li>
                            <li><a href="#s5"><span class="num">5.</span>Política de Não Reembolso</a></li>
                            <li><a href="#s6"><span class="num">6.</span>Distribuição Musical</a></li>
                            <li><a href="#s7"><span class="num">7.</span>Royalties e Pagamentos</a></li>
                            <li><a href="#s8"><span class="num">8.</span>Propriedade Intelectual</a></li>
                            <li><a href="#s9"><span class="num">9.</span>Conteúdo Proibido</a></li>
                            <li><a href="#s10"><span class="num">10.</span>Uso Aceitável da Plataforma</a></li>
                            <li><a href="#s11"><span class="num">11.</span>Suspensão e Encerramento</a></li>
                            <li><a href="#s12"><span class="num">12.</span>Limitação de Responsabilidade</a></li>
                            <li><a href="#s13"><span class="num">13.</span>Privacidade e Dados</a></li>
                            <li><a href="#s14"><span class="num">14.</span>Cookies</a></li>
                            <li><a href="#s15"><span class="num">15.</span>Serviços de Terceiros</a></li>
                            <li><a href="#s16"><span class="num">16.</span>Actualizações dos Termos</a></li>
                            <li><a href="#s17"><span class="num">17.</span>Lei Aplicável</a></li>
                            <li><a href="#s18"><span class="num">18.</span>Contacto</a></li>
                        </ul>
                        <hr class="idx-divider" />
                        <div class="text-center">
                            <small class="text-muted d-block mb-2">Outras políticas</small>
                            <a href="privacy" class="btn btn-sm btn-outline-wasomupfy w-100 mb-1 rounded-pill"><i
                                    class="fa-solid fa-shield me-1"></i> Privacidade</a>
                            <a href="cookies" class="btn btn-sm btn-outline-secondary w-100 rounded-pill"><i
                                    class="fa-solid fa-cookie-bite me-1"></i> Cookies</a>
                        </div>
                    </div>

                    <!-- CONTEÚDO DOS TERMOS -->
                    <div class="terms-content">

                        <!-- 1 -->
                        <div class="term-section" id="s1">
                            <h2><span class="sec-num">1</span>Identificação e Descrição dos Serviços</h2>
                            <p>A <strong><?php echo $siteName; ?></strong> é uma plataforma digital de distribuição
                                musical e gestão de direitos autorais, desenvolvida e operada em Angola. A plataforma
                                permite a artistas, produtores musicais, bandas e selos discográficos distribuir as suas
                                obras para mais de <strong>150 plataformas digitais</strong> em todo o mundo, incluindo,
                                entre outras: Spotify, Apple Music, YouTube Music, Deezer, TIDAL, Amazon Music,
                                Boomplay, TikTok, iTunes e outras lojas de música.</p>
                            <p>Os serviços disponibilizados pela plataforma incluem, mas não se limitam a:</p>
                            <ul>
                                <li>Distribuição de singles, EPs e álbuns para plataformas de streaming e lojas
                                    digitais;</li>
                                <li>Geração automática de códigos <strong>UPC</strong> (Universal Product Code) e
                                    <strong>ISRC</strong> (International Standard Recording Code);
                                </li>
                                <li>Painel analítico de streams, receitas, países e playlists em tempo real com
                                    exportação CSV;</li>
                                <li>Gestão financeira com carteira digital, histórico de transacções e levantamentos;
                                </li>
                                <li>Divisão automática de royalties entre colaboradores e co-artistas;</li>
                                <li>Sistema de colaboradores com três níveis de acesso (Visualizador, Editor e
                                    Administrador);</li>
                                <li>Autenticação em dois factores (2FA) via código OTP por e-mail;</li>
                                <li>Unificação e gestão de canal YouTube (Art Tracks e monetização);</li>
                                <li>Sistema de suporte por tickets com acompanhamento de estado em tempo real;</li>
                                <li>Notificações em tempo real via plataforma, e-mail e push notifications;</li>
                                <li>Relatórios mensais de receitas e estatísticas detalhadas de desempenho.</li>
                            </ul>
                            <div class="term-box pink">
                                <strong><i class="fa-solid fa-location-dot me-2"></i>Operação e Jurisdição</strong>
                                A <?php echo $siteName; ?> opera a partir de <strong>Luanda, Angola</strong>, com foco
                                primário no mercado angolano e nos países da CPLP, com alcance global de distribuição
                                para mais de 150 plataformas em todo o mundo.
                            </div>
                        </div>

                        <!-- 2 -->
                        <div class="term-section" id="s2">
                            <h2><span class="sec-num">2</span>Aceitação dos Termos e Elegibilidade</h2>
                            <p>Ao criar uma conta na plataforma <?php echo $siteName; ?>, o utilizador declara
                                expressamente que:</p>
                            <ul>
                                <li>Leu, compreendeu e aceita na íntegra os presentes Termos de Uso;</li>
                                <li>Tem idade igual ou superior a <strong>18 anos</strong>, ou age com o consentimento
                                    expresso do seu representante legal;</li>
                                <li>As informações prestadas no registo são verdadeiras, completas e actualizadas;</li>
                                <li>Tem capacidade legal para celebrar contratos vinculativos ao abrigo da legislação
                                    angolana;</li>
                                <li>Aceita a <a href="privacy" class="text-wasomupfy fw-bold">Política de
                                        Privacidade</a> e a <a href="cookies" class="text-wasomupfy fw-bold">Política de
                                        Cookies</a> da plataforma.</li>
                            </ul>
                            <p>A aceitação destes Termos fica registada no sistema com a data, hora e endereço IP do
                                registo, constituindo prova legal de aceitação.</p>
                            <div class="term-box warning">
                                <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Atenção</strong>
                                Se não concordar com qualquer parte destes Termos, deverá cessar imediatamente o uso da
                                plataforma e contactar o <a href="../support/support"
                                    class="text-wasomupfy fw-bold">suporte</a> para encerrar a sua conta. O uso
                                continuado implica aceitação plena.
                            </div>
                        </div>

                        <!-- 3 -->
                        <div class="term-section" id="s3">
                            <h2><span class="sec-num">3</span>Registo de Conta e Segurança</h2>
                            <p>Para utilizar os serviços da <?php echo $siteName; ?>, é obrigatório criar uma conta
                                pessoal. Cada utilizador pode manter <strong>apenas uma conta activa</strong> na
                                plataforma — contas duplicadas serão encerradas sem aviso prévio.</p>
                            <h3>3.1 Responsabilidade do Utilizador</h3>
                            <ul>
                                <li>O utilizador é o único responsável pela confidencialidade das suas credenciais de
                                    acesso (e-mail e palavra-passe);</li>
                                <li>Qualquer actividade realizada na conta é da inteira responsabilidade do titular,
                                    mesmo que realizada por terceiro com acesso às credenciais;</li>
                                <li>Em caso de acesso não autorizado suspeito, o utilizador deve notificar imediatamente
                                    a equipa via <a href="../support/support" class="text-wasomupfy fw-bold">pedido de
                                        suporte</a>;</li>
                                <li>A partilha de credenciais de acesso com terceiros é estritamente proibida e pode
                                    resultar na suspensão imediata da conta.</li>
                            </ul>
                            <h3>3.2 Verificação de E-mail</h3>
                            <ul>
                                <li>Após o registo, é enviado automaticamente um e-mail de verificação. A conta só fica
                                    totalmente activa após clicar no link de verificação;</li>
                                <li>O link de verificação expira ao fim de <strong>24 horas</strong> — um novo link pode
                                    ser solicitado no painel;</li>
                                <li>Enquanto não verificado, o acesso a funcionalidades de lançamento e pagamento fica
                                    limitado.</li>
                            </ul>
                            <h3>3.3 Autenticação em Dois Factores (2FA)</h3>
                            <p>A plataforma disponibiliza 2FA via <strong>e-mail (OTP)</strong>, activável em
                                <em>Definições → Segurança</em>. Cada sessão exige um código temporário válido por
                                <strong>10 minutos</strong>. A sua activação é altamente recomendada para todas as
                                contas.
                            </p>
                            <h3>3.4 Dados do Perfil</h3>
                            <ul>
                                <li>O utilizador compromete-se a manter os seus dados de perfil actualizados e
                                    verdadeiros;</li>
                                <li>A utilização de identidades falsas, nomes artísticos que violem direitos de
                                    terceiros ou imagens de perfil inapropriadas pode resultar na suspensão imediata da
                                    conta.</li>
                            </ul>
                            <h3>3.5 Desactivação e Reactivação Voluntária</h3>
                            <p>O utilizador pode desactivar temporariamente a conta em <em>Definições → Conta →
                                    Desactivar conta</em>. Os dados são preservados durante a desactivação. Para
                                reactivar, basta iniciar sessão — o sistema apresentará um diálogo de confirmação e a
                                conta será restaurada imediatamente com todos os dados intactos.</p>
                        </div>

                        <!-- 4 -->
                        <div class="term-section" id="s4">
                            <h2><span class="sec-num">4</span>Planos de Serviço e Condições de Pagamento</h2>
                            <p>A <?php echo $siteName; ?> oferece quatro planos de serviço. O utilizador deve escolher o
                                plano adequado às suas necessidades antes de efectuar qualquer lançamento.</p>

                            <div class="table-responsive">
                                <table class="plan-table">
                                    <thead>
                                        <tr>
                                            <th>Plano</th>
                                            <th>Preço</th>
                                            <th>Tipo</th>
                                            <th>Cobertura</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($plans)): foreach ($plans as $pl):
                                                $pI = ['single' => 'fa-music', 'album' => 'fa-compact-disc', 'artist' => 'fa-microphone-lines', 'label' => 'fa-tags'];
                                                $pIc = $pI[$pl['slug_plan']] ?? 'fa-music';
                                                $pPrc = number_format($pl['price_plan'], 0, ',', '.');
                                                $pType = $pl['type_plan'] === 'subscription' ? 'Subscrição' : 'Por lançamento';
                                        ?>
                                                <tr>
                                                    <td><i
                                                            class="fa-solid <?php echo $pIc; ?> text-wasomupfy me-2"></i><strong><?php echo htmlspecialchars($pl['name_plan']); ?></strong>
                                                    </td>
                                                    <td><strong><?php echo $pPrc; ?> AOA</strong></td>
                                                    <td><?php echo $pType; ?></td>
                                                    <td><?php echo htmlspecialchars($pl['description_plan'] ?? '—'); ?></td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <td><i
                                                        class="fa-solid fa-music text-wasomupfy me-2"></i><strong>Single</strong>
                                                </td>
                                                <td><strong>2.000 AOA</strong></td>
                                                <td>Por lançamento</td>
                                                <td>1 faixa por lançamento</td>
                                            </tr>
                                            <tr>
                                                <td><i
                                                        class="fa-solid fa-compact-disc text-wasomupfy me-2"></i><strong>Álbum</strong>
                                                </td>
                                                <td><strong>5.000 AOA</strong></td>
                                                <td>Por lançamento</td>
                                                <td>Até 20 faixas por lançamento</td>
                                            </tr>
                                            <tr>
                                                <td><i
                                                        class="fa-solid fa-microphone-lines text-wasomupfy me-2"></i><strong>Artist</strong>
                                                </td>
                                                <td><strong>11.400 AOA</strong></td>
                                                <td>Subscrição (2 anos)</td>
                                                <td>Lançamentos ilimitados, 1 artista</td>
                                            </tr>
                                            <tr>
                                                <td><i
                                                        class="fa-solid fa-tags text-wasomupfy me-2"></i><strong>Label</strong>
                                                </td>
                                                <td><strong>70.000 AOA</strong></td>
                                                <td>Subscrição (2 anos)</td>
                                                <td>Lançamentos ilimitados, artistas ilimitados</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <h3>4.1 Forma de Pagamento</h3>
                            <p>Os pagamentos são efectuados por <strong>transferência bancária</strong> ou outro método
                                disponibilizado pela plataforma. Após o pagamento, o utilizador deve submeter o
                                comprovante em <em>Conta → Activar Plano</em>. A activação ocorre em até <strong>24
                                    horas úteis</strong> após verificação pela equipa administrativa.</p>

                            <h3>4.2 Planos por Subscrição (Artist e Label)</h3>
                            <p>Os planos <strong>Artist</strong> e <strong>Label</strong> são subscrições com validade
                                de <strong>2 anos</strong>. A renovação não é automática — o utilizador deve efectuar o
                                pagamento e submeter o comprovante antes do vencimento para garantir continuidade sem
                                interrupção. A <?php echo $siteName; ?> envia notificação com <strong>15 dias de
                                    antecedência</strong> do vencimento.</p>

                            <h3>4.3 Plano Inactivo</h3>
                            <p>Caso o plano expire sem renovação, os lançamentos existentes nas plataformas
                                <strong>permanecerão activos e os royalties continuam a ser acumulados</strong>, mas o
                                utilizador não poderá submeter novos lançamentos até renovar o plano.
                            </p>

                            <h3>4.4 Comprovantes Fraudulentos</h3>
                            <p>A submissão de comprovantes de pagamento falsos, adulterados ou de transacções não
                                realizadas constitui fraude, resultando no encerramento permanente da conta e eventual
                                participação às autoridades competentes.</p>
                        </div>

                        <!-- 5 -->
                        <div class="term-section" id="s5">
                            <h2><span class="sec-num">5</span>Política de Não Reembolso</h2>
                            <div class="term-box danger">
                                <strong><i class="bi bi-x-circle-fill me-2"></i>Política de Não Reembolso — Leitura
                                    Obrigatória</strong>
                                Todos os pagamentos efectuados à <?php echo $siteName; ?> são <strong>definitivos,
                                    irreversíveis e não reembolsáveis</strong>, independentemente da circunstância. Ao
                                efectuar o pagamento, o utilizador declara ter compreendido e aceite esta condição de
                                forma irrevogável. <strong>Não existe reembolso sob nenhuma circunstância.</strong>
                            </div>
                            <p>A política de não reembolso aplica-se em todos os casos, incluindo:</p>
                            <ul>
                                <li>Pagamentos de activação de plano (Single, Álbum, Artist, Label);</li>
                                <li>Pagamentos de renovação de planos de subscrição;</li>
                                <li>Pagamentos efectuados por erro do utilizador (valor errado, plano errado, e-mail
                                    errado);</li>
                                <li>Situações em que o utilizador decida não utilizar o serviço após pagamento;</li>
                                <li>Casos em que a conta seja suspensa ou encerrada por violação dos presentes Termos;
                                </li>
                                <li>Lançamentos rejeitados por incumprimento dos requisitos técnicos ou de conteúdo;
                                </li>
                                <li>Indisponibilidade temporária da plataforma por manutenção programada ou causas de
                                    força maior;</li>
                                <li>Alterações nas políticas de plataformas terceiras que afectem a distribuição;</li>
                                <li>Decisão do utilizador de encerrar a conta voluntariamente antes do fim do período
                                    contratado.</li>
                            </ul>
                            <h3>5.1 Única Excepção — Crédito de Conta</h3>
                            <p>A única situação em que poderá ser analisado um pedido de <em>crédito de conta</em> (e
                                não reembolso monetário) é quando a <?php echo $siteName; ?> cometa um erro técnico
                                comprovável que resulte na <strong>cobrança duplicada exacta pelo mesmo
                                    serviço</strong>. O utilizador deve abrir um <a href="../support/support"
                                    class="text-wasomupfy fw-bold">pedido de suporte</a> com o comprovante das duas
                                cobranças no prazo de <strong>72 horas</strong> após a ocorrência. A análise não garante
                                resultado favorável.</p>
                            <div class="term-box info">
                                <strong><i class="bi bi-info-circle-fill me-2"></i>Recomendação Antes de Pagar</strong>
                                Antes de efectuar qualquer pagamento, certifica-te de que escolheste o plano correcto e
                                compreendeste as condições em <a href="../../plan/all-plans"
                                    class="text-wasomupfy fw-bold">Todos os Planos</a>. Em caso de dúvida, contacta o <a
                                    href="../support/support" class="text-wasomupfy fw-bold">suporte</a> antes de pagar.
                            </div>
                        </div>

                        <!-- 6 -->
                        <div class="term-section" id="s6">
                            <h2><span class="sec-num">6</span>Distribuição Musical — Requisitos e Prazos</h2>
                            <h3>6.1 Requisitos Técnicos de Áudio</h3>
                            <ul>
                                <li>Formato obrigatório: <strong>WAV estéreo</strong> — 16-bit ou 24-bit, 44,1 kHz
                                    (24-bit recomendado);</li>
                                <li>Headroom de masterização mínimo: <strong>-1 dB</strong> para evitar clipping;</li>
                                <li>Tamanho máximo por ficheiro de áudio: <strong>1 GB</strong>;</li>
                                <li>Ficheiros com ruído excessivo, cortes abruptos ou qualidade inferior ao padrão
                                    profissional serão rejeitados;</li>
                                <li>Formatos MP3, AAC, OGG e outros formatos com perda de qualidade <strong>não são
                                        aceites</strong>.</li>
                            </ul>
                            <h3>6.2 Requisitos da Arte da Capa</h3>
                            <ul>
                                <li>Dimensão mínima: <strong>3.000 × 3.000 pixels</strong>, formato quadrado (proporção
                                    1:1);</li>
                                <li>Formato: <strong>JPG ou PNG</strong>, modo de cor RGB, sem artefactos ou
                                    pixelização;</li>
                                <li>A capa <strong>não pode conter</strong>: logótipos de redes sociais ou lojas
                                    digitais, URLs, e-mails, QR codes, preços, marcas d'água, conteúdo explícito sem
                                    marcação adequada, ou materiais que violem direitos de autor de terceiros.</li>
                            </ul>
                            <h3>6.3 Metadados e Códigos ISRC/UPC</h3>
                            <p>O utilizador é responsável pela correcta introdução de todos os metadados: artista
                                principal, artistas em feat., compositores com respectivas percentagens, produtores,
                                engenheiros de mixagem e mastering, género musical, idioma e marcação de conteúdo
                                explícito. Os códigos <strong>ISRC</strong> e <strong>UPC</strong> são gerados
                                automaticamente pela plataforma.</p>
                            <h3>6.4 Prazos de Distribuição</h3>
                            <ul>
                                <li>Revisão interna pela equipa <?php echo $siteName; ?>: até <strong>72 horas
                                        úteis</strong> após submissão;</li>
                                <li>Spotify e Apple Music: disponibilização em <strong>3 a 7 dias</strong> após
                                    aprovação;</li>
                                <li>TikTok: <strong>2 a 4 dias úteis</strong> após aprovação;</li>
                                <li>Outras plataformas: até <strong>14 dias</strong> após aprovação;</li>
                                <li>Recomendamos submeter lançamentos com pelo menos <strong>3 semanas de
                                        antecedência</strong> para garantir pitching a playlists editoriais.</li>
                            </ul>
                            <h3>6.5 Agendamento de Lançamento</h3>
                            <p>É possível definir uma data futura de lançamento no formulário de upload. A
                                <?php echo $siteName; ?> recomenda agendar para <strong>sexta-feira</strong> (New Music
                                Friday) com 2-3 semanas de antecedência para melhor performance.</p>
                            <h3>6.6 Rejeição de Lançamentos</h3>
                            <p>A <?php echo $siteName; ?> reserva-se o direito de rejeitar qualquer lançamento que não
                                cumpra os requisitos técnicos, de conteúdo ou legais. Em caso de rejeição, o utilizador
                                é notificado com o motivo detalhado. O pagamento do plano <strong>não é
                                    reembolsável</strong> em caso de rejeição por não conformidade.</p>
                            <h3>6.7 Remoção de Lançamentos</h3>
                            <p>O utilizador pode solicitar a remoção de um lançamento a qualquer momento através do
                                painel. A remoção efectiva pode demorar até <strong>30 dias</strong> dependendo de cada
                                plataforma. Os streams e receitas gerados até à remoção efectiva são processados
                                normalmente.</p>
                        </div>

                        <!-- 7 -->
                        <div class="term-section" id="s7">
                            <h2><span class="sec-num">7</span>Royalties, Receitas e Levantamentos</h2>
                            <h3>7.1 Distribuição de Royalties</h3>
                            <p>A <?php echo $siteName; ?> distribui <strong>90% dos royalties líquidos</strong> gerados
                                pelos lançamentos directamente ao artista. Os restantes <strong>10%</strong> destinam-se
                                à cobertura dos custos operacionais da plataforma.</p>
                            <div class="term-box success">
                                <strong><i class="bi bi-check-circle-fill me-2"></i>Taxa de Royalties</strong>
                                <strong>90%</strong> para o artista · <strong>10%</strong> para operação da plataforma —
                                sem custos ocultos, sem taxas por faixa, sem comissões adicionais. Os royalties líquidos
                                são calculados <em>após</em> as deduções das próprias plataformas de streaming.
                            </div>
                            <h3>7.2 Ciclo de Pagamento</h3>
                            <p>Os dados de streaming chegam com atraso das plataformas (normalmente 2 meses). Os
                                royalties são creditados na carteira digital do utilizador até ao <strong>dia 15-20 de
                                    cada mês</strong>, referentes ao mês anterior. O utilizador pode acompanhar os
                                ganhos em tempo real no dashboard, filtrados por loja, artista ou período.</p>
                            <div class="table-responsive">
                                <table class="plan-table">
                                    <thead>
                                        <tr>
                                            <th>Mês dos Streams</th>
                                            <th>Relatório Disponível</th>
                                            <th>Pagamento Processado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Janeiro</td>
                                            <td>Março — dia 15</td>
                                            <td>Março — dia 20</td>
                                        </tr>
                                        <tr>
                                            <td>Fevereiro</td>
                                            <td>Abril — dia 15</td>
                                            <td>Abril — dia 20</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted small">…padrão repete-se
                                                mensalmente…</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <h3>7.3 Levantamentos</h3>
                            <ul>
                                <li>Valor mínimo para levantamento: <strong>1.000 AOA</strong>;</li>
                                <li>Métodos disponíveis: transferência bancária (IBAN), Express e outros métodos
                                    listados na plataforma;</li>
                                <li>Prazo de processamento: <strong>3 a 5 dias úteis</strong> após confirmação do
                                    pedido;</li>
                                <li>O utilizador é responsável pela veracidade dos dados bancários fornecidos.
                                    Pagamentos efectuados para contas erradas por dados fornecidos incorrectamente pelo
                                    utilizador não são da responsabilidade da <?php echo $siteName; ?>.</li>
                            </ul>
                            <h3>7.4 Divisão de Royalties entre Colaboradores</h3>
                            <p>O utilizador pode configurar a divisão de royalties entre colaboradores em <em>Finanças →
                                    Divisão de Royalties</em>. A soma das percentagens deve ser sempre
                                <strong>100%</strong>. Cada colaborador tem acesso à sua parte conforme as permissões
                                definidas pelo titular da conta.
                            </p>
                            <h3>7.5 Retenção por Suspeita de Fraude</h3>
                            <p>A <?php echo $siteName; ?> reserva-se o direito de reter temporariamente pagamentos de
                                royalties quando existir suspeita fundada de manipulação de streams, fraude ou
                                actividade irregular. O utilizador será notificado e terá <strong>15 dias úteis</strong>
                                para apresentar esclarecimentos.</p>
                        </div>

                        <!-- 8 -->
                        <div class="term-section" id="s8">
                            <h2><span class="sec-num">8</span>Propriedade Intelectual</h2>
                            <h3>8.1 Propriedade do Conteúdo</h3>
                            <p>O utilizador mantém a totalidade dos direitos de propriedade intelectual sobre as suas
                                obras musicais. A <?php echo $siteName; ?> não reivindica qualquer direito de
                                propriedade sobre as músicas, letras, capas ou qualquer outro conteúdo submetido pelo
                                utilizador.</p>
                            <h3>8.2 Licença de Distribuição</h3>
                            <p>Ao submeter um lançamento, o utilizador concede à <?php echo $siteName; ?> uma
                                <strong>licença não exclusiva, mundial e revogável</strong> para distribuir, reproduzir,
                                disponibilizar e promover as obras nas plataformas parceiras, em seu nome, pelo período
                                em que o lançamento estiver activo na plataforma.
                            </p>
                            <h3>8.3 Garantia de Titularidade</h3>
                            <p>O utilizador declara e garante que:</p>
                            <ul>
                                <li>É o titular legítimo ou detentor de licença válida para todos os conteúdos
                                    submetidos;</li>
                                <li>Os conteúdos submetidos não violam direitos de autor, marcas registadas ou quaisquer
                                    outros direitos de terceiros;</li>
                                <li>Não submete conteúdo que seja objecto de litígio, embargo ou decisão judicial que
                                    impeça a sua distribuição;</li>
                                <li>Samples, interpolações e elementos de terceiros utilizados estão devidamente
                                    licenciados.</li>
                            </ul>
                            <p>O utilizador será o único responsável por qualquer reclamação, litígio ou indemnização
                                resultante da violação desta garantia. A <?php echo $siteName; ?> reserva-se o direito
                                de remover imediatamente qualquer conteúdo objecto de reclamação fundamentada de
                                violação de direitos de terceiros, sem aviso prévio e sem direito a reembolso.</p>
                            <h3>8.4 Propriedade da Plataforma</h3>
                            <p>Todos os elementos da plataforma <?php echo $siteName; ?> — incluindo design,
                                código-fonte, logótipos, marcas, textos, relatórios, algoritmos e funcionalidades — são
                                propriedade exclusiva da <?php echo $siteName; ?> e estão protegidos pelas leis de
                                propriedade intelectual aplicáveis. É expressamente proibida a reprodução, cópia,
                                modificação, engenharia reversa ou distribuição de qualquer elemento da plataforma sem
                                autorização escrita prévia.</p>
                        </div>

                        <!-- 9 -->
                        <div class="term-section" id="s9">
                            <h2><span class="sec-num">9</span>Conteúdo Proibido</h2>
                            <p>É estritamente proibido submeter à plataforma qualquer conteúdo que:</p>
                            <ul>
                                <li>Viole direitos de autor, direitos conexos ou direitos de imagem de terceiros;</li>
                                <li>Contenha ou promova discurso de ódio, racismo, xenofobia, discriminação ou
                                    incitamento à violência;</li>
                                <li>Seja de natureza pornográfica ou sexualmente explícita sem as devidas marcações de
                                    conteúdo adulto;</li>
                                <li>Envolva ou promova actividades ilegais, incluindo o consumo ou tráfico de
                                    substâncias ilícitas;</li>
                                <li>Contenha ameaças, difamação ou calúnia dirigidas a indivíduos, grupos ou
                                    organizações;</li>
                                <li>Reproduza sem autorização gravações de terceiros, samples não licenciados ou
                                    remisturas não autorizadas;</li>
                                <li>Seja gerado por inteligência artificial sem a devida declaração nos metadados do
                                    lançamento, nos casos em que as plataformas de destino o exijam;</li>
                                <li>Constitua spam sonoro criado com o único objectivo de acumular streams fraudulentos.
                                </li>
                            </ul>
                            <div class="term-box danger">
                                <strong><i class="bi bi-x-octagon-fill me-2"></i>Consequências Imediatas</strong>
                                A submissão de conteúdo proibido resultará na remoção imediata do lançamento, possível
                                suspensão ou encerramento permanente da conta sem direito a reembolso, e eventual
                                responsabilização legal do utilizador.
                            </div>
                        </div>

                        <!-- 10 -->
                        <div class="term-section" id="s10">
                            <h2><span class="sec-num">10</span>Uso Aceitável da Plataforma</h2>
                            <p>O utilizador compromete-se expressamente a não:</p>
                            <ul>
                                <li>Tentar aceder a áreas restritas da plataforma sem autorização ou através de técnicas
                                    de hacking;</li>
                                <li>Utilizar ferramentas automatizadas (bots, scrapers, crawlers) para extrair dados da
                                    plataforma sem autorização escrita prévia;</li>
                                <li>Realizar ataques de negação de serviço (DoS/DDoS) ou qualquer tentativa de
                                    comprometer a segurança ou disponibilidade da plataforma;</li>
                                <li>Criar ou utilizar mais de uma conta — contas duplicadas serão encerradas sem aviso
                                    prévio e os saldos serão bloqueados;</li>
                                <li>Manipular artificialmente o número de streams, incluindo através de "stream
                                    farming", bots, listas de reprodução automáticas não humanas ou troca organizada de
                                    streams;</li>
                                <li>Partilhar, vender, alugar ou transferir a sua conta ou credenciais a terceiros;</li>
                                <li>Utilizar a plataforma para fins comerciais não autorizados, incluindo a revenda de
                                    serviços sem acordo escrito com a <?php echo $siteName; ?>;</li>
                                <li>Publicar links de phishing, malware ou qualquer conteúdo malicioso em qualquer campo
                                    da plataforma.</li>
                            </ul>
                        </div>

                        <!-- 11 -->
                        <div class="term-section" id="s11">
                            <h2><span class="sec-num">11</span>Suspensão e Encerramento de Contas</h2>
                            <h3>11.1 Suspensão Temporária</h3>
                            <p>A conta pode ser suspensa temporariamente nos seguintes casos:</p>
                            <ul>
                                <li>Detecção de actividade suspeita ou acesso de localização não reconhecida pela
                                    plataforma;</li>
                                <li>Submissão de comprovantes de pagamento falsos, adulterados ou manipulados
                                    digitalmente;</li>
                                <li>Múltiplos pedidos de levantamento em períodos anormalmente curtos ou suspeitos;</li>
                                <li>Reclamações de violação de direitos de autor recebidas pelas plataformas de
                                    distribuição parceiras;</li>
                                <li>Violação de qualquer disposição dos presentes Termos, passível de correcção;</li>
                                <li>Não pagamento de quantias devidas à plataforma;</li>
                                <li>Investigação em curso por suspeita de fraude ou manipulação de streams.</li>
                            </ul>
                            <h3>11.2 Encerramento Permanente</h3>
                            <p>A conta pode ser encerrada definitivamente nos seguintes casos:</p>
                            <ul>
                                <li>Criação de contas duplicadas ou clonadas;</li>
                                <li>Fraude comprovada, incluindo manipulação de streams, falsificação de comprovantes ou
                                    identidade falsa;</li>
                                <li>Violações graves e repetidas dos Termos de Uso;</li>
                                <li>Determinação judicial ou ordem de autoridade competente angolana ou internacional;
                                </li>
                                <li>Actividade que cause dano reputacional, financeiro ou operacional à plataforma ou a
                                    terceiros.</li>
                            </ul>
                            <div class="term-box warning">
                                <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Consequências do
                                    Encerramento por Violação</strong>
                                Em caso de encerramento por violação dos Termos, o utilizador perde imediatamente o
                                acesso a todos os dados, lançamentos e saldo da carteira, <strong>sem direito a
                                    reembolso ou compensação</strong>. Os lançamentos distribuídos poderão ser removidos
                                das plataformas sem aviso prévio.
                            </div>
                            <h3>11.3 Encerramento Voluntário</h3>
                            <p>O utilizador pode solicitar o encerramento voluntário da conta através de <em>Definições
                                    → Conta → Eliminar conta</em> ou via <a href="../support/support"
                                    class="text-wasomupfy fw-bold">pedido de suporte</a>. <strong>Esta acção é
                                    irreversível.</strong> Antes do encerramento, recomenda-se exportar todos os
                                relatórios financeiros e solicitar o levantamento de todos os saldos pendentes — o saldo
                                remanescente pode ser levantado no prazo de <strong>30 dias</strong> após o pedido.</p>
                        </div>

                        <!-- 12 -->
                        <div class="term-section" id="s12">
                            <h2><span class="sec-num">12</span>Limitação de Responsabilidade</h2>
                            <p>A <?php echo $siteName; ?> não se responsabiliza por:</p>
                            <ul>
                                <li>Falhas técnicas, indisponibilidade ou atrasos causados por plataformas de
                                    distribuição terceiras (Spotify, Apple Music, Deezer, TikTok, etc.);</li>
                                <li>Perdas de receitas resultantes de decisões unilaterais das plataformas de
                                    distribuição parceiras, incluindo alterações nas taxas de remuneração por stream;
                                </li>
                                <li>Alterações nas políticas de remuneração das plataformas de streaming que afectem os
                                    valores de royalties;</li>
                                <li>Danos indirectos, incidentais, especiais ou consequenciais resultantes do uso ou
                                    incapacidade de uso da plataforma;</li>
                                <li>Interrupções de serviço causadas por casos de força maior, incluindo falhas de
                                    energia, desastres naturais, conflitos armados, pandemias ou ordens governamentais;
                                </li>
                                <li>Perdas ou danos resultantes de acesso não autorizado à conta por falha do utilizador
                                    em proteger as suas credenciais ou por não activar o 2FA.</li>
                            </ul>
                            <p>A responsabilidade total da <?php echo $siteName; ?> perante o utilizador, em qualquer
                                circunstância, está limitada ao <strong>valor pago pelo utilizador pelo plano
                                    activo</strong> no momento do evento gerador do dano, referente ao último ciclo de
                                facturação.</p>
                        </div>

                        <!-- 13 -->
                        <div class="term-section" id="s13">
                            <h2><span class="sec-num">13</span>Privacidade e Tratamento de Dados</h2>
                            <p>O tratamento dos dados pessoais dos utilizadores é regido pela <a href="privacy"
                                    class="text-wasomupfy fw-bold">Política de Privacidade</a> da
                                <?php echo $siteName; ?>, que constitui parte integrante dos presentes Termos de Uso. Ao
                                aceitar estes Termos, o utilizador aceita igualmente a Política de Privacidade.</p>
                            <p>A <?php echo $siteName; ?> não partilha dados pessoais dos utilizadores com terceiros
                                para fins comerciais ou publicitários. Os dados são utilizados exclusivamente para a
                                prestação dos serviços contratados e para o cumprimento de obrigações legais.</p>
                            <p>Cada utilizador pode visualizar apenas os seus próprios dados, lançamentos e informações
                                financeiras. O acesso a dados de outros utilizadores é tecnicamente impedido pela
                                plataforma.</p>
                            <div class="term-box info">
                                <strong><i class="bi bi-info-circle-fill me-2"></i>Os seus Direitos</strong>
                                Tem direito a aceder, rectificar, exportar e eliminar os seus dados pessoais. Para
                                exercer estes direitos, consulte a <a href="privacy"
                                    class="text-wasomupfy fw-bold">Política de Privacidade</a> ou contacte o <a
                                    href="../support/support" class="text-wasomupfy fw-bold">suporte</a>.
                            </div>
                        </div>

                        <!-- 14 -->
                        <div class="term-section" id="s14">
                            <h2><span class="sec-num">14</span>Cookies e Tecnologias de Rastreamento</h2>
                            <p>A <?php echo $siteName; ?> utiliza cookies e tecnologias similares para:</p>
                            <ul>
                                <li>Manter a sessão de utilizador activa, segura e contínua;</li>
                                <li>Guardar preferências de tema (claro/escuro/sistema), idioma e configurações de
                                    interface;</li>
                                <li>Analisar o comportamento de utilização para melhoria contínua da plataforma;</li>
                                <li>Detectar e prevenir actividades fraudulentas e acessos não autorizados.</li>
                            </ul>
                            <p>Para informação detalhada sobre os cookies utilizados e como os gerir, consulte a <a
                                    href="cookies" class="text-wasomupfy fw-bold">Política de Cookies</a>. A
                                desactivação de cookies essenciais pode afectar o funcionamento correcto da plataforma,
                                incluindo a manutenção da sessão de login.</p>
                        </div>

                        <!-- 15 -->
                        <div class="term-section" id="s15">
                            <h2><span class="sec-num">15</span>Serviços e Plataformas de Terceiros</h2>
                            <p>A <?php echo $siteName; ?> distribui conteúdo para plataformas de terceiros (Spotify,
                                Apple Music, YouTube Music, Deezer, TikTok, Amazon Music, Boomplay, TIDAL, Audiomack e
                                outras) que possuem os seus próprios Termos de Uso e Políticas de Privacidade
                                independentes. A <?php echo $siteName; ?> não controla nem se responsabiliza pelas
                                políticas, decisões ou alterações efectuadas por essas plataformas.</p>
                            <p>A integração com o <strong>YouTube</strong> para unificação de canal e gestão de Art
                                Tracks está sujeita aos Termos de Serviço do YouTube e às políticas do YouTube Partner
                                Program. A <?php echo $siteName; ?> não garante a aprovação pelo YouTube da monetização
                                de qualquer canal.</p>
                        </div>

                        <!-- 16 -->
                        <div class="term-section" id="s16">
                            <h2><span class="sec-num">16</span>Actualizações dos Termos de Uso</h2>
                            <p>A <?php echo $siteName; ?> reserva-se o direito de actualizar os presentes Termos de Uso
                                a qualquer momento, mediante notificação prévia com pelo menos <strong>15 dias de
                                    antecedência</strong> através de:</p>
                            <ul>
                                <li>Notificação na plataforma (painel de notificações do dashboard);</li>
                                <li>Notificação por e-mail para o endereço registado na conta;</li>
                                <li>Aviso em destaque na página de login e na página inicial do site.</li>
                            </ul>
                            <p>O uso contínuo da plataforma após a entrada em vigor da nova versão constitui aceitação
                                tácita das alterações. Se o utilizador não concordar com as alterações, deve cessar o
                                uso da plataforma e solicitar o encerramento da conta antes da data de entrada em vigor.
                            </p>
                        </div>

                        <!-- 17 -->
                        <div class="term-section" id="s17">
                            <h2><span class="sec-num">17</span>Lei Aplicável e Resolução de Litígios</h2>
                            <p>Os presentes Termos de Uso são regidos e interpretados de acordo com a legislação da
                                <strong>República de Angola</strong>, em especial:
                            </p>
                            <ul>
                                <li>Lei n.º 22/11 de 17 de Junho — Lei das Comunicações Electrónicas e dos Serviços da
                                    Sociedade da Informação;</li>
                                <li>Lei n.º 22/22 de 3 de Agosto — Lei de Protecção de Dados Pessoais;</li>
                                <li>Legislação aplicável em matéria de propriedade intelectual, direitos de autor e
                                    direito do consumidor angolano.</li>
                            </ul>
                            <p>Qualquer litígio decorrente da interpretação ou execução dos presentes Termos será
                                submetido à <strong>jurisdição exclusiva dos tribunais competentes de Luanda,
                                    Angola</strong>, com renúncia expressa a qualquer outro foro.</p>
                            <p>Antes de recorrer a qualquer instância judicial, as partes comprometem-se a tentar
                                resolver o litígio de forma amigável através do <a href="../support/support"
                                    class="text-wasomupfy fw-bold">sistema de suporte</a> da plataforma, num prazo de
                                <strong>30 dias</strong> a contar da notificação formal da reclamação.
                            </p>
                        </div>

                        <!-- 18 -->
                        <div class="term-section" id="s18">
                            <h2><span class="sec-num">18</span>Contacto</h2>
                            <p>Para questões, dúvidas ou reclamações relativas aos presentes Termos de Uso, o utilizador
                                pode contactar a equipa <?php echo $siteName; ?> através dos seguintes meios:</p>
                            <ul>
                                <li><strong>Suporte na plataforma:</strong> <a href="../support/support"
                                        class="text-wasomupfy fw-bold">Enviar pedido de suporte</a> — resposta em até 48
                                    horas úteis;</li>
                                <li><strong>FAQ:</strong> <a href="../support/faq"
                                        class="text-wasomupfy fw-bold">Perguntas Frequentes</a> — para questões comuns
                                    sobre a plataforma;</li>
                                <?php if (cfg('support_email')): ?><li><strong>E-mail de suporte:</strong> <a
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                            class="text-wasomupfy"><?php echo htmlspecialchars(cfg('support_email')); ?></a>;
                                    </li><?php endif; ?>
                                <?php if (cfg('info_email')): ?><li><strong>E-mail geral:</strong> <a
                                            href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                            class="text-wasomupfy"><?php echo htmlspecialchars(cfg('info_email')); ?></a>;
                                    </li><?php endif; ?>
                                <?php if ($whatsNum): ?><li><strong>WhatsApp:</strong> <a
                                            href="https://wa.me/<?php echo $whatsNum; ?>" class="text-wasomupfy"
                                            target="_blank" rel="noopener noreferrer">+<?php echo $whatsNum; ?></a>;</li>
                                <?php endif; ?>
                                <li><strong>Localização:</strong>
                                    <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?>,
                                    <?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?>;</li>
                                <li><strong>Horário de atendimento:</strong> Segunda a Sexta, das 08h às 17h (WAT).</li>
                            </ul>
                            <div class="term-box success" style="margin-top:1.5rem">
                                <strong><i class="bi bi-check-circle-fill me-2"></i>Aceitação Confirmada no
                                    Registo</strong>
                                Ao criares a tua conta na <?php echo $siteName; ?>, confirmas a leitura e aceitação
                                integral destes Termos de Uso, da <a href="privacy"
                                    class="text-wasomupfy fw-bold">Política de Privacidade</a> e da <a href="cookies"
                                    class="text-wasomupfy fw-bold">Política de Cookies</a>. A data, hora e endereço IP
                                do teu registo serão registados como prova legal de aceitação.
                            </div>
                        </div>

                        <!-- Cards de políticas relacionadas -->
                        <div class="row g-3 mt-2 policy-cards">
                            <div class="col-md-6">
                                <a href="privacy" class="card border-0 shadow-sm h-100 p-3 text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-shape bg-light-primary rounded-circle p-3"><i
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
                        <li class="list-inline-item"><a href="privacy" class="text-reset text-decoration-none">Política
                                de Privacidade</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="terms"
                                class="text-reset text-decoration-none fw-bold text-wasomupfy">Termos de Uso</a></li>
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
            // Barra de progresso
            var bar = document.getElementById('reading-progress');
            window.addEventListener('scroll', function() {
                var st = document.documentElement.scrollTop || document.body.scrollTop,
                    sh = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                if (bar) bar.style.width = (sh > 0 ? (st / sh) * 100 : 0) + '%';
            }, {
                passive: true
            });
            // Scroll spy
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
            // Smooth scroll
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
            // Feedback AJAX
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