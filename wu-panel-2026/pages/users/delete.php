<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Excluir Utilizador (versão manual)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';

if ($admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH . '/users');
}

// ── POST: executar exclusão ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
        adminRedirect('/' . ADMIN_PATH . '/users', ['msg' => 'error']);
    }
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

    $user_id = (int)($_POST['id'] ?? 0);
    if (!$user_id) adminRedirect('/' . ADMIN_PATH . '/users');

    // Buscar dados do utilizador
    $user_stmt = $db->prepare("SELECT first_name, second_name, email_user, photo_user FROM _users WHERE id_users = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();

    if (!$user_data) adminRedirect('/' . ADMIN_PATH . '/users');

    $fullname = trim($user_data['first_name'] . ' ' . ($user_data['second_name'] ?? ''));
    $photo_file = $user_data['photo_user'];

    try {
        $db->beginTransaction();

        // ═══════════════════════════════════════════════════════════════
        // 1. APAGAR FOTO DO SERVIDOR
        // ═══════════════════════════════════════════════════════════════
        if ($photo_file) {
            $photo_path = __DIR__ . '/../../../assets/comprovantes/uploads/users/' . $photo_file;
            if (file_exists($photo_path)) {
                @unlink($photo_path);
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. ELIMINAR REGISTOS RELACIONADOS (ordem correta)
        // ═══════════════════════════════════════════════════════════════

        // 2.1 Tabelas que dependem de outras
        $db->prepare("DELETE FROM _broadcast_receipt WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _album_review_request WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _takedown_request WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _delete_requests WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _support_reply WHERE from_user = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _support_ticket WHERE id_users = ?")->execute([$user_id]);

        // 2.2 Tabelas de notificações e mensagens
        $db->prepare("DELETE FROM _notification WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _message WHERE from_user = ? OR to_user = ?")->execute([$user_id, $user_id]);

        // 2.3 Tabelas de colaboradores
        $db->prepare("DELETE FROM _artist_collaborator WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _collaborators WHERE id_users = ?")->execute([$user_id]);

        // 2.4 Tabelas de sessões e tokens (podem não existir — ignorar erro)
        foreach (
            [
                'DELETE FROM _users_tokens WHERE id_users = ?',
                'DELETE FROM _users_sessions WHERE id_users = ?',
                'DELETE FROM _user_activity_log WHERE id_users = ?',
                'DELETE FROM _user_presence WHERE id_users = ?',
                'DELETE FROM _user_settings WHERE id_users = ?',
            ] as $optional_sql
        ) {
            try {
                $db->prepare($optional_sql)->execute([$user_id]);
            } catch (Throwable $e) {
            }
        }

        // 2.7 Tabelas de segurança
        $db->prepare("DELETE FROM _users_security WHERE id_users = ?")->execute([$user_id]);

        // 2.8 Tabelas financeiras (remover referência)
        $db->prepare("DELETE FROM _wallet WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM _user_plan WHERE id_users = ?")->execute([$user_id]);

        // 2.9 YouTube
        $db->prepare("DELETE FROM _youtube_channel WHERE id_users = ?")->execute([$user_id]);

        // ═══════════════════════════════════════════════════════════════
        // 3. ANONIMIZAR TABELAS QUE PRECISAM SER MANTIDAS
        // ═══════════════════════════════════════════════════════════════

        // 3.1 Artistas: remove ligação ao utilizador
        $db->prepare("UPDATE _artist SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);

        // 3.2 Álbuns e Tracks: não têm id_users directo — ligam via _artist
        //    A anonimização já ocorre quando o _artist.id_users é anulado (passo 3.1)
        //    Os álbuns e faixas mantêm-se associados ao artista (agora órfão)

        // 3.4 Pagamentos: remove referência
        $db->prepare("UPDATE _payment SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("UPDATE _payment_intent SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("UPDATE _payment_proof SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);

        // 3.5 Transações e royalties
        $db->prepare("UPDATE _transaction SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("UPDATE _royalty SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);

        // 3.6 Withdrawal
        $db->prepare("UPDATE _withdrawal SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);
        $db->prepare("UPDATE _withdrawal_requests SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);

        // 3.7 Invoices
        $db->prepare("UPDATE _invoice SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);

        // 3.8 Financial reports
        $db->prepare("UPDATE _financial_report SET id_users = NULL WHERE id_users = ?")->execute([$user_id]);

        // 3.9 Accounts (contas bancárias)
        $db->prepare("DELETE FROM _account WHERE id_users = ?")->execute([$user_id]);

        // ═══════════════════════════════════════════════════════════════
        // 4. REGISTAR NA TABELA DE AUDITORIA
        // ═══════════════════════════════════════════════════════════════
        logAudit($admin_id, $user_id, 'users.deleted', '_users', $user_id, null, [
            'name' => $fullname,
            'email' => $user_data['email_user']
        ]);

        // ═══════════════════════════════════════════════════════════════
        // 5. POR FIM, ELIMINAR O UTILIZADOR
        // ═══════════════════════════════════════════════════════════════
        $db->prepare("DELETE FROM _users WHERE id_users = ?")->execute([$user_id]);

        $db->commit();

        adminRedirect('/' . ADMIN_PATH . '/users', ['msg' => 'deleted']);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[DELETE USER ERROR] ' . $e->getMessage());
        error_log('[DELETE USER TRACE] ' . $e->getTraceAsString());
        adminRedirect('/' . ADMIN_PATH . '/users', ['msg' => 'error']);
    }
}

// ── GET: página de confirmação (manter o mesmo HTML do código anterior) ──
$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/users');

$stmt = $db->prepare("
    SELECT
        u.id_users, u.first_name, u.second_name, u.user_name,
        u.email_user, u.photo_user, u.status_user, u.creat_user,
        (SELECT COUNT(*) FROM _artist a WHERE a.id_users = u.id_users)            AS artist_count,
        (SELECT COUNT(*)
         FROM _album al INNER JOIN _artist a ON a.id_artist = al.id_artist
         WHERE a.id_users = u.id_users)                                           AS album_count,
        (SELECT COUNT(*) FROM _payment p WHERE p.id_users = u.id_users)          AS payment_count,
        (SELECT COUNT(*) FROM _transaction t WHERE t.id_users = u.id_users)      AS tx_count,
        (SELECT COUNT(*) FROM _support_ticket st WHERE st.id_users = u.id_users) AS ticket_count
    FROM _users u
    WHERE u.id_users = ?
    LIMIT 1
");
$stmt->execute([$id]);
$usr = $stmt->fetch();

if (!$usr) adminRedirect('/' . ADMIN_PATH . '/users');

$fullname = trim($usr['first_name'] . ' ' . ($usr['second_name'] ?? ''));
$ini      = adm_initials($usr['first_name'], $usr['second_name'] ?? '');
$color    = adm_avatar_color($fullname);

// Dados de dependências (vêm das subqueries acima)
$artist_count  = (int)($usr['artist_count']  ?? 0);
$album_count   = (int)($usr['album_count']   ?? 0);
$payment_count = (int)($usr['payment_count'] ?? 0);
$tx_count      = (int)($usr['tx_count']      ?? 0);
$ticket_count  = (int)($usr['ticket_count']  ?? 0);
$total_deps    = $artist_count + $album_count + $payment_count + $tx_count + $ticket_count;
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Excluir Utilizador — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap"
        rel="stylesheet" />
    <style>
    * {
        font-family: 'Inter', sans-serif;
    }

    .del-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.5rem;
        color: #fff;
    }

    .del-avatar img {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
    }

    .danger-zone {
        border: 2px solid rgba(239, 68, 68, .3);
        border-radius: 20px;
        padding: 24px;
        background: rgba(239, 68, 68, .04);
    }

    .checklist-del {
        list-style: none;
        padding: 0;
        margin: 16px 0 0;
    }

    .checklist-del li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: .85rem;
        margin-bottom: 10px;
        color: #991b1b;
    }

    .dep-stat {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: .8rem;
        font-weight: 600;
        margin: 4px;
    }

    .info-card {
        background: rgba(0, 0, 0, .03);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 20px;
    }

    .dark-mode .info-card {
        background: rgba(255, 255, 255, .05);
    }

    .section-title {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #FF0089;
        margin-bottom: 12px;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>
        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>
            <div class="container-fluid p-0" style="max-width:620px;margin:0 auto">

                <!-- Cabeçalho -->
                <div class="row mb-4 mt-2">
                    <div class="col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-trash3 me-2 text-danger"></i>Excluir Utilizador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                        class="text-secondary">Utilizadores</a></li>
                                <li class="breadcrumb-item active text-white-stable">Excluir</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <!-- Identificação do Utilizador -->
                <div class="info-card">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($usr['photo_user'])): ?>
                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($usr['photo_user']); ?>"
                            class="del-avatar" alt="" />
                        <?php else: ?>
                        <div class="del-avatar" style="background:<?php echo $color; ?>"><?php echo $ini; ?></div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:700;font-size:1.1rem"><?php echo htmlspecialchars($fullname); ?>
                            </div>
                            <div style="font-size:.8rem;opacity:.6">
                                @<?php echo htmlspecialchars($usr['user_name'] ?? '—'); ?>
                                &nbsp;·&nbsp;<?php echo htmlspecialchars($usr['email_user']); ?>
                            </div>
                            <div class="mt-1" style="font-size:.72rem;opacity:.5">
                                Membro desde <?php echo date('d/m/Y', strtotime($usr['creat_user'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados associados (avisos) -->
                <?php if ($total_deps > 0): ?>
                <div class="alert alert-warning mb-3" style="font-size:.82rem">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Este utilizador tem dados associados que serão anonimizados:</strong>
                    <div class="mt-2">
                        <?php if ($artist_count > 0): ?>
                        <span class="dep-stat" style="background:rgba(255,0,137,.1);color:#FF0089">
                            <i class="bi bi-mic"></i><?php echo $artist_count; ?>
                            artista<?php echo $artist_count !== 1 ? 's' : ''; ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($album_count > 0): ?>
                        <span class="dep-stat" style="background:rgba(59,130,246,.1);color:#1e40af">
                            <i class="bi bi-vinyl"></i><?php echo $album_count; ?>
                            álbum<?php echo $album_count !== 1 ? 'ns' : ''; ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($payment_count > 0): ?>
                        <span class="dep-stat" style="background:rgba(234,179,8,.1);color:#92400e">
                            <i class="bi bi-credit-card"></i><?php echo $payment_count; ?>
                            pagamento<?php echo $payment_count !== 1 ? 's' : ''; ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($tx_count > 0): ?>
                        <span class="dep-stat" style="background:rgba(34,197,94,.1);color:#166534">
                            <i class="bi bi-arrow-left-right"></i><?php echo number_format($tx_count); ?>
                            transaç<?php echo $tx_count !== 1 ? 'ões' : 'ão'; ?>
                        </span>
                        <?php endif; ?>

                        <?php if ($ticket_count > 0): ?>
                        <span class="dep-stat" style="background:rgba(59,130,246,.1);color:#1e40af">
                            <i class="bi bi-ticket"></i><?php echo $ticket_count; ?>
                            ticket<?php echo $ticket_count !== 1 ? 's' : ''; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Zona de Perigo -->
                <div class="danger-zone mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-exclamation-octagon-fill text-danger fs-4"></i>
                        <strong style="color:#991b1b;font-size:1rem">Esta acção é irreversível</strong>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="section-title"><i class="bi bi-exclamation"></i> SERÁ ELIMINADO</div>
                            <ul class="checklist-del">
                                <li><i class="bi bi-trash-fill"></i>Dados pessoais (nome, email, telefone)</li>
                                <li><i class="bi bi-trash-fill"></i>Credenciais e senha</li>
                                <li><i class="bi bi-trash-fill"></i>Tokens e sessões ativas</li>
                                <li><i class="bi bi-trash-fill"></i>Configurações e preferências</li>
                                <li><i class="bi bi-trash-fill"></i>Chaves de recuperação e 2FA</li>
                                <li><i class="bi bi-trash-fill"></i>Carteira e saldo</li>
                                <li><i class="bi bi-trash-fill"></i>Notificações pessoais</li>
                                <li><i class="bi bi-trash-fill"></i>Mensagens</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="section-title"><i class="bi bi-file-text"></i> SERÁ ANONIMIZADO</div>
                            <ul class="checklist-del" style="color:#b45309">
                                <li><i class="bi bi-person-lock"></i>Artistas (perfis mantidos sem dono)</li>
                                <li><i class="bi bi-person-lock"></i>Álbuns e faixas</li>
                                <li><i class="bi bi-person-lock"></i>Pagamentos e transações</li>
                                <li><i class="bi bi-person-lock"></i>Tickets de suporte</li>
                                <li><i class="bi bi-person-lock"></i>Royalties e streams</li>
                                <li><i class="bi bi-person-lock"></i>Relatórios financeiros</li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-info mt-2 mb-0" style="font-size:.8rem">
                        <i class="bi bi-shield-check me-2"></i>
                        <strong>Conformidade com LGPD/GDPR:</strong>
                        Os dados pessoais são eliminados permanentemente (direito ao esquecimento).
                        Dados financeiros e de streams são mantidos por obrigação fiscal e interesse legítimo,
                        mas sem qualquer referência pessoal.
                    </div>
                </div>

                <!-- Formulário de confirmação -->
                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/delete" id="form-del"
                    onsubmit="return validateDel()">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                    <input type="hidden" name="id" value="<?php echo $id; ?>" />

                    <div class="mb-4">
                        <label class="form-label" style="font-size:.85rem;font-weight:600">
                            Para confirmar, escreve o nome completo do utilizador:
                        </label>
                        <input type="text" class="form-control" id="inp-confirm"
                            placeholder="<?php echo htmlspecialchars($fullname); ?>" autocomplete="off"
                            style="border-radius: 12px; padding: 12px 16px;" />
                        <div id="name-err" style="font-size:.75rem;color:#ef4444;margin-top:8px;display:none">
                            <i class="bi bi-x-circle me-1"></i>O nome não corresponde. Digite
                            <strong><?php echo htmlspecialchars($fullname); ?></strong>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $id; ?>"
                            class="btn btn-outline-secondary flex-grow-1 py-2" style="border-radius: 12px;">
                            <i class="bi bi-arrow-left me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger flex-grow-1 py-2" id="btn-del" disabled
                            style="border-radius: 12px;">
                            <span class="spinner-border spinner-border-sm d-none me-1" id="spin-del"></span>
                            <i class="bi bi-trash3 me-1"></i>Excluir definitivamente
                        </button>
                    </div>
                </form>

                <!-- Nota de segurança -->
                <div class="text-center mt-4">
                    <small class="text-muted" style="font-size:.7rem">
                        <i class="bi bi-shield-lock"></i> Esta ação é registada na auditoria do sistema.
                    </small>
                </div>

            </div>
        </div>
    </div>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    var EXPECTED = <?php echo json_encode($fullname); ?>;

    document.getElementById('inp-confirm').addEventListener('input', function() {
        var inputVal = this.value.trim();
        var ok = inputVal.toLowerCase() === EXPECTED.toLowerCase();
        document.getElementById('btn-del').disabled = !ok;
        document.getElementById('name-err').style.display = (inputVal.length > 0 && !ok) ? 'block' : 'none';
    });

    function validateDel() {
        var inputVal = document.getElementById('inp-confirm').value.trim();
        var ok = inputVal.toLowerCase() === EXPECTED.toLowerCase();

        if (!ok) {
            document.getElementById('name-err').style.display = 'block';
            return false;
        }

        return true; // confirmação já feita pelo campo de nome
    }
    </script>
</body>

</html>