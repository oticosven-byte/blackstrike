<?php
declare(strict_types=1);

define('SKIP_INSTALL_CHECK', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/schema.php';

$lockFile = APP_ROOT . '/storage/installed.lock';
$cfg = $GLOBALS['config']['db'];
$errors = [];
$done = false;
$alreadyInstalled = is_installed();

$form = [
    'full_name' => post('full_name', 'Shop Administrator'),
    'username'  => post('username', 'admin'),
    'email'     => post('email'),
    'sample'    => is_post() ? isset($_POST['sample']) : true,
    'reset'     => isset($_POST['reset']),
];

if (is_post() && !$alreadyInstalled) {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    if ($form['full_name'] === '') $errors[] = 'Enter the administrator\'s full name.';
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $form['username'])) $errors[] = 'Username must be 3–60 characters: letters, numbers, dot, dash or underscore.';
    if ($p = password_problem($password)) $errors[] = $p;
    if ($password !== (string)($_POST['password_confirm'] ?? '')) $errors[] = 'The two passwords do not match.';
    if ($cfg['driver'] !== 'sqlite' && !preg_match('/^[a-zA-Z0-9_]+$/', $cfg['database'])) $errors[] = 'The database name in config.php may only contain letters, numbers and underscores.';

    if (!$errors) {
        try {
            $driver = db_driver();
            if ($driver === 'mysql') {
                // XAMPP: creates the database. Shared hosts create it in their control panel and block this, so ignore a failure.
                try {
                    db(false)->exec('CREATE DATABASE IF NOT EXISTS `' . $cfg['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                } catch (PDOException $e) {
                }
            }
            $pdo = db();
            [$tables, $indexes] = schema_statements($driver);

            $existing = [];
            foreach (array_keys($tables) as $t) {
                try {
                    $pdo->query("SELECT 1 FROM $t LIMIT 1");
                    $existing[] = $t;
                } catch (Throwable $e) {
                }
            }
            if ($existing && !$form['reset']) {
                throw new RuntimeException('The database already contains BlackStrike tables (' . implode(', ', $existing) . '). Tick “Replace existing data” to wipe and reinstall.');
            }
            if ($existing) {
                if ($driver === 'mysql') $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                if ($driver === 'sqlite') $pdo->exec('PRAGMA foreign_keys = OFF');
                foreach (array_reverse(array_keys($tables)) as $t) {
                    $pdo->exec("DROP TABLE IF EXISTS $t");
                }
                if ($driver === 'mysql') $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                if ($driver === 'sqlite') $pdo->exec('PRAGMA foreign_keys = ON');
            }

            foreach ($tables as $sql) {
                $pdo->exec($sql);
            }
            foreach ($indexes as $sql) {
                $pdo->exec($sql);
            }

            $pdo->beginTransaction();
            foreach (default_settings() as $k => $v) {
                q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$k, $v]);
            }
            $adminId = db_insert('users', [
                'username' => $form['username'], 'full_name' => $form['full_name'], 'email' => $form['email'] ?: null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => 'admin',
                'is_active' => 1, 'created_at' => now(),
            ]);
            if ($form['sample']) {
                seed_sample_data($adminId);
            }
            db_insert('activity_log', ['user_id' => $adminId, 'action' => 'System installed', 'details' => 'Version ' . APP_VERSION, 'ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), 'created_at' => now()]);
            $pdo->commit();

            if (!is_dir(dirname($lockFile))) {
                mkdir(dirname($lockFile), 0775, true);
            }
            file_put_contents($lockFile, 'Installed ' . now() . PHP_EOL);
            $done = true;
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $code = (int)($e->errorInfo[1] ?? 0);
            $msg = $e->getMessage();
            if ($code === 2002 || str_contains($msg, '2002') || str_contains($msg, 'refused')) {
                $errors[] = 'Could not reach MySQL. Open the XAMPP Control Panel and click “Start” next to MySQL, then try again.';
            } elseif ($code === 1045 || str_contains($msg, '1045')) {
                $errors[] = 'MySQL rejected the username/password. Update the “username” and “password” values in config.php.';
            } else {
                $errors[] = 'Database error: ' . $msg;
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install · BlackStrike</title>
    <link rel="icon" href="<?= e(favicon_href()) ?>">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?= APP_VERSION ?>">
    <script src="assets/js/app.js?v=<?= APP_VERSION ?>" defer></script>
</head>
<body class="auth-body">
<main class="install-wrap">
    <div class="install-card">
        <div class="install-head">
            <?= brand_emblem(58) ?>
            <div>
                <div class="eyebrow">SETUP WIZARD · v<?= APP_VERSION ?></div>
                <h1>Install <span class="two-tone"><span>Black</span><span>Strike</span></span></h1>
            </div>
        </div>

        <?php if ($done): ?>
            <div class="alert alert-success"><?= icon('check') ?> Installation complete. The database “<?= e($cfg['database']) ?>” is ready.</div>
            <ul class="check-list">
                <li><?= icon('check') ?> Tables and settings created</li>
                <li><?= icon('check') ?> Administrator account <strong><?= e($form['username']) ?></strong> created</li>
                <?php if ($form['sample']): ?><li><?= icon('check') ?> Sample catalog and 7 months of sample sales loaded (staff login: <code>staff</code> / <code>Staff@1234</code>)</li><?php endif; ?>
            </ul>
            <p class="muted">For security, you can delete <code>install.php</code> now. To reinstall later, delete <code>storage/installed.lock</code>.</p>
            <a class="btn btn-primary" href="login.php"><?= icon('login') ?> Go to sign in</a>

        <?php elseif ($alreadyInstalled): ?>
            <div class="alert alert-info"><?= icon('info') ?> This system is already installed.</div>
            <p class="muted">To reinstall, delete <code>storage/installed.lock</code> and reload this page.</p>
            <a class="btn btn-primary" href="login.php"><?= icon('login') ?> Go to sign in</a>

        <?php else: ?>
            <div class="install-env">
                <div><span>Database driver</span><strong><?= e(strtoupper(db_driver())) ?></strong></div>
                <div><span>Server</span><strong><?= e(db_driver() === 'sqlite' ? 'Local file' : $cfg['host'] . ':' . $cfg['port']) ?></strong></div>
                <div><span>Database</span><strong><?= e(db_driver() === 'sqlite' ? basename($cfg['sqlite_path']) : $cfg['database']) ?></strong></div>
                <div><span>PHP</span><strong><?= e(PHP_VERSION) ?></strong></div>
            </div>
            <p class="muted small">Connection settings come from <code>config.php</code>. The database is created automatically if it doesn’t exist.</p>

            <?php if (version_compare(PHP_VERSION, '8.0.0', '<')): ?>
                <div class="alert alert-error">PHP 8.0 or newer is required. Please update XAMPP.</div>
            <?php endif; ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error" role="alert"><?= icon('alert') ?> <?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-grid" novalidate>
                <?= csrf_field() ?>
                <h2 class="form-section span-2">Administrator account</h2>
                <label class="field"><span>Full name</span><input name="full_name" value="<?= e($form['full_name']) ?>" required></label>
                <label class="field"><span>Username</span><input name="username" value="<?= e($form['username']) ?>" required autocomplete="username"></label>
                <label class="field span-2"><span>Email <em>(optional)</em></span><input name="email" type="email" value="<?= e($form['email']) ?>"></label>
                <label class="field"><span>Password</span><input name="password" type="password" required autocomplete="new-password" minlength="8"><small>At least 8 characters with a letter and a number.</small></label>
                <label class="field"><span>Confirm password</span><input name="password_confirm" type="password" required autocomplete="new-password"></label>

                <h2 class="form-section span-2">Options</h2>
                <label class="check span-2"><input type="checkbox" name="sample" <?= $form['sample'] ? 'checked' : '' ?>> <span><strong>Load sample data</strong> — gun models, catalog, serial-numbered firearms and 7 months of sales so you can explore every screen.</span></label>
                <label class="check span-2"><input type="checkbox" name="reset" <?= $form['reset'] ? 'checked' : '' ?>> <span><strong>Replace existing data</strong> — drops any existing BlackStrike tables in this database first.</span></label>

                <div class="span-2 form-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Install now</button></div>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
