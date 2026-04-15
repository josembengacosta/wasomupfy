<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Suporte Técnico
// Arquivo: page/support/support.php  (profundidade: ../../)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/site.php';

checkPlatformStatus('support');
trackVisitor('/page/support/support', 'Suporte Técnico — Wasom Upfy');

$plans        = getPlans();
$plansBySlug  = [];
foreach ($plans as $p) {
    $plansBySlug[$p['slug_plan']] = $p;
}

$platform     = getPlatform();
$canRegister  = (bool)$platform['allow_register'];
$siteName     = htmlspecialchars(cfg('site_name', 'Wasom Upfy'));
$siteUrl      = rtrim(cfg('site_url', 'https://wasomupfy.rf.gd'), '/');
$whatsNum     = preg_replace('/[^0-9]/', '', cfg('whatsapp_number', '244922030116'));

$csrf_support = getSiteCsrf(); // gerado no topo, antes de qualquer HTML
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="keywords" content="<?php echo $siteName; ?>, Suporte técnico, Suporte, Enviar pedido de suporte" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <meta name="theme-color" content="#FF009D">
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:title" content="<?php echo $siteName; ?> — Suporte técnico" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'A ' . $siteName . ' é a plataforma de distribuição de música mais fácil e completa do mercado.')); ?>" />
    <meta property="og:url" content="<?php echo $siteUrl; ?>/page/support/support" />
    <meta property="og:site_name" content="<?php echo $siteName; ?>" />
    <meta property="og:image"
        content="<?php echo htmlspecialchars(cfg('og_image', $siteUrl . '/assets/img/og/og_wasomupfy.jpeg')); ?>" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:width" content="300" />
    <meta property="og:image:height" content="300" />
    <meta property="og:image:alt" content="<?php echo $siteName; ?>" />
    <title><?php echo $siteName; ?> | Suporte técnico</title>

    <script>
    window.addEventListener("load", function() {
        setTimeout(function() {
            document.querySelector("body").classList.add("loaded")
        }, 200)
    })
    </script>

    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/theme.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/framework.css">
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/support.wp.css">
</head>

<body data-base-path="../..">

    <!-- Preloader -->
    <div class="preloader">
        <img src="../../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="Loading-<?php echo $siteName; ?>">
    </div>

    <!-- ══ Navbar ════════════════════════════════════════════════════════════ -->
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
                        <a title="Home" href="../../home">
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
                                            <div>
                                                <div class="dropdown-header">Blog</div>
                                                <a title="Novidades" class="dropdown-item"
                                                    href="../../blog/">Novidades</a>
                                                <a title="Passatempo Wasom Upfy" class="dropdown-item"
                                                    href="../../blog/">Passatempo</a>
                                                <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                            </div>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a title="A nossa marca" class="dropdown-item"
                                                    href="../../about?#nossamarca">A nossa marca</a>
                                                <a title="Parcerias" class="dropdown-item"
                                                    href="../partnership">Parcerias</a>
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
                                                <a title="Ocorreu um erro" class="dropdown-item active"
                                                    href="support">Suporte técnico</a>
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
    <!-- Navbar fim -->

    <main>
        <!-- ══ Hero — Suporte Técnico ════════════════════════════════════════ -->
        <section class="jarallax hero-agency bg-opacity-10" data-jarallax data-speed="0.4" data-cue="fadeIn">
            <img class="jarallax-img img-fluid" src="../../assets/img/theme/support.png"
                alt="Suporte <?php echo $siteName; ?>" />
            <div style="
                position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.4));
            "></div>
            <div class="position-relative start-0 end-0">
                <div class="container mt-7">
                    <div class="container">
                        <div class="row align-items-center g-5">

                            <!-- Coluna esquerda — texto -->
                            <div class="col-lg-5 col-12 text-white-stable" data-cue="slideInLeft">
                                <div class="mb-5">
                                    <small class="text-uppercase ls-md fw-semibold text-wasomupfy">Suporte
                                        Técnico</small>
                                    <h2 class="h1 mb-4 mt-3 text-uppercase text-white-stable fw-bold">
                                        Enviar pedido de <span class="text-wasomupfy">suporte</span>
                                    </h2>
                                    <p class="lead opacity-75">
                                        Preencha o formulário ao lado para contactar a nossa equipa técnica.
                                        Dica: <a href="faq" class="text-wasomupfy text-decoration-underline">consulte o
                                            nosso FAQ</a>
                                        para encontrar respostas imediatas!
                                    </p>
                                </div>
                                <div class="mb-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex mb-4 align-items-start">
                                            <div
                                                class="icon-box bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-3 p-3 shadow-sm">
                                                <i class="fa-solid fa-headset fs-2"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="fw-bold mb-1 text-white-stable">Compromisso com o Artista
                                                </h5>
                                                <p class="small mb-0 opacity-75">
                                                    Estamos sempre disponíveis para resolver qualquer entrave técnico. A
                                                    sua música não pode parar!
                                                </p>
                                            </div>
                                        </li>
                                        <li class="d-flex mb-4 align-items-start">
                                            <div
                                                class="icon-box bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-3 p-3 shadow-sm">
                                                <i class="fa-solid fa-triangle-exclamation fs-2"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="fw-bold mb-1 text-white-stable">Relato de Erros</h5>
                                                <p class="small mb-0 opacity-75">
                                                    Damos atenção prioritária a erros na plataforma. Se algo não estiver
                                                    a funcionar, reporte imediatamente.
                                                </p>
                                            </div>
                                        </li>
                                        <li class="d-flex mb-4 align-items-start">
                                            <div
                                                class="icon-box bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-3 p-3 shadow-sm">
                                                <i class="fa-solid fa-clock-rotate-left fs-2"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="fw-bold mb-1 text-white-stable">Resposta Expressa</h5>
                                                <p class="small mb-0 opacity-75">
                                                    Respondemos a questões complexas num tempo médio de 30 minutos.
                                                    Suporte rápido e humano.
                                                </p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Coluna direita — formulário -->
                            <div class="col-lg-7 col-12" data-cue="slideInRight">
                                <div class="position-relative mx-3">
                                    <!-- Barra de progresso -->
                                    <div class="progress-bar-container">
                                        <div class="progress-bar">
                                            <div class="progress-bar-fill" id="progressBar"></div>
                                        </div>
                                    </div>

                                    <div class="card shadow-sm mb-6">
                                        <div class="card-body">
                                            <!-- Alerta de resposta -->
                                            <div id="support-alert" class="alert d-none mb-3" role="alert"></div>

                                            <form class="support-form fade-in-custom" id="supportForm"
                                                enctype="multipart/form-data" novalidate>
                                                <!-- CSRF -->
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo htmlspecialchars($csrf_support); ?>" />

                                                <div class="form-group">
                                                    <label for="sup_name">Nome <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" id="sup_name" class="form-control"
                                                        placeholder="Insira o seu nome completo" name="name"
                                                        autocomplete="name" required>
                                                    <div class="error-message" id="nameError">Por favor, insira o seu
                                                        nome.</div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="sup_email">E-mail <span
                                                            class="text-danger">*</span></label>
                                                    <input type="email" id="sup_email" class="form-control"
                                                        placeholder="Insira o seu e-mail válido" name="email"
                                                        autocomplete="email" required>
                                                    <div class="error-message" id="emailError">Por favor, insira um
                                                        e-mail válido.</div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="sup_urgency">Urgência <span
                                                            class="text-danger">*</span></label>
                                                    <select id="sup_urgency" class="form-control" name="urgency"
                                                        required>
                                                        <option value="" disabled selected>Selecione uma opção</option>
                                                        <option value="low">Baixa</option>
                                                        <option value="medium">Média</option>
                                                        <option value="high">Alta</option>
                                                    </select>
                                                    <div class="error-message" id="urgencyError">Por favor, selecione a
                                                        urgência.</div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="sup_issue">Tipo de Problema <span
                                                            class="text-danger">*</span></label>
                                                    <select id="sup_issue" class="form-control" name="issueType"
                                                        required>
                                                        <option value="" disabled selected>Selecione uma opção</option>
                                                        <optgroup label="Acesso">
                                                            <option value="login">Problemas de Login</option>
                                                            <option value="password">Esqueci a senha</option>
                                                            <option value="account">Conta bloqueada/suspensa</option>
                                                        </optgroup>
                                                        <optgroup label="Planos e Pagamentos">
                                                            <option value="plan">Alterar o plano</option>
                                                            <option value="payment">Dúvidas sobre pagamento</option>
                                                            <option value="refund">Política de reembolso</option>
                                                        </optgroup>
                                                        <optgroup label="Serviços">
                                                            <option value="stats">Erro nas Estatísticas</option>
                                                            <option value="upload">Falha ao Enviar Arquivos</option>
                                                            <option value="distribution">Distribuição e prazos</option>
                                                        </optgroup>
                                                        <optgroup label="Outros">
                                                            <option value="other">Outro</option>
                                                        </optgroup>
                                                    </select>
                                                    <div class="error-message" id="issueTypeError">Por favor, selecione
                                                        o tipo de problema.</div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="sup_description">Descrição do Problema <span
                                                            class="text-danger">*</span></label>
                                                    <textarea id="sup_description" class="form-control"
                                                        name="description" placeholder="Descreva o problema em detalhe"
                                                        required></textarea>
                                                    <div class="error-message" id="descriptionError">Por favor, descreva
                                                        o problema.</div>
                                                </div>

                                                <div class="form-group" id="attachmentContainer">
                                                    <label for="sup_attachment">Anexar Ficheiro <span
                                                            class="text-muted small">(opcional)</span></label>
                                                    <input type="file" class="form-control" id="sup_attachment"
                                                        name="attachment[]"
                                                        accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip,.mp4,.mov">
                                                    <button type="button" class="add-attachment-btn mt-2"
                                                        id="btn-add-attachment">+ Adicionar Ficheiro</button>
                                                    <small class="text-muted d-block mt-1">Máx. 10 MB por ficheiro.
                                                        Formatos: imagem, PDF, ZIP, vídeo.</small>
                                                </div>

                                                <button type="submit" class="submit-btn" id="btn-support-submit">
                                                    Enviar Pedido
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="divider-fade"></div>

    <!-- ══ Footer ════════════════════════════════════════════════════════════ -->
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
                    <!-- Logo + Redes -->
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
                            <li class="mb-2"><a href="../services/customized-services"
                                    class="text-reset text-decoration-none hover-white">Serviços Premium</a></li>
                        </ul>
                    </div>

                    <!-- Suporte -->
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
                            <li class="mb-2">
                                <a href="https://wa.me/<?php echo $whatsNum; ?>"
                                    class="text-reset text-decoration-none hover-white">WhatsApp</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Contacto -->
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
                            <li class="d-flex"><span>Seg — Sex: 08h às 17h</span></li>
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
                    <p class="text-muted">Como tem sido a sua experiência com a
                        <strong><?php echo $siteName; ?></strong>? As suas sugestões ajudam-nos a evoluir.
                    </p>
                    <div id="feedback-modal-msg" class="alert d-none mb-3" role="alert"></div>
                    <form id="formFeedback" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_support); ?>" />
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
    <script src="<?php echo APP_URL  ?>/js/libs/tools.min.js"></script>
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
    <script src="<?php echo APP_URL  ?>/js/vendors/password.js"></script>
    <script src="<?php echo APP_URL  ?>/js/cookies.js"></script>

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

    <script>
    (function() {
        /* ── Sincroniza TODOS os csrf_token da página ─────────────────────── */
        function syncAllCsrf(token) {
            if (!token) return;
            document.querySelectorAll('[name="csrf_token"]').forEach(function(el) {
                el.value = token;
            });
        }

        /* ── Adicionar campo de ficheiro extra ───────────────────────────── */
        document.getElementById('btn-add-attachment').addEventListener('click', function() {
            var container = document.getElementById('attachmentContainer');
            var input = document.createElement('input');
            input.type = 'file';
            input.name = 'attachment[]';
            input.className = 'form-control mt-2';
            input.accept = '.jpg,.jpeg,.png,.gif,.pdf,.txt,.zip,.mp4,.mov';
            container.insertBefore(input, this);
        });

        /* ── Barra de progresso do formulário ────────────────────────────── */
        function updateProgressBar() {
            var form = document.getElementById('supportForm');
            var inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            var filled = 0;
            inputs.forEach(function(inp) {
                if (inp.value.trim() !== '') filled++;
            });
            document.getElementById('progressBar').style.width = ((filled / inputs.length) * 100) + '%';
        }

        document.getElementById('supportForm').querySelectorAll('input, select, textarea').forEach(function(el) {
            el.addEventListener('input', updateProgressBar);
            el.addEventListener('change', updateProgressBar);
        });

        /* ══ Formulário de Suporte ══════════════════════════════════════════
           Usa FormData (multipart) porque tem upload de ficheiro.
           O CSRF vai dentro do FormData — o endpoint lê $_POST['csrf_token'].
        ═══════════════════════════════════════════════════════════════════ */
        var fSupport = document.getElementById('supportForm');
        var alertBox = document.getElementById('support-alert');
        var btnSup = document.getElementById('btn-support-submit');

        fSupport.addEventListener('submit', function(e) {
            e.preventDefault();

            /* Validação manual (mantém compatibilidade com CSS .error-message) */
            var name = document.getElementById('sup_name').value.trim();
            var email = document.getElementById('sup_email').value.trim();
            var urgency = document.getElementById('sup_urgency').value;
            var issueType = document.getElementById('sup_issue').value;
            var description = document.getElementById('sup_description').value.trim();
            var isValid = true;

            document.querySelectorAll('.error-message').forEach(function(el) {
                el.style.display = 'none';
            });

            if (!name) {
                document.getElementById('nameError').style.display = 'block';
                isValid = false;
            }
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('emailError').style.display = 'block';
                isValid = false;
            }
            if (!urgency) {
                document.getElementById('urgencyError').style.display = 'block';
                isValid = false;
            }
            if (!issueType) {
                document.getElementById('issueTypeError').style.display = 'block';
                isValid = false;
            }
            if (!description) {
                document.getElementById('descriptionError').style.display = 'block';
                isValid = false;
            }

            if (!isValid) {
                updateProgressBar();
                return;
            }

            var basePath = document.body.dataset.basePath || '../..';
            btnSup.disabled = true;
            btnSup.textContent = 'A enviar…';

            var fd = new FormData(fSupport);

            fetch(basePath + '/ajax/support.php', {
                    method: 'POST',
                    body: fd /* multipart — não definir Content-Type manualmente */
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    alertBox.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
                    alertBox.textContent = data.message || (data.success ?
                        'Pedido enviado com sucesso!' : 'Erro ao enviar.');
                    alertBox.classList.remove('d-none');

                    if (data.new_csrf) syncAllCsrf(data.new_csrf);

                    if (data.success) {
                        fSupport.reset();
                        updateProgressBar();
                        /* Remover campos de anexo extra */
                        document.querySelectorAll('#attachmentContainer input[type="file"]').forEach(
                            function(el, i) {
                                if (i > 0) el.remove();
                            });
                    }
                })
                .catch(function() {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = 'Erro de ligação. Tente novamente.';
                    alertBox.classList.remove('d-none');
                })
                .finally(function() {
                    btnSup.disabled = false;
                    btnSup.textContent = 'Enviar Pedido';
                    setTimeout(function() {
                        alertBox.classList.add('d-none');
                    }, 7000);
                });
        });

        /* ══ Modal Feedback ═════════════════════════════════════════════════ */
        var fModal = document.getElementById('formFeedback');
        if (fModal) {
            fModal.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!fModal.checkValidity()) {
                    fModal.classList.add('was-validated');
                    return;
                }

                var basePath = document.body.dataset.basePath || '../..';
                var btn = document.getElementById('btn-feedback-modal');
                var msgBox = document.getElementById('feedback-modal-msg');

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';

                fetch(basePath + '/ajax/feedback.php', {
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
                        msgBox.textContent = data.message || (data.success ?
                            'Feedback enviado! Obrigado.' : 'Erro ao enviar.');
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
                        msgBox.textContent = 'Erro de ligação. Tente novamente.';
                        msgBox.classList.remove('d-none');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.innerHTML = 'Enviar Feedback <i class="fa-solid fa-paper-plane ms-2"></i>';
                    });
            });
        }

    })();
    </script>

</body>

</html>