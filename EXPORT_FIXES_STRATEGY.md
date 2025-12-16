# Stratégie de correction des exports Excel

## Diagnostic résumé

| Fichier | Statut | Problème |
|---------|--------|---------|
| ventes/export_excel.php | ✅ OK | Header moderne .xlsx + fputcsv (compatibilité ok) |
| caisse/export_journal.php | ⚠️ BOM PRE-HEADER | BOM ligne 51 AVANT headers (headers already sent error) |
| caisse/export_excel.php | ⚠️ BOM PRE-HEADER | BOM ligne 51 AVANT headers (headers already sent error) |
| compta/export_balance.php | ✅ OK | CSV + BOM, UTF-8, correct |
| compta/export_grand_livre.php | ? | À vérifier |
| compta/export_bilan.php | ⚠️ BOM PRE-HEADER | Même problème BOM |
| livraisons/export_excel.php | ✅ OK | Header moderne .xlsx + fputcsv |
| coordination/export_excel.php | ? | À vérifier |
| compta/export_journal.php | ? | À vérifier |

## Corrections à appliquer

### Type 1: BOM BEFORE Headers (URGENT - bloque le téléchargement)
- **caisse/export_journal.php** (ligne 51)
- **caisse/export_excel.php** (ligne 51)
- **compta/export_bilan.php** (ligne ?)

**Correction**: Déplacer `echo "\xEF\xBB\xBF";` APRÈS les headers (ligne suivant les headers)

### Type 2: Vérifier Headers manquants
- Les fichiers CSV doivent avoir: `header('Content-Type: text/csv; charset=UTF-8');`
- Les fichiers .xlsx doivent avoir: `header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');`

### Type 3: Ajouter Cache-Control si manquant
Tous les exports doivent avoir:
```php
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
```

## Plan d'action

1. Déplacer tous les `echo "\xEF\xBB\xBF";` après les headers
2. Vérifier tous les Content-Type déclarés
3. Ajouter Cache-Control partout
4. Tester chaque export manuellement
