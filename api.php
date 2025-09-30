<?php
// api.php

header('Content-Type: application/json');

require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Produto.php';
require_once 'classes/Fornecedor.php';
require_once 'classes/Cesta.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);

// A sessão deve ser iniciada para 'check' funcionar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!$auth->check()) {
    // Apenas login/registro são permitidos sem autenticação.
    if (!isset($_POST['action']) || !in_array($_POST['action'], ['login', 'register'])) {
        echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Ação inválida.'];
$userId = $_SESSION['user_id'] ?? null;

try {
    switch ($action) {
        // --- AUTENTICAÇÃO ---
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            if ($auth->login($username, $password)) {
                $response = ['success' => true, 'redirect' => 'dashboard.php'];
            } else {
                $response = ['success' => false, 'message' => 'Usuário ou senha inválidos.'];
            }
            break;
        case 'register':
            // ... (Lógica de registro como estava)
            break;
            
        // --- CRUD DE FORNECEDOR ---
        case 'get_fornecedores':
            $fornecedor = new Fornecedor($pdo);
            $response = ['success' => true, 'data' => $fornecedor->getAll()];
            break;
        case 'get_single_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $response = ['success' => true, 'data' => $fornecedor->getById((int)$_POST['id'])];
            break;
        case 'add_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $result = $fornecedor->create($_POST['nome'], $_POST['contato']);
            $response = ['success' => $result, 'message' => $result ? 'Fornecedor adicionado.' : 'Falha ao adicionar.'];
            break;
        case 'update_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $result = $fornecedor->update((int)$_POST['id'], $_POST['nome'], $_POST['contato']);
            $response = ['success' => $result, 'message' => $result ? 'Fornecedor atualizado.' : 'Falha ao atualizar.'];
            break;
        case 'delete_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $result = $fornecedor->delete((int)$_POST['id']);
            $response = ['success' => $result, 'message' => $result ? 'Fornecedor excluído.' : 'Falha ao excluir. Verifique se ele não está em uso.' ];
            break;
        
        // --- CRUD DE PRODUTO ---
        case 'get_produtos':
            $produto = new Produto($pdo);
            $response = ['success' => true, 'data' => $produto->getAll()];
            break;
        case 'get_single_produto':
            $produto = new Produto($pdo);
            $response = ['success' => true, 'data' => $produto->getById((int)$_POST['id'])];
            break;
        case 'add_produto':
            $produto = new Produto($pdo);
            $result = $produto->create($_POST['nome'], (float)$_POST['preco'], (int)$_POST['id_fornecedor']);
            $response = ['success' => $result, 'message' => $result ? 'Produto adicionado.' : 'Falha ao adicionar.'];
            break;
        case 'update_produto':
            $produto = new Produto($pdo);
            $result = $produto->update((int)$_POST['id'], $_POST['nome'], (float)$_POST['preco'], (int)$_POST['id_fornecedor']);
            $response = ['success' => $result, 'message' => $result ? 'Produto atualizado.' : 'Falha ao atualizar.'];
            break;
        case 'delete_produto':
            $produto = new Produto($pdo);
            $result = $produto->delete((int)$_POST['id']);
            $response = ['success' => $result, 'message' => $result ? 'Produto excluído.' : 'Falha ao excluir.'];
            break;

        // --- CESTA DE COMPRAS ---
        case 'add_to_cesta':
            $cesta = new Cesta($pdo, $userId);
            $produtos_selecionados = $_POST['produtos'] ?? [];
            if (!empty($produtos_selecionados) && is_array($produtos_selecionados)) {
                $count = $cesta->addProdutos($produtos_selecionados);
                $response = ['success' => true, 'message' => "$count produto(s) adicionado(s) à cesta!"];
            } else {
                $response = ['success' => false, 'message' => 'Selecione pelo menos um produto.'];
            }
            break;
        case 'get_cesta':
            $cesta = new Cesta($pdo, $userId);
            $response = ['success' => true, 'data' => $cesta->getItens()];
            break;
        case 'remove_item': // Unificado: este é o único método para remover
            $cesta = new Cesta($pdo, $userId);
            $result = $cesta->removeItem((int)$_POST['produto_id']);
            $response = ['success' => $result, 'message' => $result ? 'Item removido.' : 'Falha ao remover.'];
            break;
        case 'clear_cart':
            $cesta = new Cesta($pdo, $userId);
            $result = $cesta->clearCart();
            $response = ['success' => $result, 'message' => $result ? 'Cesta esvaziada.' : 'Falha ao esvaziar.'];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Ação desconhecida.'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
}

echo json_encode($response);