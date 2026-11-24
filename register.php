<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(home_page());
}
if (setting('allow_registration', '1') !== '1') {
    flash('info', 'Online registration is turned off. Please ask our staff to create an account for you.');
    redirect('login.php');
}

$errors = [];
$f = [
    'full_name' => post('full_name'), 'username' => post('username'), 'email' => post('email'),
    'phone' => post('phone'), 'address' => post('address'),
];

if (is_post()) {
    verify_csrf();
    // Simple throttle: at most 5 sign-ups per browser session per hour.
    $_SESSION['signups'] = array_filter($_SESSION['signups'] ?? [], fn($t) => $t > time() - 3600);
    $password = (string)($_POST['password'] ?? '');

    if (count($_SESSION['signups']) >= 5) $errors['general'] = 'Too many accounts created from this browser. Please try again later.';
    if ($f['full_name'] === '' || mb_strlen($f['full_name']) > 120) $errors['full_name'] = 'Enter your full name.';
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $f['username'])) $errors['username'] = '3–60 characters: letters, numbers, dot, dash or underscore.';
    elseif (db_val('SELECT COUNT(*) FROM users WHERE username = ?', [$f['username']])) $errors['username'] = 'That username is already taken.';
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if ($p = password_problem($password)) $errors['password'] = $p;
    if ($password !== (string)($_POST['password_confirm'] ?? '')) $errors['password_confirm'] = 'Passwords do not match.';
    if (!isset($_POST['agree'])) $errors['agree'] = 'Please confirm you understand the purchase requirements.';

    if (!$errors) {
        $id = db_insert('users', [
            'username' => $f['username'], 'full_name' => $f['full_name'], 'email' => $f['email'] ?: null,
            'phone' => $f['phone'] ?: null, 'address' => $f['address'] ?: null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => 'user',
            'is_active' => 1, 'created_at' => now(),
        ]);
        $_SESSION['signups'][] = time();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $id]);
        log_activity('Customer registered', $f['username']);
        flash('success', 'Welcome to ' . store_name() . ', ' . $f['full_name'] . '! Add your license details to speed up in-store purchases.');
        redirect(href('settings'));
    }
}
$store = store_name();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08090b">
    <title>Create account · <?= e($store) ?></title>
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
            <h2>YOUR <em>ACCOUNT.</em> YOUR RECORDS.</h2>
            <ul class="auth-perks">
                <li><?= icon('check') ?> See every purchase and reprint your receipts</li>
                <li><?= icon('check') ?> Keep your license details on file for faster checkout</li>
                <li><?= icon('check') ?> Get reminded before your license expires</li>
            </ul>
        </div>
    </section>
    <section class="auth-panel">
        <form class="auth-card wide" method="post" novalidate>
            <?= csrf_field() ?>
            <div class="eyebrow">CUSTOMER ACCOUNT</div>
            <h1>Create account</h1>
            <p class="muted">Already registered? <a class="link" href="login.php">Sign in</a></p>
            <?php if (isset($errors['general'])): ?><div class="alert alert-error"><?= icon('alert') ?> <?= e($errors['general']) ?></div><?php endif; ?>

            <div class="form-grid">
                <label class="field span-2"><span>Full name <em>(as shown on your license)</em></span><input name="full_name" value="<?= e($f['full_name']) ?>" required maxlength="120" autocomplete="name"><?= field_error($errors, 'full_name') ?></label>
                <label class="field"><span>Username</span><input name="username" value="<?= e($f['username']) ?>" required maxlength="60" autocomplete="username"><?= field_error($errors, 'username') ?></label>
                <label class="field"><span>Mobile number <em>(optional)</em></span><input name="phone" value="<?= e($f['phone']) ?>" maxlength="60" inputmode="tel" autocomplete="tel"></label>
                <label class="field span-2"><span>Email <em>(optional)</em></span><input name="email" type="email" value="<?= e($f['email']) ?>" maxlength="150" autocomplete="email"><?= field_error($errors, 'email') ?></label>
                <label class="field span-2"><span>Address <em>(optional)</em></span><input name="address" value="<?= e($f['address']) ?>" maxlength="255" autocomplete="street-address"></label>
                <label class="field"><span>Password</span>
                    <span class="password-wrap"><input name="password" type="password" required autocomplete="new-password" minlength="8"><button type="button" class="password-toggle" aria-label="Show password" data-toggle-password>Show</button></span>
                    <?= field_error($errors, 'password') ?><small>8+ characters with a letter and a number.</small></label>
                <label class="field"><span>Confirm password</span><input name="password_confirm" type="password" required autocomplete="new-password"><?= field_error($errors, 'password_confirm') ?></label>
                <label class="check span-2"><input type="checkbox" name="agree" <?= isset($_POST['agree']) ? 'checked' : '' ?>> <span>I understand that firearm and ammunition purchases are completed in store and require my valid license and a government-issued ID.</span></label>
                <?= field_error($errors, 'agree') ?>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('user') ?> Create my account</button>
            <div class="auth-links">
                <a href="<?= e(href('catalog')) ?>"><?= icon('book') ?> Browse catalog</a>
                <a href="<?= e(href('about')) ?>"><?= icon('store') ?> About the shop</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
