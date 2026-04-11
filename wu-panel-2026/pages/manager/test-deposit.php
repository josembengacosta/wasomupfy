<?php
// Ficheiro de teste para debug do depósito manual
require_once __DIR__ . '/../../include/platform_admin.php';
require_once __DIR__ . '/include/payment-guard.php';

paymentPanelEnsureCsrf();

// Simular dados de teste
$test_user_id = 1; // Usar ID real do teu banco
$test_track_id = 1; // Usar ID real

echo "<h2>Teste de Ligação ao Backend</h2>";
echo "<pre>";

// Teste 1: Ver se consegue ler utilizadores
echo "\n === TESTE 1: Ler Utilizadores ===\n";
$users = $db->query("SELECT id_users, first_name, second_name, email_user FROM _users WHERE status_user='active' LIMIT 3")->fetchAll();
echo "Utilizadores encontrados: " . count($users) . "\n";
if (count($users) > 0) {
    $test_user_id = $users[0]['id_users'];
    echo "Usando utilizador #" . $test_user_id . "\n";
}

// Teste 2: Ver se consegue ler álbuns do utilizador
echo "\n === TESTE 2: Ler Álbuns ===\n";
$albums = $db->query("SELECT id_album, title_album FROM _album WHERE id_users=" . $test_user_id . " AND status_album='approved' LIMIT 3")->fetchAll();
echo "Álbuns encontrados: " . count($albums) . "\n";
if (count($albums) > 0) {
    $test_album_id = $albums[0]['id_album'];
    echo "Usando álbum #" . $test_album_id . " (" . $albums[0]['title_album'] . ")\n";
}

// Teste 3: Ver se consegue ler faixas do álbum
echo "\n === TESTE 3: Ler Faixas ===\n";
if (isset($test_album_id)) {
    $tracks = $db->query("SELECT id_track, title_track FROM _track WHERE id_album=" . $test_album_id . " AND status_track='active' LIMIT 3")->fetchAll();
    echo "Faixas encontradas: " . count($tracks) . "\n";
    if (count($tracks) > 0) {
        $test_track_id = $tracks[0]['id_track'];
        echo "Usando faixa #" . $test_track_id . " (" . $tracks[0]['title_track'] . ")\n";
    }
} else {
    echo "Nenhum álbum disponível\n";
}

// Teste 4: Ver permissões
echo "\n === TESTE 4: Permissões ===\n";
echo "Admin ID: " . $admin_id . "\n";
try {
    requirePermission($admin_id, 'finances.edit');
    echo "✓ Tem permissão finances.edit\n";
} catch (Exception $e) {
    echo "✗ Sem permissão finances.edit: " . $e->getMessage() . "\n";
}

// Teste 5: Criar directório de uploads
echo "\n === TESTE 5: Directório de Uploads ===\n";
$dir = w . '/assets/payment/uploads/royalties/';
echo "Caminho esperado: " . $dir . "\n";
if (is_dir($dir)) {
    echo "✓ Directório existe\n";
} else {
    echo "✗ Directório não existe\n";
    echo "Tentando criar...\n";
    if (@mkdir($dir, 0755, true)) {
        echo "✓ Directório criado com sucesso\n";
    } else {
        echo "✗ Falha ao criar directório\n";
    }
}

// Teste 6: Conta bancária 
echo "\n === TESTE 6: Conta Bancária Padrão ===\n";
$acc = $db->prepare("SELECT id_account, type_account, status_account FROM _account WHERE id_users=? AND is_default=1 LIMIT 1");
$acc->execute([$test_user_id]);
$acc_data = $acc->fetch();
if ($acc_data) {
    echo "✓ Conta encontrada: " . $acc_data['type_account'] . " (" . $acc_data['status_account'] . ")\n";
} else {
    echo "✗ Nenhuma conta padrão para o utilizador\n";
}

// Teste 7: Wallet
echo "\n === TESTE 7: Wallet ===\n";
$wallet = $db->prepare("SELECT id_wallet, balance_aoa FROM _wallet WHERE id_users=?");
$wallet->execute([$test_user_id]);
$wallet_data = $wallet->fetch();
if ($wallet_data) {
    echo "✓ Wallet existe (ID: " . $wallet_data['id_wallet'] . ", Saldo: Kz " . number_format($wallet_data['balance_aoa'], 2, ',', '.') . ")\n";
} else {
    echo "✗ Wallet não existe\n";
}

echo "\n</pre>";
echo "<a href='javascript:history.back()'>Voltar</a>";
