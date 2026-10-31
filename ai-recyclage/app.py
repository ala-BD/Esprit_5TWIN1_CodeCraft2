"""
API du moteur IA de filière — module M4 Recyclage (TextileCycle).

Lancement :  uvicorn app:app --port 8001
"""

from __future__ import annotations

import json
from pathlib import Path

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

import entrainement
from moteur import FICHIER_MODELE, Moteur

app = FastAPI(title="TextileCycle — IA filière M4", version="1.0")

if not FICHIER_MODELE.exists():
    entrainement.entrainer()
moteur = Moteur()


class Lot(BaseModel):
    composition: str | None = ""
    poids_kg: float = Field(gt=0)
    etat: str | None = None
    type: str | None = None


class Retour(Lot):
    filiere: str


class Entrainement(BaseModel):
    retours: list[Retour] = []


@app.get("/sante")
def sante():
    return {"statut": "ok", "version": moteur.version, "algorithme": moteur.algorithme}


@app.post("/predire")
def predire(lot: Lot):
    return moteur.predire(lot.composition, lot.poids_kg, lot.etat, lot.type)


@app.post("/entrainer")
def entrainer(donnees: Entrainement):
    nb = entrainement.enregistrer_retours([r.model_dump() for r in donnees.retours])
    metriques = entrainement.entrainer()
    moteur.charger()
    return {"retours_utilises": nb, "metriques": metriques}


@app.get("/metriques")
def metriques():
    f = Path(entrainement.DOSSIER_MODELES) / "metriques.json"
    h = Path(entrainement.DOSSIER_MODELES) / "historique.json"
    if not f.exists():
        raise HTTPException(404, "Aucun modèle entraîné.")
    data = json.loads(f.read_text(encoding="utf-8"))
    data["historique"] = json.loads(h.read_text(encoding="utf-8")) if h.exists() else []
    return data
