<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Sidebar Admin (reutilizável)
// Arquivo: admin/include/sidebar.php
//
// Requer platform_admin.php carregado antes.
// Variáveis usadas: $admin_id, $admin_name,
// $admin_fullname, $admin_photo, $admin_role,
// $adm_pending_releases, $adm_pending_payments,
// $adm_open_tickets
// ══════════════════════════════════════════════
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" alt="Logo Wasom Upfy"
                class="rounded-circle me-2" style="height:40px" />
            <span class="brand-text">Wasom Upfy</span>
        </div>
        <i class="bi bi-chevron-left toggle-icon" id="sidebarCollapse" title="Colapsar/Expandir Menu"
            aria-label="Colapsar/Expandir Menu"></i>
    </div>

    <ul class="nav flex-column mt-3">

        <!-- Painel de Controle — sempre visível -->
        <li class="nav-item">
            <a href="<?php echo APP_URL; ?>/admin"
                class="nav-link<?php echo adm_is_active('') && $adm_current_path === '' ? ' active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Painel de Controle</span>
            </a>
        </li>

        <!-- ── Estatísticas e Análises ── -->
        <?php if (hasPermission($admin_id, 'analytics.view')): ?>
        <li class="nav-item">
            <a href="#collapseAnalytics"
                class="nav-link<?php echo adm_is_active('analytics') || adm_is_active('visitors') || adm_is_active('reports') ? ' active' : ''; ?>"
                data-bs-toggle="collapse"
                aria-expanded="<?php echo (adm_is_active('analytics') || adm_is_active('visitors') || adm_is_active('reports')) ? 'true' : 'false'; ?>"
                aria-controls="collapseAnalytics">
                <i class="bi bi-graph-up"></i>
                <span>Estatísticas e Análises</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo (adm_is_active('analytics') || adm_is_active('visitors') || adm_is_active('reports')) ? ' show' : ''; ?>"
                id="collapseAnalytics">
                <a href="<?php echo APP_URL; ?>/admin/analytics"
                    class="nav-link<?php echo adm_is_active('analytics'); ?>">
                    <i class="bi bi-bar-chart-line"></i><span>Visão Geral</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/visitors"
                    class="nav-link<?php echo adm_is_active('visitors'); ?>">
                    <i class="bi bi-eye"></i><span>Visitantes</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/reports" class="nav-link<?php echo adm_is_active('reports'); ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i><span>Relatórios</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão de Admins ── -->
        <?php if (hasPermission($admin_id, 'employees.view')): ?>
        <li class="nav-item">
            <a href="#collapseAdmins" class="nav-link<?php echo adm_is_active('employees') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('employees') ? 'true' : 'false'; ?>"
                aria-controls="collapseAdmins">
                <i class="bi bi-person-gear"></i>
                <span>Gestão de Admins</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('employees') ? ' show' : ''; ?>" id="collapseAdmins">
                <a href="<?php echo APP_URL; ?>/admin/employees"
                    class="nav-link<?php echo $adm_current_path === 'employees' ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i><span>Listar Admins</span>
                </a>
                <?php if (hasPermission($admin_id, 'employees.edit')): ?>
                <a href="<?php echo APP_URL; ?>/admin/employees/new"
                    class="nav-link<?php echo adm_is_active('employees/new'); ?>">
                    <i class="bi bi-person-plus"></i><span>Novo Admin</span>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão de Usuários ── -->
        <?php if (hasPermission($admin_id, 'users.view')): ?>
        <li class="nav-item">
            <a href="#collapseUsers" class="nav-link<?php echo adm_is_active('users') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('users') ? 'true' : 'false'; ?>"
                aria-controls="collapseUsers">
                <i class="bi bi-people"></i>
                <span>Gestão de Usuários</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('users') ? ' show' : ''; ?>" id="collapseUsers">
                <a href="<?php echo APP_URL; ?>/admin/users"
                    class="nav-link<?php echo $adm_current_path === 'users' ? ' active' : ''; ?>">
                    <i class="bi bi-people"></i><span>Todos Usuários</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/users/available"
                    class="nav-link<?php echo adm_is_active('users/available'); ?>">
                    <i class="bi bi-person-check"></i><span>Contas Disponíveis</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/users/unavailable"
                    class="nav-link<?php echo adm_is_active('users/unavailable'); ?>">
                    <i class="bi bi-person-exclamation"></i><span>Contas Indisponíveis</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Artistas e Colaboradores ── -->
        <?php if (hasPermission($admin_id, 'users.view')): ?>
        <li class="nav-item">
            <a href="#collapseArtists"
                class="nav-link<?php echo adm_is_active('artists') || adm_is_active('collaborators') ? ' active' : ''; ?>"
                data-bs-toggle="collapse"
                aria-expanded="<?php echo (adm_is_active('artists') || adm_is_active('collaborators')) ? 'true' : 'false'; ?>"
                aria-controls="collapseArtists">
                <i class="bi bi-music-note-beamed"></i>
                <span>Artistas e Colaboradores</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo (adm_is_active('artists') || adm_is_active('collaborators')) ? ' show' : ''; ?>"
                id="collapseArtists">
                <a href="<?php echo APP_URL; ?>/admin/artists" class="nav-link<?php echo adm_is_active('artists'); ?>">
                    <i class="bi bi-person-lines-fill"></i><span>Artistas</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/collaborators"
                    class="nav-link<?php echo adm_is_active('collaborators'); ?>">
                    <i class="bi bi-people-fill"></i><span>Colaboradores</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Gestão de Músicas / Lançamentos ── -->
        <?php if (hasPermission($admin_id, 'music.view')): ?>
        <li class="nav-item">
            <a href="#collapseSongs" class="nav-link<?php echo adm_is_active('releases') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('releases') ? 'true' : 'false'; ?>"
                aria-controls="collapseSongs">
                <i class="bi bi-music-note-list"></i>
                <span>Gestão de Músicas</span>
                <?php if ($adm_pending_releases > 0 && hasPermission($admin_id, 'music.approve')): ?>
                <span class="badge bg-danger ms-1" style="font-size:.65rem"><?php echo $adm_pending_releases; ?></span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('releases') ? ' show' : ''; ?>" id="collapseSongs">
                <a href="<?php echo APP_URL; ?>/admin/releases"
                    class="nav-link<?php echo $adm_current_path === 'releases' ? ' active' : ''; ?>">
                    <i class="bi bi-collection"></i><span>Todos os Lançamentos</span>
                </a>
                <?php if (hasPermission($admin_id, 'music.approve')): ?>
                <a href="<?php echo APP_URL; ?>/admin/releases/pending"
                    class="nav-link<?php echo adm_is_active('releases/pending'); ?>">
                    <i class="bi bi-hourglass-split"></i><span>Pendentes</span>
                    <?php if ($adm_pending_releases > 0): ?>
                    <span class="badge bg-danger ms-auto"
                        style="font-size:.6rem"><?php echo $adm_pending_releases; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Distribuição ── -->
        <?php if (hasPermission($admin_id, 'music.view')): ?>
        <li class="nav-item">
            <a href="#collapseDistribution"
                class="nav-link<?php echo adm_is_active('distribution') ? ' active' : ''; ?>" data-bs-toggle="collapse"
                aria-expanded="<?php echo adm_is_active('distribution') ? 'true' : 'false'; ?>"
                aria-controls="collapseDistribution">
                <i class="bi bi-globe"></i>
                <span>Distribuição</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('distribution') ? ' show' : ''; ?>" id="collapseDistribution">
                <a href="<?php echo APP_URL; ?>/admin/distribution/stores"
                    class="nav-link<?php echo adm_is_active('distribution/stores'); ?>">
                    <i class="bi bi-shop"></i><span>Lojas Digitais</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/distribution/schedule"
                    class="nav-link<?php echo adm_is_active('distribution/schedule'); ?>">
                    <i class="bi bi-calendar-event"></i><span>Agendar Lançamento</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Finanças ── -->
        <?php if (hasPermission($admin_id, 'finances.view')): ?>
        <li class="nav-item">
            <a href="#collapseFinances" class="nav-link<?php echo adm_is_active('finances') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('finances') ? 'true' : 'false'; ?>"
                aria-controls="collapseFinances">
                <i class="bi bi-wallet2"></i>
                <span>Finanças</span>
                <?php if ($adm_pending_payments > 0): ?>
                <span class="badge bg-warning text-dark ms-1"
                    style="font-size:.65rem"><?php echo $adm_pending_payments; ?></span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('finances') ? ' show' : ''; ?>" id="collapseFinances">
                <a href="<?php echo APP_URL; ?>/admin/finances"
                    class="nav-link<?php echo $adm_current_path === 'finances' ? ' active' : ''; ?>">
                    <i class="bi bi-currency-dollar"></i><span>Visão Geral</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/finances/payments"
                    class="nav-link<?php echo adm_is_active('finances/payments'); ?>">
                    <i class="bi bi-credit-card"></i><span>Pagamentos</span>
                    <?php if ($adm_pending_payments > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto"
                        style="font-size:.6rem"><?php echo $adm_pending_payments; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/finances/withdrawals"
                    class="nav-link<?php echo adm_is_active('finances/withdrawals'); ?>">
                    <i class="bi bi-arrow-up-circle"></i><span>Levantamentos</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/finances/reports"
                    class="nav-link<?php echo adm_is_active('finances/reports'); ?>">
                    <i class="bi bi-file-earmark-spreadsheet"></i><span>Relatórios</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Integração / YouTube ── -->
        <?php if (hasPermission($admin_id, 'music.view')): ?>
        <li class="nav-item">
            <a href="#collapseIntegration" class="nav-link<?php echo adm_is_active('integration') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('integration') ? 'true' : 'false'; ?>"
                aria-controls="collapseIntegration">
                <i class="bi bi-youtube"></i>
                <span>Unificação e V. Youtube</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('integration') ? ' show' : ''; ?>" id="collapseIntegration">
                <a href="<?php echo APP_URL; ?>/admin/integration/config"
                    class="nav-link<?php echo adm_is_active('integration/config'); ?>">
                    <i class="bi bi-gear"></i><span>Configurar Integração</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/integration/channels"
                    class="nav-link<?php echo adm_is_active('integration/channels'); ?>">
                    <i class="bi bi-check2-all"></i><span>Verificar Canais</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Suporte ── -->
        <?php if (hasPermission($admin_id, 'support.view')): ?>
        <li class="nav-item">
            <a href="#collapseSupport" class="nav-link<?php echo adm_is_active('support') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('support') ? 'true' : 'false'; ?>"
                aria-controls="collapseSupport">
                <i class="bi bi-headset"></i>
                <span>Suporte</span>
                <?php if ($adm_open_tickets > 0): ?>
                <span class="badge bg-danger ms-1" style="font-size:.65rem"><?php echo $adm_open_tickets; ?></span>
                <?php endif; ?>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('support') ? ' show' : ''; ?>" id="collapseSupport">
                <a href="<?php echo APP_URL; ?>/admin/support"
                    class="nav-link<?php echo $adm_current_path === 'support' ? ' active' : ''; ?>">
                    <i class="bi bi-ticket"></i><span>Todos os Tickets</span>
                    <?php if ($adm_open_tickets > 0): ?>
                    <span class="badge bg-danger ms-auto"
                        style="font-size:.6rem"><?php echo $adm_open_tickets; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Auditoria ── -->
        <?php if (hasPermission($admin_id, 'audit.view')): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL; ?>/admin/audit" class="nav-link<?php echo adm_is_active('audit'); ?>">
                <i class="bi bi-journal-text"></i>
                <span>Log de Auditoria</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- ── Blog / CMS ── -->
        <?php if (hasPermission($admin_id, 'settings.edit')): ?>
        <li class="nav-item">
            <a href="#collapseCms" class="nav-link<?php echo adm_is_active('cms') ? ' active' : ''; ?>"
                data-bs-toggle="collapse" aria-expanded="<?php echo adm_is_active('cms') ? 'true' : 'false'; ?>"
                aria-controls="collapseCms">
                <i class="bi bi-newspaper"></i>
                <span>Blog / CMS</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo adm_is_active('cms') ? ' show' : ''; ?>" id="collapseCms">
                <a href="<?php echo APP_URL; ?>/admin/cms/posts"
                    class="nav-link<?php echo adm_is_active('cms/posts'); ?>">
                    <i class="bi bi-file-earmark-text"></i><span>Posts</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/cms/categories"
                    class="nav-link<?php echo adm_is_active('cms/categories'); ?>">
                    <i class="bi bi-tags"></i><span>Categorias</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/cms/faq" class="nav-link<?php echo adm_is_active('cms/faq'); ?>">
                    <i class="bi bi-question-circle"></i><span>FAQs</span>
                </a>
            </div>
        </li>
        <?php endif; ?>

        <!-- ── Ajuda ── -->
        <li class="nav-item">
            <a href="#collapseHelp"
                class="nav-link<?php echo adm_is_active('help') || adm_is_active('faq') ? ' active' : ''; ?>"
                data-bs-toggle="collapse"
                aria-expanded="<?php echo (adm_is_active('help') || adm_is_active('faq')) ? 'true' : 'false'; ?>"
                aria-controls="collapseHelp">
                <i class="bi bi-question-circle"></i>
                <span>Ajuda</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size:.8rem"></i>
            </a>
            <div class="collapse<?php echo (adm_is_active('help') || adm_is_active('faq')) ? ' show' : ''; ?>"
                id="collapseHelp">
                <a href="<?php echo APP_URL; ?>/admin/faq" class="nav-link<?php echo adm_is_active('faq'); ?>">
                    <i class="bi bi-messenger"></i><span>FAQs</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/help/contact"
                    class="nav-link<?php echo adm_is_active('help/contact'); ?>">
                    <i class="bi bi-telephone"></i><span>Contacto com suporte</span>
                </a>
            </div>
        </li>

        <!-- ── Configurações ── -->
        <?php if (hasPermission($admin_id, 'settings.view')): ?>
        <li class="nav-item">
            <a href="<?php echo APP_URL; ?>/admin/settings" class="nav-link<?php echo adm_is_active('settings'); ?>">
                <i class="bi bi-sliders"></i>
                <span>Configurações</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- ── Separador + Logout ── -->
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