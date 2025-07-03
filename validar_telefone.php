<?php
session_start();
require_once 'conexao.php';

function responderJson($status, $mensagem) {
    if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); }
    echo json_encode(['status' => $status, 'mensagem' => $mensagem]);
    exit();
}

if (!isset($_SESSION['loggedin']) || !isset($_POST['codigo_sms']) || !isset($_POST['telefone'])) {
    responderJson('error', 'Acesso negado ou dados em falta.');
}

$id_usuario = $_SESSION['id_usuario'];
$codigo_recebido = trim($_POST['codigo_sms']);
$telefone_submetido = preg_replace('/\D/', '', $_POST['telefone']);

// CORREÇÃO: Busca os dados das novas colunas
$sql = "SELECT validation_code, validation_code_expires, telefone FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario || empty($usuario['validation_code'])) {
    responderJson('error', 'Nenhum código de validação pendente.');
}
if (new DateTime() > new DateTime($usuario['validation_code_expires'])) {
    responderJson('error', 'Código expirado. Por favor, solicite um novo.');
}
if ($telefone_submetido !== $usuario['telefone']){
     responderJson('error', 'O número de telefone mudou durante a validação. Tente novamente.');
}

if (password_verify($codigo_recebido, $usuario['validation_code'])) {
    $agora = date('Y-m-d H:i:s');
    // CORREÇÃO: Limpa as colunas de validação e atualiza a data de confirmação
    $sql_update = "UPDATE usuarios SET telefone_validado_em = ?, validation_code = NULL, validation_code_expires = NULL WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $agora, $id_usuario);
    $stmt_update->execute();
    $stmt_update->close();
    responderJson('success', 'Telefone validado com sucesso! A página será atualizada.');
} else {
    responderJson('error', 'Código inválido. Tente novamente.');
}

$conn->close();
?>