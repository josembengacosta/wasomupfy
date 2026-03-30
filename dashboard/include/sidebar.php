<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Sidebar Dashboard (reutilizável)
// Arquivo: dashboard/include/sidebar.php
//
// Requer platform.php carregado antes.
// Estrutura: mesma do original, links convertidos
// para APP_URL, permissões e badges adicionados.
// ══════════════════════════════════════════════
?>

<!-- Tela de Carregamento -->
<div class="loading-screen" id="loadingScreen">
    <img src="<?php echo APP_URL  ?>/assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90"
        height="90" alt="Loading-wasomupfy">
    <div class="spinner"></div>
</div>

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
            <!-- SVG Logo Wasom Upfy -->
            <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="120" height="32" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
            <!-- <img src="<?php echo APP_URL  ?>/assets/img/brand/wasomupfy_brand.png" width="70"  class="img-fluid" alt=""> -->
            <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: uppercase;
              font-family: Arial, sans-serif;
            "><?php echo APP_NAME; ?></span>
        </a>

        <!-- Desktop Menu -->
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel"><i
                            class="bi bi-speedometer2"></i>
                        Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/releases"><i
                            class="bi bi-disc"></i>
                        Lançamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics"><i
                            class="bi bi-bar-chart"></i> Estatísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview"><i
                            class="bi bi-currency-dollar"></i> Finanças</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list"><i
                            class="bi bi-person"></i>
                        Artistas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/youtube/ucy"><i
                            class="bi bi-youtube"></i> Unificação de canal
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
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
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
                    <a class="dropdown-item d-flex align-items-center gap-2"
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
                    <a class="dropdown-item"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/account/manage-account"><i
                            class="bi bi-tools me-2"></i> Gestão
                        de
                        Conta</a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/settings"><i
                            class="bi bi-gear me-2"></i> Configurações</a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/notifications"><i
                            class="bi bi-bell me-2"></i>
                        Notificações</a>
                </li>
                <li>
                    <a class="dropdown-item"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services"><i
                            class="bi bi-star me-2"></i>
                        Conta e
                        serviços disponíveis</a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <!-- <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/about"><i
                            class="bi bi-info-circle me-2"></i> Sobre</a>
                </li> -->
                <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support"><i
                            class="bi bi-headset me-2"></i> Enviar pedido de
                        suporte</a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/faq"><i
                            class="bi bi-chat-left-text me-2"></i> Perguntas
                        frequentes</a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/help"><i
                            class="bi bi-question-circle me-2"></i> Ajuda</a>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li>
                    <a class="dropdown-item"
                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/politicies/privacy"><i
                            class="bi bi-shield-check"></i><span>Privacidade</span></a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/politicies/terms"><i
                            class="bi bi-file-text"></i><span>Termos</span></a>
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
                    <span class="dropdown-item-text">Versão -
                        <?php echo APP_VERSION ?></span>
                </li>
            </ul>
        </div>
    </div>
</nav>


<!-- Offcanvas Menu para Mobile -->
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
              text-transform: uppercase;
              font-family: Arial, sans-serif;
            ">WASOM UPFY
            </span>
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel"><i
                        class="bi bi-speedometer2"></i>
                    Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/releases"><i
                        class="bi bi-disc"></i>
                    Lançamentos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics"><i
                        class="bi bi-bar-chart"></i>
                    Estatísticas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview"><i
                        class="bi bi-currency-dollar"></i> Finanças</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list"><i
                        class="bi bi-person"></i>
                    Artistas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/youtube/ucy"><i
                        class="bi bi-youtube"></i>
                    Unificação de Canal
                    YouTube</a>
            </li>
            <li class="nav-item">
                <a class="nav-link gap-2" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile">
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
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/settings"><i
                        class="bi bi-gear"></i>
                    Configurações</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/notifications"><i
                        class="bi bi-bell"></i>
                    Notificações</a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/about"><i
                        class="bi bi-info-circle"></i>
                    Sobre</a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services"><i
                        class="bi bi-star"></i>
                    Conta e Serviços
                    Disponíveis</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/help"><i
                        class="bi bi-question-circle"></i> Ajuda</a>
            </li>
            <li class="nav-item">
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

<nav class="bottom-nav d-lg-none">
    <ul class="nav justify-content-around">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel"
                aria-label="Ir para Dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/releases"
                aria-label="Ir para Lançamentos"><i class="bi bi-disc"></i><span>Lançamentos</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics"
                aria-label="Ir para Estatísticas"><i class="bi bi-bar-chart"></i><span>Estatísticas</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview"
                aria-label="Ir para Finanças"><i class="bi bi-currency-dollar"></i><span>Finanças</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list"
                aria-label="Ir para Artistas"><i class="bi bi-person"></i><span>Artistas</span></a>
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
                <button class="btn btn-danger flex-fill"
                    onclick="window.location='<?php echo rtrim(APP_URL,  '/'); ?>/logout'">
                    <i class="bi bi-box-arrow-right me-1"></i>Terminar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ════ MODAL — Logout  FIM ════ -->

<script>
// ── Conexão offline ─────────────────────────────────
function checkConnection() {
    if (!navigator.onLine) {
        var toast = bootstrap.Toast.getOrCreateInstance(document.getElementById('connectionToast'));
        toast.show();
    }
}
checkConnection();
window.addEventListener('offline', checkConnection);
window.addEventListener('online', function() {
    var toastEl = document.getElementById('connectionToast');
    var toast = bootstrap.Toast.getInstance(toastEl);
    if (toast) toast.hide();
});
</script>