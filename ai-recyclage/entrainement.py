"""
Entraînement du modèle de prédiction de filière.

1. Données : lots synthétiques calibrés + décisions réelles des recycleurs (pondérées ×3).
2. Compétition de 4 algorithmes en validation croisée stratifiée 5 plis :
   Régression logistique (référence), Random Forest, HistGradientBoosting, LightGBM.
3. Le meilleur (F1 macro) est calibré (probabilités fiables = vraie confiance).
4. Évaluation sur un jeu de test jamais vu + comparaison avec l'ancien système à règles.
5. Sauvegarde du modèle versionné + métriques + historique.
"""

from __future__ import annotations

import json
import warnings
from datetime import datetime
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from lightgbm import LGBMClassifier
from sklearn.calibration import CalibratedClassifierCV
from sklearn.ensemble import HistGradientBoostingClassifier, RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (accuracy_score, classification_report, confusion_matrix,
                             f1_score, log_loss)
from sklearn.model_selection import StratifiedKFold, cross_val_score, train_test_split
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler

from generateur_dataset import CLASSES, generer, vers_matrice
from textile_features import NOMS_FEATURES, analyser_composition

warnings.filterwarnings("ignore")

RACINE = Path(__file__).parent
DOSSIER_MODELES = RACINE / "modeles"
FICHIER_RETOURS = RACINE / "data" / "retours_recycleurs.csv"
POIDS_RETOUR_HUMAIN = 3.0


def candidats() -> dict:
    return {
        "Régression logistique": make_pipeline(
            SimpleImputer(strategy="median"), StandardScaler(),
            LogisticRegression(max_iter=2000, class_weight="balanced")),
        "Random Forest": RandomForestClassifier(
            n_estimators=200, min_samples_leaf=5, max_depth=16, class_weight="balanced",
            n_jobs=-1, random_state=42),
        "HistGradientBoosting": HistGradientBoostingClassifier(
            max_iter=300, learning_rate=0.08, class_weight="balanced", random_state=42),
        "LightGBM": LGBMClassifier(
            n_estimators=400, learning_rate=0.05, num_leaves=31, min_child_samples=20,
            subsample=0.9, subsample_freq=1, colsample_bytree=0.9,
            class_weight="balanced", random_state=42, verbose=-1),
    }


def regles_historiques(composition: str, poids: float) -> str:
    """Ancien système à règles (copie de LotTextile::recommanderFiliereIA) — sert de référence."""
    c = (composition or "").lower()
    if any(m in c for m in ("polyester", "nylon", "acrylique", "élasthanne", "elasthanne", "viscose")):
        return "RECYCLAGE_FIBRE"
    if any(m in c for m in ("laine", "lin", "soie", "cachemire")):
        return "UPCYCLING" if poids <= 100 else "RECYCLAGE_FIBRE"
    if "coton" in c:
        return "REVENTE" if poids <= 50 else "RECYCLAGE_FIBRE"
    return "RECYCLAGE_FIBRE" if poids > 50 else "RECYCLAGE_ENERGIE"


def charger_donnees() -> pd.DataFrame:
    df = generer()
    if FICHIER_RETOURS.exists():
        retours = pd.read_csv(FICHIER_RETOURS)
        if len(retours):
            retours["source"] = "recycleur"
            df = pd.concat([df, retours[df.columns]], ignore_index=True)
    return df


def version_courante() -> int:
    f = DOSSIER_MODELES / "metriques.json"
    return json.loads(f.read_text(encoding="utf-8"))["version"] if f.exists() else 0


def entrainer() -> dict:
    DOSSIER_MODELES.mkdir(exist_ok=True)
    df = charger_donnees()
    X = vers_matrice(df)
    y = df["filiere"].to_numpy()
    poids = np.where(df["source"] == "recycleur", POIDS_RETOUR_HUMAIN, 1.0)
    humain = (df["source"] == "recycleur").to_numpy()

    X_tr, X_te, y_tr, y_te, w_tr, _, h_tr, h_te, df_tr, df_te = train_test_split(
        X, y, poids, humain, df, test_size=0.2, stratify=y, random_state=42)

    # ---- 1. Compétition d'algorithmes (validation croisée 5 plis) -------------
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    comparaison = []
    for nom, modele in candidats().items():
        scores = cross_val_score(modele, X_tr, y_tr, cv=cv, scoring="f1_macro", n_jobs=1)
        comparaison.append({"algorithme": nom, "f1_macro_cv": round(float(scores.mean()), 4),
                            "ecart_type": round(float(scores.std()), 4)})
    comparaison.sort(key=lambda r: -r["f1_macro_cv"])
    meilleur_nom = comparaison[0]["algorithme"]

    # ---- 2. Entraînement du meilleur + calibration des probabilités -----------
    base = candidats()[meilleur_nom]
    calibre = _calibrer(base, X_tr, y_tr, w_tr)

    brut = candidats()[meilleur_nom]
    _fit(brut, X_tr, y_tr, w_tr)

    # ---- 3. Évaluation sur le jeu de test ------------------------------------
    y_pred = calibre.predict(X_te)
    proba = calibre.predict_proba(X_te)
    proba_brut = brut.predict_proba(X_te)
    regles = np.array([regles_historiques(r.composition, float(r.poids_kg)) for r in df_te.itertuples()])

    rapport = classification_report(y_te, y_pred, labels=CLASSES, output_dict=True, zero_division=0)
    metriques_test = {
        "precision": round(accuracy_score(y_te, y_pred), 4),
        "f1_macro": round(f1_score(y_te, y_pred, average="macro"), 4),
        "log_loss_calibre": round(log_loss(y_te, proba, labels=list(calibre.classes_)), 4),
        "log_loss_non_calibre": round(log_loss(y_te, proba_brut, labels=list(brut.classes_)), 4),
        "precision_ancien_systeme_regles": round(accuracy_score(y_te, regles), 4),
        "f1_macro_ancien_systeme_regles": round(f1_score(y_te, regles, average="macro"), 4),
        "par_classe": {c: {"precision": round(rapport[c]["precision"], 3),
                           "rappel": round(rapport[c]["recall"], 3),
                           "f1": round(rapport[c]["f1-score"], 3),
                           "support": int(rapport[c]["support"])} for c in CLASSES},
        "matrice_confusion": {"classes": CLASSES,
                              "valeurs": confusion_matrix(y_te, y_pred, labels=CLASSES).tolist()},
    }
    connu = X_te[:, NOMS_FEATURES.index("etat_connu")] == 1
    confiance = proba.max(axis=1)
    metriques_test["precision_etat_connu"] = round(accuracy_score(y_te[connu], y_pred[connu]), 4)
    metriques_test["precision_etat_inconnu"] = round(accuracy_score(y_te[~connu], y_pred[~connu]), 4)
    metriques_test["precision_par_confiance"] = [
        {"seuil": s, "part_des_lots": round(float((confiance >= s).mean()), 3),
         "precision": round(accuracy_score(y_te[confiance >= s], y_pred[confiance >= s]), 4)}
        for s in (0.5, 0.7, 0.9) if (confiance >= s).any()]
    top2 = np.argsort(-proba, axis=1)[:, :2]
    metriques_test["precision_top2"] = round(float(np.mean(
        [y_te[i] in calibre.classes_[top2[i]] for i in range(len(y_te))])), 4)
    if h_te.sum() >= 5:
        metriques_test["precision_sur_decisions_recycleurs"] = round(accuracy_score(y_te[h_te], y_pred[h_te]), 4)

    # ---- 4. Modèle final sur toutes les données + importance globale --------
    calibre_final = _calibrer(candidats()[meilleur_nom], X, y, poids)
    expliquable, algo_explication = _modele_expliquable(meilleur_nom, X, y, poids)

    importances = dict(zip(NOMS_FEATURES, expliquable.feature_importances_.astype(float)))
    total = sum(importances.values()) or 1.0
    top = sorted(importances.items(), key=lambda kv: -kv[1])[:10]

    version = version_courante() + 1
    metriques = {
        "version": version,
        "date": datetime.now().isoformat(timespec="seconds"),
        "algorithme": meilleur_nom,
        "algorithme_explication": algo_explication,
        "comparaison_algorithmes": comparaison,
        "test": metriques_test,
        "donnees": {"total": int(len(df)), "synthetiques": int((~humain).sum()),
                    "decisions_recycleurs": int(humain.sum()),
                    "repartition": {c: int((y == c).sum()) for c in CLASSES}},
        "importance_variables": [{"variable": k, "importance": round(v / total, 4)} for k, v in top],
    }

    joblib.dump({"modele": calibre_final, "expliquable": expliquable, "features": NOMS_FEATURES,
                 "version": version, "algorithme": meilleur_nom}, DOSSIER_MODELES / "modele.joblib", compress=3)
    (DOSSIER_MODELES / "metriques.json").write_text(json.dumps(metriques, ensure_ascii=False, indent=2), encoding="utf-8")

    hist_f = DOSSIER_MODELES / "historique.json"
    historique = json.loads(hist_f.read_text(encoding="utf-8")) if hist_f.exists() else []
    historique.append({k: metriques[k] for k in ("version", "date", "algorithme")}
                      | {"precision": metriques_test["precision"], "f1_macro": metriques_test["f1_macro"],
                         "decisions_recycleurs": metriques["donnees"]["decisions_recycleurs"]})
    hist_f.write_text(json.dumps(historique, ensure_ascii=False, indent=2), encoding="utf-8")
    metriques["historique"] = historique
    return metriques


def _calibrer(modele, X, y, w) -> CalibratedClassifierCV:
    calibre = CalibratedClassifierCV(modele, method="isotonic", cv=5)
    if hasattr(modele, "steps"):
        calibre.fit(X, y)          # un Pipeline n'accepte pas sample_weight directement
    else:
        calibre.fit(X, y, sample_weight=w)
    return calibre


def _fit(modele, X, y, w):
    if hasattr(modele, "steps"):
        modele.fit(X, y, **{f"{modele.steps[-1][0]}__sample_weight": w})
    else:
        modele.fit(X, y, sample_weight=w)


def _modele_expliquable(meilleur_nom: str, X, y, w):
    """
    Modèle expliqué par SHAP (TreeExplainer → valeurs exactes).
    On explique le gagnant lui-même s'il est à base d'arbres compatible,
    sinon un LightGBM entraîné sur les mêmes données.
    """
    nom = meilleur_nom if meilleur_nom in ("Random Forest", "LightGBM") else "LightGBM"
    m = candidats()[nom]
    m.fit(X, y, sample_weight=w)
    return m, nom


def enregistrer_retours(retours: list[dict]) -> int:
    """Remplace le fichier des décisions recycleurs par la liste complète envoyée par Laravel."""
    lignes = []
    for r in retours:
        if r.get("filiere") not in CLASSES:
            continue
        analyser_composition(r.get("composition"))
        lignes.append({"composition": r.get("composition") or "", "poids_kg": float(r.get("poids_kg") or 0),
                       "etat": r.get("etat"), "type": r.get("type"), "filiere": r["filiere"],
                       "source": "recycleur"})
    FICHIER_RETOURS.parent.mkdir(exist_ok=True)
    pd.DataFrame(lignes, columns=["composition", "poids_kg", "etat", "type", "filiere", "source"]) \
        .to_csv(FICHIER_RETOURS, index=False)
    return len(lignes)


if __name__ == "__main__":
    m = entrainer()
    print(json.dumps({k: m[k] for k in ("version", "algorithme", "comparaison_algorithmes")}, ensure_ascii=False, indent=2))
    t = m["test"]
    print("Précision test :", t["precision"], "| F1 macro :", t["f1_macro"])
    print("Ancien système à règles :", t["precision_ancien_systeme_regles"], "| F1 :", t["f1_macro_ancien_systeme_regles"])
    print("Log-loss calibré / non calibré :", t["log_loss_calibre"], "/", t["log_loss_non_calibre"])
    print(json.dumps(t["par_classe"], indent=2))
