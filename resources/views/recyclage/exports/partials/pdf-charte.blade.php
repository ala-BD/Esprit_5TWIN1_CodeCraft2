{{--
    Charte graphique RETISS pour les PDF (DomPDF).
    Contraintes DomPDF : pas de dégradé, pas de flexbox, pas d'emoji, graisses normal/bold uniquement.
--}}
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.45; }

    .page { padding: 0 34px 70px 34px; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .muted { color: #64748b; }
    .right { text-align: right; }
    .center { text-align: center; }
    .bold { font-weight: bold; }

    /* ── En-tête ── */
    .entete { background: #1a2030; color: #fff; }
    .entete td { vertical-align: middle; }
    .entete .logo { width: 70px; padding: 14px 0 14px 30px; }
    .entete .logo img { width: 62px; height: 64px; }
    .entete .marque { padding-left: 10px; }
    .entete .marque-nom { font-size: 20px; font-weight: bold; letter-spacing: 3px; color: #fff; }
    .entete .marque-sous { font-size: 8px; color: #5eead4; margin-top: 2px; }
    .entete .doc { text-align: right; padding-right: 34px; }
    .entete .doc-type { font-size: 7.5px; letter-spacing: 2px; color: #94a3b8; }
    .entete .doc-titre { font-size: 15px; font-weight: bold; color: #fff; margin-top: 2px; }
    .entete .doc-sous { font-size: 8px; color: #cbd5e1; margin-top: 2px; }
    .accent td { height: 4px; font-size: 1px; line-height: 1px; }

    /* ── Titres de section ── */
    .section { font-size: 8px; font-weight: bold; letter-spacing: 1.6px; color: #0f766e; text-transform: uppercase;
               border-bottom: 1.5px solid #ccfbf1; padding-bottom: 4px; margin: 16px 0 8px 0; }

    /* ── Cartes ── */
    .carte { border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .grille { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin: 0 -7px; }
    .kpi { border: 1px solid #e2e8f0; border-top: 3px solid #0d9488; border-radius: 6px; padding: 9px 8px; text-align: center; background: #fff; }
    .kpi-valeur { font-size: 17px; font-weight: bold; color: #0f172a; }
    .kpi-unite { font-size: 9px; color: #64748b; font-weight: normal; }
    .kpi-libelle { font-size: 7px; letter-spacing: 1px; color: #64748b; text-transform: uppercase; margin-top: 3px; font-weight: bold; }

    /* ── Tableaux ── */
    table { border-collapse: collapse; }
    .tableau { width: 100%; }
    .tableau th { background: #0f766e; color: #fff; font-size: 7.5px; letter-spacing: .8px; text-transform: uppercase;
                  font-weight: bold; padding: 6px 7px; text-align: left; }
    .tableau td { padding: 5px 7px; border-bottom: 1px solid #f1f5f9; }
    .tableau th.right { text-align: right; }
    .tableau tr.pair td { background: #f8fafc; }
    .tableau tr.total td { background: #ccfbf1; color: #0f766e; font-weight: bold; border-top: 1.5px solid #0d9488; }

    .infos { width: 100%; }
    .infos td { padding: 4.5px 0; border-bottom: 1px solid #f1f5f9; }
    .infos td.libelle { color: #64748b; width: 42%; }
    .infos td.valeur { font-weight: bold; text-align: right; color: #0f172a; }

    /* ── Barres de progression ── */
    .barre { background: #f1f5f9; height: 6px; border-radius: 3px; }
    .barre div { height: 6px; border-radius: 3px; }

    /* ── Pastilles ── */
    .pastille { display: inline-block; padding: 2px 8px; border-radius: 9px; font-size: 7.5px; font-weight: bold; }

    /* ── Pied de page ── */
    .pied { position: fixed; bottom: 0; left: 0; right: 0; height: 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
    .pied td { padding: 9px 34px; font-size: 7px; color: #94a3b8; vertical-align: middle; }
</style>
