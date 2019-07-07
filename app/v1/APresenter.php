<?php

namespace GSC;

use Cake\Cache\Cache;
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
    public function checkAdmins($perms);
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
    public function getFP();
    public function getFPstring();
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

    // CONSTANTS

    /** @var string */
    const ERROR_NULL = " > FATAL ERROR: UNEXPECTED NULL";

    /** @var integer */
    const LOG_FILEMODE = 0664;

    /** @var integer */
    const CSV_FILEMODE = 0664;

    /** @var integer */
    const CSV_MIN_SIZE = 42;

    /** @var integer */
    const COOKIE_KEY_FILEMODE = 0600;

    /** @var integer */
    const COOKIE_TTL = 86400 * 14;

    /** @var string */
    const GS_CSV_PREFIX = "https://docs.google.com/spreadsheets/d/e/";

    /** @var string */
    const GS_CSV_POSTFIX = "/pub?output=csv";

    /** @var string */
    const GS_SHEET_PREFIX = "https://docs.google.com/spreadsheets/d/";

    /** @var string */
    const GS_SHEET_POSTFIX = "/edit#gid=0";

    /** @var integer */
    const LIMITER_MAXIMUM = 30;

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

    private $data = []; // DATA

    private $messages = []; // INFO + MESSAGES

    private $errors = []; // ERRORS

    private $criticals = []; // CRITICAL ERRORS

    private $identity = []; // user identity

    private $force_csv_check = false; // should re-check locales?

    private $csv_postload = []; // $cfg key indexes to [$name=>$csvkey] pairs

    private static $instances = array(); // singleton instances

    /**
     * Presenter processor
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
            throw new \Exception("INSTANCE of " . $class . " ALREADY EXISTS");
            exit;
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
        return (string) json_encode($this->getData());
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        ob_flush();
        flush();
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
        define("LASAGNA_STOP", ((float) $usec + (float) $sec));
        $add = "| processing: " . round(((float) LASAGNA_STOP - (float) LASAGNA_START) * 1000, 2) . " msec."
            . "| request_uri: " . ($_SERVER["REQUEST_URI"] ?? "N/A");

        try {
            if (count($criticals) + count($errors) + count($messages)) {
                $logging = new LoggingClient(["projectId" => GCP_PROJECTID]);
                $google_logger = $logging->logger($this->getCfg("app") ?? "app");
            }
            if (count($criticals)) {
                $monolog->critical(SERVER . " ERR: " . json_encode($criticals) . $add);
                $google_logger->write($google_logger->entry(SERVER . " ERR: " . json_encode($criticals) . $add, [
                    "severity" => Logger::CRITICAL,
                ]));
            }
            if (count($errors)) {
                $monolog->error(SERVER . " ERR: " . json_encode($errors) . $add);
                $google_logger->write($google_logger->entry(SERVER . " ERR: " . json_encode($errors) . $add, [
                    "severity" => Logger::ERROR,
                ]));
            }
            if (count($messages)) {
                $monolog->info(SERVER . " MSG: " . json_encode($messages) . $add);
                $google_logger->write($google_logger->entry(SERVER . " MSG: " . json_encode($messages) . $add, [
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
     * @return object
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
     * @param string $template
     * @return string
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
     * @param string $key
     * @return mixed
     */
    public function getData($key = null)
    {
        $data = (array) $this->data;

        // global constants
        $data["CONST"]["APP"] = APP;
        $data["CONST"]["CACHE"] = CACHE;
        $data["CONST"]["DOMAIN"] = DOMAIN;
        $data["CONST"]["MONOLOG"] = MONOLOG;
        $data["CONST"]["PROJECT"] = PROJECT;
        $data["CONST"]["ROOT"] = ROOT;
        $data["CONST"]["SERVER"] = SERVER;
        $data["CONST"]["VERSION"] = VERSION;

        // class constants
        $data["COOKIE_KEY_FILEMODE"] = self::COOKIE_KEY_FILEMODE;
        $data["COOKIE_TTL"] = self::COOKIE_TTL;
        $data["CSV_FILEMODE"] = self::CSV_FILEMODE;
        $data["CSV_MIN_SIZE"] = self::CSV_MIN_SIZE;
        $data["ERROR_NULL"] = self::ERROR_NULL;
        $data["GS_CSV_POSTFIX"] = self::GS_CSV_POSTFIX;
        $data["GS_CSV_PREFIX"] = self::GS_CSV_PREFIX;
        $data["GS_SHEET_POSTFIX"] = self::GS_SHEET_POSTFIX;
        $data["GS_SHEET_PREFIX"] = self::GS_SHEET_PREFIX;
        $data["LIMITER_MAXIMUM"] = self::LIMITER_MAXIMUM;
        $data["LOG_FILEMODE"] = self::LOG_FILEMODE;

        if (is_null($key)) {
            return $data;
        }
        return $data[$key] ?? null;
    }

    /**
     * Data setter
     *
     * @param array $data
     * @param string $key
     * @param mixed $value
     * @return object
     */
    public function setData($data = null, $key = null, $value = null)
    {
        if (is_null($data)) {
            $data = $this->data;
        }
        if (is_string($key) && !empty($key)) {
            $data[$key] = $value;
        }
        $this->data = (array) $data;
        return $this;
    }

    /**
     * Messages getter
     *
     * @return array
     */
    public function getMessages()
    {
        return (array) $this->messages;
    }

    /**
     * Errors getter
     *
     * @return array
     */
    public function getErrors()
    {
        return (array) $this->errors;
    }

    /**
     * Criticals getter
     *
     * @return array
     */
    public function getCriticals()
    {
        return (array) $this->criticals;
    }

    /**
     * Add a message
     *
     * @param string $e
     * @return void
     */
    public function addMessage($e = null)
    {
        if (!is_null($e) || !empty($e)) {
            $this->messages[] = (string) $e;
        }
        return $this;
    }

    /**
     * Add an error
     *
     * @param string $e
     * @return void
     */
    public function addError($e = null)
    {
        if (!is_null($e) || !empty($e)) {
            $this->errors[] = (string) $e;
        }
        return $this;
    }

    /**
     * Add an emergency
     *
     * @param string $e
     * @return void
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
     * @return string
     */
    public function getUIDstring()
    {
        $a = $_SESSION["admin"] ?? $this->getCookie("admin") ?? "N/A";
        $b = session_id();
        $c = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
        return "${a}_${b}_${c}";
    }

    /**
     * Get universal fingerprint
     *
     * @return string
     */
    public function getFPstring()
    {
        $a = $_SESSION["admin"] ?? $this->getCookie("admin") ?? "N/A";
        $b = ($_SERVER["HTTP_USER_AGENT"] ?? "unknown") . ($_SERVER["HTTP_ACCEPT"] ?? "") . ($_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? "en");
        $c = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "N/A";
        return "${a}_${b}_${c}";
    }

    /**
     * Get universal ID hash
     *
     * @return string
     */
    public function getUID()
    {
        return hash("sha256", $this->getUIDstring());
    }

    /**
     * Get universal FP hash
     *
     * @return string
     */
    public function getFP()
    {
        return hash("sha256", $this->getFPstring());
    }

    /**
     * Set user identity
     *
     * @param array $identity
     * @return mixed
     */
    public function setIdentity($identity)
    {
        return $this;
    }

    /**
     * Get user identity
     *
     * @return mixed
     */
    public function getIdentity()
    {
        // debugging
        /*
        if (!strlen($this->getCookie("admin"))) {
        if (($_SERVER["SERVER_NAME"] ?? "") == "localhost") {
        $u = [];
        $u["avatar"] = "https://cdn0.iconfinder.com/data/icons/robot-3-2/512/RobotV2-52-512.png";
        $u["email"] = "admin@gscloud.cz";
        $u["fp"] = $this->getFP();
        $u["id"] = 666;
        $u["name"] = "Mr. Robot";
        $u["uid"] = $this->getUID();
        return $u;
        }
        }
        $u = [];
        $u["avatar"] = $this->getCookie("avatar") ?? "";
        $u["email"] = $this->getCookie("admin") ?? "";
        $u["fp"] = $this->getFP();
        $u["id"] = $this->getCookie("id") ?? "";
        $u["name"] = $this->getCookie("name") ?? "";
        $u["uid"] = $this->getUID();
         */
        return $this->identity;
    }

    /**
     * Get current user data
     *
     * @return mixed
     */
    public function getCurrentUser()
    {
        // debugging
        if (!strlen($this->getCookie("admin"))) {
            if (($_SERVER["SERVER_NAME"] ?? "") == "localhost") {
                $u = [];
                $u["avatar"] = "https://cdn0.iconfinder.com/data/icons/robot-3-2/512/RobotV2-52-512.png";
                $u["email"] = "admin@gscloud.cz";
                $u["fp"] = $this->getFP();
                $u["id"] = 666;
                $u["name"] = "Mr. Robot";
                $u["uid"] = $this->getUID();
                return $u;
            }
        }
        $u = [];
        $u["avatar"] = $this->getCookie("avatar") ?? "";
        $u["email"] = $this->getCookie("admin") ?? "";
        $u["fp"] = $this->getFP();
        $u["id"] = $this->getCookie("id") ?? "";
        $u["name"] = $this->getCookie("name") ?? "";
        $u["uid"] = $this->getUID();
        return ($u["id"] && $u["email"]) ? $u : false;
    }

    /**
     * Cfg getter
     *
     * @param string $key
     * @return mixed
     */
    public function getCfg($key = null)
    {
        $cfg = (array) $this->getData("cfg") ?? [];
        if (is_null($key)) {
            return $cfg;
        }
        return $cfg[$key] ?? null;
    }

    /**
     * Match getter
     *
     * @return mixed
     */
    public function getMatch()
    {
        return $this->getData("match") ?? false;
    }

    /**
     * Presenter getter
     *
     * @return mixed
     */
    public function getPresenter()
    {
        return $this->getData("presenter") ?? false;
    }

    /**
     * Router getter
     *
     * @return mixed
     */
    public function getRouter()
    {
        return $this->getData("router") ?? false;
    }

    /**
     * View getter
     *
     * @return mixed
     */
    public function getView()
    {
        return $this->getData("view") ?? false;
    }

    /**
     * Set HTTP header for CSV file
     *
     * @return object
     */
    public function setHeaderCsv()
    {
        header("Content-Type: text/csv; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for binary file
     *
     * @return object
     */
    public function setHeaderFile()
    {
        header("Content-Type: application/octet-stream");
        return $this;
    }

    /**
     * Set HTTP header for HTML content
     *
     * @return object
     */
    public function setHeaderHtml()
    {
        header("Content-Type: text/html; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for JSON content
     *
     * @return object
     */
    public function setHeaderJson()
    {
        header("Content-Type: application/json; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for JSON content
     *
     * @return object
     */
    public function setHeaderJavaScript()
    {
        header("Content-Type: application/javascript; charset=UTF-8");
        return $this;
    }

    /**
     * Set HTTP header for PDF file
     *
     * @return object
     */
    public function setHeaderPdf()
    {
        header("Content-Type: application/pdf");
        return $this;
    }

    /**
     * Set HTTP header for TEXT content
     *
     * @return object
     */
    public function setHeaderText()
    {
        header("Content-Type: text/plain; charset=UTF-8");
        return $this;
    }

    /**
     * Get encrypted cookie
     *
     * @param string $name
     * @return mixed
     */
    public function getCookie($name = null)
    {
        if (is_null($name)) {
            $this->addError(__NAMESPACE__ . " : " . __METHOD__ . self::ERROR_NULL);
            return $this;
        }
        $secret_key = $this->getCfg("secret_cookie_key") ?? "secure.key";
        $keyfile = DATA . "/${secret_key}";
        if (file_exists($keyfile)) {
            $enc_key = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $this->setCookie($name);
            return null;
        }
        $cookie = new Cookie($enc_key);
        return $cookie->fetch($name);
    }

    /**
     * Set encrypted cookie
     *
     * @param string $name
     * @param string $data
     * @return object
     */
    public function setCookie($name = null, $data = null)
    {
        if (is_null($name)) {
            return $this;
        }
        $secret_key = $this->getCfg("secret_cookie_key") ?? "secure.key";
        $keyfile = DATA . "/${secret_key}";
        if (file_exists($keyfile)) {
            $enc_key = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $enc_key = KeyFactory::generateEncryptionKey();
            KeyFactory::save($enc_key, $keyfile);
            @chmod($keyfile, self::COOKIE_KEY_FILEMODE);
            $this->addMessage("HALITE: new keyfile created");
        }
        $cookie = new Cookie($enc_key);
        $httponly = true;
        $samesite = "strict";
        $secure = true;
        if (DOMAIN == "localhost") {
            $secure = false;
            $httponly = true;
        }
        $cookie->store($name, $data, time() + self::COOKIE_TTL, "/", DOMAIN, $secure, $httponly, $samesite);
        return $this;
    }

    /**
     * Clear encrypted cookie
     *
     * @param string $name
     * @return object
     */
    public function clearCookie($name = null)
    {
        if (is_null($name)) {
            $this->addError(__NAMESPACE__ . " : " . __METHOD__ . self::ERROR_NULL);
            return $this;
        }
        unset($_COOKIE[$name]);
        \setcookie($name, null, -1, "/");
        return $this;
    }

    /**
     * Set URL location and exit
     *
     * @param string $location
     * @param integer $code
     * @return void
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
     * @return void
     */
    public function logout()
    {
        if (session_id() == "") {
            session_start();
        }
        $this->clearCookie("admin");
        $this->clearCookie("avatar");
        $this->clearCookie("id");
        $this->clearCookie("name");
        unset($_SESSION["admin"]);
        unset($_SESSION["avatar"]);
        unset($_SESSION["id"]);
        unset($_SESSION["name"]);
        session_regenerate_id(true);
        $this->setLocation("https://www.google.com/accounts/Logout");
    }

    /**
     * Check current user rate limits
     *
     * @param string $uid
     * @param integer $maximum
     * @return object
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
            $this->addMessage("RATE LIMITED: ${maximum} reached");
            $this->setLocation("/err/420");
        }
        Cache::write($file, $rate, "limiter");
        return $this;
    }

    /**
     * Check if current user has access rights
     *
     * @param mixed $perms
     * @return object
     */
    public function checkAdmins($perms = false)
    {
        // public nodes
        if (!$perms) {
            return $this;
        }
        // debugging
        if (strlen($this->getCookie("admin")) == 0) { // mock identity pouze pokud nemáme validní cookie!
            if (($_SERVER["SERVER_NAME"] ?? "") == "localhost") {
                return $this;
            }
        }
        $email = $_SESSION["admin"] ?? $this->getCookie("admin") ?? false;
        $groups = $this->getCfg("admin_groups") ?? [];
        // private nodes, must validate permissions
        if ($perms === 1 && strlen($email)) {
            // public access, any Google users allowed
            if (in_array("*", $groups["default"] ?? [], true)) {
                return $this;
            }
            // check default group
            if (in_array($email, $groups["default"] ?? [], true)) {
                return $this;
            }
            if (in_array($email, $groups["admin"] ?? [], true)) {
                return $this;
            }
            // logged but not authorized
            $this->setLocation("/err/401");
        }

        if (is_string($perms) && strlen($email)) {
            // check specified group
            if (in_array($email, $groups[trim($perms)] ?? [], true)) {
                return $this;
            }
            // logged but not authorized
            $this->setLocation("/err/401");
        }
        // force re-login
        if ($this->getCfg("goauth_redirect")) {
            $this->setLocation($this->getCfg("goauth_redirect") .
                "?return_uri=" . $this->getCfg("goauth_origin") . ($_SERVER["REQUEST_URI"] ?? ""));
        }
    }

    /**
     * Get current user group
     *
     * @param string $email
     * @return string
     */
    public function getUserGroup($email = null)
    {
        // debugging
        if (strlen($this->getCookie("admin")) == 0) { // mock identity pouze pokud nemáme validní cookie!
            if (($_SERVER["SERVER_NAME"] ?? "") == "localhost") {
                return "admin";
            }
        }
        $email = $_SESSION["admin"] ?? $this->getCookie("admin") ?? false;
        // not logged!
        if ($email === false) {
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
     * Force csv checking
     *
     * @param boolean $set
     * @return object
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
     * @return object
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
                    $subfile = strtolower("${k}");
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
                        $this->addCritical("ERR: ${language} locale ${k} CORRUPTED");
                    }
                    $locale = array_replace($locale, array_combine($keys, $values));
                }
                // find all $ in combined locales array
                $dolar = array('$' => '$');
                foreach ((array) $locale as $a => $b) {
                    if (substr($a, 0, 1) === '$') {
                        $a = trim($a, '${}' . " \t\n\r\0\x0B");
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
                $this->addCritical("FATAL ERROR: LOCALES CORRUPTED!");
                echo "<body><h1>HTTP Error 500</h1><h2>LOCALES CORRUPTED!</h2></body>";
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
     * @return object
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
     * @var array $cf
     * @return object
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
     * @return object
     */
    private function csv_preloader($name, $csvkey, $force = false)
    {
        $name = trim((string) $name);
        $csvkey = trim((string) $csvkey);
        $force = (bool) $force;
        $file = strtolower("${name}");
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
     * @return object
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
     * @param array $d
     * @param array $headers
     * @return object
     */
    public function writeJsonData($d = null, $headers = [])
    {
        $v = [];
        $v["timestamp"] = time();
        $v["version"] = $this->getCfg("version");
        $code = 200;
        $msg = "OK";

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
                $msg = "Unknown error.";
                break;
        }
        $v["code"] = $code;
        $v["message"] = $msg;
        $v = array_merge_recursive($v, $headers);
        if (is_string($d)) {
            $d = [$d];
        }
        if (is_null($d)) {
            $code = 500;
            $msg = "Internal Server Error";
        }
        $v["data"] = $d ?? null;
        $this->setHeaderJson();
        $data = $this->getData();
        $output = json_encode($v, JSON_PRETTY_PRINT);
        return $this->setData($data, "output", $output);
    }
}
