<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/reports.php';
require_login();

$types = report_types();
$user = current_user();

/* ---------- Generate a new numbered report (POST) ---------- */
if (is_post()) {
    verify_csrf();
    require_staff();
    $type = post('report');
    if (!isset($types[$type])) {
        flash('error', 'Unknown report type.');
        redirect(href('reports'));
    }
    $from = $to = null;
    if ($types[$type]['range']) {
        $from = post('from');
        $to = post('to');
        if (!valid_date($from) || !valid_date($to)) {
            flash('error', 'Choose a valid date range for the report.');
            redirect(href('reports') . '#official');
        }
        if ($from > $to) [$from, $to] = [$to, $from];
    }
    $reportNo = next_number('BS-' . $types[$type]['code'], 'report_logs', 'report_no');
    $logId = db_insert('report_logs', [
        'report_no' => $reportNo, 'report_type' => $type, 'period_from' => $from, 'period_to' => $to,
        'generated_by' => $user['id'], 'created_at' => now(),
    ]);
    log_activity('Generated report', $types[$type]['title'] . ' · ' . $reportNo);
    redirect('print.php?log=' . $logId . '&auto=1');
}

$store = store_name();
$certification = setting('report_certification', 'I hereby certify that the information contained in this report is true and correct.');

/* ---------- Load what to print ---------- */
$mode = null;
if ($receiptId = (int)get('receipt')) {
    $sale = db_one('SELECT s.*, u.full_name AS cashier FROM sales s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$receiptId]);
    if (!$sale) { http_response_code(404); exit('Sale not found.'); }
    // Customers may print only their own receipts.
    if (!is_staff() && (int)($sale['customer_user_id'] ?? 0) !== (int)$user['id']) {
        http_response_code(403);
        flash('error', 'You can only view your own receipts.');
        redirect(home_page());
    }
    $items = db_all('SELECT i.*, p.name, p.sku, p.caliber, p.category, f.serial_number FROM sale_items i JOIN products p ON p.id = i.product_id LEFT JOIN firearm_serials f ON f.id = i.serial_id WHERE i.sale_id = ? ORDER BY i.id', [$receiptId]);
    $mode = 'receipt';
    $docTitle = 'Sales Receipt ' . $sale['sale_no'];
    $landscape = false;
} elseif ($logId = (int)get('log')) {
    require_staff();
    $log = db_one('SELECT r.*, u.full_name FROM report_logs r LEFT JOIN users u ON u.id = r.generated_by WHERE r.id = ?', [$logId]);
    if (!$log || !isset($types[$log['report_type']])) { http_response_code(404); exit('Report not found.'); }
    $def = $types[$log['report_type']];
    $data = report_data($log['report_type'], $log['period_from'], $log['period_to']);
    $mode = 'report';
    $docTitle = $def['title'] . ' ' . $log['report_no'];
    $landscape = $def['landscape'];
    $isReprint = !get('auto');
} else {
    redirect(href('reports'));
}

function letterhead(string $store): void
{ ?>
    <header class="letterhead">
        <div class="lh-brand">
            <?= brand_emblem(48) ?>
            <div>
                <?php [$b1, $b2] = brand_parts($store); ?>
                <strong class="lh-name"><?= e(mb_strtoupper($b1)) ?><span><?= e(mb_strtoupper($b2)) ?></span></strong>
                <?php if (setting('brand_caption')): ?><span class="lh-caption"><?= e(mb_strtoupper(setting('brand_caption'))) ?></span><?php endif; ?>
                <span><?= e(setting('address')) ?></span>
                <span><?= e(implode(' · ', array_filter([setting('phone'), setting('email')]))) ?></span>
            </div>
        </div>
        <dl class="lh-ids">
            <?php if (setting('license_no')): ?><dt>Dealer License No.</dt><dd><?= e(setting('license_no')) ?></dd><?php endif; ?>
            <?php if (setting('tin')): ?><dt>TIN</dt><dd><?= e(setting('tin')) ?></dd><?php endif; ?>
            <?php if (setting('regulator')): ?><dt>Regulator</dt><dd><?= e(setting('regulator')) ?></dd><?php endif; ?>
        </dl>
    </header>
<?php }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($docTitle) ?> · <?= e($store) ?></title>
    <link rel="icon" href="<?= e(favicon_href()) ?>">
    <link rel="stylesheet" href="assets/css/print.css?v=<?= APP_VERSION ?>">
    <style>@page { size: A4 <?= $landscape ? 'landscape' : 'portrait' ?>; margin: 14mm 12mm 16mm; }</style>
</head>
<body class="<?= $landscape ? 'landscape' : 'portrait' ?>" <?= get('auto') ? 'data-autoprint' : '' ?>>
<div class="toolbar">
    <a href="<?= e($mode === 'receipt' ? (is_staff() ? href('sale', ['id' => $sale['id']]) : href('account')) : href('reports') . '#official') ?>">← Back</a>
    <span class="toolbar-title"><?= e($docTitle) ?></span>
    <?php if ($mode === 'report'): ?><a href="export.php?log=<?= (int)$log['id'] ?>">Download CSV</a><?php endif; ?>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
</div>

<main class="sheet">
<?php if ($mode === 'receipt'): $void = $sale['status'] === 'void'; ?>
    <?php if ($void): ?><div class="watermark">VOID</div><?php endif; ?>
    <?php letterhead($store); ?>
    <section class="doc-title">
        <div><h1>Sales Receipt</h1><p>Firearms, ammunition & accessories</p></div>
        <dl class="doc-meta">
            <dt>Receipt no.</dt><dd class="mono"><?= e($sale['sale_no']) ?></dd>
            <dt>Date</dt><dd><?= e(fmt_datetime($sale['created_at'])) ?></dd>
            <dt>Cashier</dt><dd><?= e($sale['cashier'] ?? '—') ?></dd>
            <dt>Payment</dt><dd><?= e($sale['payment_method']) ?></dd>
        </dl>
    </section>
    <section class="two-col">
        <div class="box">
            <h2>Sold to</h2>
            <p><strong><?= e($sale['customer_name']) ?></strong></p>
            <p><?= e($sale['customer_address'] ?: '—') ?></p>
            <p><?= e($sale['customer_contact'] ?: '') ?></p>
        </div>
        <div class="box">
            <h2>License presented</h2>
            <?php if ($sale['license_no']): ?>
                <p><?= e($sale['license_type']) ?> · <span class="mono"><?= e($sale['license_no']) ?></span></p>
                <p>Valid until <?= e(fmt_date($sale['license_expiry'])) ?></p>
                <p><?= $sale['license_verified'] ? '☑ Original license and ID inspected' : '☐ Not verified' ?></p>
            <?php else: ?><p>Not required (accessories only)</p><?php endif; ?>
        </div>
    </section>
    <table class="report-table">
        <thead><tr><th>#</th><th>Description</th><th>Serial no.</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i => $it): ?>
            <tr><td><?= $i + 1 ?></td><td><?= e($it['name']) ?><small><?= e($it['sku'] . ($it['caliber'] ? ' · ' . $it['caliber'] : '')) ?></small></td><td class="mono"><?= e($it['serial_number'] ?: '—') ?></td><td class="num"><?= num($it['quantity']) ?></td><td class="num"><?= money($it['unit_price']) ?></td><td class="num"><?= money($it['line_total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="5" class="num">TOTAL</td><td class="num"><strong><?= money($sale['total']) ?></strong></td></tr></tfoot>
    </table>
    <?php if ($void): ?><p class="void-note"><strong>VOIDED:</strong> <?= e($sale['void_reason']) ?></p><?php endif; ?>
    <p class="fine">Firearms are released unloaded and cased. The buyer is responsible for registering and transporting firearms in accordance with applicable law. Keep this receipt as proof of purchase. This is the store’s transaction record; issue the official tax invoice separately where required.</p>
    <section class="signatures three">
        <div><span class="sig-line"></span><strong><?= e($sale['customer_name']) ?></strong><span>Buyer’s signature over printed name</span></div>
        <div><span class="sig-line"></span><strong><?= e($sale['cashier'] ?? '') ?></strong><span>Sales staff</span></div>
        <div><span class="sig-line"></span><strong>&nbsp;</strong><span>Authorized representative</span></div>
    </section>

<?php else: ?>
    <?php letterhead($store); ?>
    <section class="doc-title">
        <div>
            <h1><?= e($def['title']) ?></h1>
            <p><?= $log['period_from'] ? 'For the period ' . e(fmt_date($log['period_from'], 'F j, Y')) . ' to ' . e(fmt_date($log['period_to'], 'F j, Y')) : 'As of ' . e(fmt_date($isReprint ? now() : $log['created_at'], 'F j, Y · g:i A')) ?></p>
        </div>
        <dl class="doc-meta">
            <dt>Control no.</dt><dd class="mono"><?= e($log['report_no']) ?></dd>
            <dt>Generated</dt><dd><?= e(fmt_datetime($log['created_at'])) ?></dd>
            <dt>Generated by</dt><dd><?= e($log['full_name'] ?? '—') ?></dd>
            <?php if ($isReprint): ?><dt>Reprinted</dt><dd><?= e(fmt_datetime(now())) ?> · <?= e($user['full_name']) ?></dd><?php endif; ?>
        </dl>
    </section>

    <section class="summary-boxes">
        <?php foreach ($data['summary'] as [$label, $value]): ?>
            <div><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
        <?php endforeach; ?>
    </section>

    <?php if (!$data['rows']): ?>
        <p class="no-records">No records for this period.</p>
    <?php else: ?>
    <table class="report-table">
        <thead><tr><th>#</th><?php foreach ($data['columns'] as [$k, $label, $type]): ?><th class="<?= in_array($type, ['int', 'money', 'signed'], true) ? 'num' : '' ?>"><?= e($label) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php
        $group = $data['group_by'] ?? null;
        $current = null;
        $n = 0;
        foreach ($data['rows'] as $row):
            if ($group && $row[$group] !== $current):
                $current = $row[$group]; ?>
                <tr class="group-row"><td colspan="<?= count($data['columns']) + 1 ?>"><?= e($current) ?></td></tr>
            <?php endif; $n++; ?>
            <tr class="<?= ($row['status'] ?? '') === 'VOID' ? 'void-row' : '' ?>">
                <td><?= $n ?></td>
                <?php foreach ($data['columns'] as [$k, $label, $type]): ?>
                    <td class="<?= in_array($type, ['int', 'money', 'signed'], true) ? 'num' : '' ?> <?= $type === 'mono' ? 'mono' : '' ?> <?= in_array($type, ['date', 'datetime'], true) ? 'nowrap' : '' ?>"><?= e(format_cell($row[$k] ?? null, $type)) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if (isset($data['total_key'])): ?>
            <tfoot><tr><td colspan="<?= count($data['columns']) ?>" class="num">TOTAL (excluding voided)</td><td class="num"><strong><?= money($data['total_value']) ?></strong></td></tr></tfoot>
        <?php endif; ?>
    </table>
    <p class="end-of-report">*** END OF REPORT · <?= num(count($data['rows'])) ?> record<?= count($data['rows']) === 1 ? '' : 's' ?> ***</p>
    <?php endif; ?>

    <section class="certification">
        <p><?= e($certification) ?></p>
    </section>
    <section class="signatures three">
        <div><span class="sig-line"></span><strong><?= e($log['full_name'] ?? '') ?></strong><span>Prepared by</span></div>
        <div><span class="sig-line"></span><strong>&nbsp;</strong><span>Checked / verified by</span></div>
        <div><span class="sig-line"></span><strong>&nbsp;</strong><span>Approved by (Owner / Manager)</span></div>
    </section>
<?php endif; ?>

    <footer class="doc-footer">
        <span><?= e($store) ?> · <?= e($mode === 'receipt' ? $sale['sale_no'] : $log['report_no']) ?></span>
        <span>System-generated record · Printed <?= e(fmt_datetime(now())) ?></span>
    </footer>
</main>
<script>
    if (document.body.hasAttribute('data-autoprint')) {
        window.addEventListener('load', () => setTimeout(() => window.print(), 350));
    }
</script>
</body>
</html>
