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
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;
use GSC\StringFilters as SF;

/**
 * Admin Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class AdminPresenter extends APresenter
{
    /* @var string admin key filename */
    const ADMIN_KEY = 'admin.key';

    /* @var int max. display log entries */
    const MAX_LOG_ENTRIES = 1000;

    /* @var int max. display log days */
    const VISIBLE_LOG_DAYS = 100;

    /* @var upload name stubs minimum string length */
    const MIN_STUBS_LENGTH = 3;

    /* @var string thumbnail prefix */
    const THUMB_PREFIX = '.thumb_';

    /* @var string thumbnail postfix */
    const THUMB_POSTFIX = 'px_';

    /* @var array thumbnails width to create */
    const THUMBS_CREATE_WIDTH = [
        160, 320, 640
    ];

    /* @var array thumbnails width to delete */
    const THUMBS_DELETE_WIDTH = [
        160, 320, 640
    ];

    /* @var array thumbnail extensions to delete */
    const THUMBS_DELETE_EXTENSIONS = [
        '.webp',
    ];

    /* @var string last log message */
    private string $_lastlog = '';

    // @var int number of log message repetitions
    private int $_repetitions = 0;

    /* @var int log counter */
    private int $_logcounter = 0;

    /* @var array image handler constants by type */
    const IMAGE_HANDLERS = [
        IMAGETYPE_GIF => [
            'load' => 'imagecreatefromgif',
            'save' => 'imagegif',
            'ext' => '.gif',
            'quality' => 100,
        ],
        IMAGETYPE_JPEG => [
            'load' => 'imagecreatefromjpeg',
            'save' => 'imagejpeg',
            'ext' => '.jpg',
            'quality' => 90,
        ],
        IMAGETYPE_PNG => [
            'load' => 'imagecreatefrompng',
            'save' => 'imagepng',
            'ext' => '.png',
            'quality' => 9,
        ],
        IMAGETYPE_WEBP => [
            'load' => 'imagecreatefromwebp',
            'save' => 'imagewebp',
            'ext' => '.webp',
            'quality' => 90,
        ],
    ];

    /**
     * Controller processor
     *
     * @param mixed $param optional parameter
     * 
     * @return object Controller
     */
    public function process($param = null)
    {
        \setlocale(LC_ALL, "cs_CZ.utf8");
        \error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

        // Config
        $cfg = $this->getCfg();
        if (!\is_array($cfg)) {
            return $this;
        }

        // Model
        $data = $this->getData();
        if (!\is_array($data)) {
            return $this;
        }

        // Match and View
        $match = $this->getMatch();
        if (\is_array($match)) {
            $view = $match['params']['p'] ?? null;
        } else {
            $view = $this->getView();
            if (!$view) {
                return $this;
            }
        }

        // User
        $u = $this->getCurrentUser();
        if (\is_array($u)) {
            $data['user'] = $u;
        }

        // Group
        $g = $this->getUserGroup();
        if (\is_string($g)) {
            $data['admin'] = $g;
            $data["admin_group_{$g}"] = true;
        }

        $extras = [
            'name' => 'Tesseract Admin REST API',
            'fn' => $view,
            "endpoint" => \explode('?', $_SERVER['REQUEST_URI'])[0],
            "api_quota" => "unlimited",
            "cached" => false,
            "uuid" => $this->getUID(),
            "ip" => $this->getIP(),
            // override: ?key= parameter
            'override' => (bool) $this->_isLocalAdmin(),
        ];

        // API calls
        switch ($view) {
        case 'Upload':
            $this->checkPermission('admin,manager,editor');

            // Check if the upload directory is set
            if (\is_null(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }

            // Check if the upload directory exists and is writable
            if (!\is_dir(UPLOAD) || !\is_writable(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }

            // process
            $uploads = $this->processUpload();
            $count = \count($uploads);
            $names = \array_map(
                function ($value) {
                    return "[$value] ";
                }, $uploads
            );
            $names = \implode($names);

            $this->addMessage("ADMIN: file(s) uploaded: {$count}x\n{$names}");
            return $this->writeJsonData(\array_values($uploads), $extras);

        case 'UploadDelete':
            $this->checkPermission('admin,manager,editor');
            if (\is_null(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }
            if (!\is_dir(UPLOAD) || !\is_writable(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }

            // process
            $result = $this->processDelete();

            if (\is_numeric($result)) {
                return $this->writeJsonData($result, $extras);
            }
            if (!$result) {
                return $this->writeJsonData(400, $extras);
            }

            $this->addMessage("ADMIN: file deleted:\n[{$result}]");
            return $this->writeJsonData($result, $extras);

        case 'getUploadsInfo':
            $this->checkPermission('admin,manager,editor');
            if (\is_null(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }
            if (!\is_dir(UPLOAD) || !is_readable(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }

            $size = $dotsize = $count = $dotcount = 0;
            $files = \scandir(UPLOAD);
            if ($files) {
                foreach ($files as $name) {
                    if ($name != "." && $name != "..") {
                        $path = UPLOAD . DS . $name;
                        if (\is_file($path)) {
                            $size += \filesize($path);
                            $count++;
                            if (\str_starts_with($name, '.')) {
                                $dotsize += \filesize($path);
                                $dotcount++;
                            }
                        }
                    }
                }
            } else {
                return $this->writeJsonData(500, $extras);
            }

            return $this->writeJsonData(
                [
                    'count' => $count,
                    'size' => $size,
                    'dot_count' => $dotcount,
                    'dot_size' => $dotsize,
                    'reg_count' => $count - $dotcount,
                    'reg_size' => $size - $dotsize,
                ],
                $extras
            );
        
        case 'getUploads':
            $this->checkPermission('admin,manager,editor');
            if (\is_null(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }
            if (!\is_dir(UPLOAD) || !\is_writable(UPLOAD)) {
                return $this->writeJsonData(410, $extras);
            }

            $files = [];
            $stubs = [];
            $stubs_count = [];
            $uniques = [];

            if ($handle = \opendir(UPLOAD)) {
                while (false !== ($f = \readdir($handle))) {
                    if (($f != '.') && ($f != '..')) {
                        // exclude '.size' file
                        if ($f === '.size') {
                            continue;
                        }
                        // exclude thumbnails
                        if (\str_starts_with($f, self::THUMB_PREFIX)) {
                            continue;
                        }

                        $thumbnails = [];
                        $info = \pathinfo($f);
                        if (\is_array($info)) {
                            $fn = $info['filename'];
                            if (!$fn) {
                                continue;
                            }
                            $ext = $info['extension'] ?? '';

                            // check for the thumbnails
                            foreach (self::THUMBS_CREATE_WIDTH as $w) {
                                $file = UPLOAD . DS
                                    . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                    . $fn . '.webp';
                                if (file_exists($file) && is_readable($file)) {
                                    $thumbnails[$w] = self::THUMB_PREFIX . $w
                                        . self::THUMB_POSTFIX . $fn . '.webp';
                                }
                            }

                            // output only unique WebP
                            if (\in_array($fn, $uniques) && ($ext == 'webp')) {
                                continue;
                            }

                            // remove WebP if we already have other format
                            if (\in_array($fn, $uniques) && ($ext != 'webp')) {
                                unset($files["{$fn}.webp"]);
                            }
                            \array_push($uniques, $fn);

                            if (empty($thumbnails)) {
                                $thumbnails = null;
                            }

                            $files[$f] = [
                                'name' => $f,
                                'size' => \filesize(UPLOAD . DS . $f),
                                'timestamp' => \filemtime(UPLOAD . DS . $f),
                                'thumbnails' => $thumbnails,
                            ];

                            // calculate stubs
                            $fs = \strtr($fn, '_+-.()[]', '        ');
                            $fs = \explode(' ', $fs);
                            foreach ($fs as $st) {
                                if ($st === '') {
                                    continue;
                                }
                                $st = (string) $st;
                                $stubs[$st] = $st;
                                if (!isset($stubs_count[$st])) {
                                    $stubs_count[$st] = 0;
                                }
                                $stubs_count[$st]++;
                            }
                        }
                    }
                }
                \closedir($handle);
            }

            // filter stubs
            $stubs = \array_filter($stubs);
            foreach ($stubs as $k => $v) {
                $v = (string) $v;
                if (strlen((string) $v) < self::MIN_STUBS_LENGTH) {
                    unset($stubs[$k]);
                    unset($stubs_count[$k]);
                }
            }

            \ksort($stubs);
            \ksort($files);
            \arsort($stubs_count);

            return $this->writeJsonData(
                [
                    'stubs' => \array_values($stubs),
                    'stubs_count' => $stubs_count,
                    'count' => \count($files),
                    'files' => \array_values($files),
                ],
                $extras
            );
    
        case 'AuditLog':
            $this->checkPermission('admin');
            $this->setHeaderHTML();
            $filename = DATA . DS . self::AUDITLOG_FILE;
            $file = \popen("tac {$filename}", 'r');
            $c = 0;
            $logs = [];
            if (\is_resource($file)) {
                while (($logs[] = \fgets($file)) && ($c < self::MAX_LOG_ENTRIES - 1)) { // phpcs:ignore
                    $c++;
                }
                \pclose($file);
            }
            \array_walk($logs, array($this, '_decorateLogs'));
            $data['content'] = $logs;
            $data['repetitions'] = $this->_repetitions;
            return $this->setData('output', $this->setData($data)->renderHTML('auditlog')); // phpcs:ignore

        case 'GetCsvInfo':
            $this->checkPermission('admin,manager,editor');
            $csv_info = \array_merge($cfg['locales'] ?? [], $cfg['app_data'] ?? []);
            // enhance the CSV array with information
            foreach ($csv_info as $k => $v) {
                if (!$k || !$v) {
                    continue;
                }
                if (file_exists(DATA . DS . "{$k}.csv")
                    && is_readable(DATA . DS . "{$k}.csv")
                ) {
                    $csv_info[$k] = [
                        'csv' => $v,
                        'lines' => $this->_getCSVFileLines(DATA . DS . "{$k}.csv"),
                        'sheet' => $cfg['lasagna_sheets'][$k] ?? null,
                        'timestamp' => \filemtime(DATA . DS . "{$k}.csv"),
                    ];
                    if ($csv_info[$k]['lines'] === -1) {
                        unset($csv_info[$k]);
                    }
                }
            }
            return $this->writeJsonData($csv_info, $extras);

        case 'GetArticlesInfo':
            $this->checkPermission('admin,manager,editor');
            $data = [];
            $profile = 'default';
            $f = DATA . DS. "summernote_articles_{$profile}.txt";
            $flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
            if (file_exists($f) && is_readable($f)) {
                $data = \file($f, $flags);
                if (\is_array($data)) {
                    $data = \array_unique($data, SORT_LOCALE_STRING);
                } else {
                    $data = null;
                }
            }
            return $this->writeJsonData($data, $extras);

        case 'UpdateArticles':
            $this->checkPermission('admin,manager,editor');
            $x = 0;
            $profile = 'default';
            if (isset($_POST['data'])) {
                $data = (string) trim((string) $_POST['data']);
                // remove extra whitespace
                $data_nows = \preg_replace('/\s\s+/', ' ', (string) $_POST['data']);
                $x++;
            }
            if (isset($_POST['path'])) {
                $path = trim((string) $_POST['path']);
                // URL path
                if (strlen($path)) {
                    $x++;
                }
            }
            if (isset($_POST['hash'])) {
                $hash = trim((string) $_POST['hash']);
                // SHA 256 hexadecimal
                if (strlen($hash) === 64) {
                    $x++;
                }
            }
            if ($x != 3) {
                $extras['error_descriptions'] = 'incorrect number of parameters';
                return $this->writeJsonData(500, $extras);
            }
            if (!isset($hash)) {
                $extras['error_descriptions'] = 'incorrect hash';
                return $this->writeJsonData(500, $extras);
            }
            if (!isset($path)) {
                $extras['error_descriptions'] = 'incorrect path';
                return $this->writeJsonData(500, $extras);
            }
            if (!isset($data_nows)) {
                $extras['error_descriptions'] = 'incorrect data_nows';
                return $this->writeJsonData(500, $extras);
            }
            $fr = DATA . DS. "summernote_{$profile}_{$hash}.json";
            $fb = DATA . DS. "summernote_{$profile}_{$hash}.bak";
            if (file_exists($fr) && is_readable($fr)) {
                if (copy($fr, $fb) === false) {
                    $this->addError("ADMIN: article [{$path}] backup failed");
                    return $this->writeJsonData(
                        [
                            'code' => 401,
                            'status' => 'Article backup failed.',
                            'hash' => $hash,
                        ], $extras
                    );
                };
            }
            $fp = DATA . DS . "summernote_{$profile}_{$hash}.db";
            $flags = LOCK_EX | FILE_APPEND;
            if (@file_put_contents($fp, $data_nows . "\n", $flags) === false) {
                $this->addError("ADMIN: article [{$path}] history write failed");
                return $this->writeJsonData(
                    [
                        'code' => 401,
                        'status' => 'Article write to history file failed.',
                        'hash' => $hash,
                    ], $extras
                );
            };
            $fp = DATA . DS . "summernote_{$profile}_{$hash}.json";
            $flags = LOCK_EX;
            if (@file_put_contents($fp, $data, $flags) === false) {
                $this->addError("ADMIN: article [{$path}] write to file failed");
                return $this->writeJsonData(
                    [
                        'code' => 500,
                        'status' => 'Article write to file failed.',
                        'hash' => $hash,
                    ], $extras
                );
            }

            // save article meta data
            $p = [];
            $f = DATA . DS . "summernote_articles_{$profile}.txt";
            $flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
            $p = [];
            if (file_exists($f) && is_readable($f)) {
                $p = \file($f, $flags);
            }
            if (\is_array($p)) {
                $p[] = $path;
            } else {
                $extras['error_descriptions'] = 'incorrect metadata';
                return $this->writeJsonData(500, $extras);
            }
            \sort($p, SORT_LOCALE_STRING);
            $p = \array_unique($p, SORT_LOCALE_STRING);
            $flags = LOCK_EX;
            if (@file_put_contents($f, \implode("\n", $p), $flags) === false) {
                $this->addError("ADMIN: updating article [{$path}] hash [{$hash}] failed"); // phpcs:ignore
                return $this->writeJsonData(500, $extras);
            }
            $this->addMessage("ADMIN: article updated: [{$path}] hash [{$hash}]"); // phpcs:ignore
            return $this->writeJsonData(['status' => 'OK', 'hash' => $hash], $extras); // phpcs:ignore
    
        case 'GetToken':
            $this->checkPermission('admin,manager,editor');
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            if ($user = $this->getCurrentUser()['id'] ?? null) {
                if ($role = $this->getUserGroup()) {
                    $h = \hash('sha256', $role . $user . \time());
                    $h = \substr($h, 0, 8);
                    $code = $data['base']
                        . 'admin/FNXXXRemote?role='
                        . $role
                        . '&user='
                        . $h
                        . '&token='
                        . \hash('sha256', $role . $key . $h);
                    $this->addMessage("ADMIN: NEW TOKEN as [{$role}] for [{$h}]");
                    return $this->writeJsonData($code, $extras);
                }
            }
            $this->_unauthorizedAccess();

        case 'AuditLogJSONRemote':
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $role = $_GET['role'] ?? null;
            $user = $_GET['user'] ?? null;
            $token = $_GET['token'] ?? null;
            switch ($role) {
            case 'admin':
                break;
            default:
                $this->_unauthorizedAccess();
            }
            if ($role && $user && $token || $this->_isLocalAdmin()) {
                $code = \hash('sha256', $role . $key . $user);
                if ($code === $token || $this->_isLocalAdmin()) {
                    $filename = DATA . DS . self::AUDITLOG_FILE;
                    $file = \popen("tac {$filename}", 'r');
                    $c = 0;
                    $logs = [];
                    if (\is_resource($file)) {
                        while (($logs[] = \fgets($file)) && ($c < self::MAX_LOG_ENTRIES - 1)) { // phpcs:ignore
                            $c++;
                        }
                        \pclose($file);
                    }
                    \array_walk($logs, array($this, '_decorateLogsExport'));
                    $this->addMessage("REMOTE FN: AuditLogJSON fetched [{$user}]"); // phpcs:ignore
                    return $this->writeJsonData($logs, $extras);
                }
            }
            $this->_unauthorizedAccess();

        case 'RebuildAdminKeyRemote':
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $role = $_GET['role'] ?? null;
            $user = $_GET['user'] ?? null;
            $token = $_GET['token'] ?? null;
            switch ($role) {
            case 'admin':
                break;
            default:
                $this->_unauthorizedAccess();
            }
            if ($role && $user && $token || $this->_isLocalAdmin()) {
                $code = \hash('sha256', $role . $key . $user);
                if ($code === $token || $this->_isLocalAdmin()) {
                    $this->addMessage("REMOTE FN: new admin key [{$user}]");
                    $this->_rebuildAdminKey();
                    return $this->writeJsonData(
                        [
                            'host' => HOST,
                            'message' => 'OK',
                        ], $extras
                    );
                }
            }
            $this->_unauthorizedAccess();

        case 'FlushCacheRemote':
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $role = $_GET['role'] ?? null;
            $user = $_GET['user'] ?? null;
            $token = $_GET['token'] ?? null;
            switch ($role) {
            case 'admin':
            case 'manager':
            case 'editor':
                break;
            default:
                $this->_unauthorizedAccess();
            }
            if ($role && $user && $token || $this->_isLocalAdmin()) {
                $code = \hash('sha256', $role . $key . $user);
                if ($code === $token || $this->_isLocalAdmin()) {
                    $this->addMessage("REMOTE FN: cache flush [{$user}]");
                    $this->flushCache();
                    return $this->writeJsonData(
                        [
                            'host' => HOST,
                            'message' => 'OK',
                        ], $extras
                    );
                }
            }
            $this->_unauthorizedAccess();

        case 'CoreUpdateRemote':
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $role = $_GET['role'] ?? null;
            $user = $_GET['user'] ?? null;
            $token = $_GET['token'] ?? null;
            switch ($role) {
            case 'admin':
            case 'manager':
            case 'editor':
                break;
            default:
                $this->_unauthorizedAccess();
            }
            if ($role && $user && $token || $this->_isLocalAdmin()) {
                $code = \hash('sha256', $role . $key . $user);
                if ($code === $token || $this->_isLocalAdmin()) {
                    $this->addMessage("REMOTE FN: core update [{$user}]");
                    $this->setForceCsvCheck();
                    $this->postloadAppData('app_data');
                    $this->flushCache();
                    return $this->writeJsonData(
                        [
                            'host' => HOST,
                            'message' => 'OK',
                        ], $extras
                    );
                }
            }
            $this->_unauthorizedAccess();

        case 'RebuildNonceRemote':
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $role = $_GET['role'] ?? null;
            $user = $_GET['user'] ?? null;
            $token = $_GET['token'] ?? null;
            switch ($role) {
            case 'admin':
            case 'manager':
                break;
            default:
                $this->_unauthorizedAccess();
            }
            if ($role && $user && $token || $this->_isLocalAdmin()) {
                $code = \hash('sha256', $role . $key . $user);
                if ($code === $token || $this->_isLocalAdmin()) {
                    $this->addMessage("REMOTE FN: new nonce [{$user}]");
                    $this->_rebuildNonce();
                    return $this->writeJsonData(
                        [
                            'function' => $view,
                            'host' => HOST,
                            'message' => 'OK',
                        ], $extras
                    );
                }
            }
            $this->_unauthorizedAccess();

        case 'RebuildSecureKeyRemote':
            if (!$key = $this->_readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $role = $_GET['role'] ?? null;
            $user = $_GET['user'] ?? null;
            $token = $_GET['token'] ?? null;
            switch ($role) {
            case 'admin':
            case 'manager':
                break;
            default:
                $this->_unauthorizedAccess();
            }
            if ($role && $user && $token || $this->_isLocalAdmin()) {
                $code = hash('sha256', $role . $key . $user);
                if ($code === $token || $this->_isLocalAdmin()) {
                    $this->addMessage("REMOTE FN: new secure key [{$user}]");
                    $this->_rebuildSecureKey();
                    return $this->writeJsonData(
                        [
                            'host' => HOST,
                            'function' => $view,
                            'message' => 'OK',
                        ], $extras
                    );
                }
            }
            $this->_unauthorizedAccess();

        case 'FlushCache':
            $this->checkPermission('admin,manager,editor');
            $this->addMessage('ADMIN: FLUSH CACHE');
            $this->flushCache();
            return $this->writeJsonData(['status' => 'OK'], $extras);

        case 'CoreUpdate':
            $this->checkPermission('admin,manager,editor');
            $this->addMessage('ADMIN: CORE UPDATE');
            $this->setForceCsvCheck();
            $this->postloadAppData('app_data');
            return $this->writeJsonData(['status' => 'OK'], $extras);

        default:
            $this->checkRateLimit();
            $this->_unauthorizedAccess();
        }
        return $this;
    }

    /**
     * Process uploaded files and generate thumbnails
     *
     * @return array<string> array of uploaded filenames
     */
    public function processUpload()
    {
        $uploads = [];

        // Loop through each uploaded file
        foreach ($_FILES as $key => &$file) {
            $f = $file['name'];

            // Sanitize the filename
            $f = \strtr(trim(\basename($f)), " '\"\\()", '______');
            SF::transliterate($f);
            SF::sanitizeStringLC($f);
            SF::transliterate($f);

            // skip thumbnails
            if (\str_starts_with($f, self::THUMB_PREFIX)) {
                continue;
            }
            // skip .bak extension
            if (\str_ends_with($f, '.bak')) {
                continue;
            }
            // skip .php extension
            if (\str_ends_with($f, '.php')) {
                continue;
            }
            // skip .inc extension
            if (\str_ends_with($f, '.inc')) {
                continue;
            }
            // skip '.size' file
            if ($f === '.size') {
                continue;
            }

            // Get the file information
            $info = \pathinfo($f);

            // Skip files without a name
            if (\is_array($info) && !$info['filename']) {
                continue;
            }

            // Process the uploaded file
            if (\move_uploaded_file($file['tmp_name'], UPLOAD . DS . $f)) {
                if (\is_array($info)) {
                    $fn = $info['filename'];

                    // Skip thumbnails generation if the file has no extension
                    if (empty($info['extension'])) {
                        continue;
                    }

                    $in = UPLOAD . DS . $f;
                    $uploads[$f] = \urlencode($f);

                    // Delete old thumbnails
                    foreach (self::THUMBS_DELETE_EXTENSIONS as $x) {
                        foreach (self::THUMBS_DELETE_WIDTH as $w) {
                            $file = UPLOAD . DS
                                . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                . $fn . $x;
                            if (file_exists($file)) {
                                @unlink($file);
                            }
                        }
                    }

                    // Create new thumbnails
                    foreach (self::THUMBS_CREATE_WIDTH as $w) {
                        $out = UPLOAD . DS
                            . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                            . $fn . '.webp';
                        $this->createThumbnail($in, $out, $w);
                    }

                    // Skip if the original file is already in WebP
                    if (\str_ends_with($f, '.webp')) {
                        continue;
                    }

                    // Create a WebP thumbnail for the original file
                    $this->createThumbnail($in, UPLOAD . DS . $fn . '.webp');
                }
            }
        }
        return $uploads;
    }

    /**
     * Process the deletion of a file and its associated thumbnails
     *
     * @return string|int return the sanitized filename if deletion was successful
     *                    or an HTTP status code (400 or 405) if an error occurred
     */
    public function processDelete()
    {
        if (isset($_POST['name'])) {
            $name = trim($_POST['name']);
            $name = \strtr(trim($name), " '\"\\()", '______');
            SF::transliterate($name);
            SF::sanitizeStringLC($name);
            SF::transliterate($name);
            if ($name) {
                $name = \preg_replace('/^\.\.\//', '', $name);
            }
            if (!\is_string($name)) {
                return 400;
            }
            if ($name === '.size') {
                return 405;
            }

            $info = \pathinfo($name);
            if (\is_array($info)) {
                $fn = $info['filename'];

                // delete thumbnails
                foreach (self::THUMBS_DELETE_EXTENSIONS as $x) {
                    foreach (self::THUMBS_DELETE_WIDTH as $w) {
                        $file = UPLOAD . DS
                            . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                            . $fn . $x;
                        if (file_exists($file)) {
                            @unlink($file);
                        }
                    }
                    $file = UPLOAD . DS . $fn . $x;
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }
            }

            // delete origin
            $file = UPLOAD . DS . $name;
            if (file_exists($file)) {
                @unlink($file);
            }
            return $name;
        }
        return 400;
    }

    /**
     * Check if call is made by a local administrator
     *
     * @return boolean is there a local administrator?
     */
    private function _isLocalAdmin()
    {
        if (CLI) {
            return true;
        }
        if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') { // phpcs:ignore
            return true;
        }
        $key = $this->_readAdminKey();
        if ($key && isset($_GET['key']) && $key === $_GET['key']) { // phpcs:ignore
            return true;
        }
        return false;
    }

    /**
     * Rebuild the identity nonce
     *
     * @return void
     */
    private function _rebuildNonce()
    {
        $file = DATA . DS . self::IDENTITY_NONCE_FILE;
        @unlink($file);
        \clearstatcache();
    }

    /**
     * Rebuild the admin key
     *
     * @return self
     */
    private function _rebuildAdminKey()
    {
        $file = DATA . DS . self::ADMIN_KEY;
        @unlink($file);
        return $this->_createAdminKey();
    }

    /**
     * Rebuild the secure key
     *
     * @return object
     */
    private function _rebuildSecureKey()
    {
        $key = $this->getCfg('secret_cookie_key') ?? 'secure.key';
        if (!\is_string($key)) {
            $this->addCritical('Secure cookie must be a string!');
            ErrorPresenter::getInstance()->process(
                ['code' => 500, 'message' => 'SYSTEM ERROR: unable to setup the encryption'] // phpcs:ignore
            );

        }
        if (\is_string($key)) {
            $key = trim($key, '/.');
            if (file_exists(DATA . DS . $key)) {
                unlink(DATA . DS . $key);
                clearstatcache();
            }
        }
        return $this->setIdentity();
    }

    /**
     * Flush the cache
     *
     * @return self
     */
    public function flushCache()
    {
        $store = new FlockStore();
        $factory = new Factory($store);
        $lock = $factory->createLock('core-update');
        if ($lock->acquire()) {
            try {
                if (ob_get_level()) {
                    @ob_end_clean();
                }
                if (\is_array($this->getData('cache_profiles'))) {
                    foreach ($this->getData('cache_profiles') as $k => $v) {
                        Cache::clear($k);
                        Cache::clear("{$k}_file");
                    }
                }
                \array_map('unlink', \glob(CACHE . DS . '*.php') ?: []);
                \array_map('unlink', \glob(CACHE . DS . '*.tmp') ?: []);
                \array_map('unlink', \glob(CACHE . DS . CACHEPREFIX . '*') ?: []);
                if (LOCALHOST) {
                    // localhost = do nothing :)
                } else {
                    $cf = $this->getCfg('cf');
                    if (\is_array($cf)) {
                        $this->cloudflarePurgeCache($cf);
                    }
                }
                $this->checkLocales();
            } finally {
                if (CLI) {
                    // do nothing :)
                } else {
                    $file = DATA . DS . '_random_cdn_hash';
                    $hash = \hash('sha1', $this->getNonce());
                    $flags = LOCK_EX;
                    @unlink($file);
                    if (@file_put_contents($file, $hash, $flags) === false) {
                        \error_log('Could not write to file: [' . $file . '].');
                    }
                }
                \clearstatcache();
                $lock->release();
            }
        } else {
            $this->addError('ADMIN: could not obtain a CORE-UPDATE file lock');
        }
        return $this;
    }

    /**
     * End program execution with HTTP error 401
     * 
     * @return void
     */
    private function _unauthorizedAccess()
    {
        if (CLI) {
            echo "ERROR: Unauthorized access.\n";
            exit(1);
        }

        $path = $this->getData('request_path');
        if (!\is_string($path)) {
            $path = '*** not set ***';
        }
        $this->addMessage("ADMIN: Unauthorized access.\nPath: [{$path}]");
        $this->setLocation('/err/401');
    }

    /**
     * Create the admin key
     *
     * @return self
     */
    private function _createAdminKey()
    {
        $file = DATA . DS . self::ADMIN_KEY;
        if (!file_exists($file)) {
            try {
                $key = \hash('sha256', random_bytes(32) . time());
            } catch (\Exception $e) {
                $this->addError("ADMIN: error generating random key: " . $e->getMessage()); // phpcs:ignore
                ErrorPresenter::getInstance()->process(500);
                return $this;
            }
            if (@file_put_contents($file, $key) === false) {
                $this->_handleFileError($file, "ADMIN: error writing to admin key file"); // phpcs:ignore
                unlink($file);
            } else if (!chmod($file, 0600)) {
                $this->_handleFileError($file, "ADMIN: error setting admin key file permissions"); // phpcs:ignore
                unlink($file);
            } else {
                $this->addMessage('ADMIN: keyfile created');
            }
        }
        return $this;
    }

    /**
     * Handle file errors
     *
     * @param string $file          the file causing the error
     * @param string $messagePrefix the prefix for the error message
     *
     * @return void
     */
    private function _handleFileError($file, $messagePrefix)
    {
        $err = error_get_last();
        $errMessage = ($err ? $err['message'] : 'Unknown error');
        $message = $messagePrefix . " Message:\n{$errMessage}";
        $this->addError($message);
        ErrorPresenter::getInstance()->process(500);
    }

    /**
     * Read the secure admin key or try to create a new one in the process
     *
     * @return mixed admin key / null on error
     */
    private function _readAdminKey()
    {
        $file = DATA . DS . self::ADMIN_KEY;
        if (!is_readable($file)) {
            $this->addError('ADMIN: missing or unreadable admin key file');
            $this->_createAdminKey();
        }
        if (is_readable($file)) {
            $key = trim(file_get_contents($file) ?: '');
            if (strlen($key) > 0) {
                return $key;
            }
        }
        $this->addError('ADMIN: invalid admin key file');
        return null;
    }

    /**
     * Create thumbnail from the source image
     *
     * @param string $src  file name source
     * @param string $dest file name target
     * @param mixed  $tw   output width or null
     * @param mixed  $th   output height or null
     * 
     * @return mixed image save call result
     */
    public function createThumbnail($src, $dest, $tw = null, $th = null
    ) {
        $type = \exif_imagetype($src);
        if (!$type) {
            return null; // unknown type
        }
        if (!\array_key_exists($type, self::IMAGE_HANDLERS)) {
            return null; // unsupported conversion
        }

        // load raw image
        $image = \call_user_func(self::IMAGE_HANDLERS[$type]['load'], $src);

        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        $w = \imagesx($image);
        $w = \intval($w);
        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        $h = \imagesy($image);
        $h = \intval($h);

        // calculate width and height
        if ($tw === null) {
            $tw = $w;
        }
        if (\is_numeric($tw)) {
            $tw = \intval($tw);
        }
        if ($th === null) {
            $ratio = $w / $h;
            if ($w > $h) {
                $th = \floor($tw / $ratio);
            } else {
                $th = $tw;
                $tw = \floor($tw * $ratio);
            }
        }

        $thmb = null;
        if (\is_numeric($tw)) {
            $tw = \intval($tw);
            if (\is_numeric($th)) {
                $th = \intval($th);
                $thmb = \imagecreatetruecolor($tw, $th); // placeholder
            }
        }

        if ($thmb) {
            if ($type === IMAGETYPE_PNG) {
                \imagecolortransparent(
                    $thmb,
                    \imagecolorallocate($thmb, 0, 0, 0) ?: 0
                );
                \imagealphablending($thmb, false);
                \imagesavealpha($thmb, true);
            }

            // phpcs:ignore
            /** @phpstan-ignore-next-line */
            \imagecopyresampled($thmb, $image, 0, 0, 0, 0, $tw, $th, $w, $h);

            // save WebP thumbnail
            return \call_user_func(
                self::IMAGE_HANDLERS[IMAGETYPE_WEBP]['save'],
                $thmb,
                $dest,
                self::IMAGE_HANDLERS[IMAGETYPE_WEBP]['quality']
            );
        }
    }

    /**
     * Decorate log entries by reference
     *
     * @param string $val log line
     * @param int    $key array index
     * 
     * @return void
     */
    private function _decorateLogs(&$val, $key)
    {
        if (\stripos($val, 'exception') !== false) {
            $val = '';
            return;
        }
        if (\stripos($val, 'Origin Time-out') !== false) {
            $val = '';
            return;
        }
        if (\stripos($val, 'Bad Gateway') !== false) {
            $val = '';
            return;
        }
        $x = \explode(';', $val);
        if (!\is_array($x)) {
            return;
        }
        if (\count($x) < 5) {
            return;
        }

        $this->_logcounter++;

        $t = \strtotime($x[0]);
        if ($t) {
            $now = \time() + 1;
            $diff = $now - $t;
            $days = $diff / 86400;
            if ($days > self::VISIBLE_LOG_DAYS) {
                $val = '';
                return;
            }
            $t = \date("<b>j.\tn.\tY</b>\nH:i:s", $t);
            $t = \str_replace("\t", '&nbsp;', $t);
            $t = \str_replace("\n", '<br>', $t);
            $x[0] = $t;
        }

        $x[2] = \str_replace('IP:', '', $x[2]);
        $x[3] = \str_replace('NAME:', '', $x[3]);
        $x[4] = \str_replace('EMAIL:', '', $x[4]);

        $class = $class2 = '';
        if (\strpos($x[2], ':') !== false) {
            $x[2] = \str_replace(':', ':&#173;', $x[2]);
            $class2 = 'ipadd ipv6';
        } else {
            $class2 = 'ipadd';
        }
        
        $lookup = [
            'admin' => [
                'type' => 'type_admin',
                'class' => 'green lighten-4'
            ],
            'file deleted' => [
                'type' => 'type_file_delete',
                'class' => 'red lighten-4'
            ],
            'file(s) uploaded' => [
                'type' => 'type_file_upload',
                'class' => 'blue lighten-4'
            ],
            'unauthorized access' => [
                'type' => 'type_unauthorized',
                'class' => 'orange lighten-4'
            ],
            'cloudflare' => [
                'type' => 'type_cloudflare',
                'class' => 'indigo lighten-4'
            ],
            'halite' => [
                'type' => 'type_halite',
                'class' => 'green lighten-4'
            ],
            'download' => [
                'type' => 'type_download',
                'class' => 'grey lighten-2'
            ],
            'oauth' => [
                'type' => 'type_oauth',
                'class' => 'lime lighten-4'
            ],
            'remote' => [
                'type' => 'type_remote',
                'class' => 'orange lighten-4'
            ],
            'token' => [
                'type' => 'type_token',
                'class' => 'purple lighten-4'
            ],
            'limiter' => [
                'type' => 'type_limiter',
                'class' => 'amber lighten-4'
            ],
        ];

        $class = '';
        $type = 'type_unknown';
        foreach ($lookup as $keyword => $data) {
            if (\stripos($x[1], $keyword) !== false) {
                $type = $data['type'];
                $class = $data['class'];
            }
        }

        if ($x[1] === $this->_lastlog) {
            $this->_repetitions++;
            $class = 'reps hide';
        }
        $this->_lastlog = $x[1];

        // make these bold
        $bolds = [
            'ADMIN',
            'Download',
            'HALITE',
            'OAuth login',
            'admin',
            'manager',
        ];
        
        $x[1] = preg_replace_callback(
            '/\b(' . implode('|', array_map('preg_quote', $bolds)) . ')\b/',
            function ($match) {
                return '<b>' . $match[1] . '</b>';
            },
            $x[1]
        );

        $ipinfo = "href='https://ipinfo.io/{$x[2]}'";
        if (\strpos($x[2], ':') !== false) {
            $ipinfo = '';
        }

        $val = "<tr data-type='{$type}'"
            . " class='logrow {$type} {$class}'"
            . ">"
            . "<td class=center><b>" . $this->_logcounter . "</b></td>"
            . "<td class='center c2'>"
            . "{$x[0]}<br>"
            . "<a target=_blank style='color:#fff' {$ipinfo}>"
            . "<div class='{$class2}'>"
            . "{$x[2]}"
            . "</div></a></td>"
            . "<td class='center truncate'><b>{$x[3]}</b><br>{$x[4]}</td>"
            . "<td>{$x[1]}</td>"
            . "</tr>";
    }

    /**
     * Decorate log entries by reference for export
     *
     * @param string $val log line
     * @param int    $key array index
     * 
     * @return void
     */
    private function _decorateLogsExport(&$val, $key)
    {
        if (\stripos($val, 'exception') !== false) {
            $val = '';
            return;
        }
        if (\stripos($val, 'Origin Time-out') !== false) {
            $val = '';
            return;
        }
        if (\stripos($val, 'Bad Gateway') !== false) {
            $val = '';
            return;
        }

        $x = \explode(';', $val);
        if (!\is_array($x)) {
            return;
        }
        if (\count($x) < 5) {
            return;
        }

        $t = \strtotime($x[0]);
        if ($t) {
            $now = \time() + 1;
            $diff = $now - $t;
            $days = $diff / 86400;
            if ($days > self::VISIBLE_LOG_DAYS) {
                $val = '';
                return;
            }
            $x[0] = \date("j. n. Y H:i:s", $t);
        }

        $x[2] = \str_replace('IP:', '', $x[2]);
        $x[3] = \str_replace('NAME:', '', $x[3]);
        $x[4] = \str_replace('EMAIL:', '', $x[4]);

        $x[1] = trim($x[1], "\r\n\t");
        $x[2] = trim($x[2], "\r\n\t");
        $x[3] = trim($x[3], "\r\n\t");
        $x[4] = trim($x[4], "\r\n\t");

        $type = 'unknown';
        $lookup = [
            'admin' => 'admin',
            'admin: file deleted' => 'file_delete',
            'admin: file(s) uploaded' => 'file_upload',
            'cloudflare' => 'cloudflare',
            'download' => 'download',
            'halite' => 'system',
            'limiter' => 'limiter',
            'oauth' => 'oauth',
            'remote' => 'remote',
            'token' => 'token',
        ];
        
        foreach ($lookup as $keyword => $category) {
            if (\stripos($x[1], $keyword) !== false) {
                $type = $category;
                break;
            }
        }

        $x[1] = str_replace('<br>', " ", $x[1]);

        $val = [
            'id' => $key + 1,
            'timestamp' => $t,
            'datetime' => $x[0],
            'ip' => $x[2],
            'name' => $x[3],
            'email' => $x[4],
            'type' => $type,
            'message' => $x[1],
        ];
    }

    /**
     * Get number of CSV lines in a file
     *
     * @param string $f filename
     * 
     * @return int number of lines / -1 on error
     */
    private function _getCSVFileLines($f)
    {
        try {
            if (!file_exists($f)) {
                return -1;
            }
            $csv = Reader::createFromPath($f, 'r');
            $csv->setHeaderOffset(0);
            return \count($csv) - 1;
        } catch (\Exception $e) {
            return -1;
        }
    }
}
