<?php
// classes/Auth.php

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT id, senha FROM usuarios WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Comparação da senha usando SHA256
            if (hash('sha256', $password) === $user['senha']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                return true;
            }
        }
        return false;
    }
    
    public function register($username, $password) {
        // Verifica se o usuário já existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            return false; // Usuário já existe
        }

        // Hash da senha com SHA256 (64 caracteres)
        $hashed_password = hash('sha256', $password);
        
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (username, senha) VALUES (?, ?)");
        return $stmt->execute([$username, $hashed_password]);
    }

    public function check() {
        return isset($_SESSION['user_id']);
    }

    public function logout() {
        session_unset();
        session_destroy();
    }
}
