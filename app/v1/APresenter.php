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

namespace GSC;

use Cake\Cache\Cache;
use Exception;
use Google\Cloud\Logging\LoggingClient;
use League\Csv\Reader;
use League\Csv\Statement;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use ParagonIE\Halite\Cookie;
use ParagonIE\Halite\KeyFactory;

interface IPresenter
{
    public function addCritical($e);
    public function addError($e);
    public function addMessage($m);
    public function checkPermission($role);
    public function checkLocales($force);
    public function checkRateLimit($maximum);
    public function clearCookie($name);
    public function cloudflarePurgeCache($cf);
    public function getCfg($key);
    public function getCookie($name);
    public function getCriticals();
    public function getCurrentUser();
    public function getData($key);
    public function getErrors();
    public function getIdentity();
    public function getLocale($locale, $key);
    public function getMatch();
    public function getMessages();
    public function getPresenter();
    public function getRouter();
    public function getUID();
    public function getUIDstring();
    public function getUserGroup($email);
    public function getView();
    public function logout();
    public function postloadAppData($key);
    public function preloadAppData($key, $force);
    public function process();
    public function readAppData($index);
    public function renderHTML($template);
    public function setCookie($name, $data);
    public function setData($data, $key, $value);
    public function setForceCsvCheck();
    public function setHeaderCsv();
    public function setHeaderFile();
    public function setHeaderHtml();
    public function setHeaderJavaScript();
    public function setHeaderJson();
    public function setHeaderPdf();
    public function setHeaderText();
    public function setIdentity($identity);
    public function setLocation($locationm, $code);
    public function writeJsonData($d, $headers);
}

abstract class APresenter implements IPresenter
{

    /** @var string Fatal error message when checking for null. */
    const ERROR_NULL = " > FATAL ERROR: NULL UNEXPECTED";

    /** @var integer Octal file mode for logs. */
    const LOG_FILEMODE = 0664;

    /** @var integer Octal file mode for CSV. */
    const CSV_FILEMODE = 0664;

    /** @var integer CSV minimal size. */
    const CSV_MIN_SIZE = 42;

    /** @var integer Octal file mode for cookie secret. */
    const COOKIE_KEY_FILEMODE = 0600;

    /** @var integer Cookie time to live. */
    const COOKIE_TTL = 86400 * 10;

    /** @var string Google CSV URL prefix. */
    const GS_CSV_PREFIX = "https://docs.google.com/spreadsheets/d/e/";

    /** @var string Google CSV URL postfix. */
    const GS_CSV_POSTFIX = "/pub?output=csv";

    /** @var string Google Sheet URL prefix. */
    const GS_SHEET_PREFIX = "https://docs.google.com/spreadsheets/d/";

    /** @var string Google Sheet URL postfix. */
    const GS_SHEET_POSTFIX = "/edit#gid=0";

    /** @var integer Access limiter maximum hits. */
    const LIMITER_MAXIMUM = 30;

    /** @var string Identity nonce filename. */
    const IDENTITY_NONCE = "identity_nonce.key";

    // GOOGLE DRIVE TEMPLATES

    /** @var string */
    const GOOGLE_SHEET_EDIT =
        "https://docs.google.com/spreadsheets/d/FILEID/edit#gid=0";

    /** @var string */
    const GOOGLE_SHEET_VIEW =
        "https://docs.google.com/spreadsheets/d/FILEID/view#gid=0";

    /** @var string */
    const GOOGLE_DOCUMENT_EXPORT_DOC =
        "https://docs.google.com/document/d/FILEID/export?format=doc";

    /** @var string */
    const GOOGLE_DOCUMENT_EXPORT_PDF =
        "https://docs.google.com/document/d/FILEID/export?format=pdf";

    /** @var string */
    const GOOGLE_SHEET_EXPORT_DOCX =
        "https://docs.google.com/spreadsheets/d/FILEID/export?format=docx";

    /** @var string */
    const GOOGLE_SHEET_EXPORT_PDF =
        "https://docs.google.com/spreadsheets/d/FILEID/export?format=pdf";

    /** @var string */
    const GOOGLE_SHEET_EXPORT_XLSX =
        "https://docs.google.com/spreadsheets/d/FILEID/export?format=xlsx";

    /** @var string */
    const GOOGLE_SHEET_EXPORT_CSV =
        "https://docs.google.com/spreadsheets/d/e/FILEID/pub?output=csv";

    /** @var string */
    const GOOGLE_SHEET_EXPORT_HTML =
        "https://docs.google.com/spreadsheets/d/e/FILEID/pubhtml";

    /** @var string */
    const GOOGLE_SUITE_IMAGE_VIEW =
        "https://drive.google.com/a/DOMAIN/thumbnail?id=IMAGEID";

    /** @var string */
    const GOOGLE_IMAGE_VIEW =
        "https://drive.google.com/thumbnail?id=IMAGEID";

    /** @var string */
    const GOOGLE_FILE_EXPORT_DOWNLOAD =
        "https://drive.google.com/uc?export=download&id=FILEID";

    /** @var string */
    const GOOGLE_FILE_EXPORT_VIEW =
        "https://drive.google.com/uc?export=view&id=FILEID";

    // PRIVATE VARS

    /** @var array $data Model data array. */
    private $data = [];

    /** @var array $messages Array of internal messages. */
    private $messages = [];

    /** @var array $errors Array of internal errors. */
    private $errors = [];

    /** @var array $criticals Array of internal critical errors. */
    private $criticals = [];

    /** @var array $identity Identity associative array. */
    private $identity = [];

    /** @var boolean $force_csv_check Should re-check locales? */
    private $force_csv_check = false;

    /** @var array $csv_postload Array of keys to [$name => $csvkey] pairs */
    private $csv_postload = [];

    /** @var array $instances Array of singleton instances. */
    private static $instances = array();

    /**
     * Abstract processor
     *
     * @abstract
     * @return void
     */
    abstract public function process();

    /**
     * Class constructor
     */
    final private function __construct()
    {
        $class = get_called_class();
        if (array_key_exists($class, self::$instances)) {
            throw new \Exception("INSTANCE OF [" . $class . "] ALREADY EXISTS!");
        }
    }

    /**
     * Magic clone
     *
     * @return void
     */
    final private function __clone()
    {}

    /**
     * Magic sleep
     *
     * @return void
     */
    final private function __sleep()
    {}

    /**
     * Magic wakeup
     *
     * @return void
     */
    final private function __wakeup()
    {}

    /**
     * Magic call
     *
     * @param string $name
     * @param mixed $parameter
     * @return void
     */
    final public function __call($name, $parameter)
    {}

    /**
     * Magic call static
     *
     * @param string $name
     * @param mixed $parameter
     * @return void
     */
    final public static function __callStatic($name, $parameter)
    {}

    /**
     * Serialize the object to JSON encoded string
     *
     * @return string
     */
    final public function __toString()
    {
        return (string) json_encode($this->getData(), JSON_PRETTY_PRINT);
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (ob_get_level()) ob_flush();
        ob_start();

        foreach ($this->csv_postload as $key) {
            $this->preloadAppData($key, true);
        }
        $this->checkLocales((bool) $this->force_csv_check);

        $monolog = new Logger("Tesseract log");
        $streamhandler = new StreamHandler(MONOLOG, Logger::INFO, true, self::LOG_FILEMODE);
        $streamhandler->setFormatter(new LineFormatter);
        $consolehandler = new BrowserConsoleHandler(Logger::INFO);
        $monolog->pushHandler($consolehandler);
        $monolog->pushHandler($streamhandler);
        $monolog->pushProcessor(new GitProcessor);
        $monolog->pushProcessor(new MemoryUsageProcessor);
        $monolog->pushProcessor(new WebProcessor);

        $criticals = $this->getCriticals();
        $errors = $this->getErrors();
        $messages = $this->getMessages();

        list($usec, $sec) = explode(" ", microtime());
        defined("TESSERACT_STOP") || define("TESSERACT_STOP", ((float) $usec + (float) $sec));
        $add = "| processing: " . round(((float) TESSERACT_STOP - (float) TESSERACT_START) * 1000, 2) . " msec."
            . "| request_uri: " . ($_SERVER["REQUEST_URI"] ?? "N/A");

        try {
            if (count($criticals) + count($errors) + count($messages)) {
                $logging = new LoggingClient([
                    "projectId" => GCP_PROJECTID,
                    "keyFilePath" => APP . GCP_KEYS,
                ]);
                $google_logger = $logging->logger(PROJECT);
            }
            if (count($criticals)) {
                $monolog->critical(DOMAIN . " CRIT: " . json_encode($criticals) . $add);
                $google_logger->write($google_logger->entry(DOMAIN . " ERR: " . json_encode($criticals) . $add, [
                    "severity" => Logger::CRITICAL,
                ]));
            }
            if (count($errors)) {
                $monolog->error(DOMAIN . " ERR: " . json_encode($errors) . $add);
                $google_logger->write($google_logger->entry(DOMAIN . " ERR: " . json_encode($errors) . $add, [
                    "severity" => Logger::ERROR,
                ]));
            }
            if (count($messages)) {
                $monolog->info(DOMAIN . " MSG: " . json_encode($messages) . $add);
                $google_logger->write($google_logger->entry(DOMAIN . " MSG: " . json_encode($messages) . $add, [
                    "severity" => Logger::INFO,
                ]));
            }
        } finally {}
    }

    /**
     * Get singleton object
     *
     * @static
     * @final
     * @return object Singleton instance.
     */
    final public static function getInstance()
    {
        $class = get_called_class();
        if (array_key_exists($class, self::$instances) === false) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Render HTML content from given template
     *
     * @param string $template Template name.
     * @return string HTML output.
     */
    public function renderHTML($template = "index")
    {
        if (is_null($template)) {
            $this->addError(__NAMESPACE__ . " : " . __METHOD__ . self::ERROR_NULL);
            return "";
        }
        $type = (file_exists(TEMPLATES . "/${template}.mustache")) ? 1 : 0;
        $renderer = new \Mustache_Engine(array(
            "template_class_prefix" => "__" . SERVER . "_" . PROJECT . "_" . VERSION . "_",
            "cache" => CACHE,
            "cache_file_mode" => 0666,
            "cache_lambda_templates" => true,
            "loader" => $type ? new \Mustache_Loader_FilesystemLoader(TEMPLATES) : new \Mustache_Loader_StringLoader,
            "partials_loader" => new \Mustache_Loader_FilesystemLoader(PARTIALS),
            "helpers" => [
                "unix_timestamp" => function () {
                    return (string) time();
                },

                "sha256_nonce" => function () {
                    return (string) substr(hash("sha256", random_bytes(8) . (string) time()), 0, 8);
                },

                "convert_hyperlinks" => function ($source, \Mustache_LambdaHelper $lambdaHelper) {
                    $text = $lambdaHelper->render($source);
                    $text = preg_replace(
                        "/(https)\:\/\/([a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,20})(\/[a-zA-Z0-9\-_\/]*)?/",
                        '<a rel=noopener target=_blank href="$0">$2$3</a>', $text);
                    return (string) $text;
                },

                "shuffle_lines" => function ($source, \Mustache_LambdaHelper $lambdaHelper) {
                    $text = $lambdaHelper->render($source);
                    $arr = explode("\n", $text);
                    shuffle($arr);
                    $text = join("\n", $arr);
                    return (string) $text;
                },

                "add_google_search_links" => function ($source, \Mustache_LambdaHelper $lambdaHelper) {
                    $text = $lambdaHelper->render($source);
                    $arr = explode("\n", $text);
                    foreach ($arr as $k => $v) {
                        $v = trim($v);
                        $w = str_replace(" ", "&nbsp;", $v);
                        $arr[$k] = "<a rel=noopener target=_blank href=\"http://www.google.com/search?q=" . htmlspecialchars($v) . "\">$w</a> ";
                    }
                    $text = join("\n", $arr);
                    return (string) $text;
                },

            ],
            "charset" => "UTF-8",
            "escape" => function ($value) {
                return $value;
            },
        ));
        if ($type) {
            return $renderer->loadTemplate($template)->render($this->getData());
        } else {
            return $renderer->render($template, $this->getData());
        }
    }

    /**
     * Data getter
     *
     * @param string $key Optional array key, may use dot notation.
     * @return mixed Data if key exists or whole data array.
     */
    public function getData($key = null)
    {
        $dot = new \Adbar\Dot((array) $this->data);

        // global constants
        $dot->set([
            "CONST.APP" => APP,
            "CONST.CACHE" => CACHE,
            "CONST.CACHEPREFIX" => CACHEPREFIX,
            "CONST.CLI" => CLI,
            "CONST.DATA" => DATA,
            "CONST.DOMAIN" => DOMAIN,
            "CONST.DOWNLOAD" => DOWNLOAD,
            "CONST.MONOLOG" => MONOLOG,
            "CONST.PARTIALS" => PARTIALS,
            "CONST.PROJECT" => PROJECT,
            "CONST.ROOT" => ROOT,
            "CONST.SERVER" => SERVER,
            "CONST.TEMP" => TEMP,
            "CONST.TEMPLATES" => TEMPLATES,
            "CONST.UPLOAD" => UPLOAD,
            "CONST.VERSION" => VERSION,
            "CONST.WWW" => WWW,
        ]);

        // class constants
        $dot->set([
            "CONST.COOKIE_KEY_FILEMODE" => self::COOKIE_KEY_FILEMODE,
            "CONST.COOKIE_TTL" => self::COOKIE_TTL,
            "CONST.CSV_FILEMODE" => self::CSV_FILEMODE,
            "CONST.CSV_MIN_SIZE" => self::CSV_MIN_SIZE,
            "CONST.ERROR_NULL" => self::ERROR_NULL,
            "CONST.GS_CSV_POSTFIX" => self::GS_CSV_POSTFIX,
            "CONST.GS_CSV_PREFIX" => self::GS_CSV_PREFIX,
            "CONST.GS_SHEET_POSTFIX" => self::GS_SHEET_POSTFIX,
            "CONST.GS_SHEET_PREFIX" => self::GS_SHEET_PREFIX,
            "CONST.LIMITER_MAXIMUM" => self::LIMITER_MAXIMUM,
            "CONST.LOG_FILEMODE" => self::LOG_FILEMODE,
        ]);

        $this->data = $dot->all();
        if (is_null($key)) {
            return $this->data;
        }
        if (is_string($key)) {
            return $dot->get($key);
        }
        return false;
    }

    /**
     * Data setter
     *
     * @param array $data
     * @param string $key
     * @param mixed $value
     * @return object Singleton instance.
     */
    public function setData($data = null, $key = null, $value = null) // TODO: needs rework!

    {
        if (is_null($data)) {
            $data = $this->data;
        }
        if (is_string($key) && !empty($key)) {
            $dot = new \Adbar\Dot($data);
            $dot->set($key, $value);
            $data = $dot->all();
        }
        $this->data = (array) $data;
        return $this;
    }

    /**
     * Messages getter
     *
     * @return array Array of messages.
     */
    public function getMessages()
    {
        return (array) $this->messages;
    }

    /**
     * Errors getter
     *
     * @return array Array of errors.
     */
    public function getErrors()
    {
        return (array) $this->errors;
    }

    /**
     * Criticals getter
     *
     * @return array Array of critical messages.
     */
    public function getCriticals()
    {
        return (array) $this->criticals;
    }

    /**
     * Add message
     *
     * @param string $e Error string.
     * @return object Singleton instance.
     */
    public function addMessage($e = null)
    {
        if (!is_null($e) || !empty($e)) {
            $this->messages[] = (string) $e;
        }
        return $this;
    }

    /**
     * Add error message
     *
     * @param string $e Error string.
     * @return object Singleton instance.
     */
    public function addError($e = null)
    {
        if (!is_null($e) || !empty($e)) {
            $this->errors[] = (string) $e;
        }
        return $this;
    }

    /**
     * Add critical message
     *
     * @param string $e Error string.
     * @return object Singleton instance.
     */
    public function addCritical($e = null)
    {
        if (!is_null($e) || !empty($e)) {
            $this->criticals[] = (string) $e;
        }
        return $this;
    }

    /**
     * Get universal ID string
     *
     * @return string Universal ID string.
     */
    public function getUIDstring()
    {
        $string = strtr(implode("_", [
            $_SERVER["HTTP_ACCEPT"] ?? "NA",
            $_SERVER["HTTP_ACCEPT_CHARSET"] ?? "NA",
            $_SERVER["HTTP_ACCEPT_ENCODING"] ?? "NA",
            $_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? "NA",
            $_SERVER["HTTP_USER_AGENT"] ?? "UA",
            $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "NA",
        ]), " ", "_");
        return $string;
    }

    /**
     * Get universal ID hash
     *
     * @return string Universal ID SHA256 hash.
     */
    public function getUID()
    {
        $hash = hash("sha256", $this->getUIDstring());
        return $hash;
    }

    /**
     * Set user identity
     *
     * @param array $identity Identity.
     * @return object Singleton instance.
     */
    public function setIdentity($identity)
    {
        if (!is_array($identity)) {
            throw new \Exception("Parameter must be an array!");
        }
        $i = [
            "avatar" => "",
            "country" => "",
            "email" => "",
            "id" => 0,
            "ip" => "",
            "name" => "",
        ];
        $file = DATA . "/" . self::IDENTITY_NONCE;
        $nonce = @file_get_contents($file);
        if (!$nonce) {
            try {
                $nonce = hash("sha256", random_bytes(256) . time());
                file_put_contents($file, $nonce);
                @chmod($file, 0660);
                $this->addMessage("ADMIN: noncefile created");
            } catch (Exception $e) {
                $this->addError("500: Internal Server Error");
                $this->setLocation("/err/500");
                exit;
            }
        }
        $i["nonce"] = substr(trim($nonce), 0, 8);
        if (array_key_exists("avatar", $identity)) {
            $i["avatar"] = (string) $identity["avatar"];
        }
        if (array_key_exists("email", $identity)) {
            $i["email"] = (string) $identity["email"];
        }
        if (array_key_exists("id", $identity)) {
            $i["id"] = (int) $identity["id"];
        }
        if (array_key_exists("name", $identity)) {
            $i["name"] = (string) $identity["name"];
        }
        $i["timestamp"] = time();
        $i["country"] = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? "";
        $i["ip"] = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
        $out = [];
        $keys = array_keys($i);
        shuffle($keys);
        foreach ($keys as $k) {
            $out[$k] = $i[$k];
        }
        $this->identity = $out;
        $s = json_encode($out);
        $this->setCookie("identity", $s);
//        if (CLI) echo $s."\n";
        //        bdump($_COOKIE["identity"]);
        return $this;
    }

    /**
     * Get user identity
     *
     * @return array Identity.
     */
    public function getIdentity()
    {
        $file = DATA . "/" . self::IDENTITY_NONCE;
        $nonce = @file_get_contents($file);
        if (!$nonce) {
            $this->setIdentity([]);
            $nonce = @file_get_contents($file) ?? "";
        }
        $nonce = substr(trim($nonce), 0, 8);
        $timestamp = time();

        // mock identity
        if (CLI || (DOMAIN == "localhost")) {
            $this->setIdentity([
                "email" => "f@mxd.cz",
                "id" => 666,
                "name" => "Mr. Robot",
            ]);
        }

        if (isset($_COOKIE["identity"])) {
            $identity = $this->getCookie("identity");
            $i = json_decode($identity, true);
            if (!is_array($i)) {
                $i = [];
            }

            if (!array_key_exists("nonce", $i)) {
                $i["nonce"] = "";
            }
            if (!array_key_exists("timestamp", $i)) {
                $i["timestamp"] = 0;
            }
            if ($i["nonce"] == $nonce) {
                $this->setIdentity([
                    "avatar" => $i["avatar"] ?? "",
                    "email" => $i["email"] ?? "",
                    "id" => $i["id"] ?? 0,
                    "name" => $i["name"] ?? "",
                ]);
            }
        }
        if (isset($_GET["identity"])) {
            $identity = $_GET["identity"];
            $i = json_decode($identity, true);
            if (!is_array($i)) {
                $i = [];
            }

            if (!array_key_exists("nonce", $i)) {
                $i["nonce"] = "";
            }
            if (!array_key_exists("timestamp", $i)) {
                $i["timestamp"] = 0;
            }
            if ($i["nonce"] == $nonce && ($timestamp - (int) $i["timestamp"] < 30)) {
                $this->setIdentity([
                    "avatar" => $i["avatar"] ?? "",
                    "email" => $i["email"] ?? "",
                    "id" => $i["id"] ?? 0,
                    "name" => $i["name"] ?? "",
                ]);
            }
        }
        if ($this->identity === []) {
            $this->setIdentity([]);
        }
//        bdump($this->identity, "IDENTITY");
        return $this->identity;
    }

    /**
     * Get current user data
     *
     * @return mixed Get current user data array or NULL.
     */
    public function getCurrentUser()
    {
        $u = array_replace_recursive([
            "avatar" => "",
            "email" => "",
            "id" => 0,
            "name" => "",
        ], $this->getIdentity());
        $u["uid"] = $this->getUID();
        $u["uidstring"] = $this->getUIDstring();
        return $u;
    }

    /**
     * Cfg getter
     *
     * @param string $key Index to configuration data or void.
     * @return mixed Configuration data ARRAY by index or whole ARRAY.
     */
    public function getCfg($key = null)
    {
        if (is_null($key)) {
            return $this->getData("cfg");
        }
        if (is_string($key)) {
            return $this->getData("cfg.$key");
        }
        throw new \Exception("FATAL ERROR: Invalid parameter!");
    }

    /**
     * Match getter
     *
     * @return mixed Possible match data array or false.
     */
    public function getMatch()
    {
        return $this->getData("match") ?? false;
    }

    /**
     * Presenter getter
     *
     * @return mixed Possible presenter data array or false.
     */
    public function getPresenter()
    {
        return $this->getData("presenter") ?? false;
    }

    /**
     * Router getter
     *
     * @return mixed Possible router data array or false.
     */
    public function getRouter()
    {
        return $this->getData("router") ?? false;
    }

    /**
     * View getter
     *
     * @return mixed Possible router view or false.
     */
    public function getView()
    {
        return $this->getData("view") ?? false;
    }

    /**
     * Set HTTP header for CSV file
     *
     * @return object Singleton instance.
     */
    public function setHeaderCsv()
    {
        header("Content-Type: text/csv; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for binary file
     *
     * @return object Singleton instance.
     */
    public function setHeaderFile()
    {
        header("Content-Type: application/octet-stream");
        return $this;
    }

    /**
     * Set HTTP header for HTML content
     *
     * @return object Singleton instance.
     */
    public function setHeaderHtml()
    {
        header("Content-Type: text/html; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for JSON content
     *
     * @return object Singleton instance.
     */
    public function setHeaderJson()
    {
        header("Content-Type: application/json; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for JSON content
     *
     * @return object Singleton instance.
     */
    public function setHeaderJavaScript()
    {
        header("Content-Type: application/javascript; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for PDF file
     *
     * @return object Singleton instance.
     */
    public function setHeaderPdf()
    {
        header("Content-Type: application/pdf");
        return $this;
    }

    /**
     * Set HTTP header for TEXT content
     *
     * @return object Singleton instance.
     */
    public function setHeaderText()
    {
        header("Content-Type: text/plain; charset=UTF-8");
        return $this;
    }

    /**
     * Get encrypted cookie
     *
     * @param string $name Cookie name.
     * @return mixed Cookie value.
     */
    public function getCookie($name = null)
    {
        if (is_null($name)) {
            $this->addError(__NAMESPACE__ . " : " . __METHOD__ . self::ERROR_NULL);
            return $this;
        }
        $key = $this->getCfg("secret_cookie_key") ?? "secure.key";
        $keyfile = DATA . "/$key";
        if (file_exists($keyfile)) {
            $enc = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $this->setCookie($name);
            return null;
        }
        $cookie = new Cookie($enc);
        return $cookie->fetch($name);
    }

    /**
     * Set encrypted cookie
     *
     * @param string $name Cookie name.
     * @param string $data Cookie data.
     * @return object Singleton instance.
     */
    public function setCookie($name = null, $data = "")
    {
        if (is_null($name)) {
            $this->addError(__NAMESPACE__ . " : " . __METHOD__ . self::ERROR_NULL);
            return $this;
        }
        $key = $this->getCfg("secret_cookie_key") ?? "secure.key";
        $keyfile = DATA . "/$key";
        if (file_exists($keyfile)) {
            $enc = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $enc = KeyFactory::generateEncryptionKey();
            KeyFactory::save($enc, $keyfile);
            @chmod($keyfile, self::COOKIE_KEY_FILEMODE);
            $this->addMessage("HALITE: new keyfile created");
        }
        $cookie = new Cookie($enc);
        $httponly = true;
        $samesite = "strict";
        $secure = true;
        if (DOMAIN == "localhost") {
            $secure = false;
            $httponly = true;
        }
        $cookie->store($name, (string) $data, time() + self::COOKIE_TTL, "/", DOMAIN, $secure, $httponly, $samesite);
        return $this;
    }

    /**
     * Clear encrypted cookie
     *
     * @param string $name Cookie name.
     * @return object  Singleton instance.
     */
    public function clearCookie($name = null)
    {
        if (is_null($name)) {
            $this->addError(__NAMESPACE__ . " : " . __METHOD__ . self::ERROR_NULL);
            return $this;
        }
        unset($_COOKIE[$name]);
        \setcookie($name, "", time() - 3600, "/");
        return $this;
    }

    /**
     * Set URL location and exit
     *
     * @param string $location Full or relative URL address.
     * @param integer $code HTTP code.
     * @return void Exit runtime.
     */
    public function setLocation($location = null, $code = 303)
    {
        $code = (int) $code;
        if (is_null($location)) {
            $location = "/?nonce=" . substr(hash("sha1", random_bytes(10) . (string) time()), 0, 8);
        }
        header("Location: $location", true, ($code > 300) ? $code : 303);
        exit;
    }

    /**
     * Google OAuth 2.0 logout
     *
     * @return void Exit runtime.
     */
    public function logout()
    {
        $this->setCookie("identity", "");
        unset($_COOKIE["identity"]);
        $this->identity = [];
        header('Clear-Site-Data: "cache", "cookies", "storage"');
        $this->setLocation($this->getCfg("canonical_url") ?? "/");
        exit;
    }

    /**
     * Check current user rate limits
     *
     * @param integer $maximum
     * @return object Singleton instance.
     */
    public function checkRateLimit($maximum = 0)
    {
        $maximum = ((int) $maximum > 0) ? (int) $maximum : self::LIMITER_MAXIMUM;
        $uid = $this->getUID();
        $file = "${uid}_rate_limit";
        if (!$rate = Cache::read($file, "limiter")) {
            $rate = 1;
        }
        $rate++;
        if ($rate > $maximum) {
            $this->addMessage("RATE LIMITED: $maximum reached");
            $this->setLocation("/err/420");
        }
        Cache::write($file, $rate, "limiter");
        return $this;
    }

    /**
     * Check if current user has access rights
     *
     * @param mixed $perms
     * @return object Singleton instance.
     */
    public function checkPermission($role = "admin")
    {
        if (!$role) {
            return $this;
        }
        $role = trim((string) $role);
        $email = $this->getIdentity()["email"];
        $groups = $this->getCfg("admin_groups") ?? [];

        if (strlen($role) && strlen($email)) {
            // group access by email
            if (in_array($email, $groups[$role] ?? [], true)) {
                return $this;
            }
            // any Google users allowed in group
            if (in_array("*", $groups[$role] ?? [], true)) {
                return $this;
            }
        }
        // not authorized
        $this->setLocation("/err/401");

/*
// force re-login
if ($this->getCfg("goauth_redirect")) {
$this->setLocation($this->getCfg("goauth_redirect") .
"?return_uri=" . $this->getCfg("goauth_origin") . ($_SERVER["REQUEST_URI"] ?? ""));
}
 */

    }

    /**
     * Get current user group
     *
     * @param string $email
     * @return string
     */
    public function getUserGroup($email = null)
    {
        $id = $this->getIdentity()["id"];
        $email = $this->getIdentity()["email"];
        if (!$id) {
            return false;
        }
        $mygroup = false;
        $email = trim((string) $email);
        // search all groups for email or asterisk
        foreach ($this->getCfg("admin_groups") ?? [] as $group => $users) {
            if (in_array($email, $users, true)) {
                $mygroup = $group;
                break;
            }
            if (in_array("*", $users, true)) {
                $mygroup = $group;
                continue;
            }
        }
        return $mygroup;
    }

    /**
     * Force CSV checking
     *
     * @param boolean $set
     * @return object Singleton instance.
     */
    public function setForceCsvCheck($set = true)
    {
        $this->force_csv_check = (bool) $set;
        return $this;
    }

    /**
     * Add post-load csv data
     *
     * @param mixed $key
     * @return object Singleton instance.
     */
    public function postloadAppData($key = null)
    {
        if (!is_null($key)) {
            if (is_string($key)) {
                $this->csv_postload[] = (string) $key;
                return $this;
            }
            if (is_array($key)) {
                $this->csv_postload = array_merge($this->csv_postload, $key);
                return $this;
            }
        }
        return $this;
    }

    /**
     * Get locales from GS sheets
     *
     * @param string $language
     * @param string $key
     * @return array
     */
    public function getLocale($language = "cs", $key = "key")
    {
        if (!is_array($this->getCfg("locales"))) return null;
        $locale = [];
        $language = trim(strtoupper((string) $language));
        $key = trim(strtoupper((string) $key));
        $cfg = $this->getCfg();        
        $file = strtolower("${language}_locale");
        $locale = Cache::read($file, "default");
        if ($locale === false || empty($locale)) {
            if (array_key_exists("locales", $cfg)) {
                $locale = [];
                foreach ((array) $cfg["locales"] as $k => $v) {
                    // read from csv file
                    $csv = false;
                    $subfile = strtolower($k);
                    if ($csv === false) {
                        $csv = @file_get_contents(DATA . "/${subfile}.csv");
                        if ($csv === false || strlen($csv) < self::CSV_MIN_SIZE) {
                            $csv = false;
                        }
                    }
                    // read from csv backup
                    if ($csv === false) {
                        $csv = @file_get_contents(DATA . "/${subfile}.bak");
                        if ($csv === false || strlen($csv) < self::CSV_MIN_SIZE) {
                            $csv = false;
                            continue;
                        }
                    }
                    // parse csv
                    $keys = [];
                    $values = [];
                    try {
                        $reader = Reader::createFromString($csv);
                        $reader->setHeaderOffset(0);
                        $records = (new Statement())->offset(1)->process($reader);
                        foreach ($records->fetchColumn($key) as $x) {
                            $keys[] = $x;
                        }
                        foreach ($records->fetchColumn($language) as $x) {
                            $values[] = $x;
                        }
                    } catch (Exception $e) {
                        bdump($e);
                        $this->addCritical("ERR: $language locale $k CORRUPTED");
                    }
                    $locale = array_replace($locale, array_combine($keys, $values));
                }
                $locale['$revisions'] = $this->getData("REVISIONS"); // git revisions
                // find all $ in combined locales array
                $dolar = array('$' => '$');
                foreach ((array) $locale as $a => $b) {
                    if (substr($a, 0, 1) === '$') {
                        $a = trim($a, '${}' . "\x20\t\n\r\0\x0B");
                        if (!strlen($a)) {
                            continue;
                        }
                        $dolar['$' . $a] = $b;
                        $dolar['${' . $a . "}"] = $b;
                    }
                }
                // replace $ and $$
                $locale = str_replace(array_keys($dolar), $dolar, $locale);
                $locale = str_replace(array_keys($dolar), $dolar, $locale);
            }
        }
        if ($locale === false || empty($locale)) {
            if ($this->force_csv_check) {
                header("HTTP/1.1 500 FATAL ERROR");
                $this->addCritical("ERR: LOCALES CORRUPTED");
                echo "<body><h1>HTTP Error 500</h1><h2>LOCALES CORRUPTED</h2></body>";
                exit;
            } else {
                $this->setForceCsvCheck()->checkLocales(true);
                return $this->getLocale($language, $key);
            }
        }
        Cache::write($file, $locale, "default");
        return (array) $locale;
    }

    /**
     * Check and preload locales
     *
     * @param boolean $force
     * @return object Singleton instance.
     */
    public function checkLocales($force = false)
    {
        $locales = $this->getCfg("locales");
        if (is_array($locales)) {
            foreach ($locales as $name => $csvkey) {
                $this->csv_preloader($name, $csvkey, (bool) $force);
            }
        }
        return $this;
    }

    /**
     * Purge CloudFlare cache
     *
     * @var array $cf Array of Cloudflare auth data.
     * @return object Singleton instance.
     */
    public function CloudflarePurgeCache($cf = null)
    {
        if (!is_array($cf)) {
            return false;
        }

        $email = $cf["email"] ?? null;
        $apikey = $cf["apikey"] ?? null;
        $zoneid = $cf["zoneid"] ?? null;
        try {
            if ($email && $apikey && $zoneid) {
                $key = new \Cloudflare\API\Auth\APIKey($email, $apikey);
                $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
                $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
                if (is_array($zoneid)) {
                    $myzones = $zoneid;
                }
                if (is_string($zoneid)) {
                    $myzones = [$zoneid];
                }
                foreach ($zones->listZones()->result as $zone) {
                    foreach ($myzones as $myzone) {
                        if ($zone->id == $myzone) {
                            $zones->cachePurgeEverything($zone->id);
                            $this->addMessage("CLOUDFLARE: zoneid ${myzone} cache purged");
                        }
                    }
                }
            }
        } catch (Execption $e) {}
        return $this;
    }

    /**
     * Load csv data into multi cache
     *
     * @param string $name
     * @param string $csvkey
     * @param string $postfix
     * @param boolean $force
     * @return object Singleton instance.
     */
    private function csv_preloader($name, $csvkey, $force = false)
    {
        $name = trim((string) $name);
        $csvkey = trim((string) $csvkey);
        $force = (bool) $force;
        $file = strtolower($name);
        if ($name && $csvkey) {
            if (Cache::read($file, "csv") === false || $force === true) {
                $data = false;
                if (!file_exists(DATA . "/${file}.csv")) {
                    $force = true;
                }
                if ($force) {
                    $data = @file_get_contents(self::GS_CSV_PREFIX . $csvkey . self::GS_CSV_POSTFIX . "&time=" . time());
                }
                if (strlen($data) >= self::CSV_MIN_SIZE) {
                    Cache::write($file, $data, "csv");
                    if (file_exists(DATA . "/${file}.bak")) {
                        if (@unlink(DATA . "/${file}.bak") === false) {
                            $this->addError("ERR: Deleting ${file}.bak failed!");
                        }
                    }
                    if (file_exists(DATA . "/${file}.csv")) {
                        if (@rename(DATA . "/${file}.csv", DATA . "/${file}.bak") === false) {
                            $this->addError("ERR: Backuping ${file}.csv failed!");
                        }
                    }
                    if (@file_put_contents(DATA . "/${file}.csv", $data, LOCK_EX) === false) {
                        $this->addError("ERR: Saving data to ${file}.csv failed!");
                        return false;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Load application csv data
     *
     * @param string $key
     * @param boolean $force
     * @return object Singleton instance.
     */
    public function preloadAppData($key = "app_data", $force = false)
    {
        $key = (string) $key;
        $cfg = $this->getCfg();
        if (array_key_exists($key, $cfg)) {
            foreach ((array) $cfg[$key] as $name => $csvkey) {
                $this->csv_preloader($name, $csvkey, (bool) $force);
            }
        }
        return $this;
    }

    /**
     * Read application csv data
     *
     * @param string $index
     * @return string
     */
    public function readAppData($index)
    {
        $index = (string) $index;
        $file = strtolower($index);
        $csv = Cache::read($file, "csv");
        if ($csv === false) {
            $csv = @file_get_contents(DATA . "/${file}.csv");
            if ($csv !== false || strlen($csv) >= self::CSV_MIN_SIZE) {
                Cache::write($file, $csv, "csv");
                return $csv;
            }
            $csv = @file_get_contents(DATA . "/${file}.bak");
            if ($csv !== false || strlen($csv) >= self::CSV_MIN_SIZE) {
                Cache::write($file, $csv, "csv");
                return $csv;
            }
            $csv = false;
        }
        return $csv;
    }

    /**
     * Write JSON data to output
     *
     * @param array $d Data can be integer error code or array of data.
     * @param array $headers Optional JSON array of data.
     * @return object Singleton instance.
     */
    public function writeJsonData($d = null, $headers = [])
    {
        $v = [];
        $v["timestamp"] = time();
        $v["version"] = $this->getCfg("version");
        if (is_array($this->getCfg("locales"))) {
          $locale = $this->getLocale("en");
        } else {
          $locale = [];
        }

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $code = 200;
                $msg = "OK";
                break;
            case JSON_ERROR_DEPTH:
                $code = 400;
                $msg = "Maximum stack depth exceeded.";
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $code = 400;
                $msg = "Underflow or the modes mismatch.";
                break;
            case JSON_ERROR_CTRL_CHAR:
                $code = 400;
                $msg = "Unexpected control character found.";
                break;
            case JSON_ERROR_SYNTAX:
                $code = 500;
                $msg = "Syntax error, malformed JSON.";
                break;
            case JSON_ERROR_UTF8:
                $code = 400;
                $msg = "Malformed UTF-8 characters, possibly incorrectly encoded.";
                break;
            default:
                $code = 500;
                $msg = $locale["server_error_info_500"] ?? "";
                break;
        }
        if (is_null($d)) {
            $code = 500;
            $msg = $locale["server_error_info_500"] ?? "";
        }
        if (is_string($d)) {
            $d = [$d];
        }
        if (is_int($d)) {
            $code = $d;
            switch ($d) {
                case 304:
                    $msg = $locale["server_error_info_304"] ?? "";
                    break;
                case 400:
                    $msg = $locale["server_error_info_400"] ?? "";
                    break;
                case 404:
                    $msg = $locale["server_error_info_404"] ?? "";
                    break;
                default:
                    $msg = "Unknown error.";
            }
            $d = null;
        }
        $v["code"] = $code;
        $v["message"] = $msg;
        $v = array_merge_recursive($v, $headers);
        $v["data"] = $d ?? null;
        $this->setHeaderJson();
        $data = $this->getData();
        $output = json_encode($v, JSON_PRETTY_PRINT);
        return $this->setData($data, "output", $output);
    }
}
