<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    verify_csrf();
    logout();
    session_start();
    session_regenerate_id(true);
    flash('success', 'You have been signed out.');
}
redirect('login.php');
