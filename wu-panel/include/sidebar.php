<?php
// Sidebar admin reutilizavel.
// Requer platform_admin.php carregado antes.

$canAnalyticsView     = hasPermission($admin_id, 'analytics.view');
$canEmployeesView     = hasPermission($admin_id, 'employees.view');
$canEmployeesEdit     = hasPermission($admin_id, 'employees.edit');
$canUsersView         = hasPermission($admin_id, 'users.view');
$canUsersEdit         = hasPermission($admin_id, 'users.edit');
$canMusicView         = hasPermission($admin_id, 'music.view');
$canFinancesView      = hasPermission($admin_id, 'finances.view');
$canArtistsView       = hasPermission($admin_id, 'artists.view');
$canArtistsEdit       = hasPermission($admin_id, 'artists.edit');
$canCollaboratorsView = hasPermission($admin_id, 'collaborators.view');
$canCollaboratorsEdit = hasPermission($admin_id, 'collaborators.edit');
$canSupportView       = hasPermission($admin_id, 'support.view');
$canSettingsView      = hasPermission($admin_id, 'settings.view');

$faqUrl              = APP_URL . '/page/support/faq';
$tutorialUrl         = APP_URL . '/page/support/tutorial';
$adminFaqUrl         = APP_URL . '/' . ADMIN_PATH . '/faq'; // Novo: Gestão de FAQs (Admin)
$pendingAccountsCount = $canFinancesView
    ? (int)$db->query("SELECT COUNT(*) FROM _account WHERE status_account = 'pending'")->fetchColumn()
    : 0;
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" alt="Logo Wasom Upfy"
                class="rounded-circle me-2" style="height:40px" />
            <span class="brand-text"><?php echo APP_NAME; ?></span>
        </div>
        <i class="bi bi-chevron-left toggle-icon" id="sidebarCollapse" title="Colapsar/Expandir Menu"
            aria-label="Colapsar/Expandir Menu"></i>
    </div>

    <ul class="nav flex-column mt-3">
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                class="nav-link<?php echo $adm_current_path === '' ? ' active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Painel de Controle</span>
            </a>
        </li>

        <?php if ($canAnalyticsView): ?>
        <?php $anaOpen = adm_is_active('analytics'); ?>
        <li class="nav-item">
            <a href="#collapseAnalytics" class="nav-link<?php echo $anaOpen; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $anaOpen ? 'true' : 'false'; ?>" aria-controls="collapseAnalytics">
                <i class="bi bi-graph-up"></i>
                <span>Estatísticas e Análises</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $anaOpen ? ' show' : ''; ?>" id="collapseAnalytics">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics"
                    class="nav-link<?php echo adm_is_active('analytics') && !adm_is_active('analytics/artists') && !adm_is_active('analytics/stores') && !adm_is_active('analytics/reports') && !adm_is_active('analytics/visitors') && !adm_is_active('analytics/online-users') ? ' active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Visão Geral</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/visitors"
                    class="nav-link<?php echo adm_is_active('analytics/visitors'); ?>">
                    <i class="bi bi-people"></i>
                    <span>Todos os Visitantes</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/online-users"
                    class="nav-link<?php echo adm_is_active('analytics/online-users'); ?>">
                    <i class="bi bi-broadcast"></i>
                    <span>Usuários Online</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/artists"
                    class="nav-link<?php echo adm_is_active('analytics/artists'); ?>">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Desempenho por Artista</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/stores"
                    class="nav-link<?php echo adm_is_active('analytics/stores'); ?>">
                    <i class="bi bi-shop"></i>
                    <span>Desempenho por Loja Digital</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/reports"
                    class="nav-link<?php echo adm_is_active('analytics/reports'); ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Relatórios Personalizados</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/import-streams"
                    class="nav-link<?php echo adm_is_active('analytics/import-streams') ? ' active' : ''; ?>">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Importar Streams</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($canEmployeesView): ?>
        <?php $empOpen = adm_is_active('employees'); ?>
        <li class="nav-item">
            <a href="#collapseAdmins" class="nav-link<?php echo $empOpen; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $empOpen ? 'true' : 'false'; ?>" aria-controls="collapseAdmins">
                <i class="bi bi-person-gear"></i>
                <span>Gestão de Admins</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $empOpen ? ' show' : ''; ?>" id="collapseAdmins">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                    class="nav-link<?php echo adm_is_active('employees') && !adm_is_active('employees/add') && !adm_is_active('employees/edit') && !adm_is_active('employees/view') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Listar Admins</span>
                </a>
                <?php if ($canEmployeesEdit): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/add"
                    class="nav-link<?php echo adm_is_active('employees/add'); ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Adicionar</span>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($canUsersView): ?>
        <?php $usrOpen = adm_is_active('users'); ?>
        <li class="nav-item">
            <a href="#collapseUsers" class="nav-link<?php echo $usrOpen; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $usrOpen ? 'true' : 'false'; ?>" aria-controls="collapseUsers">
                <i class="bi bi-person-gear"></i>
                <span>Gestão de Usuários</span>
                <?php if ($adm_unavailable_users > 0 || $adm_users_without_active_plan > 0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo ($adm_unavailable_users + $adm_users_without_active_plan); ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $usrOpen ? ' show' : ''; ?>" id="collapseUsers">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                    class="nav-link<?php echo adm_is_active('users') && !adm_is_active('users/add') && !adm_is_active('users/edit') && !adm_is_active('users/view') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Todos Usuários</span>
                </a>
                <?php if ($canUsersEdit): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/add"
                    class="nav-link<?php echo adm_is_active('users/add'); ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Adicionar</span>
                </a>
                <?php endif; ?>
                <a href="#" onclick="return false;" aria-disabled="true" class="nav-link disabled"
                    style="cursor:default;opacity:.85">
                    <i class="bi bi-person-check"></i>
                    <span>Contas Disponíveis</span>
                </a>
                <a href="#" onclick="return false;" aria-disabled="true" class="nav-link disabled"
                    style="cursor:default;opacity:.85">
                    <i class="bi bi-person-exclamation"></i>
                    <span>Contas Indisponíveis</span>
                    <?php if ($adm_unavailable_users > 0): ?>
                    <span class="badge bg-danger text-dark ms-auto" style="font-size:.6rem">
                        <?php echo $adm_unavailable_users; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <a href="#" onclick="return false;" aria-disabled="true" class="nav-link disabled"
                    style="cursor:default;opacity:.85">
                    <i class="bi bi-person-slash"></i>
                    <span>Sem Planos Ativos</span>
                    <?php if ($adm_users_without_active_plan > 0): ?>
                    <span class="badge bg-info text-dark ms-auto" style="font-size:.6rem">
                        <?php echo $adm_users_without_active_plan; ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($canArtistsView): ?>
        <?php $artistOpen = adm_is_active('artist'); ?>
        <li class="nav-item">
            <a href="#collapseArtist" class="nav-link<?php echo $artistOpen ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $artistOpen ? 'true' : 'false'; ?>"
                aria-controls="collapseArtist">
                <i class="bi bi-mic"></i>
                <span>Artistas</span>
                <?php if ($adm_pending_artists >  0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo ($adm_pending_artists); ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse<?php echo $artistOpen ? ' show' : ''; ?>" id="collapseArtist">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/"
                    class="nav-link<?php echo adm_is_active('artist') && !adm_is_active('artist/add') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Todos Artistas</span>
                </a>
                <?php if ($canArtistsEdit): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/add"
                    class="nav-link<?php echo adm_is_active('artist/add'); ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Adicionar</span>
                </a>
                <?php endif; ?>
                <a href="#" onclick="return false;" aria-disabled="true" class="nav-link disabled"
                    style="cursor:default;opacity:.85">
                    <i class="bi bi-hourglass-split"></i>
                    <span>Artistas Pendentes</span>
                    <span class="badge bg-warning text-dark ms-auto" style="font-size:.6rem">
                        <?php echo $adm_pending_artists; ?>
                    </span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($canCollaboratorsView): ?>
        <?php $collabOpen = adm_is_active('collab'); ?>
        <li class="nav-item">
            <a href="#collapseCollab" class="nav-link<?php echo $collabOpen ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $collabOpen ? 'true' : 'false'; ?>"
                aria-controls="collapseCollab">
                <i class="bi bi-person-plus"></i>
                <span>Colaboradores</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse<?php echo $collabOpen ? ' show' : ''; ?>" id="collapseCollab">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/"
                    class="nav-link<?php echo adm_is_active('collab') && !adm_is_active('collab/add') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Todos Colaboradores</span>
                </a>
                <?php if ($canCollaboratorsEdit): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/add"
                    class="nav-link<?php echo adm_is_active('collab/add'); ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Adicionar</span>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($canMusicView): ?>
        <?php $distOpen = adm_is_active('distribution') || adm_is_active('releases'); ?>
        <li class="nav-item">
            <a href="#collapseDistribution" class="nav-link<?php echo $distOpen ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $distOpen ? 'true' : 'false'; ?>"
                aria-controls="collapseDistribution">
                <i class="bi bi-globe"></i>
                <span>Distribuição</span>
                <?php if ($adm_pending_releases >  0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo ($adm_pending_releases); ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $distOpen ? ' show' : ''; ?>" id="collapseDistribution">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases"
                    class="nav-link<?php echo adm_is_active('releases') ? ' active' : ''; ?>">
                    <i class="bi bi-rocket-takeoff"></i>
                    <span>Lançamentos</span>
                </a>
                <a href="#" onclick="return false;" aria-disabled="true" class="nav-link disabled"
                    style="cursor:default;opacity:.85">
                    <i class="bi bi-hourglass-split"></i>
                    <span>Lançamentos Pendentes</span>
                    <span class="badge bg-danger ms-auto" style="font-size:.6rem">
                        <?php echo $adm_pending_releases; ?>
                    </span>
                </a>
                <a href="#" onclick="return false;" aria-disabled="true" class="nav-link disabled"
                    style="cursor:default;opacity:.85">
                    <i class="bi bi-trash"></i>
                    <span>Pedido de Eliminação</span>
                    <span class="badge bg-danger ms-auto" style="font-size:.6rem">
                        <?php echo $adm_delete_requests; ?>
                    </span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <?php if (in_array($admin_role, ['super_admin', 'admin'], true)): ?>
        <li class="nav-item">
            <a target="_blank" href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/login"
                class="nav-link<?php echo adm_is_active('manager/login'); ?>">
                <i class="bi bi-star"></i>
                <span>Gestão Geral</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($canFinancesView): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                class="nav-link<?php echo adm_is_active('payments') ? ' active' : ''; ?>">
                <i class="bi bi-wallet2"></i>
                <span>Pagamentos</span>
                <?php if ($adm_pending_payments > 0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo $adm_pending_payments; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances"
                class="nav-link<?php echo adm_is_active('finances'); ?>">
                <i class="bi bi-currency-dollar"></i>
                <span>Finanças e Rendimentos</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
                class="nav-link<?php echo adm_is_active('accounts'); ?>">
                <i class="bi bi-bank"></i>
                <span>Contas Bancárias</span>
                <?php if ($pendingAccountsCount > 0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo $pendingAccountsCount; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <!-- NOVO: Gestão de Planos -->
        <?php if (hasPermission($admin_id, 'finances.edit')): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/plans"
                class="nav-link<?php echo adm_is_active('plans') ? ' active' : ''; ?>">
                <i class="bi bi-tags"></i>
                <span>Planos</span>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($canMusicView): ?>
        <?php $integrationOpen = adm_is_active('integration'); ?>
        <li class="nav-item">
            <a href="#collapseIntegration" class="nav-link<?php echo $integrationOpen ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $integrationOpen ? 'true' : 'false'; ?>"
                aria-controls="collapseIntegration">
                <i class="bi bi-youtube"></i>
                <span>Unifi.V Youtube</span>
                <?php if ($adm_pending_channels > 0): ?>
                <span class="badge bg-info text-dark ms-1" style="font-size:.6rem">
                    <?php echo $adm_pending_channels; ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $integrationOpen ? ' show' : ''; ?>" id="collapseIntegration">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/integration/verify"
                    class="nav-link<?php echo adm_is_active('integration/verify'); ?>">
                    <i class="bi bi-gear"></i>
                    <span>Configurar Integração</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/integration/verify-channel"
                    class="nav-link<?php echo adm_is_active('integration/verify-channel'); ?>">
                    <i class="bi bi-check2-all"></i>
                    <span>Verificar Canais</span>
                    <?php if ($adm_pending_channels > 0): ?>
                    <span class="badge bg-info text-dark ms-auto" style="font-size:.6rem">
                        <?php echo $adm_pending_channels; ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($canSupportView): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/messages/inbox"
                class="nav-link<?php echo adm_is_active('messages') ? ' active' : ''; ?>">
                <i class="bi bi-headset"></i>
                <span>Suporte</span>
                <?php if ($adm_open_tickets > 0): ?>
                <span class="badge bg-danger ms-1" style="font-size:.6rem">
                    <?php echo $adm_open_tickets; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <?php $helpOpen = adm_is_active('help'); ?>
        <li class="nav-item">
            <a href="#collapseHelp" class="nav-link<?php echo $helpOpen ? ' active' : ''; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $helpOpen ? 'true' : 'false'; ?>" aria-controls="collapseHelp">
                <i class="bi bi-question-circle"></i>
                <span>Ajuda</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $helpOpen ? ' show' : ''; ?>" id="collapseHelp">
                <a href="<?php echo $faqUrl; ?>" class="nav-link" target="_blank" rel="noopener">
                    <i class="bi bi-messenger"></i>
                    <span>FAQs (Público)</span>
                </a>
                <?php if (hasPermission($admin_id, 'content.edit')): ?>
                <a href="<?php echo $adminFaqUrl; ?>"
                    class="nav-link<?php echo adm_is_active('faq') ? ' active' : ''; ?>">
                    <i class="bi bi-database-gear"></i>
                    <span>Gerenciar FAQs</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo $tutorialUrl; ?>" class="nav-link" target="_blank" rel="noopener">
                    <i class="bi bi-book"></i>
                    <span>Tutoriais</span>
                </a>
            </div>
        </li>

        <?php if ($canSettingsView): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings"
                class="nav-link<?php echo adm_is_active('settings'); ?>">
                <i class="bi bi-sliders"></i>
                <span>Configurações</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item mt-4">
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
        <li class="nav-item mt-2">
            <a href="<?php echo APP_URL; ?>" target="_blank" class="nav-link" rel="noopener">
                <i class="bi bi-box-arrow-in-up-right"></i>
                <span>Visitar Site</span>
            </a>
        </li>
    </ul>
</div>