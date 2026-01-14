<?php
// ajax_search_users.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

try {
    // Search users excluding self
    // Also check if already following
    $sql = "SELECT u.id, u.username, u.profile_image, 
            (SELECT status FROM follows f WHERE f.follower_id = :current_user AND f.following_id = u.id) as follow_status
            FROM users u 
            WHERE u.username LIKE :query AND u.id != :current_user
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':query' => "%$query%",
        ':current_user' => $current_user_id
    ]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>