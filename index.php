<?php
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$auth = new Auth(Database::getInstance());
if ($auth->check()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestão de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg" style="width: 100%; max-width: 400px;">
            <div class="card-header bg-primary text-white text-center">
                <h4>Acesso ao Sistema</h4>
            </div>
            <div class="card-body">
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Entrar</button>
                    <p class="mt-3 text-center">
                        <a href="#" id="showRegister">Não tem conta? Cadastre-se</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Lógica de Login e Registro (AJAX)
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const action = form.data('action') || 'login';

                $.ajax({
                    url: 'api.php',
                    type: 'POST',
                    data: form.serialize() + '&action=' + action,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message || 'Sucesso!');
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                // Caso seja registro, limpa o form para login
                                form.trigger('reset').data('action', 'login');
                                $('#showRegister').text('Não tem conta? Cadastre-se');
                                form.find('button[type="submit"]').text('Entrar');
                            }
                        } else {
                            alert('Erro: ' + response.message);
                        }
                    }
                });
            });
            
            // Alternar para a tela de registro
            $('#showRegister').click(function(e) {
                e.preventDefault();
                const form = $('#loginForm');
                const isRegister = form.data('action') === 'register';

                if (!isRegister) {
                    form.data('action', 'register');
                    form.find('button[type="submit"]').text('Cadastrar');
                    $(this).text('Voltar ao Login');
                } else {
                    form.data('action', 'login');
                    form.find('button[type="submit"]').text('Entrar');
                    $(this).text('Não tem conta? Cadastre-se');
                }
            });
        });
    </script>
</body>
</html>