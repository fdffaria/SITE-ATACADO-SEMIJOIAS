// =================================================================
// ARQUIVO: script_catalogo.js (VERSÃO FINAL E CORRETA)
// =================================================================

function atualizarTotalPecas() {
  let total = 0;
  document.querySelectorAll('.counter-value').forEach(function(input) {
    const qtd = parseInt(input.value, 10);
    if (!isNaN(qtd) && qtd > 0) {
      total += qtd;
    }
  });
  const totalPecasEl = document.getElementById('totalPecas');
  if (totalPecasEl) {
    totalPecasEl.textContent = "TOTAL = " + total + " unid.";
  }
}

function salvarNoLocalStorage() {
  const itens = [];
  document.querySelectorAll('.counter-value[data-ref]').forEach(function(input) {
    const qtd = parseInt(input.value, 10) || 0;
    if (qtd > 0) {
      // Garante que todos os atributos de dados são lidos e salvos
      itens.push({
        ref: input.getAttribute('data-ref'),
        desc: input.getAttribute('data-desc'),
        img: input.getAttribute('data-img'),
        qtd: qtd
      });
    }
  });
  localStorage.setItem('itensPedido', JSON.stringify(itens));
}

function carregarDoLocalStorage() {
  let itens = [];
  let carregadoDoCarrinhoPrincipal = false;

  try {
    const itensSalvos = localStorage.getItem('itensPedido');
    if (itensSalvos && itensSalvos !== '[]') {
        itens = JSON.parse(itensSalvos);
    } else {
      const carrinhoGeral = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]');
      const catalogoNoCarrinho = carrinhoGeral.find(p => p.catalogo === NOME_CATALOGO_ATUAL);
      if (catalogoNoCarrinho && catalogoNoCarrinho.itens) {
          itens = catalogoNoCarrinho.itens;
          carregadoDoCarrinhoPrincipal = true;
      }
    }
  } catch (e) {
    console.error("Erro ao ler dados do localStorage:", e);
    itens = [];
  }

  if (Array.isArray(itens)) {
    itens.forEach(function(item) {
      const input = document.querySelector(`.counter-value[data-ref="${item.ref}"]`);
      if (input) {
        input.value = item.qtd;
      }
    });
  }

  if (carregadoDoCarrinhoPrincipal) {
    salvarNoLocalStorage();
  }

  atualizarTotalPecas();
}

function modificarQuantidade(button) {
    const delta = parseInt(button.getAttribute('data-delta'), 10);
    const group = button.closest('.counter-group');
    const input = group.querySelector('.counter-value');
    
    if (input) {
      let valor = parseInt(input.value || "0", 10) + delta;
      if (isNaN(valor) || valor < 0) valor = 0;
      input.value = valor;
      atualizarTotalPecas();
      salvarNoLocalStorage();
    }
}

document.addEventListener("DOMContentLoaded", function() {
  carregarDoLocalStorage();

  document.querySelectorAll('.counter-btn').forEach(button => {
    button.addEventListener('mousedown', function(event) {
      event.preventDefault();
      modificarQuantidade(this);
    });
  });

  const catalogoGrid = document.getElementById('catalogoGrid');
  if (catalogoGrid) {
    catalogoGrid.addEventListener('click', function(event) {
      if (event.target.classList.contains('card-img')) {
        const card = event.target.closest('.card');
        const desc = card.querySelector('.card-desc').textContent;
        // A função showImgZoom vem do script_header.js
        if(typeof showImgZoom === 'function') {
          showImgZoom(event.target.src, desc);
        }
      }
    });
  }

  document.querySelectorAll('.counter-value').forEach(function(input) {
    input.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
      if (this.value === '') return; // Não faz nada enquanto o utilizador digita
      atualizarTotalPecas();
      salvarNoLocalStorage();
    });
    input.addEventListener('blur', function() {
      if (this.value === '' || parseInt(this.value, 10) === 0) {
        this.value = '0';
      }
      atualizarTotalPecas();
      salvarNoLocalStorage();
    });
  });

  const revisarPedidoBtn = document.getElementById('revisarPedido');
  if (revisarPedidoBtn) {
    revisarPedidoBtn.addEventListener('click', function(event) {
        event.preventDefault();
        salvarNoLocalStorage();
        window.location.href = `${NOME_CATALOGO_ATUAL}/Revisar_Pedido_${NOME_CATALOGO_ATUAL}.html`;
    });
  }
});