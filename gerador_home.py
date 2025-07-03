# =================================================
# ARQUIVO: gerador_home.py (Refatorado com Jinja2)
# =================================================

# Não precisamos mais importar o gerador de cabeçalho daqui.

def gerar_html_home(env, menu_estrutura):
    """
    Gera o código HTML para a página inicial usando um template Jinja2.
    """
    template = env.get_template("home.html")
    
    # A página home só precisa da estrutura do menu para o cabeçalho
    # e a profundidade relativa para os links funcionarem corretamente.
    return template.render(
        menu_estrutura=menu_estrutura,
        profundidade_relativa=''
    )