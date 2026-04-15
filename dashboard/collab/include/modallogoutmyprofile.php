<!-- ═══ MODAL — O meu perfil ═══ -->
<div class="modal fade" id="myProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="bi bi-person me-2" style="color:var(--wasom)"></i>O meu perfil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="text-center mb-3">
                    <?php if ($collab['photo_collab']): ?>
                    <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>"
                        style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--wasom)"
                        onerror="this.style.display='none'" alt="" />
                    <?php else: ?>
                    <div
                        style="width:72px;height:72px;border-radius:50%;background:rgba(255,0,137,.1);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto">
                        🎤</div>
                    <?php endif; ?>
                    <h5 class="fw-bold mt-2 mb-0">
                        <?php echo htmlspecialchars($collab['first_name'] . ' ' . ($collab['second_name'] ?? '')); ?>
                    </h5>
                    <div class="text-muted small">@<?php echo htmlspecialchars($collab['user_collab']); ?></div>
                </div>
                <div style="font-size:.83rem">
                    <?php
                    $info_rows = [
                        ['Email',       $collab['email_collab'],      'bi-envelope'],
                        ['Telefone',    $collab['tel_collab'] ?: '—', 'bi-telephone'],
                        ['Função',      $role_label,                   'bi-person-badge'],
                        ['Membro desde', date('d/m/Y', strtotime($collab['creat_collab'])), 'bi-calendar3'],
                        ['Último login', $collab['last_login_at'] ? date('d/m/Y H:i', strtotime($collab['last_login_at'])) : '—', 'bi-clock'],
                    ];
                    foreach ($info_rows as [$label, $val, $ico]):
                    ?>
                    <div class="d-flex gap-2 py-2 border-bottom align-items-center">
                        <i class="bi <?php echo $ico; ?> text-muted" style="width:16px"></i>
                        <span class="text-reset" style="width:100px;flex-shrink:0"><?php echo $label; ?></span>
                        <span class="fw-semibold text-truncate"><?php echo htmlspecialchars($val); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($collab['notes']): ?>
                <div class="mt-3 p-3"
                    style="background:rgba(255,0,137,.04);border-radius:10px;border:1px solid rgba(255,0,137,.1)">
                    <div class="text-reset" style="font-size:.7rem;margin-bottom:4px">NOTAS DO ADMINISTRADOR</div>
                    <div style="font-size:.82rem"><?php echo htmlspecialchars($collab['notes']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>


<!-- ═══ MODAL — Logout ═══ -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title">Terminar sessão?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="small mb-0">
                    Vais sair do painel de colaboradores. Podes entrar novamente através do link que recebeste por
                    email.
                </p>
            </div>
            <div class="modal-footer border-0 gap-2 pt-1">
                <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Continuar</button>
                <a href="<?php echo htmlspecialchars($logout_url); ?>" class="btn btn-danger btn-sm flex-fill">
                    <i class="bi bi-box-arrow-right me-1"></i>Terminar
                </a>
            </div>
        </div>
    </div>
</div>