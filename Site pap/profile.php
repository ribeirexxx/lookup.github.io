<?php
// profile.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once 'includes/db_connect.php';

$current_user_id = $_SESSION['user_id'];

// Count unread notifications
$stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt_unread->execute([$current_user_id]);
$unread_count = $stmt_unread->fetchColumn();

$profile_user_id = isset($_GET['user']) ? (int) $_GET['user'] : $current_user_id;
$is_own_profile = ($current_user_id === $profile_user_id);

// Get user data
$stmt = $pdo->prepare("SELECT username, email, profile_image, profile_banner, is_public, theme_preference FROM users WHERE id = :id");
$stmt->execute([':id' => $profile_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilizador não encontrado.");
}

// Determine Theme (Use current user's preference for viewing, not the profile owner's)
$stmt_theme = $pdo->prepare("SELECT theme_preference FROM users WHERE id = ?");
$stmt_theme->execute([$current_user_id]);
$viewer_theme = $stmt_theme->fetchColumn();
$themeClass = $viewer_theme === 'dark' ? 'dark-mode' : '';


// Check visibility & Follow status
$can_view = false;
$is_following = false;

if (!$is_own_profile) {
    $stmt = $pdo->prepare("SELECT status FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$current_user_id, $profile_user_id]);
    $follow_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($follow_data) {
        $is_following = ($follow_data['status'] === 'accepted');
        $is_pending = ($follow_data['status'] === 'pending');
    }
}

if ($is_own_profile || $user['is_public'] || $is_following) {
    $can_view = true;
}

// Handle Uploads (Only if own profile)
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Profile Image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $current_user_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$targetPath, $current_user_id]);
            header("Location: profile.php");
            exit;
        }
    }

    // 2. Profile Banner
    if (isset($_FILES['profile_banner']) && $_FILES['profile_banner']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_banner'];
        $uploadDir = 'uploads/banners/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . $current_user_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_banner = ? WHERE id = ?");
            $stmt->execute([$targetPath, $current_user_id]);
            header("Location: profile.php");
            exit;
        }
    }
}

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clothing_items WHERE user_id = :user_id");
$stmt->execute([':user_id' => $profile_user_id]);
$total_items = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = :user_id");
$stmt->execute([':user_id' => $profile_user_id]);
$followers_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = :user_id");
$stmt->execute([':user_id' => $profile_user_id]);
$following_count = $stmt->fetchColumn();

// Get Published Posts (If allowed to view)
$posts = [];
if ($can_view) {
    $sql = "
        SELECT 
            o.id, o.name, o.description, o.published_at, o.image_path,
            t.image_path as top_img,
            b.image_path as bottom_img,
            s.image_path as shoes_img,
            c.image_path as coat_img
        FROM outfits o
        LEFT JOIN clothing_items t ON o.top_id = t.id
        LEFT JOIN clothing_items b ON o.bottom_id = b.id
        LEFT JOIN clothing_items s ON o.shoes_id = s.id
        LEFT JOIN clothing_items c ON o.coat_id = c.id
        WHERE o.user_id = ? AND o.is_published = 1
        ORDER BY o.published_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$profile_user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - LOOKUP</title>
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
                    <li class="nav-item"><a class="nav-link" href="settings.php">Definições</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="profile.php">Perfil</a></li>
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
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <!-- Banner Section -->
                    <div class="profile-banner-container <?php echo $is_own_profile ? 'profile-banner-editable' : ''; ?>"
                        id="banner-area"
                        style="background-image: url('<?php echo $user['profile_banner'] ? htmlspecialchars($user['profile_banner']) : 'assets/img/default-banner.jpg'; ?>');">

                        <?php if ($is_own_profile): ?>
                            <form id="banner-form" method="POST" enctype="multipart/form-data" class="d-none">
                                <input type="file" name="profile_banner" id="banner-input" accept="image/*"
                                    onchange="document.getElementById('banner-form').submit();">
                            </form>
                            <div class="position-absolute top-0 end-0 m-3 z-3">
                                <a href="settings.php" class="btn btn-sm btn-light rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-gear-fill"></i> Definições
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-0">
                        <div class="text-center position-relative">
                            <!-- Profile Image Section -->
                            <div class="profile-avatar-container rounded-circle mx-auto bg-white <?php echo $is_own_profile ? 'profile-avatar-editable' : ''; ?>"
                                id="avatar-area">
                                <?php if ($user['profile_image']): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile"
                                        class="w-100 h-100 object-fit-cover rounded-circle">
                                <?php else: ?>
                                    <div
                                        class="w-100 h-100 d-flex align-items-center justify-content-center fw-bold text-primary h1 mb-0">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($is_own_profile): ?>
                                    <div class="avatar-overlay">
                                        <i class="bi bi-camera-fill h4 mb-0"></i>
                                        <span>Mudar Foto</span>
                                        <form id="avatar-form" method="POST" enctype="multipart/form-data" class="d-none">
                                            <input type="file" name="profile_image" id="avatar-input" accept="image/*"
                                                onchange="document.getElementById('avatar-form').submit();">
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="profile-name-section pb-4 px-4">
                                <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($user['username']); ?></h2>
                                <?php if ($is_own_profile): ?>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                <?php endif; ?>

                                <?php if (!$is_own_profile): ?>
                                    <button
                                        class="btn <?php echo ($is_following || $is_pending) ? 'btn-outline-primary' : 'btn-primary'; ?> rounded-pill px-4 mt-3 btn-follow-profile shadow-sm"
                                        data-id="<?php echo $profile_user_id; ?>"
                                        data-action="<?php echo ($is_following || $is_pending) ? 'unfollow' : 'follow'; ?>">
                                        <?php
                                        if ($is_following)
                                            echo 'A Seguir';
                                        elseif ($is_pending)
                                            echo 'Pedido Enviado';
                                        else
                                            echo 'Seguir';
                                        ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-4 pt-0">
                            <!-- Stats -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <div class="p-4 bg-light rounded-4 text-center h-100">
                                        <h3 class="fw-bold text-primary mb-1"><?php echo $total_items; ?></h3>
                                        <p class="text-muted small mb-0">Peças</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-4 bg-light rounded-4 text-center h-100 cursor-pointer hover-effect"
                                        onclick="openNetworkModal('followers')">
                                        <h3 class="fw-bold text-primary mb-1" id="followers-count">
                                            <?php echo $followers_count; ?>
                                        </h3>
                                        <p class="text-muted small mb-0">Seguidores</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-4 bg-light rounded-4 text-center h-100 cursor-pointer hover-effect"
                                        onclick="openNetworkModal('following')">
                                        <h3 class="fw-bold text-primary mb-1" id="following-count">
                                            <?php echo $following_count; ?>
                                        </h3>
                                        <p class="text-muted small mb-0">A Seguir</p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($is_own_profile): ?>
                                <div class="text-center mb-4">
                                    <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal"
                                        data-bs-target="#searchModal">
                                        <i class="bi bi-person-plus"></i> Adicionar Amigos
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Posts Grid -->
                            <h5 class="fw-bold mb-3">Publicações</h5>
                            <?php if ($can_view): ?>
                                <?php if (empty($posts)): ?>
                                    <p class="text-muted text-center py-4">Ainda não há publicações.</p>
                                <?php else: ?>
                                    <div class="row g-3">
                                        <?php foreach ($posts as $post): ?>
                                            <div class="col-6 col-md-4">
                                                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                                                    <div class="card-img-top bg-light position-relative d-flex align-items-center justify-content-center"
                                                        style="aspect-ratio: 1/1; background-color: rgba(0,0,0,0.02) !important;">
                                                        <?php if ($post['image_path']): ?>
                                                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>"
                                                                class="w-100 h-100 object-fit-contain p-2">
                                                        <?php else: ?>
                                                            <div class="d-flex flex-wrap h-100 p-1">
                                                                <?php
                                                                $items = array_filter([$post['coat_img'], $post['top_img'], $post['bottom_img'], $post['shoes_img']]);
                                                                foreach ($items as $img):
                                                                    ?>
                                                                    <div class="flex-grow-1" style="width: 50%;">
                                                                        <img src="<?php echo htmlspecialchars($img); ?>"
                                                                            class="w-100 h-100 object-fit-contain p-1">
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-footer bg-white border-0 p-2 text-center">
                                                        <small
                                                            class="d-block text-truncate fw-bold"><?php echo $post['name'] ? htmlspecialchars($post['name']) : 'Outfit'; ?></small>
                                                        <small class="text-muted"
                                                            style="font-size: 0.7rem;"><?php echo date('d/m', strtotime($post['published_at'])); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-lock-fill display-4 text-muted opacity-25"></i>
                                    <p class="text-muted mt-2">Este perfil é privado.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_own_profile): ?>
            <div class="d-grid gap-2 mt-4">
                <a href="auth/logout.php" class="btn btn-danger btn-lg rounded-pill">Terminar Sessão</a>
            </div>
        <?php endif; ?>
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- Modals (Search & Network) - Same as before, just ensuring they exist -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Adicionar Amigos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="user-search-input" class="form-control bg-light border-start-0"
                            placeholder="Pesquisar utilizador...">
                    </div>
                    <div id="search-results" class="list-group list-group-flush">
                        <div class="text-center text-muted py-3 small">Começa a escrever para pesquisar...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="networkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="networkModalTitle">Lista</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="network-results" class="list-group list-group-flush">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Profile & Banner Click Logic
            const avatarArea = document.getElementById('avatar-area');
            const avatarInput = document.getElementById('avatar-input');
            const bannerArea = document.getElementById('banner-area');
            const bannerInput = document.getElementById('banner-input');

            if (avatarArea && avatarInput) {
                avatarArea.addEventListener('click', () => avatarInput.click());
            }

            if (bannerArea && bannerInput) {
                bannerArea.addEventListener('click', (e) => {
                    // Prevent click if clicking the settings gear
                    if (e.target.closest('.btn')) return;
                    bannerInput.click();
                });
            }

            // Profile Follow Button Logic
            const profileFollowBtn = document.querySelector('.btn-follow-profile');
            if (profileFollowBtn) {
                profileFollowBtn.addEventListener('click', function () {
                    const btn = this;
                    const userId = btn.dataset.id;
                    const action = btn.dataset.action;
                    btn.disabled = true;

                    fetch('api/ajax_follow.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ target_id: userId, action: action })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (action === 'follow') {
                                    if (data.message === 'Pedido enviado') {
                                        btn.textContent = 'Pedido Enviado';
                                        btn.classList.replace('btn-primary', 'btn-outline-secondary');
                                        btn.dataset.action = 'unfollow';
                                    } else {
                                        btn.textContent = 'A Seguir';
                                        btn.classList.replace('btn-primary', 'btn-outline-primary');
                                        btn.dataset.action = 'unfollow';
                                        const countEl = document.getElementById('followers-count');
                                        if (countEl) countEl.textContent = parseInt(countEl.textContent) + 1;
                                    }
                                } else {
                                    btn.textContent = 'Seguir';
                                    btn.classList.replace('btn-outline-primary', 'btn-primary');
                                    btn.dataset.action = 'follow';
                                    // Decrement followers count
                                    const countEl = document.getElementById('followers-count');
                                    if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent) - 1);
                                }
                            } else { alert('Erro: ' + data.message); }
                        })
                        .catch(err => { console.error(err); alert('Erro de conexão.'); })
                        .finally(() => { btn.disabled = false; });
                });
            }

            // Privacy Toggle Logic
            const privacyToggle = document.getElementById('privacyToggle');
            if (privacyToggle) {
                privacyToggle.addEventListener('change', function () {
                    const isPublic = this.checked ? 1 : 0;
                    fetch('api/ajax_toggle_privacy.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ is_public: isPublic })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                alert('Erro ao atualizar privacidade.');
                                this.checked = !this.checked; // Revert
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            this.checked = !this.checked;
                        });
                });
            }

            // Include previous JS for Search/Network (simplified for brevity, assuming it's preserved or re-included)
            // ... (The previous JS for search and network modals should be here)
            // Re-injecting the search/network JS logic to ensure it works
            const searchInput = document.getElementById('user-search-input');
            const resultsContainer = document.getElementById('search-results');
            let debounceTimer;

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();
                    if (query.length < 2) {
                        resultsContainer.innerHTML = '<div class="text-center text-muted py-3 small">Começa a escrever para pesquisar...</div>';
                        return;
                    }
                    debounceTimer = setTimeout(() => { fetchUsers(query); }, 300);
                });
            }

            function fetchUsers(query) {
                resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>';
                fetch(`api/ajax_search_users.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.users.length > 0) { renderUsers(data.users); }
                        else { resultsContainer.innerHTML = '<div class="text-center text-muted py-3 small">Nenhum utilizador encontrado.</div>'; }
                    })
                    .catch(err => { console.error(err); resultsContainer.innerHTML = '<div class="text-center text-danger py-3 small">Erro na pesquisa.</div>'; });
            }

            function renderUsers(users) {
                let html = '';
                users.forEach(user => {
                    const followStatus = user.follow_status;
                    const isFollowing = followStatus === 'accepted';
                    const isPending = followStatus === 'pending';

                    let btnClass = isFollowing || isPending ? 'btn-outline-secondary' : 'btn-primary';
                    let btnText = isFollowing ? 'A Seguir' : (isPending ? 'Pedido Enviado' : 'Seguir');
                    let action = isFollowing || isPending ? 'unfollow' : 'follow';

                    const img = user.profile_image ? user.profile_image : `https://ui-avatars.com/api/?name=${user.username}&background=random`;
                    html += `
                        <div class="list-group-item border-0 px-0 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <img src="${img}" class="rounded-circle object-fit-cover" style="width: 40px; height: 40px;">
                                <span class="fw-bold">${user.username}</span>
                            </div>
                            <button class="btn btn-sm ${btnClass} rounded-pill px-3 btn-follow" data-id="${user.id}" data-action="${action}">${btnText}</button>
                        </div>
                    `;
                });
                resultsContainer.innerHTML = html;
                document.querySelectorAll('.btn-follow').forEach(btn => { btn.addEventListener('click', handleFollow); });
            }

            function handleFollow(e) {
                const btn = e.target;
                const userId = btn.dataset.id;
                const action = btn.dataset.action;
                btn.disabled = true;
                fetch('api/ajax_follow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ target_id: userId, action: action })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (action === 'follow') {
                                if (data.message === 'Pedido enviado') {
                                    btn.textContent = 'Pedido Enviado';
                                    btn.classList.replace('btn-primary', 'btn-outline-secondary');
                                } else {
                                    btn.textContent = 'A Seguir';
                                    btn.classList.replace('btn-primary', 'btn-outline-secondary');
                                }
                                btn.dataset.action = 'unfollow';
                                // Update my following count on MY profile? No, this is mostly used on modals.
                            } else {
                                btn.textContent = 'Seguir';
                                btn.classList.replace('btn-outline-secondary', 'btn-primary');
                                btn.dataset.action = 'follow';
                            }
                        } else { alert('Erro: ' + data.message); }
                    })
                    .catch(err => { console.error(err); alert('Erro de conexão.'); })
                    .finally(() => { btn.disabled = false; });
            }

            // This updateStats function was for the main profile button, but now the profile button has its own logic.
            // This function is no longer directly called by the modal's handleFollow.
            // function updateStats(change) {
            //     const countEl = document.getElementById('following-count');
            //     if (countEl) {
            //         let current = parseInt(countEl.textContent);
            //         countEl.textContent = current + change;
            //     }
            // }

            window.openNetworkModal = function (type) {
                const modalEl = document.getElementById('networkModal');
                const modalTitle = document.getElementById('networkModalTitle');
                const resultsContainer = document.getElementById('network-results');
                modalTitle.textContent = type === 'followers' ? 'Seguidores' : 'A Seguir';
                resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>';
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                fetch(`api/ajax_get_network.php?type=${type}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.users.length > 0) { renderNetworkUsers(data.users, resultsContainer); }
                        else { resultsContainer.innerHTML = '<div class="text-center text-muted py-3 small">Lista vazia.</div>'; }
                    })
                    .catch(err => { console.error(err); resultsContainer.innerHTML = '<div class="text-center text-danger py-3 small">Erro ao carregar lista.</div>'; });
            };

            function renderNetworkUsers(users, container) {
                let html = '';
                users.forEach(user => {
                    const followStatus = user.follow_status;
                    const isFollowing = followStatus === 'accepted';
                    const isPending = followStatus === 'pending';

                    let btnClass = isFollowing || isPending ? 'btn-outline-secondary' : 'btn-primary';
                    let btnText = isFollowing ? 'A Seguir' : (isPending ? 'Pedido Enviado' : 'Seguir');
                    let action = isFollowing || isPending ? 'unfollow' : 'follow';

                    const img = user.profile_image ? user.profile_image : `https://ui-avatars.com/api/?name=${user.username}&background=random`;
                    html += `
                        <div class="list-group-item border-0 px-0 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <img src="${img}" class="rounded-circle object-fit-cover" style="width: 40px; height: 40px;">
                                <span class="fw-bold">${user.username}</span>
                            </div>
                            <button class="btn btn-sm ${btnClass} rounded-pill px-3 btn-follow-network" data-id="${user.id}" data-action="${action}">${btnText}</button>
                        </div>
                    `;
                });
                container.innerHTML = html;
                container.querySelectorAll('.btn-follow-network').forEach(btn => { btn.addEventListener('click', handleFollow); });
            }
        });
    </script>
</body>

</html>