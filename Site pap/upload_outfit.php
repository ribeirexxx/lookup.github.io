<?php
// upload_outfit.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once 'includes/db_connect.php';
$user_id = $_SESSION['user_id'];
$message = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';
    // Tags
    $top_id = !empty($_POST['top_id']) ? $_POST['top_id'] : null;
    $bottom_id = !empty($_POST['bottom_id']) ? $_POST['bottom_id'] : null;
    $shoes_id = !empty($_POST['shoes_id']) ? $_POST['shoes_id'] : null;
    $coat_id = !empty($_POST['coat_id']) ? $_POST['coat_id'] : null;

    // Image Handling
    if (isset($_FILES['outfit_image']) && $_FILES['outfit_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['outfit_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('outfit_', true) . '.' . $ext;
            $upload_path = 'uploads/outfits/' . $new_filename;

            // Ensure directory exists
            if (!is_dir('uploads/outfits')) {
                mkdir('uploads/outfits', 0777, true);
            }

            if (move_uploaded_file($_FILES['outfit_image']['tmp_name'], $upload_path)) {
                // Insert into DB
                // Use provided title or fallback to "Outfit + Date"
                $raw_title = $_POST['title'] ?? '';
                $name = !empty(trim($raw_title)) ? trim($raw_title) : "Outfit " . date('d/m/Y');

                $sql = "INSERT INTO outfits (user_id, name, description, image_path, top_id, bottom_id, shoes_id, coat_id, is_published, published_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())"; // Default to published immediately as per request flow

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$user_id, $name, $description, $upload_path, $top_id, $bottom_id, $shoes_id, $coat_id])) {
                    header("Location: feed.php"); // Redirect to feed after post
                    exit;
                } else {
                    $message = "Erro ao guardar na base de dados.";
                }
            } else {
                $message = "Erro ao mover o ficheiro.";
            }
        } else {
            $message = "Formato de ficheiro inválido. Use JPG, PNG ou WEBP.";
        }
    } else {
        $message = "Por favor selecione uma imagem.";
    }
}

// Fetch user's wardrobe items for tagging
$items_sql = "SELECT id, type, image_path, color FROM clothing_items WHERE user_id = ?";
$stmt = $pdo->prepare($items_sql);
$stmt->execute([$user_id]);
$wardrobe = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Group by type (index 0 which is id? No, fetch_group uses first column)
// Actually FETCH_GROUP uses first column as key. 
// Let's modify SQL to make 'type' the first column for grouping
$items_sql = "SELECT type, id, image_path, color FROM clothing_items WHERE user_id = ?";
$stmt = $pdo->prepare($items_sql);
$stmt->execute([$user_id]);
$wardrobe = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

// Helper to get options
function getOptions($items)
{
    $html = '<option value="">Sem item</option>';
    if (!isset($items))
        return $html;
    foreach ($items as $item) {
        $html .= '<option value="' . $item['id'] . '">' . ucfirst($item['color']) . '</option>';
    }
    return $html;
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
    <title>Novo Post - LOOKUP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <style>
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 1rem;
            transition: all 0.3s;
            cursor: pointer;
            background-color: var(--card-bg);
        }

        .upload-area:hover {
            border-color: var(--accent-color);
            background-color: rgba(108, 92, 231, 0.05);
        }

        .preview-image {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 1rem;
            display: none;
        }
    </style>
</head>

<body class="bg-light <?php echo $themeClass; ?>">
    <nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand logo d-flex align-items-center gap-2" href="dashboard.php">
                <img src="assets/img/logo.png" alt="Logo" style="height: 30px;">
                LOOK<span>UP</span>
            </a>
            <div class="ms-auto">
                <a href="feed.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Cancelar</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4">Criar Novo Post 📸</h3>

                <?php if ($message): ?>
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- Image Upload -->
                    <label class="upload-area p-5 mb-4 text-center d-block" for="file-input">
                        <div id="upload-placeholder">
                            <div class="display-1 text-muted opacity-50 mb-3">☁️</div>
                            <h5 class="fw-bold text-muted">Carregar Foto</h5>
                            <p class="text-secondary small">Clica para selecionar a foto do teu outfit</p>
                        </div>
                        <img id="image-preview" class="preview-image mx-auto">
                    </label>
                    <input type="file" name="outfit_image" id="file-input" class="d-none" accept="image/*" required
                        onchange="previewImage(this)">

                    <!-- Title & Description -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Título</label>
                        <input type="text" name="title" class="form-control rounded-4 p-3"
                            placeholder="Ex: Look de Verão (Opcional)">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea name="description" class="form-control rounded-4 p-3" rows="3"
                            placeholder="O que tens a dizer sobre este look?"></textarea>
                    </div>

                    <!-- Tags -->
                    <h5 class="fw-bold mb-3">Marcar Peças (Opcional)</h5>
                    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="small text-muted mb-1">Casaco</label>
                                <select name="coat_id" class="form-select rounded-pill">
                                    <?php echo getOptions($wardrobe['casaco'] ?? null); ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1">Top / Camisola</label>
                                <select name="top_id" class="form-select rounded-pill">
                                    <?php echo getOptions($wardrobe['camisola'] ?? null); ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1">Calças</label>
                                <select name="bottom_id" class="form-select rounded-pill">
                                    <?php echo getOptions($wardrobe['calças'] ?? null); ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1">Calçado</label>
                                <select name="shoes_id" class="form-select rounded-pill">
                                    <?php echo getOptions($wardrobe['sapatos'] ?? null); ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">Publicar
                        Outfit</button>

                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('upload-placeholder').style.display = 'none';
                    var img = document.getElementById('image-preview');
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>