<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="author" content="José Mbenga da Costa" />
  <meta name="theme-color" content="#FF0089" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

  <!-- Preconnect para CDNs -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net">

  <title>Sobre — Wasom Upfy</title>

  <!-- Favicon -->
  <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
  <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png" />
  <link rel="manifest" href="../manifest.json" />
  <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />

  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../css/lastest-style.css" />
  <link rel="stylesheet" href="../../css/about.css" />
</head>

<!-- Tela de Carregamento -->
<!-- <div class="loading-screen" id="loadingScreen">
        <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg" class="loading-logo">
            <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2"/>
            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
        </svg>
        <div class="spinner"></div>
    </div> -->

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <!-- Menu Button (Left) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
      aria-controls="offcanvasMenu">
      <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
    </button>

    <!-- Logo (Center on Mobile, Left on Desktop) -->
    <a class="navbar-brand" href="../painel">
      <!-- SVG Logo Wasom Upfy -->
      <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
      <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: capitalize;
              font-family: Arial, sans-serif;
            ">WASOM UPFY</span>
    </a>

    <!-- Desktop Menu -->
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav m-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal
            YouTube</a>
        </li>
      </ul>
    </div>

    <!-- User Icon (Right) -->
    <div class="user-menu d-flex align-items-center">
      <!-- Theme Toggle Button -->
      <a class="theme-toggle text-white me-2" id="themeToggle">
        <i class="bi bi-sun" id="themeIcon"></i>
      </a>
      <a href="../notifications" class="text-white me-2" aria-label="Notificações">
        <i class="bi bi-bell fs-4"></i>
        <span class="badge bg-danger">9</span>
      </a>
      <a href="#" class="text-white" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle fs-4"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i>
            <strong><?php echo $first_name; ?></strong></a>
          <div class="text-white-50">
            &nbsp; &nbsp; &nbsp; &nbsp; (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)
          </div>
        </li>
        <li>
          <hr class="dropdown-divider" />
        </li>
        <li>
          <a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
        </li>
        <li>
          <a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i> Gestão de
            Conta</a>
        </li>
        <li>
          <hr class="dropdown-divider" />
        </li>
        <li>
          <a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i> Configurações</a>
        </li>
        <li>
          <a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i> Notificações</a>
        </li>
        <li>
          <a class="dropdown-item" href="../services/available-services"><i class="bi bi-star me-2"></i> Conta e
            serviços disponíveis</a>
        </li>
        <li>
          <a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy"><i
              class="bi bi-box-arrow-right me-2"></i> Desconectar-se</a>
        </li>
        <li>
          <hr class="dropdown-divider" />
        </li>
        <li>
          <a class="dropdown-item" href="../page/about"><i class="bi bi-info-circle me-2"></i> Sobre</a>
        </li>
        <li>
          <a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Enviar pedido de
            suporte</a>
        </li>
        <li>
          <a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i> Perguntas
            frequentes</a>
        </li>
        <li>
          <a class="dropdown-item" href="../page/help"><i class="bi bi-question-circle me-2"></i> Ajuda</a>
        </li>
        <li>
          <hr class="dropdown-divider" />
        </li>
        <li>
          <span class="dropdown-item-text" id="versionDropdown"></span>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Offcanvas Menu par Mobile e Desktop -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasMenuLabel">
      <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
      <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: capitalize;
              font-family: Arial, sans-serif;
            ">WASOM UPFY</span>
    </h5>
    <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  <div class="offcanvas-body">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal
          YouTube</a>
      </li>
      <!-- Links secundários exibidos apenas em mobile -->
      <li class="nav-item d-lg-none">
        <a class="nav-link" href="../user/profile"><i class="bi bi-person-circle"></i> Meu Perfil</a>
      </li>
      <li class="nav-item d-lg-none">
        <a class="nav-link active" href="../page/settings"><i class="bi bi-gear"></i> Configurações</a>
      </li>
      <li class="nav-item d-lg-none">
        <a class="nav-link" href="../page/notifications"><i class="bi bi-bell"></i> Notificações</a>
      </li>
      <li class="nav-item d-lg-none">
        <a class="nav-link" href="../page/about"><i class="bi bi-info-circle"></i> Sobre</a>
      </li>
      <li class="nav-item d-lg-none">
        <a class="nav-link" href="../services/available-services"><i class="bi bi-star"></i> Conta e serviços
          disponíveis</a>
      </li>
      <li class="nav-item d-lg-none">
        <a class="nav-link" href="../page/help"><i class="bi bi-question-circle"></i> Ajuda</a>
      </li>
      <li class="nav-item d-lg-none">
        <a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy"><i
            class="bi bi-box-arrow-right"></i> Desconectar-se</a>
      </li>
    </ul>
  </div>
</div>

<!-- Toast para Notificações de Status -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="connectionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">Conexão</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
    </div>
    <div class="toast-body">
      Você está offline. Alguns dados podem estar desatualizados.
      <div class="mt-2">
        <button class="btn btn-pink btn-sm" onclick="tryReconnect()">
          Tentar Reconectar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<main class="container my-4">
  <!-- About Header -->
  <div class="about-header">
    <div class="about-logo">
      <span>WU</span>
    </div>
    <h1 class="about-title">Wasom Upfy</h1>
    <p class="about-subtitle">
      A plataforma completa para artistas e gravadoras gerenciarem sua carreira musical
    </p>
  </div>

  <!-- Stats Row -->
  <div class="row g-4 mb-5 text-center">
    <div class="col-md-3 col-6">
      <div class="about-card">
        <div class="stat-number">50K+</div>
        <div class="stat-label">Artistas</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="about-card">
        <div class="stat-number">2M+</div>
        <div class="stat-label">Músicas</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="about-card">
        <div class="stat-number">150+</div>
        <div class="stat-label">Países</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="about-card">
        <div class="stat-number">10B+</div>
        <div class="stat-label">Streams</div>
      </div>
    </div>
  </div>

  <!-- Nossa História -->
  <h2 class="text-center mb-4" style="color: #FF0089; font-weight: 700;">
    <i class="bi bi-clock-history me-2"></i> Nossa História
  </h2>

  <div class="timeline mb-5">
    <div class="timeline-item">
      <div class="timeline-content">
        <span class="timeline-year">2020</span>
        <h4>O Início</h4>
        <p class="text-muted">
          Wasom Upfy nasceu da necessidade de artistas independentes terem uma plataforma
          completa para gerenciar suas carreiras. Começamos com um pequeno time de 3 pessoas
          em Luanda, Angola.
        </p>
      </div>
    </div>

    <div class="timeline-item">
      <div class="timeline-content">
        <span class="timeline-year">2021</span>
        <h4>Primeiras Parcerias</h4>
        <p class="text-muted">
          Fechamos parcerias com as principais plataformas de streaming: Spotify, Apple Music,
          Deezer e YouTube Music. Nosso catálogo ultrapassou 10.000 músicas.
        </p>
      </div>
    </div>

    <div class="timeline-item">
      <div class="timeline-content">
        <span class="timeline-year">2022</span>
        <h4>Expansão Internacional</h4>
        <p class="text-muted">
          Abrimos escritórios em Portugal e Brasil. Alcançamos a marca de 10.000 artistas
          cadastrados e lançamos nosso sistema de analytics em tempo real.
        </p>
      </div>
    </div>

    <div class="timeline-item">
      <div class="timeline-content">
        <span class="timeline-year">2023</span>
        <h4>Inovação</h4>
        <p class="text-muted">
          Lançamos o sistema de unificação de canais do YouTube e ferramentas avançadas de
          distribuição. Nos tornamos referência no mercado lusófono.
        </p>
      </div>
    </div>

    <div class="timeline-item">
      <div class="timeline-content">
        <span class="timeline-year">2024</span>
        <h4>Presente</h4>
        <p class="text-muted">
          Hoje, somos mais de 50 colaboradores espalhados pelo mundo, com mais de 50.000 artistas
          confiando em nossa plataforma para distribuir e gerenciar suas músicas.
        </p>
      </div>
    </div>
  </div>

  <!-- Missão, Visão e Valores -->
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="about-card text-center h-100">
        <div class="about-icon mx-auto">
          <i class="bi bi-bullseye"></i>
        </div>
        <h3>Missão</h3>
        <p>
          Empoderar artistas e gravadoras com ferramentas inovadoras para gerenciar,
          distribuir e monetizar sua música de forma simples e eficiente.
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="about-card text-center h-100">
        <div class="about-icon mx-auto">
          <i class="bi bi-eye"></i>
        </div>
        <h3>Visão</h3>
        <p>
          Ser a plataforma líder na gestão musical em países lusófonos, conectando artistas
          a oportunidades globais e transformando carreiras.
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="about-card text-center h-100">
        <div class="about-icon mx-auto">
          <i class="bi bi-heart"></i>
        </div>
        <h3>Valores</h3>
        <p>
          Inovação, transparência, paixão pela música, compromisso com artistas,
          diversidade e excelência em tudo o que fazemos.
        </p>
      </div>
    </div>
  </div>

  <!-- Proposta de Valor -->
  <h2 class="text-center mb-4" style="color: #FF0089; font-weight: 700;">
    <i class="bi bi-star me-2"></i> Por que escolher Wasom Upfy?
  </h2>

  <div class="row g-4 mb-5">
    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-globe2"></i>
        <h4>Distribuição Global</h4>
        <p>Alcance as principais plataformas em mais de 150 países</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-graph-up"></i>
        <h4>Analytics em Tempo Real</h4>
        <p>Acompanhe streams, receitas e engajamento ao vivo</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-currency-dollar"></i>
        <h4>Pagamentos Rápidos</h4>
        <p>Receba seus royalties de forma transparente e sem atrasos</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-headset"></i>
        <h4>Suporte Dedicado</h4>
        <p>Equipe especializada pronta para ajudar em português</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-youtube"></i>
        <h4>Unificação YouTube</h4>
        <p>Gerencie todos os seus canais em um só lugar</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-shield-check"></i>
        <h4>Proteção de Direitos</h4>
        <p>Registro e proteção dos seus direitos autorais</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-people"></i>
        <h4>Rede de Artistas</h4>
        <p>Conecte-se com outros músicos e colaboradores</p>
      </div>
    </div>

    <div class="col-md-3 col-6">
      <div class="value-prop">
        <i class="bi bi-megaphone"></i>
        <h4>Ferramentas de Marketing</h4>
        <p>Promova seus lançamentos e alcance novos fãs</p>
      </div>
    </div>
  </div>

  <!-- Nossa Equipe -->
  <h2 class="text-center mb-4" style="color: #FF0089; font-weight: 700;">
    <i class="bi bi-people-fill me-2"></i> Nossa Equipe
  </h2>

  <div class="row g-4 mb-5">
    <div class="col-lg-3 col-md-6">
      <div class="team-member">
        <div class="member-avatar">
          <img src="https://via.placeholder.com/150" alt="José Mbenga">
        </div>
        <h5 class="member-name">José Mbenga da Costa</h5>
        <div class="member-role">Founder & CEO</div>
        <div class="member-social">
          <a href="#"><i class="bi bi-linkedin"></i></a>
          <a href="#"><i class="bi bi-twitter-x"></i></a>
          <a href="#"><i class="bi bi-envelope"></i></a>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="team-member">
        <div class="member-avatar">
          <img src="https://via.placeholder.com/150" alt="Ana Silva">
        </div>
        <h5 class="member-name">Ana Silva</h5>
        <div class="member-role">Diretora de Produto</div>
        <div class="member-social">
          <a href="#"><i class="bi bi-linkedin"></i></a>
          <a href="#"><i class="bi bi-twitter-x"></i></a>
          <a href="#"><i class="bi bi-envelope"></i></a>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="team-member">
        <div class="member-avatar">
          <img src="https://via.placeholder.com/150" alt="Carlos Santos">
        </div>
        <h5 class="member-name">Carlos Santos</h5>
        <div class="member-role">CTO</div>
        <div class="member-social">
          <a href="#"><i class="bi bi-linkedin"></i></a>
          <a href="#"><i class="bi bi-github"></i></a>
          <a href="#"><i class="bi bi-envelope"></i></a>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="team-member">
        <div class="member-avatar">
          <img src="https://via.placeholder.com/150" alt="Mariana Costa">
        </div>
        <h5 class="member-name">Mariana Costa</h5>
        <div class="member-role">Head de Marketing</div>
        <div class="member-social">
          <a href="#"><i class="bi bi-linkedin"></i></a>
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-envelope"></i></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Depoimentos -->
  <h2 class="text-center mb-4" style="color: #FF0089; font-weight: 700;">
    <i class="bi bi-chat-quote me-2"></i> O que dizem nossos artistas
  </h2>

  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="testimonial-card">
        <p class="mb-3">
          "Wasom Upfy revolucionou a forma como gerencio minha carreira. As ferramentas de analytics
          me ajudaram a entender melhor meu público e aumentar meus streams em 300%."
        </p>
        <div class="testimonial-author">
          <img src="https://via.placeholder.com/50" alt="DJ Mark">
          <div>
            <h6>DJ Mark</h6>
            <small>Artista • 2M streams</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="testimonial-card">
        <p class="mb-3">
          "Como gravadora independente, precisávamos de uma plataforma completa e confiável.
          Wasom Upfy superou todas as expectativas com suporte em português e pagamentos transparentes."
        </p>
        <div class="testimonial-author">
          <img src="https://via.placeholder.com/50" alt="Eleven Records">
          <div>
            <h6>Eleven Records</h6>
            <small>Gravadora • 50+ artistas</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="testimonial-card">
        <p class="mb-3">
          "A unificação de canais do YouTube é simplesmente incrível! Economizo horas todas as
          semanas gerenciando meu conteúdo. Recomendo fortemente."
        </p>
        <div class="testimonial-author">
          <img src="https://via.placeholder.com/50" alt="Sarah Moon">
          <div>
            <h6>Sarah Moon</h6>
            <small>Cantora • 500K inscritos</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tecnologias -->
  <h2 class="text-center mb-4" style="color: #FF0089; font-weight: 700;">
    <i class="bi bi-cpu me-2"></i> Tecnologias que impulsionam a plataforma
  </h2>

  <div class="text-center mb-5">
    <span class="tech-badge"><i class="bi bi-bootstrap"></i> Bootstrap</span>
    <span class="tech-badge"><i class="bi bi-filetype-js"></i> JavaScript</span>
    <span class="tech-badge"><i class="bi bi-filetype-html"></i> HTML5</span>
    <span class="tech-badge"><i class="bi bi-filetype-css"></i> CSS3</span>
    <span class="tech-badge"><i class="bi bi-graph-up"></i> Chart.js</span>
    <span class="tech-badge"><i class="bi bi-cloud"></i> AWS</span>
    <span class="tech-badge"><i class="bi bi-database"></i> MongoDB</span>
    <span class="tech-badge"><i class="bi bi-node-plus"></i> Node.js</span>
    <span class="tech-badge"><i class="bi bi-git"></i> Git</span>
    <span class="tech-badge"><i class="bi bi-github"></i> GitHub</span>
  </div>

  <!-- Contato -->
  <div class="row mb-5">
    <div class="col-md-6 mx-auto">
      <div class="about-card text-center">
        <h3 class="mb-3">Quer saber mais?</h3>
        <p class="text-muted mb-4">
          Estamos sempre abertos para conversar sobre parcerias, sugestões ou apenas para bater um papo sobre música.
        </p>
        <div class="d-flex justify-content-center gap-3">
          <a href="mailto:contato@wasomupfy.com" class="btn btn-settings">
            <i class="bi bi-envelope me-2"></i> contato@wasomupfy.com
          </a>
          <a href="support" class="btn btn-settings-outline">
            <i class="bi bi-headset me-2"></i> Suporte
          </a>
        </div>
        <hr class="my-4">
        <div class="d-flex justify-content-center gap-3">
          <a href="#" class="text-dark"><i class="bi bi-facebook fs-4"></i></a>
          <a href="#" class="text-dark"><i class="bi bi-instagram fs-4"></i></a>
          <a href="#" class="text-dark"><i class="bi bi-twitter-x fs-4"></i></a>
          <a href="#" class="text-dark"><i class="bi bi-linkedin fs-4"></i></a>
          <a href="#" class="text-dark"><i class="bi bi-youtube fs-4"></i></a>
          <a href="#" class="text-dark"><i class="bi bi-tiktok fs-4"></i></a>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Bottom Navigation for Mobile -->
<nav class="bottom-nav d-lg-none">
  <ul class="nav justify-content-around">
    <li class="nav-item">
      <a class="nav-link" href="../painel">
        <i class="bi bi-speedometer2 d-block fs-5"></i>
        <span class="small">Dashboard</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../launch/releases">
        <i class="bi bi-disc d-block fs-5"></i>
        <span class="small">Lançamentos</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../analytics/statistics">
        <i class="bi bi-bar-chart d-block fs-5"></i>
        <span class="small">Stats</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../finances/overview">
        <i class="bi bi-currency-dollar d-block fs-5"></i>
        <span class="small">Finanças</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../artists/artists-list">
        <i class="bi bi-person d-block fs-5"></i>
        <span class="small">Artistas</span>
      </a>
    </li>
  </ul>
</nav>

<!-- Footer Version -->
<div class="footer-version">
  <div class="container">
    <p class="mb-0">
      <i class="bi bi-info-circle me-2"></i>
      Wasom Upfy - Versão 2.0.0 (2025) | Feito com <i class="bi bi-heart-fill text-danger"></i> para artistas
    </p>
  </div>
</div>

<!-- Modal Logout -->
<div class="modal fade" id="logoutModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Terminar sessão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-center mb-0">
          José da Costa, você tem certeza que deseja terminar sessão?
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Não, continuar
        </button>
        <button type="button" class="btn btn-danger" onclick="logout()">
          Sim, terminar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../js/theme.wp.js"></script>
<script src="../../js/theme.wp.js"></script>
<script src="../../js/wp.tools.js"></script>

<script>
  // Inicialização
  document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Status de conexão
    updateConnectionStatus();
    window.addEventListener('online', updateConnectionStatus);
    window.addEventListener('offline', updateConnectionStatus);
  });

  // Theme Toggle
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');

  function applyTheme(theme) {
    if (theme === 'dark') {
      document.body.classList.add('dark-mode');
      document.body.classList.remove('light-mode');
      themeIcon.classList.remove('bi-sun');
      themeIcon.classList.add('bi-moon');
    } else if (theme === 'light') {
      document.body.classList.add('light-mode');
      document.body.classList.remove('dark-mode');
      themeIcon.classList.remove('bi-moon');
      themeIcon.classList.add('bi-sun');
    } else {
      const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      applyTheme(prefersDark ? 'dark' : 'light');
    }
  }

  const savedTheme = localStorage.getItem('theme') || 'system';
  applyTheme(savedTheme);

  themeToggle.addEventListener('click', () => {
    const isDarkMode = document.body.classList.contains('dark-mode');
    const newTheme = isDarkMode ? 'light' : 'dark';
    applyTheme(newTheme);
    localStorage.setItem('theme', newTheme);
  });

  // Status de conexão
  function updateConnectionStatus() {
    const isOnline = navigator.onLine;
    const connectionToast = document.getElementById('connectionToast');
    const toast = bootstrap.Toast.getOrCreateInstance(connectionToast);

    if (!isOnline) {
      connectionToast.querySelector('.toast-body').innerHTML = `
          Você está offline. Alguns dados podem estar desatualizados.
          <div class="mt-2">
            <button class="btn btn-sm btn-primary" onclick="tryReconnect()">Tentar Reconectar</button>
          </div>
        `;
      toast.show();
    }
  }

  function tryReconnect() {
    if (navigator.onLine) {
      bootstrap.Toast.getInstance(document.getElementById('connectionToast')).hide();
    } else {
      alert('Ainda sem conexão. Verifique sua internet.');
    }
  }

  // Logout
  function logout() {
    window.location = '../../logout';
  }

  // Animações de contagem (opcional)
  function animateNumbers() {
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
      const value = stat.innerText;
      if (value.includes('+')) {
        // Animação para números
      }
    });
  }
</script>
</body>

</html>