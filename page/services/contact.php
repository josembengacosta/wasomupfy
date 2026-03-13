<?php
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('contact');
trackVisitor('/page/services/contact', 'Contacto de Serviços — Wasom Upfy');

$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];
$siteName    = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl     = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum    = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));
$whatsChannel = cfg('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04');
$plans       = getPlans();
$csrf        = getSiteCsrf();

$mode = 'general';
if (isset($_GET['analysis'])   && $_GET['analysis']   === 'free')     $mode = 'free_analysis';
elseif (isset($_GET['consultant']) && $_GET['consultant'] === 'initial') $mode = 'consultant_initial';
elseif (isset($_GET['consultant']) && $_GET['consultant'] === 'talk')    $mode = 'consultant_talk';
elseif (isset($_GET['meeting'])    && $_GET['meeting']    === 'schedule')$mode = 'meeting_schedule';

$modeConfig = [
    'free_analysis' => [
        'badge'=>'Análise Gratuita','badge_cls'=>'bg-success text-white',
        'title'=>'Análise gratuita da tua música',
        'subtitle'=>'A nossa equipa analisa o teu perfil artístico e sugere a melhor estratégia de lançamento — sem compromisso.',
        'icon'=>'fa-solid fa-magnifying-glass-chart','form_title'=>'Solicitar análise gratuita',
        'btn_label'=>'Solicitar Análise','btn_icon'=>'fa-solid fa-paper-plane',
        'has_links'=>true,'has_budget'=>false,'has_date'=>false,'has_meeting'=>false,
        'subject'=>'Análise Gratuita de Música',
        'info_items'=>[
            ['icon'=>'fa-clock','text'=>'Resposta em até 48h úteis'],
            ['icon'=>'fa-shield-halved','text'=>'Sem compromisso nem contratos'],
            ['icon'=>'fa-headphones','text'=>'Análise personalizada do teu estilo'],
            ['icon'=>'fa-chart-line','text'=>'Estratégia baseada nos teus objectivos'],
        ],
    ],
    'consultant_initial' => [
        'badge'=>'Pacote Inicial','badge_cls'=>'bg-light text-dark',
        'title'=>'Impulso Single — Começa a tua jornada',
        'subtitle'=>'Pitching para curadores, optimização de perfil e relatório de performance para artistas a dar os primeiros passos.',
        'icon'=>'fa-solid fa-music','form_title'=>'Falar com consultor — Impulso Single',
        'btn_label'=>'Enviar Pedido','btn_icon'=>'fa-solid fa-paper-plane',
        'has_links'=>true,'has_budget'=>true,'has_date'=>false,'has_meeting'=>false,
        'subject'=>'Consultor — Pacote Impulso Single',
        'info_items'=>[
            ['icon'=>'fa-check','text'=>'Pitching para Curadores de Playlists'],
            ['icon'=>'fa-check','text'=>'Optimização do Perfil Spotify'],
            ['icon'=>'fa-check','text'=>'Estratégia de Hashtags TikTok/Instagram'],
            ['icon'=>'fa-check','text'=>'Relatório Básico de Performance'],
        ],
    ],
    'consultant_talk' => [
        'badge'=>'Campanha 360°','badge_cls'=>'bg-wasomupfy text-white',
        'title'=>'Campanha 360° — Máximo alcance',
        'subtitle'=>'Tráfego pago e orgânico. Ads no Meta e Google, pitching editorial, criação de criativos e relatórios semanais.',
        'icon'=>'fa-solid fa-fire','form_title'=>'Agendar consulta — Campanha 360°',
        'btn_label'=>'Agendar Consulta','btn_icon'=>'fa-solid fa-calendar-check',
        'has_links'=>true,'has_budget'=>true,'has_date'=>true,'has_meeting'=>false,
        'subject'=>'Consultor — Campanha 360°',
        'info_items'=>[
            ['icon'=>'fa-check','text'=>'Gestão de Ads (Meta + Google)'],
            ['icon'=>'fa-check','text'=>'Pitching Editorial & Blogs'],
            ['icon'=>'fa-check','text'=>'Criação de Criativos (Vídeo/Reels)'],
            ['icon'=>'fa-check','text'=>'Relatório Avançado Semanal'],
        ],
    ],
    'meeting_schedule' => [
        'badge'=>'Reunião Executiva','badge_cls'=>'bg-dark text-white',
        'title'=>'Gestão de Label — Agendar Reunião',
        'subtitle'=>'Planeamento estratégico de longo prazo para o teu catálogo. Agenda uma reunião executiva com a nossa equipa.',
        'icon'=>'fa-solid fa-handshake','form_title'=>'Agendar reunião executiva',
        'btn_label'=>'Confirmar Agendamento','btn_icon'=>'fa-solid fa-calendar-check',
        'has_links'=>false,'has_budget'=>true,'has_date'=>true,'has_meeting'=>true,
        'subject'=>'Reunião — Gestão de Label',
        'info_items'=>[
            ['icon'=>'fa-check','text'=>'Planeamento Trimestral de Releases'],
            ['icon'=>'fa-check','text'=>'Gestão de Branding & Identidade'],
            ['icon'=>'fa-check','text'=>'Consultoria de Carreira Dedicada'],
            ['icon'=>'fa-check','text'=>'Acesso Prioritário à Equipa'],
        ],
    ],
    'general' => [
        'badge'=>'Atendimento Humanizado','badge_cls'=>'bg-wasomupfy text-white',
        'title'=>'Como podemos ajudar?',
        'subtitle'=>'Tens dúvidas sobre distribuição, queres impulsionar a tua carreira ou precisas de suporte técnico? Estamos prontos.',
        'icon'=>'fa-solid fa-envelope-open-text','form_title'=>'Envia-nos uma mensagem',
        'btn_label'=>'Enviar Mensagem','btn_icon'=>'fa-solid fa-paper-plane',
        'has_links'=>false,'has_budget'=>false,'has_date'=>false,'has_meeting'=>false,
        'subject'=>'',
        'info_items'=>[
            ['icon'=>'fa-clock','text'=>'Resposta em até 48h úteis'],
            ['icon'=>'fa-location-dot','text'=>'Luanda, Angola'],
            ['icon'=>'fa-calendar-days','text'=>'Seg – Sex: 08h às 17h'],
            ['icon'=>'fa-shield-halved','text'=>'Os teus dados estão seguros'],
        ],
    ],
];

$cfg     = $modeConfig[$mode];
$timeSlots = ['09:00','10:00','11:00','14:00','15:00','16:00','17:00'];
$minDate = date('Y-m-d', strtotime('+1 day'));
$maxDate = date('Y-m-d', strtotime('+60 days'));
$seoTitle = $cfg['title'] . ' — ' . cfg('site_name','Wasom Upfy');
$seoDesc  = $cfg['subtitle'];
?>
<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF009D" />
    <title><?php echo htmlspecialchars($seoTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoDesc); ?>" />
    <script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.body.classList.add('loaded');
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
    .form-section {
        margin-top: -60px;
        position: relative;
        z-index: 10;
    }

    .form-card {
        border-radius: 16px;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .15);
    }

    .time-slots {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .time-slot {
        padding: 8px 16px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: .875rem;
        transition: all .18s;
        background: transparent;
        color: var(--bs-body-color);
    }

    .time-slot:hover {
        border-color: #FF009D;
        color: #FF009D;
    }

    .time-slot.selected {
        border-color: #FF009D;
        background: #FF009D;
        color: #fff;
    }

    .mode-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .mode-btn {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        border: 2px solid transparent;
        transition: all .2s;
    }

    .mode-btn:not(.active) {
        border-color: #dee2e6;
        color: var(--bs-body-color);
    }

    .mode-btn:not(.active):hover {
        border-color: #FF009D;
        color: #FF009D;
    }

    .mode-btn.active {
        background: #FF009D;
        color: #fff;
        border-color: #FF009D;
    }

    .meeting-type-card {
        border: 2px solid #dee2e6;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
    }

    .meeting-type-card:hover,
    .meeting-type-card.selected {
        border-color: #FF009D;
    }

    .meeting-type-card.selected {
        background: rgba(255, 0, 141, .06);
    }

    .meeting-type-card .icon {
        font-size: 2rem;
        color: #FF009D;
        margin-bottom: 8px;
    }

    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }

    .info-item .icon-wrap {
        width: 36px;
        height: 36px;
        background: rgba(255, 0, 141, .10);
        color: #FF009D;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .form-success {
        display: none;
        text-align: center;
        padding: 48px 24px;
    }

    .form-success.show {
        display: block;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: rgba(25, 135, 84, .1);
        color: #198754;
        border-radius: 50%;
        font-size: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }
    </style>
</head>

<body>
    <div class="preloader"><img src="../../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo"
            width="90" height="90" alt="A carregar" /></div>

    <!-- Navbar -->
    <header>
        <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
            <div class="container px-3">
                <a class="navbar-brand" href="../../home"><img src="../../assets/img/brand/wasomupfy_brand.png"
                        width="65" class="img-logo" height="60" alt="Logo <?php echo $siteName; ?>" /></a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button"><i class="bi bi-list"></i></button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width:20rem">
                    <div class="offcanvas-header">
                        <a href="../../home"><img width="65" src="../../assets/img/brand/wasomupfy_brand.png"
                                alt="Logo <?php echo $siteName; ?>" /></a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item"><a class="nav-link" href="../../home">Início</a></li>
                            <li class="nav-item"><a class="nav-link" href="../../about">Sobre</a></li>
                            <li class="nav-item"><a class="nav-link" href="../../blog/" target="_blank"
                                    rel="external">Blogue</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" data-bs-toggle="dropdown">Planos <i
                                        data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-md">
                                    <?php
                                    $navIcons=['single'=>'fa-music','album'=>'fa-compact-disc','artist'=>'fa-microphone-lines','label'=>'fa-tags'];
                                    foreach($plans as $p):
                                        $nSlug=$p['slug_plan'];$nIcon=$navIcons[$nSlug]??'fa-music';
                                        $nPrc=number_format($p['price_plan'],0,',','.');
                                        $nPer=$p['type_plan']==='subscription'?'/ano':'';
                                    ?>
                                    <a class="dropdown-item mb-3 text-body" href="../../plan/<?php echo $nSlug;?>">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid <?php echo $nIcon;?> text-wasomupfy fs-3"
                                                style="width:35px"></i>
                                            <div class="ms-3 lh-1">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']);?></h5>
                                                <p class="mb-0 fs-6"><?php echo htmlspecialchars($p['name_plan']);?> —
                                                    <?php echo $nPrc;?> Kz<?php echo $nPer;?></p>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach;?>
                                    <a class="dropdown-item mb-3 text-body" href="../../plan/all-plans">
                                        <div class="d-flex align-items-center"><i
                                                class="fa-solid fa-layer-group text-wasomupfy fs-3"
                                                style="width:35px"></i>
                                            <div class="ms-3 lh-1">
                                                <h5 class="mb-1">Todos os planos</h5>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" data-bs-toggle="dropdown">Páginas <i
                                        data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a class="dropdown-item" href="../../blog/">Novidades</a>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a class="dropdown-item" href="../../about#nossamarca">A nossa marca</a>
                                                <a class="dropdown-item" href="../../partnership">Parcerias</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Serviços</div>
                                                <a class="dropdown-item" href="music-distribution">Distribuição de
                                                    música</a>
                                                <a class="dropdown-item" href="music-promotion">Promoção de música <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a class="dropdown-item active" href="contact">Contacto de Serviços</a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Contactos</div>
                                                <a class="dropdown-item"
                                                    href="https://www.facebook.com/m.me/2007900989425052"
                                                    target="_blank" rel="external noopener noreferrer">Atendimento</a>
                                                <a class="dropdown-item" href="../../contact">Contacta-nos</a>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mt-3 mt-lg-0">
                                                <div class="dropdown-header">Ajuda</div>
                                                <a class="dropdown-item" href="../support/help">Ajuda <span
                                                        class="badge bg-success">Novo</span></a>
                                                <a class="dropdown-item" href="../support/faq">FAQ</a>
                                                <a class="dropdown-item" href="../support/support">Suporte técnico</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="../../resources">Recursos</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" data-bs-toggle="dropdown">Contactar <i
                                        data-feather="chevron-down"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../../contact">Caixa de mensagem</a></li>
                                    <?php if(cfg('support_email')):?><li><a class="dropdown-item"
                                            href="mailto:<?php echo htmlspecialchars(cfg('support_email'));?>"><?php echo htmlspecialchars(cfg('support_email'));?></a>
                                    </li><?php endif;?>
                                    <?php if($whatsNum):?><li><a class="dropdown-item"
                                            href="https://api.whatsapp.com/send/?phone=<?php echo $whatsNum;?>&text&type=phone_number&app_absent=0">WhatsApp</a>
                                    </li><?php endif;?>
                                </ul>
                            </li>
                        </ul>
                        <div class="mt-3 mt-lg-0 d-flex align-items-center">
                            <a href="/wasomupfy/login" class="btn btn-secondary mx-2">Entrar <i
                                    data-feather="log-in"></i></a>
                            <?php if($canRegister):?><a href="/wasomupfy/register"
                                class="btn btn-wasomupfy">Inscreva-se</a><?php else:?><span
                                class="btn btn-secondary disabled">Inscrições fechadas</span><?php endif;?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero -->
        <section class="contact-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/contact.png" alt="Contacto <?php echo $siteName;?>"
                loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10" data-cue="fadeIn">
                        <span class="badge <?php echo $cfg['badge_cls'];?> mb-3 fs-6 px-3 py-2 rounded-pill">
                            <i class="<?php echo $cfg['icon'];?> me-1"></i>
                            <?php echo htmlspecialchars($cfg['badge']);?>
                        </span>
                        <h1 class="display-4 fw-bold text-white-stable mb-3">
                            <?php echo htmlspecialchars($cfg['title']);?></h1>
                        <p class="lead text-white-stable opacity-90 mb-0 mx-auto" style="max-width:600px">
                            <?php echo htmlspecialchars($cfg['subtitle']);?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mode selector -->
        <section class="py-3 border-bottom">
            <div class="container">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.05em">Tipo de
                        contacto:</small>
                    <div class="mode-selector">
                        <a href="contact" class="mode-btn <?php echo $mode==='general'?'active':'';?>">Geral</a>
                        <a href="contact?analysis=free"
                            class="mode-btn <?php echo $mode==='free_analysis'?'active':'';?>"><i
                                class="fa-solid fa-magnifying-glass-chart me-1"></i> Análise Gratuita</a>
                        <a href="contact?consultant=initial"
                            class="mode-btn <?php echo $mode==='consultant_initial'?'active':'';?>"><i
                                class="fa-solid fa-music me-1"></i> Impulso Single</a>
                        <a href="contact?consultant=talk"
                            class="mode-btn <?php echo $mode==='consultant_talk'?'active':'';?>"><i
                                class="fa-solid fa-fire me-1"></i> Campanha 360°</a>
                        <a href="contact?meeting=schedule"
                            class="mode-btn <?php echo $mode==='meeting_schedule'?'active':'';?>"><i
                                class="fa-solid fa-handshake me-1"></i> Gestão de Label</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form -->
        <section class="py-6 bg-light-100">
            <div class="container">
                <div class="row g-5 align-items-start">

                    <!-- Lateral info -->
                    <div class="col-lg-4" data-cue="slideInLeft">
                        <div class="sticky-top" style="top:90px">
                            <h4 class="fw-bold mb-4"><?php echo htmlspecialchars($cfg['form_title']);?></h4>
                            <?php foreach($cfg['info_items'] as $item):?>
                            <div class="info-item">
                                <div class="icon-wrap"><i class="fa-solid <?php echo $item['icon'];?>"></i></div>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($item['text']);?></p>
                            </div>
                            <?php endforeach;?>
                            <?php if($mode!=='general'):?>
                            <div class="card border-0 bg-wasomupfy bg-opacity-10 mt-4 p-3 rounded-3">
                                <p class="small mb-1 fw-semibold text-wasomupfy"><i class="fa-solid fa-clock me-1"></i>
                                    Tempo de resposta</p>
                                <p class="small mb-0 text-muted">
                                    <?php echo in_array($mode,['consultant_talk','meeting_schedule'])?'Confirmamos o agendamento em até <strong>24h úteis</strong>.':'Respondemos em até <strong>48h úteis</strong>.';?>
                                </p>
                            </div>
                            <?php endif;?>
                            <?php if($whatsNum):?>
                            <div class="mt-4">
                                <p class="small text-muted mb-2">Preferes falar directamente?</p>
                                <a href="https://wa.me/<?php echo $whatsNum;?>?text=Olá!%20Interesse%20em%20<?php echo rawurlencode($cfg['subject']?:'saber mais');?>"
                                    target="_blank" rel="noopener noreferrer"
                                    class="btn btn-success btn-sm rounded-pill px-4 w-100">
                                    <i class="fa-brands fa-whatsapp me-2"></i> Abrir WhatsApp
                                </a>
                            </div>
                            <?php endif;?>
                        </div>
                    </div>

                    <!-- Form card -->
                    <div class="col-lg-8" data-cue="fadeIn">
                        <div class="card form-card border-0 p-4 p-lg-5">

                            <!-- Sucesso -->
                            <div class="form-success" id="formSuccess">
                                <div class="success-icon"><i class="fa-solid fa-check"></i></div>
                                <h3 class="fw-bold mb-2">Recebemos o teu pedido!</h3>
                                <p class="text-muted mb-1" id="successMessage"></p>
                                <p class="text-muted small">Referência: <strong id="successRef"></strong></p>
                                <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
                                    <a href="music-promotion" class="btn btn-outline-wasomupfy"><i
                                            class="fa-solid fa-arrow-left me-1"></i> Voltar à Promoção</a>
                                    <?php if($whatsNum):?><a href="https://wa.me/<?php echo $whatsNum;?>"
                                        target="_blank" rel="noopener noreferrer" class="btn btn-success"><i
                                            class="fa-brands fa-whatsapp me-1"></i> WhatsApp</a><?php endif;?>
                                </div>
                            </div>

                            <form id="serviceContactForm" novalidate class="needs-validation">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf);?>" />
                                <input type="hidden" name="mode" value="<?php echo $mode;?>" />

                                <!-- Dados pessoais -->
                                <h6 class="text-uppercase fw-bold small text-muted mb-3"><i
                                        class="fa-solid fa-user me-2 text-wasomupfy"></i>Dados de Contacto</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="fc_name" class="form-label fw-semibold small">Nome Completo <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control py-3" id="fc_name" name="name"
                                            placeholder="O teu nome" required minlength="2" maxlength="150" />
                                        <div class="invalid-feedback">Por favor insere o teu nome.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fc_email" class="form-label fw-semibold small">E-mail <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control py-3" id="fc_email" name="email"
                                            placeholder="nome@exemplo.com" required />
                                        <div class="invalid-feedback">E-mail inválido.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fc_phone" class="form-label fw-semibold small">WhatsApp /
                                            Telefone</label>
                                        <input type="tel" class="form-control py-3" id="fc_phone" name="phone"
                                            placeholder="+244 9xx xxx xxx" />
                                    </div>
                                    <?php if($mode!=='general'):?>
                                    <div class="col-md-6">
                                        <label for="fc_artist"
                                            class="form-label fw-semibold small"><?php echo $mode==='meeting_schedule'?'Nome da Label':'Nome Artístico';?>
                                            <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control py-3" id="fc_artist" name="artist_name"
                                            placeholder="<?php echo $mode==='meeting_schedule'?'Nome da tua Label':'Como te chamas artisticamente';?>"
                                            required />
                                        <div class="invalid-feedback">Campo obrigatório.</div>
                                    </div>
                                    <?php endif;?>
                                </div>

                                <!-- Perfil artístico -->
                                <?php if($mode!=='general'):?>
                                <h6 class="text-uppercase fw-bold small text-muted mb-3"><i
                                        class="fa-solid fa-music me-2 text-wasomupfy"></i>Perfil Artístico</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="fc_genre" class="form-label fw-semibold small">Género
                                            Musical</label>
                                        <select class="form-select py-3" id="fc_genre" name="genre">
                                            <option value="">Selecionar género...</option>
                                            <?php foreach(['Afropop','Semba','Kizomba','Kuduro','Afrobeat','R&B / Soul','Hip-Hop / Trap','Reggaeton','Pop','Dance / Electronic','Gospel / Espiritual','Outro'] as $g):?>
                                            <option value="<?php echo $g;?>"><?php echo $g;?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                    <?php if($mode==='meeting_schedule'):?>
                                    <div class="col-md-6">
                                        <label for="fc_artists_num" class="form-label fw-semibold small">Nº de Artistas
                                            na Label</label>
                                        <input type="number" class="form-control py-3" id="fc_artists_num"
                                            name="num_artists" placeholder="ex: 5" min="1" max="500" />
                                    </div>
                                    <?php endif;?>
                                    <?php if($cfg['has_budget']):?>
                                    <div class="col-md-<?php echo $mode==='meeting_schedule'?'12':'6';?>">
                                        <label for="fc_budget" class="form-label fw-semibold small">Orçamento
                                            Disponível</label>
                                        <select class="form-select py-3" id="fc_budget" name="budget_range">
                                            <option value="">Selecionar faixa...</option>
                                            <option value="Menos de 50.000 Kz">Menos de 50.000 Kz</option>
                                            <option value="50.000 – 100.000 Kz">50.000 – 100.000 Kz</option>
                                            <option value="100.000 – 250.000 Kz">100.000 – 250.000 Kz</option>
                                            <option value="250.000 – 500.000 Kz">250.000 – 500.000 Kz</option>
                                            <option value="Acima de 500.000 Kz">Acima de 500.000 Kz</option>
                                            <?php if($mode==='meeting_schedule'):?><option
                                                value="Acima de 1.000.000 Kz">Acima de 1.000.000 Kz</option>
                                            <?php endif;?>
                                            <option value="A definir">A definir / Prefiro discutir</option>
                                        </select>
                                    </div>
                                    <?php endif;?>
                                </div>
                                <?php endif;?>

                                <!-- Links sociais -->
                                <?php if($cfg['has_links']):?>
                                <h6 class="text-uppercase fw-bold small text-muted mb-3"><i
                                        class="fa-solid fa-link me-2 text-wasomupfy"></i>Links das Redes Sociais</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="fc_spotify" class="form-label fw-semibold small"><i
                                                class="fa-brands fa-spotify text-success me-1"></i> Spotify</label>
                                        <input type="url" class="form-control py-3" id="fc_spotify"
                                            name="links[spotify]" placeholder="https://open.spotify.com/..." />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fc_instagram" class="form-label fw-semibold small"><i
                                                class="fa-brands fa-instagram text-danger me-1"></i> Instagram</label>
                                        <input type="url" class="form-control py-3" id="fc_instagram"
                                            name="links[instagram]" placeholder="https://instagram.com/..." />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fc_tiktok" class="form-label fw-semibold small"><i
                                                class="fa-brands fa-tiktok me-1"></i> TikTok</label>
                                        <input type="url" class="form-control py-3" id="fc_tiktok" name="links[tiktok]"
                                            placeholder="https://tiktok.com/@..." />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fc_youtube" class="form-label fw-semibold small"><i
                                                class="fa-brands fa-youtube text-danger me-1"></i> YouTube</label>
                                        <input type="url" class="form-control py-3" id="fc_youtube"
                                            name="links[youtube]" placeholder="https://youtube.com/@..." />
                                    </div>
                                </div>
                                <?php endif;?>

                                <!-- Agendamento -->
                                <?php if($cfg['has_date']):?>
                                <h6 class="text-uppercase fw-bold small text-muted mb-3"><i
                                        class="fa-solid fa-calendar-days me-2 text-wasomupfy"></i>Preferência de
                                    Agendamento</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="fc_date" class="form-label fw-semibold small">Data Preferencial
                                            <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control py-3" id="fc_date" name="preferred_date"
                                            min="<?php echo $minDate;?>" max="<?php echo $maxDate;?>" required />
                                        <div class="form-text">Seg – Sex, próximos 60 dias</div>
                                        <div class="invalid-feedback">Selecciona uma data válida.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Horário Preferencial <span
                                                class="text-danger">*</span></label>
                                        <div class="time-slots mt-1" id="timeSlots">
                                            <?php foreach($timeSlots as $slot):?>
                                            <button type="button" class="time-slot"
                                                data-time="<?php echo $slot;?>"><?php echo $slot;?></button>
                                            <?php endforeach;?>
                                        </div>
                                        <input type="hidden" id="fc_time" name="preferred_time" />
                                        <div class="form-text text-danger d-none" id="timeError">Por favor selecciona um
                                            horário.</div>
                                    </div>
                                </div>
                                <?php endif;?>

                                <!-- Tipo reunião -->
                                <?php if($cfg['has_meeting']):?>
                                <h6 class="text-uppercase fw-bold small text-muted mb-3"><i
                                        class="fa-solid fa-video me-2 text-wasomupfy"></i>Tipo de Reunião</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="meeting-type-card" data-type="online" id="mtOnline">
                                            <div class="icon"><i class="fa-solid fa-video"></i></div>
                                            <h6 class="fw-bold mb-1">Online</h6>
                                            <small class="text-muted">Via Google Meet ou Zoom</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="meeting-type-card" data-type="presencial" id="mtPresencial">
                                            <div class="icon"><i class="fa-solid fa-building"></i></div>
                                            <h6 class="fw-bold mb-1">Presencial</h6>
                                            <small class="text-muted">Escritório — Luanda, Angola</small>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="fc_meeting_type" name="meeting_type" />
                                <div class="form-text text-danger d-none mb-3" id="meetingTypeError">Por favor
                                    selecciona o tipo de reunião.</div>
                                <?php endif;?>

                                <!-- Assunto (geral) -->
                                <?php if($mode==='general'):?>
                                <div class="mb-3">
                                    <label for="fc_subject" class="form-label fw-semibold small">Motivo do Contacto
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select py-3" id="fc_subject" name="subject" required>
                                        <option value="" disabled selected>Selecionar...</option>
                                        <option>Dúvidas sobre Distribuição</option>
                                        <option>Orçamento para Promoção</option>
                                        <option>Suporte Técnico / Conta</option>
                                        <option>Financeiro / Royalties</option>
                                        <option>Imprensa e Parcerias</option>
                                        <option>Outros</option>
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona um assunto.</div>
                                </div>
                                <?php endif;?>

                                <!-- Mensagem -->
                                <div class="mb-4">
                                    <label for="fc_message" class="form-label fw-semibold small">
                                        <?php $msgLabels=['free_analysis'=>'Conta-nos sobre a tua música','consultant_initial'=>'Objectivos e contexto','consultant_talk'=>'Objectivos da campanha','meeting_schedule'=>'Contexto da Label','general'=>'Mensagem'];echo $msgLabels[$mode];?>
                                    </label>
                                    <textarea class="form-control" id="fc_message" name="message" rows="5"
                                        maxlength="2000"
                                        placeholder="<?php $ph=['free_analysis'=>'Link da tua música, género, público-alvo...','consultant_initial'=>'Objectivos de alcance, data de lançamento prevista...','consultant_talk'=>'Plataformas prioritárias, orçamento estimado em ads...','meeting_schedule'=>'Descreve a label, artistas activos, estratégia actual...','general'=>'Como podemos ajudar?'];echo htmlspecialchars($ph[$mode]);?>"></textarea>
                                    <div class="form-text text-end"><span id="msgCounter">0</span>/2000</div>
                                </div>

                                <input type="text" name="honeypot" style="display:none" tabindex="-1"
                                    autocomplete="off" />
                                <div id="formAlert" class="alert d-none mb-3" role="alert"></div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-wasomupfy btn-lg py-3 fw-bold" id="btnSubmit">
                                        <?php echo htmlspecialchars($cfg['btn_label']);?> <i
                                            class="<?php echo $cfg['btn_icon'];?> ms-2"></i>
                                    </button>
                                </div>
                                <p class="text-center text-muted small mt-3 mb-0"><i class="fa-solid fa-lock me-1"></i>
                                    Os teus dados são tratados com total confidencialidade.</p>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <?php if($mode==='general'):?>
        <section class="py-5 bg-light-100 border-top">
            <div class="container text-center">
                <h3 class="fw-bold mb-4">Dúvidas rápidas?</h3>
                <div class="row justify-content-center g-3">
                    <div class="col-auto"><a href="../support/faq#royalties"
                            class="btn btn-white shadow-sm rounded-pill px-4">Quando recebo os Royalties?</a></div>
                    <div class="col-auto"><a href="../support/faq#formato"
                            class="btn btn-white shadow-sm rounded-pill px-4">Formato de Áudio (WAV)</a></div>
                    <div class="col-auto"><a href="../support/faq#prazo"
                            class="btn btn-white shadow-sm rounded-pill px-4">Prazo de Lançamento</a></div>
                </div>
            </div>
        </section>
        <?php endif;?>

        <?php if(in_array($mode,['general','meeting_schedule'])):?>
        <section id="mapa" class="py-5 bg-light-100 border-top">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <h4 class="fw-bold mb-4 text-center">Localização — Luanda, Angola</h4>
                        <div class="rounded-4 overflow-hidden shadow-lg" style="height:320px">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126115.12768560378!2d13.155827013854156!3d-8.83832819853381!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1a51f15cdc8d2c7d%3A0x850c1c5c5b29db52!2sLuanda%2C%20Angola!5e0!3m2!1spt-PT!2sbr!4v1700000000000!5m2!1spt-PT!2sbr"
                                width="100%" height="100%" style="border:0" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif;?>
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
                            <img src="../../assets/img/brand/wasomupfy_brand.png" alt="<?php echo $siteName; ?>"
                                width="65" class="img-logo" height="60" />
                        </a>
                        <p class="lead text-muted small mb-4">
                            Levamos a música angolana para o mundo. Distribuição digital, marketing e gestão de carreira
                            num só lugar.
                        </p>
                        <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
                            <?php if (cfg('instagram_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                                rel="external noopener noreferrer"
                                aria-label="Instagram da <?php echo $siteName; ?> (abre em nova janela)"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-instagram"></i><span class="visually-hidden">Instagram</span>
                            </a>
                            <?php endif; ?>
                            <?php if (cfg('facebook_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                                rel="external noopener noreferrer"
                                aria-label="Facebook da <?php echo $siteName; ?> (abre em nova janela)"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-facebook-f"></i><span class="visually-hidden">Facebook</span>
                            </a>
                            <?php endif; ?>
                            <?php if (cfg('youtube_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                                rel="external noopener noreferrer"
                                aria-label="YouTube da <?php echo $siteName; ?> (abre em nova janela)"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-youtube"></i><span class="visually-hidden">YouTube</span>
                            </a>
                            <?php endif; ?>
                            <?php if (cfg('linkedin_url')): ?>
                            <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                                rel="external noopener noreferrer"
                                aria-label="LinkedIn da <?php echo $siteName; ?> (abre em nova janela)"
                                class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                                <i class="fa-brands fa-linkedin-in"></i><span class="visually-hidden">LinkedIn</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($whatsNum): ?>
                            <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                                rel="external noopener noreferrer"
                                aria-label="WhatsApp da <?php echo $siteName; ?> (abre em nova janela)"
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
                                <?php if ($whatsNum): ?>
                                <a href="https://wa.me/<?php echo $whatsNum; ?>"
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


    <!-- Scripts -->
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
    <script src="https://cdn.jsdelivr.net/npm/jarallax@2.2.0/dist/jarallax.min.js"></script>
    <script src="../../js/sw-register.js"></script>

    <script>
    (function() {
        'use strict';
        if (typeof feather !== 'undefined') feather.replace();
        var mode = '<?php echo $mode;?>';
        var hasDate = <?php echo $cfg['has_date']   ?'true':'false';?>;
        var hasMeeting = <?php echo $cfg['has_meeting'] ?'true':'false';?>;
        var selTime = '',
            selMeeting = '';
        var fcTime = document.getElementById('fc_time');
        var timeError = document.getElementById('timeError');
        var fcMT = document.getElementById('fc_meeting_type');
        var mtError = document.getElementById('meetingTypeError');

        // Time slots
        document.querySelectorAll('.time-slot').forEach(function(b) {
            b.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(function(x) {
                    x.classList.remove('selected');
                });
                b.classList.add('selected');
                selTime = b.dataset.time;
                if (fcTime) fcTime.value = selTime;
                if (timeError) timeError.classList.add('d-none');
            });
        });

        // Meeting type
        document.querySelectorAll('.meeting-type-card').forEach(function(c) {
            c.addEventListener('click', function() {
                document.querySelectorAll('.meeting-type-card').forEach(function(x) {
                    x.classList.remove('selected');
                });
                c.classList.add('selected');
                selMeeting = c.dataset.type;
                if (fcMT) fcMT.value = selMeeting;
                if (mtError) mtError.classList.add('d-none');
            });
        });

        // Block weekends
        var fcDate = document.getElementById('fc_date');
        if (fcDate) fcDate.addEventListener('change', function() {
            var day = new Date(this.value).getUTCDay();
            this.setCustomValidity(day === 0 || day === 6 ? 'Selecciona um dia útil (Seg–Sex).' : '');
        });

        // Char counter
        var msgArea = document.getElementById('fc_message');
        var ctr = document.getElementById('msgCounter');
        if (msgArea && ctr) msgArea.addEventListener('input', function() {
            ctr.textContent = this.value.length;
        });

        // Submit
        var form = document.getElementById('serviceContactForm');
        var btn = document.getElementById('btnSubmit');
        var alert = document.getElementById('formAlert');
        var success = document.getElementById('formSuccess');
        var defLabel = btn ? btn.innerHTML : '';

        if (form) form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (hasDate && !selTime) {
                if (timeError) timeError.classList.remove('d-none');
                document.getElementById('timeSlots')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return;
            }
            if (hasMeeting && !selMeeting) {
                if (mtError) mtError.classList.remove('d-none');
                return;
            }
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                form.querySelector(':invalid')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return;
            }
            if (form.querySelector('[name="honeypot"]').value) return;

            var links = {};
            ['spotify', 'instagram', 'tiktok', 'youtube'].forEach(function(n) {
                var el = document.getElementById('fc_' + n);
                if (el && el.value.trim()) links[n] = el.value.trim();
            });

            var payload = {
                csrf: form.querySelector('[name="csrf_token"]').value,
                mode: mode,
                name: document.getElementById('fc_name')?.value.trim() || '',
                email: document.getElementById('fc_email')?.value.trim() || '',
                phone: document.getElementById('fc_phone')?.value.trim() || '',
                artist_name: document.getElementById('fc_artist')?.value.trim() || '',
                genre: document.getElementById('fc_genre')?.value || '',
                num_artists: document.getElementById('fc_artists_num')?.value || '',
                budget_range: document.getElementById('fc_budget')?.value || '',
                preferred_date: document.getElementById('fc_date')?.value || '',
                preferred_time: selTime,
                meeting_type: selMeeting,
                message: document.getElementById('fc_message')?.value.trim() || '',
                subject: document.getElementById('fc_subject')?.value || '',
                links: Object.keys(links).length ? links : null,
            };

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';
            if (alert) alert.classList.add('d-none');

            fetch('/wasomupfy/ajax/service-contact.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (res.success) {
                        form.style.display = 'none';
                        document.getElementById('successMessage').textContent = res.message;
                        document.getElementById('successRef').textContent = res.contact_id;
                        success.classList.add('show');
                        success.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    } else {
                        if (alert) {
                            alert.className = 'alert alert-danger';
                            alert.textContent = res.message;
                            alert.classList.remove('d-none');
                        }
                    }
                })
                .catch(function() {
                    if (alert) {
                        alert.className = 'alert alert-danger';
                        alert.textContent = 'Erro de ligação. Tenta novamente.';
                        alert.classList.remove('d-none');
                    }
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = defLabel;
                });
        });
    })();
    </script>
</body>

</html>