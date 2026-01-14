<?php
// feed.php
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

// Get User Theme
$stmt = $pdo->prepare("SELECT theme_preference FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_theme = $stmt->fetchColumn();
$themeClass = $current_theme === 'dark' ? 'dark-mode' : '';

// Fetch Feed
$sql = "
    SELECT 
        o.id, o.name, o.description, o.published_at, o.image_path,
        u.username, u.profile_image, u.id as author_id,
        t.image_path as top_img,
        b.image_path as bottom_img,
        s.image_path as shoes_img,
        c.image_path as coat_img,
        (SELECT COUNT(*) FROM outfit_likes WHERE outfit_id = o.id) as likes_count,
        (SELECT COUNT(*) FROM outfit_likes WHERE outfit_id = o.id AND user_id = :current_user) as user_liked,
        (SELECT COUNT(*) FROM outfit_comments WHERE outfit_id = o.id) as comments_count
    FROM outfits o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN follows f ON f.following_id = u.id AND f.follower_id = :current_user
    LEFT JOIN clothing_items t ON o.top_id = t.id
    LEFT JOIN clothing_items b ON o.bottom_id = b.id
    LEFT JOIN clothing_items s ON o.shoes_id = s.id
    LEFT JOIN clothing_items c ON o.coat_id = c.id
    WHERE 
        o.is_published = 1
        AND (
            u.is_public = 1 
            OR (f.id IS NOT NULL AND f.status = 'accepted') 
            OR u.id = :current_user
        )
    ORDER BY o.published_at DESC
    LIMIT 50
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':current_user' => $user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get comments for a post
function getComments($pdo, $outfit_id)
{
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_image 
        FROM outfit_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.outfit_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$outfit_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidade - LOOKUP</title>
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
                    <li class="nav-item"><a class="nav-link active fw-bold" href="feed.php">Comunidade</a></li>
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
                    <li class="nav-item"><a class="nav-link" href="settings.php">Definições</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="upload_outfit.php" class="btn btn-primary btn-sm rounded-pill px-3">+ Novo Post</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Comunidade 🌍</h2>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <div class="display-1 text-muted opacity-25 mb-3">📭</div>
                        <h4 class="text-muted">Ainda não há publicações.</h4>
                        <p class="text-secondary">Sê o primeiro a publicar os teus outfits!</p>
                        <a href="upload_outfit.php" class="btn btn-primary rounded-pill mt-3">Criar Post</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
                            <div class="card-header bg-white border-0 p-3 d-flex align-items-center gap-3">
                                <a href="profile.php?user=<?php echo $post['author_id']; ?>"
                                    class="text-decoration-none text-dark d-flex align-items-center gap-2">
                                    <img src="<?php echo $post['profile_image'] ? htmlspecialchars($post['profile_image']) : 'https://ui-avatars.com/api/?name=' . $post['username']; ?>"
                                        class="rounded-circle object-fit-cover" style="width: 40px; height: 40px;">
                                    <span class="fw-bold"><?php echo htmlspecialchars($post['username']); ?></span>
                                </a>
                                <small
                                    class="text-muted ms-auto"><?php echo date('d M', strtotime($post['published_at'])); ?></small>
                            </div>

                            <div class="card-body p-0">
                                <!-- Main content: Photo OR Grid -->
                                <?php if (!empty($post['image_path'])): ?>
                                    <div class="w-100 text-center py-2" style="background-color: rgba(0,0,0,0.015);">
                                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>"
                                            class="img-fluid rounded-2 shadow-sm"
                                            style="max-height: 75vh; width: auto; display: inline-block;">
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-center align-items-center p-4 gap-2 flex-wrap bg-light"
                                        style="min-height: 300px;">
                                        <!-- Fallback to old grid if no photo uploaded -->
                                        <?php
                                        $items = [
                                            ['img' => $post['coat_img'], 'label' => 'Casaco'],
                                            ['img' => $post['top_img'], 'label' => 'Top'],
                                            ['img' => $post['bottom_img'], 'label' => 'Baixo'],
                                            ['img' => $post['shoes_img'], 'label' => 'Calçado']
                                        ];
                                        foreach ($items as $item):
                                            if ($item['img']):
                                                ?>
                                                <div class="text-center position-relative" style="width: 120px;">
                                                    <div class="rounded-3 shadow-sm border mb-1 overflow-hidden bg-white"
                                                        style="height: 120px; width: 120px;">
                                                        <img src="<?php echo htmlspecialchars($item['img']); ?>"
                                                            class="w-100 h-100 object-fit-contain p-1">
                                                    </div>
                                                </div>
                                            <?php endif; endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer bg-white border-0 p-3">
                                <!-- Social Actions -->
                                <div class="d-flex align-items-center gap-4 mb-3 border-top pt-3">
                                    <button
                                        class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-1 btn-like <?php echo $post['user_liked'] ? 'text-danger' : 'text-muted'; ?>"
                                        data-id="<?php echo $post['id']; ?>">
                                        <i
                                            class="bi <?php echo $post['user_liked'] ? 'bi-heart-fill' : 'bi-heart'; ?> h4 mb-0"></i>
                                        <span class="likes-count"><?php echo $post['likes_count']; ?></span>
                                    </button>

                                    <button
                                        class="btn btn-link text-decoration-none text-muted p-0 d-flex align-items-center gap-1 btn-comment-toggle"
                                        data-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-chat h4 mb-0"></i>
                                        <span><?php echo $post['comments_count']; ?></span>
                                    </button>

                                    <button
                                        class="btn btn-link text-decoration-none text-muted p-0 d-flex align-items-center gap-1 btn-repost"
                                        data-id="<?php echo $post['id']; ?>" title="Republicar no meu perfil">
                                        <i class="bi bi-arrow-repeat h4 mb-0"></i>
                                        <span>Repost</span>
                                    </button>
                                </div>

                                <h5 class="fw-bold mb-2">
                                    <?php echo $post['name'] ? htmlspecialchars($post['name']) : 'Outfit'; ?>
                                </h5>
                                <?php if ($post['description']): ?>
                                    <p class="text-secondary mb-3"><?php echo htmlspecialchars($post['description']); ?></p>
                                <?php endif; ?>

                                <!-- Tagged Items -->
                                <?php
                                $has_items = $post['coat_img'] || $post['top_img'] || $post['bottom_img'] || $post['shoes_img'];
                                if (!empty($post['image_path']) && $has_items):
                                    ?>
                                    <hr class="text-muted opacity-10 my-3">
                                    <h6 class="text-muted small fw-bold mb-2 text-uppercase">Peças Usadas 👇</h6>
                                    <div class="d-flex gap-2 overflow-auto pb-2 mb-3">
                                        <?php
                                        $items = [
                                            ['img' => $post['coat_img'], 'label' => 'Casaco'],
                                            ['img' => $post['top_img'], 'label' => 'Top'],
                                            ['img' => $post['bottom_img'], 'label' => 'Baixo'],
                                            ['img' => $post['shoes_img'], 'label' => 'Calçado']
                                        ];
                                        foreach ($items as $item):
                                            if ($item['img']):
                                                ?>
                                                <div class="text-center flex-shrink-0" style="width: 60px;">
                                                    <div class="rounded-3 shadow-sm border mb-1 overflow-hidden bg-white"
                                                        style="height: 60px; width: 60px;">
                                                        <img src="<?php echo htmlspecialchars($item['img']); ?>"
                                                            class="w-100 h-100 object-fit-contain p-2">
                                                    </div>
                                                    <span class="d-block text-muted small"
                                                        style="font-size: 0.6rem;"><?php echo $item['label']; ?></span>
                                                </div>
                                            <?php endif; endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Comments Section -->
                                <div class="comments-section d-none mt-3 p-3 bg-light rounded-4"
                                    id="comments-<?php echo $post['id']; ?>">
                                    <div class="comments-list mb-3" style="max-height: 200px; overflow-y: auto;">
                                        <?php
                                        $comments = getComments($pdo, $post['id']);
                                        foreach ($comments as $comment):
                                            ?>
                                            <div class="d-flex gap-2 mb-2">
                                                <img src="<?php echo $comment['profile_image'] ? htmlspecialchars($comment['profile_image']) : 'https://ui-avatars.com/api/?name=' . $comment['username']; ?>"
                                                    class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                                                <div class="small">
                                                    <span
                                                        class="fw-bold d-block"><?php echo htmlspecialchars($comment['username']); ?></span>
                                                    <span
                                                        class="text-secondary"><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control rounded-pill-start comment-input"
                                            placeholder="Escreve um comentário...">
                                        <button class="btn btn-primary rounded-pill-end btn-send-comment"
                                            data-id="<?php echo $post['id']; ?>">Enviar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle Likes
            document.querySelectorAll('.btn-like').forEach(btn => {
                btn.addEventListener('click', function () {
                    const outfitId = this.dataset.id;
                    fetch('api/ajax_like.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ outfit_id: outfitId })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const icon = this.querySelector('i');
                                const count = this.querySelector('.likes-count');
                                if (data.action === 'liked') {
                                    this.classList.replace('text-muted', 'text-danger');
                                    icon.classList.replace('bi-heart', 'bi-heart-fill');
                                } else {
                                    this.classList.replace('text-danger', 'text-muted');
                                    icon.classList.replace('bi-heart-fill', 'bi-heart');
                                }
                                count.textContent = data.count;
                            }
                        });
                });
            });

            // Toggle Comments Section
            document.querySelectorAll('.btn-comment-toggle').forEach(btn => {
                btn.addEventListener('click', function () {
                    const outfitId = this.dataset.id;
                    const section = document.getElementById('comments-' + outfitId);
                    section.classList.toggle('d-none');
                });
            });

            // Send Comment
            document.querySelectorAll('.btn-send-comment').forEach(btn => {
                btn.addEventListener('click', function () {
                    const outfitId = this.dataset.id;
                    const input = this.parentElement.querySelector('.comment-input');
                    const text = input.value.trim();

                    if (!text) return;

                    fetch('api/ajax_comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ outfit_id: outfitId, comment: text })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const list = document.querySelector(`#comments-${outfitId} .comments-list`);
                                const commentDiv = document.createElement('div');
                                commentDiv.className = 'd-flex gap-2 mb-2';
                                commentDiv.innerHTML = `
                            <img src="https://ui-avatars.com/api/?name=${data.username}" class="rounded-circle" style="width: 24px; height: 24px;">
                            <div class="small">
                                <span class="fw-bold d-block">${data.username}</span>
                                <span class="text-secondary">${text}</span>
                            </div>
                        `;
                                list.appendChild(commentDiv);
                                input.value = '';
                                // Update counter
                                const counter = document.querySelector(`.btn-comment-toggle[data-id="${outfitId}"] span`);
                                counter.textContent = parseInt(counter.textContent) + 1;
                            }
                        });
                });
            });

            // Repost
            document.querySelectorAll('.btn-repost').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (!confirm('Queres republicar este outfit no teu perfil?')) return;

                    const outfitId = this.dataset.id;
                    fetch('api/ajax_repost.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ outfit_id: outfitId })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert('Outfit republicado com sucesso! Podes vê-lo no teu perfil.');
                            } else {
                                alert('Erro ao republicar: ' + data.message);
                            }
                        });
                });
            });
        });
    </script>
</body>

</html>