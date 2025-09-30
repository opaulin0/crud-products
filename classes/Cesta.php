<?php
// classes/Cesta.php

class Cesta {
    private $pdo;
    private $user_id;

    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }

    private function getCestaId() {
        // Tenta obter a cesta aberta do usuário
        $stmt = $this->pdo->prepare("SELECT id FROM cestas WHERE id_usuario = ? ORDER BY data_criacao DESC LIMIT 1");
        $stmt->execute([$this->user_id]);
        $cesta = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cesta) {
            return $cesta['id'];
        } else {
            // Se não houver, cria uma nova cesta para o usuário
            $stmt = $this->pdo->prepare("INSERT INTO cestas (id_usuario) VALUES (?)");
            $stmt->execute([$this->user_id]);
            return $this->pdo->lastInsertId();
        }
    }

    public function addProdutos($produto_ids) {
        $cesta_id = $this->getCestaId();
        $count = 0;
        
        foreach ($produto_ids as $produto_id) {
            // Insere na tabela pivô (uma unidade por produto, conforme requisito)
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO cestas_produtos (id_cesta, id_produto) VALUES (?, ?)
            ");
            if ($stmt->execute([$cesta_id, $produto_id])) {
                $count++;
            }
        }
        return $count;
    }

    public function getItens() {
        $cesta_id = $this->getCestaId();
        
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.nome, p.preco, f.nome as fornecedor_nome
            FROM cestas_produtos cp
            JOIN produtos p ON cp.id_produto = p.id
            LEFT JOIN fornecedores f ON p.id_fornecedor = f.id
            WHERE cp.id_cesta = ?
        ");
        $stmt->execute([$cesta_id]);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = 0;
        foreach ($produtos as $p) {
            $total += (float)$p['preco']; 
        }
        
        return [
            'id' => $cesta_id,
            'produtos' => $produtos,
            'valor_total' => number_format($total, 2, ',', '.'),
            'total_itens' => count($produtos)
        ];
    }
    
    public function removeProduto($produto_id) {
        $cesta_id = $this->getCestaId();
        
        $stmt = $this->pdo->prepare("DELETE FROM cestas_produtos WHERE id_cesta = ? AND id_produto = ? LIMIT 1");
        return $stmt->execute([$cesta_id, $produto_id]);
    }
    
    public function esvaziarCesta() {
        $cesta_id = $this->getCestaId();
        
        $stmt = $this->pdo->prepare("DELETE FROM cestas_produtos WHERE id_cesta = ?");
        return $stmt->execute([$cesta_id]);
    }
    // classes/Cesta.php

public function removeItem($produto_id) {
    $cesta_id = $this->getCestaId();
    
    // Assume que a chave primária composta (id_cesta, id_produto) garante a unicidade
    $stmt = $this->pdo->prepare("
        DELETE FROM cestas_produtos
        WHERE id_cesta = ? AND id_produto = ?
    ");
    // O método execute retorna true em caso de sucesso
    return $stmt->execute([$cesta_id, $produto_id]);
}

// **EXTRA:** Adicione este método para o botão "Esvaziar Cesta"
public function clearCart() {
    $cesta_id = $this->getCestaId();
    $stmt = $this->pdo->prepare("DELETE FROM cestas_produtos WHERE id_cesta = ?");
    return $stmt->execute([$cesta_id]);
}
}