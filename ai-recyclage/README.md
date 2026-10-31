# IA filière — Module M4 Recyclage

Moteur de décision de filière **entraîné, explicable et auto-apprenant**.
Pour chaque lot textile, il prédit la meilleure filière (revente, upcycling, recyclage fibre,
recyclage énergie), donne une **probabilité calibrée** par filière, **explique** sa décision
(valeurs SHAP) et **apprend** des validations des recycleurs.

## Lancer le service

```bash
cd ai-recyclage
pip install -r requirements.txt
python -m uvicorn app:app --port 8001
```

Au premier démarrage, le modèle est entraîné automatiquement (~1 min).
Laravel l'appelle sur `AI_RECYCLAGE_URL` (par défaut `http://127.0.0.1:8001`).
**Si le service est arrêté, Laravel utilise les règles métier** : l'application ne tombe jamais en panne.

## Fonctionnement

| Étape | Fichier | Rôle |
|---|---|---|
| 1. Analyse | `textile_features.py` | Transforme « 60% coton, 35% PES, 5% lycra » en 29 variables (part de chaque fibre, synonymes FR/EN/ISO, pureté, mélange, poids, état et type du don). Détecte les compositions incohérentes. |
| 2. Données | `generateur_dataset.py` | 8 000 lots synthétiques **calibrés sur des données réelles** (voir plus bas) + décisions réelles des recycleurs (pondérées ×3). |
| 3. Entraînement | `entrainement.py` | Compétition de 4 algorithmes en validation croisée 5 plis, calibration isotonique des probabilités, évaluation sur un jeu de test jamais vu, comparaison avec l'ancien système à règles. |
| 4. Prédiction | `moteur.py` | Filière + probabilités + 5 raisons principales (SHAP) + avertissements (état inconnu, décision incertaine). |
| 5. API | `app.py` | `GET /sante`, `POST /predire`, `POST /entrainer`, `GET /metriques`. |

## Calibrage sur des données réelles

Aucun jeu de données public « lot textile → filière » n'existe ; les données sont donc générées
selon la logique d'un centre de tri et calibrées sur :

- **Refashion, rapport 2023** — débouchés des textiles triés : 58,6 % réemploi, 32,5 % recyclage
  matière, 8,8 % valorisation énergétique.
- **Sorting for Circularity** (Fashion for Good / Circle Economy) — coton 42 %, mélanges 32 %,
  polycoton 12 % ; **TU Wien 2024** — ~46 % mono-matière, coton/élasthanne mélange le plus courant.
- **Turku UAS** — environ 1/3 des textiles sans étiquette identifiable.
- **Polymers 2025** — l'élasthanne perturbe le recyclage mécanique (1-5 % dans coton/laine,
  jusqu'à 20 % dans polyester/polyamide).

Hypothèses assumées (non sourcées) : un gros lot « vrac » contient plus de pièces usées qu'un petit lot trié ;
4 % de désaccord entre trieurs ; 40 % des lots sans don rattaché (état et type inconnus).

## Résultats (version actuelle)

Voir la page **IA filière** du module ou `modeles/metriques.json`. Ordres de grandeur :

- Précision ≈ **77 %** (≈ **90 %** quand l'état du don est connu) contre ≈ **39 %** pour l'ancien système à règles.
- Bonne filière dans les 2 premières propositions ≈ **96 %**.
- Plus la confiance est haute, plus l'IA a raison (≥ 90 % de confiance → ≈ 92 % de précision).

## Tests

```bash
python -m unittest test_moteur -v
```
