<?php
// ══════════════════════════════════════════════
// WASOM UPFY — Todos os Planos
// Arquivo: plan/all-plans.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../include/site.php';

// Verificar estado da plataforma
checkPlatformStatus('all-plans');

// ── Dados da BD ───────────────────────────────
$plans    = getPlans();   // _plans + _plan_features
$platform = getPlatform(); // royalty_percentage, stores_count, allow_register
$cfg      = getSiteConfig(); // site_name, support_email, redes sociais...

// Indexar planos por slug para acesso rápido no nav e na tabela
$plansBySlug = [];
foreach ($plans as $p) {
    $plansBySlug[$p['slug_plan']] = $p;
}

// FAQs da categoria 'Distribuição' + 'Geral' — mais relevantes para página de planos
$faqs = getFaqs(); // todos, filtramos abaixo

// Ícone, descrição curta e classe de botão por slug
$planMeta = [
    'single' => [
        'icon'       => 'fa-music',
        'subtitle'   => 'Perfeito para o teu próximo hit',
        'btn_class'  => 'btn-wasomupfy',
        'featured'   => false,
    ],
    'album'  => [
        'icon'       => 'fa-compact-disc',
        'subtitle'   => 'Ideal para projetos completos',
        'btn_class'  => 'btn-outline-primary',
        'featured'   => false,
    ],
    'artist' => [
        'icon'       => 'fa-microphone-lines',
        'subtitle'   => 'Para artistas em crescimento',
        'btn_class'  => 'btn-wasomupfy',
        'featured'   => true,  // destaque visual
    ],
    'label'  => [
        'icon'       => 'fa-tags',
        'subtitle'   => 'Para gravadoras e colectivos',
        'btn_class'  => 'btn-outline-primary',
        'featured'   => false,
    ],
];

// Calcular poupança do pacote anual (single e album têm price_annual)
function calcSaving(array $plan): int
{
    if (!$plan['price_annual'] || !$plan['annual_qty']) return 0;
    $full    = $plan['price_plan'] * $plan['annual_qty'];
    $annual  = $plan['price_annual'];
    return (int)round((($full - $annual) / $full) * 100);
}

// Formatar período de preço conforme tipo de plano
function pricePeriod(array $plan): string
{
    if ($plan['type_plan'] === 'subscription') {
        return 'Kz/ano';
    }
    return 'Kz/' . $plan['slug_plan'];
}

// Rastrear visita
trackVisitor('/plan/all-plans', 'Todos os Planos — Wasom Upfy');

// Registos (inscrição disponível?)
$canRegister = (bool)$platform['allow_register'];

// Royalty da plataforma (pode mudar via admin)
$royalty = (int)$platform['royalty_percentage'];

// Nº de lojas
$storesCount = (int)$platform['stores_count'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>, Planos, Distribuição Musical, Royalties, Single, Álbum, Artista, Label" />
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
        content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> - Nossos Planos" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_tagline', 'Distribua sua música para o mundo')); ?>. <?php echo $royalty; ?>% dos royalties são seus. Distribua em <?php echo $storesCount; ?>+ lojas digitais." />
    <meta property="og:url" content="https://wasomupfy.com/plan/all-plans" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />
    <meta property="og:image" content="https://wasomupfy.com/imgs/og_wasomupfy.jpeg" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="Planos <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />

    <title><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> | Todos os Planos</title>

    <!-- Preloader JS -->
    <script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                document.querySelector("body").classList.add("loaded")
            }, 200)
        })
    </script>

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
            "url": "https://www.wasomupfy.com",
            "logo": "https://www.wasomupfy.com/logo.png",
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
                            "url"         => "https://wasomupfy.com/plan/" . $p['slug_plan'],
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
    <!-- Preloader -->
    <div class="preloader">
        <img src="../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="A carregar...">
    </div>

    <!-- Navbar -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="../home" title="Home">
                    <img src="../assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Home" href="../home">
                            <img width="65" src="../assets/img/brand/wasomupfy_brand.png"
                                alt="Logo <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item"><a class="nav-link" href="../home" title="Início">Início</a></li>
                            <li class="nav-item"><a class="nav-link" href="../about" title="Sobre">Sobre</a></li>
                            <li class="nav-item">
                                <a class="nav-link" href="../blog/" title="Blogue" target="_blank"
                                    rel="external">Blogue</a>
                            </li>

                            <!-- Dropdown Planos — preços dinâmicos da BD -->
                            <li class="nav-item dropdown active">
                                <a title="Planos" class="nav-link active" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Planos <i data-feather="chevron-down"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-md" aria-labelledby="navbarDropdown">
                                    <?php
                                    $navIcons = [
                                        'single' => 'fa-music',
                                        'album'  => 'fa-compact-disc',
                                        'artist' => 'fa-microphone-lines',
                                        'label'  => 'fa-tags',
                                    ];
                                    foreach ($plans as $p):
                                        $slug  = $p['slug_plan'];
                                        $icon  = $navIcons[$slug] ?? 'fa-music';
                                        $price = formatAOA($p['price_plan']);
                                        $period = $p['type_plan'] === 'subscription' ? '/ano' : '';
                                        $active = ($slug === 'all') ? 'active' : '';
                                    ?>
                                        <a title="<?php echo htmlspecialchars($p['name_plan']); ?>"
                                            class="dropdown-item mb-3 text-body" href="<?php echo $slug; ?>">
                                            <div class="d-flex align-items-center">
                                                <i class="fa-solid <?php echo $icon; ?> text-wasomupfy fs-3"
                                                    style="width:35px"></i>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                                    <p class="mb-0 fs-6">Plano
                                                        <?php echo htmlspecialchars($p['name_plan']); ?> —
                                                        <?php echo $price . $period; ?></p>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                    <a title="Todos os planos" class="dropdown-item mb-3 text-body active"
                                        href="all-plans">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-layer-group text-wasomupfy fs-3"
                                                style="width:35px"></i>
                                            <div class="ms-3 lh-1">
                                                <h5 class="mb-1">Todos os planos</h5>
                                                <p class="mb-0 fs-6">Compare todos os nossos planos</p>
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
                                            <div>
                                                <div class="dropdown-header">Blog</div>
                                                <a title="Novidades" class="dropdown-item" href="../blog/">Novidades</a>
                                                <a title="Passatempo" class="dropdown-item"
                                                    href="../blog/">Passatempo</a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a title="A nossa marca" class="dropdown-item"
                                                    href="../about?#nossamarca">A nossa marca</a>
                                                <a title="Parcerias" class="dropdown-item"
                                                    href="../partnership">Parcerias</a>
                                                <a title="Quem somos" class="dropdown-item"
                                                    href="../about#nossa-historia">Quem somos</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Serviços</div>
                                                <a title="Distribuição de música" class="dropdown-item"
                                                    href="../page/services/music-distribution">Distribuição de
                                                    música</a>
                                                <a title="Promoção de música" class="dropdown-item"
                                                    href="../page/services/music-promotion">Promoção de música <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a title="Serviços Personalizados" class="dropdown-item"
                                                    href="../page/services/customized-services">Serviços personalizados
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052"
                                                    target="_blank" rel="external noopener noreferrer">Atendimento</a>
                                                <a title="Contacto-nos" class="dropdown-item"
                                                    href="../contact">Contacta-nos</a>
                                                <a title="Canal WhatsApp" class="dropdown-item"
                                                    href="https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04"
                                                    target="_blank" rel="external noopener noreferrer">Canal
                                                    WhatsApp</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Sugestões</div>
                                                <a title="Ajuda" class="dropdown-item" href="../page/support/help">Ajuda
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Feedback" class="dropdown-item" href="#"
                                                    data-bs-toggle="modal" data-bs-target="#modalFeedback">Feedback</a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Ajuda</div>
                                                <a title="Tutorial" class="dropdown-item"
                                                    href="../page/support/tutorial">Tutorial <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a title="Suporte técnico" class="dropdown-item"
                                                    href="../page/support/support">Suporte técnico</a>
                                                <a title="Perguntas frequentes" class="dropdown-item"
                                                    href="../page/support/faq">Perguntas frequentes</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="../resources" title="Recursos">Recursos</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a title="Contactar" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Contactar <i data-feather="chevron-down"></i>
                                </a>
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
                            <a title="Entrar" href="/wasomupfy/login" class="btn btn-secondary mx-2">
                                Entrar <i data-feather="log-in"></i>
                            </a>
                            <?php if ($canRegister): ?>
                                <a title="Inscreva-se" href="/wasomupfy/register" class="btn btn-wasomupfy">Inscreva-se</a>
                            <?php else: ?>
                                <span class="btn btn-secondary disabled"
                                    title="Inscrições temporariamente fechadas">Inscrições fechadas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Navbar fim -->

    <main>
        <!-- ── Hero ──────────────────────────────────── -->
        <section class="all-plans-hero jarallax position-relative overflow-hidden py-7" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../assets/img/theme/all_plans.png"
                alt="Planos <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" loading="lazy">
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-8 col-md-10 text-center" data-cue="fadeIn">
                        <span class="badge bg-wasomupfy text-white-stable fw-semibold px-4 py-2 mb-3">Escolha o Seu
                            Plano</span>
                        <h1 class="display-4 mb-4 text-white-stable fw-bold">Planos que Impulsionam a Tua Música</h1>
                        <p class="lead text-white-stable mb-4 opacity-90">
                            Distribua a sua música globalmente com transparência total.
                            <strong><?php echo $royalty; ?>%</strong> dos royalties são teus!
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                            <span class="badge bg-secondary text-black px-3 py-2">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> ISRC/UPC Grátis
                            </span>
                            <span class="badge bg-secondary text-black px-3 py-2">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> <?php echo $royalty; ?>%
                                Royalties
                            </span>
                            <span class="badge bg-secondary text-black px-3 py-2">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> <?php echo $storesCount; ?>+
                                Lojas Digitais
                            </span>
                            <span class="badge bg-secondary text-black px-3 py-2">
                                <i class="bi bi-check-circle-fill text-success me-1"></i> Suporte Local
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Cards de Planos ────────────────────────── -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-8 text-center">
                        <h2 class="display-6 fw-bold mb-3">Escolha o plano perfeito para si</h2>
                        <p class="text-muted lead">Temos opções para todos os tipos de artistas, desde iniciantes até
                            gravadoras</p>

                        <!-- Toggle por lançamento / anual -->
                        <div class="d-flex justify-content-center align-items-center mb-5">
                            <span class="me-3 fw-medium">Por lançamento</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="billingToggle"
                                    checked>
                                <label class="form-check-label ms-2 fw-medium" for="billingToggle">Pacote Anual
                                    (Economize)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 justify-content-center">
                    <?php
                    $delays = [0, 100, 200, 300];
                    foreach ($plans as $i => $plan):
                        $slug    = $plan['slug_plan'];
                        $meta    = $planMeta[$slug] ?? ['icon' => 'fa-music', 'subtitle' => '', 'btn_class' => 'btn-wasomupfy', 'featured' => false];
                        $saving  = calcSaving($plan);
                        $delay   = $delays[$i] ?? ($i * 100);
                        $isFeatured = $meta['featured'] || $plan['is_featured'];

                        // Preço formatado sem "Kz" para o display (JS precisa do número)
                        $priceNum    = number_format($plan['price_plan'], 0, ',', '.');
                        $annualNum   = $plan['price_annual'] ? number_format($plan['price_annual'], 0, ',', '.') : null;

                        // Período do preço unitário
                        $unitSuffix  = $plan['type_plan'] === 'subscription'
                            ? 'Kz/ano'
                            : 'Kz/' . $slug;
                        $annualQty   = $plan['annual_qty'];

                        // Período do preço anual
                        $annualSuffix = $annualQty
                            ? 'Kz/' . $annualQty . ' ' . ($slug === 'single' ? 'singles' : 'álbuns')
                            : 'Kz/pacote';

                        // Card border — destaque para o plano featured
                        $cardClass = $isFeatured
                            ? 'pricing-card card border-wasom border-3 h-100 shadow-lg hover-lift position-relative'
                            : 'pricing-card card border-0 h-100 shadow-lg hover-lift';
                    ?>
                        <div class="col-xl-3 col-lg-6" data-cue="zoomIn"
                            <?php echo $delay ? ' data-delay="' . $delay . '"' : ''; ?>>
                            <div class="<?php echo $cardClass; ?>">

                                <?php if ($isFeatured): ?>
                                    <div class="position-absolute top-0 start-50 translate-middle">
                                        <span class="badge bg-wasomupfy text-white fw-semibold px-4 py-2">Melhor
                                            Custo-Benefício</span>
                                    </div>
                                <?php endif; ?>

                                <div
                                    class="card-header border-0 <?php echo $isFeatured ? 'pt-6' : 'pt-5'; ?> pb-4 text-center">
                                    <?php if ($plan['badge_text'] && !$isFeatured): ?>
                                        <span
                                            class="badge bg-wasom-light text-wasomupfy fw-semibold px-3 py-2 mb-3"><?php echo htmlspecialchars($plan['badge_text']); ?></span>
                                    <?php endif; ?>

                                    <h3 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($plan['name_plan']); ?></h3>

                                    <div class="price-display">
                                        <!-- Preço unitário (por lançamento / ano) -->
                                        <div class="monthly-price<?php echo $annualNum ? '' : ' always-shown'; ?>">
                                            <span class="price-amount display-4 fw-bold"><?php echo $priceNum; ?></span>
                                            <span
                                                class="price-period h5 text-muted fw-normal"><?php echo $unitSuffix; ?></span>
                                        </div>

                                        <!-- Preço do pacote anual (só single e album) -->
                                        <?php if ($annualNum): ?>
                                            <div class="annual-price d-none">
                                                <span class="price-amount display-4 fw-bold"><?php echo $annualNum; ?></span>
                                                <span
                                                    class="price-period h5 text-muted fw-normal"><?php echo $annualSuffix; ?></span>
                                                <?php if ($saving > 0): ?>
                                                    <div><span class="badge bg-success mt-2">Economize
                                                            <?php echo $saving; ?>%</span></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($meta['subtitle']); ?></p>
                                </div>

                                <div class="card-body pt-4 pb-5 px-4">
                                    <!-- Features da BD -->
                                    <ul class="list-unstyled mb-4">
                                        <?php foreach ($plan['features'] as $feat): ?>
                                            <li class="d-flex align-items-start mb-3">
                                                <?php if ($feat['is_included']): ?>
                                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3 flex-shrink-0"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill text-danger mt-1 me-3 flex-shrink-0"></i>
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($feat['feature_text']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <div class="d-grid">
                                        <?php if ($canRegister): ?>
                                            <a href="/wasomupfy/register?plan=<?php echo urlencode($slug); ?>"
                                                class="btn <?php echo $meta['btn_class']; ?> btn-lg">
                                                Escolher <?php echo htmlspecialchars($plan['name_plan']); ?>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-lg disabled">Inscrições Fechadas</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ── Tabela de Comparação ───────────────────── -->
        <section class="py-5" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-10 text-center">
                        <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">Comparação</span>
                        <h2 class="display-6 fw-bold mb-4">Compare Todos os Planos</h2>
                        <p class="text-muted lead mb-5">Veja detalhadamente o que cada plano oferece</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="border-0" style="width:30%">Recursos</th>
                                <?php foreach ($plans as $p):
                                    $isFeat = ($planMeta[$p['slug_plan']]['featured'] ?? false) || $p['is_featured'];
                                    $price  = formatAOA($p['price_plan']);
                                    $period = $p['type_plan'] === 'subscription' ? '/ano' : '/' . $p['slug_plan'];
                                ?>
                                    <th class="text-center border-0 py-4<?php echo $isFeat ? ' bg-wasom-light' : ''; ?>">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                        <div class="text-wasom fw-bold"><?php echo $price . $period; ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Linhas da tabela construídas a partir dos dados da BD
                            $tableRows = [
                                ['label' => 'Royalties', 'key' => 'royalty_rate', 'format' => fn($v) => (int)$v . '%'],
                                ['label' => 'Faixas por lançamento', 'key' => 'max_tracks_per_release', 'format' => fn($v) => $v ? $v . ' faixas' : 'Ilimitadas'],
                                ['label' => 'Artistas na conta', 'key' => 'max_artists', 'format' => fn($v) => $v . ' artista' . ($v > 1 ? 's' : '')],
                                ['label' => 'Lançamentos', 'key' => 'max_releases', 'format' => fn($v) => $v ? $v . ' por ano' : 'Ilimitados'],
                            ];

                            foreach ($tableRows as $row):
                            ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo $row['label']; ?></td>
                                    <?php foreach ($plans as $p):
                                        $isFeat = ($planMeta[$p['slug_plan']]['featured'] ?? false) || $p['is_featured'];
                                        $val    = $row['format']($p[$row['key']] ?? null);
                                    ?>
                                        <td class="text-center<?php echo $isFeat ? ' bg-wasom-light' : ''; ?>">
                                            <?php echo $val; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Linhas estáticas (são iguais em todos os planos) -->
                            <?php
                            $staticRows = [
                                'Análise de Dados',
                                'ISRC e UPC Automáticos',
                                'Smartlink e Pre-salve',
                                'Lançamento em 72h',
                                'Suporte Local (WhatsApp + E-mail)',
                                'Agendar Lançamentos',
                            ];
                            foreach ($staticRows as $label):
                            ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo $label; ?></td>
                                    <?php foreach ($plans as $p):
                                        $isFeat = ($planMeta[$p['slug_plan']]['featured'] ?? false) || $p['is_featured'];
                                    ?>
                                        <td class="text-center<?php echo $isFeat ? ' bg-wasom-light' : ''; ?>">
                                            <i class="bi bi-check-lg text-success"></i>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Personalizar nome de selo — só álbum, artista, label -->
                            <tr>
                                <td class="fw-semibold">Nome de Selo Personalizado</td>
                                <?php foreach ($plans as $p):
                                    $isFeat  = ($planMeta[$p['slug_plan']]['featured'] ?? false) || $p['is_featured'];
                                    $hasSelo = in_array($p['slug_plan'], ['album', 'artist', 'label']);
                                ?>
                                    <td class="text-center<?php echo $isFeat ? ' bg-wasom-light' : ''; ?>">
                                        <?php if ($hasSelo): ?>
                                            <i class="bi bi-check-lg text-success"></i>
                                        <?php else: ?>
                                            <i class="bi bi-dash text-muted"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Botões CTA na tabela -->
                            <tr>
                                <td></td>
                                <?php foreach ($plans as $p):
                                    $isFeat  = ($planMeta[$p['slug_plan']]['featured'] ?? false) || $p['is_featured'];
                                    $btnCls  = $planMeta[$p['slug_plan']]['btn_class'] ?? 'btn-wasomupfy';
                                ?>
                                    <td class="text-center pt-4<?php echo $isFeat ? ' bg-wasom-light' : ''; ?>">
                                        <?php if ($canRegister): ?>
                                            <a href="/wasomupfy/register?plan=<?php echo urlencode($p['slug_plan']); ?>"
                                                class="btn <?php echo $btnCls; ?> btn-sm w-100 py-2">Escolher</a>
                                        <?php else: ?>
                                            <span class="btn btn-secondary btn-sm w-100 disabled">Fechado</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ── FAQ — da BD ───────────────────────────── -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-8 text-center">
                        <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">FAQ</span>
                        <h2 class="display-6 fw-bold mb-4">Perguntas Frequentes</h2>
                        <p class="text-muted lead">Tire as suas dúvidas sobre os nossos planos e sobre a plataforma</p>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <?php if (empty($faqs)): ?>
                            <p class="text-center text-muted">Nenhuma pergunta disponível de momento.</p>
                        <?php else: ?>
                            <div class="accordion" id="faqAccordion">
                                <?php foreach ($faqs as $fi => $faq): ?>
                                    <div class="accordion-item border-0 mb-3 shadow-sm">
                                        <h3 class="accordion-header">
                                            <button
                                                class="accordion-button <?php echo $fi === 0 ? 'bg-wasomupfy' : 'bg-wasomupfy collapsed'; ?> rounded-3"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#faqItem<?php echo $faq['id_faq']; ?>"
                                                aria-expanded="<?php echo $fi === 0 ? 'true' : 'false'; ?>">
                                                <?php echo htmlspecialchars($faq['question']); ?>
                                            </button>
                                        </h3>
                                        <div id="faqItem<?php echo $faq['id_faq']; ?>"
                                            class="accordion-collapse collapse<?php echo $fi === 0 ? ' show' : ''; ?>"
                                            data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="../page/support/faq" class="btn btn-outline-primary">
                                Ver todas as perguntas <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA Final ──────────────────────────────── -->
        <section class="py-5 bg-light-100" data-cue="fadeIn">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h2 class="display-6 fw-bold mb-4">Pronto para distribuir a tua música?</h2>
                        <p class="lead mb-5 opacity-90 text-muted">
                            Junta-te a artistas que já alcançaram o mundo com a
                            <?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <?php if ($canRegister): ?>
                                <a href="/wasomupfy/register" class="btn btn-secondary btn-lg px-5 text-wasom fw-semibold">
                                    Começar Agora <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            <?php endif; ?>
                            <a href="../contact" class="btn btn-outline-primary btn-lg px-5">Fazer uma Pergunta</a>
                        </div>
                        <p class="mt-4 small opacity-75 text-muted">
                            <i class="bi bi-shield-check me-1 text-success"></i>
                            Pagamento 100% seguro &bull; Suporte em Português &bull; <?php echo $royalty; ?>% dos
                            royalties são teus
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Divider -->
    <div class="divider-fade"></div>

    <!-- ── Footer ─────────────────────────────────── -->
    <footer class="bg-light-100 pt-7" role="contentinfo" aria-label="Rodapé do site">
        <div class="container">
            <!-- Newsletter -->
            <div class="row align-items-center mb-7 border-bottom border-white-10 pb-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h3 class="fw-bold mb-1">Junte-se a Artistas em todo o Mundo</h3>
                    <p class="lead text-muted mb-0">Receba dicas de marketing, novidades da indústria e ofertas
                        exclusivas.</p>
                </div>
                <div class="col-lg-6">
                    <form action="#" class="row g-2">
                        <div class="col-sm-8">
                            <input type="email" class="form-control border-0 text-muted py-3" required
                                placeholder="O teu melhor e-mail" />
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-wasomupfy w-100 py-3 fw-bold">Inscrever</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Links do Footer -->
            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">
                    <!-- Logo + Redes -->
                    <div class="col-lg-3 col-12">
                        <a href="../home" class="d-inline-block mb-4 navbar-brand">
                            <img src="../assets/img/brand/wasomupfy_brand.png"
                                alt="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?>" width="65"
                                class="img-logo" height="60" />
                        </a>
                        <p class="lead text-muted small mb-4">
                            <?php echo htmlspecialchars(cfg('site_tagline', 'Distribua sua música para o mundo')); ?>.
                            Distribuição digital, marketing e gestão de carreira num só lugar.</p>
                        <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
                            <?php if (cfg('instagram_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" aria-label="Instagram"
                                    role="listitem"><i class="fa-brands fa-instagram"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('facebook_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" aria-label="Facebook"
                                    role="listitem"><i class="fa-brands fa-facebook-f"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('youtube_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" aria-label="YouTube"
                                    role="listitem"><i class="fa-brands fa-youtube"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('tiktok_url')): ?>
                                <a href="<?php echo htmlspecialchars(cfg('tiktok_url')); ?>" target="_blank"
                                    rel="external noopener noreferrer"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" aria-label="TikTok"
                                    role="listitem"><i class="fa-brands fa-tiktok"></i></a>
                            <?php endif; ?>
                            <?php if (cfg('whatsapp_number')): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', cfg('whatsapp_number')); ?>"
                                    target="_blank" rel="external noopener noreferrer"
                                    class="btn btn-wasomupfy btn-social rounded-circle p-2" aria-label="WhatsApp"
                                    role="listitem"><i class="fa-brands fa-whatsapp"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Empresa -->
                    <div class="col-lg-3 col-6">
                        <h3 class="fw-bold mb-3">Empresa</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="../about"
                                    class="text-reset text-decoration-none hover-white">Sobre</a></li>
                            <li class="mb-2"><a href="../about#nossamarca"
                                    class="text-reset text-decoration-none hover-white">A nossa marca</a></li>
                            <li class="mb-2"><a href="all-plans"
                                    class="text-reset text-decoration-none hover-white">Planos</a></li>
                            <li class="mb-2"><a href="../page/services/customized-services"
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
                            <li class="mb-2"><a href="../page/support/help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="../contact"
                                    class="text-reset text-decoration-none hover-white">Contacta-nos</a></li>
                            <?php if (cfg('whatsapp_number')): ?>
                                <li class="mb-2"><a
                                        href="https://wa.me/<?php echo preg_replace('/\D/', '', cfg('whatsapp_number')); ?>"
                                        class="text-reset text-decoration-none hover-white">WhatsApp</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Contacto -->
                    <div class="col-lg-3 col-12">
                        <h3 class="fw-bold mb-3">Contacto</h3>
                        <ul class="list-unstyled mb-0 text-muted small">
                            <li class="mb-3"><span>Angola — Luanda</span></li>
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
                            <li><span>Seg — Sex: 08h às 17h</span></li>
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
                        <li class="list-inline-item"><a href="../page/politicies/privacy"
                                class="text-reset text-decoration-none">Política de Privacidade</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="../page/politicies/terms"
                                class="text-reset text-decoration-none">Termos de Uso</a></li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item"><a href="../page/politicies/cookies"
                                class="text-reset text-decoration-none">Cookies</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer fim -->

    <!-- Scroll top -->
    <div class="btn-scroll-top">
        <svg class="progress-square svg-content" width="100%" height="100%" viewBox="0 0 40 40">
            <path
                d="M8 1H32C35.866 1 39 4.13401 39 8V32C39 35.866 35.866 39 32 39H8C4.13401 39 1 35.866 1 32V8C1 4.13401 4.13401 1 8 1Z" />
        </svg>
    </div>

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
                    <p class="text-muted">Como tem sido a tua experiência com a
                        <strong><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?></strong>? As tuas
                        sugestões ajudam-nos a evoluir.
                    </p>
                    <form id="formFeedback">
                        <input type="hidden" name="csrf" value="<?php echo getSiteCsrf(); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">O teu Nome</label>
                            <input type="text" class="form-control" placeholder="Ex: André Wasom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Assunto</label>
                            <select class="form-select">
                                <option>Sugestão de melhoria</option>
                                <option>Elogio</option>
                                <option>Relatar um problema</option>
                                <option>Outros</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">A tua Mensagem</label>
                            <textarea class="form-control" rows="4" placeholder="Conta-nos em detalhe..."
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
                    <small class="text-muted"><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> agradece a
                        tua parceria!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── JS ────────────────────────────────────── -->
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
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>

    <script>
        feather.replace({
            width: "1em",
            height: "1em"
        });

        // Dados dos planos vindos do PHP (para o toggle de preços)
        const plansData = <?php
                            $jsPlans = [];
                            foreach ($plans as $p) {
                                $jsPlans[$p['slug_plan']] = [
                                    'unit'       => number_format($p['price_plan'], 0, ',', '.'),
                                    'annual'     => $p['price_annual'] ? number_format($p['price_annual'], 0, ',', '.') : null,
                                    'annual_qty' => $p['annual_qty'],
                                    'type'       => $p['type_plan'],
                                    'saving'     => calcSaving($p),
                                ];
                            }
                            echo json_encode($jsPlans, JSON_UNESCAPED_UNICODE);
                            ?>;

        document.addEventListener('DOMContentLoaded', function() {

            // ── Toggle de período ─────────────────────
            const billingToggle = document.getElementById('billingToggle');
            const monthlyPrices = document.querySelectorAll('.monthly-price');
            const annualPrices = document.querySelectorAll('.annual-price');

            if (billingToggle) {
                billingToggle.addEventListener('change', function() {
                    const isAnnual = this.checked;
                    monthlyPrices.forEach(el => el.classList.toggle('d-none', isAnnual));
                    annualPrices.forEach(el => el.classList.toggle('d-none', !isAnnual));
                });
            }

            // ── Animações scroll ──────────────────────
            const animateOnScroll = () => {
                document.querySelectorAll('[data-cue]').forEach(el => {
                    if (el.getBoundingClientRect().top < window.innerHeight - 150) {
                        el.classList.add('animated');
                    }
                });
            };
            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll();

            // ── Smooth scroll âncoras ─────────────────
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href === '#' || href.startsWith('#faq')) return;
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                });
            });

        });

        // ── Google Tag Manager ────────────────────────
        ! function(e, t, a, n, g) {
            e[n] = e[n] || [], e[n].push({
                "gtm.start": (new Date).getTime(),
                event: "gtm.js"
            });
            var m = t.getElementsByTagName(a)[0],
                r = t.createElement(a);
            r.async = !0, r.src = "https://www.googletagmanager.com/gtm.js?id=GTM-MF4DZVH", m.parentNode.insertBefore(r, m)
        }(window, document, "script", "dataLayer");
    </script>
</body>

</html>