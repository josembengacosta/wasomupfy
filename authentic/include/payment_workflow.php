<?php

require_once __DIR__ . '/functions.php';

function paymentWorkflowFetchPlan(PDO $db, int $idPlan): array
{
    $stmt = $db->prepare("SELECT * FROM _plans WHERE id_plan = ? LIMIT 1");
    $stmt->execute([$idPlan]);
    $plan = $stmt->fetch();

    if (!$plan) {
        throw new RuntimeException("Plano {$idPlan} nao encontrado.");
    }

    return $plan;
}

function paymentWorkflowFetchPaymentByReference(PDO $db, string $reference): ?array
{
    $stmt = $db->prepare("SELECT * FROM _payment WHERE payment_ref = ? LIMIT 1");
    $stmt->execute([$reference]);

    return $stmt->fetch() ?: null;
}

function paymentWorkflowFetchLatestProof(PDO $db, int $intentId): ?array
{
    $stmt = $db->prepare("
        SELECT *
        FROM _payment_proof
        WHERE id_intent = ?
        ORDER BY uploaded_at DESC, id_proof DESC
        LIMIT 1
    ");
    $stmt->execute([$intentId]);

    return $stmt->fetch() ?: null;
}

function paymentWorkflowMapMethod(?string $proofMethod, ?string $existingMethod = null): string
{
    $allowedExisting = ['bank_transfer', 'multicaixa', 'paypal', 'card', 'other'];
    if ($existingMethod && in_array($existingMethod, $allowedExisting, true)) {
        return $existingMethod;
    }

    return match ($proofMethod ?? 'express') {
        'iban'    => 'bank_transfer',
        'express' => 'multicaixa',
        default   => 'other',
    };
}

function paymentWorkflowPlanExpiry(array $plan): ?string
{
    if (($plan['type_plan'] ?? '') !== 'subscription') {
        return null;
    }

    $validityDays = (int)($plan['validity_days'] ?? 0);
    if ($validityDays <= 0) {
        return null;
    }

    return date('Y-m-d H:i:s', time() + ($validityDays * 86400));
}

function paymentWorkflowIsRenewal(PDO $db, int $idUsers, int $idPlan): int
{
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM _user_plan
        WHERE id_users = ? AND id_plan = ?
    ");
    $stmt->execute([$idUsers, $idPlan]);

    return (int)($stmt->fetchColumn() > 0);
}

function paymentWorkflowCreatePendingActivation(PDO $db, array $intent, array $proof): int
{
    $idUsers   = (int)$intent['id_users'];
    $idPlan    = (int)$intent['id_plan'];
    $reference = (string)$intent['reference_code'];
    $amount    = (float)$intent['amount_expected'];
    $plan      = paymentWorkflowFetchPlan($db, $idPlan);
    $payment   = paymentWorkflowFetchPaymentByReference($db, $reference);
    $method    = paymentWorkflowMapMethod($proof['method'] ?? null, $payment['payment_method'] ?? null);

    if ($payment) {
        $db->prepare("
            UPDATE _payment
            SET id_users = ?,
                id_plan = ?,
                amount = ?,
                currency = 'AOA',
                payment_method = ?,
                comprovante = ?,
                status_payment = 'pending',
                rejection_reason = NULL,
                reviewed_by = NULL,
                reviewed_at = NULL
            WHERE id_payment = ?
        ")->execute([
            $idUsers,
            $idPlan,
            $amount,
            $method,
            $proof['file_path'] ?? null,
            $payment['id_payment'],
        ]);
        $paymentId = (int)$payment['id_payment'];
    } else {
        $db->prepare("
            INSERT INTO _payment
                (id_users, id_plan, payment_ref, amount, currency, payment_method, comprovante, status_payment, is_renewal)
            VALUES (?, ?, ?, ?, 'AOA', ?, ?, 'pending', ?)
        ")->execute([
            $idUsers,
            $idPlan,
            $reference,
            $amount,
            $method,
            $proof['file_path'] ?? null,
            paymentWorkflowIsRenewal($db, $idUsers, $idPlan),
        ]);
        $paymentId = (int)$db->lastInsertId();
    }

    $planStmt = $db->prepare("
        SELECT id_user_plan
        FROM _user_plan
        WHERE id_payment = ?
        ORDER BY id_user_plan DESC
        LIMIT 1
    ");
    $planStmt->execute([$paymentId]);
    $pendingPlan = $planStmt->fetch();

    if ($pendingPlan) {
        $db->prepare("
            UPDATE _user_plan
            SET id_users = ?,
                id_plan = ?,
                id_payment = ?,
                status_plan = 'pending_payment',
                releases_limit = ?,
                started_at = NULL,
                expires_at = NULL,
                modif_user_plan = NOW()
            WHERE id_user_plan = ?
        ")->execute([
            $idUsers,
            $idPlan,
            $paymentId,
            $plan['max_releases'] ?? null,
            $pendingPlan['id_user_plan'],
        ]);
    } else {
        $db->prepare("
            INSERT INTO _user_plan
                (id_users, id_plan, id_payment, status_plan, releases_used, releases_limit, started_at, expires_at, auto_renew)
            VALUES (?, ?, ?, 'pending_payment', 0, ?, NULL, NULL, 0)
        ")->execute([
            $idUsers,
            $idPlan,
            $paymentId,
            $plan['max_releases'] ?? null,
        ]);
    }

    return $paymentId;
}

function paymentWorkflowActivatePlan(PDO $db, array $intent, ?array $proof = null, ?int $reviewedBy = null): int
{
    $idUsers   = (int)$intent['id_users'];
    $idPlan    = (int)$intent['id_plan'];
    $intentId  = (int)$intent['id_intent'];
    $reference = (string)$intent['reference_code'];
    $amount    = (float)$intent['amount_expected'];
    $plan      = paymentWorkflowFetchPlan($db, $idPlan);
    $payment   = paymentWorkflowFetchPaymentByReference($db, $reference);
    $proof     = $proof ?: paymentWorkflowFetchLatestProof($db, $intentId);

    if (!$payment && !$proof) {
        throw new RuntimeException("Nao existe pagamento ou comprovativo para o intent {$intentId}.");
    }

    $proofPath   = $proof['file_path'] ?? ($payment['comprovante'] ?? null);
    $paymentType = paymentWorkflowMapMethod($proof['method'] ?? null, $payment['payment_method'] ?? null);
    $expiresAt   = paymentWorkflowPlanExpiry($plan);
    $wasApproved = $payment && $payment['status_payment'] === 'approved';

    if ($payment) {
        $db->prepare("
            UPDATE _payment
            SET id_users = ?,
                id_plan = ?,
                amount = ?,
                currency = 'AOA',
                payment_method = ?,
                comprovante = ?,
                status_payment = 'approved',
                rejection_reason = NULL,
                reviewed_by = COALESCE(?, reviewed_by),
                reviewed_at = NOW()
            WHERE id_payment = ?
        ")->execute([
            $idUsers,
            $idPlan,
            $amount,
            $paymentType,
            $proofPath,
            $reviewedBy,
            $payment['id_payment'],
        ]);
        $paymentId = (int)$payment['id_payment'];
    } else {
        $db->prepare("
            INSERT INTO _payment
                (id_users, id_plan, payment_ref, amount, currency, payment_method, comprovante, status_payment, is_renewal, reviewed_by, reviewed_at)
            VALUES (?, ?, ?, ?, 'AOA', ?, ?, 'approved', ?, ?, NOW())
        ")->execute([
            $idUsers,
            $idPlan,
            $reference,
            $amount,
            $paymentType,
            $proofPath,
            paymentWorkflowIsRenewal($db, $idUsers, $idPlan),
            $reviewedBy,
        ]);
        $paymentId = (int)$db->lastInsertId();
    }

    $existingPlanStmt = $db->prepare("
        SELECT *
        FROM _user_plan
        WHERE id_payment = ?
        ORDER BY id_user_plan DESC
        LIMIT 1
    ");
    $existingPlanStmt->execute([$paymentId]);
    $existingPlan = $existingPlanStmt->fetch() ?: null;

    $activatedAt = date('Y-m-d H:i:s');
    if ($existingPlan && $existingPlan['status_plan'] === 'active' && !empty($existingPlan['started_at'])) {
        $activatedAt = $existingPlan['started_at'];
    }

    $expireSql    = "UPDATE _user_plan SET status_plan = 'expired', modif_user_plan = NOW() WHERE id_users = ? AND status_plan = 'active'";
    $expireParams = [$idUsers];
    if ($existingPlan && $existingPlan['status_plan'] === 'active') {
        $expireSql .= " AND id_user_plan <> ?";
        $expireParams[] = $existingPlan['id_user_plan'];
    }
    $db->prepare($expireSql)->execute($expireParams);

    if ($existingPlan) {
        $db->prepare("
            UPDATE _user_plan
            SET id_users = ?,
                id_plan = ?,
                id_payment = ?,
                status_plan = 'active',
                releases_limit = ?,
                started_at = COALESCE(started_at, ?),
                expires_at = ?,
                modif_user_plan = NOW()
            WHERE id_user_plan = ?
        ")->execute([
            $idUsers,
            $idPlan,
            $paymentId,
            $plan['max_releases'] ?? null,
            $activatedAt,
            $expiresAt,
            $existingPlan['id_user_plan'],
        ]);
    } else {
        $db->prepare("
            INSERT INTO _user_plan
                (id_users, id_plan, id_payment, status_plan, releases_used, releases_limit, started_at, expires_at, auto_renew)
            VALUES (?, ?, ?, 'active', 0, ?, ?, ?, 0)
        ")->execute([
            $idUsers,
            $idPlan,
            $paymentId,
            $plan['max_releases'] ?? null,
            $activatedAt,
            $expiresAt,
        ]);
    }

    $transactionStmt = $db->prepare("
        SELECT id_transaction
        FROM _transaction
        WHERE reference = ? AND type_transaction = 'plan_payment'
        LIMIT 1
    ");
    $transactionStmt->execute([$reference]);
    if (!$transactionStmt->fetch()) {
        $walletStmt = $db->prepare("SELECT balance_aoa FROM _wallet WHERE id_users = ? LIMIT 1");
        $walletStmt->execute([$idUsers]);
        $wallet = $walletStmt->fetch();
        $balance = (float)($wallet['balance_aoa'] ?? 0);

        $db->prepare("
            INSERT INTO _transaction
                (id_users, type_transaction, amount, currency, balance_before, balance_after, reference, description)
            VALUES (?, 'plan_payment', ?, 'AOA', ?, ?, ?, ?)
        ")->execute([
            $idUsers,
            $amount,
            $balance,
            $balance,
            $reference,
            'Activacao de plano: ' . ($plan['name_plan'] ?? 'Plano'),
        ]);
    }

    if ($proof) {
        $db->prepare("
            UPDATE _payment_proof
            SET status = 'validated',
                reject_reason = NULL,
                reviewer_id = COALESCE(?, reviewer_id),
                reviewed_at = NOW()
            WHERE id_proof = ?
        ")->execute([
            $reviewedBy,
            $proof['id_proof'],
        ]);
    }

    $db->prepare("
        UPDATE _payment_intent
        SET status = 'approved',
            approved_by = COALESCE(?, approved_by),
            approved_at = COALESCE(approved_at, NOW())
        WHERE id_intent = ?
    ")->execute([
        $reviewedBy,
        $intentId,
    ]);

    if ($wasApproved) {
        $db->prepare("
            UPDATE _users
            SET status_user = 'active',
                plan_selected = ?,
                plan_activated_at = ?,
                plan_expires_at = ?,
                modif_user = NOW()
            WHERE id_users = ?
        ")->execute([
            $idPlan,
            $activatedAt,
            $expiresAt,
            $idUsers,
        ]);
    } else {
        $db->prepare("
            UPDATE _users
            SET status_user = 'active',
                plan_selected = ?,
                plan_activated_at = ?,
                plan_expires_at = ?,
                trust_score = LEAST(100, trust_score + 10),
                modif_user = NOW()
            WHERE id_users = ?
        ")->execute([
            $idPlan,
            $activatedAt,
            $expiresAt,
            $idUsers,
        ]);
    }

    return $paymentId;
}

function paymentWorkflowRejectPendingActivation(PDO $db, array $intent, ?int $reviewedBy = null, string $reason = ''): void
{
    $payment = paymentWorkflowFetchPaymentByReference($db, (string)$intent['reference_code']);

    if ($payment && $payment['status_payment'] !== 'approved') {
        $db->prepare("
            UPDATE _payment
            SET status_payment = 'rejected',
                rejection_reason = ?,
                reviewed_by = COALESCE(?, reviewed_by),
                reviewed_at = NOW()
            WHERE id_payment = ?
        ")->execute([
            $reason ?: null,
            $reviewedBy,
            $payment['id_payment'],
        ]);

        $db->prepare("
            UPDATE _user_plan
            SET status_plan = 'cancelled',
                modif_user_plan = NOW()
            WHERE id_payment = ? AND status_plan = 'pending_payment'
        ")->execute([$payment['id_payment']]);
    }

    $db->prepare("UPDATE _payment_intent SET status = 'rejected' WHERE id_intent = ?")
        ->execute([(int)$intent['id_intent']]);
}
