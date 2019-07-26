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
defined("CLI") || die($x);
defined("ROOT") || die($x);

// constants
defined("CACHEPREFIX") || define("CACHEPREFIX", "cakephpcache_");
defined("DOMAIN") || define("DOMAIN", strtolower(preg_replace("/[^A-Za-z0-9.-]/", "", $_SERVER["SERVER_NAME"] ?? "DOMAIN")));
defined("PROJECT") || define("PROJECT", $cfg["project"] ?? "LASAGNA");
defined("SERVER") || define("SERVER", strtoupper(preg_replace("/[^A-Za-z0-9]/", "", $_SERVER["SERVER_NAME"] ?? "SERVER")));
defined("VERSION") || define("VERSION", "v1");
defined("MONOLOG") || define("MONOLOG", CACHE . "/MONOLOG_" . SERVER . "_" . PROJECT . "_" . VERSION . ".log");

// Google Cloud Platform
defined("GCP_PROJECTID") || define("GCP_PROJECTID", $cfg["gcp_project_id"] ?? null);
defined("GCP_KEYS") || define("GCP_KEYS", $cfg["gcp_keys"] ?? null);
if (GCP_KEYS) {
    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . APP . GCP_KEYS);
}

// Stackdriver
function logger($message, $severity = Logger::INFO)
{
    if (!GCP_PROJECTID) {
        return;
    }

    if (!$message) {
        return;
    }

    ob_flush();
    try {
        $logging = new LoggingClient([
            "projectId" => GCP_PROJECTID,
            "keyFilePath" => APP . GCP_KEYS,
        ]);
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
    "default" => [strtolower(trim(str_replace("https://", "", $cfg["canonical_url"]), "/") ?? DOMAIN)],
], $cfg["multisite_profiles"] ?? []);
foreach ($multisite_profiles as $k => $v) {
    $multisite_names[] = strtolower($k);
}
$profile_index = (string) trim(strtolower($_GET["profile"] ?? "default"));
if (!in_array($profile_index, $multisite_names)) {
    $profile_index = "default";
}
$origin_domain = strtolower(str_replace("https://", "", $cfg["goauth_origin"]));
if ($origin_domain != $multisite_profiles["default"][0]) {
    $multisite_profiles["default"][1] = $origin_domain;
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

// router defaults
$presenter = array();
$defaults = $router["defaults"] ?? [];
foreach ($router as $k => $v) {
    if ($k == "defaults") continue;
    foreach ($defaults as $i => $j) {
        $router[$k][$i] = $v[$i] ?? $defaults[$i];
    }
    $presenter[$k] = $router[$k];
}

// map routes
$alto = new \AltoRouter();
foreach ($presenter as $k => $v) {
    if (!isset($v["path"])) continue;
    if ($v["path"] == "/") {
        if ($data["request_path_hash"] == "") {
            $data["request_path_hash"] = hash("sha256", $v["language"]);
        }
    }
    $alto->map($v["method"], $v["path"], $k, "route_${k}");
    if (substr($v["path"], -1) != "/") $alto->map($v["method"], $v["path"] . "/", $k, "route_${k}_slash");
}

// CLI modules
if (CLI) {
    if (isset($argv[1])) {

        switch ($argv[1]) {
            case "localtest":
            case "productiontest":
                require_once "CiTester.php";
                exit;
                break;

            case "app":
                require_once "APresenter.php";
                require_once "CliPresenter.php";
                $app = CliPresenter::getInstance()->process();
                if ($argc != 3) {
                    echo 'Use $app singleton as entry point.'."\n\n";
                    echo 'Example: app \'print_r($app->getData());\''."\n";
                    echo 'Example: app \'$app->showConst();\''."\n";
                    exit;
                }
                echo eval($argv[2]);
                echo "\n";
                exit;
            break;


            default:
                break;
        }
    }

    echo "Tesseract LASAGNA command line interface. \n\n";
    echo "Usage: Bootstrap.php <command> [<parameters>...] \n\n";
    echo "\t app '<code>' - run code \n";
    echo "\t localtest - CI local test \n";
    echo "\t productiontest - CI production test \n";
    exit;
}

// routing
$match = $alto->match();
$view = $match ? $match["target"] : ($router["defaults"]["view"] ?? "home");

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
$app = $p::getInstance()->setData($data)->process();
$data = $app->getData();

// OUTPUT
$output = "";
if (array_key_exists("output", $data)) {
    $output = $data["output"];
}
echo $output;

// END
$events = null;
$data["country"] = $country = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? "";
$data["processing_time"] = $time = round((float) \Tracy\Debugger::timer() * 1000, 2);
header("X-Country: $country");
header("X-Processing: $time msec.");
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
