<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

/*
 * Simple front controller: index.php?page=<name> loads pages/<file>.php.
 * Each page handles its own POST requests (and redirects) before any HTML is sent.
 */
$routes = [
    'dashboard' => ['dashboard.php', 'staff'],
    'inventory' => ['inventory.php', 'staff'],
    'product'   => ['product.php',   'admin'],
    'receive'   => ['receive.php',   'staff'],
    'adjust'    => ['adjust.php',    'admin'],
    'sales'     => ['sales.php',     'staff'],
    'sale-new'  => ['sale_new.php',  'staff'],
    'sale'      => ['sale_view.php', 'staff'],
    'account'   => ['account.php',   'customer'],
    'checkout'  => ['checkout.php',  'customer'],
    'shop'      => ['shop.php',      'public'],
    'catalog'   => ['catalog.php',   'public'],
    'models'    => ['models.php',    'admin'],
    'reports'   => ['reports.php',   'staff'],
    'settings'  => ['settings.php',  'login'],
    'users'     => ['users.php',     'admin'],
    'activity'  => ['activity.php',  'admin'],
    'about'     => ['about.php',     'public'],
];

$fallback = !is_logged_in() ? 'shop' : (is_staff() ? 'dashboard' : 'shop');
$page = get('page') ?: $fallback;
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = $fallback;
    flash('error', 'That page does not exist.');
}

[$file, $access] = $routes[$page];
if ($access === 'login') {
    require_login();
} elseif ($access === 'staff') {
    require_staff();
} elseif ($access === 'admin') {
    require_admin();
} elseif ($access === 'customer') {
    require_customer();
}

require __DIR__ . '/pages/' . $file;
