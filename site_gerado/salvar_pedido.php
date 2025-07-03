<?php
// Arquivo: salvar_pedido.php (VERSÃO HÍBRIDA DEFINITIVA E CORRIGIDA)

session_start();
require_once 'conexao.php';

// --- Configurações ---
$destinatario_email = "pedidos@atacadosemijoias.com"; 
$remetente_email = "pedidos@atacadosemijoias.com";
$url_base_site = "https://www.atacadosemijoias.com"; 

header('Content-Type: application/json');

// 1. VERIFICAR LOGIN
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado. Por favor, faça o login para continuar.']));
}

// 2. VALIDAR DADOS
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Método não permitido.']));
}
$dados_json = file_get_contents('php://input');
$pedidos_carrinho = json_decode($dados_json, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($pedidos_carrinho) || empty($pedidos_carrinho)) {
    http_response_code(400);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Dados do pedido inválidos ou vazios.']));
}

// 3. PREPARAR DADOS
$id_usuario = $_SESSION['id_usuario'];
$total_pecas_geral = 0;
$itens_para_inserir_bd = [];
$html_blocos_catalogo = '';
foreach ($pedidos_carrinho as $catalogo) {
    $catalogo_nome_formatado = htmlspecialchars(str_replace('_', ' ', $catalogo['catalogo']));
    $catalogo_nome_display = (strpos($catalogo_nome_formatado, 'pedido recarregado') !== false) ? "Itens de um Pedido Anterior" : strtoupper($catalogo_nome_formatado);
    $html_itens_tabela = '';
    $total_pecas_catalogo = 0;
    foreach ($catalogo['itens'] as $item) {
        $qtd = intval($item['qtd']);
        $total_pecas_geral += $qtd;
        $total_pecas_catalogo += $qtd;
        $itens_para_inserir_bd[] = ['ref' => $item['ref'], 'desc' => $item['desc'], 'img' => $item['img'], 'qtd' => $qtd];
        $html_itens_tabela .= "<tr><td><img src=\"" . htmlspecialchars($item['img']) . "\"></td><td class=\"ref\">" . htmlspecialchars($item['ref']) . "</td><td class=\"desc\">" . htmlspecialchars($item['desc']) . "</td><td class=\"qtd\">" . $qtd . "</td></tr>";
    }
    $html_blocos_catalogo .= '<div class="tabela-pedido-box"><span class="nomecat">Catálogo: ' . $catalogo_nome_display . '</span><table><thead><tr><th>Imagem</th><th>Referência</th><th>Descrição</th><th>Qtd</th></tr></thead><tbody>' . $html_itens_tabela . '</tbody></table><div class="total-pecas-abaixo-dir">Total neste catálogo = <b>' . $total_pecas_catalogo . '</b></div></div>';
}

// 4. SALVAR NO BANCO DE DADOS
$conn->begin_transaction();
$id_novo_pedido = 0;
try {
    $sql_pedido = "INSERT INTO pedidos (id_usuario, total_pecas, status_pedido) VALUES (?, ?, 'Pendente')";
    $stmt_pedido = $conn->prepare($sql_pedido);
    $stmt_pedido->bind_param("ii", $id_usuario, $total_pecas_geral);
    $stmt_pedido->execute();
    $id_novo_pedido = $conn->insert_id;
    $sql_itens = "INSERT INTO pedido_itens (id_pedido, ref_produto, desc_produto, img_produto, quantidade) VALUES (?, ?, ?, ?, ?)";
    $stmt_itens = $conn->prepare($sql_itens);
    foreach ($itens_para_inserir_bd as $item) {
        $stmt_itens->bind_param("isssi", $id_novo_pedido, $item['ref'], $item['desc'], $item['img'], $item['qtd']);
        $stmt_itens->execute();
    }
    $conn->commit();
    $stmt_pedido->close();
    $stmt_itens->close();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Falha crítica ao salvar o pedido no banco de dados.']));
}

// 5. GERAR O FICHEIRO HTML
$pasta_pedidos = 'pedidos';
if (!is_dir($pasta_pedidos)) { mkdir($pasta_pedidos, 0755, true); }
date_default_timezone_set('America/Sao_Paulo');
$data_arquivo = date('Ymd_His');
$nome_arquivo = "pedido_{$id_novo_pedido}_{$data_arquivo}.html";
$caminho_arquivo = $pasta_pedidos . "/" . $nome_arquivo;

// ======================= CÓDIGO ALTERADO =======================
// Adicionado o link para o estilo_global.css e o novo botão no final do corpo do HTML
$html_final = '<!DOCTYPE html><html lang="pt"><head><meta charset="utf-8"><title>Pedido #'.$id_novo_pedido.'</title><link rel="stylesheet" href="../../estilo_global.css"><style>body{background:#fffbe9;color:#222;font-family:Segoe UI,Arial,sans-serif;margin:0;padding:32px;}h2{color:#a58428;text-align:center;margin-bottom:18px;font-size:2.2em;font-weight:900;}img{width:56px;height:56px;border-radius:9px;border:1.5px solid #f2e7c7;background:#fcf8ec;object-fit:cover;vertical-align:middle;}.tabela-pedido-box{width:100%;max-width:720px;margin:28px auto 2px auto;background:none;}table{border-collapse:collapse;width:100%;background:#fff;border-radius:12px;box-shadow:0 2px 16px #e5d3a87b;}th,td{padding:10px 8px;text-align:center;border-bottom:1px solid #f2e7c7;}th{background:#ffe38d;color:#634600;font-size:1.15em;}tr:last-child td{border-bottom:none;}.ref{font-weight:700;color:#000;} .desc{text-align:left;} .qtd{font-weight:600;color:#3b1c01;}.nomecat{display:block;text-align:center;color:#be952d;font-size:1.13em;font-weight:700;margin-top:7px;}.total-pecas-abaixo-dir{width:100%;font-weight:700;color:#000;text-align:right;margin-top:6px;padding-right:2px;}.total-geral{width:100%;max-width:720px;margin:30px auto;text-align:right;font-size:1.3em;font-weight:bold;color:#a58428;}</style></head><body><h2>Resumo do Pedido #'.$id_novo_pedido.'</h2>' . $html_blocos_catalogo . '<div class="total-geral">TOTAL GERAL DE PEÇAS = <b>' . $total_pecas_geral . '</b></div><a href="../../index.html" class="botao-padrao botao-whatsapp botao-piscando" style="text-decoration: none; margin-top: 30px;">Voltar à Página Inicial</a><p style="text-align:center;font-size:1em;color:#866404;margin-top:44px;">Pedido gerado em ' . date('d/m/Y H:i:s') . '</p></body></html>';
// =================================================================

if (file_put_contents($caminho_arquivo, $html_final)) {
    $link_pedido = rtrim($url_base_site, '/') . "/" . $caminho_arquivo;
    $assunto = "Novo Pedido Recebido (#{$id_novo_pedido}) - " . date('d/m/Y');
    $mensagem_email = "<html><body><h2>Novo pedido #{$id_novo_pedido} recebido!</h2><p>Acesse o pedido no link: <a href='$link_pedido'>$link_pedido</a></p></body></html>";
    $cabecalhos = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: Pedidos Atacado Semijoias <$remetente_email>\r\n";
    @mail($destinatario_email, $assunto, $mensagem_email, $cabecalhos);
    
    // 6. DEVOLVER A RESPOSTA DE SUCESSO
    echo json_encode(['status' => 'sucesso', 'pedido_id' => $id_novo_pedido, 'url_pedido' => $link_pedido]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Pedido salvo no banco, mas falha ao criar o ficheiro HTML no servidor.']);
}
?>