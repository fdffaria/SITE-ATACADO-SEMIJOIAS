<?php
// Arquivo: conexao.php (ATUALIZADO COM FUSO HORÁRIO)

// Define o fuso horário padrão para todas as funções de data e hora do PHP
date_default_timezone_set('America/Sao_Paulo');

$servername = "193.203.175.153";
$username   = "u351874708_admin";
$password   = "Mldf07032022@";
$dbname     = "u351874708_cadastro";

// Tenta criar a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Define o charset para utf8 para evitar problemas com acentos
mysqli_set_charset($conn, "utf8");

// Verifica se a conexão falhou
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}
?>