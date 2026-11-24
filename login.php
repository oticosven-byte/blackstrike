<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(home_page());
}

$error = '';
$username = '';
if (is_post()) {
    verify_csrf();
    $username = post('username');
    [$ok, $error] = attempt_login($username, (string)($_POST['password'] ?? ''));
    if ($ok) {
        $to = $_SESSION['intended'] ?? home_page();
        unset($_SESSION['intended']);
        // Only follow local redirects.
        if (!is_string($to) || preg_match('#^(https?:)?//#i', $to)) {
            $to = home_page();
        }
        flash('success', 'Welcome back, ' . current_user()['full_name'] . '.');
        redirect($to);
    }
}
$store = store_name();
$flashes = take_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08090b">
    <title>Sign in · <?= e($store) ?></title>
    <link rel="icon" href="<?= e(favicon_href()) ?>">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?= APP_VERSION ?>">
    <script src="assets/js/app.js?v=<?= APP_VERSION ?>" defer></script>
</head>
<body class="auth-body">
<main class="auth-layout">
    <section class="auth-visual" aria-hidden="true">
        <div class="hero-art"><span class="hero-orbit orbit-one"></span><span class="hero-orbit orbit-two"></span><span class="hero-grid"></span></div>
        <div class="auth-visual-copy">
            <?= brand_logo('auth') ?>
            <h2>READY. <em>STOCKED.</em> SECURE.</h2>
            <p>Inventory, sales and compliance records for licensed dealers, all in one place.</p>
        </div>
    </section>
    <section class="auth-panel">
        <form class="auth-card" method="post" novalidate>
            <?= csrf_field() ?>
            <div class="eyebrow">ACCOUNT ACCESS</div>
            <h1>Sign in</h1>
            <p class="muted">Staff and customers sign in here with their username.</p>

            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?php if ($error): ?><div class="alert alert-error" role="alert"><?= icon('alert') ?> <?= e($error) ?></div><?php endif; ?>

            <label class="field">
                <span>Username</span>
                <input name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
            </label>
            <label class="field">
                <span>Password</span>
                <span class="password-wrap">
                    <input name="password" type="password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" aria-label="Show password" data-toggle-password>Show</button>
                </span>
            </label>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('lock') ?> Sign in</button>
            <?php if (setting('allow_registration', '1') === '1'): ?>
                <p class="auth-register">New customer? <a class="link" href="register.php">Create an account</a></p>
            <?php endif; ?>
            <div class="auth-links">
                <a href="<?= e(href('catalog')) ?>"><?= icon('book') ?> Browse catalog</a>
                <a href="<?= e(href('about')) ?>"><?= icon('store') ?> About the shop</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
