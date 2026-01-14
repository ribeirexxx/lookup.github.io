<?php
// login.php
// Script de autenticação de utilizadores

session_start();
require_once '../includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['email']); // Mantemos o name='email' no form para não partir CSS/autofill, mas tratamos como input genérico
    $password = trim($_POST['password']);

    if (empty($login_input) || empty($password)) {
        $error = "Por favor preencha todos os campos.";
    } else {
        // Preparar a query para evitar SQL Injection
        // Verifica se o input corresponde ao email ou ao username
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = :input OR username = :input");
        $stmt->bindParam(':input', $login_input);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar a password
            if (password_verify($password, $user['password_hash'])) {
                // Login com sucesso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("location: ../dashboard.php"); // Redirecionar para o dashboard
                exit;
            } else {
                $error = "Password incorreta.";
            }
        } else {
            $error = "Não existe conta com este email ou username.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LOOKUP</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">

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

<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <a href="../index.php"
                                class="logo h2 text-decoration-none d-flex align-items-center justify-content-center gap-2">
                                <img src="../assets/img/logo.png" alt="Logo" style="height: 40px;">
                                LOOK<span>UP</span>
                            </a>
                            <h5 class="text-muted mt-2">Bem-vindo de volta!</h5>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form action="login.php" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label text-secondary">Email ou Username</label>
                                <input type="text" name="email" class="form-control form-control-lg fs-6"
                                    placeholder="exemplo@email.com ou username" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label text-secondary">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg fs-6"
                                    placeholder="••••••••" required>
                            </div>
                            <button type="submit"
                                class="btn btn-primary w-100 btn-lg rounded-pill fs-6 fw-bold">Entrar</button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="small text-muted">Ainda não tens conta? <a href="register.php"
                                    class="text-primary text-decoration-none fw-bold">Regista-te</a></p>
                            <p class="small"><a href="../index.html" class="text-secondary text-decoration-none">Voltar
                                    ao
                                    início</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>