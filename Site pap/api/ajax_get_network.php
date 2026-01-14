<?php
// ajax_get_network.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : ''; // 'followers' or 'following'

try {
    if ($type === 'followers') {
        // Get users who follow me
        $sql = "SELECT u.id, u.username, u.profile_image,
                (SELECT status FROM follows f2 WHERE f2.follower_id = :current_user AND f2.following_id = u.id) as follow_status
                FROM follows f
                JOIN users u ON f.follower_id = u.id
                WHERE f.following_id = :current_user";
    } elseif ($type === 'following') {
        // Get users I follow
        $sql = "SELECT u.id, u.username, u.profile_image,
                'accepted' as follow_status
                FROM follows f
                JOIN users u ON f.following_id = u.id
                WHERE f.follower_id = :current_user";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':current_user' => $current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>