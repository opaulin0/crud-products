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

if (!$auth->check()) {
    // Apenas login/registro são permitidos sem autenticação.
    if (!isset($_POST['action']) || !in_array($_POST['action'], ['login', 'register'])) {
        echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Ação inválida.'];

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
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            if (empty($username) || empty($password) || strlen($password) < 6) {
                 $response = ['success' => false, 'message' => 'Dados inválidos. Mínimo 6 caracteres para senha.'];
            } elseif ($auth->register($username, $password)) {
                $response = ['success' => true, 'message' => 'Usuário registrado com sucesso. Faça o login.'];
            } else {
                $response = ['success' => false, 'message' => 'Falha ao registrar. Usuário já existe?'];
            }
            break;
            
        // --- CRUD DE FORNECEDOR ---
        case 'get_fornecedores':
            $fornecedor = new Fornecedor($pdo);
            $response = ['success' => true, 'data' => $fornecedor->getAll()];
            break;
        case 'add_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $result = $fornecedor->create($_POST['nome'], $_POST['contato']);
            $response = ['success' => $result, 'message' => $result ? 'Fornecedor adicionado.' : 'Falha ao adicionar.'];
            break;
        // ... (Implemente update e delete de Fornecedor de forma similar)
        
        // --- CRUD DE PRODUTO ---
        case 'get_produtos':
            $produto = new Produto($pdo);
            $response = ['success' => true, 'data' => $produto->getAll()];
            break;
        case 'add_produto':
            $produto = new Produto($pdo);
            $result = $produto->create($_POST['nome'], (float)$_POST['preco'], (int)$_POST['id_fornecedor']);
            $response = ['success' => $result, 'message' => $result ? 'Produto adicionado.' : 'Falha ao adicionar.'];
            break;
        // ... (Implemente update e delete de Produto de forma similar)

        // --- CESTA DE COMPRAS ---
        case 'add_to_cesta':
            $cesta = new Cesta($pdo, $_SESSION['user_id']);
            $produtos_selecionados = $_POST['produtos'] ?? [];
            if (!empty($produtos_selecionados) && is_array($produtos_selecionados)) {
                $count = $cesta->addProdutos($produtos_selecionados);
                $response = ['success' => true, 'message' => "$count produto(s) adicionado(s) à cesta!"];
            } else {
                $response = ['success' => false, 'message' => 'Selecione pelo menos um produto.'];
            }
            break;
        case 'get_cesta':
            $cesta = new Cesta($pdo, $_SESSION['user_id']);
            $response = ['success' => true, 'data' => $cesta->getItens()];
            break;
        case 'remove_cesta_item':
            $cesta = new Cesta($pdo, $_SESSION['user_id']);
            $result = $cesta->removeProduto((int)$_POST['produto_id']);
            $response = ['success' => $result, 'message' => $result ? 'Item removido.' : 'Falha ao remover.'];
            break;
            // api.php - Dentro do switch ($action)

case 'remove_item':
    if (!isset($_POST['produto_id'])) {
        $response = ['success' => false, 'message' => 'ID do produto não fornecido.'];
        break;
    }
    
    $cesta = new Cesta($pdo, $_SESSION['user_id']);
    $produto_id = (int)$_POST['produto_id'];

    if ($cesta->removeItem($produto_id)) {
        $response = ['success' => true, 'message' => 'Produto removido da cesta com sucesso.'];
    } else {
        $response = ['success' => false, 'message' => 'Falha ao remover produto da cesta.'];
    }
    break;

case 'clear_cart':
    $cesta = new Cesta($pdo, $_SESSION['user_id']);
    if ($cesta->clearCart()) {
        $response = ['success' => true, 'message' => 'Cesta esvaziada com sucesso!'];
    } else {
        $response = ['success' => false, 'message' => 'Falha ao esvaziar a cesta.'];
    }
    break;
        default:
            $response = ['success' => false, 'message' => 'Ação desconhecida.'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
}

echo json_encode($response);