<?php
// ══════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Dashboard Runtime Layer
// Arquivo: dashboard/include/platform.php
//
// ARQUITECTURA:
//   authentic/include/functions.php  → auth / segurança / DB
//   dashboard/include/platform.php   → lógica de runtime do dashboard
//   include/site.php                 → lógica do site público
//
// DEPENDÊNCIAS:
//   Requer functions.php já incluído (usa getDB()).
//
// INCLUIR logo após functions.php em cada página do dashboard:
//   require_once __DIR__ . '//dashboard/authentic/include/functions.php';
//   require_once __DIR__ . '/http://localhost/wasomupfy/dashboard/include/platform.php';
//   startSecureSession();
//   checkRememberMe();
//   requireLogin();
//   checkDashboardStatus();
//   $user = checkUserAccess($_SESSION['id_users']);
// ══════════════════════════════════════════════════════════════════


// ══════════════════════════════════════════════════════════════════
// 1. _platform — estado e configuração do DASHBOARD
//    Lê a coluna `status` (não site_status — essa é do site público).
//    Auto-expiry: se maintenance/blocked e maintenance_end já passou,
//    restaura automaticamente para 'active'.
// ══════════════════════════════════════════════════════════════════
function getDashboardPlatform(): array
{
    static $p = null;
    if ($p !== null) return $p;

    try {
        $db = getDB();
        $p  = $db->query("SELECT * FROM _platform ORDER BY id_platform ASC LIMIT 1")->fetch();
    } catch (Throwable $e) {
        error_log('[getDashboardPlatform] BD indisponível: ' . $e->getMessage());
    }

    // Defaults seguros se BD estiver em baixo
    if (!$p) {
        $p = [
            'status'                  => 'active',
            'site_status'             => 'active',
            'allow_register'          => 1,
            'allow_login'             => 1,
            'royalty_percentage'      => 90.00,
            'platform_fee'            => 10.00,
            'currency_default'        => 'AOA',
            'usd_to_aoa_rate'         => 900.00,
            'contact_email'           => 'suporte@wasomupfy.com',
            'stores_count'            => 150,
            'version'                 => '2.0',
            'maintenance_msg'         => null,
            'maintenance_start'       => null,
            'maintenance_end'         => null,
            'maintenance_services'    => null,
        ];
        return $p;
    }

    // ── Auto-expiry do DASHBOARD (coluna `status`) ────────────────
    // Não toca em site_status — são independentes.
    $expirable = ['maintenance', 'blocked'];
    if (
        in_array($p['status'], $expirable, true) &&
        !empty($p['maintenance_end']) &&
        strtotime($p['maintenance_end']) <= time()
    ) {
        try {
            $db->prepare("
                UPDATE _platform SET
                    status                = 'active',
                    maintenance_msg       = NULL,
                    maintenance_start     = NULL,
                    maintenance_end       = NULL,
                    maintenance_services  = NULL,
                    modif_platform        = NOW()
                WHERE id_platform = ?
            ")->execute([$p['id_platform']]);

            $p['status']               = 'active';
            $p['maintenance_msg']      = null;
            $p['maintenance_start']    = null;
            $p['maintenance_end']      = null;
            $p['maintenance_services'] = null;

            error_log('[getDashboardPlatform] Auto-expiry: status restaurado para active.');
        } catch (Throwable $e) {
            error_log('[getDashboardPlatform] Auto-expiry falhou: ' . $e->getMessage());
        }
    }

    return $p;
}


// ══════════════════════════════════════════════════════════════════
// 2. Verificar estado do dashboard + redirecionar se necessário
//    Chamar logo após requireLogin() em todas as páginas.
//
//    $current_page — identificador da página actual para evitar
//    loops em páginas de estado (ex: 'maintenance', '403', etc.).
// ══════════════════════════════════════════════════════════════════
function checkDashboardStatus(string $current_page = ''): array
{
    // Páginas de estado nunca redireccionam (evita loop infinito)
    $status_pages = ['maintenance', '403', '404', '500', '503', 'unauthorized'];
    if (in_array($current_page, $status_pages, true)) {
        return getDashboardPlatform();
    }

    $p  = getDashboardPlatform();
    $st = $p['status'] ?? 'active';

    if ($st === 'maintenance') {
        header('Location: http://localhost/wasomupfy/dashboard/status/maintenance');
        exit;
    }

    if ($st === 'blocked') {
        header('Location: http://localhost/wasomupfy/dashboard/status/503');
        exit;
    }

    if ($st === 'unauthorized') {
        header('Location: http://localhost/wasomupfy/dashboard/status/403');
        exit;
    }

    return $p;
}


// ══════════════════════════════════════════════════════════════════
// 3. Estado do utilizador
//    Lê _users + _user_plan e devolve um array enriquecido com
//    campos calculados: plan_active, plan_days_left, etc.
//    Verifica também expiração de plano e actualiza status_user.
// ══════════════════════════════════════════════════════════════════
function getUserStatus(int $id_users): ?array
{
    try {
        $db   = getDB();
        // NOTA: _user_plan não tem coluna `status`.
        // Usamos subquery para apanhar apenas o registo mais recente da _user_plan.
        $stmt = $db->prepare("
            SELECT
                u.*,
                p.name_plan,
                p.type_plan,
                p.price_plan,
                p.billing_plan,
                up.releases_used,
                up.releases_limit,
                up.started_at  AS plan_started_at,
                up.expires_at  AS plan_expires_at_up
            FROM _users u
            LEFT JOIN _plans p
                ON p.id_plan = u.plan_selected
            LEFT JOIN _user_plan up
                ON up.id_users = u.id_users
               AND up.started_at = (
                       SELECT MAX(started_at) FROM _user_plan
                       WHERE id_users = u.id_users
                   )
            WHERE u.id_users = ?
            LIMIT 1
        ");
        $stmt->execute([$id_users]);
        $user = $stmt->fetch();

        // Fallback: se o JOIN falhou (ex: utilizador sem registo em _user_plan),
        // buscar apenas os dados base do utilizador e devolver com defaults seguros.
        if (!$user) {
            $user = getUserById($id_users);
            if (!$user) return null;
            $user['plan_active']    = false;
            $user['plan_days_left'] = null;
            $user['plan_expired']   = false;
            $user['name_plan']      = null;
            $user['type_plan']      = null;
            $user['price_plan']     = null;
            $user['billing_plan']   = null;
            $user['releases_used']  = 0;
            $user['releases_limit'] = 0;
            return $user;
        }

        // ── Campos calculados ──────────────────────────────────────
        $now = time();

        // Data de expiração: preferir plan_expires_at da _users
        $expires_ts = !empty($user['plan_expires_at'])
            ? strtotime($user['plan_expires_at'])
            : (!empty($user['plan_expires_at_up']) ? strtotime($user['plan_expires_at_up']) : null);

        $user['plan_active']    = false;
        $user['plan_days_left'] = null;
        $user['plan_expired']   = false;

        if ($expires_ts !== null) {
            $user['plan_days_left'] = (int)ceil(($expires_ts - $now) / 86400);
            $user['plan_active']    = ($expires_ts > $now) && ($user['status_user'] === 'active');
            $user['plan_expired']   = $expires_ts <= $now;
        } elseif (!empty($user['plan_selected'])) {
            // Plano por lançamento (per_release) — sem data de expiração
            $user['plan_active']  = ($user['status_user'] === 'active');
            $user['plan_expired'] = false;
        }

        // ── Auto-expiração do plano ───────────────────────────────
        // Se o plano expirou e o status_user ainda está 'active',
        // actualiza para 'pending_plan' automaticamente.
        if (
            $user['plan_expired'] &&
            $user['status_user'] === 'active' &&
            !empty($user['plan_selected'])
        ) {
            try {
                $db->prepare("
                    UPDATE _users
                    SET status_user = 'pending_plan', modif_user = NOW()
                    WHERE id_users = ?
                ")->execute([$id_users]);
                $user['status_user'] = 'pending_plan';
                $user['plan_active'] = false;
            } catch (Throwable $e) {
                error_log('[getUserStatus] Auto-expiração plano falhou: ' . $e->getMessage());
            }
        }

        return $user;
    } catch (Throwable $e) {
        error_log('[getUserStatus] ' . $e->getMessage());
        return null;
    }
}


// ══════════════════════════════════════════════════════════════════
// 4. Verificar acesso do utilizador ao dashboard
//    Chama getUserStatus() e redireciona conforme o estado.
//    Devolve o array do utilizador para uso imediato na página.
// ══════════════════════════════════════════════════════════════════
function checkUserAccess(int $id_users): array
{
    $user = getUserStatus($id_users);

    // Se getUserStatus() falhou (ex: query com JOIN a lançar excepção),
    // tentar getUserById() como fallback antes de destruir a sessão.
    if (!$user) {
        $user = getUserById($id_users);
    }

    if (!$user) {
        // Utilizador genuinamente não encontrado — sessão inválida
        session_destroy();
        header('Location: http://localhost/wasomupfy/dashboard/status/unauthorized');
        exit;
    }

    $st = $user['status_user'] ?? 'active';

    if ($st === 'suspended' || $st === 'banned') {
        header('Location: http://localhost/wasomupfy/dashboard/status/403');
        exit;
    }

    if ($st === 'inactive') {
        session_destroy();
        header('Location: http://localhost/wasomupfy/dashboard/status/unauthorized');
        exit;
    }

    // Garantir que os campos calculados existem mesmo vindo do fallback
    $user += [
        'plan_active'    => false,
        'plan_days_left' => null,
        'plan_expired'   => false,
        'name_plan'      => null,
        'releases_used'  => 0,
        'releases_limit' => 0,
    ];

    // pending_plan e pending_verification → deixar aceder mas
    // getDashboardAlerts() vai mostrar avisos na UI
    return $user;
}


// ══════════════════════════════════════════════════════════════════
// 5. Alertas do dashboard
//    Gera lista de avisos contextuais para mostrar no topo das
//    páginas do dashboard. Cada alerta tem:
//      type    → 'warning' | 'danger' | 'info' | 'success'
//      icon    → classe Bootstrap Icons
//      message → texto do aviso
//      action  → ['label' => '...', 'url' => '...'] ou null
//      dismiss → true/false (pode ser fechado pelo utilizador)
// ══════════════════════════════════════════════════════════════════
function getDashboardAlerts(array $user, array $platform): array
{
    $alerts = [];

    // ── Plano expirado ─────────────────────────────────────────────
    if (!empty($user['plan_expired']) && !empty($user['plan_selected'])) {
        $alerts[] = [
            'type'    => 'danger',
            'icon'    => 'bi-exclamation-triangle-fill',
            'message' => 'O teu plano expirou. Renova agora para continuar a distribuir música.',
            'action'  => ['label' => 'Renovar Plano', 'url' => 'http://localhost/wasomupfy/dashboard/finances/overview'],
            'dismiss' => false,
        ];
    }

    // ── Plano a expirar em breve (≤ 7 dias) ───────────────────────
    elseif (
        isset($user['plan_days_left']) &&
        $user['plan_days_left'] !== null &&
        $user['plan_days_left'] > 0 &&
        $user['plan_days_left'] <= 7
    ) {
        $dias = $user['plan_days_left'];
        $alerts[] = [
            'type'    => 'warning',
            'icon'    => 'bi-clock-fill',
            'message' => "O teu plano expira em {$dias} " . ($dias === 1 ? 'dia' : 'dias') . '. Renova para não interromper os teus lançamentos.',
            'action'  => ['label' => 'Renovar', 'url' => 'http://localhost/wasomupfy/dashboard/finances/overview'],
            'dismiss' => true,
        ];
    }

    // ── Sem plano activo ───────────────────────────────────────────
    if (
        ($user['status_user'] === 'pending_plan' || empty($user['plan_selected'])) &&
        empty($user['plan_expired']) // não duplicar com o alerta de expirado
    ) {
        $alerts[] = [
            'type'    => 'info',
            'icon'    => 'bi-star-fill',
            'message' => 'Ainda não tens um plano activo. Escolhe um plano para começar a distribuir.',
            'action'  => ['label' => 'Ver Planos', 'url' => 'http://localhost/wasomupfy/dashboard/finances/overview'],
            'dismiss' => false,
        ];
    }

    // ── Conta bancária não verificada (para quem já tem saldo) ────
    // (só mostrar se o utilizador já tem histórico de ganhos)
    try {
        $db    = getDB();
        $wallet = $db->prepare("SELECT balance_aoa FROM _wallet WHERE id_users = ? LIMIT 1");
        $wallet->execute([$user['id_users']]);
        $w = $wallet->fetch();

        if ($w && (float)$w['balance_aoa'] > 0) {
            $acc = $db->prepare("
                SELECT id_account FROM _account
                WHERE id_users = ? AND status_account = 'verified'
                LIMIT 1
            ");
            $acc->execute([$user['id_users']]);
            if (!$acc->fetch()) {
                $alerts[] = [
                    'type'    => 'warning',
                    'icon'    => 'bi-bank',
                    'message' => 'Tens saldo disponível mas não tens uma conta bancária verificada para levantamentos.',
                    'action'  => ['label' => 'Adicionar Conta', 'url' => 'http://localhost/wasomupfy/dashboard/account/manage-account'],
                    'dismiss' => true,
                ];
            }
        }
    } catch (Throwable $e) {
        error_log('[getDashboardAlerts] wallet check falhou: ' . $e->getMessage());
    }

    // ── Onboarding não concluído ───────────────────────────────────
    if (empty($user['onboarding_done'])) {
        $alerts[] = [
            'type'    => 'info',
            'icon'    => 'bi-person-check-fill',
            'message' => 'Completa o teu perfil para desbloquear todas as funcionalidades.',
            'action'  => ['label' => 'Completar Perfil', 'url' => 'http://localhost/wasomupfy/dashboard/user/profile'],
            'dismiss' => true,
        ];
    }

    // ── Trust score baixo ──────────────────────────────────────────
    if (isset($user['trust_score']) && (int)$user['trust_score'] < 30) {
        $alerts[] = [
            'type'    => 'danger',
            'icon'    => 'bi-shield-exclamation',
            'message' => 'A tua conta tem um índice de confiança baixo. Contacta o suporte para mais informações.',
            'action'  => ['label' => 'Contactar Suporte', 'url' => 'http://localhost/wasomupfy/dashboard/page/support'],
            'dismiss' => false,
        ];
    }

    // ── Plataforma em manutenção agendada (aviso antecipado) ───────
    // Mostrar aviso se manutenção está agendada para as próximas 2h
    $st = $platform['status'] ?? 'active';
    if (
        $st === 'active' &&
        !empty($platform['maintenance_start']) &&
        strtotime($platform['maintenance_start']) > time() &&
        strtotime($platform['maintenance_start']) - time() <= 7200 // 2 horas
    ) {
        $inicio = date('H:i', strtotime($platform['maintenance_start']));
        $alerts[] = [
            'type'    => 'warning',
            'icon'    => 'bi-tools',
            'message' => "Manutenção programada para as {$inicio}. O dashboard ficará temporariamente indisponível.",
            'action'  => null,
            'dismiss' => true,
        ];
    }

    return $alerts;
}

// ══════════════════════════════════════════════════════════════════
// 6. Renderizar alertas no HTML
//    Chama getDashboardAlerts() e imprime o HTML dos avisos.
//    Incluir logo após o header/navbar em cada página:
//       renderDashboardAlerts($user, $platform);
// ══════════════════════════════════════════════════════════════════

function renderDashboardAlerts(array $user, array $platform): void
{
    $alerts = getDashboardAlerts($user, $platform);
    if (empty($alerts)) return;

    $colorMap = [
        'danger' => ['bg' => 'rgba(239,68,68,.08)', 'border' => 'rgba(239,68,68,.25)', 'text' => '#ef4444'],
        'warning' => ['bg' => 'rgba(234,179,8,.08)', 'border' => 'rgba(234,179,8,.25)', 'text' => '#eab308'],
        'info' => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
        'success' => ['bg' => 'rgba(34,197,94,.08)', 'border' => 'rgba(34,197,94,.25)', 'text' => '#22c55e'],
    ];

    echo '<div class="wu-alerts-wrap" id="wuAlertsWrap"
    style="display:flex;flex-direction:column;gap:8px;margin-bottom:1.2rem;">';
    foreach ($alerts as $i => $alert) {
        $c = $colorMap[$alert['type']] ?? $colorMap['info'];
        $id = 'wuAlert' . $i;
        $dis = $alert['dismiss'] ? "data-dismiss=\"{$id}\"" : '';
        echo "<div id=\"{$id}\" style=\" display:flex;align-items:flex-start;gap:10px; background:{$c['bg']}; border:1px
        solid {$c['border']}; border-radius:12px;padding:.75rem 1rem; font-size:.83rem;color:{$c['text']};
        transition:opacity .3s,max-height .3s; \">";
        echo "<i class=\"bi {$alert['icon']}\" style=\"font-size:1rem;flex-shrink:0;margin-top:1px;\"></i>";
        echo "<span style=\"flex:1;color:rgba(232,232,240,.85);line-height:1.6;\">{$alert['message']}";
        if (!empty($alert['action'])) {
            $label = htmlspecialchars($alert['action']['label']);
            $url = htmlspecialchars($alert['action']['url']);
            echo " <a href=\"{$url}\"
                style=\"color:{$c['text']};font-weight:700;text-decoration:underline;white-space:nowrap;\">{$label}
                →</a>";
        }
        echo '</span>';
        if ($alert['dismiss']) {
            echo "<button type=\"button\" {$dis} onclick=\"wuDismissAlert('{$id}')\"
            style=\"background:none;border:none;color:rgba(255,255,255,.3);
            cursor:pointer;font-size:1.1rem;line-height:1;padding:0;flex-shrink:0;\" aria-label=\"Fechar\">×</button>";
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<script>
function wuDismissAlert(id) {
    var el = document.getElementById(id);
    if (el) {
        el.style.opacity = "0";
        setTimeout(function() {
            el.style.display = "none";
        }, 300);
    }
}
</script>';
}

// ══════════════════════════════════════════════════════════════════
// 7. Notificações — helpers para o dashboard
//    (A lógica completa está em ajax/notifications_api.php;
//     estas funções são para acesso síncrono no PHP da página.)
// ══════════════════════════════════════════════════════════════════
function getUnreadNotifCount(int $id_users): int
{
    try {
        $db = getDB();

        // Notificações directas não lidas
        $s1 = $db->prepare("
            SELECT COUNT(*) FROM _notification
            WHERE id_users = ? AND is_read = 0
        ");
        $s1->execute([$id_users]);
        $direct = (int)$s1->fetchColumn();

        // Broadcasts não lidos (via _broadcast_receipt)
        $s2 = $db->prepare("
            SELECT COUNT(*) FROM _broadcast_receipt
            WHERE id_users = ? AND is_read = 0
        ");
        $s2->execute([$id_users]);
        $broadcast = (int)$s2->fetchColumn();

        return $direct + $broadcast;
    } catch (Throwable $e) {
        error_log('[getUnreadNotifCount] ' . $e->getMessage());
        return 0;
    }
}

function getRecentNotifs(int $id_users, int $limit = 5): array
{
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT id_notification, type, title, body, action_url, is_read, creat_notification
            FROM _notification
            WHERE id_users = ?
            ORDER BY creat_notification DESC
            LIMIT ?
        ");
        $stmt->execute([$id_users, $limit]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('[getRecentNotifs] ' . $e->getMessage());
        return [];
    }
}

// ══════════════════════════════════════════════════════════════════
// 8. Configuração da plataforma — helpers de acesso rápido
// ══════════════════════════════════════════════════════════════════
function getPlatformConfig(): array
{
    $p = getDashboardPlatform();
    return [
        'royalty_percentage' => (float)($p['royalty_percentage'] ?? 90.00),
        'platform_fee'       => (float)($p['platform_fee']       ?? 10.00),
        'currency_default'   => $p['currency_default']            ?? 'AOA',
        'usd_to_aoa_rate'    => (float)($p['usd_to_aoa_rate']    ?? 900.00),
        'contact_email'      => $p['contact_email']               ?? 'suporte@wasomupfy.com',
        'stores_count'       => (int)($p['stores_count']          ?? 150),
        'allow_register'     => (bool)($p['allow_register']       ?? true),
        'allow_login'        => (bool)($p['allow_login']          ?? true),
        'version'            => $p['version']                     ?? '2.0',
    ];
}

// Converte USD → AOA usando a taxa da plataforma
function usdToAoa(float $usd): float
{
    $rate = (float)(getDashboardPlatform()['usd_to_aoa_rate'] ?? 900.00);
    return round($usd * $rate, 2);
}

// Formata valor em AOA: 2000 → "2.000 Kz"
function formatAOA(float $value): string
{
    return number_format($value, 0, ',', '.') . ' Kz';
}

// Formata valor em USD: 1.5 → "1,50 USD"
function formatUSD(float $value): string
{
    return number_format($value, 2, ',', '.') . ' USD';
}

// Formata royalty: calcula parte do utilizador com base na taxa da plataforma
function calcRoyalty(float $total_usd): array
{
    $cfg     = getPlatformConfig();
    $pct     = $cfg['royalty_percentage'] / 100;
    $user    = round($total_usd * $pct, 4);
    $platform = round($total_usd * ($cfg['platform_fee'] / 100), 4);
    return [
        'total'    => $total_usd,
        'user_usd' => $user,
        'plat_usd' => $platform,
        'user_aoa' => usdToAoa($user),
        'plat_aoa' => usdToAoa($platform),
        'pct'      => $cfg['royalty_percentage'],
    ];
}


// ══════════════════════════════════════════════════════════════════
// 9. Estado do plano — helper para a UI
//    Devolve array com informação de apresentação do plano activo.
// ══════════════════════════════════════════════════════════════════
function getPlanBadge(array $user): array
{
    if (empty($user['plan_selected'])) {
        return [
            'label'  => 'Sem Plano',
            'color'  => '#6b7280',
            'icon'   => 'bi-dash-circle',
            'active' => false,
        ];
    }

    $name = $user['name_plan'] ?? 'Plano';

    if (!empty($user['plan_expired'])) {
        return [
            'label'  => $name . ' (Expirado)',
            'color'  => '#ef4444',
            'icon'   => 'bi-x-circle-fill',
            'active' => false,
        ];
    }

    $days  = $user['plan_days_left'] ?? null;
    $color = '#22c55e'; // verde
    if ($days !== null && $days <= 7)  $color = '#eab308'; // âmbar
    if ($days !== null && $days <= 3)  $color = '#ef4444'; // vermelho

    $suffix = '';
    if ($days !== null) {
        $suffix = ' · ' . $days . ' ' . ($days === 1 ? 'dia' : 'dias');
    }

    return [
        'label'  => $name . $suffix,
        'color'  => $color,
        'icon'   => 'bi-check-circle-fill',
        'active' => (bool)$user['plan_active'],
        'days'   => $days,
    ];
}


// ══════════════════════════════════════════════════════════════════
// 10. Utilitários gerais do dashboard
// ══════════════════════════════════════════════════════════════════

// Formata data/hora em pt-AO: "13 mar. 2026 às 14:30"
function formatDatePT(string $datetime, bool $time = true): string
{
    if (empty($datetime)) return '—';
    $ts     = strtotime($datetime);
    $months = [
        '',
        'jan.',
        'fev.',
        'mar.',
        'abr.',
        'mai.',
        'jun.',
        'jul.',
        'ago.',
        'set.',
        'out.',
        'nov.',
        'dez.'
    ];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts);
    $t = $time ? ' às ' . date('H:i', $ts) : '';
    return "{$d} {$m} {$y}{$t}";
}

// "há 5 minutos", "há 2 horas", "há 3 dias", etc.
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'agora mesmo';
    if ($diff < 3600)   return 'há ' . floor($diff / 60)   . ' min';
    if ($diff < 86400)  return 'há ' . floor($diff / 3600) . 'h';
    if ($diff < 604800) return 'há ' . floor($diff / 86400) . ' ' .
        (floor($diff / 86400) === 1 ? 'dia' : 'dias');
    return formatDatePT($datetime, false);
}

// Trunca texto com reticências: truncate('texto longo', 40)
function truncate(string $text, int $length = 60, string $suffix = '…'): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return rtrim(mb_substr($text, 0, $length)) . $suffix;
}

// Gera URL de avatar via initials (fallback quando não há foto)
// Devolve string CSS inline para usar no style de um div.
function avatarInitials(string $name, int $size = 40): string
{
    $parts    = array_filter(explode(' ', trim($name)));
    $initials = mb_strtoupper(
        mb_substr($parts[0] ?? '?', 0, 1) .
            mb_substr(end($parts) ?? '', 0, 1)
    );
    $colors = ['#FF0089', '#6366f1', '#22c55e', '#eab308', '#f97316', '#06b6d4'];
    $color  = $colors[array_sum(array_map('ord', str_split($name))) % count($colors)];
    return "width:{$size}px;height:{$size}px;border-radius:50%;"
        . "background:{$color};display:inline-flex;align-items:center;"
        . "justify-content:center;font-family:'Syne',sans-serif;"
        . "font-weight:900;font-size:" . round($size * 0.38) . "px;color:#fff;"
        . "flex-shrink:0;\" data-initials=\"{$initials}";
}
