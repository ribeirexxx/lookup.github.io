<?php
// favorites.php
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

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM outfits WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: favorites.php");
    exit;
}

// Fetch Outfits with Item Images
$sql = "
    SELECT 
        o.id, o.name, o.created_at, o.is_published,
        t.image_path as top_img,
        b.image_path as bottom_img,
        s.image_path as shoes_img,
        c.image_path as coat_img
    FROM outfits o
    LEFT JOIN clothing_items t ON o.top_id = t.id
    LEFT JOIN clothing_items b ON o.bottom_id = b.id
    LEFT JOIN clothing_items s ON o.shoes_id = s.id
    LEFT JOIN clothing_items c ON o.coat_id = c.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$outfits = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Favoritos - LOOKUP</title>
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
                    <li class="nav-item"><a class="nav-link active fw-bold" href="favorites.php">Favoritos</a></li>
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
        <h2 class="fw-bold mb-4">Meus Favoritos ❤️</h2>

        <?php if (empty($outfits)): ?>
            <div class="text-center py-5">
                <div class="display-1 text-muted opacity-25 mb-3">👗</div>
                <h4 class="text-muted">Ainda não tens outfits guardados.</h4>
                <p class="text-secondary">Vai ao Dashboard e gera algumas sugestões!</p>
                <a href="dashboard.php" class="btn btn-primary rounded-pill mt-3">Ir para Dashboard</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($outfits as $outfit): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                            <div
                                class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0 text-truncate"><?php echo htmlspecialchars($outfit['name']); ?></h5>
                                <?php if ($outfit['is_published']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Publicado</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <?php
                                    $items = [
                                        ['img' => $outfit['coat_img'], 'label' => 'Casaco'],
                                        ['img' => $outfit['top_img'], 'label' => 'Top'],
                                        ['img' => $outfit['bottom_img'], 'label' => 'Baixo'],
                                        ['img' => $outfit['shoes_img'], 'label' => 'Calçado']
                                    ];
                                    foreach ($items as $item):
                                        if ($item['img']):
                                            ?>
                                            <div class="text-center" style="width: 70px;">
                                                <div class="rounded-3 shadow-sm border mb-1 overflow-hidden"
                                                    style="height: 70px; width: 70px;">
                                                    <img src="<?php echo htmlspecialchars($item['img']); ?>"
                                                        class="w-100 h-100 object-fit-contain p-1">
                                                </div>
                                                <span class="d-block text-muted"
                                                    style="font-size: 0.6rem;"><?php echo $item['label']; ?></span>
                                            </div>
                                        <?php endif; endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 pb-4 px-4 d-flex gap-2">
                                <?php if (!$outfit['is_published']): ?>
                                    <button class="btn btn-primary btn-sm flex-grow-1 rounded-pill"
                                        onclick="openPublishModal(<?php echo $outfit['id']; ?>)">
                                        Publicar
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm flex-grow-1 rounded-pill" disabled>
                                        Já Publicado
                                    </button>
                                <?php endif; ?>
                                <a href="favorites.php?delete=<?php echo $outfit['id']; ?>"
                                    class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                    onclick="return confirm('Apagar este outfit?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Publish Modal -->
    <div class="modal fade" id="publishModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Publicar Outfit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Partilha o teu estilo com a comunidade!</p>
                    <input type="hidden" id="publish-outfit-id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea id="publish-description" class="form-control" rows="3"
                            placeholder="Ex: O meu look para um dia de chuva..."></textarea>
                    </div>
                    <button onclick="confirmPublish()" class="btn btn-primary w-100 rounded-pill">Publicar
                        Agora</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <script>
        let publishModal;

        document.addEventListener('DOMContentLoaded', () => {
            publishModal = new bootstrap.Modal(document.getElementById('publishModal'));
        });

        function openPublishModal(id) {
            document.getElementById('publish-outfit-id').value = id;
            document.getElementById('publish-description').value = '';
            publishModal.show();
        }

        function confirmPublish() {
            const id = document.getElementById('publish-outfit-id').value;
            const desc = document.getElementById('publish-description').value;

            fetch('api/ajax_publish_outfit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ outfit_id: id, description: desc })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(err => console.error(err));
        }
    </script>
</body>

</html>