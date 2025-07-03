<?php
// Arquivo: servico_sms.php (VERSÃO FINAL COM PARÂMETRO UTF-8)

/**
 * Envia uma mensagem SMS usando a API da SpeedMarket.
 *
 * @param string $numero   Número de destino (DDD + número, ex: 11999999999).
 * @param string $mensagem Texto da mensagem a ser enviada.
 * @return array Retorna array com 'success' (bool) e 'mensagem' (texto de retorno da API).
 */
function enviarSMS_SpeedMarket($numero, $mensagem) {
    // Confirme que suas credenciais reais estão inseridas aqui.
    $usuario = 'jufolheados';
    $senha   = 'Mldf07032022@';

    // ======================= CORREÇÃO IMPORTANTE =======================
    // Dados para a API, agora incluindo o parâmetro utf8=1 para acentuação.
    $dados = [
        'type'         => 1,          // 1 = SMS Interativo (Modo Flash)
        'country_code' => 55,         // Brasil
        'number'       => $numero,
        'content'      => $mensagem,
        'utf8'         => 1           // PARÂMETRO ADICIONADO
    ];
    // ===================================================================

    $payload = http_build_query($dados);
    $authKey = base64_encode($usuario . ':' . $senha);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://54.233.99.254/webservice-rest/send-single');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . $authKey
    ]);

    $responseJson = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respostaApi = json_decode($responseJson, true);

    if ($httpCode === 200 && isset($respostaApi['success']) && $respostaApi['success'] === true) {
        return [
            'success'  => true,
            'mensagem' => $respostaApi['responseDescription'] ?? 'Enviado com sucesso',
            'id'       => $respostaApi['id'] ?? null
        ];
    } else {
        return [
            'success'  => false,
            'mensagem' => $respostaApi['responseDescription'] ?? 'Erro desconhecido ao enviar SMS',
            'codigo'   => $respostaApi['responseCode'] ?? null
        ];
    }
}
?>