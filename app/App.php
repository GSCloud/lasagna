<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

use Cake\Cache\Cache;
use Google\Cloud\Logging\LoggingClient;
use Monolog\Logger;
use Nette\Neon\Neon;

// SANITY CHECK - CONSTANTS
foreach (["APP", "CACHE", "DATA", "DS", "LOGS", "ROOT", "TEMP", "ENABLE_CSV_CACHE"] as $x) {
    defined($x) || die("FATAL ERROR: sanity check for constant '$x' failed!");
}

// USER-DEFINED ERROR HANDLER
function exception_error_handler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) { // this error code is not included in error_reporting
        return;
    }
    throw new \Exception("ERROR: $message FILE: $file LINE: $line");
}
set_error_handler("\\GSC\\exception_error_handler");

// POPULATE DATA ARRAY
$base58 = new \Tuupola\Base58;
$cfg = $data = $cfg ?? [];
$data["cfg"] = $cfg; // cfg backup
$data["GET"] = array_map("htmlspecialchars", $_GET);
$data["POST"] = array_map("htmlspecialchars", $_POST);
$data["VERSION"] = $version = trim(@file_get_contents(ROOT . DS . "VERSION") ?? "", "\r\n");
$data["VERSION_DATE"] = date("j. n. Y", @filemtime(ROOT . DS . "VERSION") ?? time());
$data["VERSION_TIMESTAMP"] = @filemtime(ROOT . DS . "VERSION") ?? time();
$data["REVISIONS"] = (int) trim(@file_get_contents(ROOT . DS . "REVISIONS") ?? "0", "\r\n");
$data["PHP_VERSION"] = PHP_VERSION_ID;
$data["DATA_VERSION"] = null;
$data["cdn"] = $data["CDN"] = DS . "cdn-assets" . DS . $version;
$data["host"] = $data["HOST"] = $host = $_SERVER["HTTP_HOST"] ?? "";
$data["base"] = $data["BASE"] = $host ? (($_SERVER["HTTPS"] ?? "off" == "on") ? "https://${host}/" : "http://${host}/") : "";
$data["request_uri"] = $_SERVER["REQUEST_URI"] ?? "";
$data["request_path"] = $rqp = trim(trim(strtok($_SERVER["REQUEST_URI"] ?? "", "?&"), "/"));
$data["request_path_hash"] = ($rqp == "") ? "" : hash("sha256", $rqp);
$data["LOCALHOST"] = (bool) LOCALHOST;
$data["VERSION_SHORT"] = $base58->encode(base_convert(substr(hash("sha256", $version), 0, 4), 16, 10));
$data["nonce"] = $data["NONCE"] = $nonce = substr(hash("sha256", random_bytes(16) . (string) time()), 0, 16);
$data["utm"] = $data["UTM"] = "?utm_source=${host}&utm_medium=website&nonce=${nonce}";
$data["ALPHA"] = (in_array($host, (array) ($cfg["alpha_hosts"] ?? [])));
$data["BETA"] = (in_array($host, (array) ($cfg["beta_hosts"] ?? [])));

/** @const cache name prefix */
$x = $cfg["app"] ?? $cfg["canonical_url"] ?? $cfg["goauth_origin"] ?? "";
defined("CACHEPREFIX") || define("CACHEPREFIX",
    "cache_" . hash("sha256", $x) . "_");

/** @const domain name, extracted from $_SERVER */
defined("DOMAIN") || define("DOMAIN", strtolower(preg_replace("/[^A-Za-z0-9.-]/", "", $_SERVER["SERVER_NAME"] ?? "localhost")));

/** @const server name, extracted from $_SERVER */
defined("SERVER") || define("SERVER", strtolower(preg_replace("/[^A-Za-z0-9]/", "", $_SERVER["SERVER_NAME"] ?? "localhost")));

/** @const project name, default "LASAGNA" */
defined("PROJECT") || define("PROJECT", (string) ($cfg["project"] ?? "LASAGNA"));

/** @const Application name, default "app" */
defined("APPNAME") || define("APPNAME", (string) ($cfg["app"] ?? "app"));

/** @const Monolog log filename, full path */
defined("MONOLOG") || define("MONOLOG", LOGS . DS . "MONOLOG_" . SERVER . "_" . PROJECT . ".log");

/** @const Google Cloud Platform project ID */
defined("GCP_PROJECTID") || define("GCP_PROJECTID", $cfg["gcp_project_id"] ?? null);

/** @const Google Cloud Platform JSON auth keys */
defined("GCP_KEYS") || define("GCP_KEYS", $cfg["gcp_keys"] ?? null);

// set GCP_KEYS ENV variable
if (GCP_KEYS && file_exists(APP . DS . GCP_KEYS)) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . APP . DS . GCP_KEYS);
}

/**
 * Google Stackdriver logger
 *
 * @param string $message
 * @param mixed $severity (optional)
 * @return void
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
        $logging = new LoggingClient([
            "projectId" => GCP_PROJECTID,
            "keyFilePath" => APP . DS . GCP_KEYS,
        ]);
        $stack = $logging->logger(PROJECT);
        $stack->write(DOMAIN . " " . $stack->entry($message), [
            "severity" => $severity,
        ]);
    } finally {}
    return true;
}

// CACHING PROFILES
$cache_profiles = array_replace([
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
], (array) ($cfg["cache_profiles"] ?? []));

// init caching profiles
foreach ($cache_profiles as $k => $v) {
    if ($cfg["redis"]["port"] ?? null) {
        // use REDIS
        Cache::setConfig("${k}_file", [
            "className" => "Cake\Cache\Engine\FileEngine", // fallback File engine
            "duration" => $v,
            "lock" => true,
            "path" => CACHE,
            "prefix" => SERVER . "_" . PROJECT . "_" . APPNAME . "_" . CACHEPREFIX,
        ]);
        Cache::setConfig($k, [
            "className" => "Cake\Cache\Engine\RedisEngine",
            "database" => $cfg["redis"]["database"] ?? 0,
            "duration" => $v,
            "fallback" => "${k}_file", // use fallback
            "host" => $cfg["redis"]["host"] ?? "127.0.0.1",
            "password" => $cfg["redis"]["password"] ?? "",
            "path" => CACHE,
            "persistent" => false,
            "port" => $cfg["redis"]["port"] ?? 6379,
            "prefix" => SERVER . "_" . PROJECT . "_" . APPNAME . "_" . CACHEPREFIX,
            "timeout" => $cfg["redis"]["timeout"] ?? 1,
            "unix_socket" => $cfg["redis"]["unix_socket"] ?? "",
        ]);
    } else {
        // no REDIS !!!
        Cache::setConfig("${k}_file", [
            "className" => "Cake\Cache\Engine\FileEngine", // File engine
            "duration" => $v,
            "fallback" => false,
            "lock" => true,
            "path" => CACHE,
            "prefix" => SERVER . "_" . PROJECT . "_" . APPNAME . "_" . CACHEPREFIX,
        ]);
        Cache::setConfig($k, [
            "className" => "Cake\Cache\Engine\FileEngine", // File engine
            "duration" => $v,
            "fallback" => false,
            "lock" => true,
            "path" => CACHE,
            "prefix" => SERVER . "_" . PROJECT . "_" . APPNAME . "_" . CACHEPREFIX,
        ]);
    }
}

// MULTI-SITE PROFILES
$multisite_names = [];
$multisite_profiles = array_replace([
    "default" => [strtolower(trim(str_replace("https://", "", (string) ($cfg["canonical_url"] ?? "")), "/") ?? DOMAIN)],
], (array) ($cfg["multisite_profiles"] ?? []));
foreach ($multisite_profiles as $k => $v) {
    $multisite_names[] = strtolower($k);
}
$profile_index = (string) trim(strtolower($_GET["profile"] ?? "default"));
if (!in_array($profile_index, $multisite_names)) {
    $profile_index = "default";
}
$auth_domain = strtolower(str_replace("https://", "", (string) ($cfg["goauth_origin"] ?? "")));
if (!in_array($auth_domain, $multisite_profiles["default"])) {
    $multisite_profiles["default"][] = $auth_domain;
}

// POPULATE DATA ARRAY
$data["cache_profiles"] = $cache_profiles;
$data["multisite_profiles"] = $multisite_profiles;
$data["multisite_names"] = $multisite_names;
$data["multisite_profiles_json"] = json_encode($multisite_profiles);

// ROUTING CONFIGURATION
$router = [];
$routes = $cfg["routes"] ?? [ // can be overriden in config.neon
    // default routing configuration
    "router_defaults.neon",
    "router_core.neon",
    "router_extras.neon",
    "router_admin.neon",
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
        echo "<h1>Internal Server Error</h1><h2>Error in routing table</h2><h3>$r</h3>";
        exit;
    }
    $router = array_replace_recursive($router, @Neon::decode($content));
}

// SET ROUTING DEFAULTS AND PROPERTIES
$presenter = [];
$defaults = $router["defaults"] ?? [];
foreach ($router ?? [] as $k => $v) {
    if ($k == "defaults") {
        continue;
    }
    // ALIASED ROUTE
    if (isset($v["alias"]) && $v["alias"]) {
        foreach ($defaults as $i => $j) {
            $router[$k][$i] = $router[$v["alias"]][$i] ?? $defaults[$i]; // data from the aliased origin
            if ($i == "path") {
                $router[$k][$i] = $v[$i]; // path property from the source
            }
        }
        $presenter[$k] = $router[$k];
        continue;
    }
    // CLONED ROUTE
    if (isset($v["clone"]) && $v["clone"]) {
        foreach ($defaults as $i => $j) {
            $router[$k][$i] = $router[$v["clone"]][$i] ?? $defaults[$i]; // data from the cloned origin
            if (isset($v[$i])) {
                $router[$k][$i] = $v[$i]; // existing properties from the source
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
        if ($data["request_path_hash"] == "") { // set homepage hash to default language
            $data["request_path_hash"] = hash("sha256", $v["language"]);
        }
    }
    $alto->map($v["method"], $v["path"], $k, "route_${k}");
    if (substr($v["path"], -1) != "/") { // skip the root route
        $alto->map($v["method"], $v["path"] . "/", $k, "route_${k}_x"); // map also slash endings
    }
}

// POPULATE DATA ARRAY
$data["presenter"] = $presenter;
$data["router"] = $router;

// CLI HANDLER
if (CLI) {
    /** @const end global timer */
    define("TESSERACT_END", microtime(true));
    if (ob_get_level()) {
        ob_end_clean();
    }
    if (isset($argv[1])) {
        CliPresenter::getInstance()->setData($data)->process()->selectModule($argv[1], $argc, $argv);
        exit;
    }
    CliPresenter::getInstance()->setData($data)->process()->help();
    exit;
}

// PROCESS ROUTING
$match = $alto->match();
$view = $match ? $match["target"] : ($router["defaults"]["view"] ?? "home");

// POPULATE DATA ARRAY
$data["match"] = $match;
$data["view"] = $view;

// "sethl" property = set HOME LANGUAGE
if ($router[$view]["sethl"] ?? false) {
    $r = trim(strtolower($_GET["hl"] ?? $_COOKIE["hl"] ?? null));
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
        setcookie("hl", $r, time() + 86400 * 31, "/"); // no need to sanitize this cookie
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
    case "epub": // skip CSP for EPUB reader
        break;

    default: // set CSP HEADER
        if (file_exists(CSP) && is_readable(CSP)) {
            $csp = @Neon::decode(@file_get_contents(CSP));
            header(implode(" ", (array) $csp["csp"]));
        }
}

# POPULATE GLOBAL CSV CACHE
$arr = [];
if (ENABLE_CSV_CACHE && \is_array($locales = $data["locales"] ?? null)) {
    foreach (array_replace($locales, $data["app_data"] ?? []) as $name => $csvkey) {
        $arr[hash("sha256", $name)] = [
            "name" => $name,
            "sheet" => $csvkey,
            "data" => null,
        ];
    }
}
$data["csvcache"] = $arr;
unset($arr);

// GEO BLOCKING
$blocked = (array) ($data["geoblock"] ?? [""]);
#$blocked = (array) ($data["geoblock"] ?? ["RU", "BY", "KZ", "MD"]); // use XX to block unknown GEO locations
$data["country"] = $country = (string) ($_SERVER["HTTP_CF_IPCOUNTRY"] ?? "XX");
if (!LOCALHOST && in_array($country, $blocked)) {
    header("HTTP/1.1 403 Not Found");
    exit;
}

// CREATE CORE SINGLETON CLASS
$data["controller"] = $p = ucfirst(strtolower($presenter[$view]["presenter"])) . "Presenter";
$controller = "\\GSC\\${p}";
\Tracy\Debugger::timer("PROCESS");
$app = $controller::getInstance()->setData($data)->process(); // set and process model
$data = $app->getData(); // get model back

// PREPARE ANALYTICS DATA
$events = null;
$data = $app->getData();
$data["running_time"] = $time1 = round((float) \Tracy\Debugger::timer("RUN") * 1000, 2);
$data["processing_time"] = $time2 = round((float) \Tracy\Debugger::timer("PROCESS") * 1000, 2);

// SET X-HEADERS
header("X-Country: $country");
header("X-Processing: $time2 ms");
header("X-RunTime: $time1 ms");

// SEND ANALYTICS
if (method_exists($app, "SendAnalytics")) {
    $app->setData($data)->SendAnalytics();
}

// EXPORT OUTPUT
echo $data["output"] ?? "";

// PROCESS DEBUGGING
if (DEBUG) {
    /** @const end global timer */
    define("TESSERACT_END", microtime(true));
    // remove private information
    unset($data["cf"]);
    unset($data["goauth_secret"]);
    // dumps
    bdump($app->getIdentity(), "identity");
    bdump($data, 'model');
}

exit;
