<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */

namespace GSC;

use Cake\Cache\Cache;
use League\Csv\Reader;
use League\Csv\Statement;
use Nette\Neon\Neon;
use ParagonIE\Halite\Cookie;
use ParagonIE\Halite\KeyFactory;

/**
 * Abstract Presenter class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
abstract class APresenter
{
    /* @var integer max string length to decode by NE-ON */
    const NEON_DECODE_LIMIT = 4096;

    /* @var integer octal file mode for logs */
    const LOG_FILEMODE = 0664;

    /* @var integer octal file mode for CSV */
    const CSV_FILEMODE = 0664;

    /* @var integer CSV min. file size - something meaningful :) */
    const CSV_MIN_SIZE = 42;

    /* @var string UID cookie name */
    const COOKIE_UID = 'UID';

    /* @var string cookie secret filename */
    const COOKIE_KEY_FILE = 'cookie_key.key';

    /* @var string cookie secret filename */
    const COOKIE_KEY_FILE_TEST = 'cookie_key_test.key';

    /* @var integer octal file mode for cookie secret */
    const COOKIE_KEY_FILEMODE = 0600;

    /* @var integer cookie TTL in seconds */
    const COOKIE_TTL = 86400 * 31;

    /* @var string Google Sheet export URL prefix */
    const GS_CSV_PREFIX = 'https://docs.google.com/spreadsheets/d/e/';

    /* @var string Google Sheet export to CSV URL postfix */
    const GS_CSV_POSTFIX = '/pub?gid=0&single=true&output=csv';

    /* @var string Google Sheet export to TSV URL postfix */
    const GS_TSV_POSTFIX = '/pub?gid=0&single=true&output=tsv';

    /* @var string Google Sheet URL prefix */
    const GS_SHEET_PREFIX = 'https://docs.google.com/spreadsheets/d/';

    /* @var string Google Sheet edit URL postfix */
    const GS_SHEET_POSTFIX = '/edit#gid=0';

    /* @var integer rate limiter - max. hits per cache interval */
    const LIMITER_MAXIMUM = 30;

    /* @var integer rate limiter - max. limiter ceil hits before ban */
    const BAN_MAXIMUM = 10;

    /* @var integer update ignore interval in seconds */
    const CSV_UPDATE_IGNORE = 120;

    /* @var string identity nonce filename inside the DATA folder */
    const IDENTITY_NONCE_FILE = 'identity_nonce.key';

    /* @var string audit log filename */
    const AUDITLOG_FILE = 'AuditLog.txt';

    /* @var integer octal file mode for Audit Log */
    const AUDITLOG_FILEMODE = 0644;

    /* @var array data model */
    public $data = [];

    /* @var array messages */
    public $messages = [];

    /* @var array errors */
    public $errors = [];

    /* @var array critical Errors */
    public $criticals = [];

    /* @var array user identity */
    public $identity = [];

    /* @var boolean force check locales in desctructor */
    public $force_csv_check = false;

    /* @var array CSV Keys */
    public $csv_postload = [];

    /* @var array cookies */
    public $cookies = [];

    /* @var array singleton instances */
    public static $instances = [];

    /**
     * Abstract Processor
     *
     * @param mixed $param optional parameter
     * 
     * @abstract
     * 
     * @return object instance
     */
    abstract public function process($param = null);

    /**
     * Class constructor
     */
    private function __construct()
    {
        $class = get_called_class();
        if (array_key_exists($class, self::$instances)) {
            // throw an exception if class is already instantiated
            throw new \Exception(
                "FATAL ERROR: instance of class [{$class}] already exists"
            );
        }
    }

    /**
     * Magic clone - when invoking inaccessible methods in an object context
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Magic call - when invoking inaccessible methods in an object context
     *
     * @param string $name      name
     * @param mixed  $parameter parameter
     * 
     * @return void
     */
    public function __call($name, $parameter)
    {
    }

    /**
     * Magic call static - when invoking inaccessible methods in an object context
     *
     * @param string $name      name
     * @param mixed  $parameter parameter
     * 
     * @return void
     */
    public static function __callStatic($name, $parameter)
    {
    }

    /**
     * Object to string
     *
     * @return string Serialized JSON data model
     */
    public function __toString()
    {
        return (string) \json_encode($this->getData(), JSON_PRETTY_PRINT);
    }

    /**
     * Class destructor - the home of many final tasks
     */
    public function __destruct()
    {
        if (\ob_get_level()) {
            @\ob_end_flush();
        }
        if (\function_exists('fastcgi_finish_request')) {
            \fastcgi_finish_request();
        }

        // save messages and errors
        $errors = $this->getErrors();
        $messages = $this->getMessages();
        $criticals = $this->getCriticals();
        $this->_logArrayToJson($errors, LOGS . DS . 'errors.json');
        $this->_logArrayToJson($messages, LOGS . DS . 'messages.json');
        $this->_logArrayToJson($criticals, LOGS . DS . 'criticals.json');

        // preload CSV definitions
        foreach ($this->csv_postload as $key) {
            $this->preloadAppData((string) $key, true);
        }

        // load actual CSV data
        $this->checkLocales((bool) $this->force_csv_check);
        exit(0);
    }

    /**
     * Logs an array to a JSON file
     *
     * @param array  $data     data to be logged
     * @param string $filePath path to the log file
     *
     * @return void
     */
    private function _logArrayToJson(array $data, string $filePath): void
    {
        if (empty($data)) {
            return;
        }
        $logEntry = [
            'timestamp' => \date('c'),
            'data' => $data,
        ];
        $json = \json_encode($logEntry);
        if ($json === false) {
            $err = \json_last_error_msg();
            \error_log("Error encoding JSON for file [" . $filePath . ']. Message: ' .  $err); // phpcs:ignore
            return;
        }
        $logLine = $json . "\n";
        $flags = FILE_APPEND | LOCK_EX;
        if (@file_put_contents($filePath, $logLine, $flags) === false) {
            $err = \error_get_last()['message'];
            \error_log("Error writing to log file [" . $filePath . ']. Message: ' . $err); // phpcs:ignore
        }
    }

    /**
     * Get singleton object
     *
     * @static
     * @final
     * 
     * @return self
     */
    final public static function getInstance()
    {
        // SANITY CHECK
        foreach ([
            'APP',
            'CONFIG',
            'DATA',
            'DS',
            'ROOT',
            'SS',
        ] as $x) {
            defined($x) || die("FATAL ERROR: sanity check - const '{$x}' failed!");
        }
        $class = get_called_class();
        if (array_key_exists($class, self::$instances) === false) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Get instance for testing
     *
     * @static
     * @final
     * 
     * @return object Class instance
     */
    final public static function getTestInstance()
    {
        // SANITY CHECK
        foreach ([
            'APP',
            'CONFIG',
            'DATA',
            'DS',
            'ROOT',
            'SS',
        ] as $x) {
            defined($x) || die("FATAL ERROR: sanity check - const '{$x}' failed!");
        }
        $class = get_called_class();
        return new $class();
    }

    /**
     * Render HTML content from given template
     *
     * @param string $template Template name
     * 
     * @return string HTML output
     */
    public function renderHTML($template = null)
    {
        if (\is_null($template)) {
            $template = 'index';
        }
        // $type: string = 0, template = 1
        $type = (file_exists(TEMPLATES . DS . "{$template}.mustache")) ? 1 : 0;
        $renderer = new \Mustache_Engine(
            array(
            'template_class_prefix' => PROJECT . SS,
            'cache' => TEMP,
            'cache_file_mode' => 0666,
            'cache_lambda_templates' => true,
            'loader' => $type ? new \Mustache_Loader_FilesystemLoader(TEMPLATES)
                : new \Mustache_Loader_StringLoader,
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(PARTIALS),
            'helpers' => [
                'timestamp' => function () {
                    return (string) time();
                },
                'rndstr' => function () {
                    return $this->getNonce();
                },
                'convert_hyperlinks' => function (
                    $source, \Mustache_LambdaHelper $lambdaHelper
                ) {
                    $text = $lambdaHelper->render($source);
                    $text = preg_replace(
                        '/(https)\:\/\/([a-zA-Z0-9\-\.]+\.'
                        . '[a-zA-Z]{2,20})(\/[a-zA-Z0-9\-_\/]*)?/',
                        '<a rel="noopener nofollow" '
                        . 'target=_blank href="$0">$2$3</a>',
                        $text
                    );
                    return (string) $text;
                },
                'shuffle_lines' => function (
                    $source, \Mustache_LambdaHelper $lambdaHelper
                ) {
                    $text = $lambdaHelper->render($source);
                    $arr = explode("\n", $text);
                    shuffle($arr);
                    $text = join("\n", $arr);
                    return (string) $text;
                },
            ],
            'charset' => 'UTF-8',
            'escape' => function ($value) {
                return $value;
            },
            )
        );
        return $type ? $renderer->loadTemplate($template)->render($this->getData())
            : $renderer->render($template, $this->getData());
    }

    /**
     * Data getter
     *
     * @param string $key array key, dot notation (optional)
     * 
     * @return mixed value / array
     */
    public function getData($key = null)
    {
        $dot = new \Adbar\Dot((array) $this->data);

        // global engine constants
        $dot->set(
            [
                'CONST.APP' => APP,
                'CONST.CACHE' => CACHE,
                'CONST.CACHEPREFIX' => CACHEPREFIX,
                'CONST.CLI' => CLI,
                'CONST.CONFIG' => CONFIG,
                'CONST.CONFIG_PRIVATE' => CONFIG_PRIVATE,
                'CONST.CSP' => CSP,
                'CONST.DATA' => DATA,
                'CONST.DOMAIN' => DOMAIN,
                'CONST.DOWNLOAD' => DOWNLOAD,
                'CONST.ENGINE' => ENGINE,
                'CONST.FCGI_ENABLED' => FCGI_ENABLED,
                'CONST.LOGS' => LOGS,
                'CONST.LOCALHOST' => LOCALHOST,
                'CONST.PARTIALS' => PARTIALS,
                'CONST.PROJECT' => PROJECT,
                'CONST.REDIS_CACHE' => REDIS_CACHE,
                'CONST.ROOT' => ROOT,
                'CONST.SERVER' => SERVER,
                'CONST.TEMP' => TEMP,
                'CONST.TEMPLATES' => TEMPLATES,
                'CONST.UPLOAD' => UPLOAD,
                'CONST.WWW' => WWW,

                // PHP ini constants
                'CONST.MAX_FILE_UPLOADS' => ini_get('max_file_uploads'),
                'CONST.POST_MAX_SIZE' => ini_get('post_max_size'),
                'CONST.UPLOAD_MAX_FILESIZE' => ini_get('upload_max_filesize'),
            ]
        );

        // class constants
        $dot->set(
            [
                'CONST.LOG_FILEMODE' => self::LOG_FILEMODE,
                'CONST.CSV_FILEMODE' => self::CSV_FILEMODE,
                'CONST.CSV_MIN_SIZE' => self::CSV_MIN_SIZE,
                'CONST.COOKIE_KEY_FILEMODE' => self::COOKIE_KEY_FILEMODE,
                'CONST.COOKIE_TTL' => self::COOKIE_TTL,
                'CONST.GS_CSV_PREFIX' => self::GS_CSV_PREFIX,
                'CONST.GS_CSV_POSTFIX' => self::GS_CSV_POSTFIX,
                'CONST.GS_TSV_POSTFIX' => self::GS_TSV_POSTFIX,
                'CONST.GS_SHEET_PREFIX' => self::GS_SHEET_PREFIX,
                'CONST.GS_SHEET_POSTFIX' => self::GS_SHEET_POSTFIX,
                'CONST.LIMITER_MAXIMUM' => self::LIMITER_MAXIMUM,
                'CONST.BAN_MAXIMUM' => self::BAN_MAXIMUM,
                'CONST.CSV_UPDATE_IGNORE' => self::CSV_UPDATE_IGNORE,
                'CONST.AUDITLOG_FILE' => self::AUDITLOG_FILE,
            ]
        );
        if (\is_string($key)) {
            return $dot->get($key);
        }
        $this->data = (array) $dot->all();
        return $this->data;
    }

    /**
     * Data setter
     *
     * @param mixed $data  array / key
     * @param mixed $value value
     * 
     * @return self
     */
    public function setData($data = null, $value = null)
    {
        if (\is_array($data)) {
             // new model, replace it
            $this->data = (array) $data;
        } else {
            // $data = key index
            $key = $data;
            if (\is_string($key) && !empty($key)) {
                if (\str_starts_with($key, 'cfg.')) {
                    throw new \Exception('FATAL ERROR: trying to modify cfg data');
                }
                $dot = new \Adbar\Dot($this->data);
                $dot->set($key, $value);
                $this->data = (array) $dot->all();
            }
        }
        return $this;
    }

    /**
     * Messages getter
     *
     * @return array Array of messages
     */
    public function getMessages()
    {
        return (array) $this->messages;
    }

    /**
     * Errors getter
     *
     * @return array Array of errors
     */
    public function getErrors()
    {
        return (array) $this->errors;
    }

    /**
     * Criticals getter
     *
     * @return array Array of critical messages
     */
    public function getCriticals()
    {
        return (array) $this->criticals;
    }

    /**
     * Add audit message to the Audit Log
     *
     * @param string $message Message string
     * 
     * @return self
     */
    public function addAuditMessage($message = null)
    {
        if (CLI || !is_string($message) || empty(trim($message))) {
            return $this;
        }

        $message = trim($message);
        $message = \str_replace(["\n", "\r", "\t", ';', '  '], ["<br>", " ", " ", ",", ' '], $message); // phpcs:ignore
        $date = \date('c');
        $ip = $this->getIP();
        $i = $this->getIdentity();
        $name = $i['name'] ?? '';
        $email = $i['email'] ?? '';

        if (empty($name)) {
            try {
                $name = \gethostbyaddr($ip);
                if ($name === $ip) {
                    $name = '';
                }
            } catch (\Exception $e) {
                $name = '';
                $this->errors[] = 'Could not translate IP address: [' . $ip . '] ' . $e->getMessage(); // phpcs:ignore
            }
        }

        $file = DATA . DS . self::AUDITLOG_FILE;
        $logline = "$date;$message;{$ip};{$name};{$email}\n";
        $flags = FILE_APPEND | LOCK_EX;
        if (@file_put_contents($file, $logline, $flags) === false) {
            $this->criticals[] = 'Could not write to the Audit Log file: ' . $file;
        }
        return $this;
    }

    /**
     * Add info message
     *
     * @param string $message message string
     * 
     * @return self
     */
    public function addMessage($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $this->messages[] = $message;
            $this->addAuditMessage($message);

            // get the backtrace
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[0];
            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 'unknown';

            $message = date('Y-m-d H:i:s') . ' ' . $message . ' - file: ' . $file . ' - line: ' . $line; // phpcs:ignore
            \error_log($message, 0);
            if (CLI) {
                return $this;
            }
            $message = str_replace("\n", ' ', $message);
            \error_log($message . PHP_EOL, 3, LOGS . DS . 'messages.txt');
        }
        return $this;
    }

    /**
     * Add error message
     *
     * @param string $message error string
     * 
     * @return self
     */
    public function addError($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $this->errors[] = $message;
            $this->addAuditMessage($message);

            // get the backtrace
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[0];
            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 'unknown';

            $message = '* ERROR: ' . $message;
            $message = date('Y-m-d H:i:s') . ' ' . $message . ' - file: ' . $file . ' - line: ' . $line; // phpcs:ignore
            \error_log($message, 0);
            if (CLI) {
                return $this;
            }
            $message = str_replace("\n", ' ', $message);
            \error_log($message . PHP_EOL, 3, LOGS . DS . 'errors.txt');
        }
        return $this;
    }

    /**
     * Add critical message
     *
     * @param string $message error string
     * 
     * @return self
     */
    public function addCritical($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $this->criticals[] = $message;

            // get the backtrace
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[0];
            $file = $caller['file'] ?? 'unknown';
            $line = $caller['line'] ?? 'unknown';

            $message = '*** CRITICAL: ' . $message;
            $message = date('Y-m-d H:i:s') . ' ' . $message . ' - file: ' . $file . ' - line: ' . $line; // phpcs:ignore
            \error_log($message, 0);
            if (CLI) {
                return $this;
            }
            $message = str_replace("\n", ' ', $message);
            \error_log($message . PHP_EOL, 3, LOGS . DS . 'critical_errors.txt');
        }
        return $this;
    }

    /**
     * Get IP address
     *
     * @return string IP address
     */
    public function getIP()
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = \explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim(\end($ipList));
            return $ip;
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '127.0.0.1';
    }

    /**
     * Get Universal ID string
     *
     * @return string Universal ID string
     */
    public function getUIDstring()
    {
        $parts = [
            CLI ? 'CLI' : '',
            CLI ? '' : $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'N/A',
            CLI ? '' : $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'N/A',
            CLI ? '' : $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            $this->getIP(),
        ];
        if (!CLI) {
            $name = self::COOKIE_UID;
            $uid = $this->getNonce();
            if (isset($_COOKIE[$name])) {
                $uid = $_COOKIE[$name];
                if (!preg_match('/^[a-fA-f0-9]{16}$/', $uid)) {
                    $this->addError("COOKIE: invalid UUID cookie");
                    unset($_COOKIE[$name]);
                }
            }
            if (!isset($_COOKIE[$name])) {
                \setcookie(
                    $name,
                    $uid,
                    \time() + self::COOKIE_TTL,
                    '/',
                    DOMAIN,
                    !LOCALHOST,
                    true
                );
                $_COOKIE[$name] = $uid;
            }
            $parts[] = $uid;
            \header('X-UID: ' . $uid);
        }
        $parts = \array_filter($parts);
        return \preg_replace('/__/', SS, \strtr(\implode(SS, $parts), ' ', SS));
    }

    /**
     * Get Universal ID (hash)
     *
     * @return string SHA-256 hash
     */
    public function getUID()
    {
        return \hash('sha256', $this->getUIDstring());
    }

    /**
     * Set user identity
     *
     * @param array $identity Identity array
     * 
     * @return self
     */
    public function setIdentity($identity = [])
    {
        if (!\is_array($identity)) {
            $identity = [];
        }

        $i = [
            'id' => 0,
            'ip' => '',
            'name' => '',
            'email' => '',
            'avatar' => '',
            'provider' => '',
            'country' => '',
        ];

        // nonce required
        $nonce = $this->getIdentityNonce();

        // set keys
        $i['ip'] = $this->getIP();
        $i['country'] = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX';
        $i['nonce'] = $nonce;

        // check other keys
        if (\array_key_exists('id', $identity)) {
            $i['id'] = (int) $identity['id'];
        }
        if (\array_key_exists('name', $identity)) {
            $i['name'] = (string) $identity['name'];
        }
        if (\array_key_exists('email', $identity)) {
            $i['email'] = (string) $identity['email'];
        }
        if (\array_key_exists('avatar', $identity)) {
            $i['avatar'] = (string) $identity['avatar'];
        }
        if (\array_key_exists('provider', $identity)) {
            $i['provider'] = (string) $identity['provider'];
        }

        // shuffle keys
        $out = [];
        $keys = \array_keys($i);
        \shuffle($keys);
        foreach ($keys as $k) {
            $out[$k] = $i[$k];
        }

        // set new identity
        $this->identity = $out;
        $app = $this->getCfg('app') ?? 'app';

        // encrypted cookie
        if ($out['id']) {
            $this->setCookie($app, \json_encode($out));
        } else {
            $this->clearCookie($app);
        }
        return $this;
    }

    /**
     * Get user identity
     *
     * @return array Identity array
     */
    public function getIdentity()
    {
        if (CLI) {
            return [
                'id' => 1,
                'ip' => '127.0.0.1',
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'avatar' => '',
                'country' => 'XX',
                'provider' => 'cli',
            ];
        }

        $i = [
            'id' => 0,
            'ip' => '',
            'name' => '',
            'email' => '',
            'avatar' => '',
            'country' => '',
            'provider' => '',
        ];

        // check current identity
        $id = $this->identity['id'] ?? null;
        $email = $this->identity['email'] ?? null;
        $name = $this->identity['name'] ?? null;
        if ($id && $email && $name) {
            return $this->identity;
        }

        // nonce required
        $nonce = $this->getIdentityNonce();

        // COOKIE identity
        $app = $this->getCfg('app') ?? 'app';
        if (isset($_COOKIE[$app])) {
            $content = $this->getCookie($app);
            $q = \json_decode($content, true);
            if (!\is_array($q)) {
                error_log('Cookie identity is not an array.');
                $this->logout();
            }
            if (!\array_key_exists('email', $q)) {
                error_log('Cookie identity has no email.');
                $this->logout();
            }
            if (!\array_key_exists('id', $q)) {
                error_log('Cookie identity has no id.');
                $this->logout();
            }
            if (!\array_key_exists('nonce', $q)) {
                error_log('Cookie identity has no nonce.');
                $this->logout();
            }
            if ($q['nonce'] !== $nonce) {
                error_log('Cookie identity nonce is invalid.');
                $this->logout();
            }
            if ($q['nonce'] === $nonce) {
                $this->identity = $q;
            }
        }

        // empty identity
        return $this->identity;
    }

    /**
     * Get current user
     *
     * @return array current user data
     */
    public function getCurrentUser()
    {
        $u = \array_replace(
            [
                'id' => 0,
                'name' => '',
                'email' => '',
                'avatar' => '',
                'country' => '',
                'provider' => '',
            ],
            $this->getIdentity()
        );

        // get UID stuff
        $u['uid'] = $this->getUID();
        $u['uidstring'] = $this->getUIDstring();
        return $u;
    }

    /**
     * Cfg getter
     *
     * @param string $key Index to configuration data / void
     * 
     * @return mixed Configuration data by index / whole array
     */
    public function getCfg($key = null)
    {
        if (\is_null($key)) {
            return $this->getData('cfg');
        }
        if (\is_string($key)) {
            return $this->getData("cfg.{$key}");
        }
        throw new \Exception('FATAL ERROR: invalid get parameter');
    }

    /**
     * Match getter
     *
     * @return mixed Match data array
     */
    public function getMatch()
    {
        return $this->getData('match') ?? null;
    }

    /**
     * Presenter getter
     *
     * @return mixed Rresenter data array
     */
    public function getPresenter()
    {
        return $this->getData('presenter') ?? null;
    }

    /**
     * Router getter
     *
     * @return mixed Router data array
     */
    public function getRouter()
    {
        return $this->getData('router') ?? null;
    }

    /**
     * View getter
     *
     * @return mixed Router view
     */
    public function getView()
    {
        return $this->getData('view') ?? null;
    }

    /**
     * Set HTTP header for CSV content
     *
     * @return self
     */
    public function setHeaderCsv()
    {
        \header('Content-Type: text/csv; charset=UTF-8');
        return $this;
    }

    /**
     * Set HTTP header for binary content
     *
     * @return self
     */
    public function setHeaderFile()
    {
        \header('Content-Type: application/octet-stream');
        return $this;
    }

    /**
     * Set HTTP header for HTML content
     *
     * @return self
     */
    public function setHeaderHtml()
    {
        \header('Content-Type: text/html; charset=UTF-8');
        return $this;
    }

    /**
     * Set HTTP header for JSON content
     *
     * @return self
     */
    public function setHeaderJson()
    {
        \header('Content-Type: application/json; charset=UTF-8');
        return $this;
    }

    /**
     * Set HTTP header for JavaScript content
     *
     * @return self
     */
    public function setHeaderJavaScript()
    {
        \header('Content-Type: application/javascript; charset=UTF-8');
        return $this;
    }

    /**
     * Set HTTP header for PDF content
     *
     * @return self
     */
    public function setHeaderPdf()
    {
        \header('Content-Type: application/pdf');
        return $this;
    }

    /**
     * Set HTTP header for TEXT content
     *
     * @return self
     */
    public function setHeaderText()
    {
        \header('Content-Type: text/plain; charset=UTF-8');
        return $this;
    }

    /**
     * Set HTTP header for XML content
     *
     * @return self
     */
    public function setHeaderXML()
    {
        \header('Content-Type: application/xml; charset=utf-8');
        return $this;
    }

    /**
     * Get encrypted cookie
     *
     * @param string $name cookie name
     * 
     * @return mixed cookie value
     */
    public function getCookie($name)
    {
        if (CLI) {
            return $this->cookies[$name] ?? null;
        }
        if (empty($name)) {
            return null;
        }

        $key = $this->getCfg('secret_cookie_key') ?? 'secure.key';
        $key = trim($key, "/.\\");
        $keyfile = DATA . DS . $key;
        if (file_exists($keyfile) && is_readable($keyfile)) {
            $enc = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $this->addError('HALITE: getCookie - missing encryption key');
            return null;
        }
        $cookie = new Cookie($enc);
        return $cookie->fetch($name);
    }

    /**
     * Set encrypted cookie
     *
     * @param string $name Cookie name
     * @param string $data Cookie data
     * 
     * @return self
     */
    public function setCookie($name, $data)
    {
        if (CLI || empty($name) || !\is_string($data)) {
            return $this;
        }

        $key = $this->getCfg('secret_cookie_key') ?? 'secure.key';
        $key = trim($key, "/.\\");
        $keyfile = DATA . DS . $key;

        if (file_exists($keyfile) && is_readable($keyfile)) {
            $enc = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $enc = KeyFactory::generateEncryptionKey();
            if (\is_writable(DATA)) {
                KeyFactory::save($enc, $keyfile);
                \chmod($keyfile, self::COOKIE_KEY_FILEMODE);
                $this->addMessage('HALITE: Cookie encryption keyfile created.'); // phpcs:ignore
            } else {
                $this->addCritical('HALITE: Cannot write cookie encryption key!!!'); // phpcs:ignore
                ErrorPresenter::getInstance()->process(
                    ['code' => 500, 'message' => 'SYSTEM ERROR: unable to setup the encryption'] // phpcs:ignore
                );
            }
        }

        $cookie = new Cookie($enc);
        if (DOMAIN === 'localhost') {
            $httponly = true;
            $samesite = 'lax';
            $secure = false;
        } else {
            $httponly = true;
            $samesite = 'lax';
            $secure = true;
        }
        $cookie->store(
            $name,
            $data,
            \time() + self::COOKIE_TTL,
            '/',
            DOMAIN,
            $secure,
            $httponly,
            $samesite
        );
        $this->cookies[$name] = $data;
        return $this;
    }

    /**
     * Clear encrypted cookie
     *
     * @param string $name Cookie name
     * 
     * @return object Singleton instance
     */
    public function clearCookie($name)
    {
        if (empty($name)) {
            return $this;
        }

        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            \setcookie(
                $name,
                '',
                time() - 3600,
                '/',
                DOMAIN,
                !LOCALHOST,
                true
            );
        }
        return $this;
    }

    /**
     * Set URL location and exit
     *
     * @param string  $location URL address (optional)
     * @param integer $code     HTTP code (optional)
     * 
     * @return void
     */
    public function setLocation($location = null, $code = 303)
    {
        if (CLI) {
            exit;
        }
        $code = (int) $code;
        if (empty($location)) {
            $location = '/?nonce=' . $this->getNonce();
        }
        \header("Location: $location", true, ($code > 300) ? $code : 303);
        exit;
    }

    /**
     * Logout
     * 
     * @return void
     */
    public function logout()
    {
        if (CLI) {
            exit;
        }
        $nonce = $this->getNonce();
        $this->setLocation("/?logout&nonce=$nonce");
    }

    /**
     * Enforce current user rate limits
     *
     * @param integer $max hits per limiter cache time (optional)
     * 
     * @return self
     */
    public function checkRateLimit($max = self::LIMITER_MAXIMUM)
    {
        if (CLI) {
            return $this;
        }
        if (!\is_numeric($max)) {
            return $this;
        }
        $uuid = $this->getUID();
        $ban_rate = "user_ban_limit_{$uuid}";
        $rate_limit = "user_rate_limit_{$uuid}";
        $ban_secs = $this->getData['ban_secs'] ?? 3600;
        $limiter_secs = $this->getData['limiter_secs'] ?? 5;

        // bans limiting
        $ban_rate_count = (int) (Cache::read($ban_rate, 'ban') ?? 0);
        if ($ban_rate_count >= self::BAN_MAXIMUM) {
            if ($this->checkPermission('admin,manager,editor', true)) {
                // user is limited
                $ban_reset = \floor(self::BAN_MAXIMUM / 2);
                Cache::write($ban_rate, $ban_reset, 'ban');
                header('Retry-After: ' . $limiter_secs);
                $this->setLocation('/err/429');
            }
            // user is banned
            header('Retry-After: ' . $ban_secs);
            $this->setLocation('/err/429');
        }

        // rate limiting
        $rate_limit_count = (int) (Cache::read($rate_limit, 'limiter') ?? 0);
        Cache::write($rate_limit, ++$rate_limit_count, 'limiter');

        if ($rate_limit_count >= (int) $max) {
            // increment ban
            $ban_rate_count = (int) (Cache::read($ban_rate, 'ban') ?? 0);
            Cache::write($ban_rate, ++$ban_rate_count, 'ban');
            if ($ban_rate_count >= self::BAN_MAXIMUM) {
                // user is banned
                $path = $this->getData('request_path');
                if (!\is_string($path)) {
                    $path = '*** unknown ***';
                }
                $ua = null;
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $ua = trim($_SERVER['HTTP_USER_AGENT']);
                }
                if ($ua) {
                    $this->addMessage("LIMITER: User [{$ua}] is banned.\nPath: [{$path}]"); // phpcs:ignore
                } else {
                    $this->addMessage("LIMITER: User is banned.\nPath: [{$path}]"); // phpcs:ignore
                }
                header('Retry-After: ' . $ban_secs);
                $this->setLocation('/err/429');
            }
            // user is limited
            header('Retry-After: ' . $limiter_secs);
            $this->setLocation('/err/429');
        }
        return $this;
    }

    /**
     * Get current user rate limits
     *
     * @return integer current rate limit
     */
    public function getRateLimit()
    {
        if (CLI) {
            return null;
        }
        return Cache::read("user_rate_limit_{$this->getUID()}", 'limiter');
    }

    /**
     * Check if current user has access rights
     *
     * @param mixed $rolelist roles separated by comma (optional)
     * @param bool  $retbool  return the status as boolean? (optional)
     * 
     * @return self
     */
    public function checkPermission($rolelist = 'admin', $retbool = false)
    {
        if (CLI || empty($rolelist)) {
            return $this;
        }

        $roles = \explode(',', trim((string) $rolelist));
        if (\is_array($roles)) {
            $email = $this->getIdentity()['email'] ?? '';
            $groups = $this->getCfg('admin_groups') ?? [];
            foreach ($roles as $role) {
                $role = \strtolower(trim($role));
                if (strlen($role) && strlen($email)) {
                    // check if email is allowed
                    if (\in_array($email, $groups[$role] ?? [], true)) {
                        if ($retbool) {
                            return true;
                        }
                        return $this;
                    }
                    // check if any users is allowed
                    if (\in_array('*', $groups[$role] ?? [], true)) {
                        if ($retbool) {
                            return true;
                        }
                        return $this;
                    }
                }
            }
        }
        if ($retbool) {
            return false;
        }

        // not authorized
        $this->setLocation('/err/401');
    }

    /**
     * Get current user group
     *
     * @return string User group name
     */
    public function getUserGroup()
    {
        $id = $this->getIdentity()['id'] ?? null;
        $email = $this->getIdentity()['email'] ?? null;
        if (!$id) {
            return null;
        }
        $mygroup = null;
        $email = trim((string) $email);

        // search all groups for email or asterisk
        foreach ($this->getCfg('admin_groups') ?? [] as $group => $users) {
            if (in_array($email, $users, true)) {
                $mygroup = $group;
                break;
            }
            if (in_array('*', $users, true)) {
                $mygroup = $group;
                continue;
            }
        }
        return $mygroup;
    }

    /**
     * Force CSV checking
     *
     * @return self
     */
    public function setForceCsvCheck()
    {
        $this->force_csv_check = true;
        return $this;
    }

    /**
     * Post-load CSV data
     *
     * @param mixed $key string / array to be merged
     * 
     * @return self
     */
    public function postloadAppData($key)
    {
        if (!empty($key)) {
            if (\is_string($key)) {
                $this->csv_postload[] = (string) $key;
                return $this;
            }
            if (\is_array($key)) {
                $this->csv_postload = array_merge($this->csv_postload, $key);
                return $this;
            }
        }
        return $this;
    }

    /**
     * Get locale
     *
     * @param string $language language code
     * @param string $key      index column code (optional)
     * 
     * @return array locales
     */
    public function getLocale($language, $key = 'KEY')
    {
        if (!\is_array($this->getCfg('locales'))) {
            return null;
        }
        $cfg = $this->getCfg();
        $language = trim(\strtoupper((string) $language));
        $key = trim(\strtoupper((string) $key));
        $file = \strtolower("{$language}_locale");
        
        $locale = [];
        $locale = Cache::read($file, 'default');
        if ($locale === false || empty($locale)) {
            if (\array_key_exists('locales', $cfg)) {

                $locale = [];
                foreach ((array) $cfg['locales'] as $k => $v) {
                    $csv = false;
                    $subfile = \strtolower($k);
                    $csvfile = DATA . DS . "{$subfile}.csv";
                    $csvfilebak = DATA . DS . "{$subfile}.bak";

                    // 0. read injected prefabricated base CSV file
                    if (\str_ends_with($v, ".csv")) {
                        $csvfile = APP . DS . $v;
                        if (file_exists(($csvfile))) {
                            $csv = @file_get_contents($csvfile);
                        }
                    }

                    // 1. read from CSV file
                    if ($csv === false && file_exists(($csvfile))) {
                        $csv = @file_get_contents($csvfile);
                        if ($csv === false || strlen($csv) < self::CSV_MIN_SIZE) {
                            $csv = false;
                        }
                    }

                    // 2. read from CSV file backup
                    if ($csv === false && file_exists($csvfilebak)) {
                        $csv = @file_get_contents($csvfilebak);
                        if ($csv === false || strlen($csv) < self::CSV_MIN_SIZE) {
                            $csv = false;
                            continue;
                        } else {
                            \copy($csvfilebak, $csvfile);
                        }
                    }

                    // parse CSV string
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
                    } catch (\Exception $e) {
                        continue;
                    }
                    $locale = \array_replace(
                        $locale, \array_combine($keys, $values)
                    );
                }

                // EXTRA locale variable = git revisions
                $locale['$revisions'] = $this->getData('REVISIONS');

                // find all $ in combined locales array
                $dolar = ['$' => '$'];
                foreach ((array) $locale as $a => $b) {
                    if (\substr($a, 0, 1) === '$') {
                        $a = trim($a, '{}$' . "\x20\t\n\r\0\x0B");
                        if (!strlen($a)) {
                            continue;
                        }
                        $dolar['$' . $a] = $b;
                        $dolar['{$' . $a . '}'] = $b;
                    }
                }

                // replace $ and $$
                $locale = \str_replace(\array_keys($dolar), $dolar, $locale);
                $locale = \str_replace(\array_keys($dolar), $dolar, $locale);
            }
        }
        if ($locale === false || empty($locale)) {
            if ($this->force_csv_check) {
                $this->addCritical('Corrupted locales: [' . $language . ']');
                ErrorPresenter::getInstance()->process(
                    ['code' => 500, 'message' => 'SYSTEM ERROR: corrupted localization'] // phpcs:ignore
                );
            } else {
                // second try!
                $this->checkLocales(true);
                return $this->getLocale($language, $key);
            }
        }
        Cache::write($file, $locale, 'default');

        // locale override
        $override = $this->getData('locale_override');
        if (\is_array($override)) {
            foreach ($override as $k => $v) {
                $locale[$k] = $v;
            }
        }
        return $locale;
    }

    /**
     * Check and preload locales
     *
     * @param boolean $force force loading locales (optional)
     * 
     * @return self
     */
    public function checkLocales(bool $force = false)
    {
        $locales = $this->getCfg('locales') ?? null;
        if (\is_array($locales)) {
            foreach ($locales as $name => $csvkey) {
                $this->csvPreloader($name, $csvkey, (bool) $force);
            }
        }
        return $this;
    }

    /**
     * Purge Cloudflare cache
     *
     * @param array $cf Cloudflare authentication array
     * 
     * @return self
     */
    public function cloudflarePurgeCache($cf)
    {
        if (!\is_array($cf)) {
            return $this;
        }

        $email = $cf['email'] ?? null;
        $apikey = $cf['apikey'] ?? null;
        $zoneid = $cf['zoneid'] ?? null;

        try {
            if ($email && $apikey && $zoneid) {
                $key = new \Cloudflare\API\Auth\APIKey($email, $apikey);
                $adapter = new \Cloudflare\API\Adapter\Guzzle($key);
                $zones = new \Cloudflare\API\Endpoints\Zones($adapter);
                $myzones = [];
                if (\is_array($zoneid)) {
                    $myzones = $zoneid;
                }
                if (\is_string($zoneid)) {
                    $myzones = [$zoneid];
                }
                $c = 0;
                foreach ($zones->listZones()->result as $zone) {
                    foreach ($myzones as $myzone) {
                        if ($zone->id === $myzone) {
                            $zones->cachePurgeEverything($zone->id);
                            $c++;
                            $this->addMessage("CLOUDFLARE: #{$c} cache for zone [{$myzone}] purged"); // phpcs:ignore
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addError("CLOUDFLARE: we got exception.\nMessage: " . $e->getMessage()); // phpcs:ignore
        }
        return $this;
    }

    /**
     * Load CSV data into cache
     *
     * @param string  $name   CSV nickname
     * @param string  $csvkey Google CSV token (partial or full URL to CSV endpoint)
     * @param boolean $force  force the resource to refresh (optional)
     * 
     * @return self
     */
    public function csvPreloader($name, $csvkey, $force = false)
    {
        $name = trim((string) $name);
        $csvkey = trim((string) $csvkey);
        $force = (bool) $force;
        $file = \strtolower($name);
        if ($name && $csvkey) {
            if (Cache::read($file, 'csv') === false || $force === true) {
                $data = false;
                if (file_exists(DATA . DS . "{$file}.csv")) {
                    // CSV file exists
                    $modtime = \filemtime(DATA . DS . "{$file}.csv");
                    if ($modtime + self::CSV_UPDATE_IGNORE > time()) {
                        // CSV file is too fresh to update
                        return $this;
                    }
                } else {
                    // no CSV file to load
                    $force = true;
                }
                if (!$force) {
                    // bail out
                    return $this;
                }
                if ($force) {
                    if (CLI) {
                        $this->addMessage("downloading CSV: [{$name}]");
                    }

                    // full path
                    if (\strpos($csvkey, 'https') === 0) {
                        $remote = $csvkey;
                    } else {
                        // partial path
                        if (\strpos($csvkey, '?gid=') > 0) {
                            // partial path incl. parameters
                            $remote = self::GS_CSV_PREFIX . $csvkey;
                        } else {
                            // partial path without parameters
                            $remote = self::GS_CSV_PREFIX
                                . $csvkey . self::GS_CSV_POSTFIX;
                        }
                    }
                    try {
                        $data = @file_get_contents($remote);
                    } catch (\Exception $e) {
                        $data = '';
                        $this->addError("CSV: fetching URL [{$remote}] failed"); // phpcs:ignore
                    }
                }
                if (!$data) {
                    $this->addError("CSV: there is no data for [{$name}]");
                    return $this;
                }
                if ($data && !\is_string($data)) {
                    $this->addError("CSV: there is no data for [{$name}]");
                    return $this;
                }
                if ($data && \strpos($data, '!DOCTYPE html') > 0) {
                    $this->addError("CSV: fetching URL [{$remote}] data contains HTML"); // phpcs:ignore
                    return $this;
                }
                if (strlen($data) >= self::CSV_MIN_SIZE) {
                    Cache::write($file, $data, 'csv');
                    $f1 = DATA . DS . "{$file}.csv";
                    $f2 = DATA . DS . "{$file}.bak";

                    // remove old backup
                    if (file_exists($f2)) {
                        if (@\unlink($f2) === false) {
                            $this->addError("CSV: delete of file [{$file}.bak] failed"); // phpcs:ignore
                        }
                    }

                    // move CSV to backup
                    if (file_exists($f1)) {
                        if (@\rename($f1, $f2) === false) {
                            $this->addError("CSV: backup of file [{$file}.csv] failed"); // phpcs:ignore
                        }
                    }

                    // write new CSV
                    if (@file_put_contents($f1, $data, LOCK_EX) === false) {
                        $this->addError("CSV: save to file [{$file}.csv] failed"); // phpcs:ignore
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Pre-load application CSV data
     *
     * @param string  $key   key to the data array (optional)
     * @param boolean $force force load when trye (optional)
     * 
     * @return self
     */
    public function preloadAppData($key = 'app_data', $force = false)
    {
        if (empty($key) || !strlen($key)) {
            $key = 'app_data';
        }
        $key = \strtolower(trim((string) $key));
        $cfg = $this->getCfg();
        if (\array_key_exists($key, $cfg)) {
            foreach ((array) $cfg[$key] as $name => $csvkey) {
                $this->csvPreloader($name, $csvkey, (bool) $force);
            }
        }
        return $this;
    }

    /**
     * Read application CSV data
     *
     * @param string $name CSV nickname (foobar)
     * 
     * @return mixed CSV data
     */
    public function readAppData($name)
    {
        if (!\is_string($name)) {
            return null;
        }
        $name = trim($name);
        if (empty($name)) {
            return null;
        }
        $file = \strtolower($name);

        // part 1 = cache
        if ($csv = Cache::read($file, 'csv')) {
            return $csv;
        }

        // part 2 = CSV file
        $csv = null;
        if (file_exists(DATA . DS . "{$file}.csv")) {
            if (!$csv = @file_get_contents(DATA . DS . "{$file}.csv")) {
                $csv = null;
                $this->addError("AppData: reading file [{$file}.csv] failed");
            }
        }

        if (\is_string($csv) && \stripos($csv, '<!DOCTYPE html') === 0) {
            // we got HTML document = failure
            $csv = null;
            $this->addError("AppData: file [{$file}.csv] data contains HTML");
        }

        if (\is_string($csv) && strlen($csv) >= self::CSV_MIN_SIZE) {
            Cache::write($file, $csv, 'csv');
            return $csv;
        }

        // part 3 = CSV backup file
        $csv = null;
        if (file_exists(DATA . DS . "{$file}.bak")) {
            if (!$csv = @file_get_contents(DATA . DS . "{$file}.bak")) {
                $csv = null;
                $this->addError("AppData: reading backup file [{$file}.bak] failed");
            }
        }
        if (\is_string($csv) && \stripos($csv, '<!DOCTYPE html') === 0) {
            // we got HTML document = failure
            $csv = null;
            $this->addError("AppData: backup file [{$file}.bak] contains HTML");
        }
        if (\is_string($csv) && strlen($csv) >= self::CSV_MIN_SIZE) {
            // make a copy to the main CSV file
            \copy(DATA . DS . "{$file}.bak", DATA . DS . "{$file}.csv");
            Cache::write($file, $csv, 'csv');
        }
        return $csv;
    }

    /**
     * Write JSON data to output
     *
     * @param mixed $data     error code / data array
     * @param array $headers  array of extra data (optional)
     * @param mixed $switches JSON encoder switches
     * 
     * @return object instance
     */
    public function writeJsonData($data, $headers = [], $switches = null)
    {
        $code = 200;
        $time = \time();
        $out = [
            'timestamp' => $time,
            'timestamp_RFC2822' => date(\DATE_RFC2822, $time),
            'version' => (string) ($this->getCfg('version') ?? 'v1'),
            'engine' => ENGINE,
            'domain' => DOMAIN,
        ];
        switch (\json_last_error()) { // last decoding error
        case JSON_ERROR_NONE:
            $code = 200;
            $msg = 'OK';
            break;
        case JSON_ERROR_DEPTH:
            $code = 400;
            $msg = 'Maximum stack depth exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $code = 400;
            $msg = 'Underflow or the modes mismatch.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $code = 400;
            $msg = 'Unexpected control character found.';
            break;
        case JSON_ERROR_SYNTAX:
            $code = 500;
            $msg = 'Syntax error, malformed JSON.';
            break;
        case JSON_ERROR_UTF8:
            $code = 400;
            $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        default:
            $code = 500;
            $msg = 'Internal server error.';
            break;
        }
        if (\is_null($data)) {
            $code = 500;
            $msg = 'No DATA! Internal Server Error';
            \header('HTTP/1.1 500 Internal Server Error');
        }
        if (\is_string($data)) {
            $data = [$data];
        }
        if (is_int($data)) {
            $code = $data;
            $data = null;
            $h = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            $m = null;
            switch ($code) {
            case 304:
                $m = 'Not Modified';
                break;
            case 400:
                $m = 'Bad Request';
                break;
            case 401:
                $m = 'Unauthorized';
                break;
            case 402:
                $m = 'Payment Required';
                break;
            case 403:
                $m = 'Forbidden';
                break;
            case 404:
                $m = 'Not Found';
                break;
            case 405:
                $m = 'Method Not Allowed';
                break;
            case 406:
                $m = 'Not Acceptable';
                break;
            case 409:
                $m = 'Conflict';
                break;
            case 410:
                $m = 'Gone';
                break;
            case 412:
                $m = 'Precondition Failed';
                break;
            case 415:
                $m = 'Unsupported Media Type';
                break;
            case 416:
                $m = 'Requested Range Not Satisfiable';
                break;
            case 417:
                $m = 'Expectation Failed';
                break;
            case 500:
                $m = 'Internal Server Error';
                break;
            default:
                $msg = 'Unknown Error';
            }
            if ($m) {
                $msg = "$m.";
                \header("$h $code $m"); // set corresponding HTTP header
            }
        }
        $this->setHeaderJson();
        $out['message'] = $msg;
        $out['processing_time'] = \round(
            (\microtime(true) - TESSERACT_START) * 1000, 2
        ) . ' ms';

        // merge headers
        $out = \array_merge_recursive($out, $headers);

        // set data model
        $out['data'] = $data ?? null;

        // process extra switches
        if (\is_null($switches)) {
            return $this->setData('output', \json_encode($out, JSON_PRETTY_PRINT));
        }
        return $this->setData(
            'output', \json_encode($out, JSON_PRETTY_PRINT | $switches)
        );
    }

    /**
     * Data model expander
     *
     * @param array $data mModel by reference
     * 
     * @return self
     */
    public function dataExpander(&$data)
    {
        if (empty($data) || !\is_array($data)) {
            return $this;
        }

        // language
        $presenter = $this->getPresenter();
        $view = $this->getView();
        if ($presenter && $view) {
            $data['lang'] = $language = \strtolower(
                $presenter[$view]['language']
            ) ?? 'en';
            $data["lang{$language}"] = true;
        } else {
            $this->addCritical('SYSTEM ERROR: something is terribly wrong with locales!'); // phpcs:ignore
            ErrorPresenter::getInstance()->process(
                ['code' => 500, 'message' => 'SYSTEM ERROR: corrupted localization'] // phpcs:ignore
            );
        }

        // get locale if not already present
        $l = null;
        if (!\array_key_exists('l', $data)) {
            $l = $this->getLocale($language);
            if (\is_null($l)) {
                $l = $this->getLocale('en');
                if (\is_null($l)) {
                    $l = [];
                    $l['title'] = 'ERROR! NO ENGLISH LOCALE';
                }
            }
            $data['l'] = $l;
        }

        // process special keys: [cfg.*, usr.*, add.*, del.*]
        $reps = 0;
        $dot = new \Adbar\Dot($data);
        $dot->set('model.changes', []);
        foreach ($l ?? [] as $k => $v) {
            $kk = $k;

            // CFG: replace key if exists
            if (\str_starts_with($k, 'cfg.')) {
                $k = \substr($k, 4);
                if (!\strlen($k)) {
                    continue;
                }
                if (\str_starts_with($v, '[neon]')) {
                    try {
                        \substr($v, 0, self::NEON_DECODE_LIMIT);
                        $v = Neon::decode(\substr($v, 6));
                    } catch (\Throwable $e) {
                        bdump($e, $kk);
                        continue;
                    }
                }
                if ($dot->has($k)) {
                    $dot->set($k, $v);
                    $dot->merge('model.changes', $kk);
                    $reps++;
                }
                continue;
            }

            // USR: set key
            if (\str_starts_with($k, 'usr.')) {
                $k = \substr($k, 4);
                if (!\strlen($k)) {
                    continue;
                }
                if (\str_starts_with($v, '[neon]')) {
                    try {
                        \substr($v, 0, self::NEON_DECODE_LIMIT);
                        $v = Neon::decode(\substr($v, 6));
                    } catch (\Throwable $e) {
                        bdump($e, $kk);
                        continue;
                    }
                }
                $dot->delete($k)->add($k, $v);
                $dot->merge('model.changes', $kk);
                $reps++;
                continue;
            }

            // DEL: delete key
            if (\str_starts_with($k, 'del.')) {
                $k = \substr($k, 4);
                if (!\strlen($k)) {
                    continue;
                }
                $dot->delete($k);
                $dot->merge('model.changes', $kk);
                $reps++;
                continue;
            }

            //  ADD: add to array
            if (\str_starts_with($k, 'add.')) {
                $k = \substr($k, 4);
                if (!\strlen($k)) {
                    continue;
                }
                if (\str_starts_with($v, '[neon]')) {
                    try {
                        \substr($v, 0, self::NEON_DECODE_LIMIT);
                        $v = Neon::decode(\substr($v, 6));
                    } catch (\Throwable $e) {
                        bdump($e, $kk);
                        continue;
                    }
                }
                if ($dot->has($k) && \is_array($dot->get($k))) {
                    if (\is_array($v)) {
                        if (!\count($v)) {
                            // skip empty arrays
                            continue;
                        }
                        $dot->set($k, \array_merge_recursive($dot->get($k), $v));
                        $dot->merge('model.changes', $kk);
                    } elseif (\is_string($v)) {
                        if (!\strlen($v)) {
                            // skip empty strings
                            continue;
                        }
                        $a = $dot->get($k);
                        $a[] = $v;
                        $dot->set($k, $a);
                        $dot->merge('model.changes', $kk);
                    } elseif (\is_numeric($v)) {
                        $a = $dot->get($k);
                        $a[] = $v;
                        $dot->set($k, $a);
                        $dot->merge('model.changes', $kk);
                    }
                    $reps++;
                }
            }
        }
        // update model
        if ($reps) {
            $this->data = $data = $dot->all();
            bdump($reps, 'MODEL updates');
        }

        // USERS AND GROUPS
        $data["is_admin"] = false;
        $data['user'] = $user = $this->getCurrentUser();
        $data['group'] = $data['admin'] = $group = $this->getUserGroup();
        if ($group) {
            $data["admin_group_{$group}"] = true;
            $data["is_admin"] = true;
        }

        // MASKED ADMIN GROUPS
        $data['admin_groups_masked'] = $data['admin_groups'] ?? [];
        \array_walk_recursive(
            $data['admin_groups_masked'], function (&$e) {
                if (\is_string($e) && \strpos($e, '@') > 0) {
                    $p = \explode('@', $e);
                    $l = $p[0] ?: '';
                    $d = $p[1] ?: '';
                    if (\strlen($l) > 3) {
                        $l = \substr($l, 0, 4) . '*';
                    }
                    if (\strlen($d) > 4) {
                        $d = \substr($d, 0, 5) . '*';
                    }
                    $e = "{$l}@{$d}";
                }
            }
        );

        // compute DATA HASH
        if ($l) {
            $data['DATA_VERSION'] = \hash('sha256', (string) \json_encode($l));
        }

        // extract REQUEST PATH SLUG
        if (($pos = \strpos($data['request_path'], $language)) !== false) {
            $data['request_path_slug'] = \substr_replace(
                $data['request_path'], '', $pos, \strlen($language)
            );
        } else {
            $data['request_path_slug'] = $data['request_path'] ?? '';
        }
        return $this;
    }

    /**
     * Nonce string generator
     *
     * @return string 16 chars nonce
     */
    public function getNonce()
    {
        try {
            $randomBytes = random_bytes(32);
            $time = (string) time();
            $hash = hash('sha256', $randomBytes . $time);
            return \substr($hash, 0, 16);
        } catch (\Exception $e) {
            $this->addError("Error generating the cryptographically secure nonce: " . $e->getMessage()); // phpcs:ignore
            if (function_exists('openssl_random_pseudo_bytes')) {
                $this->addMessage("Using openssl_random_pseudo_bytes to generate nonce."); // phpcs:ignore
                $randomBytes = openssl_random_pseudo_bytes(32);
                $time = (string) time();
                $hash = hash('sha256', $randomBytes . $time);
                return \substr($hash, 0, 16);
            }
            $this->addCritical("Error generating any system nonce: " . $e->getMessage()); // phpcs:ignore
            ErrorPresenter::getInstance()->process(
                ['code' => 500, 'message' => 'SYSTEM ERROR: unable to setup the encryption'] // phpcs:ignore
            );
        }
    }

    /**
     * Generate or retrieve an identity nonce
     *
     * @return string identity nonce
     */
    public function getIdentityNonce()
    {
        $file = DATA . DS . self::IDENTITY_NONCE_FILE;
        if (is_readable($file)) {
            $nonce = @file_get_contents($file);
            if ($nonce !== false) {
                return $nonce;
            }
        }
        if (!file_exists($file)) {
            try {
                $randomBytes = \random_bytes(32);
                $time = (string) time();
                $nonce = hash('sha256', $randomBytes . $time);
            } catch (\Exception $e) {
                error_log("Error generating cryptographically secure nonce: " . $e->getMessage()); // phpcs:ignore
                if (function_exists('openssl_random_pseudo_bytes')) {
                    $randomBytes = openssl_random_pseudo_bytes(32);
                    $time = (string) time();
                    $nonce = hash('sha256', $randomBytes . $time);
                    error_log("Using less secure openssl_random_pseudo_bytes to generate nonce."); // phpcs:ignore
                } else {
                    error_log("Error generating identity nonce through openssl_random_pseudo_bytes: " . $e->getMessage()); // phpcs:ignore
                    die("Error generating identity nonce through openssl_random_pseudo_bytes: " . $e->getMessage()); // phpcs:ignore
                }
            }
            if (@file_put_contents($file, $nonce, LOCK_EX) === false) {
                error_log('Failed to write identity nonce to file.');
                die('Failed to write identity nonce to file.');
            }
            if (!chmod($file, 0644)) {
                error_log('Failed to set permissions on identity nonce file.'); // phpcs:ignore
                die('Failed to set permissions on identity nonce file.');
            }
            error_log('Identity nonce written to file.');
        }
        return $nonce;
    }
}
