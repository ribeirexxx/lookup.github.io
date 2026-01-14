-- Consolidado de Base de Dados para LOOKUP
-- Baseado em database.sql + update_schema_v2 até v9

CREATE DATABASE IF NOT EXISTS lookup_db2;
USE lookup_db2;

-- Tabela de Utilizadores
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    profile_banner VARCHAR(255) DEFAULT NULL,
    country VARCHAR(100) DEFAULT 'Portugal',
    style_preference VARCHAR(100) DEFAULT 'Casual',
    gender VARCHAR(20) DEFAULT 'Outro',
    is_admin BOOLEAN DEFAULT 0,
    is_public BOOLEAN DEFAULT 1,
    theme_preference ENUM('light', 'dark') DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Roupas
CREATE TABLE IF NOT EXISTS clothing_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('casaco', 'camisola', 'calças', 'sapatos', 'acessório') NOT NULL,
    color VARCHAR(30),
    image_path VARCHAR(255),
    usage_count INT DEFAULT 0,
    last_worn DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Looks (Outfits)
CREATE TABLE IF NOT EXISTS outfits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100),
    top_id INT,
    bottom_id INT,
    shoes_id INT,
    coat_id INT DEFAULT NULL,
    is_published BOOLEAN DEFAULT 0,
    description TEXT,
    published_at TIMESTAMP NULL DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    repost_of_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (top_id) REFERENCES clothing_items(id) ON DELETE SET NULL,
    FOREIGN KEY (bottom_id) REFERENCES clothing_items(id) ON DELETE SET NULL,
    FOREIGN KEY (shoes_id) REFERENCES clothing_items(id) ON DELETE SET NULL,
    FOREIGN KEY (coat_id) REFERENCES clothing_items(id) ON DELETE SET NULL,
    FOREIGN KEY (repost_of_id) REFERENCES outfits(id) ON DELETE SET NULL
);

-- Tabela de Gostos (Likes)
CREATE TABLE IF NOT EXISTS outfit_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    outfit_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, outfit_id)
);

-- Tabela de Comentários (Comments)
CREATE TABLE IF NOT EXISTS outfit_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    outfit_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE
);

-- Tabela de Seguir (Follows)
CREATE TABLE IF NOT EXISTS follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    status ENUM('pending', 'accepted') DEFAULT 'accepted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id)
);

-- Tabela de Notificações
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT NOT NULL,
    type ENUM('follow', 'like', 'comment', 'repost', 'follow_request') NOT NULL,
    target_id INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
