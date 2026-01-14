<?php
// api/ajax_toggle_privacy.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$is_public = isset($data['is_public']) ? (int) $data['is_public'] : 1;

try {
    $stmt = $pdo->prepare("UPDATE users SET is_public = ? WHERE id = ?");
    $stmt->execute([$is_public, $_SESSION['user_id']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na BD: ' . $e->getMessage()]);
}
?>