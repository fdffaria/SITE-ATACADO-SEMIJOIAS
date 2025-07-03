<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header("location: login.html"); exit; }
require_once 'conexao.php';
$id_usuario = $_SESSION['id_usuario'];

// Busca os dados ATUAIS do usuário, incluindo o telefone
$usuario = null;
$sql_user = "SELECT nome, email, telefone, telefone_validado_em FROM usuarios WHERE id = ?";
if($stmt_user = $conn->prepare($sql_user)){
    $stmt_user->bind_param("i", $id_usuario);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $usuario = $result_user->fetch_assoc();
    $stmt_user->close();
}

$pedidos = [];
$sql = "SELECT id, data_pedido, total_pecas FROM pedidos WHERE id_usuario = ? ORDER BY data_pedido DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($linha = $resultado->fetch_assoc()) { $pedidos[] = $linha; }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Minha Conta - Atacado de Semijoias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilo_global.css">
    <link rel="stylesheet" href="estilo_header.css">
    <style>
        .container-conta { max-width: 800px; margin: 20px auto; padding: 20px; }
        .bloco-conta { background: #f9f9f9; border: 1px solid #eee; border-radius: 12px; padding: 20px; margin-top: 30px; }
        .bloco-conta h2 { margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        .feedback { display: none; padding: 10px; margin-top: 15px; border-radius: 8px; text-align: center; }
        .feedback.sucesso { background-color: #d4edda; color: #155724; }
        .feedback.erro { background-color: #f8d7da; color: #721c24; }
        .tabela-pedidos { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .tabela-pedidos th, .tabela-pedidos td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .tabela-pedidos th { background-color: #f2f2f2; font-weight: bold; }
        .btn-abrir-pedido { padding: 5px 10px; text-decoration: none; background-color: #b49758; color: white !important; border-radius: 5px; font-size: 0.9em; }
    </style>
</head>
<body>
    <header class="topo-menu"></header>
    <div class="page-wrapper">
        <main class="container-conta">
            <h1 class="titulo-catalogo">Minha Conta</h1>
            <p>Olá, <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>! Bem-vindo de volta.</p>

            <div class="bloco-conta">
                <h2>Meu Telefone</h2>
                <?php if ($usuario && $usuario['telefone_validado_em']): ?>
                    <p>Seu telefone <strong><?php echo htmlspecialchars($usuario['telefone']); ?></strong> está validado e pode ser usado para login.</p>
                    <button class="botao-padrao botao-dourado" onclick="document.getElementById('formAdicionarTelefone').style.display='block'; this.style.display='none';">Alterar Telefone</button>
                    <div id="formAdicionarTelefone" style="display:none;">
                <?php else: ?>
                    <p>Cadastre e valide seu telefone para usá-lo como opção de login.</p>
                    <div id="formAdicionarTelefone">
                <?php endif; ?>
                    <form id="formEnviarSmsValidacao">
                        <div class="form-group">
                            <label for="telefone">Celular com DDD</label>
                            <input type="tel" id="telefone" name="telefone" placeholder="Ex: 11999999999" required>
                        </div>
                        <button type="submit" class="botao-padrao botao-whatsapp">Enviar Código SMS</button>
                    </form>
                    </div>

                <div id="etapaValidarTelefone" style="display:none;">
                    <hr>
                    <p>Enviámos um código para o número informado. Insira-o abaixo para validar.</p>
                    <form id="formConfirmarTelefone">
                        <div class="form-group">
                            <label for="codigo_sms">Código de Validação</label>
                            <input type="text" id="codigo_sms" name="codigo_sms" required>
                        </div>
                        <button type="submit" class="botao-padrao botao-dourado">Validar Telefone</button>
                    </form>
                </div>
                <div id="feedbackTelefone" class="feedback"></div>
            </div>

            <a href="index.html" class="botao-padrao botao-whatsapp botao-piscando" style="text-decoration: none; margin-top: 30px;">Voltar à Página Inicial</a>
            <div class="bloco-conta">
                <h2>Meus Pedidos</h2>
                 <?php if (count($pedidos) > 0): ?>
                <table class="tabela-pedidos">
                     <thead>
                        <tr>
                            <th>Nº do Pedido</th>
                            <th>Data</th>
                            <th>Total de Peças</th>
                            <th class="coluna-acoes">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?php echo $pedido['id']; ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($pedido['data_pedido'])); ?></td>
                                <td><?php echo $pedido['total_pecas']; ?></td>
                                <td class="coluna-acoes"><a href="carrinho.html?recarregar_pedido=<?php echo $pedido['id']; ?>" class="btn-abrir-pedido">Abrir Pedido</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                 <?php else: ?>
                <p class="nenhum-pedido">Você ainda não fez nenhum pedido.</p>
                 <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="script_header.js" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formEnviar = document.getElementById('formEnviarSmsValidacao');
        const formValidar = document.getElementById('formConfirmarTelefone');
        const etapaValidar = document.getElementById('etapaValidarTelefone');
        const feedback = document.getElementById('feedbackTelefone');

        formEnviar.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.textContent = 'Aguarde...';

            const formData = new FormData(this);
            fetch('enviar_validacao_sms.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                feedback.textContent = data.mensagem;
                feedback.className = 'feedback ' + (data.status === 'success' ? 'sucesso' : 'erro');
                feedback.style.display = 'block';

                if (data.status === 'success') {
                    etapaValidar.style.display = 'block';
                    document.getElementById('codigo_sms').focus();
                }
                btn.disabled = false;
                btn.textContent = 'Enviar Código SMS';
            });
        });

        formValidar.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.textContent = 'A validar...';

            const formData = new FormData(this);
            formData.append('telefone', document.getElementById('telefone').value); 

            fetch('validar_telefone.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                feedback.textContent = data.mensagem;
                feedback.className = 'feedback ' + (data.status === 'success' ? 'sucesso' : 'erro');
                if(data.status === 'success') {
                    setTimeout(() => window.location.reload(), 2000);
                }
                btn.disabled = false;
                btn.textContent = 'Validar Telefone';
            });
        });
    });
    </script>
</body>
</html>