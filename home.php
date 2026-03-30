<?php
// ══════════════════════════════════════════════
// WASOM UPFY — Home (Página Principal)
// Arquivo: home.php  (raiz do site)
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/site.php';

checkPlatformStatus('home');
trackVisitor('/home', 'Home — Wasom Upfy');

$plans       = getPlans();
$plansBySlug = [];
foreach ($plans as $p) {
    $plansBySlug[$p['slug_plan']] = $p;
}

// FAQs da categoria 'Distribuição' + 'Geral' — mais relevantes para página de planos
$faqs = getFaqs(); // todos, filtramos abaixo

$platform     = getPlatform();
$canRegister  = (bool)$platform['allow_register'];
$royalty      = (int)$platform['royalty_percentage'];
$stores       = (int)$platform['stores_count'];
$siteName     = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl      = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum     = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));
$whatsChannel = cfg('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04');
$csrf_home    = getSiteCsrf(); // gerado aqui no topo, antes de qualquer HTML

// ─── Configuração editorial dos cartões de plano ───────────────────────────
$plansGrid = [
    'single' => [
        'badge'     => 'Iniciante',
        'badgeCls'  => 'bg-light text-dark',
        'cardCls'   => 'border-0 shadow-sm',
        'btnCls'    => 'btn-outline-primary',
        'btnIcon'   => 'bi-cart-plus',
        'btnLabel'  => 'Escolher Plano',
        'btnNote'   => 'Perfeito para quem está começando',
        'popular'   => false,
        'delay'     => '',
    ],
    'album' => [
        'badge'     => 'Artista',
        'badgeCls'  => 'bg-wasomupfy bg-opacity-10 text-wasomupfy',
        'cardCls'   => 'border-2 border-wasomupfy shadow-lg position-relative',
        'btnCls'    => 'btn-wasomupfy py-3',
        'btnIcon'   => 'bi-lightning-charge-fill',
        'btnLabel'  => 'Começar Agora',
        'btnNote'   => '<i class="bi bi-shield-check text-success me-1"></i>Ideal para artistas independentes',
        'popular'   => true,
        'delay'     => 'data-delay="100"',
    ],
    'artist' => [
        'badge'     => 'Profissional',
        'badgeCls'  => 'bg-primary bg-opacity-10 text-primary',
        'cardCls'   => 'border-0 shadow-sm',
        'btnCls'    => 'btn-outline-primary',
        'btnIcon'   => 'bi-people-fill',
        'btnLabel'  => 'Escolher Plano',
        'btnNote'   => 'Perfeito para produtores e managers',
        'popular'   => false,
        'delay'     => 'data-delay="200"',
    ],
    'label' => [
        'badge'     => 'Empresarial',
        'badgeCls'  => 'bg-warning bg-opacity-10 text-warning',
        'cardCls'   => 'border-0 shadow-sm',
        'btnCls'    => 'btn-outline-warning',
        'btnIcon'   => 'bi-handshake-fill',
        'btnLabel'  => 'Tornar-se Parceiro',
        'btnNote'   => 'Para selos e gravadoras',
        'popular'   => false,
        'delay'     => 'data-delay="300"',
    ],
];

// ─── Features editoriais por plano ─────────────────────────────────────────
$planFeatures = [
    'single' => [
        [true,  'Upload de uma faixa'],
        [true,  '1 Artista'],
        [true,  'Colaboradores Ilimitados'],
        [true,  'Análise de dados avançados'],
        [true,  'ISRC e UPC grátis'],
        [true,  'Smartlink e pre-salve'],
        [true,  'Lançamento mais rápido possível a 72h'],
        [true,  'Suporte local (WhatsApp + E-mail)'],
        [true,  'Agendar lançamentos'],
        [false, 'Personalizar nome de selo'],
    ],
    'album' => [
        [true, 'Upload de 15 faixas'],
        [true, '1 Artista'],
        [true, 'Colaboradores Ilimitados'],
        [true, 'Análise de dados avançados'],
        [true, 'ISRC e UPC grátis'],
        [true, 'Smartlink e pre-salve'],
        [true, 'Lançamento mais rápido possível a 72h'],
        [true, 'Suporte local (WhatsApp + E-mail)'],
        [true, 'Agendar lançamentos'],
        [true, 'Personalizar nome de selo'],
    ],
    'artist' => [
        [true, 'Upload de faixas ilimitadas'],
        [true, '1 Artista'],
        [true, 'Colaboradores Ilimitados'],
        [true, 'Análise de dados avançados'],
        [true, 'ISRC e UPC grátis'],
        [true, 'Smartlink e pre-salve'],
        [true, 'Lançamento mais rápido possível a 72h'],
        [true, 'Suporte local (WhatsApp + E-mail)'],
        [true, 'Agendar lançamentos'],
        [true, 'Personalizar nome de selo'],
    ],
    'label' => [
        [true, 'Upload de faixas ilimitadas'],
        [true, '10 Artistas'],
        [true, 'Colaboradores Ilimitados'],
        [true, 'Análise de dados avançados'],
        [true, 'ISRC e UPC grátis'],
        [true, 'Smartlink e pre-salve'],
        [true, 'Lançamento mais rápido possível a 72h'],
        [true, 'Suporte local (WhatsApp + E-mail)'],
        [true, 'Agendar lançamentos'],
        [true, 'Sem lançamento obrigatório'],
    ],
];
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
    <meta name="site-csrf" content="<?php echo htmlspecialchars($csrf_home); ?>" />
    <meta name="visitor-page-url" content="/home" />

    <!-- SEO dinâmico -->
    <title><?php echo $siteName; ?> | Home</title>
    <meta name="description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'Distribua sua música em plataformas como Spotify, Apple Music e mais de ' . $stores . ' lojas. Mantenha ' . $royalty . '% dos seus royalties com a ' . $siteName . '.')); ?>" />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_keywords', 'Wasom Upfy, distribuição de música, ganhar dinheiro com música, streaming Angola')); ?>" />

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Alcance novos fãs e ganhe dinheiro com sua música." />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'A plataforma de distribuição musical mais fácil e completa do mercado.')); ?>" />
    <meta property="og:url" content="<?php echo htmlspecialchars($siteUrl); ?>/" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/imgs/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />

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

    <!-- Schema.org JSON-LD dinâmico -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?php echo addslashes($siteName); ?>",
            "url": "<?php echo addslashes($siteUrl); ?>",
            "logo": "<?php echo addslashes($siteUrl); ?>/assets/img/brand/wasomupfy_brand.png",
            "sameAs": [
                <?php
                $sameAs = [];
                if (cfg('facebook_url'))  $sameAs[] = '"' . addslashes(cfg('facebook_url'))  . '"';
                if (cfg('instagram_url')) $sameAs[] = '"' . addslashes(cfg('instagram_url')) . '"';
                if (cfg('twitter_url'))   $sameAs[] = '"' . addslashes(cfg('twitter_url'))   . '"';
                if (cfg('youtube_url'))   $sameAs[] = '"' . addslashes(cfg('youtube_url'))   . '"';
                if (cfg('linkedin_url'))  $sameAs[] = '"' . addslashes(cfg('linkedin_url'))  . '"';
                echo implode(",\n            ", $sameAs ?: ['"https://www.facebook.com/wasom.official"']);
                ?>
            ],
            "contactPoint": {
                "@type": "ContactPoint",
                "email": "<?php echo addslashes(cfg('info_email', 'info@wasomupfy.com')); ?>",
                "contactType": "customer service",
                "hoursAvailable": {
                    "@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                    "opens": "08:00",
                    "closes": "17:00"
                }
            },
            "address": {
                "@type": "PostalAddress",
                "addressCountry": "Angola",
                "addressLocality": "Luanda"
            }
        }
    </script>
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
                <a class="navbar-brand" href="<?php echo  APP_URL ?>/home" title="Home">
                    <img src="assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo <?php echo $siteName; ?>" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button"><i class="bi bi-list"></i></button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Logotipo" href="<?php echo  APP_URL ?>/home">
                            <img width="65" src="assets/img/brand/wasomupfy_brand.png"
                                alt="Logo <?php echo $siteName; ?>" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item">
                                <a class="nav-link active" href="home" title="Início">Início</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="about" title="Sobre">Sobre</a>
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
                                        href="plan/all-plans">
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
                                            <a title="Novidades" class="dropdown-item" href="blog/">Novidades</a>
                                            <a title="Passatempo" class="dropdown-item" href="blog/">Passatempo</a>
                                            <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                <span class="badge bg-warning">Indisponível</span></a>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a title="A nossa marca" class="dropdown-item"
                                                    href="about?#nossamarca">A
                                                    nossa marca</a>
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
                                                <a title="Serviços Personalizados" class="dropdown-item"
                                                    href="page/services/customized-services">Serviços personalizados
                                                    <span class="badge bg-warning">Em breve</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052"
                                                    target="_blank" rel="external noopener noreferrer">Atendimento</a>
                                                <a title="Contacta-nos" class="dropdown-item"
                                                    href="contact">Contacta-nos</a>
                                                <a title="Canal WhatsApp" class="dropdown-item"
                                                    href="<?php echo htmlspecialchars($whatsChannel); ?>"
                                                    target="_blank" rel="external noopener noreferrer">Canal
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
                                                    <a title="Suporte técnico" class="dropdown-item"
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
                                <a title="Contactar" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Contactar <i data-feather="chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a title="Caixa de mensagem" class="dropdown-item" href="contact">Caixa de
                                            mensagem</a></li>
                                    <?php if (cfg('support_email')): ?>
                                        <li><a title="E-mail" class="dropdown-item"
                                                href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                        </li>
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

    <main>
        <!-- ══ Hero ════════════════════════════════════════════════════════════ -->
        <section class="jarallax py-7 hero-agency" data-jarallax data-speed="0.4" data-cue="fadeIn">
            <img class="jarallax-img" src="assets/img/theme/afro_girl_theme.png" alt="Capa <?php echo $siteName; ?>" />
            <div class="position-relative start-0 end-0">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-5 col-lg-7 col-12" data-cues="zoomIn" data-group="page-title"
                            data-delay="700">
                            <div class="text-left text-lg-start">
                                <div class="text-white-stable">
                                    <small class="text-uppercase ls-lg">— Alcance novos fãs e ganhe dinheiro com a sua
                                        música</small>
                                    <h1 class="mb-3 mt-3 display-3 text-white-stable"><?php echo $siteName; ?>.</h1>
                                    <h3 class="text-white-stable">
                                        Suba as suas músicas por apenas
                                        <?php if (isset($plansBySlug['single'])): ?>
                                            <strong
                                                class="text-wasomupfy"><?php echo number_format($plansBySlug['single']['price_plan'], 0, ',', '.'); ?>Kz</strong>
                                        <?php else: ?>
                                            <strong class="text-wasomupfy">2.000Kz</strong>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="lead font-weight-lighter mt-3">
                                        <q>Distribua a sua música em plataformas como Spotify, Apple Music, Youtube,
                                            Instagram, Tiktok
                                            e muito mais — em mais de <strong
                                                class="text-wasomupfy"><?php echo $stores; ?>
                                                lojas</strong>
                                            globais.
                                            Mantenha <strong class="text-wasomupfy"><?php echo $royalty; ?>% dos seus
                                                royalties</strong> e
                                            conserve os
                                            direitos autorais das suas músicas.</q>
                                    </p>
                                </div>
                                <div class="d-flex flex-row gap-2 justify-content-left">
                                    <?php if ($canRegister): ?>
                                        <a href="/wasomupfy/register" title="Junta-se agora"
                                            class="btn btn-wasomupfy hover-scale-x-105 icon-link icon-link-hover"
                                            rel="internal">
                                            Junta-se agora
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                                <path fill-rule="evenodd"
                                                    d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    <a href="about" title="Sobre"
                                        class="btn btn-secondary hover-scale-x-105 icon-link icon-link-hover">
                                        Saiba mais
                                    </a>
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <div class="mb-3 text-start">
                                        <small class="text-white-stable acompanha">Acompanhe-nos:</small>
                                    </div>
                                    <div class="text-md-start d-flex align-items-start justify-content-md-start">
                                        <div class="ms-1 d-flex gap-3">
                                            <?php if (cfg('facebook_url')): ?>
                                                <a target="_blank" rel="external" title="Facebook <?php echo $siteName; ?>"
                                                    href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>"
                                                    class="btn btn-wasomupfy">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                                                        <path
                                                            d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (cfg('instagram_url')): ?>
                                                <a target="_blank" rel="external" title="Instagram <?php echo $siteName; ?>"
                                                    href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>"
                                                    class="btn btn-wasomupfy">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                                                        <path
                                                            d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($whatsNum): ?>
                                                <a target="_blank" rel="external" title="WhatsApp <?php echo $siteName; ?>"
                                                    href="https://wa.me/<?php echo $whatsNum; ?>" class="btn btn-wasomupfy">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.76.982.998-3.677-.236-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.9 6.994c-.004 5.45-4.437 9.88-9.885 9.88m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.333.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.333 11.893-11.893 0-3.18-1.24-6.162-3.495-8.411" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (cfg('youtube_url')): ?>
                                                <a target="_blank" rel="external" title="YouTube <?php echo $siteName; ?>"
                                                    href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>"
                                                    class="btn btn-wasomupfy">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16">
                                                        <path
                                                            d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.007 2.007 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.007 2.007 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31.4 31.4 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.007 2.007 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A99.788 99.788 0 0 1 7.858 2h.193zM6.4 5.209v4.818l4.157-2.408L6.4 5.209z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (cfg('linkedin_url')): ?>
                                                <a target="_blank" rel="external" title="LinkedIn <?php echo $siteName; ?>"
                                                    href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>"
                                                    class="btn btn-wasomupfy">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider-fade"></div>

        <!-- ══ Vantagens & Como Funciona ══════════════════════════════════════ -->
        <section class="my-xl-4 py-4">
            <div class="row g-4">
                <div class="col-12" data-cue="zoomIn">
                    <div class="text-center mb-5">
                        <span
                            class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                            <i class="bi bi-globe"></i> Distribuição Global
                        </span>
                        <h2 class="display-6 fw-bold text-dark mb-4">
                            Distribua a sua música para o mundo inteiro
                        </h2>
                        <p class="lead text-body mb-0 px-lg-5">
                            Alcance novos fãs e ganhe dinheiro com a sua música. A <?php echo $siteName; ?>
                            é a plataforma de distribuição mais fácil e completa do mercado,
                            levando a sua música para todas as principais plataformas de streaming —
                            em mais de <strong class="text-wasomupfy"><?php echo $stores; ?> lojas</strong> globais.
                        </p>
                    </div>
                </div>

                <!-- Vantagens -->
                <div class="col-lg-6" data-cue="slideInLeft">
                    <div class="border-0 shadow-sm h-100">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex align-items-center mb-4">
                                <div class="icon-shape icon-lg bg-success bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="bi bi-award-fill text-success fs-3"></i>
                                </div>
                                <h3 class="mb-0 h4">Vantagens Exclusivas</h3>
                            </div>
                            <div class="benefits-list">
                                <div class="benefit-item d-flex mb-4">
                                    <div
                                        class="icon-shape icon-sm bg-success bg-opacity-10 rounded-2 p-2 me-3 flex-shrink-0">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold"><?php echo $royalty; ?>% dos Royalties</h5>
                                        <p class="text-body mb-0 small">
                                            Receba a maior parte dos seus ganhos com uma das taxas mais competitivas do
                                            mercado.
                                        </p>
                                    </div>
                                </div>
                                <div class="benefit-item d-flex mb-4">
                                    <div
                                        class="icon-shape icon-sm bg-primary bg-opacity-10 rounded-2 p-2 me-3 flex-shrink-0">
                                        <i class="bi bi-lightning-charge-fill text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">Pagamentos Rápidos</h5>
                                        <p class="text-body mb-0 small">
                                            Saques mensais direto para a sua conta. Taxas transparentes e histórico
                                            completo 24/7.
                                        </p>
                                    </div>
                                </div>
                                <div class="benefit-item d-flex mb-4">
                                    <div
                                        class="icon-shape icon-sm bg-info bg-opacity-10 rounded-2 p-2 me-3 flex-shrink-0">
                                        <i class="bi bi-bar-chart-fill text-info"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">Relatórios Detalhados</h5>
                                        <p class="text-body mb-0 small">
                                            Acompanhe streams, países, dados demográficos e compare com lançamentos
                                            anteriores.
                                        </p>
                                    </div>
                                </div>
                                <div class="benefit-item d-flex">
                                    <div
                                        class="icon-shape icon-sm bg-warning bg-opacity-10 rounded-2 p-2 me-3 flex-shrink-0">
                                        <i class="bi bi-megaphone-fill text-warning"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">Marketing Avançado</h5>
                                        <p class="text-body mb-0 small">
                                            Inclusão em playlists, campanhas em redes sociais e acesso a influenciadores
                                            musicais.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Como Funciona -->
                <div class="col-lg-6" data-cue="slideInRight">
                    <div class="border-0 shadow-sm h-100">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex align-items-center mb-4">
                                <div class="icon-shape icon-lg bg-wasomupfy bg-opacity-10 rounded-3 p-3 me-3">
                                    <i class="bi bi-rocket-takeoff-fill text-wasomupfy fs-3"></i>
                                </div>
                                <h3 class="mb-0 h4">Como Funciona</h3>
                            </div>
                            <div class="process-steps">
                                <div class="step-item d-flex mb-4 pb-3 border-bottom">
                                    <div class="step-number bg-wasomupfy text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                        style="width:40px;height:40px">
                                        <span class="h6 mb-0 fw-bold">1</span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">Cadastre-se em Minutos</h5>
                                        <p class="text-body mb-0 small">Crie a sua conta gratuitamente. Sem custos
                                            ocultos, sem compromissos.</p>
                                    </div>
                                </div>
                                <div class="step-item d-flex mb-4 pb-3 border-bottom">
                                    <div class="step-number bg-wasomupfy text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                        style="width:40px;height:40px">
                                        <span class="h6 mb-0 fw-bold">2</span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">Envie a sua Música</h5>
                                        <p class="text-body mb-0 small">Faça upload do áudio, capa do álbum e todos os
                                            metadados necessários.</p>
                                    </div>
                                </div>
                                <div class="step-item d-flex">
                                    <div class="step-number bg-wasomupfy text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                        style="width:40px;height:40px">
                                        <span class="h6 mb-0 fw-bold">3</span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-semibold">Lançamento Global</h5>
                                        <p class="text-body mb-0 small">A sua música no ar em até 48 horas em todas as
                                            plataformas principais.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 pt-3">
                                <?php if ($canRegister): ?>
                                    <a href="/wasomupfy/register?new_account"
                                        class="btn btn-wasomupfy btn-lg w-100 py-3 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-music-note-beamed me-2 fs-5"></i>
                                        Comece a distribuir agora
                                    </a>
                                <?php else: ?>
                                    <span
                                        class="btn btn-secondary btn-lg w-100 py-3 d-flex align-items-center justify-content-center disabled">
                                        Inscrições temporariamente fechadas
                                    </span>
                                <?php endif; ?>
                                <p class="text-center text-body small mt-2 mb-0">
                                    <i class="bi bi-shield-check text-success me-1"></i>
                                    Direitos autorais protegidos • Suporte 24/7 • Sem fidelidade
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Por que artistas escolhem -->
            <div class="container">
                <div class="row">
                    <div class="row justify-content-center mb-5 pt-5" data-cue="fadeIn">
                        <div class="col-lg-8 text-center">
                            <h2 class="fw-bold text-dark mb-2">
                                Por que artistas escolhem a <?php echo $siteName; ?>?
                            </h2>
                            <p class="lead text-body mb-0">
                                Da sua cidade natal para o mundo. A sua música em rádios, playlists virais e nas
                                principais plataformas globais.
                            </p>
                        </div>
                    </div>
                    <div class="row g-4" data-cue="fadeIn">
                        <?php
                        $featureCards = [
                            ['ico' => 'assets/img/icones/promove_sua_música.png',     'title' => 'Promoção eficiente da sua música',   'items' => ['Estratégias personalizadas por género musical', 'Inclusão em playlists temáticas', 'Campanhas de mídia paga otimizadas']],
                            ['ico' => 'assets/img/icones/controle1.png',              'title' => 'Controle total sobre os seus Lançamentos', 'items' => ['Adicione novos singles quando quiser', 'Atualize capas ou metadados a qualquer momento', 'Remova conteúdos sem burocracia']],
                            ['ico' => 'assets/img/icones/tempo_de_aprovacao.png',     'title' => 'Aprovação Rápida',                   'items' => ['Processo de revisão ágil (24-48h)', 'Feedback claro caso precise de ajustes', 'Suporte humano durante todo o processo']],
                            ['ico' => 'assets/img/icones/analises_inovadoras.png',    'title' => 'Análises Inovadoras',                'items' => ['Dashboard intuitivo com todos os dados', 'Alertas quando a sua música ganha tração', 'Insights para planear próximos lançamentos']],
                            ['ico' => 'assets/img/icones/dinheiro.png',               'title' => 'Maximize os seus Ganhos',            'items' => ['Monetização em todas as plataformas', 'Sistema de divisão de royalties', 'Oportunidades de licenciamento para filmes/ads']],
                            ['ico' => 'assets/img/icones/divisao_de_seus_royalties.png', 'title' => 'Transparência nos Royalties',      'items' => ['Divisão clara entre todos os envolvidos', 'Relatórios mensais detalhados', 'Sistema anti-fraude para proteger os seus direitos']],
                        ];
                        foreach ($featureCards as $fc): ?>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="card-lift h-100" data-cue="zoomIn" data-duration="500">
                                    <div class="card-body p-5">
                                        <div class="d-lg-flex">
                                            <div class="p-3 icon-xl icon-shape rounded bg-opacity-10">
                                                <img src="<?php echo $fc['ico']; ?>" width="120" height="120"
                                                    alt="<?php echo htmlspecialchars($fc['title']); ?>" />
                                            </div>
                                            <div class="ms-lg-5 mt-4 mt-lg-0">
                                                <div class="mb-4">
                                                    <h3><?php echo $fc['title']; ?></h3>
                                                    <ul class="list-group mb-2">
                                                        <?php foreach ($fc['items'] as $fi): ?>
                                                            <li>— <?php echo $fi; ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center">
                            <p>Junte-se a milhares de artistas que já transformaram as suas carreiras com a
                                <?php echo $siteName; ?>.</p>
                            <?php if ($canRegister): ?>
                                <a href="/wasomupfy/register?new_account"
                                    class="btn btn-wasomupfy btn-lg w-100 py-3 d-flex align-items-center justify-content-center">
                                    Cadastra-se agora
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Counters -->
                        <div class="col-lg-3 col-6 border-end border-bottom border-bottom-lg-0 pt-2">
                            <div class="p-4 p-lg-5">
                                <h2 class="display-5 fw-bold text-wasomupfy mb-2 counter_ws" data-valor="1500"
                                    data-tipo="contagem"></h2>
                                <p class="text-dark mb-0">Artistas Ativos</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 border-end border-bottom border-bottom-lg-0">
                            <div class="p-4 p-lg-5">
                                <h2 class="display-5 fw-bold text-wasomupfy mb-2 counter_ws" data-valor="5000000"
                                    data-tipo="contagem">0</h2>
                                <p class="text-dark mb-0">Streams Mensais</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 border-end border-bottom border-bottom-lg-0">
                            <div class="p-4 p-lg-5">
                                <h2 class="display-5 fw-bold text-wasomupfy mb-2 counter_ws" data-valor="95"
                                    data-tipo="porcentagem">0</h2>
                                <p class="text-dark mb-0">Satisfação dos Artistas</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="p-4 p-lg-5">
                                <h2 class="display-5 fw-bold text-wasomupfy mb-2"><?php echo $stores; ?>+</h2>
                                <p class="text-dark mb-0">Plataformas Globais</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══ Planos ══════════════════════════════════════════════════════════ -->
        <section class="py-lg-8 py-5">
            <div class="container">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-8 text-center">
                        <span
                            class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                            <i class="fas fa-money-bill-wave"></i> Planos &amp; Preços
                        </span>
                        <h2 class="display-5 fw-bold text-dark mb-3">Escolha o plano ideal para a sua carreira</h2>
                        <p class="lead text-body mb-0">
                            Desde artistas iniciantes até selos estabelecidos. Encontre o plano perfeito para as suas
                            necessidades.
                        </p>
                    </div>
                </div>

                <!-- Cards de planos — dinâmico -->
                <div class="row g-4 justify-content-center">
                    <?php foreach ($plansGrid as $slug => $cfg_plan):
                        $p = $plansBySlug[$slug] ?? null;
                        if (!$p) continue;
                        $price   = number_format($p['price_plan'], 0, ',', '.');
                        $period  = $p['type_plan'] === 'subscription' ? '/ano' : '';
                        $feats   = $planFeatures[$slug] ?? [];
                    ?>
                        <div class="col-xl-3 col-lg-6" data-cue="zoomIn" <?php echo $cfg_plan['delay']; ?>>
                            <div class="card <?php echo $cfg_plan['cardCls']; ?> h-100 hover-lift">
                                <?php if ($cfg_plan['popular']): ?>
                                    <div class="position-absolute top-0 start-50 translate-middle mt-3">
                                        <span class="badge bg-wasomupfy text-white rounded-pill px-3 py-2">
                                            <i class="bi bi-star-fill me-1"></i> Mais Popular
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body p-4 <?php echo $cfg_plan['popular'] ? 'pt-5' : ''; ?>">
                                    <div class="text-center mb-4 <?php echo $cfg_plan['popular'] ? 'pt-3' : ''; ?>">
                                        <span
                                            class="badge <?php echo $cfg_plan['badgeCls']; ?> rounded-pill px-3 py-1 mb-3 d-inline-block">
                                            <?php echo $cfg_plan['badge']; ?>
                                        </span>
                                        <h3 class="h4 mb-2 text-wasomupfy"><?php echo htmlspecialchars($p['name_plan']); ?>
                                        </h3>
                                        <div class="d-flex align-items-baseline justify-content-center mb-3">
                                            <span class="h1 fw-bold text-wasomupfy mb-0"><?php echo $price; ?></span>
                                            <span class="text-body ms-1">Kz<?php echo $period; ?></span>
                                        </div>
                                        <div class="mb-4">
                                            <span class="text-success fw-semibold"><?php echo $royalty; ?>% Royalties</span>
                                            <div class="progress mt-2" style="height:6px">
                                                <div class="progress-bar bg-success" style="width:<?php echo $royalty; ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <ul class="list-unstyled mb-4">
                                        <?php foreach ($feats as [$check, $text]): ?>
                                            <li class="d-flex align-items-start mb-3">
                                                <i
                                                    class="bi <?php echo $check ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?> me-2 mt-1"></i>
                                                <span><?php echo htmlspecialchars($text); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="text-center mt-auto">
                                        <?php if ($canRegister): ?>
                                            <a href="/wasomupfy/register?plan=<?php echo $slug; ?>"
                                                class="btn <?php echo $cfg_plan['btnCls']; ?> w-100">
                                                <i class="bi <?php echo $cfg_plan['btnIcon']; ?> me-2"></i>
                                                <?php echo $cfg_plan['btnLabel']; ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="plan/<?php echo $slug; ?>" class="btn btn-outline-secondary w-100">
                                                Ver detalhes do plano
                                            </a>
                                        <?php endif; ?>
                                        <p class="text-body small mt-2 mb-0"><?php echo $cfg_plan['btnNote']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tabela comparativa -->
                <div class="row mt-6" data-cue="fadeIn">
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body p-4 p-lg-5">
                                <div class="text-center mb-4">
                                    <h4 class="mb-3">Comparação de Planos</h4>
                                    <p class="text-body mb-0">Veja as principais diferenças entre os planos</p>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-borderless mb-0">
                                        <thead>
                                            <tr class="border-bottom">
                                                <th class="text-start" style="width:40%">Característica</th>
                                                <?php foreach ($plansBySlug as $slug => $p): ?>
                                                    <th class="text-center"><?php echo htmlspecialchars($p['name_plan']); ?>
                                                    </th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="text-start fw-semibold">Royalties</td>
                                                <?php foreach ($plansBySlug as $slug => $p): ?>
                                                    <td class="text-center text-success fw-bold"><?php echo $royalty; ?>%
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-start fw-semibold">Artistas</td>
                                                <td class="text-center">1</td>
                                                <td class="text-center">1</td>
                                                <td class="text-center">1</td>
                                                <td class="text-center text-success fw-bold">10</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-start fw-semibold">Lançamento</td>
                                                <td class="text-center">72h</td>
                                                <td class="text-center text-success fw-bold">24h</td>
                                                <td class="text-center text-success fw-bold">24h</td>
                                                <td class="text-center text-success fw-bold">24h</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-start fw-semibold">Suporte</td>
                                                <td class="text-center">Básico</td>
                                                <td class="text-center text-success fw-bold">Prioritário</td>
                                                <td class="text-center text-success fw-bold">Dedicado</td>
                                                <td class="text-center text-success fw-bold">Empresarial</td>
                                            </tr>
                                            <tr>
                                                <td class="text-start fw-semibold">Personalizar nome de selo</td>
                                                <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i>
                                                </td>
                                                <td class="text-center"><i
                                                        class="bi bi-check-circle-fill text-success"></i></td>
                                                <td class="text-center"><i
                                                        class="bi bi-check-circle-fill text-success"></i></td>
                                                <td class="text-center"><i
                                                        class="bi bi-check-circle-fill text-success"></i></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ inline -->
                <div class="row mt-6" data-cue="fadeIn">
                    <div class="col-lg-10 mx-auto">
                        <div class="text-center mb-5">
                            <h4 class="mb-3">Perguntas Frequentes</h4>
                            <p class="text-body mb-0">Tire as suas dúvidas sobre os nossos planos e sobre a plataforma
                            </p>
                        </div>
                        <div class="text-justify">
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
                                <a href="page/support/faq" class="btn btn-outline-primary">
                                    Ver todas as perguntas <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-6" data-cue="fadeIn">
                    <div class="col-lg-8 mx-auto">
                        <div class="card border-wasomupfy border-2 bg-wasomupfy bg-opacity-5">
                            <div class="card-body p-4 p-lg-5 text-center">
                                <h3 class="fw-bold mb-3">Ainda com dúvidas?</h3>
                                <p class="text-body mb-4">A nossa equipa está pronta para ajudá-lo a escolher o plano
                                    ideal.</p>
                                <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                                    <?php if ($canRegister): ?>
                                        <a href="/wasomupfy/register" class="btn btn-wasomupfy btn-lg">
                                            <i class="bi bi-rocket-takeoff-fill me-2"></i> Começar Agora
                                        </a>
                                    <?php endif; ?>
                                    <a href="contact" class="btn btn-outline-primary btn-lg">
                                        <i class="bi bi-chat-dots-fill me-2"></i> Falar com Suporte
                                    </a>
                                </div>
                                <p class="text-body small mt-4 mb-0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    * Impostos adicionais podem ser aplicados conforme a legislação de cada país.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider-fade"></div>

        <!-- ══ Plataformas ════════════════════════════════════════════════════ -->
        <section class="py-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-12">
                        <div class="text-center mb-5">
                            <span
                                class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                                <i class="bi bi-globe2"></i> Plataformas de streamings &amp; <?php echo $siteName; ?>
                            </span>
                            <p class="mb-0 px-xl-4">
                                Assine agora e terá as suas músicas em mais de
                                <span class="text-wasomupfy"><?php echo $stores; ?> plataformas</span> digitais
                                como Spotify, Apple Music, Amazon Music, Tiktok, Instagram entre outras.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="marquee" style="height:92px">
                    <div class="track py-3">
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-spotify fs-1 text-success"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-apple fs-1 text-danger"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-amazon fs-1 text-info"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-youtube fs-1 text-danger"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-deezer fs-1 text-dark"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-gem fs-1 text-dark"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-tiktok fs-1 text-dark"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-instagram fs-1 text-danger"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-facebook fs-1 text-primary"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-snapchat fs-1 text-warning"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-twitch fs-1 text-primary"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-bolt-lightning fs-1 text-info"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-soundcloud fs-1 text-warning"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-p fs-1 text-primary"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-play fs-1 text-warning"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-compact-disc fs-1 text-muted"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-vimeo-v fs-1 text-info"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-napster fs-1 text-dark"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-soundcloud fs-1 text-warning"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-x-twitter fs-1 text-dark"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-brands fa-lastfm fs-1 text-danger"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-record-vinyl fs-1 text-info"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-wave-square fs-1 text-primary"></i></a>
                        <a href="#!" class="btn btn-icon btn-lg shadow-sm mx-2 btn-lift"><i
                                class="fa-solid fa-globe fs-1 text-muted"></i></a>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="text-center mt-lg-6">
                            <?php if ($canRegister): ?>
                                <a href="/wasomupfy/register" title="Inscreva-se agora"
                                    class="btn btn-wasomupfy btn-lg px-5 py-3 hover-scale-x-105 icon-link icon-link-hover">
                                    Inscreve-se agora
                                </a>
                            <?php else: ?>
                                <a href="plan/all-plans" class="btn btn-outline-primary btn-lg px-5 py-3">Ver os planos</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider-fade"></div>

        <!-- ══ Depoimentos ═══════════════════════════════════════════════════ -->
        <section class="py-5">
            <div class="container" data-cue="fadeIn">
                <div class="row justify-content-center mb-6">
                    <div class="col-lg-8 text-center">
                        <span
                            class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                            <i class="bi bi-star"></i> Histórias de Sucesso
                        </span>
                        <h2 class="display-5 fw-bold text-dark mb-3">O que os nossos artistas dizem</h2>
                        <p class="lead text-body mb-0">
                            Artistas reais, resultados reais. Descubra como a <?php echo $siteName; ?> transformou
                            carreiras musicais.
                        </p>
                    </div>
                </div>

                <!-- Carrossel -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="position-relative">
                            <div class="d-flex justify-content-center gap-3 mb-4">
                                <button class="btn btn-outline-primary testimonial-prev"><i
                                        class="bi bi-chevron-left"></i></button>
                                <button class="btn btn-outline-primary testimonial-next"><i
                                        class="bi bi-chevron-right"></i></button>
                            </div>
                            <div class="testimonial-carousel">
                                <?php
                                $depos = [
                                    ['seed' => 'Cristiano', 'bg' => 'ff009d', 'name' => 'Cristiano Amadeu', 'role' => 'Artista Album • Afrobeat', 'metric' => '<i class="bi bi-spotify text-success me-1"></i>+45K streams mensais', 'stars' => 5, 'badge' => '<span class="badge bg-success bg-opacity-10 text-success ms-3"><i class="bi bi-fire me-1"></i>Em Alta</span>', 'msg' => '"Em apenas 1 mês na Wasom Upfy, a minha música alcançou 10x mais ouvintes do que em 2 anos tentando sozinho! A distribuição global realmente funciona."'],
                                    ['seed' => 'José', 'bg' => '1db954', 'name' => 'José Mbenga', 'role' => 'Produtor • Semba', 'metric' => '<i class="bi bi-youtube text-danger me-1"></i>+32K views no YouTube', 'stars' => 5, 'badge' => '<span class="badge bg-info bg-opacity-10 text-info ms-3"><i class="bi bi-graph-up me-1"></i>Crescendo</span>', 'msg' => '"Os relatórios detalhados da Wasom Upfy ajudaram-me a entender o meu público e planear a minha tournée virtual. Finalmente vejo para onde a minha música está a ir!"'],
                                    ['seed' => 'Bruna', 'bg' => '9146ff', 'name' => 'Bruna Silva', 'role' => 'Cantora • Pop', 'metric' => '<i class="bi bi-tiktok text-dark me-1"></i>Viral no TikTok', 'stars' => 4, 'badge' => '<span class="badge bg-purple bg-opacity-10 text-white ms-3"><i class="bi bi-currency-dollar me-1"></i>Royalties</span>', 'msg' => '"Finalmente recebi os meus primeiros royalties internacionais com total transparência! A Wasom Upfy cuida de tudo enquanto eu foco na música."'],
                                    ['seed' => 'Pedro', 'bg' => 'ff9900', 'name' => 'Pedro Santos', 'role' => 'DJ • Eletrónica', 'metric' => '<i class="bi bi-apple text-dark me-1"></i>#2 em playlists Apple Music', 'stars' => 5, 'badge' => '<span class="badge bg-warning bg-opacity-10 text-warning ms-3"><i class="bi bi-rocket-takeoff me-1"></i>Lançamento</span>', 'msg' => '"O meu single foi para o ar em menos de 24 horas e já está em todas as plataformas. A velocidade da Wasom Upfy é impressionante!"'],
                                ];
                                foreach ($depos as $d): ?>
                                    <div class="testimonial-item">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body p-4 p-lg-5">
                                                <div class="mb-4">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="rating-stars">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i
                                                                    class="bi <?php echo $i <= $d['stars'] ? 'bi-star-fill' : 'bi-star-half'; ?> text-warning"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <?php echo $d['badge']; ?>
                                                    </div>
                                                    <p class="lead mb-0"><?php echo $d['msg']; ?></p>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <div class="position-relative">
                                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $d['seed']; ?>&backgroundColor=<?php echo $d['bg']; ?>"
                                                            alt="<?php echo htmlspecialchars($d['name']); ?>"
                                                            class="avatar avatar-lg rounded-circle" />
                                                    </div>
                                                    <div class="ms-3">
                                                        <h5 class="mb-1 fw-bold text-muted">
                                                            <?php echo htmlspecialchars($d['name']); ?></h5>
                                                        <p class="text-body-secondary mb-1 small"><?php echo $d['role']; ?>
                                                        </p>
                                                        <div class="d-flex align-items-center small">
                                                            <?php echo $d['metric']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats de sucesso -->
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card border-0 bg-light-100 overflow-hidden">
                            <div class="card-body p-4 p-lg-5">
                                <div class="row align-items-center">
                                    <div class="col-lg-6 mb-4 mb-lg-0">
                                        <h3 class="mb-3 fw-bold">Resultados Reais</h3>
                                        <p class="text-body mb-0">Os nossos artistas já alcançaram marcas
                                            impressionantes com a <?php echo $siteName; ?>.</p>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="row text-center">
                                            <div class="col-6 col-md-3 mb-3">
                                                <div class="h2 fw-bold text-wasomupfy mb-1 counter_ws" data-valor="250"
                                                    data-tipo="contagem">0+</div>
                                                <div class="small">Streams Totais</div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-3">
                                                <div class="h2 fw-bold text-wasomupfy mb-1 counter_ws" data-valor="3"
                                                    data-tipo="contagem">0+</div>
                                                <div class="small">Países</div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-3">
                                                <div class="h2 fw-bold text-wasomupfy mb-1 counter_ws"
                                                    data-valor="5000000" data-tipo="moeda">0</div>
                                                <div class="small">Royalties Pagos</div>
                                            </div>
                                            <div class="col-6 col-md-3 mb-3">
                                                <div class="h2 fw-bold text-wasomupfy mb-1 counter_ws" data-valor="98"
                                                    data-tipo="porcentagem">0</div>
                                                <div class="small">Satisfação</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid de depoimentos -->
                <div class="row g-4 mb-6">
                    <?php
                    $gridDepos = [
                        ['seed' => 'Maria', 'bg' => 'e4405f', 'name' => 'Maria Costa', 'role' => 'Plano Manager', 'msg' => '"O suporte da Wasom Upfy é excecional. Eles realmente se importam com o sucesso dos artistas."'],
                        ['seed' => 'Rafael', 'bg' => '0088ff', 'name' => 'Rafael Lima', 'role' => 'Artista Album', 'msg' => '"As ferramentas de promoção são incríveis. A minha música apareceu em playlists que nem imaginava!"'],
                        ['seed' => 'Ana', 'bg' => '00a8e1', 'name' => 'Ana Santos', 'role' => 'Produtora Musical', 'msg' => '"Transparência total nos pagamentos. Finalmente uma plataforma que respeita os artistas."'],
                    ];
                    foreach ($gridDepos as $gd): ?>
                        <div class="col-lg-4" data-cue="zoomIn">
                            <div class="card border-0 shadow-sm h-100 hover-lift">
                                <div class="card-body p-4">
                                    <div class="mb-3"><i class="bi bi-quote fs-1 text-wasomupfy opacity-25"></i></div>
                                    <p class="mb-4"><?php echo $gd['msg']; ?></p>
                                    <div class="d-flex align-items-center">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $gd['seed']; ?>&backgroundColor=<?php echo $gd['bg']; ?>"
                                            alt="<?php echo htmlspecialchars($gd['name']); ?>"
                                            class="avatar avatar-md rounded-circle me-3" />
                                        <div>
                                            <h6 class="mb-0 fw-semibold text-muted">
                                                <?php echo htmlspecialchars($gd['name']); ?></h6>
                                            <span class="text-body-secondary small"><?php echo $gd['role']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- CTA -->
                <div class="row" data-cue="fadeIn">
                    <div class="col-lg-8 mx-auto text-center">
                        <h3 class="mb-4">Pronto para escrever a sua história de sucesso?</h3>
                        <p class="text-body mb-4 lead">
                            Junte-se a milhares de artistas que já transformaram as suas carreiras com a
                            <?php echo $siteName; ?>.
                        </p>
                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                            <?php if ($canRegister): ?>
                                <a href="/wasomupfy/register" class="btn btn-wasomupfy btn-lg px-5 py-3">
                                    <i class="bi bi-rocket-takeoff-fill me-2"></i> Começar Agora
                                </a>
                            <?php endif; ?>
                            <a href="#testemunhos" class="btn btn-outline-primary btn-lg px-5 py-3">
                                <i class="bi bi-play-circle-fill me-2"></i> Ver Mais Histórias
                            </a>
                        </div>
                        <p class="text-body small mt-4">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            30 dias de garantia • Suporte 24/7 • Sem fidelidade
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider-fade"></div>

        <!-- ══ Sobre Nós ══════════════════════════════════════════════════════ -->
        <section class="py-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-5 mb-lg-0" data-cue="slideInLeft">
                        <div class="mb-5">
                            <span
                                class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                                <i class="bi bi-music-note-beamed"></i> Nossa História
                            </span>
                            <h2 class="display-5 fw-bold text-dark mb-4">Transformando sonhos em realidade musical</h2>
                            <p class="lead text-body mb-4">
                                Somos a ponte que conecta talentos africanos ao mercado musical global.
                                Da sua cidade natal para o mundo, com tecnologia e paixão pela música.
                            </p>
                        </div>
                        <div class="row g-4 mb-5">
                            <?php
                            $aboutStats = [
                                ['val' => '2022',    'tipo' => '',         'label' => 'Fundação'],
                                ['val' => '1500',     'tipo' => 'contagem', 'label' => 'Artistas'],
                                ['val' => (string)$stores, 'tipo' => 'contagem', 'label' => 'Plataformas'],
                                ['val' => '50000000', 'tipo' => 'contagem', 'label' => 'Streams'],
                            ];
                            foreach ($aboutStats as $as): ?>
                                <div class="col-6 col-md-3">
                                    <div class="text-center">
                                        <?php if ($as['tipo']): ?>
                                            <div class="h2 fw-bold text-wasomupfy mb-2 counter_ws"
                                                data-valor="<?php echo $as['val']; ?>" data-tipo="<?php echo $as['tipo']; ?>">0
                                            </div>
                                        <?php else: ?>
                                            <div class="h2 fw-bold text-wasomupfy mb-2"><?php echo $as['val']; ?></div>
                                        <?php endif; ?>
                                        <div class="text-body small"><?php echo $as['label']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="icon-shape icon-lg bg-primary bg-opacity-10 rounded-3 p-3 me-3">
                                        <i class="bi bi-bullseye text-primary fs-3"></i>
                                    </div>
                                    <div>
                                        <h4 class="h5 mb-2">Nossa Missão</h4>
                                        <p class="text-body mb-0">
                                            Democratizar o acesso ao mercado musical global para artistas independentes
                                            de Angola e África Lusófona.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="icon-shape icon-lg bg-success bg-opacity-10 rounded-3 p-3 me-3">
                                        <i class="bi bi-eye-fill text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <h4 class="h5 mb-2">Nossa Visão</h4>
                                        <p class="text-body mb-0">
                                            Ser a principal plataforma de distribuição digital para artistas africanos
                                            até 2030.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card da empresa -->
                    <div class="col-lg-5" data-cue="slideInRight">
                        <div class="card border-0 shadow-lg overflow-hidden">
                            <div class="card-body p-4 p-lg-5">
                                <div class="text-center mb-4">
                                    <div class="position-relative mb-4">
                                        <img src="<?php echo htmlspecialchars(cfg('founder_photo', 'https://media.licdn.com/dms/image/v2/D4D0BAQEZT7NgIGnmIg/img-crop_100/B4DZowkXkgG8AM-/0/1761751438182?e=2147483647&v=beta&t=oTDWQM3hF58kZey3ykwyIuBBzYmvgTBXIkxGwrcsfbs')); ?>"
                                            alt="<?php echo $siteName; ?>"
                                            class="rounded-circle border border-4 border-wasomupfy" width="120"
                                            height="120" />
                                        <div
                                            class="position-absolute bottom-0 end-0 bg-wasomupfy text-white rounded-circle p-2">
                                            <i class="bi bi-star-fill"></i>
                                        </div>
                                    </div>
                                    <h3 class="h4 mb-1 text-muted">Wasom Music Group</h3>
                                    <p class="text-body-secondary mb-3">Empresa</p>
                                    <div class="d-flex justify-content-center gap-2 mb-3">
                                        <?php if (cfg('linkedin_url')): ?>
                                            <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                                class="btn btn-outline-wasomupfy btn-sm rounded-circle">
                                                <i class="bi bi-linkedin"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-center mb-4">
                                    <i class="bi bi-quote fs-1 text-wasomupfy opacity-25"></i>
                                    <p class="fst-italic mb-0">
                                        "Acreditamos que todo artista tem uma voz que merece ser ouvida. A nossa missão
                                        é amplificar essas vozes."
                                    </p>
                                </div>
                                <div class="border-top pt-4">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="h5 fw-bold text-wasomupfy mb-1">
                                                <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?></div>
                                            <div class="text-body-secondary small">Sede Principal</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="h5 fw-bold text-wasomupfy mb-1">
                                                <?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?></div>
                                            <div class="text-body-secondary small">Origem</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- O que fazemos -->
                <div class="row mt-6 pt-5 border-top" data-cue="fadeIn">
                    <div class="col-12">
                        <div class="text-center mb-5">
                            <h3 class="h2 mb-3">O Que Fazemos</h3>
                            <p class="lead text-body mb-0">Soluções completas para artistas independentes</p>
                        </div>
                        <div class="row g-4">
                            <?php
                            $servicos = [
                                ['icon' => 'bi-globe-americas', 'title' => 'Distribuição Digital Global', 'desc' => 'A sua música em todas as principais plataformas de streaming mundial.', 'items' => ['Spotify, Apple Music, Deezer', 'YouTube, TikTok, Instagram', '+' . $stores . ' lojas globais'], 'link' => 'page/services/music-distribution'],
                                ['icon' => 'bi-megaphone-fill', 'title' => 'Marketing Musical Estratégico', 'desc' => 'Promoção inteligente para maximizar o alcance da sua música.', 'items' => ['Campanhas segmentadas', 'Inclusão em playlists', 'Gestão de tráfego pago'], 'link' => 'page/services/music-promotion'],
                                ['icon' => 'bi-bar-chart-fill', 'title' => 'Análise e Relatórios', 'desc' => 'Dados detalhados para tomar decisões estratégicas informadas.', 'items' => ['Relatórios de desempenho', 'Análise demográfica', 'Insights de mercado'], 'link' => 'plan/all-plans'],
                            ];
                            foreach ($servicos as $srv): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="card border-0 shadow-sm h-100 hover-lift">
                                        <div class="card-body p-4 p-lg-5">
                                            <div class="icon-shape icon-lg bg-wasomupfy bg-opacity-10 rounded-3 p-3 mb-4">
                                                <i class="bi <?php echo $srv['icon']; ?> text-wasomupfy fs-2"></i>
                                            </div>
                                            <h4 class="h5 mb-3"><?php echo $srv['title']; ?></h4>
                                            <p class="text-body mb-4"><?php echo $srv['desc']; ?></p>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($srv['items'] as $si): ?>
                                                    <li class="d-flex align-items-start mb-2">
                                                        <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                                        <span><?php echo $si; ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-6" data-cue="fadeIn">
                    <div class="col-lg-10 mx-auto">
                        <div class="card border-wasomupfy border-2 bg-wasomupfy bg-opacity-5">
                            <div class="card-body p-4 p-lg-5 text-center">
                                <div class="row align-items-center">
                                    <div class="col-lg-8 mb-4 mb-lg-0 text-lg-start">
                                        <h3 class="mb-3">Pronto para transformar a sua carreira?</h3>
                                        <p class="text-body mb-0">Junte-se à família <?php echo $siteName; ?> e alcance
                                            novos patamares na sua jornada musical.</p>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="d-flex flex-column gap-3">
                                            <a href="about" class="btn btn-outline-primary btn-lg">
                                                <i class="bi bi-info-circle-fill me-2"></i> Conheça Mais
                                            </a>
                                            <?php if ($canRegister): ?>
                                                <a href="/wasomupfy/register" class="btn btn-wasomupfy btn-lg">
                                                    <i class="bi bi-rocket-takeoff-fill me-2"></i> Começar Agora
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider-fade"></div>

        <!-- ══ Suporte — Caixa de mensagem + Sugestões ═══════════════════════ -->
        <section class="my-xl-6 my-4" data-cue="fadeIn">
            <div class="container">
                <div class="row">
                    <div class="col-md-12" data-cue="fadeIn">
                        <ul class="nav nav-pills mb-2 nav-primary" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a title="Caixa de mensagem" href="#" class="nav-link active me-2" id="pillsDayone-tab"
                                    data-bs-toggle="pill" data-bs-target="#pillsDayone" role="tab"
                                    aria-controls="pillsDayone" aria-selected="true">
                                    Caixa de mensagem
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a title="Sugestões" href="#" class="nav-link me-2" id="pillsDaytwo-tab"
                                    data-bs-toggle="pill" data-bs-target="#pillsDaytwo" role="tab"
                                    aria-controls="pillsDaytwo" aria-selected="false">
                                    Sugestões
                                </a>
                            </li>
                        </ul>

                        <div class="divider-fade"></div>

                        <div class="tab-content row" id="pills-tabContent">

                            <!-- ─── Aba 1 — Caixa de mensagem (ajax/contact.php) ─── -->
                            <div class="tab-pane show active" id="pillsDayone" role="tabpanel"
                                aria-labelledby="pillsDayone-tab" tabindex="0" data-cues="slideInRight">
                                <div class="container">
                                    <div class="row align-items-center g-5">
                                        <div class="col-lg-5 col-12" data-cue="slideInLeft">
                                            <div class="mb-3">
                                                <h2 class="mt-0">Seja bem-vindo à nossa área de Suporte</h2>
                                                <i class="fa-solid fa-headset text-dark mb-3"
                                                    style="font-size:80px"></i>
                                                <p class="text-dark lead">Preencha os campos abaixo conforme a sua
                                                    necessidade:</p>
                                            </div>
                                            <div class="mb-6">
                                                <ul class="list-unstyled mb-0">
                                                    <li class="d-flex mb-3">
                                                        <span><i
                                                                class="fa-solid fa-circle-check text-wasomupfy text-opacity-50"></i></span>
                                                        <span class="ms-2"><span
                                                                class="text-dark fw-semibold">Compromisso:</span>
                                                            Solicitar acordo formal ou relatório de entrega.</span>
                                                    </li>
                                                    <li class="d-flex mb-3">
                                                        <span><i
                                                                class="fa-solid fa-circle-check text-wasomupfy text-opacity-50"></i></span>
                                                        <span class="ms-2"><span
                                                                class="text-dark fw-semibold">Denúncia:</span> Reportar
                                                            problemas técnicos ou violações de uso.</span>
                                                    </li>
                                                    <li class="d-flex mb-3">
                                                        <span><i
                                                                class="fa-solid fa-circle-check text-wasomupfy text-opacity-50"></i></span>
                                                        <span class="ms-2"><span
                                                                class="text-dark fw-semibold">Feedback:</span> Partilhe
                                                            a sua experiência com os nossos serviços.</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-12" data-cue="slideInRight">
                                            <div class="position-relative mx-3">
                                                <div class="m-auto card mt-5 text-left">
                                                    <div class="card-body">
                                                        <div id="contact-msg-home" class="alert d-none mb-3"
                                                            role="alert"></div>
                                                        <div class="col-md-12 m-auto text-left pt-3">
                                                            <form id="form-contact-home"
                                                                class="row g-3 needs-validation" novalidate
                                                                accept-charset="utf-8">
                                                                <input type="hidden" name="csrf_token"
                                                                    value="<?php echo htmlspecialchars($csrf_home); ?>" />
                                                                <input type="hidden" name="page_origin" value="home" />
                                                                <div class="col-md-6">
                                                                    <label for="name_user_home"
                                                                        class="form-label text-dark-form">Nome Completo
                                                                        <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="text" minlength="3" maxlength="60"
                                                                        placeholder="Insira o seu nome completo"
                                                                        class="form-control" autocomplete="name"
                                                                        id="name_user_home" name="name_msg" required />
                                                                    <div class="invalid-feedback"><span
                                                                            class="text-base">Qual é o seu nome
                                                                            completo?</span></div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="tel_user_home"
                                                                        class="form-label text-dark-form">Número de
                                                                        Telefone <span
                                                                            class="text-muted opacity-8 hit">(opcional)</span></label>
                                                                    <input type="tel" maxlength="18"
                                                                        placeholder="Insira o seu número de telefone"
                                                                        class="form-control" autocomplete="tel"
                                                                        id="tel_user_home" name="phone_msg" />
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="email_user_home"
                                                                        class="form-label text-dark-form">E-mail <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="email" maxlength="100"
                                                                        placeholder="usuario@exemplo.com"
                                                                        class="form-control" autocomplete="email"
                                                                        id="email_user_home" required
                                                                        name="email_msg" />
                                                                    <div class="invalid-feedback"><span
                                                                            class="text-base">Insira um e-mail
                                                                            válido!</span></div>
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="subject_home"
                                                                        class="form-label text-dark-form">Assunto <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="text" maxlength="100" minlength="5"
                                                                        placeholder="Insira o seu assunto"
                                                                        class="form-control" autocomplete="off"
                                                                        id="subject_home" required name="subject_msg" />
                                                                    <div class="invalid-feedback"><span
                                                                            class="text-base">Qual é o Assunto?</span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="message_home"
                                                                        class="form-label text-dark-form">Mensagem <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <textarea name="message_msg" id="message_home"
                                                                        required cols="30" class="form-control"
                                                                        maxlength="1000" rows="5"
                                                                        placeholder="Escreva a sua mensagem aqui!"></textarea>
                                                                    <div class="invalid-feedback"><span
                                                                            class="text-base">Por favor, escreva a sua
                                                                            mensagem!</span></div>
                                                                </div>
                                                                <div class="col-12 mt-3">
                                                                    <button class="btn btn-wasomupfy w-100"
                                                                        type="submit" id="btn-contact-home">
                                                                        Enviar mensagem
                                                                    </button>
                                                                    <div class="mt-1">
                                                                        <span class="text-sm text-dark-form-50"><span
                                                                                class="text-danger text-xs">*</span>
                                                                            campo obrigatório</span>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ─── Aba 2 — Sugestões (ajax/feedback.php) ─── -->
                            <div class="tab-pane" id="pillsDaytwo" role="tabpanel" aria-labelledby="pillsDaytwo-tab"
                                tabindex="0" data-cues="slideInRight">
                                <div class="container">
                                    <div class="row align-items-center g-5">
                                        <div class="col-lg-5 col-12" data-cue="slideInLeft">
                                            <div class="mb-3">
                                                <h2 class="mt-0">Seja bem-vindo à nossa área de Sugestões</h2>
                                                <i class="fa-solid fa-lightbulb text-dark mb-3"
                                                    style="font-size:80px"></i>
                                                <p class="text-dark lead">Ajude-nos a melhorar! Deixe a sua sugestão
                                                    abaixo:</p>
                                            </div>
                                            <div class="mb-6">
                                                <ul class="list-unstyled mb-0">
                                                    <li class="d-flex mb-3">
                                                        <span><i
                                                                class="fa-solid fa-circle-check text-wasomupfy text-opacity-50"></i></span>
                                                        <span class="ms-2"><span
                                                                class="text-dark fw-semibold">Melhorias:</span> O que
                                                            podemos aprimorar nos nossos serviços?</span>
                                                    </li>
                                                    <li class="d-flex mb-3">
                                                        <span><i
                                                                class="fa-solid fa-circle-check text-wasomupfy text-opacity-50"></i></span>
                                                        <span class="ms-2"><span
                                                                class="text-dark fw-semibold">Suporte:</span> Como
                                                            podemos atendê-lo melhor?</span>
                                                    </li>
                                                    <li class="d-flex mb-3">
                                                        <span><i
                                                                class="fa-solid fa-circle-check text-wasomupfy text-opacity-50"></i></span>
                                                        <span class="ms-2"><span
                                                                class="text-dark fw-semibold">Soluções:</span> Ideias
                                                            para novos recursos ou ferramentas.</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-lg-7 col-12" data-cue="slideInRight">
                                            <div class="position-relative mx-3">
                                                <div class="m-auto card mt-5 text-left">
                                                    <div class="card-body">
                                                        <div id="feedback-msg-home" class="alert d-none mb-3"
                                                            role="alert"></div>
                                                        <div class="col-md-12 m-auto text-left pt-3">
                                                            <form id="form-feedback-home"
                                                                class="row g-3 needs-validation" novalidate
                                                                accept-charset="utf-8">
                                                                <input type="hidden" name="csrf_token"
                                                                    value="<?php echo htmlspecialchars($csrf_home); ?>" />
                                                                <input type="hidden" name="page_origin" value="home" />
                                                                <div class="col-md-6">
                                                                    <label for="name_fb_home"
                                                                        class="form-label text-dark-form">Primeiro nome
                                                                        <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="text" minlength="3" maxlength="40"
                                                                        placeholder="Insira o seu primeiro nome"
                                                                        class="form-control" autocomplete="given-name"
                                                                        id="name_fb_home" name="name_fb" required />
                                                                    <div class="invalid-feedback">Qual é o seu primeiro
                                                                        nome?</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="name2_fb_home"
                                                                        class="form-label text-dark-form">Segundo nome
                                                                        <span
                                                                            class="text-muted opacity-8 hit">(opcional)</span></label>
                                                                    <input type="text" maxlength="40"
                                                                        placeholder="Insira o seu segundo nome"
                                                                        class="form-control" autocomplete="family-name"
                                                                        id="name2_fb_home" name="name2_fb" />
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="subject_fb_home"
                                                                        class="form-label text-dark-form">Assunto <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="text" maxlength="100" minlength="5"
                                                                        placeholder="Insira o seu assunto"
                                                                        class="form-control" autocomplete="off"
                                                                        id="subject_fb_home" required
                                                                        name="subject_fb" />
                                                                    <div class="invalid-feedback">Qual é o Assunto da
                                                                        sugestão?</div>
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="message_fb_home"
                                                                        class="form-label text-dark-form">Mensagem <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <textarea name="message_fb" id="message_fb_home"
                                                                        required cols="30" class="form-control"
                                                                        maxlength="1000" rows="5"
                                                                        placeholder="Escreva a sua mensagem aqui!"></textarea>
                                                                    <div class="invalid-feedback">Por favor, escreva a
                                                                        sua mensagem de sugestão!</div>
                                                                </div>
                                                                <div class="col-12 mt-3">
                                                                    <button class="btn btn-wasomupfy w-100"
                                                                        type="submit" id="btn-feedback-home">
                                                                        Enviar Sugestão
                                                                    </button>
                                                                    <div class="mt-1">
                                                                        <span class="text-sm text-dark-form-50"><span
                                                                                class="text-danger text-xs">*</span>
                                                                            campo obrigatório</span>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="divider-fade"></div>

        <!-- ══ Comunidade WhatsApp ════════════════════════════════════════════ -->
        <section class="py-5 position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 w-25 h-25 opacity-10">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#FF009D"
                        d="M45.2,-58.7C58.7,-48.2,70.1,-34.1,73.1,-18.2C76.1,-2.3,70.7,15.3,62.2,31.6C53.7,47.8,42.2,62.6,26.8,69.6C11.5,76.5,-7.8,75.6,-25.8,70C-43.8,64.4,-60.6,54.2,-68.2,39.2C-75.8,24.3,-74.1,4.7,-69.8,-13.2C-65.4,-31.1,-58.4,-47.4,-46.2,-58C-34,-68.6,-17,-73.5,0,-73.5C17,-73.5,34,-68.6,45.2,-58.7Z"
                        transform="translate(100 100)" />
                </svg>
            </div>
            <div class="position-absolute bottom-0 start-0 w-25 h-25 opacity-10">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#25D366"
                        d="M45.2,-58.7C58.7,-48.2,70.1,-34.1,73.1,-18.2C76.1,-2.3,70.7,15.3,62.2,31.6C53.7,47.8,42.2,62.6,26.8,69.6C11.5,76.5,-7.8,75.6,-25.8,70C-43.8,64.4,-60.6,54.2,-68.2,39.2C-75.8,24.3,-74.1,4.7,-69.8,-13.2C-65.4,-31.1,-58.4,-47.4,-46.2,-58C-34,-68.6,-17,-73.5,0,-73.5C17,-73.5,34,-68.6,45.2,-58.7Z"
                        transform="translate(100 100)" />
                </svg>
            </div>

            <div class="container position-relative z-1">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0" data-cue="slideInLeft">
                        <div class="mb-4">
                            <span
                                class="badge bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-pill px-4 py-2 mb-3 d-inline-block">
                                <i class="bi bi-whatsapp me-2"></i> Comunidade Exclusiva
                            </span>
                            <h2 class="display-5 fw-bold mb-3">Junte-se à nossa comunidade no WhatsApp</h2>
                            <p class="lead mb-4">
                                Conecte-se com outros artistas, receba insights valiosos e acelere a sua carreira
                                musical com a nossa comunidade ativa.
                            </p>
                        </div>
                        <div class="row g-3 mb-4">
                            <?php
                            $commBenefits = [
                                ['bg' => 'success', 'icon' => 'bi-lightning-charge-fill', 'label' => 'Dicas Exclusivas'],
                                ['bg' => 'warning', 'icon' => 'bi-graph-up-arrow', 'label' => 'Tendências'],
                                ['bg' => 'info', 'icon' => 'bi-tags-fill', 'label' => 'Ofertas Especiais'],
                                ['bg' => 'purple', 'icon' => 'bi-trophy-fill', 'label' => 'Concursos'],
                            ];
                            foreach ($commBenefits as $cb): ?>
                                <div class="col-6">
                                    <div class="d-flex align-items-center bg-white bg-opacity-10 rounded-3 p-3">
                                        <div class="icon-shape icon-sm bg-<?php echo $cb['bg']; ?> rounded-2 p-2 me-3">
                                            <i class="bi <?php echo $cb['icon']; ?> text-<?php echo $cb['bg']; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 small fw-semibold"><?php echo $cb['label']; ?></h6>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex gap-4 mb-4">
                            <div class="text-center">
                                <div class="h3 fw-bold text-wasomupfy mb-1 counter_ws" data-valor="500"
                                    data-tipo="contagem">0</div>
                                <div class="text-muted small">Artistas Ativos</div>
                            </div>
                            <div class="text-center">
                                <div class="h3 fw-bold text-wasomupfy mb-1">24/7</div>
                                <div class="text-muted small">Comunicação</div>
                            </div>
                            <div class="text-center">
                                <div class="h3 fw-bold text-wasomupfy mb-1">100%</div>
                                <div class="text-muted small">Gratuito</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6" data-cue="slideInRight">
                        <div class="card border-0 shadow-lg overflow-hidden">
                            <div class="card-body p-4 p-lg-5">
                                <div class="text-center mb-4">
                                    <div
                                        class="icon-shape icon-xl bg-success bg-opacity-10 rounded-circle p-4 mb-3 mx-auto">
                                        <i class="bi bi-whatsapp text-success fs-1"></i>
                                    </div>
                                    <h3 class="h3 mb-2 fw-bold">Comunidade WhatsApp</h3>
                                    <p class="text-body mb-0">Entre para o grupo exclusivo de artistas</p>
                                </div>
                                <div class="text-center mb-4">
                                    <div class="position-relative d-inline-block">
                                        <div class="bg-light-100 rounded-3 p-4 mb-3">
                                            <i class="bi bi-whatsapp text-success display-1"></i>
                                        </div>
                                        <div
                                            class="position-absolute top-0 end-0 bg-success text-white rounded-circle p-2">
                                            <i class="bi bi-check-lg"></i>
                                        </div>
                                    </div>
                                    <p class="text-body small mb-0">
                                        <i class="bi bi-shield-check text-success me-1"></i> Grupo verificado e moderado
                                    </p>
                                </div>
                                <div class="mb-4">
                                    <?php
                                    $commList = [
                                        ['Dicas Diárias de Promoção', 'Aprenda a promover a sua música de forma eficiente'],
                                        ['Tendências do Mercado', 'Fique a par das novidades da indústria musical'],
                                        ['Oportunidades Exclusivas', 'Acesso antecipado a concursos e colaborações'],
                                        ['Networking com Artistas', 'Conecte-se com produtores e artistas da comunidade'],
                                    ];
                                    foreach ($commList as [$t, $d]): ?>
                                        <div class="d-flex align-items-start mb-3">
                                            <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                                            <div>
                                                <h6 class="mb-1 fw-semibold text-muted"><?php echo $t; ?></h6>
                                                <p class="text-body small mb-0"><?php echo $d; ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center">
                                    <a href="<?php echo htmlspecialchars($whatsChannel); ?>" target="_blank"
                                        rel="noopener noreferrer" class="btn btn-success btn-lg w-100 py-3 mb-3">
                                        <i class="bi bi-whatsapp me-2"></i> Entrar no Grupo Agora
                                    </a>
                                    <p class="text-body-secondary small mb-0">
                                        <i class="bi bi-info-circle me-1"></i> Clique no botão para ser redirecionado ao
                                        WhatsApp
                                    </p>
                                </div>
                            </div>
                            <div class="bg-light-100 border-0 text-center py-3">
                                <div class="d-flex align-items-center justify-content-center">
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Artist1&backgroundColor=ff009d"
                                        alt="Artista" class="avatar avatar-xs rounded-circle me-2" />
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Artist2&backgroundColor=1db954"
                                        alt="Artista" class="avatar avatar-xs rounded-circle me-2" />
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Artist3&backgroundColor=9146ff"
                                        alt="Artista" class="avatar avatar-xs rounded-circle me-2" />
                                    <span class="text-body small ms-2">
                                        <span class="fw-semibold counter_ws" data-valor="500"
                                            data-tipo="contagem">+0</span> artistas já fazem parte
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ rápido da comunidade -->
                <div class="row mt-6 border-top" data-cue="fadeIn">
                    <div class="col-lg-10 mx-auto">
                        <div class="text-center mb-4">
                            <h4 class="mb-3">Perguntas Frequentes</h4>
                            <p class="text-body mb-0">Tire as suas dúvidas sobre a nossa comunidade</p>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="bg-white bg-opacity-10 rounded-3 p-4 h-100">
                                    <h6 class="mb-2"><i class="bi bi-question-circle text-wasomupfy me-2"></i>O grupo é
                                        realmente gratuito?</h6>
                                    <p class="text-body small mb-0">Sim! A comunidade WhatsApp é 100% gratuita para
                                        todos os artistas cadastrados na <?php echo $siteName; ?>.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-white bg-opacity-10 rounded-3 p-4 h-100">
                                    <h6 class="mb-2"><i class="bi bi-question-circle text-wasomupfy me-2"></i>Com que
                                        frequência recebo conteúdo?</h6>
                                    <p class="text-body small mb-0">Enviamos dicas diárias, atualizações semanais do
                                        mercado e oportunidades mensais especiais.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-6" data-cue="fadeIn">
                    <div class="col-lg-8 mx-auto text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <i class="bi bi-chat-heart-fill text-wasomupfy fs-1 me-3"></i>
                            <div class="text-start">
                                <h4 class="mb-0">Não fique de fora!</h4>
                                <p class="text-body mb-0">A comunidade está a crescer rapidamente</p>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($whatsChannel); ?>" target="_blank"
                            rel="noopener noreferrer" class="btn btn-success btn-lg px-5 py-3 shadow-lg hover-lift">
                            <i class="bi bi-whatsapp me-2"></i> Entrar Agora na Comunidade
                        </a>
                        <p class="text-body small mt-3 mb-0">
                            <i class="bi bi-clock-history me-1"></i>
                            Vagas limitadas • Entre enquanto há espaço disponível
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="divider-fade"></div>
    <?php require_once __DIR__ . '/include/components/footer.php'; ?>

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
                    A <?php echo $siteName; ?> utiliza cookies e tecnologias similares para melhorar a sua experiência
                    de navegação,
                    personalizar conteúdo e anúncios, fornecer recursos de mídia social e analisar o nosso tráfego.
                    Ao clicar em "Aceitar todos", concorda com o uso de todos os cookies.
                </p>
                <div class="cookie-alert-links">
                    <a href="page/politicies/cookies" class="cookie-alert-link" target="_blank"
                        rel="noopener noreferrer">Política de Cookies</a>
                    <a href="page/politicies/privacy" class="cookie-alert-link" target="_blank"
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
                    <form id="formFeedback">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_home); ?>" />
                        <input type="hidden" name="page_origin" value="home-modal" />
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

    <!-- ══ Scripts ═══════════════════════════════════════════════════════════ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/headhesive@1.2.4/dist/headhesive.min.js"></script>
    <script src="js/libs/tools.min.js"></script>
    <script src="js/theme.min.js"></script>
    <script src="js/vendors/color-modes.js"></script>
    <script src="js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="js/vendors/scrollcue.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.0/dist/feather.min.js"></script>
    <script src="https://unpkg.com/in-view@0.6.1/dist/in-view.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sticky-kit/1.1.3/sticky-kit.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imagesloaded/5.0.0/imagesloaded.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>
    <script src="js/vendors/password.js"></script>
    <script src="js/cookies.js"></script>
    <script src="js/visitor-presence.js"></script>

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

    <!-- Carrossel de depoimentos -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const carousel = document.querySelector(".testimonial-carousel");
            const prevBtn = document.querySelector(".testimonial-prev");
            const nextBtn = document.querySelector(".testimonial-next");
            if (!carousel || !prevBtn || !nextBtn) return;
            const items = document.querySelectorAll(".testimonial-item");
            let currentIndex = 0;

            function updateCarousel() {
                const itemWidth = items[0].offsetWidth + 24;
                carousel.scrollTo({
                    left: currentIndex * itemWidth,
                    behavior: "smooth"
                });
            }
            nextBtn.addEventListener("click", function() {
                if (currentIndex < items.length - 1) {
                    currentIndex++;
                    updateCarousel();
                }
            });
            prevBtn.addEventListener("click", function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });
        });
    </script>

    <!-- ═══════════════════════════════════════════════════════════════════════
         AJAX — Gestão centralizada de CSRF + formulários
         Problema resolvido: quando qualquer endpoint roda o token (new_csrf),
         syncAllCsrf() actualiza TODOS os inputs csrf_token da página ao mesmo
         tempo, evitando que forms subsequentes falhem com 403.
    ════════════════════════════════════════════════════════════════════════════ -->
    <script>
        (function() {
            const BASE = document.body.dataset.basePath || '.';

            /* ── Actualiza todos os inputs csrf_token da página de uma só vez ── */
            function syncAllCsrf(newToken) {
                if (!newToken) return;
                document.querySelectorAll('[name="csrf_token"]').forEach(function(el) {
                    el.value = newToken;
                });
            }

            /* ── Utilitário: envia JSON, mostra resposta, esconde após delay ─── */
            function ajaxPost(url, payload, msgBox, btn, labelDefault, onSuccess) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';

                fetch(BASE + url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        msgBox.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
                        msgBox.textContent = data.message || (data.success ? 'Enviado com sucesso!' :
                            'Erro ao enviar.');
                        msgBox.classList.remove('d-none');

                        /* Roda o token em TODOS os forms da página */
                        if (data.new_csrf) syncAllCsrf(data.new_csrf);

                        if (data.success && typeof onSuccess === 'function') onSuccess();
                    })
                    .catch(function() {
                        msgBox.className = 'alert alert-danger';
                        msgBox.textContent = 'Erro de ligação. Tente novamente.';
                        msgBox.classList.remove('d-none');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.innerHTML = labelDefault;
                        setTimeout(function() {
                            msgBox.classList.add('d-none');
                        }, 7000);
                    });
            }

            /* ════════════════════════════════════════════════════════════════════
               1. Formulário de Contacto (Caixa de mensagem)
            ═════════════════════════════════════════════════════════════════════= */
            var fContact = document.getElementById('form-contact-home');
            if (fContact) {
                fContact.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!fContact.checkValidity()) {
                        fContact.classList.add('was-validated');
                        return;
                    }

                    ajaxPost(
                        '/ajax/contact.php', {
                            csrf: fContact.querySelector('[name="csrf_token"]').value,
                            name: document.getElementById('name_user_home').value.trim(),
                            email: document.getElementById('email_user_home').value.trim(),
                            phone: document.getElementById('tel_user_home').value.trim(),
                            subject: document.getElementById('subject_home').value.trim(),
                            message: document.getElementById('message_home').value.trim()
                        },
                        document.getElementById('contact-msg-home'),
                        document.getElementById('btn-contact-home'),
                        'Enviar mensagem',
                        function() {
                            fContact.reset();
                            fContact.classList.remove('was-validated');
                        }
                    );
                });
            }

            /* ════════════════════════════════════════════════════════════════════
               2. Formulário de Sugestões (inline, aba 2)
            ═════════════════════════════════════════════════════════════════════= */
            var fFeedback = document.getElementById('form-feedback-home');
            if (fFeedback) {
                fFeedback.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!fFeedback.checkValidity()) {
                        fFeedback.classList.add('was-validated');
                        return;
                    }

                    var firstName = document.getElementById('name_fb_home').value.trim();
                    var secondName = document.getElementById('name2_fb_home').value.trim();

                    ajaxPost(
                        '/ajax/feedback.php', {
                            csrf: fFeedback.querySelector('[name="csrf_token"]').value,
                            name: secondName ? firstName + ' ' + secondName : firstName,
                            subject: document.getElementById('subject_fb_home').value.trim(),
                            message: document.getElementById('message_fb_home').value.trim(),
                            page: window.location.pathname
                        },
                        document.getElementById('feedback-msg-home'),
                        document.getElementById('btn-feedback-home'),
                        'Enviar Sugestão',
                        function() {
                            fFeedback.reset();
                            fFeedback.classList.remove('was-validated');
                        }
                    );
                });
            }

            /* ════════════════════════════════════════════════════════════════════
               3. Modal Feedback
            ═════════════════════════════════════════════════════════════════════= */
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

        })();
    </script>

    <script>
        // Animação dos elementos ao rolar
        document.addEventListener("DOMContentLoaded", function() {
            // Observador de interseção para animações
            const observerOptions = {
                threshold: 0.1,
                rootMargin: "0px 0px -50px 0px",
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("animated");
                    }
                });
            }, observerOptions);

            // Observar todos os elementos com data-cue
            document.querySelectorAll("[data-cue]").forEach((el) => {
                observer.observe(el);
            });

            // Contador animado para estatísticas
            const counters = document.querySelectorAll(".counter-animate");

            counters.forEach((counter) => {
                const target = parseInt(counter.textContent);
                const increment = target / 100;
                let current = 0;

                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.ceil(current);
                        setTimeout(updateCounter, 20);
                    } else {
                        counter.textContent = target;
                    }
                };

                // Iniciar quando visível
                const counterObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            counterObserver.unobserve(entry.target);
                        }
                    });
                });

                counterObserver.observe(counter);
            });

            // Efeito de digitação para o título (opcional)
            const title = document.querySelector(".display-5");
            if (title) {
                const text = title.textContent;
                title.textContent = "";

                let i = 0;

                function typeWriter() {
                    if (i < text.length) {
                        title.textContent += text.charAt(i);
                        i++;
                        setTimeout(typeWriter, 50);
                    }
                }

                // Iniciar quando visível
                const titleObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            typeWriter();
                            titleObserver.unobserve(entry.target);
                        }
                    });
                });

                titleObserver.observe(title);
            }
        });
    </script>

    <!-- Script para atualizar ano automaticamente -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Atualizar ano atual
            document.getElementById("current-year").textContent =
                new Date().getFullYear();

            // Inicializar Feather Icons se estiverem sendo usados
            if (typeof feather !== "undefined") {
                feather.replace();
            }

            // Melhorar acessibilidade dos botões de colapso
            document
                .querySelectorAll('[data-bs-toggle="collapse"]')
                .forEach((button) => {
                    button.addEventListener("click", function() {
                        const target = document.querySelector(
                            this.getAttribute("data-bs-target")
                        );
                        const isExpanded = this.getAttribute("aria-expanded") === "true";
                        this.setAttribute("aria-expanded", !isExpanded);

                        // Atualizar ícone
                        const icon = this.querySelector("i[data-feather]");
                        if (icon) {
                            icon.setAttribute(
                                "data-feather",
                                isExpanded ? "chevron-down" : "chevron-up"
                            );
                            if (typeof feather !== "undefined") {
                                feather.replace();
                            }
                        }
                    });
                });
        });
    </script>
</body>

</html>
