
<?php

function exportCsv(string $filename, array $headers, array $rows): void
{
    // Clear output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $filename);

    $safeName .= '_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');

    header(
        'Content-Disposition: attachment; filename="' . $safeName . '"'
    );

    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // UTF-8 BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, array_values($row));
    }

    fclose($out);

    exit;
}

?>
