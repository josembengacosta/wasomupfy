<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processo de Divisão de Royalties
// Arquivo: dashboard/finances/split_process.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL_PANEL . '/transactions');
}

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];

// ── CSRF ──────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (!validateCsrf($csrf)) {
    redirect(APP_URL_PANEL . '/transactions', ['error' => 'invalid']);
}

// ── Honeypot ──────────────────────────────────
checkHoneypot('honeypot');

$action = trim($_POST['action'] ?? '');

// ════════════════════════════════════════════════
// CRIAR DIVISÃO
// ════════════════════════════════════════════════
if ($action === 'create') {

    $id_artist     = (int)($_POST['id_artist']      ?? 0);
    $name_collab   = trim($_POST['name_collab']     ?? '');
    $role_collab   = trim($_POST['role_collab']     ?? '');
    $email_collab  = trim($_POST['email_collab']    ?? '');
    $royalty_share = (float)($_POST['royalty_share'] ?? 0);

    $valid_roles = ['feat', 'producer', 'composer', 'lyricist', 'manager', 'label', 'other'];

    // Validação básica
    if (
        $id_artist <= 0
        || empty($name_collab)
        || !in_array($role_collab, $valid_roles)
        || $royalty_share < 0.1
        || $royalty_share > 100
        || mb_strlen($name_collab) > 150
    ) {
        redirect(APP_URL_PANEL . '/transactions', ['error' => 'invalid']);
    }

    // Verificar que o artista pertence ao utilizador
    $art_q = $db->prepare("
        SELECT id_artist FROM _artist
        WHERE id_artist = ? AND id_users = ? LIMIT 1
    ");
    $art_q->execute([$id_artist, $id_users]);
    if (!$art_q->fetch()) {
        redirect(APP_URL_PANEL . '/transactions', ['error' => 'noartist']);
    }

    // Verificar soma total não excede 100%
    $sum_q = $db->prepare("
        SELECT COALESCE(SUM(royalty_share), 0)
        FROM _artist_collaborator WHERE id_artist = ?
    ");
    $sum_q->execute([$id_artist]);
    $current_sum = (float)$sum_q->fetchColumn();

    if (($current_sum + $royalty_share) > 100.0) {
        redirect(APP_URL_PANEL . '/transactions', ['error' => 'over100']);
    }

    // Verificar e-mail (se fornecido)
    $collab_user_id = null;
    if (!empty($email_collab)) {

        if (!filter_var($email_collab, FILTER_VALIDATE_EMAIL)) {
            redirect(APP_URL_PANEL . '/transactions', ['error' => 'invalid']);
        }

        // Não pode ser a própria conta
        $own_q = $db->prepare("SELECT email_user FROM _users WHERE id_users = ? LIMIT 1");
        $own_q->execute([$id_users]);
        $own_email = $own_q->fetchColumn();
        if (strtolower($email_collab) === strtolower($own_email)) {
            redirect(APP_URL_PANEL . '/transactions', ['error' => 'sameemail']);
        }

        // Verificar se existe na plataforma
        $eu_q = $db->prepare("SELECT id_users FROM _users WHERE email_user = ? LIMIT 1");
        $eu_q->execute([$email_collab]);
        $found = $eu_q->fetchColumn();
        if ($found) {
            $collab_user_id = (int)$found;
        }
        // e-mail externo (não registado) é permitido — guarda apenas o e-mail
    }

    // Verificar duplicado (mesmo artista + mesmo utilizador registado)
    if ($collab_user_id) {
        $dup_q = $db->prepare("
            SELECT id_collab FROM _artist_collaborator
            WHERE id_artist = ? AND id_users = ? LIMIT 1
        ");
        $dup_q->execute([$id_artist, $collab_user_id]);
        if ($dup_q->fetch()) {
            redirect(APP_URL_PANEL . '/transactions', ['error' => 'dupli']);
        }
    }

    // Inserir
    $ins = $db->prepare("
        INSERT INTO _artist_collaborator
            (id_artist, id_users, name_collab, role_collab, email_collab, royalty_share)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([
        $id_artist,
        $collab_user_id,
        $name_collab,
        $role_collab,
        !empty($email_collab) ? $email_collab : null,
        round($royalty_share, 2),
    ]);

    logActivity(
        $id_users,
        'royalty_split_created',
        "Divisão criada: {$name_collab} ({$royalty_share}%) — artista ID {$id_artist}",
        'artist',
        $id_artist
    );

    redirect(APP_URL_PANEL . '/transactions', ['success' => 'created']);
}


// ════════════════════════════════════════════════
// REMOVER DIVISÃO
// ════════════════════════════════════════════════
if ($action === 'delete') {

    $id_collab = (int)($_POST['id_collab'] ?? 0);
    $id_artist = (int)($_POST['id_artist'] ?? 0);

    if ($id_collab <= 0 || $id_artist <= 0) {
        redirect(APP_URL_PANEL . '/transactions', ['error' => 'invalid']);
    }

    // Verificar que o artista pertence ao utilizador
    $art_q = $db->prepare("
        SELECT id_artist FROM _artist
        WHERE id_artist = ? AND id_users = ? LIMIT 1
    ");
    $art_q->execute([$id_artist, $id_users]);
    if (!$art_q->fetch()) {
        redirect(APP_URL_PANEL . '/transactions', ['error' => 'noartist']);
    }

    // Verificar que o split existe e pertence a este artista
    $chk = $db->prepare("
        SELECT id_collab, name_collab FROM _artist_collaborator
        WHERE id_collab = ? AND id_artist = ? LIMIT 1
    ");
    $chk->execute([$id_collab, $id_artist]);
    $split = $chk->fetch();
    if (!$split) {
        redirect(APP_URL_PANEL . '/transactions', ['error' => 'notfound']);
    }

    $db->prepare("
        DELETE FROM _artist_collaborator WHERE id_collab = ? AND id_artist = ?
    ")->execute([$id_collab, $id_artist]);

    logActivity(
        $id_users,
        'royalty_split_deleted',
        "Divisão removida: {$split['name_collab']} — artista ID {$id_artist}",
        'artist',
        $id_artist
    );

    redirect(APP_URL_PANEL . '/transactions', ['success' => 'deleted']);
}

// Acção inválida
redirect(APP_URL_PANEL . '/transactions', ['error' => 'invalid']);
