<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOOKUP - O Teu Estilo, Reinventado</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Favicon -->
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


<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand logo" href="#">LOOK<span>UP</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="#home">Início</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">Sobre</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Funcionalidades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contacto</a></li>
                    <li class="nav-item">
                        <a href="auth/login.php" class="btn btn-outline-primary rounded-pill px-4">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="auth/register.php" class="btn btn-primary rounded-pill px-4">Registar</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a href="auth/logout.php" class="btn btn-outline-danger rounded-pill px-4">Sair</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="theme-toggle" class="theme-toggle" title="Alternar Tema">
                            <i class="bi bi-moon-stars-fill"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="home" class="hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content text-center text-lg-start mb-5 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">O Teu Estilo, <br><span class="highlight">Reinventado.</span>
                    </h1>
                    <p class="lead text-muted mb-5">Organiza o teu guarda-roupa, recebe sugestões de looks e veste-te
                        com confiança todos os dias.</p>
                    <a href="#about" class="btn btn-primary btn-lg rounded-pill px-5 shadow-lg">Saber Mais</a>
                </div>
                <div class="col-lg-6 position-relative text-center">
                    <img src="assets/img/app_mockup_home.png" alt="App Mockup" class="img-fluid floating-mockup">
                </div>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <section id="about" class="section py-5 bg-light">
        <div class="container py-5">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="fw-bold mb-3">Sobre o Projeto</h2>
                    <p class="text-muted lead">Uma solução inteligente para a gestão do vestuário diário.</p>
                </div>
            </div>
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <p class="lead mb-4 text-secondary">O <strong>LOOKUP</strong> é um projeto desenvolvido no âmbito da
                        Prova de
                        Aptidão Profissional (PAP) com o objetivo de simplificar a rotina matinal de escolher o que
                        vestir.</p>
                    <p class="text-muted">A aplicação permite digitalizar o guarda-roupa, criar combinações e, o mais
                        importante, receber
                        sugestões automáticas baseadas na meteorologia e no gosto pessoal do utilizador.</p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/img/app_mockup_wardrobe.png" alt="Wardrobe Feature"
                        class="img-fluid rounded-4 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Funcionalidades Principais</h2>
                <p class="text-muted">Tudo o que precisas para gerir o teu estilo.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-center rounded-4 border-0 shadow-sm bg-white">
                        <div class="display-4 mb-4">🔐</div>
                        <h3 class="h5 fw-bold mb-3">Autenticação Segura</h3>
                        <p class="text-muted small">Registo e login personalizados para manter os teus dados seguros.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-center rounded-4 border-0 shadow-sm bg-white">
                        <div class="display-4 mb-4">👕</div>
                        <h3 class="h5 fw-bold mb-3">Gestão de Roupa</h3>
                        <p class="text-muted small">Adiciona, edita e organiza as tuas peças num guarda-roupa digital.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-center rounded-4 border-0 shadow-sm bg-white">
                        <div class="display-4 mb-4">🌤️</div>
                        <h3 class="h5 fw-bold mb-3">Meteorologia</h3>
                        <p class="text-muted small">Sugestões de roupa adequadas ao clima do dia em tempo real.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card h-100 p-4 text-center rounded-4 border-0 shadow-sm bg-white">
                        <div class="display-4 mb-4">✨</div>
                        <h3 class="h5 fw-bold mb-3">Sugestões Smart</h3>
                        <p class="text-muted small">Algoritmo que combina estilo e tempo para o look perfeito.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer py-5 text-center text-white">
        <div class="container">
            <div class="footer-logo h3 fw-bold mb-3">LOOK<span>UP</span></div>
            <p class="mb-2 opacity-75">Projeto de Aptidão Profissional (PAP)</p>
            <p class="small opacity-50">&copy; 2025 Jonathan Ribeiro. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>