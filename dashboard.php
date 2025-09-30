<?php
// dashboard.php - CÓDIGO FINAL

require_once 'classes/Database.php';
require_once 'classes/Auth.php';

// A classe Auth.php inicia a sessão internamente.
$auth = new Auth(Database::getInstance());

// 1. TRATAMENTO DE LOGOUT
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit; 
}

// 2. VERIFICAÇÃO DE AUTENTICAÇÃO
if (!$auth->check()) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestão de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .nav-link.active { font-weight: bold; color: #fff !important; }
        .table-acoes { width: 1%; white-space: nowrap; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Gestão de Produtos</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active-nav active" href="#" data-area="crud"><i class="fas fa-boxes"></i> **Cadastros**</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active-nav" href="#" data-area="cesta-selecao"><i class="fas fa-search-plus"></i> Seleção de Produtos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active-nav" href="#" data-area="cesta-view"><i class="fas fa-shopping-cart"></i> **Minha Cesta**</a>
                </li>
            </ul>
            <span class="navbar-text me-3 text-white">
                Olá, **<?php echo htmlspecialchars($username); ?>**
            </span>
            <a href="?logout=true" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div id="contentArea">
        <h2 class="text-center text-muted">Carregando...</h2>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="editModalLabel">Editar Registro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditItem">
        <div class="modal-body">
          <input type="hidden" id="editId" name="id">
          <input type="hidden" id="editType" name="type">
          
          <div class="mb-3">
            <label for="editNome" class="form-label">Nome</label>
            <input type="text" class="form-control" id="editNome" name="nome" required>
          </div>
          <div id="editContatoGroup" class="mb-3">
            <label for="editContato" class="form-label">Contato</label>
            <input type="text" class="form-control" id="editContato" name="contato">
          </div>
          <div id="editPrecoGroup" class="mb-3" style="display: none;">
            <label for="editPreco" class="form-label">Preço</label>
            <input type="number" class="form-control" id="editPreco" name="preco" step="0.01">
          </div>
          </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> 
<script>
    const API_URL = 'api.php';
    let fornecedoresCache = []; 

    // --- FUNÇÕES DE RENDERIZAÇÃO E ROTEAMENTO ---
    
    function renderFornecedorForm() { return `<h4>Cadastrar Fornecedor</h4><form id="formAddFornecedor" class="row g-3 mb-4"><div class="col-md-5"><input type="text" class="form-control" name="nome" placeholder="Nome do Fornecedor" required></div><div class="col-md-5"><input type="text" class="form-control" name="contato" placeholder="Contato"></div><div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button></div></form>`; }
    
    function renderProdutoForm() { return `<h4>Cadastrar Produto</h4><form id="formAddProduto" class="row g-3 mb-4"><div class="col-md-3"><input type="text" class="form-control" name="nome" placeholder="Nome do Produto" required></div><div class="col-md-3"><input type="number" class="form-control" name="preco" placeholder="Preço" step="0.01" required></div><div class="col-md-4"><select class="form-select" name="id_fornecedor" id="selectFornecedorProduto" required><option value="">Selecione um Fornecedor</option></select></div><div class="col-md-2"><button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button></div></form>`; }

    function renderCrudArea() {
        return `
            <h2>Gerenciamento de Cadastros</h2>
            <ul class="nav nav-tabs" id="crudTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-produtos" type="button" role="tab" aria-controls="tab-produtos" aria-selected="true">Produtos</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fornecedores" type="button" role="tab" aria-controls="tab-fornecedores" aria-selected="false">Fornecedores</button></li>
            </ul>
            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="tab-produtos" role="tabpanel">${renderProdutoForm()}<hr><h3>Lista de Produtos</h3><div id="produtosList">Carregando...</div></div>
                <div class="tab-pane fade" id="tab-fornecedores" role="tabpanel">${renderFornecedorForm()}<hr><h3>Lista de Fornecedores</h3><div id="fornecedoresList">Carregando...</div></div>
            </div>
        `;
    }
    
    function renderSelecaoArea() {
         return `
            <h2><i class="fas fa-search-plus"></i> Seleção de Produtos</h2>
            <form id="formAddCesta">
                <p class="lead">Marque os produtos que deseja adicionar (1 unidade por produto):</p>
                <div id="produtosSelecaoList" class="table-responsive"><p class="text-muted">Carregando produtos para seleção...</p></div>
                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-cart-plus"></i> Adicionar Selecionados à Cesta</button>
            </form>
         `;
    }
    
    function renderCestaViewArea() {
         return `
            <h2><i class="fas fa-shopping-cart"></i> Minha Cesta de Compras</h2>
            <div id="cestaResumo" class="alert alert-info">Carregando resumo...</div>
            <div id="cestaItens" class="table-responsive"><p class="text-muted">Carregando itens da cesta...</p></div>
            <button id="esvaziarCestaBtn" class="btn btn-danger mt-3" style="display: none;"><i class="fas fa-trash"></i> Esvaziar Cesta</button>
         `;
    }
    
    // --- LÓGICA DE CARREGAMENTO AJAX (loadArea, sendCrudRequest, fetchProdutos...) ---

    function loadArea(area) {
        $('.active-nav').removeClass('active');
        $(`[data-area="${area}"]`).addClass('active');
        const content = $('#contentArea');
        content.empty();
        
        switch (area) {
            case 'crud': content.html(renderCrudArea()); initCrudArea(); break;
            case 'cesta-selecao': content.html(renderSelecaoArea()); loadSelecaoProdutos(); break;
            case 'cesta-view': content.html(renderCestaViewArea()); loadCesta(); break;
            default: content.html('<h2 class="text-center text-muted">Selecione uma opção no menu.</h2>');
        }
    }

    // Ajax Genérico
    function sendCrudRequest(action, data, successCallback) {
        $.ajax({
            url: API_URL, type: 'POST', data: { action: action, ...data }, dataType: 'json',
            success: function(response) {
                alert(response.message);
                if (response.success && successCallback) { successCallback(); }
            },
            error: function(xhr) {
                alert('Erro de comunicação com o servidor.');
                console.error("AJAX Error:", xhr.status, xhr.responseText);
            }
        });
    }

    // Função auxiliar para carregar produtos
    function fetchProdutos(renderCallback, targetElement) {
        targetElement.html('<p class="text-muted">Carregando produtos...</p>');
        $.post(API_URL, { action: 'get_produtos' }, function(response) {
            if (response.success) { renderCallback(response.data, targetElement); } 
            else { targetElement.html(`<div class="alert alert-danger">${response.message}</div>`); }
        }, 'json');
    }

    // --- CRUD: Carregamento e Edição ---

    function loadFornecedores() {
        const list = $('#fornecedoresList');
        list.html('<p class="text-muted">Carregando...</p>');
        $.post(API_URL, { action: 'get_fornecedores' }, function(response) {
            if (!response.success) { list.html(`<div class="alert alert-danger">${response.message}</div>`); return; }
            fornecedoresCache = response.data; 
            const select = $('#selectFornecedorProduto');
            select.empty().append('<option value="">Selecione um Fornecedor</option>');

            if (fornecedoresCache.length === 0) { list.html('<p class="text-info">Nenhum fornecedor cadastrado.</p>'); return; }

            let tableHtml = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Contato</th><th class="table-acoes">Ações</th></tr></thead><tbody>';
            fornecedoresCache.forEach(f => {
                tableHtml += `<tr>
                    <td>${f.id}</td>
                    <td>${f.nome}</td>
                    <td>${f.contato}</td>
                    <td>
                        <button class="btn btn-info btn-sm edit-fornecedor" data-id="${f.id}" data-bs-toggle="modal" data-bs-target="#editModal"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm delete-fornecedor" data-id="${f.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
                select.append(`<option value="${f.id}">${f.nome}</option>`);
            });
            tableHtml += '</tbody></table>';
            list.html(tableHtml);
        }, 'json');
    }

    function loadProdutos() {
        const element = $('#produtosList');
        fetchProdutos((data) => {
            if (data.length === 0) { element.html('<p class="text-info">Nenhum produto cadastrado.</p>'); return; }
            let html = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Fornecedor</th><th class="table-acoes">Ações</th></tr></thead><tbody>';
            data.forEach(p => {
                html += `<tr>
                    <td>${p.id}</td>
                    <td>${p.nome}</td>
                    <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                    <td>${p.fornecedor_nome || 'N/A'}</td>
                    <td>
                        <button class="btn btn-info btn-sm edit-produto" data-id="${p.id}" data-bs-toggle="modal" data-bs-target="#editModal"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm delete-produto" data-id="${p.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            element.html(html);
        }, element);
    }
    
    // --- CESTA: Lógica de Carregamento e Eventos ---

    function loadSelecaoProdutos() {
        const list = $('#produtosSelecaoList');
        fetchProdutos((data) => {
            if (data.length === 0) { list.html('<p class="alert alert-warning">Nenhum produto disponível. Cadastre na aba "Cadastros".</p>'); return; }
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
            list.html(html);
        }, list);
    }

    function loadCesta() {
        const itensList = $('#cestaItens');
        const resumo = $('#cestaResumo');
        itensList.html('<p class="text-muted">Carregando itens da cesta...</p>');
        resumo.html('<p class="text-muted">Carregando resumo...</p>');

        $.post(API_URL, { action: 'get_cesta' }, function(response) {
            if (!response.success || !response.data || !Array.isArray(response.data.produtos)) {
                itensList.html('<p class="alert alert-danger">Falha ao carregar cesta.</p>'); return;
            }

            const dados = response.data;
            resumo.html(`
                <i class="fas fa-info-circle"></i>
                Total de Produtos Selecionados: <strong>${dados.total_itens}</strong> | 
                Valor Total da Cesta: <strong>R$ ${dados.valor_total}</strong>
            `);

            if (dados.total_itens === 0) {
                itensList.html('<p class="alert alert-warning">Sua cesta está vazia.</p>');
                $('#esvaziarCestaBtn').hide();
                return;
            }

            let html = '<table class="table table-bordered table-sm"><thead><tr><th>Nome</th><th>Preço</th><th>Ação</th></tr></thead><tbody>';
            dados.produtos.forEach(p => {
                html += `<tr>
                    <td>${p.nome}</td>
                    <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                    <td><button class="btn btn-sm btn-danger remove-item" data-id="${p.id}"><i class="fas fa-times"></i> Remover</button></td>
                </tr>`;
            });
            html += '</tbody></table>';
            itensList.html(html);
            $('#esvaziarCestaBtn').show();
        }, 'json');
    }

    // --- PONTO DE ENTRADA E EVENTOS GLOBAIS ---

    function initCrudArea() {
        loadFornecedores();
        loadProdutos();
        $('#crudTabs button').on('shown.bs.tab', function(e) {
            if ($(this).attr('data-bs-target') === '#tab-fornecedores') { loadFornecedores(); }
        });
    }

    $(document).ready(function() {
        const contentArea = $('#contentArea');

        // Roteamento do menu
        $('.nav-link').click(function(e) { e.preventDefault(); loadArea($(this).data('area')); });

        // --- Eventos CRUD (CREATE) ---
        contentArea.on('submit', '#formAddFornecedor', function(e) { e.preventDefault(); sendCrudRequest('add_fornecedor', $(this).serializeArray().reduce((a, x) => ({...a, [x.name]: x.value}), {}), loadFornecedores); });
        contentArea.on('submit', '#formAddProduto', function(e) { e.preventDefault(); sendCrudRequest('add_produto', $(this).serializeArray().reduce((a, x) => ({...a, [x.name]: x.value}), {}), loadProdutos); });
        
        // --- Eventos DELETE ---
        contentArea.on('click', '.delete-fornecedor', function() { if (confirm('Excluir este fornecedor?')) { sendCrudRequest('delete_fornecedor', { id: $(this).data('id') }, loadFornecedores); } });
        contentArea.on('click', '.delete-produto', function() { if (confirm('Excluir este produto?')) { sendCrudRequest('delete_produto', { id: $(this).data('id') }, loadProdutos); } });

        // --- Eventos UPDATE (EDITAR) ---
        // ABRIR MODAL e buscar dados para FORNECEDOR
        contentArea.on('click', '.edit-fornecedor', function() {
            const id = $(this).data('id');
            $('#editModalLabel').text('Editar Fornecedor ID: ' + id);
            $('#editId').val(id);
            $('#editType').val('fornecedor'); 
            $('#editContatoGroup').show();
            $('#editPrecoGroup, #editFornecedorGroup').hide();

            $.post(API_URL, { action: 'get_fornecedor_one', id: id }, function(response) {
                if (response.success) {
                    const f = response.data;
                    $('#editNome').val(f.nome);
                    $('#editContato').val(f.contato);
                } else {
                    alert('Erro ao carregar dados: ' + response.message);
                }
            }, 'json');
        });
        
        // ABRIR MODAL e buscar dados para PRODUTO
        contentArea.on('click', '.edit-produto', function() {
            const id = $(this).data('id');
            $('#editModalLabel').text('Editar Produto ID: ' + id);
            $('#editId').val(id);
            $('#editType').val('produto'); 
            $('#editPrecoGroup, #editFornecedorGroup').show();
            $('#editContatoGroup').hide();

            // Você precisaria de um 'get_produto_one' aqui e popular os campos
            // Usando dados do cache para simplificar, mas o ideal seria AJAX
            // Exemplo Simplificado:
          
        });


        // --- EVENTO DE SUBMISSÃO GERAL DE EDIÇÃO ---
        $('#formEditItem').submit(function(e) {
            e.preventDefault();
            const type = $('#editType').val();
            const data = $(this).serializeArray().reduce((a, x) => ({...a, [x.name]: x.value}), {});
            let action = '';
            let successCallback = null;
            
            if (type === 'fornecedor') {
                action = 'update_fornecedor';
                successCallback = loadFornecedores;
            } else if (type === 'produto') {
                action = 'update_produto';
                successCallback = loadProdutos;
            }

            if (action) {
                sendCrudRequest(action, data, function() {
                    successCallback();
                    // Fecha o modal após a atualização
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                });
            }
        });
        
        // --- Eventos da Cesta (Remoção e Adição) ---
        contentArea.on('submit', '#formAddCesta', function(e) {
            e.preventDefault();
            const selectedProducts = $(this).find('input[name="produtos[]"]:checked').map((i, el) => $(el).val()).get();
            if (selectedProducts.length === 0) { alert('Selecione pelo menos um produto.'); return; }
            sendCrudRequest('add_to_cesta', { produtos: selectedProducts }, loadSelecaoProdutos);
        });
        contentArea.on('click', '.remove-item', function() { 
            if (confirm('Confirmar remoção deste item?')) { sendCrudRequest('remove_item', { produto_id: $(this).data('id') }, loadCesta); } 
        });
        contentArea.on('click', '#esvaziarCestaBtn', function() {
            if (confirm('Deseja esvaziar toda a sua cesta?')) { sendCrudRequest('clear_cart', {}, loadCesta); }
        });


        // Carga Inicial
        loadArea('crud');
    });
</script>
</body>
</html>