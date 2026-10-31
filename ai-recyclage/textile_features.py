"""
Extraction des caractéristiques (features) d'un lot textile.

Transforme les données brutes saisies dans TextileCycle
(composition en texte libre, poids, état et type du don rattaché)
en un vecteur numérique exploitable par le modèle.
"""

from __future__ import annotations

import math
import re
import unicodedata

# ---------------------------------------------------------------------------
# Fibres reconnues et synonymes (FR / EN / noms commerciaux)
# ---------------------------------------------------------------------------
FIBRES = [
    "coton", "polyester", "elasthanne", "laine", "viscose",
    "nylon", "acrylique", "lin", "soie", "cachemire", "autre",
]

SYNONYMES = {
    "coton": "coton", "cotton": "coton", "co": "coton",
    "polyester": "polyester", "poly": "polyester", "pes": "polyester", "pet": "polyester",
    "elasthanne": "elasthanne", "elastane": "elasthanne", "spandex": "elasthanne",
    "lycra": "elasthanne", "ea": "elasthanne", "el": "elasthanne",
    "laine": "laine", "wool": "laine", "merinos": "laine", "wo": "laine",
    "viscose": "viscose", "rayonne": "viscose", "rayon": "viscose", "modal": "viscose",
    "lyocell": "viscose", "tencel": "viscose", "cv": "viscose",
    "nylon": "nylon", "polyamide": "nylon", "pa": "nylon",
    "acrylique": "acrylique", "acrylic": "acrylique", "pc": "acrylique",
    "lin": "lin", "linen": "lin", "li": "lin",
    "soie": "soie", "silk": "soie", "se": "soie",
    "cachemire": "cachemire", "cashmere": "cachemire",
}

SYNTHETIQUES = ("polyester", "elasthanne", "nylon", "acrylique")
NATURELLES = ("coton", "laine", "lin", "soie", "cachemire")
NOBLES = ("laine", "lin", "soie", "cachemire")

MOTS_INCONNUS = ("inconnu", "inconnue", "melange", "divers", "non etiquete", "sans etiquette", "?")

# ---------------------------------------------------------------------------
# État et type de vêtement (valeurs utilisées dans le module M1)
# ---------------------------------------------------------------------------
ETATS = {
    "neuf": 4.0,
    "tres bon": 3.0, "bon etat": 3.0, "bon_etat": 3.0,
    "bon": 2.0,
    "usage": 1.0, "use": 1.0, "a reparer": 1.0, "abime": 1.0,
    "a recycler": 0.0, "a_recycler": 0.0, "hors d'usage": 0.0, "dechire": 0.0,
}

FAMILLES = {
    "tshirt":   ("t-shirt", "tshirt", "tee", "debardeur", "polo", "top"),
    "jean":     ("jean", "denim", "pantalon", "short", "jupe"),
    "chemise":  ("chemise", "chemisier", "blouse"),
    "pull":     ("pull", "sweat", "gilet", "tricot", "cardigan"),
    "veste":    ("veste", "manteau", "blouson", "parka", "doudoune", "costume", "blazer"),
    "robe":     ("robe",),
    "sport":    ("sport", "legging", "jogging", "survetement", "maillot"),
    "linge":    ("drap", "serviette", "linge", "nappe", "rideau", "couverture", "housse"),
}
TYPES = list(FAMILLES) + ["inconnu"]


def _normaliser(texte: str | None) -> str:
    texte = (texte or "").strip().lower()
    texte = unicodedata.normalize("NFKD", texte)
    return "".join(c for c in texte if not unicodedata.combining(c))


def analyser_composition(composition: str | None) -> tuple[dict[str, float], list[str]]:
    """
    "60% coton, 35% polyester, 5% élasthanne" → {"coton": .60, "polyester": .35, "elasthanne": .05, ...}

    Gère : pourcentages avant ou après la fibre, décimales, synonymes,
    fibres sans pourcentage (réparties équitablement), compositions inconnues.
    Retourne aussi la liste des avertissements de qualité de saisie.
    """
    parts = {f: 0.0 for f in FIBRES}
    avertissements: list[str] = []
    texte = _normaliser(composition)

    if not texte or any(m in texte for m in MOTS_INCONNUS):
        parts["autre"] = 1.0
        avertissements.append("Composition inconnue ou non étiquetée.")
        return parts, avertissements

    texte = texte.replace(",", " , ").replace("/", " / ").replace("+", " + ")
    jetons = re.findall(r"\d+(?:[.,]\d+)?\s*%|[a-z]+", texte)

    trouves: list[tuple[str, float | None]] = []
    pct_en_attente: float | None = None
    non_reconnues: list[str] = []
    for jeton in jetons:
        if jeton.endswith("%"):
            pct_en_attente = float(jeton.rstrip("% ").replace(",", "."))
            if trouves and trouves[-1][1] is None:
                # format "coton 60%"
                trouves[-1] = (trouves[-1][0], pct_en_attente)
                pct_en_attente = None
            continue
        fibre = SYNONYMES.get(jeton)
        if fibre is None:
            if len(jeton) > 3 and jeton not in ("pure", "recycle", "bio", "et", "and"):
                non_reconnues.append(jeton)
            continue
        trouves.append((fibre, pct_en_attente))
        pct_en_attente = None

    if not trouves:
        parts["autre"] = 1.0
        avertissements.append("Aucune fibre reconnue dans la composition.")
        return parts, avertissements

    connus = sum(p for _, p in trouves if p is not None)
    sans_pct = [f for f, p in trouves if p is None]
    reste = max(0.0, 100.0 - connus)
    for fibre, pct in trouves:
        valeur = pct if pct is not None else (reste / len(sans_pct) if sans_pct else 0.0)
        parts[fibre] += valeur

    total = sum(parts.values())
    if connus and not sans_pct and abs(connus - 100) > 2:
        avertissements.append(f"Le total de la composition fait {connus:g} % au lieu de 100 %.")
        if connus < 100:
            parts["autre"] += 100 - connus
            total = 100.0
    if non_reconnues:
        avertissements.append("Termes non reconnus : " + ", ".join(sorted(set(non_reconnues))) + ".")

    return {f: v / total for f, v in parts.items()}, avertissements


def score_etat(etat: str | None) -> float:
    """Score ordinal 0 (à recycler) → 4 (neuf). NaN si inconnu (aucun don rattaché)."""
    e = _normaliser(etat).replace("-", " ")
    if not e:
        return math.nan
    if e in ETATS:
        return ETATS[e]
    for cle, val in sorted(ETATS.items(), key=lambda kv: -len(kv[0])):
        if cle in e:
            return val
    return math.nan


def famille_type(type_vetement: str | None) -> str:
    t = _normaliser(type_vetement)
    for famille, mots in FAMILLES.items():
        if any(m in t for m in mots):
            return famille
    return "inconnu"


# ---------------------------------------------------------------------------
# Vecteur de caractéristiques
# ---------------------------------------------------------------------------
NOMS_FEATURES = (
    [f"part_{f}" for f in FIBRES]
    + ["part_synthetique", "part_naturelle", "part_noble", "purete", "nb_fibres",
       "poids_kg", "log_poids", "etat_score", "etat_connu"]
    + [f"type_{t}" for t in TYPES]
)

# Regroupement pour l'explication lisible (un groupe = une raison affichée)
GROUPES = {
    **{f"part_{f}": f for f in FIBRES},
    "part_synthetique": "synthetique", "part_naturelle": "naturelle", "part_noble": "noble",
    "purete": "melange", "nb_fibres": "melange",
    "poids_kg": "poids", "log_poids": "poids",
    "etat_score": "etat", "etat_connu": "etat",
    **{f"type_{t}": "type" for t in TYPES},
}


def construire_features(composition: str | None, poids_kg: float,
                        etat: str | None = None, type_vetement: str | None = None) -> tuple[dict, list[str], dict]:
    parts, avertissements = analyser_composition(composition)
    etat_val = score_etat(etat)
    famille = famille_type(type_vetement)

    significatives = [v for v in parts.values() if v >= 0.03]
    x = {f"part_{f}": parts[f] for f in FIBRES}
    x.update({
        "part_synthetique": sum(parts[f] for f in SYNTHETIQUES),
        "part_naturelle": sum(parts[f] for f in NATURELLES),
        "part_noble": sum(parts[f] for f in NOBLES),
        "purete": max(parts.values()),
        "nb_fibres": float(len(significatives)),
        "poids_kg": float(poids_kg),
        "log_poids": math.log1p(max(float(poids_kg), 0.0)),
        "etat_score": etat_val,
        "etat_connu": 0.0 if math.isnan(etat_val) else 1.0,
    })
    x.update({f"type_{t}": 1.0 if t == famille else 0.0 for t in TYPES})

    contexte = {"fibres": {f: round(v, 3) for f, v in parts.items() if v > 0},
                "etat_score": None if math.isnan(etat_val) else etat_val,
                "famille": famille}
    return x, avertissements, contexte
