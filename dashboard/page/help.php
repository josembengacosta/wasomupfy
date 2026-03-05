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

  <title>Ajuda — Wasom Upfy</title>

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

  <style>
    /* Estilos específicos da página de ajuda */
    .help-header {
      background: linear-gradient(135deg, #FF0089 0%, #FF4D4D 100%);
      border-radius: 30px;
      padding: 3rem 2rem;
      margin-bottom: 2rem;
      color: white;
      position: relative;
      overflow: hidden;
      text-align: center;
    }

    .help-header::before {
      content: '\F431';
      font-family: 'bootstrap-icons';
      position: absolute;
      left: -20px;
      bottom: -20px;
      font-size: 12rem;
      opacity: 0.1;
      color: white;
      transform: rotate(-15deg);
    }

    .help-header::after {
      content: '\F44F';
      font-family: 'bootstrap-icons';
      position: absolute;
      right: -20px;
      top: -20px;
      font-size: 10rem;
      opacity: 0.1;
      color: white;
      transform: rotate(15deg);
    }

    .help-header h1 {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 1rem;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
      position: relative;
      z-index: 2;
    }

    .help-header p {
      font-size: 1.2rem;
      max-width: 700px;
      margin: 0 auto;
      opacity: 0.95;
      position: relative;
      z-index: 2;
    }

    .search-box {
      max-width: 600px;
      margin: 2rem auto 0;
      position: relative;
      z-index: 2;
    }

    .search-box .input-group {
      background: white;
      border-radius: 50px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .search-box input {
      border: none;
      padding: 1rem 1.5rem;
      font-size: 1rem;
    }

    .search-box input:focus {
      box-shadow: none;
      outline: none;
    }

    .search-box button {
      background: white;
      border: none;
      padding: 0 2rem;
      color: #FF0089;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .search-box button:hover {
      background: #FF0089;
      color: white;
    }

    .help-category-card {
      background: white;
      border-radius: 20px;
      padding: 2rem 1.5rem;
      text-align: center;
      height: 100%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
    }

    .help-category-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(255, 0, 137, 0.15);
      border-color: #FF0089;
    }

    .help-category-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, rgba(255, 0, 137, 0.1), rgba(255, 77, 77, 0.1));
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }

    .help-category-icon i {
      font-size: 3rem;
      color: #FF0089;
    }

    .help-category-card h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .help-category-card p {
      color: #6c757d;
      margin-bottom: 1rem;
      font-size: 0.95rem;
    }

    .help-category-card .badge {
      background: #FF0089;
      color: white;
      font-weight: 500;
      padding: 0.5rem 1rem;
    }

    .faq-item {
      background: white;
      border-radius: 15px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }

    .faq-item:hover {
      box-shadow: 0 10px 30px rgba(255, 0, 137, 0.1);
      border-left-color: #FF0089;
      transform: translateX(5px);
    }

    .faq-question {
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
    }

    .faq-question h5 {
      margin: 0;
      font-weight: 600;
      color: #333;
    }

    .faq-question i {
      font-size: 1.2rem;
      color: #FF0089;
      transition: transform 0.3s ease;
    }

    .faq-question[aria-expanded="true"] i {
      transform: rotate(180deg);
    }

    .faq-answer {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #e9ecef;
      color: #6c757d;
    }

    .tutorial-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      height: 100%;
    }

    .tutorial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(255, 0, 137, 0.15);
    }

    .tutorial-thumbnail {
      position: relative;
      padding-top: 56.25%;
      /* 16:9 */
      background: linear-gradient(135deg, #FF0089, #FF4D4D);
      overflow: hidden;
    }

    .tutorial-thumbnail img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.8;
      transition: transform 0.5s ease;
    }

    .tutorial-card:hover .tutorial-thumbnail img {
      transform: scale(1.1);
    }

    .tutorial-thumbnail .play-button {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 50px;
      height: 50px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FF0089;
      font-size: 1.5rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
    }

    .tutorial-card:hover .play-button {
      background: #FF0089;
      color: white;
      transform: translate(-50%, -50%) scale(1.1);
    }

    .tutorial-info {
      padding: 1.5rem;
    }

    .tutorial-info h5 {
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .tutorial-info p {
      color: #6c757d;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .tutorial-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.8rem;
      color: #6c757d;
    }

    .tutorial-meta i {
      margin-right: 0.3rem;
      color: #FF0089;
    }

    .support-option {
      background: white;
      border-radius: 20px;
      padding: 2rem;
      text-align: center;
      height: 100%;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .support-option:hover {
      border-color: #FF0089;
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 0, 137, 0.1);
    }

    .support-option i {
      font-size: 3rem;
      color: #FF0089;
      margin-bottom: 1rem;
    }

    .support-option h4 {
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .support-option p {
      color: #6c757d;
      margin-bottom: 1.5rem;
    }

    .btn-help {
      background: linear-gradient(135deg, #FF0089, #FF4D4D);
      border: none;
      color: white;
      padding: 0.20rem 2rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-help:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(255, 0, 137, 0.3);
      color: white;
    }

    .btn-help-outline {
      background: transparent;
      border: 2px solid #FF0089;
      color: #FF0089;
      padding: 0.20rem 2rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-help-outline:hover {
      background: #FF0089;
      color: white;
    }

    .contact-info {
      background: #f8f9fa;
      border-radius: 15px;
      padding: 1.5rem;
    }

    .contact-item {
      display: flex;
      align-items: center;
      margin-bottom: 1rem;
    }

    .contact-item i {
      width: 40px;
      height: 40px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FF0089;
      margin-right: 1rem;
      box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05);
    }

    .contact-item div {
      flex: 1;
    }

    .contact-item strong {
      display: block;
      font-size: 0.9rem;
    }

    .contact-item span {
      color: #6c757d;
      font-size: 0.85rem;
    }

    .quick-link-card {
      background: white;
      border-radius: 12px;
      padding: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      text-decoration: none;
      color: inherit;
    }

    .quick-link-card:hover {
      background: #FF0089;
      color: white;
      transform: translateX(5px);
    }

    .quick-link-card:hover i {
      color: white;
    }

    .quick-link-card i {
      font-size: 2rem;
      color: #FF0089;
      transition: color 0.3s ease;
    }

    .quick-link-card div h6 {
      margin: 0;
      font-weight: 600;
    }

    .quick-link-card div small {
      color: #6c757d;
    }

    .quick-link-card:hover div small {
      color: rgba(255, 255, 255, 0.8);
    }

    @media (max-width: 768px) {
      .help-header {
        padding: 2rem 1rem;
      }

      .help-header h1 {
        font-size: 2rem;
      }

      .help-header p {
        font-size: 1rem;
      }

      .search-box {
        margin-top: 1.5rem;
      }

      .search-box input {
        padding: 0.75rem 1rem;
      }

      .help-category-card {
        padding: 1.5rem;
      }

      .help-category-icon {
        width: 60px;
        height: 60px;
      }

      .help-category-icon i {
        font-size: 2rem;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg" aria-label="Menu principal">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
        aria-controls="offcanvasMenu">
        <span class="navbar-toggler-icon">
          <i class="bi bi-list text-white fs-1"></i>
        </span>
      </button>

      <a class="navbar-brand" href="../painel">
        <span class="text-light brand-text">WASOM UPFY</span>
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
            <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal</a>
          </li>
        </ul>
      </div>

      <!-- User Menu -->
      <div class="user-menu d-flex align-items-center">
        <button class="theme-toggle btn btn-link text-white p-0 me-2" id="themeToggle">
          <i class="bi bi-sun" id="themeIcon"></i>
        </button>

        <a href="../page/notifications" class="text-white me-2 position-relative">
          <i class="bi bi-bell fs-4"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            9
            <span class="visually-hidden">notificações</span>
          </span>
        </a>

        <div class="dropdown">
          <button class="btn btn-link text-white p-0" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-4"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <span class="dropdown-item-text">
                <strong><?php echo $first_name; ?></strong><br>
                <small class="text-muted">Conta 560108</small>
              </span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="../user/profile">Meu Perfil</a></li>
            <li><a class="dropdown-item" href="../account/manage-account">Gestão de Conta</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="../page/settings">Configurações</a></li>
            <li><a class="dropdown-item" href="../page/notifications">Notificações</a></li>
            <li><a class="dropdown-item" href="../services/available-services">Serviços disponíveis</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="../page/about">Sobre</a></li>
            <li><a class="dropdown-item" href="../page/support">Suporte</a></li>
            <li><a class="dropdown-item" href="../page/faq">FAQ</a></li>
            <li><a class="dropdown-item active" href="../page/help">Ajuda</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right me-2"></i> Sair
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- Offcanvas Menu -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">
        <span class="brand-text">WASOM UPFY</span>
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="../painel">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../launch/releases">Lançamentos</a></li>
        <li class="nav-item"><a class="nav-link" href="../analytics/statistics">Estatísticas</a></li>
        <li class="nav-item"><a class="nav-link" href="../finances/overview">Finanças</a></li>
        <li class="nav-item"><a class="nav-link" href="../artists/artists-list">Artistas</a></li>
        <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy">Unificação de canal</a></li>

        <li class="nav-item d-lg-none">
          <hr>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="../user/profile">Meu Perfil</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="../page/settings">Configurações</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="../page/notifications">Notificações</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="../page/about">Sobre</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link active" href="../page/help">Ajuda</a>
        </li>
      </ul>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="connectionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <strong class="me-auto">Conexão</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div class="toast-body">
        Você está offline. Alguns dados podem estar desatualizados.
        <div class="mt-2">
          <button class="btn btn-sm btn-primary" onclick="tryReconnect()">
            Tentar Reconectar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <main class="container my-4">
    <!-- Help Header -->
    <div class="help-header">
      <h1>
        <i class="bi bi-question-circle-fill me-3"></i>
        Central de Ajuda
      </h1>
      <p>
        Encontre respostas para suas dúvidas, tutoriais passo a passo e suporte especializado
        para aproveitar ao máximo a plataforma Wasom Upfy.
      </p>

      <!-- Search Box -->
      <div class="search-box">
        <div class="input-group">
          <input type="text" class="form-control"
            placeholder="Buscar ajuda... Ex: como lançar música, saques, estatísticas" id="helpSearch">
          <button class="btn" type="button" onclick="searchHelp()">
            <i class="bi bi-search me-2"></i> Buscar
          </button>
        </div>
      </div>
    </div>

    <!-- Categorias de Ajuda -->
    <div class="row g-4 mb-5">
      <div class="col-md-3 col-6">
        <div class="help-category-card" onclick="window.location.href='faq?categoria=lancamentos'">
          <div class="help-category-icon">
            <i class="bi bi-disc"></i>
          </div>
          <h3>Lançamentos</h3>
          <p>Como publicar e gerenciar suas músicas</p>
          <span class="badge">12 artigos</span>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="help-category-card" onclick="window.location.href='faq?categoria=financeiro'">
          <div class="help-category-icon">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <h3>Financeiro</h3>
          <p>Saques, royalties e pagamentos</p>
          <span class="badge">8 artigos</span>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="help-category-card" onclick="window.location.href='faq?categoria=conta'">
          <div class="help-category-icon">
            <i class="bi bi-person-circle"></i>
          </div>
          <h3>Conta</h3>
          <p>Gerenciamento de perfil e planos</p>
          <span class="badge">10 artigos</span>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="help-category-card" onclick="window.location.href='faq?categoria=youtube'">
          <div class="help-category-icon">
            <i class="bi bi-youtube"></i>
          </div>
          <h3>YouTube</h3>
          <p>Unificação de canais e Art Tracks</p>
          <span class="badge">6 artigos</span>
        </div>
      </div>
    </div>

    <!-- FAQ em Destaque -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="mb-0">
            <i class="bi bi-patch-question me-2" style="color: #FF0089;"></i>
            Perguntas Frequentes
          </h2>
          <a href="faq" class="btn btn-help-outline btn-sm">
            Ver todas <i class="bi bi-arrow-right ms-2"></i>
          </a>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="faq-item">
          <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="false">
            <h5>Como criar um novo lançamento?</h5>
            <i class="bi bi-chevron-down"></i>
          </div>
          <div id="faq1" class="collapse">
            <div class="faq-answer">
              <p>Para criar um novo lançamento:</p>
              <ol class="mb-2">
                <li>Acesse a seção "Lançamentos" no menu principal</li>
                <li>Clique no botão "Novo Lançamento"</li>
                <li>Preencha as informações da música (título, artista, gênero)</li>
                <li>Faça upload do arquivo de áudio (WAV ou FLAC)</li>
                <li>Adicione a arte da capa (mínimo 3000x3000px)</li>
                <li>Escolha a data de lançamento</li>
                <li>Revise e confirme o envio</li>
              </ol>
              <p class="mb-0">Seu lançamento será processado em até 72 horas.</p>
            </div>
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false">
            <h5>Como sacar meu saldo?</h5>
            <i class="bi bi-chevron-down"></i>
          </div>
          <div id="faq2" class="collapse">
            <div class="faq-answer">
              <p>Para realizar um saque:</p>
              <ol class="mb-2">
                <li>Vá para a seção "Finanças"</li>
                <li>Clique em "Sacar" no card de saldo disponível</li>
                <li>Selecione o método de saque (IBAN, PayPal, Express)</li>
                <li>Informe o valor desejado</li>
                <li>Confirme sua senha</li>
                <li>Aguarde a confirmação por e-mail</li>
              </ol>
              <p class="mb-0">O prazo para processamento é de 3 a 5 dias úteis.</p>
            </div>
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false">
            <h5>O que é unificação de canal YouTube?</h5>
            <i class="bi bi-chevron-down"></i>
          </div>
          <div id="faq3" class="collapse">
            <div class="faq-answer">
              <p>A unificação de canal YouTube permite:</p>
              <ul class="mb-2">
                <li>Conectar seus canais do YouTube à plataforma</li>
                <li>Sincronizar automaticamente seus Art Tracks</li>
                <li>Acompanhar streams e receitas em tempo real</li>
                <li>Gerenciar todos os seus vídeos musicais</li>
                <li>Detectar conteúdo gerado por fãs</li>
              </ul>
              <p class="mb-0">É gratuito e disponível para todos os planos.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="faq-item">
          <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false">
            <h5>Como funcionam os royalties?</h5>
            <i class="bi bi-chevron-down"></i>
          </div>
          <div id="faq4" class="collapse">
            <div class="faq-answer">
              <p>Nossa política de royalties:</p>
              <ul class="mb-2">
                <li><strong>90% de royalties</strong> para todos os planos</li>
                <li>Pagamentos mensais, até dia 15</li>
                <li>Relatórios detalhados por plataforma</li>
                <li>Valor mínimo para saque: 1.000 Kz</li>
                <li>Sem taxas ocultas ou anuais</li>
              </ul>
              <p class="mb-0">Os 10% restantes cobrem custos de distribuição e operação.</p>
            </div>
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false">
            <h5>Quais formatos de áudio são aceitos?</h5>
            <i class="bi bi-chevron-down"></i>
          </div>
          <div id="faq5" class="collapse">
            <div class="faq-answer">
              <p>Formatos aceitos para upload:</p>
              <ul class="mb-2">
                <li><strong>WAV</strong> (recomendado) - 16 ou 24 bits, 44.1kHz</li>
                <li><strong>FLAC</strong> - Qualidade sem perdas</li>
                <li><strong>AIFF</strong> - Compatível com Apple</li>
                <li><strong>MP3</strong> - 320kbps (menos recomendado)</li>
              </ul>
              <p class="mb-0">Tamanho máximo por arquivo: 1GB.</p>
            </div>
          </div>
        </div>

        <div class="faq-item">
          <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false">
            <h5>Posso trocar de plano?</h5>
            <i class="bi bi-chevron-down"></i>
          </div>
          <div id="faq6" class="collapse">
            <div class="faq-answer">
              <p>Sim! Você pode alterar seu plano a qualquer momento:</p>
              <ul class="mb-2">
                <li><strong>Upgrade:</strong> Disponível imediatamente, valor proporcional</li>
                <li><strong>Downgrade:</strong> Disponível ao final do ciclo atual</li>
                <li>Entre em contato com o suporte para alterações</li>
                <li>Consulte a seção "Conta e serviços" para mais detalhes</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tutoriais em Vídeo -->
    <div class="row mb-5">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="bi bi-play-circle-fill me-2" style="color: #FF0089;"></i>
          Tutoriais em Vídeo
        </h2>
      </div>

      <div class="col-md-4 mb-3">
        <div class="tutorial-card">
          <div class="tutorial-thumbnail">
            <img src="https://via.placeholder.com/320x180/FF0089/FFFFFF?text=Guia+Iniciante" alt="Tutorial thumbnail">
            <div class="play-button">
              <i class="bi bi-play-fill"></i>
            </div>
          </div>
          <div class="tutorial-info">
            <h5>Guia Completo para Iniciantes</h5>
            <p>Aprenda os primeiros passos na plataforma</p>
            <div class="tutorial-meta">
              <span><i class="bi bi-clock"></i> 15 min</span>
              <span><i class="bi bi-eye"></i> 2.5k visualizações</span>
            </div>
            <button class="btn btn-help-outline btn-sm w-100 mt-2" onclick="openTutorial('iniciante')">
              Assistir Tutorial
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="tutorial-card">
          <div class="tutorial-thumbnail">
            <img src="https://via.placeholder.com/320x180/FF4D4D/FFFFFF?text=Como+Lançar" alt="Tutorial thumbnail">
            <div class="play-button">
              <i class="bi bi-play-fill"></i>
            </div>
          </div>
          <div class="tutorial-info">
            <h5>Como Lançar sua Primeira Música</h5>
            <p>Passo a passo do processo de lançamento</p>
            <div class="tutorial-meta">
              <span><i class="bi bi-clock"></i> 8 min</span>
              <span><i class="bi bi-eye"></i> 3.2k visualizações</span>
            </div>
            <button class="btn btn-help-outline btn-sm w-100 mt-2" onclick="openTutorial('lancamento')">
              Assistir Tutorial
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="tutorial-card">
          <div class="tutorial-thumbnail">
            <img src="https://via.placeholder.com/320x180/FF0089/FFFFFF?text=Análise+Dados" alt="Tutorial thumbnail">
            <div class="play-button">
              <i class="bi bi-play-fill"></i>
            </div>
          </div>
          <div class="tutorial-info">
            <h5>Analisando Estatísticas e Métricas</h5>
            <p>Entenda seus dados e aumente seus streams</p>
            <div class="tutorial-meta">
              <span><i class="bi bi-clock"></i> 12 min</span>
              <span><i class="bi bi-eye"></i> 1.8k visualizações</span>
            </div>
            <button class="btn btn-help-outline btn-sm w-100 mt-2" onclick="openTutorial('estatisticas')">
              Assistir Tutorial
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Opções de Suporte -->
    <div class="row mb-5">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="bi bi-headset me-2" style="color: #FF0089;"></i>
          Precisa de Ajuda Personalizada?
        </h2>
      </div>

      <div class="col-md-4 mb-3">
        <div class="support-option">
          <i class="bi bi-chat-dots"></i>
          <h4>Chat ao Vivo</h4>
          <p>Atendimento em tempo real com nossa equipe</p>
          <p class="text-success"><i class="bi bi-circle-fill me-1 small"></i> Online agora</p>
          <button class="btn btn-help" onclick="startChat()">
            <i class="bi bi-chat me-2"></i> Iniciar Chat
          </button>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="support-option">
          <i class="bi bi-envelope"></i>
          <h4>E-mail</h4>
          <p>Resposta em até 24 horas úteis</p>
          <p class="text-muted small">suporte@wasomupfy.com</p>
          <button class="btn btn-help-outline" onclick="window.location.href='mailto:suporte@wasomupfy.com'">
            <i class="bi bi-send me-2"></i> Enviar E-mail
          </button>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="support-option">
          <i class="bi bi-whatsapp"></i>
          <h4>WhatsApp</h4>
          <p>Atendimento rápido via mensagem</p>
          <p class="text-muted small">+244 123 456 789</p>
          <button class="btn btn-help-outline" onclick="window.open('https://wa.me/244123456789')">
            <i class="bi bi-whatsapp me-2"></i> Chamar no WhatsApp
          </button>
        </div>
      </div>
    </div>

    <!-- Informações de Contato e Links Rápidos -->
    <div class="row g-4">
      <div class="col-md-6">
        <div class="contact-info">
          <h5 class="mb-3">
            <i class="bi bi-info-circle me-2" style="color: #FF0089;"></i>
            Horário de Atendimento
          </h5>

          <div class="contact-item">
            <i class="bi bi-calendar-check"></i>
            <div>
              <strong>Segunda a Sexta</strong>
              <span>9h às 18h (WAT)</span>
            </div>
          </div>

          <div class="contact-item">
            <i class="bi bi-calendar"></i>
            <div>
              <strong>Sábado</strong>
              <span>9h às 12h (WAT)</span>
            </div>
          </div>

          <div class="contact-item">
            <i class="bi bi-calendar-x"></i>
            <div>
              <strong>Domingo e Feriados</strong>
              <span>Fechado</span>
            </div>
          </div>

          <hr class="my-3">

          <div class="contact-item">
            <i class="bi bi-clock-history"></i>
            <div>
              <strong>Tempo médio de resposta</strong>
              <span>Chat: 5 min | E-mail: 4h | WhatsApp: 30 min</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="contact-info">
          <h5 class="mb-3">
            <i class="bi bi-link-45deg me-2" style="color: #FF0089;"></i>
            Links Úteis
          </h5>

          <div class="quick-link-card mb-2" onclick="window.location.href='faq'">
            <i class="bi bi-question-circle"></i>
            <div>
              <h6>Perguntas Frequentes (FAQ)</h6>
              <small>Respostas para as dúvidas mais comuns</small>
            </div>
          </div>

          <div class="quick-link-card mb-2" onclick="window.location.href='../services/available-services'">
            <i class="bi bi-star"></i>
            <div>
              <h6>Planos e Serviços</h6>
              <small>Compare todos os planos disponíveis</small>
            </div>
          </div>

          <div class="quick-link-card mb-2" onclick="window.location.href='../../planos'">
            <i class="bi bi-arrow-up-circle"></i>
            <div>
              <h6>Fazer Upgrade</h6>
              <small>Melhore seu plano e ganhe mais benefícios</small>
            </div>
          </div>

          <div class="quick-link-card" onclick="window.location.href='../../about'">
            <i class="bi bi-info-circle"></i>
            <div>
              <h6>Sobre o Wasom Upfy</h6>
              <small>Conheça nossa história e missão</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Versão -->
    <div class="text-center mt-5">
      <small class="text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Versão da Central de Ajuda: 2.0.0 (2025) | Última atualização: 19/02/2025
      </small>
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

    // Função de busca
    function searchHelp() {
      const searchTerm = document.getElementById('helpSearch').value;
      if (searchTerm.trim()) {
        window.location.href = `faq?search=${encodeURIComponent(searchTerm)}`;
      }
    }

    // Permitir busca com Enter
    document.getElementById('helpSearch').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        searchHelp();
      }
    });

    // Abrir tutorial
    function openTutorial(tutorial) {
      // Simular abertura de tutorial
      alert(`Abrindo tutorial: ${tutorial}. Em produção, isso redirecionaria para a página do tutorial.`);
      // window.location.href = `../tutorials/${tutorial}`;
    }

    // Iniciar chat
    function startChat() {
      alert('Iniciando chat ao vivo... (Funcionalidade em desenvolvimento)');
    }

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
        location.reload();
      } else {
        alert('Ainda sem conexão. Verifique sua internet.');
      }
    }

    // Logout
    function logout() {
      window.location = "../../logout";
    }
  </script>
</body>

</html>