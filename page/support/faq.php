<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Perguntas Frequentes (FAQ)
// Arquivo: page/support/faq.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('faq');
trackVisitor('/page/support/faq', 'Perguntas Frequentes — Wasom Upfy');

$plans       = getPlans();
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];

$siteName  = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl   = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum  = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));

$youtubeId = cfg('youtube_tutorial_id', '');

$csrf_page = getSiteCsrf();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords"
        content="Perguntas frequentes <?php echo $siteName; ?>, cadastro de artistas, royalties, distribuição, suporte" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Perguntas Frequentes" />
    <meta property="og:description"
        content="Perguntas frequentes sobre a plataforma <?php echo $siteName; ?>: cadastro, royalties, distribuição, planos e suporte." />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/support/faq" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Perguntas Frequentes</title>

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
    <link rel="stylesheet" href="../../css/faq-web.css" />
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
                                                <a title="Tutorial" class="dropdown-item" href="tutorial">Tutorial
                                                    <span class="badge bg-success">Novo</span></a>
                                                <a title="Suporte técnico" class="dropdown-item" href="support">Suporte
                                                    técnico</a>
                                                <a title="Perguntas frequentes" class="dropdown-item active"
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

        <!-- Hero -->
        <section class="faq-hero jarallax position-relative overflow-hidden py-5" data-jarallax data-speed="0.4">
            <img class="jarallax-img" src="../../assets/img/theme/faq.png"
                alt="Perguntas frequentes <?php echo $siteName; ?>" loading="lazy" />
            <div class="hero-overlay"></div>
            <div class="container position-relative z-index-2 py-6">
                <div class="row justify-content-center text-center">
                    <div class="col-xl-8 col-lg-10 text-center" data-cue="fadeIn">
                        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../home" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">FAQ</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 mb-4 text-white-stable fw-bold" data-i18n="faq_title">
                            Perguntas Frequentes
                        </h1>
                        <p class="lead text-white-stable mb-4 opacity-90" data-i18n="faq_description">
                            Encontre respostas para as perguntas mais comuns sobre a plataforma
                            <?php echo $siteName; ?>.
                            Não encontrou o que procurava? Entre em contacto com o nosso
                            <a href="support" title="Suporte" class="text-secondary">suporte</a>!
                        </p>
                        <p class="update-date" data-i18n="faq_update_date">
                            Última actualização: 14 de Fevereiro de 2026
                        </p>
                        <a href="#faq-content" class="btn btn-wasomupfy btn-lg mt-2 smooth-scroll">
                            Ver perguntas <i class="bi bi-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Conteúdo FAQ -->
        <section id="faq-content" class="my-xl-4 py-5 bg-light-100">
            <div class="container mb-xl-3" data-cue="fadeIn">
                <div class="row row-cols-1 row-cols-md-3 gy-4">

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="faq.pdf" download>
                            <i class="bi bi-file-earmark-pdf"></i>
                            <span data-i18n="download_pdf">Baixar em PDF</span>
                        </a>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-bar-fill" id="progressBar"></div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="search-bar fade-in-custom">
                        <input type="text" id="faqSearch" class="form-control" placeholder="Pesquisar perguntas..."
                            data-i18n-placeholder="search_placeholder" onkeyup="searchFAQ()" />
                    </div>

                    <!-- Índice lateral -->
                    <nav class="nav-index fade-in-custom">
                        <h3 data-i18n="index_title">Índice</h3>
                        <ul class="list-unstyled">
                            <li class="index-item index-cat"><strong>Conta &amp; Acesso</strong></li>
                            <li class="index-item" id="index-faq1"><a href="#faq1" data-i18n="index_faq1">Como me
                                    cadastro?</a></li>
                            <li class="index-item" id="index-faq2"><a href="#faq2" data-i18n="index_faq2">Esqueci a
                                    minha senha</a></li>
                            <li class="index-item" id="index-faq3"><a href="#faq3" data-i18n="index_faq3">Verificação de
                                    e-mail</a></li>
                            <li class="index-item" id="index-faq4"><a href="#faq4" data-i18n="index_faq4">Como activar o
                                    2FA?</a></li>
                            <li class="index-item" id="index-faq5"><a href="#faq5" data-i18n="index_faq5">Desactivar a
                                    conta</a></li>
                            <li class="index-item" id="index-faq6"><a href="#faq6" data-i18n="index_faq6">Eliminar a
                                    conta</a></li>
                            <li class="index-item" id="index-faq7"><a href="#faq7" data-i18n="index_faq7">Reactivar
                                    conta</a></li>
                            <li class="index-item index-cat mt-2"><strong>Distribuição</strong></li>
                            <li class="index-item" id="index-faq8"><a href="#faq8" data-i18n="index_faq8">Prazos para as
                                    lojas</a></li>
                            <li class="index-item" id="index-faq9"><a href="#faq9" data-i18n="index_faq9">Formatos de
                                    áudio</a></li>
                            <li class="index-item" id="index-faq10"><a href="#faq10" data-i18n="index_faq10">Requisitos
                                    da capa</a></li>
                            <li class="index-item" id="index-faq11"><a href="#faq11" data-i18n="index_faq11">Lojas
                                    disponíveis</a></li>
                            <li class="index-item" id="index-faq12"><a href="#faq12" data-i18n="index_faq12">ISRC e
                                    metadados</a></li>
                            <li class="index-item index-cat mt-2"><strong>Planos &amp; Preços</strong></li>
                            <li class="index-item" id="index-faq13"><a href="#faq13" data-i18n="index_faq13">Diferença
                                    entre planos</a></li>
                            <li class="index-item" id="index-faq14"><a href="#faq14" data-i18n="index_faq14">Posso mudar
                                    de plano?</a></li>
                            <li class="index-item index-cat mt-2"><strong>Royalties &amp; Pagamentos</strong></li>
                            <li class="index-item" id="index-faq15"><a href="#faq15" data-i18n="index_faq15">Percentagem
                                    de royalties</a></li>
                            <li class="index-item" id="index-faq16"><a href="#faq16" data-i18n="index_faq16">Quando e
                                    como recebo?</a></li>
                            <li class="index-item index-cat mt-2"><strong>Artistas &amp; Dashboard</strong></li>
                            <li class="index-item" id="index-faq17"><a href="#faq17" data-i18n="index_faq17">Cadastrar
                                    artista</a></li>
                            <li class="index-item" id="index-faq18"><a href="#faq18" data-i18n="index_faq18">Ver
                                    estatísticas</a></li>
                            <li class="index-item" id="index-faq19"><a href="#faq19" data-i18n="index_faq19">Adicionar
                                    colaboradores</a></li>
                            <li class="index-item index-cat mt-2"><strong>Plataforma</strong></li>
                            <li class="index-item" id="index-faq20"><a href="#faq20" data-i18n="index_faq20">Modo
                                    escuro</a></li>
                            <li class="index-item" id="index-faq21"><a href="#faq21" data-i18n="index_faq21">Múltiplos
                                    idiomas</a></li>
                            <li class="index-item mt-2" id="index-tips"><a href="#tips" data-i18n="index_tips">Dicas
                                    Rápidas</a></li>
                            <li class="index-item" id="index-tutorial"><a href="#tutorial"
                                    data-i18n="index_tutorial">Tutorial</a></li>
                        </ul>
                    </nav>

                    <!-- ══ FAQ Content ═════════════════════════════════════════════════ -->
                    <section class="faq-content fade-in-custom">

                        <!-- ▸ CATEGORIA: Conta & Acesso -->
                        <div class="faq-category-header">
                            <i class="bi bi-person-circle me-2 text-wasomupfy"></i>Conta &amp; Acesso
                        </div>

                        <div class="faq-item visible" id="faq1" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq1-answer">
                                <i class="bi bi-person-plus"></i>
                                <span data-i18n="faq1_question">Como me cadastro na <?php echo $siteName; ?>?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq1-answer" data-i18n="faq1_answer">
                                Aceda a <a href="/wasomupfy/register"
                                    class="text-wasomupfy fw-bold">wasomupfy/register</a> e preencha o formulário com o
                                seu nome, e-mail e uma senha segura. Após submeter, receberá um e-mail de verificação —
                                clique no link para activar a conta. Feito isso, já pode entrar no dashboard e começar a
                                gerir os seus artistas e lançamentos. Se as inscrições estiverem temporariamente
                                encerradas, aparecerá uma mensagem de aviso no site.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq2" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq2-answer">
                                <i class="bi bi-lock"></i>
                                <span data-i18n="faq2_question">O que fazer se esquecer a minha senha?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq2-answer" data-i18n="faq2_answer">
                                Aceda à página de login e clique em <strong>"Esqueci a senha"</strong>. Receberá um
                                e-mail com um link de redefinição seguro válido por 1 hora. Clique no link, defina uma
                                nova senha forte (mínimo 8 caracteres, misturando letras, números e símbolos) e
                                confirme. Verifique a caixa de spam caso o e-mail não apareça na sua caixa de entrada.
                                Se continuar sem acesso, contacte o suporte.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq3" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq3-answer">
                                <i class="bi bi-envelope-check"></i>
                                <span data-i18n="faq3_question">Como funciona a verificação de e-mail?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq3-answer" data-i18n="faq3_answer">
                                Após o cadastro, enviamos automaticamente um e-mail de verificação. Clique no botão
                                <strong>"Verificar e-mail"</strong> contido no e-mail para activar a sua conta. Enquanto
                                o e-mail não estiver verificado, o acesso ao dashboard ficará limitado. Se não receber o
                                e-mail em alguns minutos, entre no painel e clique em <strong>"Reenviar
                                    verificação"</strong>. O link de verificação expira ao fim de 24 horas.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq4" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq4-answer">
                                <i class="bi bi-shield-lock"></i>
                                <span data-i18n="faq4_question">Como activar a autenticação em dois factores
                                    (2FA)?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq4-answer" data-i18n="faq4_answer">
                                Aceda ao dashboard → <strong>Definições → Segurança</strong> e active o 2FA. Utilizamos
                                autenticação por e-mail: após inserir a senha no login, será enviado um código
                                temporário (OTP) para o seu e-mail que deverá inserir para concluir o acesso. O código
                                expira ao fim de 10 minutos. Recomendamos activar o 2FA para proteger a sua conta,
                                especialmente se gerir artistas ou receber royalties.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq5" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq5-answer">
                                <i class="bi bi-pause-circle"></i>
                                <span data-i18n="faq5_question">Como desactivar temporariamente a minha conta?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq5-answer" data-i18n="faq5_answer">
                                Aceda ao dashboard → <strong>Definições → Conta</strong> e escolha a opção
                                <strong>"Desactivar conta"</strong>. A conta ficará suspensa e os seus dados serão
                                preservados. Durante este período, o seu perfil e músicas ficam invisíveis para
                                terceiros. Para reactivar, basta fazer login novamente — uma caixa de diálogo irá
                                perguntar se deseja restaurar a conta, e após confirmar, tudo voltará ao normal.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq6" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq6-answer">
                                <i class="bi bi-trash3"></i>
                                <span data-i18n="faq6_question">Como eliminar permanentemente a minha conta?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq6-answer" data-i18n="faq6_answer">
                                Aceda ao dashboard → <strong>Definições → Conta → Eliminar conta</strong>. Esta acção é
                                <strong>irreversível</strong>: todos os seus artistas, lançamentos, estatísticas e dados
                                pessoais serão apagados permanentemente. Recomendamos exportar os seus relatórios
                                financeiros antes de prosseguir. Caso tenha royalties pendentes, solicite o levantamento
                                primeiro. A eliminação é processada imediatamente após a confirmação.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq7" data-category="conta">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq7-answer">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span data-i18n="faq7_question">Como reactivar uma conta desactivada?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq7-answer" data-i18n="faq7_answer">
                                Aceda à página de login com o seu e-mail e senha. Ao iniciar sessão, o sistema detecta
                                que a conta está desactivada e apresenta uma caixa de diálogo a perguntar se deseja
                                <strong>"Restaurar a conta"</strong>. Confirme a acção e a conta será reactivada
                                imediatamente com todos os dados intactos — artistas, lançamentos, histórico financeiro
                                e configurações.
                            </div>
                        </div>

                        <!-- ▸ CATEGORIA: Distribuição & Upload -->
                        <div class="faq-category-header mt-4">
                            <i class="bi bi-cloud-upload me-2 text-wasomupfy"></i>Distribuição &amp; Upload
                        </div>

                        <div class="faq-item visible" id="faq8" data-category="distribuicao">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq8-answer">
                                <i class="bi bi-clock-history"></i>
                                <span data-i18n="faq8_question">Quanto tempo demora para a música estar nas
                                    lojas?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq8-answer" data-i18n="faq8_answer">
                                O prazo médio é de <strong>3 a 7 dias úteis</strong> após aprovação interna (24-48h).
                                Cada plataforma tem o seu próprio tempo: Spotify 3-5 dias, Apple Music 2-3 dias, Deezer
                                3-7 dias. Recomendamos enviar o lançamento com <strong>pelo menos 3 semanas de
                                    antecedência</strong> para garantir que o pitch para playlists editoriais seja feito
                                a tempo. Data de lançamento agendada é possível — defina-a no formulário de upload.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq9" data-category="distribuicao">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq9-answer">
                                <i class="bi bi-file-earmark-music"></i>
                                <span data-i18n="faq9_question">Quais os formatos de áudio aceitos?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq9-answer" data-i18n="faq9_answer">
                                Aceitamos exclusivamente <strong>WAV estéreo, 44.1 kHz, 16-bit ou 24-bit</strong>.
                                Ficheiros MP3, AAC, OGG e outros formatos com perda de qualidade não são aceites pelas
                                lojas digitais. Para melhores resultados no mastering, deixe um headroom de -1 dB e
                                evite compressão excessiva. O tamanho máximo por faixa é de 1 GB. Se tiver graves
                                intensos, opte por 24-bit para preservar a dinâmica.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq10" data-category="distribuicao">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq10-answer">
                                <i class="bi bi-image"></i>
                                <span data-i18n="faq10_question">Quais os requisitos da arte da capa?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq10-answer" data-i18n="faq10_answer">
                                A capa deve ser um <strong>quadrado perfeito de mínimo 3000×3000 px</strong>, em formato
                                JPG ou PNG, modo de cor RGB, sem artefactos ou pixelização. É <strong>proibido</strong>
                                incluir logótipos de redes sociais (Instagram, TikTok, etc.), marcas d'água, preços, QR
                                codes, URLs ou informações de contacto. Capas com conteúdo explícito sem a marcação
                                adequada serão rejeitadas. Reveja sempre a capa em tamanho reduzido (thumbnail) antes de
                                submeter.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq11" data-category="distribuicao">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq11-answer">
                                <i class="bi bi-shop"></i>
                                <span data-i18n="faq11_question">Para quais lojas a <?php echo $siteName; ?>
                                    distribui?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq11-answer" data-i18n="faq11_answer">
                                Distribuímos para mais de <strong>150 lojas e plataformas</strong> globais, incluindo
                                Spotify, Apple Music, Deezer, TikTok, Amazon Music, TIDAL, YouTube Music, Boomplay,
                                Audiomack, Anghami e muitas outras. A disponibilidade pode variar por plano — o plano
                                Single e Álbum cobrem as principais lojas, enquanto os planos Artista e Label garantem
                                distribuição completa e prioritária para todas as plataformas disponíveis.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq12" data-category="distribuicao">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq12-answer">
                                <i class="bi bi-list-check"></i>
                                <span data-i18n="faq12_question">Como preencher os metadados e gerar o ISRC?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq12-answer" data-i18n="faq12_answer">
                                O <strong>ISRC</strong> (International Standard Recording Code) é gerado automaticamente
                                pela plataforma para cada faixa. No formulário de upload, preencha correctamente: nome
                                do artista principal, artistas convidados (feat.), compositores com as respectivas
                                percentagens, produtores, engenheiros de mixagem e mastering, género musical, idioma e
                                se a letra contém conteúdo explícito. Dados incorrectos podem atrasar a distribuição ou
                                causar conflitos de royalties.
                            </div>
                        </div>

                        <!-- ▸ CATEGORIA: Planos & Preços -->
                        <div class="faq-category-header mt-4">
                            <i class="bi bi-tag me-2 text-wasomupfy"></i>Planos &amp; Preços
                        </div>

                        <div class="faq-item visible" id="faq13" data-category="planos">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq13-answer">
                                <i class="bi bi-layers"></i>
                                <span data-i18n="faq13_question">Qual a diferença entre os planos disponíveis?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq13-answer" data-i18n="faq13_answer">
                                Temos 4 planos: <strong>Single (2.000 Kz)</strong> — ideal para lançar 1 a 3 faixas
                                pontuais; <strong>Álbum (5.000 Kz)</strong> — para trabalhos completos com múltiplas
                                faixas; <strong>Artista (11.400 Kz/2 anos)</strong> — plano de gestão contínua para
                                artistas activos, com lançamentos ilimitados, estatísticas avançadas e suporte
                                prioritário; <strong>Label (70.000 Kz/2 anos)</strong> — para editoras e selos que gerem
                                vários artistas, com painel multi-artista e relatórios consolidados. Veja todos os
                                detalhes em <a href="../../plan/all-plans" class="text-wasomupfy fw-bold">Todos os
                                    Planos</a>.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq14" data-category="planos">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq14-answer">
                                <i class="bi bi-arrow-repeat"></i>
                                <span data-i18n="faq14_question">Posso mudar de plano depois da compra?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq14-answer" data-i18n="faq14_answer">
                                Sim. Pode fazer upgrade para um plano superior a qualquer momento — basta aceder ao
                                dashboard e seleccionar o novo plano. Para fazer downgrade ou para questões específicas
                                de transição entre planos, contacte o suporte através da página de <a href="support"
                                    class="text-wasomupfy fw-bold">Suporte Técnico</a>. O plano actual permanece activo
                                até ao fim do período contratado antes de ser substituído.
                            </div>
                        </div>

                        <!-- ▸ CATEGORIA: Royalties & Pagamentos -->
                        <div class="faq-category-header mt-4">
                            <i class="bi bi-cash-stack me-2 text-wasomupfy"></i>Royalties &amp; Pagamentos
                        </div>

                        <div class="faq-item visible" id="faq15" data-category="financeiro">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq15-answer">
                                <i class="bi bi-percent"></i>
                                <span data-i18n="faq15_question">Que percentagem de royalties recebo?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq15-answer" data-i18n="faq15_answer">
                                Recebe <strong>90% dos royalties líquidos</strong> gerados pelas suas músicas nas lojas.
                                Os 10% restantes cobrem a infraestrutura da plataforma, suporte ao artista e taxas
                                administrativas. Os royalties líquidos são calculados após as deduções das plataformas
                                (ex: Spotify retém uma parte antes de repassar às distribuidoras). Pode acompanhar os
                                ganhos em tempo real no seu dashboard, filtrado por loja, período ou artista.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq16" data-category="financeiro">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq16-answer">
                                <i class="bi bi-wallet2"></i>
                                <span data-i18n="faq16_question">Quando e como recebo os meus ganhos?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq16-answer" data-i18n="faq16_answer">
                                Os relatórios de streams são actualizados mensalmente — os dados de Janeiro ficam
                                disponíveis em Março (dia 15) e o pagamento é processado até ao dia 20. Após atingir o
                                valor mínimo de levantamento do seu plano, pode solicitar o resgate através da sua
                                <strong>carteira <?php echo $siteName; ?></strong> via transferência bancária, IBAN ou
                                outros métodos disponíveis. Certifique-se de que os seus dados bancários estão correctos
                                nas definições da conta antes de solicitar o levantamento.
                            </div>
                        </div>

                        <!-- ▸ CATEGORIA: Artistas & Dashboard -->
                        <div class="faq-category-header mt-4">
                            <i class="bi bi-mic me-2 text-wasomupfy"></i>Artistas &amp; Dashboard
                        </div>

                        <div class="faq-item visible" id="faq17" data-category="artistas">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq17-answer">
                                <i class="bi bi-person-plus"></i>
                                <span data-i18n="faq17_question">Como cadastro um novo artista na plataforma?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq17-answer" data-i18n="faq17_answer">
                                No dashboard, aceda à secção <strong>"Artistas"</strong> e clique em <strong>"Adicionar
                                    Artista"</strong>. Preencha o nome artístico, bio, género musical e carregue a foto
                                de perfil (formato JPG/PNG, mínimo 400×400 px). Após guardar, o artista fica disponível
                                para associar aos seus lançamentos. Pode editar as informações a qualquer momento. Em
                                planos Label, pode gerir múltiplos artistas sob o mesmo painel com controlo total sobre
                                cada perfil.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq18" data-category="artistas">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq18-answer">
                                <i class="bi bi-bar-chart"></i>
                                <span data-i18n="faq18_question">Como vejo as estatísticas das minhas músicas?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq18-answer" data-i18n="faq18_answer">
                                Aceda ao dashboard e clique em <strong>"Estatísticas"</strong> no menu lateral. Pode
                                filtrar por artista, lançamento ou período de tempo para visualizar streams, países com
                                mais audiência, plataformas com melhor desempenho e evolução ao longo do tempo. Os dados
                                são apresentados em gráficos e tabelas interactivas. Também pode exportar os relatórios
                                em formato <strong>CSV</strong> para análise detalhada em ferramentas externas como
                                Excel ou Google Sheets.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq19" data-category="artistas">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq19-answer">
                                <i class="bi bi-people"></i>
                                <span data-i18n="faq19_question">Posso adicionar colaboradores à minha conta?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq19-answer" data-i18n="faq19_answer">
                                Sim. Aceda ao dashboard → <strong>Definições → Colaboradores</strong> e convide
                                utilizadores por e-mail. Pode definir o nível de acesso de cada colaborador:
                                <strong>Visualizador</strong> (só lê estatísticas), <strong>Editor</strong> (gere
                                artistas e lançamentos) ou <strong>Administrador</strong> (acesso total exceto dados
                                financeiros). Os colaboradores recebem um convite por e-mail para criar ou vincular a
                                sua conta à sua equipa. Esta funcionalidade é ideal para managers, produtores e equipas
                                de marketing.
                            </div>
                        </div>

                        <!-- ▸ CATEGORIA: Plataforma -->
                        <div class="faq-category-header mt-4">
                            <i class="bi bi-display me-2 text-wasomupfy"></i>Plataforma &amp; Interface
                        </div>

                        <div class="faq-item visible" id="faq20" data-category="plataforma">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq20-answer">
                                <i class="bi bi-moon"></i>
                                <span data-i18n="faq20_question">Como funciona o modo escuro?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq20-answer" data-i18n="faq20_answer">
                                O modo escuro pode ser activado manualmente ou seguir automaticamente a preferência do
                                seu sistema operativo. Clique no ícone <strong>☀️/🌙</strong> no canto inferior direito
                                da página e escolha entre <strong>Claro</strong>, <strong>Escuro</strong> ou
                                <strong>Sistema</strong> (automático). A preferência é guardada no seu browser e
                                aplica-se a todas as páginas do site. O modo escuro reduz o cansaço visual em ambientes
                                com pouca luz e prolonga a bateria em dispositivos com ecrã OLED.
                            </div>
                        </div>

                        <div class="faq-item visible" id="faq21" data-category="plataforma">
                            <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false"
                                aria-controls="faq21-answer">
                                <i class="bi bi-translate"></i>
                                <span data-i18n="faq21_question">A plataforma suporta múltiplos idiomas?</span>
                                <i class="bi bi-chevron-down toggle-icon"></i>
                            </div>
                            <div class="answer" id="faq21-answer" data-i18n="faq21_answer">
                                Sim. O site público e algumas secções do FAQ estão disponíveis em <strong>Português
                                    (PT/AO/BR)</strong> e <strong>Inglês</strong>. O idioma é detectado automaticamente
                                com base nas preferências do seu browser, mas pode alterá-lo manualmente através do
                                selector de idioma disponível na página. O dashboard está optimizado para Português,
                                sendo o idioma principal da plataforma dada a sua natureza focada no mercado angolano e
                                da CPLP.
                            </div>
                        </div>

                        <!-- ── Dicas Rápidas ───────────────────────────────────────────── -->
                        <div class="tips-section" id="tips">
                            <h2 data-i18n="tips_title">Dicas Rápidas</h2>
                            <div class="tip-card" data-i18n="tip1">
                                <i class="bi bi-calendar-range"></i> Lance com <strong>3 semanas de
                                    antecedência</strong> para garantir o pitch nas playlists editoriais do Spotify e
                                Apple Music.
                            </div>
                            <div class="tip-card" data-i18n="tip2">
                                <i class="bi bi-bar-chart-line"></i> Use os filtros de data nas
                                <strong>Estatísticas</strong> para comparar o desempenho entre lançamentos e identificar
                                a sua audiência principal.
                            </div>
                            <div class="tip-card" data-i18n="tip3">
                                <i class="bi bi-shield-check"></i> Active o <strong>2FA</strong> nas definições de
                                segurança para proteger a sua conta e os seus royalties contra acessos não autorizados.
                            </div>
                            <div class="tip-card" data-i18n="tip4">
                                <i class="bi bi-download"></i> Exporte os relatórios financeiros em <strong>CSV</strong>
                                regularmente para ter um registo histórico dos seus ganhos fora da plataforma.
                            </div>
                        </div>

                    </section><!-- /faq-content -->
                </div><!-- /row -->
            </div><!-- /container -->

            <!-- Tutorial Section -->
            <div class="tutorial-section" id="tutorial">
                <h2 data-i18n="tutorial_title">Assista ao Nosso Tutorial</h2>
                <button class="btn-wasomupfy" data-bs-toggle="modal" data-bs-target="#tutorialModal"
                    data-i18n="watch_video">
                    Ver Vídeo
                </button>
            </div>
        </section>

    </main>

    <!-- Modal Tutorial Vídeo -->
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tutorialModalLabel" data-i18n="tutorial_modal_title">
                        Tutorial <?php echo $siteName; ?>
                    </h5>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        data-i18n="close">Fechar</button>
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
    <script src="../../js/faq.js"></script>
    <script src="../../js/cookies.js"></script>

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