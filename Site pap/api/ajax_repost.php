<?php
// api/ajax_repost.php
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
    // Obter dados do outfit original
    $stmt = $pdo->prepare("SELECT * FROM outfits WHERE id = ?");
    $stmt->execute([$outfit_id]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$original) {
        echo json_encode(['success' => false, 'message' => 'Outfit original não encontrado']);
        exit;
    }

    // Criar novo outfit baseado no original (repost)
    $stmt = $pdo->prepare("INSERT INTO outfits (user_id, name, top_id, bottom_id, shoes_id, coat_id, image_path, description, is_published, published_at, repost_of_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?)");

    $name = "Republicado: " . $original['name'];
    $stmt->execute([
        $_SESSION['user_id'],
        $name,
        $original['top_id'],
        $original['bottom_id'],
        $original['shoes_id'],
        $original['coat_id'],
        $original['image_path'],
        "Partilhado de " . $original['user_id'], // Podia buscar o nome do autor original mas simplificado
        $outfit_id
    ]);

    // Notificação ao autor original
    if ($original['user_id'] != $_SESSION['user_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id) VALUES (?, ?, 'repost', ?)")
            ->execute([$original['user_id'], $_SESSION['user_id'], $outfit_id]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na BD: ' . $e->getMessage()]);
}
?>