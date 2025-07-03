<?php
// Iniciar sessão e conexão com o banco de dados
session_start();
require_once 'conexao.php';

// Função para exibir uma mensagem de erro e parar a execução
function exibirErro($mensagem) {
    // A tag de link aponta para o login.html na raiz do site
    die('<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Erro de Autenticação</title><style>body{font-family: Arial, sans-serif; text-align: center; padding-top: 50px; background-color: #f8d7da; color: #721c24;} .container{max-width: 600px; margin: auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);} h2{color: #dc3545;}</style></head><body><div class="container"><h2>Link Inválido</h2><p>' . $mensagem . '</p><p><a href="login.html">Tentar novamente</a></p></div></body></html>');
}

// 1. Verificar se o token foi passado na URL
if (empty($_GET['token'])) {
    exibirErro("O link de acesso parece estar quebrado. Por favor, solicite um novo.");
}

$token_recebido = $_GET['token'];

// 2. Procurar o utilizador com este token na base de dados
$sql = "SELECT id, nome, token_validade FROM usuarios WHERE login_token = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $token_recebido);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // 3. Verificar se o token não expirou
        $agora = new DateTime();
        $validade_token = new DateTime($user['token_validade']);

        if ($agora > $validade_token) {
            // Token expirado
            exibirErro("Este link de acesso expirou. Por favor, solicite um novo.");
        } else {
            // 4. Token válido! Fazer o login
            
            // Define as variáveis de sessão para o login
            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $user['id'];
            $_SESSION['nome_usuario'] = $user['nome'];

            // 5. Invalidar o token para que não possa ser reutilizado
            $sql_invalidate = "UPDATE usuarios SET login_token = NULL, token_validade = NULL WHERE id = ?";
            if ($stmt_invalidate = $conn->prepare($sql_invalidate)) {
                $stmt_invalidate->bind_param("i", $user['id']);
                $stmt_invalidate->execute();
                $stmt_invalidate->close();
            }

            // 6. Redirecionar para a página inicial (home)
            header("Location: index.html");
            exit();
        }

    } else {
        // Token não encontrado na base de dados
        exibirErro("Este link de acesso é inválido ou já foi utilizado.");
    }

    $stmt->close();
}
$conn->close();
?>