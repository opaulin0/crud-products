<?php
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

// A sessão deve ser iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth(Database::getInstance());

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit; 
}

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
                <li class="nav-item"><a class="nav-link" href="#" data-area="crud">Cadastros</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-area="cesta-selecao">Seleção de Produtos</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-area="cesta-view">Minha Cesta</a></li>
            </ul>
            <span class="navbar-text me-3">Olá, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="?logout=true" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div id="contentArea"><h2 class="text-center text-muted">Carregando...</h2></div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar Produto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="formEditProduto">
            <input type="hidden" id="editProdutoId" name="id">
            <div class="mb-3"><label for="editNomeProduto" class="form-label">Nome</label><input type="text" class="form-control" id="editNomeProduto" name="nome" required></div>
            <div class="mb-3"><label for="editPreco" class="form-label">Preço</label><input type="number" class="form-control" id="editPreco" name="preco" step="0.01" required></div>
            <div class="mb-3"><label for="editSelectFornecedorProduto" class="form-label">Fornecedor</label><select class="form-select" id="editSelectFornecedorProduto" name="id_fornecedor" required></select></div>
        </form>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btnSalvarEdicaoProduto">Salvar</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="editFornecedorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar Fornecedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="formEditFornecedor">
            <input type="hidden" id="editFornecedorId" name="id">
            <div class="mb-3"><label for="editNomeFornecedor" class="form-label">Nome</label><input type="text" class="form-control" id="editNomeFornecedor" name="nome" required></div>
            <div class="mb-3"><label for="editContatoFornecedor" class="form-label">Contato</label><input type="text" class="form-control" id="editContatoFornecedor" name="contato"></div>
        </form>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btnSalvarEdicaoFornecedor">Salvar</button></div>
    </div>
  </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> 
<script>
// Constantes e variáveis globais
const API_URL = 'api.php';
let fornecedoresCache = [];

// --- Funções de Renderização ---
function renderCrudArea() { /* ... (igual ao anterior) ... */ }
function renderFornecedorForm() { /* ... (igual ao anterior) ... */ }
function renderProdutoForm() { /* ... (igual ao anterior) ... */ }
function renderSelecaoArea() { /* ... (igual ao anterior) ... */ }
function renderCestaViewArea() { /* ... (igual ao anterior) ... */ }

// --- Funções de Lógica e AJAX ---
function sendCrudRequest(action, data, successCallback) { /* ... (igual ao anterior) ... */ }

function loadFornecedores() {
    const list = $('#fornecedoresList');
    list.html('<p class="text-muted">Carregando...</p>');

    $.post(API_URL, { action: 'get_fornecedores' }, function(response) {
        if (!response.success) {
            list.html(`<div class="alert alert-danger">${response.message}</div>`);
            return;
        }
        fornecedoresCache = response.data;
        const selectProduto = $('#selectFornecedorProduto');
        selectProduto.empty().append('<option value="">Selecione</option>');
        if (fornecedoresCache.length === 0) {
            list.html('<p class="text-info">Nenhum fornecedor cadastrado.</p>');
            return;
        }

        let tableHtml = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Contato</th><th>Ações</th></tr></thead><tbody>';
        fornecedoresCache.forEach(f => {
            tableHtml += `<tr>
                <td>${f.id}</td>
                <td>${f.nome}</td>
                <td>${f.contato}</td>
                <td>
                    <button class="btn btn-info btn-sm edit-fornecedor" data-id="${f.id}" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm delete-fornecedor" data-id="${f.id}" title="Excluir"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
            selectProduto.append(`<option value="${f.id}">${f.nome}</option>`);
        });
        tableHtml += '</tbody></table>';
        list.html(tableHtml);
    }, 'json');
}

function fetchProdutos(renderCallback, targetElement) { /* ... (igual ao anterior) ... */ }

function loadProdutos() {
    fetchProdutos((data, element) => {
        if (data.length === 0) {
            element.html('<p class="text-info">Nenhum produto cadastrado.</p>');
            return;
        }
        let html = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Fornecedor</th><th>Ações</th></tr></thead><tbody>';
        data.forEach(p => {
            html += `<tr>
                <td>${p.id}</td>
                <td>${p.nome}</td>
                <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                <td>${p.fornecedor_nome || 'N/A'}</td>
                <td>
                    <button class="btn btn-info btn-sm edit-produto" data-id="${p.id}" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm delete-produto" data-id="${p.id}" title="Excluir"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        element.html(html);
    }, $('#produtosList'));
}

function loadSelecaoProdutos() { /* ... (igual ao anterior) ... */ }
function loadCesta() { /* ... (igual ao anterior) ... */ }

// --- Inicialização e Roteamento ---
function initCrudArea() { /* ... (igual ao anterior) ... */ }
function loadArea(area) { /* ... (igual ao anterior) ... */ }

// --- Ponto de Entrada e Eventos Globais ---
$(document).ready(function() {
    const contentArea = $('#contentArea');
    const editProductModal = new bootstrap.Modal($('#editProductModal'));
    const editFornecedorModal = new bootstrap.Modal($('#editFornecedorModal'));

    // Navegação
    $('.nav-link').click(function(e) { e.preventDefault(); loadArea($(this).data('area')); });

    // --- Eventos CRUD ---
    contentArea.on('submit', '#formAddFornecedor', function(e) { e.preventDefault(); sendCrudRequest('add_fornecedor', $(this).serialize(), loadFornecedores); });
    contentArea.on('submit', '#formAddProduto', function(e) { e.preventDefault(); sendCrudRequest('add_produto', $(this).serialize(), loadProdutos); });
    
    // Deletar
    contentArea.on('click', '.delete-produto', function() { if (confirm('Excluir este produto?')) { $.post(API_URL, { action: 'delete_produto', id: $(this).data('id') }, (res) => { alert(res.message); if(res.success) loadProdutos(); }, 'json'); } });
    contentArea.on('click', '.delete-fornecedor', function() { if (confirm('Excluir este fornecedor?')) { $.post(API_URL, { action: 'delete_fornecedor', id: $(this).data('id') }, (res) => { alert(res.message); if(res.success) loadFornecedores(); }, 'json'); } });

    // Editar Produto
    contentArea.on('click', '.edit-produto', function() {
        const id = $(this).data('id');
        $.post(API_URL, { action: 'get_single_produto', id: id }, (res) => {
            if(res.success) {
                const p = res.data;
                $('#editProdutoId').val(p.id);
                $('#editNomeProduto').val(p.nome);
                $('#editPreco').val(p.preco);
                const select = $('#editSelectFornecedorProduto').empty();
                fornecedoresCache.forEach(f => select.append(`<option value="${f.id}">${f.nome}</option>`));
                select.val(p.id_fornecedor);
                editProductModal.show();
            } else { alert(res.message); }
        }, 'json');
    });
    $('#btnSalvarEdicaoProduto').on('click', function() { $.post(API_URL, 'action=update_produto&' + $('#formEditProduto').serialize(), (res) => { alert(res.message); if(res.success) { editProductModal.hide(); loadProdutos(); } }, 'json'); });

    // Editar Fornecedor
    contentArea.on('click', '.edit-fornecedor', function() {
        const id = $(this).data('id');
        $.post(API_URL, { action: 'get_single_fornecedor', id: id }, (res) => {
            if(res.success) {
                const f = res.data;
                $('#editFornecedorId').val(f.id);
                $('#editNomeFornecedor').val(f.nome);
                $('#editContatoFornecedor').val(f.contato);
                editFornecedorModal.show();
            } else { alert(res.message); }
        }, 'json');
    });
    $('#btnSalvarEdicaoFornecedor').on('click', function() { $.post(API_URL, 'action=update_fornecedor&' + $('#formEditFornecedor').serialize(), (res) => { alert(res.message); if(res.success) { editFornecedorModal.hide(); loadFornecedores(); } }, 'json'); });

    // --- Eventos da Cesta ---
    contentArea.on('submit', '#formAddCesta', function(e) { /* ... (igual ao anterior) ... */ });
    contentArea.on('click', '.remove-item', function() { if (confirm('Remover este item?')) { $.post(API_URL, { action: 'remove_item', produto_id: $(this).data('id') }, (res) => { alert(res.message); if (res.success) loadCesta(); }, 'json'); } });
    contentArea.on('click', '#esvaziarCestaBtn', function() { if (confirm('Esvaziar toda a cesta?')) { $.post(API_URL, { action: 'clear_cart' }, (res) => { alert(res.message); if (res.success) loadCesta(); }, 'json'); } });
    
    // Carga Inicial
    loadArea('crud');
});
</script>

<script>
function renderCrudArea() {
    return `<h2>Gerenciamento de Cadastros</h2><ul class="nav nav-tabs" id="crudTabs"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-produtos">Produtos</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fornecedores">Fornecedores</button></li></ul><div class="tab-content pt-3"><div class="tab-pane fade show active" id="tab-produtos">${renderProdutoForm()}<hr><h3>Produtos</h3><div id="produtosList"></div></div><div class="tab-pane fade" id="tab-fornecedores">${renderFornecedorForm()}<hr><h3>Fornecedores</h3><div id="fornecedoresList"></div></div></div>`;
}
function renderFornecedorForm() {
    return `<h4>Cadastrar Fornecedor</h4><form id="formAddFornecedor" class="row g-3 mb-4"><div class="col-md-5"><input type="text" class="form-control" name="nome" placeholder="Nome do Fornecedor" required></div><div class="col-md-5"><input type="text" class="form-control" name="contato" placeholder="Contato"></div><div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button></div></form>`;
}
function renderProdutoForm() {
    return `<h4>Cadastrar Produto</h4><form id="formAddProduto" class="row g-3 mb-4"><div class="col-md-3"><input type="text" class="form-control" name="nome" placeholder="Nome do Produto" required></div><div class="col-md-3"><input type="number" class="form-control" name="preco" placeholder="Preço" step="0.01" required></div><div class="col-md-4"><select class="form-select" name="id_fornecedor" id="selectFornecedorProduto" required><option value="">Selecione um Fornecedor</option></select></div><div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button></div></form>`;
}
function renderSelecaoArea() {
    return `<h2><i class="fas fa-search-plus"></i> Seleção de Produtos</h2><form id="formAddCesta"><p class="lead">Selecione os produtos para adicionar à cesta:</p><div id="produtosSelecaoList" class="table-responsive"><p class="text-muted">Carregando...</p></div><button type="submit" class="btn btn-primary mt-3"><i class="fas fa-cart-plus"></i> Adicionar à Cesta</button></form>`;
}
function renderCestaViewArea() {
    return `<h2>Minha Cesta de Compras</h2><div id="cestaResumo" class="alert alert-info"></div><div id="cestaItens">Carregando...</div><button id="esvaziarCestaBtn" class="btn btn-danger mt-3" style="display: none;"><i class="fas fa-trash"></i> Esvaziar Cesta</button>`;
}
function sendCrudRequest(action, data, successCallback) {
    $.ajax({url: API_URL, type: 'POST', data: `action=${action}&${data}`, dataType: 'json',
        success: function(response) {
            alert(response.message);
            if (response.success) {
                if (successCallback) successCallback();
                if (action.startsWith('add_')) {
                    const formId = `#formAdd${action.split('_')[1].charAt(0).toUpperCase() + action.split('_')[1].slice(1)}`;
                    if ($(formId).length) $(formId).trigger('reset');
                }
            }
        },
        error: function(xhr) { alert('Erro de comunicação.'); console.error("AJAX Error:", xhr.status, xhr.responseText); }
    });
}
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
    content.html(`<h2>Carregando ${area}...</h2>`);
    $('.nav-link').removeClass('active');
    $(`.nav-link[data-area="${area}"]`).addClass('active');
    switch (area) {
        case 'crud': content.html(renderCrudArea()); initCrudArea(); break;
        case 'cesta-selecao': content.html(renderSelecaoArea()); loadSelecaoProdutos(); break;
        case 'cesta-view': content.html(renderCestaViewArea()); loadCesta(); break;
        default: content.html('<h2 class="text-center text-muted">Selecione uma opção no menu.</h2>');
    }
}
</script>
</body>
</html>