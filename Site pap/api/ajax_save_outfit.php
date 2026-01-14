<?php
// ajax_save_outfit.php
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

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$top_id = isset($data['top_id']) ? $data['top_id'] : null;
$bottom_id = isset($data['bottom_id']) ? $data['bottom_id'] : null;
$shoes_id = isset($data['shoes_id']) ? $data['shoes_id'] : null;
$coat_id = isset($data['coat_id']) ? $data['coat_id'] : null;
$name = isset($data['name']) ? $data['name'] : 'Outfit ' . date('Y-m-d H:i');

if (!$top_id && !$bottom_id && !$shoes_id && !$coat_id) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO outfits (user_id, name, top_id, bottom_id, shoes_id, coat_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $top_id, $bottom_id, $shoes_id, $coat_id]);

    // Update usage stats for items
    $items_to_update = array_filter([$top_id, $bottom_id, $shoes_id, $coat_id]);
    if (!empty($items_to_update)) {
        $placeholders = implode(',', array_fill(0, count($items_to_update), '?'));
        $update_sql = "UPDATE clothing_items SET usage_count = usage_count + 1, last_worn = CURRENT_DATE WHERE id IN ($placeholders)";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute(array_values($items_to_update));
    }

    echo json_encode(['success' => true, 'message' => 'Outfit saved successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>