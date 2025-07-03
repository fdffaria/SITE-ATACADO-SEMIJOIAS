import pandas as pd
import sys

# =================================================
# ARQUIVO 1: CONFIGURAÇÕES E FUNÇÕES AUXILIARES
# =================================================

# --- CONFIGURAÇÕES GLOBAIS ---

CSV_FILE = "catalogos_final.csv"
NUMERO_WHATSAPP = "5519993343060"

CATALOGOS = [
    ("BCL", "braceletes"),
    ("BAG", "brincos_argolas"),
    ("AGF", "brincos_argolas_fio"),
    ("BCA", "brincos_cartilagem"),
    ("BGR", "brincos_grandes"),
    ("BMX", "brincos_max"),
    ("BMD", "brincos_medios"),
    ("BPD", "brincos_pequenos"),
    ("CCH", "colares_choker"),
    ("CJT", "conjuntos_delicados"),
    ("CJP", "conjuntos_zirconias"),
    ("KPL", "pulseiras_kit"),
    ("PMC", "pulseiras_masculinas"),
    ("KTZ", "tornozeleiras_kit"),
]

# --- ESTRUTURA PARA O MENU DA HOME PAGE ---
MENU_HOME = [
    ("BRACELETES", [
        ("Braceletes", "braceletes.html"),
    ]),
    ("BRINCOS", [
        ("Argolas", "brincos_argolas.html"),
        ("Argolas de Fio", "brincos_argolas_fio.html"),
        ("Cartilagem", "brincos_cartilagem.html"),
        ("Grandes", "brincos_grandes.html"),
        ("Max", "brincos_max.html"),
        ("Médios", "brincos_medios.html"),
        ("Pequenos", "brincos_pequenos.html"),
    ]),
    ("COLARES", [
        ("Choker", "colares_choker.html"),
    ]),
    ("CONJUNTOS", [
        ("Delicados", "conjuntos_delicados.html"),
        ("Zircônias", "conjuntos_zirconias.html"),
    ]),
    ("PULSEIRAS", [
        ("Kit Pulseiras", "pulseiras_kit.html"),
        ("Masculinas", "pulseiras_masculinas.html"),
    ]),
    ("TORNOZELEIRAS", [
        ("Kit Tornozeleiras", "tornozeleiras_kit.html"),
    ]),
]

# --- FUNÇÕES AUXILIARES ---

def validar_colunas(df, colunas_obrigatorias):
    """Verifica se o DataFrame contém todas as colunas necessárias."""
    faltando = [col for col in colunas_obrigatorias if col not in df.columns]
    if faltando:
        print(f"\nERRO: O arquivo CSV está sem as seguintes colunas obrigatórias: {', '.join(faltando)}")
        print("Colunas encontradas no CSV:")
        print(list(df.columns))
        sys.exit(1)

def get_primeira_url_imagem(campo):
    """Extrai a primeira URL de um campo que pode ter múltiplas URLs separadas."""
    if pd.isna(campo):
        return ""
    for sep in [';', ',', ' ']:
        if sep in str(campo):
            return str(campo).split(sep)[0].strip()
    return str(campo).strip()