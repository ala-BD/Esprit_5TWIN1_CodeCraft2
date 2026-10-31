"""
Moteur de prédiction : charge le modèle, prédit la filière, explique la décision (SHAP).
"""

from __future__ import annotations

import math
import threading
from pathlib import Path

import joblib
import numpy as np
import shap

from textile_features import FIBRES, GROUPES, NOMS_FEATURES, construire_features

FICHIER_MODELE = Path(__file__).parent / "modeles" / "modele.joblib"

LIBELLES_FIBRES = {"coton": "Coton", "polyester": "Polyester", "elasthanne": "Élasthanne",
                   "laine": "Laine", "viscose": "Viscose", "nylon": "Polyamide/Nylon",
                   "acrylique": "Acrylique", "lin": "Lin", "soie": "Soie",
                   "cachemire": "Cachemire", "autre": "Fibres non identifiées"}
LIBELLES_ETAT = {4.0: "Neuf", 3.0: "Très bon", 2.0: "Bon", 1.0: "À réparer", 0.0: "À recycler"}
LIBELLES_FAMILLE = {"tshirt": "T-shirt / haut", "jean": "Jean / pantalon", "chemise": "Chemise",
                    "pull": "Pull / maille", "veste": "Veste / manteau", "robe": "Robe",
                    "sport": "Sport / legging", "linge": "Linge de maison", "inconnu": "Non renseigné"}


class Moteur:
    def __init__(self):
        self._verrou = threading.Lock()
        self.charger()

    def charger(self):
        with self._verrou:
            paquet = joblib.load(FICHIER_MODELE)
            self.modele = paquet["modele"]
            self.expliquable = paquet["expliquable"]
            self.version = paquet["version"]
            self.algorithme = paquet["algorithme"]
            self.classes = list(self.modele.classes_)
            self.explainer = shap.TreeExplainer(self.expliquable)

    def predire(self, composition: str | None, poids_kg: float,
                etat: str | None = None, type_vetement: str | None = None) -> dict:
        x, avertissements, contexte = construire_features(composition, poids_kg, etat, type_vetement)
        vecteur = np.array([[x[f] for f in NOMS_FEATURES]], dtype=float)

        with self._verrou:
            proba = self.modele.predict_proba(vecteur)[0]
            valeurs_shap = self.explainer.shap_values(vecteur)

        ordre = np.argsort(-proba)
        filiere = self.classes[ordre[0]]
        confiance = float(proba[ordre[0]])
        marge = confiance - float(proba[ordre[1]])

        if contexte["etat_score"] is None:
            avertissements.append("État inconnu (aucun don rattaché) : rattacher un don améliore la prédiction.")
        if confiance < 0.55 or marge < 0.15:
            avertissements.append(
                f"Décision incertaine entre {filiere} et {self.classes[ordre[1]]} : validation humaine conseillée.")

        return {
            "filiere": filiere,
            "confiance": round(confiance, 4),
            "probabilites": {c: round(float(p), 4) for c, p in zip(self.classes, proba)},
            "explication": self._expliquer(valeurs_shap, filiere, x, contexte),
            "avertissements": avertissements,
            "analyse": contexte,
            "modele": {"version": self.version, "algorithme": self.algorithme},
        }

    # -----------------------------------------------------------------
    def _expliquer(self, valeurs_shap, filiere: str, x: dict, contexte: dict) -> list[dict]:
        """Contributions SHAP regroupées par raison lisible, en % de l'influence totale."""
        idx = list(self.expliquable.classes_).index(filiere)
        v = np.asarray(valeurs_shap)
        if v.ndim == 3:                      # (n, features, classes)
            contrib = v[0, :, idx]
        else:                                # liste par classe (anciennes versions de SHAP)
            contrib = np.asarray(valeurs_shap[idx])[0]

        groupes: dict[str, float] = {}
        for nom, c in zip(NOMS_FEATURES, contrib):
            groupes[GROUPES[nom]] = groupes.get(GROUPES[nom], 0.0) + float(c)

        total = sum(abs(c) for c in groupes.values()) or 1.0
        raisons = []
        for groupe, c in sorted(groupes.items(), key=lambda kv: -abs(kv[1])):
            libelle = self._libelle(groupe, x, contexte)
            if libelle is None or abs(c) / total < 0.03:
                continue
            raisons.append({"facteur": libelle, "impact": round(100 * c / total, 1),
                            "sens": "pour" if c > 0 else "contre"})
            if len(raisons) == 5:
                break
        return raisons

    @staticmethod
    def _libelle(groupe: str, x: dict, contexte: dict) -> str | None:
        if groupe in FIBRES:
            part = x[f"part_{groupe}"]
            return f"{LIBELLES_FIBRES[groupe]} : {part * 100:.0f} %" if part > 0 else f"Absence de {LIBELLES_FIBRES[groupe].lower()}"
        if groupe == "synthetique":
            return f"Part de fibres synthétiques : {x['part_synthetique'] * 100:.0f} %"
        if groupe == "naturelle":
            return f"Part de fibres naturelles : {x['part_naturelle'] * 100:.0f} %"
        if groupe == "noble":
            return f"Part de fibres nobles : {x['part_noble'] * 100:.0f} %"
        if groupe == "melange":
            n = int(x["nb_fibres"])
            return "Matière pure (mono-fibre)" if n <= 1 else f"Mélange de {n} fibres"
        if groupe == "poids":
            return f"Poids du lot : {x['poids_kg']:g} kg"
        if groupe == "etat":
            e = x["etat_score"]
            return "État inconnu" if math.isnan(e) else f"État : {LIBELLES_ETAT.get(e, e)}"
        if groupe == "type":
            return f"Type : {LIBELLES_FAMILLE[contexte['famille']]}"
        return None
