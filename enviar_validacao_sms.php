<?php
session_start();
require_once 'conexao.php';
require_once 'servico_sms.php';

function responderJson($status, $mensagem) {
    if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); }
    echo json_encode(['status' => $status, 'mensagem' => $mensagem]);
    exit();
}

if (!isset($_SESSION['loggedin']) || !isset($_POST['telefone'])) {
    responderJson('error', 'Acesso negado.');
}

$id_usuario = $_SESSION['id_usuario'];
$telefone = preg_replace('/\D/', '', $_POST['telefone']);

if (!preg_match('/^\d{10,11}$/', $telefone)) {
    responderJson('error', 'Formato de telefone inválido.');
}

$codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$codigo_hash = password_hash($codigo, PASSWORD_DEFAULT);
$validade = (new DateTime())->add(new DateInterval('PT10M'))->format('Y-m-d H:i:s');

// CORREÇÃO: Usa as novas colunas 'validation_code' e 'validation_code_expires'
$sql = "UPDATE usuarios SET telefone = ?, validation_code = ?, validation_code_expires = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $telefone, $codigo_hash, $validade, $id_usuario);
$stmt->execute();

if ($stmt->error) {
    responderJson('error', 'Erro ao salvar o código de validação na base de dados.');
}
$stmt->close();

$mensagem_sms = "Seu codigo de validacao para Atacado Semijoias e: " . $codigo;
$sms_enviado = enviarSMS_SpeedMarket($telefone, $mensagem_sms);

if($sms_enviado['success']){
    responderJson('success', 'Código de validação enviado por SMS!');
} else {
    responderJson('error', 'Não foi possível enviar o SMS. Verifique o número e tente novamente.');
}
$conn->close();
?>