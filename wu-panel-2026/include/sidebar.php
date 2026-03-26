<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Sidebar Admin (reutilizável)
// Arquivo: admin/include/sidebar.php
//
// Requer platform_admin.php carregado antes.
// Estrutura: links convertidos para APP_URL + ADMIN_PATH,
// permissões e badges adicionados.
// ═══════════════════════════════════════════════════════════════════════════════
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

        <!-- ── Painel de Controle ── -->
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                class="nav-link<?php echo $adm_current_path === '' ? ' active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Painel de Controle</span>
            </a>
        </li>

        <!-- ── Estatísticas e Análises ── -->
        <?php if (hasPermission($admin_id, 'analytics.view')): ?>
        <?php $ana_open = adm_is_active('analytics'); ?>
        <li class="nav-item">
            <a href="#collapseAnalytics" class="nav-link<?php echo $ana_open; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $ana_open ? 'true' : 'false'; ?>" aria-controls="collapseAnalytics">
                <i class="bi bi-graph-up"></i>
                <span>Estatísticas e Análises</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $ana_open ? ' show' : ''; ?>" id="collapseAnalytics">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics"
                    class="nav-link<?php echo adm_is_active('analytics') && !adm_is_active('analytics/artists') && !adm_is_active('analytics/stores') && !adm_is_active('analytics/reports') ? ' active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Visão Geral</span>
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
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão de Admins ── -->
        <?php if (hasPermission($admin_id, 'employees.view')): ?>
        <?php $emp_open = adm_is_active('employees'); ?>
        <li class="nav-item">
            <a href="#collapseAdmins" class="nav-link<?php echo $emp_open; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $emp_open ? 'true' : 'false'; ?>" aria-controls="collapseAdmins">
                <i class="bi bi-person-gear"></i>
                <span>Gestão de Admins</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $emp_open ? ' show' : ''; ?>" id="collapseAdmins">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                    class="nav-link<?php echo adm_is_active('employees') && !adm_is_active('employees/add') && !adm_is_active('employees/edit') && !adm_is_active('employees/view') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Listar Admins</span>
                </a>
                <?php if (hasPermission($admin_id, 'employees.edit')): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/add"
                    class="nav-link<?php echo adm_is_active('employees/add'); ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Adicionar</span>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão de Usuários ── -->
        <?php if (hasPermission($admin_id, 'users.view')): ?>
        <?php $usr_open = adm_is_active('users'); ?>
        <li class="nav-item">
            <a href="#collapseUsers" class="nav-link<?php echo $usr_open; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $usr_open ? 'true' : 'false'; ?>" aria-controls="collapseUsers">
                <i class="bi bi-person-gear"></i>
                <span>Gestão de Usuários</span>
                <?php if ($adm_unavailable_users > 0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo $adm_unavailable_users; ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $usr_open ? ' show' : ''; ?>" id="collapseUsers">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                    class="nav-link<?php echo adm_is_active('users') && !adm_is_active('users/add') && !adm_is_active('users/edit') && !adm_is_active('users/view') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Todos Usuários</span>
                </a>
                <?php if (hasPermission($admin_id, 'users.edit')): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/add"
                    class="nav-link<?php echo adm_is_active('users/add'); ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Adicionar</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/available" style="cursor:none;"
                    class="disabled nav-link<?php echo adm_is_active('users/available'); ?>">
                    <i class="bi bi-person-check"></i>
                    <span>Contas Disponíveis</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/unavailable" style="cursor:none;"
                    class="disabled nav-link<?php echo adm_is_active('users/unavailable'); ?>">
                    <i class="bi bi-person-exclamation"></i>
                    <span>Contas Indisponíveis</span>
                    <?php if ($adm_unavailable_users > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto" style="font-size:.6rem">
                        <?php echo $adm_unavailable_users; ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão de Músicas ── -->
        <?php if (hasPermission($admin_id, 'music.view')): ?>
        <?php $mus_open = adm_is_active('music') || adm_is_active('releases'); ?>
        <li class="nav-item">
            <a href="#collapseSongs" class="nav-link<?php echo $mus_open ? ' active' : ''; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $mus_open ? 'true' : 'false'; ?>" aria-controls="collapseSongs">
                <i class="bi bi-music-note-list"></i>
                <span>Gestão de Músicas</span>
                <?php if ($adm_pending_releases > 0): ?>
                <span class="badge bg-danger ms-1" style="font-size:.6rem">
                    <?php echo $adm_pending_releases; ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $mus_open ? ' show' : ''; ?>" id="collapseSongs">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music/revise"
                    class="nav-link<?php echo adm_is_active('music/revise'); ?>">
                    <i class="bi bi-eye"></i>
                    <span>Revisar Envios</span>
                    <?php if ($adm_pending_releases > 0): ?>
                    <span class="badge bg-danger ms-auto" style="font-size:.6rem">
                        <?php echo $adm_pending_releases; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <?php if (hasPermission($admin_id, 'music.approve')): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music/approve"
                    class="nav-link<?php echo adm_is_active('music/approve'); ?>">
                    <i class="bi bi-check-circle"></i>
                    <span>Aprovar</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music/reject"
                    class="nav-link<?php echo adm_is_active('music/reject'); ?>">
                    <i class="bi bi-x-circle"></i>
                    <span>Rejeitar</span>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Artistas ── -->
        <?php if (hasPermission($admin_id, 'artists.view')): ?>
        <li class="nav-item">
            <a href="#collapseArtist" class="nav-link<?php echo adm_is_active('artist') ? ' active' : ''; ?>"
                data-bs-toggle="collapse">
                <i class="bi bi-mic"></i>
                <span>Artistas</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('artist') ? ' show' : ''; ?>" id="collapseArtist">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/"
                    class="nav-link<?php echo adm_is_active('artist') && !adm_is_active('artist/add') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i> Todos Artistas
                </a>
                <?php if (hasPermission($admin_id, 'artists.edit')): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/add"
                    class="nav-link<?php echo adm_is_active('artist/add'); ?>">
                    <i class="bi bi-person-plus"></i> Adicionar
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Colaboradores ── -->
        <?php if (hasPermission($admin_id, 'collaborators.view')): ?>
        <li class="nav-item">
            <a href="#collapseCollab" class="nav-link<?php echo adm_is_active('collab') ? ' active' : ''; ?>"
                data-bs-toggle="collapse">
                <i class="bi bi-person-plus"></i>
                <span>Colaboradores</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('collab') ? ' show' : ''; ?>" id="collapseCollab">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/"
                    class="nav-link<?php echo adm_is_active('collab') && !adm_is_active('collab/add') ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i> Todos Colaboradores
                </a>
                <?php if (hasPermission($admin_id, 'collaborators.edit')): ?>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/add"
                    class="nav-link<?php echo adm_is_active('collab/add'); ?>">
                    <i class="bi bi-person-plus"></i> Adicionar
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Distribuição ── -->
        <?php if (hasPermission($admin_id, 'music.view')): ?>
        <?php $dist_open = adm_is_active('distribution') || adm_is_active('releases'); ?>
        <li class="nav-item">
            <a href="#collapseDistribution" class="nav-link<?php echo $dist_open ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $dist_open ? 'true' : 'false'; ?>"
                aria-controls="collapseDistribution">
                <i class="bi bi-globe"></i>
                <span>Distribuição</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $dist_open ? ' show' : ''; ?>" id="collapseDistribution">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases"
                    class="nav-link<?php echo adm_is_active('releases'); ?>">
                    <i class="bi bi-rocket-takeoff"></i>
                    <span>Lançamentos</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/distribution/stores"
                    class="nav-link<?php echo adm_is_active('distribution/stores'); ?>">
                    <i class="bi bi-shop"></i>
                    <span>Lojas Digitais</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/distribution/schedule"
                    class="nav-link<?php echo adm_is_active('distribution/schedule'); ?>">
                    <i class="bi bi-calendar-event"></i>
                    <span>Agendar Lançamento</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão Geral (royalties) — só super_admin e admin ── -->
        <?php if (in_array($admin_role, ['super_admin', 'admin'])): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/gestion"
                class="nav-link<?php echo adm_is_active('manager/gestion'); ?>">
                <i class="bi bi-star"></i>
                <span>Gestão Geral</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- ── Pagamentos ── -->
        <?php if (hasPermission($admin_id, 'finances.view')): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/"
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
        <?php endif; ?>

        <!-- ── Finanças e Rendimentos ── -->
        <?php if (hasPermission($admin_id, 'finances.view')): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances"
                class="nav-link<?php echo adm_is_active('finances'); ?>">
                <i class="bi bi-currency-dollar"></i>
                <span>Finanças e Rendimentos</span>
            </a>
        </li>
        <li class="nav-item">
            <?php
                // Contar contas pendentes para badge informativo
                $pending_accounts_count = (int)$db->query("SELECT COUNT(*) FROM _account WHERE status_account = 'pending'")->fetchColumn();
                ?>
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
                class="nav-link<?php echo adm_is_active('accounts'); ?>">
                <i class="bi bi-bank"></i>
                <span>Contas Bancárias</span>
                <?php if ($pending_accounts_count > 0): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">
                    <?php echo $pending_accounts_count; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <!-- ── Unificação e V. Youtube ── -->
        <?php if (hasPermission($admin_id, 'music.view')): ?>
        <?php $int_open = adm_is_active('integration'); ?>
        <li class="nav-item">
            <a href="#collapseIntegration" class="nav-link<?php echo $int_open ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $int_open ? 'true' : 'false'; ?>"
                aria-controls="collapseIntegration">
                <i class="bi bi-youtube"></i>
                <span>Unificação e V. Youtube</span>
                <?php if ($adm_pending_channels > 0): ?>
                <span class="badge bg-info text-dark ms-1" style="font-size:.6rem">
                    <?php echo $adm_pending_channels; ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $int_open ? ' show' : ''; ?>" id="collapseIntegration">
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
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/monetization"
                    class="nav-link<?php echo adm_is_active('monetization'); ?>">
                    <i class="bi bi-youtube"></i>
                    <span>Gerenciamento de Conteúdo Monetizado</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Suporte ── -->
        <?php if (hasPermission($admin_id, 'support.view')): ?>
        <?php $sup_open = adm_is_active('messages'); ?>
        <li class="nav-item">
            <a href="#collapseSupport" class="nav-link<?php echo $sup_open ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo $sup_open ? 'true' : 'false'; ?>"
                aria-controls="collapseSupport">
                <i class="bi bi-headset"></i>
                <span>Suporte</span>
                <?php if ($adm_open_tickets > 0): ?>
                <span class="badge bg-danger ms-1" style="font-size:.6rem">
                    <?php echo $adm_open_tickets; ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $sup_open ? ' show' : ''; ?>" id="collapseSupport">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/messages/inbox"
                    class="nav-link<?php echo adm_is_active('messages/inbox'); ?>">
                    <i class="bi bi-envelope"></i>
                    <span>Caixa de entrada</span>
                    <?php if ($adm_open_tickets > 0): ?>
                    <span class="badge bg-danger ms-auto" style="font-size:.6rem">
                        <?php echo $adm_open_tickets; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/messages/compose"
                    class="nav-link<?php echo adm_is_active('messages/compose'); ?>">
                    <i class="bi bi-pencil"></i>
                    <span>Enviar mensagens</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Ajuda ── -->
        <?php $hlp_open = adm_is_active('help'); ?>
        <li class="nav-item">
            <a href="#collapseHelp" class="nav-link<?php echo $hlp_open ? ' active' : ''; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo $hlp_open ? 'true' : 'false'; ?>" aria-controls="collapseHelp">
                <i class="bi bi-question-circle"></i>
                <span>Ajuda</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo $hlp_open ? ' show' : ''; ?>" id="collapseHelp">
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/help/faqs"
                    class="nav-link<?php echo adm_is_active('help/faqs'); ?>">
                    <i class="bi bi-messenger"></i>
                    <span>FAQs</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/help/tutorials"
                    class="nav-link<?php echo adm_is_active('help/tutorials'); ?>">
                    <i class="bi bi-book"></i>
                    <span>Tutoriais</span>
                </a>
                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/help/contact"
                    class="nav-link<?php echo adm_is_active('help/contact'); ?>">
                    <i class="bi bi-telephone"></i>
                    <span>Contacto com suporte</span>
                </a>
            </div>
        </li>

        <!-- ── Configurações ── -->
        <?php if (hasPermission($admin_id, 'settings.view')): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings"
                class="nav-link<?php echo adm_is_active('settings'); ?>">
                <i class="bi bi-sliders"></i>
                <span>Configurações</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- ── Logout + Visitar Site ── -->
        <li class="nav-item mt-4">
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
        <li class="nav-item mt-2">
            <a href="<?php echo APP_URL; ?>" target="_blank" class="nav-link">
                <i class="bi bi-box-arrow-in-up-right"></i>
                <span>Visitar Site</span>
            </a>
        </li>

    </ul>
</div>