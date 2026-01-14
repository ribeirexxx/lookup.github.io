<?php
// suggestions.php
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

// --- WARDROBE ANALYSIS ---
$stmt = $pdo->prepare("SELECT * FROM clothing_items WHERE user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 1. Shopping Suggestions (Essentials Check)
$essentials = [
    'top' => ['Branco', 'Preto', 'Cinzento'],
    'calças' => ['Jeans', 'Preto', 'Bege'],
    'casaco' => ['Preto', 'Gangas', 'Bege'],
    'sapatos' => ['Ténis Branco', 'Botas Pretas']
];

$owned_types = [];
foreach ($items as $item) {
    $type = strtolower($item['type']);
    $color = $item['color']; // Case sensitive check might be needed, but for now simple
    $owned_types[$type][] = $color;
}

$suggestions = [];

// Check Tops
if (!isset($owned_types['camisola']) && !isset($owned_types['t-shirt'])) {
    $suggestions[] = ['item' => 'T-shirt Branca Básica', 'reason' => 'Um essencial para qualquer guarda-roupa.'];
}

// Check Bottoms
if (!isset($owned_types['calças'])) {
    $suggestions[] = ['item' => 'Jeans Clássicos', 'reason' => 'A base para a maioria dos outfits casuais.'];
}

// Check Shoes
if (!isset($owned_types['sapatos']) && !isset($owned_types['ténis'])) {
    $suggestions[] = ['item' => 'Ténis Brancos', 'reason' => 'Versáteis e combinam com tudo.'];
}

// Check Coats
if (!isset($owned_types['casaco'])) {
    $suggestions[] = ['item' => 'Casaco Preto ou Blazer', 'reason' => 'Para dias mais frios ou ocasiões formais.'];
}

// 2. Wardrobe Audit (Least Used)
// Filter items created more than 30 days ago to give them a chance
$audit_items = array_filter($items, function ($item) {
    $created = new DateTime($item['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created);
    return $diff->days > 30 && $item['usage_count'] < 3;
});

// Sort by usage count (asc) then last_worn (asc - older first)
usort($audit_items, function ($a, $b) {
    if ($a['usage_count'] == $b['usage_count']) {
        return strcmp($a['last_worn'] ?? '', $b['last_worn'] ?? '');
    }
    return $a['usage_count'] - $b['usage_count'];
});

// Handle "Donate/Sell" Action (Delete)
if (isset($_POST['action']) && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    $action_type = $_POST['action']; // 'donate' or 'sell'

    $stmt = $pdo->prepare("DELETE FROM clothing_items WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$item_id, $user_id])) {
        $success_msg = ($action_type == 'donate') ? "Peça marcada para doação e removida!" : "Peça marcada para venda e removida!";
        // Refresh items
        header("Location: suggestions.php?msg=" . urlencode($success_msg));
        exit;
    }
}

// Check theme
$stmt = $pdo->prepare("SELECT theme_preference FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$theme = $stmt->fetchColumn();
$themeClass = $theme === 'dark' ? 'dark-mode' : '';
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugestões & Audit - LOOKUP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                    <li class="nav-item"><a class="nav-link active fw-bold" href="suggestions.php">Sugestões</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success rounded-4 mb-4">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <!-- Shopping Suggestions -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h4 class="fw-bold text-primary mb-0">🛍️ Sugestões de Compras</h4>
                        <p class="text-muted small">Baseado no que falta no teu armário.</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($suggestions)): ?>
                            <div class="text-center py-5">
                                <div class="display-4 mb-3">✨</div>
                                <h5>O teu guarda-roupa está top!</h5>
                                <p class="text-muted">Tens todos os essenciais básicos.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($suggestions as $sug): ?>
                                    <div class="list-group-item border-0 px-0 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light rounded-circle p-3 text-primary">
                                                <i class="bi bi-bag-plus"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1"><?php echo $sug['item']; ?></h6>
                                                <p class="text-muted small mb-0"><?php echo $sug['reason']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Wardrobe Audit -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h4 class="fw-bold text-danger mb-0">🧹 Wardrobe Audit</h4>
                        <p class="text-muted small">Peças que usas pouco. Considera doar ou vender.</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($audit_items)): ?>
                            <div class="text-center py-5">
                                <div class="display-4 mb-3">🔥</div>
                                <h5>Tudo em uso!</h5>
                                <p class="text-muted">Estás a dar bom uso a todas as tuas roupas.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach (array_slice($audit_items, 0, 4) as $item): ?>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo $item['image_path']; ?>"
                                                    class="rounded-3 object-fit-contain bg-white border"
                                                    style="width: 60px; height: 60px;">
                                                <div>
                                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($item['type']); ?></h6>
                                                    <small class="text-muted">Usado: <?php echo $item['usage_count']; ?>
                                                        vezes</small>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <form method="POST" onsubmit="return confirm('Doar esta peça?');">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="action" value="donate">
                                                    <button class="btn btn-sm btn-outline-success rounded-pill">Doar</button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Vender esta peça?');">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="action" value="sell">
                                                    <button class="btn btn-sm btn-outline-warning rounded-pill">Vender</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>

</html>