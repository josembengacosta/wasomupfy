<?php
// ══════════════════════════════════════════════
// WASOM UPFY — Plano Label
// Arquivo: plan/label.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../include/site.php';

checkPlatformStatus('label');
trackVisitor('/plan/label', 'Plano Label — Wasom Upfy');

$plans       = getPlans();
$plansBySlug = [];
foreach ($plans as $p) {
    $plansBySlug[$p['slug_plan']] = $p;
}
$plan        = $plansBySlug['label'] ?? null;
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
$period = $plan['type_plan'] === 'subscription' ? 'Kz/ano' : 'Kz/label';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>, Label, Distribuição Musical, Royalties, Single, Álbum, Artista, Label" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D">

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> - Plano Label" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_tagline', 'Distribua sua música para o mundo')); ?>. <?php echo $royalty; ?>% dos royalties são seus. Distribua em <?php echo $storesCount; ?>+ lojas digitais." />
    <meta property="og:url" content="https://wasomupfy.com/plan/label" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />
    <meta property="og:image" content="https://wasomupfy.com/imgs/og_wasomupfy.jpeg" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="Planos <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />

    <title><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> | Plano Label</title>
    <!-- O processo de carregamento do site em Javascript fim -->
    <script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                document.querySelector("body").classList.add("loaded")
            }, 200)
        })
    </script>
    <!-- O processo de carregamento do site em Javascript fim -->
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon">
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
                            "url"         => "https://wasomupfy.com/plan/label" . $p['slug_plan'],
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
                                        $nActive = ($nSlug === 'label') ? ' active' : '';
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
                                    aria-expanded="false">Páginas <i data-feather="chevron-down"></i></span></a>
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
                                    <li><a title="Caixa de mensagem" class="dropdown-item" href="../contact">Caixa de
                                            mensagem</a></li>
                                    <li><a title="E-mail" class="dropdown-item"
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email', 'suporte@wasomupfy.com')); ?>"><?php echo htmlspecialchars(cfg('support_email', 'suporte@wasomupfy.com')); ?></a>
                                    </li>
                                    <li>
                                        <a title="WhatsApp" class="dropdown-item"
                                            href="https://api.whatsapp.com/send/?phone=<?php echo preg_replace('/\D/', '', cfg('whatsapp_number', '244922000000')); ?>&text&type=phone_number&app_absent=0"
                                            target="_blank" rel="external noopener noreferrer">WhatsApp</a>
                                    </li>
                                </ul>
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
            <img class="jarallax-img" src="../assets/img/theme/plan_label.png" alt="Plano Label Wasom Upfy"
                loading="lazy">
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item"><a href="all-plans" class="text-muted">Planos</a></li>
                                <li class="breadcrumb-item active text-white" aria-current="page">Plano Label</li>
                            </ol>
                        </nav>
                        <!-- <span class="badge bg-wasomupfy text-white-stable fw-semibold px-4 py-2 mb-3">Perfeito para gravadoras</span> -->
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">Plano Label</h1>
                        <p class="lead text-white-stable mb-4 opacity-90">Gerencie até 10 artistas por apenas
                            <?php echo $price; ?><?php echo $period; ?></p>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                            <span class="badge bg-secondary text-black fw-semibold px-3 py-2">
                                <i class="bi bi-music-note-list text-success me-1"></i> 10 Artistas
                            </span>
                            <span class="badge bg-secondary text-black fw-semibold px-3 py-2">
                                <i class="bi bi-percent text-success me-1"></i> 90% Royalties
                            </span>
                            <span class="badge bg-secondary text-black fw-semibold px-3 py-2">
                                <i class="bi bi-cash-stack text-success me-1"></i> Economia Máxima
                            </span>
                        </div>
                        <a href="#details" class="btn btn-wasomupfy btn-lg mt-2 smooth-scroll">
                            Ver Dashboard <i class="bi bi-arrow-down ms-2"></i>
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
                            <h3 class="h2 fw-bold mb-1">10</h3>
                            <p class="small mb-0 opacity-85">Artistas</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1"><?php echo $royalty; ?>%</h3>
                            <p class="small mb-0 opacity-85">Royalties</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1">5</h3>
                            <p class="small mb-0 opacity-85">Colaboradores</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <h3 class="h2 fw-bold mb-1">24/7</h3>
                            <p class="small mb-0 opacity-85">Suporte VIP</p>
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
                        <h2 class="display-5 fw-bold mb-3">O plano completo para gravadoras e coletivos</h2>
                        <p class="lead text-muted">Dashboard centralizado, relatórios consolidados e gestão
                            multi-artista</p>
                    </div>
                </div>

                <div class="row g-5">
                    <!-- Card do Plano -->
                    <div class="col-lg-8" data-cue="zoomIn">
                        <div class="pricing-card-main card shadow-lg hover-lift">
                            <div class="card-header border-0 pt-5 pb-4 px-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="h2 fw-bold mb-2">Plano Label</h3>
                                        <p class="text-muted mb-0">Assinatura anual para gravadoras</p>
                                    </div>
                                    <div class="text-end">
                                        <div class="price-display">
                                            <span class="price-amount display-3 fw-bold"><?php echo $price; ?></span>
                                            <span
                                                class="price-period h4 text-muted fw-normal"><?php echo $period; ?></span>
                                        </div>
                                        <div class="badge bg-success mt-2">Economize 86% vs Individual</div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-5">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">Royalty Split</span>
                                        <span class="fw-bold text-success"><?php echo $royalty; ?>% Label |
                                            <?php echo $fee; ?>% Wasom Upfy</span>
                                    </div>
                                    <div class="progress" style="height: 12px; border-radius: 6px;">
                                        <div class="progress-bar bg-wasom-gradient" role="progressbar"
                                            style="width: <?php echo $royalty; ?>%" aria-label="Royalties da label"
                                            aria-valuenow="<?php echo $royalty; ?>" aria-valuemin="0"
                                            aria-valuemax="100">
                                            <span class="visually-hidden">90% para a label</span>
                                        </div>
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 10%"
                                            aria-label="Royalties da plataforma" aria-valuenow="10" aria-valuemin="0"
                                            aria-valuemax="100">
                                            <span class="visually-hidden">10% para a plataforma</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dashboard Preview -->
                                <div class="dashboard-preview mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-2"><i class="bi bi-speedometer2 me-2 text-wasomupfy"></i>
                                                Dashboard Label
                                            </h5>
                                            <p class="small opacity-75 mb-0">Gerencie todos os seus artistas em um único
                                                painel</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="badge bg-wasom-gradient">10 Slots Disponíveis</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body pt-4 pb-5 px-5">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 class="h5 mb-3 text-dark"><i class="bi bi-building text-success me-2"></i>
                                            Recursos da Label:</h4>
                                        <ul class="list-unstyled mb-4">
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Até 10 artistas</strong> na conta</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Lançamentos ilimitados</strong> por artista</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>5 colaboradores</strong> por faixa</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Dashboard centralizado</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Relatórios consolidados</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Contratos digitais</strong> integrados</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="h5 mb-3 text-dark"><i
                                                class="bi bi-trophy-fill text-warning me-2"></i> Vantagens Exclusivas:
                                        </h4>
                                        <ul class="list-unstyled mb-4">
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Selo personalizado premium</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Lançamento em 24h</strong> (prioridade máxima)</span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Gerente de conta dedicado</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Suporte 24/7 VIP</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>Treinamento da equipe</strong></span>
                                            </li>
                                            <li class="d-flex align-items-start mb-3">
                                                <i class="bi bi-check-lg text-success mt-1 me-3"></i>
                                                <span><strong>API para integração</strong></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Comparação de Economia -->
                                <div class="economy-box mt-5">
                                    <h5 class="mb-4 text-center"><i
                                            class="bi bi-graph-up-arrow text-wasomupfy me-2"></i>
                                        Economia Empresarial:</h5>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="text-center p-3 bg- rounded-3">
                                                <h6 class="mb-2">10 Planos Artista</h6>
                                                <div class="h4 fw-bold mb-1">114.000 Kz</div>
                                                <p class="small text-muted mb-0">10 artistas separados</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div
                                                class="comparison-item text-center p-3 rounded-3 border-wasom border-2">
                                                <h6 class="mb-2 text-muted">Plano Label</h6>
                                                <div class="h4 fw-bold mb-1 text-wasomupfy">70.000 Kz</div>
                                                <p class="small text-muted mb-0">10 artistas + dashboard</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3 bg- rounded-3">
                                                <div class="h4 fw-bold mb-1 text-success">44.000 Kz</div>
                                                <p class="small mb-0">Economia anual</p>
                                                <div class="badge bg-success mt-1">38% mais barato</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-center mt-3 small text-muted mb-0">
                                        <i class="bi bi-calculator me-1"></i> Custo por artista: 7.000 Kz/ano (vs 11.400
                                        Kz individual)
                                    </p>
                                </div>

                                <!-- Call to Action -->
                                <div class="cta-box bg-wasom-light rounded-4 p-4 mt-5">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h4 class="h5 mb-2">Transforme sua gravadora!</h4>
                                            <p class="mb-0 text-muted">Dashboard centralizado, economia máxima e suporte
                                                dedicado</p>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <?php if ($canRegister): ?><a href="<?php echo APP_URL  ?>/register?plan=label"
                                                    class="btn btn-wasomupfy btn-lg px-5">
                                                    Solicitar Demonstração <i class="bi bi-arrow-right ms-2"></i>
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
                        <!-- Painel Label -->
                        <div class="card border-0 shadow-sm mb-4 hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3"><i class="bi bi-building-gear text-wasom me-2"></i> Painel Label
                                </h4>
                                <div class="label-panel mb-3">
                                    <i class="bi bi-building-gear fs-1 mb-2"></i>
                                    <h6 class="mb-1">SUA GRAVADORA</h6>
                                    <p class="small opacity-75 mb-0">Dashboard empresarial completo</p>
                                </div>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-speedometer2 text-wasomupfy mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">Dashboard central</span>
                                            <p class="small text-muted mb-0">Todos os artistas em um lugar</p>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-file-earmark-text text-wasomupfy mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">Relatórios consolidados</span>
                                            <p class="small text-muted mb-0">Análise financeira completa</p>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="bi bi-shield-check text-wasomupfy mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">Contratos digitais</span>
                                            <p class="small text-muted mb-0">Gestão de direitos automatizada</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Gestão de Artistas -->
                        <div class="card border-0 shadow-sm mb-4 hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3"><i class="bi bi-people-fill text-wasom me-2"></i> Gestão de Artistas
                                </h4>
                                <div class="artists-grid mb-3">
                                    <div class="artist-item">
                                        <i class="bi bi-person-circle fs-3 text-dark"></i>
                                        <p class="small mb-0 mt-1">Artista 1</p>
                                    </div>
                                    <div class="artist-item">
                                        <i class="bi bi-person-circle fs-3 text-dark"></i>
                                        <p class="small mb-0 mt-1">Artista 2</p>
                                    </div>
                                    <div class="artist-item">
                                        <i class="bi bi-person-circle fs-3 text-dark"></i>
                                        <p class="small mb-0 mt-1">Artista 3</p>
                                    </div>
                                    <div class="artist-item">
                                        <i class="bi bi-person-circle fs-3 text-dark"></i>
                                        <p class="small mb-0 mt-1">Artista 4</p>
                                    </div>
                                    <div class="artist-item">
                                        <i class="bi bi-person-circle fs-3 text-dark"></i>
                                        <p class="small mb-0 mt-1">Artista 5</p>
                                    </div>
                                    <div class="artist-item">
                                        <span class="badge bg-dark text-white">+5</span>
                                        <p class="small mb-0 mt-1">Slots</p>
                                    </div>
                                </div>
                                <p class="small text-muted mb-0">Gerencie até 10 artistas diferentes com perfis
                                    individuais e relatórios separados.</p>
                            </div>
                        </div>

                        <!-- Suporte Empresarial -->
                        <div class="card border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3"><i class="bi bi-headset text-wasom me-2"></i> Suporte Empresarial
                                </h4>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-person-vcard text-success mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">Gerente dedicado</span>
                                            <p class="small text-muted mb-0">Atendimento personalizado</p>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start mb-2">
                                        <i class="bi bi-calendar-week text-wasomupfy mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">Reuniões estratégicas</span>
                                            <p class="small text-muted mb-0">1x por mês</p>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <i class="bi bi-file-earmark-ppt text-warning mt-1 me-2"></i>
                                        <div>
                                            <span class="fw-medium">Treinamento da equipe</span>
                                            <p class="small text-muted mb-0">Sessões exclusivas</p>
                                        </div>
                                    </li>
                                </ul>
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
                        <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">FAQ
                            EMPRESARIAL</span>
                        <h2 class="display-5 fw-bold mb-4">Perguntas sobre o Plano Label</h2>
                        <p class="text-muted lead">Tire suas dúvidas sobre gestão multi-artista</p>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="accordion" id="labelFaqAccordion">
                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Como funciona a gestão de múltiplos artistas?
                                    </button>
                                </h3>
                                <div id="faq1" class="accordion-collapse collapse show"
                                    data-bs-parent="#labelFaqAccordion">
                                    <div class="accordion-body">
                                        Você tem um dashboard central onde gerencia até 10 artistas. Cada artista tem
                                        seu perfil independente, mas você vê todos os lançamentos, royalties e analytics
                                        em um único painel. Pode adicionar/remover artistas conforme necessário durante
                                        o ano.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Como são divididos os royalties entre a label e os artistas?
                                    </button>
                                </h3>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#labelFaqAccordion">
                                    <div class="accordion-body">
                                        A label recebe 90% dos royalties totais. Como a distribuição entre label e
                                        artistas é feita internamente, oferecemos um sistema de split automático onde
                                        você define percentuais diferentes para cada artista/colaborador. Também
                                        fornecemos contratos digitais para formalizar esses acordos.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Posso ter mais de 10 artistas?
                                    </button>
                                </h3>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#labelFaqAccordion">
                                    <div class="accordion-body">
                                        O plano inclui até 10 artistas. Se precisar de mais, entre em contato para um
                                        plano personalizado. Para cada artista adicional, oferecemos condições
                                        especiais. Também pode remover artistas inativos para adicionar novos.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 mb-3 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq4">
                                        Como funcionam os relatórios financeiros?
                                    </button>
                                </h3>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#labelFaqAccordion">
                                    <div class="accordion-body">
                                        Oferecemos relatórios consolidados mensais com: royalties por artista,
                                        plataforma e território; análise de crescimento; projeções; e dados para
                                        declaração fiscal. Todos os relatórios podem ser exportados em PDF e Excel.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item border-0 shadow-sm">
                                <h3 class="accordion-header">
                                    <button class="accordion-button bg-wasomupfy rounded-3 collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq5">
                                        Há suporte para contratos digitais?
                                    </button>
                                </h3>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#labelFaqAccordion">
                                    <div class="accordion-body">
                                        Sim! Incluímos um sistema de contratos digitais onde você pode criar, enviar e
                                        gerenciar contratos com artistas e colaboradores. Os contratos são assinados
                                        digitalmente, armazenados com segurança e integrados ao sistema de split de
                                        royalties.
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
                        <h2 class="display-5 fw-bold mb-4">Nossas Soluções para Artistas</h2>
                        <p class="text-muted lead">Encontre a opção perfeita para cada necessidade</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4" data-cue="zoomIn">
                        <div class="card border-0 h-100 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <h4 class="h5 mb-3">Plano Single</h4>
                                <div class="price-display mb-3">
                                    <span
                                        class="price-amount h3 fw-bold"><?php echo isset($plansBySlug['single']) ? number_format($plansBySlug['single']['price_plan'], 0, ',', '.') : '—'; ?></span>
                                    <span
                                        class="price-period text-muted"><?php echo isset($plansBySlug['single']) ? ($plansBySlug['single']['type_plan'] === 'subscription' ? 'Kz/ano' : 'Kz/single') : ''; ?></span>
                                </div>
                                <ul class="list-unstyled mb-4">
                                    <?php if (isset($plansBySlug['single']['features'])): foreach (array_slice($plansBySlug['single']['features'], 0, 3) as $f): ?>
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="bi bi-check-lg text-success mt-1 me-2"></i>
                                                <span><?php echo htmlspecialchars($f['feature_text']); ?></span>
                                            </li>
                                    <?php endforeach;
                                    endif; ?>
                                </ul>
                                <a href="single" class="btn btn-outline-primary w-100">Ver Plano Single</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4" data-cue="zoomIn" data-delay="100">
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

                    <div class="col-md-4" data-cue="zoomIn" data-delay="200">
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
                </div>
        </section>

        <!-- Final CTA -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h2 class="display-5 fw-bold mb-4">Pronto para escalar sua gravadora?</h2>
                        <p class="lead mb-5 opacity-90">Dashboard centralizado, economia de 38% e suporte empresarial
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <?php if ($canRegister): ?><a href="<?php echo APP_URL  ?>/register?plan=label"
                                    class="btn btn-wasomupfy btn-lg px-5 text-wasom fw-semibold">
                                    Solicitar Demonstração <i class="bi bi-arrow-right ms-2"></i>
                                </a><?php else: ?><span class="btn btn-secondary btn-lg px-5 disabled">Inscrições
                                    Fechadas</span><?php endif; ?>
                            <a href="../contact" class="btn btn-outline-secondary btn-lg px-5">
                                Falar com Vendas Empresariais
                            </a>
                        </div>
                        <p class="mt-4 small opacity-75">
                            <i class="bi bi-shield-check me-1 text-success"></i> Contrato empresarial • Faturamento •
                            Suporte
                            dedicado 24/7
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


    <!-- ══════════════════════════════════════════════════════
         MODAL FEEDBACK — igual em todas as páginas do site
         ══════════════════════════════════════════════════════ -->
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

                    <!-- Alerta de resultado (oculto por defeito) -->
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
                            <div class="form-text text-end">
                                <span id="feedbackCharCount">0</span>/2000
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" id="feedbackSubmitBtn" class="btn btn-wasomupfy btn-lg">
                                <span id="feedbackBtnText">
                                    Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>
                                </span>
                                <span id="feedbackBtnLoading" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    A enviar...
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Estado de sucesso (oculto até envio) -->
                    <div id="feedbackSuccess" class="d-none text-center py-3">
                        <div class="mb-3">
                            <i class="fa-solid fa-circle-check text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Feedback enviado!</h5>
                        <p class="text-muted mb-4">Obrigado pela tua opinião. A equipa da
                            <strong><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?></strong>
                            vai analisar com atenção. 🙏
                        </p>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Fechar
                        </button>
                    </div>
                </div>

                <div class="modal-footer border-0 justify-content-center pb-4">
                    <small class="text-muted">
                        <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> agradece a tua parceria!
                    </small>
                </div>

            </div>
        </div>
    </div>
    <!-- ══════════════════════════════════════════════════════
         FIM MODAL FEEDBACK
         ══════════════════════════════════════════════════════ -->


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
                link.setAttribute('href', link.getAttribute('href') + '?plan=label');
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

    <script>
        (function() {
            'use strict';

            // ── Determina o path base do ajax (funciona em qualquer subpasta) ──
            // ex: /plan/single → base = /plan/../ajax = /ajax
            // Usa o atributo data-base-path no body, ou deriva do pathname
            function getAjaxBase() {
                var base = document.body.dataset.basePath;
                if (base) return base.replace(/\/$/, '');
                // heurística: se estamos em /plan/* → ../ajax, senão /ajax
                var parts = window.location.pathname.split('/').filter(Boolean);
                // Remove o ficheiro (último segmento com extensão)
                if (parts.length && parts[parts.length - 1].indexOf('.') !== -1) parts.pop();
                // Se temos /plan/pagina → dois níveis, então ../../ajax etc.
                // Mais simples: usar path relativo hardcoded por profundidade
                return '../ajax'; // funciona para /plan/*, /page/*
            }

            var AJAX_URL = getAjaxBase() + '/feedback.php';

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

            if (!form) return; // modal não está na página

            // ── Contador de caracteres ────────────────────────────────────────
            if (textarea && charCount) {
                textarea.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                    charCount.classList.toggle('text-danger', this.value.length > 1800);
                });
            }

            // ── Reset do modal ao fechar ──────────────────────────────────────
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    resetFeedbackModal();
                });
            }

            function resetFeedbackModal() {
                form.reset();
                form.classList.remove('d-none');
                if (alertEl) {
                    alertEl.className = 'alert d-none';
                    alertEl.textContent = '';
                }
                if (successEl) {
                    successEl.classList.add('d-none');
                }
                if (charCount) {
                    charCount.textContent = '0';
                    charCount.classList.remove('text-danger');
                }
                setLoading(false);
            }

            function setLoading(loading) {
                submitBtn.disabled = loading;
                btnText.classList.toggle('d-none', loading);
                btnLoading.classList.toggle('d-none', !loading);
            }

            function showAlert(type, message) {
                alertEl.className = 'alert alert-' + type + ' mb-3';
                alertEl.textContent = message;
            }

            // ── Submit ────────────────────────────────────────────────────────
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                var name = document.getElementById('feedbackName').value.trim();
                var subject = document.getElementById('feedbackSubject').value;
                var message = textarea.value.trim();
                var csrf = csrfInput.value;

                // Validação mínima client-side
                if (name.length < 2) {
                    showAlert('warning', 'Por favor, insere o teu nome.');
                    document.getElementById('feedbackName').focus();
                    return;
                }
                if (message.length < 10) {
                    showAlert('warning', 'A mensagem deve ter pelo menos 10 caracteres.');
                    textarea.focus();
                    return;
                }

                setLoading(true);
                if (alertEl) alertEl.className = 'alert d-none';

                fetch(AJAX_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf: csrf,
                            name: name,
                            subject: subject,
                            message: message,
                            page: window.location.pathname,
                        })
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        setLoading(false);
                        if (data.success) {
                            // Actualiza CSRF para próxima submissão
                            if (data.new_csrf) csrfInput.value = data.new_csrf;
                            // Esconde form, mostra sucesso
                            form.classList.add('d-none');
                            successEl.classList.remove('d-none');
                        } else {
                            showAlert('danger', data.message || 'Ocorreu um erro. Tenta novamente.');
                        }
                    })
                    .catch(function() {
                        setLoading(false);
                        showAlert('danger', 'Erro de ligação. Verifica a tua internet e tenta novamente.');
                    });
            });

        })();
    </script>
</body>

</html>