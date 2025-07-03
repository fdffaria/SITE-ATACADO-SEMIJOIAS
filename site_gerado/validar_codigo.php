<?php
// Arquivo: validar_codigo.php (VERSÃO FINAL E CORRIGIDA)

session_start();
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson($status, $mensagem, $extra_data = []) {
    $resposta = ['status' => $status, 'mensagem' => $mensagem];
    if (!empty($extra_data)) {
        $resposta = array_merge($resposta, $extra_data);
    }
    echo json_encode($resposta);
    exit();
}

// Verifica se os campos corretos foram recebidos do formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['email']) || empty($_POST['codigo'])) {
    responderJson('error', 'Informações inválidas. Por favor, tente novamente desde o início.');
}

$email = trim($_POST['email']);
$codigo_recebido = trim($_POST['codigo']);
$redirect_url_form = !empty($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null;

// A validação é sempre feita com o e-mail, que é o identificador único
$sql = "SELECT id, nome, login_code, code_validade FROM usuarios WHERE email = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (empty($user['login_code']) || empty($user['code_validade'])) {
            responderJson('error', 'Nenhum código de acesso ativo. Por favor, solicite um novo.');
        }

        $agora = new DateTime();
        $validade_code = new DateTime($user['code_validade']);

        if ($agora > $validade_code) {
            responderJson('error', 'Este código de acesso expirou. Por favor, solicite um novo.');
        }

        if (password_verify($codigo_recebido, $user['login_code'])) {
            // SUCESSO
            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $user['id'];
            $_SESSION['nome_usuario'] = $user['nome'];

            // Invalida o código
            $sql_invalidate = "UPDATE usuarios SET login_code = NULL, code_validade = NULL WHERE id = ?";
            if ($stmt_invalidate = $conn->prepare($sql_invalidate)) {
                $stmt_invalidate->bind_param("i", $user['id']);
                $stmt_invalidate->execute();
                $stmt_invalidate->close();
            }

            // Redireciona para o destino correto
            $url_destino = $redirect_url_form ? $redirect_url_form : 'minha_conta.php';
            responderJson('success', 'Login realizado com sucesso! A redirecioná-lo...', ['redirect_url' => $url_destino]);

        } else {
            responderJson('error', 'O código que digitou está incorreto.');
        }

    } else {
        responderJson('error', 'Utilizador não encontrado. Verifique os dados e tente novamente.');
    }
    $stmt->close();
}
$conn->close();
?>