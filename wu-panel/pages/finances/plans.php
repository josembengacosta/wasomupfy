<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Gestão de Planos
// Arquivo: wu-panel/pages/finances/plans.php
// Rota:    wu-panel/plans
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Ordenação ──────────────────────────────────────────────────
$sort_col = $_GET['sort'] ?? 'display_order';
$sort_dir = $_GET['dir'] ?? 'asc';
$allowed_cols = ['id_plan', 'name_plan', 'slug_plan', 'type_plan', 'price_plan', 'display_order', 'is_active'];
if (!in_array($sort_col, $allowed_cols)) $sort_col = 'display_order';
$sort_dir = strtolower($sort_dir) === 'desc' ? 'DESC' : 'ASC';
$next_dir = $sort_dir === 'ASC' ? 'desc' : 'asc';

// ── Stats globais ────────────────────────────────────────────
$stats = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_active = 1)             AS active,
        SUM(is_active = 0)             AS inactive,
        SUM(type_plan = 'per_release') AS per_release,
        SUM(type_plan = 'subscription') AS subscription
    FROM _plans
")->fetch();

// ── Todos os planos (ordenados) ───────────────────────────────
$all_plans = $db->query("
    SELECT
        id_plan, slug_plan, name_plan, description_plan,
        type_plan, price_plan, price_usd, price_annual, annual_qty,
        validity_days, max_artists, max_releases, max_tracks_per_release,
        royalty_rate, img_plan, badge_text, is_featured, is_active, display_order
    FROM _plans
    ORDER BY $sort_col $sort_dir
")->fetchAll();

// ── Helpers ──────────────────────────────────────────────────
function plan_type_label(string $t): string
{
    return match ($t) {
        'per_release'  => '<span class="badge-pill-pink">Por Lançamento</span>',
        'subscription' => '<span class="badge-pill-blue">Assinatura Anual</span>',
        default        => '<span class="badge-pill-muted">' . htmlspecialchars($t) . '</span>',
    };
}

function plan_status_badge(int $active): string
{
    return $active
        ? '<span class="badge bg-success">Ativo</span>'
        : '<span class="badge bg-secondary">Inativo</span>';
}

function fmt_aoa(?float $v): string
{
    if ($v === null) return '—';
    return number_format($v, 2, ',', '.') . ' AOA';
}

function fmt_unlimited(?int $v, string $suffix = ''): string
{
    if ($v === null) return '<span class="text-muted fst-italic">∞</span>';
    return $v . ($suffix ? ' ' . $suffix : '');
}

function sort_link(string $col, string $label): string
{
    global $sort_col, $sort_dir, $next_dir;
    $icon = '';
    if ($sort_col === $col) {
        $icon = $sort_dir === 'ASC' ? ' ▲' : ' ▼';
    }
    $url = '?' . http_build_query(['sort' => $col, 'dir' => $next_dir]);
    return '<a href="' . $url . '" class="text-decoration-none text-inherit">' . htmlspecialchars($label) . $icon . '</a>';
}

$base_url = APP_URL . '/' . ADMIN_PATH;
$csrf     = $_SESSION['admin_csrf_token'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Planos — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    /* ── Stat cards ── */
    .plan-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform .2s, box-shadow .2s;
        cursor: default;
        color: inherit;
    }

    .plan-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .07);
    }

    .plan-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .plan-stat-val {
        font-size: 1.3rem;
        font-weight: 800;
        line-height: 1;
    }

    .plan-stat-lbl {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        opacity: .6;
        margin-top: 2px;
    }

    /* ── Pill badges ── */
    .badge-pill-pink {
        font-size: .7rem;
        background: rgba(255, 0, 137, .1);
        color: #FF0089;
        padding: 3px 9px;
        border-radius: 20px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-pill-blue {
        font-size: .7rem;
        background: rgba(59, 130, 246, .1);
        color: #3b82f6;
        padding: 3px 9px;
        border-radius: 20px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-pill-muted {
        font-size: .7rem;
        background: #f0f0f0;
        color: #666;
        padding: 3px 9px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* ── Tabela ── */
    .fin-table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        white-space: nowrap;
    }

    .fin-table td {
        font-size: .83rem;
        vertical-align: middle;
    }

    .plan-name-cell strong {
        display: block;
    }

    .plan-name-cell .plan-badge-tag {
        font-size: .62rem;
        background: rgba(234, 179, 8, .15);
        color: #b45309;
        padding: 1px 6px;
        border-radius: 10px;
        font-weight: 600;
    }

    .star-featured {
        color: #f59e0b;
        font-size: .85rem;
    }

    /* ── Dropdown de ações ── */
    .actions-dropdown .dropdown-menu {
        position: absolute;
        z-index: 1060;
        min-width: 180px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
        padding: 6px;
    }

    .actions-dropdown .dropdown-item {
        font-size: .82rem;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        border-radius: 8px;
    }

    /* ── Modal ── */
    .section-per_release,
    .section-subscription {
        display: none;
    }

    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    #imgPreview {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid var(--border-color, #e8e8f0);
        display: none;
    }

    .modal-section-title {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #FF0089;
        border-bottom: 1px solid rgba(255, 0, 137, .15);
        padding-bottom: 6px;
        margin-bottom: 4px;
    }

    .text-inherit {
        color: inherit;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>

        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <div class="container-fluid p-0">

                <!-- Cabeçalho -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-tags me-2"></i>Planos</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Planos</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-sm text-white" style="background:#FF0089" id="btnAddPlan">
                            <i class="bi bi-plus-lg me-1"></i> Novo Plano
                        </button>
                    </div>
                </div>

                <!-- Stat cards -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_items = [
                        ['total',        '#FF0089', 'bi-tags',           'Total',          $stats['total']],
                        ['active',       '#22c55e', 'bi-check-circle',   'Ativos',         $stats['active']],
                        ['inactive',     '#6b7280', 'bi-x-circle',       'Inativos',       $stats['inactive']],
                        ['per_release',  '#eab308', 'bi-disc',           'Por Lançamento', $stats['per_release']],
                        ['subscription', '#3b82f6', 'bi-calendar-check', 'Assinatura',     $stats['subscription']],
                    ];
                    foreach ($stat_items as [$key, $color, $icon, $lbl, $val]): ?>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="plan-stat">
                            <div class="plan-stat-icon" style="background:<?php echo $color; ?>1a">
                                <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                            </div>
                            <div>
                                <div class="plan-stat-val"><?php echo number_format((int)$val); ?></div>
                                <div class="plan-stat-lbl"><?php echo $lbl; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tabela -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php echo number_format($stats['total']); ?> planos registados
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover fin-table mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo sort_link('id_plan', 'ID'); ?></th>
                                    <th><?php echo sort_link('name_plan', 'Nome / Badge'); ?></th>
                                    <th><?php echo sort_link('slug_plan', 'Slug'); ?></th>
                                    <th><?php echo sort_link('type_plan', 'Tipo'); ?></th>
                                    <th><?php echo sort_link('price_plan', 'Preço (AOA)'); ?></th>
                                    <th>Royalty</th>
                                    <th>Artistas</th>
                                    <th>Faixas</th>
                                    <th>Releases</th>
                                    <th>Validade</th>
                                    <th>Dest.</th>
                                    <th><?php echo sort_link('display_order', 'Ordem'); ?></th>
                                    <th><?php echo sort_link('is_active', 'Status'); ?></th>
                                    <th style="width:56px">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_plans as $plan): ?>
                                <tr>
                                    <td class="text-muted">#<?php echo $plan['id_plan']; ?></td>
                                    <td class="plan-name-cell">
                                        <strong><?php echo htmlspecialchars($plan['name_plan']); ?></strong>
                                        <?php if ($plan['badge_text']): ?>
                                        <span
                                            class="plan-badge-tag"><?php echo htmlspecialchars($plan['badge_text']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($plan['slug_plan']); ?></code></td>
                                    <td><?php echo plan_type_label($plan['type_plan']); ?></td>
                                    <td><?php echo fmt_aoa($plan['price_plan']); ?></td>
                                    <td><?php echo $plan['royalty_rate']; ?>%</td>
                                    <td><?php echo fmt_unlimited($plan['max_artists']); ?></td>
                                    <td><?php echo fmt_unlimited($plan['max_tracks_per_release']); ?></td>
                                    <td><?php echo fmt_unlimited($plan['max_releases']); ?></td>
                                    <td><?php echo $plan['validity_days'] ? $plan['validity_days'] . ' dias' : '<span class="text-muted fst-italic">∞</span>'; ?>
                                    </td>
                                    <td><?php echo $plan['is_featured'] ? '<i class="bi bi-star-fill star-featured" title="Destaque"></i>' : '<i class="bi bi-star text-muted"></i>'; ?>
                                    </td>
                                    <td><?php echo $plan['display_order']; ?></td>
                                    <td><?php echo plan_status_badge($plan['is_active']); ?></td>
                                    <td>
                                        <div class="actions-dropdown dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item edit-plan" href="#"
                                                        data-id="<?php echo $plan['id_plan']; ?>"><i
                                                            class="bi bi-pencil text-primary"></i> Editar</a></li>
                                                <li><a class="dropdown-item toggle-plan" href="#"
                                                        data-id="<?php echo $plan['id_plan']; ?>"
                                                        data-active="<?php echo $plan['is_active']; ?>"><i
                                                            class="bi bi-eye<?php echo $plan['is_active'] ? '-slash' : ''; ?> text-warning"></i>
                                                        <?php echo $plan['is_active'] ? 'Desativar' : 'Ativar'; ?></a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-1">
                                                </li>
                                                <li><a class="dropdown-item text-danger delete-plan" href="#"
                                                        data-id="<?php echo $plan['id_plan']; ?>"
                                                        data-name="<?php echo htmlspecialchars($plan['name_plan']); ?>"><i
                                                            class="bi bi-trash"></i> Eliminar</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- Modal Adicionar / Editar Plano -->
    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white" id="modalTitle">
                        <i class="bi bi-plus-circle me-2"></i>Novo Plano
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="planForm" novalidate>
                    <input type="hidden" name="action" value="save_plan">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="id_plan" id="planId">

                    <div class="modal-body">

                        <!-- Informações Básicas -->
                        <p class="modal-section-title"><i class="bi bi-info-circle me-1"></i>Informações Básicas</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nome do Plano <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name_plan" id="planName" maxlength="100"
                                    required placeholder="Ex: Artista Pro">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="slug_plan" id="planSlug" maxlength="50"
                                    placeholder="ex: artist-pro" required>
                                <small class="text-muted">Gerado automaticamente a partir do nome</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Descrição</label>
                                <textarea class="form-control" name="description_plan" id="planDesc" rows="2"
                                    placeholder="Breve descrição do plano..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" name="type_plan" id="planType" required>
                                    <option value="per_release">Por Lançamento</option>
                                    <option value="subscription">Assinatura Anual</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end gap-4 pb-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="planStatus"
                                        value="1" checked>
                                    <label class="form-check-label" for="planStatus">Plano ativo</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="planFeatured"
                                        value="1">
                                    <label class="form-check-label" for="planFeatured"><i
                                            class="bi bi-star-fill text-warning me-1"></i>Destaque</label>
                                </div>
                            </div>
                        </div>

                        <!-- Preços -->
                        <p class="modal-section-title mt-1"><i class="bi bi-currency-exchange me-1"></i>Preços</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Preço Base (AOA) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" name="price_plan"
                                        id="planPrice" required>
                                    <span class="input-group-text">AOA</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Preço em USD <small
                                        class="fw-normal text-muted">(opcional)</small></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" name="price_usd"
                                        id="planPriceUSD">
                                    <span class="input-group-text">USD</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Royalty (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control"
                                        name="royalty_rate" id="planRoyalty" value="90.00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <!-- Por Lançamento: Pacote Anual -->
                            <div class="col-md-6 section-per_release">
                                <label class="form-label fw-bold">Preço Pacote Anual (AOA) <small
                                        class="fw-normal text-muted">(opcional)</small></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" name="price_annual"
                                        id="planPriceAnnual">
                                    <span class="input-group-text">AOA</span>
                                </div>
                            </div>
                            <div class="col-md-6 section-per_release">
                                <label class="form-label fw-bold">Releases no Pacote <small
                                        class="fw-normal text-muted">(opcional)</small></label>
                                <input type="number" min="1" class="form-control" name="annual_qty" id="planAnnualQty"
                                    placeholder="Ex: 10">
                                <small class="text-muted">Quantidade de lançamentos incluídos no pacote</small>
                            </div>

                            <!-- Assinatura: Validade -->
                            <div class="col-md-6 section-subscription">
                                <label class="form-label fw-bold">Validade (dias)</label>
                                <div class="input-group">
                                    <input type="number" min="1" class="form-control" name="validity_days"
                                        id="planValidityDays" placeholder="Ex: 365">
                                    <span class="input-group-text">dias</span>
                                </div>
                                <small class="text-muted">Deixe vazio para sem expiração</small>
                            </div>
                        </div>

                        <!-- Limites -->
                        <p class="modal-section-title mt-1"><i class="bi bi-sliders me-1"></i>Limites & Capacidades</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Máx. Artistas</label>
                                <input type="number" min="1" class="form-control" name="max_artists" id="planMaxArtists"
                                    value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Máx. Releases</label>
                                <input type="number" min="1" class="form-control" name="max_releases"
                                    id="planMaxReleases" placeholder="∞ ilimitado">
                                <small class="text-muted">Deixe vazio para ilimitado</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Máx. Faixas</label>
                                <input type="number" min="1" class="form-control" name="max_tracks_per_release"
                                    id="planMaxTracks" placeholder="∞ ilimitado">
                                <small class="text-muted">Por lançamento</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Ordem</label>
                                <input type="number" class="form-control" name="display_order" id="planOrder" value="0">
                            </div>
                        </div>

                        <!-- Visual -->
                        <p class="modal-section-title mt-1"><i class="bi bi-palette me-1"></i>Visual & Badge</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Badge do Plano <small
                                        class="fw-normal text-muted">(opcional)</small></label>
                                <input type="text" class="form-control" name="badge_text" id="planBadge" maxlength="50"
                                    placeholder="Ex: Mais Popular, Recomendado">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">URL da Imagem do Card <small
                                        class="fw-normal text-muted">(opcional)</small></label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="text" class="form-control" name="img_plan" id="planImg"
                                        placeholder="<?php echo APP_URL; ?>/assets/img/theme/plan_single.png">
                                    <img id="imgPreview" src="" alt="preview" data-base-url="<?php echo APP_URL; ?>">
                                </div>
                            </div>
                        </div>

                    </div><!-- /modal-body -->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-pink" id="saveBtn">
                            <i class="bi bi-save me-1"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    (function() {
        'use strict';

        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        // Aguardar o DOM estar completamente carregado
        document.addEventListener('DOMContentLoaded', function() {

            // Referências aos elementos (só serão acessadas após o DOM pronto)
            const modalEl = document.getElementById('planModal');
            if (!modalEl) return; // segurança
            const modal = new bootstrap.Modal(modalEl);
            const form = document.getElementById('planForm');
            const planId = document.getElementById('planId');
            const modalTitle = document.getElementById('modalTitle');
            const planType = document.getElementById('planType');
            const planName = document.getElementById('planName');
            const planSlug = document.getElementById('planSlug');
            const planDesc = document.getElementById('planDesc');
            const planPrice = document.getElementById('planPrice');
            const planPriceUSD = document.getElementById('planPriceUSD');
            const planPriceAnnual = document.getElementById(
                'planPriceAnnual');
            const planAnnualQty = document.getElementById('planAnnualQty');
            const planValidityDays = document.getElementById(
                'planValidityDays');
            const planRoyalty = document.getElementById('planRoyalty');
            const planMaxArtists = document.getElementById(
                'planMaxArtists');
            const planMaxReleases = document.getElementById(
                'planMaxReleases');
            const planMaxTracks = document.getElementById('planMaxTracks');
            const planOrder = document.getElementById('planOrder');
            const planStatus = document.getElementById('planStatus');
            const planFeatured = document.getElementById('planFeatured');
            const planBadge = document.getElementById('planBadge');
            const planImg = document.getElementById('planImg');
            const imgPreview = document.getElementById('imgPreview');
            const saveBtn = document.getElementById('saveBtn');

            let slugManual = false;

            // Função para aplicar UI condicional por tipo
            function applyTypeUI(type) {
                document.querySelectorAll('.section-per_release').forEach(
                    el => el.style.display = type ===
                    'per_release' ? '' : 'none');
                document.querySelectorAll('.section-subscription').forEach(
                    el => el.style.display = type ===
                    'subscription' ? '' : 'none');
            }

            // Event listeners
            planType.addEventListener('change', () => applyTypeUI(planType
                .value));

            planSlug.addEventListener('input', () => slugManual = true);
            planName.addEventListener('input', () => {
                if (slugManual) return;
                let slug = planName.value
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g,
                        '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-');
                if (!slug) slug = 'plano-' + Date.now().toString(
                    36);
                planSlug.value = slug;
            });

            planImg.addEventListener('input', () => {
                let url = planImg.value.trim();
                if (url) {
                    // Se não for uma URL completa (não começa com http:// ou https://), prefixa com APP_URL
                    if (!url.match(/^https?:\/\//i)) {
                        const baseUrl = imgPreview.dataset.baseUrl || '';
                        url = baseUrl.replace(/\/$/, '') + '/' + url.replace(/^\//, '');
                    }
                    imgPreview.src = url;
                    imgPreview.style.display = '';
                    imgPreview.onerror = () => imgPreview.style.display = 'none';
                } else {
                    imgPreview.style.display = 'none';
                }
            });

            // Reset do modal
            function resetModal() {
                form.reset();
                planId.value = '';
                planStatus.checked = true;
                planFeatured.checked = false;
                imgPreview.style.display = 'none';
                slugManual = false;
                applyTypeUI('per_release');
                document.querySelectorAll('.is-invalid').forEach(el => el
                    .classList.remove('is-invalid'));
            }

            // Validação do formulário
            function validateForm() {
                let ok = true;
                const name = planName.value.trim();
                const slug = planSlug.value.trim();
                const price = planPrice.value;

                [planName, planSlug, planType, planPrice].forEach(el => el
                    .classList.remove('is-invalid'));
                if (!name) {
                    planName.classList.add('is-invalid');
                    ok = false;
                }
                if (!slug) {
                    planSlug.classList.add('is-invalid');
                    ok = false;
                }
                if (!planType.value) {
                    planType.classList.add('is-invalid');
                    ok = false;
                }
                if (!price || parseFloat(price) < 0) {
                    planPrice.classList.add('is-invalid');
                    ok = false;
                }
                return ok;
            }

            // Botão "Novo Plano"
            document.getElementById('btnAddPlan').addEventListener('click',
                () => {
                    resetModal();
                    modalTitle.innerHTML =
                        '<i class="bi bi-plus-circle me-2"></i>Novo Plano';
                    modal.show();
                });

            // Editar plano (delegação de eventos)
            document.addEventListener('click', async (e) => {
                const editBtn = e.target.closest('.edit-plan');
                if (!editBtn) return;
                e.preventDefault();
                const id = editBtn.dataset.id;

                try {
                    const resp = await fetch('plans-process', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=get_plan&id_plan=${id}&csrf_token=${encodeURIComponent(csrf)}`
                    });
                    const data = await resp.json();
                    if (!data.ok) {
                        Swal.fire('Erro', data.message,
                            'error');
                        return;
                    }
                    const p = data.plan;
                    resetModal();
                    slugManual = true;

                    planId.value = p.id_plan;
                    planName.value = p.name_plan;
                    planSlug.value = p.slug_plan;
                    planDesc.value = p.description_plan || '';
                    planType.value = p.type_plan;
                    planPrice.value = p.price_plan;
                    planPriceUSD.value = p.price_usd || '';
                    planPriceAnnual.value = p.price_annual ||
                        '';
                    planAnnualQty.value = p.annual_qty || '';
                    planValidityDays.value = p.validity_days ||
                        '';
                    planRoyalty.value = p.royalty_rate;
                    planMaxArtists.value = p.max_artists || '';
                    planMaxReleases.value = p.max_releases ||
                        '';
                    planMaxTracks.value = p
                        .max_tracks_per_release || '';
                    planOrder.value = p.display_order;
                    planStatus.checked = p.is_active == 1;
                    planFeatured.checked = p.is_featured == 1;
                    planBadge.value = p.badge_text || '';
                    planImg.value = p.img_plan || '';
                    planImg.dispatchEvent(new Event('input'));

                    applyTypeUI(p.type_plan);
                    modalTitle.innerHTML =
                        '<i class="bi bi-pencil-square me-2"></i>Editar Plano';
                    modal.show();
                } catch {
                    Swal.fire('Erro', 'Falha na comunicação.',
                        'error');
                }
            });

            // Submeter formulário
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!validateForm()) {
                    Swal.fire('Atenção',
                        'Preencha todos os campos obrigatórios.',
                        'warning');
                    return;
                }

                const fd = new FormData(form);
                fd.set('csrf_token', csrf);
                fd.set('is_active', planStatus.checked ? '1' :
                    '0');
                fd.set('is_featured', planFeatured.checked ?
                    '1' : '0');

                saveBtn.disabled = true;
                saveBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>A guardar…';

                try {
                    const resp = await fetch('plans-process', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await resp.json();
                    if (data.ok) {
                        modal.hide();
                        Swal.fire({
                                icon: 'success',
                                title: 'Sucesso',
                                text: data.message,
                                timer: 1800,
                                showConfirmButton: false
                            })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message,
                            'error');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML =
                            '<i class="bi bi-save me-1"></i>Guardar';
                    }
                } catch {
                    Swal.fire('Erro', 'Falha na comunicação.',
                        'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML =
                        '<i class="bi bi-save me-1"></i>Guardar';
                }
            });

            // Alternar status
            document.addEventListener('click', async (e) => {
                const toggleBtn = e.target.closest(
                    '.toggle-plan');
                if (!toggleBtn) return;
                e.preventDefault();
                const id = toggleBtn.dataset.id;
                const active = toggleBtn.dataset.active === '1';
                const newStatus = active ? 0 : 1;
                const label = newStatus ? 'ativar' :
                    'desativar';

                const result = await Swal.fire({
                    title: `${newStatus ? 'Ativar' : 'Desativar'} plano?`,
                    text: `Confirma que pretende ${label} este plano?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: newStatus ?
                        '#22c55e' : '#6b7280',
                    confirmButtonText: 'Sim, confirmar',
                    cancelButtonText: 'Cancelar'
                });
                if (!result.isConfirmed) return;

                try {
                    const resp = await fetch('plans-process', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=toggle_plan&id_plan=${id}&is_active=${newStatus}&csrf_token=${encodeURIComponent(csrf)}`
                    });
                    const data = await resp.json();
                    if (data.ok) {
                        Swal.fire({
                                icon: 'success',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message,
                            'error');
                    }
                } catch {
                    Swal.fire('Erro', 'Falha na comunicação.',
                        'error');
                }
            });

            // Eliminar plano
            document.addEventListener('click', async (e) => {
                const delBtn = e.target.closest('.delete-plan');
                if (!delBtn) return;
                e.preventDefault();
                const id = delBtn.dataset.id;
                const name = delBtn.dataset.name;

                const result = await Swal.fire({
                    title: 'Eliminar plano?',
                    html: `Tem certeza que deseja eliminar permanentemente o plano <strong>${name}</strong>?<br><small class="text-danger">Esta ação não pode ser desfeita.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Sim, eliminar',
                    cancelButtonText: 'Cancelar'
                });
                if (!result.isConfirmed) return;

                try {
                    const resp = await fetch('plans-process', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete_plan&id_plan=${id}&csrf_token=${encodeURIComponent(csrf)}`
                    });
                    const data = await resp.json();
                    if (data.ok) {
                        Swal.fire({
                                icon: 'success',
                                title: 'Eliminado!',
                                text: data.message,
                                timer: 1800,
                                showConfirmButton: false
                            })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message,
                            'error');
                    }
                } catch {
                    Swal.fire('Erro', 'Falha na comunicação.',
                        'error');
                }
            });

        }); // fim DOMContentLoaded
    })();
    </script>
</body>

</html>