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
  <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
  <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png" />
  <link rel="manifest" href="../manifest.json" />
  <title>Comparar Períodos — Wasom Upfy</title>
  <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
  <link rel="stylesheet" href="../../css/libs/scrollue.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../css/lastest-style.css" />
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .comparison-header {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      border-radius: 15px;
      padding: 2rem;
      margin-bottom: 2rem;
      color: white;
    }

    .period-card {
      background: var(--bg-card, #1e1e2d);
      border-radius: 12px;
      padding: 1.5rem;
      height: 100%;
      border: 1px solid var(--border-color, #2d2d3d);
      transition: transform 0.3s ease;
    }

    .period-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(255, 0, 137, 0.2);
    }

    .period-card.period-a {
      border-left: 4px solid #ff0089;
    }

    .period-card.period-b {
      border-left: 4px solid #00ff88;
    }

    .metric-box {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
      padding: 1rem;
      text-align: center;
      margin-bottom: 1rem;
    }

    .metric-value {
      font-size: 1.8rem;
      font-weight: bold;
      color: #ff0089;
    }

    .metric-label {
      font-size: 0.9rem;
      color: #888;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .comparison-badge {
      display: inline-block;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: bold;
      margin: 1rem 0;
    }

    .badge-positive {
      background: rgba(0, 255, 136, 0.2);
      color: #00ff88;
      border: 1px solid #00ff88;
    }

    .badge-negative {
      background: rgba(255, 68, 68, 0.2);
      color: #ff4444;
      border: 1px solid #ff4444;
    }

    .badge-neutral {
      background: rgba(255, 255, 255, 0.1);
      color: #888;
      border: 1px solid #888;
    }

    .progress-comparison {
      height: 30px;
      border-radius: 15px;
      margin: 0.5rem 0;
    }

    .progress-bar-period-a {
      background: linear-gradient(90deg, #ff0089, #ff4da6);
    }

    .progress-bar-period-b {
      background: linear-gradient(90deg, #00ff88, #4dffb8);
    }

    .platform-comparison-row {
      display: flex;
      align-items: center;
      padding: 1rem;
      border-bottom: 1px solid var(--border-color, #2d2d3d);
    }

    .platform-comparison-row:last-child {
      border-bottom: none;
    }

    .platform-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      font-size: 1.5rem;
    }

    .spotify-icon {
      background: #1db954;
      color: white;
    }

    .deezer-icon {
      background: #ff0089;
      color: white;
    }

    .apple-icon {
      background: #ff4d4d;
      color: white;
    }

    .youtube-icon {
      background: #ff0000;
      color: white;
    }

    .amazon-icon {
      background: #00a8e1;
      color: white;
    }

    .percentage-change {
      font-weight: bold;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.9rem;
    }

    .btn-compare {
      background: #ff0089;
      color: white;
      border: none;
      padding: 0.8rem 2rem;
      border-radius: 25px;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .btn-compare:hover {
      background: #ff4da6;
      transform: scale(1.05);
      color: white;
    }

    .btn-swap {
      background: transparent;
      border: 2px solid #ff0089;
      color: #ff0089;
      padding: 0.5rem 1rem;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .btn-swap:hover {
      background: #ff0089;
      color: white;
      transform: rotate(180deg);
    }

    .quick-select {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-color, #2d2d3d);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      margin: 0.2rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .quick-select:hover {
      background: #ff0089;
      border-color: #ff0089;
    }

    .quick-select.active {
      background: #ff0089;
      border-color: #ff0089;
    }

    .total-streams-a {
      color: #ff0089;
      font-size: 2.5rem;
      font-weight: bold;
    }

    .total-streams-b {
      color: #00ff88;
      font-size: 2.5rem;
      font-weight: bold;
    }

    .vs-divider {
      font-size: 1.5rem;
      font-weight: bold;
      color: #888;
      margin: 0 1rem;
    }
  </style>
</head>

<body>
  <!-- Navbar (mesma da página de estatísticas) -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
        aria-controls="offcanvasMenu">
        <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
      </button>

      <a class="navbar-brand" href="../painel">
        <span class="text-light" style="font-weight: bold; text-transform: capitalize;">WASOM UPFY</span>
      </a>

      <div class="collapse navbar-collapse">
        <ul class="navbar-nav m-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="../analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
          </li>
        </ul>
      </div>

      <div class="user-menu d-flex align-items-center">
        <a class="theme-toggle text-white me-2" id="themeToggle">
          <i class="bi bi-sun" id="themeIcon"></i>
        </a>
        <a href="../page/notifications" class="text-white me-2">
          <i class="bi bi-bell fs-4"></i>
          <span class="badge bg-danger">9</span>
        </a>
        <a href="#" class="text-white" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle fs-4"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a></li>
          <li><a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i> Configurações</a></li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li><a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
              data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i> Desconectar-se</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Offcanvas Menu -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title text-light">WASOM UPFY</h5>
      <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
        </li>
        <li class="nav-item"><a class="nav-link active" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
            Estatísticas</a></li>
        <li class="nav-item"><a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i>
            Finanças</a></li>
        <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
            Artistas</a></li>
      </ul>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container my-4">
    <!-- Header -->
    <div class="comparison-header">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1><i class="bi bi-calendar-range me-3"></i>Comparar Períodos</h1>
          <p class="lead mb-0">Compare o desempenho das suas músicas entre diferentes períodos e visualize o crescimento
          </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <button class="btn btn-pink me-2" onclick="window.location='statistics'">
            <i class="bi bi-arrow-left"></i> Voltar
          </button>
          <button class="btn btn-secondary" onclick="exportComparison()">
            <i class="bi bi-download"></i> Exportar
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Select Periods -->
    <div class="mb-4">
      <label class="text-white mb-2">Seleção rápida:</label>
      <div>
        <span class="quick-select" onclick="setQuickCompare('week')">Esta semana vs Semana passada</span>
        <span class="quick-select" onclick="setQuickCompare('month')">Este mês vs Mês passado</span>
        <span class="quick-select" onclick="setQuickCompare('quarter')">Este trimestre vs Trimestre passado</span>
        <span class="quick-select" onclick="setQuickCompare('year')">Este ano vs Ano passado</span>
      </div>
    </div>

    <!-- Period Selection -->
    <div class="row mb-4">
      <div class="col-md-5">
        <div class="period-card period-a">
          <h4 class="text-white mb-3"><i class="bi bi-calendar-check me-2"></i>Período A</h4>
          <div class="row">
            <div class="col-6">
              <label class="text-white-50">Data inicial</label>
              <input type="date" id="startDateA" class="form-control bg-dark text-white border-secondary"
                value="2024-10-01">
            </div>
            <div class="col-6">
              <label class="text-white-50">Data final</label>
              <input type="date" id="endDateA" class="form-control bg-dark text-white border-secondary"
                value="2024-10-31">
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-2 d-flex align-items-center justify-content-center">
        <button class="btn-swap" onclick="swapPeriods()">
          <i class="bi bi-arrow-left-right"></i>
        </button>
      </div>

      <div class="col-md-5">
        <div class="period-card period-b">
          <h4 class="text-white mb-3"><i class="bi bi-calendar-check me-2"></i>Período B</h4>
          <div class="row">
            <div class="col-6">
              <label class="text-white-50">Data inicial</label>
              <input type="date" id="startDateB" class="form-control bg-dark text-white border-secondary"
                value="2024-11-01">
            </div>
            <div class="col-6">
              <label class="text-white-50">Data final</label>
              <input type="date" id="endDateB" class="form-control bg-dark text-white border-secondary"
                value="2024-11-30">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Compare Button -->
    <div class="text-center mb-5">
      <button class="btn-compare" onclick="comparePeriods()">
        <i class="bi bi-graph-up-arrow me-2"></i>Comparar Períodos
      </button>
    </div>

    <!-- Results Section (initially hidden) -->
    <div id="comparisonResults" style="display: none;">
      <!-- Total Streams Comparison -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="period-card">
            <div class="row align-items-center text-center">
              <div class="col-md-5">
                <div class="metric-label">Total de Streams (Período A)</div>
                <div class="total-streams-a" id="totalStreamsA">0</div>
                <div class="text-white-50" id="periodDatesA"></div>
              </div>
              <div class="col-md-2">
                <div class="vs-divider">VS</div>
              </div>
              <div class="col-md-5">
                <div class="metric-label">Total de Streams (Período B)</div>
                <div class="total-streams-b" id="totalStreamsB">0</div>
                <div class="text-white-50" id="periodDatesB"></div>
              </div>
            </div>

            <!-- Percentage Change -->
            <div class="text-center mt-4" id="percentageChange">
              <!-- Dynamic content -->
            </div>
          </div>
        </div>
      </div>

      <!-- Metrics Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="period-card">
            <div class="metric-box">
              <i class="bi bi-music-note-beamed fs-2" style="color: #ff0089;">
            </div>
            <div class="metric-label">Média Diária A</div>
            <div class="metric-value" id="dailyAvgA">0</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="period-card">
          <div class="metric-box">
            <i class="bi bi-music-note-beamed fs-2" style="color: #00ff88;">
          </div>
          <div class="metric-label">Média Diária B</div>
          <div class="metric-value" id="dailyAvgB">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="period-card">
        <div class="metric-box">
          <i class="bi bi-calendar-week fs-2" style="color: #ff0089;">
        </div>
        <div class="metric-label">Dias no Período A</div>
        <div class="metric-value" id="daysA">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="period-card">
      <div class="metric-box">
        <i class="bi bi-calendar-week fs-2" style="color: #00ff88;">
      </div>
      <div class="metric-label">Dias no Período B</div>
      <div class="metric-value" id="daysB">0</div>
    </div>
  </div>
  </div>
  </div>

  <!-- Growth Chart -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="period-card">
        <h5 class="text-white mb-3"><i class="bi bi-graph-up me-2"></i>Comparação Diária</h5>
        <canvas id="comparisonChart" style="height: 300px;"></canvas>
      </div>
    </div>
  </div>

  <!-- Platform Comparison -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="period-card">
        <h5 class="text-white mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Comparação por Plataforma</h5>
        <div class="table-responsive">
          <table class="table table-dark table-hover">
            <thead>
              <tr>
                <th>Plataforma</th>
                <th>Streams A</th>
                <th>Streams B</th>
                <th>Variação</th>
                <th>Tendência</th>
              </tr>
            </thead>
            <tbody id="platformComparisonBody">
              <!-- Dynamic content -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Artists Comparison -->
  <div class="row">
    <div class="col-md-6">
      <div class="period-card period-a">
        <h5 class="text-white mb-3"><i class="bi bi-person-fill me-2"></i>Top Artistas (Período A)</h5>
        <div id="topArtistsA">
          <!-- Dynamic content -->
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="period-card period-b">
        <h5 class="text-white mb-3"><i class="bi bi-person-fill me-2"></i>Top Artistas (Período B)</h5>
        <div id="topArtistsB">
          <!-- Dynamic content -->
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>

  <!-- Bottom Navigation -->
  <nav class="bottom-nav d-lg-none">
    <ul class="nav justify-content-around">
      <li class="nav-item">
        <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i><span>Lançamentos</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="../analytics/statistics"><i
            class="bi bi-bar-chart"></i><span>Estatísticas</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i><span>Finanças</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i><span>Artistas</span></a>
      </li>
    </ul>
  </nav>

  <!-- Logout Modal -->
  <div class="modal fade" id="logoutwasomupfy" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-dark">Terminar sessão</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-center text-dark">Tem certeza que deseja terminar sessão?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-danger" onclick="logout()">Sair</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../js/wp.tools.js"></script>
  <script src="../../js/theme.wp.js"></script>

  <script>
    // Dados simulados para comparação
    const mockData = {
      dailyData: {
        periodA: [3200, 3400, 3600, 3800, 4000, 4200, 4400, 4600, 4800, 5000, 5200, 5400, 5600, 5800, 6000, 6200, 6400, 6600, 6800, 7000, 7200, 7400, 7600, 7800, 8000, 8200, 8400, 8600, 8800, 9000, 9200],
        periodB: [3500, 3800, 4100, 4400, 4700, 5000, 5300, 5600, 5900, 6200, 6500, 6800, 7100, 7400, 7700, 8000, 8300, 8600, 8900, 9200, 9500, 9800, 10100, 10400, 10700, 11000, 11300, 11600, 11900, 12200]
      },
      platforms: [{
          name: 'Spotify',
          streamsA: 45000,
          streamsB: 67000,
          icon: 'bi-spotify',
          color: '#1db954',
          iconClass: 'spotify-icon'
        },
        {
          name: 'Deezer',
          streamsA: 23000,
          streamsB: 31000,
          icon: 'bi-music-player',
          color: '#ff0089',
          iconClass: 'deezer-icon'
        },
        {
          name: 'Apple Music',
          streamsA: 38000,
          streamsB: 42000,
          icon: 'bi-apple',
          color: '#ff4d4d',
          iconClass: 'apple-icon'
        },
        {
          name: 'YouTube',
          streamsA: 52000,
          streamsB: 48000,
          icon: 'bi-youtube',
          color: '#ff0000',
          iconClass: 'youtube-icon'
        },
        {
          name: 'Amazon Music',
          streamsA: 15000,
          streamsB: 22000,
          icon: 'bi-music-note-beamed',
          color: '#00a8e1',
          iconClass: 'amazon-icon'
        }
      ],
      artists: {
        periodA: [{
            name: 'Eunic',
            streams: 25000,
            change: '+15%'
          },
          {
            name: 'CHVJays',
            streams: 18000,
            change: '+8%'
          },
          {
            name: 'Anderson Cuper',
            streams: 12000,
            change: '+5%'
          }
        ],
        periodB: [{
            name: 'Eunic',
            streams: 32000,
            change: '+28%'
          },
          {
            name: 'CHVJays',
            streams: 21000,
            change: '+16%'
          },
          {
            name: 'Anderson Cuper',
            streams: 15000,
            change: '+25%'
          }
        ]
      }
    };

    let comparisonChart;

    function setQuickCompare(type) {
      const today = new Date();
      let startA, endA, startB, endB;

      switch (type) {
        case 'week':
          // Esta semana (últimos 7 dias) vs semana passada
          endA = new Date(today);
          endA.setDate(today.getDate() - 7);
          startA = new Date(endA);
          startA.setDate(endA.getDate() - 6);

          endB = new Date(today);
          startB = new Date(endB);
          startB.setDate(endB.getDate() - 6);
          break;

        case 'month':
          // Este mês vs mês passado
          startA = new Date(today.getFullYear(), today.getMonth() - 1, 1);
          endA = new Date(today.getFullYear(), today.getMonth(), 0);

          startB = new Date(today.getFullYear(), today.getMonth(), 1);
          endB = new Date(today.getFullYear(), today.getMonth() + 1, 0);
          break;

        case 'quarter':
          // Este trimestre vs trimestre passado
          let quarter = Math.floor(today.getMonth() / 3);
          startA = new Date(today.getFullYear(), (quarter - 1) * 3, 1);
          endA = new Date(today.getFullYear(), quarter * 3, 0);

          startB = new Date(today.getFullYear(), quarter * 3, 1);
          endB = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
          break;

        case 'year':
          // Este ano vs ano passado
          startA = new Date(today.getFullYear() - 1, 0, 1);
          endA = new Date(today.getFullYear() - 1, 11, 31);

          startB = new Date(today.getFullYear(), 0, 1);
          endB = new Date(today.getFullYear(), 11, 31);
          break;
      }

      // Format dates and set inputs
      document.getElementById('startDateA').value = formatDate(startA);
      document.getElementById('endDateA').value = formatDate(endA);
      document.getElementById('startDateB').value = formatDate(startB);
      document.getElementById('endDateB').value = formatDate(endB);

      // Automatically compare
      comparePeriods();
    }

    function formatDate(date) {
      return date.toISOString().split('T')[0];
    }

    function swapPeriods() {
      const startA = document.getElementById('startDateA').value;
      const endA = document.getElementById('endDateA').value;
      const startB = document.getElementById('startDateB').value;
      const endB = document.getElementById('endDateB').value;

      document.getElementById('startDateA').value = startB;
      document.getElementById('endDateA').value = endB;
      document.getElementById('startDateB').value = startA;
      document.getElementById('endDateB').value = endA;

      comparePeriods();
    }

    function calculateTotalStreams(data) {
      return data.reduce((a, b) => a + b, 0);
    }

    function calculatePercentageChange(oldVal, newVal) {
      return ((newVal - oldVal) / oldVal * 100).toFixed(1);
    }

    function comparePeriods() {
      // Show results section
      document.getElementById('comparisonResults').style.display = 'block';

      // Get dates
      const startA = new Date(document.getElementById('startDateA').value);
      const endA = new Date(document.getElementById('endDateA').value);
      const startB = new Date(document.getElementById('startDateB').value);
      const endB = new Date(document.getElementById('endDateB').value);

      // Format for display
      document.getElementById('periodDatesA').innerHTML = `${formatDate(startA)} - ${formatDate(endA)}`;
      document.getElementById('periodDatesB').innerHTML = `${formatDate(startB)} - ${formatDate(endB)}`;

      // Calculate days
      const daysA = Math.ceil((endA - startA) / (1000 * 60 * 60 * 24)) + 1;
      const daysB = Math.ceil((endB - startB) / (1000 * 60 * 60 * 24)) + 1;

      document.getElementById('daysA').innerHTML = daysA;
      document.getElementById('daysB').innerHTML = daysB;

      // Calculate totals
      const totalA = calculateTotalStreams(mockData.dailyData.periodA.slice(0, daysA));
      const totalB = calculateTotalStreams(mockData.dailyData.periodB.slice(0, daysB));

      document.getElementById('totalStreamsA').innerHTML = totalA.toLocaleString();
      document.getElementById('totalStreamsB').innerHTML = totalB.toLocaleString();

      // Calculate averages
      document.getElementById('dailyAvgA').innerHTML = Math.round(totalA / daysA).toLocaleString();
      document.getElementById('dailyAvgB').innerHTML = Math.round(totalB / daysB).toLocaleString();

      // Percentage change
      const percentChange = calculatePercentageChange(totalA, totalB);
      const changeElement = document.getElementById('percentageChange');
      const changeClass = percentChange > 0 ? 'badge-positive' : (percentChange < 0 ? 'badge-negative' : 'badge-neutral');
      const changeIcon = percentChange > 0 ? 'bi-arrow-up' : (percentChange < 0 ? 'bi-arrow-down' : 'bi-dash');

      changeElement.innerHTML = `
        <span class="comparison-badge ${changeClass}">
          <i class="bi ${changeIcon} me-1"></i>
          ${Math.abs(percentChange)}% ${percentChange > 0 ? 'de crescimento' : (percentChange < 0 ? 'de queda' : 'estável')}
        </span>
      `;

      // Update chart
      updateComparisonChart(daysA, daysB);

      // Update platform comparison
      updatePlatformComparison();

      // Update top artists
      updateTopArtists();

      // Scroll to results
      document.getElementById('comparisonResults').scrollIntoView({
        behavior: 'smooth'
      });
    }

    function updateComparisonChart(daysA, daysB) {
      const ctx = document.getElementById('comparisonChart').getContext('2d');

      if (comparisonChart) {
        comparisonChart.destroy();
      }

      // Prepare data
      const maxDays = Math.max(daysA, daysB);
      const labels = Array.from({
        length: maxDays
      }, (_, i) => `Dia ${i + 1}`);

      const dataA = [...mockData.dailyData.periodA.slice(0, daysA)];
      const dataB = [...mockData.dailyData.periodB.slice(0, daysB)];

      // Pad with null for missing days
      while (dataA.length < maxDays) dataA.push(null);
      while (dataB.length < maxDays) dataB.push(null);

      comparisonChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
              label: 'Período A',
              data: dataA,
              borderColor: '#ff0089',
              backgroundColor: 'rgba(255, 0, 137, 0.1)',
              tension: 0.4,
              fill: true
            },
            {
              label: 'Período B',
              data: dataB,
              borderColor: '#00ff88',
              backgroundColor: 'rgba(0, 255, 136, 0.1)',
              tension: 0.4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: {
              labels: {
                color: 'white'
              }
            }
          },
          scales: {
            y: {
              grid: {
                color: 'rgba(255,255,255,0.1)'
              },
              ticks: {
                color: 'white'
              }
            },
            x: {
              grid: {
                color: 'rgba(255,255,255,0.1)'
              },
              ticks: {
                color: 'white'
              }
            }
          }
        }
      });
    }

    function updatePlatformComparison() {
      const tbody = document.getElementById('platformComparisonBody');
      tbody.innerHTML = '';

      mockData.platforms.forEach(platform => {
        const change = ((platform.streamsB - platform.streamsA) / platform.streamsA * 100).toFixed(1);
        const changeClass = change > 0 ? 'badge-positive' : (change < 0 ? 'badge-negative' : 'badge-neutral');
        const changeIcon = change > 0 ? 'bi-arrow-up' : (change < 0 ? 'bi-arrow-down' : 'bi-dash');

        const row = document.createElement('tr');
        row.innerHTML = `
          <td>
            <div class="d-flex align-items-center">
              <div class="platform-icon ${platform.iconClass} me-2">
                <i class="bi ${platform.icon}"></i>
              </div>
              ${platform.name}
            </div>
          </td>
          <td>${platform.streamsA.toLocaleString()}</td>
          <td>${platform.streamsB.toLocaleString()}</td>
          <td>
            <span class="percentage-change ${changeClass}">
              <i class="bi ${changeIcon} me-1"></i>
              ${Math.abs(change)}%
            </span>
          </td>
          <td>
            <div class="progress progress-comparison">
              <div class="progress-bar progress-bar-period-a" style="width: ${(platform.streamsA / Math.max(platform.streamsA, platform.streamsB) * 100)}%">
                ${platform.streamsA.toLocaleString()}
              </div>
              <div class="progress-bar progress-bar-period-b" style="width: ${(platform.streamsB / Math.max(platform.streamsA, platform.streamsB) * 100)}%">
                ${platform.streamsB.toLocaleString()}
              </div>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });
    }

    function updateTopArtists() {
      const artistsAContainer = document.getElementById('topArtistsA');
      const artistsBContainer = document.getElementById('topArtistsB');

      artistsAContainer.innerHTML = '';
      artistsBContainer.innerHTML = '';

      mockData.artists.periodA.forEach((artist, index) => {
        const div = document.createElement('div');
        div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border-bottom border-secondary';
        div.innerHTML = `
          <div>
            <span class="badge bg-secondary me-2">${index + 1}</span>
            <strong class="text-white">${artist.name}</strong>
          </div>
          <div>
            <span class="text-white me-3">${artist.streams.toLocaleString()}</span>
            <span class="badge bg-success">${artist.change}</span>
          </div>
        `;
        artistsAContainer.appendChild(div);
      });

      mockData.artists.periodB.forEach((artist, index) => {
        const div = document.createElement('div');
        div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border-bottom border-secondary';
        div.innerHTML = `
          <div>
            <span class="badge bg-secondary me-2">${index + 1}</span>
            <strong class="text-white">${artist.name}</strong>
          </div>
          <div>
            <span class="text-white me-3">${artist.streams.toLocaleString()}</span>
            <span class="badge bg-success">${artist.change}</span>
          </div>
        `;
        artistsBContainer.appendChild(div);
      });
    }

    function exportComparison() {
      alert('Exportando comparação... (funcionalidade em desenvolvimento)');
      // Aqui você implementaria a lógica de exportação (PDF, Excel, etc)
    }

    function logout() {
      window.location = "logout";
    }

    // Initialize with default comparison
    document.addEventListener('DOMContentLoaded', function() {
      comparePeriods();
    });
  </script>
</body>

</html>