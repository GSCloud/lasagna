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
    'CACHE',
    'DATA',
    'LOGS',
    'ROOT',
    'TEMP',
] as $x) {
    defined($x) || die("FATAL ERROR: sanity check - constant '{$x}' failed!");
}

// DEFINE MODEL
$cfg = $data = $cfg ?? [];

// CLEAR COOKIES on ?logout
if (isset($_GET['logout'])) {
    $cu = $cfg['canonical_url'];
    $go = $cfg['goauth_origin'];
    $lgo = $cfg['local_goauth_origin'];
    $go = LOCALHOST ? $lgo : $go ?? $cu;
    header('Clear-Site-Data: "cookies"');
    header("Location: {$go}", true, 303);
    exit;
}

// CLEAR EVERYTHING on ?clearall
if (isset($_GET['clearall'])) {
    $cu = $cfg['canonical_url'];
    $go = $cfg['goauth_origin'];
    $lgo = $cfg['local_goauth_origin'];
    $go = LOCALHOST ? $lgo : $go ?? $cu;
    header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
    header("Location: {$go}", true, 303);
    exit;
}

// inject base CSV locale into $cfg
if (\array_key_exists('locales', $cfg)) {
    array_unshift($cfg['locales'], 'base.csv');
    unset($cfg['locales']['default']);
    unset($cfg['locales']['admin']);
}

$data['cfg'] = $cfg;

// CLOUDFLARE GEO BLOCKING; XX = unknown, T1 = TOR anonymous
$blocked = (array) ($data['geoblock'] ?? [
    // default blocked countries
    'BY',
    'IR',
    'RU',
    'T1',
]);
$country = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX'));
$data['country'] = $country;
if (!LOCALHOST && in_array($country, $blocked)) {
    header('HTTP/1.1 403 Not Found', true, 301);
    exit;
}

// + MODEL
define('ENGINE', 'Tesseract v2.4.4');
$data['ENGINE'] = ENGINE;
$data['codemirror'] = '6.65.7'; // CodeMirror version to load for admin interface

\Tracy\Debugger::timer('DATA');
$base58 = new \Tuupola\Base58;

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (!$requestUri) {
    $requestUri = '';
}

$data['ARGC'] = $argc ?? 0;
$data['ARGV'] = $argv ?? [];
$data['GET'] = array_map('htmlspecialchars', $_GET);
$data['POST'] = array_map('htmlspecialchars', $_POST);
$data['COOKIE'] = array_map('htmlspecialchars', $_COOKIE);
$data['REFERER'] = $_SERVER['HTTP_REFERER'] ?? null;
$data['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$data['IP'] = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$data['PHP_VERSION'] = PHP_VERSION;
$data['DATA_VERSION'] = null;
$data['VERSION'] = $version = trim(
    @file_get_contents(ROOT . DS . 'VERSION') ?: ''
);
$data['VERSION_SHORT'] = $base58->encode(
    base_convert(substr($version, 0, 6), 16, 10)
);
$data['VERSION_DATE']
    = date('j. n. Y G:i', @filemtime(ROOT . DS . 'VERSION') ?: time());
$data['VERSION_TIMESTAMP']
    = @filemtime(ROOT . DS . 'VERSION') ?: time();
$data['REVISIONS'] = (int) trim(
    @file_get_contents(ROOT . DS . 'REVISIONS') ?: '0'
);

// random hash set by administrator after cache purge
$hash = DATA . DS . '_random_cdn_hash';
if (file_exists($hash) && is_readable($hash)) {
    $hash = @file_get_contents($hash);
    if ($hash) {
        $version = trim($hash);
    }
}

$data['cdn'] = $data['CDN'] = DS . 'cdn-assets' . DS . $version;
$data['cdn_trimmed'] = 'cdn-assets' . DS . $version;
defined('CDN') || define('CDN', $data['CDN']);

$isSafari = false;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if ((stripos($ua, 'Chrome') === false) && (stripos($ua, 'Safari') !== false)) {
    $isSafari = true;
}
$data['isSafari'] = $isSafari;

$data['host'] = $data['HOST'] = $host = $_SERVER['HTTP_HOST'] ?? '';
$data['base'] = $data['BASE'] = $host ? (
    ($_SERVER['HTTPS'] ?? 'off' == 'on') ? "https://{$host}/" : "http://{$host}/"
) : '';
$data['request_uri'] = $requestUri;

$rqp = strtok($requestUri, '?&');
if (!$rqp) {
    $rqp = '';
}
$rqp = trim($rqp, '/');
$data['request_path'] = $rqp;
$data['request_path_hash'] = ($rqp === '') ? '' : hash('sha256', $rqp);
$data['nonce'] = $data['NONCE'] = $nonce = substr(
    hash(
        'sha256', random_bytes(16) . (string) time()
    ), 0, 8
);

$data['LOCALHOST'] = (bool) LOCALHOST;

$x = $cfg['app'] ?? $cfg['canonical_url'] ?? $cfg['goauth_origin'] ?? '';

defined('CACHEPREFIX') || define(
    'CACHEPREFIX',
    'cache_' . hash('sha256', $x) . '_'
);

defined('DOMAIN') || define(
    'DOMAIN',
    strtolower(
        preg_replace(
            "/[^A-Za-z0-9.-]/", '', $_SERVER['SERVER_NAME'] ?? 'localhost'
        )
    )
);

defined('SERVER') || define(
    'SERVER',
    strtolower(
        preg_replace(
            "/[^A-Za-z0-9]/", '', $_SERVER['SERVER_NAME'] ?? 'localhost'
        )
    )
);

defined('PROJECT') || define('PROJECT', (string) ($cfg['project'] ?? 'LASAGNA'));
defined('APPNAME') || define('APPNAME', (string) ($cfg['app'] ?? 'app'));

// running on Google OAuth origin server?
$data['is_goauth_origin'] = false;
if (DOMAIN === str_replace('https://', '', $cfg['goauth_origin'] ?? '')) {
    $data['is_goauth_origin'] = true;
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
        'csv' => '+72 hours', // CSV cold storage
        'limiter' => '+5 seconds', // access rate limiter
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
            'prefix' => PROJECT
                . '_'
                . APPNAME
                . '_'
                . CACHEPREFIX,
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
                'port' => $cfg['redis']['port'] ?? 6377,
                'prefix' => PROJECT
                    . '_'
                    . APPNAME
                    . '_'
                    . CACHEPREFIX,
                'timeout' => $cfg['redis']['timeout'] ?? 1,
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
                'prefix' => PROJECT
                    . '_'
                    . APPNAME
                    . '_'
                    . CACHEPREFIX,
            ]
        );
    }
}

// REDIS TEST
if ($cfg['redis']['port'] ?? null) {
    $redis_test = 'redis_test';
    Cache::setConfig(
        $redis_test,
        [
            'className' => 'Cake\Cache\Engine\RedisEngine',
            'database' => $cfg['redis']['database'] ?? 0,
            'duration' => '+10 seconds',
            'host' => $cfg['redis']['host'] ?? '127.0.0.1',
            'password' => $cfg['redis']['password'] ?? '',
            'port' => $cfg['redis']['port'] ?? 6377,
            'prefix' => PROJECT
                . '_'
                . APPNAME
                . '_'
                . CACHEPREFIX,
            'timeout' => $cfg['redis']['timeout'] ?? 1,
            'unix_socket' => $cfg['redis']['unix_socket'] ?? '',
        ]
    );
    Cache::write($redis_test, $redis_test, $redis_test);
    define('REDIS_CACHE', Cache::read($redis_test, $redis_test) === $redis_test);
} else {
    define('REDIS_CACHE', false);
}

// + MODEL
$data['cache_profiles'] = $cache_profiles;

// MASKED ADMIN GROUPS
$data['admin_groups_masked'] = $data['admin_groups'] ?? [];
array_walk_recursive(
    $data['admin_groups_masked'], function (&$e) {
        $p = explode('@', $e);
        $l = $p[0] ?: '';
        $d = $p[1] ?: '';
        if (strlen($l) > 3) {
            $l = substr($l, 0, 4) . '*';
        }
        if (strlen($d) > 4) {
            $d = substr($d, 0, 5) . '*';
        }
        $e = "{$l}@{$d}";
    }
);

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
array_push($routes, 'router.neon'); // main router

// + MODEL
$data['router_files'] = $routes;

// ROUTING TABLES
foreach ($routes as $r) {
    $r = APP . DS . $r;
    if (($content = @file_get_contents($r)) === false) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('HTTP/1.1 500 Internal Server Error');
        echo "<h1>Server Error</h1><h2>Routing table:</h2><h3>{$r}</h3>";
        exit;
    }
    $next = @Neon::decode($content);
    if (is_array($next)) {
        $router = array_replace_recursive($router, $next);
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
    if ($v['path'] === '/') {
        if ($data['request_path_hash'] == '') {
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

// COUNTRY REDIRECT
if ($router[$view]['country'] ?? false) {
    $nonce = '';
    if (isset($_GET['nonce'])) {
        $rand = substr(hash('sha256', random_bytes(8) . (string) time()), 0, 16);
        $nonce = "?nonce={$rand}";
    }
    if (!LOCALHOST && array_key_exists($country, $router[$view]['country'])) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        if (strpos($router[$view]['country'][$country], '?') !== false) {
            $nonce = '';
        }
        header(
            'Location: '
            . $router[$view]['country'][$country] . $nonce,
            true, 303
        );
        exit;
    }
    if (LOCALHOST && array_key_exists('localhost', $router[$view]['country'])) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        if (strpos($router[$view]['country']['localhost'], '?') !== false) {
            $nonce = '';
        }
        header(
            'Location: '
            . $router[$view]['country']['localhost'] . $nonce,
            true, 303
        );
        exit;
    }
    if (!LOCALHOST && array_key_exists('default', $router[$view]['country'])) {
        if (ob_get_level()) {
            @ob_end_clean();
        }
        if (strpos($router[$view]['country']['default'], '?') !== false) {
            $nonce = '';
        }
        header(
            'Location: '
            . $router[$view]['country']['default'] . $nonce,
            true, 303
        );
        exit;
    }
}

// CSP HEADERS
$data['csp_nonce'] = '';
switch ($presenter[$view]['template']) {
default:
    if (CSP && file_exists(CSP) && is_readable(CSP)) {
        $csp = @Neon::decode(@file_get_contents(CSP) ?: '');
        if (is_array($csp)) {
            $cspn = $data['csp_nonce'] = sha1(random_bytes(8));
            header(
                str_replace(
                    'nonce-random',
                    'nonce-' . $cspn,
                    implode(' ', (array) $csp['csp'])
                )
            );
        }
    }
}

// PROFILER
$data['time_data'] = round(
    (float) \Tracy\Debugger::timer('DATA') * 1000, 2
);

// SINGLETON
$data['controller'] = $p = ucfirst(
    strtolower($presenter[$view]['presenter'])
) . 'Presenter';
$controller = "\\GSC\\{$p}";
\Tracy\Debugger::timer('PROCESS');
$app = $controller::getInstance()->setData($data)->process();
$model = $app->getData();

// PROFILER
$time1 = $model['time_data'];
$time2 = $model['time_process'] = round(
    (float) \Tracy\Debugger::timer('PROCESS') * 1000, 2
);
$time3 = $model['time_run'] = round(
    (float) \Tracy\Debugger::timer('RUN') * 1000, 2
);

// X-HEADERS
header('X-Engine: ' . ENGINE);
header("X-Country: $country");
header("X-Time-Data: $time1 ms");
header("X-Time-Process: $time2 ms");
header("X-Time-Run: $time3 ms");
$limit = $app->getRateLimit();
if ($limit && is_int($limit)) {
    header("X-Rate-Limit: $limit");
} else {
    $limit = 1;
}

// OUTPUT
$output = $model['output'] ?? '';

// TIMINGS
$fn = [
    "if(d.getElementById('time1'))d.getElementById('time1').textContent='{$time1}';",
    "if(d.getElementById('time2'))d.getElementById('time2').textContent='{$time2}';",
    "if(d.getElementById('time3'))d.getElementById('time3').textContent='{$time3}';",
    "if(d.getElementById('limit'))d.getElementById('limit').textContent='{$limit}';",
];

foreach (headers_list() as $h) {
    if (strpos($h, 'Content-Type: text/html;') === 0) {
        $output = str_replace(
            '</body>',
            '<script nonce="'
                . $data['csp_nonce']
                .'">(function(d){'
                . join("\n", $fn)
                . '})(document);</script></body>',
            $output
        );
        break;
    }
}

echo $output;

// DEBUGGING
if (DEBUG) {
    // protect private information
    $model['cf'] = '[protected]';
    $model['goauth_secret'] = '[protected]';
    bdump($app->getIdentity(), 'identity');
    bdump($model, 'model');
}

exit(0);
