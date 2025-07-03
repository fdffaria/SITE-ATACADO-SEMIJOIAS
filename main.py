import pandas as pd
import sys
import os
import shutil
from jinja2 import Environment, FileSystemLoader, select_autoescape

from config_e_helpers import CATALOGOS, CSV_FILE, NUMERO_WHATSAPP, validar_colunas, MENU_HOME
from geradores_html import gerar_html_catalogo, gerar_html_revisar_pedido, gerar_html_carrinho
from gerador_home import gerar_html_home

def executar_geracao():
    try:
        df = pd.read_csv(CSV_FILE, sep=';', encoding="utf-8")
    except FileNotFoundError:
        print(f"\nERRO: O arquivo '{CSV_FILE}' não foi encontrado.")
        sys.exit(1)
    except Exception as e:
        print(f"\nERRO ao ler o arquivo CSV: {e}")
        sys.exit(1)

    colunas_obrigatorias = ["Código", "Descrição", "URL Imagens Externas"]
    validar_colunas(df, colunas_obrigatorias)

    pasta_destino = "site_gerado"
    if os.path.exists(pasta_destino):
        shutil.rmtree(pasta_destino)
    os.makedirs(pasta_destino, exist_ok=True)

    env = Environment(loader=FileSystemLoader("templates"), autoescape=select_autoescape(['html', 'xml']))

    # ======================= BLOCO ATUALIZADO =======================
    # Lista completa dos arquivos estáticos (CSS, JS, PHP, integração SMS/e-mail)
    arquivos_estaticos = [
        "estilo_global.css", "estilo_header.css", "estilo_catalogo.css",
        "estilo_revisao.css", "estilo_carrinho.css", "script_header.js",
        "script_catalogo.js", "script_revisao.js", "script_carrinho.js",
        "salvar_pedido.php", "conexao.php", "logout.php", "verificar_sessao.php",
        "minha_conta.php", "carregar_pedido.php",
        "enviar_link.php", "validar_link.php",
        "enviar_codigo.php", "validar_codigo.php",
        "servico_sms.php",
        # NOVOS FICHEIROS PARA VALIDAÇÃO DE TELEFONE ADICIONADOS
        "enviar_validacao_sms.php", "validar_telefone.php"
    ]
    # ===============================================================

    for nome_arquivo in arquivos_estaticos:
        caminho_origem = ""
        if os.path.exists(nome_arquivo):
            caminho_origem = nome_arquivo
        elif os.path.exists(os.path.join("templates", nome_arquivo)):
            caminho_origem = os.path.join("templates", nome_arquivo)
        else:
            print(f"\nAVISO: O arquivo estático '{nome_arquivo}' não foi encontrado.")
            continue

        try:
            shutil.copy(caminho_origem, os.path.join(pasta_destino, nome_arquivo))
        except Exception as e:
            print(f"\nAVISO: Falha ao copiar o arquivo '{nome_arquivo}'. Erro: {e}")

    print("Copiados arquivos estáticos (CSS/JS/PHP).")

    # =========== Bloco para cópia de bibliotecas externas ===========
    # Copia a pasta PHPMailer (usada para envio de e-mail)
    pasta_phpmailer_origem = "PHPMailer"
    if os.path.exists(pasta_phpmailer_origem) and os.path.isdir(pasta_phpmailer_origem):
        pasta_phpmailer_destino = os.path.join(pasta_destino, pasta_phpmailer_origem)
        try:
            shutil.copytree(pasta_phpmailer_origem, pasta_phpmailer_destino)
            print("Copiada a pasta 'PHPMailer' com sucesso.")
        except Exception as e:
            print(f"\nERRO: Falha ao copiar a pasta 'PHPMailer'. Erro: {e}")
    else:
        print("\nAVISO CRÍTICO: A pasta 'PHPMailer' não foi encontrada. O envio de e-mail não irá funcionar.")

    # Se precisar copiar outras bibliotecas no futuro (ex: para SMS), siga o mesmo padrão acima.
    # ===============================================================

    # Geração das páginas dos catálogos
    for prefixo, nome_cat in CATALOGOS:
        df_cat = df[df["Código"].str.startswith(prefixo, na=False)].copy()
        if df_cat.empty:
            continue
        TITULO_CATALOGO = nome_cat.replace('_', ' ').title()

        html_catalogo = gerar_html_catalogo(env, df=df_cat, titulo_catalogo=TITULO_CATALOGO, nome_cat=nome_cat, profundidade_relativa='')
        with open(os.path.join(pasta_destino, f"{nome_cat}.html"), "w", encoding="utf-8") as f:
            f.write(html_catalogo)

        pasta_revisar = os.path.join(pasta_destino, nome_cat)
        os.makedirs(pasta_revisar, exist_ok=True)
        html_revisar = gerar_html_revisar_pedido(env, titulo_catalogo=TITULO_CATALOGO, nome_cat=nome_cat, profundidade_relativa='../')
        with open(os.path.join(pasta_revisar, f"Revisar_Pedido_{nome_cat}.html"), "w", encoding="utf-8") as f:
            f.write(html_revisar)

    # Geração das páginas fixas que usam templates
    paginas_fixas_templates = {
        "index.html": {},
        "carrinho.html": {"numero_whatsapp": NUMERO_WHATSAPP},
        "login.html": {},
    }

    for pagina, extras in paginas_fixas_templates.items():
        print(f"A gerar página {pagina}...")
        template = env.get_template(pagina)
        render_vars = {
            "menu_estrutura": MENU_HOME,
            "profundidade_relativa": '',
            **extras
        }
        html_pagina = template.render(render_vars)
        with open(os.path.join(pasta_destino, pagina), "w", encoding="utf-8") as f:
            f.write(html_pagina)

    print(f"\nProcesso concluído! O site foi gerado na pasta '{pasta_destino}'.")

if __name__ == "__main__":
    executar_geracao()