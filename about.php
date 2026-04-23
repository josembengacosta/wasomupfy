<?php
// ══════════════════════════════════════════════
// WASOM UPFY — Sobre Nós
// Arquivo: about.php (raiz do site)
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/site.php';

checkPlatformStatus('about');
trackVisitor('/about', 'Sobre Nós — Wasom Upfy');

$plans       = getPlans();
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];
$stores      = (int)$platform['stores_count'];
?>
<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />

    <!-- SEO dinâmico -->
    <title><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> | Sobre</title>
    <meta name="description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'A Wasom Upfy é a plataforma de distribuição de música mais fácil e completa do mercado, focada na promoção de artistas independentes.')); ?>" />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_keywords', 'Wasom Upfy, sobre, quem somos, a nossa marca, distribuição musical Angola')); ?>" />

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:type" content="website" />
    <meta property="og:title"
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> — Saiba tudo sobre nós" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'Plataforma de distribuição musical de Angola para o mundo.')); ?>" />
    <meta property="og:url"
        content="<?php echo htmlspecialchars(cfg('site_url', 'https://wasomupfy.rf.gd')); ?>/about" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', cfg('site_url', 'https://wasomupfy.rf.gd') . '/imgs/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />

    <!-- Loader JS -->
    <script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                document.querySelector("body").classList.add("loaded");
            }, 200);
        });
    </script>

    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="css/theme.min.css" />
    <link rel="stylesheet" href="js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="css/framework.css" />
    <link rel="stylesheet" href="css/main.css" />
</head>

<body data-base-path=".">
    <!-- Preloader -->
    <div class="preloader">
        <img src="assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="Loading-wasomupfy" />
    </div>

    <!-- Navbar -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="home" title="Home">
                    <img src="assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo Wasom Upfy" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Logotipo" href="home">
                            <img width="65" src="assets/img/brand/wasomupfy_brand.png" alt="Logo Wasom Upfy" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item">
                                <a class="nav-link" href="home" title="Inicio">Início</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="about" title="Sobre">Sobre</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="blog/" title="Blogue" target="_blank"
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
                                            class="dropdown-item mb-3 text-body" href="plan/<?php echo $nSlug; ?>">
                                            <div class="d-flex align-items-center">
                                                <i class="fa-solid <?php echo $nIcon; ?> text-wasomupfy fs-3"
                                                    style="width: 35px"></i>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                                    <p class="mb-0 fs-6">Nosso plano
                                                        <?php echo htmlspecialchars($p['name_plan']); ?> —
                                                        <?php echo $nPrc; ?> Kz<?php echo $nPer; ?></p>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                    <a title="Todos os planos" class="dropdown-item mb-3 text-body"
                                        href="plan/all-plans">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-layer-group text-wasomupfy fs-3"
                                                style="width: 35px"></i>
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
                                    aria-expanded="false">Páginas <i data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a title="Novidades" class="dropdown-item" href="blog/">Novidades</a>
                                            <a title="Passatempo Wasom Upfy" class="dropdown-item"
                                                href="blog/">Passatempo</a>
                                            <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                <span class="badge bg-warning">Indisponível</span></a>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a title="A nossa marca" class="dropdown-item"
                                                    href="about?#nossamarca">A nossa marca</a>
                                                <a title="Parcerias" class="dropdown-item"
                                                    href="partnership">Parcerias</a>
                                                <a title="Quem somos" class="dropdown-item"
                                                    href="about#nossa-historia">Quem somos</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Serviços</div>
                                                <a title="Distribuição de música" class="dropdown-item"
                                                    href="page/services/music-distribution">Distribuição de música</a>
                                                <a title="Promoção de música" class="dropdown-item"
                                                    href="page/services/music-promotion">Promoção de música
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Indisponível" class="dropdown-item"
                                                    href="page/services/customized-services">Serviços personalizados
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052">Atendimento</a>
                                                <a title="Contacto-nos" class="dropdown-item"
                                                    href="contact">Contacta-nos</a>
                                                <a title="Canal WhatsApp" class="dropdown-item"
                                                    href="https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04">Canal
                                                    WhatsApp</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Sugestões</div>
                                                <a title="Ajuda" class="dropdown-item" href="page/support/help">Ajuda
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Feedback" class="dropdown-item" href="#"
                                                    data-bs-toggle="modal" data-bs-target="#modalFeedback">Feedback</a>
                                                <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                                <div class="mt-3">
                                                    <div class="dropdown-header">Ajuda</div>
                                                    <a title="Tutorial" class="dropdown-item"
                                                        href="page/support/tutorial">Tutorial
                                                        <span class="badge bg-success">Novo</span></a>
                                                    <a title="Ocorreu um erro" class="dropdown-item"
                                                        href="page/support/support">Suporte técnico</a>
                                                    <a title="Perguntas frequentes" class="dropdown-item"
                                                        href="page/support/faq">Perguntas frequentes</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="resources" title="Recursos">Recursos</a>
                            </li>

                            <li class="nav-item dropdown">
                                <a title="Contacto" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">Contactar <i data-feather="chevron-down"></i></a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a title="Caixa de mensagem" class="dropdown-item" href="contact">Caixa de
                                            mensagem</a>
                                    </li>
                                    <li>
                                        <?php if (cfg('support_email')): ?>
                                            <a title="E-mail" class="dropdown-item"
                                                href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>">
                                                <?php echo htmlspecialchars(cfg('support_email')); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                    <li>
                                        <?php if (cfg('whatsapp_number')): ?>
                                            <a title="WhatsApp" class="dropdown-item"
                                                href="https://api.whatsapp.com/send/?phone=<?php echo preg_replace('/[^0-9]/', '', cfg('whatsapp_number')); ?>&text&type=phone_number&app_absent=0">
                                                WhatsApp
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <div class="mt-3 mt-lg-0 d-flex align-items-center">
                            <a title="Sign-in" href="login" class="btn btn-secondary mx-2">
                                Entrar <i data-feather="log-in"></i>
                            </a>
                            <?php if ($canRegister): ?>
                                <a title="Sign-up" href="register" class="btn btn-wasomupfy">Inscreva-se</a>
                            <?php else: ?>
                                <span class="btn btn-secondary disabled">Inscrições fechadas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Section com parallax -->
        <section class="about-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="assets/img/theme/about.png" alt="Sobre Wasom Upfy" loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Sobre Nós</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">
                            Conheça a <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>
                        </h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Transformando talentos musicais em sucessos globais desde 2021
                        </p>
                        <a href="#nossa-historia" class="btn btn-wasomupfy btn-lg mt-2 smooth-scroll">
                            Descubra nossa jornada <i class="bi bi-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Counter -->
        <section class="py-4 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row g-4 text-center">
                    <div class="col-md-3 col-6" data-cue="zoomIn">
                        <div class="stat-card p-4 rounded-4 shadow-sm">
                            <div class="stat-icon mb-3">
                                <i class="bi bi-globe-americas fs-1 text-wasomupfy"></i>
                            </div>
                            <h3 class="stat-number display-5 fw-bold text-dark mb-2"
                                data-counter="<?php echo $stores ?: 150; ?>">0</h3>
                            <p class="stat-label text-muted mb-0">Plataformas Globais</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6" data-cue="zoomIn" data-delay="100">
                        <div class="stat-card p-4 rounded-4 shadow-sm">
                            <div class="stat-icon mb-3">
                                <i class="bi bi-music-note-beamed fs-1 text-wasomupfy"></i>
                            </div>
                            <h3 class="stat-number display-5 fw-bold text-dark mb-2" data-counter="240">0</h3>
                            <p class="stat-label text-muted mb-0">Artistas Atendidos</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6" data-cue="zoomIn" data-delay="200">
                        <div class="stat-card p-4 rounded-4 shadow-sm">
                            <div class="stat-icon mb-3">
                                <i class="bi bi-headphones fs-1 text-wasomupfy"></i>
                            </div>
                            <h3 class="stat-number display-5 fw-bold text-dark mb-2" data-counter="2500">0</h3>
                            <p class="stat-label text-muted mb-0">Milhões de Streams</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6" data-cue="zoomIn" data-delay="300">
                        <div class="stat-card p-4 rounded-4 shadow-sm">
                            <div class="stat-icon mb-3">
                                <i class="bi bi-flag fs-1 text-wasomupfy"></i>
                            </div>
                            <h3 class="stat-number display-5 fw-bold text-dark mb-2" data-counter="6">0</h3>
                            <p class="stat-label text-muted mb-0">Países Alcançados</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nossa História -->
        <section id="nossa-historia" class="py-5" data-cue="fadeIn">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6" data-cue="slideInLeft">
                        <span
                            class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                            Nossa Origem
                        </span>
                        <h2 class="display-6 fw-bold mb-4">Quem Somos?</h2>
                        <div class="timeline ps-3 border-start border-wasom">
                            <div class="timeline-item mb-4">
                                <div class="timeline-date text-wasomupfy fw-semibold">2021 - 2022</div>
                                <h4 class="h5 mb-2">O Início de uma Revolução</h4>
                                <p>
                                    A <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> é idealizada por
                                    <strong>Cristiano Amadeu</strong> em Luanda. O foco inicial foi preencher a lacuna
                                    entre o talento
                                    independente angolano e as grandes prateleiras digitais do mundo, estabelecendo os
                                    primeiros pilares
                                    de distribuição.
                                </p>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-date text-wasomupfy fw-semibold">2023</div>
                                <h4 class="h5 mb-2">Ecossistema Digital Wasom</h4>
                                <p>
                                    Consolidamos nossa presença no <strong>Instagram, YouTube e LinkedIn</strong>,
                                    criando uma
                                    comunidade vibrante. Implementamos tecnologia própria para garantir que artistas
                                    africanos chegassem
                                    a mais de 150 países com transparência total.
                                </p>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-date text-wasomupfy fw-semibold">2024</div>
                                <h4 class="h5 mb-2">Referência em Distribuição Africana</h4>
                                <p>
                                    Tornamo-nos uma autoridade no mercado, alcançando a marca de
                                    <strong>240 artistas agenciados</strong>. Nossa rede estratégica expandiu-se,
                                    garantindo parcerias
                                    diretas com curadores de playlists globais.
                                </p>
                            </div>
                            <div class="timeline-item mb-4">
                                <div class="timeline-date text-wasomupfy fw-semibold">2025</div>
                                <h4 class="h5 mb-2">Tecnologia e Inteligência Musical</h4>
                                <p>
                                    Lançamento de ferramentas de análise de dados exclusivas para nossos artistas,
                                    permitindo que eles
                                    entendam onde seu público está e como maximizar os lucros do seu catálogo musical.
                                </p>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-date text-wasomupfy fw-semibold">2026</div>
                                <h4 class="h5 mb-2">O Futuro é Agora</h4>
                                <p>
                                    A <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> posiciona-se como
                                    a principal
                                    ponte tecnológica para a música africana global. Com presença em Luanda e alcance em
                                    todos os
                                    continentes, somos o padrão ouro para quem busca impacto e sustentabilidade
                                    artística.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6" data-cue="slideInRight">
                        <div class="about-image-wrapper position-relative">
                            <img src="assets/img/theme/story.png" alt="Equipe Wasom Upfy"
                                class="rounded-4 img-fluid shadow-lg" loading="lazy" />
                            <div
                                class="about-badge bg-wasom text-wasomupfy rounded-3 p-3 shadow position-absolute bottom-0 start-0 translate-middle">
                                <h5 class="mb-1">+240 Artistas</h5>
                                <p class="small mb-0 opacity-75 text-wasomupfy font-bolder">Empoderados</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Missão, Visão e Valores -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-4" data-cue="zoomIn">
                        <div class="card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="icon-wrapper bg-wasom-light rounded-3 p-2 mb-3 d-inline-flex">
                                    <i class="bi bi-bullseye fs-1 text-wasomupfy"></i>
                                </div>
                                <h3 class="h4 mb-3 fw-bold">Nossa Missão</h3>
                                <p class="mb-0 text-muted">
                                    Acelerar a carreira de artistas independentes através de uma infraestrutura
                                    tecnológica de ponta,
                                    garantindo que a música africana rompa fronteiras e gere receita real em escala
                                    global.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4" data-cue="zoomIn" data-delay="100">
                        <div class="card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="icon-wrapper bg-wasom-light rounded-3 p-2 mb-3 d-inline-flex">
                                    <i class="bi bi-eye fs-1 text-wasomupfy"></i>
                                </div>
                                <h3 class="h4 mb-3 fw-bold">Nossa Visão</h3>
                                <p class="mb-0 text-muted">
                                    Ser a maior autoridade global em distribuição e marketing de ritmos africanos, sendo
                                    reconhecida
                                    como o parceiro número 1 de artistas que buscam profissionalismo e liderança no
                                    mercado digital.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4" data-cue="zoomIn" data-delay="200">
                        <div class="card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="icon-wrapper bg-wasom-light rounded-3 p-2 mb-3 d-inline-flex">
                                    <i class="bi bi-heart fs-1 text-wasomupfy"></i>
                                </div>
                                <h3 class="h4 mb-3 fw-bold">Nossos Valores</h3>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2 mt-1"></i>
                                        <span><span class="fw-semibold">Inovação Orientada a Dados:</span> Decisões
                                            inteligentes para o sucesso.</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2 mt-1"></i>
                                        <span><span class="fw-semibold">Transparência Radical:</span> O artista sempre
                                            no controle dos ganhos.</span>
                                    </li>
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2 mt-1"></i>
                                        <span><span class="fw-semibold">Paixão Cultural:</span> Elevando a essência da
                                            nossa arte ao mundo.</span>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2 mt-1"></i>
                                        <span><span class="fw-semibold">Excelência Executiva:</span> Agilidade e rigor
                                            em cada lançamento.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- O Que Fazemos -->
        <section id="oque-fazemos" class="py-5" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8 text-center">
                        <span
                            class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-3 py-2 mb-3 d-inline-block">
                            Nossos Serviços
                        </span>
                        <h2 class="display-6 fw-bold mb-4">O Que Fazemos?</h2>
                        <p class="lead text-muted">Oferecemos soluções completas para levar sua música ao mundo</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-6" data-cue="slideInUp">
                        <div class="service-card card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="service-icon text-wasomupfy rounded-3 p-3 me-3">
                                        <i class="bi bi-cloud-upload fs-1"></i>
                                    </div>
                                    <div>
                                        <h3 class="h4 mb-2">Distribuição Digital Global</h3>
                                        <p class="text-muted mb-0">Levamos sua música para todas as plataformas
                                            principais</p>
                                    </div>
                                </div>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Spotify, Apple Music, Deezer, Tidal</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>YouTube, TikTok, Instagram Music</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Mais de <?php echo $stores ?: 200; ?> serviços mundiais</span>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Distribuição em 24-48 horas</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" data-cue="slideInUp" data-delay="100">
                        <div class="service-card card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="service-icon text-wasomupfy rounded-3 p-3 me-3">
                                        <i class="bi bi-bar-chart fs-1"></i>
                                    </div>
                                    <div>
                                        <h3 class="h4 mb-2">Marketing Musical Estratégico</h3>
                                        <p class="text-muted mb-0">Maximizamos seu alcance com campanhas inteligentes
                                        </p>
                                    </div>
                                </div>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Campanhas segmentadas em redes sociais</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Otimização para algoritmos de streaming</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Gestão de tráfego pago (Ads)</span>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Parcerias com influenciadores</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" data-cue="slideInUp" data-delay="200">
                        <div class="service-card card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="service-icon text-wasomupfy rounded-3 p-3 me-3">
                                        <i class="bi bi-music-note-list fs-1"></i>
                                    </div>
                                    <div>
                                        <h3 class="h4 mb-2">Curadoria &amp; Playlisting</h3>
                                        <p class="text-muted mb-0">Colocamos sua música nas playlists certas</p>
                                    </div>
                                </div>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Inclusão em playlists temáticas próprias</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Pitch para editores de Spotify/Apple Music</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Parcerias com curadores independentes</span>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Análise de dados para otimização</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" data-cue="slideInUp" data-delay="300">
                        <div class="service-card card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="service-icon text-wasomupfy rounded-3 p-3 me-3">
                                        <i class="bi bi-person-badge fs-1"></i>
                                    </div>
                                    <div>
                                        <h3 class="h4 mb-2">Assessoria Artística</h3>
                                        <p class="text-muted mb-0">Desenvolvemos sua carreira musical</p>
                                    </div>
                                </div>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Desenvolvimento de identidade visual/sonora</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Planejamento estratégico de carreira</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Gestão de branding e comunicação</span>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-wasomupfy me-2"></i>
                                        <span>Consultoria para lançamentos</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Presença Global -->
        <section class="py-5" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8 text-center">
                        <span
                            class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-3 py-2 mb-3 d-inline-block">
                            Alcance Mundial
                        </span>
                        <h2 class="display-6 fw-bold mb-4">Presença Global</h2>
                        <p class="lead text-muted">Sediados em Luanda, alcançamos o mundo através da tecnologia</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4" data-cue="zoomIn">
                        <div class="global-card text-center p-4">
                            <div class="global-icon mb-3">
                                <i class="bi bi-cpu fs-1 text-wasomupfy"></i>
                            </div>
                            <h4 class="h5 mb-3">Tecnologia Própria</h4>
                            <p class="text-muted mb-0">
                                Desenvolvemos soluções digitais exclusivas que automatizam processos e garantem
                                eficiência
                                na distribuição global.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4" data-cue="zoomIn" data-delay="100">
                        <div class="global-card text-center p-4">
                            <div class="global-icon mb-3">
                                <i class="bi bi-diagram-3 fs-1 text-wasomupfy"></i>
                            </div>
                            <h4 class="h5 mb-3">Rede Estratégica</h4>
                            <p class="text-muted mb-0">
                                Parcerias com plataformas, curadores e influenciadores em todos os continentes para
                                maximizar o alcance.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4" data-cue="zoomIn" data-delay="200">
                        <div class="global-card text-center p-4">
                            <div class="global-icon mb-3">
                                <i class="bi bi-shield-check fs-1 text-wasomupfy"></i>
                            </div>
                            <h4 class="h5 mb-3">Sustentabilidade Artística</h4>
                            <p class="text-muted mb-0">
                                Modelos de negócio que valorizam o trabalho criativo e garantem retorno justo para
                                os artistas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nosso Board -->
        <section class="py-5 bg-light-100" id="nosso-board" data-cue="fadeIn">
            <div class="container text-center">
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-6">
                        <span class="badge bg-wasomupfy rounded-pill px-3 py-2 mb-3 d-inline-block">Nosso Board</span>
                        <h2 class="display-6 fw-bold mt-2">Liderança Extraordinária</h2>
                        <div class="divider-center bg-wasomupfy mb-2" style="width: 50px; height: 3px; margin: 0 auto">
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-5 col-lg-4" data-cue="zoomIn">
                        <div class="team-card p-4 rounded-5 transition-all hover-lift border-0 shadow-sm bg-light-100">
                            <div class="position-relative mb-4 d-inline-block">
                                <img src="https://media.licdn.com/dms/image/v2/D4D03AQH2TwTrIPX5sA/profile-displayphoto-scale_200_200/B4DZuvfPHZJQAY-/0/1768175763296?e=2147483647&v=beta&t=wXk2jDLxJ3ukV2cJXE3ge-e5bFvtoY1WusRqAHJneRM"
                                    class="rounded-circle border border-4 border-wasomupfy" width="180" height="180"
                                    style="object-fit: cover" alt="Cristiano Amadeu" />
                                <div class="position-absolute bottom-0 end-0 bg-wasomupfy text-white rounded-circle p-2 shadow"
                                    style="width:45px;height:45px;display:flex;align-items:center;justify-content:center">
                                    <i class="fa-solid fa-crown small"></i>
                                </div>
                            </div>
                            <h3 class="h4 fw-bold mb-1">Cristiano Amadeu</h3>
                            <p class="text-wasomupfy fw-semibold mb-3">Fundador &amp; CEO</p>
                            <p class="text-muted small mb-4 px-3">
                                Visionário e estratega, liderando a
                                <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>
                                na missão de digitalizar e globalizar a música africana.
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="https://www.linkedin.com/in/cristiano-amadeu"
                                    class="btn btn-icon btn-outline-wasomupfy rounded-circle btn-sm shadow-sm border-light"
                                    target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                                <a href="https://www.instagram.com/cristiano_amadeu_"
                                    class="btn btn-icon btn-outline-wasomupfy rounded-circle btn-sm shadow-sm border-light"
                                    target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-instagram"></i>
                                </a>
                                <a href="https://facebook.com/cristianoamadeuofficial"
                                    class="btn btn-icon btn-outline-wasomupfy rounded-circle btn-sm shadow-sm border-light"
                                    target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-facebook"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nossa Marca -->
        <section id="nossamarca" class="py-5" data-cue="fadeIn">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6" data-cue="slideInLeft">
                        <span class="badge bg-wasomupfy rounded-pill px-3 py-2 mb-3 d-inline-block">Identidade</span>
                        <h2 class="display-6 fw-bold mb-4">Nossa Marca</h2>
                        <p class="lead mb-4 opacity-90">
                            Mais que uma empresa, somos uma <strong>plataforma de expressão artística</strong> que
                            conecta
                            talento, inovação e impacto global.
                        </p>
                        <div class="brand-values">
                            <div class="brand-value-item d-flex align-items-start mb-3">
                                <div class="brand-icon text-wasomupfy rounded-2 p-2 me-3">
                                    <i class="bi bi-lightning-charge fs-1"></i>
                                </div>
                                <div>
                                    <h4 class="h5 mb-1">Energia</h4>
                                    <p class="small opacity-75 mb-0">Movimento constante rumo à inovação</p>
                                </div>
                            </div>
                            <div class="brand-value-item d-flex align-items-start mb-3">
                                <div class="brand-icon text-wasomupfy rounded-2 p-2 me-3">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                                <div>
                                    <h4 class="h5 mb-1">Empoderamento</h4>
                                    <p class="small opacity-75 mb-0">Artistas no centro de cada decisão</p>
                                </div>
                            </div>
                            <div class="brand-value-item d-flex align-items-start">
                                <div class="brand-icon text-wasomupfy rounded-2 p-2 me-3">
                                    <i class="bi bi-globe2 fs-1"></i>
                                </div>
                                <div>
                                    <h4 class="h5 mb-1">Impacto Global</h4>
                                    <p class="small opacity-75 mb-0">Vozes africanas em todos os continentes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6" data-cue="slideInRight">
                        <div class="brand-showcase">
                            <div class="brand-card card rounded-4 p-4 shadow-lg mb-4">
                                <div class="text-center">
                                    <img src="assets/img/brand/wasomupfy_brand.png"
                                        alt="Logo <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>"
                                        class="img-fluid mb-3" width="200" loading="lazy" style="border-radius: 50%" />
                                    <h5 class="mb-2"><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>
                                    </h5>
                                    <p class="small mb-3">Onde música encontra propósito</p>
                                    <?php if (cfg('youtube_url')): ?>
                                        <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                            rel="noopener noreferrer" class="btn btn-danger btn-sm">
                                            <i class="bi bi-youtube me-1"></i> Canal Oficial
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-center">
                                Cada detalhe da nossa marca foi pensado para refletir
                                <strong>autenticidade</strong>, <strong>modernidade</strong> e
                                <strong>compromisso</strong> com a diversidade sonora.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="display-6 fw-bold mb-4">Pronto para levar sua música ao mundo?</h2>
                        <p class="lead text-muted mb-5">
                            Junte-se a centenas de artistas que já transformaram suas carreiras com a
                            <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <?php if ($canRegister): ?>
                                <a href="register" class="btn btn-wasomupfy btn-lg px-5">
                                    Começar Agora <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn btn-secondary btn-lg px-5 disabled">Inscrições Fechadas</span>
                            <?php endif; ?>
                            <a href="contact" class="btn btn-outline-secondary btn-lg px-5">Fale Conosco</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="divider-fade"></div>

    <!-- Footer -->
    <footer class="bg-light-100 pt-7" role="contentinfo" aria-label="Rodapé do site">
        <div class="container">
            <!-- Newsletter -->
            <div class="row align-items-center mb-7 border-bottom border-white-10 pb-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h3 class="fw-bold mb-1">Junte-se a +10.000 Artistas</h3>
                    <p class="lead text-muted mb-0">Receba dicas de marketing, novidades da indústria e ofertas
                        exclusivas.</p>
                </div>
                <div class="col-lg-6">
                    <form action="#" class="row g-2">
                        <div class="col-sm-8">
                            <input type="email" class="form-control border-0 text-muted py-3" required
                                placeholder="Seu melhor e-mail" />
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-wasomupfy w-100 py-3 fw-bold">Inscrever</button>
                        </div>
                    </form>
                </div>
            </div>

            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">
                    <!-- Logo + Socials -->
                    <div class="col-lg-3 col-12">
                        <a href="home" class="d-inline-block mb-4 navbar-brand">
                            <img src="assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy" width="65" class="img-logo"
                                height="60" />
                        </a>
                        <p class="lead text-muted small mb-4">
                            Levamos a música angolana para o mundo. Distribuição digital, marketing e gestão de carreira
                            num só lugar.
                        </p>
                        <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
                            <?php if (cfg('instagram_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="Instagram da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-instagram"></i><span class="visually-hidden">Instagram</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('facebook_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="Facebook da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-facebook-f"></i><span class="visually-hidden">Facebook</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('youtube_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="YouTube da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-youtube"></i><span class="visually-hidden">YouTube</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('linkedin_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="LinkedIn da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-linkedin-in"></i><span class="visually-hidden">LinkedIn</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('whatsapp_number')): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', cfg('whatsapp_number')); ?>"
                                    target="_blank" rel="external noopener noreferrer"
                                    aria-label="WhatsApp da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-whatsapp"></i><span class="visually-hidden">WhatsApp</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Empresa -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Empresa</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="about"
                                    class="text-reset text-decoration-none hover-white">Sobre</a></li>
                            <li class="mb-2"><a href="about#nossamarca"
                                    class="text-reset text-decoration-none hover-white">A nossa marca</a></li>
                            <li class="mb-2"><a href="plan/all-plans"
                                    class="text-reset text-decoration-none hover-white">Planos</a></li>
                            <li class="mb-2"><a href="page/services/customized-services"
                                    class="text-reset text-decoration-none hover-white">Serviços Premium</a></li>
                        </ul>
                    </div>

                    <!-- Suporte -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Suporte</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento</a></li>
                            <li class="mb-2"><a href="page/support/help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="contact"
                                    class="text-reset text-decoration-none hover-white">Contacta-nos</a></li>
                            <li class="mb-2">
                                <?php if (cfg('whatsapp_number')): ?>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', cfg('whatsapp_number')); ?>"
                                        class="text-reset text-decoration-none hover-white">WhatsApp</a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>

                    <!-- Contacto -->
                    <div class="col-lg-3 col-12">
                        <h3 class="fw-bold mb-3">Contacto</h3>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-3 d-flex"><span>Angola - Luanda</span></li>
                            <?php if (cfg('support_email')): ?>
                                <li class="mb-3 d-flex">
                                    <a href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                        class="text-reset text-decoration-none">
                                        <?php echo htmlspecialchars(cfg('support_email')); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (cfg('info_email')): ?>
                                <li class="mb-3 d-flex">
                                    <a href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                        class="text-reset text-decoration-none">
                                        <?php echo htmlspecialchars(cfg('info_email')); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="d-flex"><span>Seg - Sex: 08h às 17h</span></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Copyright -->
            <div class="row py-4 mt-6 border-top border-white-10 align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="text-muted small mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>.
                        Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 small">
                        <li class="list-inline-item">
                            <a href="page/politicies/privacy" class="text-reset text-decoration-none">Política de
                                Privacidade</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="page/politicies/terms" class="text-reset text-decoration-none">Termos de Uso</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="page/politicies/cookies" class="text-reset text-decoration-none">Cookies</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll top -->
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
                    aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme (auto)">
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

    <!-- Modal Feedback -->
    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-labelledby="modalFeedbackLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-wasomupfy text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalFeedbackLabel">
                        <i class="fa-solid fa-bullhorn me-2"></i> A tua opinião importa!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">
                        Como tem sido a tua experiência com a
                        <strong><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?></strong>?
                        As tuas sugestões ajudam-nos a evoluir.
                    </p>
                    <div id="feedbackAlert" class="alert d-none mb-3" role="alert"></div>
                    <form id="formFeedback" novalidate>
                        <input type="hidden" id="feedbackCsrf" name="csrf" value="<?php echo getSiteCsrf(); ?>">
                        <div class="mb-3">
                            <label for="feedbackName" class="form-label fw-semibold text-dark">O teu Nome</label>
                            <input type="text" class="form-control" id="feedbackName" name="name"
                                placeholder="Ex: André Wasom" maxlength="120" required>
                        </div>
                        <div class="mb-3">
                            <label for="feedbackSubject" class="form-label fw-semibold text-dark">Assunto</label>
                            <select class="form-select" id="feedbackSubject" name="subject">
                                <option value="Sugestão de melhoria">Sugestão de melhoria</option>
                                <option value="Elogio">Elogio</option>
                                <option value="Relatar um problema">Relatar um problema</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="feedbackMessage" class="form-label fw-semibold text-dark">A tua Mensagem</label>
                            <textarea class="form-control" id="feedbackMessage" name="message" rows="4"
                                placeholder="Conta-nos em detalhe..." maxlength="2000" required></textarea>
                            <div class="form-text text-end"><span id="feedbackCharCount">0</span>/2000</div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" id="feedbackSubmitBtn" class="btn btn-wasomupfy btn-lg">
                                <span id="feedbackBtnText">Enviar Feedback <i
                                        class="fa-solid fa-paper-plane ms-2"></i></span>
                                <span id="feedbackBtnLoading" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>A enviar...
                                </span>
                            </button>
                        </div>
                    </form>
                    <div id="feedbackSuccess" class="d-none text-center py-3">
                        <i class="fa-solid fa-circle-check text-success mb-3" style="font-size:3rem"></i>
                        <h5 class="fw-bold mb-2">Feedback enviado!</h5>
                        <p class="text-muted mb-4">Obrigado! A equipa vai analisar com atenção. <i
                                class="fa-solid fa-heart text-danger"></i></p>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <small class="text-muted"><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> agradece a
                        tua parceria!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Libs JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/headhesive@1.2.4/dist/headhesive.min.js"></script>
    <script src="js/theme.min.js"></script>
    <script src="js/vendors/color-modes.js"></script>
    <script src="js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="js/vendors/scrollcue.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-injector@1.1.3/dist/svg-injector.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    <script src="https://unpkg.com/in-view@0.6.1/dist/in-view.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sticky-kit/1.1.3/sticky-kit.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>

    <script>
        feather.replace({
            width: "1em",
            height: "1em"
        });
    </script>

    <script>
        !(function(e, t, a, n, g) {
            (e[n] = e[n] || []),
            e[n].push({
                "gtm.start": new Date().getTime(),
                event: "gtm.js"
            });
            var m = t.getElementsByTagName(a)[0],
                r = t.createElement(a);
            (r.async = !0),
            (r.src = "https://www.googletagmanager.com/gtm.js?id=GTM-MF4DZVH"),
            m.parentNode.insertBefore(r, m);
        })(window, document, "script", "dataLayer");
    </script>

    <!-- Animações e counter -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Stats Counter
            const counters = document.querySelectorAll("[data-counter]");
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const counter = entry.target;
                            const target = parseInt(counter.getAttribute("data-counter"));
                            const duration = 2000;
                            const step = target / (duration / 16);
                            let current = 0;
                            const timer = setInterval(() => {
                                current += step;
                                if (current >= target) {
                                    counter.textContent = target + (target >= 100 ? "+" : "");
                                    clearInterval(timer);
                                } else {
                                    counter.textContent = Math.floor(current);
                                }
                            }, 16);
                            observer.unobserve(counter);
                        }
                    });
                }, {
                    threshold: 0.5
                }
            );
            counters.forEach((counter) => observer.observe(counter));

            // Scroll animations
            const animateOnScroll = () => {
                const elements = document.querySelectorAll("[data-cue]");
                elements.forEach((element) => {
                    const elementTop = element.getBoundingClientRect().top;
                    if (elementTop < window.innerHeight - 150) {
                        element.classList.add("animated");
                    }
                });
            };
            window.addEventListener("scroll", animateOnScroll);
            animateOnScroll();
        });
    </script>

    <!-- Modal Feedback JS -->
    <script>
        (function() {
            'use strict';
            var form = document.getElementById('formFeedback');
            var alertEl = document.getElementById('feedbackAlert');
            var successEl = document.getElementById('feedbackSuccess');
            var submitBtn = document.getElementById('feedbackSubmitBtn');
            var btnText = document.getElementById('feedbackBtnText');
            var btnLoading = document.getElementById('feedbackBtnLoading');
            var csrfInput = document.getElementById('feedbackCsrf');
            var charCount = document.getElementById('feedbackCharCount');
            var textarea = document.getElementById('feedbackMessage');
            var modal = document.getElementById('modalFeedback');

            if (!form) return;

            if (textarea && charCount) {
                textarea.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                    charCount.classList.toggle('text-danger', this.value.length > 1800);
                });
            }

            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    form.reset();
                    form.classList.remove('d-none');
                    alertEl.className = 'alert d-none';
                    successEl.classList.add('d-none');
                    if (charCount) {
                        charCount.textContent = '0';
                        charCount.classList.remove('text-danger');
                    }
                    submitBtn.disabled = false;
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var name = document.getElementById('feedbackName').value.trim();
                var subject = document.getElementById('feedbackSubject').value;
                var message = textarea.value.trim();

                if (name.length < 2) {
                    alertEl.className = 'alert alert-warning mb-3';
                    alertEl.textContent = 'Insere o teu nome.';
                    return;
                }
                if (message.length < 10) {
                    alertEl.className = 'alert alert-warning mb-3';
                    alertEl.textContent = 'A mensagem deve ter pelo menos 10 caracteres.';
                    return;
                }

                submitBtn.disabled = true;
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                alertEl.className = 'alert d-none';

                fetch('ajax/feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf: csrfInput.value,
                            name,
                            subject,
                            message,
                            page: window.location.pathname
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        submitBtn.disabled = false;
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                        if (data.success) {
                            if (data.new_csrf) csrfInput.value = data.new_csrf;
                            form.classList.add('d-none');
                            successEl.classList.remove('d-none');
                        } else {
                            alertEl.className = 'alert alert-danger mb-3';
                            alertEl.textContent = data.message || 'Ocorreu um erro. Tenta novamente.';
                        }
                    })
                    .catch(function() {
                        submitBtn.disabled = false;
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                        alertEl.className = 'alert alert-danger mb-3';
                        alertEl.textContent = 'Erro de ligação. Tenta novamente.';
                    });
            });
        })();
    </script>

</body>

</html>