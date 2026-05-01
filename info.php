<?php
// ── status.php ──
// Mostra as principais configurações do PHP do teu servidor
// (coloca na raiz do site e acede via browser)

// Função auxiliar para formatar bytes
function format_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    $units = ['KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    $i = min($i, count($units)-1);
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

// Informações do servidor
$server   = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido';
$phpVer   = phpversion();
$sapi     = php_sapi_name();
$maxUpload    = ini_get('upload_max_filesize');
$postMax      = ini_get('post_max_size');
$maxExec      = ini_get('max_execution_time');
$maxInputTime = ini_get('max_input_time');
$memoryLimit  = ini_get('memory_limit');
$maxFileUploads = ini_get('max_file_uploads');
$displayErrors = ini_get('display_errors');
$errorReporting = error_reporting();

$disabledFunctions = ini_get('disable_functions');
$extensions = get_loaded_extensions();
sort($extensions);

// Estilo simples
echo '<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>PHP Status – Wasom Upfy</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; background: #f5f5f5; color: #333; }
        h1 { color: #FF0089; }
        table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.05); border-radius: 8px; overflow: hidden; }
        th, td { padding: .7rem 1rem; text-align: left; border-bottom: 1px solid #eee; font-size: .9rem; }
        th { background: #f9f9f9; font-weight: 600; color: #555; width: 40%; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 12px; font-size: .75rem; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        details { margin: 1.5rem 0; }
        summary { font-weight: 600; cursor: pointer; padding: .5rem 0; }
        .ext-list { display: flex; flex-wrap: wrap; gap: .3rem; }
        .ext-list span { background: #eee; padding: .2rem .5rem; border-radius: 4px; font-size: .8rem; }
    </style>
</head>
<body>
    <h1>⚙️ Configuração do PHP – Resumo</h1>
    <table>
        <tr><th>Versão do PHP</th><td>' . $phpVer . ' (' . $sapi . ')</td></tr>
        <tr><th>Servidor Web</th><td>' . htmlspecialchars($server) . '</td></tr>
        <tr><th>upload_max_filesize</th><td><strong>' . $maxUpload . '</strong></td></tr>
        <tr><th>post_max_size</th><td><strong>' . $postMax . '</strong> (total do POST permitido)</td></tr>
        <tr><th>max_file_uploads</th><td>' . $maxFileUploads . ' (número máximo de ficheiros por pedido)</td></tr>
        <tr><th>max_execution_time</th><td>' . $maxExec . ' segundos</td></tr>
        <tr><th>max_input_time</th><td>' . $maxInputTime . ' segundos</td></tr>
        <tr><th>memory_limit</th><td>' . $memoryLimit . '</td></tr>
        <tr><th>display_errors</th><td>' . ($displayErrors ? '<span class="badge badge-danger">On</span>' : '<span class="badge badge-success">Off</span>') . '</td></tr>
    </table>
    
    <h2>📦 Extensões carregadas (' . count($extensions) . ')</h2>
    <details>
        <summary>Ver lista completa</summary>
        <div class="ext-list">';
        foreach ($extensions as $ext) {
            echo '<span>' . htmlspecialchars($ext) . '</span> ';
        }
        echo '</div>
    </details>
    
    <footer style="margin-top:2rem;font-size:.8rem;color:#888;">
        <p>Script de diagnóstico – apaga este ficheiro depois de usar, por segurança.</p>
    </footer>
</body>
</html>';