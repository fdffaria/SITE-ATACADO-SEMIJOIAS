<?php
// Arquivo: enviar_codigo.php (VERSÃO FINAL COM CORREÇÃO)

// Garante que todas as operações de string do PHP usem UTF-8
mb_internal_encoding('UTF-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Inclui o serviço de SMS, se ele existir
if (file_exists('servico_sms.php')) {
    require_once 'servico_sms.php';
}

// Função padronizada para responder em JSON
function responderJson($status, $mensagem, $extra_data = []) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $resposta = ['status' => $status, 'mensagem' => $mensagem];
    if (!empty($extra_data)) {
        $resposta = array_merge($resposta, $extra_data);
    }
    echo json_encode($resposta);
    exit();
}

try {
    // Inclusão de dependências
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once 'conexao.php';

    session_start();

    // ======================= CORREÇÃO IMPORTANTE =======================
    // Agora verifica o campo correto: 'login_identificador'
    if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['login_identificador'])) {
        responderJson('error', 'Pedido inválido.');
    }
    // ===================================================================

    $identificador = trim($_POST['login_identificador']);
    $is_email = filter_var($identificador, FILTER_VALIDATE_EMAIL);
    $usuario = null;

    // Lógica para encontrar o utilizador por e-mail ou telemóvel
    if ($is_email) {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $identificador);
    } else {
        $telefone = preg_replace('/\D/', '', $identificador);
        if (!preg_match('/^\d{10,11}$/', $telefone)) {
            responderJson('error', 'Formato de celular inválido. Use apenas números, com DDD.');
        }
        $sql = "SELECT * FROM usuarios WHERE telefone = ? AND telefone_validado_em IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $telefone);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        if ($is_email) {
            // Se for um e-mail novo, cria o utilizador
            $nome_usuario = explode('@', $identificador)[0];
            $senha_placeholder = password_hash(random_bytes(20), PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $nome_usuario, $identificador, $senha_placeholder);
            $stmt_insert->execute();
            $id_novo_usuario = $conn->insert_id;
            $stmt_insert->close();
            // Busca o novo usuário para ter todos os dados
            $sql_novo = "SELECT * FROM usuarios WHERE id = ?";
            $stmt_novo = $conn->prepare($sql_novo);
            $stmt_novo->bind_param("i", $id_novo_usuario);
            $stmt_novo->execute();
            $usuario = $stmt_novo->get_result()->fetch_assoc();
            $stmt_novo->close();
        } else {
            responderJson('error', 'Celular não cadastrado ou não validado. Por favor, acesse sua conta com o e-mail para cadastrar seu telefone.');
        }
    }
    
    // Geração e armazenamento do código de acesso
    $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codigo_hash = password_hash($codigo, PASSWORD_DEFAULT);
    $validade = (new DateTime())->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s');
    $sql_update = "UPDATE usuarios SET login_code = ?, code_validade = ? WHERE id = ?";
    $stmt_upd = $conn->prepare($sql_update);
    $stmt_upd->bind_param("ssi", $codigo_hash, $validade, $usuario['id']);
    $stmt_upd->execute();
    $stmt_upd->close();

    // Envio do código por e-mail
    if (!empty($usuario['email'])) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pedidos@atacadosemijoias.com';
        $mail->Password   = 'Mldf07032022@';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('pedidos@atacadosemijoias.com', 'Atacado de Semijoias');
        $mail->addAddress($usuario['email'], $usuario['nome']);
        $mail->isHTML(true);
        $mail->Subject = "Seu código de acesso: " . $codigo;
        $mail->Body    = "Olá!<br><br>Seu código de acesso para Atacado Semijoias é:<br><br><h1 style='text-align:center; letter-spacing: 5px; color: #C9A33A;'>{$codigo}</h1><br>Este código é válido por 10 minutos.";
        $mail->send();
    }
    
    // Envio do código por SMS, se for um login por telemóvel
    if (!$is_email && function_exists('enviarSMS_SpeedMarket') && !empty($usuario['telefone'])) {
        $mensagem_sms = "Seu codigo de acesso para Atacado Semijoias e: " . $codigo;
        enviarSMS_SpeedMarket($usuario['telefone'], $mensagem_sms);
    }
    
    // Resposta de sucesso para o front-end
    $destino_display = $is_email ? $usuario['email'] : 'seu celular final ' . substr($usuario['telefone'], -4);
    responderJson('success', 'Código enviado!', [
        'destino_para_display' => $destino_display,
        'email_para_validacao' => $usuario['email'],
        'tipo_envio' => $is_email ? 'email' : 'sms'
    ]);

} catch (Exception $e) {
    responderJson('error', "Erro no processo: " . $e->getMessage());
}

$conn->close();
?>