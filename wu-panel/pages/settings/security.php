<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Segurança Avançada do Painel
// Arquivo: admin/pages/settings/security.php
// Rota: admin/settings/security
// Só acessível por super_admin
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';

if ($admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH . '', ['err' => 'forbidden']);
}

// ── Carregar configs da BD ──
$cfg = [];
$rows = $db->query("SELECT config_key, config_value FROM _admin_config")->fetchAll();
foreach ($rows as $r) $cfg[$r['config_key']] = $r['config_value'];

$current_path  = $cfg['admin_path']       ?? ADMIN_PATH;
$wl_on         = ($cfg['ip_whitelist_on'] ?? '0') === '1';
$basic_on      = ($cfg['basic_auth_on']   ?? '0') === '1';
$path_changed  = $cfg['path_last_changed'] ?? '—';
$path_prev     = $cfg['admin_path_prev']   ?? ($cfg['path_prev'] ?? '');

// ── IPs da whitelist ──
$ips = $db->query("
    SELECT i.*, e.first_name, e.second_name
    FROM _admin_ip_whitelist i
    LEFT JOIN _employees e ON e.id_employees = i.added_by
    ORDER BY i.active DESC, i.creat_ip DESC
")->fetchAll();
$active_ips = count(array_filter($ips, static fn(array $row): bool => (int)($row['active'] ?? 0) === 1));

// ── Tentativas bloqueadas (últimas 20) ──
$blocked_log = $db->query("
    SELECT * FROM _admin_access_log
    ORDER BY creat_log DESC LIMIT 20
")->fetchAll();

// ── Feedback ──
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'wl_saved'      => ['success', 'bi-check-circle', 'Configuração de whitelist actualizada.'],
    'ip_added'      => ['success', 'bi-check-circle', 'IP adicionado à whitelist.'],
    'ip_removed'    => ['success', 'bi-check-circle', 'IP removido da whitelist.'],
    'ip_toggled'    => ['success', 'bi-check-circle', 'Estado do IP alterado.'],
    'path_changed'  => ['success', 'bi-shield-check', 'Caminho do painel alterado com sucesso.'],
    'htaccess_ok'   => ['success', 'bi-file-code', '.htaccess regenerado com sucesso.'],
    'basic_saved'   => ['success', 'bi-check-circle', 'Basic Auth actualizado.'],
    'error'         => ['danger',  'bi-x-circle',     'Ocorreu um erro. Tenta novamente.'],
    'no_write'      => ['danger',  'bi-x-circle',     'Sem permissão de escrita no servidor. Verifica as permissões da pasta.'],
    'path_exists'   => ['danger',  'bi-x-circle',     'Esse caminho já existe no servidor. Escolhe outro.'],
    'path_invalid'  => ['danger',  'bi-x-circle',     'Caminho inválido. Usa apenas letras, números e hífen.'],
    'rename_failed' => ['danger',  'bi-x-circle',     'Nao foi possivel renomear a pasta do painel. Nada foi alterado.'],
    'wl_no_ip'      => ['danger',  'bi-x-circle',     'Nao foi possivel activar a whitelist sem um IP valido.'],
    'self_ip_blocked' => ['warning', 'bi-shield-exclamation', 'Nao podes desactivar ou remover o teu proprio IP enquanto a whitelist esta activa.'],
    'last_ip_blocked' => ['warning', 'bi-shield-fill-exclamation', 'A whitelist precisa de pelo menos um IP activo.'],
    default         => null,
};

// IP actual do admin
$my_ip = $_SERVER['REMOTE_ADDR'] ?? '—';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <title>Segurança Avançada — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        .sec-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .sec-card h5 {
            font-size: .95rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sec-card h5 i {
            color: #FF0089;
        }

        .sec-desc {
            font-size: .82rem;
            opacity: .65;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .path-display {
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: #FF0089;
            background: rgba(255, 0, 137, .08);
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px dashed rgba(255, 0, 137, .3);
            display: inline-block;
            letter-spacing: .5px;
        }

        .ip-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .84rem;
        }

        .ip-row:last-child {
            border-bottom: none;
        }

        .ip-addr {
            font-family: monospace;
            font-weight: 600;
        }

        .ip-label {
            opacity: .6;
            font-size: .78rem;
        }

        .ip-actions {
            margin-left: auto;
            display: flex;
            gap: 6px;
        }

        .warn-box {
            background: rgba(234, 179, 8, .08);
            border: 1px solid rgba(234, 179, 8, .3);
            border-radius: 10px;
            padding: 14px 16px;
            font-size: .82rem;
            color: #92400e;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .danger-box {
            background: rgba(239, 68, 68, .06);
            border: 1px solid rgba(239, 68, 68, .2);
            border-radius: 10px;
            padding: 14px 16px;
            font-size: .82rem;
            color: #991b1b;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .checklist {
            list-style: none;
            padding: 0;
            margin: 10px 0 0;
        }

        .checklist li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 6px;
            font-size: .82rem;
        }

        .checklist li i {
            color: #eab308;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .log-row td {
            font-size: .78rem;
            vertical-align: middle;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #e8e8f0;
            border-radius: 24px;
            transition: .3s;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .3s;
        }

        .toggle-switch input:checked+.toggle-slider {
            background: #FF0089;
        }

        .toggle-switch input:checked+.toggle-slider:before {
            transform: translateX(20px);
        }

        .my-ip-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(34, 197, 94, .1);
            border: 1px solid rgba(34, 197, 94, .3);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .78rem;
            color: #166534;
            font-family: monospace;
            font-weight: 600;
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

                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1">
                            <i class="bi bi-shield-lock-fill me-2" style="color:#FF0089"></i>
                            Segurança Avançada do Painel
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Segurança</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <span class="badge bg-danger py-2 px-3">
                            <i class="bi bi-shield-fill me-1"></i>Só Super Administrador
                        </span>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-xl-8">

                        <!-- ══ 1. Caminho do Painel ══ -->
                        <div class="sec-card">
                            <h5><i class="bi bi-folder2-open"></i> Caminho do Painel</h5>
                            <p class="sec-desc">
                                O caminho da URL do painel. Mudar para algo imprevisível elimina
                                ~95% dos ataques automatizados que procuram
                                <code><?php echo '/' . ADMIN_PATH . '/'; ?></code>.
                            </p>

                            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                                <div>
                                    <div style="font-size:.74rem;opacity:.6;margin-bottom:4px">CAMINHO ACTUAL</div>
                                    <div class="path-display">/<?php echo htmlspecialchars($current_path); ?>/</div>
                                </div>
                                <?php if ($path_changed && $path_changed !== '—' && $path_changed !== ''): ?>
                                    <div>
                                        <div style="font-size:.74rem;opacity:.6;margin-bottom:4px">ÚLTIMA ALTERAÇÃO</div>
                                        <div style="font-size:.84rem">
                                            <?php echo htmlspecialchars(adm_fmt_date($path_changed)); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="warn-box">
                                <strong>⚠ Atenção — lê antes de alterar:</strong>
                                <ul class="checklist">
                                    <li><i class="bi bi-exclamation-triangle-fill"></i>Após alterar, a pasta do servidor
                                        <strong>deve ser renomeada manualmente</strong> para o novo nome.
                                    </li>
                                    <li><i class="bi bi-exclamation-triangle-fill"></i>O sistema tentará renomear
                                        automaticamente — se falhar, terás de o fazer via ficheiro manager.</li>
                                    <li><i class="bi bi-exclamation-triangle-fill"></i>O <code>config.php</code> e o
                                        <code>.htaccess</code> são actualizados automaticamente.
                                    </li>
                                    <li><i class="bi bi-exclamation-triangle-fill"></i>Partilha o novo caminho com a
                                        equipa <strong>antes</strong> de guardar.</li>
                                </ul>
                            </div>

                            <form method="POST"
                                action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process"
                                id="form-path" onsubmit="return confirmPathChange()">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                <input type="hidden" name="action" value="change_path" />
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-size:.8rem;font-weight:600">
                                            Novo caminho <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text" style="font-size:.82rem">/wasomupfy/</span>
                                            <input type="text" class="form-control" name="new_path" id="inp-new-path"
                                                placeholder="ex: wu-panel" pattern="[a-z0-9\-]{3,40}"
                                                maxlength="40"
                                                title="Apenas letras minúsculas, números e hífen (3-40 chars)"
                                                required />
                                            <span class="input-group-text" style="font-size:.82rem">/</span>
                                        </div>
                                        <div class="form-text">Apenas letras minúsculas, números e hífen. 3-40
                                            caracteres.</div>
                                    </div>
                                    <div class="col-md-auto">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-arrow-repeat me-1"></i>Rodar Caminho
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Regenerar .htaccess sem mudar caminho -->
                            <hr style="margin:16px 0;opacity:.15">
                            <form method="POST"
                                action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                <input type="hidden" name="action" value="regen_htaccess" />
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-file-code me-1"></i>Regenerar .htaccess (sem mudar caminho)
                                </button>
                            </form>
                        </div>

                        <!-- ══ 2. Whitelist de IPs ══ -->
                        <div class="sec-card">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                <div>
                                    <h5><i class="bi bi-shield-shaded"></i> Whitelist de IPs</h5>
                                    <p class="sec-desc mb-0">
                                        Quando activa, só os IPs listados conseguem aceder ao painel.
                                        Todos os outros recebem um 404 genérico.
                                    </p>
                                </div>
                                <!-- Toggle on/off -->
                                <form method="POST"
                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process"
                                    style="flex-shrink:0">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                    <input type="hidden" name="action" value="toggle_whitelist" />
                                    <label class="toggle-switch"
                                        title="<?php echo $wl_on ? 'Desactivar whitelist' : 'Activar whitelist'; ?>">
                                        <input type="checkbox" <?php echo $wl_on ? 'checked' : ''; ?>
                                            onchange="this.form.submit()" />
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <div style="font-size:.7rem;text-align:center;margin-top:2px;opacity:.6">
                                        <?php echo $wl_on ? 'Activa' : 'Inactiva'; ?>
                                    </div>
                                </form>
                            </div>

                            <?php if ($wl_on && $active_ips === 0): ?>
                                <div class="danger-box">
                                    <i class="bi bi-exclamation-octagon me-1"></i>
                                    <strong>Atenção:</strong> A whitelist está activa mas não tem nenhum IP registado.
                                    Isso significa que <strong>ninguém consegue aceder ao painel</strong>, incluindo tu.
                                    Adiciona o teu IP abaixo imediatamente.
                                </div>
                            <?php endif; ?>

                            <!-- IP actual -->
                            <div class="mb-3">
                                <span style="font-size:.78rem;opacity:.7">O teu IP actual:</span>
                                <span class="my-ip-chip ms-2">
                                    <i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($my_ip); ?>
                                </span>
                                <form method="POST"
                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process"
                                    style="display:inline" class="ms-2">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                    <input type="hidden" name="action" value="add_ip" />
                                    <input type="hidden" name="ip_address"
                                        value="<?php echo htmlspecialchars($my_ip); ?>" />
                                    <input type="hidden" name="label" value="O meu dispositivo" />
                                    <button type="submit" class="btn btn-outline-success btn-sm"
                                        style="font-size:.75rem;padding:2px 10px">
                                        <i class="bi bi-plus"></i> Adicionar meu IP
                                    </button>
                                </form>
                            </div>

                            <!-- Lista de IPs -->
                            <?php if (!empty($ips)): ?>
                                <div class="mb-3">
                                    <?php foreach ($ips as $ip): ?>
                                        <div class="ip-row">
                                            <div>
                                                <div class="ip-addr"><?php echo htmlspecialchars($ip['ip_address']); ?></div>
                                                <div class="ip-label">
                                                    <?php echo htmlspecialchars($ip['label'] ?? '—'); ?>
                                                    · adicionado por
                                                    <?php echo htmlspecialchars(trim(($ip['first_name'] ?? '') . ' ' . ($ip['second_name'] ?? '')) ?: 'sistema'); ?>
                                                    · <?php echo adm_fmt_date($ip['creat_ip']); ?>
                                                </div>
                                            </div>
                                            <div class="ip-actions">
                                                <?php if ($ip['active']): ?>
                                                    <span class="badge bg-success" style="font-size:.65rem">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary" style="font-size:.65rem">Inactivo</span>
                                                <?php endif; ?>

                                                <form method="POST"
                                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process"
                                                    style="display:inline">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                                    <input type="hidden" name="action" value="toggle_ip" />
                                                    <input type="hidden" name="ip_id"
                                                        value="<?php echo (int)$ip['id_ip']; ?>" />
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                        style="font-size:.72rem;padding:2px 8px"
                                                        title="<?php echo $ip['active'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i
                                                            class="bi bi-<?php echo $ip['active'] ? 'pause' : 'play'; ?>-fill"></i>
                                                    </button>
                                                </form>
                                                <form method="POST"
                                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process"
                                                    style="display:inline"
                                                    onsubmit="return confirm('Remover este IP da whitelist?')">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                                    <input type="hidden" name="action" value="remove_ip" />
                                                    <input type="hidden" name="ip_id"
                                                        value="<?php echo (int)$ip['id_ip']; ?>" />
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        style="font-size:.72rem;padding:2px 8px">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="font-size:.83rem;opacity:.5;margin-bottom:12px">Nenhum IP registado ainda.</p>
                            <?php endif; ?>

                            <!-- Adicionar novo IP -->
                            <form method="POST"
                                action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings/security-process"
                                class="d-flex gap-2 flex-wrap">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                <input type="hidden" name="action" value="add_ip" />
                                <input type="text" class="form-control form-control-sm" name="ip_address"
                                    placeholder="Ex: 192.168.1.100 ou 2001:db8::1" style="max-width:220px" required />
                                <input type="text" class="form-control form-control-sm" name="label"
                                    placeholder="Etiqueta (ex: Escritório)" style="max-width:180px" />
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus me-1"></i>Adicionar IP
                                </button>
                            </form>
                        </div>

                        <!-- ══ Log de tentativas bloqueadas ══ -->
                        <div class="sec-card">
                            <h5><i class="bi bi-journal-text"></i> Tentativas de Acesso Bloqueadas</h5>
                            <p class="sec-desc">Últimas 20 tentativas de acesso por IPs não autorizados.</p>

                            <?php if (empty($blocked_log)): ?>
                                <p style="font-size:.83rem;opacity:.5">Nenhuma tentativa registada.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="font-size:.72rem">IP</th>
                                                <th style="font-size:.72rem">Caminho</th>
                                                <th style="font-size:.72rem">Motivo</th>
                                                <th style="font-size:.72rem">Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($blocked_log as $log): ?>
                                                <tr class="log-row">
                                                    <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                                    <td
                                                        style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                                        <?php echo htmlspecialchars($log['path_tried'] ?? '—'); ?>
                                                    </td>
                                                    <td><span class="badge bg-danger"
                                                            style="font-size:.65rem"><?php echo htmlspecialchars($log['reason'] ?? '—'); ?></span>
                                                    </td>
                                                    <td><?php echo adm_fmt_date($log['creat_log']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div><!-- /col -->

                    <!-- Coluna direita — dicas -->
                    <div class="col-xl-4">
                        <div class="sec-card" style="position:sticky;top:20px">
                            <h5><i class="bi bi-lightbulb"></i> Guia de Segurança</h5>

                            <div style="font-size:.82rem;line-height:1.7">
                                <p><strong style="color:#FF0089">Camadas implementadas:</strong></p>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Caminho obscuro</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i
                                        class="bi bi-<?php echo $wl_on ? 'check-circle-fill text-success' : 'circle text-muted'; ?>"></i>
                                    <span>Whitelist de IPs</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Autenticação CSRF</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Rate limiting / bloqueio por tentativas</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Auditoria de todas as acções</span>
                                </div>

                                <hr style="opacity:.15">

                                <p><strong>Rotação recomendada:</strong></p>
                                <p style="opacity:.7">Muda o caminho do painel a cada 3-6 meses ou sempre que um
                                    funcionário sai da equipa.</p>

                                <p><strong>IPs dinâmicos:</strong></p>
                                <p style="opacity:.7">Se a tua equipa usa IPs dinâmicos (ISP residencial), usa a
                                    whitelist apenas em produção com IPs fixos.</p>

                                <p><strong>Após rodar o caminho:</strong></p>
                                <ol style="opacity:.7;padding-left:16px">
                                    <li>O sistema tenta renomear a pasta automaticamente</li>
                                    <li>Confirma que <code>config.php</code> foi actualizado</li>
                                    <li>Avisa a equipa do novo URL</li>
                                    <li>Faz logout e login no novo URL</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                </div><!-- /row -->
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        function confirmPathChange() {
            var newPath = document.getElementById('inp-new-path').value.trim();
            if (!newPath) return false;
            return confirm(
                '⚠ ATENÇÃO — Rotação do Caminho\n\n' +
                'Novo caminho: /' + newPath + '/\n\n' +
                'O sistema irá:\n' +
                '1. Tentar renomear a pasta do servidor\n' +
                '2. Actualizar o config.php\n' +
                '3. Regenerar o .htaccess\n\n' +
                'Após confirmar, serás redirecionado para o novo URL.\n' +
                'Partilhaste o novo caminho com a equipa?\n\n' +
                'Confirmar?'
            );
        }
    </script>
</body>

</html>