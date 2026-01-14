<?php
// dashboard.php
session_start();

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once 'includes/db_connect.php';

// Obter contagem de roupas
$user_id = $_SESSION['user_id'];

// Count unread notifications
$stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt_unread->execute([$user_id]);
$unread_count = $stmt_unread->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clothing_items WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$total_items = $stmt->fetchColumn();

// Simulação de tempo
$temp = 22;
$condition = "Céu Limpo";
$icon = "☀️";

// Get user profile data and wardrobe
try {
    // Profile & Style
    $stmt = $pdo->prepare("SELECT profile_image, country, style_preference FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $profile_img = $user_data['profile_image'];
    $country = $user_data['country'] ?? 'Portugal';
    $style_preference = $user_data['style_preference'] ?? 'Casual';

    // Wardrobe Items
    $stmtW = $pdo->prepare("SELECT id, type, color, image_path FROM clothing_items WHERE user_id = ?");
    $stmtW->execute([$_SESSION['user_id']]);
    $wardrobe = $stmtW->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $profile_img = null;
    $country = 'Portugal';
    $style_preference = 'Casual';
    $wardrobe = [];
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
    <title>Dashboard - LOOKUP</title>
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

    <!-- Authenticated Navbar -->
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
                    <li class="nav-item"><a class="nav-link active fw-bold" href="dashboard.php">Dashboard</a></li>
                    <?php
                    // Verificar se é admin para mostrar link
                    $stmt_admin = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
                    $stmt_admin->execute([$_SESSION['user_id']]);
                    $user_admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
                    if ($user_admin && $user_admin['is_admin'] == 1):
                        ?>
                        <li class="nav-item"><a class="nav-link text-primary fw-bold" href="admin/admin.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>

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
                    <li class="nav-item ms-lg-3">
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12">
                <div class="col-12 d-flex align-items-center gap-3">
                    <?php if ($profile_img): ?>
                        <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile"
                            class="rounded-circle object-fit-cover shadow-sm" style="width: 64px; height: 64px;">
                    <?php endif; ?>
                    <div>
                        <h2 class="fw-bold mb-0">Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
                        <p class="text-muted mb-0">Aqui está o resumo do teu estilo hoje.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widgets Row -->
        <div class="row g-4">
            <!-- Weather Widget -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative weather-card">
                    <!-- Background Image dynamically set based on weather could go here -->
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center"
                        id="weather-container">
                        <div class="spinner-border text-light mx-auto" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-white mt-2 small">A carregar meteorologia...</p>
                    </div>
                </div>
            </div>

            <!-- Smart Outfit Suggestion -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 rounded-4 position-relative overflow-hidden"
                    style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="fw-bold text-dark mb-0">Sugestão do Dia</h5>
                            <span class="badge bg-white text-dark shadow-sm rounded-pill px-2">Estilo
                                <?php echo htmlspecialchars($style_preference); ?></span>
                        </div>

                        <div id="outfit-container"
                            class="flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center">
                            <div class="spinner-border text-secondary spinner-border-sm mb-2" role="status"></div>
                            <small class="text-muted">A analisar o tempo...</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wardrobe Stats -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Teu Guarda-Roupa</h5>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-muted">Total de Peças</span>
                            <span class="h2 fw-bold mb-0 text-primary"><?php echo $total_items; ?></span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 70%"></div>
                        </div>
                        <a href="wardrobe.php" class="btn btn-outline-primary w-100 rounded-pill">Gerir Roupa</a>
                    </div>
                </div>
            </div>

            <!-- Quick Action -->
            <div class="col-12">
                <div
                    class="card border-0 shadow-sm h-100 rounded-4 bg-primary text-white position-relative overflow-hidden">
                    <div class="card-body p-4 position-relative z-1 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Adicionar Nova Peça</h5>
                            <p class="opacity-75 mb-0">Comprou algo novo? Adiciona ao teu armário virtual.</p>
                        </div>
                        <a href="wardrobe.php?action=add"
                            class="btn btn-light text-primary rounded-pill px-4 fw-bold shadow-sm">Adicionar</a>
                    </div>
                    <div class="position-absolute bottom-0 end-0 opacity-25"
                        style="font-size: 6rem; line-height: 0.8; margin-right: -10px; margin-bottom: -10px;">
                        👕</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .weather-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: transform 0.3s ease;
        }

        .weather-card:hover {
            transform: translateY(-5px);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Use json_encode for safe JS string output
            const country = <?php echo json_encode($country); ?>;
            const style = <?php echo json_encode($style_preference); ?>;
            const wardrobe = <?php echo json_encode($wardrobe); ?>;

            const weatherContainer = document.getElementById('weather-container');
            const outfitContainer = document.getElementById('outfit-container');

            if (!weatherContainer) return;

            // Mapeamento explícito de timezones
            const timezoneMap = {
                'Portugal': 'Europe/Lisbon',
                'Brazil': 'America/Sao_Paulo',
                'Spain': 'Europe/Madrid',
                'France': 'Europe/Paris',
                'United Kingdom': 'Europe/London',
                'Germany': 'Europe/Berlin',
                'United States': 'America/New_York',
                'Italy': 'Europe/Rome',
                'Angola': 'Africa/Luanda',
                'Mozambique': 'Africa/Maputo',
                'Cape Verde': 'Atlantic/Cape_Verde'
            };

            // Fallback para caso o país esteja vazio
            const searchCountry = country || 'Portugal';
            let userTimezone = timezoneMap[country] || 'UTC';

            console.log("Fetching weather for:", searchCountry);

            fetch(`https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(searchCountry)}&count=1&language=pt&format=json`)
                .then(response => {
                    if (!response.ok) throw new Error('Geocoding API failed');
                    return response.json();
                })
                .then(data => {
                    if (data.results && data.results.length > 0) {
                        const lat = data.results[0].latitude;
                        const lon = data.results[0].longitude;

                        // Só atualiza a timezone pela API se não tivermos uma mapeada manualmente
                        if (!timezoneMap[country]) {
                            userTimezone = data.results[0].timezone;
                        }

                        return fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=auto`);
                    } else {
                        throw new Error('País não encontrado.');
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Weather API failed');
                    return response.json();
                })
                .then(data => {
                    const current = data.current;
                    if (!current) throw new Error('Dados incompletos da API');

                    const temp = Math.round(current.temperature_2m);
                    const wind = current.wind_speed_10m;
                    const humidity = current.relative_humidity_2m;
                    const weatherCode = current.weather_code;

                    // Mapeamento simples de códigos WMO para Ícones/Texto
                    let icon = "☀️";
                    let condition = "Céu Limpo";

                    if (weatherCode >= 1 && weatherCode <= 3) { icon = "⛅"; condition = "Parcialmente Nublado"; }
                    else if (weatherCode >= 45 && weatherCode <= 48) { icon = "🌫️"; condition = "Nevoeiro"; }
                    else if (weatherCode >= 51 && weatherCode <= 67) { icon = "🌧️"; condition = "Chuva"; }
                    else if (weatherCode >= 71 && weatherCode <= 77) { icon = "❄️"; condition = "Neve"; }
                    else if (weatherCode >= 80 && weatherCode <= 82) { icon = "🌧️"; condition = "Aguaceiros"; }
                    else if (weatherCode >= 95) { icon = "⛈️"; condition = "Trovoada"; }

                    const updateTime = () => {
                        try {
                            const now = new Date();
                            return now.toLocaleTimeString('pt-PT', {
                                timeZone: userTimezone,
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        } catch (e) {
                            console.error("Timezone error:", e);
                            return "--:--";
                        }
                    };

                    const timeString = updateTime();

                    weatherContainer.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center w-100 mb-3">
                                    <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-1 fw-light"><i class="bi bi-geo-alt-fill"></i> ${searchCountry}</span>
                                    <span class="fw-bold" id="country-time">${timeString}</span>
                                </div>
                                <div class="display-1 mb-2 weather-icon">${icon}</div>
                                <h2 class="fw-bold mb-0">${temp}°C</h2>
                                <p class="opacity-75 mb-3">${condition}</p>
                                <div class="row w-100 border-top border-white border-opacity-25 pt-3 mt-2">
                                    <div class="col-6 text-center border-end border-white border-opacity-25">
                                        <small class="d-block opacity-75 text-uppercase" style="font-size: 0.7rem;">Vento</small>
                                        <span class="fw-bold">${wind} km/h</span>
                                    </div>
                                    <div class="col-6 text-center">
                                        <small class="d-block opacity-75 text-uppercase" style="font-size: 0.7rem;">Humidade</small>
                                        <span class="fw-bold">${humidity}%</span>
                                    </div>
                                </div>
                            `;

                    setInterval(() => {
                        const timeEl = document.getElementById('country-time');
                        if (timeEl) timeEl.textContent = updateTime();
                    }, 1000);

                    // --- SMART STYLIST LOGIC ---
                    suggestOutfit(temp, wardrobe, outfitContainer);

                })
                .catch(err => {
                    console.error("Weather Error:", err);
                    weatherContainer.innerHTML = `
                                <div class="text-center h-100 d-flex flex-column justify-content-center">
                                    <div class="text-white opacity-50 display-4 mb-2">⚠️</div>
                                    <p class="small text-white">Não disponível.</p>
                                    <button class="btn btn-sm btn-outline-light rounded-pill mt-2" onclick="location.reload()">Recarregar</button>
                                </div>
                            `;
                    if (outfitContainer) outfitContainer.innerHTML = '<p class="text-muted small">Sem dados meteorológicos.</p>';
                });

            let currentOutfitData = null;

            function suggestOutfit(temp, items, container) {
                if (!items || items.length === 0) {
                    container.innerHTML = `
                                <div class="text-center">
                                    <p class="mb-2">📭</p>
                                    <p class="text-muted small">Adiciona roupa ao teu armário para receberes sugestões!</p>
                                    <a href="wardrobe.php?action=add" class="btn btn-sm btn-dark rounded-pill mt-2">Adicionar Roupa</a>
                                </div>
                            `;
                    return;
                }

                // Categorias simples
                const tops = items.filter(i => ['camisola', 't-shirt', 'top'].includes(i.type.toLowerCase()) || i.type.toLowerCase().includes('camisa'));
                const bottoms = items.filter(i => ['calças', 'shorts', 'saia'].includes(i.type.toLowerCase()));
                const shoes = items.filter(i => ['sapatos', 'ténis', 'botas', 'sandálias'].includes(i.type.toLowerCase()));
                const coats = items.filter(i => ['casaco', 'sobretudo', 'blazer'].includes(i.type.toLowerCase()));

                let suggestion = { top: null, bottom: null, shoes: null, coat: null };
                let message = "";

                // Lógica baseada na temperatura
                if (temp >= 25) {
                    // Calor: T-shirt/Top + Shorts/Saia + Sandálias/Ténis
                    message = "Está calor! ☀️ Aqui tens algo fresco.";
                    suggestion.top = getRandom(tops);
                    suggestion.bottom = getRandom(bottoms); // Idealmente shorts, mas simplificado
                    suggestion.shoes = getRandom(shoes);
                } else if (temp >= 15) {
                    // Ameno: Camisola + Calças + Ténis
                    message = "Tempo agradável. 👌";
                    suggestion.top = getRandom(tops);
                    suggestion.bottom = getRandom(bottoms);
                    suggestion.shoes = getRandom(shoes);
                } else {
                    // Frio: Casaco + Camisola + Calças + Botas/Ténis
                    message = "Está frio! 🥶 Leva um casaco.";
                    suggestion.top = getRandom(tops);
                    suggestion.bottom = getRandom(bottoms);
                    suggestion.shoes = getRandom(shoes);
                    suggestion.coat = getRandom(coats);
                }

                currentOutfitData = suggestion;

                // Renderizar
                let html = `<p class="text-primary small fw-bold mb-3">${message}</p><div class="d-flex justify-content-center gap-2 flex-wrap">`;

                const renderItem = (item, label) => {
                    if (!item) return '';
                    return `
                                <div class="text-center" style="width: 80px;">
                                    <div class="rounded-3 shadow-sm border mb-1 overflow-hidden" style="height: 80px; width: 80px;">
                                        <img src="${item.image_path}" class="w-100 h-100 object-fit-contain p-1" alt="${item.type}">
                                    </div>
                                    <span class="d-block text-muted" style="font-size: 0.65rem;">${label}</span>
                                </div>
                            `;
                };

                if (suggestion.coat) html += renderItem(suggestion.coat, 'Casaco');
                if (suggestion.top) html += renderItem(suggestion.top, 'Top');
                if (suggestion.bottom) html += renderItem(suggestion.bottom, 'Baixo');
                if (suggestion.shoes) html += renderItem(suggestion.shoes, 'Calçado');

                html += `</div>`;

                if (!suggestion.top && !suggestion.bottom) {
                    html = `<p class="text-muted small">Não encontrei peças suficientes para o tempo atual (${temp}°C).</p>`;
                } else {
                    // Add Buttons
                    html += `
                        <div class="d-flex gap-2 mt-3 justify-content-center w-100">
                            <button id="btn-refresh-outfit" class="btn btn-light btn-sm rounded-pill shadow-sm border px-3">
                                🔄 Gerar Outro
                            </button>
                            <button id="btn-save-outfit" class="btn btn-outline-danger btn-sm rounded-pill shadow-sm border px-3">
                                ❤️ Guardar
                            </button>
                        </div>
                    `;
                }

                container.innerHTML = html;

                // Attach Listeners
                const btnRefresh = document.getElementById('btn-refresh-outfit');
                const btnSave = document.getElementById('btn-save-outfit');

                if (btnRefresh) {
                    btnRefresh.addEventListener('click', () => suggestOutfit(temp, items, container));
                }
                if (btnSave) {
                    btnSave.addEventListener('click', saveCurrentOutfit);
                }
            }

            function saveCurrentOutfit() {
                if (!currentOutfitData) return;

                const payload = {
                    top_id: currentOutfitData.top ? currentOutfitData.top.id : null,
                    bottom_id: currentOutfitData.bottom ? currentOutfitData.bottom.id : null,
                    shoes_id: currentOutfitData.shoes ? currentOutfitData.shoes.id : null,
                    coat_id: currentOutfitData.coat ? currentOutfitData.coat.id : null
                };

                fetch('api/ajax_save_outfit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const toastEl = document.getElementById('favToast');
                            const toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        } else {
                            alert('Erro ao guardar: ' + data.message);
                        }
                    })
                    .catch(err => console.error('Error saving outfit:', err));
            }

            function getRandom(arr) {
                if (!arr || arr.length === 0) return null;
                return arr[Math.floor(Math.random() * arr.length)];
            }
        });
    </script>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="favToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    Outfit guardado nos favoritos! ❤️
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
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