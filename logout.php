<?php
// Arquivo: logout.php

// Inicia a sessão
session_start();

// Destrói todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona o utilizador para a página inicial
header("location: index.html");
exit;
?>