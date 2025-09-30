<?php
// api.php (TOPO CORRIGIDO)

// Tente remover o header temporariamente para ver se o erro de output some
// header('Content-Type: application/json');

require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Produto.php';
require_once 'classes/Fornecedor.php';
require_once 'classes/Cesta.php';

$pdo = Database::getInstance();
// A linha abaixo inicia a sessão internamente
$auth = new Auth($pdo); 

// Remova o bloco if (session_status()...) que estava aqui!

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Ação inválida.'];
$userId = $_SESSION['user_id'] ?? null; // Aqui o userId agora está seguro

if (!$auth->check() && !in_array($action, ['login', 'register'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

try {
    // ... (restante do switch case) ...
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
            // api.php - Adicione no bloco switch($action)

        case 'get_fornecedor_one':
            $fornecedor = new Fornecedor($pdo);
            $data = $fornecedor->getOne((int)$_POST['id']);
            if ($data) {
                $response = ['success' => true, 'data' => $data];
            } else {
                $response = ['success' => false, 'message' => 'Fornecedor não encontrado.'];
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
        case 'update_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $result = $fornecedor->update((int)$_POST['id'], $_POST['nome'], $_POST['contato']);
            $response = ['success' => $result, 'message' => $result ? 'Fornecedor atualizado.' : 'Falha ao atualizar.'];
            break;
        case 'delete_fornecedor':
            $fornecedor = new Fornecedor($pdo);
            $result = $fornecedor->delete((int)$_POST['id']);
            $response = ['success' => $result, 'message' => $result ? 'Fornecedor excluído.' : 'Falha ao excluir.' ];
            break;
        
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
        case 'remove_item':
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