<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/reports.php';
require_staff();

$types = report_types();
$from = $to = null;

if ($logId = (int)get('log')) {
    $log = db_one('SELECT * FROM report_logs WHERE id = ?', [$logId]);
    if (!$log) { http_response_code(404); exit('Report not found.'); }
    $type = $log['report_type'];
    $from = $log['period_from'];
    $to = $log['period_to'];
    $suffix = $log['report_no'];
} else {
    $type = get('report');
    if (!isset($types[$type])) { http_response_code(400); exit('Unknown report.'); }
    if ($types[$type]['range']) {
        $from = valid_date(get('from')) ? get('from') : date('Y-m-01');
        $to = valid_date(get('to')) ? get('to') : today();
        if ($from > $to) [$from, $to] = [$to, $from];
    }
    $suffix = $from ? "$from-to-$to" : date('Y-m-d');
}

$data = report_data($type, $from, $to);
log_activity('Exported CSV', $types[$type]['title']);

$filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($types[$type]['title'])) . '_' . $suffix . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₱ and accents correctly

$safe = function ($v) {
    // Neutralise spreadsheet formula injection.
    $v = (string)($v ?? '');
    return preg_match('/^[=+\-@\t\r]/', $v) && !is_numeric($v) ? "'" . $v : $v;
};

fputcsv($out, [store_name() . ' — ' . $types[$type]['title']]);
fputcsv($out, [$from ? "Period: $from to $to" : 'As of ' . now()]);
fputcsv($out, ['Exported by ' . current_user()['full_name'] . ' on ' . now()]);
fputcsv($out, []);
fputcsv($out, array_map(fn($c) => $c[1], $data['columns']));
foreach ($data['rows'] as $row) {
    fputcsv($out, array_map(fn($c) => $safe($row[$c[0]] ?? ''), $data['columns']));
}
fputcsv($out, []);
foreach ($data['summary'] as [$label, $value]) {
    fputcsv($out, [$label, $value]);
}
fclose($out);
