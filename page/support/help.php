<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Central de Ajuda
// Arquivo: page/support/help.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('help');
trackVisitor('/page/support/help', 'Ajuda — Wasom Upfy');

$plans       = getPlans();
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];

$siteName  = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl   = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum  = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));

$csrf_page = getSiteCsrf();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords" content="Ajuda <?php echo $siteName; ?>, royalties, distribuição, conta, suporte técnico" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Central de Ajuda" />
    <meta property="og:description"
        content="Ajuda sobre a plataforma <?php echo $siteName; ?>: royalties, distribuição, conta, formatos de áudio e suporte." />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/support/help" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Central de Ajuda</title>

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
    <link rel="stylesheet" href="../../css/theme.min.css" />
    <link rel="stylesheet" href="../../js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="../../css/framework.css" />
    <link rel="stylesheet" href="../../css/main.css" />
    <link rel="stylesheet" href="../../css/tutorial.css" />
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
                                <a title="Páginas" class="nav-link active" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Páginas <i data-feather="chevron-down"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a title="Novidades" class="dropdown-item" href="../../blog/">Novidades</a>
                                            <a title="Passatempo" class="dropdown-item"
                                                href="../../blog/">Passatempo</a>
                                            <a title="Indisponível" class="dropdown-item" href="#!">Indisponível <span
                                                    class="badge bg-warning">Indisponível</span></a>
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
                                                    href="../../page/services/music-promotion">Promoção de música <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a title="Serviços Personalizados" class="dropdown-item"
                                                    href="../../page/services/customized-services">Serviços
                                                    personalizados <span
                                                        class="badge bg-warning">Indisponível</span></a>
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
                                                <a title="Ajuda" class="dropdown-item active" href="help">Ajuda <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a title="Feedback" class="dropdown-item" href="#"
                                                    data-bs-toggle="modal" data-bs-target="#modalFeedback">Feedback</a>
                                                <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Ajuda</div>
                                                <a title="Tutorial" class="dropdown-item" href="tutorial">Tutorial <span
                                                        class="badge bg-success">Novo</span></a>
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
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($whatsNum): ?>
                                    <li><a title="WhatsApp" class="dropdown-item"
                                            href="https://wa.me/<?php echo $whatsNum; ?>">WhatsApp</a></li>
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- ══ Main ════════════════════════════════════════════════════════════════ -->
    <main>

        <!-- Hero com pesquisa -->
        <section class="tutorial-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/help.png" alt="Ajuda <?php echo $siteName; ?>"
                loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Central de Ajuda</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">Como podemos ajudar você?</h1>
                        <div class="position-relative mb-3">
                            <div class="search-container position-relative">
                                <input type="text" id="searchInput"
                                    class="form-control form-control-lg border-0 shadow-lg py-3 ps-5 rounded-pill"
                                    placeholder="Ex: royalties, formato de áudio, capa, saque..." autocomplete="off" />
                                <i
                                    class="fa-solid fa-search position-absolute top-50 start-0 translate-middle-y ms-4 text-wasomupfy"></i>
                                <!-- Sugestões -->
                                <div id="searchSuggestions"
                                    class="search-suggestions bg-white rounded-3 shadow-lg mt-2 p-2 d-none">
                                    <!-- Populadas por help.js -->
                                </div>
                            </div>
                        </div>
                        <p class="text-white-stable opacity-90 small">Pesquise por tópicos ou navegue pelas categorias
                            abaixo.</p>
                        <div class="popular-searches mt-4">
                            <span class="text-white-stable me-2">Populares:</span>
                            <a href="#!" data-search="royalties"
                                class="popular-pill badge bg-secondary text-black fw-semibold bg-opacity-15 text-decoration-none py-2 px-3 rounded-pill me-2 hover-scale">Royalties</a>
                            <a href="#!" data-search="formato de áudio aceito"
                                class="popular-pill badge bg-secondary text-black fw-semibold bg-opacity-15 text-decoration-none py-2 px-3 rounded-pill me-2 hover-scale">Upload</a>
                            <a href="#!" data-search="conta"
                                class="popular-pill badge bg-secondary text-black fw-semibold bg-opacity-15 text-decoration-none py-2 px-3 rounded-pill me-2 hover-scale">Conta</a>
                            <a href="#!" data-search="pitching playlists"
                                class="popular-pill badge bg-secondary text-black fw-semibold bg-opacity-15 text-decoration-none py-2 px-3 rounded-pill me-2 hover-scale">Playlists</a>
                            <a href="#!" data-search="2fa autenticação"
                                class="popular-pill badge bg-secondary text-black fw-semibold bg-opacity-15 text-decoration-none py-2 px-3 rounded-pill me-2 hover-scale">2FA</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cards de categorias -->
        <section class="py-5 bg-light-100 top-minus-50 position-relative z-index-3">
            <div class="container">
                <div id="searchResultsInfo" class="alert alert-wasomupfy mb-4 d-none">
                    <i class="fa-regular fa-circle-info me-2"></i>
                    <span id="resultsCount">0</span> resultado(s) encontrado(s) para "<span id="searchTerm"></span>"
                    <button type="button" class="btn-close float-end" id="clearSearch"></button>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-3 col-md-6" data-cue="zoomIn">
                        <a href="#distribuicao"
                            class="card h-100 border-0 shadow-sm text-center py-4 hover-lift text-decoration-none smooth-scroll category-card"
                            data-category="distribuicao">
                            <div class="card-body">
                                <div
                                    class="icon-shape icon-lg bg-light-primary text-wasomupfy rounded-circle mb-3 mx-auto position-relative">
                                    <i class="fa-solid fa-cloud-arrow-up fs-2 text-wasomupfy icon-animated"></i>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-wasomupfy">6</span>
                                </div>
                                <h5 class="fw-bold text-dark">Upload & Distribuição</h5>
                                <p class="text-muted small mb-2">Formatos, capas, prazos e lojas.</p>
                                <span class="badge bg-wasomupfy text-dark">6 artigos</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6" data-cue="zoomIn" data-delay="100">
                        <a href="#financeiro"
                            class="card h-100 border-0 shadow-sm text-center py-4 hover-lift text-decoration-none smooth-scroll category-card"
                            data-category="financeiro">
                            <div class="card-body">
                                <div
                                    class="icon-shape icon-lg bg-light-success text-success rounded-circle mb-3 mx-auto">
                                    <i class="fa-solid fa-sack-dollar fs-2 text-wasomupfy icon-animated"></i>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-wasomupfy">3</span>
                                </div>
                                <h5 class="fw-bold text-dark">Royalties & Pagamentos</h5>
                                <p class="text-muted small mb-2">90%, saques e calendário.</p>
                                <span class="badge bg-wasomupfy text-dark">3 artigos</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6" data-cue="zoomIn" data-delay="200">
                        <a href="#conta"
                            class="card h-100 border-0 shadow-sm text-center py-4 hover-lift text-decoration-none smooth-scroll category-card"
                            data-category="conta">
                            <div class="card-body">
                                <div class="icon-shape icon-lg bg-light-info text-info rounded-circle mb-3 mx-auto">
                                    <i class="fa-solid fa-user-gear fs-2 text-wasomupfy icon-animated"></i>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-wasomupfy">6</span>
                                </div>
                                <h5 class="fw-bold text-dark">Minha Conta</h5>
                                <p class="text-muted small mb-2">Login, senha, 2FA e perfil.</p>
                                <span class="badge bg-wasomupfy text-dark">6 artigos</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6" data-cue="zoomIn" data-delay="300">
                        <a href="#promocao"
                            class="card h-100 border-0 shadow-sm text-center py-4 hover-lift text-decoration-none smooth-scroll category-card"
                            data-category="marketing">
                            <div class="card-body">
                                <div
                                    class="icon-shape icon-lg bg-light-warning text-warning rounded-circle mb-3 mx-auto">
                                    <i class="fa-solid fa-bullhorn fs-2 text-wasomupfy icon-animated"></i>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-wasomupfy">3</span>
                                </div>
                                <h5 class="fw-bold text-dark">Marketing & Promo</h5>
                                <p class="text-muted small mb-2">Playlists, pitching e anúncios.</p>
                                <span class="badge bg-wasomupfy text-dark">3 artigos</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <span class="text-muted me-2">Ver também:</span>
                            <a href="tutorial"
                                class="badge bg-wasomupfy border py-2 px-3 rounded-pill text-decoration-none">
                                <i class="fa-regular fa-circle-play me-1"></i> Vídeos Tutoriais
                            </a>
                            <a href="faq" class="badge bg-wasomupfy border py-2 px-3 rounded-pill text-decoration-none">
                                <i class="fa-regular fa-circle-question me-1"></i> Perguntas Frequentes (FAQ)
                            </a>
                            <a href="support"
                                class="badge bg-wasomupfy border py-2 px-3 rounded-pill text-decoration-none">
                                <i class="fa-regular fa-headset me-1"></i> Suporte Técnico
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══ Artigos com accordion ══════════════════════════════════════════ -->
        <section id="help-articles" class="py-7 bg-light-100">
            <div class="container">
                <div class="row">

                    <!-- Sidebar -->
                    <div class="col-lg-3 mb-4 mb-lg-0">
                        <div class="sticky-top" style="top: 100px">
                            <div class="card border-0 shadow-sm p-3">
                                <h6 class="fw-bold mb-3">Navegação Rápida</h6>
                                <div class="nav flex-column nav-pills sidebar-nav">
                                    <a class="nav-link active py-2 px-3 rounded-3 mb-1" href="#distribuicao">
                                        <i class="fa-regular fa-circle-check me-2 text-wasomupfy"></i>Distribuição
                                        <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy float-end">6</span>
                                    </a>
                                    <a class="nav-link py-2 px-3 rounded-3 mb-1" href="#financeiro">
                                        <i class="fa-regular fa-circle-check me-2 text-wasomupfy"></i>Financeiro
                                        <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy float-end">3</span>
                                    </a>
                                    <a class="nav-link py-2 px-3 rounded-3 mb-1" href="#conta">
                                        <i class="fa-regular fa-circle-check me-2 text-wasomupfy"></i>Conta
                                        <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy float-end">6</span>
                                    </a>
                                    <a class="nav-link py-2 px-3 rounded-3 mb-1" href="#promocao">
                                        <i class="fa-regular fa-circle-check me-2 text-wasomupfy"></i>Marketing
                                        <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy float-end">3</span>
                                    </a>
                                    <a class="nav-link py-2 px-3 rounded-3 mb-1" href="#faq-geral">
                                        <i class="fa-regular fa-circle-check me-2 text-wasomupfy"></i>FAQ Geral
                                        <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy float-end">6</span>
                                    </a>
                                </div>
                                <hr class="my-3" />
                                <div class="p-3 rounded-3">
                                    <i class="fa-regular fa-message fs-3 text-wasomupfy mb-2"></i>
                                    <h6 class="fw-bold mb-2">Precisa de ajuda urgente?</h6>
                                    <p class="small text-muted mb-2">A nossa equipa responde em até 2h</p>
                                    <?php if ($whatsNum): ?>
                                    <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                        rel="noopener noreferrer" class="btn btn-sm btn-wasomupfy w-100">
                                        <i class="fa-brands fa-whatsapp me-2"></i> Chat Ao Vivo
                                    </a>
                                    <?php else: ?>
                                    <a href="../../contact" class="btn btn-sm btn-wasomupfy w-100">
                                        <i class="fa-regular fa-message me-2"></i> Contacta-nos
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-wasomupfy bg-opacity-10 rounded-3 mt-2">
                                    <i class="fa-regular fa-circle-question fs-4 text-wasomupfy mb-1 d-block"></i>
                                    <p class="small mb-2">Não encontrou aqui? Temos mais de 20 respostas no</p>
                                    <a href="faq" class="btn btn-sm btn-outline-wasomupfy w-100 fw-bold">
                                        Ver FAQ Completo →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conteúdo principal -->
                    <div class="col-lg-9">
                        <div class="d-flex align-items-center mb-4">
                            <h2 class="fw-bold mb-0 me-3">Todos os Artigos</h2>
                        </div>

                        <!-- ── Distribuição ──────────────────────────────────────── -->
                        <div id="distribuicao" class="mb-5 scroll-margin-top-100 category-section"
                            data-category="distribuicao">
                            <div class="d-flex align-items-center mb-4">
                                <div class="icon-shape bg-light-primary rounded-circle p-3 me-3">
                                    <i class="fa-solid fa-cloud-arrow-up text-wasomupfy fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0">Distribuição e Upload</h3>
                                <span class="badge bg-wasomupfy ms-3">6 artigos</span>
                            </div>

                            <div class="accordion accordion-flush shadow-sm rounded-3 overflow-hidden"
                                id="accordionDistro">

                                <!-- distro1 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#distro1">
                                            <i class="fa-regular fa-file-audio me-2 text-wasomupfy"></i>Qual o formato
                                            de áudio aceito?
                                        </button>
                                    </h2>
                                    <div id="distro1" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionDistro">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <p class="fw-bold text-dark">Especificações Técnicas:</p>
                                                    <ul class="list-unstyled">
                                                        <li class="mb-2"><i
                                                                class="fa-regular fa-circle-check text-success me-2"></i><strong>WAV
                                                                estéreo</strong> (obrigatório)</li>
                                                        <li class="mb-2"><i
                                                                class="fa-regular fa-circle-check text-success me-2"></i><strong>16-bit
                                                                ou 24-bit</strong> (24-bit recomendado para mais
                                                            dinâmica)</li>
                                                        <li class="mb-2"><i
                                                                class="fa-regular fa-circle-check text-success me-2"></i><strong>44.1
                                                                kHz</strong> de taxa de amostragem</li>
                                                        <li class="mb-2"><i
                                                                class="fa-regular fa-circle-check text-success me-2"></i>Headroom
                                                            de <strong>-1 dB</strong> no master</li>
                                                        <li class="mb-2"><i
                                                                class="fa-regular fa-circle-xmark text-danger me-2"></i><strong>MP3,
                                                                AAC, OGG, FLAC</strong> não são aceitos</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="bg-light p-3 rounded-3">
                                                        <p class="small text-muted mb-2"><i
                                                                class="fa-regular fa-lightbulb text-warning me-1"></i>Dica
                                                            Profissional:</p>
                                                        <p class="small mb-0">Músicas com muito grave soam melhor em
                                                            24-bit. Evite compressão excessiva no master — deixe espaço
                                                            para a dinâmica natural.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-wasomupfy mt-3 mb-0">
                                                <i class="fa-regular fa-circle-info me-2"></i>
                                                Tamanho máximo por faixa: <strong>1 GB</strong>. Verifique sempre o
                                                ficheiro antes do upload.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- distro2 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#distro2">
                                            <i class="fa-regular fa-image me-2 text-wasomupfy"></i>Requisitos da Arte da
                                            Capa
                                        </button>
                                    </h2>
                                    <div id="distro2" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionDistro">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-7">
                                                    <p class="fw-bold">Requisitos Técnicos:</p>
                                                    <ul>
                                                        <li>Formato: <strong>JPG ou PNG</strong></li>
                                                        <li>Tamanho mínimo: <strong>3000 × 3000 pixels</strong></li>
                                                        <li>Modo de cor: <strong>RGB</strong> (não CMYK)</li>
                                                        <li>Qualidade: <strong>Alta, sem artefactos ou
                                                                pixelização</strong></li>
                                                    </ul>
                                                    <div class="bg-danger bg-opacity-10 p-3 rounded-3 mt-2">
                                                        <p class="text-danger mb-1 fw-bold"><i
                                                                class="fa-solid fa-triangle-exclamation me-2"></i>Proibido:
                                                        </p>
                                                        <ul class="small text-danger mb-0">
                                                            <li>Logótipos de redes sociais (Instagram, TikTok, etc.)
                                                            </li>
                                                            <li>Marcas d'água, preços, QR codes</li>
                                                            <li>URLs e informações de contacto</li>
                                                            <li>Conteúdo explícito sem marcação adequada</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="border rounded-3 p-2">
                                                        <img src="../../assets/img/theme/capa-exemplo.jpg"
                                                            alt="Exemplo de capa" class="img-fluid rounded-2" />
                                                        <p class="small text-muted mt-2 mb-0 text-center">Exemplo de
                                                            capa aprovada</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- distro3 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#distro3">
                                            <i class="fa-regular fa-clock me-2 text-wasomupfy"></i>Prazos de
                                            Distribuição
                                        </button>
                                    </h2>
                                    <div id="distro3" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionDistro">
                                        <div class="accordion-body">
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <div class="step-circle bg-wasomupfy text-white">1</div>
                                                    <p class="fw-bold mt-2 mb-0">Revisão Interna</p>
                                                    <small class="text-muted">24-48h</small>
                                                </div>
                                                <div class="col-4">
                                                    <div class="step-circle bg-primary text-white">2</div>
                                                    <p class="fw-bold mt-2 mb-0">Aprovação</p>
                                                    <small class="text-muted">1-2 dias</small>
                                                </div>
                                                <div class="col-4">
                                                    <div class="step-circle bg-success text-white">3</div>
                                                    <p class="fw-bold mt-2 mb-0">Online nas Lojas</p>
                                                    <small class="text-muted">2-5 dias</small>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Loja</th>
                                                            <th>Prazo médio</th>
                                                            <th>Recomendação para pitch</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fa-brands fa-spotify text-success me-1"></i>
                                                                Spotify</td>
                                                            <td>3-5 dias úteis</td>
                                                            <td>3 semanas antes</td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fa-brands fa-apple text-dark me-1"></i> Apple
                                                                Music</td>
                                                            <td>2-3 dias úteis</td>
                                                            <td>1 semana antes</td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fa-brands fa-deezer text-warning me-1"></i>
                                                                Deezer</td>
                                                            <td>3-7 dias úteis</td>
                                                            <td>2 semanas antes</td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fa-brands fa-tiktok text-dark me-1"></i>
                                                                TikTok</td>
                                                            <td>2-4 dias úteis</td>
                                                            <td>2 semanas antes</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="alert alert-wasomupfy mb-0">
                                                <i class="fa-regular fa-lightbulb me-2"></i>
                                                Envie sempre com <strong>pelo menos 3 semanas de antecedência</strong>
                                                para garantir o pitching a playlists editoriais.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- distro4 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#distro4">
                                            <i class="fa-regular fa-store me-2 text-wasomupfy"></i>Para quais lojas
                                            distribuímos?
                                        </button>
                                    </h2>
                                    <div id="distro4" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionDistro">
                                        <div class="accordion-body">
                                            <div class="row g-2 mb-3">
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-brands fa-spotify text-success fs-5 me-2"></i>Spotify
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-brands fa-apple text-dark fs-5 me-2"></i>Apple
                                                        Music</div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-brands fa-deezer text-warning fs-5 me-2"></i>Deezer
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-brands fa-tiktok text-dark fs-5 me-2"></i>TikTok
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-brands fa-amazon text-warning fs-5 me-2"></i>Amazon
                                                        Music</div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-solid fa-music text-primary fs-5 me-2"></i>TIDAL
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-brands fa-youtube text-danger fs-5 me-2"></i>YouTube
                                                        Music</div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-solid fa-headphones text-info fs-5 me-2"></i>Boomplay
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="d-flex align-items-center p-2 bg-light rounded-3"><i
                                                            class="fa-solid fa-music text-wasomupfy fs-5 me-2"></i>Audiomack
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="small text-muted mb-0">+ mais de <strong>150 lojas e
                                                    plataformas</strong> parceiras em todo o mundo.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- distro5 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#distro5">
                                            <i class="fa-regular fa-list-check me-2 text-wasomupfy"></i>Como preencher
                                            metadados e gerar o ISRC?
                                        </button>
                                    </h2>
                                    <div id="distro5" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionDistro">
                                        <div class="accordion-body">
                                            <p>O <strong>ISRC</strong> (International Standard Recording Code) é gerado
                                                automaticamente pela plataforma para cada faixa — não precisa de se
                                                preocupar com isso.</p>
                                            <p class="fw-bold">No formulário de upload, preencha correctamente:</p>
                                            <ul>
                                                <li>Nome do <strong>artista principal</strong> e artistas em
                                                    <strong>feat.</strong>
                                                </li>
                                                <li><strong>Compositores</strong> com as respectivas percentagens de
                                                    autoria</li>
                                                <li><strong>Produtores</strong>, engenheiros de mixagem e mastering</li>
                                                <li><strong>Género musical</strong> e idioma da letra</li>
                                                <li>Se a letra contém <strong>conteúdo explícito</strong> (marque
                                                    correctamente)</li>
                                            </ul>
                                            <div class="alert alert-danger mb-0">
                                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                                Metadados incorrectos podem atrasar a distribuição ou causar conflitos
                                                de royalties entre compositores.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- distro6 -->
                                <div class="accordion-item search-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#distro6">
                                            <i class="fa-regular fa-calendar me-2 text-wasomupfy"></i>Posso agendar a
                                            data de lançamento?
                                        </button>
                                    </h2>
                                    <div id="distro6" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionDistro">
                                        <div class="accordion-body">
                                            <p>Sim. No formulário de upload, existe um campo <strong>"Data de
                                                    Lançamento"</strong> onde pode escolher o dia exacto em que a música
                                                ficará disponível ao público.</p>
                                            <p>Recomendamos agendar para uma <strong>sexta-feira</strong>, que é o dia
                                                oficial de lançamentos da indústria musical global (New Music Friday).
                                            </p>
                                            <div class="alert alert-wasomupfy mb-0">
                                                <i class="fa-regular fa-lightbulb me-2"></i>
                                                Para garantir pitching em playlists editoriais do Spotify, submeta com
                                                <strong>pelo menos 3 semanas de antecedência</strong> em relação à data
                                                de lançamento.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /accordionDistro -->
                        </div>

                        <!-- ── Financeiro ────────────────────────────────────────── -->
                        <div id="financeiro" class="mb-5 scroll-margin-top-100 category-section"
                            data-category="financeiro">
                            <div class="d-flex align-items-center mb-4">
                                <div class="icon-shape bg-light-success rounded-circle p-3 me-3">
                                    <i class="fa-solid fa-sack-dollar text-success fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0">Pagamentos e Royalties</h3>
                                <span class="badge bg-wasomupfy ms-3">3 artigos</span>
                            </div>

                            <div class="accordion accordion-flush shadow-sm rounded-3 overflow-hidden"
                                id="accordionFinance">

                                <!-- fin1 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#fin1">
                                            <i class="fa-regular fa-percent me-2 text-wasomupfy"></i>Qual a percentagem
                                            de royalties que eu recebo?
                                        </button>
                                    </h2>
                                    <div id="fin1" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionFinance">
                                        <div class="accordion-body">
                                            <div class="text-center mb-4">
                                                <div class="display-3 fw-bold text-wasomupfy">90%</div>
                                                <p class="text-muted">dos royalties líquidos vão para você</p>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="bg-light p-3 rounded-3">
                                                        <p class="fw-bold mb-2"><i
                                                                class="fa-regular fa-check-circle text-success me-2"></i>Os
                                                            90% incluem:</p>
                                                        <ul class="small">
                                                            <li>Streaming (Spotify, Apple Music, Deezer, etc.)</li>
                                                            <li>Downloads pagos</li>
                                                            <li>Sincronização (quando aplicável)</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="bg-light p-3 rounded-3">
                                                        <p class="fw-bold mb-2"><i
                                                                class="fa-regular fa-clock text-warning me-2"></i>Os 10%
                                                            cobrem:</p>
                                                        <ul class="small">
                                                            <li>Infraestrutura da plataforma</li>
                                                            <li>Suporte dedicado ao artista</li>
                                                            <li>Taxas administrativas e operacionais</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-wasomupfy mt-3 mb-0">
                                                <i class="fa-regular fa-circle-info me-2"></i>
                                                Os royalties líquidos são calculados <em>após</em> as deduções das
                                                próprias plataformas (ex.: o Spotify já retém a sua parte antes de
                                                repassar à distribuidora).
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- fin2 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#fin2">
                                            <i class="fa-regular fa-calendar me-2 text-wasomupfy"></i>Calendário de
                                            Pagamentos
                                        </button>
                                    </h2>
                                    <div id="fin2" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionFinance">
                                        <div class="accordion-body">
                                            <p class="small text-muted mb-3">Os dados de streaming chegam com atraso das
                                                plataformas. O calendário abaixo explica quando ficam disponíveis e
                                                quando são pagos:</p>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm">
                                                    <thead class="table-light">
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
                                                            <td>Março</td>
                                                            <td>Maio — dia 15</td>
                                                            <td>Maio — dia 20</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p class="small text-muted mb-0">O padrão repete-se mensalmente. Pode
                                                acompanhar os ganhos em tempo real no seu dashboard, filtrado por loja,
                                                artista ou período.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- fin3 -->
                                <div class="accordion-item search-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#fin3">
                                            <i class="fa-regular fa-wallet me-2 text-wasomupfy"></i>Como e quando posso
                                            solicitar o levantamento?
                                        </button>
                                    </h2>
                                    <div id="fin3" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionFinance">
                                        <div class="accordion-body">
                                            <p>Após atingir o <strong>valor mínimo de levantamento</strong> definido
                                                para o seu plano, aceda ao dashboard → <strong>Finanças → Solicitar
                                                    Levantamento</strong> e escolha o método de pagamento:</p>
                                            <ul>
                                                <li>Transferência bancária (IBAN)</li>
                                                <li>Outros métodos disponíveis na sua carteira <?php echo $siteName; ?>
                                                </li>
                                            </ul>
                                            <div class="alert alert-wasomupfy mb-0">
                                                <i class="fa-regular fa-triangle-exclamation me-2"></i>
                                                Certifique-se de que os seus dados bancários estão correctos nas
                                                <strong>Definições da Conta</strong> antes de solicitar — pagamentos
                                                devolvidos por dados incorrectos podem levar tempo adicional a ser
                                                reprocessados.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /accordionFinance -->
                        </div>

                        <!-- ── Conta & Segurança ──────────────────────────────────── -->
                        <div id="conta" class="mb-5 scroll-margin-top-100 category-section" data-category="conta">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-light-info rounded-circle p-3 me-3">
                                    <i class="fa-solid fa-user-shield text-info fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0">Conta e Segurança</h3>
                                <span class="badge bg-wasomupfy ms-3">6 artigos</span>
                            </div>

                            <div class="accordion accordion-flush shadow-sm rounded-3 overflow-hidden"
                                id="accordionAccount">

                                <!-- acc1 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#acc1">
                                            <i class="fa-solid fa-gear me-2 text-wasomupfy"></i>Como verificar o meu
                                            perfil no Spotify for Artists (S4A)?
                                        </button>
                                    </h2>
                                    <div id="acc1" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAccount">
                                        <div class="accordion-body text-muted">
                                            <p>Após o seu <strong>primeiro lançamento estar online</strong>, pode
                                                reivindicar o seu perfil artístico em <a
                                                    href="https://artists.spotify.com" target="_blank"
                                                    class="text-wasomupfy fw-bold">artists.spotify.com</a>.</p>
                                            <p>Se precisar de acesso antecipado (Instant Access) antes do lançamento
                                                estar live, entre em contacto com o nosso suporte — verificamos a
                                                disponibilidade caso a caso.</p>
                                            <p class="mb-0">O mesmo processo aplica-se ao <strong>Apple Music for
                                                    Artists</strong> e ao painel de artistas do <strong>Amazon
                                                    Music</strong>.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- acc2 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#acc2">
                                            <i class="fa-solid fa-lock me-2 text-wasomupfy"></i>Esqueci a minha senha —
                                            o que fazer?
                                        </button>
                                    </h2>
                                    <div id="acc2" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAccount">
                                        <div class="accordion-body text-muted">
                                            <p>Na página de login, clique em <strong>"Esqueci a Senha"</strong>.
                                                Enviaremos um link de redefinição seguro para o seu e-mail cadastrado,
                                                válido por <strong>1 hora</strong>.</p>
                                            <ul>
                                                <li>Defina uma senha com mínimo 8 caracteres, misturando letras, números
                                                    e símbolos</li>
                                                <li>Verifique a caixa de <strong>spam</strong> caso o e-mail não apareça
                                                    na caixa de entrada</li>
                                                <li>Se continuar sem acesso, abra um ticket em <a href="support"
                                                        class="text-wasomupfy fw-bold">Suporte Técnico</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- acc3 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#acc3">
                                            <i class="fa-solid fa-shield-halved me-2 text-wasomupfy"></i>Como activar a
                                            autenticação em dois factores (2FA)?
                                        </button>
                                    </h2>
                                    <div id="acc3" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAccount">
                                        <div class="accordion-body">
                                            <p>Aceda ao dashboard → <strong>Definições → Segurança</strong> e active o
                                                2FA.</p>
                                            <p>Utilizamos autenticação por <strong>e-mail (OTP)</strong>: após inserir a
                                                senha no login, um código temporário é enviado para o seu e-mail que
                                                deverá inserir para concluir o acesso. O código expira ao fim de
                                                <strong>10 minutos</strong>.
                                            </p>
                                            <div class="alert alert-success mb-0">
                                                <i class="fa-solid fa-shield-check me-2"></i>
                                                <strong>Altamente recomendado</strong> para contas que gerem artistas ou
                                                recebem royalties. O 2FA é a proteção mais eficaz contra acessos não
                                                autorizados.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- acc4 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#acc4">
                                            <i class="fa-regular fa-envelope-open me-2 text-wasomupfy"></i>Como funciona
                                            a verificação de e-mail?
                                        </button>
                                    </h2>
                                    <div id="acc4" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAccount">
                                        <div class="accordion-body text-muted">
                                            <p>Após o cadastro, enviamos um e-mail de verificação. Clique no botão
                                                <strong>"Verificar e-mail"</strong> para activar a conta. Enquanto não
                                                verificar, o acesso ao dashboard ficará limitado.
                                            </p>
                                            <p>Se não receber o e-mail em alguns minutos, entre no painel e clique em
                                                <strong>"Reenviar verificação"</strong>. O link expira ao fim de
                                                <strong>24 horas</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- acc5 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#acc5">
                                            <i class="fa-regular fa-pause-circle me-2 text-wasomupfy"></i>Como
                                            desactivar ou reactivar a minha conta?
                                        </button>
                                    </h2>
                                    <div id="acc5" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAccount">
                                        <div class="accordion-body">
                                            <p><strong>Para desactivar:</strong> Definições → Conta → <em>"Desactivar
                                                    conta"</em>. A conta ficará suspensa, mas os dados são preservados.
                                                O perfil e músicas ficam invisíveis para terceiros.</p>
                                            <p><strong>Para reactivar:</strong> Basta fazer login novamente. O sistema
                                                detecta a desactivação e apresenta um diálogo a perguntar se deseja
                                                restaurar. Após confirmar, tudo regressa ao normal com todos os dados
                                                intactos.</p>
                                            <div class="alert alert-warning mb-0">
                                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                                A opção <strong>"Eliminar conta"</strong> (também em Definições → Conta)
                                                é <em>irreversível</em>. Exporte os seus relatórios financeiros antes de
                                                prosseguir.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- acc6 -->
                                <div class="accordion-item search-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#acc6">
                                            <i class="fa-regular fa-users me-2 text-wasomupfy"></i>Como adicionar
                                            colaboradores à minha conta?
                                        </button>
                                    </h2>
                                    <div id="acc6" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionAccount">
                                        <div class="accordion-body">
                                            <p>Aceda ao dashboard → <strong>Definições → Colaboradores</strong> e
                                                convide utilizadores por e-mail. Pode definir o nível de acesso de cada
                                                colaborador:</p>
                                            <ul>
                                                <li><strong>Visualizador</strong> — apenas lê estatísticas, sem acesso
                                                    financeiro</li>
                                                <li><strong>Editor</strong> — gere artistas e lançamentos</li>
                                                <li><strong>Administrador</strong> — acesso total, exceto dados
                                                    financeiros sensíveis</li>
                                            </ul>
                                            <p class="mb-0 small text-muted">Ideal para managers, produtores e equipas
                                                de marketing que precisam de trabalhar na conta sem acesso a royalties e
                                                dados bancários.</p>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /accordionAccount -->
                        </div>

                        <!-- ── Marketing ─────────────────────────────────────────── -->
                        <div id="promocao" class="mb-5 scroll-margin-top-100 category-section"
                            data-category="marketing">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-light-warning rounded-circle p-3 me-3">
                                    <i class="fa-solid fa-bullhorn text-warning fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0">Marketing e Promoção</h3>
                                <span class="badge bg-wasomupfy ms-3">3 artigos</span>
                            </div>

                            <div class="accordion accordion-flush shadow-sm rounded-3 overflow-hidden"
                                id="accordionMarketing">

                                <!-- mkt1 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#mkt1">
                                            <i class="fa-solid fa-music me-2 text-wasomupfy"></i>Como ser incluído em
                                            playlists editoriais?
                                        </button>
                                    </h2>
                                    <div id="mkt1" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionMarketing">
                                        <div class="accordion-body">
                                            <p>O <strong>pitching para playlists editoriais</strong> é feito
                                                directamente pelo painel, com até <strong>4 semanas de
                                                    antecedência</strong>. Para aumentar as hipóteses de selecção,
                                                preencha:</p>
                                            <ul>
                                                <li>Género musical e sub-género</li>
                                                <li>Mood da música (energético, melancólico, relaxante, etc.)</li>
                                                <li>Instrumentos principais</li>
                                                <li>Contexto e história por detrás do lançamento</li>
                                            </ul>
                                            <div class="alert alert-wasomupfy mb-0">
                                                <i class="fa-regular fa-lightbulb me-2"></i>
                                                Só é possível fazer pitching para músicas com <strong>data de lançamento
                                                    futura</strong>. Após publicação, apenas playlists algorítmicas e de
                                                utilizadores ficam disponíveis.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- mkt2 -->
                                <div class="accordion-item search-item border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#mkt2">
                                            <i class="fa-solid fa-chart-line me-2 text-wasomupfy"></i>Posso criar
                                            anúncios a partir do painel?
                                        </button>
                                    </h2>
                                    <div id="mkt2" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionMarketing">
                                        <div class="accordion-body">
                                            <p>Sim. Oferecemos integração com <strong>Facebook/Instagram Ads e TikTok
                                                    for Business</strong> directamente do painel. Pode criar campanhas
                                                de promoção de lançamentos, pré-saves e segmentação por país sem sair da
                                                plataforma.</p>
                                            <p class="mb-0 small text-muted">Esta funcionalidade faz parte do plano de
                                                <a href="../../page/services/music-promotion"
                                                    class="text-wasomupfy fw-bold">Promoção de Música</a>. Para serviços
                                                personalizados, consulte a nossa página de <a
                                                    href="../../page/services/customized-services"
                                                    class="text-wasomupfy fw-bold">Serviços Premium</a>.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- mkt3 -->
                                <div class="accordion-item search-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#mkt3">
                                            <i class="fa-brands fa-tiktok me-2 text-wasomupfy"></i>Como tirar o máximo
                                            partido do TikTok?
                                        </button>
                                    </h2>
                                    <div id="mkt3" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionMarketing">
                                        <div class="accordion-body">
                                            <p>A música fica disponível na biblioteca do TikTok após a distribuição.
                                                Para maximizar o impacto:</p>
                                            <ul>
                                                <li>Seleccione o <strong>melhor excerto</strong> (hook ou refrão)
                                                    durante o upload — será o que aparece no TikTok</li>
                                                <li>Crie vídeos de <strong>15 a 30 segundos</strong> a usar a música
                                                    antes do lançamento</li>
                                                <li>Incentive fãs e criadores a usarem a faixa nos seus vídeos</li>
                                                <li>Um único vídeo viral pode gerar centenas de milhares de streams</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /accordionMarketing -->
                        </div>

                    </div><!-- /col-lg-9 -->
                </div><!-- /row -->
            </div><!-- /container -->
        </section>

        <!-- ══ FAQ Rápido + link para FAQ completo ════════════════════════════ -->
        <section id="faq-geral" class="py-5 bg-light-100 border-top">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy py-2 px-3 rounded-pill mb-3">FAQ</span>
                    <h2 class="fw-bold">Respostas Rápidas</h2>
                    <p class="text-muted">As dúvidas mais frequentes — resumidas aqui para si.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fa-regular fa-file-audio text-wasomupfy fs-3 me-3"></i>
                                    <h6 class="fw-bold mb-0">Posso enviar covers?</h6>
                                </div>
                                <p class="small text-muted mb-0">Sim, desde que possua a <strong>licença
                                        mecânica</strong> necessária. Entre em contacto com o nosso suporte para mais
                                    informações sobre como obter a licença.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fa-regular fa-pen-to-square text-wasomupfy fs-3 me-3"></i>
                                    <h6 class="fw-bold mb-0">Como editar um lançamento já aprovado?</h6>
                                </div>
                                <p class="small text-muted mb-0">Após aprovação, não é possível editar directamente.
                                    Solicite o cancelamento pelo painel e faça um novo upload com as correcções
                                    necessárias.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fa-regular fa-trash-can text-wasomupfy fs-3 me-3"></i>
                                    <h6 class="fw-bold mb-0">Como remover uma música das lojas?</h6>
                                </div>
                                <p class="small text-muted mb-0">Solicite a remoção pelo painel. O processo leva de
                                    <strong>2 a 5 dias úteis</strong> em todas as lojas. Note que os streams gerados até
                                    à data de remoção são pagos normalmente.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fa-regular fa-clock text-wasomupfy fs-3 me-3"></i>
                                    <h6 class="fw-bold mb-0">Posso agendar um lançamento?</h6>
                                </div>
                                <p class="small text-muted mb-0">Sim, escolha a data exacta no formulário de upload.
                                    Recomendamos <strong>sexta-feira</strong> (New Music Friday) e 2-3 semanas de
                                    antecedência para melhor performance.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fa-solid fa-layer-group text-wasomupfy fs-3 me-3"></i>
                                    <h6 class="fw-bold mb-0">Qual plano escolher?</h6>
                                </div>
                                <p class="small text-muted mb-0">Depende da sua necessidade:
                                    <strong>Single/Álbum</strong> para lançamentos pontuais; <strong>Artista</strong>
                                    para gestão contínua; <strong>Label</strong> para selos com múltiplos artistas.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div
                            class="card h-100 border-0 shadow-sm hover-lift bg-wasomupfy bg-opacity-5 border-wasomupfy">
                            <div class="card-body d-flex flex-column justify-content-center text-center py-4">
                                <i class="fa-regular fa-circle-question text-wasomupfy fs-1 mb-3"></i>
                                <h6 class="fw-bold mb-2">Tem mais dúvidas?</h6>
                                <p class="small text-muted mb-3">Consulte as nossas mais de <strong>20 perguntas e
                                        respostas</strong> completas no FAQ.</p>
                                <a href="faq" class="btn btn-wasomupfy btn-sm rounded-pill fw-bold">
                                    Ver FAQ Completo <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Suporte avançado -->
        <section class="py-7 bg-light-100 border-top">
            <div class="container text-center">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="row g-4 mb-5">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <i class="fa-regular fa-message fs-1 text-wasomupfy mb-3"></i>
                                        <h5 class="fw-bold">Chat Ao Vivo</h5>
                                        <p class="small text-muted mb-3">Resposta em até 2h</p>
                                        <span class="badge bg-success text-white mb-3">Online agora</span>
                                        <?php if ($whatsNum): ?>
                                        <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-sm btn-outline-wasomupfy w-100">Iniciar Chat</a>
                                        <?php else: ?>
                                        <a href="../../contact" class="btn btn-sm btn-outline-wasomupfy w-100">Iniciar
                                            Chat</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <i class="fa-regular fa-envelope fs-1 text-wasomupfy mb-3"></i>
                                        <h5 class="fw-bold">Email</h5>
                                        <p class="small text-muted mb-3">Resposta em até 24h</p>
                                        <?php if (cfg('support_email')): ?>
                                        <a href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                            class="small text-wasomupfy d-block mb-2"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                        <?php endif; ?>
                                        <a href="../../contact"
                                            class="btn btn-sm btn-outline-wasomupfy w-100 mt-1">Enviar Email</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <i class="fa-brands fa-whatsapp fs-1 text-success mb-3"></i>
                                        <h5 class="fw-bold">WhatsApp</h5>
                                        <p class="small text-muted mb-3">Suporte rápido</p>
                                        <?php if ($whatsNum): ?>
                                        <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                            rel="noopener noreferrer" class="btn btn-sm btn-success w-100">
                                            <i class="fa-brands fa-whatsapp me-2"></i>+<?php echo $whatsNum; ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 rounded-4">
                            <i class="fa-solid fa-headset fs-1 mb-4 text-wasomupfy"></i>
                            <h3 class="fw-bold mb-3">Ainda com dúvidas?</h3>
                            <p class="mb-4 opacity-90">A nossa equipa de especialistas está pronta para ajudá-lo a
                                crescer a sua carreira musical.</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <a href="../../contact?assunto=suporte"
                                    class="btn btn-wasomupfy rounded-pill px-5 py-3 fw-bold shadow-lg">
                                    Abrir Chamado Técnico <i class="fa-solid fa-arrow-right ms-2"></i>
                                </a>
                                <a href="faq" class="btn btn-outline-secondary rounded-pill px-4 py-3 fw-bold">
                                    <i class="fa-regular fa-circle-question me-2"></i>Ver FAQ Completo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
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
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem"><i
                                    class="fa-brands fa-instagram"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('facebook_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="Facebook"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem"><i
                                    class="fa-brands fa-facebook-f"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('youtube_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="YouTube"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem"><i
                                    class="fa-brands fa-youtube"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('linkedin_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="LinkedIn"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem"><i
                                    class="fa-brands fa-linkedin-in"></i></a>
                            <?php endif; ?>
                            <?php if ($whatsNum): ?>
                            <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                rel="external noopener noreferrer" aria-label="WhatsApp"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem"><i
                                    class="fa-brands fa-whatsapp"></i></a>
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
                            <li class="mb-2"><a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento</a></li>
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
                            <li class="mb-3 d-flex"><a href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                    class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('info_email')); ?></a>
                            </li>
                            <?php endif; ?>
                            <?php if (cfg('support_email')): ?>
                            <li class="mb-3 d-flex"><a
                                    href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                    class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                            </li>
                            <?php endif; ?>
                            <li class="d-flex"><span>Seg — Sex: 08h às 17h</span></li>
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
    <script src="../../js/theme.min.js"></script>
    <script src="../../js/vendors/color-modes.js"></script>
    <script src="../../js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="../../js/vendors/scrollcue.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    <script src="https://unpkg.com/in-view@0.6.1/dist/in-view.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sticky-kit/1.1.3/sticky-kit.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>
    <!-- help.js substitui tutorial.js nesta página -->
    <script src="js/help.js"></script>
    <script src="../../js/cookies.js"></script>

    <script>
    feather.replace({
        width: "1em",
        height: "1em"
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