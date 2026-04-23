<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Tutorial
// Arquivo: page/support/tutorial.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('tutorial');
trackVisitor('/page/support/tutorial', 'Tutorial — Wasom Upfy');

$plans       = getPlans();
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];

$siteName  = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl   = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum  = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));

// ID do vídeo YouTube do tutorial — configurar em _site_config (key: youtube_tutorial_id)
$youtubeId = cfg('youtube_tutorial_id', 'SEU_VIDEO_AQUI');

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
        content="Tutorial <?php echo $siteName; ?>, cadastro de artistas, distribuição musical, guia passo a passo" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Tutorial" />
    <meta property="og:description"
        content="Tutorial sobre a plataforma <?php echo $siteName; ?>, incluindo cadastro de artistas, estatísticas e suporte." />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/support/tutorial" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Tutorial</title>

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
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/tutorial.css" />
</head>

<body data-base-path="../..">

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
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Logotipo" href="../../home">
                            <img width="65" src="../../assets/img/brand/wasomupfy_brand.png"
                                alt="Logo <?php echo $siteName; ?>" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item">
                                <a class="nav-link" href="../../home" title="Inicio">Início</a>
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
                                        href="../../plan/all-plans">
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
                                <a title="Páginas" class="nav-link active" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Páginas <i data-feather="chevron-down"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a title="Novidades" class="dropdown-item" href="../../blog/">Novidades</a>
                                            <a title="Passatempo Wasom Upfy" class="dropdown-item"
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
                                                    href="../../page/services/music-distribution">Distribuição de
                                                    música</a>
                                                <a title="Promoção de música" class="dropdown-item"
                                                    href="../../page/services/music-promotion">Promoção de música
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Serviços Personalizados" class="dropdown-item"
                                                    href="../../page/services/customized-services">Serviços
                                                    personalizados
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052"
                                                    target="_blank" rel="external noopener noreferrer">Atendimento</a>
                                                <a title="Contacto-nos" class="dropdown-item"
                                                    href="../../contact">Contacta-nos</a>
                                                <a title="Canal WhatsApp" class="dropdown-item"
                                                    href="<?php echo htmlspecialchars(cfg('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04')); ?>"
                                                    target="_blank" rel="external noopener noreferrer">Canal
                                                    WhatsApp</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Sugestões</div>
                                                <a title="Ajuda" class="dropdown-item" href="help">Ajuda
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Feedback" class="dropdown-item" href="#"
                                                    data-bs-toggle="modal" data-bs-target="#modalFeedback">Feedback</a>
                                                <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Ajuda</div>
                                                <a title="Tutorial" class="dropdown-item active"
                                                    href="tutorial">Tutorial
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Suporte técnico" class="dropdown-item" href="support">Suporte
                                                    técnico</a>
                                                <a title="Perguntas frequentes" class="dropdown-item"
                                                    href="faq">Perguntas frequentes</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../../resources" title="Recursos">Recursos</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a title="Contacto" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Contactar <i data-feather="chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a title="Caixa de mensagem" class="dropdown-item" href="../../contact">Caixa de
                                            mensagem</a></li>
                                    <?php if (cfg('support_email')): ?>
                                        <li><a title="E-mail" class="dropdown-item"
                                                href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>">
                                                <?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($whatsNum): ?>
                                        <li><a title="WhatsApp" class="dropdown-item"
                                                href="https://wa.me/<?php echo $whatsNum; ?>">WhatsApp</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        </ul>
                        <div class="mt-3 mt-lg-0 d-flex align-items-center">
                            <a title="Sign-in" href="<?php echo APP_URL  ?>/login" class="btn btn-secondary mx-2">
                                Entrar <i data-feather="log-in"></i>
                            </a>
                            <?php if ($canRegister): ?>
                                <a title="Sign-up" href="<?php echo APP_URL  ?>/register" class="btn btn-wasomupfy">Inscreva-se</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- ══ Main ════════════════════════════════════════════════════════════════ -->
    <main>

        <!-- Hero parallax -->
        <section class="tutorial-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/tutorial.png" alt="Tutorial <?php echo $siteName; ?>"
                loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Tutorial</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">Guia para iniciantes</h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Encontre guias para esclarecer as suas dúvidas sobre a plataforma <?php echo $siteName; ?>.
                            Não encontrou o que procurava? Entre em contacto com o nosso
                            <a href="support" title="Suporte" class="text-secondary">suporte</a>!
                        </p>
                        <p class="update-date" data-i18n="faq_update_date">
                            Última actualização: 14 de Fevereiro de 2026
                        </p>
                        <a href="#detalhes-tutorial" class="btn btn-wasomupfy btn-lg mt-2 smooth-scroll">
                            Explorar o tutorial <i class="fa-solid fa-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cards de passos -->
        <section id="detalhes-tutorial" class="py-5 bg-light-100">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-7 text-center" data-cue="fadeIn">
                        <h2 class="mb-3">Como começar a distribuir a sua música</h2>
                        <p class="text-muted">Siga os nossos passos detalhados para garantir que o seu lançamento esteja
                            pronto para as lojas globais.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4" data-cue="slideInUp">
                        <div class="card border-0 shadow-sm h-100 p-4 btn-lift">
                            <div class="icon-shape icon-lg bg-wasomupfy text-wasomupfy rounded-circle mb-4">
                                <i class="fa-solid fa-user-plus fs-2"></i>
                            </div>
                            <h3>1. Criar sua conta</h3>
                            <p class="text-muted">Saiba como configurar o seu perfil de artista ou selo (label) e
                                verificar os seus dados de pagamento.</p>
                            <a href="#!" class="text-wasomupfy fw-bold mt-auto">Ler mais <i
                                    class="fa-solid fa-chevron-right ms-1 small"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-cue="slideInUp">
                        <div class="card border-0 shadow-sm h-100 p-4 btn-lift">
                            <div class="icon-shape icon-lg bg-wasomupfy text-wasomupfy rounded-circle mb-4">
                                <i class="fa-solid fa-cloud-arrow-up fs-2"></i>
                            </div>
                            <h3>2. Formatos de Áudio</h3>
                            <p class="text-muted">Entenda a diferença entre Single e Álbum e quais os requisitos
                                técnicos (WAV, 44.1kHz) para o upload.</p>
                            <a href="#!" class="text-wasomupfy fw-bold mt-auto">Ler mais <i
                                    class="fa-solid fa-chevron-right ms-1 small"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-cue="slideInUp">
                        <div class="card border-0 shadow-sm h-100 p-4 btn-lift">
                            <div class="icon-shape icon-lg bg-wasomupfy text-wasomupfy rounded-circle mb-4">
                                <i class="fa-solid fa-image fs-2"></i>
                            </div>
                            <h3>3. Guia de Capas</h3>
                            <p class="text-muted">Evite rejeições: saiba as dimensões exactas e o que não pode conter na
                                imagem da sua capa.</p>
                            <a href="#!" class="text-wasomupfy fw-bold mt-auto">Ler mais <i
                                    class="fa-solid fa-chevron-right ms-1 small"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-cue="slideInUp">
                        <div class="card border-0 shadow-sm h-100 p-4 btn-lift">
                            <div class="icon-shape icon-lg bg-wasomupfy text-wasomupfy rounded-circle mb-4">
                                <i class="fa-solid fa-list-check fs-2"></i>
                            </div>
                            <h3>4. ISRC e Metadados</h3>
                            <p class="text-muted">Como preencher correctamente o nome dos compositores, produtores e
                                gerar os seus códigos ISRC.</p>
                            <a href="#!" class="text-wasomupfy fw-bold mt-auto">Ler mais <i
                                    class="fa-solid fa-chevron-right ms-1 small"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-cue="slideInUp">
                        <div class="card border-0 shadow-sm h-100 p-4 btn-lift">
                            <div class="icon-shape icon-lg bg-wasomupfy text-wasomupfy rounded-circle mb-4">
                                <i class="fa-solid fa-money-bill-trend-up fs-2"></i>
                            </div>
                            <h3>5. Ganhos e Royalties</h3>
                            <p class="text-muted">Saiba como funcionam os relatórios de vendas e como solicitar o
                                levantamento dos seus lucros.</p>
                            <a href="#!" class="text-wasomupfy fw-bold mt-auto">Ler mais <i
                                    class="fa-solid fa-chevron-right ms-1 small"></i></a>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4" data-cue="slideInUp">
                        <div class="card border-0 shadow-sm h-100 p-4 btn-lift">
                            <div class="icon-shape icon-lg bg-wasomupfy text-wasomupfy rounded-circle mb-4">
                                <i class="fa-solid fa-bullhorn fs-2"></i>
                            </div>
                            <h3>6. Pitching & Marketing</h3>
                            <p class="text-muted">Dicas para enviar a sua música para as playlists editoriais do Spotify
                                e Apple Music.</p>
                            <a href="#!" class="text-wasomupfy fw-bold mt-auto">Ler mais <i
                                    class="fa-solid fa-chevron-right ms-1 small"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Secção vídeo -->
        <section class="py-5 bg-light-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-5 mb-5 mb-lg-0" data-cue="fadeIn">
                        <div class="mb-4">
                            <span class="badge bg-wasomupfy text-white mb-3">Passo a Passo em Vídeo</span>
                            <h2 class="display-5 fw-bold text-dark">Aprenda visualmente em menos de 5 minutos</h2>
                            <p class="lead text-muted">
                                Preparámos um vídeo completo que mostra desde o login até à confirmação do envio do seu
                                Single ou Álbum.
                            </p>
                        </div>
                        <ul class="list-unstyled mb-5">
                            <li class="d-flex mb-3">
                                <i class="fa-solid fa-circle-play text-wasomupfy mt-1 fs-5"></i>
                                <span class="ms-3 text-dark">Interface intuitiva e fácil de usar.</span>
                            </li>
                            <li class="d-flex mb-3">
                                <i class="fa-solid fa-circle-play text-wasomupfy mt-1 fs-5"></i>
                                <span class="ms-3 text-dark">Dicas exclusivas para evitar rejeição.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-7" data-cue="slideInRight">
                        <div class="position-relative">
                            <div class="rounded-3 shadow-lg overflow-hidden position-relative">
                                <img src="../../assets/img/theme/video-thumbnail.png" class="img-fluid w-100"
                                    alt="Thumbnail Tutorial" />
                                <?php if ($youtubeId && $youtubeId !== 'SEU_VIDEO_AQUI'): ?>
                                    <a class="position-absolute top-50 start-50 translate-middle"
                                        href="https://www.youtube.com/watch?v=<?php echo htmlspecialchars($youtubeId); ?>"
                                        data-bs-toggle="modal" data-bs-target="#tutorialModal">
                                        <div
                                            class="icon-shape icon-xl bg-wasomupfy text-white rounded-circle shadow-lg pulse-animation">
                                            <i class="fa-solid fa-play fs-3"></i>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <div
                                            class="icon-shape icon-xl bg-wasomupfy text-white rounded-circle shadow-lg pulse-animation opacity-50">
                                            <i class="fa-solid fa-play fs-3"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA final -->
        <section class="py-5 position-relative overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10"
                style="background-image: url('../../assets/img/theme/pattern.png');"></div>
            <div class="container position-relative z-index-2">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8" data-cue="zoomIn">
                        <h2 class="display-4 text-white-stable fw-bold mb-3">Pronto para dominar o mundo?</h2>
                        <p class="text-white-stable fs-4 mb-5">
                            Agora que já sabe como funciona, não deixe a sua música guardada na gaveta.
                        </p>
                        <div class="d-grid d-md-flex justify-content-md-center gap-3">
                            <?php if ($canRegister): ?>
                                <a href="<?php echo APP_URL  ?>/register" class="btn btn-secondary btn-lg px-5 py-3 fw-bold shadow">
                                    Começar Lançamento <i class="fa-solid fa-rocket ms-2"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo APP_URL  ?>/login" class="btn btn-secondary btn-lg px-5 py-3 fw-bold shadow">
                                    Começar Lançamento <i class="fa-solid fa-rocket ms-2"></i>
                                </a>
                            <?php endif; ?>
                            <a href="../../contact" class="btn btn-outline-wasomupfy btn-lg px-5 py-3">
                                Falar com Consultor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Modal Tutorial Vídeo -->
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tutorialModalLabel">Tutorial <?php echo $siteName; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9">
                        <?php if ($youtubeId && $youtubeId !== 'SEU_VIDEO_AQUI'): ?>
                            <iframe id="tutorialIframe"
                                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtubeId); ?>"
                                title="Tutorial <?php echo $siteName; ?>" frameborder="0" allowfullscreen></iframe>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light rounded">
                                <div class="text-center text-muted p-5">
                                    <i class="fa-brands fa-youtube fs-1 mb-3 d-block"></i>
                                    <p class="mb-0">Vídeo tutorial em breve disponível.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Conteúdo Dinâmico (usado por tutorial.js) -->
    <div class="modal fade" id="modalConteudo" tabindex="-1" aria-labelledby="modalConteudoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fs-3 fw-bold" id="modalConteudoLabel">Título do Tutorial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div id="modalConteudoBody"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <a href="#" class="btn btn-wasomupfy" id="modalBtnAcao">Ver guia completo</a>
                </div>
            </div>
        </div>
    </div>

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

            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">
                    <div class="col-lg-3 col-12">
                        <a href="../../home" class="d-inline-block mb-4 navbar-brand">
                            <img src="../../assets/img/brand/wasomupfy_brand.png" alt="<?php echo $siteName; ?>"
                                width="65" class="img-logo" height="60" />
                        </a>
                        <p class="lead text-muted small mb-4">Levamos a música angolana para o mundo. Distribuição
                            digital, marketing e gestão de carreira num só lugar.</p>
                        <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
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
                            <li class="mb-2">
                                <a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento</a>
                            </li>
                            <li class="mb-2"><a href="help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="../../contact"
                                    class="text-reset text-decoration-none hover-white">Contacta-nos</a></li>
                            <?php if ($whatsNum): ?>
                                <li class="mb-2"><a href="https://wa.me/<?php echo $whatsNum; ?>"
                                        class="text-reset text-decoration-none hover-white">WhatsApp</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="col-lg-3 col-12">
                        <h3 class="fw-bold mb-3">Contacto</h3>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-3 d-flex">
                                <span><?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?> —
                                    <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?></span>
                            </li>
                            <?php if (cfg('info_email')): ?>
                                <li class="mb-3 d-flex">
                                    <a href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                        class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('info_email')); ?></a>
                                </li>
                            <?php endif; ?>
                            <?php if (cfg('support_email')): ?>
                                <li class="mb-3 d-flex">
                                    <a href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                        class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="d-flex">
                                <span>Seg — Sex: 08h às 17h</span>
                            </li>
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
                        <li class="list-inline-item"><a href="../../page/politicies/privacy"
                                class="text-reset text-decoration-none">Política de Privacidade</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="../../page/politicies/terms"
                                class="text-reset text-decoration-none">Termos de Uso</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="../../page/politicies/cookies"
                                class="text-reset text-decoration-none">Cookies</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

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
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><button type="button" class="dropdown-item d-flex align-items-center"
                            data-bs-theme-value="light" aria-pressed="false"><i class="fa-solid fa-sun"></i><span
                                class="ms-2">Claro</span></button></li>
                    <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"
                            aria-pressed="false"><i class="fa-solid fa-moon"></i><span
                                class="ms-2">Escuro</span></button></li>
                    <li><button type="button" class="dropdown-item d-flex align-items-center active"
                            data-bs-theme-value="auto" aria-pressed="true"><i class="fa-solid fa-display"></i><span
                                class="ms-2">Sistema</span></button></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ══ Modal Feedback ══════════════════════════════════════════════════════ -->
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
                    <p class="text-muted">Como tem sido a sua experiência com a
                        <strong><?php echo $siteName; ?></strong>? As suas sugestões ajudam-nos a evoluir.
                    </p>
                    <div id="feedback-modal-msg" class="alert d-none mb-3" role="alert"></div>
                    <form id="formFeedback" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_page); ?>" />
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Seu Nome</label>
                            <input type="text" class="form-control" name="name_fb" placeholder="Ex: André Wasom"
                                required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Assunto</label>
                            <select class="form-select" name="subject_fb">
                                <option>Sugestão de melhoria</option>
                                <option>Elogio</option>
                                <option>Relatar um problema</option>
                                <option>Outros</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">A sua Mensagem</label>
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
                    <small class="text-muted">A <?php echo $siteName; ?> agradece a sua parceria!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Scripts ══════════════════════════════════════════════════════════════ -->
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
    <script src="<?php echo APP_URL  ?>/js/tutorial.js"></script>
    <script src="<?php echo APP_URL  ?>/js/cookies.js"></script>

    <script>
        feather.replace({
            width: "1em",
            height: "1em"
        });
    </script>

    <!-- Parar vídeo ao fechar modal -->
    <script>
        document.getElementById('tutorialModal').addEventListener('hidden.bs.modal', function() {
            var iframe = document.getElementById('tutorialIframe');
            if (iframe) {
                iframe.src = iframe.src;
            }
        });
    </script>

    <!-- GTM -->
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

    <!-- Modal Feedback AJAX -->
    <script>
        (function() {
            function syncAllCsrf(token) {
                if (!token) return;
                document.querySelectorAll('[name="csrf_token"]').forEach(function(el) {
                    el.value = token;
                });
            }

            var fModal = document.getElementById('formFeedback');
            if (!fModal) return;

            fModal.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!fModal.checkValidity()) {
                    fModal.classList.add('was-validated');
                    return;
                }

                var btn = document.getElementById('btn-feedback-modal');
                var msgBox = document.getElementById('feedback-modal-msg');
                var base = document.body.dataset.basePath || '../..';

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';

                fetch(base + '/ajax/feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf: fModal.querySelector('[name="csrf_token"]').value,
                            name: fModal.querySelector('[name="name_fb"]').value.trim(),
                            subject: fModal.querySelector('[name="subject_fb"]').value.trim(),
                            message: fModal.querySelector('[name="message_fb"]').value.trim(),
                            page: window.location.pathname
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        msgBox.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
                        msgBox.textContent = data.message || (data.success ? 'Obrigado pelo feedback!' :
                            'Erro ao enviar.');
                        msgBox.classList.remove('d-none');
                        if (data.new_csrf) syncAllCsrf(data.new_csrf);
                        if (data.success) {
                            fModal.reset();
                            setTimeout(function() {
                                var m = bootstrap.Modal.getInstance(document.getElementById(
                                    'modalFeedback'));
                                if (m) m.hide();
                            }, 2500);
                        }
                    })
                    .catch(function() {
                        msgBox.className = 'alert alert-danger';
                        msgBox.textContent = 'Erro de ligação. Tenta novamente.';
                        msgBox.classList.remove('d-none');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.innerHTML = 'Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>';
                    });
            });
        })();
    </script>

</body>

</html>