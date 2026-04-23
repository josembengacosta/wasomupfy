<?php
// ══════════════════════════════════════════════
// WASOM UPFY — Plano Single
// Arquivo: plan/single.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../include/site.php';

checkPlatformStatus('single');
trackVisitor('/plan/single', 'Plano Single — Wasom Upfy');

$plans       = getPlans();
$plansBySlug = [];
foreach ($plans as $p) {
    $plansBySlug[$p['slug_plan']] = $p;
}
$plan        = $plansBySlug['single'] ?? null;
$platform    = getPlatform();

$canRegister = (bool)$platform['allow_register'];
$royalty     = (int)$platform['royalty_percentage'];
$fee         = 100 - $royalty;
$storesCount = (int)$platform['stores_count'];

if (!$plan) {
    header('Location: all-plans');
    exit;
}

$price  = number_format($plan['price_plan'], 0, ',', '.');
$period = $plan['type_plan'] === 'subscription' ? 'Kz/ano' : 'Kz/single';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>, Single, Distribuição Musical, Royalties, Single, Álbum, Artista, Label" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D">

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title"
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> - Plano Single" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_tagline', 'Distribua sua música para o mundo')); ?>. <?php echo $royalty; ?>% dos royalties são seus. Distribua em <?php echo $storesCount; ?>+ lojas digitais." />
    <meta property="og:url" content="https://wasomupfy.com/plan/single" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />
    <meta property="og:image" content="https://wasomupfy.com/imgs/og_wasomupfy.jpeg" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="Planos <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />

    <title><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> | Plano Single</title>
    <!-- O processo de carregamento do site em Javascript fim -->
    <script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                document.querySelector("body").classList.add("loaded")
            }, 200)
        })
    </script>
    <!-- O processo de carregamento do site em Javascript fim -->
    <link rel="shortcut icon" href="<?php echo APP_URL  ?>/assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/theme.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/framework.css">
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/main.css">

    <!-- Schema Markup -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?php echo cfg('site_name', 'Wasom Upfy'); ?>",
            "url": "https://wasomupfy.rf.gd",
            "logo": "https://wasomupfy.rf.gd/logo.png",
            "sameAs": [
                <?php
                $sameAs = array_filter([
                    cfg('facebook_url'),
                    cfg('instagram_url'),
                    cfg('youtube_url'),
                    cfg('tiktok_url'),
                ]);
                echo '"' . implode('","', $sameAs) . '"';
                ?>
            ],
            "contactPoint": {
                "@type": "ContactPoint",
                "email": "<?php echo cfg('support_email', 'suporte@wasomupfy.com'); ?>",
                "contactType": "customer service",
                "hoursAvailable": {
                    "@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                    "opens": "08:00",
                    "closes": "17:00"
                }
            }
        }
    </script>

    <!-- Offer Schema por plano -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ItemList",
            "name": "Planos <?php echo cfg('site_name', 'Wasom Upfy'); ?>",
            "itemListElement": [
                <?php
                $schemaItems = [];
                foreach ($plans as $i => $p) {
                    $schemaItems[] = json_encode([
                        "@type"    => "ListItem",
                        "position" => $i + 1,
                        "item"     => [
                            "@type"       => "Offer",
                            "name"        => $p['name_plan'],
                            "description" => $p['description_plan'],
                            "price"       => number_format($p['price_plan'], 2, '.', ''),
                            "priceCurrency" => "AOA",
                            "url"         => "https://wasomupfy.com/plan/single" . $p['slug_plan'],
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                }
                echo implode(",\n            ", $schemaItems);
                ?>
            ]
        }
    </script>
</head>

<body>

    <!-- O processo de carregamento do site em HTML & CSS -->
    <div class="preloader">
        <img src="../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="Loading-wasomupfy">
    </div>
    <!-- O processo de carregamento do site em HTML & CSS fim -->

    <!-- Cabecalho da página de navbar -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="../home" title="Home"><img src="../assets/img/brand/wasomupfy_brand.png"
                        width="65" class="img-logo" height="60" alt="Logo Wasom Upfy" /></a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Home" href="../home"><img width="65" src="../assets/img/brand/wasomupfy_brand.png"
                                alt="Logo Wasom Upfy" /></a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item">
                                <a class="nav-link" href="../home" title="Inicio" role="button" data-bs-toggle="link"
                                    aria-expanded="false">Início</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../about" title="Sobre" role="button" data-bs-toggle="link"
                                    aria-expanded="false">Sobre</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../blog/" title="Blogue" target="_blank" rel="external"
                                    role="button" data-bs-toggle="link" aria-expanded="false">Blogue</a>
                            </li>
                            <li class="nav-item dropdown active">
                                <a title="Planos" class="nav-link active" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Planos
                                    <i data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-md" aria-labelledby="navbarDropdown">
                                    <?php foreach ($plans as $p):
                                        $nSlug   = $p['slug_plan'];
                                        $iconMap  = ['single' => 'fa-music', 'album' => 'fa-compact-disc', 'artist' => 'fa-microphone-lines', 'label' => 'fa-tags'];
                                        $nIcon   = $iconMap[$nSlug] ?? 'fa-music';
                                        $nPrc    = number_format($p['price_plan'], 0, ',', '.');
                                        $nPer    = $p['type_plan'] === 'subscription' ? '/ano' : '';
                                        $nActive = ($nSlug === 'single') ? ' active' : '';
                                    ?>
                                        <a title="<?php echo htmlspecialchars($p['name_plan']); ?>"
                                            class="dropdown-item mb-3 text-body<?php echo $nActive; ?>"
                                            href="<?php echo $nSlug; ?>">
                                            <div class="d-flex align-items-center">
                                                <i class="fa-solid <?php echo $nIcon; ?> text-wasomupfy fs-3"
                                                    style="width: 35px;"></i>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                                    <p class="mb-0 fs-6">Nosso plano
                                                        <?php echo htmlspecialchars($p['name_plan']); ?> —
                                                        <?php echo $nPrc; ?> Kz<?php echo $nPer; ?></p>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>

                                    <a title="Todos os planos" class="dropdown-item mb-3 text-body" href="all-plans">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-layer-group text-wasomupfy fs-3"
                                                style="width: 35px;"></i>
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
                                            <div>
                                                <div>
                                                    <div class="dropdown-header">Blog</div>
                                                    <a title="Novidades" class="dropdown-item"
                                                        href="../blog/">Novidades</a>
                                                    <a title="Passatempo Wasom Upfy" class="dropdown-item"
                                                        href="../blog/">Passatempo</a>
                                                    <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                        <span class="badge bg-warning">Indisponível</span></a>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="dropdown-header">Sobre</div>
                                                    <a title="A nossa marca" class="dropdown-item"
                                                        href="../about?#nossamarca">A
                                                        nossa marca</a>
                                                    <a title="Parcerias" class="dropdown-item"
                                                        href="../partnership">Parcerias</a>
                                                    <a title="Quem somos" class="dropdown-item"
                                                        href="../about#nossa-historia">Quem
                                                        somos</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div>
                                                    <div class="dropdown-header">Serviços</div>
                                                    <a title="Distribuição de música" class="dropdown-item"
                                                        href="../page/services/music-distribution">Distribuição
                                                        de música</a>
                                                    <a title="Promoção de música" class="dropdown-item"
                                                        href="../page/services/music-promotion">Promoção de
                                                        música <span class="badge bg-success">Novo</span></a>
                                                    <a title="Serviços Personalizados" class="dropdown-item"
                                                        href="../page/services/customized-services">Serviços
                                                        personalizados
                                                        <span class="badge bg-warning">Indisponível</span></a>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="dropdown-header">Contactos</div>
                                                    <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                        href="https://www.facebook.com/m.me/2007900989425052"
                                                        target="_blank"
                                                        rel="external noopener noreferrer">Atendimento</a>
                                                    <a title="Contacto-nos" class="dropdown-item"
                                                        href="../contact">Contacta-nos</a>
                                                    <a title="Canal WhatsApp" class="dropdown-item"
                                                        href="https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04"
                                                        target="_blank" rel="external noopener noreferrer">Canal
                                                        WhatsApp</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div>
                                                    <div class="dropdown-header">Sugestões</div>
                                                    <a title="Ajuda" class="dropdown-item"
                                                        href="../page/support/help">Ajuda <span
                                                            class="badge bg-success">Novo</span></a>
                                                    <a title="Feedback" class="dropdown-item" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#modalFeedback">
                                                        Feedback</a>
                                                    <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                        <span class="badge bg-warning">Indisponível</span></a>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="dropdown-header">Ajuda</div>
                                                    <a title="Tutorial" class="dropdown-item"
                                                        href="../page/support/tutorial">Tutorial <span
                                                            class="badge bg-success">Novo</span></a>
                                                    <a title="Ocorreu um erro" class="dropdown-item"
                                                        href="../page/support/support">Suporte técnico</a>
                                                    <a title="Perguntas frequentes" class="dropdown-item"
                                                        href="../page/support/faq">Perguntas frequentes</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../resources" title="Recursos" role="button"
                                    data-bs-toggle="link" aria-expanded="false"> Recursos</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a title="Contacto" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">Contactar <i data-feather="chevron-down"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a title="Caixa de mensagem" class="dropdown-item" href="../contact"> Caixa
                                            de
                                            mensagem</a>
                                    </li>
                                    <li><a title="E-mail" class="dropdown-item"
                                            href="/cdn-cgi/l/email-protection#41282f272e013620322e2c343127386f222e2c7e3234232b2422357c042f3533202f252e61242c61222e2f352022352e61222e2c61243034283120612524611620322e2c61143127386f672322227c3234312e333524013620322e2c343127386f222e2c67232e25387c0e2d82e061243034283120612524611620322e2c61143127386f">
                                            <span class="__cf_email__"
                                                data-cfemail="41282f272e013620322e2c343127386f222e2c">[email&#160;protected]</span></a>
                                    </li>
                                    <li><a title="WhatsApp" class="dropdown-item"
                                            href="https://api.whatsapp.com/send/?phone=244922030116&text&type=phone_number&app_absent=0">
                                            WhatsApp </a>
                                    </li>
                                </ul>
                            </li>
                            </li>
                        </ul>
                        <div class="mt-3 mt-lg-0 d-flex align-items-center">
                            <a title="Sign-in" href="<?php echo APP_URL  ?>/login" class="btn btn-secondary mx-2">Entrar
                                <i data-feather="log-in"></i>
                            </a>
                            <a title="Sign-up" href="<?php echo APP_URL  ?>/register" class="btn btn-wasomupfy">Inscreva-se
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Cabecalho da página de navbar fim -->
    <!--Seccão do site inteira até ao perto do footer  -->
    <main>
        <!-- Hero Section -->
        <section class="all-plans-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../assets/img/theme/plan_single.png" alt="Plano Single Wasom Upfy"
                loading="lazy">
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item"><a href="all-plans" class="text-muted">Planos</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Plano Single</li>
                            </ol>
                        </nav>
                        <!-- <span class="badge bg-wasomupfy text-white-stable fw-semibold px-4 py-2 mb-3">Perfeito para
                            Iniciantes</span> -->
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">Plano Single</h1>
                        <p class="lead text-white-stable mb-4 opacity-90">Distribua seu próximo hit em todas as
                            plataformas por apenas <?php echo $price; ?><?php echo $period; ?></p>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                            <span class="badge bg-secondary text-black fw-semibold px-3 py-2">
                                <i class="bi bi-percent text-success me-1"></i> 90% Royalties
                            </span>
                            <span class="badge bg-secondary text-black fw-semibold px-3 py-2">
                                <i class="bi bi-lightning text-success me-1"></i> Lançamento em 72h
                            </span>
                            <span class="badge bg-secondary text-black fw-semibold px-3 py-2">
                                <i class="bi bi-headset text-success me-1"></i> Suporte 24/7
                            </span>
                        </div>
                        <a href="#details" class="btn btn-wasomupfy btn-lg mt-2 smooth-scroll">
                            Ver Detalhes <i class="bi bi-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Banner -->
        <section class="py-4 bg-wasom-gradient text-white" data-cue="fadeIn">
            <div class="container">
                <div class="row g-3 text-center">
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1"><?php echo $royalty; ?>%</h3>
                            <p class="small mb-0 opacity-85">Royalties</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1">48h-72h</h3>
                            <p class="small mb-0 opacity-85">Lançamento</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1">157+</h3>
                            <p class="small mb-0 opacity-85">Plataformas</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1">24/7</h3>
                            <p class="small mb-0 opacity-85">Suporte</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Plano Details -->
        <section id="details" class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-10 text-center">
                        <h2 class="display-5 fw-bold mb-3">Tudo o que você precisa para seu próximo sucesso</h2>
                        <p class="lead text-muted">O plano perfeito para artistas que estão começando ou querem testar
                            uma nova música</p>
                    </div>
                </div>

                <div class="row g-5">
                    <!-- Card do Plano -->
                    <div class="col-lg-8" data-cue="zoomIn">
                        <div class="pricing-card-main card border-0 shadow-lg hover-lift">
                            <div class="card-header border-0 pt-5 pb-4 px-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="h2 fw-bold mb-2">Plano Single</h3>
                                        <p class="text-muted mb-0">Por lançamento</p>
                                    </div>
                                    <div class="text-end">
                                        <div class="price-display">
                                            <span class="price-amount display-3 fw-bold"><?php echo $price; ?></span>
                                            <span
                                                class="price-period h4 text-muted fw-normal"><?php echo $period; ?></span>
                                        </div>
                                        <div class="badge bg-success mt-2">90% Royalties</div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-5">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">Royalty Split</span>
                                        <span class="fw-bold text-success"><?php echo $royalty; ?>% Artista |
                                            <?php echo $fee; ?>% Wasom Upfy</span>
                                    </div>
                                    <div class="progress" style="height: 12px; border-radius: 6px;">
                                        <div class="progress-bar bg-wasom-gradient" role="progressbar"
                                            style="width: <?php echo $royalty; ?>%" aria-label="Royalties do artista"
                                            aria-valuenow="<?php echo $royalty; ?>" aria-valuemin="0"
                                            aria-valuemax="100">
                                            <span class="visually-hidden">90% para o artista</span>
                                        </div>
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 10%"
                                            aria-label="Royalties da plataforma" aria-valuenow="10" aria-valuemin="0"
                                            aria-valuemax="100">
                                            <span class="visually-hidden">10% para a plataforma</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body pt-4 pb-5 px-5">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 class="h5 mb-3 text-dark"><i
                                                class="bi bi-check-circle-fill text-success me-2"></i> O que está
                                            incluído:</h4>
                                        <ul class="list-unstyled mb-4">
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>1 lançamento</strong> - Apenas uma faixa</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>1 Artista</strong> principal</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>1 Colaborador</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Análise de dados avançados</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>ISRC e UPC grátis</strong></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="h5 mb-3 text-dark"><i
                                                class="bi bi-lightning-fill text-warning me-2"></i> Recursos extras:
                                        </h4>
                                        <ul class="list-unstyled mb-4">
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Smartlink e pre-salve</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Lançamento em 48h-72h</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Suporte local (WhatsApp + E-mail)</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Atendimento rápido</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Agendar lançamentos</strong></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Call to Action -->
                                <div class="cta-box bg-wasom-light rounded-4 p-4 mt-5">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h4 class="h5 mb-2">Pronto para lançar sua música?</h4>
                                            <p class="mb-0 text-muted">Comece agora e tenha sua música em todas as
                                                plataformas em 48h-72h</p>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <?php if ($canRegister): ?><a href="<?php echo APP_URL  ?>/register?plan=single"
                                                    class="btn btn-wasomupfy btn-lg px-5">
                                                    Começar Agora <i class="bi bi-arrow-right ms-2"></i>
                                                </a><?php else: ?><span
                                                    class="btn btn-secondary btn-lg px-5 disabled">Inscrições
                                                    Fechadas</span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar com informações adicionais -->
                    <div class="col-lg-4" data-cue="slideInRight">
                        <!-- Plataformas -->
                        <div class="card border-0 shadow-sm mb-4 hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3"><i class="bi bi-grid-3x3-gap-fill text-wasom me-2"></i> Plataformas
                                    Incluídas</h4>
                                <div class="platforms-grid">
                                    <div class="platform-item text-center p-2">
                                        <i class="bi bi-spotify fs-3 text-success"></i>
                                        <p class="small mb-0 mt-1">Spotify</p>
                                    </div>
                                    <div class="platform-item text-center p-2">
                                        <i class="bi bi-apple fs-3 text-muted"></i>
                                        <p class="small mb-0 mt-1">Apple Music</p>
                                    </div>
                                    <div class="platform-item text-center p-2">
                                        <i class="bi bi-youtube fs-3 text-danger"></i>
                                        <p class="small mb-0 mt-1">YouTube Music</p>
                                    </div>
                                    <div class="platform-item text-center p-2">
                                        <i class="bi bi-tiktok fs-3 text-black"></i>
                                        <p class="small mb-0 mt-1">TikTok</p>
                                    </div>
                                    <div class="platform-item text-center p-2">
                                        <i class="bi bi-instagram fs-3 text-danger"></i>
                                        <p class="small mb-0 mt-1">Instagram</p>
                                    </div>
                                    <div class="platform-item text-center p-2">
                                        <span class="badge bg-dark text-white">+157</span>
                                        <p class="small mb-0 mt-1">Outras</p>
                                    </div>
                                </div>
                                <p class="small text-muted mt-3 mb-0">Distribuição global em todas as principais
                                    plataformas</p>
                            </div>
                        </div>

                        <!-- Suporte -->
                        <div class="card border-0 shadow-sm mb-4 hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3"><i class="bi bi-headset text-wasom me-2"></i> Suporte Premium</h4>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-whatsapp text-success mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">WhatsApp</span>
                                            <p class="small text-muted mb-0">Suporte rápido via WhatsApp</p>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-envelope text-primary mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">E-mail</span>
                                            <p class="small text-muted mb-0">Suporte detalhado por e-mail</p>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="bi bi-clock text-warning mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">24/7</span>
                                            <p class="small text-muted mb-0">Atendimento em tempo útil</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Processo Simples -->
                        <div class="card border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3"><i class="bi bi-list-check text-wasom me-2"></i> Processo em 3
                                    Passos</h4>
                                <div class="process-steps">
                                    <div class="process-step d-flex mb-3">
                                        <div class="step-number bg-wasom-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 36px; height: 36px;">
                                            1
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Upload da Música</h6>
                                            <p class="small text-muted mb-0">Envie seus arquivos de áudio e arte</p>
                                        </div>
                                    </div>
                                    <div class="process-step d-flex mb-3">
                                        <div class="step-number bg-wasom-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 36px; height: 36px;">
                                            2
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Revisão e Aprovação</h6>
                                            <p class="small text-muted mb-0">Nossa equipe revisa em até 24h</p>
                                        </div>
                                    </div>
                                    <div class="process-step d-flex">
                                        <div class="step-number bg-wasom-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 36px; height: 36px;">
                                            3
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Lançamento Global</h6>
                                            <p class="small text-muted mb-0">Sua música vai ao ar em 48h-72h</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-8 text-center">
                        <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">FAQ</span>
                        <h2 class="display-5 fw-bold mb-4">Perguntas Frequentes sobre o Plano Single</h2>
                        <p class="text-muted lead">Tire todas as suas dúvidas antes de começar</p>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="accordion" id="singleFaqAccordion">
                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq1">
                                        O que exatamente inclui o "1 lançamento"?
                                    </button>
                                </h3>
                                <div id="faq1" class="accordion-collapse collapse show"
                                    data-bs-parent="#singleFaqAccordion">
                                    <div class="accordion-body">
                                        O plano Single inclui a distribuição de uma única faixa musical. Isso inclui
                                        todos os processos: revisão de metadados, criação de ISRC/UPC, distribuição para
                                        todas as plataformas, smartlink e pre-salve. Você pode lançar uma música por
                                        vez, ideal para singles ou testar novas produções.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Como funcionam os 90% de royalties?
                                    </button>
                                </h3>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#singleFaqAccordion">
                                    <div class="accordion-body">
                                        Você recebe 90% de todo o valor gerado pela sua música nas plataformas de
                                        streaming (Spotify, Apple Music, Deezer, etc.). Os 10% restantes cobrem nossos
                                        custos operacionais. Os pagamentos são processados mensalmente e você tem acesso
                                        a relatórios detalhados na sua conta.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Posso adicionar colaboradores na música?
                                    </button>
                                </h3>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#singleFaqAccordion">
                                    <div class="accordion-body">
                                        Sim! O plano Single permite 1 colaborador. Você pode
                                        adicionar
                                        admininistrador, analista, editor, etc.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq4">
                                        Em quanto tempo minha música estará disponível?
                                    </button>
                                </h3>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#singleFaqAccordion">
                                    <div class="accordion-body">
                                        Após a aprovação, sua música é distribuída em até 72 horas. Você também pode
                                        agendar lançamentos futuros. Para pré-saves, o smartlink fica disponível
                                        imediatamente após a aprovação, permitindo que você comece a promover sua música
                                        antes do lançamento oficial.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq5">
                                        E se eu quiser fazer upgrade depois?
                                    </button>
                                </h3>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#singleFaqAccordion">
                                    <div class="accordion-body">
                                        Você pode fazer upgrade para nossos planos Álbum, Artista ou Label a qualquer
                                        momento. Se fizer upgrade dentro do mesmo mês, o valor pago no Single será
                                        descontado proporcionalmente do novo plano. Todo o histórico de lançamentos é
                                        mantido na sua conta.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Outros Planos -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-8 text-center">
                        <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">Compare
                            Planos</span>
                        <h2 class="display-5 fw-bold mb-4">Conheça Nossos Outros Planos</h2>
                        <p class="text-muted lead">Encontre o plano perfeito para suas necessidades</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4" data-cue="zoomIn">
                        <div class="card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3">Plano Álbum</h4>
                                <div class="price-display mb-3">
                                    <span
                                        class="price-amount h3 fw-bold"><?php echo isset($plansBySlug['album']) ? number_format($plansBySlug['album']['price_plan'], 0, ',', '.') : '—'; ?></span>
                                    <span
                                        class="price-period text-muted"><?php echo isset($plansBySlug['album']) ? ($plansBySlug['album']['type_plan'] === 'subscription' ? 'Kz/ano' : 'Kz/album') : ''; ?></span>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <?php if (isset($plansBySlug['album']['features'])): foreach (array_slice($plansBySlug['album']['features'], 0, 3) as $f): ?>
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="bi bi-check-lg text-success mt-1 me-2"></i>
                                                <span><?php echo htmlspecialchars($f['feature_text']); ?></span>
                                            </li>
                                    <?php endforeach;
                                    endif; ?>
                                </ul>
                                <a href="album" class="btn btn-outline-primary w-100">Ver Plano Álbum</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4" data-cue="zoomIn" data-delay="100">
                        <div class="card border-wasom border-3 h-100 shadow-lg hover-lift position-relative">
                            <div class="position-absolute top-0 start-50 translate-middle">
                                <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2">Mais Popular</span>
                            </div>
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3">Plano Artista</h4>
                                <div class="price-display mb-3">
                                    <span
                                        class="price-amount h3 fw-bold"><?php echo isset($plansBySlug['artist']) ? number_format($plansBySlug['artist']['price_plan'], 0, ',', '.') : '—'; ?></span>
                                    <span
                                        class="price-period text-muted"><?php echo isset($plansBySlug['artist']) ? ($plansBySlug['artist']['type_plan'] === 'subscription' ? 'Kz/ano' : 'Kz/artist') : ''; ?></span>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <?php if (isset($plansBySlug['artist']['features'])): foreach (array_slice($plansBySlug['artist']['features'], 0, 3) as $f): ?>
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="bi bi-check-lg text-success mt-1 me-2"></i>
                                                <span><?php echo htmlspecialchars($f['feature_text']); ?></span>
                                            </li>
                                    <?php endforeach;
                                    endif; ?>
                                </ul>
                                <a href="artist" class="btn btn-wasomupfy w-100">Ver Plano Artista</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4" data-cue="zoomIn" data-delay="200">
                        <div class="card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3">Plano Label</h4>
                                <div class="price-display mb-3">
                                    <span
                                        class="price-amount h3 fw-bold"><?php echo isset($plansBySlug['label']) ? number_format($plansBySlug['label']['price_plan'], 0, ',', '.') : '—'; ?></span>
                                    <span
                                        class="price-period text-muted"><?php echo isset($plansBySlug['label']) ? ($plansBySlug['label']['type_plan'] === 'subscription' ? 'Kz/ano' : 'Kz/label') : ''; ?></span>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <?php if (isset($plansBySlug['label']['features'])): foreach (array_slice($plansBySlug['label']['features'], 0, 3) as $f): ?>
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="bi bi-check-lg text-success mt-1 me-2"></i>
                                                <span><?php echo htmlspecialchars($f['feature_text']); ?></span>
                                            </li>
                                    <?php endforeach;
                                    endif; ?>
                                </ul>
                                <a href="label" class="btn btn-outline-primary w-100">Ver Plano Label</a>
                            </div>
                        </div>
                    </div>
                </div>
        </section>

        <!-- Final CTA -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h2 class="display-5 fw-bold mb-4 text-">Pronto para lançar sua música?</h2>
                        <p class="lead mb-5 opacity-90">Comece agora com o Plano Single e tenha seu próximo hit em todas
                            as plataformas em 72 horas</p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <?php if ($canRegister): ?><a href="<?php echo APP_URL  ?>/register?plan=single"
                                    class="btn btn-wasomupfy btn-lg px-5 text-wasom fw-semibold">
                                    Começar Agora <i class="bi bi-arrow-right ms-2"></i>
                                </a><?php else: ?><span class="btn btn-secondary btn-lg px-5 disabled">Inscrições
                                    Fechadas</span><?php endif; ?>
                            <a href="contact" class="btn btn-outline-secondary btn-lg px-5">
                                Falar com Suporte
                            </a>
                        </div>
                        <p class="mt-4 small opacity-75">
                            <i class="bi bi-shield-check me-1 text-success"></i> Pagamento 100% seguro • Sem contrato
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <!--Seccão do site inteira até ao perto do footer  fim -->
    <div class="divider-fade"></div>
    <!-- Footer -->
    <footer class="bg-light-100 pt-7" role="contentinfo" aria-label="Rodapé do site">
        <div class="container">
            <!-- Call-to-action Newsletter -->
            <div class="row align-items-center mb-7 border-bottom border-white-10 pb-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h3 class="fw-bold mb-1">Junte-se a +10.000 Artistas</h3>
                    <p class="lead text-muted mb-0">
                        Receba dicas de marketing, novidades da indústria e ofertas
                        exclusivas.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form action="#" class="row g-2">
                        <div class="col-sm-8">
                            <input type="email" class="form-control border-0 text-muted py-3" autocapitalize="email"
                                required placeholder="Seu melhor e-mail" />
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-wasomupfy w-100 py-3 fw-bold">
                                Inscrever
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Navegação do Footer -->
            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">
                    <!-- Logo + Redes Sociais -->
                    <div class="col-lg-3 col-12">
                        <a href="../home" class="d-inline-block mb-4 navbar-brand">
                            <img src="../assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy" width="65"
                                class="img-logo" height="60" />
                        </a>
                        <p class="lead text-muted small mb-4">
                            Levamos a música angolana para o mundo. Distribuição digital,
                            marketing e gestão de carreira num só lugar.
                        </p>
                        <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
                            <?php if (cfg('instagram_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="Instagram da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-instagram"></i>
                                    <span class="visually-hidden">Instagram</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('facebook_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="Facebook da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-facebook-f"></i>
                                    <span class="visually-hidden">Facebook</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('youtube_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="YouTube da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-youtube"></i>
                                    <span class="visually-hidden">YouTube</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('linkedin_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    aria-label="LinkedIn da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-linkedin-in"></i>
                                    <span class="visually-hidden">LinkedIn</span>
                                </a>
                            <?php endif; ?>
                            <?php if (cfg('whatsapp_number')): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', cfg('whatsapp_number')); ?>"
                                    target="_blank" rel="external noopener noreferrer"
                                    aria-label="WhatsApp da Wasom Upfy (abre em nova janela)"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                    <i class="fa-brands fa-whatsapp"></i>
                                    <span class="visually-hidden">WhatsApp</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Empresa -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Empresa</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a href="../../about" class="text-reset text-decoration-none hover-white">Sobre
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../../about#nossamarca" class="text-reset text-decoration-none hover-white">A
                                    nossa marca
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="all-plans" class="text-reset text-decoration-none hover-white">Planos
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../page/services/customized-services"
                                    class="text-reset text-decoration-none hover-white">Serviços Premium</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Suporte -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Suporte</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="text-reset text-decoration-none hover-white">Atendimento
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../page/support/help" class="text-reset text-decoration-none hover-white">Ajuda
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../contact" class="text-reset text-decoration-none hover-white">Contacta-nos
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="https://wa.me/244923456789"
                                    class="text-reset text-decoration-none hover-white">WhatsApp
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Contacto e Localidade -->
                    <div class="col-lg-3 col-12">
                        <h3 class="fw-bold mb-3">Contacto</h3>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-3 d-flex">

                                <span>Angola - Luanda</span>
                            </li>
                            <li class="mb-3 d-flex">
                                <?php if (cfg('support_email')): ?>
                                    <a href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                        class="text-reset text-decoration-none">
                                        <?php echo htmlspecialchars(cfg('support_email')); ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                            <?php if (cfg('info_email')): ?>
                                <li class="mb-3 d-flex">
                                    <a href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                        class="text-reset text-decoration-none">
                                        <?php echo htmlspecialchars(cfg('info_email')); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="d-flex">
                                <span>Seg - Sex: 08h às 17h</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Rodapé Inferior - Copyright e Links Legais -->
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
                            <a href="../page/politicies/privacy" class="text-reset text-decoration-none">Política de
                                Privacidade</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="../page/politicies/terms" class="text-reset text-decoration-none">Termos de Uso</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="../page/politicies/cookies" class="text-reset text-decoration-none">Cookies</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer fim -->

    <!-- Scroll top  ou voltar para cima-->
    <div class="btn-scroll-top">
        <svg class="progress-square svg-content" width="100%" height="100%" viewBox="0 0 40 40">
            <path
                d="M8 1H32C35.866 1 39 4.13401 39 8V32C39 35.866 35.866 39 32 39H8C4.13401 39 1 35.866 1 32V8C1 4.13401 4.13401 1 8 1Z" />
        </svg>
    </div>
    <!-- Scroll top  ou voltar para cima fim-->
    <!-- Mudar de do tema da so site-->
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
                            <i class="fa-solid fa-sun"></i>
                            <span class="ms-2">Claro</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"
                            aria-pressed="false">
                            <i class="fa-solid fa-moon"></i>
                            <span class="ms-2">Escuro</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center active"
                            data-bs-theme-value="auto" aria-pressed="true">
                            <i class="fa-solid fa-display"></i>
                            <span class="ms-2">Sistema</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Mudar de do tema da so site fim-->
    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-labelledby="modalFeedbackLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-wasomupfy text-white border-0">
                    <h5 class="modal-title fw-bold" id="modalFeedbackLabel">
                        <i class="fa-solid fa-bullhorn me-2"></i> Sua opinião importa!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-muted">Como tem sido sua experiência com a <strong>Wasom Upfy</strong>? Suas
                        sugestões nos ajudam a evoluir.</p>

                    <form id="formFeedback">
                        <input type="hidden" name="csrf" value="<?php echo getSiteCsrf(); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Seu Nome</label>
                            <input type="text" class="form-control" placeholder="Ex: André Wasom" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Assunto</label>
                            <select class="form-select">
                                <option selected>Sugestão de melhoria</option>
                                <option>Elogio</option>
                                <option>Relatar um problema</option>
                                <option>Outros</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Sua Mensagem</label>
                            <textarea class="form-control" rows="4" placeholder="Conte-nos em detalhes..."
                                required></textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-wasomupfy btn-lg">
                                Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="modal-footer border-0 justify-content-center pb-4">
                    <small class="text-muted">A Wasom Upfy agradece sua parceria!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Libs JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Simplebar (Scrollbar customizado) -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <!-- Headhesive (Sticky header) -->
    <script src="https://cdn.jsdelivr.net/npm/headhesive@1.2.4/dist/headhesive.min.js"></script>
    <!-- Theme JS -->
    <script src="<?php echo APP_URL  ?>/js/theme.min.js"></script>
    <!-- Color modes -->
    <script src="<?php echo APP_URL  ?>/js/vendors/color-modes.js"></script>
    <script src="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/vendors/scrollcue.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-injector@1.1.3/dist/svg-injector.min.js"></script>
    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    <!-- In View (Detectar elementos na viewport) -->
    <script src="https://unpkg.com/in-view@0.6.1/dist/in-view.min.js"></script>
    <!-- Sticky Kit (Elementos sticky) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sticky-kit/1.1.3/sticky-kit.min.js"></script>
    <!-- ImagesLoaded -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <!-- Jarallax (Efeitos parallax) -->
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>
    <script>
        feather.replace({
            width: "1em",
            height: "1em"
        })
    </script>
    <script>
        ! function(e, t, a, n, g) {
            e[n] = e[n] || [], e[n].push({
                "gtm.start": (new Date).getTime(),
                event: "gtm.js"
            });
            var m = t.getElementsByTagName(a)[0],
                r = t.createElement(a);
            r.async = !0, r.src = "https://www.googletagmanager.com/gtm.js?id=GTM-MF4DZVH", m.parentNode.insertBefore(r,
                m)
        }(window, document, "script", "dataLayer")
    </script>

    <script>
        // Smooth scroll para âncoras
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;

                e.preventDefault();
                const targetElement = document.querySelector(href);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animation on scroll
        const animateOnScroll = () => {
            const elements = document.querySelectorAll('[data-cue]');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;

                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('animated');
                }
            });
        };

        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll();

        // Add plan parameter to register links
        document.querySelectorAll('a[href*="register"]').forEach(link => {
            if (!link.getAttribute('href').includes('plan=')) {
                link.setAttribute('href', link.getAttribute('href') + '?plan=single');
            }
        });

        // FAQ accordion
        const faqItems = document.querySelectorAll('.accordion-button');
        faqItems.forEach(item => {
            item.addEventListener('click', function() {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                if (!isExpanded) {
                    target.classList.add('expanding');
                    setTimeout(() => {
                        target.classList.remove('expanding');
                    }, 300);
                }
            });
        });
    </script>
</body>

</html>