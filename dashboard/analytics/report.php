<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Relatórios Financeiros
// Arquivo: dashboard/analytics/report.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
  redirect('authentic/logout');
}

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ── Relatórios agrupados por mês/ano ──────────
// Campo report_file na tabela _royalty guarda o PDF gerado pela equipa
$reports_q = $db->prepare("
    SELECT
        r.year_royalty,
        r.month_royalty,
        SUM(r.net_royalty_aoa)  AS total_aoa,
        SUM(r.net_royalty)      AS total_usd,
        SUM(r.gross_revenue)    AS total_gross,
        COUNT(r.id_royalty)     AS num_tracks,
        MAX(r.status_royalty)   AS status_royalty,
        MAX(r.report_file)      AS report_file,
        MAX(r.paid_at)          AS paid_at
    FROM _royalty r
    WHERE r.id_users = ?
    GROUP BY r.year_royalty, r.month_royalty
    ORDER BY r.year_royalty DESC, r.month_royalty DESC
");
$reports_q->execute([$id_users]);
$reports = $reports_q->fetchAll(PDO::FETCH_ASSOC);

// ── Totais pagos ──────────────────────────────
$totals_q = $db->prepare("
    SELECT
        COALESCE(SUM(net_royalty_aoa), 0) AS grand_aoa,
        COALESCE(SUM(net_royalty), 0)     AS grand_usd
    FROM _royalty WHERE id_users = ? AND status_royalty = 'paid'
");
$totals_q->execute([$id_users]);
$totals = $totals_q->fetch();

// ── Helpers ────────────────────────────────────
$months_pt = [
  1 => 'Janeiro',
  2 => 'Fevereiro',
  3 => 'Março',
  4 => 'Abril',
  5 => 'Maio',
  6 => 'Junho',
  7 => 'Julho',
  8 => 'Agosto',
  9 => 'Setembro',
  10 => 'Outubro',
  11 => 'Novembro',
  12 => 'Dezembro'
];
$status_map = [
  'pending'    => ['label' => 'Pendente',    'class' => 'bg-warning text-dark'],
  'processing' => ['label' => 'A processar', 'class' => 'bg-primary text-white'],
  'paid'       => ['label' => 'Pago',        'class' => 'bg-success text-white'],
  'cancelled'  => ['label' => 'Cancelado',   'class' => 'bg-secondary text-white'],
];
$base_url    = rtrim(APP_URL, '/');
$reports_url = $base_url . '/assets/reports/';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="theme-color" content="#FF0089" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
  <link rel="manifest" href="../manifest.json" />
  <title>Relatórios — <?php echo APP_NAME; ?></title>
  <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../css/lastest-style.css" />
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
            <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
              Estatísticas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i>
              Finanças</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de
              canal
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
            <a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
              Gestão de
              Conta</a>
          </li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li>
            <a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
              Configurações</a>
          </li>
          <li>
            <a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
              Notificações</a>
          </li>
          <li>
            <a class="dropdown-item" href="../services/available-services"><i class="bi bi-star me-2"></i>
              Conta e
              serviços disponíveis</a>
          </li>
          <li>
            <a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
              data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i>
              Desconectar-se</a>
          </li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li>
            <a class="dropdown-item" href="../page/about"><i class="bi bi-info-circle me-2"></i> Sobre</a>
          </li>
          <li>
            <a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Enviar pedido
              de
              suporte</a>
          </li>
          <li>
            <a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i> Perguntas
              frequentes</a>
          </li>
          <li>
            <a class="dropdown-item" href="../page/help"><i class="bi bi-question-circle me-2"></i>
              Ajuda</a>
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

  <!-- Offcanvas Menu para Mobile e Desktop -->
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
          <a class="nav-link" href="../youtube"><i class="bi bi-youtube"></i> Unificação de canal YouTube</a>
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
          <a class="nav-link" href="../services/available-services"><i class="bi bi-star"></i> Conta e
            serviços
            disponíveis</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="../page/help"><i class="bi bi-question-circle"></i> Ajuda</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal"
            data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a>
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
  <div class="container my-4">

    <!-- Cabeçalho -->
    <div class="page-header">
      <div class="row align-items-center mb-4">
        <div class="col-md-8">
          <div class="page-header-compact">
            <h1><i class="bi bi-file-earmark-text-fill me-3"></i>Relatórios Financeiros</h1>
            <p class="lead">
              Todos os relatórios mensais dos conteúdos distribuídos por esta conta estão disponíveis
              aqui.
              Faz o download para análise detalhada no teu dispositivo.
            </p>
          </div>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <a href="../finances/overview" class="btn btn-light">
            <i class="bi bi-arrow-left-circle me-2"></i>Voltar às Finanças
          </a>
        </div>
      </div>

      <style>
        .page-header::before {
          content: '\F45D';
          /* bi-file-earmark-text-fill */
        }
      </style>
    </div>

    <?php if (!empty($reports)): ?>
      <!-- Cards de resumo -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card h-100"
            style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
            <div class="card-body">
              <div class="text-muted small mb-1"><i class="bi bi-calendar-check me-1"></i>Períodos com
                royalties</div>
              <div class="fw-bold" style="font-size:1.6rem"><?php echo count($reports); ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100"
            style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
            <div class="card-body">
              <div class="text-muted small mb-1"><i class="bi bi-currency-dollar me-1"></i>Total pago (USD)
              </div>
              <div class="fw-bold" style="font-size:1.6rem">
                $<?php echo number_format((float)$totals['grand_usd'], 2); ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100"
            style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
            <div class="card-body">
              <div class="text-muted small mb-1"><i class="bi bi-cash me-1"></i>Total pago (AOA)</div>
              <div class="fw-bold" style="font-size:1.6rem">
                <?php echo number_format((float)$totals['grand_aoa'], 2, ',', '.'); ?> Kz</div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Tabela de relatórios -->
    <div class="table-card mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-file-earmark-text me-2 text-pink"></i>Relatórios Mensais</h6>
          <span class="badge bg-secondary"><?php echo count($reports); ?> períodos</span>
        </div>
        <div class="table-responsive">
          <?php if (empty($reports)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
              <div class="small fw-semibold mb-1">Nenhum relatório disponível ainda.</div>
              <div class="small">Os relatórios aparecem aqui após o processamento mensal dos teus royalties
                pela equipa Wasom Upfy.</div>
            </div>
          <?php else: ?>
            <table id="reportsWasomupfy" class="table table-striped table-hover mb-0">
              <thead>
                <tr>
                  <th>Mês</th>
                  <th>Ano</th>
                  <th class="text-center">Faixas</th>
                  <th>Valor (USD)</th>
                  <th>Valor (AOA)</th>
                  <th>Estado</th>
                  <th class="text-center">Arquivo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reports as $rep):
                  $month_name = $months_pt[(int)$rep['month_royalty']] ?? '—';
                  $st         = $status_map[$rep['status_royalty']] ?? $status_map['pending'];
                  $has_file   = !empty($rep['report_file']);
                ?>
                  <tr>
                    <td class="fw-semibold small"><?php echo $month_name; ?></td>
                    <td class="small"><?php echo (int)$rep['year_royalty']; ?></td>
                    <td class="small text-center"><?php echo (int)$rep['num_tracks']; ?></td>
                    <td class="small fw-semibold">$<?php echo number_format((float)$rep['total_usd'], 4); ?>
                    </td>
                    <td class="small fw-semibold">
                      <?php echo $rep['total_aoa']
                        ? number_format((float)$rep['total_aoa'], 2, ',', '.') . ' Kz'
                        : '—'; ?>
                    </td>
                    <td>
                      <span class="badge <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span>
                    </td>
                    <td class="text-center">
                      <?php if ($has_file): ?>
                        <a href="<?php echo htmlspecialchars($reports_url . $rep['report_file']); ?>"
                          class="btn btn-sm btn-outline-pink" target="_blank" rel="noopener" download
                          data-bs-toggle="tooltip"
                          title="Descarregar <?php echo $month_name . ' ' . $rep['year_royalty']; ?>">
                          <i class="bi bi-download me-1"></i>PDF
                        </a>
                      <?php else: ?>
                        <span class="text-muted small" data-bs-toggle="tooltip"
                          title="O arquivo ainda não foi gerado pela equipa.">
                          <i class="bi bi-clock me-1"></i>A aguardar
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Nota informativa -->
    <div class="p-3 mb-4"
      style="background:rgba(255,0,137,.04);border-radius:14px;border:1px solid rgba(255,0,137,.12)">
      <div class="d-flex gap-2 align-items-start">
        <i class="bi bi-info-circle-fill mt-1" style="color:#FF0089;flex-shrink:0"></i>
        <div style="font-size:.8rem;color:var(--text-muted,#6c757d)">
          Os relatórios são gerados mensalmente pela equipa Wasom Upfy após o encerramento do período de
          reporte das plataformas de streaming.
          Caso tenhas dúvidas sobre os valores apresentados, contacta o <a href="../page/support"
            class="text-pink">suporte</a>.
        </div>
      </div>
    </div>

  </div><!-- /container -->

  <!-- Bottom Nav Mobile -->
  <nav class="bottom-nav d-lg-none">
    <ul class="nav justify-content-around">
      <li class="nav-item"><a class="nav-link" href="../painel"><i
            class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
      <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
            class="bi bi-disc"></i><span>Lançamentos</span></a></li>
      <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
            class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
      <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
            class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
      <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i
            class="bi bi-person"></i><span>Artistas</span></a></li>
    </ul>
  </nav>

  <!-- ════ MODAL — Logout ════ -->
  <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header border-0 pb-0">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
              style="width:44px;height:44px;background:rgba(220,53,69,.12)">
              <i class="bi bi-box-arrow-right fs-5 text-danger"></i>
            </div>
            <div>
              <h5 class="modal-title text-dark mb-0" id="logoutwasomupfyLabel">Terminar sessão</h5>
              <small class="text-muted">@<?php echo $user_name; ?></small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body pt-2">
          <!-- Informação da sessão actual -->
          <div class="rounded-3 p-3 mb-3" style="background:rgba(0,0,0,.04)">
            <div class="row g-2" style="font-size:.82rem">
              <div class="col-6 d-flex gap-2 align-items-start">
                <i class="bi bi-clock text-muted mt-1 flex-shrink-0"></i>
                <div>
                  <div class="text-muted">Duração da sessão</div>
                  <div class="fw-semibold text-dark"><?php echo $session_duration_str; ?></div>
                </div>
              </div>
              <div class="col-6 d-flex gap-2 align-items-start">
                <i class="bi bi-calendar3 text-muted mt-1 flex-shrink-0"></i>
                <div>
                  <div class="text-muted">Último acesso</div>
                  <div class="fw-semibold text-dark"><?php echo $last_login_str; ?></div>
                </div>
              </div>
              <div class="col-6 d-flex gap-2 align-items-start">
                <i class="bi bi-globe text-muted mt-1 flex-shrink-0"></i>
                <div>
                  <div class="text-muted">Localização</div>
                  <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sess_location); ?>
                  </div>
                </div>
              </div>
              <div class="col-6 d-flex gap-2 align-items-start">
                <i class="bi bi-browser-chrome text-muted mt-1 flex-shrink-0"></i>
                <div>
                  <div class="text-muted">Navegador</div>
                  <div class="fw-semibold text-dark"><?php echo htmlspecialchars($browser); ?></div>
                </div>
              </div>
              <div class="col-6 d-flex gap-2 align-items-start">
                <i class="bi bi-hdd-network text-muted mt-1 flex-shrink-0"></i>
                <div>
                  <div class="text-muted">IP</div>
                  <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sess_ip); ?></div>
                </div>
              </div>
              <div class="col-6 d-flex gap-2 align-items-start">
                <i class="bi bi-person-badge text-muted mt-1 flex-shrink-0"></i>
                <div>
                  <div class="text-muted">Membro desde</div>
                  <div class="fw-semibold text-dark"><?php echo $member_since; ?></div>
                </div>
              </div>
            </div>
          </div>

          <p class="text-dark text-center mb-0" style="font-size:.9rem">
            Tens a certeza que queres terminar a sessão?<br>
            <span class="text-muted" style="font-size:.8rem">Terás de iniciar sessão novamente para aceder
              ao painel.</span>
          </p>
        </div>

        <div class="modal-footer border-0 pt-0 gap-2">
          <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
            <i class="bi bi-arrow-left me-1"></i>Não, continuar
          </button>
          <button class="btn btn-danger flex-fill" type="button" onclick="logout_wasomupfy()">
            <i class="bi bi-box-arrow-right me-1"></i>Sim, terminar
          </button>
        </div>

      </div>
    </div>
  </div>
  <!-- ════ MODAL — Logout  FIM ════ -->

  <script>
    function logout_wasomupfy() {
      window.location = '../logout';
    }
  </script>

  <!-- ═══ JS ═══ -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="../../js/theme.wp.js"></script>
  <script src="../../js/wp.tools.js"></script>
  <script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    <?php if (!empty($reports)): ?>
      $(document).ready(function() {
        $('#reportsWasomupfy').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          lengthChange: false,
          pageLength: 10,
          order: [
            [1, 'desc'],
            [0, 'desc']
          ],
          columnDefs: [{
            orderable: false,
            targets: 6
          }],
          language: {
            search: 'Pesquisar por mês ou ano:',
            info: 'A mostrar _START_ a _END_ de _TOTAL_ relatórios',
            paginate: {
              next: 'Próximo',
              previous: 'Anterior'
            },
            emptyTable: 'Nenhum relatório disponível.'
          }
        });
      });
    <?php endif; ?>
  </script>
</body>

</html>