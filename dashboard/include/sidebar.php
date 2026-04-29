<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Sidebar Dashboard (reutilizável)
// Arquivo: dashboard/include/sidebar.php
//
// Requer platform.php carregado antes.
// Estrutura: mesma do original, links convertidos
// para APP_URL, permissões e badges adicionados.
// ══════════════════════════════════════════════

// ── Deteção da página ativa ──────────────────
$current_uri = $_SERVER['REQUEST_URI'];
$active_page = 'painel'; // padrão

// Mapeamento: parte da URL -> chave do item
$routes = [
    // Principais (navbar + bottom)
    '/dashboard/releases'           => 'releases',
    '/dashboard/statistics'         => 'statistics',
    '/dashboard/overview'           => 'finances',
    '/dashboard/artists-list'       => 'artists',
    '/dashboard/youtube/ucy'        => 'youtube',
    // Perfil
    '/dashboard/user/profile'       => 'profile',
    //Financas
    '/dashboard/report'             => 'overview',
    '/dashboard/withdrawal'         => 'overview',
    '/dashboard/transactions'       => 'overview',
    //estatisticas
    '/dashboard/statistics/overview' => 'statistics',
    '/dashboard/statistics/artists' => 'statistics',
    // Configurações
    '/dashboard/page/settings'      => 'settings',
    '/dashboard/account/manage-account' => 'manage_account',
    '/dashboard/page/notifications' => 'notifications',
    '/dashboard/services/available-services' => 'services',
    // Ajuda / Suporte
    '/dashboard/page/help'          => 'help',
    '/dashboard/page/faq'           => 'faq',
    '/dashboard/page/support'       => 'support',
    // Políticas
    '/dashboard/page/politicies/privacy' => 'privacy',
    '/dashboard/page/politicies/terms'   => 'terms',
    // Planos / Pagamentos
    '/dashboard/all-plans'          => 'all_plans',
    '/dashboard/payment/pay'        => 'payment_pay',
    // Colaborador (collab)
    '/dashboard/collab/overview'    => 'collab_overview',
    '/dashboard/collab/releases'    => 'collab_releases',
    '/dashboard/collab/artists'     => 'collab_artists',
    '/dashboard/collab/statistics'  => 'collab_statistics',
    '/dashboard/collab/finances'    => 'collab_finances',
    // Adicionar artista
    '/dashboard/add-artist'         => 'artists',
];

foreach ($routes as $url => $page) {
    if (strpos($current_uri, $url) !== false) {
        $active_page = $page;
        break;
    }
}

function is_active_dash($page, $current)
{
    return ($page === $current) ? 'active' : '';
}
?>

<!-- Tela de Carregamento -->
<!-- <div class="loading-screen" id="loadingScreen">
    <img src="<?php echo APP_URL  ?>/assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90"
        height="90" alt="Loading-wasomupfy">
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
        <a class="navbar-brand" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel">
            <span class="text-light"
                style="font-weight: bold; box-sizing: border-box; text-transform: uppercase; font-family: Arial, sans-serif;">
                <?php echo APP_NAME; ?>
            </span>
        </a>

        <!-- Desktop Menu -->
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_dash('painel', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_dash('releases', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/releases">
                        <i class="bi bi-disc"></i> Lançamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_dash('statistics', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics">
                        <i class="bi bi-bar-chart"></i> Estatísticas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_dash('finances', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview">
                        <i class="bi bi-currency-dollar"></i> Finanças
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_dash('artists', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list">
                        <i class="bi bi-person"></i> Artistas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo is_active_dash('youtube', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/youtube/ucy">
                        <i class="bi bi-youtube"></i> U-CanalYT
                    </a>
                </li>
            </ul>
        </div>

        <!-- User Icon (Right) — com indicador de página ativa -->
        <div class="user-menu d-flex align-items-center">
            <!-- Theme Toggle Button -->
            <a class="theme-toggle text-white me-2" id="themeToggle">
                <i class="bi bi-sun" id="themeIcon"></i>
            </a>
            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/notifications"
                class="text-white me-2 position-relative" aria-label="Notificações" id="navNotifBtn">
                <i class="bi bi-bell fs-4"></i>
                <?php if ($notif_count > 0): ?>
                <span id="navNotifBadge" class="position-absolute translate-middle badge rounded-pill" style="top:2px;left:calc(100% - 4px);background:#FF0089;font-size:.6rem;
                       min-width:18px;height:18px;padding:0 5px;line-height:18px;
                       box-shadow:0 0 0 2px #1a1a2e;">
                    <?php echo $notif_count > 99 ? '99+' : $notif_count; ?>
                </span>
                <?php else: ?>
                <span id="navNotifBadge" class="position-absolute translate-middle badge rounded-pill" style="top:2px;left:calc(100% - 4px);background:#FF0089;font-size:.6rem;
                       min-width:18px;height:18px;padding:0 5px;line-height:18px;
                       box-shadow:0 0 0 2px #1a1a2e;display:none;">0</span>
                <?php endif; ?>
            </a>
            <a href="#" class="text-white" data-bs-toggle="dropdown">
                <?php if ($user_photo): ?>
                <img src="<?php echo APP_URL  ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($user_photo); ?>"
                    width="32" height="32" class="rounded-circle flex-shrink-0"
                    style="object-fit:cover;border:2px solid #FF4D4D" alt="Foto conta"
                    onerror="this.onerror=null;this.src='<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png'">
                <?php else: ?>
                <img src="<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png" width="32" height="32"
                    class="rounded-circle flex-shrink-0" style="object-fit:cover;" alt="Avatar">
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 <?php echo is_active_dash('profile', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile">
                        <?php if ($user_photo): ?>
                        <img src="<?php echo APP_URL  ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($user_photo); ?>"
                            width="32" height="32" class="rounded-circle flex-shrink-0"
                            style="object-fit:cover;border:2px solid #FF4D4D" alt="Foto conta"
                            onerror="this.onerror=null;this.src='<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png'">
                        <?php else: ?>
                        <img src="<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png" width="32" height="32"
                            class="rounded-circle flex-shrink-0" style="object-fit:cover;" alt="Avatar">
                        <?php endif; ?>
                        <div class="overflow-hidden">
                            <div class="fw-bold text-truncate" style="max-width:160px">
                                <?php echo $name_artist_band; ?></div>
                            <div class="text-white-50" style="font-size:.72rem">
                                Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 <?php echo is_active_dash('profile', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile">
                        <?php if ($user_photo): ?>
                        <img src="<?php echo APP_URL  ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($user_photo); ?>"
                            width="28" height="28" class="rounded-circle flex-shrink-0" style="object-fit:cover"
                            alt="Foto perfil"
                            onerror="this.onerror=null;this.src='<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png'">
                        <?php else: ?>
                        <img src="<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png" width="28" height="28"
                            class="rounded-circle flex-shrink-0" style="object-fit:cover" alt="Perfil"
                            onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<i class=\'bi bi-person-circle fs-5 flex-shrink-0\'></i>')">
                        <?php endif; ?>
                        Meu Perfil
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <a class="dropdown-item <?php echo is_active_dash('manage_account', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/account/manage-account">
                        <i class="bi bi-tools me-2"></i> Gestão de Conta
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?php echo is_active_dash('settings', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/settings">
                        <i class="bi bi-gear me-2"></i> Configurações
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <a class="dropdown-item <?php echo is_active_dash('support', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">
                        <i class="bi bi-headset me-2"></i> Enviar pedido de suporte
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?php echo is_active_dash('faq', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/faq">
                        <i class="bi bi-chat-left-text me-2"></i> Perguntas frequentes
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <a class="dropdown-item <?php echo is_active_dash('privacy', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/politicies/privacy">
                        <i class="bi bi-shield-check"></i><span>Privacidade</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?php echo is_active_dash('terms', $active_page); ?>"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/politicies/terms">
                        <i class="bi bi-file-text"></i><span>Termos</span>
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <a class="dropdown-item text-danger" href="#?logout-wasomupfy" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i>
                        Desconectar-se</a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <span class="dropdown-item-text">Versão - <?php echo APP_VERSION ?></span>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Offcanvas Menu para Mobile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasMenuLabel">
            <span class="text-light"
                style="font-weight: bold; box-sizing: border-box; text-transform: uppercase; font-family: Arial, sans-serif;">
                WASOM UPFY
            </span>
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('painel', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <!-- Lançamentos -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('releases', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/releases">
                    <i class="bi bi-disc"></i> Lançamentos
                </a>
            </li>
            <!-- Estatísticas -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('statistics', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics">
                    <i class="bi bi-bar-chart"></i> Estatísticas
                </a>
            </li>
            <!-- Finanças -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('finances', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview">
                    <i class="bi bi-currency-dollar"></i> Finanças
                </a>
            </li>
            <!-- Artistas -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('artists', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list">
                    <i class="bi bi-person"></i> Artistas
                </a>
            </li>
            <!-- YouTube -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('youtube', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/youtube/ucy">
                    <i class="bi bi-youtube"></i> U-CanalYT
                </a>
            </li>
            <!-- Meu Perfil -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('profile', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile">
                    <?php if ($user_photo): ?>
                    <img src="<?php echo APP_URL  ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($user_photo); ?>"
                        width="28" height="28" class="rounded-circle flex-shrink-0" style="object-fit:cover"
                        alt="Foto perfil"
                        onerror="this.onerror=null;this.src='<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png'">
                    <?php else: ?>
                    <img src="<?php echo APP_URL  ?>/assets/img/avatar/avatar_wasomupfy.png" width="28" height="28"
                        class="rounded-circle flex-shrink-0" style="object-fit:cover" alt="Perfil"
                        onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<i class=\'bi bi-person-circle fs-5 flex-shrink-0\'></i>')">
                    <?php endif; ?>
                    Meu Perfil
                </a>
            </li>
            <!-- Configurações -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('settings', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/settings">
                    <i class="bi bi-gear"></i> Configurações
                </a>
            </li>
            <!-- Notificações -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('notifications', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/notifications">
                    <i class="bi bi-bell"></i> Notificações
                </a>
            </li>
            <!-- Conta e Serviços Disponíveis -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('services', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services">
                    <i class="bi bi-star"></i> Conta e Serviços Disponíveis
                </a>
            </li>
            <!-- Ajuda -->
            <li class="nav-item">
                <a class="nav-link <?php echo is_active_dash('help', $active_page); ?>"
                    href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/help">
                    <i class="bi bi-question-circle"></i> Ajuda
                </a>
            </li>
            <!-- Desconectar-se -->
            <li class="nav-item">
                <a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal"
                    data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i>
                    Desconectar-se</a>
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

<nav class="bottom-nav d-lg-none">
    <ul class="nav justify-content-around">
        <li class="nav-item">
            <a class="nav-link <?php echo is_active_dash('painel', $active_page); ?>"
                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel" aria-label="Ir para Dashboard"><i
                    class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_active_dash('releases', $active_page); ?>"
                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/releases" aria-label="Ir para Lançamentos"><i
                    class="bi bi-disc"></i><span>Lançamentos</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_active_dash('statistics', $active_page); ?>"
                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics" aria-label="Ir para Estatísticas"><i
                    class="bi bi-bar-chart"></i><span>Estatísticas</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_active_dash('finances', $active_page); ?>"
                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview" aria-label="Ir para Finanças"><i
                    class="bi bi-currency-dollar"></i><span>Finanças</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_active_dash('artists', $active_page); ?>"
                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list" aria-label="Ir para Artistas"><i
                    class="bi bi-person"></i><span>Artistas</span></a>
        </li>
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
                        <h5 class="modal-title text-dark mb-0" id="logoutwasomupfyLabel">Terminar sessão
                        </h5>
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
                                <div class="fw-semibold text-dark"><?php echo $session_duration_str; ?>
                                </div>
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
                                <div class="fw-semibold text-dark">
                                    <?php echo htmlspecialchars($sess_location); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 d-flex gap-2 align-items-start">
                            <i class="bi bi-browser-chrome text-muted mt-1 flex-shrink-0"></i>
                            <div>
                                <div class="text-muted">Navegador</div>
                                <div class="fw-semibold text-dark">
                                    <?php echo htmlspecialchars($browser); ?></div>
                            </div>
                        </div>
                        <div class="col-6 d-flex gap-2 align-items-start">
                            <i class="bi bi-hdd-network text-muted mt-1 flex-shrink-0"></i>
                            <div>
                                <div class="text-muted">IP</div>
                                <div class="fw-semibold text-dark">
                                    <?php echo htmlspecialchars($sess_ip); ?></div>
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
                    <span class="text-muted" style="font-size:.8rem">Terás de iniciar sessão novamente
                        para aceder
                        ao painel.</span>
                </p>
            </div>

            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i>Não, continuar
                </button>
                <button class="btn btn-danger flex-fill"
                    onclick="window.location='<?php echo rtrim(APP_URL,  '/'); ?>/logout'">
                    <i class="bi bi-box-arrow-right me-1"></i>Terminar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ════ MODAL — Logout  FIM ════ -->