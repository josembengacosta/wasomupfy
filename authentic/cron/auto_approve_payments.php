<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/payment_workflow.php';

$db       = getDB();
$panelUrl = APP_URL . '/' . APP_URL_PANEL . '/painel';
$now      = date('Y-m-d H:i:s');
$logs     = [];

$stmt = $db->query("
    SELECT pi.id_intent, pi.id_users, pi.id_plan, pi.reference_code, pi.amount_expected,
           pl.name_plan, pl.slug_plan,
           u.email_user, u.first_name, u.second_name,
           pp.id_proof, pp.file_path, pp.method, pp.uploaded_at
    FROM _payment_intent pi
    JOIN _payment p
      ON p.payment_ref = pi.reference_code
     AND p.id_users = pi.id_users
     AND p.status_payment = 'pending'
    JOIN _plans pl ON pl.id_plan = pi.id_plan
    JOIN _users u ON u.id_users = pi.id_users
    JOIN _payment_proof pp
      ON pp.id_proof = (
            SELECT pp2.id_proof
            FROM _payment_proof pp2
            WHERE pp2.id_intent = pi.id_intent
            ORDER BY pp2.uploaded_at DESC, pp2.id_proof DESC
            LIMIT 1
        )
    WHERE pi.status = 'under_review'
      AND pp.status = 'pending'
      AND pl.slug_plan IN ('single', 'album', 'artist')
      AND pp.uploaded_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ORDER BY pp.uploaded_at ASC
    LIMIT 50
");
$pending = $stmt->fetchAll();

if (!$pending) {
    echo '[' . $now . "] Nenhum pagamento pronto para auto-activacao.\n";
    exit;
}

foreach ($pending as $intent) {
    try {
        $db->beginTransaction();

        paymentWorkflowActivatePlan($db, $intent, $intent, null);

        $db->prepare("
            INSERT INTO _notification (id_users, type, title, body, action_url)
            VALUES (?, 'payment', ?, ?, ?)
        ")->execute([
            $intent['id_users'],
            'Plano activado',
            'O teu plano ' . $intent['name_plan'] . ' foi confirmado e ja esta activo.',
            $panelUrl,
        ]);

        $db->commit();

        $userName = trim(($intent['first_name'] ?? '') . ' ' . ($intent['second_name'] ?? ''));
        sendEmail(
            $intent['email_user'],
            'Plano ' . $intent['name_plan'] . ' activado - ' . APP_NAME,
            '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto">' .
            '<h2>Plano activado</h2>' .
            '<p>Ola ' . htmlspecialchars($userName) . ',</p>' .
            '<p>O teu pagamento foi confirmado e o plano <strong>' . htmlspecialchars($intent['name_plan']) . '</strong> ja esta activo.</p>' .
            '<p><strong>Referencia:</strong> ' . htmlspecialchars($intent['reference_code']) . '</p>' .
            '<p><a href="' . $panelUrl . '">Ir ao painel</a></p>' .
            '</div>'
        );

        $logs[] = '[OK] Intent ' . $intent['id_intent'] . ' activado automaticamente.';
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $logs[] = '[ERRO] Intent ' . $intent['id_intent'] . ': ' . $e->getMessage();
        error_log('[AUTO_APPROVE] ' . $e->getMessage());
    }
}

echo implode("\n", $logs) . "\n";
