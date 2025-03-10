<?php

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PhpMyAdmin\OutputBuffering;

/** @psalm-suppress InvalidGlobal */
global $containerBuilder;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

if (PHP_VERSION_ID < 70205) {
    die('<p>PHP 7.2.5+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require_once ROOT_PATH . 'libraries/vendor_config.php';

/**
 * Activate autoloader
 */
if (! @is_readable(AUTOLOAD_FILE)) {
    die(
        '<p>File <samp>' . AUTOLOAD_FILE . '</samp> missing or not readable.</p>'
        . '<p>Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">'
        . 'install library files</a>.</p>'
    );
}

require AUTOLOAD_FILE;

chdir('..');

// Send correct type.
header('Content-Type: text/javascript; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this file is reloaded when config changes.
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

$isMinimumCommon = true;
// phpcs:disable PSR1.Files.SideEffects
define('PMA_PATH_TO_BASEDIR', '../');
define('PMA_NO_SESSION', true);
// phpcs:enable

Common::run();

$buffer = OutputBuffering::getInstance();
$buffer->start();

register_shutdown_function(static function () {
    echo OutputBuffering::getInstance()->getContents();
});

/** @var JavaScriptMessagesController $controller */
$controller = $containerBuilder->get(JavaScriptMessagesController::class);
$controller();
