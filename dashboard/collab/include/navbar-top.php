<nav class="collab-nav">
    <button class="theme-btn d-md-none" id="btn-sidebar-toggle">
        <i class="bi bi-list"></i>
    </button>
    <a class="nav-brand" href="overview">
        <?php echo APP_NAME; ?>
        <span>For Collaborator</span>
    </a>
    <div class="nav-spacer"></div>

    <!-- Role chip -->
    <div class="nav-chip d-none d-md-inline-flex"
        style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>;border-color:<?php echo $rm['color']; ?>20">
        <i class="bi <?php echo $rm['icon']; ?>"></i>
        <?php echo $role_label; ?>
    </div>

    <!-- Theme toggle -->
    <button class="theme-btn" id="themeToggle" title="Alternar tema">
        <i class="bi bi-sun" id="themeIcon"></i>
    </button>

    <!-- Avatar + dropdown -->
    <div class="dropdown">
        <button class="nav-avatar dropdown-toggle" style="padding:0" data-bs-toggle="dropdown">
            <?php if ($collab['photo_collab']): ?>
            <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>" alt=""
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
            <span style="display:none"><i class="bi bi-person-circle"></i></span>
            <?php else: ?>
            <span><i class="bi bi-person-circle"></i></span>
            <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="font-size:.84rem;min-width:200px">
            <li class="px-3 py-2">
                <div class="fw-bold">
                    <?php echo htmlspecialchars($collab['first_name'] . ' ' . ($collab['second_name'] ?? '')); ?>
                </div>
                <div class="text-reset" style="font-size:.72rem">
                    @<?php echo htmlspecialchars($collab['user_collab']); ?></div>
                <div class="mt-1">
                    <span class="chip" style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>">
                        <i class="bi <?php echo $rm['icon']; ?>"></i><?php echo $role_label; ?>
                    </span>
                </div>
            </li>
            <li>
                <hr class="dropdown-divider" />
            </li>
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#myProfileModal">
                    <i class="bi bi-person me-2"></i>O meu perfil
                </a></li>
            <li>
                <hr class="dropdown-divider" />
            </li>
            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="bi bi-box-arrow-right me-2"></i>Terminar sessão
                </a></li>
        </ul>
    </div>
</nav>