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

use Nette\Neon\Neon;
use Tracy\Debugger;

// START
list($usec, $sec) = explode(" ", microtime());
/** @const Global timer start. */
define("LASAGNA_START", ((float) $usec + (float) $sec));
ob_start();
error_reporting(E_ALL);
@ini_set("auto_detect_line_endings", true);
@ini_set("default_socket_timeout", 15);
@ini_set("display_errors", true);

// constants (in SPECIFIC ORDER !!!)

/** @const Bootstrap root folder. */
defined("ROOT") || define("ROOT", __DIR__);
/** @const Cache and logs folder, defaults to "cache". */
defined("CACHE") || define("CACHE", ROOT . "/cache");
/** @const Application data folder, defaults to "data". */
defined("DATA") || define("DATA", ROOT . "/data");
/** @const Website assets folder, defaults to "www". */
defined("WWW") || define("WWW", ROOT . "/www");
/** @const Configuration file, full path. */
defined("CONFIG") || define("CONFIG", ROOT . "/config.neon");
/** @const Private configuration file, full path. */
defined("CONFIG_PRIVATE") || define("CONFIG_PRIVATE", ROOT . "/config_private.neon");
/** @const Website templates folder, defaults to "www/templates". */
defined("TEMPLATES") || define("TEMPLATES", WWW . "/templates");
/** @const Website template partials folder, defaults to "www/partials". */
defined("PARTIALS") || define("PARTIALS", WWW . "/partials");
/** @const Website downloads folder, defaults to "www/download". */
defined("DOWNLOAD") || define("DOWNLOAD", WWW . "/download");
/** @const Website uploads folder, defaults to "www/upload". */
defined("UPLOAD") || define("UPLOAD", WWW . "/upload");
/** @const Temporary files folder, defaults to "/tmp". */
defined("TEMP") || define("TEMP", "/tmp");
/** @const True if running from command line interface. */
define("CLI", (bool) (PHP_SAPI === "cli"));
/** @const True if running server locally. */
define("LOCALHOST", (bool) (($_SERVER["SERVER_NAME"] ?? "") == "localhost") || CLI);

require_once ROOT . "/vendor/autoload.php";

function check_file($f)
{
    if (!file_exists($f) || !is_readable($f)) {
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Core Corrupted</h2><h3>File: $f</h3>\n\n";
        exit;
    }
}

function check_folder($f, $writable = false)
{
    if (!file_exists($f) || !is_readable($f)) {
        ob_end_clean();
        header("HTTP/1.1 500 Internal Server Error");
        echo "<h1>Internal Server Error</h1><h2>Core Corrupted</h2><h3>Folder: $f</h3>\n\n";
        exit;
    }
    if ((bool) $writable === true) {
        if (!is_writable($f)) {
            ob_end_clean();
            header("HTTP/1.1 500 Internal Server Error");
            echo "<h1>Internal Server Error</h1><h2>Access Denied</h2><h3>Folder: $f</h3>\n\n";
            exit;
        }
    }
}

// sanity check
check_file(CONFIG);
check_file(ROOT . "/VERSION");
check_folder(CACHE, true);
check_folder(DATA, true);
check_folder(PARTIALS);
check_folder(TEMP, true);
check_folder(TEMPLATES);
check_folder(WWW);

// configuration
$cfg = @Neon::decode(@file_get_contents(CONFIG));
if (file_exists(CONFIG_PRIVATE)) {
    $cfg = array_replace_recursive($cfg, @Neon::decode(@file_get_contents(CONFIG_PRIVATE)));
}
date_default_timezone_set((string) ($cfg["date_default_timezone"] ?? "Europe/Prague"));

/** @const Version string. */
defined("VERSION") || define("VERSION", (string) ($cfg["version"] ?? "v1"));
/** @const Application folder. */
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
        echo "<h1>Internal Server Error</h1><h2>Corrupted Configuration</h2><h3>$arr: $key</h3>\n\n";
        exit;
    }
}

// sanity checks
check_var($cfg, "app", "app");
check_var($cfg, "canonical_url");
check_var($cfg, "minify", false);

// debugger
if (($_SERVER["SERVER_NAME"] ?? "") == "localhost") {
    /** @const True if debugging. */
    defined("DEBUG") || define("DEBUG", true);
}
if (CLI === true) {
    /** @const Disable for CLI. */
    defined("DEBUG") || define("DEBUG", false);
}
if (isset($_SERVER["HTTP_USER_AGENT"]) && strpos($_SERVER["HTTP_USER_AGENT"], "curl") !== false) {
    /** @const Disable for curl. */
    defined("DEBUG") || define("DEBUG", false);
}
defined("DEBUG") || define("DEBUG", (bool) ($cfg["dbg"] ?? false));
if (DEBUG === true) {
    // https://api.nette.org/3.0/Tracy/Debugger.html
    Debugger::$logSeverity = 15; // https://www.php.net/manual/en/errorfunc.constants.php
    Debugger::$maxDepth = (int) ($cfg["DEBUG_MAX_DEPTH"] ?? 5);
    Debugger::$maxLength = (int) ($cfg["DEBUG_MAX_LENGTH"] ?? 500);
    Debugger::$scream = (bool) ($cfg["DEBUG_SCREAM"] ?? true);
    Debugger::$showBar = (bool) ($cfg["DEBUG_SHOW_BAR"] ?? true);
    Debugger::$showFireLogger = (bool) ($cfg["DEBUG_SHOW_FIRELOGGER"] ?? false);
    Debugger::$showLocation = (bool) ($cfg["DEBUG_SHOW_LOCATION"] ?? false);
    Debugger::$strictMode = (bool) ($cfg["DEBUG_STRICT_MODE"] ?? true);
    $a = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"];
    // cookie: tracy-debug
    Debugger::enable(
        ($cfg["DEBUG_COOKIE"] ?? null) ? (string) $cfg["DEBUG_COOKIE"] . "@" . $a : Debugger::DETECT,
        CACHE,
        (string) ($cfg["DEBUG_EMAIL"] ?? null)
    );
    Debugger::timer("RUNNING");
}

// data population
$base58 = new \Tuupola\Base58;
$data = (array) $cfg;
$data["cfg"] = $cfg;
$data["VERSION"] = $version = trim(@file_get_contents(ROOT . "/VERSION") ?? "", "\r\n");
$data["VERSION_DATE"] = date("j. n. Y", @filemtime(ROOT . "/VERSION") ?? time());
$data["REVISIONS"] = (int) trim(@file_get_contents(ROOT . "/REVISIONS") ?? "0", "\r\n");
$data["DATA_VERSION"] = null;
$data["cdn"] = $data["CDN"] = "/cdn-assets/$version";
$data["host"] = $data["HOST"] = $host = $_SERVER["HTTP_HOST"] ?? "";
$data["request_uri"] = $_SERVER["REQUEST_URI"] ?? "";
$data["request_path"] = $rqp = trim(trim(strtok($_SERVER["REQUEST_URI"] ?? "", "?&"), "/"));
$data["request_path_hash"] = ($rqp == "") ? "" : hash("sha256", $rqp);
$data["base"] = $data["BASE"] = ($_SERVER["HTTPS"] ?? "off" == "on") ? "https://${host}/" : "http://${host}/";
$data["LOCALHOST"] = (bool) (($_SERVER["SERVER_NAME"] ?? "") == "localhost") || CLI;
$data["VERSION_SHORT"] = $base58->encode(base_convert(substr(hash("sha256", $version), 0, 8), 16, 10));
$data["nonce"] = $data["NONCE"] = $nonce = substr(hash("sha256", random_bytes(10) . (string) time()), 0, 8);
$data["utm"] = $data["UTM"] = "?utm_source=${host}&utm_medium=website&nonce=${nonce}";
$data["ALPHA"] = (in_array($host, (array) ($cfg["alpha_hosts"] ?? [])));
$data["BETA"] = (in_array($host, (array) ($cfg["beta_hosts"] ?? [])));

require_once APP . "/App.php";
