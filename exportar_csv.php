<?php
// exportar_csv.php — descarga la vista de indicadores en CSV
// (útil para cargar a Google Sheets -> Looker Studio)
require __DIR__ . '/config/db.php';
	requiereTecnico();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="indicadores_tickets.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel respete las tildes

$rows = $pdo->query("SELECT * FROM vista_indicadores ORDER BY id")->fetchAll();
if ($rows) {
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
}
fclose($out);
