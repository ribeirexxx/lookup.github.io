<?php
// api/ajax_comment.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$outfit_id = $data['outfit_id'] ?? null;
$text = trim($data['comment'] ?? '');

if (!$outfit_id || empty($text)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO outfit_comments (user_id, outfit_id, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $outfit_id, $text]);

    // Notificação
    $stmt_owner = $pdo->prepare("SELECT user_id FROM outfits WHERE id = ?");
    $stmt_owner->execute([$outfit_id]);
    $owner_id = $stmt_owner->fetchColumn();

    if ($owner_id && $owner_id != $_SESSION['user_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id) VALUES (?, ?, 'comment', ?)")
            ->execute([$owner_id, $_SESSION['user_id'], $outfit_id]);
    }

    echo json_encode(['success' => true, 'username' => $_SESSION['username']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na BD: ' . $e->getMessage()]);
}
?>