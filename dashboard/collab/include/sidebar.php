<?php
// Detecção da página ativa (apenas se ainda não definida)
if (!isset($active_page)) {
    $current_uri = $_SERVER['REQUEST_URI'];
    $active_page = 'dashboard';

    if (strpos($current_uri, '/collab/releases') !== false) {
        $active_page = 'releases';
    } elseif (strpos($current_uri, '/collab/artists') !== false) {
        $active_page = 'artists';
    } elseif (strpos($current_uri, '/collab/statistics') !== false) {
        $active_page = 'statistics';
    } elseif (strpos($current_uri, '/collab/finances') !== false) {
        $active_page = 'finances';
    }
}

if (!function_exists('is_active_collab')) {
    function is_active_collab($page, $current)
    {
        return $page === $current ? 'active' : '';
    }
}
?>

<aside class="collab-sidebar" id="collabSidebar">

    <!-- Owner info -->
    <div class="owner-card mb-3">
        <div
            style="font-size:.65rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">
            Conta
        </div>
        <div class="fw-bold" style="font-size:.95rem"><?php echo $owner_artist_name; ?></div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.75);margin-top:2px"><?php echo $plan_name; ?></div>
    </div>

    <div class="sidebar-section">Menu</div>

    <a href="overview" class="sidebar-link <?php echo is_active_collab('dashboard', $active_page); ?>">
        <i class="bi bi-speedometer2"></i>Dashboard
    </a>

    <?php if ($can_view_releases): ?>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/releases"
        class="sidebar-link <?php echo is_active_collab('releases', $active_page); ?>">
        <i class="bi bi-disc"></i>Lançamentos
        <?php if ((int)($album_stats['pending'] ?? 0) > 0): ?>
        <span class="badge-count"><?php echo $album_stats['pending']; ?></span>
        <?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if ($can_view_artists): ?>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/artists"
        class="sidebar-link <?php echo is_active_collab('artists', $active_page); ?>">
        <i class="bi bi-people"></i>Artistas
    </a>
    <?php endif; ?>

    <?php if ($can_view_finances): ?>
    <div class="sidebar-section">Finanças</div>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/finances"
        class="sidebar-link <?php echo is_active_collab('finances', $active_page); ?>">
        <i class="bi bi-currency-dollar"></i>Visão geral
    </a>
    <?php endif; ?>

    <?php if ($can_view_stats): ?>
    <div class="sidebar-section">Análise</div>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/statistics"
        class="sidebar-link <?php echo is_active_collab('statistics', $active_page); ?>">
        <i class="bi bi-bar-chart"></i>Estatísticas
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Conta</div>
    <a href="#" class="sidebar-link" data-bs-toggle="modal" data-bs-target="#myProfileModal">
        <i class="bi bi-person-gear"></i>O meu perfil
    </a>
    <a href="#" class="sidebar-link text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="bi bi-box-arrow-right"></i>Terminar sessão
    </a>

</aside>