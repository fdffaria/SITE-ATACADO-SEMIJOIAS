# Arquivo: geradores_html.py (Revertido para usar URLs Externas)
from config_e_helpers import get_primeira_url_imagem, MENU_HOME

def gerar_html_catalogo(env, df, titulo_catalogo, nome_cat, profundidade_relativa=''):
    """Gera o HTML da página de catálogo usando a URL externa do CSV."""
    template = env.get_template("catalogo.html")
    
    produtos = []
    for _, row in df.iterrows():
        # Voltamos a usar a função para pegar a URL diretamente da coluna do CSV
        url_da_imagem = get_primeira_url_imagem(row["URL Imagens Externas"])
        
        produtos.append({
            "cod": str(row["Código"]).strip(),
            "desc": str(row["Descrição"]).strip(),
            "img_url": url_da_imagem # Passamos a URL completa para o template
        })

    return template.render(
        titulo_catalogo=titulo_catalogo,
        produtos=produtos,
        nome_cat=nome_cat,
        menu_estrutura=MENU_HOME,
        profundidade_relativa=profundidade_relativa
    )

def gerar_html_revisar_pedido(env, titulo_catalogo, nome_cat, profundidade_relativa=''):
    template = env.get_template("revisar_pedido.html")
    return template.render(
        titulo_catalogo=titulo_catalogo,
        nome_cat=nome_cat,
        nome_cat_formatado=nome_cat.replace(' ', '_').replace('ç', 'c').replace('ã', 'a'),
        menu_estrutura=MENU_HOME,
        profundidade_relativa=profundidade_relativa
    )

def gerar_html_carrinho(env, numero_whatsapp, profundidade_relativa=''):
    template = env.get_template("carrinho.html")
    return template.render(
        numero_whatsapp=numero_whatsapp,
        menu_estrutura=MENU_HOME,
        profundidade_relativa=profundidade_relativa
    )