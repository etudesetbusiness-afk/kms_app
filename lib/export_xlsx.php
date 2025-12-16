<?php
/**
 * lib/export_xlsx.php - Export Excel avec fallback CSV si ZIP non disponible
 */

class ExportXLSX {
    /**
     * Génère un fichier XLSX ou CSV (fallback) directement en output
     * 
     * @param array $data Tableau de données [[col1, col2, ...], ...]
     * @param array $headers En-têtes colonne
     * @param string $filename Nom du fichier
     * @param string $sheetName Nom de la feuille
     */
    public static function generate($data, $headers, $filename, $sheetName = 'Sheet1') {
        // Si ZipArchive n'est pas disponible, utiliser CSV
        if (!extension_loaded('zip')) {
            self::generateCSV($data, $headers, str_replace('.xlsx', '.csv', $filename));
            return;
        }
        
        // Créer un fichier ZIP temporaire
        $tempZip = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        
        if ($zip->open($tempZip, ZipArchive::CREATE) !== true) {
            // Fallback CSV si impossible de créer le ZIP
            self::generateCSV($data, $headers, str_replace('.xlsx', '.csv', $filename));
            return;
        }
        
        // Ajouter les fichiers nécessaires à une structure XLSX
        
        // 1. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);
        
        // 2. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        
        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        
        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="' . htmlspecialchars($sheetName) . '" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);
        
        // 5. xl/worksheets/sheet1.xml (contenu des données)
        $sheetContent = self::buildSheetXml($data, $headers);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetContent);
        
        // 6. docProps/core.xml
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>' . htmlspecialchars($sheetName) . '</dc:title>
    <dc:creator>KENNE MULTI-SERVICES</dc:creator>
    <dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>
</cp:coreProperties>';
        $zip->addFromString('docProps/core.xml', $core);
        
        // Fermer et envoyer le ZIP
        $zip->close();
        
        // Envoyer au navigateur
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($tempZip);
        unlink($tempZip);
        exit;
    }
    
    /**
     * Génère un fichier CSV (fallback quand ZIP n'est pas disponible)
     */
    public static function generateCSV($data, $headers, $filename) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM UTF-8 pour Excel
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Écrire les en-têtes
        if (!empty($headers)) {
            fputcsv($output, $headers, ';');
        }
        
        // Écrire les données
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Construit le XML du contenu de la feuille
     */
    private static function buildSheetXml($data, $headers) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';
        
        $rowIndex = 1;
        
        // Ajouter les en-têtes
        if (!empty($headers)) {
            $xml .= '<row r="' . $rowIndex . '">';
            $colIndex = 1;
            foreach ($headers as $header) {
                $colLetter = self::getColumnLetter($colIndex);
                $xml .= '<c r="' . $colLetter . $rowIndex . '" t="inlineStr"><is><t>' . self::escapeXml($header) . '</t></is></c>';
                $colIndex++;
            }
            $xml .= '</row>';
            $rowIndex++;
        }
        
        // Ajouter les données
        foreach ($data as $row) {
            $xml .= '<row r="' . $rowIndex . '">';
            $colIndex = 1;
            foreach ($row as $cell) {
                $colLetter = self::getColumnLetter($colIndex);
                // Déterminer si c'est un nombre ou du texte
                $isNumber = is_numeric($cell) && $cell !== '';
                if ($isNumber) {
                    $xml .= '<c r="' . $colLetter . $rowIndex . '" t="n"><v>' . (float)$cell . '</v></c>';
                } else {
                    $xml .= '<c r="' . $colLetter . $rowIndex . '" t="inlineStr"><is><t>' . self::escapeXml($cell) . '</t></is></c>';
                }
                $colIndex++;
            }
            $xml .= '</row>';
            $rowIndex++;
        }
        
        $xml .= '</sheetData>
</worksheet>';
        
        return $xml;
    }
    
    /**
     * Convertit un index numérique en lettre de colonne Excel (A, B, C, ... Z, AA, AB...)
     */
    private static function getColumnLetter($index) {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }
        return $letter;
    }
    
    /**
     * Échappe les caractères XML
     */
    private static function escapeXml($string) {
        $string = (string)$string;
        return htmlspecialchars($string, ENT_XML1, 'UTF-8');
    }
}
