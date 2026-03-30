<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Acções AJAX de Pagamentos
// Arquivo: wu-panel-2026/pages/payments/process.php
// Rota:    wu-panel-2026/payments/process (POST only)
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

$receipts_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/payment/uploads/receipts';
if (!is_dir($receipts_dir)) {
    mkdir($receipts_dir, 0750, true);
}

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

$csrf_post = $_POST['csrf_token'] ?? '';
$csrf_session = $_SESSION['admin_csrf_token'] ?? '';
if (!$csrf_session || !hash_equals($csrf_session, $csrf_post)) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.');
}

$action = trim($_POST['action'] ?? '');
$id_payment = (int)($_POST['id_payment'] ?? 0);
if (!$id_payment) jsonOut(false, 'ID do pagamento inválido.');

// Buscar dados do pagamento
$stmt = $db->prepare("
    SELECT p.*, u.id_users, u.email_user, u.first_name, u.second_name, pl.name_plan
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    WHERE p.id_payment = ?
");
$stmt->execute([$id_payment]);
$pay = $stmt->fetch();
if (!$pay) jsonOut(false, 'Pagamento não encontrado.');

// ──────────────────────────────────────────────────────────────────────────────
// ACÇÃO: update_status (aprovado/rejeitado/reembolsado)
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'update_status') {
    requirePermission($admin_id, 'finances.edit');

    $new_status = trim($_POST['new_status'] ?? '');
    $reject_reason = trim($_POST['reject_reason'] ?? '');

    $allowed = ['pending', 'approved', 'rejected', 'refunded'];
    if (!in_array($new_status, $allowed, true)) jsonOut(false, 'Estado inválido.');
    if ($pay['status_payment'] === $new_status) jsonOut(false, 'O pagamento já está com este estado.');

    // Se for rejeitado e não houver motivo, pedir
    if ($new_status === 'rejected' && empty($reject_reason)) {
        jsonOut(false, 'É necessário um motivo para rejeitar o pagamento.');
    }

    try {
        $db->beginTransaction();

        // Actualizar _payment
        $db->prepare("
            UPDATE _payment
            SET status_payment = ?,
                rejection_reason = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id_payment = ?
        ")->execute([$new_status, $reject_reason ?: null, $admin_id, $id_payment]);

        // Se aprovado, activar plano (se ainda não estiver activo)
        if ($new_status === 'approved' && $pay['status_payment'] !== 'approved') {
            // Recuperar intent associado
            $intent_stmt = $db->prepare("SELECT id_intent FROM _payment_intent WHERE reference_code = ?");
            $intent_stmt->execute([$pay['payment_ref']]);
            $intent = $intent_stmt->fetch();
            if ($intent) {
                // Incluir função activatePlan
                require_once __DIR__ . '/../../../../dashboard/payment_process.php';
                activatePlan($pay['id_users'], $pay['id_plan'], $intent['id_intent'], $db);
            }
        }

        // Registrar auditoria
        $old_val = json_encode(['status' => $pay['status_payment']]);
        $new_val = json_encode(['status' => $new_status, 'reject_reason' => $reject_reason]);
        logAudit($admin_id, $pay['id_users'], 'payment.status_changed', '_payment', $id_payment, $old_val, $new_val);

        $db->commit();

        // Enviar notificação por e-mail ao utilizador
        $subject = "Actualização do seu pagamento - " . APP_NAME;
        $body = "<div style='font-family:Arial;max-width:540px;margin:auto'>";
        $body .= "<div style='background:#FF0089;padding:20px;text-align:center'><h1 style='color:#fff'>" . APP_NAME . "</h1></div>";
        $body .= "<div style='padding:20px;border:1px solid #eee;border-top:none'>";
        $body .= "<p>Olá " . htmlspecialchars(trim($pay['first_name'] . ' ' . $pay['second_name'])) . ",</p>";
        $body .= "<p>O estado do seu pagamento para o plano <strong>" . htmlspecialchars($pay['name_plan']) . "</strong> foi actualizado para <strong>" . ucfirst($new_status) . "</strong>.</p>";
        if ($new_status === 'rejected' && $reject_reason) {
            $body .= "<p><strong>Motivo:</strong> " . htmlspecialchars($reject_reason) . "</p>";
            $body .= "<p>Por favor, entre em contacto com o suporte ou realize um novo pagamento.</p>";
        } elseif ($new_status === 'approved') {
            $body .= "<p>O seu plano foi activado com sucesso. A partir de agora pode utilizar todos os recursos do seu plano.</p>";
        }
        $body .= "<hr><small>" . APP_NAME . "</small></div></div>";

        $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
        if (file_exists($mailer_path)) {
            if (!class_exists('\Wasom\Mailer')) require_once $mailer_path;
            try {
                $wm = new \Wasom\Mailer();
                $wm->host = MAIL_HOST;
                $wm->port = MAIL_PORT;
                $wm->secure = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
                $wm->username = MAIL_USER;
                $wm->password = MAIL_PASS;
                $wm->debug = 0;
                $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)->addAddress($pay['email_user'])->setSubject($subject)->setBody($body, strip_tags($body));
                $wm->send();
            } catch (\Wasom\MailerException $e) {
                error_log('[PAYMENT MAIL] ' . $e->getMessage());
            }
        }

        $msg = match ($new_status) {
            'approved' => 'Pagamento aprovado com sucesso! O plano foi activado.',
            'rejected' => 'Pagamento rejeitado. O utilizador foi notificado.',
            'refunded' => 'Pagamento marcado como reembolsado.',
            default    => 'Estado actualizado com sucesso!',
        };
        jsonOut(true, $msg);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[PAYMENT UPDATE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao actualizar estado.');
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// ACÇÃO: generate_receipt (gerar página HTML para impressão/PDF)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'generate_receipt') {
    requirePermission($admin_id, 'finances.view');

    // Buscar dados completos do pagamento
    $stmt = $db->prepare("
        SELECT p.*, u.first_name, u.second_name, u.email_user, u.tel_user,
               pl.name_plan, pl.type_plan, pl.validity_days,
               pr.file_path, pr.full_name AS proof_name, pr.phone AS proof_phone,
               pr.method AS proof_method, pr.uploaded_at
        FROM _payment p
        LEFT JOIN _users u ON u.id_users = p.id_users
        LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
        LEFT JOIN _payment_intent pi ON pi.reference_code = p.payment_ref
        LEFT JOIN _payment_proof pr ON pr.id_intent = pi.id_intent
        WHERE p.id_payment = ?
    ");
    $stmt->execute([$id_payment]);
    $pay_data = $stmt->fetch();
    if (!$pay_data) jsonOut(false, 'Dados do pagamento não encontrados.');

    // Preparar dados
    $fullname = trim(($pay_data['first_name'] ?? '') . ' ' . ($pay_data['second_name'] ?? ''));
    $payment_date = date('d/m/Y H:i', strtotime($pay_data['creat_payment']));
    $amount = number_format((float)$pay_data['amount'], 2) . ' ' . ($pay_data['currency'] ?? 'AOA');
    $receipt_number = str_pad($pay_data['id_payment'], 8, '0', STR_PAD_LEFT);
    $plan_name = $pay_data['name_plan'] ?? 'Plano não identificado';
    $method_label = match ($pay_data['payment_method']) {
        'bank_transfer' => 'Transferência Bancária',
        'multicaixa'    => 'Multicaixa Express',
        'paypal'        => 'PayPal',
        'card'          => 'Cartão de Crédito',
        default         => ucfirst($pay_data['payment_method']),
    };
    $reviewed_at = $pay_data['reviewed_at'] ? date('d/m/Y H:i', strtotime($pay_data['reviewed_at'])) : '';
    $uploaded_at = $pay_data['uploaded_at'] ? date('d/m/Y H:i', strtotime($pay_data['uploaded_at'])) : '';

    // Dados da empresa (podem vir da base de dados)
    $company_name = APP_NAME ?? 'Wasom Upfy';
    $company_nif = '5417020235';
    $company_address = 'Luanda, Angola';
    $company_phone = '+244 923 000 000';
    $company_email = 'suporte@wasomupfy.com';
    $company_website = 'wasomupfy.com';
    $current_date = date('d/m/Y H:i:s');

    // Usar Heredoc para construir o HTML sem problemas de escape
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-ao">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento #{$receipt_number}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #FF0089;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #FF0089;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #777;
        }
        .receipt-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        .row {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #eee;
            padding: 5px 0;
        }
        .label {
            font-weight: bold;
            width: 40%;
        }
        .value {
            width: 60%;
            text-align: right;
        }
        .total-row {
            margin-top: 15px;
            font-size: 16px;
            font-weight: bold;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .signature {
            margin-top: 30px;
            text-align: right;
            font-style: italic;
            font-size: 12px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; border: none; }
            .btn-print { display: none; }
        }
        .btn-print {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 16px;
            background: #FF0089;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print:hover { background: #d40073; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <h1>{$company_name}</h1>
            <p>{$company_address} | NIF: {$company_nif} | {$company_phone}</p>
            <p>{$company_email} | {$company_website}</p>
        </div>

        <div class="receipt-title">RECIBO DE PAGAMENTO</div>
        <div class="row"><div class="label">Número do Recibo:</div><div class="value">{$receipt_number}</div></div>
        <div class="row"><div class="label">Data:</div><div class="value">{$payment_date}</div></div>
        <div class="row"><div class="label">Referência do Pagamento:</div><div class="value">{$pay_data['payment_ref']}</div></div>

        <div style="margin: 20px 0 10px; font-weight: bold;">Dados do Cliente</div>
        <div class="row"><div class="label">Nome:</div><div class="value">{$fullname}</div></div>
        <div class="row"><div class="label">E-mail:</div><div class="value">{$pay_data['email_user']}</div></div>
HTML;

    // Adicionar telefone se existir
    if (!empty($pay_data['tel_user'])) {
        $html .= '<div class="row"><div class="label">Telefone:</div><div class="value">' . htmlspecialchars($pay_data['tel_user']) . '</div></div>';
    }

    $html .= <<<HTML
        <div style="margin: 20px 0 10px; font-weight: bold;">Detalhes do Pagamento</div>
        <div class="row"><div class="label">Plano:</div><div class="value">{$plan_name}</div></div>
        <div class="row"><div class="label">Método de Pagamento:</div><div class="value">{$method_label}</div></div>
        <div class="row"><div class="label">Valor:</div><div class="value">{$amount}</div></div>
HTML;

    if ($pay_data['status_payment'] === 'approved' && !empty($reviewed_at)) {
        $html .= '<div class="row"><div class="label">Aprovado em:</div><div class="value">' . $reviewed_at . '</div></div>';
    }
    if (!empty($uploaded_at)) {
        $html .= '<div class="row"><div class="label">Comprovativo enviado em:</div><div class="value">' . $uploaded_at . '</div></div>';
    }

    $html .= <<<HTML
        <div class="total-row">
            <div class="label">Total Pago:</div>
            <div class="value">{$amount}</div>
        </div>

        <div class="signature">
            ________________________________<br>
            {$company_name}
        </div>

        <div class="footer">
            Este documento é uma prova de pagamento válida para os efeitos legais.<br>
            Gerado automaticamente em {$company_name} em {$current_date}.
        </div>

        <div style="text-align: center;">
            <button class="btn-print" onclick="window.print();">Imprimir / Salvar como PDF</button>
        </div>
    </div>
</body>
</html>
HTML;

    // Salvar o arquivo HTML temporário
    $receipts_dir = dirname(__DIR__, 3) . '/assets/payment/uploads/receipts';
    if (!is_dir($receipts_dir)) mkdir($receipts_dir, 0750, true);
    $filename = 'receipt_' . $id_payment . '_' . time() . '.html';
    $file_path = '/assets/payment/uploads/receipts/' . $filename;
    $full_path = $receipts_dir . '/' . $filename;
    file_put_contents($full_path, $html);

    jsonOut(true, 'Recibo gerado com sucesso.', ['pdf_url' => APP_URL . $file_path]);
}


// ═══════════════════════════════════════════════════════════════════════════════
// ACÇÃO: email_receipt (enviar recibo por e-mail como HTML)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'email_receipt') {
    requirePermission($admin_id, 'finances.edit');

    // Buscar dados completos (igual à ação anterior)
    $stmt = $db->prepare("
        SELECT p.*, u.first_name, u.second_name, u.email_user, u.tel_user,
               pl.name_plan, pl.type_plan, pl.validity_days,
               pr.file_path, pr.full_name AS proof_name, pr.phone AS proof_phone,
               pr.method AS proof_method, pr.uploaded_at
        FROM _payment p
        LEFT JOIN _users u ON u.id_users = p.id_users
        LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
        LEFT JOIN _payment_intent pi ON pi.reference_code = p.payment_ref
        LEFT JOIN _payment_proof pr ON pr.id_intent = pi.id_intent
        WHERE p.id_payment = ?
    ");
    $stmt->execute([$id_payment]);
    $pay_data = $stmt->fetch();
    if (!$pay_data) jsonOut(false, 'Dados do pagamento não encontrados.');

    // Preparar dados
    $fullname = trim(($pay_data['first_name'] ?? '') . ' ' . ($pay_data['second_name'] ?? ''));
    $payment_date = date('d/m/Y H:i', strtotime($pay_data['creat_payment']));
    $amount = number_format((float)$pay_data['amount'], 2) . ' ' . ($pay_data['currency'] ?? 'AOA');
    $receipt_number = str_pad($pay_data['id_payment'], 8, '0', STR_PAD_LEFT);
    $plan_name = $pay_data['name_plan'] ?? 'Plano não identificado';
    $method_label = match ($pay_data['payment_method']) {
        'bank_transfer' => 'Transferência Bancária',
        'multicaixa'    => 'Multicaixa Express',
        'paypal'        => 'PayPal',
        'card'          => 'Cartão de Crédito',
        default         => ucfirst($pay_data['payment_method']),
    };
    $reviewed_at = $pay_data['reviewed_at'] ? date('d/m/Y H:i', strtotime($pay_data['reviewed_at'])) : '';
    $uploaded_at = $pay_data['uploaded_at'] ? date('d/m/Y H:i', strtotime($pay_data['uploaded_at'])) : '';

    $company_name = APP_NAME ?? 'Wasom Upfy';
    $company_nif = '5417020235';
    $company_address = 'Luanda, Angola';
    $company_phone = '+244 923 000 000';
    $company_email = 'suporte@wasomupfy.com';
    $company_website = 'wasomupfy.com';
    $current_date = date('d/m/Y H:i:s');

    // Construir HTML do recibo (versão compacta para e-mail, sem botão de impressão)
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Recibo de Pagamento #{$receipt_number}</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; margin:0; padding:20px; background:#f5f5f5; }
    .receipt-container { max-width:800px; margin:0 auto; background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; }
    .header { text-align:center; border-bottom:2px solid #FF0089; padding-bottom:15px; margin-bottom:20px; }
    .header h1 { margin:0; color:#FF0089; font-size:24px; }
    .header p { margin:5px 0 0; font-size:12px; color:#777; }
    .receipt-title { text-align:center; font-size:18px; font-weight:bold; margin:20px 0; }
    .row { margin-bottom:10px; display:flex; justify-content:space-between; border-bottom:1px dashed #eee; padding:5px 0; }
    .label { font-weight:bold; width:40%; }
    .value { width:60%; text-align:right; }
    .total-row { margin-top:15px; font-size:16px; font-weight:bold; background:#f9f9f9; padding:10px; border-radius:4px; }
    .footer { margin-top:30px; text-align:center; font-size:10px; color:#aaa; border-top:1px solid #eee; padding-top:15px; }
    .signature { margin-top:30px; text-align:right; font-style:italic; font-size:12px; }
</style>
</head>
<body>
<div class="receipt-container">
    <div class="header">
        <h1>{$company_name}</h1>
        <p>{$company_address} | NIF: {$company_nif} | {$company_phone}</p>
        <p>{$company_email} | {$company_website}</p>
    </div>
    <div class="receipt-title">RECIBO DE PAGAMENTO</div>
    <div class="row"><div class="label">Número do Recibo:</div><div class="value">{$receipt_number}</div></div>
    <div class="row"><div class="label">Data:</div><div class="value">{$payment_date}</div></div>
    <div class="row"><div class="label">Referência:</div><div class="value">{$pay_data['payment_ref']}</div></div>
    <div style="margin:20px 0 10px; font-weight:bold;">Dados do Cliente</div>
    <div class="row"><div class="label">Nome:</div><div class="value">{$fullname}</div></div>
    <div class="row"><div class="label">E-mail:</div><div class="value">{$pay_data['email_user']}</div></div>
HTML;

    if (!empty($pay_data['tel_user'])) {
        $html .= '<div class="row"><div class="label">Telefone:</div><div class="value">' . htmlspecialchars($pay_data['tel_user']) . '</div></div>';
    }

    $html .= <<<HTML
    <div style="margin:20px 0 10px; font-weight:bold;">Detalhes do Pagamento</div>
    <div class="row"><div class="label">Plano:</div><div class="value">{$plan_name}</div></div>
    <div class="row"><div class="label">Método:</div><div class="value">{$method_label}</div></div>
    <div class="row"><div class="label">Valor:</div><div class="value">{$amount}</div></div>
HTML;

    if ($pay_data['status_payment'] === 'approved' && !empty($reviewed_at)) {
        $html .= '<div class="row"><div class="label">Aprovado em:</div><div class="value">' . $reviewed_at . '</div></div>';
    }
    if (!empty($uploaded_at)) {
        $html .= '<div class="row"><div class="label">Comprovativo enviado em:</div><div class="value">' . $uploaded_at . '</div></div>';
    }

    $html .= <<<HTML
    <div class="total-row"><div class="label">Total Pago:</div><div class="value">{$amount}</div></div>
    <div class="signature">________________________________<br>{$company_name}</div>
    <div class="footer">Este documento é uma prova de pagamento válida. Gerado em {$current_date}.</div>
</div>
</body>
</html>
HTML;

    // Enviar e-mail usando WasomMailer
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (!file_exists($mailer_path)) {
        jsonOut(false, 'Sistema de e-mail não configurado.');
    }
    require_once $mailer_path;

    try {
        $mail = new \Wasom\Mailer();
        $mail->host     = MAIL_HOST;
        $mail->port     = MAIL_PORT;
        $mail->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
        $mail->username = MAIL_USER;
        $mail->password = MAIL_PASS;
        $mail->debug    = 0;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($pay_data['email_user'], $fullname);
        $mail->setSubject('Recibo de pagamento - ' . APP_NAME);
        $mail->setBody($html, strip_tags($html));
        $mail->send();

        jsonOut(true, 'Recibo enviado por e-mail com sucesso.');
    } catch (\Wasom\MailerException $e) {
        error_log('[RECEIPT EMAIL] ' . $e->getMessage());
        jsonOut(false, 'Erro ao enviar e-mail: ' . $e->getMessage());
    }
}