<?php
// Arquivo: carregar_pedido.php (VERSÃO CORRIGIDA COM AGRUPAMENTO POR CATEGORIA)

session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

// Valida se o utilizador está logado e se o ID do pedido foi fornecido
if (!isset($_SESSION['loggedin']) || !isset($_GET['id_pedido'])) {
    http_response_code(403);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado ou ID do pedido não fornecido.']));
}

$id_pedido_carregar = intval($_GET['id_pedido']);
$id_usuario = intval($_SESSION['id_usuario']);

// Validação dos IDs
if ($id_pedido_carregar <= 0 || $id_usuario <= 0) {
    http_response_code(400);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'ID de pedido ou de usuário inválido.']));
}

// Mapeamento de prefixos para nomes de catálogo (igual ao do Python)
$mapeamento_catalogos = [
    "BCL" => "braceletes", "BAG" => "brincos_argolas", "AGF" => "brincos_argolas_fio",
    "BCA" => "brincos_cartilagem", "BGR" => "brincos_grandes", "BMX" => "brincos_max",
    "BMD" => "brincos_medios", "BPD" => "brincos_pequenos", "CCH" => "colares_choker",
    "CJT" => "conjuntos_delicados", "CJP" => "conjuntos_zirconias", "KPL" => "pulseiras_kit",
    "PMC" => "pulseiras_masculinas", "KTZ" => "tornozeleiras_kit",
];

// Garante que o pedido pertence ao utilizador
$sql_check = "SELECT id FROM pedidos WHERE id = ? AND id_usuario = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_pedido_carregar, $id_usuario);
$stmt_check->execute();
$stmt_check->store_result();
if ($stmt_check->num_rows !== 1) {
    $stmt_check->close();
    $conn->close();
    http_response_code(404);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Pedido não encontrado ou não pertence a este usuário.']));
}
$stmt_check->close();


// Busca os itens do pedido
$pedidos_agrupados = [];
$sql_itens = "SELECT ref_produto, desc_produto, img_produto, quantidade FROM pedido_itens WHERE id_pedido = ?";
if ($stmt_itens = $conn->prepare($sql_itens)) {
    $stmt_itens->bind_param("i", $id_pedido_carregar);
    $stmt_itens->execute();
    $resultado = $stmt_itens->get_result();

    // Loop para agrupar os itens por catálogo
    while ($item = $resultado->fetch_assoc()) {
        $prefixo = substr($item['ref_produto'], 0, 3);
        $catalogo_nome = isset($mapeamento_catalogos[$prefixo]) ? $mapeamento_catalogos[$prefixo] : 'outros';
        
        if (!isset($pedidos_agrupados[$catalogo_nome])) {
            $pedidos_agrupados[$catalogo_nome] = [
                'catalogo' => $catalogo_nome,
                'itens' => []
            ];
        }
        
        $pedidos_agrupados[$catalogo_nome]['itens'][] = [
            'ref' => $item['ref_produto'],
            'desc' => $item['desc_produto'],
            'img' => $item['img_produto'],
            'qtd' => $item['quantidade']
        ];
    }
    $stmt_itens->close();
} else {
    $conn->close();
    http_response_code(500);
    exit(json_encode(['status' => 'erro', 'mensagem' => 'Falha ao preparar a consulta de itens do pedido.']));
}

// Converte o array agrupado para o formato final que o JavaScript espera
$carrinho_final = array_values($pedidos_agrupados);

echo json_encode(['status' => 'sucesso', 'carrinho' => $carrinho_final]);
$conn->close();
?>