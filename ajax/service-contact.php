<?php
// ══════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — AJAX: Processar Contacto de Serviços
// Arquivo: ajax/service-contact.php
// ══════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

// ── CSRF ─────────────────────────────────────────────────────────
if (!validateCsrf($data['csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarrega a página.']);
    exit;
}

// ── Campos base obrigatórios ──────────────────────────────────────
$mode  = in_array($data['mode'] ?? '', ['free_analysis','consultant_initial','consultant_talk','meeting_schedule','general'])
       ? $data['mode'] : 'general';
$name  = trim($data['name']  ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$phone = trim($data['phone'] ?? '');

if (empty($name) || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Por favor insere o teu nome completo.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Endereço de e-mail inválido.']);
    exit;
}

// ── Campos opcionais / por modo ───────────────────────────────────
$artistName  = trim($data['artist_name']  ?? '');
$genre       = trim($data['genre']        ?? '');
$numArtists  = is_numeric($data['num_artists'] ?? '') ? (int)$data['num_artists'] : null;
$budgetRange = trim($data['budget_range'] ?? '');
$message     = trim($data['message']      ?? '');

// Links sociais (JSON)
$socialLinks = null;
if (!empty($data['links'])) {
    $links = array_filter([
        'spotify'   => trim($data['links']['spotify']   ?? ''),
        'instagram' => trim($data['links']['instagram'] ?? ''),
        'tiktok'    => trim($data['links']['tiktok']    ?? ''),
        'youtube'   => trim($data['links']['youtube']   ?? ''),
        'other'     => trim($data['links']['other']     ?? ''),
    ]);
    if ($links) $socialLinks = json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Agendamento
$preferredDate = null;
$preferredTime = null;
$meetingType   = null;

if (in_array($mode, ['consultant_talk', 'meeting_schedule'])) {
    $rawDate = trim($data['preferred_date'] ?? '');
    if ($rawDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        $dt = DateTime::createFromFormat('Y-m-d', $rawDate);
        $tomorrow = new DateTime('tomorrow');
        if ($dt && $dt >= $tomorrow) {
            $preferredDate = $rawDate;
        }
    }
    $rawTime = trim($data['preferred_time'] ?? '');
    $validTimes = ['09:00','10:00','11:00','14:00','15:00','16:00','17:00'];
    if (in_array($rawTime, $validTimes)) {
        $preferredTime = $rawTime;
    }
    if ($mode === 'meeting_schedule') {
        $mt = $data['meeting_type'] ?? '';
        if (in_array($mt, ['online','presencial'])) $meetingType = $mt;
    }
}

// Assunto calculado
$subjectMap = [
    'free_analysis'      => 'Análise Gratuita de Música',
    'consultant_initial' => 'Consultor — Pacote Impulso Single',
    'consultant_talk'    => 'Consultor — Campanha 360°',
    'meeting_schedule'   => 'Reunião — Gestão de Label',
    'general'            => trim($data['subject'] ?? 'Contacto Geral'),
];
$subject = $subjectMap[$mode];

// ── Gravar na DB ──────────────────────────────────────────────────
try {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO _service_contacts
            (mode, name_contact, email_contact, phone_contact,
             artist_name, music_genre, num_artists, social_links,
             budget_range, preferred_date, preferred_time, meeting_type,
             message_contact, subject_contact, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $mode,
        $name,
        $email,
        $phone ?: null,
        $artistName ?: null,
        $genre ?: null,
        $numArtists,
        $socialLinks,
        $budgetRange ?: null,
        $preferredDate,
        $preferredTime,
        $meetingType,
        $message ?: null,
        $subject,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);

    $contactId = $db->lastInsertId();

} catch (Exception $e) {
    error_log('[SERVICE CONTACT ERROR] DB: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar. Tenta novamente ou contacta-nos pelo WhatsApp.']);
    exit;
}

// ── Enviar email de confirmação ao utilizador ─────────────────────
$confirmBody = "
<div style='font-family:Arial,sans-serif;max-width:560px;margin:auto'>
  <div style='background:#FF009D;padding:24px;border-radius:8px 8px 0 0;text-align:center'>
    <h2 style='color:#fff;margin:0'>" . htmlspecialchars(cfg('site_name','Wasom Upfy')) . "</h2>
  </div>
  <div style='background:#f9f9f9;padding:28px;border-radius:0 0 8px 8px'>
    <h3 style='color:#222'>Recebemos o teu pedido! ✅</h3>
    <p>Olá <strong>" . htmlspecialchars($name) . "</strong>,</p>
    <p>Recebemos o teu pedido de <strong>" . htmlspecialchars($subject) . "</strong> com o nº de referência <strong>#" . str_pad($contactId, 5, '0', STR_PAD_LEFT) . "</strong>.</p>";

if ($preferredDate) {
    $confirmBody .= "<p>A tua preferência de data é <strong>" . date('d/m/Y', strtotime($preferredDate)) . "</strong>"
        . ($preferredTime ? " às <strong>" . $preferredTime . "</strong>" : '') . ".</p>"
        . "<p><em>A nossa equipa confirmará o agendamento em até 24h úteis.</em></p>";
} else {
    $confirmBody .= "<p>A nossa equipa entrará em contacto em até <strong>24-48h úteis</strong>.</p>";
}

$confirmBody .= "
    <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
    <p style='color:#888;font-size:13px'>
      Enquanto aguardas, podes visitar a nossa <a href='" . cfg('site_url','https://wasomupfy.rf.gd') . "/page/support/faq'>página de perguntas frequentes</a>.
    </p>
    <p style='color:#aaa;font-size:12px;margin-top:20px'>Wasom Upfy — Este é um e-mail automático, não respondas.</p>
  </div>
</div>";

sendEmail($email, 'Recebemos o teu pedido — ' . cfg('site_name','Wasom Upfy'), $confirmBody);

// ── Notificar equipa interna ──────────────────────────────────────
$internalEmail = cfg('support_email', 'suporte@wasomupfy.com');
$internalBody  = "
<div style='font-family:monospace;font-size:13px;max-width:600px;margin:auto'>
  <h3>Novo contacto de serviço — #{$contactId}</h3>
  <table cellpadding='6' style='border-collapse:collapse;width:100%'>
    <tr><td style='color:#888;width:140px'>Modo:</td><td><strong>{$mode}</strong></td></tr>
    <tr><td style='color:#888'>Assunto:</td><td>{$subject}</td></tr>
    <tr><td style='color:#888'>Nome:</td><td>{$name}</td></tr>
    <tr><td style='color:#888'>Email:</td><td>{$email}</td></tr>
    <tr><td style='color:#888'>WhatsApp:</td><td>" . ($phone ?: '—') . "</td></tr>
    <tr><td style='color:#888'>Artista:</td><td>" . ($artistName ?: '—') . "</td></tr>
    <tr><td style='color:#888'>Género:</td><td>" . ($genre ?: '—') . "</td></tr>
    <tr><td style='color:#888'>Budget:</td><td>" . ($budgetRange ?: '—') . "</td></tr>"
    . ($preferredDate ? "<tr><td style='color:#888'>Data Pref.:</td><td>{$preferredDate} {$preferredTime}</td></tr>" : '')
    . ($meetingType   ? "<tr><td style='color:#888'>Reunião:</td><td>{$meetingType}</td></tr>" : '') . "
    <tr><td style='color:#888'>Mensagem:</td><td>" . nl2br(htmlspecialchars($message ?: '—')) . "</td></tr>
  </table>
  " . ($socialLinks ? "<p><strong>Links:</strong> {$socialLinks}</p>" : '') . "
  <p style='color:#aaa;font-size:11px'>IP: " . ($_SERVER['REMOTE_ADDR'] ?? '?') . " | " . date('d/m/Y H:i') . "</p>
</div>";

sendEmail($internalEmail, "[Wasom Upfy] Novo contacto: {$subject} #{$contactId}", $internalBody);

// ── Resposta final ────────────────────────────────────────────────
$messages = [
    'free_analysis'      => 'Pedido de análise recebido! Entraremos em contacto em até 48h. Verifica o teu e-mail.',
    'consultant_initial' => 'Pedido recebido! A nossa equipa de consultoria entrará em contacto em breve.',
    'consultant_talk'    => 'Ótimo! Recebemos o pedido. Confirmaremos o agendamento em até 24h úteis.',
    'meeting_schedule'   => 'Reunião solicitada com sucesso! Confirmaremos em até 24h úteis.',
    'general'            => 'Mensagem enviada com sucesso! Respondemos em até 48h.',
];

echo json_encode([
    'success'    => true,
    'message'    => $messages[$mode],
    'contact_id' => '#' . str_pad($contactId, 5, '0', STR_PAD_LEFT),
]);