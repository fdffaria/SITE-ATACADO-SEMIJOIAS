let itensPedido = [];
const resumoDiv = document.getElementById('resumoPedido');

function salvarNoLocalStorage() { localStorage.setItem('itensPedido', JSON.stringify(itensPedido)); }

function atualizarTotal() {
  const total = itensPedido.reduce((acc, item) => acc + (parseInt(item.qtd, 10) || 0), 0);
  const totalEl = document.getElementById('totalPecas');
  if(totalEl) totalEl.textContent = 'TOTAL = ' + total + ' unid.';
  
  const btnAdicionar = document.getElementById('adicionarCarrinho');
  if(btnAdicionar) {
    btnAdicionar.disabled = total === 0;
    btnAdicionar.style.opacity = total === 0 ? '0.5' : '1';
  }
  if (total === 0) resumoDiv.innerHTML = '<p class="mensagem-vazio">Nenhum item selecionado.<br>Clique em "Voltar" para escolher.</p>';
}

function renderizarItens() {
  if(!resumoDiv) return;
  resumoDiv.innerHTML = '';
  if (itensPedido.length === 0) {
      atualizarTotal();
      return;
  }
  itensPedido.forEach(item => {
    const qtdNum = parseInt(item.qtd, 10) || 0;
    
    // --- CORREÇÃO AQUI ---
    // Usamos a URL da imagem diretamente como ela foi salva, sem adicionar nada antes.
    const urlImagem = item.img; 
    // ---------------------

    resumoDiv.innerHTML += `
      <div class="item-resumo" data-ref="${item.ref}">
        <div class="item-img-container">
          <img src="${urlImagem}" alt="${item.desc}" class="item-img">
          <button class="btn-excluir-item" title="Remover item">&times;</button>
        </div>
        <div class="item-info">
          <div class="item-desc">${item.desc}</div>
          <div class="item-ref">Ref: ${item.ref}</div>
          <div class="counter-outer">
            <div class="counter-group">
              <button class="counter-btn" data-action="decrease" tabindex="-1">–</button>
              <input type="text" class="counter-value" value="${qtdNum}" aria-label="Quantidade">
              <button class="counter-btn" data-action="increase" tabindex="-1">+</button>
            </div>
          </div>
        </div>
      </div>`;
  });
  atualizarTotal();
}

function handleInteraction(e) {
    const target = e.target;
    if (target.matches('.counter-btn, .btn-excluir-item, .item-img')) e.preventDefault();
    
    const itemResumo = target.closest('.item-resumo');
    if (!itemResumo) return;

    const ref = itemResumo.getAttribute('data-ref');
    const itemIndex = itensPedido.findIndex(i => i.ref === ref);
    if (itemIndex === -1) return;

    if (target.classList.contains('btn-excluir-item')) {
      itensPedido.splice(itemIndex, 1);
    } else if (target.matches('.counter-btn')) {
      const action = target.getAttribute('data-action');
      if (action === 'increase') itensPedido[itemIndex].qtd++;
      else if (action === 'decrease') itensPedido[itemIndex].qtd--;
      if (itensPedido[itemIndex].qtd <= 0) itensPedido.splice(itemIndex, 1);
    } else if (target.matches('.item-img')) {
      showImgZoom(target.src, itemResumo.querySelector('.item-desc').textContent);
      return; 
    } else if (target.matches('.counter-value')) {
      target.addEventListener('blur', () => {
        let novaQtd = parseInt(target.value.replace(/[^0-9]/g, ''), 10);
        if(isNaN(novaQtd) || novaQtd <= 0) itensPedido.splice(itemIndex, 1);
        else itensPedido[itemIndex].qtd = novaQtd;
        renderizarItens();
        salvarNoLocalStorage();
      }, { once: true });
      return;
    } else {
        return;
    }
    renderizarItens();
    salvarNoLocalStorage();
}

document.addEventListener("DOMContentLoaded", function(){
  try { itensPedido = JSON.parse(localStorage.getItem('itensPedido') || '[]'); } catch (e) { itensPedido = []; }
  renderizarItens();

  if(resumoDiv) {
    resumoDiv.addEventListener('mousedown', handleInteraction);
    resumoDiv.addEventListener('click', e => e.preventDefault());
    resumoDiv.addEventListener('focusin', handleInteraction);
  }

  const adicionarCarrinhoBtn = document.getElementById('adicionarCarrinho');
  if(adicionarCarrinhoBtn) {
    adicionarCarrinhoBtn.addEventListener('click', function() {
      if (itensPedido.length === 0) return alert("Nenhum item para adicionar.");
      let carrinho = [];
      try { carrinho = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]'); } catch(e) { carrinho = []; }
      
      const indiceExistente = carrinho.findIndex(p => p.catalogo === NOME_CAT_FORMATADO);
      if (indiceExistente > -1) {
          if (itensPedido.length > 0) carrinho[indiceExistente].itens = itensPedido;
          else carrinho.splice(indiceExistente, 1);
      } else if (itensPedido.length > 0) {
          carrinho.push({ catalogo: NOME_CAT_FORMATADO, itens: itensPedido });
      }
      localStorage.setItem('carrinhoPedidos', JSON.stringify(carrinho));
      
      window.dispatchEvent(new CustomEvent('cartUpdated'));

      localStorage.removeItem('itensPedido');
      alert(`Pedido do catálogo "${TITULO_CATALOGO}" foi adicionado/atualizado no carrinho!`);
      window.location.href = PROFUNDIDADE_RELATIVA + 'carrinho.html';
    });
  }

  /*
   * ===============================================================
   * CÓDIGO ADICIONADO PARA TORNAR O BOTÃO 'VOLTAR' DINÂMICO
   * ===============================================================
   */
  const voltarBtn = document.getElementById('voltarAdicionarMais');
  if (voltarBtn) {
    // A variável TITULO_CATALOGO é definida no HTML e contém o nome do catálogo
    voltarBtn.textContent = `Voltar e adicionar mais ${TITULO_CATALOGO}`;
  }
});