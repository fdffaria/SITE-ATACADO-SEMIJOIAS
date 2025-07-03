// VERSÃO FINAL COM TODAS AS FUNCIONALIDADES CORRIGIDAS E ESTÁVEIS

function renderCarrinho() {
    let pedidos = [];
    try { pedidos = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]'); if (!Array.isArray(pedidos)) pedidos = []; } catch (e) { pedidos = []; }
    const areaPedidos = document.getElementById('areaPedidos');
    const totalGeralEl = document.getElementById('totalGeral');
    const areaBotoesFinal = document.getElementById('areaBotoesFinal');
    let totalGeral = 0;
    areaPedidos.innerHTML = '';
    
    // Limpa os botões adicionados dinamicamente e a classe de estado
    if (areaBotoesFinal) {
        const botoesDinamicos = areaBotoesFinal.querySelectorAll('.botao-dinamico');
        botoesDinamicos.forEach(b => b.remove());
        areaBotoesFinal.classList.remove('pedido-enviado');
    }

    document.getElementById('enviarWhatsapp').style.display = 'flex';
    document.getElementById('voltarCatalogoAnterior').style.display = 'flex';
    document.getElementById('visualizarPedido').style.display = 'none';
    document.getElementById('mostrarCatalogosBtn').style.display = 'flex';

    if (!pedidos.length) {
        areaPedidos.innerHTML = '<div class="mensagem-vazio">Nenhum pedido foi adicionado ao carrinho. <br>Navegue pelos catálogos e adicione seus itens.</div>';
        totalGeralEl.innerText = '';
        if (areaBotoesFinal) areaBotoesFinal.style.display = 'none';
        window.dispatchEvent(new CustomEvent('cartUpdated'));
        return;
    }
    if (areaBotoesFinal) areaBotoesFinal.style.display = 'flex';
    pedidos.forEach(function(pedido) {
        let totalPecasCatalogo = 0;
        let itensHtml = '';
        (pedido.itens || []).forEach(function(item) {
            const qtdNum = parseInt(item.qtd, 10) || 1;
            totalPecasCatalogo += qtdNum;
            itensHtml += `<div class="item-resumo" data-catalogo="${pedido.catalogo}" data-ref="${item.ref}"><div class="item-img-container"><img src="${item.img}" alt="${item.desc}" class="item-img"><button class="btn-excluir-item" title="Remover item">&times;</button></div><div class="item-info"><div class="item-desc">${item.desc}</div><div class="item-ref">Ref: ${item.ref}</div><div class="counter-outer"><div class="counter-group"><button class="counter-btn" data-action="decrease" tabindex="-1">–</button><input type="text" class="counter-value" value="${qtdNum}" aria-label="Quantidade"><button class="counter-btn" data-action="increase" tabindex="-1">+</button></div></div></div></div>`;
        });
        totalGeral += totalPecasCatalogo;
        areaPedidos.innerHTML += `<div class="bloco-catalogo"><div class="catalogo-titulo">${pedido.catalogo.replace(/_/g, ' ').replace('Pedido Recarregado ', '#').toUpperCase()}</div><div class="lista-itens-catalogo">${itensHtml}</div><div class="total-pecas-bloco">Total neste catálogo: ${totalPecasCatalogo}</div></div>`;
    });
    totalGeralEl.innerText = "Total geral de peças: " + totalGeral;
    window.dispatchEvent(new CustomEvent('cartUpdated'));
    const voltarBtn = document.getElementById('voltarCatalogoAnterior');
    if (voltarBtn && pedidos.length > 0) {
        const ultimoPedido = pedidos[pedidos.length - 1];
        if (ultimoPedido.catalogo.includes('recarregado')) {
            voltarBtn.style.display = 'none';
        } else {
            voltarBtn.textContent = 'Voltar ao Catálogo Anterior';
            voltarBtn.onclick = function() { window.location.href = ultimoPedido.catalogo + '.html'; };
            voltarBtn.style.display = 'flex';
        }
    }
}

function adicionarLogicaDeInteracao() {
    const areaPedidos = document.getElementById('areaPedidos');
    if (!areaPedidos) return;
    areaPedidos.addEventListener('click', function(event) {
        const target = event.target;
        const itemResumo = target.closest('.item-resumo');
        if (target.classList.contains('item-img')) { showImgZoom(target.src, target.alt); return; }
        if (!itemResumo) return;
        const catalogoId = itemResumo.getAttribute('data-catalogo');
        const refId = itemResumo.getAttribute('data-ref');
        let pedidos = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]');
        const indiceCatalogo = pedidos.findIndex(p => p.catalogo === catalogoId);
        if (indiceCatalogo === -1) return;
        const indiceItem = pedidos[indiceCatalogo].itens.findIndex(i => i.ref === refId);
        if (target.classList.contains('btn-excluir-item')) {
            if (indiceItem > -1) pedidos[indiceCatalogo].itens.splice(indiceItem, 1);
        } else if (target.classList.contains('counter-btn')) {
            if (indiceItem > -1) {
                const acao = target.getAttribute('data-action');
                if (acao === 'increase') pedidos[indiceCatalogo].itens[indiceItem].qtd++;
                else if (acao === 'decrease') pedidos[indiceCatalogo].itens[indiceItem].qtd--;
                if (pedidos[indiceCatalogo].itens[indiceItem].qtd <= 0) pedidos[indiceCatalogo].itens.splice(indiceItem, 1);
            }
        }
        if (pedidos[indiceCatalogo].itens.length === 0) pedidos.splice(indiceCatalogo, 1);
        localStorage.setItem('carrinhoPedidos', JSON.stringify(pedidos));
        renderCarrinho();
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const pedidoIdParaRecarregar = urlParams.get('recarregar_pedido');
    if (pedidoIdParaRecarregar) {
        fetch(`carregar_pedido.php?id_pedido=${pedidoIdParaRecarregar}`).then(response => response.json()).then(data => {
            if (data.status === 'sucesso' && data.carrinho) {
                localStorage.setItem('carrinhoPedidos', JSON.stringify(data.carrinho));
                window.history.replaceState({}, document.title, "carrinho.html");
                renderCarrinho();
            } else { alert('Erro ao carregar o pedido: ' + data.mensagem); renderCarrinho(); }
        }).catch(error => { console.error("Falha ao buscar pedido:", error); alert("Não foi possível carregar os detalhes do pedido."); renderCarrinho(); });
    } else {
        renderCarrinho();
    }
    const enviarPedidoBtn = document.getElementById('enviarWhatsapp');
    const mostrarCatalogosBtn = document.getElementById('mostrarCatalogosBtn'); 
    
    if (enviarPedidoBtn) {
        enviarPedidoBtn.addEventListener('click', function() {
            const pedidos = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]');
            if (!pedidos.length) { alert('Nenhum pedido no carrinho para enviar.'); return; }
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = 'flex';
            
            const whatsappTab = window.open('', '_blank');
            if (whatsappTab) {
                whatsappTab.document.write('Aguarde, a processar o seu pedido...');
            } else {
                loadingOverlay.style.display = 'none';
                alert('O seu navegador bloqueou a abertura de uma nova aba. Por favor, desative o bloqueador de pop-ups para este site.');
                return;
            }

            enviarPedidoBtn.disabled = true;
            
            fetch('salvar_pedido.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(pedidos) })
            .then(response => {
                return response.json().then(data => ({ status_code: response.status, body: data }));
            })
            .then(({ status_code, body }) => {
                if (status_code === 200 && body.status === 'sucesso' && body.url_pedido) {
                    localStorage.removeItem('carrinhoPedidos');
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                    
                    const areaPedidos = document.getElementById('areaPedidos');
                    const totalGeralEl = document.getElementById('totalGeral');
                    const areaBotoesFinal = document.getElementById('areaBotoesFinal');
                    
                    areaPedidos.innerHTML = `<div class="mensagem-sucesso"><h2>Pedido Enviado!</h2><p>O seu pedido #${body.pedido_id} foi recebido e pode ser visualizado a qualquer momento na sua conta.</p></div>`;
                    totalGeralEl.innerHTML = '';
                    
                    document.getElementById('enviarWhatsapp').style.display = 'none';
                    document.getElementById('voltarCatalogoAnterior').style.display = 'none';
                    document.getElementById('mostrarCatalogosBtn').style.display = 'none';
                    
                    const visualizarBtn = document.getElementById('visualizarPedido');
                    visualizarBtn.style.display = 'flex';
                    visualizarBtn.onclick = function() { window.open(body.url_pedido, '_blank'); };

                    const novoPedidoBtn = document.createElement('a');
                    novoPedidoBtn.href = "index.html";
                    novoPedidoBtn.textContent = "Iniciar Novo Pedido";
                    novoPedidoBtn.className = "botao-padrao botao-whatsapp botao-piscando botao-dinamico";
                    areaBotoesFinal.appendChild(novoPedidoBtn);

                    // ======================= ALTERAÇÃO PRINCIPAL =======================
                    // Adiciona uma classe ao contentor dos botões para que o CSS possa estilizá-los
                    areaBotoesFinal.classList.add('pedido-enviado');
                    // =================================================================

                    const frase = "Olá! Segue o link do meu pedido, aguardo o contato para confirmação das peças";
                    const textoParaWhatsApp = encodeURIComponent(`${frase}\n${body.url_pedido}`);
                    const urlWhatsApp = `https://api.whatsapp.com/send?phone=${NUMERO_WHATSAPP}&text=${textoParaWhatsApp}`;
                    
                    whatsappTab.location.href = urlWhatsApp;

                } else if (status_code === 403) {
                    if (whatsappTab) whatsappTab.close();
                    const avisoDiv = document.getElementById('avisoRedirect');
                    if (avisoDiv) {
                        avisoDiv.style.display = 'flex';
                    }
                    setTimeout(() => {
                        window.location.href = `login.html?redirect_url=${encodeURIComponent('carrinho.html')}&reason=checkout`;
                    }, 3500);

                } else {
                    if (whatsappTab) whatsappTab.close();
                    alert('Houve um erro: ' + (body.mensagem || 'Não foi possível completar o pedido.'));
                }
            })
            .catch(error => {
                if (whatsappTab) whatsappTab.close();
                console.error('Erro de rede ou JSON inválido:', error);
                alert('Não foi possível comunicar com o servidor. Verifique sua conexão.');
            })
            .finally(() => {
                loadingOverlay.style.display = 'none';
                if (document.getElementById('enviarWhatsapp').style.display !== 'none') {
                    enviarPedidoBtn.disabled = false;
                }
            });
        });
    }
    
    if (mostrarCatalogosBtn) {
        mostrarCatalogosBtn.addEventListener('click', function() {
            const abrirMenuBtn = document.getElementById('abrirMenu');
            if (abrirMenuBtn) { abrirMenuBtn.click(); }
        });
    }

    adicionarLogicaDeInteracao();
});