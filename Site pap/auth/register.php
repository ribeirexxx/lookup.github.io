<?php
// register.php
// Script de registo de novos utilizadores

require_once '../includes/db_connect.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Hash da password para segurança
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Verificar se são credenciais de admin
    $is_admin = 0;
    if ($username === 'ADM' && $password === 'adm12345') {
        $is_admin = 1;
    }

    try {
        $sql = "INSERT INTO users (username, email, password_hash, is_admin, country, style_preference, gender) VALUES (:username, :email, :password_hash, :is_admin, :country, :style, :gender)";
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':is_admin', $is_admin);

        $country = $_POST['country'];
        $style = $_POST['style'];
        $gender = $_POST['gender'];
        $stmt->bindParam(':country', $country);
        $stmt->bindParam(':style', $style);
        $stmt->bindParam(':gender', $gender);

        if ($stmt->execute()) {
            $message = "Registo efetuado com sucesso! <a href='login.php' class='fw-bold'>Faça login aqui</a>.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            // Verificação mais específica se o erro foi no email ou username
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Este nome de utilizador já existe.";
            } else {
                $error = "Este email já existe.";
            }
        } else {
            $error = "Erro no registo: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - LOOKUP</title>
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
                            <h5 class="text-muted mt-2">Cria a tua conta</h5>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-success py-2 text-center"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <!-- Error Alert Script -->
                        <?php if ($error): ?>
                            <script>alert("<?php echo addslashes($error); ?>");</script>
                        <?php endif; ?>

                        <form action="register.php" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label text-secondary">Nome de Utilizador</label>
                                <input type="text" name="username" class="form-control form-control-lg fs-6"
                                    placeholder="O teu nome"
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>

                            <!-- Gender Select -->
                            <div class="mb-3">
                                <label for="gender" class="form-label text-secondary">Género</label>
                                <select name="gender" class="form-select form-select-lg fs-6" required>
                                    <option value="" disabled <?php echo (!isset($_POST['gender'])) ? 'selected' : ''; ?>>Selecione o seu género</option>
                                    <option value="Masculino" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="Feminino" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                                    <option value="Outro" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Outro') ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label text-secondary">Email</label>
                                <input type="email" name="email" class="form-control form-control-lg fs-6"
                                    placeholder="exemplo@email.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label text-secondary">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg fs-6"
                                    placeholder="••••••••" required>
                            </div>

                            <!-- Country Select -->
                            <div class="mb-3">
                                <label for="country" class="form-label text-secondary">País</label>
                                <select name="country" class="form-select form-select-lg fs-6" required>
                                    <option value="" disabled <?php echo (!isset($_POST['country'])) ? 'selected' : ''; ?>>Selecione o seu país</option>
                                    <?php
                                    $countries = ["Portugal", "Brazil", "Spain", "France", "United Kingdom", "Germany", "United States", "Italy", "Angola", "Mozambique", "Cape Verde"];
                                    foreach ($countries as $c) {
                                        $selected = (isset($_POST['country']) && $_POST['country'] === $c) ? 'selected' : '';
                                        echo "<option value=\"$c\" $selected>$c</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Style Select -->
                            <div class="mb-4">
                                <label for="style" class="form-label text-secondary">Estilo Principal</label>
                                <select name="style" class="form-select form-select-lg fs-6" required>
                                    <option value="" disabled <?php echo (!isset($_POST['style'])) ? 'selected' : ''; ?>>Selecione o seu estilo</option>
                                    <?php
                                    $styles = ["Casual", "Formal", "Streetwear", "Business", "Sporty", "Vintage", "Chic", "Bohemian", "Minimalist", "Grunge", "Preppy", "Punk", "Goth", "Hip Hop", "Classic"];
                                    foreach ($styles as $s) {
                                        $selected = (isset($_POST['style']) && $_POST['style'] === $s) ? 'selected' : '';
                                        echo "<option value=\"$s\" $selected>$s</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg rounded-pill fs-6 fw-bold">Criar
                                Conta</button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="small text-muted">Já tens conta? <a href="login.php"
                                    class="text-primary text-decoration-none fw-bold">Faz Login</a></p>
                            <a href="../index.php"
                                class="logo h2 text-decoration-none d-flex align-items-center justify-content-center gap-2">
                                <img src="../assets/img/logo.png" alt="Logo" style="height: 30px;">
                                LOOK<span>UP</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>