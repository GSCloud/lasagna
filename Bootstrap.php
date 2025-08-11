<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://mini.gscloud.cz
 */

declare (strict_types = 1);

use Nette\Neon\Neon;
use Tracy\Debugger;

// TIMER START
define('TESSERACT_START', microtime(true));

// directory separator
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// string separator
defined('SS') || define('SS', '_');

// current working directory
defined('ROOT') || define('ROOT', __DIR__);

// CLI SAPI external include (optional)
if (PHP_SAPI === 'cli') {
    $req = getenv('CLI_REQ');
    if ($req && file_exists($req) && is_readable($req)) {
        include_once $req;
    }
}

// PHP INI
ini_set(
    'auto_detect_line_endings',
    defined('AUTO_DETECT_LINE_ENDINGS') ? AUTO_DETECT_LINE_ENDINGS : 'true'
);
ini_set(
    'default_socket_timeout',
    defined('DEFAULT_SOCKET_TIMEOUT') ? DEFAULT_SOCKET_TIMEOUT : '15'
);
ini_set(
    'display_errors',
    defined('DISPLAY_ERRORS') ? DISPLAY_ERRORS : 'true'
);

ob_start();
error_reporting(E_ALL);

// is FastCGI on?
if (getenv('FCGI_ROLE') === 'RESPONDER') {
    define('FCGI_ENABLED', true);
} else {
    define('FCGI_ENABLED', false);
}

// WEB root, not mandatory for the main code or CLI
defined('WWW') || define('WWW', ROOT . DS . 'www');

// APP directory, mandatory for the main code (App.php etc. loaded from here)
$d = 'APP';
$x = ROOT . DS . 'app';
if (defined($d) && is_dir($d) && is_readable($d)) {
} else {
    if (is_dir($x) && is_readable($x)) {
        define($d, $x);
    } else {
        die('Could not set APP directory: ' . $x);
    }
}

// CACHE directory, can use the system temp
$d = 'CACHE';
$x = ROOT . DS . 'temp';
if (defined($d) && is_dir($d) && is_readable($d) && is_writable($d)) {
} else {
    if (is_dir($x) && is_readable($x) && is_writable($x)) {
        define($d, $x);
    } else {
        define($d, '/tmp');
        if (is_dir(CACHE) && is_readable(CACHE) && is_writable(CACHE)) {
        } else {
            die('Could not set CACHE directory: ' . $d);
        }
    }
}

// DATA directory, can use the system temp
$d = 'DATA';
$x = ROOT . DS . 'data';
if (defined($d) && is_dir($d) && is_readable($d) && is_writable($d)) {
} else {
    if (is_dir($x) && is_readable($x) && is_writable($x)) {
        define($d, $x);
    } else {
        define($d, '/tmp');
        if (is_dir(DATA) && is_readable(DATA) && is_writable(DATA)) {
        } else {
            die('Could not set DATA directory: ' . $d);
        }
    }
}

// configuration file, test later
defined('CONFIG') || define('CONFIG', APP . DS . 'config.neon');

// private configuration file, test later
defined('CONFIG_PRIVATE') || define(
    'CONFIG_PRIVATE', APP . DS . 'config_private.neon'
);

// Docker configuration file, test later
defined('CONFIG_DOCKER') || define(
    'CONFIG_DOCKER', APP . DS . 'config_docker.neon'
);


// LOGS storage for Tracy
$d = 'LOGS';
$x = ROOT . DS . 'logs';
if (defined($d) && is_readable($d) && is_writable($d)) {
} else {
    if (is_dir($x) && is_readable($x) && is_writable($x)) {
        define($d, $x);
    } else {
        define($d, '/tmp');
        if (is_dir(LOGS) && is_readable(LOGS) && is_writable(LOGS)) {
        } else {
            die('Could not set LOGS directory: ' . $d);
        }
    }
}

// TEMP files storage
$d = 'TEMP';
$x = ROOT . DS . 'temp';
if (defined($d) && is_readable($d) && is_writable($d)) {
} else {
    if (is_dir($x) && is_readable($x) && is_writable($x)) {
        define($d, $x);
    } else {
        define($d, '/tmp');
        if (is_dir(TEMP) && is_readable(TEMP) && is_writable(TEMP)) {
        } else {
            die('Could not set TEMP directory: ' . $d);
        }
    }
}

// running from CLI?
defined('CLI') || define('CLI', (bool) (PHP_SAPI === 'cli'));
if (CLI) {
    defined('HOST') || define('HOST', null);
    defined('SERVER') || define('SERVER', null);
    defined('DOMAIN') || define('DOMAIN', null);
}

// running on localhost?
defined('LOCALHOST') || define(
    'LOCALHOST', (bool) (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') || CLI
);

// Composer autoloader
require_once ROOT . DS . 'vendor' . DS . 'autoload.php';

// load CONFIGURATION
$cfg = null;
if (file_exists(CONFIG) && is_readable(CONFIG)) {
    $cfg_content = file_get_contents(CONFIG);
    if ($cfg_content) {
        try {
            $cfg = Neon::decode($cfg_content);
        } catch (\Nette\Neon\Exception $e) {
            error_log("Error parsing NE-ON file: " . CONFIG);
            die('FATAL ERROR in: ' . CONFIG . ': '. $e->getMessage());
        }
    } else {
        $cfg = null;
    }
    if (!is_array($cfg)) {
        $err = 'FATAL ERROR: INVALID CONFIG';
        error_log($err);
        die($err);
    }
    try {
        if (file_exists(CONFIG_PRIVATE) && is_readable(CONFIG_PRIVATE)) {
            $arr = null;
            if ($content = file_get_contents(CONFIG_PRIVATE)) {
                $arr = Neon::decode($content);
            }
            if (!is_array($arr)) {
                $err = 'FATAL ERROR: Error parsing: ' . CONFIG_PRIVATE;
                error_log($err);
                die($err);
            }
            $cfg = array_replace_recursive($cfg, $arr);
        }
    } catch (\Nette\Neon\Exception $e) {
        die('FATAL ERROR in: ' . CONFIG_PRIVATE . ': '. $e->getMessage()); // phpcs:ignore
    }
    try {
        if (!file_exists(ROOT . DS . '.env')) {
            if (file_exists(CONFIG_DOCKER) && is_readable(CONFIG_DOCKER)) {
                $arr = null;
                if ($content = file_get_contents(CONFIG_DOCKER)) {
                    $arr = Neon::decode($content);
                }
                if (!is_array($arr)) {
                    $err = 'FATAL ERROR: Error parsing: ' . CONFIG_DOCKER;
                    error_log($err);
                    die($err);
                }
                $cfg = array_replace_recursive($cfg, $arr);
            }
        }
    } catch (\Nette\Neon\Exception $e) {
        die('FATAL ERROR: ' . $e->getMessage());
    }
} else {
    $err = 'FATAL ERROR: CONFIG file not found';
    error_log($err);
    die($err);
}
if (!is_array($cfg)) {
    $err = 'FATAL ERROR: INVALID CONFIG';
    error_log($err);
    die($err);
}

// DEFAULT TIME ZONE
date_default_timezone_set(
    (string) ($cfg['date_default_timezone'] ?? 'Europe/Prague')
);

// DEBUGGER
if (CLI === true) {
    defined('DEBUG') || define('DEBUG', false);
}
if (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') {
    if (($cfg['dbg'] ?? null) === false) {
        defined('DEBUG') || define('DEBUG', false); // DISABLED - configuration
    }
    defined('DEBUG') || define('DEBUG', true); // ENABLED - localhost
}
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'curl') !== false) {
        defined('DEBUG') || define('DEBUG', false); // DISABLED - curl
    }
}
defined('DEBUG') || define('DEBUG', (bool) ($cfg['dbg'] ?? false));

if (DEBUG === true) {
    // https://api.nette.org/tracy/master/Tracy.html
    // https://www.php.net/manual/en/errorfunc.constants.php
    Debugger::$logDirectory = LOGS;
    Debugger::$logSeverity = 15;
    Debugger::$dumpTheme = (string) ($cfg['DEBUG_DUMP_THEME'] ?? 'dark');
    Debugger::$maxDepth = (int) ($cfg['DEBUG_MAX_DEPTH'] ?? 10);
    Debugger::$maxItems = (int) ($cfg['DEBUG_MAX_ITEMS'] ?? 1000);
    Debugger::$maxLength = (int) ($cfg['DEBUG_MAX_LENGTH'] ?? 500);
    Debugger::$scream = (bool) ($cfg['DEBUG_SCREAM'] ?? true);
    Debugger::$showBar = (bool) ($cfg['DEBUG_SHOW_BAR'] ?? true);
    Debugger::$showFireLogger = (bool) ($cfg['DEBUG_SHOW_FIRELOGGER'] ?? false);
    Debugger::$showLocation = (bool) ($cfg['DEBUG_SHOW_LOCATION'] ?? false);
    Debugger::$strictMode = (bool) ($cfg['DEBUG_STRICT_MODE'] ?? true);

    // debug cookie name: tracy-debug
    if ($cfg['DEBUG_COOKIE'] ?? null) {
        if (($_COOKIE['tracy-debug'] ?? null) === $cfg['DEBUG_COOKIE']) {
            Debugger::enable(Debugger::Development);
        } else {
            Debugger::enable(Debugger::Production);
        }
    } else {
        Debugger::enable(
            (bool) ($cfg['DEBUG_DEVELOPMENT_MODE'] ?? true)
            ? Debugger::DEVELOPMENT : Debugger::DETECT, LOGS
        );
    }
}

Debugger::timer('RUN');
require_once APP . DS . 'App.php';
