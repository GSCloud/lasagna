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
use Google\Cloud\Logging\LoggingClient;use Monolog\Logger;
use Nette\Neon\Neon;
use There4\Analytics\AnalyticsEvent;

// sanity check
$x = "FATAL ERROR: broken chain of trust";
defined("APP") || die($x);
defined("CACHE") || die($x);
defined("CLI") || die($x);
defined("ROOT") || die($x);

/** @const Cache prefix. */
defined("CACHEPREFIX") || define("CACHEPREFIX", "cakephpcache_");
/** @const Domain name, extracted from SERVER array. */
defined("DOMAIN") || define("DOMAIN", strtolower(preg_replace("/[^A-Za-z0-9.-]/", "", $_SERVER["SERVER_NAME"] ?? "localhost")));
/** @const Project name, default LASAGNA. */
defined("PROJECT") || define("PROJECT", (string) $cfg["project"] ?? "LASAGNA");
/** @const Server name, extracted from SERVER array. */
defined("SERVER") || define("SERVER", strtolower(preg_replace("/[^A-Za-z0-9]/", "", $_SERVER["SERVER_NAME"] ?? "localhost")));
/** @const Version string. */
defined("VERSION") || define("VERSION", "v1");
/** @const Monolog filename, full path. */
defined("MONOLOG") || define("MONOLOG", CACHE . "/MONOLOG_" . SERVER . "_" . PROJECT . "_" . VERSION . ".log");

/** @const Google Cloud Platform project ID. */
defined("GCP_PROJECTID") || define("GCP_PROJECTID", $cfg["gcp_project_id"] ?? null);
/** @const Google Cloud Platform JSON auth keys. */
defined("GCP_KEYS") || define("GCP_KEYS", $cfg["gcp_keys"] ?? null);
if (GCP_KEYS) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . APP . GCP_KEYS);
}

// remove old unused cookies
array_map(function ($name) {
    setcookie($name, "", 0, "/", DOMAIN, false, true);
    setcookie($name, "", 0, "/", DOMAIN, true, true);
    unset($_COOKIE[$name]);
}, explode(";", "admin;avatar;id;name;email"));

// Stackdriver
function logger($message, $severity = Logger::INFO)
{
    if (empty($message)) {
        return;
    }

    if (is_null(GCP_PROJECTID)) {
        return;
    }

    if (is_null(GCP_KEYS)) {
        return;
    }

    ob_flush();
    try {
        $logging = new LoggingClient([
            "projectId" => GCP_PROJECTID,
            "keyFilePath" => APP . GCP_KEYS,
        ]);
        $stack = $logging->logger(PROJECT);
        $stack->write(DOMAIN . " " . $stack->entry($message), [
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
    "default" => [strtolower(trim(str_replace("https://", "", ($cfg["canonical_url"] ?? "")), "/") ?? DOMAIN)],
], $cfg["multisite_profiles"] ?? []);
foreach ($multisite_profiles as $k => $v) {
    $multisite_names[] = strtolower($k);
}

$profile_index = (string) trim(strtolower($_GET["profile"] ?? "default"));
if (!in_array($profile_index, $multisite_names)) {
    $profile_index = "default";
}

$auth_domain = strtolower(str_replace("https://", "", $cfg["goauth_origin"] ?? ""));
if (!in_array($auth_domain, $multisite_profiles["default"])) {
    $multisite_profiles["default"][] = $auth_domain;
}

// data population
$data["cache_profiles"] = $cache_profiles;
$data["multisite_profiles"] = $multisite_profiles;
$data["multisite_names"] = $multisite_names;
$data["multisite_profiles_json"] = json_encode($multisite_profiles);

// routing tables
$router = array();
$routes = [
    APP . "/router_defaults.neon",
    APP . "/router_admin.neon",
    APP . "/router.neon",
];

foreach ($routes as $r) {
    if (is_callable("check_file")) {
        check_file($r);
    }
    if (($content = @file_get_contents($r)) === false) {
        logger("Error in routing table: $r", Logger::EMERGENCY);
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Error in routing table</h2><h3>Router: $r</h3>";
        exit;
    }
    $router = array_replace_recursive($router, @Neon::decode($content));
}

// router defaults
$presenter = array();
$defaults = $router["defaults"] ?? [];
foreach ($router as $k => $v) {
    if ($k == "defaults") {
        continue;
    }
    foreach ($defaults as $i => $j) {
        $router[$k][$i] = $v[$i] ?? $defaults[$i];
    }
    $presenter[$k] = $router[$k];
}
// router mappings
$alto = new \AltoRouter();
foreach ($presenter as $k => $v) {
    if (!isset($v["path"])) { // skip presenters without path
        continue;
    }

    if ($v["path"] == "/") {
        if ($data["request_path_hash"] == "") { // set path hash for homepage to default language
            $data["request_path_hash"] = hash("sha256", $v["language"]);
        }
    }
    $alto->map($v["method"], $v["path"], $k, "route_${k}");
    if (substr($v["path"], -1) != "/") { // map duplicates ending with slash
        $alto->map($v["method"], $v["path"] . "/", $k, "route_${k}_slash");
    }

}

// CLI modules
if (CLI) {
    if (isset($argv[1])) {

        switch ($argv[1]) {
            case "testlocal":
            case "testprod":
                require_once "CiTester.php";
                break;

            case "doctor":
                require_once "Doctor.php";
                break;

            case "test":
                require_once "UnitTester.php";
                break;

            case "app":
                require_once "APresenter.php";
                require_once "CliPresenter.php";
                $app = CliPresenter::getInstance()->setData($data)->process();
                $app->evaler($app, $argc, $argv);
                exit;
                break;

            default:
                break;
        }
    }

    $climate = new League\CLImate\CLImate;
    $climate->out("\n<bold><green>Tesseract LASAGNA CLI</green></bold> \n");
    $climate->out("Usage: php -f Bootstrap.php <command> [<parameters>...] \n");
    $climate->out("\t <bold>app</bold> '<code>' \t - run code");
    $climate->out("\t <bold>doctor</bold> \t - check system requirements");
    $climate->out("\t <bold>testlocal</bold> \t - CI local test");
    $climate->out("\t <bold>testprod</bold> \t - CI production test \n");
    exit;
}

// routing
$match = $alto->match();
$view = $match ? $match["target"] : ($router["defaults"]["view"] ?? "home");

// data population
$data["match"] = $match;
$data["presenter"] = $presenter;
$data["router"] = $router;
$data["view"] = $view;

// sethl
if ($router[$view]["sethl"] ?? false) {
    $r = trim(strtolower($_GET["hl"] ?? $_COOKIE["hl"] ?? null));
    switch ($r) {
        case "cs":
        case "en":
            break;

        default:
            $r = null;
    }
    if ($r) {
        \setcookie("hl", $r, time() + 86400 * 31, "/");
        $presenter[$view]["language"] = $r;
        $data["presenter"] = $presenter;
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

// singleton
$data["controller"] = $p = ucfirst(strtolower($presenter[$view]["presenter"])) . "Presenter";
$presenter_file = APP . "/${p}.php";
if (!file_exists($presenter_file)) {
    logger("MISSING PRESENTER: $p", Logger::EMERGENCY);
    header("Location: /error/410", true, 303);
    exit;
}

// CSP headers
header(implode(" ", [
    "Content-Security-Policy: ",
    "default-src",
    "'unsafe-inline'",
    "'self'",
    "https://*;",
    "connect-src",
    "'self'",
    "https://*;",
    "font-src",
    "'self'",
    "'unsafe-inline'",
    "*.gstatic.com;",
    "script-src",
    "cdnjs.cloudflare.com",
    "*.facebook.net",
    "*.google-analytics.com",
    "*.googleapis.com",
    "*.googletagmanager.com",
    "*.ytimg.com",
    "cdn.onesignal.com",
    "onesignal.com",
    "platform.twitter.com",
    "'self'",
    "'unsafe-inline'",
    "'unsafe-eval';",
    "img-src",
    "*",
    "'self'",
    "'unsafe-inline'",
    "data:;",
    "form-action",
    "'self';",
]));

// APP
require_once APP . "/APresenter.php";
require_once $presenter_file;

\Tracy\Debugger::timer("PROCESSING");

$app = $p::getInstance()->setData($data)->process();
$data = $app->getData();

// simple counter + data clearing
if ($app->getUserGroup() != "admin") {
    $c = isset($_COOKIE["counter"]) ? (int) $_COOKIE["counter"] : 0;
    if ($c > 500) {
        header('Clear-Site-Data: "cache", "storage"');
        $c = 0;
    }
    $c++;
    setcookie("counter", $c, time() + 86400 * 31, "/", DOMAIN, false, true);
}

// ANALYTICS
$events = null;
$data["country"] = $country = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? "";
$data["running_time"] = $time1 = round((float) \Tracy\Debugger::timer("RUNNING") * 1000, 2);
$data["processing_time"] = $time2 = round((float) \Tracy\Debugger::timer("PROCESSING") * 1000, 2);
header("X-Country: $country");
header("X-Runtime: $time1 msec.");
header("X-Processing: $time2 msec.");
$dot = new \Adbar\Dot((array) $data);
if ($dot->has("google.ua") && (strlen($dot->get("google.ua"))) && (isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on")) {
    $events = new AnalyticsEvent($dot->get("google.ua"), $dot->get("canonical_url") . $dot->get("request_path"));
}
if ($events) {
    ob_flush();
    @$events->trackEvent($cfg["app"] ?? "APP", "country_code", $country);
    @$events->trackEvent($cfg["app"] ?? "APP", "processing_time", $time);
}

// OUTPUT
echo $data["output"] ?? "";

// DEBUG
if (DEBUG) {
    unset($data["cf"]);
    unset($data["goauth_secret"]);
    unset($data["goauth_client_id"]);
    unset($data["google_drive_backup "]);
    bdump($data, '$data');
}
