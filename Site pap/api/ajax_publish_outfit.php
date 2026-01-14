<?php
// api/ajax_publish_outfit.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$outfit_id = $data['outfit_id'] ?? null;
$description = $data['description'] ?? '';

if (!$outfit_id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE outfits SET is_published = 1, description = ?, published_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$description, $outfit_id, $_SESSION['user_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Outfit não encontrado ou erro ao atualizar']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na BD: ' . $e->getMessage()]);
}
?>