<?php
// admin_actions.php
session_start();
require_once '../includes/db_connect.php';

// Verificação de segurança: Apenas admins podem aceder
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Verificar se é impersonator ou user real
$check_id = $_SESSION['user_id'];
if (isset($_SESSION['impersonator_id'])) {
    $check_id = $_SESSION['impersonator_id'];
}

// Verificar se o utilizador atual (ou impersonator) é admin na base de dados
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$check_id]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['is_admin'] != 1) {
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- ADICIONAR USER ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (!empty($username) && !empty($email) && !empty($password)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hash]);
                header("Location: admin.php?msg=User criado com sucesso");
            } catch (PDOException $e) {
                header("Location: admin.php?error=Erro ao criar user: " . urlencode($e->getMessage()));
            }
        } else {
            header("Location: admin.php?error=Preencha todos os campos");
        }
        exit;
    }

    // --- REMOVER USER ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $userId = $_POST['user_id'];

        // Impedir que o admin se apague a si próprio (segurança básica)
        if ($userId == $_SESSION['user_id']) {
            header("Location: admin.php?error=Nao pode apagar a sua propria conta");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            header("Location: admin.php?msg=User removido");
        } catch (PDOException $e) {
            header("Location: admin.php?error=Erro ao remover: " . urlencode($e->getMessage()));
        }
        exit;
    }

    // --- IMPERSONAR (LOGIN AS) ---
    if (isset($_POST['action']) && $_POST['action'] === 'impersonate') {
        $targetId = $_POST['user_id'];

        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($targetUser) {
                // Trocar a sessão para o utilizador alvo
                // Se já não estivermos a impersonar, guardar o ID original
                if (!isset($_SESSION['impersonator_id'])) {
                    $_SESSION['impersonator_id'] = $_SESSION['user_id'];
                }

                $_SESSION['user_id'] = $targetUser['id'];
                $_SESSION['username'] = $targetUser['username'];

                header("Location: ../dashboard.php");
                exit;
            }
        } catch (Exception $e) {
            header("Location: admin.php?error=Erro ao aceder a conta");
        }
        exit;
    }


    // --- PARAR IMPERSONAÇÃO ---
    if (isset($_POST['action']) && $_POST['action'] === 'stop_impersonate') {
        if (isset($_SESSION['impersonator_id'])) {
            // Restaurar o ID original
            $_SESSION['user_id'] = $_SESSION['impersonator_id'];

            // Buscar o username do admin original para manter a consistência
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($adminUser) {
                $_SESSION['username'] = $adminUser['username'];
            }

            // Limpar a flag de impersonação
            unset($_SESSION['impersonator_id']);

            header("Location: admin.php");
        } else {
            // Se não houver impersonação, volta pro dashboard ou admin
            header("Location: admin.php");
        }
        exit;
    }
}
?>