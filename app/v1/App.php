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

// sanity check
defined("APP") || exit;
defined("CACHE") || exit;
defined("ROOT") || exit;
defined("VERSION") || exit;

// global constants
defined("DOMAIN") || define("DOMAIN", $_SERVER["SERVER_NAME"] ?? "");
defined("PROJECT") || define("PROJECT", $cfg["project"] ?? "LASAGNA");
defined("SERVER") || define("SERVER", strtr($_SERVER["SERVER_NAME"] ?? "", ".", "_"));
defined("MONOLOG") || define("MONOLOG", CACHE . "/MONOLOG_" . SERVER . "_" . PROJECT . "_" . VERSION . ".log");

// Google Cloud Platform
defined("GCP_PROJECTID") || define("GCP_PROJECTID", $cfg["gcp_project_id"] ?? "gscloudcz-163314");
defined("GCP_KEYS") || define("GCP_KEYS", $cfg["gcp_keys"] ?? "/keys/GSCloud-6dd97e5ac451.json");

if (file_exists(APP . GCP_KEYS)) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . APP . GCP_KEYS);
}

// Stackdriver logging
function logger($message, $severity = Logger::INFO)
{
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
$cache_profiles = array_replace(
    $cfg["cache_profiles"] ?? [],
    [
        "default" => "+3 minutes",
        "csv" => "+60 minutes",
        "limiter" => "+2 seconds",
        "page" => "+10 seconds",
    ]
);
foreach ($cache_profiles as $k => $v) {
    Cache::setConfig($k, [
        "className" => "File",
        "duration" => $v,
        "path" => CACHE,
        "prefix" => "cakephpcache_" . SERVER . "_" . PROJECT . "_" . VERSION . "_",
    ]);
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

// set defaults
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
    // map secondary path as ending with /
    if ($v["path"] != "/") {
        $alto->map($v["method"], $v["path"] . "/", $k, "{$k}_");
    }
}

// CLI tester
if (php_sapi_name() === "cli") {
    require_once "CliTester.php";
    exit;
}

// routing
$match = $alto->match();
$view = $match ? $match["target"] : ($router["defaults"]["view"] ?? "home");

// HTTP redirect
if ($router[$view]["redirect"] ?? false) {
    ob_end_clean();
    header("Location: " . $router[$view]["redirect"], true, 303);
    exit;
}

// session start
if ($router[$view]["session"] ?? false) {
    session_start();
}

// no PWA
if ($router[$view]["nopwa"] ?? false) {
    $data["nopwa"] = true;
    if (!isset($_GET["nonce"])) {
        header("Location: ?nonce=" . substr(hash("sha256", random_bytes(10) . (string) time()), 0, 8), true, 303);
        exit;
    }
}

$data["cache_profiles"] = $cache_profiles;
$data["match"] = $match;
$data["presenter"] = $presenter;
$data["router"] = $router;
$data["view"] = $view;

// instantiate singleton
$data["controller"] = $p = ucfirst(strtolower($presenter[$view]["presenter"])) . "Presenter";
$presenter_file = APP . "/${p}.php";
if (!file_exists($presenter_file)) {
    // missing presenter
    logger("MISSING PRESENTER: $p", Logger::EMERGENCY);
    header("Location: /error/410", true, 303);
    exit;
}

// CSP headers
$security = [
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
    "https://*.googleapis.com",
    "https://*.gstatic.com;",
    "script-src",
    "'self'",
    "'unsafe-inline'",
    "'unsafe-eval';",
    "img-src",
    "'self'",
    "'unsafe-inline'",
    "https://*.googleusercontent.com/*",
    "data:;",
    "form-action",
    "'self';",
];
#header(implode(" ", $security));

// the App
require_once APP . "/APresenter.php";
require_once $presenter_file;
$app = $p::getInstance()->setData($data)->process();
$data = $app->getData();

// output
$output = "";
if (array_key_exists("output", $data)) {
    $output = $data["output"];
}
echo $output;

// finishing game
$data["country"] = $country = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? "";
$data["processing_time"] = $time = round((float) \Tracy\Debugger::timer() * 1000, 2);
header("X-Processing: ${time} msec.");
if ($events) {
    ob_flush();
    @$events->trackEvent($cfg["app"], "country_code", $country);
    @$events->trackEvent($cfg["app"], "processing_time", $time);
}

// last debug
if (DEBUG) {
    $data["cf"] = "redacted";
    $data["goauth_client_id"] = "redacted";
    $data["goauth_secret"] = "redacted";
    bdump($data, "DATA " . date("Y-m-d"));
}
