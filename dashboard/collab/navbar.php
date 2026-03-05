<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Navbar Universal
// Arquivo: dashboard/collab/navbar.php
//
// Inclui nas páginas existentes em substituição
// do bloco <nav class="navbar ..."> ... </nav>
// e do <div class="offcanvas ..."> ... </div>
//
// Requer que o middleware.php já tenha corrido:
//   $is_collab, $collab, $collab_role, $first_name, $user_name
// ══════════════════════════════════════════════

$_nav_role_meta = [
    'admin'   => ['label'=>'Administrador','color'=>'#dc3545','icon'=>'bi-shield-fill'],
    'editor'  => ['label'=>'Editor',       'color'=>'#FF0089','icon'=>'bi-pencil-fill'],
    'analyst' => ['label'=>'Analista',     'color'=>'#0d6efd','icon'=>'bi-bar-chart-fill'],
    'support' => ['label'=>'Suporte',      'color'=>'#198754','icon'=>'bi-headset'],
];
$_nav_rm = ($is_collab && $collab_role) ? ($_nav_role_meta[$collab_role] ?? []) : [];

// Active page detection
$_current = basename($_SERVER['PHP_SELF'], '.php');
$_path    = $_SERVER['REQUEST_URI'] ?? '';
$_is_active = function(string $segment) use ($_path): bool {
    return str_contains($_path, $segment);
};
?>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu">
            <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
        </button>

        <?php if ($is_collab): ?>
        <a class="navbar-brand d-flex flex-column" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/collab/overview"
            style="line-height:1.1">
            <span class="fw-bold text-white" style="font-size:.95rem">WASOM UPFY</span>
            <span style="font-size:.62rem;color:rgba(255,255,255,.65);font-weight:400">Colaboradores</span>
        </a>
        <?php else: ?>
        <a class="navbar-brand" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/painel">
            <span class="text-light fw-bold" style="font-family:Arial,sans-serif">WASOM UPFY</span>
        </a>
        <?php endif; ?>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                <?php if (!$is_collab): ?>
                <!-- Navbar normal -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/painel') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/painel">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/launch') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/launch/releases">
                        <i class="bi bi-disc me-1"></i>Lançamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/analytics') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/analytics/statistics">
                        <i class="bi bi-bar-chart me-1"></i>Estatísticas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/finances') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/finances/overview">
                        <i class="bi bi-currency-dollar me-1"></i>Finanças
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/artists') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/artists/artists-list">
                        <i class="bi bi-people me-1"></i>Artistas
                    </a>
                </li>

                <?php else: ?>
                <!-- Navbar colaborador — filtrada por role -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/collab/overview">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <?php if (collabCan('releases')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/launch') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/launch/releases">
                        <i class="bi bi-disc me-1"></i>Lançamentos
                    </a>
                </li>
                <?php endif; ?>
                <?php if (collabCan('artists')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/artists') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/artists/artists-list">
                        <i class="bi bi-people me-1"></i>Artistas
                    </a>
                </li>
                <?php endif; ?>
                <?php if (collabCan('stats')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/analytics') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/analytics/statistics">
                        <i class="bi bi-bar-chart me-1"></i>Estatísticas
                    </a>
                </li>
                <?php endif; ?>
                <?php if (collabCan('finances')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $_is_active('/finances') ? 'active' : ''; ?>"
                        href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/finances/overview">
                        <i class="bi bi-currency-dollar me-1"></i>Finanças
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="user-menu d-flex align-items-center gap-2">
            <button class="theme-toggle btn btn-link text-white p-0" id="themeToggle">
                <i class="bi bi-sun" id="themeIcon"></i>
            </button>

            <?php if (!$is_collab): ?>
            <!-- Ícone notificações (só utilizadores normais) -->
            <a href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/notifications" class="text-white"><i
                    class="bi bi-bell fs-4"></i></a>
            <?php endif; ?>

            <div class="dropdown">
                <a href="#" class="text-white d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <?php if ($is_collab && !empty($collab['photo_collab'])): ?>
                    <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>"
                        style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.4)"
                        onerror="this.style.display='none'" alt="" />
                    <?php else: ?>
                    <i class="bi bi-person-circle fs-4"></i>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:210px">
                    <li class="px-3 py-2">
                        <div class="fw-bold small"><?php echo $first_name; ?></div>
                        <div class="text-muted" style="font-size:.72rem">@<?php echo $user_name; ?></div>
                        <?php if ($is_collab && $_nav_rm): ?>
                        <div class="mt-1">
                            <span
                                style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,0,137,.08);color:<?php echo $_nav_rm['color']; ?>;padding:2px 10px;border-radius:20px;font-size:.68rem;font-weight:700">
                                <i class="bi <?php echo $_nav_rm['icon']; ?>"></i><?php echo $_nav_rm['label']; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>

                    <?php if ($is_collab): ?>
                    <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/collab/overview">
                            <i class="bi bi-speedometer2 me-2"></i>Painel colaborador
                        </a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy">
                            <i class="bi bi-box-arrow-right me-2"></i>Terminar sessão
                        </a></li>

                    <?php else: ?>
                    <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/user/profile">
                            <i class="bi bi-person me-2"></i>Meu Perfil
                        </a></li>
                    <li><a class="dropdown-item"
                            href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/account/manage-account">
                            <i class="bi bi-tools me-2"></i>Gestão de Conta
                        </a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/page/settings">
                            <i class="bi bi-gear me-2"></i>Configurações
                        </a></li>
                    <li><a class="dropdown-item" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/page/plans">
                            <i class="bi bi-star me-2"></i>Planos
                        </a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy">
                            <i class="bi bi-box-arrow-right me-2"></i>Desconectar
                        </a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Offcanvas mobile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu">
    <div class="offcanvas-header">
        <span class="fw-bold" style="font-family:Arial,sans-serif">WASOM UPFY</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column gap-1">
            <?php if ($is_collab): ?>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/collab/overview">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a></li>
            <?php if (collabCan('releases')): ?>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/launch/releases">
                    <i class="bi bi-disc me-2"></i>Lançamentos
                </a></li>
            <?php endif; ?>
            <?php if (collabCan('artists')): ?>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/artists/artists-list">
                    <i class="bi bi-people me-2"></i>Artistas
                </a></li>
            <?php endif; ?>
            <?php if (collabCan('stats')): ?>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/analytics/statistics">
                    <i class="bi bi-bar-chart me-2"></i>Estatísticas
                </a></li>
            <?php endif; ?>
            <?php if (collabCan('finances')): ?>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/finances/overview">
                    <i class="bi bi-currency-dollar me-2"></i>Finanças
                </a></li>
            <?php endif; ?>
            <?php else: ?>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/painel">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a></li>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/launch/releases">
                    <i class="bi bi-disc me-2"></i>Lançamentos
                </a></li>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/artists/artists-list">
                    <i class="bi bi-people me-2"></i>Artistas
                </a></li>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/user/profile">
                    <i class="bi bi-person me-2"></i>Meu Perfil
                </a></li>
            <li><a class="nav-link" href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/account/manage-account">
                    <i class="bi bi-tools me-2"></i>Gestão de Conta
                </a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Modal logout (usado por todas as páginas) -->
<div class="modal fade" id="logoutwasomupfy" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Terminar sessão?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-footer border-0 gap-2">
                <button class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Continuar</button>
                <a href="<?php echo $is_collab ? rtrim(APP_URL,'/').'/dashboard/collab/logout' : rtrim(APP_URL,'/').'/logout'; ?>"
                    class="btn btn-danger flex-fill">
                    <i class="bi bi-box-arrow-right me-1"></i>Terminar
                </a>
            </div>
        </div>
    </div>
</div>