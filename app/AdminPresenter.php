<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

use Cake\Cache\Cache;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * Admin Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class AdminPresenter extends APresenter
{
    /* @var string admin key filename */
    const ADMIN_KEY = "admin.key";
    /* @var string thumbnail prefix */
    const THUMB_PREFIX = ".thumb_";
    /* @var string thumbnail postfix */
    const THUMB_POSTFIX = "px_";
    /* @var array thumbnails width to create */
    const THUMBS_CREATE = [
        160, 320, 640
    ];
    /* @var array thumbnails width to delete, incl. legacy */
    const THUMBS_DELETE = [
        50, 64, 128, 150, 160, 320, 512, 640
    ];
    /* @var array thumbnail extensions */
    const THUMBS_EXTENSIONS = [
        '.jpeg',
        '.jpg',
        '.png',
        '.webp',
    ];
    /* @var array image handler constants by type */
    const IMAGE_HANDLERS = [
        IMAGETYPE_JPEG => [
            "load" => "imagecreatefromjpeg",
            "save" => "imagejpeg",
            "quality" => 75,
        ],
        IMAGETYPE_PNG => [
            "load" => "imagecreatefrompng",
            "save" => "imagepng",
            "quality" => 8,
        ],
        IMAGETYPE_WEBP => [
            "load" => "imagecreatefromwebp",
            "save" => "imagewebp",
            "quality" => 75,
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
        $cfg = $this->getCfg();
        if (!\is_array($cfg)) {
            return $this;
        }

        $data = $this->getData();
        if (!\is_array($data)) {
            return $this;
        }

        $match = $this->getMatch();
        if (\is_array($match)) {
            $view = $match["params"]["p"] ?? null;
        } else {
            $view = $this->getView();
        }

        $u = $this->getCurrentUser();
        if (\is_array($u)) {
            $data["user"] = $u;
        }
        $g = $this->getUserGroup();
        if (\is_string($g)) {
            $data["admin"] = $g;
            $data["admin_group_{$g}"] = true;
        }
        $extras = [
            "fn" => $view,
            "ip" => $this->getIP(),
            "name" => "Tesseract Admin",
            // override by ?key= parameter
            "override" => (bool) $this->isLocalAdmin(),
        ];

        // API calls
        switch ($view) {

        case "upload":
            $this->checkPermission("admin");
            $uploads = [];
            foreach ($_FILES as $key => &$file) {

                // sanitize filename
                $f = $file["name"];
                $f = \strtr(\trim(\basename(\strtolower($f))), " '\"\\", "____");

                // skip possible thumbnails
                if (\str_starts_with($f, self::THUMB_PREFIX)) {
                    continue;
                }

                // process uploaded file
                if (@\move_uploaded_file($file["tmp_name"], UPLOAD . DS . $f)) {
                    $info = \pathinfo($f);
                    if (\is_array($info)) {
                        $fn = $info['filename'];
                        $in = UPLOAD . DS . $f;
                        $uploads[$f] = \urlencode($f);

                        // delete old thumbnails
                        foreach (self::THUMBS_EXTENSIONS as $x) {
                            foreach (self::THUMBS_DELETE as $w) {
                                $file = UPLOAD . DS
                                    . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                    . $fn . $x;
                                if (\file_exists($file)) {
                                    \unlink($file);
                                }
                            }
                        }

                        // create new thumbnails
                        foreach (self::THUMBS_CREATE as $w) {
                            $out = UPLOAD . DS
                                . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                . $f;
                            $this->createThumbnail($in, $out, $w);
                            $out = UPLOAD . DS
                                . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                . $fn . ".webp";
                            $this->createThumbnail($in, $out, $w);
                        }

                        // skip conversion if the original is already in WebP
                        if (\str_ends_with($f, '.webp')) {
                            continue;
                        }
                        $this->createThumbnail($in, UPLOAD . DS . $fn . '.webp');
                    }
                }
            }
            return $this->writeJsonData($uploads, $extras);

        case "uploadDelete":
            $this->checkPermission("admin");
            if (isset($_POST["name"])) {
                $name = \trim($_POST["name"], "\\/.");
                $info = \pathinfo($name);
                if (\is_array($info)) {
                    $fn = $info['filename'];

                    // delete all files by the extension
                    foreach (self::THUMBS_EXTENSIONS as $x) {

                        // delete old thumbnails
                        foreach (self::THUMBS_DELETE as $w) {
                            $file = UPLOAD . DS
                                . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                . $fn . $x;
                            if (\file_exists($file)) {
                                \unlink($file);
                            }
                        }

                        // delete main file(s)
                        $file = UPLOAD . DS . $fn . $x;
                        if (\file_exists($file)) {
                            \unlink($file);
                        }
                    }
                }

                // delete the origin
                $file = UPLOAD . DS . $name;
                if (\file_exists($file)) {
                    \unlink($file);
                }
                return $this->writeJsonData($name, $extras);
            }
            break;

        case "getUploads":
            $this->checkPermission("admin");
            $count = 0;
            $files = [];
            $uniques = [];
            if ($handle = \opendir(UPLOAD)) {
                while (false !== ($f = \readdir($handle))) {
                    if ($f != "." && $f != "..") {
                        
                        // exclude thumbnails
                        if (\str_starts_with($f, self::THUMB_PREFIX)) {
                            continue;
                        }
                        
                        $thumbnails = [];
                        $info = \pathinfo($f);
                        if (\is_array($info)) {
                            $fn = $info['filename'];
                            $ext = $info['extension'] ?? '';

                            // check for the thumbnails
                            foreach (self::THUMBS_CREATE as $w) {
                                $file = UPLOAD . DS
                                    . self::THUMB_PREFIX . $w . self::THUMB_POSTFIX
                                    . $f;
                                if (\file_exists($file) && \is_readable($file)) {
                                    $thumbnails[$w] = self::THUMB_PREFIX . $w
                                        . self::THUMB_POSTFIX . $f;
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
                                "name" => $f,
                                "size" => \filesize(UPLOAD . DS . $f),
                                "timestamp" => \filemtime(UPLOAD . DS . $f),
                                "thumbnails" => $thumbnails,
                            ];
                            $count++;
                        }
                    }
                }
                \closedir($handle);
            }
            \ksort($files);
            return $this->writeJsonData(
                [
                    "count" => $count,
                    "files" => array_values($files),
                ],
                $extras
            );
    
        case "clearcache":
            \header('Clear-Site-Data: "cache"');
            $this->addMessage("Browser cache cleared");
            $this->setLocation();

        case "clearcookies":
            \header('Clear-Site-Data: "cookies"');
            $this->addMessage("Browser cookies cleared");
            $this->setLocation();

        case "clearbrowser":
            \header('Clear-Site-Data: "cache", "cookies", "storage"');
            $this->addMessage("Browser storage cleared");
            $this->setLocation();

        case "AuditLog":
            $this->checkPermission("admin");
            $this->setHeaderHTML();
            $filename = DATA . DS . "AuditLog.txt";
            $file = \popen("tac $filename", 'r');
            $c = 0;
            $logs = [];
            if (\is_resource($file)) {
                while (($s = \fgets($file)) && ($c < 100)) {
                    $logs[] = $s;
                    $c++;
                }
            }
            \array_walk($logs, array($this, 'decorateLogs'));
            $data["content"] = $logs;
            return $this->setData(
                "output", $this->setData($data)->renderHTML("auditlog")
            );

        case "GetCsvInfo":
            $this->checkPermission("admin");
            $arr = \array_merge($cfg["locales"] ?? [], $cfg["app_data"] ?? []);
            foreach ($arr as $k => $v) {
                if (!$k || !$v) {
                    continue;
                }
                if (\file_exists(DATA . DS . "{$k}.csv")
                    && \is_readable(DATA . DS . "{$k}.csv")
                ) {
                    $arr[$k] = [
                        "csv" => $v,
                        "lines" => $this->getFileLines(DATA . DS . "{$k}.csv"),
                        "sheet" => $cfg["lasagna_sheets"][$k] ?? null,
                        "timestamp" => \filemtime(DATA . DS . "{$k}.csv"),
                    ];
                    if ($arr[$k]["lines"] === -1) {
                        unset($arr[$k]);
                    }
                }
            }
            return $this->writeJsonData($arr, $extras);

        case "GetArticlesInfo":
            $this->checkPermission("admin");
            $data = [];
            $profile = "default";
            $f = DATA . DS. "summernote_articles_{$profile}.txt";
            if (\file_exists($f) && \is_readable($f)) {
                $data = @\file(
                    $f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
                );
                if (\is_array($data)) {
                    $data = \array_unique($data, SORT_LOCALE_STRING);
                } else {
                    $data = null;
                }
            }
            return $this->writeJsonData($data, $extras);

        case "GetUpdateToken":
            $this->checkPermission("admin");
            if (!$key = $this->readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $code = "";
            $user = $this->getCurrentUser();
            if (($user["id"] ?? null)) {
                $hashid = \hash("sha256", $user["id"]);
                $code = $data["base"]
                    . "admin/CoreUpdateRemote?user="
                    . $hashid
                    . "&token="
                    . \hash("sha256", $key . $hashid);
                $name = $user["name"] ?? "Unknown user";
                $this->addMessage("Get UPDATE TOKEN");
                $this->addAuditMessage("Get UPDATE TOKEN");
                return $this->writeJsonData($code, $extras);
            }
            $this->unauthorizedAccess();

        case "RebuildAdminKeyRemote":
            if (!$key = $this->readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $token = $_GET["token"] ?? null;
            $user = $_GET["user"] ?? null;
            if ($user && $token || $this->isLocalAdmin()) {
                $code = \hash("sha256", $key . $user);
                if ($code == $token || $this->isLocalAdmin()) {
                    $this->rebuildAdminKey();
                    $this->addMessage("REMOTE FN: ADMIN KEY REBUILT");
                    $this->addAuditMessage("REMOTE FN: ADMIN KEY REBUILT");
                    return $this->writeJsonData(
                        [
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras
                    );
                }
            }
            $this->unauthorizedAccess();

        case "FlushCacheRemote":
            if (!$key = $this->readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $token = $_GET["token"] ?? null;
            $user = $_GET["user"] ?? null;
            if ($user && $token || $this->isLocalAdmin()) {
                $code = \hash("sha256", $key . $user);
                if ($code == $token || $this->isLocalAdmin()) {
                    $this->flushCache();
                    $this->addAuditMessage("REMOTE FN: CACHE FLUSHED");
                    return $this->writeJsonData(
                        [
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras
                    );
                }
            }
            $this->unauthorizedAccess();

        case "CoreUpdateRemote":
            if (!$key = $this->readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $token = $_GET["token"] ?? null;
            $user = $_GET["user"] ?? null;
            if ($user && $token || $this->isLocalAdmin()) {
                $code = \hash("sha256", $key . $user);
                if ($code == $token || $this->isLocalAdmin()) {
                    $this->setForceCsvCheck(true);
                    $this->postloadAppData("app_data");
                    $this->flushCache();
                    $this->addAuditMessage("REMOTE FN: CORE UPDATED");
                    return $this->writeJsonData(
                        [
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras
                    );
                }
            }
            $this->unauthorizedAccess();

        case "RebuildNonceRemote":
            if (!$key = $this->readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $token = $_GET["token"] ?? null;
            $user = $_GET["user"] ?? null;
            if ($user && $token || $this->isLocalAdmin()) {
                $code = \hash("sha256", $key . $user);
                if ($code == $token || $this->isLocalAdmin()) {
                    $this->rebuildNonce();
                    $this->addMessage("REMOTE FN: NEW NONCE");
                    $this->addAuditMessage("REMOTE FN: NEW NONCE");
                    return $this->writeJsonData(
                        [
                            "function" => $view,
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras
                    );
                }
            }
            $this->unauthorizedAccess();

        case "RebuildSecureKeyRemote":
            if (!$key = $this->readAdminKey()) {
                return $this->writeJsonData(500, $extras);
            }
            $token = $_GET["token"] ?? null;
            $user = $_GET["user"] ?? null;
            if ($user && $token || $this->isLocalAdmin()) {
                $code = hash("sha256", $key . $user);
                if ($code == $token || $this->isLocalAdmin()) {
                    $this->rebuildSecureKey();
                    $this->addMessage("REMOTE FN: NEW SECURE KEY");
                    $this->addAuditMessage("REMOTE FN: NEW SECURE KEY");
                    return $this->writeJsonData(
                        [
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => $view,
                            "message" => "OK",
                        ], $extras
                    );
                }
            }
            $this->unauthorizedAccess();

        case "FlushCache":
            $this->checkPermission("admin");
            $this->flushCache();
            return $this->writeJsonData(["status" => "OK"], $extras);

        case "CoreUpdate":
            $this->checkPermission("admin");
            $this->setForceCsvCheck(true);
            $this->postloadAppData("app_data");
            $this->flushCache();
            return $this->writeJsonData(["status" => "OK"], $extras);

        case "UpdateArticles":
            $this->checkPermission("admin");
            $x = 0;
            $profile = "default";
            if (isset($_POST["data"])) {
                $data = (string) \trim((string) $_POST["data"]);
                // remove all extra whitespace
                $data_nows = \preg_replace('/\s\s+/', ' ', (string) $_POST["data"]);
                $x++;
            }
            if (isset($_POST["path"])) {
                $path = \trim((string) $_POST["path"]);
                // URL path
                if (\strlen($path)) {
                    $x++;
                }
            }
            if (isset($_POST["hash"])) {
                $hash = \trim((string) $_POST["hash"]);
                // SHA 256 hexadecimal
                if (\strlen($hash) == 64) {
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
            if (\file_exists(DATA . DS. "summernote_{$profile}_{$hash}.json")
                && \is_readable(DATA . DS. "summernote_{$profile}_{$hash}.json")
            ) {
                if (@\copy(
                    DATA . DS . "summernote_{$profile}_{$hash}.json",
                    DATA . DS . "summernote_{$profile}_{$hash}.bak"
                ) === false
                ) {
                    $this->addError("Article $path backup failed.");
                    $this->addAuditMessage("Article $path backup failed.");
                    return $this->writeJsonData(
                        [
                            "code" => 401,
                            "status" => "Article backup failed.",
                            "profile" => $profile,
                            "hash" => $hash,
                        ], $extras
                    );
                };
            }
            if (@\file_put_contents(
                DATA . DS . "summernote_{$profile}_{$hash}.db",
                $data_nows . "\n", LOCK_EX | FILE_APPEND
            ) === false
            ) {
                $this->addError("Article $path history write failed.");
                $this->addAuditMessage("Article $path history write failed.");
                return $this->writeJsonData(
                    [
                        "code" => 401,
                        "status" => "Article write to history file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras
                );
            };
            if (@\file_put_contents(
                DATA . DS . "summernote_{$profile}_{$hash}.json", $data, LOCK_EX
            ) === false
            ) {
                $this->addError("Article $path write to file failed.");
                $this->addAuditMessage("Article $path write to file failed.");
                return $this->writeJsonData(
                    [
                        "code" => 500,
                        "status" => "Article write to file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras
                );
            }
            // save article meta data
            $p = [];
            $f = DATA . DS . "summernote_articles_{$profile}.txt";
            if (\file_exists($f) && \is_readable($f)) {
                $p = @\file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } else {
                $p = [];
            }
            if (\is_array($p)) {
                $p[] = $path;
            } else {
                $extras['error_descriptions'] = 'incorrect metadata';
                return $this->writeJsonData(500, $extras);
            }
            \sort($p, SORT_LOCALE_STRING);
            $p = \array_unique($p, SORT_LOCALE_STRING);
            \file_put_contents($f, \implode("\n", $p), LOCK_EX);
            $this->addMessage("UPDATE ARTICLE $profile - $path - $hash");
            return $this->writeJsonData(
                [
                    "status" => "OK",
                    "profile" => $profile,
                    "hash" => $hash,
                ], $extras
            );

        default:
            $this->unauthorizedAccess();
        }
        return $this;
    }

    /**
     * Rebuild the identity nonce
     *
     * @return object
     */
    public function rebuildNonce()
    {
        if (\file_exists(DATA . DS . self::IDENTITY_NONCE)) {
            @\unlink(DATA . DS . self::IDENTITY_NONCE);
        }
        \clearstatcache();
        return $this->setIdentity();
    }

    /**
     * Check if call is made by a local administrator
     *
     * @return boolean is there a local administrator?
     */
    public function isLocalAdmin()
    {
        if (CLI) {
            return true;
        }
        $ip = $_SERVER["REMOTE_ADDR"];
        switch ($ip) {
        case "127.0.0.1":
            return true;
        break;
        }
        $key = $this->readAdminKey();
        $gkey = $_GET["key"] ?? null;
        if ($key && $key == $gkey) {
            // GET ?key is same as the admin key
            return true;
        }
        return false;
    }

    /**
     * Rebuild the admin key
     *
     * @return self
     */
    public function rebuildAdminKey()
    {
        if (\file_exists(DATA . DS . self::ADMIN_KEY)) {
            @\unlink(DATA . DS . self::ADMIN_KEY);
        }
        return $this->createAdminKey();
    }

    /**
     * Rebuild the secure key
     *
     * @return object
     */
    public function rebuildSecureKey()
    {
        $key = $this->getCfg("secret_cookie_key") ?? "secure.key";
        if (!\is_string($key)) {
            ErrorPresenter::getInstance()->process(500);
        }
        if (\is_string($key)) {
            $key = \trim($key, '/.');
            if (\file_exists(DATA . DS . $key)) {
                @\unlink(DATA . DS . $key);
            }
        }
        \clearstatcache();
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
        $lock = $factory->createLock("core-update");
        if ($lock->acquire()) {
            try {
                @\ob_flush();
                if (\is_array($this->getData("cache_profiles"))) {
                    foreach ($this->getData("cache_profiles") as $k => $v) {
                        Cache::clear($k);
                        Cache::clear("{$k}_file");
                        if (CLI) {
                            echo "🔪 $k\n";
                            echo "🔪 {$k}_file\n";
                        }
                    }
                }
                \clearstatcache();
                if (CLI) {
                    echo "🔪 " . CACHE . " ...\n";
                }
                // phpcs:ignore
                /** @phpstan-ignore-next-line */
                \array_map("unlink", \glob(CACHE . DS . "*.php"));
                // phpcs:ignore
                /** @phpstan-ignore-next-line */
                \array_map("unlink", \glob(CACHE . DS . "*.tmp"));
                // phpcs:ignore
                /** @phpstan-ignore-next-line */
                \array_map("unlink", \glob(CACHE . DS . CACHEPREFIX . "*"));
                \clearstatcache();
                if (!LOCALHOST) {
                    $this->cloudflarePurgeCache($this->getCfg("cf"));
                }
                $this->checkLocales();
            } finally {
                @\touch(DATA . DS . "_default_cache_flushed_");
                @\touch(DATA . DS . "_admin_cache_flushed_");
                $lock->release();
            }
        } else {
            if (CLI) {
                echo "ERROR: lock cannot be acquired";
            }
            $this->setLocation("/err/429");
            exit;
        }
        return $this;
    }

    /**
     * End program execution with HTTP error 401
     * 
     * @return void
     */
    public function unauthorizedAccess()
    {
        if (CLI) {
            echo "Unauthorized access!\n";
            exit;
        }
        $this->setLocation("/err/401");
        exit;
    }

    /**
     * Decorate log entries
     *
     * @param string $val log line
     * @param int    $key array index
     * 
     * @return void
     */
    public function decorateLogs(&$val, $key)
    {
        $x = \explode(";", $val);
        // remove column 6
        unset($x[5]);
        \array_walk(
            $x, function (&$value, $key) {
                $value = \str_replace("IP:", "", $value);
                $value = \str_replace("EMAIL:", "", $value);
                $value = \str_replace("NAME:", "", $value);
            }
        );
        $y = \implode("</td><td>", $x);
        $z = "<td>$y</td>";
        for ($i = 1; $i <= 5; $i++) {
            // add 5 column classes
            if (\is_string($z)) {
                $z = \preg_replace("/<td>/", "<td class='alogcol{$i}'>", $z, 1);
            }
        }
        $val = $z;
    }

    /**
     * Create the admin key
     *
     * @return self
     */
    public function createAdminKey()
    {
        $f = DATA . DS . self::ADMIN_KEY;
        if (!\file_exists($f)) {
            if (!\file_put_contents(
                $f, \hash("sha256", \random_bytes(256) . \time())
            )
            ) {
                $this->addError("CREATE ADMIN KEY: Internal Server Error");
                $this->setLocation("/err/500");
                exit;
            }
            @\chmod($f, 0660);
            $this->addMessage("ADMIN: keyfile created");
            $this->addAuditMessage("ADMIN: keyfile created");
        }
        return $this;
    }

    /**
     * Read the admin key
     *
     * @return mixed admin key or null if not found
     */
    public function readAdminKey()
    {
        $f = DATA . DS . self::ADMIN_KEY;
        if (\file_exists($f) && \is_readable($f)) {
            $key = \trim(@\file_get_contents($f) ?: '');
        } else {
            $this->createAdminKey();
            $key = \trim(@\file_get_contents($f) ?: '');
        }
        if (strlen($key) > 0) {
            return $key;
        }
        return null;
    }

    /**
     * Create thumbnail from the source image
     *
     * @param string $src  file source
     * @param string $dest file target
     * @param mixed  $tw   output width
     * @param mixed  $th   output height or null
     * 
     * @return mixed image conversion call result
     */
    public function createThumbnail($src, $dest,
        $tw = null, $th = null
    ) {
        $type = \exif_imagetype($src);
        if (!$type) {
            return null;
        }
        if (!\array_key_exists($type, self::IMAGE_HANDLERS)) {
            return null;
        }
        $image = \call_user_func(self::IMAGE_HANDLERS[$type]["load"], $src);
        if (!$image) {
            return null;
        }
        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        $width = \imagesx($image);
        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        $height = \imagesy($image);
        if ($tw == null) {
            $tw = $width;
        }
        if ($th == null) {
            $ratio = $width / $height;
            if ($width > $height) {
                $th = \floor($tw / $ratio);
            } else {
                $th = $tw;
                $tw = \floor($tw * $ratio);
            }
        }
        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        $thmb = \imagecreatetruecolor($tw, $th);
        if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {
            // phpcs:ignore
            /** @phpstan-ignore-next-line */
            \imagecolortransparent($thmb, \imagecolorallocate($thmb, 0, 0, 0));
            if ($type == IMAGETYPE_PNG) {
                // phpcs:ignore
                /** @phpstan-ignore-next-line */
                \imagealphablending($thmb, false);
                // phpcs:ignore
                /** @phpstan-ignore-next-line */
                \imagesavealpha($thmb, true);
            }
        }
        // phpcs:ignore
        /** @phpstan-ignore-next-line */
        \imagecopyresampled($thmb, $image, 0, 0, 0, 0, $tw, $th, $width, $height);
        return \call_user_func(
            self::IMAGE_HANDLERS[$type]["save"],
            $thmb,
            $dest,
            self::IMAGE_HANDLERS[$type]["quality"]
        );
    }

    /**
     * Get number of lines in a file
     *
     * @param string $f filename
     * 
     * @return int number of lines / -1 if non-existent
     */
    public function getFileLines($f)
    {
        try {
            if (!\file_exists($f)) {
                return -1;
            }
            $file = new \SplFileObject($f, "r");
            $file->seek(PHP_INT_MAX);
            return $file->key() + 1;
        } catch (\Exception $e) {
            return -1;
        }
    }
}
