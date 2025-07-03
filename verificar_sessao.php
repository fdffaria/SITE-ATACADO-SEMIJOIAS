<?php
// Arquivo: verificar_sessao.php

session_start();

// Prepara uma resposta em formato JSON
header('Content-Type: application/json');

// Verifica se a sessão 'loggedin' existe e é verdadeira
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Se estiver logado, envia o nome do utilizador
    echo json_encode(['logado' => true, 'nome' => $_SESSION['nome_usuario']]);
} else {
    // Se não estiver logado, envia uma resposta indicando isso
    echo json_encode(['logado' => false]);
}
?>