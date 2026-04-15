<nav class="bottom-nav-collab">
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/overview"
        class="<?php echo isset($active_page) ? is_active_collab('dashboard', $active_page) : ''; ?>">
        <i class="bi bi-speedometer2"></i>Dashboard
    </a>
    <?php if ($can_view_releases): ?>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/releases"
        class="<?php echo isset($active_page) ? is_active_collab('releases', $active_page) : ''; ?>">
        <i class="bi bi-disc"></i>Releases
    </a>
    <?php endif; ?>
    <?php if ($can_view_artists): ?>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/artists"
        class="<?php echo isset($active_page) ? is_active_collab('artists', $active_page) : ''; ?>">
        <i class="bi bi-people"></i>Artistas
    </a>
    <?php endif; ?>
    <?php if ($can_view_stats): ?>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/statistics"
        class="<?php echo isset($active_page) ? is_active_collab('statistics', $active_page) : ''; ?>">
        <i class="bi bi-bar-chart"></i>Stats
    </a>
    <?php endif; ?>
    <?php if ($can_view_finances): ?>
    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/finances"
        class="<?php echo isset($active_page) ? is_active_collab('finances', $active_page) : ''; ?>">
        <i class="bi bi-bar-chart"></i>Finanças
    </a>
    <?php endif; ?>
</nav>