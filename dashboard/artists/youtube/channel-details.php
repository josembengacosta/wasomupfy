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
  <meta name="apple-mobile-web-app-status-bar-style" content="#FF0089" />

  <!-- Preconnect para CDNs -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" />

  <title>Detalhes do Canal - Eleven Records Official — Wasom Upfy</title>

  <!-- Favicon -->
  <link rel="shortcut icon" href="../../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
  <link rel="apple-touch-icon" href="../../../assets/img/icones/wasomupfy_fiv_512.png" />
  <link rel="manifest" href="../../manifest.json" />

  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../../css/youtube-channel-details.css" />
  <link rel="stylesheet" href="../../../css/lastest-style.css" />

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>

  <style>
    /* Estilos específicos para a página de detalhes */
    .channel-header {
      background: linear-gradient(135deg, #ff0089 0%, #ff4d4d 100%);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 2rem;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .channel-header::before {
      content: "";
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle,
          rgba(255, 255, 255, 0.1) 0%,
          transparent 70%);
      animation: rotate 20s linear infinite;
    }

    @keyframes rotate {
      from {
        transform: rotate(0deg);
      }

      to {
        transform: rotate(360deg);
      }
    }

    .channel-avatar-large {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 4px solid white;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      object-fit: cover;
    }

    .stat-card {
      border-radius: 15px;
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease;
      height: 100%;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(255, 0, 137, 0.15);
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 1rem;
    }

    .stat-icon.views {
      background: linear-gradient(135deg, #ff0089, #ff4d4d);
      color: white;
    }

    .stat-icon.subs {
      background: linear-gradient(135deg, #1db954, #1ed760);
      color: white;
    }

    .stat-icon.videos {
      background: linear-gradient(135deg, #ff0000, #cc0000);
      color: white;
    }

    .stat-icon.revenue {
      background: linear-gradient(135deg, #f59e0b, #fbbf24);
      color: white;
    }

    .metric-trend {
      font-size: 0.875rem;
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
    }

    .metric-trend.up {
      color: #10b981;
    }

    .metric-trend.down {
      color: #ef4444;
    }

    .video-card {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      height: 100%;
    }

    .video-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 0, 137, 0.15);
    }

    .video-thumbnail {
      position: relative;
      padding-top: 56.25%;
      /* 16:9 */
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      overflow: hidden;
    }

    .video-thumbnail img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .video-duration {
      position: absolute;
      bottom: 10px;
      right: 10px;
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.75rem;
    }

    .video-info {
      padding: 1rem;
    }

    .engagement-badge {
      border-radius: 20px;
      padding: 0.25rem 0.75rem;
      font-size: 0.75rem;
      display: inline-flex;
      align-items: center;
    }

    .tab-custom {
      border-bottom: 2px solid #e9ecef;
      margin-bottom: 2rem;
    }

    .tab-custom .nav-link {
      color: #6c757d;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      border: none;
      position: relative;
    }

    .tab-custom .nav-link.active {
      color: #ff0089;
      background: transparent;
    }

    .tab-custom .nav-link.active::after {
      content: "";
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, #ff0089, #ff4d4d);
      border-radius: 2px;
    }

    .music-platform-badge {
      display: inline-flex;
      align-items: center;
      padding: 0.5rem 1rem;
      border-radius: 30px;
      font-size: 0.875rem;
      font-weight: 500;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .platform-spotify {
      background: #1db954;
      color: white;
    }

    .platform-deezer {
      background: #ff0089;
      color: white;
    }

    .platform-apple {
      background: #fa57c1;
      color: white;
    }

    .platform-amazon {
      background: #ff9900;
      color: white;
    }

    .platform-tidal {
      background: #000000;
      color: white;
    }

    .comment-item {
      padding: 1rem;
      border-bottom: 1px solid #e9ecef;
    }

    .comment-item:last-child {
      border-bottom: none;
    }

    .comment-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .quick-action-btn {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #e9ecef;
      color: #ff0089;
      transition: all 0.3s ease;
    }

    .quick-action-btn:hover {
      background: #ff0089;
      color: white;
      border-color: #ff0089;
    }

    @media (max-width: 768px) {
      .channel-header {
        padding: 1.5rem;
      }

      .channel-avatar-large {
        width: 80px;
        height: 80px;
      }

      .tab-custom .nav-link {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
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
          <i class="bi bi-list text-white fs-1" aria-hidden="true"></i>
        </span>
      </button>

      <a class="navbar-brand" href="../painel">
        <span class="text-light brand-text">WASOM UPFY</span>
      </a>

      <!-- Desktop Menu -->
      <div class="collapse navbar-collapse" id="navbarDesktop">
        <ul class="navbar-nav m-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="../painel">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../launch/releases">
              <i class="bi bi-disc"></i> Lançamentos
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../analytics/statistics">
              <i class="bi bi-bar-chart"></i> Estatísticas
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../finances/overview">
              <i class="bi bi-currency-dollar"></i> Finanças
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../artists/artists-list">
              <i class="bi bi-person"></i> Artistas
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../youtube">
              <i class="bi bi-youtube"></i> Unificação de canal
            </a>
          </li>
        </ul>
      </div>

      <!-- User Menu -->
      <div class="user-menu d-flex align-items-center gap-2">
        <button class="theme-toggle btn btn-link text-white p-0" id="themeToggle">
          <i class="bi bi-sun" id="themeIcon"></i>
        </button>

        <a href="../../notifications" class="text-white position-relative">
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
                <strong><?php echo $first_name; ?></strong><br />
                <small class="text-muted">Conta 560108</small>
              </span>
            </li>
            <li>
              <hr class="dropdown-divider" />
            </li>
            <li>
              <a class="dropdown-item" href="../profile/profile">Meu Perfil</a>
            </li>
            <li>
              <a class="dropdown-item" href="../account/manage-account">Gestão de Conta</a>
            </li>
            <li>
              <hr class="dropdown-divider" />
            </li>
            <li>
              <a class="dropdown-item" href="../page/settings">Configurações</a>
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
        <li class="nav-item">
          <a class="nav-link" href="../painel">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../launch/releases">Lançamentos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../analytics/statistics">Estatísticas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../finances/overview">Finanças</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../artists/artists-list">Artistas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="../youtube">Unificação de canal</a>
        </li>

        <li class="nav-item d-lg-none">
          <hr />
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="../profile/profile">
            <i class="bi bi-person-circle me-2"></i> Meu Perfil
          </a>
        </li>
      </ul>
    </div>
  </div>

  <!-- Breadcrumb -->
  <div class="container mt-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="ucy" class="text-decoration-none">Canais YouTube</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          Eleven Records Official
        </li>
      </ol>
    </nav>
  </div>

  <!-- Main Content -->
  <main class="container my-4">
    <!-- Channel Header -->
    <div class="channel-header">
      <div class="row align-items-center position-relative">
        <div class="col-md-8">
          <div class="d-flex align-items-center">
            <img src="https://via.placeholder.com/120" alt="Channel Avatar" class="channel-avatar-large me-4" />
            <div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <h1 class="h2 mb-0">Eleven Records Official</h1>
                <span class="badge bg-success">
                  <i class="bi bi-check-circle-fill me-1"></i> Verificado
                </span>
              </div>
              <p class="mb-2">
                <i class="bi bi-people-fill me-2"></i> 125K inscritos •
                <i class="bi bi-film ms-3 me-2"></i> 450 vídeos •
                <i class="bi bi-eye ms-3 me-2"></i> 2.5M visualizações
              </p>
              <div class="d-flex gap-2">
                <span class="badge bg-white text-dark">
                  <i class="bi bi-calendar me-1"></i> Criado em 15 Mar 2020
                </span>
                <span class="badge bg-white text-dark">
                  <i class="bi bi-geo-alt me-1"></i> Angola
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4 mt-3 mt-md-0">
          <div class="d-flex gap-2 justify-content-md-end">
            <button class="quick-action-btn" data-bs-toggle="tooltip" title="Sincronizar agora">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <button class="quick-action-btn" data-bs-toggle="tooltip" title="Compartilhar">
              <i class="bi bi-share"></i>
            </button>
            <button class="quick-action-btn" data-bs-toggle="tooltip" title="Configurações">
              <i class="bi bi-gear"></i>
            </button>
            <button class="btn btn-light text-danger ms-2" data-bs-toggle="modal" data-bs-target="#disconnectModal">
              <i class="bi bi-plug me-2"></i> Desconectar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon views">
            <i class="bi bi-eye"></i>
          </div>
          <h3 class="mb-1">2.5M</h3>
          <p class="text-muted small mb-2">Visualizações totais</p>
          <span class="metric-trend up">
            <i class="bi bi-arrow-up-short me-1"></i> +12.5% este mês
          </span>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon subs">
            <i class="bi bi-people"></i>
          </div>
          <h3 class="mb-1">125K</h3>
          <p class="text-muted small mb-2">Inscritos</p>
          <span class="metric-trend up">
            <i class="bi bi-arrow-up-short me-1"></i> +2.3K este mês
          </span>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon videos">
            <i class="bi bi-film"></i>
          </div>
          <h3 class="mb-1">450</h3>
          <p class="text-muted small mb-2">Vídeos publicados</p>
          <span class="metric-trend up">
            <i class="bi bi-arrow-up-short me-1"></i> +12 este mês
          </span>
        </div>
      </div>

      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon revenue">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <h3 class="mb-1">$45.2K</h3>
          <p class="text-muted small mb-2">Receita total</p>
          <span class="metric-trend up">
            <i class="bi bi-arrow-up-short me-1"></i> +$3.2K este mês
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs tab-custom" id="channelTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button"
          role="tab">
          <i class="bi bi-house-door me-2"></i> Visão Geral
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab">
          <i class="bi bi-film me-2"></i> Vídeos
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="music-tab" data-bs-toggle="tab" data-bs-target="#music" type="button" role="tab">
          <i class="bi bi-music-note-beamed me-2"></i> Músicas
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button"
          role="tab">
          <i class="bi bi-graph-up me-2"></i> Análises
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button"
          role="tab">
          <i class="bi bi-chat-dots me-2"></i> Comentários
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="channelTabsContent">
      <!-- Overview Tab -->
      <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row mt-4">
          <div class="col-lg-8">
            <!-- Gráfico de Performance -->
            <div class="card mb-4">
              <div class="card-header bg-transparent">
                <h5 class="mb-0">Performance dos Últimos 30 Dias</h5>
              </div>
              <div class="card-body">
                <canvas id="performanceChart" style="height: 300px"></canvas>
              </div>
            </div>

            <!-- Vídeos em Destaque -->
            <h5 class="mb-3">Vídeos em Destaque</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="video-card">
                  <div class="video-thumbnail">
                    <img src="https://via.placeholder.com/320x180" alt="Video Thumbnail" />
                    <span class="video-duration">3:45</span>
                  </div>
                  <div class="video-info">
                    <h6 class="mb-1">Summer Vibes 2024 (Official Video)</h6>
                    <p class="text-muted small mb-2">Há 3 dias • 45K views</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="engagement-badge">
                        <i class="bi bi-hand-thumbs-up me-1"></i> 2.3K
                      </span>
                      <span class="engagement-badge">
                        <i class="bi bi-chat me-1"></i> 156
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="video-card">
                  <div class="video-thumbnail">
                    <img src="https://via.placeholder.com/320x180" alt="Video Thumbnail" />
                    <span class="video-duration">4:20</span>
                  </div>
                  <div class="video-info">
                    <h6 class="mb-1">Night Drive (Lyric Video)</h6>
                    <p class="text-muted small mb-2">
                      Há 1 semana • 89K views
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="engagement-badge">
                        <i class="bi bi-hand-thumbs-up me-1"></i> 4.1K
                      </span>
                      <span class="engagement-badge">
                        <i class="bi bi-chat me-1"></i> 289
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <!-- Informações do Canal -->
            <div class="card mb-4">
              <div class="card-header bg-transparent">
                <h5 class="mb-0">Sobre o Canal</h5>
              </div>
              <div class="card-body">
                <p class="small">
                  Canal oficial da Eleven Records. Música eletrônica, deep
                  house e muito mais. Novos lançamentos toda semana!
                </p>
                <hr />
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Link do canal</span>
                  <a href="#" class="text-decoration-none small">
                    youtube.com/@elevenrecords
                  </a>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">E-mail de contato</span>
                  <span class="small">contact@elevenrecords.com</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">País</span>
                  <span class="small">Angola</span>
                </div>
              </div>
            </div>

            <!-- Plataformas Conectadas -->
            <div class="card mb-4">
              <div class="card-header bg-transparent">
                <h5 class="mb-0">Plataformas Conectadas</h5>
              </div>
              <div class="card-body">
                <div class="d-flex flex-wrap">
                  <span class="music-platform-badge platform-spotify">
                    <i class="bi bi-spotify me-2"></i> Spotify
                  </span>
                  <span class="music-platform-badge platform-deezer">
                    <i class="bi bi-music-player me-2"></i> Deezer
                  </span>
                  <span class="music-platform-badge platform-apple">
                    <i class="bi bi-apple me-2"></i> Apple Music
                  </span>
                  <span class="music-platform-badge platform-amazon">
                    <i class="bi bi-amazon me-2"></i> Amazon Music
                  </span>
                </div>

                <hr />

                <div class="d-flex align-items-center justify-content-between">
                  <span class="small text-muted">Última sincronização</span>
                  <span class="small">Hoje, 10:30</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-1">
                  <span class="small text-muted">Status</span>
                  <span class="badge bg-success">Ativo</span>
                </div>
              </div>
            </div>

            <!-- Métricas Rápidas -->
            <div class="card">
              <div class="card-header bg-transparent">
                <h5 class="mb-0">Métricas da Semana</h5>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small">Views</span>
                    <span class="small fw-bold">45.2K</span>
                  </div>
                  <div class="progress" style="height: 6px">
                    <div class="progress-bar bg-primary" style="width: 75%"></div>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small">Inscritos</span>
                    <span class="small fw-bold">+234</span>
                  </div>
                  <div class="progress" style="height: 6px">
                    <div class="progress-bar bg-success" style="width: 45%"></div>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small">Engajamento</span>
                    <span class="small fw-bold">8.3%</span>
                  </div>
                  <div class="progress" style="height: 6px">
                    <div class="progress-bar bg-info" style="width: 60%"></div>
                  </div>
                </div>

                <div>
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small">Receita</span>
                    <span class="small fw-bold">$1.2K</span>
                  </div>
                  <div class="progress" style="height: 6px">
                    <div class="progress-bar bg-warning" style="width: 35%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Videos Tab -->
      <div class="tab-pane fade" id="videos" role="tabpanel">
        <div class="row mt-4">
          <div class="col-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <h5>Todos os Vídeos</h5>
              <button class="btn btn-primary btn-sm">
                <i class="bi bi-funnel me-2"></i> Filtrar
              </button>
            </div>
          </div>

          <!-- Lista de Vídeos -->
          <div class="col-12">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Vídeo</th>
                    <th>Data</th>
                    <th>Views</th>
                    <th>Curtidas</th>
                    <th>Comentários</th>
                    <th>Duração</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <img src="https://via.placeholder.com/60x34" alt="Thumb" class="me-2 rounded"
                          style="width: 60px" />
                        <div>
                          <strong>Summer Vibes 2024</strong><br />
                          <small class="text-muted">Official Video</small>
                        </div>
                      </div>
                    </td>
                    <td>15/02/2024</td>
                    <td>45.2K</td>
                    <td>2.3K</td>
                    <td>156</td>
                    <td>3:45</td>
                  </tr>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <img src="https://via.placeholder.com/60x34" alt="Thumb" class="me-2 rounded"
                          style="width: 60px" />
                        <div>
                          <strong>Night Drive</strong><br />
                          <small class="text-muted">Lyric Video</small>
                        </div>
                      </div>
                    </td>
                    <td>10/02/2024</td>
                    <td>89.1K</td>
                    <td>4.1K</td>
                    <td>289</td>
                    <td>4:20</td>
                  </tr>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <img src="https://via.placeholder.com/60x34" alt="Thumb" class="me-2 rounded"
                          style="width: 60px" />
                        <div>
                          <strong>Ocean Eyes</strong><br />
                          <small class="text-muted">Official Audio</small>
                        </div>
                      </div>
                    </td>
                    <td>05/02/2024</td>
                    <td>112.3K</td>
                    <td>5.6K</td>
                    <td>412</td>
                    <td>3:30</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Music Tab -->
      <div class="tab-pane fade" id="music" role="tabpanel">
        <div class="row mt-4">
          <div class="col-12">
            <h5 class="mb-3">Músicas Detectadas</h5>

            <div class="card">
              <div class="card-body p-0">
                <div class="list-group list-group-flush">
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-1">Summer Vibes</h6>
                      <small class="text-muted">Artista: DJ Mark</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                      <span class="music-platform-badge platform-spotify py-1 px-2">
                        <i class="bi bi-spotify"></i>
                      </span>
                      <span class="music-platform-badge platform-deezer py-1 px-2">
                        <i class="bi bi-music-player"></i>
                      </span>
                      <span class="badge bg-success">Sincronizado</span>
                    </div>
                  </div>

                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-1">Night Drive</h6>
                      <small class="text-muted">Artista: ElectroBeats</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                      <span class="music-platform-badge platform-spotify py-1 px-2">
                        <i class="bi bi-spotify"></i>
                      </span>
                      <span class="music-platform-badge platform-apple py-1 px-2">
                        <i class="bi bi-apple"></i>
                      </span>
                      <span class="badge bg-warning text-dark">Pendente</span>
                    </div>
                  </div>

                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-1">Ocean Eyes</h6>
                      <small class="text-muted">Artista: Sarah Moon</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                      <span class="music-platform-badge platform-apple py-1 px-2">
                        <i class="bi bi-apple"></i>
                      </span>
                      <span class="music-platform-badge platform-amazon py-1 px-2">
                        <i class="bi bi-amazon"></i>
                      </span>
                      <span class="badge bg-success">Sincronizado</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Analytics Tab -->
      <div class="tab-pane fade" id="analytics" role="tabpanel">
        <div class="row mt-4">
          <div class="col-md-6 mb-4">
            <div class="card">
              <div class="card-header bg-transparent">
                <h6 class="mb-0">Fontes de Tráfego</h6>
              </div>
              <div class="card-body">
                <canvas id="trafficChart" style="height: 250px"></canvas>
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-4">
            <div class="card">
              <div class="card-header bg-transparent">
                <h6 class="mb-0">Dispositivos</h6>
              </div>
              <div class="card-body">
                <canvas id="devicesChart" style="height: 250px"></canvas>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card">
              <div class="card-header bg-transparent">
                <h6 class="mb-0">Horários de Pico</h6>
              </div>
              <div class="card-body">
                <canvas id="peakHoursChart" style="height: 200px"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comments Tab -->
      <div class="tab-pane fade" id="comments" role="tabpanel">
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Últimos Comentários</h6>
                <button class="btn btn-sm btn-primary">
                  <i class="bi bi-arrow-repeat me-2"></i> Atualizar
                </button>
              </div>
              <div class="card-body p-0">
                <div class="comment-item">
                  <div class="d-flex">
                    <img src="https://via.placeholder.com/40" alt="User" class="comment-avatar me-3" />
                    <div>
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <strong>João Silva</strong>
                        <small class="text-muted">• 2 horas atrás</small>
                      </div>
                      <p class="small mb-2">
                        Música incrível! Já está nos meus favoritos 🔥
                      </p>
                      <div class="d-flex gap-3">
                        <span class="small text-muted">
                          <i class="bi bi-hand-thumbs-up me-1"></i> 15
                        </span>
                        <span class="small text-muted">
                          <i class="bi bi-hand-thumbs-down me-1"></i> 1
                        </span>
                        <span class="small text-primary">Responder</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="comment-item">
                  <div class="d-flex">
                    <img src="https://via.placeholder.com/40" alt="User" class="comment-avatar me-3" />
                    <div>
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <strong>Maria Santos</strong>
                        <small class="text-muted">• 5 horas atrás</small>
                      </div>
                      <p class="small mb-2">Melhor música do ano! 🎵</p>
                      <div class="d-flex gap-3">
                        <span class="small text-muted">
                          <i class="bi bi-hand-thumbs-up me-1"></i> 23
                        </span>
                        <span class="small text-muted">
                          <i class="bi bi-hand-thumbs-down me-1"></i> 0
                        </span>
                        <span class="small text-primary">Responder</span>
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
  </main>

  <!-- Bottom Navigation (Mobile) -->
  <nav class="bottom-nav d-lg-none fixed-bottom bg-white shadow">
    <ul class="nav justify-content-around">
      <li class="nav-item">
        <a class="nav-link text-center" href="../painel">
          <i class="bi bi-speedometer2 d-block fs-5"></i>
          <span class="small">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="../launch/releases">
          <i class="bi bi-disc d-block fs-5"></i>
          <span class="small">Lançamentos</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="../analytics/statistics">
          <i class="bi bi-bar-chart d-block fs-5"></i>
          <span class="small">Stats</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="../finances/overview">
          <i class="bi bi-currency-dollar d-block fs-5"></i>
          <span class="small">Finanças</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center active" href="../youtube">
          <i class="bi bi-youtube d-block fs-5"></i>
          <span class="small">YouTube</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Modal Desconectar -->
  <div class="modal fade" id="disconnectModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Desconectar Canal</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>
            Tem certeza que deseja desconectar o canal "Eleven Records
            Official"?
          </p>
          <p class="text-danger small">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Isso irá interromper a sincronização de dados e você poderá perder
            estatísticas não salvas.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button type="button" class="btn btn-danger" onclick="disconnectChannel()">
            Desconectar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal logout -->
  <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5 text-dark" id="logoutwasomupfyLabel">
            Terminar sessão
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="container">
            <div class="row justify-content-center text-center">
              <div class="col-md-12 content-center justify-center text-center">
                <p class="text-center text-dark">
                  @josembengadacosta você tem certeza de que desejas terminar
                  sessão?
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div>
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
              Não, continuar
            </button>
          </div>
          <div>
            <button class="btn btn-danger" type="button" name="logout_wasomupfy" onclick="logout_wasomupfy()">
              Sim, terminar
            </button>
          </div>
          <script type="text/javascript">
            function logout_wasomupfy() {
              window.location = "logout";
            }
          </script>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal logout fim -->

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../js/theme.wp.js"></script>
  <script src="../../../js/wp.tools.js"></script>

  <script>
    // Funções específicas
    function disconnectChannel() {
      console.log("Desconectando canal...");
      // Aqui você implementaria a lógica de desconexão
      alert("Canal desconectado com sucesso!");
      window.location.href = "../youtube";
    }

    function logout() {
      window.location = "../../logout";
    }

    // Inicializar tooltips
    document.addEventListener("DOMContentLoaded", function() {
      const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
      );
      tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });

      // Inicializar gráficos
      initCharts();
    });

    function initCharts() {
      // Performance Chart
      const perfCtx = document
        .getElementById("performanceChart")
        ?.getContext("2d");
      if (perfCtx) {
        new Chart(perfCtx, {
          type: "line",
          data: {
            labels: ["Semana 1", "Semana 2", "Semana 3", "Semana 4"],
            datasets: [{
                label: "Visualizações",
                data: [45000, 52000, 48000, 58000],
                borderColor: "#FF0089",
                backgroundColor: "rgba(255, 0, 137, 0.1)",
                tension: 0.4,
                fill: true,
              },
              {
                label: "Inscritos",
                data: [1200, 1350, 1280, 1450],
                borderColor: "#1DB954",
                backgroundColor: "rgba(29, 185, 84, 0.1)",
                tension: 0.4,
                fill: true,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
              },
            },
          },
        });
      }

      // Traffic Chart
      const trafficCtx = document
        .getElementById("trafficChart")
        ?.getContext("2d");
      if (trafficCtx) {
        new Chart(trafficCtx, {
          type: "doughnut",
          data: {
            labels: [
              "Pesquisa YouTube",
              "Sugeridos",
              "Playlists",
              "Externos",
            ],
            datasets: [{
              data: [45, 30, 15, 10],
              backgroundColor: ["#FF0089", "#1DB954", "#FF4D4D", "#F59E0B"],
            }, ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
              },
            },
          },
        });
      }

      // Devices Chart
      const devicesCtx = document
        .getElementById("devicesChart")
        ?.getContext("2d");
      if (devicesCtx) {
        new Chart(devicesCtx, {
          type: "doughnut",
          data: {
            labels: ["Mobile", "Desktop", "Tablet", "TV"],
            datasets: [{
              data: [65, 25, 7, 3],
              backgroundColor: ["#FF0089", "#1DB954", "#FA57C1", "#FF0000"],
            }, ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
              },
            },
          },
        });
      }

      // Peak Hours Chart
      const peakCtx = document
        .getElementById("peakHoursChart")
        ?.getContext("2d");
      if (peakCtx) {
        new Chart(peakCtx, {
          type: "bar",
          data: {
            labels: ["0-4", "4-8", "8-12", "12-16", "16-20", "20-24"],
            datasets: [{
              label: "Visualizações",
              data: [5000, 3000, 15000, 18000, 25000, 12000],
              backgroundColor: "#FF0089",
            }, ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
          },
        });
      }
    }
  </script>
</body>

</html>