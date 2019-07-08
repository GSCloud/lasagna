<?php
/**
 * GSC Tesseract LASAGNA
 *
 * @category Framework
 * @package  LASAGNA
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://microsite.gscloud.cz
 */

use Nette\Neon\Neon;
use There4\Analytics\AnalyticsEvent;
use Tracy\Debugger;

// timer start
list($usec, $sec) = explode(" ", microtime());
define("LASAGNA_START", ((float) $usec + (float) $sec));

ob_start();
error_reporting(E_ALL);

// tweak ini
ini_set("auto_detect_line_endings", "1");
ini_set("default_socket_timeout", 20);
ini_set("display_errors", "1");

// constants in specific order!
defined("ROOT") || define("ROOT", __DIR__);
defined("TEMP") || define("TEMP", "/tmp");
defined("CACHE") || define("CACHE", ROOT . "/cache");
defined("DATA") || define("DATA", ROOT . "/data");
defined("WWW") || define("WWW", ROOT . "/www");
defined("TEMPLATES") || define("TEMPLATES", WWW . "/templates");
defined("PARTIALS") || define("PARTIALS", WWW . "/partials");
defined("CONFIG") || define("CONFIG", ROOT . "/config.neon");
defined("CONFIG_PRIVATE") || define("CONFIG_PRIVATE", ROOT . "/config_private.neon");

// Composer
require_once ROOT . "/vendor/autoload.php";

function check_folder($f, $writable = false)
{
    if (!file_exists($f) || !is_readable($f)) {
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Corrupted Core</h2><h3>Folder: $f</h3>";
        exit;
    }
    if ((bool) $writable === true) {
        if (!is_writable($f)) {
            ob_end_clean();
            header("HTTP/1.1 500 Internal Server Error");
            echo "<h1>Internal Server Error</h1><h2>Access Denied</h2><h3>Folder: $f</h3>";
            exit;
        }
    }
}

function check_file($f)
{
    if (!file_exists($f) || !is_readable($f)) {
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Corrupted Core</h2><h3>File: $f</h3>";
        exit;
    }
}

// checks
check_folder(CACHE, true);
check_folder(DATA, true);
check_folder(PARTIALS);
check_folder(TEMP, true);
check_folder(TEMPLATES);
check_folder(WWW);
check_file(CONFIG);
check_file(ROOT . "/VERSION");

// NEON configuration
$cfg = @Neon::decode(@file_get_contents(CONFIG));
if (file_exists(CONFIG_PRIVATE)) {
    $cfg = array_replace_recursive($cfg, @Neon::decode(@file_get_contents(CONFIG_PRIVATE)));
}
date_default_timezone_set($cfg["date_default_timezone"] ?? "Europe/Prague");
defined("VERSION") || define("VERSION", $cfg["version"] ?? "v1");
defined("APP") || define("APP", ROOT . "/app/" . VERSION);

function check_var(&$arr, $key, $default = null)
{
    if (!array_key_exists($key, $arr)) {
        if ($default !== null) {
            $arr[$key] = $default;
            return;
        }
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Corrupted Configuration</h2><h3>$arr: $key</h3>";
        exit;
    }
}

// checks
check_var($cfg, "app", "app");
check_var($cfg, "canonical_url");
check_var($cfg, "dbg", ($_SERVER["SERVER_NAME"] ?? "") == "localhost");
check_var($cfg, "minify", false);

// Tracy
defined("DEBUG") || define("DEBUG", (bool) $cfg["dbg"]);
if (DEBUG) {
    Debugger::enable(Debugger::DEVELOPMENT, CACHE);
}
Debugger::$logSeverity = E_NOTICE | E_WARNING;
Debugger::$maxDepth = $cfg["DEBUG_DEPTH"] ?? 5;
Debugger::$maxLength = $cfg["DEBUG_LENGTH"] ?? 2500;
Debugger::$strictMode = true;
Debugger::timer();

// data population
$data = $cfg;
$data["cfg"] = $cfg; // secondary copy :)
$data["VERSION"] = $version = trim(@file_get_contents(ROOT . "/VERSION") ?? "", "\r\n");
$data["VERSION_DATE"] = date(DATE_ATOM, @filemtime(ROOT . "/VERSION") ?? 0);
$data["cdn"] = "/cdn-assets/${version}";
$data["host"] = $host = $_SERVER["HTTP_HOST"] ?? "";
$data["request_uri"] = $_SERVER["REQUEST_URI"] ?? "";
$data["request_path"] = trim(($_SERVER["REQUEST_URI"] ?? ""), "/");
$data["base"] = ($_SERVER["HTTPS"] ?? "off" == "on") ? "https://${host}/" : "http://${host}/";
$data["LOCALHOST"] = (($_SERVER["SERVER_NAME"] ?? "") == "localhost");
$base58 = new \Tuupola\Base58;
$data["VERSION_SHORT"] = $base58->encode(base_convert(substr(hash("sha256", $version), 0, 8), 16, 10));
$data["nonce"] = $nonce = substr(hash("sha256", random_bytes(10) . (string) time()), 0, 8);
$data["utm"] = "?utm_source=${host}&utm_medium=website&nonce=${nonce}";
$data["ALPHA"] = (in_array($host, $cfg["alpha_hosts"] ?? []));
$data["BETA"] = (in_array($host, $cfg["beta_hosts"] ?? []));

// Google Analytics
$events = false;
if (array_key_exists("ua", $data["google"]) && (isset($_SERVER["HTTPS"]))) {
    if ($_SERVER["HTTPS"] == "on") {
        if (strlen($data["google"]["ua"])) {
            $events = new AnalyticsEvent($data["google"]["ua"], $data["canonical_url"]);
        }
    }
}

// locate the APP
if (file_exists(APP) && is_file(APP)) {
    require_once APP;
} elseif (file_exists(APP . "/App.php") && is_file(APP . "/App.php")) {
    require_once APP . "/App.php";
}
