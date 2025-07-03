<?php
// Arquivo: registrar_usuario.php (VERSÃO FINAL COM REDIRECIONAMENTO DE ERRO)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    if (empty($nome) || empty($email) || empty($senha)) {
        die("Erro: Todos os campos são obrigatórios.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Erro: Formato de e-mail inválido.");
    }

    // 3. Verificar se o e-mail já existe na base de dados
    $sql_check = "SELECT id FROM usuarios WHERE email = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        /*
        * ===============================================================
        * ALTERAÇÃO DE ERRO AQUI
        * ===============================================================
        */
        if ($stmt_check->num_rows > 0) {
            // Em vez de "morrer", mostra uma mensagem amigável e redireciona de volta.
            echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Erro no Cadastro</title>';
            // Redireciona para a página de cadastro após 4 segundos
            echo '<meta http-equiv="refresh" content="4;url=cadastro.html">';
            echo '<style>body{font-family: Arial, sans-serif; text-align: center; padding-top: 50px; background-color: #f4f5ff; color: #333;} .container{max-width: 600px; margin: auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);} h2{color: #dc3545;}</style></head><body>';
            echo '<div class="container">';
            echo '<h2>Erro: E-mail já cadastrado!</h2>';
            echo '<p>O e-mail que inseriu já existe no nosso sistema.</p>';
            echo '<p>Você será redirecionado de volta para a página de cadastro em 4 segundos. Por favor, tente usar outro e-mail ou <a href="login.html">faça login</a>.</p>';
            echo '</div></body></html>';
            
            // Fecha as conexões e para o script de forma limpa
            $stmt_check->close();
            $conn->close();
            exit(); 
        }
        $stmt_check->close();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql_insert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        $stmt_insert->bind_param("sss", $nome, $email, $senha_hash);

        if ($stmt_insert->execute()) {
            // Mensagem de sucesso com redirecionamento para o login
            echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Sucesso no Cadastro</title>';
            echo '<meta http-equiv="refresh" content="3;url=login.html">';
            echo '<style>body{font-family: Arial, sans-serif; text-align: center; padding-top: 50px; background-color: #f4f5ff; color: #333;} .container{max-width: 600px; margin: auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);} h2{color: #28a745;}</style></head><body>';
            echo '<div class="container">';
            echo '<h2>Cadastro realizado com sucesso!</h2>';
            echo '<p>Você será redirecionado para a página de login em 3 segundos.</p>';
            echo '<p>Se não for redirecionado, <a href="login.html">clique aqui</a>.</p>';
            echo '</div>';
            echo '</body></html>';
        } else {
            echo "Erro ao tentar realizar o cadastro. Por favor, tente novamente.";
        }
        $stmt_insert->close();
    }
    
    $conn->close();

} else {
    echo "Acesso inválido.";
}
?>