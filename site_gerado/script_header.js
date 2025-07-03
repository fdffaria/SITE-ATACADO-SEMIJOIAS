// VERSÃO ATUALIZADA COM MODAL E LÓGICA DE LOGOUT

function verificarLoginStatus() {
    fetch('verificar_sessao.php')
        .then(response => response.json())
        .then(data => {
            const loginAreaHeader = document.getElementById('loginAreaHeader');
            if (!loginAreaHeader) return;

            if (data.logado) {
                const primeiroNome = data.nome.split(' ')[0];
                
                // Atualiza a área de login com saudação e botão SAIR
                loginAreaHeader.innerHTML = `
                    <a href="minha_conta.php" class="login-greeting">
                        <span class="material-icons menu-icon">account_circle</span>
                        <span class="menu-text">Olá, ${primeiroNome}</span>
                    </a>
                    <a href="#" id="logoutBtn" class="logout-link-header">SAIR</a>
                `;
                // Remove o cursor de 'ponteiro' da div principal, já que agora contém links internos
                loginAreaHeader.style.cursor = 'default';

            } else {
                // Garante que a área de login mostre o link padrão
                loginAreaHeader.innerHTML = `
                    <a href="login.html" class="login-greeting">
                        <span class="material-icons menu-icon">account_circle</span>
                        <span class="menu-text">Login</span>
                    </a>
                `;
                loginAreaHeader.style.cursor = 'pointer';
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status de login:', error);
        });
}


const listaDeBusca = [
    { nome: "Braceletes", url: "braceletes.html" }, { nome: "Brincos Argolas", url: "brincos_argolas.html" },
    { nome: "Brincos Argolas de Fio", url: "brincos_argolas_fio.html" }, { nome: "Brincos Cartilagem", url: "brincos_cartilagem.html" },
    { nome: "Brincos Grandes", url: "brincos_grandes.html" }, { nome: "Brincos Max", url: "brincos_max.html" },
    { nome: "Brincos Médios", url: "brincos_medios.html" }, { nome: "Brincos Pequenos", url: "brincos_pequenos.html" },
    { nome: "Colares Choker", url: "colares_choker.html" }, { nome: "Conjuntos Delicados", url: "conjuntos_delicados.html" },
    { nome: "Conjuntos Zircônias", url: "conjuntos_zirconias.html" }, { nome: "Pulseiras Kit", url: "pulseiras_kit.html" },
    { nome: "Pulseiras Masculinas", url: "pulseiras_masculinas.html" }, { nome: "Tornozeleiras Kit", url: "tornozeleiras_kit.html" }
];

function showImgZoom(imgSrc, desc) {
  const modalBg = document.getElementById('imgModalBg');
  if (!modalBg) return; 
  const imgElement = document.getElementById('imgModalImg');
  if(imgElement) imgElement.src = imgSrc;
  modalBg.classList.add('active');
}

function closeImgZoom() {
  const modalBg = document.getElementById('imgModalBg');
  if (modalBg) modalBg.classList.remove('active');
}

window.addEventListener("DOMContentLoaded", function() {
    const abrirMenuBtn = document.getElementById('abrirMenu'), menuLateral = document.getElementById('menuLateral'),
          menuOverlay = document.getElementById('menuOverlay'), fecharMenuBtn = document.getElementById('fecharMenu'),
          abrirMenuProdutos = document.getElementById('abrirMenuProdutos'), abrirLupaBtn = document.getElementById('abrirLupa'),
          modalLupaBg = document.getElementById('modalLupaBg'), fecharLupaBtn = document.getElementById('fecharLupa'),
          modalLupaInput = document.getElementById('modalLupaInput'), searchResultsContainer = document.getElementById('searchResultsContainer'),
          voltarAoTopoBtn = document.getElementById('voltarAoTopoBtn');
          
    // --- ELEMENTOS DO NOVO MODAL ---
    const infoModal = document.getElementById('infoModal');
    const infoModalText = document.getElementById('infoModalText');
    const infoModalClose = document.getElementById('infoModalClose');

    // ======================= BLOCO ADICIONADO =======================
    // Lógica para o processo de logout
    document.body.addEventListener('click', function(event) {
        if (event.target && event.target.id === 'logoutBtn') {
            event.preventDefault(); // Impede que o link '#' mude a URL

            // Faz a chamada ao script de logout no servidor
            fetch('logout.php')
                .then(() => {
                    // Após deslogar, atualiza o texto e exibe o modal
                    if(infoModal && infoModalText) {
                        infoModalText.textContent = "Você não está mais Conectado. Realize o Login para acessar sua Conta.";
                        infoModal.style.display = 'flex';
                    }
                    // Atualiza o header para o estado "deslogado" imediatamente
                    verificarLoginStatus();
                    // Atualiza o contador do carrinho
                    window.dispatchEvent(new CustomEvent('cartUpdated'));
                })
                .catch(err => console.error("Erro ao tentar deslogar:", err));
        }
    });

    // Lógica para fechar o modal de informação
    if(infoModalClose && infoModal) {
        infoModalClose.addEventListener('click', function() {
            infoModal.style.display = 'none';
            // Redireciona para a página inicial
            window.location.href = 'index.html';
        });
    }
    // ===============================================================

  function abrirMenuLateral(submenu) {
    if(menuLateral) menuLateral.classList.add('aberto');
    if(menuOverlay) menuOverlay.classList.add('aberto');
    document.querySelectorAll('.menu-item-av.ativo, .submenu.ativo').forEach(el => el.classList.remove('ativo'));
    if (submenu) {
      const item = document.querySelector(`.menu-item-av[data-menu="${submenu}"]`);
      if(item) {
        item.classList.add('ativo');
        const sub = item.parentElement.querySelector(`.submenu[data-submenu="${submenu}"]`);
        if(sub) sub.classList.add('ativo');
      }
    }
  }

  function atualizarContadorCarrinho() {
      let carrinho = [];
      try { carrinho = JSON.parse(localStorage.getItem('carrinhoPedidos') || '[]'); } catch(e) {}
      const total = carrinho.reduce((s, p) => s + (p.itens || []).reduce((si, i) => si + (parseInt(i.qtd,10)||0), 0), 0);
      const elContador = document.getElementById('carrinhoNum');
      if (elContador) elContador.textContent = total;
  }

  window.addEventListener('cartUpdated', atualizarContadorCarrinho);

  if (abrirMenuBtn) abrirMenuBtn.addEventListener('click', () => abrirMenuLateral());
  if (fecharMenuBtn) fecharMenuBtn.addEventListener('click', () => {
    if(menuLateral) menuLateral.classList.remove('aberto');
    if(menuOverlay) menuOverlay.classList.remove('aberto');
  });
  if (menuOverlay) menuOverlay.addEventListener('click', () => fecharMenuBtn && fecharMenuBtn.click());
  if (abrirMenuProdutos) abrirMenuProdutos.addEventListener('click', () => abrirMenuLateral('brincos'));
  document.querySelectorAll('.menu-item-av').forEach(item => {
    item.addEventListener('click', function(e) {
      e.stopPropagation();
      const menu = this.getAttribute('data-menu'), submenu = this.parentElement.querySelector(`.submenu[data-submenu="${menu}"]`),
            estavaAberto = this.classList.contains('ativo');
      document.querySelectorAll('.menu-item-av.ativo, .submenu.ativo').forEach(el => el.classList.remove('ativo'));
      if (!estavaAberto) { this.classList.add('ativo'); if(submenu) submenu.classList.add('ativo'); }
    });
  });

  if (abrirLupaBtn) abrirLupaBtn.addEventListener('click', () => {
    if(modalLupaBg) modalLupaBg.classList.add('active');
    setTimeout(() => modalLupaInput && modalLupaInput.focus(), 60);
  });
  
  if (fecharLupaBtn) {
    fecharLupaBtn.addEventListener('click', () => {
      if(modalLupaBg) modalLupaBg.classList.remove('active');
      if (modalLupaInput) modalLupaInput.value = "";
      if (searchResultsContainer) searchResultsContainer.innerHTML = "";
    });
  }
  
  if (modalLupaBg) modalLupaBg.addEventListener('click', (e) => {
    if (e.target === modalLupaBg) fecharLupaBtn && fecharLupaBtn.click();
  });
  
  if (typeof Fuse === "function" && modalLupaInput) {
    const fuse = new Fuse(listaDeBusca, { keys: ['nome'], threshold: 0.32 });
    const btnBuscar = document.getElementById('modalLupaBuscar');
    modalLupaInput.addEventListener("input", () => {
      const termo = modalLupaInput.value.trim();
      if (!termo) { searchResultsContainer.innerHTML = ""; return; }
      const res = fuse.search(termo);
      searchResultsContainer.innerHTML = !res.length ? "<div class='search-result-item'>Nenhum resultado.</div>" :
        res.map(i => `<a href="${i.item.url}" class="search-result-item">${i.item.nome}</a>`).join('');
    });
    const buscar = () => {
        const termo = modalLupaInput.value.trim();
        if (!termo) return;
        const res = fuse.search(termo);
        if (res.length > 0) window.location.href = res[0].item.url;
    };
    if (btnBuscar) btnBuscar.addEventListener('click', buscar);
    modalLupaInput.addEventListener("keydown", e => e.key === "Enter" && buscar());
  }

  document.addEventListener('keydown', e => {
    if (e.key === "Escape" || e.key === "Esc") {
      if (modalLupaBg && modalLupaBg.classList.contains('ativo')) fecharLupaBtn && fecharLupaBtn.click();
      closeImgZoom();
    }
  });

  const imgModalBg = document.getElementById('imgModalBg');
  if (imgModalBg) imgModalBg.addEventListener('click', e => {
    if (e.target.id === 'imgModalBg' || e.target.id === 'imgModalClose') closeImgZoom();
  });

  if (voltarAoTopoBtn) {
    window.addEventListener('scroll', () => {
      voltarAoTopoBtn.classList.toggle('visivel', window.scrollY > 300);
    });
    voltarAoTopoBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  atualizarContadorCarrinho();
  
  verificarLoginStatus(); 
});