<?php
// wardrobe.php
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
$error = '';

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $type = $_POST['type'];
    $color = $_POST['color'];
    // Handle Image Upload
    $image_path = "https://placehold.co/300x400?text=" . urlencode($type); // Default

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['item_image'];
        $uploadDir = 'uploads/wardrobe/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'item_' . $user_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $image_path = $targetPath;
        }
    }

    try {
        $sql = "INSERT INTO clothing_items (user_id, type, color, image_path) VALUES (:user_id, :type, :color, :image_path)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $type,
            ':color' => $color,
            ':image_path' => $image_path
        ]);
        $message = "Peça adicionada com sucesso!";
    } catch (PDOException $e) {
        $error = "Erro ao adicionar: " . $e->getMessage();
    }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clothing_items WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    header("Location: wardrobe.php");
    exit;
}

// Fetch Items
$stmt = $pdo->prepare("SELECT * FROM clothing_items WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Meu Guarda-Roupa - LOOKUP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>

<body class="bg-light <?php echo $themeClass; ?>">
    <?php
    $showBanner = false;
    $impersonatorName = '';
    if (isset($_SESSION['impersonator_id'])) {
        // Double check na DB para garantir que é mesmo admin
        $stmtCheck = $pdo->prepare("SELECT username, is_admin FROM users WHERE id = ?");
        $stmtCheck->execute([$_SESSION['impersonator_id']]);
        $impUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($impUser && $impUser['is_admin'] == 1) {
            $showBanner = true;
            $impersonatorName = $impUser['username'];
        }
    }
    ?>

    <?php if ($showBanner): ?>
        <div class="bg-warning text-dark text-center py-2 shadow-sm" style="z-index: 1050;">
            <div class="container d-flex justify-content-center align-items-center gap-3">
                <span class="fw-bold">⚠️ Modo de Acesso: <?php echo htmlspecialchars($impersonatorName); ?></span>
                <form action="admin/admin_actions.php" method="POST" class="d-inline">
                    <input type="hidden" name="action" value="stop_impersonate">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Voltar para Admin</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
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
                    <li class="nav-item"><a class="nav-link active fw-bold" href="wardrobe.php">Meu Guarda-Roupa</a>
                    </li>
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
                    <li class="nav-item ms-lg-3">
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Meu Armário</h2>
            <button class="btn btn-primary rounded-pill shadow-sm" data-bs-toggle="modal"
                data-bs-target="#addItemModal">
                + Adicionar Peça
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success rounded-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden item-card">
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top" alt="Item">
                                <span class="badge bg-white text-dark position-absolute top-0 end-0 m-2 shadow-sm">
                                    <?php echo htmlspecialchars($item['type']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="card-text text-muted mb-2 small">Cor: <span
                                        class="fw-bold text-dark"><?php echo htmlspecialchars($item['color']); ?></span></p>
                                <a href="wardrobe.php?delete=<?php echo $item['id']; ?>"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill"
                                    onclick="return confirm('Tem a certeza?');">Remover</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="display-1 text-muted opacity-25 mb-3">👕</div>
                    <h4 class="text-muted">O teu armário está vazio.</h4>
                    <p class="text-secondary">Usa o botão acima para adicionar a tua primeira peça.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Nova Peça</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="wardrobe.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="add_item" value="1">
                        <div class="mb-3">
                            <label class="form-label text-secondary">Tipo de Peça</label>
                            <select name="type" class="form-select form-select-lg fs-6" required>
                                <option value="casaco">Casaco</option>
                                <option value="camisola">Camisola/T-shirt</option>
                                <option value="calças">Calças/Shorts</option>
                                <option value="sapatos">Sapatos</option>
                                <option value="acessório">Acessório</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary">Cor</label>
                            <input type="text" name="color" class="form-control form-control-lg fs-6"
                                placeholder="Ex: Preto, Azul..." required>
                            <div class="mb-4">
                                <label class="form-label text-secondary">Foto da Peça</label>
                                <input type="file" name="item_image" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg rounded-pill">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>