<?php
// classes/Fornecedor.php

class Fornecedor {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($nome, $contato) {
        $stmt = $this->pdo->prepare("INSERT INTO fornecedores (nome, contato) VALUES (?, ?)");
        return $stmt->execute([$nome, $contato]);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM fornecedores ORDER BY nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function update($id, $nome, $contato) {
        $stmt = $this->pdo->prepare("UPDATE fornecedores SET nome = ?, contato = ? WHERE id = ?");
        return $stmt->execute([$nome, $contato, $id]);
    }
    
    public function delete($id) {
        // O BD cuida da integridade referencial, definindo id_fornecedor em produtos como NULL
        $stmt = $this->pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        return $stmt->execute([$id]);
    }
}