<?php
// db_connect.php
// Estabelece a ligação à base de dados MySQL usando PDO

$host = 'localhost';
$dbname = 'lookup_db2';
$username = 'root'; // Alterar conforme configuração do servidor
$password = '';     // Alterar conforme configuração do servidor

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Define o modo de erro para exceções
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Ligação efetuada com sucesso!"; 

} catch (PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}
?>