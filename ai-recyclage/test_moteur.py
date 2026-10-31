"""Tests du moteur IA — lancer avec :  python -m unittest test_moteur -v"""

import math
import unittest

from generateur_dataset import CLASSES, generer
from moteur import Moteur
from textile_features import analyser_composition, famille_type, score_etat


class TestFeatures(unittest.TestCase):
    def test_composition_formats(self):
        parts, _ = analyser_composition("60% coton, 40% polyester")
        self.assertAlmostEqual(parts["coton"], 0.6)
        self.assertAlmostEqual(parts["polyester"], 0.4)

        parts, _ = analyser_composition("coton 95% élasthanne 5%")
        self.assertAlmostEqual(parts["elasthanne"], 0.05)

        parts, _ = analyser_composition("70% cotton 25% PES 5% lycra")
        self.assertAlmostEqual(parts["coton"], 0.7)
        self.assertAlmostEqual(parts["polyester"], 0.25)

        parts, _ = analyser_composition("Laine / acrylique")
        self.assertAlmostEqual(parts["laine"], 0.5)

    def test_composition_inconnue_et_incoherente(self):
        parts, avert = analyser_composition("inconnue")
        self.assertEqual(parts["autre"], 1.0)
        self.assertTrue(avert)

        parts, avert = analyser_composition("60% coton 20% polyester")
        self.assertAlmostEqual(parts["autre"], 0.2)
        self.assertIn("80", avert[0])

    def test_etat_et_type(self):
        self.assertEqual(score_etat("Neuf"), 4.0)
        self.assertEqual(score_etat("Très bon"), 3.0)
        self.assertEqual(score_etat("À réparer"), 1.0)
        self.assertTrue(math.isnan(score_etat(None)))
        self.assertEqual(famille_type("Jean slim"), "jean")
        self.assertEqual(famille_type("Legging de sport"), "sport")


class TestDataset(unittest.TestCase):
    def test_repartition_proche_des_donnees_reelles(self):
        rep = generer().filiere.value_counts(normalize=True)
        reemploi = rep["REVENTE"] + rep["UPCYCLING"]
        self.assertAlmostEqual(reemploi, 0.586, delta=0.05)               # Refashion 2023
        self.assertAlmostEqual(rep["RECYCLAGE_FIBRE"], 0.325, delta=0.05)
        self.assertAlmostEqual(rep["RECYCLAGE_ENERGIE"], 0.088, delta=0.04)


class TestMoteur(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.moteur = Moteur()

    def test_prediction_complete(self):
        r = self.moteur.predire("100% laine", 15, "À réparer", "Pull")
        self.assertIn(r["filiere"], CLASSES)
        self.assertAlmostEqual(sum(r["probabilites"].values()), 1.0, places=3)
        self.assertTrue(r["explication"])
        self.assertEqual(r["filiere"], "UPCYCLING")

    def test_cas_metier(self):
        self.assertEqual(self.moteur.predire("82% polyester, 18% élasthanne", 50, "À recycler", "Legging")["filiere"],
                         "RECYCLAGE_ENERGIE")
        self.assertEqual(self.moteur.predire("100% coton", 30, "Très bon", "Chemise")["filiere"], "REVENTE")
        self.assertEqual(self.moteur.predire("100% coton", 300, "À recycler", "Drap")["filiere"], "RECYCLAGE_FIBRE")

    def test_etat_inconnu_signale(self):
        r = self.moteur.predire("100% coton", 30)
        self.assertTrue(any("État inconnu" in a for a in r["avertissements"]))


if __name__ == "__main__":
    unittest.main()
