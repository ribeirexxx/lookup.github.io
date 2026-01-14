<?php
// ajax_follow.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$target_id = isset($data['target_id']) ? intval($data['target_id']) : 0;
$action = isset($data['action']) ? $data['action'] : ''; // 'follow' or 'unfollow'

if ($target_id <= 0 || $target_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Invalid target user']);
    exit;
}

try {
    if ($action === 'follow') {
        // Verificar se conta é privada
        $stmt_priv = $pdo->prepare("SELECT is_public FROM users WHERE id = ?");
        $stmt_priv->execute([$target_id]);
        $target_is_public = $stmt_priv->fetchColumn();

        $status = $target_is_public ? 'accepted' : 'pending';
        $notif_type = $target_is_public ? 'follow' : 'follow_request';

        $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, following_id, status) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $target_id, $status]);

        // Adicionar notificação
        $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (?, ?, ?)")
            ->execute([$target_id, $_SESSION['user_id'], $notif_type]);

        $message = ($status === 'pending') ? 'Pedido enviado' : 'Seguindo';
    } elseif ($action === 'unfollow') {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$_SESSION['user_id'], $target_id]);
        $message = 'Unfollowed successfully';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>