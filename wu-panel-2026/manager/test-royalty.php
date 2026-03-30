<?php
// ════════════════════════════════════════════════════════════════════════
// TESTE DE DIAGNÓSTICO — Royalty Payment System
// ════════════════════════════════════════════════════════════════════════

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnóstico Royalties</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }

        .test-item {
            background: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }

        .test-pass {
            border-left-color: #28a745;
        }

        .test-fail {
            border-left-color: #dc3545;
        }

        .test-info {
            border-left-color: #17a2b8;
        }

        h1 {
            margin-bottom: 30px;
        }

        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h1>🔍 Diagnóstico do Sistema de Royalties</h1>

        <?php
        // ── Teste 1: Ficheiros existem ──
        echo '<div class="test-item test-info">
    <h5>1️⃣ Ficheiros do Sistema</h5>';

        $files_to_check = [
            'process.php'            => __DIR__ . '/process.php',
            'royalty-splits.php'     => __DIR__ . '/../pages/manager/royalty-splits.php',
            'royalty-payments.php'   => __DIR__ . '/../pages/manager/royalty-payments.php',
            'payment-guard.php'      => __DIR__ . '/include/payment-guard.php',
            'payment-sidebar.php'    => __DIR__ . '/include/payment-sidebar.php',
            'platform_admin.php'     => __DIR__ . '/../include/platform_admin.php',
        ];

        $all_files_ok = true;
        foreach ($files_to_check as $name => $path) {
            if (file_exists($path)) {
                echo "<p class='mb-0'><i class='badge bg-success'>✓</i> <strong>$name</strong> — Encontrado</p>";
            } else {
                echo "<p class='mb-0'><i class='badge bg-danger'>✗</i> <strong>$name</strong> — <em>NÃO ENCONTRADO em $path</em></p>";
                $all_files_ok = false;
            }
        }
        echo '</div>';

        // ── Teste 2: Conectividade BD ──
        echo '<div class="test-item ' . ($all_files_ok ? 'test-info' : 'test-fail') . '">
    <h5>2️⃣ Configuração e Conectividade BD</h5>';

        define('TEST_MODE', true);

        try {
            require_once __DIR__ . '/../include/platform_admin.php';
            echo "<p class='mb-0'><i class='badge bg-success'>✓</i> <strong>Platform Admin</strong> — Carregado</p>";
        } catch (Exception $e) {
            echo "<p class='mb-0'><i class='badge bg-danger'>✗</i> <strong>Platform Admin</strong> — <em>" . htmlspecialchars($e->getMessage()) . "</em></p>";
        }

        // Testa BD
        try {
            $test_query = $db->query("SELECT 1");
            echo "<p class='mb-0'><i class='badge bg-success'>✓</i> <strong>Conexão BD</strong> — OK</p>";
        } catch (Exception $e) {
            echo "<p class='mb-0'><i class='badge bg-danger'>✗</i> <strong>Conexão BD</strong> — <em>" . htmlspecialchars($e->getMessage()) . "</em></p>";
        }

        echo '</div>';

        // ── Teste 3: Tabelas ──
        echo '<div class="test-item test-info">
    <h5>3️⃣ Tabelas Base de Dados</h5>';

        $tables_to_check = ['_royalty', '_wallet', '_transaction', '_users', '_album', '_track', '_account', '_notification'];
        foreach ($tables_to_check as $table) {
            try {
                $result = $db->query("SHOW COLUMNS FROM $table LIMIT 1");
                if ($result) {
                    echo "<p class='mb-0'><i class='badge bg-success'>✓</i> <code>$table</code></p>";
                } else {
                    echo "<p class='mb-0'><i class='badge bg-warning'>⚠</i> <code>$table</code> — Existente mas sem dados</p>";
                }
            } catch (Exception $e) {
                echo "<p class='mb-0'><i class='badge bg-danger'>✗</i> <code>$table</code> — NÃO EXISTE</p>";
            }
        }

        echo '</div>';

        // ── Teste 4: Dados de Teste ──
        echo '<div class="test-item test-info">
    <h5>4️⃣ Dados Disponíveis</h5>';

        try {
            $usuarios = $db->query("SELECT COUNT(*) as total FROM _users")->fetch();
            $albums = $db->query("SELECT COUNT(*) as total FROM _album WHERE status_album='approved'")->fetch();
            $faixas = $db->query("SELECT COUNT(*) as total FROM _track WHERE status_track='active'")->fetch();
            $contas = $db->query("SELECT COUNT(*) as total FROM _account WHERE is_default=1")->fetch();

            echo "<p class='mb-0'>👥 <strong>Utilizadores:</strong> " . $usuarios['total'] . "</p>";
            echo "<p class='mb-0'>💿 <strong>Álbuns (aprovados):</strong> " . $albums['total'] . "</p>";
            echo "<p class='mb-0'>🎵 <strong>Faixas (activas):</strong> " . $faixas['total'] . "</p>";
            echo "<p class='mb-0'>🏦 <strong>Contas (default):</strong> " . $contas['total'] . "</p>";

            if ($usuarios['total'] > 0 && $albums['total'] > 0 && $faixas['total'] > 0) {
                echo '<p class="mt-3 mb-0"><i class="badge bg-success">✓</i> Dados suficientes para testar depósito</p>';
            } else {
                echo '<p class="mt-3 mb-0"><i class="badge bg-warning">⚠</i> Dados limitados para testar</p>';
            }
        } catch (Exception $e) {
            echo "<p><i class='badge bg-danger'>✗</i> Erro ao contar dados: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        echo '</div>';

        // ── Teste 5: Funções ──
        echo '<div class="test-item test-info">
    <h5>5️⃣ Funções e Helpers</h5>';

        echo '<p class="mb-0">';
        echo function_exists('requirePermission') ? '<i class="badge bg-success">✓</i> requirePermission — OK' : '<i class="badge bg-danger">✗</i> requirePermission — FALTA';
        echo '</p>';
        echo '<p class="mb-0">';
        echo function_exists('paymentPanelRequireAccess') ? '<i class="badge bg-success">✓</i> paymentPanelRequireAccess — OK' : '<i class="badge bg-danger">✗</i> paymentPanelRequireAccess — FALTA';
        echo '</p>';
        echo '<p class="mb-0">';
        echo function_exists('paymentPanelBaseUrl') ? '<i class="badge bg-success">✓</i> paymentPanelBaseUrl — OK' : '<i class="badge bg-danger">✗</i> paymentPanelBaseUrl — FALTA';
        echo '</p>';
        echo '<p class="mb-0">';
        echo function_exists('paymentPanelGetDefaultAccountForUser') ? '<i class="badge bg-success">✓</i> paymentPanelGetDefaultAccountForUser — OK' : '<i class="badge bg-danger">✗</i> paymentPanelGetDefaultAccountForUser — FALTA';
        echo '</p>';
        echo '<p class="mb-0">';
        echo function_exists('logAudit') ? '<i class="badge bg-success">✓</i> logAudit — OK' : '<i class="badge bg-warning">⚠</i> logAudit — Pode estar em funções_admin.php';
        echo '</p>';

        echo '</div>';

        // ── Teste 6: Permissões ──
        echo '<div class="test-item test-info">
    <h5>6️⃣  Permissões e Segurança</h5>';

        echo '<p class="mb-0">Admin ID: <code>' . (isset($admin_id) ? $admin_id : 'NÃO DEFINIDO') . '</code></p>';
        echo '<p class="mb-0">CSRF Token: ' . (isset($_SESSION['admin_csrf_token']) ? '✓ Existe' : '✗ Falta') . '</p>';
        echo '<p class="mb-0">Sessão ativa: ' . (isset($_SESSION['payment_control_auth']) ? '✓ Sim' : '✗ Não') . '</p>';

        // Tenta verificar permissão
        try {
            if (isset($admin_id)) {
                $perm_check = $db->prepare("
            SELECT p.perm_key FROM _admin_perms p
            JOIN _admin_roles r ON p.id_role = r.id_role
            JOIN _admin_staff s ON s.id_role = r.id_role
            WHERE s.id_employees = ? AND p.perm_key = 'finances.edit'
            LIMIT 1
        ");
                $perm_check->execute([$admin_id]);
                if ($perm_check->fetch()) {
                    echo '<p class="mb-0"><i class="badge bg-success">✓</i> <strong>finances.edit</strong> — Permissão OK</p>';
                } else {
                    echo '<p class="mb-0"><i class="badge bg-warning">⚠</i> finances.edit — Sem permissão (espera-se erro ao testar)</p>';
                }
            }
        } catch (Exception $e) {
            echo '<p class="mb-0"><i class="badge bg-warning">⚠</i> Verificação de permissão indisponível</p>';
        }

        echo '</div>';

        // ── Teste 7: Diretórios ──
        echo '<div class="test-item test-info">
    <h5>7️⃣ Diretórios de Upload</h5>';

        $upload_dirs = [
            'Royalties' => dirname(__DIR__, 4) . '/assets/payment/uploads/royalties/',
        ];

        foreach ($upload_dirs as $name => $dir) {
            if (is_dir($dir)) {
                echo "<p class='mb-0'><i class='badge bg-success'>✓</i> <code>$dir</code> — Existe</p>";
            } else {
                echo "<p class='mb-0'><i class='badge bg-danger'>✗</i> <code>$dir</code> — <strong>NÃO EXISTE</strong> (será criado ao fazer upload)</p>";
            }
        }

        echo '</div>';

        // ── Teste 8: URLs ──
        echo '<div class="test-item test-info">
    <h5>8️⃣ URLs Configuradas</h5>';

        echo '<p class="mb-0"><strong>APP_URL:</strong> <code>' . (defined('APP_URL') ? APP_URL : 'NÃO DEFINIDO') . '</code></p>';
        echo '<p class="mb-0"><strong>ADMIN_PATH:</strong> <code>' . (defined('ADMIN_PATH') ? ADMIN_PATH : 'NÃO DEFINIDO') . '</code></p>';

        if (function_exists('paymentPanelBaseUrl')) {
            echo '<p class="mb-0"><strong>paymentPanelBaseUrl():</strong> <code>' . paymentPanelBaseUrl() . '</code></p>';
            echo '<p class="mb-0"><strong>Process Endpoint:</strong> <code>' . paymentPanelBaseUrl() . '/process</code></p>';
        }

        echo '</div>';

        ?>

    </div>

    <div class="container mt-5">
        <div class="alert alert-info">
            <strong>ℹ️ Próximos Passos:</strong>
            <ul class="mb-0 mt-2">
                <li>Se todos os testes passarem, tenta fazer um depósito testando pelo formulário</li>
                <li>Se algum teste falhar, executa this diagnóstico e partilha os resultados</li>
                <li>Verifica a consola do navegador (F12) para erros JavaScript</li>
                <li>Verifica o ficheiro de log do PHP: <code>xampp/php/logs/php_error_log</code></li>
            </ul>
        </div>
    </div>

</body>

</html>