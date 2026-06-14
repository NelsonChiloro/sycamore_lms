<?php
/**
 * Emergency session reset — bypasses CodeIgniter so it never waits on a locked session file.
 * Use when the site keeps loading/spinning in the browser.
 */
$cookiePath = '/sycamore_lms/';

foreach (array_keys($_COOKIE) as $name) {
    if (strpos($name, 'ci_session') !== false) {
        setcookie($name, '', time() - 3600, $cookiePath);
        setcookie($name, '', time() - 3600, '/');
    }
}

$sessionDirs = array(
    'C:/wamp64/tmp',
    sys_get_temp_dir(),
    __DIR__ . '/application/cache/sessions',
);

foreach ($sessionDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    foreach (glob($dir . '/ci_session*') ?: array() as $file) {
        @unlink($file);
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Location: /sycamore_lms/', true, 302);
exit;
