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

// sanity checks
$x = "FATAL ERROR: broken chain of trust";
defined("APP") || die($x);
defined("CACHE") || die($x);
defined("ROOT") || die($x);
defined("VERSION") || die($x);

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

// Stackdriver
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
        "prefix" => "cakephpcache_" . SERVER . "_" . PROJECT . "_" . VERSION . "_",
    ]);
}

// multi-site profiles
$multisite_names = [];
$multisite_profiles = array_replace([
    "default" => DOMAIN,
], $cfg["multisite_profiles"] ?? []
);
foreach ($multisite_profiles as $k => $v) {
    $multisite_names[] = $k;
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
    // map secondary path as ending with /
    if ($v["path"] != "/") {
        $alto->map($v["method"], $v["path"] . "/", $k, "{$k}_");
    }
}

// CI tester, CLI ONLY!!!
if (php_sapi_name() === "cli") {
    require_once "CiTester.php";
    exit;
}

// routing
$match = $alto->match();
$view = $match ? $match["target"] : ($router["defaults"]["view"] ?? "home");

// sethl
if ($router[$view]["sethl"] ?? false) {
    $r = $_COOKIE["hl"] ?? $router[$view]["redirect"] ?? false;
    switch ($r) {
        case "cs":
        case "/cs":
            $r = "cs";
            break;

        case "en":
        case "/en":
            $r = "en";
            break;
    }
    if ($r) {
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
$data["multisite_names"] = $multisite_names;
$data["multisite_profiles"] = $multisite_profiles;

$data["match"] = $match;
$data["presenter"] = $presenter;
$data["router"] = $router;
$data["view"] = $view;

// App singleton
$data["controller"] = $p = ucfirst(strtolower($presenter[$view]["presenter"])) . "Presenter";
$presenter_file = APP . "/${p}.php";
if (!file_exists($presenter_file)) {
    // missing presenter
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
]));
 */

// App
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

// end credits
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
    // sanitize private data
    unset($data["cf"]);
    unset($data["goauth_secret"]);
    bdump($data, "DATA " . date("Y-m-d"));
}
