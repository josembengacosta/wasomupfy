<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Sidebar reutilizável
// Include: wu-panel-2026/pages/manager/include/payment-sidebar.php
// Variável esperada: $payment_sidebar_active (string)
// ══════════════════════════════════════════════════════════════

// Garantir guard disponível
if (!function_exists('paymentPanelBaseUrl')) {
    require_once __DIR__ . '/payment-guard.php';
}

// Contadores de badges
$_sb_pending_wd    = (int)$db->query("SELECT COUNT(*) FROM _withdrawal WHERE status_withdrawal='pending'")->fetchColumn();
$_sb_pending_proof = (int)$db->query("SELECT COUNT(*) FROM _payment_proof WHERE status='pending'")->fetchColumn();
$_sb_pending_roy   = (int)$db->query("SELECT COUNT(*) FROM _royalty WHERE status_royalty='pending'")->fetchColumn();

$_sb_active = $payment_sidebar_active ?? '';
$_sb_base   = paymentPanelBaseUrl();

function _sb_item(string $href, string $icon, string $label, string $key, string $current, int $badge = 0): string
{
    $active = $current === $key ? ' active' : '';
    $bdg    = $badge > 0
        ? '<span class="sb-badge">' . ($badge > 99 ? '99+' : $badge) . '</span>'
        : '';
    return '<a href="' . $href . '" class="sb-link' . $active . '">'
        . '<i class="bi ' . $icon . '"></i>'
        . '<span>' . $label . '</span>'
        . $bdg
        . '</a>';
}
?>
<style>
/* ═══════════════════════════════════════════════════════
   Wasom Upfy for Business — Design System Global
   ═══════════════════════════════════════════════════════ */
:root {
    --sb-bg: #0b0b16;
    --sb-border: rgba(255, 255, 255, .06);
    --sb-link: rgba(255, 255, 255, .58);
    --sb-active: #FF0089;
    --sb-hover: rgba(255, 0, 137, .1);
    --sb-section: rgba(255, 255, 255, .22);
    --sb-badge: #FF0089;
    --sb-w: 272px;
    --biz-radius: 20px;
    --biz-shadow: 0 2px 12px rgba(0, 0, 0, .05);
    --biz-bg: #f4f6fb;
}

*,
*::before,
*::after {
    box-sizing: border-box;
}

body {
    font-family: 'Inter', system-ui, sans-serif;
    margin: 0;
    background: var(--biz-bg);
}

/* ── Sidebar ── */
.biz-sidebar {
    width: var(--sb-w);
    background: var(--sb-bg);
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 1040;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform .28s cubic-bezier(.4, 0, .2, 1);
    border-right: 1px solid var(--sb-border);
}

.biz-sidebar-scroll {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0 88px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, .08) transparent;
}

.biz-sidebar-scroll::-webkit-scrollbar {
    width: 4px;
}

.biz-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, .1);
    border-radius: 4px;
}

.biz-content {
    margin-left: var(--sb-w);
    min-height: 100vh;
    background: var(--biz-bg);
}

@media (max-width: 991px) {
    .biz-sidebar {
        transform: translateX(-100%);
    }

    .biz-sidebar.open {
        transform: translateX(0);
        box-shadow: 6px 0 32px rgba(0, 0, 0, .5);
    }

    .biz-content {
        margin-left: 0 !important;
    }

    .sb-overlay {
        display: block !important;
    }
}

/* ── Header ── */
.sb-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--sb-border);
    flex-shrink: 0;
}

.sb-brand-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sb-brand-img {
    height: 38px;
    border-radius: 50%;
}

.sb-brand-name {
    font-size: .92rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.15;
}

.sb-brand-sub {
    font-size: .64rem;
    color: var(--sb-active);
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
}

/* ── Admin pill ── */
.sb-admin-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 12px;
    padding: 9px 12px;
    background: rgba(255, 255, 255, .04);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, .05);
}

.sb-admin-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 0, 137, .3);
    flex-shrink: 0;
}

.sb-admin-ini {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF0089, #f97316);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: .72rem;
    color: #fff;
    flex-shrink: 0;
}

.sb-admin-name {
    font-size: .78rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}

.sb-admin-role {
    font-size: .63rem;
    color: rgba(255, 255, 255, .4);
    margin-top: 1px;
}

/* ── Secções e links ── */
.sb-section {
    font-size: .59rem;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--sb-section);
    padding: 14px 20px 5px;
    font-weight: 700;
}

.sb-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 9px 20px;
    color: var(--sb-link);
    text-decoration: none;
    font-size: .8rem;
    font-weight: 500;
    transition: all .18s;
    border-left: 3px solid transparent;
}

.sb-link i {
    font-size: .95rem;
    flex-shrink: 0;
    width: 18px;
    text-align: center;
}

.sb-link span {
    flex: 1;
}

.sb-link:hover {
    color: #fff;
    background: var(--sb-hover);
    border-left-color: rgba(255, 0, 137, .45);
}

.sb-link.active {
    color: #fff;
    background: rgba(255, 0, 137, .16);
    border-left-color: var(--sb-active);
    font-weight: 700;
}

.sb-link.active i {
    color: var(--sb-active);
}

/* ── Badge ── */
.sb-badge {
    background: var(--sb-badge);
    color: #fff;
    font-size: .6rem;
    font-weight: 800;
    padding: 1px 7px;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
    flex-shrink: 0;
}

/* ── Footer ── */
.sb-footer {
    flex-shrink: 0;
    padding: 10px 12px;
    border-top: 1px solid var(--sb-border);
    background: var(--sb-bg);
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
}

.sb-footer a {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 7px 12px;
    color: rgba(255, 255, 255, .45);
    text-decoration: none;
    font-size: .76rem;
    border-radius: 10px;
    transition: all .18s;
}

.sb-footer a:hover {
    color: #fff;
    background: rgba(255, 255, 255, .06);
}

.sb-footer .sb-logout {
    color: rgba(239, 68, 68, .65);
}

.sb-footer .sb-logout:hover {
    color: #ef4444;
    background: rgba(239, 68, 68, .1);
}

/* ── Overlay ── */
.sb-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .55);
    z-index: 1039;
    backdrop-filter: blur(3px);
}

/* ══════════════════════════════════════════════
   Topbar, layout, stat cards, biz-card, badges,
   tabelas, filtros, modais — design system global
   ══════════════════════════════════════════════ */
.biz-topbar {
    background: #fff;
    border-bottom: 1px solid #e8eaf2;
    padding: 0 24px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 6px rgba(0, 0, 0, .04);
}

.biz-topbar-title {
    font-size: .98rem;
    font-weight: 800;
    color: #1a1a2e;
}

.biz-topbar-sub {
    font-size: .7rem;
    color: #9ca3af;
    margin-top: 1px;
}

.biz-hamburger {
    background: transparent;
    border: 1px solid #e8eaf2;
    border-radius: 10px;
    padding: 7px 11px;
    color: #555;
    cursor: pointer;
    display: none;
    align-items: center;
}

@media (max-width:991px) {
    .biz-hamburger {
        display: flex;
    }
}

.biz-inner {
    padding: 24px;
}

.biz-stat {
    background: #fff;
    border-radius: var(--biz-radius);
    padding: 18px 20px;
    border: 1px solid rgba(0, 0, 0, .04);
    box-shadow: var(--biz-shadow);
    transition: all .22s;
    height: 100%;
}

.biz-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, .08);
}

.biz-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

.biz-stat-val {
    font-size: 1.7rem;
    font-weight: 800;
    line-height: 1.1;
    color: #1a1a2e;
}

.biz-stat-lbl {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #9ca3af;
    margin-top: 3px;
}

.biz-stat-sub {
    font-size: .73rem;
    margin-top: 5px;
}

.biz-card {
    background: #fff;
    border-radius: var(--biz-radius);
    border: 1px solid rgba(0, 0, 0, .04);
    box-shadow: var(--biz-shadow);
    overflow: hidden;
}

.biz-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid #f0f2f8;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.biz-card-title {
    font-size: .88rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0;
    flex: 1;
}

.biz-s-pending {
    background: #fff3e0;
    color: #ea580c;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .71rem;
    font-weight: 700;
    display: inline-block;
}

.biz-s-processing {
    background: #e0f2fe;
    color: #0284c7;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .71rem;
    font-weight: 700;
    display: inline-block;
}

.biz-s-approved,
.biz-s-paid {
    background: #dcfce7;
    color: #16a34a;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .71rem;
    font-weight: 700;
    display: inline-block;
}

.biz-s-rejected,
.biz-s-cancelled {
    background: #fee2e2;
    color: #dc2626;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .71rem;
    font-weight: 700;
    display: inline-block;
}

.biz-table th {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .45px;
    font-weight: 700;
    white-space: nowrap;
    background: #f8f9fc;
    color: #555;
    padding: 10px 14px;
}

.biz-table td {
    font-size: .81rem;
    vertical-align: middle;
    padding: 10px 14px;
}

.biz-table tbody tr:hover {
    background: #fafbff;
}

.biz-table tbody tr.highlight-pending {
    background: #fffbeb;
}

.biz-table tbody tr.highlight-pending:hover {
    background: #fef3c7;
}

.det-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid #f4f4f8;
}

.det-row:last-child {
    border-bottom: none;
}

.det-lbl {
    font-size: .76rem;
    font-weight: 600;
    color: #9ca3af;
    min-width: 145px;
}

.det-val {
    font-size: .82rem;
    text-align: right;
    word-break: break-all;
}

.bi-doc-img {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 10px;
    border: 2px solid #e8eaf2;
    cursor: zoom-in;
    transition: border-color .2s;
}

.bi-doc-img:hover {
    border-color: #FF0089;
}

.filter-card {
    background: #fff;
    border-radius: var(--biz-radius);
    padding: 14px 18px;
    border: 1px solid rgba(0, 0, 0, .04);
    box-shadow: var(--biz-shadow);
    margin-bottom: 18px;
}

.filter-card .form-label {
    font-size: .73rem;
    font-weight: 600;
    margin-bottom: 3px;
    color: #555;
}

.biz-chart-wrap {
    position: relative;
    height: 220px;
}

.pag-link {
    border-radius: 8px !important;
    margin: 0 2px;
    font-size: .79rem;
}

/* ── Account info box (usado no modal royalty) ── */
.account-info-box {
    background: #f8f9fc;
    border-radius: 12px;
    border: 1px solid #e8eaf2;
    padding: 14px 16px;
    font-size: .82rem;
    transition: border-color .2s;
}

.account-info-box.verified {
    border-color: rgba(34, 197, 94, .4);
    background: rgba(34, 197, 94, .03);
}

.account-info-box.pending {
    border-color: rgba(249, 115, 22, .4);
    background: rgba(249, 115, 22, .03);
}

.account-info-box.empty {
    border-color: rgba(239, 68, 68, .35);
    background: rgba(239, 68, 68, .03);
}

.ai-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px dashed #eee;
}

.ai-row:last-child {
    border-bottom: none;
}

.ai-lbl {
    color: #9ca3af;
    font-size: .73rem;
    font-weight: 500;
}

.ai-val {
    font-weight: 700;
    color: #1a1a2e;
    font-size: .8rem;
}

/* ── Preview de valor ── */
.amount-preview {
    background: linear-gradient(135deg, rgba(255, 0, 137, .05), rgba(249, 115, 22, .04));
    border: 1px solid rgba(255, 0, 137, .18);
    border-radius: 14px;
    padding: 18px;
    text-align: center;
}

.ap-amount {
    font-size: 1.6rem;
    font-weight: 800;
    color: #FF0089;
    line-height: 1;
}

.ap-label {
    font-size: .7rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-top: 5px;
}

.ap-breakdown {
    font-size: .76rem;
    color: #555;
    margin-top: 10px;
    line-height: 1.7;
}
</style>

<!-- Overlay mobile -->
<div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>

<aside class="biz-sidebar" id="bizSidebar">
    <div class="sb-header">
        <div class="sb-brand-wrap">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="sb-brand-img" alt="">
            <div>
                <div class="sb-brand-name">Wasom Upfy</div>
                <div class="sb-brand-sub">for Business</div>
            </div>
        </div>
    </div>

    <?php
    $sb_adm = $db->prepare("SELECT first_name,second_name,role,photo_employees FROM _employees WHERE id_employees=?");
    $sb_adm->execute([$admin_id]);
    $sb_a   = $sb_adm->fetch() ?: [];
    $sb_nm  = trim(($sb_a['first_name'] ?? '') . (' ' . ($sb_a['second_name'] ?? '')));
    $sb_ini = mb_strtoupper(mb_substr($sb_a['first_name'] ?? 'A', 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr($sb_a['second_name'] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $sb_rl  = ['super_admin' => 'Super Admin', 'admin' => 'Administrador', 'editor' => 'Editor', 'support' => 'Suporte'];
    ?>
    <div class="sb-admin-pill">
        <?php if (!empty($sb_a['photo_employees'])): ?>
        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($sb_a['photo_employees']); ?>"
            class="sb-admin-avatar" alt=""
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="sb-admin-ini" style="display:none"><?php echo $sb_ini; ?></div>
        <?php else: ?>
        <div class="sb-admin-ini"><?php echo $sb_ini; ?></div>
        <?php endif; ?>
        <div>
            <div class="sb-admin-name"><?php echo htmlspecialchars($sb_nm); ?></div>
            <div class="sb-admin-role"><?php echo $sb_rl[$sb_a['role'] ?? ''] ?? ucfirst($sb_a['role'] ?? 'Admin'); ?>
            </div>
        </div>
    </div>

    <div class="biz-sidebar-scroll">
        <div class="sb-section">Visão Geral</div>
        <?php echo _sb_item($_sb_base . '/gestion',       'bi-speedometer2',        'Dashboard',          'dashboard',      $_sb_active); ?>

        <div class="sb-section">Pagamentos</div>
        <?php echo _sb_item($_sb_base . '/withdrawals',   'bi-arrow-up-circle',     'Pedidos de Saque',   'withdrawals',    $_sb_active, $_sb_pending_wd); ?>
        <?php echo _sb_item($_sb_base . '/proofs',        'bi-file-earmark-check',  'Comprovativos',      'proofs',         $_sb_active, $_sb_pending_proof); ?>

        <div class="sb-section">Royalties</div>
        <?php echo _sb_item($_sb_base . '/royalty-splits', 'bi-cash-coin',            'Pagar Royalties',    'royalty-splits', $_sb_active, $_sb_pending_roy); ?>

        <div class="sb-section">Histórico</div>
        <?php echo _sb_item($_sb_base . '/transactions',  'bi-arrow-left-right',    'Transacções',        'transactions',   $_sb_active); ?>
    </div>

    <div class="sb-footer">
        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>">
            <i class="bi bi-arrow-left"></i> Voltar ao Admin
        </a>
        <a href="#" class="sb-logout" onclick="logoutBusiness();return false">
            <i class="bi bi-power"></i> Sair do Painel
        </a>
    </div>
</aside>

<script>
function openSidebar() {
    document.getElementById('bizSidebar').classList.add('open');
    document.getElementById('sbOverlay').style.display = 'block';
}

function closeSidebar() {
    document.getElementById('bizSidebar').classList.remove('open');
    document.getElementById('sbOverlay').style.display = 'none';
}

function logoutBusiness() {
    const btn = event?.target;
    if (btn) btn.disabled = true;

    Swal.fire({
        title: 'A fazer logout...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const fd = new FormData();
    fd.append('action', 'logout_payment_panel');
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch('<?php echo paymentPanelBaseUrl(); ?>/process', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            redirect: 'manual' //  impede o fetch de seguir o redirect do servidor
        })
        .then(() => {
            // Independentemente da resposta, navega para /login no cliente
            // O servidor já destruiu a sessão — só precisamos de ir para lá
            window.location.replace('<?php echo paymentPanelBaseUrl(); ?>/login');
        })
        .catch(() => {
            // Mesmo em caso de erro de rede, tenta o redirect
            // (a sessão pode já ter sido destruída no servidor)
            window.location.replace('<?php echo paymentPanelBaseUrl(); ?>/login');
        });
}

// 🔧 Fix: Quando a janela for redimensionada para tamanho desktop, fecha a sidebar
window.addEventListener('resize', function() {
    if (window.innerWidth > 991) {
        closeSidebar();
    }
});

// Opcional: Fechar sidebar ao carregar a página se a largura for desktop (previne estado inicial incorreto)
if (window.innerWidth > 991) {
    closeSidebar();
}
</script>