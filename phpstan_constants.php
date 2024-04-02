<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://mini.gscloud.cz
 */

define('ROOT', __DIR__);
define('APP', ROOT . DS . 'app');
define('CACHE', ROOT . DS . 'temp');
define('DATA', ROOT . DS . 'data');
define('WWW', ROOT . DS . 'www');
define('CONFIG', APP . DS . 'config.neon');
define('CONFIG_PRIVATE', APP . DS . 'config_private.neon');
define('CSP', APP . DS . 'csp.neon');
define('TEMPLATES', APP . DS . 'templates');
define('PARTIALS', APP . DS . 'partials');
define('DOWNLOAD', WWW . DS . 'download');
define('UPLOAD', WWW . DS . 'upload');
define('LOGS', ROOT . DS . 'logs');
define('TEMP', ROOT . DS . 'temp');
define('CLI', (bool) (PHP_SAPI == 'cli'));
define('LOCALHOST', (bool) (($_SERVER['SERVER_NAME'] ?? '') == 'localhost') || CLI);
define('DEBUG', true);
define("CACHEPREFIX", "cache_");
define("APPNAME", "app");
define("DOMAIN", $_SERVER["SERVER_NAME"] ?? "localhost");
define("GCP_KEYS", null);
define("GCP_PROJECTID", null);
define("PROJECT", "LASAGNA");
define("SERVER", $_SERVER["SERVER_NAME"] ?? "localhost");
