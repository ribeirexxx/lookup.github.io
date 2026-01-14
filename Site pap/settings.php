<?php
// settings.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once 'includes/db_connect.php';
$user_id = $_SESSION['user_id'];

// Count unread notifications
$stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt_unread->execute([$user_id]);
$unread_count = $stmt_unread->fetchColumn();

$message = '';
$messageType = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_privacy'])) {
        $new_privacy = $_POST['privacy_value'] == '1' ? 0 : 1; // Toggle
        $stmt = $pdo->prepare("UPDATE users SET is_public = ? WHERE id = ?");
        if ($stmt->execute([$new_privacy, $user_id])) {
            $message = "Privacidade atualizada com sucesso!";
            $messageType = "success";
        }
    }

    if (isset($_POST['toggle_theme'])) {
        $new_theme = $_POST['theme_value'] == 'light' ? 'dark' : 'light'; // Toggle
        $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        if ($stmt->execute([$new_theme, $user_id])) {
            $message = "Tema atualizado com sucesso!";
            $messageType = "success";
            // Update session for immediate effect if we use session for theme
            $_SESSION['theme'] = $new_theme;
        }
    }
}

// Fetch current settings
$stmt = $pdo->prepare("SELECT is_public, theme_preference FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_public = $user['is_public'];
$theme = $user['theme_preference'];

// Set theme class for body
$themeClass = $theme === 'dark' ? 'dark-mode' : '';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definições - LOOKUP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>

<body class="bg-light <?php echo $themeClass; ?>">
    <nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand logo d-flex align-items-center gap-2" href="dashboard.php">
                <img src="assets/img/logo.png" alt="Logo" style="height: 30px;">
                LOOK<span>UP</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="wardrobe.php">Meu Guarda-Roupa</a></li>
                    <li class="nav-item"><a class="nav-link" href="favorites.php">Favoritos</a></li>
                    <li class="nav-item"><a class="nav-link" href="feed.php">Comunidade</a></li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-1" href="notifications.php">
                            Notificações
                            <?php if ($unread_count > 0): ?>
                                <span class="badge rounded-pill bg-danger"
                                    style="font-size: 0.6rem;"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="suggestions.php">Sugestões</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="settings.php">Definições</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="fw-bold mb-0">Definições ⚙️</h2>
                    <a href="profile.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Voltar ao
                        Perfil</a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show border-0 shadow-sm rounded-4"
                        role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Privacidade da Conta</h5>

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo $is_public ? 'Conta Pública' : 'Conta Privada'; ?>
                                </h6>
                                <p class="text-muted small mb-0">
                                    <?php echo $is_public
                                        ? 'Todos os utilizadores podem ver os teus posts publicados.'
                                        : 'Apenas os teus seguidores podem ver os teus posts.'; ?>
                                </p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="privacy_value" value="<?php echo $is_public; ?>">
                                <button type="submit" name="toggle_privacy"
                                    class="btn btn-<?php echo $is_public ? 'success' : 'secondary'; ?> rounded-pill px-4">
                                    <?php echo $is_public ? 'Pública' : 'Privada'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Aparência</h5>

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo $theme === 'dark' ? 'Modo Escuro' : 'Modo Claro'; ?>
                                </h6>
                                <p class="text-muted small mb-0">
                                    Escolhe a aparência da aplicação.
                                </p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="theme_value" value="<?php echo $theme; ?>">
                                <button type="submit" name="toggle_theme"
                                    class="btn btn-<?php echo $theme === 'dark' ? 'dark' : 'light'; ?> border rounded-pill px-4">
                                    <?php echo $theme === 'dark' ? '🌙 Escuro' : '☀️ Claro'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sync database theme to localStorage for unauthenticated pages consistency
        localStorage.setItem('theme', '<?php echo $theme; ?>');
    </script>
</body>

</html>