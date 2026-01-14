<?php
// api/ajax_like.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$outfit_id = $data['outfit_id'] ?? null;

if (!$outfit_id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Verificar se já existe like
    $stmt = $pdo->prepare("SELECT id FROM outfit_likes WHERE user_id = ? AND outfit_id = ?");
    $stmt->execute([$_SESSION['user_id'], $outfit_id]);
    $like = $stmt->fetch();

    if ($like) {
        // Remover like
        $stmt = $pdo->prepare("DELETE FROM outfit_likes WHERE id = ?");
        $stmt->execute([$like['id']]);
        $action = 'unliked';
    } else {
        // Adicionar like
        $stmt = $pdo->prepare("INSERT INTO outfit_likes (user_id, outfit_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $outfit_id]);
        $action = 'liked';

        // Notificação
        $stmt_owner = $pdo->prepare("SELECT user_id FROM outfits WHERE id = ?");
        $stmt_owner->execute([$outfit_id]);
        $owner_id = $stmt_owner->fetchColumn();

        if ($owner_id && $owner_id != $_SESSION['user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id) VALUES (?, ?, 'like', ?)")
                ->execute([$owner_id, $_SESSION['user_id'], $outfit_id]);
        }
    }

    // Obter contagem atualizada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM outfit_likes WHERE outfit_id = ?");
    $stmt->execute([$outfit_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'action' => $action, 'count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na BD: ' . $e->getMessage()]);
}
?>