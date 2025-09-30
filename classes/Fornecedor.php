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
    
   // classes/Fornecedor.php

// ... (código existente)

public function update($id, $nome, $contato) {
    $stmt = $this->pdo->prepare("UPDATE fornecedores SET nome = ?, contato = ? WHERE id = ?");
    return $stmt->execute([$nome, $contato, $id]);
}

public function delete($id) {
    // O banco de dados lida com a chave estrangeira em produtos (SET NULL)
    $stmt = $this->pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
    return $stmt->execute([$id]);
}

// classes/Fornecedor.php

// ... (métodos existentes)

public function getOne($id) {
    $stmt = $this->pdo->prepare("SELECT id, nome, contato FROM fornecedores WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ... (restante da classe)
}