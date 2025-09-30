<?php
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

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
        .nav-link.active { font-weight: bold; }
        /* Aqui você adicionaria mais CSS para um visual AdminLTE/Tailwind */
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Gestão de Produtos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active-nav" href="#" data-area="crud">Cadastros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active-nav" href="#" data-area="cesta-selecao">Seleção de Produtos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active-nav" href="#" data-area="cesta-view">Minha Cesta</a>
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
        <h2 class="text-center text-muted">Selecione uma opção no menu.</h2>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> 
<script>
    const API_URL = 'api.php';
    let currentArea = '';

    // Função de roteamento principal
    function loadArea(area) {
        currentArea = area;
        const content = $('#contentArea');
        content.empty(); // Limpa o conteúdo anterior

        switch (area) {
            case 'crud':
                content.html(renderCrudArea());
                initCrudArea();
                break;
            case 'cesta-selecao':
                content.html(renderSelecaoArea());
                loadSelecaoProdutos(); // Função para carregar os produtos via AJAX
                break;
            case 'cesta-view':
                content.html(renderCestaViewArea());
                loadCesta(); // Função para carregar a cesta via AJAX
                break;
            default:
                content.html('<h2 class="text-center text-muted">Selecione uma opção no menu.</h2>');
        }
    }

    // --- Renderização do HTML Estático (Você deve implementar o AJAX aqui!) ---
    
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
                <div id="produtosList">Carregando produtos...</div>
            </div>
            <div class="tab-pane fade" id="tab-fornecedores">
                ${renderFornecedorForm()}
                <hr>
                <h3>Fornecedores</h3>
                <div id="fornecedoresList">Carregando fornecedores...</div>
            </div>
        </div>
    `;
}function renderFornecedorForm() {
    return `
        <h4>Cadastrar Fornecedor</h4>
        <form id="formAddFornecedor" class="row g-3 mb-4">
            <div class="col-md-5">
                <input type="text" class="form-control" name="nome" placeholder="Nome do Fornecedor" required>
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="contato" placeholder="Contato/Telefone/Email">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button>
            </div>
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
    function renderProdutoForm() {
    return `
        <h4>Cadastrar Produto</h4>
        <form id="formAddProduto" class="row g-3 mb-4">
            <div class="col-md-3">
                <input type="text" class="form-control" name="nome" placeholder="Nome do Produto" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" name="preco" placeholder="Preço" step="0.01" required>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="id_fornecedor" id="selectFornecedorProduto" required>
                    <option value="">Selecione um Fornecedor</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Adicionar</button>
            </div>
        </form>
    `;
}

    function renderCestaViewArea() {
         return `
            <h2>Minha Cesta de Compras</h2>
            <div id="cestaResumo"></div>
            <div id="cestaItens">Carregando itens da cesta...</div>
         `;
    }

    // --- Inicialização e Eventos ---

    // Evento de clique no menu
    $('.active-nav').click(function(e) {
        e.preventDefault();
        $('.active-nav').removeClass('active');
        $(this).addClass('active');
        loadArea($(this).data('area'));
    });
    
    // Função de Logout
    if (window.location.search.includes('logout=true')) {
        $.ajax({
            url: API_URL,
            type: 'POST',
            data: { action: 'logout' },
            success: function() {
                window.location.href = 'index.php';
            }
        });
    }

    // Carrega a área inicial ao carregar a página
    loadArea('crud');
    
    // **Atenção: A partir daqui, você deve implementar:**
    // 1. initCrudArea() - Lógica para carregar e gerenciar Produtos/Fornecedores (CRUD completo).
    // 2. loadSelecaoProdutos() - Carrega produtos com checkbox e envia dados para 'api.php?action=add_to_cesta'.
    // 3. loadCesta() - Carrega os dados da cesta ('api.php?action=get_cesta') e os exibe no resumo e tabela.
    // --- FUNÇÕES DE LÓGICA E AJAX ---

// A. Função que liga todos os eventos da área CRUD
function initCrudArea() {
    // 1. Carrega os dados iniciais
    loadFornecedores();
    loadProdutos();
    
    // 2. Trata o clique na aba para garantir que os dados sejam recarregados
    $('#crudTabs button').on('shown.bs.tab', function (e) {
        const targetId = $(this).attr('data-bs-target');
        if (targetId === '#tab-fornecedores') {
            loadFornecedores();
        } else if (targetId === '#tab-produtos') {
            loadProdutos();
        }
    });

    // 3. Evento de submissão do formulário de Fornecedor
    $('#formAddFornecedor').submit(function(e) {
        e.preventDefault();
        // Chama a função genérica para envio de dados
        sendCrudRequest('add_fornecedor', $(this).serialize(), loadFornecedores);
    });
    
    // 4. Evento de submissão do formulário de Produto
    $('#formAddProduto').submit(function(e) {
        e.preventDefault();
        // Chama a função genérica para envio de dados
        sendCrudRequest('add_produto', $(this).serialize(), loadProdutos);
    });
}

// B. Função para carregar Fornecedores (que estava em "Carregando...")
function loadFornecedores() {
    $.post(API_URL, { action: 'get_fornecedores' }, function(response) {
        if (response.success) {
            fornecedoresCache = response.data; // Armazena em cache para o formulário de Produto
            const list = $('#fornecedoresList');
            list.empty();
            
            // Preenche o Select de Produtos
            const select = $('#selectFornecedorProduto');
            select.empty().append('<option value="">Selecione um Fornecedor</option>');
            
            if (response.data.length === 0) {
                list.html('<p class="text-info">Nenhum fornecedor cadastrado. Use o formulário acima.</p>');
                return;
            }

            let html = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Contato</th></tr></thead><tbody>';
            response.data.forEach(f => {
                html += `
                    <tr>
                        <td>${f.id}</td>
                        <td>${f.nome}</td>
                        <td>${f.contato}</td>
                    </tr>
                `;
                select.append(`<option value="${f.id}">${f.nome}</option>`);
            });
            html += '</tbody></table>';
            list.html(html);

        } else {
            $('#fornecedoresList').html(`<p class="alert alert-danger">Erro ao carregar dados: ${response.message}</p>`);
        }
    }, 'json');
}

// C. Função para carregar Produtos
function loadProdutos() {
    $.post(API_URL, { action: 'get_produtos' }, function(response) {
        if (response.success) {
            const list = $('#produtosList');
            list.empty();

            if (response.data.length === 0) {
                list.html('<p class="text-info">Nenhum produto cadastrado. Use o formulário acima.</p>');
                return;
            }
            
            let html = '<table class="table table-striped table-sm"><thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Fornecedor</th></tr></thead><tbody>';
            response.data.forEach(p => {
                html += `
                    <tr>
                        <td>${p.id}</td>
                        <td>${p.nome}</td>
                        <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                        <td>${p.fornecedor_nome || 'N/A'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            list.html(html);
        } else {
            $('#produtosList').html(`<p class="alert alert-danger">Erro ao carregar dados: ${response.message}</p>`);
        }
    }, 'json');
}
function sendCrudRequest(action, data, successCallback) {
    $.ajax({
        url: API_URL,
        type: 'POST',
        data: data + '&action=' + action,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                successCallback(); // Recarrega a lista
                if(action.includes('add')) {
                    // Limpa o formulário após o sucesso
                    $('#' + (action === 'add_produto' ? 'formAddProduto' : 'formAddFornecedor')).trigger('reset');
                }
            } else {
                alert('Erro na operação: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Erro de comunicação com o servidor. Verifique o console (F12) para detalhes.');
            console.error("AJAX Error:", status, error, xhr.responseText);
        }
    });
}
// dashboard.php - Evento para REMOVER UM ITEM
$('#contentArea').on('click', '.remove-item', function() {
    const produtoId = $(this).data('id');
    
    if (!confirm('Confirmar remoção deste item?')) {
        return;
    }

    $.ajax({
        url: API_URL,
        type: 'POST',
        data: { action: 'remove_item', produto_id: produtoId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                loadCesta(); // Recarrega a cesta para refletir a mudança
            } else {
                alert('Erro ao remover item: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('Erro de comunicação com o servidor ao remover item.');
            console.error('AJAX Error:', xhr.responseText);
        }
    });
});

// dashboard.php - Evento para ESVAZIAR A CESTA INTEIRA
$('#contentArea').on('click', '#esvaziarCestaBtn', function() {
    if (!confirm('Tem certeza que deseja esvaziar toda a sua cesta?')) {
        return;
    }

    $.ajax({
        url: API_URL,
        type: 'POST',
        data: { action: 'clear_cart' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                loadCesta(); // Recarrega a cesta, que agora estará vazia
            } else {
                alert('Erro ao esvaziar cesta: ' + response.message);
            }
        }
    });
});
// Evento de submissão do formulário de Adicionar à Cesta
$('#contentArea').on('submit', '#formAddCesta', function(e) {
    
    // LINHA CRUCIAL: Impede o recarregamento da página
    e.preventDefault(); 
    
    const selectedProducts = $(this).find('input[name="produtos[]"]:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedProducts.length === 0) {
        alert('Atenção: Selecione pelo menos um produto para adicionar à cesta.');
        return;
    }

    $.ajax({
        url: API_URL,
        type: 'POST',
        data: { action: 'add_to_cesta', produtos: selectedProducts },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                // Reseta os checkboxes após adicionar
                $('#formAddCesta').trigger('reset'); 
            } else {
                alert('Falha ao adicionar à cesta: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Erro de comunicação ao adicionar à cesta. Verifique o console (F12).');
            console.error("AJAX Error Cesta:", status, error, xhr.responseText);
        }
    });
});
    // Função para carregar produtos na Área de Seleção (loadSelecaoProdutos)
function loadSelecaoProdutos() {
    // Note que esta chamada AJAX é idêntica à de loadProdutos!
    $.post(API_URL, { action: 'get_produtos' }, function(response) { 
        if (response.success) {
            const list = $('#produtosSelecaoList');
            list.empty();
            
            if (response.data.length === 0) {
                list.html('<p class="alert alert-warning">Nenhum produto disponível. Cadastre na aba "Cadastros".</p>');
                return;
            }

            let html = '<table class="table table-hover"><thead><tr><th>Nome</th><th>Preço</th><th>Fornecedor</th><th>Selecionar</th></tr></thead><tbody>';
            response.data.forEach(p => {
                html += `
                    <tr>
                        <td>${p.nome}</td>
                        <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                        <td>${p.fornecedor_nome || 'N/A'}</td>
                        <td><input type="checkbox" name="produtos[]" value="${p.id}" class="form-check-input"></td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            list.html(html); // Finalmente, insere o HTML com a tabela e os checkboxes!
        } else {
            $('#produtosSelecaoList').html(`<p class="alert alert-danger">Erro ao carregar produtos para seleção: ${response.message}</p>`);
        }
    }, 'json');
}



function loadCesta() {
    $.post(API_URL, { action: 'get_cesta' }, function(response) {
        if (response.success) {
            
            // Verifica se 'data' é um objeto e se 'produtos' é um array
            if (!response.data || !Array.isArray(response.data.produtos)) {
                 $('#cestaItens').html('<p class="alert alert-danger">Erro de estrutura de dados: Não foi possível ler a lista de produtos.</p>');
                 return;
            }

            const dados = response.data; // Agora 'dados' é o objeto de resumo
            const itensList = $('#cestaItens');
            const resumo = $('#cestaResumo');
            
            // --- 1. RENDERIZA O RESUMO ---
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

            // --- 2. RENDERIZA A TABELA DE ITENS ---
            let html = '<table class="table table-bordered table-sm"><thead><tr><th>Nome</th><th>Fornecedor</th><th>Preço</th><th>Ação</th></tr></thead><tbody>';
            
            // O ERRO ESTAVA AQUI: A iteração sobre os produtos!
            dados.produtos.forEach(p => { 
                html += `
                    <tr>
                        <td>${p.nome}</td>
                        <td>${p.fornecedor_nome || 'N/A'}</td>
                        <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
                        <td><button class="btn btn-sm btn-danger remove-item" data-id="${p.id}"><i class="fas fa-times"></i> Remover</button></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            itensList.html(html);
            $('#esvaziarCestaBtn').show();

        } else {
            $('#cestaItens').html(`<p class="alert alert-danger">Erro ao carregar cesta: ${response.message}</p>`);
        }
    }, 'json');
}













   $(document).ready(function() {
        // Evento de clique no menu
        $('.active-nav').click(function(e) {
            e.preventDefault();
            $('.active-nav').removeClass('active');
            $(this).addClass('active');
            loadArea($(this).data('area'));
        });
        
        // Eventos de submissão de formulários (como #formAddCesta) também devem estar aqui.
        // ... (Todos os outros listeners de formulário)

        // Carrega a área inicial ao carregar a página
        loadArea('crud');
    });
</script>
</body>
</html>