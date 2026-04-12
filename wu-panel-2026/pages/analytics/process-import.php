<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Admin: Processamento de Streams (Country Only)
// Arquivo: wu-panel-2026/pages/analytics/process-import.php
// ═══════════════════════════════════════════════════════════════════════════
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.edit');

// ── JSON output único ─────────────────────────────────────────────────────
$jsonResponseSent = false;

function jsonOut(bool $ok, string $message, array $extra = [], int $status = 200): never
{
    global $jsonResponseSent;
    $jsonResponseSent = true;
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode(
        array_merge(['ok' => $ok, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

register_shutdown_function(static function (): void {
    global $jsonResponseSent;
    if ($jsonResponseSent) return;
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Erro interno no servidor.'], JSON_UNESCAPED_UNICODE);
});

// ── Helpers de normalização ───────────────────────────────────────────────
function normalizeText(mixed $v): string
{
    return is_scalar($v) ? trim((string)$v) : '';
}

function normalizeInteger(mixed $v): int
{
    $v = normalizeText($v);
    if ($v === '') return 0;
    $n = preg_replace('/[^\d-]/', '', str_replace(',', '', $v));
    return ($n === null || $n === '' || $n === '-') ? 0 : (int)$n;
}

function normalizeDecimal(mixed $v): float
{
    $v = normalizeText($v);
    if ($v === '') return 0.0;
    $v = preg_replace('/[^\d,.\-]/', '', $v) ?? '';
    if ($v === '') return 0.0;
    $lc = strrpos($v, ',');
    $ld = strrpos($v, '.');
    if ($lc !== false && $ld !== false) {
        if ($lc > $ld) {
            $v = str_replace(['.', ','], ['', '.'], $v);
        } else {
            $v = str_replace(',', '', $v);
        }
    } elseif ($lc !== false) {
        $v = str_replace(['.', ','], ['', '.'], $v);
    }
    return is_numeric($v) ? (float)$v : 0.0;
}

function normalizeMonth(int $m): int
{
    return ($m >= 1 && $m <= 12) ? $m : 1;
}

function normalizeCountryCode(mixed $v): string
{
    $v = preg_replace('/[^a-zA-Z]/', '', normalizeText($v)) ?? '';
    return strtolower(substr($v, 0, 2));
}

function mappedCell(array $row, array $mappings, string $field): string
{
    return array_key_exists($field, $mappings) ? normalizeText($row[(int)$mappings[$field]] ?? '') : '';
}

function resolveTrackId(PDOStatement $stmt, array &$cache, string $raw): int
{
    $raw = normalizeText($raw);
    if ($raw === '') return 0;
    if (ctype_digit($raw)) return (int)$raw;
    $key = strtoupper($raw);
    if (!array_key_exists($key, $cache)) {
        $stmt->execute([$key]);
        $cache[$key] = (int)($stmt->fetchColumn() ?: 0);
    }
    return $cache[$key];
}

// ── Gate: método + CSRF ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.', [], 405);
}
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessão expirada. Recarregue a página.', [], 419);
}

$db     = getDB();
$action = trim((string)($_POST['action'] ?? ''));

// ── Carregar hash da senha do admin ───────────────────────────────────────
$hashStmt = $db->prepare('SELECT password_employees FROM _employees WHERE id_employees = ?');
$hashStmt->execute([$admin_id]);
$admin_password_hash = (string)($hashStmt->fetchColumn() ?: '');

function verifyAdminPassword(string $password): void
{
    global $admin_password_hash;
    if ($password === '') {
        jsonOut(false, 'Informe a senha do admin para confirmar.');
    }
    if ($admin_password_hash === '' || !password_verify($password, $admin_password_hash)) {
        jsonOut(false, 'Senha incorreta. Operação cancelada.');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTION: import_csv  ── importar CSV de streams por país
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'import_csv') {
    $selectedYear = (int)($_POST['year'] ?? date('Y'));
    if ($selectedYear < 2000 || $selectedYear > ((int)date('Y') + 1)) {
        jsonOut(false, 'Ano inválido.');
    }

    $mappings = json_decode((string)($_POST['mappings'] ?? ''), true);
    $csvData  = json_decode((string)($_POST['csv_data']  ?? ''), true);

    if (!is_array($mappings) || empty($mappings))  jsonOut(false, 'Mapeamento inválido.');
    if (!is_array($csvData)  || empty($csvData))   jsonOut(false, 'Ficheiro CSV sem dados válidos.');
    if (!array_key_exists('id_track',     $mappings)) jsonOut(false, 'Mapeie a coluna da faixa (id_track).');
    if (!array_key_exists('country_code', $mappings)) jsonOut(false, 'Mapeie a coluna do país (country_code).');

    $trackLookup = $db->prepare('SELECT id_track FROM _track WHERE UPPER(isrc) = ? LIMIT 1');
    $insertStmt  = $db->prepare('
        INSERT INTO _stream_country
            (id_track, year_stream, month_stream, country_code, country_name, streams, revenue)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            streams      = streams  + VALUES(streams),
            revenue      = revenue  + VALUES(revenue),
            country_name = COALESCE(VALUES(country_name), country_name)
    ');

    $trackCache = [];
    $imported   = 0;
    $skipped    = 0;

    $db->beginTransaction();
    try {
        foreach ($csvData as $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            $trackId     = resolveTrackId($trackLookup, $trackCache, mappedCell($row, $mappings, 'id_track'));
            $countryCode = normalizeCountryCode(mappedCell($row, $mappings, 'country_code'));
            $countryName = mappedCell($row, $mappings, 'country_name');
            $streams     = max(0, normalizeInteger(mappedCell($row, $mappings, 'streams')));
            $revenue     = max(0.0, normalizeDecimal(mappedCell($row, $mappings, 'revenue')));
            $rawMonth    = mappedCell($row, $mappings, 'month_stream');
            $month       = normalizeMonth($rawMonth !== '' ? (int)$rawMonth : 1);

            if ($trackId <= 0 || $countryCode === '') {
                $skipped++;
                continue;
            }

            $insertStmt->execute([$trackId, $selectedYear, $month, $countryCode, $countryName ?: null, $streams, $revenue]);
            $imported++;
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('[CSV IMPORT] ' . $e->getMessage());
        jsonOut(false, 'Erro ao processar a importação.');
    }

    jsonOut(true, 'Importação concluída.', ['imported' => $imported, 'skipped' => $skipped]);
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTION: manual_add  ── adicionar registo de stream por país + loja (sync)
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'manual_add') {
    $trackId     = (int)($_POST['id_track']      ?? 0);
    $year        = (int)($_POST['year_stream']   ?? 0);
    $month       = normalizeMonth((int)($_POST['month_stream'] ?? 0));
    $countryCode = normalizeCountryCode($_POST['country_code'] ?? '');
    $countryName = normalizeText($_POST['country_name'] ?? '');
    $streams     = max(0, normalizeInteger($_POST['streams']   ?? 0));
    $revenue     = max(0.0, normalizeDecimal($_POST['revenue'] ?? 0));
    $storeId     = (int)($_POST['id_store']      ?? 0);   // opcional → sinc _stream
    $downloads   = max(0, normalizeInteger($_POST['downloads'] ?? 0));
    $password    = trim((string)($_POST['password_confirm'] ?? ''));

    if ($trackId <= 0)                               jsonOut(false, 'Selecione uma faixa válida.');
    if ($year < 2000 || $year > (int)date('Y') + 1) jsonOut(false, 'Ano inválido.');
    if ($countryCode === '')                          jsonOut(false, 'Selecione um país válido.');

    verifyAdminPassword($password);

    $db->beginTransaction();
    try {
        // 1. Inserir / actualizar _stream_country
        $stmtSc = $db->prepare('
            INSERT INTO _stream_country
                (id_track, year_stream, month_stream, country_code, country_name, streams, revenue)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                streams      = streams + VALUES(streams),
                revenue      = revenue + VALUES(revenue),
                country_name = COALESCE(VALUES(country_name), country_name)
        ');
        $stmtSc->execute([$trackId, $year, $month, $countryCode, $countryName ?: null, $streams, $revenue]);

        // 2. Se loja indicada → sincronizar _stream (upsert)
        if ($storeId > 0) {
            $stmtS = $db->prepare('
                INSERT INTO _stream
                    (id_track, id_store, year_stream, month_stream, streams, downloads, revenue)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    streams   = streams   + VALUES(streams),
                    downloads = downloads + VALUES(downloads),
                    revenue   = revenue   + VALUES(revenue)
            ');
            $stmtS->execute([$trackId, $storeId, $year, $month, $streams, $downloads, $revenue]);
        }

        $db->commit();

        // 3. Retornar o registo completo para atualização live da tabela
        $fetchStmt = $db->prepare('
            SELECT sc.id_stream_country, sc.id_track, t.title_track,
                   sc.country_code, sc.country_name,
                   sc.year_stream, sc.month_stream, sc.streams, sc.revenue
            FROM _stream_country sc
            JOIN _track t ON t.id_track = sc.id_track
            WHERE sc.id_track = ? AND sc.year_stream = ? AND sc.month_stream = ? AND sc.country_code = ?
        ');
        $fetchStmt->execute([$trackId, $year, $month, $countryCode]);
        $record = $fetchStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        jsonOut(true, 'Registo adicionado com sucesso.', ['record' => $record]);
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('[MANUAL ADD] ' . $e->getMessage());
        jsonOut(false, 'Erro ao guardar o registo. Tente novamente.');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTION: update_record  ── editar por id_stream_country (PK)
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'update_record') {
    $id          = (int)($_POST['id']            ?? 0);
    $countryCode = normalizeCountryCode($_POST['country_code'] ?? '');
    $countryName = normalizeText($_POST['country_name'] ?? '');
    $year        = (int)($_POST['year_stream']   ?? 0);
    $month       = normalizeMonth((int)($_POST['month_stream'] ?? 0));
    $streams     = max(0, normalizeInteger($_POST['streams']   ?? 0));
    $revenue     = max(0.0, normalizeDecimal($_POST['revenue'] ?? 0));
    $storeId     = (int)($_POST['id_store']      ?? 0);
    $downloads   = max(0, normalizeInteger($_POST['downloads'] ?? 0));
    $password    = trim((string)($_POST['password_confirm']    ?? ''));

    if ($id <= 0) {
        jsonOut(false, 'ID de registo inválido.');
    }
    if ($year < 2000 || $year > (int)date('Y') + 1) {
        jsonOut(false, 'Ano inválido.');
    }
    if ($countryCode === '') {
        jsonOut(false, 'Código de país obrigatório.');
    }

    verifyAdminPassword($password);

    // 1. Obter todos os dados originais do registo (para detectar mudanças de período e loja)
    $originalStmt = $db->prepare('
        SELECT id_track, year_stream AS old_year, month_stream AS old_month
        FROM _stream_country
        WHERE id_stream_country = ?
    ');
    $originalStmt->execute([$id]);
    $original = $originalStmt->fetch(PDO::FETCH_ASSOC);

    if (!$original) {
        jsonOut(false, 'Registo não encontrado.');
    }

    $trackId   = (int)$original['id_track'];
    $oldYear   = (int)$original['old_year'];
    $oldMonth  = (int)$original['old_month'];

    // 2. Verificar se houve alteração nos campos principais de _stream_country
    //    (opcional: pular update se nada mudou)
    $checkStmt = $db->prepare('
        SELECT country_code, country_name, year_stream, month_stream, streams, revenue
        FROM _stream_country
        WHERE id_stream_country = ?
    ');
    $checkStmt->execute([$id]);
    $current = $checkStmt->fetch(PDO::FETCH_ASSOC);

    $mainFieldsChanged = false;
    if (
        $current['country_code'] !== $countryCode ||
        ($current['country_name'] ?? '') !== ($countryName ?: '') ||
        $current['year_stream'] != $year ||
        $current['month_stream'] != $month ||
        $current['streams'] != $streams ||
        abs((float)$current['revenue'] - $revenue) > 0.00001
    ) {
        $mainFieldsChanged = true;
    }

    // 3. Iniciar transação
    $db->beginTransaction();
    try {
        // 3.1 Atualizar _stream_country se necessário
        if ($mainFieldsChanged) {
            $stmtSc = $db->prepare('
                UPDATE _stream_country
                SET country_code = ?,
                    country_name = ?,
                    year_stream  = ?,
                    month_stream = ?,
                    streams      = ?,
                    revenue      = ?
                WHERE id_stream_country = ?
            ');
            $stmtSc->execute([
                $countryCode,
                $countryName ?: null,
                $year,
                $month,
                $streams,
                $revenue,
                $id
            ]);
        }

        // 3.2 Sincronizar com _stream apenas se uma loja foi selecionada
        if ($storeId > 0) {
            // Determinar se o período (ano/mês) mudou
            $periodChanged = ($oldYear != $year || $oldMonth != $month);

            if ($periodChanged) {
                // Período mudou → eliminar TODOS os registos antigos em _stream para esta faixa no período ANTIGO
                $deleteOld = $db->prepare('
                    DELETE FROM _stream
                    WHERE id_track = ? AND year_stream = ? AND month_stream = ?
                ');
                $deleteOld->execute([$trackId, $oldYear, $oldMonth]);
            }

            // Se o período não mudou, mas a loja pode ter mudado, precisamos verificar se já existe um registo
            // para a loja escolhida. Se existir, atualizamos; se não, criamos.
            // Além disso, se a loja mudou, devemos eliminar o registo da loja antiga (se existir e não for a mesma).
            if (!$periodChanged) {
                // Verificar qual era a loja anterior (se houver) para este período
                $oldStoreStmt = $db->prepare('
                    SELECT id_store FROM _stream
                    WHERE id_track = ? AND year_stream = ? AND month_stream = ?
                    LIMIT 1
                ');
                $oldStoreStmt->execute([$trackId, $year, $month]);
                $oldStoreId = (int)($oldStoreStmt->fetchColumn() ?: 0);

                if ($oldStoreId > 0 && $oldStoreId != $storeId) {
                    // Loja mudou: eliminar o registo da loja antiga
                    $deleteOldStore = $db->prepare('
                        DELETE FROM _stream
                        WHERE id_track = ? AND year_stream = ? AND month_stream = ? AND id_store = ?
                    ');
                    $deleteOldStore->execute([$trackId, $year, $month, $oldStoreId]);
                }
            }

            // 3.3 Inserir ou atualizar o registo na _stream para a nova combinação
            //     Se o período mudou, a tabela já foi limpa, então podemos inserir diretamente.
            //     Se não mudou, fazemos upsert normalmente.
            $stmtS = $db->prepare('
                INSERT INTO _stream
                    (id_track, id_store, year_stream, month_stream, streams, downloads, revenue)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    streams   = VALUES(streams),
                    downloads = VALUES(downloads),
                    revenue   = VALUES(revenue)
            ');
            $stmtS->execute([
                $trackId,
                $storeId,
                $year,
                $month,
                $streams,
                $downloads,
                $revenue
            ]);
        } else {
            // Se nenhuma loja foi selecionada (storeId = 0), opcionalmente podemos remover
            // todos os registos em _stream para este período, pois a sincronização foi desativada.
            // Isso evita dados órfãos. (Comportamento opcional, comente se não desejar)
            $deleteAll = $db->prepare('
                DELETE FROM _stream
                WHERE id_track = ? AND year_stream = ? AND month_stream = ?
            ');
            $deleteAll->execute([$trackId, $year, $month]);
        }

        $db->commit();
        jsonOut(true, 'Registo actualizado com sucesso.');
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->errorInfo[1] == 1062) {
            jsonOut(false, 'Já existe um registo com esta faixa, ano, mês e país. Escolha uma combinação diferente.');
        }
        error_log('[UPDATE RECORD] PDOException: ' . $e->getMessage());
        jsonOut(false, 'Erro ao actualizar o registo: ' . $e->getMessage());
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('[UPDATE RECORD] Throwable: ' . $e->getMessage());
        jsonOut(false, 'Erro inesperado ao actualizar o registo.');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTION: delete_record  ── eliminar por id_stream_country (PK)
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'delete_record') {
    $id       = (int)($_POST['id']               ?? 0);
    $password = trim((string)($_POST['password_confirm'] ?? ''));

    if ($id <= 0) jsonOut(false, 'ID inválido.');

    verifyAdminPassword($password);

    // Get track/year/month for cascade delete
    $infoStmt = $db->prepare('SELECT id_track, year_stream, month_stream FROM _stream_country WHERE id_stream_country = ?');
    $infoStmt->execute([$id]);
    $info = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        jsonOut(false, 'Registo não encontrado.');
    }

    $db->beginTransaction();
    try {
        // Delete related _stream records (all stores for this track/year/month/country)
        $cascadeStmt = $db->prepare('DELETE FROM _stream WHERE id_track = ? AND year_stream = ? AND month_stream = ?');
        $cascadeStmt->execute([$info['id_track'], $info['year_stream'], $info['month_stream']]);

        // Delete _stream_country
        $stmt = $db->prepare('DELETE FROM _stream_country WHERE id_stream_country = ?');
        $stmt->execute([$id]);

        $db->commit();
        jsonOut(true, 'Registo eliminado (incluindo ' . $cascadeStmt->rowCount() . ' em _stream).');
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('[DELETE RECORD CASCADE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao eliminar (rollback).');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTION: fetch_table  ── browser de tabelas da BD
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'fetch_table') {
    // Tabelas permitidas (whitelist de segurança)
    $ALLOWED_TABLES = [
        '_stream',
        '_stream_country',
        '_track',
        '_store',
        '_album',
        '_artist',
        '_users',
        '_royalty',
        '_wallet',
        '_withdrawal',
        '_payment',
    ];

    $table   = trim((string)($_POST['table']   ?? ''));
    $page    = max(1, (int)($_POST['page']     ?? 1));
    $perPage = 50;
    $offset  = ($page - 1) * $perPage;

    if (!in_array($table, $ALLOWED_TABLES, true)) {
        jsonOut(false, 'Tabela não permitida.');
    }

    try {
        $total = (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

        // Paginado
        $rows = $db->query("SELECT * FROM `{$table}` LIMIT {$offset}, {$perPage}")
            ->fetchAll(PDO::FETCH_ASSOC);

        $cols = $rows ? array_keys($rows[0]) : [];

        jsonOut(true, '', [
            'table'    => $table,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'columns'  => $cols,
            'rows'     => $rows,
        ]);
    } catch (Throwable $e) {
        error_log('[FETCH TABLE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao carregar a tabela.');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTION: fetch_tables_list  ── lista de tabelas com contagens
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'fetch_tables_list') {
    $TABLES = [
        '_stream'         => ['icon' => 'bi-shop',        'label' => 'Streams por Loja'],
        '_stream_country' => ['icon' => 'bi-globe2',      'label' => 'Streams por País'],
        '_track'          => ['icon' => 'bi-music-note',  'label' => 'Faixas'],
        '_store'          => ['icon' => 'bi-shop-window', 'label' => 'Lojas'],
        '_album'          => ['icon' => 'bi-vinyl',       'label' => 'Álbuns'],
        '_artist'         => ['icon' => 'bi-person-badge', 'label' => 'Artistas'],
        '_users'          => ['icon' => 'bi-people',      'label' => 'Utilizadores'],
        '_royalty'        => ['icon' => 'bi-cash-coin',   'label' => 'Royalties'],
        '_wallet'         => ['icon' => 'bi-wallet2',     'label' => 'Carteiras'],
        '_withdrawal'     => ['icon' => 'bi-send',        'label' => 'Levantamentos'],
        '_payment'        => ['icon' => 'bi-credit-card', 'label' => 'Pagamentos'],
    ];

    $result = [];
    foreach ($TABLES as $tbl => $meta) {
        try {
            $count = (int)$db->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
        } catch (Throwable $e) {
            $count = -1; // tabela não existe
        }
        $result[] = array_merge(['table' => $tbl, 'count' => $count], $meta);
    }

    jsonOut(true, '', ['tables' => $result]);
}

jsonOut(false, 'Ação desconhecida.', [], 400);
