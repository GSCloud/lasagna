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

defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('SS') || define('SS', '_');

define('ROOT', __DIR__);
define('APP', ROOT . DS . 'app');
define('CACHE', ROOT . DS . 'temp');
define('CDN', '');
define('DATA', ROOT . DS . 'data');
define('WWW', ROOT . DS . 'www');

define('CONFIG', APP . DS . 'config.neon');
define('CONFIG_PRIVATE', APP . DS . 'config_private.neon');
define('CONFIG_DOCKER', APP . DS . 'config_docker.neon');

define('CSP', APP . DS . 'csp.neon');
define('TEMPLATES', APP . DS . 'templates');
define('PARTIALS', APP . DS . 'partials');
define('DOWNLOAD', WWW . DS . 'download');
define('UPLOAD', WWW . DS . 'upload');
define('LOGS', ROOT . DS . 'logs');
define('TEMP', ROOT . DS . 'temp');

define('CLI', (bool) (PHP_SAPI == 'cli'));
define('DEBUG', true);
define('REDIS_CACHE', true);
define('LOCALHOST', (bool) (($_SERVER['SERVER_NAME'] ?? '') == 'localhost') || CLI);
define('HOST', 'localhost');
define('APPNAME', 'app');
define('CACHEPREFIX', 'cache_');
define('DOMAIN', $_SERVER['SERVER_NAME'] ?? 'localhost');
define('PROJECT', 'LASAGNA');
define('SERVER', $_SERVER['SERVER_NAME'] ?? 'localhost');
