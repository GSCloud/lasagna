<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

use Cake\Cache\Cache;
use Google\Cloud\Logging\LoggingClient;
use Monolog\Logger;
use Nette\Neon\Neon;

// SANITY CHECK
foreach ([
    "APP",
    "CACHE",
    "DATA",
    "DS",
    "LOGS",
    "ROOT",
    "TEMP",
] as $x) {
    defined($x) || die("FATAL ERROR: sanity check for constant '$x' failed!");
}

// POPULATE DATA ARRAY
$base58 = new \Tuupola\Base58;
$cfg = $data = $cfg ?? [];

$requestUri = $_SERVER["REQUEST_URI"] ?? "";
if (!$requestUri) {
    $requestUri = '';
}

$data["cfg"] = $cfg; // cfg backup array
$data["ARGC"] = $argc ?? 0; // arguments count
$data["ARGV"] = $argv ?? []; // arguments array

$data["GET"] = array_map("htmlspecialchars", $_GET);
$data["POST"] = array_map("htmlspecialchars", $_POST);

$data["DATA_VERSION"] = null;
$data["PHP_VERSION"] = PHP_VERSION_ID;
$data["ENGINE"] = "Tesseract LASAGNA v2.1.0";
$data["VERSION"] = $version = trim(
    @file_get_contents(ROOT . DS . "VERSION") ?: '', "\r\n"
);
$data["VERSION_SHORT"] = $base58->encode(
    base_convert(substr(hash("sha256", $version), 0, 4), 16, 10)
);
$data["VERSION_DATE"]
    = date("j. n. Y G:i", @filemtime(ROOT . DS . "VERSION") ?: time());
$data["VERSION_TIMESTAMP"]
    = @filemtime(ROOT . DS . "VERSION") ?: time();
$data["REVISIONS"] = (int) trim(
    @file_get_contents(ROOT . DS . "REVISIONS") ?: "0", "\r\n"
);
$data["cdn"] = $data["CDN"] = DS . "cdn-assets" . DS . $version;
$data["host"] = $data["HOST"] = $host = $_SERVER["HTTP_HOST"] ?? "";
$data["base"] = $data["BASE"] = $host ? (
    ($_SERVER["HTTPS"] ?? "off" == "on") ? "https://{$host}/" : "http://{$host}/"
    ) : "";
$data["request_uri"] = $requestUri;

$rqp = strtok($requestUri, "?&");
if (!$rqp) {
    $rqp = '';
}
$rqp = trim($rqp, "/");
$data["request_path"] = $rqp;
$data["request_path_hash"] = ($rqp === '') ? '' : hash("sha256", $rqp);

$data["nonce"] = $data["NONCE"] = $nonce = substr(
    hash(
        "sha256", random_bytes(16) . (string) time()
    ), 0, 8
);
$data["utm"] = $data["UTM"]
    = "?utm_source={$host}&utm_medium=website&nonce={$nonce}";

$data["LOCALHOST"] = (bool) LOCALHOST;
$data["ALPHA"] = (in_array($host, (array) ($cfg["alpha_hosts"] ?? [])));
$data["BETA"] = (in_array($host, (array) ($cfg["beta_hosts"] ?? [])));

$x = $cfg["app"] ?? $cfg["canonical_url"] ?? $cfg["goauth_origin"] ?? "";
defined("CACHEPREFIX") || define(
    "CACHEPREFIX",
    "cache_" . hash("sha256", $x) . "_"
);
defined("DOMAIN") || define(
    "DOMAIN",
    strtolower(
        preg_replace(
            "/[^A-Za-z0-9.-]/", "", $_SERVER["SERVER_NAME"] ?? "localhost"
        )
    )
);
defined("SERVER") || define(
    "SERVER",
    strtolower(
        preg_replace(
            "/[^A-Za-z0-9]/", "", $_SERVER["SERVER_NAME"] ?? "localhost"
        )
    )
);
defined("PROJECT") || define("PROJECT", (string) ($cfg["project"] ?? "LASAGNA"));
defined("APPNAME") || define("APPNAME", (string) ($cfg["app"] ?? "app"));
defined("MONOLOG") || define(
    "MONOLOG", LOGS . DS . "MONOLOG_" . SERVER . "_" . PROJECT . ".log"
);
defined("GCP_PROJECTID") || define("GCP_PROJECTID", $cfg["gcp_project_id"] ?? null);
defined("GCP_KEYS") || define("GCP_KEYS", $cfg["gcp_keys"] ?? null);

// set GCP_KEYS ENV variable
if (GCP_KEYS && file_exists(APP . DS . GCP_KEYS)) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . APP . DS . GCP_KEYS);
}

/**
 * Google Stackdriver logger
 *
 * @param string $message  message
 * @param mixed  $severity severity
 *
 * @return mixed
 */
function logger($message, $severity = Logger::INFO)
{
    if (empty($message) || is_null(GCP_PROJECTID) || is_null(GCP_KEYS)) {
        return false;
    }
    if (ob_get_level()) {
        ob_end_clean();
    }
    try {
        $logging = new LoggingClient(
            [
                "projectId" => GCP_PROJECTID,
                "keyFilePath" => APP . DS . GCP_KEYS,
            ]
        );
        $stack = $logging->logger(PROJECT);
        $stack->write($stack->entry($message), ["severity" => $severity,]);
    } finally {
    }
    return true;
}

// CACHE PROFILES
$cache_profiles = array_replace(
    [
        "default" => "+2 minutes",
        "second" => "+1 seconds",
        "tenseconds" => "+10 seconds",
        "thirtyseconds" => "+30 seconds",
        "minute" => "+60 seconds",
        "fiveminutes" => "+5 minutes",
        "tenminutes" => "+10 minutes",
        "thirtyminutes" => "+30 minutes",
        "hour" => "+60 minutes",
        "day" => "+24 hours",
        "csv" => "+360 minutes", // CSV cold storage
        "limiter" => "+5 seconds", // access limiter
        "page" => "+10 seconds", // public web page, user not logged
    ], (array) ($cfg["cache_profiles"] ?? [])
);

// init cache profiles
foreach ($cache_profiles as $k => $v) {
    if ($cfg["redis"]["port"] ?? null) {
        Cache::setConfig(
            "{$k}_file", [
                // fallback File engine
                "className" => "Cake\Cache\Engine\FileEngine",
                "duration" => $v,
                "lock" => true,
                "path" => CACHE,
                "prefix" => SERVER
                    . "_"
                    . PROJECT
                    . "_"
                    . APPNAME
                    . "_"
                    . CACHEPREFIX,
            ]
        );
        Cache::setConfig(
            $k, [
                "className" => "Cake\Cache\Engine\RedisEngine",
                "database" => $cfg["redis"]["database"] ?? 0,
                "duration" => $v,
                "fallback" => "{$k}_file", // use fallback
                "host" => $cfg["redis"]["host"] ?? "127.0.0.1",
                "password" => $cfg["redis"]["password"] ?? "",
                "path" => CACHE,
                "persistent" => true,
                "port" => $cfg["redis"]["port"] ?? 6377,
                "prefix" => SERVER
                    . "_"
                    . PROJECT
                    . "_"
                    . APPNAME
                    . "_"
                    . CACHEPREFIX,
                "timeout" => $cfg["redis"]["timeout"] ?? 1,
                "unix_socket" => $cfg["redis"]["unix_socket"] ?? "",
            ]
        );
    } else {
        Cache::setConfig(
            "{$k}_file", [
                "className" => "Cake\Cache\Engine\FileEngine", // File engine
                "duration" => $v,
                "fallback" => false,
                "lock" => true,
                "path" => CACHE,
                "prefix" => SERVER
                    . "_"
                    . PROJECT
                    . "_"
                    . APPNAME
                    . "_"
                    . CACHEPREFIX,
            ]
        );
        Cache::setConfig(
            $k, [
                "className" => "Cake\Cache\Engine\FileEngine", // File engine
                "duration" => $v,
                "fallback" => false,
                "lock" => true,
                "path" => CACHE,
                "prefix" => SERVER
                    . "_"
                    . PROJECT
                    . "_"
                    . APPNAME
                    . "_"
                    . CACHEPREFIX,
            ]
        );
    }
}

// POPULATE DATA ARRAY
$data["cache_profiles"] = $cache_profiles;

// ROUTING CONFIGURATION
$router = [];
$routes = $cfg["routes"] ?? [ // can be overriden in config.neon
    "router_defaults.neon",
    "router_admin.neon",
    "router_core.neon",
    "router_extras.neon",
    "router_api.neon",
    "router.neon",
];

// LOAD ROUTING TABLES
foreach ($routes as $r) {
    $r = APP . DS . $r;
    if (($content = @file_get_contents($r)) === false) {
        logger("ERROR in routing table: $r", Logger::EMERGENCY);
        if (ob_get_level()) {
            ob_end_clean();
        }
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Server Error</h1><h2>Routing table</h2><h3>$r</h3>";
        exit;
    }
    $next = @Neon::decode($content);
    if (is_array($next)) {
        $router = array_replace_recursive($router, $next);
    }
}

// SET ROUTING DEFAULTS AND PROPERTIES
$presenter = [];
$defaults = $router["defaults"] ?? [];
foreach ($router as $k => $v) {
    if ($k == "defaults") {
        continue;
    }
    // ALIASED ROUTE
    if (isset($v["alias"]) && $v["alias"]) {
        foreach ($defaults as $i => $j) {
            // data from the aliased origin
            $router[$k][$i] = $router[$v["alias"]][$i] ?? $defaults[$i];
            if ($i == "path") {
                // path property from the source
                $router[$k][$i] = $v[$i];
            }
        }
        $presenter[$k] = $router[$k];
        continue;
    }
    // CLONED ROUTE
    if (isset($v["clone"]) && $v["clone"]) {
        foreach ($defaults as $i => $j) {
            // data from the cloned origin
            $router[$k][$i] = $router[$v["clone"]][$i] ?? $defaults[$i];
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
    if (!isset($v["path"])) {
        continue;
    }
    if ($v["path"] == "/") {
        if ($data["request_path_hash"] == "") {
            // set homepage hash to default language
            $data["request_path_hash"] = hash("sha256", $v["language"]);
        }
    }
    $alto->map($v["method"], $v["path"], $k, "route_{$k}");
    if (substr($v["path"], -1) != "/") {
        // skip the root route, map also slash endings
        $alto->map($v["method"], $v["path"] . "/", $k, "route_{$k}_x");
    }
}

// POPULATE DATA ARRAY
$data["presenter"] = $presenter;
$data["router"] = $router;

// CLI HANDLER
if (CLI) {
    define("TESSERACT_END", microtime(true));

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
    $view = $match["target"];
} else {
    $view = $router["defaults"]["view"] ?? "home";
}

// POPULATE DATA ARRAY
$data["match"] = $match;
$data["view"] = $view;

// "sethl" property = set HOME LANGUAGE
if ($router[$view]["sethl"] ?? false) {
    $r = trim(strtolower($_GET["hl"] ?? $_COOKIE["hl"] ?? ""));
    switch ($r) {
    case "cs":
    case "de":
    case "en":
    case "sk":
        break;

    default:
        $r = null;
    }
    if ($r) {
        // no need to sanitize this cookie
        setcookie("hl", $r, time() + 86400 * 31, "/");
        header("X-Set-HL: " . $r);
        $presenter[$view]["language"] = $r;
        $data["presenter"] = $presenter;
    }
}

// PROCESS REDIRECTS
if ($router[$view]["redirect"] ?? false) {
    $r = $router[$view]["redirect"];
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: " . $r, true, 303);
    exit;
}

// CSP HEADERS
switch ($presenter[$view]["template"]) {
default:
    if (file_exists(CSP) && is_readable(CSP)) {
        $csp = @Neon::decode(@file_get_contents(CSP) ?: '');
        if (is_array($csp)) {
            header(implode(" ", (array) $csp["csp"]));
        }
    }
}

// GEO BLOCKING
// use XX to block unknown locations
$blocked = (array) ($data["geoblock"] ?? ["RU", "BY", "KZ", "MD", "XX"]);
$data["country"] = $country = (string) ($_SERVER["HTTP_CF_IPCOUNTRY"] ?? "XX");
if (!LOCALHOST && in_array($country, $blocked)) {
    header("HTTP/1.1 403 Not Found");
    exit;
}

// SINGLETON CONTROLLER
$data["controller"] = $p = ucfirst(
    strtolower($presenter[$view]["presenter"])
) . "Presenter";
$controller = "\\GSC\\$p";
\Tracy\Debugger::timer("PROCESS");

// set Model and start processing
$app = $controller::getInstance()->setData($data)->process();
$data = $app->getData(); // get Model

// PREPARE ANALYTICS DATA
$data["running_time"] = $time1 = round(
    (float) \Tracy\Debugger::timer("RUN") * 1000, 2
);
$data["processing_time"] = $time2 = round(
    (float) \Tracy\Debugger::timer("PROCESS") * 1000, 2
);

// SET X-HEADERS
header("X-Engine: Tesseract 2.0");
header("X-Country: $country");
header("X-Running: $time1 ms");
header("X-Processing: $time2 ms");

// EXPORT OUTPUT
echo $data["output"] ?? "";

// PROCESS DEBUGGING
if (DEBUG) {
    define("TESSERACT_END", microtime(true));
    // remove private information
    unset($data["cf"]);
    unset($data["goauth_secret"]);
    
    // phpcs:ignore
    /** @phpstan-ignore-next-line */
    bdump($app->getIdentity(), "identity");

    // phpcs:ignore
    /** @phpstan-ignore-next-line */
    bdump($data, 'model');
}

exit(0);
