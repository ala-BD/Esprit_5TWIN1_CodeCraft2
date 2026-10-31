"""
Générateur d'un jeu de données de lots textiles, calibré sur des données réelles
de la filière (pas de dataset public étiqueté « filière » n'existe à ce jour).

Calibrage (sources) :
  - Débouchés des textiles triés en France — Refashion, rapport d'activité 2023 :
      réemploi 58,6 % | recyclage matière 32,5 % (effilochage 22,6 % + chiffons 9,9 %)
      | valorisation énergétique 8,8 % | élimination 0,15 %
  - Composition des textiles post-consommation — « Sorting for Circularity »
    (Fashion for Good / Circle Economy, 21 t analysées) : coton 42 %, mélanges 32 %,
    polycoton 12 % ; TU Wien (Kählig et al., 2024) : ~46 % de mono-matière,
    coton/élasthanne = mélange le plus fréquent.
  - Turku UAS (2017-2018) : environ 1/3 des textiles sans étiquette identifiable.
  - Élasthanne : 1-5 % dans coton/laine, jusqu'à 20 % dans polyester/polyamide ;
    perturbe le recyclage mécanique (bourrage, qualité des fibres) — Polymers 2025.

La décision (étiquette) suit la logique d'un centre de tri :
  1. réemploi si l'état le permet (critère n°1 en pratique),
  2. upcycling pour les matières à valeur (fibres nobles, denim) en état moyen,
  3. recyclage matière si la composition est connue et recyclable,
  4. valorisation énergétique sinon.
Un bruit de décision (désaccord entre trieurs) est ajouté : la précision
maximale atteignable est donc volontairement < 100 %, comme dans la réalité.
"""

from __future__ import annotations

import numpy as np
import pandas as pd

from textile_features import NOMS_FEATURES, construire_features

CLASSES = ["RECYCLAGE_ENERGIE", "RECYCLAGE_FIBRE", "REVENTE", "UPCYCLING"]

# Familles de vêtements : (fréquence, [(probabilité, générateur de composition)])
def _archetypes(rng: np.random.Generator):
    u = rng.uniform
    def mono(f):            return lambda: f"100% {f}"
    def duo(a, b, lo, hi):  return lambda: (lambda p: f"{p}% {a}, {100 - p}% {b}")(int(u(lo, hi)))
    def stretch(base, lo, hi):
        return lambda: (lambda e: f"{100 - e}% {base}, {e}% élasthanne")(int(u(lo, hi)))
    def trio():
        return lambda: (lambda c, e: f"{c}% coton, {100 - c - e}% polyester, {e}% élasthanne")(int(u(45, 70)), int(u(2, 6)))

    return {
        "T-shirt":   (0.20, [(0.55, mono("coton")), (0.30, duo("coton", "polyester", 35, 70)), (0.15, stretch("coton", 3, 8))]),
        "Jean":      (0.14, [(0.25, mono("coton")), (0.55, stretch("coton", 1, 4)), (0.20, trio())]),
        "Chemise":   (0.12, [(0.45, mono("coton")), (0.35, duo("coton", "polyester", 40, 70)), (0.12, mono("lin")), (0.08, mono("viscose"))]),
        "Pull":      (0.12, [(0.18, mono("laine")), (0.30, mono("acrylique")), (0.30, duo("laine", "acrylique", 20, 70)),
                             (0.05, mono("cachemire")), (0.17, mono("coton"))]),
        "Veste":     (0.10, [(0.35, mono("polyester")), (0.28, duo("laine", "polyester", 50, 80)), (0.20, mono("nylon")), (0.17, mono("coton"))]),
        "Robe":      (0.08, [(0.30, mono("viscose")), (0.35, mono("polyester")), (0.20, mono("coton")), (0.05, mono("soie")),
                             (0.10, stretch("polyester", 5, 12))]),
        "Legging sport": (0.10, [(0.60, stretch("polyester", 8, 20)), (0.30, stretch("polyamide", 10, 22)), (0.10, mono("polyester"))]),
        "Drap":      (0.08, [(0.65, mono("coton")), (0.35, duo("coton", "polyester", 50, 70))]),
    }


def _tirer(rng, options):
    probas = np.array([p for p, _ in options])
    return options[rng.choice(len(options), p=probas / probas.sum())][1]()


def _decider(rng, x: dict, etat_reel: float, qualite: float) -> str:
    """Décision du centre de tri à partir des informations COMPLÈTES (dont l'état réel)."""
    # 1. Réemploi / revente : l'état (et la qualité perçue) domine
    # « qualite » = valeur perçue par le trieur (marque, tendance) : non saisie dans l'application
    peu_demande = x["type_sport"] or x["type_linge"] or x["type_tshirt"]
    if etat_reel >= 3:
        revente = not (peu_demande and qualite < 0.15)
    elif etat_reel == 2:
        revente = (not peu_demande and qualite >= 0.25) or qualite >= 0.75
    else:
        revente = False
    if revente:
        # les belles pièces nobles en petit lot partent en upcycling haut de gamme
        if x["part_noble"] >= 0.7 and x["poids_kg"] <= 40 and etat_reel <= 3:
            return "UPCYCLING"
        return "REVENTE"

    # 2. Upcycling : matière à valeur (fibres nobles, denim), état moyen, lot artisanal
    valeur = x["part_noble"] >= 0.6 or (x["type_jean"] and x["part_coton"] >= 0.9)
    if valeur and etat_reel >= 1 and x["poids_kg"] <= 150:
        return "UPCYCLING"

    # 3. Recyclage matière (effilochage, chiffons) : composition connue et recyclable
    inconnue = x["part_autre"] >= 0.5
    elasthanne = x["part_elasthanne"]
    if not inconnue and elasthanne <= 0.05 and (x["purete"] >= 0.9 or x["nb_fibres"] <= 2):
        return "RECYCLAGE_FIBRE"                            # effilochage
    if not inconnue and elasthanne <= 0.10 and x["purete"] >= 0.5:
        return "RECYCLAGE_FIBRE"                            # chiffons d'essuyage
    return "RECYCLAGE_ENERGIE"


def _probas_etat(poids: float) -> np.ndarray:
    """
    HYPOTHÈSE de modélisation (non sourcée) : un petit lot est souvent déjà trié,
    un gros lot « vrac » post-collecte contient davantage de pièces usées.
    Interpolation entre 20 kg (lot trié) et 500 kg (vrac).
    """
    leger = np.array([0.11, 0.38, 0.30, 0.14, 0.07])
    lourd = np.array([0.03, 0.15, 0.30, 0.30, 0.22])
    w = float(np.clip(np.log(poids / 20) / np.log(25), 0, 1))
    p = (1 - w) * leger + w * lourd
    return p / p.sum()


def generer(n: int = 8000, graine: int = 42, bruit: float = 0.04, part_sans_don: float = 0.40,
            part_sans_etiquette: float = 0.10) -> pd.DataFrame:
    rng = np.random.default_rng(graine)
    archetypes = _archetypes(rng)
    noms = list(archetypes)
    freqs = np.array([archetypes[k][0] for k in noms])

    etats_libelles = {4: "Neuf", 3: "Très bon", 2: "Bon", 1: "À réparer", 0: "À recycler"}

    lignes = []
    for _ in range(n):
        type_v = noms[rng.choice(len(noms), p=freqs / freqs.sum())]   # type réel
        composition = _tirer(rng, archetypes[type_v][1])
        if rng.uniform() < part_sans_etiquette:
            composition = rng.choice(["inconnue", "mélange non étiqueté", "divers"])
        poids = float(np.clip(rng.lognormal(mean=np.log(35), sigma=1.0), 1, 1500))
        etat_reel = int(rng.choice([4, 3, 2, 1, 0], p=_probas_etat(poids)))
        qualite = float(rng.beta(2, 2))

        # Le centre de tri voit tout (état et type réels) ; le modèle ne voit que ce qui est
        # saisi dans l'application : sans don rattaché, état ET type sont inconnus.
        x_reel, _, _ = construire_features(composition, poids, etats_libelles[etat_reel], type_v)
        filiere = _decider(rng, x_reel, etat_reel, qualite)

        sans_don = rng.uniform() < part_sans_don
        etat_saisi = None if sans_don else etats_libelles[etat_reel]
        type_saisi = None if sans_don else type_v
        if rng.uniform() < bruit:                           # désaccord entre trieurs
            filiere = rng.choice([c for c in CLASSES if c != filiere])

        lignes.append({"composition": composition, "poids_kg": round(poids, 1),
                       "etat": etat_saisi, "type": type_saisi, "filiere": filiere,
                       "source": "synthetique"})
    return pd.DataFrame(lignes)


def vers_matrice(df: pd.DataFrame) -> np.ndarray:
    lignes = []
    for r in df.itertuples(index=False):
        etat = r.etat if isinstance(r.etat, str) else None
        type_v = r.type if isinstance(r.type, str) else None
        x, _, _ = construire_features(r.composition, float(r.poids_kg), etat, type_v)
        lignes.append([x[f] for f in NOMS_FEATURES])
    return np.array(lignes, dtype=float)


if __name__ == "__main__":
    df = generer()
    print(df["filiere"].value_counts(normalize=True).round(3))
    df.to_csv("data/lots_synthetiques.csv", index=False)
