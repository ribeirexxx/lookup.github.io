<?php
// repair_db.php
// Script para reparar a base de dados adicionando colunas em falta e criando tabelas necessárias.

require_once 'includes/db_connect.php';

echo "<h2>🔧 A reparar a base de dados...</h2>";

function columnExists($pdo, $table, $column)
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $stmt->rowCount() > 0;
}

function addColumn($pdo, $table, $column, $definition)
{
    if (!columnExists($pdo, $table, $column)) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "✅ Coluna '$column' adicionada à tabela '$table'.<br>";
        } catch (PDOException $e) {
            echo "❌ Erro ao adicionar '$column' a '$table': " . $e->getMessage() . "<br>";
        }
    } else {
        echo "ℹ️ A coluna '$column' já existe em '$table'.<br>";
    }
}

try {
    // 1. Tabela 'users'
    echo "<h3>Verificando tabela 'users'...</h3>";
    addColumn($pdo, 'users', 'profile_image', "VARCHAR(255) DEFAULT NULL AFTER password_hash");
    addColumn($pdo, 'users', 'profile_banner', "VARCHAR(255) DEFAULT NULL AFTER profile_image");
    addColumn($pdo, 'users', 'country', "VARCHAR(100) DEFAULT 'Portugal'");
    addColumn($pdo, 'users', 'style_preference', "VARCHAR(100) DEFAULT 'Casual'");
    addColumn($pdo, 'users', 'gender', "VARCHAR(20) DEFAULT 'Outro'");
    addColumn($pdo, 'users', 'is_admin', "BOOLEAN DEFAULT 0");
    addColumn($pdo, 'users', 'is_public', "BOOLEAN DEFAULT 1");
    addColumn($pdo, 'users', 'theme_preference', "ENUM('light', 'dark') DEFAULT 'light'");

    // 2. Tabela 'clothing_items'
    echo "<h3>Verificando tabela 'clothing_items'...</h3>";
    addColumn($pdo, 'clothing_items', 'usage_count', "INT DEFAULT 0");
    addColumn($pdo, 'clothing_items', 'last_worn', "DATE DEFAULT NULL");

    // 3. Tabela 'outfits'
    echo "<h3>Verificando tabela 'outfits'...</h3>";
    addColumn($pdo, 'outfits', 'coat_id', "INT DEFAULT NULL AFTER shoes_id");
    addColumn($pdo, 'outfits', 'is_published', "BOOLEAN DEFAULT 0");
    addColumn($pdo, 'outfits', 'description', "TEXT");
    addColumn($pdo, 'outfits', 'published_at', "TIMESTAMP NULL DEFAULT NULL");
    addColumn($pdo, 'outfits', 'image_path', "VARCHAR(255) DEFAULT NULL");
    addColumn($pdo, 'outfits', 'repost_of_id', "INT DEFAULT NULL");

    // Foreign Key para coat_id se não existir
    try {
        $pdo->exec("ALTER TABLE outfits ADD CONSTRAINT fk_outfits_coat FOREIGN KEY (coat_id) REFERENCES clothing_items(id) ON DELETE SET NULL");
        echo "✅ Constraint fk_outfits_coat adicionada.<br>";
    } catch (Exception $e) {
    }

    // Foreign Key para repost_of_id se não existir
    try {
        $pdo->exec("ALTER TABLE outfits ADD CONSTRAINT fk_outfits_repost FOREIGN KEY (repost_of_id) REFERENCES outfits(id) ON DELETE SET NULL");
        echo "✅ Constraint fk_outfits_repost adicionada.<br>";
    } catch (Exception $e) {
    }

    // 4. Criar Tabelas Sociais
    echo "<h3>Verificando tabelas sociais...</h3>";

    // Likes
    $pdo->exec("CREATE TABLE IF NOT EXISTS outfit_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        outfit_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (user_id, outfit_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE
    )");
    echo "✅ Tabela 'outfit_likes' verificada/criada.<br>";

    // Comments
    $pdo->exec("CREATE TABLE IF NOT EXISTS outfit_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        outfit_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE
    )");
    echo "✅ Tabela 'outfit_comments' verificada/criada.<br>";

    // 5. Criar Tabela 'follows' se não existir
    echo "<h3>Verificando tabela 'follows'...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS follows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        status ENUM('pending', 'accepted') DEFAULT 'accepted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    addColumn($pdo, 'follows', 'status', "ENUM('pending', 'accepted') DEFAULT 'accepted'");

    // 6. Criar Tabela 'notifications' se não existir
    echo "<h3>Verificando tabela 'notifications'...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sender_id INT NOT NULL,
        type ENUM('follow', 'like', 'comment', 'repost', 'follow_request') NOT NULL,
        target_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Garantir que o tipo 'follow_request' existe no enum
    try {
        $pdo->exec("ALTER TABLE notifications MODIFY COLUMN type ENUM('follow', 'like', 'comment', 'repost', 'follow_request') NOT NULL");
        echo "✅ Tipo 'follow_request' garantido na tabela 'notifications'.<br>";
    } catch (Exception $e) {
        echo "ℹ️ Enum na tabela 'notifications' já atualizado ou erro: " . $e->getMessage() . "<br>";
    }
    echo "✅ Tabela 'notifications' verificada/criada.<br>";

    echo "<h2 style='color: green;'>✨ Reparação concluída com sucesso!</h2>";
    echo "<p>Agora podes testar as funcionalidades de seguir e adicionar amigos.</p>";
    echo "<a href='profile.php'>Ir para o Perfil</a>";

} catch (PDOException $e) {
    die("<h2 style='color: red;'>❌ Erro fatal: " . $e->getMessage() . "</h2>");
}
?>