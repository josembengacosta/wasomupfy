<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.edit');

$jsonResponseSent = false;

function jsonOut(bool $ok, string $message, array $extra = [], int $status = 200): never
{
    global $jsonResponseSent;

    $jsonResponseSent = true;

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

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

    if ($jsonResponseSent) {
        return;
    }

    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode(
        ['ok' => false, 'message' => 'Erro interno ao processar a importacao.'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
});

function normalizeText(mixed $value): string
{
    return is_scalar($value) ? trim((string)$value) : '';
}

function normalizeInteger(mixed $value): int
{
    $value = normalizeText($value);
    if ($value === '') {
        return 0;
    }

    $normalized = preg_replace('/[^\d-]/', '', str_replace(',', '', $value));
    if ($normalized === null || $normalized === '' || $normalized === '-') {
        return 0;
    }

    return (int)$normalized;
}

function normalizeDecimal(mixed $value): float
{
    $value = normalizeText($value);
    if ($value === '') {
        return 0.0;
    }

    $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';
    if ($value === '') {
        return 0.0;
    }

    $lastComma = strrpos($value, ',');
    $lastDot = strrpos($value, '.');

    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($lastComma !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }

    return is_numeric($value) ? (float)$value : 0.0;
}

function normalizeMonth(int $month): int
{
    return ($month >= 1 && $month <= 12) ? $month : 1;
}

function normalizeCountryCode(mixed $value): string
{
    $value = preg_replace('/[^a-z]/i', '', normalizeText($value)) ?? '';
    return strtoupper(substr($value, 0, 2));
}

function mappedCell(array $row, array $mappings, string $field): string
{
    if (!array_key_exists($field, $mappings)) {
        return '';
    }

    $index = (int)$mappings[$field];
    $value = $row[$index] ?? '';

    return normalizeText($value);
}

function resolveTrackId(PDOStatement $lookupStmt, array &$cache, string $rawValue): int
{
    $rawValue = normalizeText($rawValue);
    if ($rawValue === '') {
        return 0;
    }

    if (ctype_digit($rawValue)) {
        return (int)$rawValue;
    }

    $cacheKey = strtoupper($rawValue);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $lookupStmt->execute([$cacheKey]);
    $cache[$cacheKey] = (int)($lookupStmt->fetchColumn() ?: 0);

    return $cache[$cacheKey];
}

function resolveStoreId(PDOStatement $lookupStmt, array &$cache, string $rawValue): int
{
    $rawValue = normalizeText($rawValue);
    if ($rawValue === '') {
        return 0;
    }

    if (ctype_digit($rawValue)) {
        return (int)$rawValue;
    }

    $cacheKey = mb_strtolower($rawValue, 'UTF-8');
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $lookupStmt->execute([$rawValue, $rawValue]);
    $cache[$cacheKey] = (int)($lookupStmt->fetchColumn() ?: 0);

    return $cache[$cacheKey];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Metodo nao permitido.', [], 405);
}

if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessao expirada. Recarregue a pagina.', [], 419);
}

$db = getDB();
$action = trim((string)($_POST['action'] ?? ''));

if ($action === 'import_csv') {
    $dataType = trim((string)($_POST['data_type'] ?? ''));
    if (!in_array($dataType, ['country', 'store'], true)) {
        jsonOut(false, 'Tipo de importacao invalido.');
    }

    $selectedYear = (int)($_POST['year'] ?? date('Y'));
    if ($selectedYear < 2000 || $selectedYear > ((int)date('Y') + 1)) {
        jsonOut(false, 'Ano invalido.');
    }

    $mappings = json_decode((string)($_POST['mappings'] ?? ''), true);
    $csvData = json_decode((string)($_POST['csv_data'] ?? ''), true);

    if (!is_array($mappings) || empty($mappings)) {
        jsonOut(false, 'Mapeamento invalido.');
    }
    if (!is_array($csvData) || empty($csvData)) {
        jsonOut(false, 'O ficheiro CSV nao contem dados validos.');
    }
    if (!array_key_exists('id_track', $mappings)) {
        jsonOut(false, 'Mapeie a coluna da faixa antes de importar.');
    }
    if ($dataType === 'country' && !array_key_exists('country_code', $mappings)) {
        jsonOut(false, 'Mapeie a coluna country_code.');
    }
    if ($dataType === 'store' && !array_key_exists('id_store', $mappings)) {
        jsonOut(false, 'Mapeie a coluna id_store.');
    }

    $trackLookupStmt = $db->prepare('SELECT id_track FROM _track WHERE UPPER(isrc) = ? LIMIT 1');
    $storeLookupStmt = $db->prepare('SELECT id_store FROM _store WHERE LOWER(slug_store) = LOWER(?) OR LOWER(name_store) = LOWER(?) LIMIT 1');
    $insertCountryStmt = $db->prepare(
        'INSERT INTO _stream_country
            (id_track, year_stream, month_stream, country_code, country_name, streams, revenue)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            streams = streams + VALUES(streams),
            revenue = revenue + VALUES(revenue)'
    );
    $insertStoreStmt = $db->prepare(
        'INSERT INTO _stream
            (id_track, id_store, year_stream, month_stream, streams, downloads, revenue)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            streams = streams + VALUES(streams),
            downloads = downloads + VALUES(downloads),
            revenue = revenue + VALUES(revenue)'
    );

    $trackCache = [];
    $storeCache = [];
    $imported = 0;
    $skipped = 0;
    $errors = [];

    $db->beginTransaction();

    try {
        foreach ($csvData as $rowIndex => $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            $trackId = resolveTrackId($trackLookupStmt, $trackCache, mappedCell($row, $mappings, 'id_track'));
            $rowYear = normalizeInteger(mappedCell($row, $mappings, 'year_stream'));
            $year = ($rowYear >= 2000 && $rowYear <= 2100) ? $rowYear : $selectedYear;
            $month = normalizeMonth(normalizeInteger(mappedCell($row, $mappings, 'month_stream')));
            $streams = max(0, normalizeInteger(mappedCell($row, $mappings, 'streams')));
            $downloads = max(0, normalizeInteger(mappedCell($row, $mappings, 'downloads')));
            $revenue = max(0, normalizeDecimal(mappedCell($row, $mappings, 'revenue')));

            if ($trackId <= 0) {
                $skipped++;
                if (count($errors) < 5) {
                    $errors[] = 'Linha ' . ($rowIndex + 2) . ': faixa nao encontrada.';
                }
                continue;
            }

            if ($dataType === 'country') {
                $countryCode = normalizeCountryCode(mappedCell($row, $mappings, 'country_code'));
                $countryName = mappedCell($row, $mappings, 'country_name');

                if ($countryCode === '') {
                    $skipped++;
                    if (count($errors) < 5) {
                        $errors[] = 'Linha ' . ($rowIndex + 2) . ': country_code em falta.';
                    }
                    continue;
                }

                try {
                    $insertCountryStmt->execute([
                        $trackId,
                        $year,
                        $month,
                        $countryCode,
                        $countryName !== '' ? $countryName : null,
                        $streams,
                        $revenue,
                    ]);
                    $imported++;
                } catch (Throwable $e) {
                    $skipped++;
                    error_log('[IMPORT COUNTRY] ' . $e->getMessage());
                    if (count($errors) < 5) {
                        $errors[] = 'Linha ' . ($rowIndex + 2) . ': nao foi possivel gravar o pais.';
                    }
                }
                continue;
            }

            $storeId = resolveStoreId($storeLookupStmt, $storeCache, mappedCell($row, $mappings, 'id_store'));
            if ($storeId <= 0) {
                $skipped++;
                if (count($errors) < 5) {
                    $errors[] = 'Linha ' . ($rowIndex + 2) . ': loja nao encontrada.';
                }
                continue;
            }

            try {
                $insertStoreStmt->execute([
                    $trackId,
                    $storeId,
                    $year,
                    $month,
                    $streams,
                    $downloads,
                    $revenue,
                ]);
                $imported++;
            } catch (Throwable $e) {
                $skipped++;
                error_log('[IMPORT STORE] ' . $e->getMessage());
                if (count($errors) < 5) {
                    $errors[] = 'Linha ' . ($rowIndex + 2) . ': nao foi possivel gravar a loja.';
                }
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[PROCESS IMPORT CSV] ' . $e->getMessage());
        jsonOut(false, 'Erro ao processar a importacao.');
    }

    if ($imported === 0) {
        jsonOut(false, 'Nenhum registo valido foi importado.', [
            'imported' => 0,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    $message = $skipped > 0
        ? 'Importacao concluida com algumas linhas ignoradas.'
        : 'Importacao concluida com sucesso.';

    jsonOut(true, $message, [
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
    ]);
}

if ($action === 'manual_add') {
    $dataType = trim((string)($_POST['data_type'] ?? ''));
    if (!in_array($dataType, ['country', 'store'], true)) {
        jsonOut(false, 'Tipo de registo invalido.');
    }

    $trackId = (int)($_POST['id_track'] ?? 0);
    $year = (int)($_POST['year_stream'] ?? 0);
    $month = normalizeMonth((int)($_POST['month_stream'] ?? 0));
    $streams = max(0, normalizeInteger($_POST['streams'] ?? 0));
    $downloads = max(0, normalizeInteger($_POST['downloads'] ?? 0));
    $revenue = max(0, normalizeDecimal($_POST['revenue'] ?? 0));
    $password = trim((string)($_POST['password_confirm'] ?? ''));

    if ($trackId <= 0) {
        jsonOut(false, 'Selecione uma faixa.');
    }
    if ($year < 2000 || $year > ((int)date('Y') + 1)) {
        jsonOut(false, 'Ano invalido.');
    }
    if ($password === '') {
        jsonOut(false, 'Informe a senha do admin.');
    }
    if ($admin_password_hash === '' || !password_verify($password, $admin_password_hash)) {
        jsonOut(false, 'Senha incorreta.');
    }

    try {
        if ($dataType === 'country') {
            $countryCode = normalizeCountryCode($_POST['country_code'] ?? '');
            $countryName = normalizeText($_POST['country_name'] ?? '');

            if ($countryCode === '') {
                jsonOut(false, 'Selecione um codigo de pais valido.');
            }

            $stmt = $db->prepare(
                'INSERT INTO _stream_country
                    (id_track, year_stream, month_stream, country_code, country_name, streams, revenue)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    streams = streams + VALUES(streams),
                    revenue = revenue + VALUES(revenue)'
            );
            $stmt->execute([
                $trackId,
                $year,
                $month,
                $countryCode,
                $countryName !== '' ? $countryName : null,
                $streams,
                $revenue,
            ]);
        } else {
            $storeId = (int)($_POST['id_store'] ?? 0);
            if ($storeId <= 0) {
                jsonOut(false, 'Selecione uma loja.');
            }

            $stmt = $db->prepare(
                'INSERT INTO _stream
                    (id_track, id_store, year_stream, month_stream, streams, downloads, revenue)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    streams = streams + VALUES(streams),
                    downloads = downloads + VALUES(downloads),
                    revenue = revenue + VALUES(revenue)'
            );
            $stmt->execute([
                $trackId,
                $storeId,
                $year,
                $month,
                $streams,
                $downloads,
                $revenue,
            ]);
        }
    } catch (Throwable $e) {
        error_log('[PROCESS IMPORT MANUAL] ' . $e->getMessage());
        jsonOut(false, 'Nao foi possivel guardar o registo.');
    }

    jsonOut(true, 'Registo adicionado com sucesso.');
}

jsonOut(false, 'Acao desconhecida.', [], 400);
