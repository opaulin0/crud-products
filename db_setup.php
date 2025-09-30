<?php
// db_setup.php

$host = 'localhost';
$db   = 'gestao_produtos';
$user = 'root';
$pass = '';

try {
    // 1. Conecta sem especificar o BD para poder criá-lo
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Cria o banco de dados
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // 3. Conecta ao novo BD
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Cria as tabelas
    $sql = "
        CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) UNIQUE NOT NULL,
            `senha` CHAR(64) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `fornecedores` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `contato` VARCHAR(255)
        );

        CREATE TABLE IF NOT EXISTS `produtos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `preco` DECIMAL(10, 2) NOT NULL,
            `id_fornecedor` INT,
            FOREIGN KEY (`id_fornecedor`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS `cestas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `id_usuario` INT NOT NULL,
            `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS `cestas_produtos` (
            `id_cesta` INT,
            `id_produto` INT,
            PRIMARY KEY (`id_cesta`, `id_produto`),
            FOREIGN KEY (`id_cesta`) REFERENCES `cestas`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`id_produto`) REFERENCES `produtos`(`id`) ON DELETE CASCADE
        );
    ";
    
    $pdo->exec($sql);

    // 5. Cadastra um usuário padrão para testes (senha: '123456')
    $hashed_password = hash('sha256', '123456');
    $pdo->exec("INSERT INTO usuarios (username, senha) VALUES ('admin', '$hashed_password') ON DUPLICATE KEY UPDATE senha='$hashed_password'");

    echo "Sucesso: Banco de dados e tabelas criados. Usuário padrão: admin/123456.";

} catch (PDOException $e) {
    die("Erro no Setup: " . $e->getMessage());
}
?>