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

  <title>Notificações — Wasom Upfy</title>

  <!-- Favicon -->
  <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
  <link rel="apple-touch-icon" href="../assets/img/icones/wasomupfy_fiv_512.png" />
  <link rel="manifest" href="../manifest.json" />

  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../css/lastest-style.css" />
  <link rel="stylesheet" href="../../css/notification.css" />
</head>

<body>
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
            <a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
              data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i> Desconectar-se</a>
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
    <!-- Header -->
    <div class="notifications-header">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="display-5 fw-bold mb-3">
            <i class="bi bi-bell-fill me-2"></i> Notificações
          </h1>
          <p class="lead mb-0">
            Fique por dentro de todas as novidades, atualizações e
            movimentações da sua conta.
          </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <span class="badge bg-white text-dark p-3">
            <i class="bi bi-envelope-fill text-primary me-2"></i>
            <strong>9 não lidas</strong> • 23 no total
          </span>
        </div>
      </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="quick-mark">
          <button class="btn btn-outline-primary" onclick="markAllAsRead()">
            <i class="bi bi-check2-all me-2"></i> Marcar todas como lidas
          </button>
          <button class="btn btn-outline-secondary" onclick="archiveAll()">
            <i class="bi bi-archive me-2"></i> Arquivar todas
          </button>
          <button class="btn btn-outline-danger" onclick="clearAll()">
            <i class="bi bi-trash me-2"></i> Limpar todas
          </button>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filter-tabs d-flex flex-wrap gap-2">
      <button class="btn-filter active" data-filter="all">
        <i class="bi bi-bell me-2"></i> Todas
      </button>
      <button class="btn-filter" data-filter="unread">
        <i class="bi bi-envelope me-2"></i> Não lidas
        <span class="badge bg-danger ms-2">9</span>
      </button>
      <button class="btn-filter" data-filter="streams">
        <i class="bi bi-spotify me-2"></i> Streams
      </button>
      <button class="btn-filter" data-filter="revenue">
        <i class="bi bi-currency-dollar me-2"></i> Receitas
      </button>
      <button class="btn-filter" data-filter="system">
        <i class="bi bi-gear me-2"></i> Sistema
      </button>
      <button class="btn-filter" data-filter="releases">
        <i class="bi bi-disc me-2"></i> Lançamentos
      </button>
    </div>

    <div class="row">
      <!-- Lista de Notificações -->
      <div class="col-lg-8">
        <!-- Hoje -->
        <div class="notification-group-date">
          <i class="bi bi-calendar-day me-2"></i> Hoje
        </div>

        <!-- Notificação 1 - Nova stream -->
        <div class="notification-card unread" data-type="streams" onclick="openNotification(this)" data-id="1">
          <div class="row">
            <div class="col-auto">
              <div class="notification-icon icon-success">
                <i class="bi bi-spotify"></i>
              </div>
            </div>
            <div class="col">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1">Novas streams detectadas no Spotify</h6>
                  <p class="text-muted small mb-2">
                    Sua música "Summer Vibes" teve 1,234 novas streams nas
                    últimas 24 horas.
                  </p>
                  <div class="d-flex gap-3">
                    <span class="notification-type-badge badge-stream">
                      <i class="bi bi-spotify me-1"></i> Streams
                    </span>
                    <span class="notification-time">
                      <i class="bi bi-clock me-1"></i> Há 5 minutos
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="notification-actions">
            <button class="action-btn" onclick="event.stopPropagation(); markAsRead(1)" title="Marcar como lida">
              <i class="bi bi-check"></i>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); archiveNotification(1)" title="Arquivar">
              <i class="bi bi-archive"></i>
            </button>
          </div>
        </div>

        <!-- Notificação 2 - Receita -->
        <div class="notification-card unread" data-type="revenue" onclick="openNotification(this)" data-id="2">
          <div class="row">
            <div class="col-auto">
              <div class="notification-icon icon-warning">
                <i class="bi bi-currency-dollar"></i>
              </div>
            </div>
            <div class="col">
              <div>
                <h6 class="mb-1">Receita disponível para saque</h6>
                <p class="text-muted small mb-2">
                  Seu saldo atingiu $500. Você já pode solicitar o saque dos
                  seus rendimentos.
                </p>
                <div class="d-flex gap-3">
                  <span class="notification-type-badge badge-revenue">
                    <i class="bi bi-cash me-1"></i> Receita
                  </span>
                  <span class="notification-time">
                    <i class="bi bi-clock me-1"></i> Há 2 horas
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="notification-actions">
            <button class="action-btn" onclick="event.stopPropagation(); markAsRead(2)">
              <i class="bi bi-check"></i>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); archiveNotification(2)">
              <i class="bi bi-archive"></i>
            </button>
          </div>
        </div>

        <!-- Notificação 3 - Sistema -->
        <div class="notification-card" data-type="system" onclick="openNotification(this)" data-id="3">
          <div class="row">
            <div class="col-auto">
              <div class="notification-icon icon-info">
                <i class="bi bi-shield-check"></i>
              </div>
            </div>
            <div class="col">
              <div>
                <h6 class="mb-1">Atualização de segurança concluída</h6>
                <p class="text-muted small mb-2">
                  Sua conta foi atualizada com os novos protocolos de
                  segurança. Nenhuma ação necessária.
                </p>
                <div class="d-flex gap-3">
                  <span class="notification-type-badge badge-system">
                    <i class="bi bi-gear me-1"></i> Sistema
                  </span>
                  <span class="notification-time">
                    <i class="bi bi-clock me-1"></i> Há 5 horas
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="notification-actions">
            <button class="action-btn" onclick="event.stopPropagation(); markAsRead(3)">
              <i class="bi bi-check"></i>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); archiveNotification(3)">
              <i class="bi bi-archive"></i>
            </button>
          </div>
        </div>

        <!-- Este mês -->
        <div class="notification-group-date">
          <i class="bi bi-calendar-week me-2"></i> Este mês
        </div>

        <!-- Notificação 4 - Lançamento -->
        <div class="notification-card" data-type="releases" onclick="openNotification(this)" data-id="4">
          <div class="row">
            <div class="col-auto">
              <div class="notification-icon icon-primary">
                <i class="bi bi-disc"></i>
              </div>
            </div>
            <div class="col">
              <div>
                <h6 class="mb-1">Lançamento aprovado</h6>
                <p class="text-muted small mb-2">
                  Seu novo lançamento "Night Drive" foi aprovado e já está
                  disponível em todas as plataformas.
                </p>
                <div class="d-flex gap-3">
                  <span class="notification-type-badge badge-release">
                    <i class="bi bi-music-note me-1"></i> Lançamento
                  </span>
                  <span class="notification-time">
                    <i class="bi bi-clock me-1"></i> 3 dias atrás
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="notification-actions">
            <button class="action-btn" onclick="event.stopPropagation(); markAsRead(4)">
              <i class="bi bi-check"></i>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); archiveNotification(4)">
              <i class="bi bi-archive"></i>
            </button>
          </div>
        </div>

        <!-- Notificação 5 - Subscribers -->
        <div class="notification-card" data-type="streams" onclick="openNotification(this)" data-id="5">
          <div class="row">
            <div class="col-auto">
              <div class="notification-icon icon-success">
                <i class="bi bi-youtube"></i>
              </div>
            </div>
            <div class="col">
              <div>
                <h6 class="mb-1">Marco de inscritos no YouTube</h6>
                <p class="text-muted small mb-2">
                  Seu canal "Eleven Records Official" atingiu 125.000
                  inscritos! 🎉
                </p>
                <div class="d-flex gap-3">
                  <span class="notification-type-badge badge-subscriber">
                    <i class="bi bi-people me-1"></i> Inscritos
                  </span>
                  <span class="notification-time">
                    <i class="bi bi-clock me-1"></i> 5 dias atrás
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="notification-actions">
            <button class="action-btn" onclick="event.stopPropagation(); markAsRead(5)">
              <i class="bi bi-check"></i>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); archiveNotification(5)">
              <i class="bi bi-archive"></i>
            </button>
          </div>
        </div>

        <!-- Mês passado -->
        <div class="notification-group-date">
          <i class="bi bi-calendar-month me-2"></i> Mês passado
        </div>

        <!-- Notificação 6 - Receita mensal -->
        <div class="notification-card" data-type="revenue" onclick="openNotification(this)" data-id="6">
          <div class="row">
            <div class="col-auto">
              <div class="notification-icon icon-warning">
                <i class="bi bi-graph-up"></i>
              </div>
            </div>
            <div class="col">
              <div>
                <h6 class="mb-1">Relatório de receita mensal disponível</h6>
                <p class="text-muted small mb-2">
                  Seu relatório de receita de Janeiro já está disponível para
                  consulta.
                </p>
                <div class="d-flex gap-3">
                  <span class="notification-type-badge badge-revenue">
                    <i class="bi bi-cash me-1"></i> Receita
                  </span>
                  <span class="notification-time">
                    <i class="bi bi-clock me-1"></i> 15 Jan 2024
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="notification-actions">
            <button class="action-btn" onclick="event.stopPropagation(); markAsRead(6)">
              <i class="bi bi-check"></i>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); archiveNotification(6)">
              <i class="bi bi-archive"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar - Configurações -->
      <div class="col-lg-4">
        <!-- Card de Status -->
        <div class="settings-card mb-4">
          <h6><i class="bi bi-pie-chart me-2"></i> Resumo</h6>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Total de notificações</span>
              <span class="fw-bold">23</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Não lidas</span>
              <span class="fw-bold text-danger">9</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Arquivadas</span>
              <span class="fw-bold">15</span>
            </div>
            <div class="progress mt-2" style="height: 8px">
              <div class="progress-bar bg-primary" style="width: 39%"></div>
              <div class="progress-bar bg-secondary" style="width: 61%"></div>
            </div>
            <small class="text-muted d-block mt-2">
              <i class="bi bi-circle-fill text-primary me-1 small"></i> Não
              lidas (39%)
              <i class="bi bi-circle-fill text-secondary ms-3 me-1 small"></i>
              Lidas (61%)
            </small>
          </div>
        </div>

        <!-- Card de Preferências -->
        <div class="settings-card mb-4">
          <h6><i class="bi bi-gear me-2"></i> Preferências de Notificação</h6>

          <div class="notification-preference">
            <div>
              <span>Streams</span>
              <small class="d-block text-muted">Novas streams em suas músicas</small>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchStreams" checked />
            </div>
          </div>

          <div class="notification-preference">
            <div>
              <span>Receitas</span>
              <small class="d-block text-muted">Pagamentos e saques</small>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchRevenue" checked />
            </div>
          </div>

          <div class="notification-preference">
            <div>
              <span>Lançamentos</span>
              <small class="d-block text-muted">Status de novos lançamentos</small>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchReleases" checked />
            </div>
          </div>

          <div class="notification-preference">
            <div>
              <span>Sistema</span>
              <small class="d-block text-muted">Atualizações e segurança</small>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchSystem" checked />
            </div>
          </div>

          <div class="notification-preference">
            <div>
              <span>E-mail</span>
              <small class="d-block text-muted">Receber notificações por e-mail</small>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchEmail" />
            </div>
          </div>

          <hr />

          <div class="d-grid">
            <button class="btn btn-primary btn-sm" onclick="savePreferences()">
              <i class="bi bi-save me-2"></i> Salvar preferências
            </button>
          </div>
        </div>

        <!-- Card de Atalhos -->
        <div class="settings-card">
          <h6><i class="bi bi-lightning-charge me-2"></i> Atalhos Rápidos</h6>

          <div class="d-grid gap-2">
            <button class="btn btn-outline-primary text-start" onclick="window.location.href='../finances/overview'">
              <i class="bi bi-currency-dollar me-2"></i> Verificar receitas
            </button>
            <button class="btn btn-outline-primary text-start" onclick="window.location.href='../analytics/statistics'">
              <i class="bi bi-bar-chart me-2"></i> Ver estatísticas
            </button>
            <button class="btn btn-outline-primary text-start" onclick="window.location.href='../launch/creat-release'">
              <i class="bi bi-plus-circle me-2"></i> Novo lançamento
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Bottom Navigation (Mobile) -->
  <nav class="bottom-nav d-lg-none fixed-bottom bg-white shadow">
    <ul class="nav justify-content-around">
      <li class="nav-item">
        <a class="nav-link text-center" href="painel">
          <i class="bi bi-speedometer2 d-block fs-5"></i>
          <span class="small">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="launch/releases">
          <i class="bi bi-disc d-block fs-5"></i>
          <span class="small">Lançamentos</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="analytics/statistics">
          <i class="bi bi-bar-chart d-block fs-5"></i>
          <span class="small">Stats</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="finances/overview">
          <i class="bi bi-currency-dollar d-block fs-5"></i>
          <span class="small">Finanças</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-center" href="youtube">
          <i class="bi bi-youtube d-block fs-5"></i>
          <span class="small">YouTube</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Modal de Detalhes da Notificação -->
  <div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="notificationModalTitle">
            Detalhes da Notificação
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="notificationModalBody">
          <!-- Conteúdo dinâmico -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" onclick="markCurrentAsRead()">
            <i class="bi bi-check2 me-2"></i> Marcar como lida
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmação -->
  <div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar ação</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="confirmModalMessage">
          Tem certeza que deseja realizar esta ação?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button type="button" class="btn btn-danger" id="confirmActionBtn">
            Confirmar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ════ MODAL — Logout ════ -->
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
  <!-- ════ MODAL — Logout  FIM ════ -->

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../js/theme.wp.js"></script>
  <script src="../../js/wp.tools.js"></script>

  <script>
    // Estado das notificações
    let currentNotificationId = null;
    let notificationModal = null;
    let confirmModal = null;

    document.addEventListener("DOMContentLoaded", function() {
      // Inicializar modais
      notificationModal = new bootstrap.Modal(
        document.getElementById("notificationModal")
      );
      confirmModal = new bootstrap.Modal(
        document.getElementById("confirmModal")
      );

      // Inicializar tooltips
      const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
      );
      tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });

      // Configurar filtros
      setupFilters();
    });

    // Configurar filtros
    function setupFilters() {
      const filterButtons = document.querySelectorAll(".btn-filter");

      filterButtons.forEach((button) => {
        button.addEventListener("click", function() {
          // Remover active de todos
          filterButtons.forEach((btn) => btn.classList.remove("active"));

          // Adicionar active no clicado
          this.classList.add("active");

          // Filtrar notificações
          const filter = this.dataset.filter;
          filterNotifications(filter);
        });
      });
    }

    // Filtrar notificações
    function filterNotifications(filter) {
      const notifications = document.querySelectorAll(".notification-card");

      notifications.forEach((notification) => {
        if (filter === "all") {
          notification.style.display = "block";
        } else if (filter === "unread") {
          notification.style.display = notification.classList.contains(
              "unread"
            ) ?
            "block" :
            "none";
        } else {
          const type = notification.dataset.type;
          notification.style.display = type === filter ? "block" : "none";
        }
      });

      // Mostrar/esconder grupos de data baseado em visibilidade
      updateDateGroups();
    }

    // Atualizar grupos de data
    function updateDateGroups() {
      const dateGroups = document.querySelectorAll(
        ".notification-group-date"
      );
      const notifications = document.querySelectorAll(".notification-card");

      dateGroups.forEach((group) => {
        let nextElement = group.nextElementSibling;
        let hasVisibleNotifications = false;

        while (
          nextElement &&
          !nextElement.classList.contains("notification-group-date")
        ) {
          if (
            nextElement.classList.contains("notification-card") &&
            nextElement.style.display !== "none"
          ) {
            hasVisibleNotifications = true;
            break;
          }
          nextElement = nextElement.nextElementSibling;
        }

        group.style.display = hasVisibleNotifications ? "block" : "none";
      });
    }

    // Abrir notificação
    function openNotification(element, notificationId) {
      const id = notificationId || element.dataset.id;
      currentNotificationId = id;

      // Buscar detalhes da notificação
      const title = element.querySelector("h6").textContent;
      const content = element.querySelector("p").textContent;
      const time = element
        .querySelector(".notification-time")
        .textContent.trim();
      const type = element
        .querySelector(".notification-type-badge")
        .cloneNode(true);

      // Remover marcação de não lida
      if (element.classList.contains("unread")) {
        element.classList.remove("unread");
        updateUnreadCount();
      }

      // Preencher modal
      document.getElementById("notificationModalTitle").textContent = title;

      let typeHtml = "";
      if (type) {
        typeHtml = `<div class="mb-3">${type.outerHTML}</div>`;
      }

      document.getElementById("notificationModalBody").innerHTML = `
        <p class="text-muted small">${time}</p>
        <p class="mb-4">${content}</p>
        ${typeHtml}
        <hr>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); window.location.href='#'">
            <i class="bi bi-box-arrow-up-right me-2"></i> Ver detalhes completos
          </button>
        </div>
      `;

      notificationModal.show();
    }

    // Marcar como lida
    function markAsRead(id) {
      const notification = document.querySelector(
        `.notification-card[data-id="${id}"]`
      );
      if (notification) {
        notification.classList.remove("unread");
        updateUnreadCount();
        showToast("Notificação marcada como lida");
      }
    }

    // Marcar notificação atual como lida
    function markCurrentAsRead() {
      if (currentNotificationId) {
        markAsRead(currentNotificationId);
      }
      notificationModal.hide();
    }

    // Arquivar notificação
    function archiveNotification(id) {
      const notification = document.querySelector(
        `.notification-card[data-id="${id}"]`
      );
      if (notification) {
        notification.style.display = "none";
        updateDateGroups();
        showToast("Notificação arquivada");
      }
    }

    // Marcar todas como lidas
    function markAllAsRead() {
      document
        .querySelectorAll(".notification-card.unread")
        .forEach((notification) => {
          notification.classList.remove("unread");
        });
      updateUnreadCount();
      showToast("Todas as notificações foram marcadas como lidas");
    }

    // Arquivar todas
    function archiveAll() {
      document.getElementById("confirmModalMessage").innerHTML =
        "Tem certeza que deseja arquivar todas as notificações?";

      document.getElementById("confirmActionBtn").onclick = function() {
        document
          .querySelectorAll(".notification-card")
          .forEach((notification) => {
            notification.style.display = "none";
          });
        updateDateGroups();
        confirmModal.hide();
        showToast("Todas as notificações foram arquivadas");
      };

      confirmModal.show();
    }

    // Limpar todas
    function clearAll() {
      document.getElementById("confirmModalMessage").innerHTML =
        "Tem certeza que deseja limpar todas as notificações? Esta ação não pode ser desfeita.";

      document.getElementById("confirmActionBtn").onclick = function() {
        document
          .querySelectorAll(".notification-card")
          .forEach((notification) => {
            notification.remove();
          });
        updateDateGroups();
        confirmModal.hide();
        showToast("Todas as notificações foram removidas");
      };

      confirmModal.show();
    }

    // Atualizar contador de não lidas
    function updateUnreadCount() {
      const unreadCount = document.querySelectorAll(
        ".notification-card.unread"
      ).length;

      // Atualizar badge no header
      const headerBadge = document.querySelector(
        ".notifications-header .badge"
      );
      if (headerBadge) {
        headerBadge.innerHTML = `<i class="bi bi-envelope-fill text-primary me-2"></i>
          <strong>${unreadCount} não lida${unreadCount !== 1 ? "s" : ""
          }</strong> • 
          ${document.querySelectorAll(".notification-card").length} no total`;
      }

      // Atualizar badge no filtro
      const filterBadge = document.querySelector(
        '.btn-filter[data-filter="unread"] .badge'
      );
      if (filterBadge) {
        filterBadge.textContent = unreadCount;
      }

      // Atualizar badge no ícone da navbar
      const navbarBadge = document.querySelector(".user-menu .badge");
      if (navbarBadge) {
        navbarBadge.textContent = unreadCount;
      }
    }

    // Salvar preferências
    function savePreferences() {
      showToast("Preferências salvas com sucesso!");
    }

    // Logout
    function logout() {
      window.location = "logout";
    }

    // Mostrar toast (você precisará implementar um sistema de toast)
    function showToast(message) {
      // Implementar toast se necessário
      console.log("Toast:", message);
    }

    // Placeholder para funções não implementadas
    window.tryReconnect = function() {
      console.log("Tentando reconectar...");
    };
  </script>
</body>

</html>