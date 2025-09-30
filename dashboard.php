<?php
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$auth = new Auth(Database::getInstance());

// Lógica de logout é tratada aqui, no servidor, antes da página carregar.
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit; 
}

// Proteção da página: verifica se o usuário está logado.
if (!$auth->check()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestão de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .nav-link.active { 
            font-weight: bold;
            color: #fff !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Gestão de Produtos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-area="crud">Cadastros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-area="cesta-selecao">Seleção de Produtos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-area="cesta-view">Minha Cesta</a>
                </li>
            </ul>
            <span class="navbar-text me-3">
                Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="?logout=true" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div id="contentArea">
        <h2 class="text-center text-muted">Carregando...</h2>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> 

<script>
// Constantes e variáveis globais
const API_URL = 'api.php';
let fornecedoresCache = []; // Cache para evitar múltiplas chamadas

// ===============================================
// FUNÇÕES DE RENDERIZAÇÃO DE HTML (Templates)
// ===============================================

function renderCrudArea() {
    return `
        <h2>Gerenciamento de Cadastros</h2>
        <ul class="nav nav-tabs" id="crudTabs">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-produtos" type="button">Produtos</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fornecedores" type="button">Fornecedores</button></li>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-produtos">
                ${renderProdutoForm()}
                <hr>
                <h3>Produtos</h3>
                <div id="produtosList"></div>
            </div>
            <div class="tab-pane fade" id="tab-fornecedores">
                ${renderFornecedorForm()}
                <hr>
                <h3>Fornecedores</h3>
                <div id="fornecedoresList"></div>
            </div>
        </div>
    `;
}

function renderFornecedorForm() {
    return `
        <h4>Cadastrar Fornecedor</h4>
        <form id="formAddFornecedor" class="row g-3 mb-4">
            <div class="col-md-5"><input type="text" class="form-control" name="nome" placeholder="Nome do Fornecedor" required></div>
            <div class="col-md-5"><input type="text" class="form-control" name="contato" placeholder="Contato/Telefone/Email"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button></div>
        </form>
    `;
}

function renderProdutoForm() {
    return `
        <h4>Cadastrar Produto</h4>
        <form id="formAddProduto" class="row g-3 mb-4">
            <div class="col-md-3"><input type="text" class="form-control" name="nome" placeholder="Nome do Produto" required></div>
            <div class="col-md-3"><input type="number" class="form-control" name="preco" placeholder="Preço" step="0.01" required></div>
            <div class="col-md-4">
                <select class="form-select" name="id_fornecedor" id="selectFornecedorProduto" required>
                    <option value="">Selecione um Fornecedor</option>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button></div>
        </form>
    `;
}

function renderSelecaoArea() {
    return `
        <h2><i class="fas fa-search-plus"></i> Seleção de Produtos</h2>
        <form id="formAddCesta">
            <p class="lead">Selecione os produtos que deseja adicionar à sua cesta:</p>
            <div id="produtosSelecaoList" class="table-responsive">
                <p class="text-muted">Carregando produtos para seleção...</p>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-cart-plus"></i> Adicionar Selecionados à Cesta</button>
        </form>
    `;
}

function renderCestaViewArea() {
    return `
        <h2>Minha Cesta de Compras</h2>
        <div id="cestaResumo" class="alert alert-info"></div>
        <div id="cestaItens">Carregando itens da cesta...</div>
        <button id="esvaziarCestaBtn" class="btn btn-danger mt-3" style="display: none;"><i class="fas fa-trash"></i> Esvaziar Cesta</button>
    `;
}


// ===============================================
// FUNÇÕES DE LÓGICA E AJAX
// ===============================================

/**
 * Função genérica para requisições CRUD.
 * @param {string} action - A ação para a API (ex: 'add_produto').
 * @param {string} data - Os dados serializados do formulário.
 * @param {function} successCallback - Função a ser chamada em caso de sucesso.
 */
function sendCrudRequest(action, data, successCallback) {
    $.ajax({
        url: API_URL,
        type: 'POST',
        data: `action=${action}&${data}`,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                if (successCallback) successCallback();
                // Limpa o formulário correspondente
                const formId = action.startsWith('add_') ? `#formAdd${action.split('_')[1].charAt(0).toUpperCase() + action.split('_')[1].slice(1)}` : '';
                if (formId && $(formId).length) $(formId).trigger('reset');
            } else {
                alert('Erro na operação: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('Erro de comunicação com o servidor. Verifique o console (F12) para detalhes.');
            console.error("AJAX Error:", xhr.status, xhr.responseText);
        }
    });
}

function loadFornecedores() {
    const list = $('#fornecedoresList');
    list.html('<p class="text-muted">Carregando...</p>');

    $.post(API_URL, { action: 'get_fornecedores' }, function(response) {
        if (!response.success) {
            list.html(`<div class="alert alert-danger">Erro ao carregar dados: ${response.message}</div>`);
            return;
        }

        fornecedoresCache = response.data;
        const select = $('#selectFornecedorProduto');
        select.empty().append('<option value="">Selecione um Fornecedor</option>');

        if (fornecedoresCache.length === 0) {
            list.html('<p class="text-info">Nenhum fornecedor cadastrado.</p>');
            return;
        }

        let tableHtml = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Contato</th></tr></thead><tbody>';
        fornecedoresCache.forEach(f => {
            tableHtml += `<tr><td>${f.id}</td><td>${f.nome}</td><td>${f.contato}</td></tr>`;
            select.append(`<option value="${f.id}">${f.nome}</option>`);
        });
        tableHtml += '</tbody></table>';
        list.html(tableHtml);

    }, 'json');
}

/**
 * Função genérica que busca produtos na API e passa os dados para um callback.
 * @param {function} renderCallback - Função que recebe os dados e renderiza o HTML.
 * @param {jQuery} targetElement - O elemento jQuery onde o resultado será exibido.
 */
function fetchProdutos(renderCallback, targetElement) {
    targetElement.html('<p class="text-muted">Carregando produtos...</p>');
    $.post(API_URL, { action: 'get_produtos' }, function(response) {
        if (response.success) {
            renderCallback(response.data, targetElement);
        } else {
            targetElement.html(`<div class="alert alert-danger">Erro ao carregar produtos: ${response.message}</div>`);
        }
    }, 'json');
}

function loadProdutos() {
    fetchProdutos((data, element) => {
        if (data.length === 0) {
            element.html('<p class="text-info">Nenhum produto cadastrado.</p>');
            return;
        }
        let html = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Fornecedor</th></tr></thead><tbody>';
        data.forEach(p => {
            html += `<tr>
                <td>${p.id}</td>
                <td>${p.nome}</td>
                <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                <td>${p.fornecedor_nome || 'N/A'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        element.html(html);
    }, $('#produtosList'));
}

function loadSelecaoProdutos() {
    fetchProdutos((data, element) => {
        if (data.length === 0) {
            element.html('<p class="alert alert-warning">Nenhum produto disponível para seleção.</p>');
            return;
        }
        let html = '<table class="table table-hover"><thead><tr><th>Nome</th><th>Preço</th><th>Fornecedor</th><th>Selecionar</th></tr></thead><tbody>';
        data.forEach(p => {
            html += `<tr>
                <td>${p.nome}</td>
                <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                <td>${p.fornecedor_nome || 'N/A'}</td>
                <td><input type="checkbox" name="produtos[]" value="${p.id}" class="form-check-input"></td>
            </tr>`;
        });
        html += '</tbody></table>';
        element.html(html);
    }, $('#produtosSelecaoList'));
}


function loadCesta() {
    $.post(API_URL, { action: 'get_cesta' }, function(response) {
        if (!response.success) {
            $('#cestaItens').html(`<p class="alert alert-danger">Erro ao carregar cesta: ${response.message}</p>`);
            return;
        }

        const cesta = response.data;
        $('#cestaResumo').html(`
            <i class="fas fa-info-circle"></i>
            Total de Itens: <strong>${cesta.total_itens}</strong> | 
            Valor Total: <strong>R$ ${cesta.valor_total}</strong>
        `);

        if (cesta.total_itens === 0) {
            $('#cestaItens').html('<p class="alert alert-info">Sua cesta está vazia.</p>');
            $('#esvaziarCestaBtn').hide();
            return;
        }

        let tableHtml = '<table class="table table-bordered table-sm"><thead><tr><th>Nome</th><th>Fornecedor</th><th>Preço</th><th>Ação</th></tr></thead><tbody>';
        cesta.produtos.forEach(p => {
            tableHtml += `<tr>
                <td>${p.nome}</td>
                <td>${p.fornecedor_nome || 'N/A'}</td>
                <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                <td><button class="btn btn-sm btn-danger remove-item" data-id="${p.id}"><i class="fas fa-times"></i></button></td>
            </tr>`;
        });
        tableHtml += '</tbody></table>';
        $('#cestaItens').html(tableHtml);
        $('#esvaziarCestaBtn').show();
    }, 'json');
}


// ===============================================
// INICIALIZAÇÃO E ROTEAMENTO
// ===============================================

function initCrudArea() {
    loadFornecedores();
    loadProdutos();
    $('#crudTabs button').on('shown.bs.tab', function(e) {
        if ($(this).attr('data-bs-target') === '#tab-fornecedores') {
            loadFornecedores();
        }
    });
}

function loadArea(area) {
    const content = $('#contentArea');
    content.html(`<h2>Carregando área de ${area}...</h2>`);

    // Atualiza o menu
    $('.nav-link').removeClass('active');
    $(`.nav-link[data-area="${area}"]`).addClass('active');

    switch (area) {
        case 'crud':
            content.html(renderCrudArea());
            initCrudArea();
            break;
        case 'cesta-selecao':
            content.html(renderSelecaoArea());
            loadSelecaoProdutos();
            break;
        case 'cesta-view':
            content.html(renderCestaViewArea());
            loadCesta();
            break;
        default:
            content.html('<h2 class="text-center text-muted">Selecione uma opção no menu.</h2>');
    }
}


// ===============================================
// PONTO DE ENTRADA E EVENTOS GLOBAIS
// ===============================================

$(document).ready(function() {
    
    // --- Eventos do Menu ---
    $('.nav-link').click(function(e) {
        e.preventDefault();
        const area = $(this).data('area');
        loadArea(area);
    });

    // --- Eventos Delegados para Conteúdo Dinâmico ---
    const contentArea = $('#contentArea');

    // CRUD
    contentArea.on('submit', '#formAddFornecedor', function(e) {
        e.preventDefault();
        sendCrudRequest('add_fornecedor', $(this).serialize(), loadFornecedores);
    });

    contentArea.on('submit', '#formAddProduto', function(e) {
        e.preventDefault();
        sendCrudRequest('add_produto', $(this).serialize(), loadProdutos);
    });
    
    // Cesta
    contentArea.on('submit', '#formAddCesta', function(e) {
        e.preventDefault();
        const selectedProducts = $(this).find('input[name="produtos[]"]:checked').map(function() { return $(this).val(); }).get();
        if (selectedProducts.length === 0) {
            alert('Selecione pelo menos um produto.');
            return;
        }
        $.post(API_URL, { action: 'add_to_cesta', produtos: selectedProducts }, (response) => {
            alert(response.message);
            if(response.success) $(this).trigger('reset');
        }, 'json');
    });

    contentArea.on('click', '.remove-item', function() {
        if (!confirm('Confirmar remoção deste item?')) return;
        const produtoId = $(this).data('id');
        $.post(API_URL, { action: 'remove_item', produto_id: produtoId }, (response) => {
            alert(response.message);
            if (response.success) loadCesta();
        }, 'json');
    });

    contentArea.on('click', '#esvaziarCestaBtn', function() {
        if (!confirm('Esvaziar toda a cesta?')) return;
        $.post(API_URL, { action: 'clear_cart' }, (response) => {
            alert(response.message);
            if (response.success) loadCesta();
        }, 'json');
    });

    // --- Carga Inicial ---
    loadArea('crud');
});
</script>

</body>
</html>