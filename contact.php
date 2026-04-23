<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Página de Contacto
// Arquivo: contact.php (raiz do site)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/include/site.php';

checkPlatformStatus('contact');
trackVisitor('/contact', 'Contacto — Wasom Upfy');

$plans       = getPlans();
$platform    = getPlatform();
$canRegister = (bool)$platform['allow_register'];
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
    <title><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> | Contacto</title>
    <meta name="description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'Plataforma de distribuição musical de Angola para o mundo.')); ?>" />
    <meta name="keywords"
        content="<?php echo htmlspecialchars(cfg('site_keywords', 'Wasom Upfy, contacto, fale connosco, distribuição musical Angola')); ?>" />

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:locale:alternate" content="pt_PT" />
    <meta property="og:locale:alternate" content="pt_BR" />
    <meta property="og:locale:alternate" content="en_EN" />
    <meta property="og:locale:alternate" content="fr_FR" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?> — Contacto" />
    <meta property="og:description"
        content="<?php echo htmlspecialchars(cfg('site_description', 'Distribua a sua música globalmente com a Wasom Upfy.')); ?>" />
    <meta property="og:url"
        content="<?php echo htmlspecialchars(cfg('site_url', 'https://wasomupfy.rf.gd')); ?>/contact" />
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

    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv1.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="css/theme.min.css" />
    <link rel="stylesheet" href="js/libs/scrollcue/scrollCue.css" />
    <link rel="stylesheet" href="css/framework.css" />
</head>

<style>
.btn-social {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: transform 0.2s ease, background-color 0.2s ease;
}

.btn-social:hover,
.btn-social:focus {
    transform: translateY(-2px);
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.icon-shape {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    height: 48px;
    transition: all 0.3s ease;
}

li:hover .icon-shape {
    background-color: #ff009d !important;
    color: white !important;
    transform: scale(1.1);
    box-shadow: 0 5px 15px rgba(255, 0, 157, 0.3) !important;
}
</style>

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
                <a class="navbar-brand" href="home" title="Home">
                    <img src="assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                        alt="Logo Wasom Upfy" />
                </a>
                <button class="navbar-toggler offcanvas-nav-btn" type="button">
                    <i class="bi bi-list"></i>
                </button>
                <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                    <div class="offcanvas-header">
                        <a title="Logotipo" href="home">
                            <img width="65" src="assets/img/brand/wasomupfy_brand.png" alt="Logo Wasom Upfy" />
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body pt-0 align-items-center">
                        <ul class="navbar-nav mx-auto align-items-lg-center">
                            <li class="nav-item">
                                <a class="nav-link" href="home" title="Inicio">Início</a>
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
                                    aria-expanded="false">Páginas <i data-feather="chevron-down"></i></a>
                                <div class="dropdown-menu dropdown-menu-xxl">
                                    <div class="row row-cols-lg-3">
                                        <div class="col">
                                            <div class="dropdown-header">Blog</div>
                                            <a title="Novidades" class="dropdown-item" href="blog/">Novidades</a>
                                            <a title="Passatempo Wasom Upfy" class="dropdown-item"
                                                href="blog/">Passatempo</a>
                                            <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                <span class="badge bg-warning">Indisponível</span></a>
                                            <div class="mt-3">
                                                <div class="dropdown-header">Sobre</div>
                                                <a title="A nossa marca" class="dropdown-item"
                                                    href="about?#nossamarca">A nossa marca</a>
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
                                                    <span class="badge bg-warning">Indisponível</span></a>
                                                <div class="mt-3">
                                                    <div class="dropdown-header">Contactos</div>
                                                    <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                        href="https://www.facebook.com/m.me/2007900989425052"
                                                        target="_blank"
                                                        rel="external noopener noreferrer">Atendimento</a>
                                                    <a title="Contacto-nos" class="dropdown-item"
                                                        href="contact">Contacta-nos</a>
                                                    <a title="Canal WhatsApp" class="dropdown-item"
                                                        href="https://whatsapp.com/channel/0029VaCEDqo59PwWpU0nGa04"
                                                        target="_blank" rel="external noopener noreferrer">Canal
                                                        WhatsApp</a>
                                                </div>
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
                                <a title="Contactar" class="nav-link active" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    Contactar <i data-feather="chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a title="Caixa de mensagem" class="dropdown-item active" href="contact">Caixa
                                            de mensagem</a>
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
                            <a title="Sign-in" href="login" class="btn btn-secondary mx-2">
                                Entrar <i data-feather="log-in"></i>
                            </a>
                            <?php if ($canRegister): ?>
                            <a title="Sign-up" href="register" class="btn btn-wasomupfy">Inscreva-se</a>
                            <?php else: ?>
                            <span class="btn btn-secondary disabled">Inscrições fechadas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main -->
    <main>
        <section class="jarallax hero-agency bg-opacity-10" data-jarallax data-speed="0.4" data-cue="fadeIn">
            <img class="jarallax-img img-fluid" src="assets/img/theme/contact.png" alt="Contacto Wasom Upfy" />
            <div style="position:absolute;top:0;left:0;width:100%;height:100%;
                  background:linear-gradient(rgba(0,0,0,0.7),rgba(0,0,0,0.4))"></div>

            <div class="position-relative start-0 end-0 mt-3">
                <div class="container mt-7">
                    <div class="container">
                        <div class="row align-items-center g-5">

                            <!-- Coluna esquerda — info -->
                            <div class="col-lg-5 col-12 text-white-stable" data-cue="slideInLeft">
                                <small class="text-uppercase ls-md fw-semibold text-wasomupfy">Contactar</small>
                                <h2 class="h1 mb-4 mt-4 text-uppercase text-white-stable fw-bold">
                                    Conecte-se com a <span class="text-wasomupfy">nossa equipa</span>
                                </h2>
                                <p class="lead opacity-75">
                                    Estamos prontos para ouvir as suas ideias, resolver questões ou receber o seu
                                    feedback.
                                    Escolha o canal que preferir.
                                </p>

                                <div class="mb-6 mt-5">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex mb-4 align-items-start">
                                            <div
                                                class="icon-shape bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle p-3 shadow-sm">
                                                <i class="fa-solid fa-handshake fs-2"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="fw-bold mb-1 text-white-stable">Compromisso Total</h5>
                                                <p class="small mb-0 opacity-75">
                                                    Estamos sempre disponíveis para agendar uma reunião ou call.
                                                    A sua carreira é a nossa prioridade.
                                                </p>
                                            </div>
                                        </li>
                                        <li class="d-flex mb-4 align-items-start">
                                            <div
                                                class="icon-shape bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle p-3 shadow-sm">
                                                <i class="fa-solid fa-shield-halved fs-2"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="fw-bold mb-1 text-white-stable">Canal de Denúncia</h5>
                                                <p class="small mb-0 opacity-75">
                                                    Levamos a sério a integridade da nossa comunidade.
                                                    Se algo não estiver correto, não hesite em reportar.
                                                </p>
                                            </div>
                                        </li>
                                        <li class="d-flex mb-4 align-items-start">
                                            <div
                                                class="icon-shape bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle p-3 shadow-sm">
                                                <i class="fa-solid fa-bolt-lightning fs-2"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="fw-bold mb-1 text-white-stable">Feedback Ágil</h5>
                                                <p class="small mb-0 opacity-75">
                                                    Tempo é música. Respondemos às mensagens em tempo recorde,
                                                    geralmente em menos de 30 minutos.
                                                </p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Coluna direita — formulário -->
                            <div class="col-md-7" data-cue="slideInRight">
                                <div class="position-relative mx-3">
                                    <div class="card shadow-sm mb-6">
                                        <div class="card-body">

                                            <!-- Alerta resultado -->
                                            <div id="contactAlert" class="alert d-none mb-3" role="alert"></div>

                                            <!-- Formulário -->
                                            <form id="contactForm" class="row g-3 needs-validation" novalidate>
                                                <input type="hidden" id="contactCsrf" name="csrf"
                                                    value="<?php echo getSiteCsrf(); ?>">

                                                <div class="col-md-6">
                                                    <label for="name_user" class="form-label text-dark-form">
                                                        Nome Completo <span class="text-danger text-xs">*</span>
                                                    </label>
                                                    <input type="text" minlength="3" maxlength="120"
                                                        placeholder="Insira o seu nome completo" class="form-control"
                                                        autocomplete="name" id="name_user" name="name" required />
                                                    <div class="invalid-feedback">Qual é o seu nome completo?</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="tel_user" class="form-label text-dark-form">
                                                        Número de Telefone
                                                        <span class="text-muted opacity-8 hit">(opcional)</span>
                                                    </label>
                                                    <input type="tel" maxlength="30" placeholder="Ex: +244 900 000 000"
                                                        class="form-control" autocomplete="tel" id="tel_user"
                                                        name="phone" />
                                                </div>

                                                <div class="col-md-12">
                                                    <label for="email_user" class="form-label text-dark-form">
                                                        E-mail <span class="text-danger text-xs">*</span>
                                                        <span class="text-muted opacity-8 hit">(Insira um e-mail que
                                                            utiliza)</span>
                                                    </label>
                                                    <input type="email" maxlength="120"
                                                        placeholder="usuario@exemplo.com" class="form-control"
                                                        autocomplete="email" id="email_user" name="email" required />
                                                    <div class="valid-feedback">E-mail válido.</div>
                                                    <div class="invalid-feedback">Insira um e-mail válido!</div>
                                                </div>

                                                <div class="col-md-12">
                                                    <label for="subject_user" class="form-label text-dark-form">
                                                        Assunto <span class="text-danger text-xs">*</span>
                                                    </label>
                                                    <input type="text" maxlength="120" minlength="5"
                                                        placeholder="Insira o seu assunto" class="form-control"
                                                        id="subject_user" name="subject" required />
                                                    <div class="invalid-feedback">Qual é o assunto?</div>
                                                </div>

                                                <div class="col-md-12">
                                                    <label for="message_user" class="form-label text-dark-form">
                                                        Mensagem <span class="text-danger text-xs">*</span>
                                                    </label>
                                                    <textarea name="message" id="message_user" required
                                                        class="form-control" maxlength="2000" rows="5"
                                                        placeholder="Escreva a sua mensagem aqui!"></textarea>
                                                    <div class="form-text text-end">
                                                        <span id="contactCharCount">0</span>/2000
                                                    </div>
                                                    <div class="invalid-feedback">Por favor, escreva a sua mensagem!
                                                    </div>
                                                </div>

                                                <div class="col-12 mt-3">
                                                    <button class="btn btn-wasomupfy w-100" type="submit"
                                                        id="contactSubmitBtn">
                                                        <span id="contactBtnText">
                                                            Enviar mensagem <i class="fa-solid fa-paper-plane ms-2"></i>
                                                        </span>
                                                        <span id="contactBtnLoading" class="d-none">
                                                            <span class="spinner-border spinner-border-sm me-2"
                                                                role="status"></span>
                                                            A enviar...
                                                        </span>
                                                    </button>
                                                    <div class="mt-1">
                                                        <span class="text-sm text-dark-form-50">
                                                            <span class="text-danger text-xs">*</span> campo obrigatório
                                                        </span>
                                                    </div>
                                                </div>
                                            </form>

                                            <!-- Estado sucesso (oculto) -->
                                            <div id="contactSuccess" class="d-none text-center py-4">
                                                <div class="mb-3">
                                                    <i class="fa-solid fa-circle-check text-success"
                                                        style="font-size:3.5rem"></i>
                                                </div>
                                                <h4 class="fw-bold mb-2">Mensagem enviada! <i
                                                        class="fa-solid fa-paper-plane"></i></h4>
                                                <p class="text-muted mb-4">
                                                    Recebemos a tua mensagem com sucesso.<br>
                                                    A equipa da
                                                    <strong><?php echo htmlspecialchars(cfg('site_name', 'Wasom Upfy')); ?></strong>
                                                    vai responder em menos de 30 minutos.
                                                </p>
                                                <button class="btn btn-outline-secondary" onclick="resetContactForm()">
                                                    Enviar outra mensagem
                                                </button>
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

            <!-- Links do footer -->
            <nav aria-label="Navegação do rodapé">
                <div class="row g-5" id="ft-links">

                    <!-- Logo + Social -->
                    <div class="col-lg-3 col-12">
                        <a href="home" class="d-inline-block mb-4 navbar-brand">
                            <img src="assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy" width="65" class="img-logo"
                                height="60" />
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
                            <li class="mb-2"><a href="about"
                                    class="text-reset text-decoration-none hover-white">Sobre</a></li>
                            <li class="mb-2"><a href="about#nossamarca"
                                    class="text-reset text-decoration-none hover-white">A nossa marca</a></li>
                            <li class="mb-2"><a href="plan/all-plans"
                                    class="text-reset text-decoration-none hover-white">Planos</a></li>
                            <li class="mb-2"><a href="page/services/customized-services"
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
                            <li class="mb-2"><a href="page/support/help"
                                    class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                            <li class="mb-2"><a href="contact"
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
                            <a href="page/politicies/privacy" class="text-reset text-decoration-none">Política de
                                Privacidade</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="page/politicies/terms" class="text-reset text-decoration-none">Termos de Uso</a>
                        </li>
                        <li class="list-inline-item mx-2 text-white-10">|</li>
                        <li class="list-inline-item">
                            <a href="page/politicies/cookies" class="text-reset text-decoration-none">Cookies</a>
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
                            <div class="form-text text-end">
                                <span id="feedbackCharCount">0</span>/2000
                            </div>
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
    <script src="js/theme.min.js"></script>
    <script src="js/vendors/color-modes.js"></script>
    <script src="js/libs/scrollcue/scrollCue.min.js"></script>
    <script src="js/vendors/scrollcue.js"></script>
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

    <!-- ══ JS — Formulário de Contacto ══════════════════════════ -->
    <script>
    (function() {
        'use strict';

        var form = document.getElementById('contactForm');
        var alertEl = document.getElementById('contactAlert');
        var successEl = document.getElementById('contactSuccess');
        var submitBtn = document.getElementById('contactSubmitBtn');
        var btnText = document.getElementById('contactBtnText');
        var btnLoading = document.getElementById('contactBtnLoading');
        var csrfInput = document.getElementById('contactCsrf');
        var charCount = document.getElementById('contactCharCount');
        var textarea = document.getElementById('message_user');

        // Contador de caracteres
        if (textarea && charCount) {
            textarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
                charCount.classList.toggle('text-danger', this.value.length > 1800);
            });
        }

        function setLoading(on) {
            submitBtn.disabled = on;
            btnText.classList.toggle('d-none', on);
            btnLoading.classList.toggle('d-none', !on);
        }

        function showAlert(type, msg) {
            alertEl.className = 'alert alert-' + type + ' mb-3';
            alertEl.textContent = msg;
            alertEl.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }

        window.resetContactForm = function() {
            form.reset();
            form.classList.remove('d-none');
            successEl.classList.add('d-none');
            alertEl.className = 'alert d-none';
            if (charCount) charCount.textContent = '0';
            setLoading(false);
        };

        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var name = document.getElementById('name_user').value.trim();
            var email = document.getElementById('email_user').value.trim();
            var phone = document.getElementById('tel_user').value.trim();
            var subject = document.getElementById('subject_user').value.trim();
            var message = textarea.value.trim();
            var csrf = csrfInput.value;

            // Validação client-side básica
            if (name.length < 3) {
                showAlert('warning', 'Por favor, insere o teu nome completo.');
                return;
            }
            if (!email.includes('@')) {
                showAlert('warning', 'Insere um e-mail válido.');
                return;
            }
            if (subject.length < 5) {
                showAlert('warning', 'O assunto deve ter pelo menos 5 caracteres.');
                return;
            }
            if (message.length < 10) {
                showAlert('warning', 'A mensagem deve ter pelo menos 10 caracteres.');
                return;
            }

            setLoading(true);
            alertEl.className = 'alert d-none';

            fetch('ajax/contact.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf,
                        name,
                        email,
                        phone,
                        subject,
                        message
                    })
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    setLoading(false);
                    if (data.success) {
                        if (data.new_csrf) csrfInput.value = data.new_csrf;
                        form.classList.add('d-none');
                        successEl.classList.remove('d-none');
                        successEl.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
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

    <!-- ══ JS — Modal Feedback ══════════════════════════════════ -->
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

            fetch('ajax/feedback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf: csrfInput.value,
                        name,
                        subject,
                        message,
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