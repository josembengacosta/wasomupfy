<?php
// ════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Sistema de Alertas do Painel
// Arquivo: dashboard/include/alert.php
// ════════════════════════════════════════════════════════════════

/**
 * Mapa de cores para os alertas (consistente com renderDashboardAlerts)
 */
$alertColors = [
    'danger'  => ['bg' => 'rgba(239,68,68,.08)',  'border' => 'rgba(239,68,68,.25)',  'text' => '#ef4444'],
    'warning' => ['bg' => 'rgba(234,179,8,.08)',  'border' => 'rgba(234,179,8,.25)',  'text' => '#eab308'],
    'info'    => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
];

/**
 * Exibe um alerta estilizado inline.
 */
function wuAlert(string $type, string $icon, string $message, ?array $action = null, bool $dismiss = true, string $id = ''): void
{
    global $alertColors;
    $c   = $alertColors[$type] ?? $alertColors['info'];
    $eid = $id ?: ('wuPanelAlert_' . md5($message));
    echo "<div id=\"{$eid}\" style=\"display:flex;align-items:flex-start;gap:10px;"
        . "background:{$c['bg']};border:1px solid {$c['border']};border-radius:12px;"
        . "padding:.75rem 1rem;font-size:.83rem;margin-bottom:.6rem;"
        . "transition:opacity .3s;\">";
    echo "<i class=\"bi {$icon}\" style=\"font-size:1rem;flex-shrink:0;margin-top:2px;color:{$c['text']};\"></i>";
    echo '<span class="wu-alert-msg">' . $message;
    if ($action) {
        echo " <a href=\"{$action['url']}\" style=\"color:{$c['text']};font-weight:700;"
            . "text-decoration:underline;white-space:nowrap\">{$action['label']} &rarr;</a>";
    }
    echo '</span>';
    if ($dismiss) {
        echo "<button type=\"button\" class=\"wu-alert-dismiss\" aria-label=\"Fechar\""
            . " onclick=\"(function(el){el.style.opacity='0';"
            . "setTimeout(function(){el.style.display='none'},300)})(document.getElementById('{$eid}'))\">"
            . "&times;</button>";
    }
    echo '</div>';
}

/**
 * Renderiza todos os alertas do painel com base no estado do utilizador.
 * 
 * @param array $user        Dados do utilizador (deve conter email_verified, name_artist_band, etc.)
 * @param array $plan        Dados do plano (ou null)
 * @param bool  $plan_paid   Se o plano está pago/ativo
 * @param array $bank_account Conta bancária verificada (ou null)
 * @param PDO   $db          Conexão à base de dados
 * @param int   $id_users    ID do utilizador
 */
function renderPanelAlerts(array $user, ?array $plan, bool $plan_paid, ?array $bank_account, PDO $db, int $id_users): void
{
    $email_verified = (bool)($user['email_verified'] ?? false);

    // 1. Email não verificado (crítico)
    if (!$email_verified) {
        wuAlert(
            'danger',
            'bi-envelope-exclamation-fill',
            '<strong>Email não verificado.</strong> Verifica o teu e-mail para garantir o acesso à conta e receber notificações de pagamentos.',
            ['label' => 'Verificar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/user/profile#perfil'],
            true,
            'banner-email'
        );
    }

    // 2. Plano pendente ou sem plano
    if ($plan && !$plan_paid) {
        wuAlert(
            'warning',
            'bi-clock-history',
            '<strong>Pagamento pendente — ' . htmlspecialchars($plan['name_plan']) . '.</strong> O plano foi seleccionado mas o pagamento ainda não foi confirmado. Os teus lançamentos estão pausados até confirmação.',
            ['label' => 'Finalizar pagamento', 'url' => APP_URL . '/' . APP_URL_PANEL . '/payment/pay'],
            true,
            'banner-plan-pending'
        );
    } elseif (!$plan) {
        wuAlert(
            'danger',
            'bi-credit-card-fill',
            '<strong>Sem plano activo.</strong> Escolhe um plano para começar a distribuir a tua música para +150 plataformas.',
            ['label' => 'Ver planos', 'url' => APP_URL . '/' . APP_URL_PANEL . '/all-plans'],
            false,
            'banner-plan'
        );
    }

    // Só prossegue com os alertas de perfil artístico e conta se o plano estiver pago
    if (!$plan_paid) {
        return;
    }

    // 3. Nome artístico do responsável
    $user_artist_name = trim($user['name_artist_band'] ?? '');
    $needs_main_artist = empty($user_artist_name);

    // 4. Verificar artistas para lançamento
    $as = $db->prepare('SELECT COUNT(*) FROM _artist WHERE id_users = ?');
    $as->execute([$id_users]);
    $total_artists = (int)$as->fetchColumn();
    $has_any_artist = $total_artists > 0;

    // 5. Limite de artistas do plano
    $max_artists = isset($plan['max_artists']) ? (int)$plan['max_artists'] : 1;
    $can_add_more_artists = $total_artists < $max_artists;

    if ($needs_main_artist) {
        wuAlert(
            'info',
            'bi-person-badge-fill',
            '<strong>Configura o teu nome artístico.</strong> Define o nome artístico principal da tua conta.',
            ['label' => 'Configurar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/account/manage-account'],
            true,
            'banner-main-artist'
        );
    } elseif (!$has_any_artist) {
        wuAlert(
            'info',
            'bi-person-plus-fill',
            '<strong>Cria o primeiro perfil artístico.</strong> Tens plano ativo mas ainda não criaste um artista para lançar música.',
            ['label' => 'Criar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/add-artist'],
            true,
            'banner-artist'
        );
    } elseif ($can_add_more_artists) {
        wuAlert(
            'info',
            'bi-person-add',
            '<strong>Podes adicionar mais artistas.</strong> O teu plano permite até ' . $max_artists . ' perfil(ns) artístico(s).',
            ['label' => 'Adicionar artista', 'url' => APP_URL . '/' . APP_URL_PANEL . '/add-artist'],
            true,
            'banner-add-artist'
        );
    }

    // 6. Conta bancária não registada
    if ($has_any_artist && !$bank_account) {
        wuAlert(
            'info',
            'bi-bank',
            '<strong>Conta bancária não registada.</strong> Para poder sacar os teus royalties, regista uma conta IBAN ou Multicaixa Express.',
            ['label' => 'Registar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/withdraw'],
            true,
            'banner-bank'
        );
    }

    // 7. Conta bancária rejeitada
    $rej_stmt = $db->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
    $rej_stmt->execute([$id_users]);
    $rejected_account = $rej_stmt->fetch();

    if ($rejected_account) {
        $rej_msg = '<strong>Conta ' . htmlspecialchars($rejected_account['type_account']) . ' rejeitada.</strong>';
        if ($rejected_account['reject_reason']) {
            $rej_msg .= ' Motivo: <em>' . htmlspecialchars($rejected_account['reject_reason']) . '</em>.';
        }
        $rej_msg .= ' Actualiza os dados e submete novamente.';
        wuAlert(
            'danger',
            'bi-x-circle-fill',
            $rej_msg,
            ['label' => 'Corrigir agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/withdraw'],
            true,
            'banner-account-rejected'
        );
    }
}