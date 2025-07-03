function renderPedidoFinal() {
    let pedidos = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]');
    const areaPedidos = document.getElementById('areaPedidos');
    const totalGeralEl = document.getElementById('totalGeral');
    let totalGeral = 0;
    areaPedidos.innerHTML = '';
    if (!pedidos.length) {
        areaPedidos.innerHTML = '<div class="mensagem-vazio">Não há pedido para mostrar.</div>';
        return;
    }
    pedidos.forEach(function(pedido) {
        let totalPecasCatalogo = 0;
        let itensHtml = '';
        (pedido.itens || []).forEach(function(item) {
            const qtdNum = parseInt(item.qtd, 10) || 0;
            totalPecasCatalogo += qtdNum;
            itensHtml += `<div class="item-resumo"><div class="item-info"><div class="item-desc">${item.desc}</div><div class="item-ref">Ref: ${item.ref}</div><div class="item-qtd">Quantidade: ${qtdNum}</div></div></div>`;
        });
        totalGeral += totalPecasCatalogo;
        areaPedidos.innerHTML += `<div class="bloco-catalogo"><div class="catalogo-titulo">${pedido.catalogo.replace(/_/g, ' ').toUpperCase()}</div><div class="lista-itens-catalogo">${itensHtml}</div><div class="total-pecas-bloco">Total de peças deste catálogo: ${totalPecasCatalogo}</div></div>`;
    });
    totalGeralEl.innerText = "Total geral de peças: " + totalGeral;
}

function gerarResumoPedidoTexto() {
    let pedidos = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]');
    if (!pedidos.length) return '';
    let texto = "Resumo de Pedido - Atacado de Semijoias\n\n";
    pedidos.forEach(function(pedido) {
        texto += `*Catálogo: ${pedido.catalogo.replace(/_/g, ' ').toUpperCase()}*\n`;
        (pedido.itens || []).forEach(function(item) {
            texto += `• ${item.desc} (Ref: ${item.ref}) - Qtd: ${item.qtd}\n`;
        });
        let totalPecas = (pedido.itens || []).reduce((soma, item) => soma + (parseInt(item.qtd,10)||0), 0);
        texto += `_Total de peças deste catálogo: ${totalPecas}_\n\n`;
    });
    let totalGeral = pedidos.reduce((soma, p) => soma + (p.itens || []).reduce((s, i) => s + (parseInt(i.qtd,10)||0), 0), 0);
    texto += `*Total geral de peças: ${totalGeral}*`;
    return texto;
}

document.addEventListener("DOMContentLoaded", function() {
    renderPedidoFinal();
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const partilharBtn = document.getElementById('partilharBtn');
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', function() { window.print(); });
    }
    if (partilharBtn) {
        partilharBtn.addEventListener('click', function() {
            const resumoTexto = gerarResumoPedidoTexto();
            if (navigator.share) {
                navigator.share({ title: 'Resumo de Pedido', text: resumoTexto, url: window.location.href }).catch(console.error);
            } else {
                navigator.clipboard.writeText(resumoTexto).then(function() {
                    alert('Resumo do pedido copiado para a área de transferência! Agora pode colá-lo onde quiser.');
                }, function(err) {
                    alert('Não foi possível copiar o texto. Por favor, selecione e copie manually.');
                });
            }
        });
    }
});