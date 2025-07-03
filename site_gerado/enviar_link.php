<?php
// Usar as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Incluir os ficheiros da biblioteca
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Iniciar sessão e conexão
session_start();
require_once 'conexao.php';

// Verificação do pedido
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['email'])) {
    header("Location: login.html?status=error_invalid_request");
    exit();
}

$email = trim($_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.html?status=error_invalid_email");
    exit();
}

$id_usuario = null;
$nome_usuario = '';

// Verificar/criar utilizador
$sql_check = "SELECT id, nome FROM usuarios WHERE email = ?";
if ($stmt_check = $conn->prepare($sql_check)) {
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $id_usuario = $user['id'];
        $nome_usuario = $user['nome'];
    } else {
        $nome_usuario = explode('@', $email)[0];
        $senha_placeholder = password_hash(random_bytes(20), PASSWORD_DEFAULT);

        $sql_insert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("sss", $nome_usuario, $email, $senha_placeholder);
            $stmt_insert->execute();
            $id_usuario = $conn->insert_id;
            $stmt_insert->close();
        }
    }
    $stmt_check->close();
}

if (!$id_usuario) {
    header("Location: login.html?status=error_db");
    exit();
}

// Gerar e guardar o token
$token = bin2hex(random_bytes(32));
$validade = new DateTime();
$validade->add(new DateInterval('PT15M'));
$validade_formatada = $validade->format('Y-m-d H:i:s');

$sql_update = "UPDATE usuarios SET login_token = ?, token_validade = ? WHERE id = ?";
if ($stmt_update = $conn->prepare($sql_update)) {
    $stmt_update->bind_param("ssi", $token, $validade_formatada, $id_usuario);
    $stmt_update->execute();
    $stmt_update->close();
}

// Criar URL de validação e adicionar o redirecionamento
$url_validacao = "https://www.atacadosemijoias.com/validar_link.php?token=" . $token;

// ======================= LÓGICA DE REDIRECIONAMENTO ADICIONADA =======================
if (!empty($_POST['redirect_url'])) {
    $url_validacao .= "&redirect_url=" . urlencode($_POST['redirect_url']);
}
// ===================================================================================

$mail = new PHPMailer(true);

try {
    // Configurações SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'pedidos@atacadosemijoias.com';
    $mail->Password   = 'Mldf07032022@'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Remetente e Destinatário
    $mail->setFrom('pedidos@atacadosemijoias.com', 'Atacado de Semijoias');
    $mail->addAddress($email, $nome_usuario);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Seu Link de Acesso - Atacado de Semijoias';
    $mail->Body    = "Olá!<br><br>Para aceder à sua conta, clique no botão abaixo. Este link é único e válido por 15 minutos.<br><br><div style='text-align:center;'><a href='{$url_validacao}' style='background-color: #C9A33A; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-size: 16px;'>Aceder à Conta</a></div><br><br>Se não solicitou isto, pode simplesmente ignorar este e-mail.<br><br>Atenciosamente,<br>Equipa Atacado de Semijoias";
    $mail->AltBody = "Para aceder à sua conta, copie e cole este link no seu navegador: {$url_validacao}";

    $mail->send();
    header("Location: login.html?status=success");
    exit();

} catch (Exception $e) {
    header("Location: login.html?status=error_email");
    exit();
}

$conn->close();
?>