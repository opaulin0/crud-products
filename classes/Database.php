<?php
// classes/Database.php

class Database {
    private static $host = 'localhost';
    private static $db   = 'gestao_produtos';
    private static $user = 'root';
    private static $pass = '';
    private static $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance() {
        if (!self::$instance) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8mb4",
                    self::$user,
                    self::$pass
                );
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Erro de Conexão com o BD: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}