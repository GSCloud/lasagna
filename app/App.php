<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */

namespace GSC;

use Cake\Cache\Cache;
use Nette\Neon\Neon;

// SANITY CHECK
foreach ([
    'APP',
    'CONFIG',
    'DATA',
    'DS',
    'ROOT',
    'SS',
] as $x) {
    defined($x) || die("FATAL ERROR: sanity check - const '{$x}' failed!");
}

// BLOCK BAD ROBOTS
if (isset($cfg['block_robots']) && $cfg['block_robots']) {
    $bots = APP . DS . 'badrobots.txt';
    if (file_exists($bots) && is_readable($bots)) {
        $blockedUA = file($bots, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($blockedUA) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = strtolower(trim($_SERVER['HTTP_USER_AGENT']));
            foreach ($blockedUA as $badBot) {
                if (strpos($ua, strtolower(trim($badBot))) !== false) {
                    header("HTTP/1.1 403 Forbidden");
                    echo "You are not authorized to access this page.";
                    exit;
                }
            }
        }
    }
}

// CLEAR COOKIES on ?logout
if (isset($_GET['logout'])) {
    $canonicalUrl = $cfg['canonical_url'] ?? null;
    $googleOAuthOrigin = $cfg['goauth_origin'] ?? null;
    $localGoogleOAuthOrigin = $cfg['local_goauth_origin'] ?? null;
    $redirectUrl = LOCALHOST ? ($localGoogleOAuthOrigin ?? $canonicalUrl) : ($canonicalUrl ?? $googleOAuthOrigin); // phpcs:ignore
    $redirectUrl = trim($redirectUrl, '/');
    $nonce = substr(md5((string) \microtime(true)), 0, 4);
    header('Clear-Site-Data: "cookies"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header("Location: {$redirectUrl}/?{$nonce}", true, 303);
    exit;
}

// CLEAR EVERYTHING on ?clearall
if (isset($_GET['clearall'])) {
    $canonicalUrl = $cfg['canonical_url'] ?? null;
    $googleOAuthOrigin = $cfg['goauth_origin'] ?? null;
    $localGoogleOAuthOrigin = $cfg['local_goauth_origin'] ?? null;
    $redirectUrl = LOCALHOST ? ($localGoogleOAuthOrigin ?? $canonicalUrl) : ($canonicalUrl ?? $googleOAuthOrigin); // phpcs:ignore
    $redirectUrl = trim($redirectUrl, '/');
    $nonce = substr(md5((string) \microtime(true)), 0, 4);
    header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header("Location: {$redirectUrl}/?{$nonce}", true, 303);
    exit;
}

// TIMER START
\Tracy\Debugger::timer('DATA');

// MODEL
$cfg = $cfg ?? [];

// LANGUAGE - PREBAKED CSV
if (isset($cfg['locales'])) {
    array_unshift($cfg['locales'], 'base.csv');
}
$data = $cfg;
$data['cfg'] = $cfg; // $cfg shallow backup

// CLOUDFLARE GEO BLOCKING: XX = unknown, T1 = TOR anon.
$data['cf_ray'] = $_SERVER['Cf-Ray'] ?? null;
$data['cf_worker'] = $_SERVER['CF-Worker'] ?? null;
$data['cf_cache_status'] = $_SERVER['Cf-Cache-Status'] ?? null;
$country = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX'));
$data['cf_country'] = $data['country'] = $country;
$data["country_id_{$country}"] = true; // country_id_UK etc.
$blocked = (array) ($data['geoblock'] ?? [ // default blocked countries
    'AF',
    'BY',
    'IR',
    'KP',
    'RU',
    'SY',
    'T1', // TOR network
]);

if (!LOCALHOST && in_array($country, $blocked)) {
    error_log("Country [{$country}] forbidden and all requests blocked.");
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// + MODEL
define('ENGINE', 'Tesseract v2.4.6');
$data['ENGINE'] = ENGINE;

// version to load: https://cdnjs.com/libraries/codemirror 
$data['codemirror'] = '6.65.7';
// version to load: https://cdn.gscloud.cz/summernote/
$data['summernote'] = 'v0.8.18';

// load Base58 encoder
$base58 = new \Tuupola\Base58;

$data['ARGC'] = $argc ?? 0;
$data['ARGV'] = $argv ?? [];
$data['GET'] = array_map('htmlspecialchars', $_GET);
$data['POST'] = array_map('htmlspecialchars', $_POST);
$data['COOKIE'] = array_map('htmlspecialchars', $_COOKIE);

$data['REFERER'] = $_SERVER['HTTP_REFERER'] ?? null;
$data['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$data['IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; // phpcs:ignore
$data['PHP_VERSION'] = PHP_VERSION;
$data['DATA_VERSION'] = null;
$data['VERSION'] = $version = trim(file_get_contents(ROOT . DS . 'VERSION') ?: '');
$data['VERSION_SHORT'] = $base58->encode(base_convert(substr($version, 0, 6), 16, 10)); // phpcs:ignore
$data['VERSION_DATE'] = date('j. n. Y G:i', @filemtime(ROOT . DS . 'VERSION') ?: time()); // phpcs:ignore
$data['VERSION_TIMESTAMP'] = @filemtime(ROOT . DS . 'VERSION') ?: time();
$data['REVISIONS'] = (int) trim(file_get_contents(ROOT . DS . 'REVISIONS') ?: '0');

// RANDOM HASH set by the administrator after CACHE PURGE
$hash = DATA . DS . '_random_cdn_hash';
if (file_exists($hash) && is_readable($hash)) {
    if ($hash = file_get_contents($hash)) {
        $version = trim($hash);
    }
}
$data['cdn'] = $data['CDN'] = DS . 'cdn-assets' . DS . $version;
$data['cdn_trimmed'] = 'cdn-assets' . DS . $version;
defined('CDN') || define('CDN', $data['CDN']);

// fix Apple
$isSafari = false;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ((stripos($ua, 'Chrome') === false) && (stripos($ua, 'Safari') !== false)) {
    $isSafari = true;
}
$data['isSafari'] = $isSafari;

$data['host'] = $data['HOST'] = $host = $_SERVER['HTTP_HOST'] ?? '';
define('HOST', $host);
$data['base'] = $data['BASE'] = $host ? (($_SERVER['HTTPS'] ?? 'off' == 'on') ? "https://{$host}/" : "http://{$host}/") : ''; // phpcs:ignore
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (!$requestUri) {
    $requestUri = '';
}
$data['request_uri'] = $requestUri;
$rqp = strtok($requestUri, '?&');
if (!$rqp) {
    $rqp = '';
}
$rqp = trim($rqp, '/');
$data['request_path'] = $rqp;
$data['request_path_hash'] = ($rqp === '') ? '' : hash('sha256', $rqp);
$data['nonce'] = $data['NONCE'] = $nonce = substr(md5(random_bytes(16) . (string) time()), 0, 8); // phpcs:ignore
$data['LOCALHOST'] = (bool) LOCALHOST;

// canonical URI
$x = $cfg['app'] ?? $cfg['canonical_url'] ?? $cfg['goauth_origin'] ?? '';
defined('CACHEPREFIX') || define('CACHEPREFIX', 'cache_' . md5($x) . SS);

defined('APPNAME') || define('APPNAME', (string) ($cfg['app'] ?? 'app'));
defined('PROJECT') || define('PROJECT', (string) ($cfg['project'] ?? 'LASAGNA'));
defined('DOMAIN')  || define('DOMAIN', strtolower(preg_replace("/[^A-Za-z0-9.-]/", '', $_SERVER['SERVER_NAME'] ?? 'localhost'))); // phpcs:ignore
defined('SERVER')  || define('SERVER', strtolower(preg_replace("/[^A-Za-z0-9]/", '', $_SERVER['SERVER_NAME'] ?? 'localhost'))); // phpcs:ignore

// OFFLINE TEMPLATE resolution
$offline = TEMPLATES . DS . 'offline.mustache';
$offline_local = TEMPLATES . DS . strtolower("offline_{$country}.mustache");
if (file_exists($offline_local) && is_readable($offline_local)) {
    $offline = $offline_local;
}
if (file_exists($offline) && is_readable($offline)) {
    $offline = file_get_contents($offline);
    if (is_string($offline)) {
        $offline = preg_replace("/[\n\r\t]/", '', $offline);
        if (is_string($offline)) {
            $offline = preg_replace('/> +/', '>', $offline);
        }
        if (is_string($offline)) {
            $offline = preg_replace("/\s+/", ' ', $offline);
        }
        $data['offline_template'] = $offline;
    }
}

// running on Google OAuth origin server?
$data['is_goauth_origin'] = false;
if (DOMAIN === str_replace('https://', '', $cfg['goauth_origin'] ?? '')) {
    $data['is_goauth_origin'] = true;
}

// BAN/LIMITER times in seconds
if (!isset($data['ban_secs'])) {
    $data['ban_secs'] = 3600;
}
if (!isset($data['limiter_secs'])) {
    $data['limiter_secs'] = 5;
}

// CACHE PROFILES
$cache_profiles = array_replace(
    [
        'default' => '+3 minutes',
        'second' => '+1 seconds',
        'fiveseconds' => '+5 seconds',
        'tenseconds' => '+10 seconds',
        'thirtyseconds' => '+30 seconds',
        'minute' => '+60 seconds',
        'threeminutes' => '+3 minutes',
        'fiveminutes' => '+5 minutes',
        'tenminutes' => '+10 minutes',
        'fifteenminutes' => '+15 minutes',
        'thirtyminutes' => '+30 minutes',
        'hour' => '+60 minutes',
        'twohours' => '+2 hours',
        'threehours' => '+3 hours',
        'sixhours' => '+6 hours',
        'twelfhours' => '+12 hours',
        'day' => '+24 hours',
        'ban' => '+60 minutes', // ban time
        'limiter' => '+5 seconds', // rate limiting interval
        'csv' => '+72 hours', // CSV cold storage
    ], (array) ($cfg['cache_profiles'] ?? [])
);

// INIT CACHE PROFILES
foreach ($cache_profiles as $k => $v) {
    Cache::setConfig(
        "{$k}_file", [
            'className' => 'Cake\Cache\Engine\FileEngine',
            'duration' => $v,
            'lock' => true,
            'path' => CACHE,
            'prefix' => PROJECT . SS . APPNAME . SS . CACHEPREFIX,
        ]
    );
    if ($cfg['redis']['port'] ?? null) {
        Cache::setConfig(
            $k, [
                'className' => 'Cake\Cache\Engine\RedisEngine',
                'database' => $cfg['redis']['database'] ?? 0,
                'duration' => $v,
                'fallback' => "{$k}_file",
                'host' => $cfg['redis']['host'] ?? '127.0.0.1',
                'password' => $cfg['redis']['password'] ?? '',
                'path' => CACHE,
                'persistent' => true,
                'port' => (int) $cfg['redis']['port'],
                'prefix' => PROJECT . SS . APPNAME . SS . CACHEPREFIX,
                'timeout' => (int) ($cfg['redis']['timeout'] ?? 1),
                'unix_socket' => $cfg['redis']['unix_socket'] ?? '',
            ]
        );
    } else {
        Cache::setConfig(
            $k, [
                'className' => 'Cake\Cache\Engine\FileEngine',
                'duration' => $v,
                'fallback' => false,
                'lock' => true,
                'path' => CACHE,
                'prefix' => PROJECT . SS . APPNAME . SS . CACHEPREFIX,
            ]
        );
    }
}

// REDIS TEST
if (Cache::enabled()) {
    if ($cfg['redis']['port'] ?? null) {
        $redis_test = 'redis_test';
        Cache::setConfig(
            $redis_test,
            [
                'className' => 'Cake\Cache\Engine\RedisEngine',
                'database' => $cfg['redis']['database'] ?? 0,
                'duration' => '+5 seconds',
                'host' => $cfg['redis']['host'] ?? '127.0.0.1',
                'password' => $cfg['redis']['password'] ?? '',
                'port' => (int) $cfg['redis']['port'],
                'prefix' => PROJECT . SS . APPNAME . SS . CACHEPREFIX,
                'timeout' => (int) ($cfg['redis']['timeout'] ?? 1),
                'unix_socket' => $cfg['redis']['unix_socket'] ?? '',
            ]
        );
        Cache::write($redis_test, 42, $redis_test);
        define('REDIS_CACHE', Cache::read($redis_test, $redis_test) === 42);
    }
}
defined('REDIS_CACHE') || define('REDIS_CACHE', false);

// + MODEL
$data['cache_profiles'] = $cache_profiles;

// ADMIN ENDPOINT
$admin = $data['admin'] ?? 'admin';
$admin = substr($admin, 0, 32);
$admin = trim($admin, '/');
if ($admin === '') {
    $admin = 'admin';
}
if (is_dir(WWW . DS . $admin)) {
    $err = "Admin end-point [{$admin}] already exists as a web folder!";
    error_log($err);
    throw new \Exception($err);
}
$data['admin'] = $admin;
$data['cfg']['admin'] = $admin;

// ROUTING CONFIGURATION
$router = [];
$routes = $cfg['routers'] ?? [];
chdir(APP);
array_unshift($routes, 'router_defaults.neon');
if ($routers = glob('router_*.neon')) {
    foreach ($routers as $r) {
        if (is_file($r) && is_readable($r) && ($r != 'router_defaults.neon')) {
            array_push($routes, $r);
        }
    }
}
// main router
array_push($routes, 'router.neon');

// + MODEL
$data['router_files'] = $routes;

// load and parse ROUTING TABLES
foreach ($routes as $routeFileName) {
    $route = APP . DS . $routeFileName;
    if (!$content = file_get_contents($route)) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo "<h1>Server Error</h1><h2>Routing Table</h2><h3>{$routeFileName}</h3>";
        error_log("Error loading routes from file: " . $route);
        exit;
    }
    try {
        $next = Neon::decode($content);
        if (is_array($next)) {
            $router = array_replace_recursive($router, $next);
        }
    } catch (\Nette\Neon\Exception $e) {
        error_log("Error parsing router file: " . $e->getMessage());
        die("Error parsing router file: " . $e->getMessage());
    }
}

// ROUTING DEFAULTS AND PROPERTIES
$presenter = [];
$defaults = $router['defaults'] ?? [];
foreach ($router as $k => $v) {
    if ($k === 'defaults') {
        continue;
    }
    // ALIASED ROUTE
    if (isset($v['alias']) && $v['alias']) {
        foreach ($defaults as $i => $j) {
            // data from the aliased origin
            $router[$k][$i] = $router[$v['alias']][$i] ?? $defaults[$i];
            if ($i === 'path') {
                // path property from the source
                $router[$k][$i] = $v[$i];
            }
        }
        $presenter[$k] = $router[$k];
        continue;
    }
    // CLONED ROUTE
    if (isset($v['clone']) && $v['clone']) {
        foreach ($defaults as $i => $j) {
            // data from the cloned origin
            $router[$k][$i] = $router[$v['clone']][$i] ?? $defaults[$i];
            if (isset($v[$i])) {
                // existing properties from the source
                $router[$k][$i] = $v[$i];
            }
        }
        $presenter[$k] = $router[$k];
        continue;
    }
    // NORMAL ROUTE
    foreach ($defaults as $i => $j) {
        $router[$k][$i] = $v[$i] ?? $defaults[$i];
    }
    $presenter[$k] = $router[$k];
}

// ROUTER MAPPINGS
$alto = new \AltoRouter();
foreach ($presenter as $k => $v) {
    if (!isset($v['path'])) {
        continue;
    }
    $v['path'] = str_replace('/admin/', "/{$admin}/", $v['path']);
    if ($v['path'] === '/') {
        if ($data['request_path_hash'] === '') {
            // set homepage hash to default language
            $data['request_path_hash'] = hash('sha256', $v['language']);
        }
    }
    $alto->map($v['method'], $v['path'], $k, "route_{$k}");
    if (substr($v['path'], -1) !== '/') {
        // skip the root route, map also slash endings
        $alto->map($v['method'], $v['path'] . '/', $k, "route_{$k}_x");
    }
}
$data['presenter'] = $presenter;
$data['router'] = $router;

// CLI
if (CLI) {
    if (function_exists('pcntl_signal')) {
        declare(ticks=1); // required for signal handling to work
        pcntl_signal(
            SIGINT, function () {
                echo "\n\033[31mScript terminated by user (Ctrl+C).\033[0m\n";
                exit(1);
            }
        );
    }
    if (ob_get_level()) {
        @ob_end_clean();
    }
    if (isset($argv[1])) {
        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        CliPresenter::getInstance()->setData($data)->selectModule(
            $argv[1], $argc, $argv
        );
        exit;
    }
    // phpcs:ignore
    /** @phpstan-ignore-next-line */
    CliPresenter::getInstance()->setData($data)->process()->help();
    exit;
}

// PROCESS ROUTING
$match = $alto->match();
if (is_array($match)) {
    $view = $match['target'];
} else {
    $view = $router['defaults']['view'] ?? 'home';
}
$data['match'] = $match;
$data['view'] = $view;

// FORCED REDIRECT
if ($router[$view]['redirect'] ?? false) {
    $r = $router[$view]['redirect'];
    if (ob_get_level()) {
        @ob_end_clean();
    }
    header('Location: ' . $r, true, 303);
    exit;
}

// COUNTRY REDIRECTS
if ($router[$view]['country'] ?? false) {
    $nonce = '';
    if (isset($_GET['nonce'])) {
        $rand = substr(md5(random_bytes(4) . (string) time()), 0, 4);
        $nonce = "?{$rand}";
    }
    if (!LOCALHOST && array_key_exists($country, $router[$view]['country'])) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        if (strpos($router[$view]['country'][$country], '?') !== false) {
            $nonce = '';
        }
        header('Location: ' . $router[$view]['country'][$country] . $nonce, true, 303); // phpcs:ignore
        exit;
    }
    if (LOCALHOST && array_key_exists('localhost', $router[$view]['country'])) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        if (strpos($router[$view]['country']['localhost'], '?') !== false) {
            $nonce = '';
        }
        header('Location: ' . $router[$view]['country']['localhost'] . $nonce, true, 303); // phpcs:ignore
        exit;
    }
    if (!LOCALHOST && array_key_exists('default', $router[$view]['country'])) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        if (strpos($router[$view]['country']['default'], '?') !== false) {
            $nonce = '';
        }
        header('Location: ' . $router[$view]['country']['default'] . $nonce, true, 303); // phpcs:ignore
        exit;
    }
}

// POLICIES
$csp = null;
$data['csp_nonce'] = '';
switch ($presenter[$view]['template']) {
default:
    if (CSP && file_exists(CSP) && is_readable(CSP)) {
        try {
            $csp = Neon::decode(@file_get_contents(CSP) ?: '');
        }
        catch (\Throwable $e) {
            $csp = null;
            error_log("Error parsing NE-ON file: " . $e->getMessage());
        }
        if (is_array($csp)) {
            $csp_nonce = $data['csp_nonce'] = md5(random_bytes(8));
            header(
                str_replace(
                    'nonce-random',
                    'nonce-' . $csp_nonce,
                    implode(' ', (array) $csp['csp'])
                )
            );

            // PERMISSIONS
            if (isset($data['csp_permissions']) && is_string($data['csp_permissions'])) { // phpcs:ignore
                header($data['csp_permissions']);
            } else {
                header('Permissions-Policy: camera=(), microphone=(), geolocation=(), midi=(), usb=(), serial=(), hid=(), gamepad=(), payment=(), publickey-credentials-get=(), clipboard-write=(), display-capture=()'); // phpcs:ignore
            }
                
        }
    }
}

// PROFILER
$data['time_data'] = round((float) \Tracy\Debugger::timer('DATA') * 1000, 1);

// SINGLETON TEMPLATE
$data['controller'] = $p = ucfirst(strtolower($presenter[$view]['presenter'])) . 'Presenter'; // phpcs:ignore
$controller = "\\GSC\\{$p}";

// TIMER START
\Tracy\Debugger::timer('PROCESS');

// RUN
$app = $controller::getInstance()->setData($data)->process();
$data = $app->getData();

// PROFILER
$time1 = $data['time_data'];
$time2 = $data['time_process'] = round((float) \Tracy\Debugger::timer('PROCESS') * 1000, 1); // phpcs:ignore
$time3 = $data['time_run'] = round((float) \Tracy\Debugger::timer('RUN') * 1000, 1); // phpcs:ignore
$limit = $app->getRateLimit();
if (!$limit || !is_int($limit)) {
    $limit = 1;
}

// X-HEADERS
header("X-Country: $country");
header("X-Time-Data: $time1 ms");
header("X-Time-Process: $time2 ms");
header("X-Time-Run: $time3 ms");
header("X-Rate-Limit: $limit");

// OUTPUT
$output = $data['output'] ?? '';

// TIMING
$fn = [
    "if(d.getElementById('time1'))d.getElementById('time1').textContent='{$time1}';",
    "if(d.getElementById('time2'))d.getElementById('time2').textContent='{$time2}';",
    "if(d.getElementById('time3'))d.getElementById('time3').textContent='{$time3}';",
    "if(d.getElementById('limit'))d.getElementById('limit').textContent='{$limit}';",
];

// TIMING injection
foreach (headers_list() as $h) {
    if (strpos($h, 'Content-Type: text/html;') === 0) {
        $output = str_replace(
            '</body>',
            '<script nonce="'
                . $data['csp_nonce']
                .'">(function(d){'
                . join("\n", $fn)
                . "})(document);\n</script>\n</body>",
            $output
        );
        break;
    }
}

echo $output;

// DEBUGGING
if (DEBUG) {
    // protect private information
    $data['cf'] = '[protected]';
    $data['goauth_secret'] = '[protected]';
    bdump($app->getIdentity(), 'IDENTITY');
    bdump($data, 'MODEL');
}

exit(0);
