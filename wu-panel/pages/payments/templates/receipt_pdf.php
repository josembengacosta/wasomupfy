<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Template do Recibo de Pagamento (PDF)
// Usado pela ação generate_receipt em process.php
// ═══════════════════════════════════════════════════════════════════════════════
// Variáveis esperadas:
// $pay: dados do pagamento (id_payment, payment_ref, amount, currency, payment_method, creat_payment, etc.)
// $user: dados do utilizador (id_users, first_name, second_name, email_user, tel_user)
// $plan: dados do plano (name_plan, type_plan, validity_days)
// $proof: dados do comprovativo (file_path, proof_name, proof_phone, proof_method, uploaded_at)
// $admin: dados do administrador que aprovou (first_name, second_name) – opcional
// $company: dados da empresa (nome, NIF, endereço, etc.)

// Configurações da empresa (podem vir da base de dados)
$company_name = APP_NAME ?? 'Wasom Upfy';
$company_nif = '5417020235'; // NIF fictício, ajustar conforme real
$company_address = 'Luanda, Angola';
$company_phone = '+244 975 818 046';
$company_email = 'suporte@wasomupfy.com';
$company_website = 'wasomupfy.rf.gd';

$fullname = trim(($user['first_name'] ?? '') . ' ' . ($user['second_name'] ?? ''));
$payment_date = date('d/m/Y H:i', strtotime($pay['creat_payment']));
$amount_formatted = number_format((float)$pay['amount'], 2) . ' ' . ($pay['currency'] ?? 'AOA');
$receipt_number = str_pad($pay['id_payment'], 8, '0', STR_PAD_LEFT);
$plan_name = $plan['name_plan'] ?? 'Plano não identificado';
$method_label = match ($pay['payment_method']) {
    'bank_transfer' => 'Transferência Bancária',
    'multicaixa'    => 'Multicaixa Express',
    'paypal'        => 'PayPal',
    'card'          => 'Cartão de Crédito',
    default         => ucfirst($pay['payment_method']),
};
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento #<?php echo $receipt_number; ?></title>
    <style>
    body {
        font-family: 'DejaVu Sans', 'Arial', sans-serif;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #e8e8f0;
        border-radius: 8px;
        padding: 20px;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($company_name); ?></h1>
            <p><?php echo htmlspecialchars($company_address); ?> | NIF: <?php echo $company_nif; ?> |
                <?php echo htmlspecialchars($company_phone); ?></p>
            <p><?php echo htmlspecialchars($company_email); ?> | <?php echo htmlspecialchars($company_website); ?></p>
        </div>

        <div class="receipt-title">RECIBO DE PAGAMENTO</div>
        <div class="row">
            <div class="label">Número do Recibo:</div>
            <div class="value"><?php echo $receipt_number; ?></div>
        </div>
        <div class="row">
            <div class="label">Data:</div>
            <div class="value"><?php echo $payment_date; ?></div>
        </div>
        <div class="row">
            <div class="label">Referência do Pagamento:</div>
            <div class="value"><?php echo htmlspecialchars($pay['payment_ref']); ?></div>
        </div>

        <div style="margin: 20px 0 10px; font-weight: bold;">Dados do Cliente</div>
        <div class="row">
            <div class="label">Nome:</div>
            <div class="value"><?php echo htmlspecialchars($fullname); ?></div>
        </div>
        <div class="row">
            <div class="label">E-mail:</div>
            <div class="value"><?php echo htmlspecialchars($user['email_user'] ?? ''); ?></div>
        </div>
        <?php if (!empty($user['tel_user'])): ?>
        <div class="row">
            <div class="label">Telefone:</div>
            <div class="value"><?php echo htmlspecialchars($user['tel_user']); ?></div>
        </div>
        <?php endif; ?>

        <div style="margin: 20px 0 10px; font-weight: bold;">Detalhes do Pagamento</div>
        <div class="row">
            <div class="label">Plano:</div>
            <div class="value"><?php echo htmlspecialchars($plan_name); ?></div>
        </div>
        <div class="row">
            <div class="label">Método de Pagamento:</div>
            <div class="value"><?php echo $method_label; ?></div>
        </div>
        <div class="row">
            <div class="label">Valor:</div>
            <div class="value"><?php echo $amount_formatted; ?></div>
        </div>

        <?php if ($pay['status_payment'] === 'approved' && !empty($pay['reviewed_at'])): ?>
        <div class="row">
            <div class="label">Aprovado em:</div>
            <div class="value"><?php echo date('d/m/Y H:i', strtotime($pay['reviewed_at'])); ?></div>
        </div>
        <?php endif; ?>

        <?php if ($proof && !empty($proof['uploaded_at'])): ?>
        <div class="row">
            <div class="label">Comprovativo enviado em:</div>
            <div class="value"><?php echo date('d/m/Y H:i', strtotime($proof['uploaded_at'])); ?></div>
        </div>
        <?php endif; ?>

        <div class="total-row">
            <div class="label">Total Pago:</div>
            <div class="value"><?php echo $amount_formatted; ?></div>
        </div>

        <div class="signature">
            ________________________________<br>
            <?php echo htmlspecialchars($company_name); ?>
        </div>

        <div class="footer">
            Este documento é uma prova de pagamento válida para os efeitos legais.<br>
            Gerado automaticamente em <?php echo date('d/m/Y H:i:s'); ?>.
        </div>
    </div>
</body>

</html>