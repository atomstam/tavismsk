<?php
/**
 * Set the admin password. Pass it as an argument; it is not stored in git.
 * Usage: php scripts/set_admin_password.php 'your-password'
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$plain = (string) ($argv[1] ?? '');
if ($plain === '') {
    fwrite(STDERR, "Usage: php scripts/set_admin_password.php <password>\n");
    exit(1);
}

$hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
db()->query('UPDATE users SET password = ? WHERE username = ?', [$hash, 'admin']);
echo "admin password set\n";
