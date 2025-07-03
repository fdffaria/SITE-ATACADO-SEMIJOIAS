<?php
// Arquivo: validar_login.php (VERSÃO COM REDIRECIONAMENTO INTELIGENTE)

session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha_form = trim($_POST['senha']);
    // Pega o URL de redirecionamento do campo escondido do formulário
    $redirect_url = trim($_POST['redirect_url']);

    if (empty($email) || empty($senha_form)) {
        die("Erro: E-mail e senha são obrigatórios.");
    }

    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $nome, $senha_hash_db);
            $stmt->fetch();

            if (password_verify($senha_form, $senha_hash_db)) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id_usuario'] = $id;
                $_SESSION['nome_usuario'] = $nome;
                
                // LÓGICA DE REDIRECIONAMENTO INTELIGENTE
                if (!empty($redirect_url)) {
                    // Se houver um URL de retorno, volta para ele
                    header("location: " . $redirect_url);
                } else {
                    // Caso contrário, vai para a página padrão "minha_conta"
                    header("location: minha_conta.php");
                }
                exit;

            } else {
                echo "Erro: A senha que você digitou não é válida.";
            }
        } else {
            echo "Erro: Nenhum utilizador encontrado com este e-mail.";
        }
        $stmt->close();
    }
    $conn->close();
} else {
    echo "Acesso inválido.";
}
?>