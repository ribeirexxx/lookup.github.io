<?php
// api/ajax_handle_follow_request.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;
$action = $data['action'] ?? ''; // 'accept' or 'reject'

if (!$notification_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    // Buscar a notificação para validar dono e remetente
    $stmt = $pdo->prepare("SELECT sender_id FROM notifications WHERE id = ? AND user_id = ? AND type = 'follow_request'");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    $notif = $stmt->fetch();

    if (!$notif) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }

    $sender_id = $notif['sender_id'];

    if ($action === 'accept') {
        // Atualizar status do follow
        $stmt = $pdo->prepare("UPDATE follows SET status = 'accepted' WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$sender_id, $_SESSION['user_id']]);

        // Criar notificação de confirmação
        $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (?, ?, 'follow')")
            ->execute([$sender_id, $_SESSION['user_id']]);

        $msg = 'Pedido aceite';
    } else {
        // Remover follow pendente
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ? AND status = 'pending'");
        $stmt->execute([$sender_id, $_SESSION['user_id']]);
        $msg = 'Pedido rejeitado';
    }

    // Remover a notificação do pedido
    $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$notification_id]);

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na BD: ' . $e->getMessage()]);
}
?>