<?php
// classes/Produto.php

class Produto {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($nome, $preco, $id_fornecedor) {
        $stmt = $this->pdo->prepare("INSERT INTO produtos (nome, preco, id_fornecedor) VALUES (?, ?, ?)");
        return $stmt->execute([$nome, $preco, $id_fornecedor]);
    }

    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT p.*, f.nome as fornecedor_nome 
            FROM produtos p
            LEFT JOIN fornecedores f ON p.id_fornecedor = f.id 
            ORDER BY p.nome ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // classes/Produto.php

// ... (código existente)

public function update($id, $nome, $preco, $id_fornecedor) {
    $stmt = $this->pdo->prepare("UPDATE produtos SET nome = ?, preco = ?, id_fornecedor = ? WHERE id = ?");
    return $stmt->execute([$nome, $preco, $id_fornecedor, $id]);
}

public function delete($id) {
    // A integridade referencial na cesta é cuidada pelo CASCADE
    $stmt = $this->pdo->prepare("DELETE FROM produtos WHERE id = ?");
    return $stmt->execute([$id]);
}
}