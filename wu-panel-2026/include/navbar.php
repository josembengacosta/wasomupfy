<?php
// Navbar admin reutilizavel.
// Requer platform_admin.php carregado antes.

$canMusicApprove = hasPermission($admin_id, 'music.approve');
$canFinancesView = hasPermission($admin_id, 'finances.view');
$canSupportView  = hasPermission($admin_id, 'support.view');
$canSettingsView = hasPermission($admin_id, 'settings.view');

$faqUrl        = APP_URL . '/page/support/faq';
$releasesUrl   = APP_URL . '/' . ADMIN_PATH . '/releases';
$paymentsUrl   = APP_URL . '/' . ADMIN_PATH . '/payments';
$supportUrl    = APP_URL . '/' . ADMIN_PATH . '/messages/inbox';
$visibleNotifs = ($canMusicApprove ? $adm_pending_releases : 0)
    + ($canFinancesView ? $adm_pending_payments : 0)
    + ($canSupportView ? $adm_open_tickets : 0);
?>

<nav class="navbar navbar-expand-lg">
    <button class="navbar-toggler" type="button" id="sidebarToggle" aria-label="Abrir/Fechar Menu">
        <i class="bi bi-list text-white"></i>
    </button>
    <div class="ms-auto d-flex align-items-center">
        <button class="btn btn-outline-light btn-sm me-2" onclick="toggleDarkMode()" aria-label="Modo Escuro">
            <i class="bi bi-moon"></i>
        </button>

        <div class="dropdown me-2 position-relative">
            <button class="btn btn-outline-light btn-sm position-relative" type="button" data-bs-toggle="dropdown"
                aria-label="Notificações">
                <i class="bi bi-bell"></i>
                <?php if ($visibleNotifs > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $visibleNotifs; ?>
                </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-start p-0" style="min-width:260px">
                <li class="dropdown-header bg-dark text-white p-2 d-flex justify-content-between align-items-center">
                    <span>Notificações</span>
                    <span class="badge bg-danger"><?php echo $visibleNotifs; ?></span>
                </li>
                <?php if ($canMusicApprove && $adm_pending_releases > 0): ?>
                <li>
                    <a class="dropdown-item p-2" href="<?php echo $releasesUrl; ?>">
                        <i class="bi bi-hourglass-split text-warning me-2"></i>
                        <?php echo $adm_pending_releases; ?> lançamento(s) pendente(s)
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canFinancesView && $adm_pending_payments > 0): ?>
                <li>
                    <a class="dropdown-item p-2" href="<?php echo $paymentsUrl; ?>">
                        <i class="bi bi-credit-card text-info me-2"></i>
                        <?php echo $adm_pending_payments; ?> pagamento(s) por aprovar
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canSupportView && $adm_open_tickets > 0): ?>
                <li>
                    <a class="dropdown-item p-2" href="<?php echo $supportUrl; ?>">
                        <i class="bi bi-headset text-danger me-2"></i>
                        <?php echo $adm_open_tickets; ?> ticket(s) em aberto
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($visibleNotifs === 0): ?>
                <li>
                    <span class="dropdown-item p-2 text-muted">Sem notificações pendentes</span>
                </li>
                <?php endif; ?>
                <li class="dropdown-footer text-center p-2">
                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-primary">Ir ao painel</a>
                </li>
            </ul>
        </div>

        <?php if ($canSupportView): ?>
        <div class="dropdown me-2 position-relative">
            <button class="btn btn-outline-light btn-sm position-relative" type="button" data-bs-toggle="dropdown"
                aria-label="Mensagens">
                <i class="bi bi-envelope"></i>
                <?php if ($adm_open_tickets > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $adm_open_tickets; ?>
                </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-start p-0" style="min-width:250px">
                <li class="dropdown-header bg-dark text-white p-2">Mensagens</li>
                <li>
                    <a class="dropdown-item p-2" href="<?php echo $supportUrl; ?>">
                        <i class="bi bi-ticket me-2"></i>Ver tickets de suporte
                    </a>
                </li>
                <li class="dropdown-footer text-center p-2">
                    <a href="<?php echo $supportUrl; ?>" class="text-primary">Ir para suporte</a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center" type="button"
                data-bs-toggle="dropdown" aria-label="Menu do Utilizador">
                <?php if ($admin_photo): ?>
                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($admin_photo); ?>"
                    alt="" class="rounded-circle me-1" style="height:24px;width:24px;object-fit:cover" />
                <?php else: ?>
                <span class="rounded-circle me-1 d-inline-flex align-items-center justify-content-center"
                    style="height:24px;width:24px;background:#FF0089;font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0">
                    <?php echo adm_initials($admin_name, explode(' ', $admin_fullname, 2)[1] ?? ''); ?>
                </span>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($admin_name); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item<?php echo adm_is_active('profile'); ?>"
                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile">
                        <i class="bi bi-person me-2"></i>Perfil
                    </a>
                </li>
                <?php if ($canSettingsView): ?>
                <li>
                    <a class="dropdown-item<?php echo adm_is_active('settings'); ?>"
                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings">
                        <i class="bi bi-sliders me-2"></i>Configurações
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item" href="<?php echo $faqUrl; ?>" target="_blank" rel="noopener">
                        <i class="bi bi-question-circle me-2"></i>Ajuda
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="connection-status" id="connectionStatus"></div>
<div class="status-notification" id="statusNotification"></div>

<div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content modal-bottom">
            <div class="modal-header">
                <h1 class="modal-title fs-5 text-dark" id="logoutwasomupfyLabel">
                    <i class="bi bi-box-arrow-right me-2 text-danger"></i>Terminar sessão
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:#f8f7fc;border-radius:12px;margin-bottom:14px">
                    <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#FF0089,#ff6bb5);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;color:#fff;flex-shrink:0;overflow:hidden">
                        <?php if ($admin_photo): ?>
                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($admin_photo); ?>"
                            alt="" style="width:100%;height:100%;object-fit:cover" />
                        <?php else: ?>
                        <?php echo adm_initials($admin_name, explode(' ', $admin_fullname, 2)[1] ?? ''); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-size:.95rem;font-weight:700;color:#111">
                            <?php echo htmlspecialchars($admin_fullname); ?></div>
                        <div style="font-size:.78rem;color:#888">
                            <?php echo getRoleLabel($admin_role); ?> · <?php echo htmlspecialchars($admin_email); ?>
                        </div>
                    </div>
                </div>

                <div
                    style="background:rgba(0,0,0,.04);border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:16px;margin-bottom:16px">
                    <?php foreach (
                        [
                            ['bi-geo-alt', 'Endereço IP', htmlspecialchars($adm_client_ip)],
                            ['bi-browser-chrome', 'Navegador', htmlspecialchars($adm_browser)],
                            ['bi-laptop', 'Sistema', htmlspecialchars($adm_os)],
                            ['bi-clock-history', 'Sessão', $adm_session_mins > 0 ? $adm_session_mins . ' min' : 'Recém iniciada'],
                        ] as [$icon, $label, $value]
                    ): ?>
                    <div
                        style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.84rem;color:#555;border-bottom:1px solid rgba(0,0,0,.06)">
                        <i class="bi <?php echo $icon; ?>" style="color:#FF0089;width:16px;flex-shrink:0"></i>
                        <span><?php echo $label; ?></span>
                        <strong
                            style="color:#222;margin-left:auto;text-align:right;font-size:.82rem"><?php echo $value; ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>

                <p class="text-center text-dark mb-0" style="font-size:.88rem">
                    Tens a certeza de que desejas terminar a sessão?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Cancelar
                </button>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/logout" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Sim, terminar sessão
                </a>
            </div>
        </div>
    </div>
</div>
