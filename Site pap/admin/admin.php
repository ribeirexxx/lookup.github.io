<?php
// admin.php
session_start();
require_once '../includes/db_connect.php';

// --- SEGURANÇA: Verificar se é admin ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT is_admin, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['is_admin'] != 1) {
    // Se não for admin, redirecionar para dashboard normal ou erro
    header("Location: ../dashboard.php");
    exit;
}

// --- BUSCAR TODOS OS USERS ---
// Filtros e Ordenação
$orderBy = 'created_at DESC'; // Default
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($sort === 'alpha_asc') {
    $orderBy = 'username ASC';
} elseif ($sort === 'alpha_desc') {
    $orderBy = 'username DESC';
} elseif ($sort === 'date_asc') {
    $orderBy = 'created_at ASC';
} elseif ($sort === 'date_desc') {
    $orderBy = 'created_at DESC';
}

$sql = "SELECT id, username, email, created_at, is_admin FROM users";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE username LIKE ? OR email LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - LOOKUP</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body class="bg-light">

    <!-- Navbar simples -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand logo d-flex align-items-center gap-2 fw-bold text-white text-decoration-none"
                href="admin.php">
                <img src="../assets/img/logo.png" alt="Logo" style="height: 30px;">
                LOOK<span class="text-primary">UP</span> <small class="text-white-50 ms-1"
                    style="font-size: 0.8rem;">ADMIN</small>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small">Logado como: <strong
                        class="text-white"><?php echo htmlspecialchars($user['username']); ?></strong></span>
                <a href="../dashboard.php" class="btn btn-outline-light btn-sm">Ir para Site</a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">

        <div class="row align-items-center mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <form action="admin.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control rounded-pill"
                        placeholder="Pesquisar por nome ou email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-secondary rounded-pill">🔍</button>
                    <?php if ($sort): ?><input type="hidden" name="sort"
                            value="<?php echo htmlspecialchars($sort); ?>"><?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="dropdown d-inline-block">
                    <button class="btn btn-white border shadow-sm rounded-pill dropdown-toggle px-4" type="button"
                        data-bs-toggle="dropdown">
                        Ordenar por
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 rounded-4 mt-2">
                        <li><a class="dropdown-item"
                                href="admin.php?sort=date_desc&search=<?php echo urlencode($search); ?>">📅 Mais
                                Recentes</a></li>
                        <li><a class="dropdown-item"
                                href="admin.php?sort=date_asc&search=<?php echo urlencode($search); ?>">📅 Mais
                                Antigos</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item"
                                href="admin.php?sort=alpha_asc&search=<?php echo urlencode($search); ?>">🅰️ Nome
                                (A-Z)</a></li>
                        <li><a class="dropdown-item"
                                href="admin.php?sort=alpha_desc&search=<?php echo urlencode($search); ?>">💤 Nome
                                (Z-A)</a></li>
                    </ul>
                </div>
                <button class="btn btn-primary rounded-pill px-4 ms-2" data-bs-toggle="modal"
                    data-bs-target="#addUserModal">
                    + Novo Utilizador
                </button>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabela de Users -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-secondary border-0">ID</th>
                            <th class="py-3 text-secondary border-0">Utilizador</th>
                            <th class="py-3 text-secondary border-0">Email</th> <!-- Mostrando Email -->
                            <th class="py-3 text-secondary border-0">Data Criação</th>
                            <th class="py-3 text-secondary border-0">Tipo</th>
                            <th class="text-end pe-4 py-3 text-secondary border-0">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?php echo $u['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                                            style="width: 40px; height: 40px; color: #666;">
                                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($u['username']); ?></span>
                                    </div>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($u['is_admin']): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Admin</span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Aceder Conta (Impersonate) -->
                                        <form action="admin_actions.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="impersonate">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3"
                                                title="Aceder à conta">
                                                Aceder
                                            </button>
                                        </form>

                                        <!-- Apagar (Só se não for ele mesmo) -->
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form action="admin_actions.php" method="POST" class="d-inline"
                                                onsubmit="return confirm('Tem a certeza que deseja apagar este utilizador? Esta ação é irreversível.');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                    title="Remover">
                                                    Remover
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar User -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold ms-2">Novo Utilizador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="admin_actions.php" method="POST">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Username</label>
                            <input type="text" name="username"
                                class="form-control form-control-lg fs-6 bg-light border-0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Email</label>
                            <input type="email" name="email" class="form-control form-control-lg fs-6 bg-light border-0"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small text-uppercase fw-bold">Password</label>
                            <input type="password" name="password"
                                class="form-control form-control-lg fs-6 bg-light border-0" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg rounded-pill fs-6 fw-bold">Criar
                            Conta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>