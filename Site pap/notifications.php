<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Marcar todas como lidas ao entrar na página
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);

$sql = "SELECT n.*, u.username as sender_name, u.profile_image as sender_img, 
               o.name as outfit_name, o.image_path as outfit_img
        FROM notifications n
        JOIN users u ON n.sender_id = u.id
        LEFT JOIN outfits o ON n.target_id = o.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check theme
$stmt = $pdo->prepare("SELECT theme_preference FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$theme = $stmt->fetchColumn();
$themeClass = $theme === 'dark' ? 'dark-mode' : '';

// Count unread (though we just marked them read, it's for the navbar count if we had one before update)
// Actually we'll need a way to show count in navbars across all pages.
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - LOOKUP</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <!-- Immediate Theme Detection -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>

<body class="bg-light <?php echo $themeClass; ?>">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand logo" href="index.php">
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
                    <li class="nav-item"><a class="nav-link active fw-bold" href="notifications.php">Notificações</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="suggestions.php">Sugestões</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="fw-bold mb-0">Atividade Recente 🔔</h2>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="display-1 text-muted opacity-25 mb-3">🔔</div>
                        <h4 class="text-muted">Ainda não tens notificações.</h4>
                        <p class="text-secondary">Quando alguém interagir contigo, verás aqui!</p>
                        <a href="feed.php" class="btn btn-primary rounded-pill mt-3">Explorar Comunidade</a>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item p-3 border-0 border-bottom">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Sender Avatar -->
                                        <a href="profile.php?user=<?php echo $n['sender_id']; ?>" class="flex-shrink-0">
                                            <img src="<?php echo $n['sender_img'] ? htmlspecialchars($n['sender_img']) : 'https://ui-avatars.com/api/?name=' . $n['sender_name']; ?>"
                                                class="rounded-circle object-fit-cover" style="width: 50px; height: 50px;">
                                        </a>

                                        <!-- Notification Text -->
                                        <div class="flex-grow-1">
                                            <p class="mb-0">
                                                <a href="profile.php?user=<?php echo $n['sender_id']; ?>"
                                                    class="fw-bold text-dark text-decoration-none">
                                                    <?php echo htmlspecialchars($n['sender_name']); ?>
                                                </a>
                                                <?php
                                                switch ($n['type']) {
                                                    case 'follow':
                                                        echo " começou a seguir-te.";
                                                        break;
                                                    case 'like':
                                                        echo " gostou do teu outfit <span class='fw-bold'>\"" . htmlspecialchars($n['outfit_name']) . "\"</span>.";
                                                        break;
                                                    case 'comment':
                                                        echo " comentou no teu outfit <span class='fw-bold'>\"" . htmlspecialchars($n['outfit_name']) . "\"</span>.";
                                                        break;
                                                    case 'repost':
                                                        echo " republicou o teu outfit <span class='fw-bold'>\"" . htmlspecialchars($n['outfit_name']) . "\"</span>.";
                                                        break;
                                                    case 'follow_request':
                                                        echo " enviou um pedido para te seguir.";
                                                        break;
                                                }
                                                ?>
                                            </p>
                                            <?php if ($n['type'] === 'follow_request'): ?>
                                                <div class="d-flex gap-2 mt-2">
                                                    <button class="btn btn-primary btn-sm rounded-pill px-3 handle-request"
                                                        data-id="<?php echo $n['id']; ?>" data-action="accept">Aceitar</button>
                                                    <button class="btn btn-light btn-sm rounded-pill px-3 handle-request"
                                                        data-id="<?php echo $n['id']; ?>" data-action="reject">Recusar</button>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <?php echo date('d M, Y - H:i', strtotime($n['created_at'])); ?>
                                            </small>
                                        </div>

                                        <!-- Target Content Preview (if any) -->
                                        <?php if ($n['outfit_img']): ?>
                                            <div class="flex-shrink-0" style="width: 50px; height: 50px;">
                                                <img src="<?php echo htmlspecialchars($n['outfit_img']); ?>"
                                                    class="rounded-3 w-100 h-100 object-fit-cover shadow-sm">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.handle-request').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const action = this.dataset.action;
                const container = this.closest('.list-group-item');

                fetch('api/ajax_handle_follow_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: id, action: action })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            container.style.opacity = '0.5';
                            container.innerHTML = `<div class="p-2 text-center text-muted small">${data.message}</div>`;
                            setTimeout(() => container.remove(), 2000);
                        } else {
                            alert('Erro: ' + data.message);
                        }
                    });
            });
        });
    </script>
</body>

</html>