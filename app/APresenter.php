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
use ParagonIE\Halite\Cookie;
use ParagonIE\Halite\KeyFactory;

/**
 * APresenter interface
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
interface IPresenter
{
    /**
     * Add critical message
     *
     * @param string $message Critical error string
     * 
     * @return self
     */
    public function addCritical($message);

    /**
     * Add error message
     *
     * @param string $message Error string
     * 
     * @return self
     */
    public function addError($message);

    /**
     * Add info message
     *
     * @param string $message Message string
     * 
     * @return self
     */
    public function addMessage($message);

    /**
     * Add audit message
     *
     * @param string $message Message string
     * 
     * @return self
     */
    public function addAuditMessage($message);

    /**
     * Criticals getter
     *
     * @return array Array of critical messages
     */
    public function getCriticals();

    /**
     * Errors getter
     *
     * @return array Array of errors
     */
    public function getErrors();

    /**
     * Messages getter
     *
     * @return array Array of messages
     */
    public function getMessages();

    /**
     * Cfg getter
     *
     * @param string $key Index to configuration data / void
     * 
     * @return mixed Configuration data by index / whole array
     */
    public function getCfg($key);

    /**
     * Get encrypted cookie
     *
     * @param string $name Cookie name
     * 
     * @return mixed Cookie value
     */
    public function getCookie($name);

    /**
     * Get current user
     *
     * @return array current user data
     */
    public function getCurrentUser();
    /**
     * Data getter
     *
     * @param string $key array key, dot notation (optional)
     * 
     * @return mixed value / whole array
     */
    public function getData($key);

    /**
     * Get IP address
     *
     * @return string IP address
     */
    public function getIP();

    /**
     * Get user identity
     *
     * @return array Identity array
     */
    public function getIdentity();

    /**
     * Get locales from GS Sheets
     *
     * @param string $language language code
     * @param string $key      index column code (optional)
     * 
     * @return array locales
     */
    public function getLocale($language, $key = 'KEY');
    
    /**
     * Get current user rate limits
     *
     * @return integer current rate limit
     */
    public function getRateLimit();

    /**
     * Get universal ID hash
     *
     * @return string SHA-256 hash
     */
    public function getUID();

    /**
     * Get universal ID string
     *
     * @return string Universal ID string
     */
    public function getUIDstring();

    /**
     * Get current user group
     *
     * @return string User group name
     */
    public function getUserGroup();

    /**
     * Match getter (alias)
     *
     * @return mixed Match data array
     */
    public function getMatch();

    /**
     * Presenter getter (alias)
     *
     * @return mixed Rresenter data array
     */
    public function getPresenter();

    /**
     * Router getter (alias)
     *
     * @return mixed Router data array
     */
    public function getRouter();

    /**
     * View getter (alias)
     *
     * @return mixed Router view
     */
    public function getView();

    /**
     * Check and preload locales
     *
     * @param boolean $force force loading locales (optional)
     * 
     * @return self
     */
    public function checkLocales(bool $force);

    /**
     * Check if current user has access rights
     *
     * @param mixed $rolelist roles (optional)
     * 
     * @return self
     */
    public function checkPermission($rolelist = 'admin');

    /**
     * Check and enforce current user rate limits
     *
     * @param integer $max Hits per second (optional)
     * 
     * @return self
     */
    public function checkRateLimit($max = self::LIMITER_MAXIMUM);

    /**
     * Set encrypted cookie
     *
     * @param string $name Cookie name
     * @param string $data Cookie data
     * 
     * @return self
     */
    public function setCookie($name, $data);

    /**
     * Data setter
     *
     * @param mixed $data  array / key
     * @param mixed $value value
     * 
     * @return self
     */
    public function setData($data, $value);

    /**
     * Force CSV checking
     *
     * @return self
     */
    public function setForceCsvCheck();

    /**
     * Set HTTP header for CSV content
     *
     * @return self
     */
    public function setHeaderCsv();

    /**
     * Set HTTP header for binary content
     *
     * @return self
     */
    public function setHeaderFile();

    /**
     * Set HTTP header for HTML content
     *
     * @return self
     */
    public function setHeaderHtml();

    /**
     * Set HTTP header for JavaScript content
     *
     * @return self
     */
    public function setHeaderJavaScript();

    /**
     * Set HTTP header for JSON content
     *
     * @return self
     */
    public function setHeaderJson();

    /**
     * Set HTTP header for PDF content
     *
     * @return self
     */
    public function setHeaderPdf();

    /**
     * Set HTTP header for TEXT content
     *
     * @return self
     */
    public function setHeaderText();

    /**
     * Set HTTP header for XML content
     *
     * @return self
     */
    public function setHeaderXML();

    /**
     * Set user identity
     *
     * @param array $identity Identity array
     * 
     * @return self
     */
    public function setIdentity($identity);

    /**
     * Set URL location and exit
     *
     * @param string  $location URL address (optional)
     * @param integer $code     HTTP code (optional)
     * 
     * @return void
     */
    public function setLocation($location = null, $code = 303);

    /**
     * Clear encrypted cookie
     *
     * @param string $name Cookie name
     * 
     * @return object Singleton instance
     */
    public function clearCookie($name);

    /**
     * Purge Cloudflare cache
     *
     * @param array $cf Cloudflare authentication array
     * 
     * @return self
     */
    public function cloudflarePurgeCache($cf);

    /**
     * Data model expander
     *
     * @param array $data model by reference
     * 
     * @return self
     */
    public function dataExpander(&$data);

    /**
     * Logout
     * 
     * @return void
     */
    public function logout();

    /**
     * Post-load CSV data
     *
     * @param mixed $key string / array to be merged
     * 
     * @return self
     */
    public function postloadAppData($key);

    /**
     * Pre-load application CSV data
     *
     * @param string  $key   configuration array (optional)
     * @param boolean $force load? (optional)
     * 
     * @return self
     */
    public function preloadAppData($key, $force);

    /**
     * Read application CSV data
     *
     * @param string $name CSV nickname (foobar)
     * 
     * @return mixed CSV data
     */
    public function readAppData($name);

    /**
     * Render HTML content from given template
     *
     * @param string $template Template name
     * 
     * @return string HTML output
     */
    public function renderHTML($template);

    /**
     * Write JSON data to output
     *
     * @param mixed $data     error code / data array
     * @param array $headers  array of extra data (optional)
     * @param mixed $switches JSON encoder switches
     * 
     * @return object instance
     */
    public function writeJsonData($data, $headers = [], $switches = null);

    /**
     * Abstract Processor
     *
     * @param mixed $param optional parameter
     * 
     * @abstract
     * 
     * @return object instance
     */
    public function process($param = null);

    /**
     * Get singleton object
     *
     * @static
     * @final
     * 
     * @return self
     */
    public static function getInstance();

    /**
     * Get instance for testing
     *
     * @static
     * @final
     * 
     * @return object Class instance
     */
    public static function getTestInstance();
}

/**
 * Abstract Presenter class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
abstract class APresenter implements IPresenter
{
    /* @var integer octal file mode for logs */
    const LOG_FILEMODE = 0664;

    /* @var integer octal file mode for CSV */
    const CSV_FILEMODE = 0664;

    /* @var integer CSV min. file size - something meaningful :) */
    const CSV_MIN_SIZE = 42;

    /* @var integer octal file mode for cookie secret */
    const COOKIE_KEY_FILEMODE = 0600;

    /* @var integer cookie TTL in seconds */
    const COOKIE_TTL = 86400 * 31;

    /* @var string Google CSV URL prefix */
    const GS_CSV_PREFIX = 'https://docs.google.com/spreadsheets/d/e/';

    /* @var string Google CSV URL postfix */
    const GS_CSV_POSTFIX = '/pub?gid=0&single=true&output=csv';

    /* @var string Google Sheet URL prefix */
    const GS_SHEET_PREFIX = 'https://docs.google.com/spreadsheets/d/';

    /* @var string Google Sheet URL postfix */
    const GS_SHEET_POSTFIX = '/edit#gid=0';

    /* @var integer access rate limiter maximum hits */
    const LIMITER_MAXIMUM = 100;

    /* @var string identity nonce filename */
    const IDENTITY_NONCE = 'identity_nonce.key';

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
        // clear outbut buffering
        if (\ob_get_level()) {
            @\ob_end_flush();
        }
        // finish request
        if (\function_exists('fastcgi_finish_request')) {
            \fastcgi_finish_request();
        }
        // preload CSV definitions
        foreach ($this->csv_postload as $key) {
            $this->preloadAppData((string) $key, true);
        }
        // load actual CSV data
        $this->checkLocales((bool) $this->force_csv_check);
        list($usec, $sec) = \explode(' ', \microtime());
        defined('TESSERACT_STOP') || define(
            'TESSERACT_STOP', (
            (float) $usec + (float) $sec)
        );
        $add = '; processing: '
            . \round(((float) TESSERACT_STOP - (float) TESSERACT_START) * 1000, 2)
            . ' ms' . '; request_uri: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A');
        exit(0);
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
            'template_class_prefix' => PROJECT . '_',
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
                        '<a rel="noopener,nofollow" '
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

        // global constants
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
                'CONST.LOGS' => LOGS,
                'CONST.PARTIALS' => PARTIALS,
                'CONST.PROJECT' => PROJECT,
                'CONST.REDIS_CACHE' => REDIS_CACHE,
                'CONST.ROOT' => ROOT,
                'CONST.SERVER' => SERVER,
                'CONST.TEMP' => TEMP,
                'CONST.TEMPLATES' => TEMPLATES,
                'CONST.UPLOAD' => UPLOAD,
                'CONST.WWW' => WWW,

                'CONST.LIMITER_MAXIMUM' => self::LIMITER_MAXIMUM,

                'CONST.MAX_FILE_UPLOADS' => ini_get('max_file_uploads'),
                'CONST.POST_MAX_SIZE' => ini_get('post_max_size'),
                'CONST.UPLOAD_MAX_FILESIZE' => ini_get('upload_max_filesize'),
            ]
        );

        // class constants
        $dot->set(
            [
                'CONST.COOKIE_KEY_FILEMODE' => self::COOKIE_KEY_FILEMODE,
                'CONST.COOKIE_TTL' => self::COOKIE_TTL,
                'CONST.CSV_FILEMODE' => self::CSV_FILEMODE,
                'CONST.CSV_MIN_SIZE' => self::CSV_MIN_SIZE,
                'CONST.GS_CSV_POSTFIX' => self::GS_CSV_POSTFIX,
                'CONST.GS_CSV_PREFIX' => self::GS_CSV_PREFIX,
                'CONST.GS_SHEET_POSTFIX' => self::GS_SHEET_POSTFIX,
                'CONST.GS_SHEET_PREFIX' => self::GS_SHEET_PREFIX,
                'CONST.LIMITER_MAXIMUM' => self::LIMITER_MAXIMUM,
                'CONST.LOG_FILEMODE' => self::LOG_FILEMODE,
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
             // $data = new model, replace it
            $this->data = (array) $data;
        } else {
            // $data = key index
            $key = $data;
            if (\is_string($key) && !empty($key)) {
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
     * Add audit message
     *
     * @param string $message Message string
     * 
     * @return self
     */
    public function addAuditMessage($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $file = DATA . DS . 'AuditLog.txt';
            $date = \date('c');
            $message = \trim($message);
            $i = $this->getIdentity();
            @\file_put_contents(
                $file, "$date;$message;{$i['ip']};{$i['name']};{$i['email']}\n",
                FILE_APPEND | LOCK_EX
            );
        }

        if (CLI) {
            return $this;
        }

        return $this;
    }

    /**
     * Add info message
     *
     * @param string $message Message string
     * 
     * @return self
     */
    public function addMessage($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $this->messages[] = (string) $message;
        }
        return $this;
    }

    /**
     * Add error message
     *
     * @param string $message Error string
     * 
     * @return self
     */
    public function addError($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $this->errors[] = (string) $message;
            $this->addAuditMessage($message);
        }
        return $this;
    }

    /**
     * Add critical message
     *
     * @param string $message Critical error string
     * 
     * @return self
     */
    public function addCritical($message = null)
    {
        if (\is_string($message) && !empty($message)) {
            $this->criticals[] = (string) $message;
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
        return $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    /**
     * Get universal ID string
     *
     * @return string Universal ID string
     */
    public function getUIDstring()
    {
        return preg_replace(
            '/__/', '_', strtr(
                implode(
                    '_',
                    [
                    CLI ? 'CLI' : '',
                    CLI ? '' : $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'N/A',
                    CLI ? '' : $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'N/A',
                    CLI ? '' : $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
                    $this->getIP(),
                    ]
                ),
                ' ', '_'
            )
        );
    }

    /**
     * Get universal ID hash
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
            'avatar' => '',
            'country' => '',
            'email' => '',
            'id' => 0,
            'ip' => '',
            'name' => '',
        ];
        $file = DATA . DS . self::IDENTITY_NONCE; // nonce file
        if (!\file_exists($file)) {
            try {
                $nonce = \hash('sha256', \random_bytes(1024) . \time());
                if (\file_put_contents($file, $nonce, LOCK_EX) === false) {
                    $this->addError('ERROR 500: write nonce file');
                    $this->setLocation('/err/500');
                }
                @\chmod($file, 0660);
                $this->addMessage('ADMIN: nonce file created');
            } catch (\Exception $e) {
                $this->addError('ERROR 500: create nonce file: ' . $e->getMessage());
                $this->setLocation('/err/500');
            }
        }
        if (!$nonce = @\file_get_contents($file)) {
            $this->addError('ERROR 500: read nonce file');
            $this->setLocation('/err/500');
        }
        $i['nonce'] = \substr(\trim($nonce), 0, 16); // trim nonce to 16 chars
        // check all keys
        if (\array_key_exists('avatar', $identity)) {
            $i['avatar'] = (string) $identity['avatar'];
        }
        if (\array_key_exists('email', $identity)) {
            $i['email'] = (string) $identity['email'];
        }
        if (\array_key_exists('id', $identity)) {
            $i['id'] = (int) $identity['id'];
        }
        if (\array_key_exists('name', $identity)) {
            $i['name'] = (string) $identity['name'];
        }
        // set other values
        $i['country'] = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'XX';
        $i['ip'] = $this->getIP();
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
        if ($out['id']) {
            // encrypted cookie
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
                'country' => 'XX',
                'email' => 'john.doe@example.com',
                'id' => 1,
                'ip' => '127.0.0.1',
                'name' => 'John Doe',
            ];
        }

        // check current identity
        $id = $this->identity['id'] ?? null;
        $email = $this->identity['email'] ?? null;
        $name = $this->identity['name'] ?? null;
        if ($id && $email && $name) {
            return $this->identity;
        }
        $file = DATA . DS . self::IDENTITY_NONCE;
        if (!\file_exists($file)) {
            // set empty identity
            $this->setIdentity();
            return $this->identity;
        }
        if (!$nonce = @\file_get_contents($file)) {
            $this->addError('ERROR 500: cannot read nonce file');
            $this->setLocation('/err/500');
        }
        $nonce = \substr(\trim($nonce), 0, 16);
        $i = [
            'avatar' => '',
            'country' => '',
            'email' => '',
            'id' => 0,
            'ip' => '',
            'name' => '',
        ];
        do {
            if (isset($_GET['identity'])) {
                $tls = '';
                if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                    $tls = 's';
                }
                $this->setCookie($this->getCfg('app') ?? 'app', $_GET['identity']);
                $this->setLocation(
                    "http{$tls}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"
                );
            }
            if (isset($_COOKIE[$this->getCfg('app') ?? 'app'])) {
                // COOKIE identity
                $x = 0;
                $q = \json_decode(
                    $this->getCookie($this->getCfg('app') ?? 'app')?? '', true
                );
                if (!\is_array($q)) {
                    $x++;
                } else {
                    if (!\array_key_exists('email', $q)) {
                        $x++;
                    }
                    if (!\array_key_exists('id', $q)) {
                        $x++;
                    }
                    if (!\array_key_exists('nonce', $q)) {
                        $x++;
                    }
                }
                if ($x) {
                    $this->logout(); // something is terribly wrong!!!
                }
                if ($q['nonce'] == $nonce) { // compare nonces
                    $this->identity = $q; // set identity
                    break;
                }
            }
            $this->setIdentity($i); // set empty identity
            break;
        } while (true);
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
                'avatar' => '',
                'country' => '',
                'email' => '',
                'id' => 0,
                'name' => '',
            ],
            $this->getIdentity()
        );
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
     * Match getter (alias)
     *
     * @return mixed Match data array
     */
    public function getMatch()
    {
        return $this->getData('match') ?? null;
    }

    /**
     * Presenter getter (alias)
     *
     * @return mixed Rresenter data array
     */
    public function getPresenter()
    {
        return $this->getData('presenter') ?? null;
    }

    /**
     * Router getter (alias)
     *
     * @return mixed Router data array
     */
    public function getRouter()
    {
        return $this->getData('router') ?? null;
    }

    /**
     * View getter (alias)
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
        if (empty($name)) {
            return null;
        }

        if (CLI) {
            return $this->cookies[$name] ?? null;
        }

        $key = $this->getCfg('secret_cookie_key') ?? 'secure.key';
        $key = \trim($key, "/.\\");
        $keyfile = DATA . DS . $key;
        if (\file_exists($keyfile) && is_readable($keyfile)) {
            $enc = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $this->addError('HALITE: Missing encryption key!');
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
        if (empty($name)) {
            return $this;
        }
        $key = $this->getCfg('secret_cookie_key') ?? 'secure.key';
        $key = \trim($key, "/.\\");
        $keyfile = DATA . DS . $key;
        if (\file_exists($keyfile) && \is_readable($keyfile)) {
            $enc = KeyFactory::loadEncryptionKey($keyfile);
        } else {
            $enc = KeyFactory::generateEncryptionKey();
            if (is_writable(DATA)) {
                KeyFactory::save($enc, $keyfile);
                @\chmod($keyfile, self::COOKIE_KEY_FILEMODE);
                $this->addMessage('HALITE: New keyfile created');
            } else {
                $this->addError('HALITE: Cannot write encryption key!');
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
        if (!CLI) {
            $cookie->store(
                $name,
                (string) $data,
                time() + self::COOKIE_TTL,
                '/',
                DOMAIN,
                $secure,
                $httponly,
                $samesite
            );
        }
        $this->cookies[$name] = (string) $data;
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
        if (($this->cookies[$name] ?? null) || ($_COOKIE[$name] ?? null)) {
            unset($_COOKIE[$name]);
            unset($this->cookies[$name]);
            \setcookie($name, '', time() - 3600, '/');
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
     * Check and enforce current user rate limits
     *
     * @param integer $max Hits per second (optional)
     * 
     * @return self
     */
    public function checkRateLimit($max = self::LIMITER_MAXIMUM)
    {
        if (CLI) {
            return $this;
        }
        $f = "user_rate_limit_{$this->getUID()}";
        $rate = (int) (Cache::read($f, 'limiter') ?? 0);
        Cache::write($f, ++$rate, 'limiter');
        if ($rate > (int) $max) {
            \header('HTTP/1.1 429 Too Many Requests');
            exit;
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
     * 
     * @return self
     */
    public function checkPermission($rolelist = 'admin')
    {
        if (CLI) {
            return $this;
        }
        if (empty($rolelist)) {
            return $this;
        }

        $roles = \explode(',', \trim((string) $rolelist));
        if (\is_array($roles)) {
            foreach ($roles as $role) {
                $role = \strtolower(\trim($role));
                $email = $this->getIdentity()['email'] ?? '';
                $groups = $this->getCfg('admin_groups') ?? [];
                if (\strlen($role) && \strlen($email)) {
                    // check if email is allowed
                    if (\in_array($email, $groups[$role] ?? [], true)) {
                        return $this;
                    }
                    // check if any users is allowed
                    if (\in_array('*', $groups[$role] ?? [], true)) {
                        return $this;
                    }
                }
            }
        }
        $this->setLocation('/err/401'); // not authorized
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
        $email = \trim((string) $email);

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
        $locale = [];
        $language = \trim(\strtoupper((string) $language));
        $key = \trim(\strtoupper((string) $key));
        $cfg = $this->getCfg();
        $file = \strtolower("{$language}_locale");
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
                        if (\file_exists(($csvfile))) {
                            $csv = @\file_get_contents($csvfile);
                        }
                    }

                    // 1. read from CSV file
                    if ($csv === false && \file_exists(($csvfile))) {
                        $csv = @\file_get_contents($csvfile);
                        if ($csv === false || \strlen($csv) < self::CSV_MIN_SIZE) {
                            $csv = false;
                        }
                    }

                    // 2. read from CSV file backup
                    if ($csv === false && \file_exists($csvfilebak)) {
                        $csv = @\file_get_contents($csvfilebak);
                        if ($csv === false || \strlen($csv) < self::CSV_MIN_SIZE) {
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
                        $this->addCritical("LOCALE: $language [$k] ERR");
                        //$this->addAuditMessage("LOCALE: $language [$k] ERR");
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
                        $a = \trim($a, '{}$' . "\x20\t\n\r\0\x0B");
                        if (!\strlen($a)) {
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
                \header('HTTP/1.1 500 FATAL ERROR');
                $this->addCritical('ERR: LOCALES CORRUPTED!');
                echo '<body><h1>ERR 500</h1><h2>LOCALES CORRUPTED!</h2></body>';
                exit;
            } else {
                $this->checkLocales(true); // second try!
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
                if (\is_array($zoneid)) {
                    $myzones = $zoneid;
                }
                if (\is_string($zoneid)) {
                    $myzones = [$zoneid];
                }
                foreach ($zones->listZones()->result as $zone) {
                    foreach ($myzones as $myzone) {
                        if ($zone->id == $myzone) {
                            $zones->cachePurgeEverything($zone->id);
                            $this->addMessage("Cloudflare: zone {$myzone} purged");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addError('ERROR: Cloudflare exception!');
        }
        return $this;
    }

    /**
     * Load CSV data into cache
     *
     * @param string  $name   CSV nickname (foobar)
     * @param string  $csvkey Google CSV token (partial or full URI to CSV endpoint)
     * @param boolean $force  force the resource refresh? (optional)
     * 
     * @return self
     */
    public function csvPreloader($name, $csvkey, $force = false)
    {
        //dump("csvPreloader", $name, $csvkey, $force);
        $name = \trim((string) $name);
        $csvkey = \trim((string) $csvkey);
        $force = (bool) $force;
        $file = \strtolower($name);
        if ($name && $csvkey) {
            if (Cache::read($file, 'csv') === false || $force === true) {
                $data = false;
                if (!\file_exists(DATA . DS . "{$file}.csv")) {
                    $force = true;
                }
                if ($force) {
                    if (CLI) {
                        echo "loading CSV [{$name}]\n";
                    }
                    // contains full path
                    if (\strpos($csvkey, 'https') === 0) {
                        $remote = $csvkey;
                    } else {
                        // contains path incl. parameters
                        if (\strpos($csvkey, '?gid=') > 0) {
                            $remote = self::GS_CSV_PREFIX . $csvkey;
                        } else {
                            $remote = self::GS_CSV_PREFIX
                                . $csvkey . self::GS_CSV_POSTFIX;
                        }
                    }
                    $this->addMessage("FILE: fetching {$remote}");
                    try {
                        $data = @\file_get_contents($remote);
                    } catch (\Exception $e) {
                        $this->addError("ERROR: fetching {$remote}");
                        $data = '';
                    }
                }
                if (\strpos($data, '!DOCTYPE html') > 0) {
                    // this is HTML document = failure!
                    $this->addError("ERROR: fetching {$remote} - HTML document");
                    return $this;
                }
                if (\strlen($data) >= self::CSV_MIN_SIZE) {
                    Cache::write($file, $data, 'csv');
                    $f1 = DATA . DS . "{$file}.csv";
                    $f2 = DATA . DS . "{$file}.bak";
                    // remove old backup
                    if (\file_exists($f2)) {
                        if (@\unlink($f2) === false) {
                            $this->addError("FILE: remove {$file}.bak failed!");
                        }
                    }
                    // move CSV to backup
                    if (\file_exists($f1)) {
                        if (@\rename($f1, $f2) === false) {
                            $this->addError("FILE: backup {$file}.csv failed!");
                        }
                    }
                    // write new CSV
                    if (\file_put_contents($f1, $data, LOCK_EX) === false) {
                        $this->addError("FILE: save {$file}.csv failed!");
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Pre-load application CSV data
     *
     * @param string  $key   configuration array (optional)
     * @param boolean $force load? (optional)
     * 
     * @return self
     */
    public function preloadAppData($key = 'app_data', $force = false)
    {
        if (empty($key) || !strlen($key)) {
            $key = 'app_data';
        }
        $key = \strtolower(\trim((string) $key));
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
        $name = \trim((string) $name);
        if (empty($name) || !strlen($name)) {
            return '';
        }
        $file = \strtolower($name);
        if (empty($file)) {
            return null;
        }

        if (!$csv = Cache::read($file, 'csv')) {
            $csv = false;
            if (\file_exists(DATA . DS . "{$file}.csv")) {
                $csv = \file_get_contents(DATA . DS . "{$file}.csv");
            }
            if (\strpos($csv, '!DOCTYPE html') > 0) {
                $csv = false; // we got HTML document, try backup
            }
            if ($csv !== false || \strlen($csv) >= self::CSV_MIN_SIZE) {
                Cache::write($file, $csv, 'csv');
                return $csv;
            }
            $csv = false;
            if (\file_exists(DATA . DS . "{$file}.bak")) {
                $csv = \file_get_contents(DATA . DS . "{$file}.bak");
            }
            if (\strpos($csv, '!DOCTYPE html') > 0) {
                return null; // we got HTML document = failure
            }
            if ($csv !== false || \strlen($csv) >= self::CSV_MIN_SIZE) {
                \copy(DATA . DS . "{$file}.bak", DATA . DS . "{$file}.csv");
                Cache::write($file, $csv, 'csv');
                return $csv;
            }
            $csv = null; // failure
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
        if (empty($data)) {
            return $this;
        }

        // user and group
        $data["is_admin"] = false;
        $data['user'] = $user = $this->getCurrentUser();
        $data['group'] = $data['admin'] = $group = $this->getUserGroup();
        if ($group) {
            $data["admin_group_{$group}"] = true;
            $data["is_admin"] = true;
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
            // something is terribly wrong
            ErrorPresenter::getInstance()->process(500);
            return $this;
        }

        // get locale if not already present
        $l = null;
        if (!\array_key_exists('l', $data)) {
            $l = $this->getLocale($language);
            if (\is_null($l)) {
                $l = $this->getLocale('en');
                if (\is_null($l)) {
                    $l = [];
                    $l['title'] = 'ERR: MISSING ENGLISH LOCALE!';
                }
            }
            $data['l'] = $l;
        }

        // compute data hash
        if ($l) {
            $data['DATA_VERSION'] = \hash('sha256', (string) \json_encode($l));
        }

        // extract request path slug
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
        return \substr(\hash('sha256', \random_bytes(8) . (string) \time()), 0, 16);
    }
}
