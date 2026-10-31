<?php

namespace App\Services\Recyclage;

use App\Http\Controllers\Recyclage\ExportController;
use App\Models\EtapeTraitement;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports Excel (.xlsx) du module M4 aux couleurs de la charte RETISS, avec le logo officiel.
 */
class ExportExcel
{
    // Charte RETISS (couleurs du logo)
    const NAVY       = '1A2030';
    const TEAL       = '0D9488';
    const TEAL_DARK  = '0F766E';
    const TEAL_LIGHT = 'CCFBF1';
    const GREEN      = '16A34A';
    const SLATE      = '64748B';
    const LIGHT      = 'F1F5F9';
    const BORDER     = 'E2E8F0';

    const STATUTS = [
        'EN_ATTENTE'    => ['FEF3C7', '92400E'],
        'EN_TRAITEMENT' => ['DBEAFE', '1E40AF'],
        'TRAITE'        => ['DCFCE7', '166534'],
        'CERTIFIE'      => ['EDE9FE', '5B21B6'],
    ];

    const FILIERES = [
        'REVENTE'           => '059669',
        'UPCYCLING'         => 'D97706',
        'RECYCLAGE_FIBRE'   => '2563EB',
        'RECYCLAGE_ENERGIE' => 'DC2626',
    ];

    /*
    |------------------------------------------------------------------
    | Inventaire des lots
    |------------------------------------------------------------------
    */
    public function inventaireLots(Collection $lots, Recycleur $recycleur, string $filtres): Spreadsheet
    {
        $ss = $this->classeur('Inventaire des lots textiles');
        $ws = $ss->getActiveSheet()->setTitle('Lots');

        $colonnes = [
            ['Référence', 20], ['Date de création', 17], ['Composition', 30], ['Poids (kg)', 12],
            ['Origine', 22], ['Filière IA', 18], ['Confiance IA', 13], ['Filière retenue', 18],
            ['Statut', 15], ['Progression', 13], ['Étapes', 10], ['Don rattaché', 26],
            ['Passeport (QR)', 32], ['CO₂ évité (kg)', 15], ['Eau économisée (L)', 18],
        ];
        $der = Coordinate::stringFromColumnIndex(count($colonnes));
        foreach ($colonnes as $i => [, $largeur]) {
            $ws->getColumnDimensionByColumn($i + 1)->setWidth($largeur);
        }

        $ligne = $this->entete($ws, $der, 'Inventaire des lots textiles',
            "{$recycleur->nom} · Agrément {$recycleur->agrement} · {$recycleur->localisation}");

        // Indicateurs
        $kg = (float) $lots->sum('poids_kg');
        $this->cartes($ws, $ligne, [
            ['A', 'B', 'Lots exportés', $lots->count(), '0'],
            ['C', 'D', 'Poids total', $kg, '#,##0.0 "kg"'],
            ['E', 'G', 'CO₂ évité', $kg * ExportController::CO2_PAR_KG, '#,##0.0 "kg"'],
            ['H', 'K', 'Eau économisée', $kg * ExportController::EAU_PAR_KG, '#,##0 "L"'],
            ['L', 'O', 'Lots certifiés', $lots->where('statut', 'CERTIFIE')->count(), '0'],
        ]);
        $ligne += 3;

        $ws->setCellValue("A{$ligne}", $filtres);
        $ws->getStyle("A{$ligne}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB(self::SLATE);
        $ligne += 2;

        // En-tête du tableau
        $entete = $ligne;
        foreach ($colonnes as $i => [$titre]) {
            $ws->setCellValue([$i + 1, $entete], $titre);
        }
        $this->styleEnteteTableau($ws, "A{$entete}:{$der}{$entete}");

        // Données
        $l = $entete + 1;
        foreach ($lots as $lot) {
            $circuit = EtapeTraitement::CIRCUITS[$lot->filiere_finale] ?? [];
            $ws->fromArray([
                $lot->reference,
                ExcelDate::PHPToExcel($lot->created_at),
                $lot->composition,
                (float) $lot->poids_kg,
                $lot->origine,
                LotTextile::libelleFiliere($lot->filiere_recommandee_ia),
                $lot->score_confiance_ia,
                $lot->filiere_validee ? LotTextile::libelleFiliere($lot->filiere_validee) : '—',
                ExportController::statutLabel($lot->statut),
                $lot->progression / 100,
                $lot->etapeTraitements->whereNotNull('date_fin')->whereIn('type', $circuit)->count() . ' / ' . count($circuit),
                $lot->donVetement ? "Don n°{$lot->donVetement->id} — {$lot->donVetement->type}" : '—',
                $lot->passeportNumerique?->qr_code ?? '—',
                $lot->poids_kg * ExportController::CO2_PAR_KG,
                $lot->poids_kg * ExportController::EAU_PAR_KG,
            ], null, "A{$l}", true);

            [$fond, $texte] = self::STATUTS[$lot->statut] ?? ['F3F4F6', '374151'];
            $this->remplir($ws, "I{$l}", $fond);
            $ws->getStyle("I{$l}")->getFont()->setBold(true)->getColor()->setRGB($texte);
            $ws->getStyle("F{$l}")->getFont()->setBold(true)->getColor()->setRGB(self::FILIERES[$lot->filiere_recommandee_ia] ?? '374151');
            if ($lot->filiere_validee) {
                $ws->getStyle("H{$l}")->getFont()->setBold(true)->getColor()->setRGB(self::FILIERES[$lot->filiere_validee] ?? '374151');
            }
            if ($l % 2 === 0) {
                $this->remplir($ws, "A{$l}:H{$l}", 'F8FAFC');
                $this->remplir($ws, "J{$l}:{$der}{$l}", 'F8FAFC');
            }
            $l++;
        }
        $fin = max($l - 1, $entete + 1);

        // Formats
        $ws->getStyle("A" . ($entete + 1) . ":A{$fin}")->getFont()->setBold(true)->getColor()->setRGB(self::NAVY);
        $ws->getStyle("B" . ($entete + 1) . ":B{$fin}")->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        $ws->getStyle("D" . ($entete + 1) . ":D{$fin}")->getNumberFormat()->setFormatCode('#,##0.0');
        $ws->getStyle("G" . ($entete + 1) . ":G{$fin}")->getNumberFormat()->setFormatCode('0%');
        $ws->getStyle("J" . ($entete + 1) . ":J{$fin}")->getNumberFormat()->setFormatCode('0%');
        $ws->getStyle("N" . ($entete + 1) . ":N{$fin}")->getNumberFormat()->setFormatCode('#,##0.0');
        $ws->getStyle("O" . ($entete + 1) . ":O{$fin}")->getNumberFormat()->setFormatCode('#,##0');
        $ws->getStyle("M" . ($entete + 1) . ":M{$fin}")->getFont()->setName('Consolas')->setSize(9);
        foreach (['B', 'D', 'G', 'I', 'J', 'K'] as $c) {
            $ws->getStyle("{$c}" . ($entete + 1) . ":{$c}{$fin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->bordures($ws, "A{$entete}:{$der}{$fin}");
        $ws->getStyle("A" . ($entete + 1) . ":{$der}{$fin}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Totaux (SOUS.TOTAL : suivent les filtres Excel)
        $t = $fin + 1;
        $ws->setCellValue("A{$t}", 'TOTAL');
        $ws->setCellValue("C{$t}", "=SUBTOTAL(3,A" . ($entete + 1) . ":A{$fin}) & \" lot(s)\"");
        $ws->setCellValue("D{$t}", "=SUBTOTAL(9,D" . ($entete + 1) . ":D{$fin})");
        $ws->setCellValue("N{$t}", "=SUBTOTAL(9,N" . ($entete + 1) . ":N{$fin})");
        $ws->setCellValue("O{$t}", "=SUBTOTAL(9,O" . ($entete + 1) . ":O{$fin})");
        $this->remplir($ws, "A{$t}:{$der}{$t}", self::TEAL_LIGHT);
        $ws->getStyle("A{$t}:{$der}{$t}")->getFont()->setBold(true)->getColor()->setRGB(self::TEAL_DARK);
        $ws->getStyle("D{$t}")->getNumberFormat()->setFormatCode('#,##0.0');
        $ws->getStyle("N{$t}")->getNumberFormat()->setFormatCode('#,##0.0');
        $ws->getStyle("O{$t}")->getNumberFormat()->setFormatCode('#,##0');
        $ws->getStyle("D{$t}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("A{$t}:{$der}{$t}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::TEAL);

        $this->piedDePage($ws, $t + 2);

        $ws->setAutoFilter("A{$entete}:{$der}{$fin}");
        $ws->freezePane('B' . ($entete + 1));
        $this->miseEnPage($ws, $entete, PageSetup::ORIENTATION_LANDSCAPE);

        return $ss;
    }

    /*
    |------------------------------------------------------------------
    | Bilan d'activité
    |------------------------------------------------------------------
    */
    public function bilan(Recycleur $recycleur, array $b): Spreadsheet
    {
        $ss = $this->classeur("Bilan d'activité");
        $ws = $ss->getActiveSheet()->setTitle('Bilan');
        foreach (['A' => 22, 'B' => 14, 'C' => 14, 'D' => 16, 'E' => 4, 'F' => 22, 'G' => 14, 'H' => 14] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }

        $l = $this->entete($ws, 'H', "Bilan d'activité — Recyclage",
            "{$recycleur->nom} · Agrément {$recycleur->agrement} · {$recycleur->localisation}");

        // Indicateurs
        $this->cartes($ws, $l, [
            ['A', 'A', 'Lots enregistrés', $b['total_lots'], '0'],
            ['B', 'C', 'Poids total', $b['total_kg'], '#,##0.0 "kg"'],
            ['D', 'D', 'CO₂ évité', $b['co2'], '#,##0.0 "kg"'],
            ['F', 'F', 'Eau économisée', $b['eau'], '#,##0 "L"'],
            ['G', 'H', 'Capacité utilisée', $b['capacite'] / 100, '0%'],
        ]);
        $l += 4;

        // Répartitions côte à côte
        $this->titreSection($ws, "A{$l}", 'Répartition par filière IA');
        $this->titreSection($ws, "F{$l}", 'Répartition par statut');
        $l++;
        $this->tableauRepartition($ws, 'A', $l, 'Filière',
            collect($b['filieres'])->mapWithKeys(fn ($n, $f) => [LotTextile::libelleFiliere($f) => [$n, self::FILIERES[$f]]])->all(),
            $b['total_lots']);
        $this->tableauRepartition($ws, 'F', $l, 'Statut',
            collect($b['statuts'])->mapWithKeys(fn ($n, $s) => [ExportController::statutLabel($s) => [$n, self::STATUTS[$s][1]]])->all(),
            $b['total_lots']);
        $l += 7;

        // Évolution mensuelle + graphique
        $this->titreSection($ws, "A{$l}", 'Évolution sur 12 mois');
        $l++;
        $entete = $l;
        $ws->fromArray(['Mois', 'Lots créés', 'Poids (kg)', 'CO₂ évité (kg)'], null, "A{$entete}", true);
        $this->styleEnteteTableau($ws, "A{$entete}:D{$entete}");
        foreach ($b['mois'] as $i => $m) {
            $r = $entete + 1 + $i;
            $ws->fromArray([$m['label'], $m['lots'], $m['kg'], $m['co2']], null, "A{$r}", true);
            if ($i % 2) {
                $this->remplir($ws, "A{$r}:D{$r}", 'F8FAFC');
            }
        }
        $fin = $entete + count($b['mois']);
        $ws->getStyle("C" . ($entete + 1) . ":D{$fin}")->getNumberFormat()->setFormatCode('#,##0.0');
        $ws->getStyle("B" . ($entete + 1) . ":D{$fin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $t = $fin + 1;
        $ws->fromArray(['TOTAL', "=SUM(B" . ($entete + 1) . ":B{$fin})", "=SUM(C" . ($entete + 1) . ":C{$fin})", "=SUM(D" . ($entete + 1) . ":D{$fin})"], null, "A{$t}", true);
        $this->remplir($ws, "A{$t}:D{$t}", self::TEAL_LIGHT);
        $ws->getStyle("A{$t}:D{$t}")->getFont()->setBold(true)->getColor()->setRGB(self::TEAL_DARK);
        $ws->getStyle("C{$t}:D{$t}")->getNumberFormat()->setFormatCode('#,##0.0');
        $ws->getStyle("B{$t}:D{$t}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->bordures($ws, "A{$entete}:D{$t}");

        $this->graphiqueMensuel($ws, $entete, $fin, "F{$entete}", 'H' . ($t + 6));

        $this->piedDePage($ws, max($t, $entete + 18) + 2);
        $this->miseEnPage($ws, null, PageSetup::ORIENTATION_PORTRAIT);

        return $ss;
    }

    public function telecharger(Spreadsheet $ss, string $fichier): StreamedResponse
    {
        return response()->streamDownload(function () use ($ss) {
            $writer = new Xlsx($ss);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $fichier, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /*
    |------------------------------------------------------------------
    | Briques de mise en forme
    |------------------------------------------------------------------
    */
    private function classeur(string $titre): Spreadsheet
    {
        $ss = new Spreadsheet();
        $ss->getProperties()
            ->setCreator('RETISS — TextileCycle')
            ->setCompany('RETISS')
            ->setTitle($titre)
            ->setSubject('Module M4 — Recyclage');
        $ss->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        return $ss;
    }

    /** Bandeau RETISS (fond bleu nuit du logo) + logo officiel + barre d'accent. Retourne la ligne suivante. */
    private function entete(Worksheet $ws, string $der, string $titre, string $sousTitre): int
    {
        $ws->setShowGridlines(false);
        foreach ([1 => 26, 2 => 20, 3 => 18] as $r => $h) {
            $ws->getRowDimension($r)->setRowHeight($h);
        }
        $this->remplir($ws, "A1:{$der}3", self::NAVY);

        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            $d = new Drawing();
            $d->setName('Logo RETISS')->setPath($logo)->setHeight(76)->setCoordinates('A1')->setOffsetX(10)->setOffsetY(2);
            $d->setWorksheet($ws);
        }

        $dernierIndex = Coordinate::columnIndexFromString($der);
        $finTitre = Coordinate::stringFromColumnIndex(max(1, $dernierIndex - 2));
        $debutDroite = Coordinate::stringFromColumnIndex($dernierIndex - 1);

        $ws->mergeCells("A1:{$finTitre}1")->setCellValue('A1', 'RETISS  ·  ' . $titre);
        $ws->mergeCells("A2:{$finTitre}2")->setCellValue('A2', $sousTitre);
        $ws->mergeCells("A3:{$finTitre}3")->setCellValue('A3', "Plateforme de l'économie circulaire du textile — Module M4 Recyclage");
        $ws->getStyle('A1:A3')->getAlignment()->setIndent(10)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('FFFFFF');
        $ws->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('5EEAD4');
        $ws->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('94A3B8');

        $ws->mergeCells("{$debutDroite}1:{$der}1")->setCellValue("{$debutDroite}1", 'Édité le ' . now()->format('d/m/Y'));
        $ws->mergeCells("{$debutDroite}2:{$der}2")->setCellValue("{$debutDroite}2", 'à ' . now()->format('H:i'));
        $ws->getStyle("{$debutDroite}1:{$der}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getStyle("{$debutDroite}1:{$der}2")->getFont()->setSize(9)->getColor()->setRGB('CBD5E1');

        // Barre d'accent teal / vert
        $ws->getRowDimension(4)->setRowHeight(5);
        $this->remplir($ws, "A4:{$finTitre}4", self::TEAL);
        $this->remplir($ws, "{$debutDroite}4:{$der}4", '4ADE80');
        $ws->getRowDimension(5)->setRowHeight(10);

        return 6;
    }

    /** Cartes d'indicateurs : libellé (ligne 1) + valeur (ligne 2), cadre teal */
    private function cartes(Worksheet $ws, int $ligne, array $cartes): void
    {
        $ws->getRowDimension($ligne)->setRowHeight(16);
        $ws->getRowDimension($ligne + 1)->setRowHeight(26);
        foreach ($cartes as [$de, $a, $libelle, $valeur, $format]) {
            $ws->mergeCells("{$de}{$ligne}:{$a}{$ligne}")->setCellValue("{$de}{$ligne}", mb_strtoupper($libelle));
            $ws->mergeCells("{$de}" . ($ligne + 1) . ":{$a}" . ($ligne + 1))->setCellValue("{$de}" . ($ligne + 1), $valeur);
            $plage = "{$de}{$ligne}:{$a}" . ($ligne + 1);
            $this->remplir($ws, $plage, 'F0FDFA');
            $ws->getStyle($plage)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $ws->getStyle("{$de}{$ligne}")->getFont()->setBold(true)->setSize(8)->getColor()->setRGB(self::SLATE);
            $ws->getStyle("{$de}" . ($ligne + 1))->getFont()->setBold(true)->setSize(15)->getColor()->setRGB(self::TEAL_DARK);
            $ws->getStyle("{$de}" . ($ligne + 1))->getNumberFormat()->setFormatCode($format);
            $ws->getStyle($plage)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('99F6E4');
            $ws->getStyle($plage)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB(self::TEAL);
        }
    }

    private function titreSection(Worksheet $ws, string $cellule, string $titre): void
    {
        $ws->setCellValue($cellule, mb_strtoupper($titre));
        $ws->getStyle($cellule)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::TEAL_DARK);
    }

    /** Tableau Libellé | Lots | Part, avec couleur par ligne */
    private function tableauRepartition(Worksheet $ws, string $col, int $ligne, string $titre, array $lignes, int $total): void
    {
        $c1 = $col;
        $c2 = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col) + 1);
        $c3 = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col) + 2);
        $ws->fromArray([$titre, 'Lots', 'Part'], null, "{$c1}{$ligne}", true);
        $this->styleEnteteTableau($ws, "{$c1}{$ligne}:{$c3}{$ligne}");
        $r = $ligne + 1;
        foreach ($lignes as $libelle => [$n, $couleur]) {
            $ws->fromArray([$libelle, $n, $total ? $n / $total : 0], null, "{$c1}{$r}", true);
            $ws->getStyle("{$c1}{$r}")->getFont()->setBold(true)->getColor()->setRGB($couleur);
            $ws->getStyle("{$c1}{$r}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB($couleur);
            $r++;
        }
        $ws->getStyle("{$c3}" . ($ligne + 1) . ":{$c3}" . ($r - 1))->getNumberFormat()->setFormatCode('0%');
        $ws->getStyle("{$c2}" . ($ligne + 1) . ":{$c3}" . ($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->bordures($ws, "{$c1}{$ligne}:{$c3}" . ($r - 1));
    }

    private function graphiqueMensuel(Worksheet $ws, int $entete, int $fin, string $hautGauche, string $basDroite): void
    {
        $feuille = "'" . $ws->getTitle() . "'";
        $n = $fin - $entete;
        $serie = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$feuille}!\$C\$" . ($entete + 1) . ":\$C\${$fin}", null, $n);
        $serie->setFillColor(self::TEAL);

        $donnees = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$feuille}!\$C\${$entete}", null, 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$feuille}!\$A\$" . ($entete + 1) . ":\$A\${$fin}", null, $n)],
            [$serie]
        );
        $donnees->setPlotDirection(DataSeries::DIRECTION_COL);

        $graphique = new Chart('poids_mensuel', new Title('Poids traité par mois (kg)'),
            new Legend(Legend::POSITION_BOTTOM, null, false), new PlotArea(null, [$donnees]));
        $graphique->setTopLeftPosition($hautGauche);
        $graphique->setBottomRightPosition($basDroite);
        $ws->addChart($graphique);
    }

    private function styleEnteteTableau(Worksheet $ws, string $plage): void
    {
        $this->remplir($ws, $plage, self::TEAL_DARK);
        $style = $ws->getStyle($plage);
        $style->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $ws->getRowDimension((int) preg_replace('/\D/', '', explode(':', $plage)[0]))->setRowHeight(24);
    }

    private function bordures(Worksheet $ws, string $plage): void
    {
        $ws->getStyle($plage)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER);
    }

    private function remplir(Worksheet $ws, string $plage, string $rgb): void
    {
        $ws->getStyle($plage)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    private function piedDePage(Worksheet $ws, int $ligne): void
    {
        $ws->setCellValue("A{$ligne}", 'Document généré par RETISS le ' . now()->format('d/m/Y à H:i')
            . ' — Facteurs d\'impact : ' . ExportController::CO2_PAR_KG . ' kg CO₂ et '
            . number_format(ExportController::EAU_PAR_KG, 0, ',', ' ') . ' L d\'eau par kg de textile valorisé.');
        $ws->getStyle("A{$ligne}")->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('94A3B8');
    }

    private function miseEnPage(Worksheet $ws, ?int $ligneEntete, string $orientation): void
    {
        $ps = $ws->getPageSetup();
        $ps->setOrientation($orientation)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        if ($ligneEntete) {
            $ps->setRowsToRepeatAtTopByStartAndEnd($ligneEntete, $ligneEntete);
        }
        $ws->getPageMargins()->setTop(0.4)->setBottom(0.5)->setLeft(0.3)->setRight(0.3);
        $ws->getHeaderFooter()->setOddFooter('&L&8RETISS — Module Recyclage&R&8Page &P / &N');
        $ws->setSelectedCell('A1');
    }
}
