<?php
require_once 'authentic/include/connection.php';
try {
    $db = getDB();
    echo 'Feedback count: ' . $db->query('SELECT COUNT(*) FROM _feedback')->fetchColumn() . PHP_EOL;
    $stmt = $db->prepare('SELECT id, subject_fb, message_fb, status_fb FROM _feedback WHERE id=?');
    $stmt->execute([2]);
    $fb = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fb) {
        echo 'Encontrado - ID: ' . $fb['id'] . ', Assunto: ' . $fb['subject_fb'] . ', Status: ' . $fb['status_fb'] . PHP_EOL;
    } else {
        echo 'Não encontrado' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}