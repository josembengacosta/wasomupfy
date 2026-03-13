<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Promoção de Música
// Arquivo: page/services/music-promotion.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('music-promotion');
trackVisitor('/page/services/music-promotion', 'Promoção de Música — Wasom Upfy');

$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];
$siteName    = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl     = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum    = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));
$whatsChannel = cfg('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04');
$plans       = getPlans();
$csrf        = getSiteCsrf();
?>
<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />

    <!-- SEO dinâmico -->
    <?php
  $seoTitle = 'Promoção de Música — ' . $siteName;
  $seoDesc  = 'Não basta lançar — é preciso ser ouvido. Campanhas de tráfego pago, pitching para playlists editoriais e estratégias de lançamento personalizadas para artistas e labels independentes.';
  $seoImg   = $siteUrl . '/assets/img/og_wasomupfy.jpeg';
  ?>
    <title><?php echo htmlspecialchars($seoTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoDesc); ?>" />
    <meta name="keywords"
        content="promoção de música, marketing musical, playlists, spotify pitching, meta ads, tiktok artistas, angola música, wasom upfy" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($seoDesc); ?>" />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/services/music-promotion" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image" content="<?php echo $seoImg; ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $siteUrl; ?>/page/services/music-promotion" />

    <!-- Preloader -->
    <script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.querySelector('body').classList.add('loaded');
        }, 200);
    });
    </script>

    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../../css/theme.min.css" />
    <link rel="stylesheet" href="../../js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="../../css/framework.css" />
    <link rel="stylesheet" href="../../css/main.css" />

    <style>
    /* ── Contador de estatísticas ───────────────────────────── */
    .stat-ring {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: conic-gradient(var(--bs-pink, #FF009D) var(--pct, 75%), rgba(255, 255, 255, .08) 0);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .stat-ring::before {
        content: '';
        position: absolute;
        inset: 8px;
        background: var(--bs-body-bg);
        border-radius: 50%;
    }

    .stat-ring span {
        position: relative;
        z-index: 1;
    }

    /* ── Ferramentas cards ─────────────────────────────────── */
    .tool-card {
        transition: transform .25s ease, box-shadow .25s ease;
        cursor: default;
    }

    .tool-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 1rem 2.5rem rgba(255, 0, 141, .18) !important;
    }

    .tool-icon {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        background: rgba(255, 0, 141, .10);
        color: #FF009D;
        transition: background .25s;
    }

    .tool-card:hover .tool-icon {
        background: rgba(255, 0, 141, .22);
    }

    /* ── Pacotes ───────────────────────────────────────────── */
    .package-card {
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .package-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, .20) !important;
    }

    .package-card.featured {
        border: 2px solid #FF009D !important;
    }

    /* ── Processo steps ────────────────────────────────────── */
    .step-line {
        position: absolute;
        top: 50px;
        left: calc(50% + 55px);
        width: calc(100% - 110px);
        height: 2px;
        background: linear-gradient(90deg, #FF009D 0%, rgba(255, 0, 141, .15) 100%);
    }

    @media (max-width: 767px) {
        .step-line {
            display: none;
        }
    }

    /* ── FAQ Accordion ─────────────────────────────────────── */
    .faq-accordion .accordion-button:not(.collapsed) {
        color: #FF009D;
        background: rgba(255, 0, 141, .06);
        box-shadow: none;
    }

    .faq-accordion .accordion-button::after {
        filter: none;
    }

    /* ── CTA final ─────────────────────────────────────────── */
    .cta-section {
        background: linear-gradient(135deg, #FF009D 0%, #c2006e 100%);
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('../../assets/img/theme/pattern.png') center/cover;
        opacity: .07;
    }
    </style>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="../../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="A carregar — <?php echo $siteName; ?>" />
    </div>

    <!-- ══ Navbar ════════════════════════════════════════════════════════════ -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="../../home" title="Home">
                    <img src="../../assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo <?php echo $siteName; ?>" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width:20rem">
                    <div class="offcanvas-header">
                        <a title="Logotipo" href="../../home">
                            <img width="65" src="../../assets/img/brand/wasomupfy_brand.png"
                                alt="Logo <?php echo $siteName; ?>" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item">
                                <a class="nav-link" href="../../home" title="Início">Início</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../../about" title="Sobre">Sobre</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../../blog/" title="Blogue" target="_blank"
                                    rel="external">Blogue</a>
                            </li>

                            <!-- Planos — dinâmico -->
                            <li class="nav-item dropdown">
                                <a title="Planos" class="nav-link" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Planos <i data-feather="chevron-down"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-md" aria-labelledby="navbarDropdown">
                                    <?php
                  $navIcons = ['single' => 'fa-music', 'album' => 'fa-compact-disc', 'artist' => 'fa-microphone-lines', 'label' => 'fa-tags'];
                  foreach ($plans as $p):
                    $nSlug = $p['slug_plan'];
                    $nIcon = $navIcons[$nSlug] ?? 'fa-music';
                    $nPrc  = number_format($p['price_plan'], 0, ',', '.');
                    $nPer  = $p['type_plan'] === 'subscription' ? '/ano' : '';
                  ?>
                                    <a title="<?php echo htmlspecialchars($p['name_plan']); ?>"
                                        class="dropdown-item mb-3 text-body" href="../../plan/<?php echo $nSlug; ?>">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid <?php echo $nIcon; ?> text-wasomupfy fs-3"
                                                style="width:35px"></i>
                                            <div class="ms-3 lh-1">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                                <p class="mb-0 fs-6">
                                                    Nosso plano <?php echo htmlspecialchars($p['name_plan']); ?>
                                                    — <?php echo $nPrc; ?> Kz<?php echo $nPer; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                    <a title="Todos os planos" class="dropdown-item mb-3 text-body"
                                        href="../../plan/all-plans">
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
                                <a title="Páginas" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Páginas <i data-feather="chevron-down"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a title="Novidades" class="dropdown-item" href="../../blog/">Novidades</a>
                                            <a title="Passatempo" class="dropdown-item"
                                                href="../../blog/">Passatempo</a>
                                            <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                <span class="badge bg-warning">Indisponível</span></a>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a title="A nossa marca" class="dropdown-item"
                                                    href="../../about?#nossamarca">A nossa marca</a>
                                                <a title="Parcerias" class="dropdown-item"
                                                    href="../../partnership">Parcerias</a>
                                                <a title="Quem somos" class="dropdown-item"
                                                    href="../../about#nossa-historia">Quem somos</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Serviços</div>
                                                <a title="Distribuição de música" class="dropdown-item"
                                                    href="music-distribution">Distribuição de música</a>
                                                <a title="Promoção de música" class="dropdown-item active"
                                                    href="music-promotion">Promoção de música
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Serviços Personalizados" class="dropdown-item"
                                                    href="customized-services">Serviços personalizados
                                                    <span class="badge bg-warning">Em breve</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052"
                                                    target="_blank" rel="external noopener noreferrer">Atendimento</a>
                                                <a title="Contacta-nos" class="dropdown-item"
                                                    href="../../contact">Contacta-nos</a>
                                                <a title="Canal WhatsApp" class="dropdown-item"
                                                    href="<?php echo htmlspecialchars($whatsChannel); ?>"
                                                    target="_blank" rel="external noopener noreferrer">Canal
                                                    WhatsApp</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Sugestões</div>
                                                <a title="Ajuda" class="dropdown-item" href="../support/help">Ajuda
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Feedback" class="dropdown-item" href="#"
                                                    data-bs-toggle="modal" data-bs-target="#modalFeedback">Feedback</a>
                                                <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                                <div class="mt-3">
                                                    <div class="dropdown-header">Ajuda</div>
                                                    <a title="Tutorial" class="dropdown-item"
                                                        href="../support/tutorial">Tutorial
                                                        <span class="badge bg-success">Novo</span></a>
                                                    <a title="Suporte técnico" class="dropdown-item"
                                                        href="../support/support">Suporte técnico</a>
                                                    <a title="Perguntas frequentes" class="dropdown-item"
                                                        href="../support/faq">Perguntas frequentes</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="../../resources" title="Recursos">Recursos</a>
                            </li>

                            <li class="nav-item dropdown">
                                <a title="Contactar" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Contactar <i data-feather="chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a title="Caixa de mensagem" class="dropdown-item" href="../../contact">Caixa de
                                            mensagem</a></li>
                                    <?php if (cfg('support_email')): ?>
                                    <li><a title="E-mail" class="dropdown-item"
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>">
                                            <?php echo htmlspecialchars(cfg('support_email')); ?></a></li>
                                    <?php endif; ?>
                                    <?php if ($whatsNum): ?>
                                    <li><a title="WhatsApp" class="dropdown-item"
                                            href="https://api.whatsapp.com/send/?phone=<?php echo $whatsNum; ?>&text&type=phone_number&app_absent=0">
                                            WhatsApp</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        </ul>

                        <div class="mt-3 mt-lg-0 d-flex align-items-center">
                            <a title="Sign-in" href="/wasomupfy/login" class="btn btn-secondary mx-2">
                                Entrar <i data-feather="log-in"></i>
                            </a>
                            <?php if ($canRegister): ?>
                            <a title="Sign-up" href="/wasomupfy/register" class="btn btn-wasomupfy">Inscreva-se</a>
                            <?php else: ?>
                            <span class="btn btn-secondary disabled">Inscrições fechadas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- ══ Navbar fim ════════════════════════════════════════════════════════ -->

    <main>

        <!-- ══ Hero ══════════════════════════════════════════════════════════ -->
        <section class="promotion-hero jarallax position-relative overflow-hidden" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/promoting.png"
                alt="Promoção Musical <?php echo $siteName; ?>" loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative py-7" style="z-index:2">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="mb-4">
                            <ol class="breadcrumb justify-content-center">
                                <li class="breadcrumb-item">
                                    <a href="../../home" class="text-white-50 text-decoration-none">Início</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="music-distribution" class="text-white-50 text-decoration-none">Serviços</a>
                                </li>
                                <li class="breadcrumb-item active text-white" aria-current="page">Promoção de Música
                                </li>
                            </ol>
                        </nav>
                        <span class="badge bg-wasomupfy text-white mb-3 fs-6 px-3 py-2 rounded-pill">
                            <i class="fa-solid fa-fire me-1"></i> Marketing Musical
                        </span>
                        <h1 class="display-3 fw-bold text-white-stable mb-4">
                            Não basta lançar.<br />É preciso ser <span class="text-wasomupfy">ouvido</span>.
                        </h1>
                        <p class="lead text-white-stable mb-5 opacity-90 mx-auto" style="max-width:620px">
                            Ajudamos artistas e labels a furar a bolha dos algoritmos. Campanhas de tráfego,
                            pitching para playlists e estratégias de lançamento personalizadas.
                        </p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="#pacotes"
                                class="btn btn-wasomupfy btn-lg px-5 py-3 fw-bold shadow-lg smooth-scroll">
                                Ver Pacotes <i class="fa-solid fa-fire ms-2"></i>
                            </a>
                            <a href="contact?analysis=free" class="btn btn-outline-light btn-lg px-5 py-3">
                                Análise Gratuita <i class="fa-regular fa-comments ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══ Stats ═══════════════════════════════════════════════════════ -->
        <section class="py-5 bg-wasomupfy">
            <div class="container">
                <div class="row g-4 text-white text-center justify-content-center">
                    <?php
          $stats = [
            ['val' => '60K',  'lbl' => 'Músicas lançadas por dia',   'sub' => 'em todo o mundo'],
            ['val' => '+450%', 'lbl' => 'Crescimento médio de alcance', 'sub' => 'nas nossas campanhas'],
            ['val' => '100+', 'lbl' => 'Lojas e plataformas',        'sub' => 'onde chegamos'],
            ['val' => '90%',  'lbl' => 'Royalties para o artista',   'sub' => $siteName],
          ];
          foreach ($stats as $s):
          ?>
                    <div class="col-6 col-md-3" data-cue="fadeIn">
                        <div class="fw-bold fs-1 mb-1"><?php echo $s['val']; ?></div>
                        <div class="fw-semibold"><?php echo $s['lbl']; ?></div>
                        <div class="small opacity-75"><?php echo $s['sub']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══ Problema / Solução ══════════════════════════════════════════ -->
        <section class="py-7 bg-light-100">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6" data-cue="slideInLeft">
                        <div class="position-relative">
                            <img src="../../assets/img/theme/meeting.png" class="img-fluid rounded-4 shadow-lg"
                                alt="Estratégia Musical" />
                            <div class="card position-absolute bottom-0 end-0 mb-n4 me-n4 border-0 shadow-lg p-3"
                                style="max-width:210px">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape icon-md bg-success text-white rounded-circle">
                                        <i class="fa-solid fa-arrow-trend-up"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="mb-0 fw-bold">+450%</h5>
                                        <small class="text-muted">Alcance Mensal</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6" data-cue="fadeIn">
                        <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy mb-3">O Problema</span>
                        <h2 class="display-5 fw-bold mb-4">
                            60.000 músicas são lançadas todos os dias…
                        </h2>
                        <p class="lead text-muted mb-4">
                            Como fazer a tua destacar-se no meio de tantas? A distribuição coloca a tua
                            música na loja, mas a <strong>promoção</strong> traz os ouvintes para dentro.
                        </p>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex mb-4">
                                <div
                                    class="icon-shape icon-sm bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle me-3 flex-shrink-0">
                                    <i class="fa-solid fa-bullseye"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Tráfego Qualificado</h5>
                                    <p class="text-muted small mb-0">
                                        Anúncios direcionados para quem realmente gosta do teu estilo musical.
                                    </p>
                                </div>
                            </li>
                            <li class="d-flex mb-4">
                                <div
                                    class="icon-shape icon-sm bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle me-3 flex-shrink-0">
                                    <i class="fa-solid fa-list-ul"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Curadoria de Playlists</h5>
                                    <p class="text-muted small mb-0">
                                        Pitching directo para curadores independentes e playlists editoriais.
                                    </p>
                                </div>
                            </li>
                            <li class="d-flex">
                                <div
                                    class="icon-shape icon-sm bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle me-3 flex-shrink-0">
                                    <i class="fa-solid fa-hashtag"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Redes Sociais</h5>
                                    <p class="text-muted small mb-0">
                                        Estratégias criativas para TikTok, Instagram Reels e YouTube Shorts.
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══ Ferramentas ═════════════════════════════════════════════════ -->
        <section class="py-7">
            <div class="container">
                <div class="text-center mb-6" data-cue="fadeIn">
                    <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy mb-3">Ferramentas</span>
                    <h2 class="fw-bold">Tudo o que precisas para um lançamento profissional</h2>
                    <p class="text-muted lead">
                        Uma suite completa de marketing musical ao alcance da tua carreira.
                    </p>
                </div>

                <div class="row g-4">
                    <?php
          $tools = [
            ['icon' => 'fa-brands fa-spotify',   'title' => 'Pitching Spotify',   'desc' => 'Submissão estratégica para playlists algorítmicas, editoriais e de curadores independentes.',          'delay' => ''],
            ['icon' => 'fa-brands fa-youtube',   'title' => 'YouTube Ads',         'desc' => 'Campanhas TrueView para maximizar visualizações do teu clipe e crescimento de canal.',                  'delay' => '100'],
            ['icon' => 'fa-brands fa-instagram', 'title' => 'Meta Ads',            'desc' => 'Anúncios segmentados no Instagram e Facebook Stories, Reels e Feed para o teu público-alvo.',          'delay' => '200'],
            ['icon' => 'fa-brands fa-tiktok',    'title' => 'TikTok Strategy',     'desc' => 'Criação de trends, desafios virais e estratégias de crescimento orgânico na plataforma.',              'delay' => '300'],
            ['icon' => 'fa-solid fa-pen-nib',    'title' => 'Press Kit (EPK)',      'desc' => 'Criação de biografia artística, one-sheet e material profissional para imprensa e eventos.',           'delay' => ''],
            ['icon' => 'fa-solid fa-chart-line', 'title' => 'Analytics & Relatório', 'desc' => 'Relatórios detalhados de performance com dados de streams, alcance e retorno sobre investimento.',     'delay' => '100'],
            ['icon' => 'fa-solid fa-newspaper',  'title' => 'Pitching Editorial',  'desc' => 'Envio da tua música para blogs especializados, webrádios e portais de música africana.',               'delay' => '200'],
            ['icon' => 'fa-solid fa-envelope',   'title' => 'Email Marketing',     'desc' => 'Campanhas de email para a tua base de fãs com newsletter profissional e automações.',                  'delay' => '300'],
          ];
          foreach ($tools as $t):
          ?>
                    <div class="col-lg-3 col-md-6" data-cue="zoomIn"
                        <?php if ($t['delay']) echo 'data-delay="' . $t['delay'] . '"'; ?>>
                        <div class="card tool-card h-100 border-0 shadow-sm p-4">
                            <div class="tool-icon mb-3">
                                <i class="<?php echo $t['icon']; ?>"></i>
                            </div>
                            <h5 class="fw-bold mb-2"><?php echo $t['title']; ?></h5>
                            <p class="small text-muted mb-0"><?php echo $t['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══ Como funciona ══════════════════════════════════════════════ -->
        <section class="py-7 bg-light-100">
            <div class="container">
                <div class="text-center mb-6" data-cue="fadeIn">
                    <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy mb-3">Processo</span>
                    <h2 class="fw-bold">Como funciona</h2>
                    <p class="text-muted">Do contacto ao lançamento em 4 passos simples.</p>
                </div>

                <div class="row g-4 justify-content-center">
                    <?php
          $steps = [
            ['num' => '01', 'icon' => 'fa-solid fa-comments',      'title' => 'Consulta Inicial',    'desc' => 'Analisamos a tua música, estilo e objectivos de carreira numa reunião gratuita.'],
            ['num' => '02', 'icon' => 'fa-solid fa-map',            'title' => 'Estratégia Customizada', 'desc' => 'Criamos um plano de promoção personalizado para o teu lançamento e orçamento.'],
            ['num' => '03', 'icon' => 'fa-solid fa-rocket',         'title' => 'Execução da Campanha', 'desc' => 'Activamos os canais escolhidos — ads, pitching, redes sociais — com monitorização diária.'],
            ['num' => '04', 'icon' => 'fa-solid fa-chart-bar',      'title' => 'Resultados & Análise', 'desc' => 'Recebes relatórios detalhados e optimizações contínuas ao longo da campanha.'],
          ];
          foreach ($steps as $i => $s):
          ?>
                    <div class="col-md-6 col-lg-3" data-cue="fadeIn" data-delay="<?php echo $i * 100; ?>">
                        <div class="text-center px-3 position-relative">
                            <?php if ($i < count($steps) - 1): ?>
                            <div class="step-line d-none d-lg-block"></div>
                            <?php endif; ?>
                            <div class="icon-shape icon-xl bg-wasomupfy text-white rounded-circle shadow mx-auto mb-4">
                                <i class="<?php echo $s['icon']; ?> fs-3"></i>
                            </div>
                            <div class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy mb-2"><?php echo $s['num']; ?>
                            </div>
                            <h5 class="fw-bold mb-2"><?php echo $s['title']; ?></h5>
                            <p class="text-muted small mb-0"><?php echo $s['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ══ Pacotes ═════════════════════════════════════════════════════ -->
        <section id="pacotes" class="py-7 bg-wasomupfy position-relative">
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10"
                style="background-image:url('../../assets/img/theme/pattern.png')"></div>
            <div class="container position-relative" style="z-index:2">
                <div class="text-center mb-6" data-cue="fadeIn">
                    <span class="badge bg-white text-wasomupfy mb-3">Pacotes</span>
                    <h2 class="fw-bold text-white">Investimento na tua Carreira</h2>
                    <p class="text-white opacity-90">
                        Escolhe o nível de intensidade que o teu lançamento precisa.
                    </p>
                </div>

                <div class="row g-4 justify-content-center">

                    <!-- Impulso Single -->
                    <div class="col-lg-4 col-md-6" data-cue="fadeIn">
                        <div class="card package-card h-100 border-0 shadow-lg p-4">
                            <div class="card-body">
                                <span class="badge bg-light text-dark mb-3">Inicial</span>
                                <h3 class="fw-bold mb-1">Impulso Single</h3>
                                <p class="text-muted small mb-4">
                                    Para quem está a começar e precisa de validação no mercado.
                                </p>
                                <hr class="opacity-10 my-4" />
                                <ul class="list-unstyled mb-4">
                                    <?php
                  $f1 = ['Pitching para Curadores de Playlists', 'Otimização de Perfil Spotify', 'Estratégia de Hashtags', 'Relatório Básico de Performance'];
                  foreach ($f1 as $f):
                  ?>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fa-solid fa-check text-wasomupfy me-2 flex-shrink-0"></i>
                                        <span><?php echo $f; ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="contact?consultant=initial"
                                    class="btn btn-outline-wasomupfy w-100 py-3 fw-bold">
                                    Consultar Valor <i class="fa-solid fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Campanha 360 — MAIS VENDIDO -->
                    <div class="col-lg-4 col-md-6" data-cue="fadeIn" data-delay="100">
                        <div
                            class="card package-card featured h-100 border-0 shadow-lg p-4 position-relative overflow-hidden">
                            <div
                                class="position-absolute top-0 start-0 w-100 bg-wasomupfy text-white text-center small fw-bold py-2">
                                <i class="fa-solid fa-fire me-1"></i> MAIS ESCOLHIDO
                            </div>
                            <div class="card-body mt-4">
                                <span class="badge bg-wasomupfy text-white mb-3">Profissional</span>
                                <h3 class="fw-bold mb-1">Campanha 360°</h3>
                                <p class="text-muted small mb-4">
                                    Tráfego pago e orgânico combinados para o máximo alcance.
                                </p>
                                <hr class="opacity-10 my-4" />
                                <ul class="list-unstyled mb-4">
                                    <?php
                  $f2 = ['<strong>Gestão de Ads (Meta + Google)</strong>', 'Pitching Editorial & Blogs', 'Criação de Criativos (Vídeo/Reels)', 'Estratégia TikTok & Reels', 'Relatório Avançado Semanal'];
                  foreach ($f2 as $f):
                  ?>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fa-solid fa-check text-wasomupfy me-2 flex-shrink-0"></i>
                                        <span><?php echo $f; ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="contact?consultant=talk" class="btn btn-wasomupfy w-100 py-3 fw-bold shadow">
                                    Falar com Consultor <i class="fa-solid fa-headset ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Gestão de Label -->
                    <div class="col-lg-4 col-md-6" data-cue="fadeIn" data-delay="200">
                        <div class="card package-card h-100 border-0 shadow-lg p-4">
                            <div class="card-body">
                                <span class="badge bg-dark text-white mb-3">Empresarial</span>
                                <h3 class="fw-bold mb-1">Gestão de Label</h3>
                                <p class="text-muted small mb-4">
                                    Planeamento estratégico de longo prazo para todo o teu catálogo.
                                </p>
                                <hr class="opacity-10 my-4" />
                                <ul class="list-unstyled mb-4">
                                    <?php
                  $f3 = ['Planeamento Trimestral de Releases', 'Gestão de Branding & Identidade', 'Consultoria de Carreira Dedicada', 'Relatório Executivo Mensal', 'Acesso Prioritário à Equipa'];
                  foreach ($f3 as $f):
                  ?>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="fa-solid fa-check text-wasomupfy me-2 flex-shrink-0"></i>
                                        <span><?php echo $f; ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="contact?meeting=schedule" class="btn btn-outline-wasomupfy w-100 py-3 fw-bold">
                                    Agendar Reunião <i class="fa-solid fa-calendar-check ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ══ FAQ ═══════════════════════════════════════════════════════ -->
        <section class="py-7 bg-light-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="text-center mb-6" data-cue="fadeIn">
                            <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy mb-3">FAQ</span>
                            <h2 class="fw-bold">Perguntas Frequentes</h2>
                            <p class="text-muted">Tudo o que precisas saber sobre promoção musical.</p>
                        </div>

                        <div class="accordion faq-accordion" id="faqPromo">
                            <?php
              $faqs = [
                [
                  'q' => 'A promoção garante que a minha música entra em playlists?',
                  'a' => 'Fazemos o pitching estratégico para curadores e playlists editoriais, mas a decisão final é do curador. O que garantimos é que a tua música chega às pessoas certas, com o melhor material de apresentação possível.'
                ],
                [
                  'q' => 'Preciso ter a distribuição feita pela Wasom Upfy para aceder à promoção?',
                  'a' => 'Não necessariamente. Aceitamos artistas que distribuíram a sua música através de outras plataformas, desde que a música já esteja disponível nas lojas digitais.'
                ],
                [
                  'q' => 'Quanto tempo demora uma campanha?',
                  'a' => 'O mínimo recomendado é 4 semanas. Campanhas de lançamento geralmente começam 2 semanas antes do lançamento e estendem-se por mais 2 semanas após o release para maximizar o algoritmo.'
                ],
                [
                  'q' => 'Qual é o orçamento mínimo para campanhas de ads?',
                  'a' => 'Para campanhas Meta Ads e Google Ads, recomendamos um orçamento mínimo de 150 USD (ou equivalente em Kz) para obter dados estatisticamente relevantes. O nosso valor de gestão é cobrado separadamente.'
                ],
                [
                  'q' => 'Trabalham com artistas de todos os géneros musicais?',
                  'a' => 'Sim. Trabalhamos com Afropop, Semba, Kizomba, Kuduro, Hip-Hop, Afrobeat, R&B e outros géneros. A estratégia é sempre adaptada ao estilo e ao público-alvo do artista.'
                ],
                [
                  'q' => 'Como acompanho os resultados da campanha?',
                  'a' => 'Enviamos relatórios semanais (planos Profissional e Empresarial) ou mensais (plano Inicial) com dados de streams, alcance, engagement, crescimento de seguidores e ROI das campanhas pagas.'
                ],
              ];
              foreach ($faqs as $i => $faq):
                $faqId = 'faq' . $i;
              ?>
                            <div class="accordion-item border-0 shadow-sm mb-3 rounded-3 overflow-hidden"
                                data-cue="fadeIn">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button <?php echo $i > 0 ? 'collapsed' : ''; ?> fw-semibold rounded-3"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $faqId; ?>"
                                        aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                                        <?php echo htmlspecialchars($faq['q']); ?>
                                    </button>
                                </h2>
                                <div id="<?php echo $faqId; ?>"
                                    class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>"
                                    data-bs-parent="#faqPromo">
                                    <div class="accordion-body text-muted">
                                        <?php echo htmlspecialchars($faq['a']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══ CTA Final ══════════════════════════════════════════════════ -->
        <section class="cta-section py-7">
            <div class="container position-relative" style="z-index:2">
                <div class="row justify-content-center text-center text-white">
                    <div class="col-lg-8" data-cue="fadeIn">
                        <i class="fa-solid fa-headphones-simple fs-1 mb-4 opacity-75"></i>
                        <h2 class="display-5 fw-bold mb-3">
                            A tua música merece ser ouvida pelo mundo.
                        </h2>
                        <p class="lead mb-5 opacity-90">
                            Cada lançamento é único. A nossa equipa analisa a tua música
                            e sugere a melhor estratégia — sem compromisso.
                        </p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="contact?analysis=free"
                                class="btn btn-white btn-lg px-5 py-3 fw-bold text-wasomupfy shadow">
                                <i class="fa-regular fa-comments me-2"></i> Análise Gratuita
                            </a>
                            <?php if ($whatsNum): ?>
                            <a href="https://wa.me/<?php echo $whatsNum; ?>?text=Olá!%20Tenho%20interesse%20nos%20serviços%20de%20promoção%20musical%20da%20<?php echo rawurlencode($siteName); ?>"
                                target="_blank" rel="noopener noreferrer"
                                class="btn btn-outline-light btn-lg px-5 py-3 fw-bold">
                                <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp
                            </a>
                            <?php endif; ?>
                        </div>
                        <p class="small mt-4 opacity-75">
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            Sem contratos longos. Sem taxas escondidas. Resultados reais.
                        </p>
                    </div>
                </div>
            </div>
        </section>

    </main>
    <!-- ══ Main fim ══════════════════════════════════════════════════════════ -->

    <div class="divider-fade"></div>

    <!-- ══ Footer ════════════════════════════════════════════════════════════ -->
    <footer class="bg-light-100 pt-7" role="contentinfo" aria-label="Rodapé do site">
        <div class="container">
            <!-- Newsletter -->
            <div class="row align-items-center mb-7 border-bottom border-white-10 pb-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h3 class="fw-bold mb-1">Junte-se a +10.000 Artistas</h3>
                    <p class="lead text-muted mb-0">
                        Receba dicas de marketing, novidades da indústria e ofertas exclusivas.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form action="#" class="row g-2">
                        <div class="col-sm-8">
                            <input type="email" class="form-control border-0 text-muted py-3" autocomplete="email"
                                required placeholder="Seu melhor e-mail" />
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-wasomupfy w-100 py-3 fw-bold">Inscrever</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Links -->
            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">
                    <!-- Logo + Redes -->
                    <div class="col-lg-3 col-12">
                        <a href="../../home" class="d-inline-block mb-4 navbar-brand">
                            <img src="../../assets/img/brand/wasomupfy_brand.png" alt="<?php echo $siteName; ?>"
                                width="65" class="img-logo" height="60" />
                        </a>
                        <p class="lead text-muted small mb-4">
                            Levamos a música angolana para o mundo. Distribuição digital,
                            marketing e gestão de carreira num só lugar.
                        </p>
                        <div class="d-flex gap-3 flex-wrap" role="list" aria-label="Redes sociais">
                            <?php if (cfg('instagram_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="Instagram"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-instagram"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (cfg('facebook_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="Facebook"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (cfg('youtube_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="YouTube"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-youtube"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (cfg('linkedin_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="LinkedIn"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-linkedin-in"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($whatsNum): ?>
                            <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="WhatsApp"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Empresa -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Empresa</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="../../about"
                                    class="text-reset text-decoration-none hover-white">Sobre</a></li>
                            <li class="mb-2"><a href="../../about#nossamarca"
                                    class="text-reset text-decoration-none hover-white">A nossa marca</a></li>
                            <li class="mb-2"><a href="../../plan/all-plans"
                                    class="text-reset text-decoration-none hover-white">Planos</a></li>
                            <li class="mb-2"><a href="customized-services"
                                    class="text-reset text-decoration-none hover-white">Serviços Premium</a></li>
                        </ul>
                    </div>

                    <!-- Suporte -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Suporte</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento</a>
                            </li>
                            <li class="mb-2"><a href="../support/help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="../../contact"
                                    class="text-reset text-decoration-none hover-white">Contacta-nos</a></li>
                            <?php if ($whatsNum): ?>
                            <li class="mb-2">
                                <a href="https://wa.me/<?php echo $whatsNum; ?>"
                                    class="text-reset text-decoration-none hover-white">WhatsApp</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Contacto -->
                    <div class="col-lg-3 col-12">
                        <h3 class="fw-bold mb-3">Contacto</h3>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-3">
                                <?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?>
                                — <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?>
                            </li>
                            <?php if (cfg('info_email')): ?>
                            <li class="mb-3">
                                <a href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                    class="text-reset text-decoration-none">
                                    <?php echo htmlspecialchars(cfg('info_email')); ?></a>
                            </li>
                            <?php endif; ?>
                            <?php if (cfg('support_email')): ?>
                            <li class="mb-3">
                                <a href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                    class="text-reset text-decoration-none">
                                    <?php echo htmlspecialchars(cfg('support_email')); ?></a>
                            </li>
                            <?php endif; ?>
                            <li>Seg — Sex: 08h às 17h</li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Copyright -->
            <div class="row py-4 mt-6 border-top border-white-10 align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="text-muted small mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 small">
                        <li class="list-inline-item">
                            <a href="../politicies/privacy" class="text-reset text-decoration-none">Política de
                                Privacidade</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="../politicies/terms" class="text-reset text-decoration-none">Termos de Uso</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="../politicies/cookies" class="text-reset text-decoration-none">Cookies</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <!-- ══ Footer fim ════════════════════════════════════════════════════════ -->

    <!-- Cookie Consent -->
    <div id="cookie-alert" class="cookie-alert" role="alertdialog" aria-labelledby="cookie-alert-title"
        aria-describedby="cookie-alert-description">
        <div class="cookie-alert-content">
            <div class="cookie-alert-header">
                <h3 id="cookie-alert-title" class="cookie-alert-title">
                    <i data-feather="cookie" aria-hidden="true"></i> Uso de Cookies
                </h3>
                <button type="button" class="cookie-alert-close" id="cookie-alert-close"
                    aria-label="Fechar banner de cookies">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="cookie-alert-body">
                <p id="cookie-alert-description" class="cookie-alert-text">
                    A <?php echo $siteName; ?> utiliza cookies e tecnologias similares para melhorar a sua
                    experiência de navegação, personalizar conteúdo e analisar o nosso tráfego.
                    Ao clicar em "Aceitar todos", concorda com o uso de todos os cookies.
                </p>
                <div class="cookie-alert-links">
                    <a href="../politicies/cookies" class="cookie-alert-link" target="_blank"
                        rel="noopener noreferrer">Política de Cookies</a>
                    <a href="../politicies/privacy" class="cookie-alert-link" target="_blank"
                        rel="noopener noreferrer">Política de Privacidade</a>
                </div>
                <div class="cookie-alert-actions">
                    <button type="button" class="btn btn-outline-light cookie-alert-btn" id="reject-cookies">Rejeitar
                        todos</button>
                    <button type="button" class="btn btn-primary cookie-alert-btn" id="accept-cookies">Aceitar
                        todos</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to top -->
    <div class="btn-scroll-top">
        <svg class="progress-square svg-content" width="100%" height="100%" viewBox="0 0 40 40">
            <path
                d="M8 1H32C35.866 1 39 4.13401 39 8V32C39 35.866 35.866 39 32 39H8C4.13401 39 1 35.866 1 32V8C1 4.13401 4.13401 1 8 1Z" />
        </svg>
    </div>

    <!-- Theme switcher -->
    <div class="customizer_1">
        <div class="position-absolute end-0 bottom-0 m-4 fixed">
            <div class="dropdown">
                <button class="btn btn-wasomupfy rounded-circle d-flex align-items-center" type="button"
                    aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme">
                    <i class="fa-solid fa-circle-half-stroke"></i>
                    <span class="visually-hidden bs-theme-text">Tema do Site</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bs-theme-text">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center"
                            data-bs-theme-value="light" aria-pressed="false">
                            <i class="fa-solid fa-sun"></i><span class="ms-2">Claro</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"
                            aria-pressed="false">
                            <i class="fa-solid fa-moon"></i><span class="ms-2">Escuro</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center active"
                            data-bs-theme-value="auto" aria-pressed="true">
                            <i class="fa-solid fa-display"></i><span class="ms-2">Sistema</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ══ Modal Feedback ════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-labelledby="modalFeedbackLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-wasomupfy text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalFeedbackLabel">
                        <i class="fa-solid fa-bullhorn me-2"></i> A sua opinião importa!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">
                        Como tem sido a sua experiência com a <strong><?php echo $siteName; ?></strong>?
                        As suas sugestões ajudam-nos a evoluir.
                    </p>
                    <div id="feedback-modal-msg" class="alert d-none mb-3" role="alert"></div>
                    <form id="formFeedback" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>" />
                        <input type="hidden" name="page_origin" value="music-promotion-modal" />
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seu Nome</label>
                            <input type="text" class="form-control" name="name_fb" placeholder="Ex: André Wasom"
                                required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assunto</label>
                            <select class="form-select" name="subject_fb">
                                <option>Sugestão de melhoria</option>
                                <option>Elogio</option>
                                <option>Relatar um problema</option>
                                <option>Outros</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">A sua Mensagem</label>
                            <textarea class="form-control" rows="4" name="message_fb"
                                placeholder="Conte-nos em detalhes..." required></textarea>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-wasomupfy btn-lg" id="btn-feedback-modal">
                                Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <small class="text-muted"><?php echo $siteName; ?> agradece a tua parceria!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Scripts ═══════════════════════════════════════════════════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/headhesive@1.2.4/dist/headhesive.min.js"></script>
    <script src="../../js/theme.min.js"></script>
    <script src="../../js/vendors/color-modes.js"></script>
    <script src="../../js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="../../js/vendors/scrollcue.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-injector@1.1.3/dist/svg-injector.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    <script src="https://unpkg.com/in-view@0.6.1/dist/in-view.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sticky-kit/1.1.3/sticky-kit.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jarallax/2.1.4/jarallax.min.js"></script>
    <script src="../../js/sw-register.js"></script>

    <script>
    (function() {
        'use strict';

        /* ── Feather Icons ─────────────────────────────── */
        if (typeof feather !== 'undefined') feather.replace();

        /* ── Smooth scroll para #pacotes ───────────────── */
        document.querySelectorAll('.smooth-scroll').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        /* ── AJAX helper ───────────────────────────────── */
        function ajaxPost(url, data, msgEl, btnEl, defaultLabel, onSuccess) {
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';
            }
            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (!msgEl) return;
                    msgEl.className = 'alert ' + (res.success ? 'alert-success' : 'alert-danger');
                    msgEl.textContent = res.message || (res.success ? 'Enviado com sucesso!' :
                        'Ocorreu um erro.');
                    msgEl.classList.remove('d-none');
                    if (res.success && onSuccess) onSuccess();
                    setTimeout(function() {
                        msgEl.classList.add('d-none');
                    }, 7000);
                })
                .catch(function() {
                    if (!msgEl) return;
                    msgEl.className = 'alert alert-danger';
                    msgEl.textContent = 'Erro de ligação. Tente novamente.';
                    msgEl.classList.remove('d-none');
                })
                .finally(function() {
                    if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.innerHTML = defaultLabel;
                    }
                });
        }

        /* ── Modal Feedback ────────────────────────────── */
        var fModal = document.getElementById('formFeedback');
        if (fModal) {
            fModal.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!fModal.checkValidity()) {
                    fModal.classList.add('was-validated');
                    return;
                }
                ajaxPost(
                    '/ajax/feedback.php', {
                        csrf: fModal.querySelector('[name="csrf_token"]').value,
                        name: fModal.querySelector('[name="name_fb"]').value.trim(),
                        subject: fModal.querySelector('[name="subject_fb"]').value.trim(),
                        message: fModal.querySelector('[name="message_fb"]').value.trim(),
                        page: window.location.pathname
                    },
                    document.getElementById('feedback-modal-msg'),
                    document.getElementById('btn-feedback-modal'),
                    'Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>',
                    function() {
                        fModal.reset();
                        setTimeout(function() {
                            var m = bootstrap.Modal.getInstance(document.getElementById(
                                'modalFeedback'));
                            if (m) m.hide();
                        }, 2500);
                    }
                );
            });
        }

        /* ── Cookie Consent ────────────────────────────── */
        var cookieAlert = document.getElementById('cookie-alert');
        if (cookieAlert && !localStorage.getItem('wu_cookies')) {
            cookieAlert.classList.add('show');
        }
        var btnAccept = document.getElementById('accept-cookies');
        var btnReject = document.getElementById('reject-cookies');
        var btnClose = document.getElementById('cookie-alert-close');

        function closeCookieAlert(val) {
            if (val) localStorage.setItem('wu_cookies', val);
            if (cookieAlert) cookieAlert.classList.remove('show');
        }
        if (btnAccept) btnAccept.addEventListener('click', function() {
            closeCookieAlert('accepted');
        });
        if (btnReject) btnReject.addEventListener('click', function() {
            closeCookieAlert('rejected');
        });
        if (btnClose) btnClose.addEventListener('click', function() {
            closeCookieAlert('');
        });

    })();
    </script>

</body>

</html>