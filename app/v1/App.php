<?php
/**
 * GSC Tesseract LASAGNA
 *
 * @category Framework
 * @package  LASAGNA
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

use Cake\Cache\Cache;
use Google\Cloud\Logging\LoggingClient;
use Monolog\Logger;
use Nette\Neon\Neon;
use There4\Analytics\AnalyticsEvent;

// sanity check
$x = "FATAL ERROR: broken chain of trust\n\n";
defined("APP") || die($x);
defined("CACHE") || die($x);
defined("ROOT") || die($x);

// constants
defined("CACHEPREFIX") || define("CACHEPREFIX", "cakephpcache_");
defined("CLI") || define("CLI", (php_sapi_name() === "cli"));
defined("DOMAIN") || define("DOMAIN", preg_replace("/[^A-Za-z0-9.-]/", "", $_SERVER["SERVER_NAME"] ?? "DOMAIN"));
defined("PROJECT") || define("PROJECT", $cfg["project"] ?? "LASAGNA");
defined("SERVER") || define("SERVER", preg_replace("/[^A-Za-z0-9]/", "", $_SERVER["SERVER_NAME"] ?? "SERVER"));
defined("VERSION") || define("VERSION", "v1");
defined("MONOLOG") || define("MONOLOG", CACHE . "/MONOLOG_" . SERVER . "_" . PROJECT . "_" . VERSION . ".log");

// Google Cloud Platform
defined("GCP_PROJECTID") || define("GCP_PROJECTID", $cfg["gcp_project_id"] ?? null);
defined("GCP_KEYS") || define("GCP_KEYS", $cfg["gcp_keys"] ?? null);
if (!CLI && GCP_KEYS && file_exists(APP . GCP_KEYS)) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . APP . GCP_KEYS);
}

// Stackdriver
function logger($message, $severity = Logger::INFO)
{
    if (CLI) {
        return;
    }

    if (!GCP_PROJECTID) {
        return;
    }

    if (!$message) {
        return;
    }

    ob_flush();
    try {
        $logging = new LoggingClient(["projectId" => GCP_PROJECTID]);
        $stack = $logging->logger(APP ?? "app");
        $stack->write($stack->entry($message), [
            "severity" => $severity,
        ]);
    } finally {}
}

// caching profiles
$cache_profiles = array_replace([
    "default" => "+3 minutes",
    "csv" => "+60 minutes",
    "limiter" => "+2 seconds",
    "page" => "+10 seconds",
],
    $cfg["cache_profiles"] ?? []
);
foreach ($cache_profiles as $k => $v) {
    Cache::setConfig($k, [
        "className" => "File",
        "duration" => $v,
        "path" => CACHE,
        "prefix" => CACHEPREFIX . SERVER . "_" . PROJECT . "_" . VERSION . "_",
    ]);
}

// multi-site profiles
$multisite_names = [];
$multisite_profiles = array_replace([
    "default" => [trim(str_replace("https://", "", $cfg["canonical_url"]), "/") ?? DOMAIN],
], $cfg["multisite_profiles"] ?? []);
foreach ($multisite_profiles as $k => $v) {
    $multisite_names[] = $k;
}

$profile_index = (string) trim($_GET["profile"] ?? "default");
if (!in_array($profile_index, $multisite_names)) {
    $profile_index = "default";
}

// routing tables
$router = array();
$defaults = [
    APP . "/router_defaults.neon",
    APP . "/router_admin.neon",
    APP . "/router.neon",
];
foreach ($defaults as $r) {
    if (is_callable("check_file")) {
        check_file($r);
    }
    $content = @file_get_contents($r);
    if ($content === false) {
        logger("Error in routing table: $r", Logger::EMERGENCY);
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Error in routing table</h2><h3>Router: $r</h3>";
        exit;
    }
    $router = array_replace_recursive($router, @Neon::decode($content));
}

// routing defaults
$presenter = array();
$defaults = $router["defaults"] ?? [];
foreach ($router as $k => $v) {
    if ($k === "defaults") {
        continue;
    }
    foreach ($defaults as $i => $j) {
        $router[$k][$i] = $v[$i] ?? $defaults[$i];
    }
    $presenter[$k] = $router[$k];
}

// map routes
$alto = new \AltoRouter();
foreach ($presenter as $k => $v) {
    if (!isset($v["path"])) {
        continue;
    }
    $alto->map($v["method"], $v["path"], $k, "{$k}");
    // secondary path ending with /
    if ($v["path"] != "/") {
        $alto->map($v["method"], $v["path"] . "/", $k, "{$k}_");
    }
}

// CLI interface
if (php_sapi_name() === "cli") {
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case "localtest":
            case "production":
                require_once "CiTester.php";
                exit;
                break;
        }
    }

    echo "Tesseract LASAGNA command line interface. \n\n";
    echo "Usage: Bootstrap.php <command> [<parameters>...] \n\n";
    echo "\t localtest - CI local test \n";
    echo "\t production - CI production test \n";
    exit;
}

// routing
$match = $alto->match();
$view = $match ? $match["target"] : ($router["defaults"]["view"] ?? "home");

// sethl
if ($router[$view]["sethl"] ?? false) {
    $r = $_GET["hl"] ?? $_COOKIE["hl"] ?? null;
    switch ($r) {
        case "cs":
        case "en":
            break;

        default:
            $r = null;
    }
    if ($r) {
        ob_end_clean();
        \setcookie("hl", $r, time() + 86400 * 30, "/");
        header("Location: /" . $r, true, 303);
        exit;
    }
}

// redirect
if ($router[$view]["redirect"] ?? false) {
    $r = $router[$view]["redirect"];
    ob_end_clean();
    header("Location: " . $r, true, 303);
    exit;
}

// nopwa
if ($router[$view]["nopwa"] ?? false) {
    if (!isset($_GET["nonce"])) {
        header("Location: ?nonce=" . substr(hash("sha256", random_bytes(10) . (string) time()), 0, 8), true, 303);
        exit;
    }
}

// data population
$data["cache_profiles"] = $cache_profiles;
$data["multisite_profiles"] = $multisite_profiles;
$data["multisite_names"] = $multisite_names;
$data["multisite_profiles_json"] = json_encode($multisite_profiles);

$data["match"] = $match;
$data["presenter"] = $presenter;
$data["router"] = $router;
$data["view"] = $view;

// singleton
$data["controller"] = $p = ucfirst(strtolower($presenter[$view]["presenter"])) . "Presenter";
$presenter_file = APP . "/${p}.php";
if (!file_exists($presenter_file)) {
    logger("MISSING PRESENTER: $p", Logger::EMERGENCY);
    header("Location: /error/410", true, 303);
    exit;
}

// CSP headers
/*
header(implode(" ", [
    "Content-Security-Policy:",
    "connect-src",
    "'self'",
    "https://*;",

    "default-src",
    "'unsafe-inline'",
    "'self'",
    "https://*;",

    "font-src",
    "'self'",
    "'unsafe-inline'",
    "https://*.google-analytics.com",
    "https://*.googleapis.com",
    "https://*.googletagmanager.com",
    "https://*.gstatic.com",
    "https://cdn.onesignal.com",
    "https://platform.twitter.com;",

    "script-src",
    "'self'",
    "'unsafe-inline'",
    "'unsafe-eval';",
    "script-src-elem",
    "'self'",
    "https://*.facebook.net",
    "https://*.google-analytics.com",
    "https://*.googleapis.com",
    "https://*.googletagmanager.com",
    "https://cdn.onesignal.com",
    "https://platform.twitter.com",
    "'unsafe-inline'",
    "'unsafe-eval';",

    "img-src",
    "'self'",
    "'unsafe-inline'",
    "https://*.google-analytics.com",
    "https://*.googleapis.com",
    "https://*.googletagmanager.com",
    "https://*.googleusercontent.com",
    "https://*.gstatic.com,",
    "https://cdn.onesignal.com",
    "https://platform.twitter.com",
    "data:;",

    "form-action",
    "'self';",
]));
*/

// APP
require_once APP . "/APresenter.php";
require_once $presenter_file;
$app = $p::getInstance()->setData($data)->process();
$data = $app->getData();

// OUTPUT
$output = "";
if (array_key_exists("output", $data)) {
    $output = $data["output"];
}
echo $output;

// END
$data["country"] = $country = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? "";
$data["processing_time"] = $time = round((float) \Tracy\Debugger::timer() * 1000, 2);
$events = null;
header("X-Processing: ${time} msec.");
$dot = new \Adbar\Dot((array) $data);
if ($dot->has("google.ua") && (strlen($dot->get("google.ua"))) && (isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on")) {
    $events = new AnalyticsEvent($dot->get("google.ua"), $dot->get("canonical_url") . $dot->get("request_path"));
}
if ($events) {
    ob_flush();
    @$events->trackEvent($cfg["app"] ?? "APP", "country_code", $country);
    @$events->trackEvent($cfg["app"] ?? "APP", "processing_time", $time);
}

// DEBUG
if (DEBUG) {
    unset($data["cf"]);
    unset($data["goauth_secret"]);
    unset($data["goauth_client_id"]);
    unset($data["google_drive_backup "]);
    bdump($data, "DATA " . date("Y-m-d"));
}
