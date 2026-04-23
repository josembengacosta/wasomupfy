<?php
// ══════════════════════════════════════════════
// WASOM UPFY — Distribuição de Música
// Arquivo: page/services/music-distribution.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('music-distribution');
trackVisitor('/page/services/music-distribution', 'Distribuição de Música — Wasom Upfy');

$plans       = getPlans();
$plansBySlug = [];
foreach ($plans as $p) {
    $plansBySlug[$p['slug_plan']] = $p;
}
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];
$royalty     = (int)$platform['royalty_percentage'];
$fee         = 100 - $royalty;
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
    <title><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> | Distribuição de Música</title>
    <meta name="description"
        content="Distribua a sua música para Spotify, Apple Music, TikTok e mais de <?php echo $stores ?: 150; ?> plataformas digitais. Mantenha <?php echo $royalty; ?>% dos seus direitos com a <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>." />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_keywords', 'distribuição de música, Spotify, Apple Music, Angola, Wasom Upfy, distribuição digital')); ?>" />

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:type" content="website" />
    <meta property="og:title"
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> — Distribuição de Música Digital" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'Plataforma de distribuição musical de Angola para o mundo.')); ?>" />
    <meta property="og:url"
        content="<?php echo htmlspecialchars(cfg('site_url', 'https://wasomupfy.rf.gd')); ?>/page/services/music-distribution" />
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

    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/theme.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/framework.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/main.css" />
</head>

<body data-base-path="../..">
    <!-- Preloader -->
    <div class="preloader">
        <img src="../../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="Loading-wasomupfy" />
    </div>

    <!-- Navbar -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="../../home" title="Home">
                    <img src="../../assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo Wasom Upfy" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Logotipo" href="../../home">
                            <img width="65" src="../../assets/img/brand/wasomupfy_brand.png" alt="Logo Wasom Upfy" />
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
                                <a title="Páginas" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">Páginas <i data-feather="chevron-down"></i></a>
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
                                                <a title="Distribuição de música" class="dropdown-item active"
                                                    href="music-distribution">Distribuição de música</a>
                                                <a title="Promoção de música" class="dropdown-item"
                                                    href="music-promotion">Promoção de música
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Serviços personalizados" class="dropdown-item"
                                                    href="customized-services">Serviços personalizados
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
                                                    href="https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04"
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
                                    aria-expanded="false">Contactar <i data-feather="chevron-down"></i></a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a title="Caixa de mensagem" class="dropdown-item" href="../../contact">Caixa de
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
                            <a title="Sign-in" href="<?php echo APP_URL  ?>/login" class="btn btn-secondary mx-2">
                                Entrar <i data-feather="log-in"></i>
                            </a>
                            <?php if ($canRegister): ?>
                                <a title="Sign-up" href="<?php echo APP_URL  ?>/register" class="btn btn-wasomupfy">Inscreva-se</a>
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
        <!-- Hero -->
        <section class="distribution-hero jarallax position-relative overflow-hidden py-5" data-jarallax
            data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/distribution_music.png" alt="Distribuição Wasom Upfy"
                loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <span class="badge bg-wasomupfy text-white mb-3 fs-6 px-3 py-2 rounded-pill">
                            Distribuição Digital Global
                        </span>
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">
                            A tua música em todas as plataformas.
                        </h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Distribua seus Singles e Álbuns para Spotify, Apple Music, TikTok e mais de
                            <strong><?php echo $stores ?: 150; ?> lojas digitais</strong>.
                            Mantenha <strong><?php echo $royalty; ?>%</strong> dos seus direitos.
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="#planos" class="btn btn-wasomupfy btn-lg mt-2 smooth-scroll">
                                Ver Planos <i class="fa-solid fa-arrow-down ms-2"></i>
                            </a>
                            <a href="../../contact" class="btn btn-outline-secondary btn-lg">
                                Falar com Suporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Parceiros -->
        <div class="py-4 bg-light-100 border-bottom">
            <div class="container">
                <p class="text-center text-white-stable small text-uppercase fw-bold mb-3">
                    Parceiros Oficiais de Distribuição
                </p>
                <div class="row justify-content-center align-items-center grayscale-hover g-4 text-center">
                    <div class="col-6 col-md-2"><i class="fa-brands fa-spotify text-wasomupfy fs-2"></i> Spotify</div>
                    <div class="col-6 col-md-2"><i class="fa-brands fa-apple text-wasomupfy fs-2"></i> Apple Music</div>
                    <div class="col-6 col-md-2"><i class="fa-brands fa-youtube text-wasomupfy fs-2"></i> YouTube</div>
                    <div class="col-6 col-md-2"><i class="fa-brands fa-tiktok text-wasomupfy fs-2"></i> TikTok</div>
                    <div class="col-6 col-md-2"><i class="fa-brands fa-amazon text-wasomupfy fs-2"></i> Amazon</div>
                    <div class="col-6 col-md-2"><i class="fa-brands fa-deezer text-wasomupfy fs-2"></i> Deezer</div>
                </div>
            </div>
        </div>

        <!-- Como funciona -->
        <section class="py-4 bg-light-100">
            <div class="container">
                <div class="text-center mb-6" data-cue="fadeIn">
                    <h2 class="fw-bold display-5">Do estúdio para o mundo</h2>
                    <p class="text-muted lead">O processo é simples, rápido e transparente.</p>
                </div>
                <div class="row g-5">
                    <div class="col-md-4 text-center" data-cue="slideInUp">
                        <div class="icon-shape icon-xl bg-light-primary text-wasomupfy rounded-circle mb-4 shadow-sm">
                            <i class="fa-solid fa-cloud-arrow-up fs-2"></i>
                        </div>
                        <h3>1. Upload</h3>
                        <p class="text-muted">
                            Carregue suas faixas em alta qualidade (WAV) e a arte da capa
                            diretamente na nossa plataforma intuitiva.
                        </p>
                    </div>
                    <div class="col-md-4 text-center" data-cue="slideInUp">
                        <div class="icon-shape icon-xl bg-light-primary text-wasomupfy rounded-circle mb-4 shadow-sm">
                            <i class="fa-solid fa-list-check fs-2"></i>
                        </div>
                        <h3>2. Verificação</h3>
                        <p class="text-muted">
                            Nossa equipe revisa os metadados para garantir que tudo está nos
                            padrões das lojas, evitando rejeições.
                        </p>
                    </div>
                    <div class="col-md-4 text-center" data-cue="slideInUp">
                        <div class="icon-shape icon-xl bg-light-primary text-wasomupfy rounded-circle mb-4 shadow-sm">
                            <i class="fa-solid fa-globe fs-2"></i>
                        </div>
                        <h3>3. Lançamento</h3>
                        <p class="text-muted">
                            Sua música entra nas lojas na data escolhida e você começa a
                            acompanhar as estatísticas e ganhos.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Planos — dinâmico -->
        <section id="planos" class="py-5 bg-light-100">
            <div class="container">
                <div class="text-center mb-6">
                    <h2 class="fw-bold">Escolha o plano ideal para a sua carreira</h2>
                    <p class="text-muted">Opções flexíveis para artistas independentes e selos.</p>
                </div>

                <div class="row g-4 justify-content-center">
                    <?php
                    $planIcons    = ['single' => 'fa-music', 'album' => 'fa-compact-disc', 'artist' => 'fa-microphone-lines', 'label' => 'fa-tags'];
                    $planLabels   = ['single' => 'Single', 'album' => 'Álbum / EP', 'artist' => 'Plano Artista', 'label' => 'Label / Selo'];
                    $planFeatures = [
                        'single'  => ['1 Faixa', 'Distribuição Global', 'ISRC Grátis', 'Pagamento Único'],
                        'album'   => ['Múltiplas Faixas', 'Distribuição Global', 'UPC/ISRC Grátis', 'Pagamento Único'],
                        'artist'  => ['<strong>Lançamentos Ilimitados</strong>', '1 Artista', 'Verificado Spotify', 'Suporte Prioritário'],
                        'label'   => ['<strong>Artistas Ilimitados</strong>', 'Painel de Gestão', 'Relatórios Separados', 'Distribuição Ilimitada'],
                    ];
                    $delays = ['single' => '', 'album' => ' data-delay="100"', 'artist' => ' data-delay="200"', 'label' => ' data-delay="300"'];
                    $isFeatured = 'artist';

                    foreach (['single', 'album', 'artist', 'label'] as $idx => $slug):
                        $p = $plansBySlug[$slug] ?? null;
                        $price = $p ? number_format($p['price_plan'], 0, ',', '.') : '—';
                        $period = $p ? ($p['type_plan'] === 'subscription' ? '/2 anos' : '/lançamento') : '';
                        $icon  = $planIcons[$slug];
                        $label = $planLabels[$slug];
                        $feats = $planFeatures[$slug];
                        $delay = $delays[$slug];
                        $featured = ($slug === $isFeatured);
                    ?>
                        <div class="col-lg-3 col-md-6" data-cue="zoomIn" <?php echo $delay; ?>>
                            <?php if ($featured): ?>
                                <div
                                    class="card h-100 border-0 shadow-lg hover-lift text-center py-4 position-relative overflow-hidden">
                                    <span
                                        class="bg-wasomupfy text-white position-absolute top-0 start-0 w-100 py-1 small fw-bold">
                                        RECOMENDADO
                                    </span>
                                    <div class="card-body mt-2">
                                    <?php else: ?>
                                        <div class="card h-100 border-0 shadow-sm hover-lift text-center py-4">
                                            <div class="card-body">
                                            <?php endif; ?>
                                            <div class="mb-3 text-wasomupfy">
                                                <i class="fa-solid <?php echo $icon; ?> display-4"></i>
                                            </div>
                                            <h4 class="fw-bold"><?php echo $label; ?></h4>
                                            <h2 class="my-3 text-dark">
                                                <?php echo $price; ?> Kz
                                                <span class="fs-6 text-muted"><?php echo $period; ?></span>
                                            </h2>
                                            <ul class="list-unstyled mb-4 text-start small px-3">
                                                <?php foreach ($feats as $feat): ?>
                                                    <li class="mb-2">
                                                        <i class="fa-solid fa-check text-success me-2"></i>
                                                        <?php echo $feat; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php if ($canRegister): ?>
                                                <a href="../../plan/<?php echo $slug; ?>"
                                                    class="btn <?php echo $featured ? 'btn-wasomupfy' : 'btn-outline-wasomupfy'; ?> w-100 rounded-pill<?php echo $featured ? ' shadow' : ''; ?>">
                                                    Selecionar
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary w-100 rounded-pill disabled">Indisponível</span>
                                            <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                        </div>
        </section>

        <!-- FAQ -->
        <section class="py-5 bg-light-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="text-center mb-5">
                            <h2 class="fw-bold">Perguntas Frequentes</h2>
                        </div>
                        <div class="accordion accordion-flush" id="faqDistro">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Quanto tempo demora para a música entrar no Spotify?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqDistro">
                                    <div class="accordion-body text-muted">
                                        Geralmente leva entre 2 a 5 dias úteis após a aprovação da nossa equipe.
                                        Recomendamos enviar com 2 semanas de antecedência.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Eu mantenho os direitos da minha música?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqDistro">
                                    <div class="accordion-body text-muted">
                                        Sim, <?php echo $royalty; ?>%. A
                                        <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>
                                        é apenas a distribuidora. Você mantém a propriedade total das suas obras e
                                        fonogramas.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Como recebo meus pagamentos?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqDistro">
                                    <div class="accordion-body text-muted">
                                        Os royalties são pagos mensalmente assim que as lojas nos repassam.
                                        Você pode solicitar o saque direto para sua conta bancária angolana.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="py-5 bg-light-100 position-relative overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10"
                style="background-image: url('../../assets/img/theme/pattern.png')"></div>
            <div class="container position-relative z-index-2 text-center">
                <h2 class="display-6 fw-bold mb-4">Pronto para lançar seu próximo hit?</h2>
                <p class="lead text-muted mb-5">
                    Junte-se a milhares de artistas que confiam na
                    <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>.
                </p>
                <?php if ($canRegister): ?>
                    <a href="<?php echo APP_URL  ?>/register"
                        class="btn btn-wasomupfy btn-xl px-5 py-3 fw-bold rounded-pill shadow-lg hover-scale">
                        Criar Conta Gratuitamente <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                <?php else: ?>
                    <span class="btn btn-secondary btn-xl px-5 py-3 fw-bold rounded-pill disabled">
                        Inscrições Temporariamente Fechadas
                    </span>
                <?php endif; ?>
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
                    <!-- Logo + Social -->
                    <div class="col-lg-3 col-12">
                        <a href="../../home" class="d-inline-block mb-4 navbar-brand">
                            <img src="../../assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy" width="65"
                                class="img-logo" height="60" />
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
                            <li class="mb-2"><a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento</a></li>
                            <li class="mb-2"><a href="../support/help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="../../contact"
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
                        <p class="text-muted mb-4">Obrigado! A equipa vai analisar com atenção. 🙏</p>
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
    <script src="<?php echo APP_URL  ?>/js/theme.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/vendors/color-modes.js"></script>
    <script src="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/vendors/scrollcue.js"></script>
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

                fetch('../../ajax/feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf: csrfInput.value,
                            name: name,
                            subject: subject,
                            message: message,
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