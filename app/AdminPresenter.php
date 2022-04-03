<?php
/**
 * GSC Tesseract
 *
 * @author   Fred Brooker <git@gscloud.cz>
 * @category Framework
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
 * @package GSC
 */
class AdminPresenter extends APresenter
{
    /** @var string admin key filename */
    const ADMIN_KEY = "admin.key";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX32 = ".thumb_32px_";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX50 = ".thumb_50px_";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX64 = ".thumb_64px_";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX128 = ".thumb_128px_";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX150 = ".thumb_150px_";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX320 = ".thumb_320px_";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX512 = ".thumb_512px_";

    /** @var array image constants by image type */
    const IMAGE_HANDLERS = [
        IMAGETYPE_JPEG => [
            "load" => "imagecreatefromjpeg",
            "save" => "imagejpeg",
            "quality" => 50,
        ],
        IMAGETYPE_PNG => [
            "load" => "imagecreatefrompng",
            "save" => "imagepng",
            "quality" => 6,
        ],
        IMAGETYPE_GIF => [
            "load" => "imagecreatefromgif",
            "save" => "imagegif",
        ],
        IMAGETYPE_WEBP => [
            "load" => "imagecreatefromwebp",
            "save" => "imagewebp",
            "quality" => 50,
        ],
        IMAGETYPE_BMP => [
            "load" => "imagecreatefrombmp",
            "save" => "imagebmp",
        ],
    ];

    /**
     * Controller processor
     *
     * @return self
     */
    public function process()
    {
        $cfg = $this->getCfg();
        $data = $this->getData();
        $match = $this->getMatch();
        $view = $match["params"]["p"] ?? null;
        $data["user"] = $this->getCurrentUser();
        $data["admin"] = $g = $this->getUserGroup();
        if ($g) {
            $data["admin_group_${g}"] = true; // for templating
        }
        $extras = [
            "fn" => $view,
            "ip" => $this->getIP(),
            "name" => "Tesseract Admin",
            "override" => (bool) $this->isLocalAdmin(), // override by ?key= parameter
        ];

        /**
         * Get number of lines in a file
         *
         * @param string $f filename
         * @return int number of lines / -1 if non-existent
         */
        function getFileLines($f)
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

        // API calls
        switch ($view) {
            case "upload":
                $this->checkPermission("admin,editor");
                $x = [];
                foreach ($_FILES as $key => &$file) {
                    $b = \strtr(\trim(\basename($file["name"])), " '\"\\", "____");

                    // don't allow thumbnail filenames
                    if (\strpos($b, self::THUMB_PREFIX32) === 0) {
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX50) === 0) {
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX64) === 0) {
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX128) === 0) {
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX150) === 0) {
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX320) === 0) {
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX512) === 0) {
                        continue;
                    }

                    if (@\move_uploaded_file($file["tmp_name"], UPLOAD . DS . $b)) {
                        $x[$b] = \urlencode($b);
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX32 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX32 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX50 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX50 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX64 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX64 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX128 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX128 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX150 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX150 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX320 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX320 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX512 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX512 . $b);
                        }
                        if (stripos($b, ".epub")) {
                            continue;
                        }
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX32 . $b, 32);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX50 . $b, 50);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX64 . $b, 64);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX128 . $b, 128);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX150 . $b, 150);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX320 . $b, 320);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX512 . $b, 512);
                        if (stripos($b, ".webp")) {
                            continue;
                        }
                        $info = pathinfo($b);
                        $c = $info['filename'];
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . $c . '.webp');
                    }
                }
                $this->addMessage("FILES UPLOADED: " . \count($x));
                return $this->writeJsonData($x, $extras);
                break;

            case "UploadedFileDelete":
                $this->checkPermission("admin,editor");
                if (isset($_POST["name"])) {
                    $f1 = UPLOAD . DS . \trim($_POST["name"]); // original file
                    $f2 = UPLOAD . DS . self::THUMB_PREFIX32 . \trim($_POST["name"]);
                    $f3 = UPLOAD . DS . self::THUMB_PREFIX50 . \trim($_POST["name"]);
                    $f4 = UPLOAD . DS . self::THUMB_PREFIX64 . \trim($_POST["name"]);
                    $f5 = UPLOAD . DS . self::THUMB_PREFIX128 . \trim($_POST["name"]);
                    $f6 = UPLOAD . DS . self::THUMB_PREFIX150 . \trim($_POST["name"]);
                    $f7 = UPLOAD . DS . self::THUMB_PREFIX320 . \trim($_POST["name"]);
                    $f8 = UPLOAD . DS . self::THUMB_PREFIX512 . \trim($_POST["name"]);
                    if (\file_exists($f1)) {
                        @\unlink($f1);
                        @\unlink($f2);
                        @\unlink($f3);
                        @\unlink($f4);
                        @\unlink($f5);
                        @\unlink($f6);
                        @\unlink($f7);
                        @\unlink($f8);
                        $this->addMessage("FILE DELETED: $f1 + thumbnails");
                        return $this->writeJsonData($f1, $extras);
                    }
                }
                break;

            case "clearcache":
                \header('Clear-Site-Data: "cache"');
                $this->addMessage("Browser cache cleared");
                $this->setLocation();
                break;

            case "clearcookies":
                \header('Clear-Site-Data: "cookies"');
                $this->addMessage("Browser cookies cleared");
                $this->setLocation();
                break;

            case "clearbrowser":
                \header('Clear-Site-Data: "cache", "cookies", "storage"');
                $this->addMessage("Browser storage cleared");
                $this->setLocation();
                break;

            case "AuditLog":
                $this->checkPermission("admin");
                $this->setHeaderHTML();
                $f = DATA . "/AuditLog.txt";
                $logs = \file($f); // TBD - fix large logs
                \array_walk($logs, array($this, "decorateLogs"));
                $data["content"] = \array_reverse($logs);
                return $this->setData("output", $this->setData($data)->renderHTML("auditlog"));
                break;

            case "GetCsvInfo":
                $this->checkPermission("admin,editor");
                $arr = \array_merge($cfg["locales"] ?? [], $cfg["app_data"] ?? []);
                foreach ($arr as $k => $v) {
                    if (!$k || !$v) {
                        continue;
                    }
                    if (\file_exists(DATA . "/${k}.csv") && \is_readable(DATA . "/${k}.csv")) {
                        $arr[$k] = [
                            "csv" => $v,
                            "lines" => getFileLines(DATA . "/${k}.csv"),
                            "sheet" => $cfg["lasagna_sheets"][$k] ?? null,
                            "timestamp" => \filemtime(DATA . "/${k}.csv"),
                        ];
                        if ($arr[$k]["lines"] === -1) {
                            unset($arr[$k]);
                        }
                    }
                }
                return $this->writeJsonData($arr, $extras);
                break;

            case "GetArticlesInfo":
                $this->checkPermission("admin,editor");
                $data = [];
                $profile = "default";
                if (isset($_GET["profile"])) { // profile ID
                    $profile = \trim((string) $_GET["profile"]);
                }
                $profile = \preg_replace('/[^a-z0-9_-]+/', '', \strtolower($profile)); // fix profile ID
                if ($profile) {
                    $f = DATA . "/summernote_articles_${profile}.txt";
                    if (\file_exists($f) && \is_readable($f)) {
                        $data = @\file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $data = \array_unique($data, SORT_LOCALE_STRING);
                    }
                }
                return $this->writeJsonData($data, $extras);
                break;

            case "GetUploadFileInfo":
                $this->checkPermission("admin,editor");
                $files = [];
                if ($handle = \opendir(UPLOAD)) {
                    while (false !== ($entry = \readdir($handle))) {
                        if ($entry != "." && $entry != "..") {
                            if (\strpos($entry, self::THUMB_PREFIX32) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX50) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX64) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX128) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX150) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX320) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX512) === 0) {
                                continue;
                            }
                            $thumbnail32 = null;
                            $thumbnail50 = null;
                            $thumbnail64 = null;
                            $thumbnail128 = null;
                            $thumbnail150 = null;
                            $thumbnail320 = null;
                            $thumbnail512 = null;
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX32 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX32 . $entry)) {
                                $thumbnail32 = "/upload/" . self::THUMB_PREFIX32 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX50 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX50 . $entry)) {
                                $thumbnail50 = "/upload/" . self::THUMB_PREFIX50 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX64 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX64 . $entry)) {
                                $thumbnail64 = "/upload/" . self::THUMB_PREFIX64 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX128 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX128 . $entry)) {
                                $thumbnail128 = "/upload/" . self::THUMB_PREFIX128 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX150 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX150 . $entry)) {
                                $thumbnail150 = "/upload/" . self::THUMB_PREFIX150 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX320 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX320 . $entry)) {
                                $thumbnail320 = "/upload/" . self::THUMB_PREFIX320 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX512 . $entry) && \is_readable(UPLOAD . DS . self::THUMB_PREFIX512 . $entry)) {
                                $thumbnail512 = "/upload/" . self::THUMB_PREFIX512 . $entry;
                            }
                            $files[$entry] = [
                                "name" => $entry,
                                "size" => \filesize(UPLOAD . DS . $entry),
                                "thumbnail" => $thumbnail50,
                                "thumbnail32" => $thumbnail32,
                                "thumbnail50" => $thumbnail50,
                                "thumbnail64" => $thumbnail64,
                                "thumbnail128" => $thumbnail128,
                                "thumbnail150" => $thumbnail150,
                                "thumbnail320" => $thumbnail320,
                                "thumbnail512" => $thumbnail512,
                                "timestamp" => \filemtime(UPLOAD . DS . $entry),
                            ];
                        }
                    }
                    \closedir($handle);
                }
                \ksort($files);
                return $this->writeJsonData($files, $extras);
                break;

            case "GetPSInsights":
                $this->checkPermission("admin");
                $base = \urlencode($cfg["canonical_url"] ?? "https://" . $_SERVER["SERVER_NAME"]);
                $key = $this->getCfg("google.pagespeedinsights_key") ?? "";
                if (!$key) {
                    return $this->writeJsonData(401, $extras); // unauthorized
                }
                $hash = \hash("sha256", $base);
                $uri = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=${base}&key=${key}";
                $f = "PageSpeed_Insights_${hash}";
                if (!$data = Cache::read($f, "minute")) { // read data from Google
                    if ($data = @\file_get_contents($uri)) {
                        Cache::write($f, $data, "minute");
                    } else {
                        return $this->writeJsonData(500, $extras); // error
                    }
                }
                return $this->writeJsonData(json_decode($data), $extras);
                break;

            case "GetUpdateToken":
                $this->checkPermission("admin");
                if (!$key = $this->readAdminKey()) {
                    return $this->writeJsonData(500, $extras); // error
                }
                $code = "";
                $user = $this->getCurrentUser();
                if ($user["id"] ?? null && $key) {
                    $hashid = \hash("sha256", $user["id"]);
                    $code = $data["base"] . "admin/CoreUpdateRemote?user=" . $hashid . "&token=" . \hash("sha256", $key . $hashid);
                    $name = $user["name"] ?? "Unknown user";
                    $this->addMessage("[$name] got UPDATE TOKEN");
                    $this->addAuditMessage("[$name] got UPDATE TOKEN");
                    return $this->writeJsonData($code, $extras);
                }
                $this->unauthorizedAccess();
                break;

            case "RebuildAdminKeyRemote":
                if (!$key = $this->readAdminKey()) {
                    return $this->writeJsonData(500, $extras); // error
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key || $this->isLocalAdmin()) {
                    $code = \hash("sha256", $key . $user);
                    if ($code == $token || $this->isLocalAdmin()) {
                        $this->rebuildAdminKey();
                        $this->addMessage("REMOTE -> ADMIN KEY REBUILT");
                        $this->addAuditMessage("REMOTE -> ADMIN KEY REBUILT");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "FlushCacheRemote":
                if (!$key = $this->readAdminKey()) {
                    return $this->writeJsonData(500, $extras); // error
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key || $this->isLocalAdmin()) {
                    $code = \hash("sha256", $key . $user);
                    if ($code == $token || $this->isLocalAdmin()) {
                        $this->flushCache();
                        $this->addAuditMessage("REMOTE -> CACHE FLUSHED");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "CoreUpdateRemote":
                if (!$key = $this->readAdminKey()) {
                    return $this->writeJsonData(500, $extras); // error
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key || $this->isLocalAdmin()) {
                    $code = \hash("sha256", $key . $user);
                    if ($code == $token || $this->isLocalAdmin()) {
                        $this->setForceCsvCheck(true);
                        $this->postloadAppData("app_data");
                        $this->flushCache();
                        $this->addAuditMessage("REMOTE -> CORE UPDATED");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "RebuildNonceRemote":
                if (!$key = $this->readAdminKey()) {
                    return $this->writeJsonData(500, $extras); // error
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key || $this->isLocalAdmin()) {
                    $code = \hash("sha256", $key . $user);
                    if ($code == $token || $this->isLocalAdmin()) {
                        $this->rebuildNonce();
                        $this->addMessage("REMOTE -> NEW NONCE");
                        $this->addAuditMessage("REMOTE -> NEW NONCE");
                        return $this->writeJsonData([
                            "function" => $view,
                            "host" => $_SERVER["HTTP_HOST"],
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "RebuildSecureKeyRemote":
                if (!$key = $this->readAdminKey()) {
                    return $this->writeJsonData(500, $extras); // error
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key || $this->isLocalAdmin()) {
                    $code = hash("sha256", $key . $user);
                    if ($code == $token || $this->isLocalAdmin()) {
                        $this->rebuildSecureKey();
                        $this->addMessage("REMOTE -> NEW SECURE KEY");
                        $this->addAuditMessage("REMOTE -> NEW SECURE KEY");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => $view,
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "FlushCache":
                $this->checkPermission("admin,editor");
                $this->flushCache();
                return $this->writeJsonData(["status" => "OK"], $extras);
                break;

            case "CoreUpdate":
                $this->checkPermission("admin,editor");
                $this->setForceCsvCheck(true);
                $this->postloadAppData("app_data");
                $this->flushCache();
                return $this->writeJsonData(["status" => "OK"], $extras);
                break;

            case "UpdateArticles":
                $this->checkPermission("admin,editor");
                $x = 0;
                if (isset($_POST["data"])) {
                    $data = (string) \trim((string) $_POST["data"]);
                    $data_nows = \preg_replace('/\s\s+/', ' ', (string) $_POST["data"]); // remove all extra whitespace
                    $x++;
                }
                if (isset($_POST["profile"])) {
                    $profile = \trim((string) $_POST["profile"]);
                    $profile = \preg_replace('/[^a-z0-9_-]+/', '', \strtolower($profile)); // fix profile ID
                    if (\strlen($profile)) { // profile ID
                        $x++;
                    }
                }
                if (isset($_POST["path"])) {
                    $path = \trim((string) $_POST["path"]);
                    if (\strlen($path)) { // URL path
                        $x++;
                    }
                }
                if (isset($_POST["hash"])) {
                    $hash = \trim((string) $_POST["hash"]);
                    if (\strlen($hash) == 64) { // SHA 256 hexadecimal
                        $x++;
                    }
                }
                if ($x != 4) {
                    return $this->writeJsonData(500, $extras); // error 500
                }
                if (\file_exists(DATA . "/summernote_${profile}_${hash}.json") && \is_readable(DATA . "/summernote_${profile}_${hash}.json")) {
                    if (@\copy(DATA . "/summernote_${profile}_${hash}.json", DATA . "/summernote_${profile}_${hash}.bak") === false) {
                        $this->addError("Article $path copy to backup file failed.");
                        $this->addAuditMessage("Article $path copy to backup file failed.");
                        return $this->writeJsonData([ // error
                            "code" => 401,
                            "status" => "Article copy to backup file failed.",
                            "profile" => $profile,
                            "hash" => $hash,
                        ], $extras);
                    };
                }
                if (@\file_put_contents(DATA . "/summernote_${profile}_${hash}.db", $data_nows . "\n", LOCK_EX | FILE_APPEND) === false) {
                    $this->addError("Article $path write to history file failed.");
                    $this->addAuditMessage("Article $path write to history file failed.");
                    return $this->writeJsonData([ // error
                        "code" => 401,
                        "status" => "Article write to history file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras);
                };
                if (@\file_put_contents(DATA . "/summernote_${profile}_${hash}.json", $data, LOCK_EX) === false) {
                    $this->addError("Article $path write to file failed.");
                    $this->addAuditMessage("Article $path write to file failed.");
                    return $this->writeJsonData([ // error
                        "code" => 500,
                        "status" => "Article write to file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras);
                }
                // save article meta data
                $p = [];
                $f = DATA . "/summernote_articles_${profile}.txt";
                if (\file_exists($f) && \is_readable($f)) {
                    $p = @\file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }
                $p[] = $path;
                \sort($p, SORT_LOCALE_STRING);
                $p = \array_unique($p, SORT_LOCALE_STRING);
                \file_put_contents($f, \implode("\n", $p), LOCK_EX);
                // OK
                $this->addMessage("UPDATE ARTICLE $profile - $path - $hash");
                //$this->addAuditMessage("Article $profile - $path updated");
                return $this->writeJsonData([
                    "status" => "OK",
                    "profile" => $profile,
                    "hash" => $hash,
                ], $extras);
                break;

            default:
                $this->unauthorizedAccess();
                break;
        }
        return $this;
    }

    /**
     * Rebuild the identity nonce
     *
     * @return self
     */
    private function rebuildNonce()
    {
        if (\file_exists(DATA . "/" . self::IDENTITY_NONCE)) {
            @\unlink(DATA . "/" . self::IDENTITY_NONCE);
        }
        \clearstatcache();
        return $this->setIdentity();
    }

    /**
     * Check if call is made by a local administrator
     *
     * @return boolean is there a local administrator?
     */
    private function isLocalAdmin()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        switch ($ip) {
            case "127.0.0.1":
                return true;
                break;
        }
        $key = $this->readAdminKey();
        $gkey = $_GET["key"] ?? null;
        if ($key && $key == $gkey) {
            return true; // GET ?key is same as the admin key
        }
        return false;
    }

    /**
     * Rebuild the admin key
     *
     * @return self
     */
    private function rebuildAdminKey()
    {
        if (\file_exists(DATA . "/" . self::ADMIN_KEY)) {
            @\unlink(DATA . "/" . self::ADMIN_KEY);
        }
        return $this->createAdminKey();
    }

    /**
     * Rebuild the secure key
     *
     * @return self
     */
    private function rebuildSecureKey()
    {
        $key = $this->getCfg("secret_cookie_key") ?? "secure.key"; // secure key
        $key = \trim($key, "/.");
        if (!$key) {
            return $this->writeJsonData(500, $extras); // error
        }
        if (\file_exists(DATA . "/${key}")) {
            @\unlink(DATA . "/${key}");
        }
        \clearstatcache();
        return $this->setIdentity();
    }

    /**
     * Flush the cache
     *
     * @return self
     */
    private function flushCache()
    {
        $store = new FlockStore();
        $factory = new Factory($store);
        $lock = $factory->createLock("core-update");
        if ($lock->acquire()) {
            try {
                @\ob_flush();
                foreach ($this->getData("cache_profiles") as $k => $v) { // clear all cache profiles
                    Cache::clear($k);
                    Cache::clear("${k}_file");
                }
                \clearstatcache();
                \array_map("unlink", \glob(CACHE . "/*.php"));
                \array_map("unlink", \glob(CACHE . "/*.tmp"));
                \array_map("unlink", \glob(CACHE . "/" . CACHEPREFIX . "*"));
                \clearstatcache();
                if (!LOCALHOST) {
                    // purge cache if run on server only
                    $this->CloudflarePurgeCache($this->getCfg("cf"));
                }
                $this->checkLocales();
            } finally {
                $lock->release();
            }
        } else {
            $this->setLocation("/err/429"); // error - cannot acquire a lock
            exit;
        }
        return $this;
    }

    /**
     * End program execution with HTTP error 401
     */
    private function unauthorizedAccess()
    {
        $this->setLocation("/err/401");
        exit;
    }

    /**
     * Decorate log entries
     *
     * @param string $val log line
     * @param int $key array index
     */
    public function decorateLogs(&$val, $key)
    {
        $x = \explode(";", $val);
        unset($x[5]); // remove column 6
        \array_walk($x, function (&$value, $key) { // remove some strings
            $value = \str_replace("IP:", "", $value);
            $value = \str_replace("EMAIL:", "", $value);
            $value = \str_replace("NAME:", "", $value);
        });
        $y = \implode("</td><td>", $x);
        $z = "<td>$y</td>";
        for ($i = 1; $i <= 5; $i++) { // add 5 column classes
            $z = \preg_replace("/<td>/", "<td class='alogcol${i}'>", $z, 1);
        }
        $val = $z;
    }

    /**
     * Create the admin key
     *
     * @return self
     */
    private function createAdminKey()
    {
        $f = DATA . "/" . self::ADMIN_KEY;
        if (!\file_exists($f)) {
            if (!\file_put_contents($f, \hash("sha256", \random_bytes(256) . \time()))) {
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
     * @return string admin key
     */
    private function readAdminKey()
    {
        $f = DATA . "/" . self::ADMIN_KEY;
        if (\file_exists($f) && \is_readable($f)) {
            $key = \trim(@\file_get_contents($f));
        } else {
            $this->createAdminKey();
            $key = \trim(@\file_get_contents($f));
        }
        return $key ?? null;
    }

    /**
     * Create thumbnail from the source image
     *
     * @param $src file source
     * @param $dest file target
     * @param $targetWidth output width
     * @param $targetHeight output height or null
     * @return mixed image conversion call result
     */
    private function createThumbnail($src, $dest, $targetWidth = null, $targetHeight = null)
    {
        $type = \exif_imagetype($src);
        if (!$type || !self::IMAGE_HANDLERS[$type]) {
            return null;
        }
        $image = \call_user_func(self::IMAGE_HANDLERS[$type]["load"], $src);
        if (!$image) {
            return null;
        }
        $width = \imagesx($image);
        $height = \imagesy($image);
        if ($targetWidth == null) {
            $targetWidth = $width;
        }
        if ($targetHeight == null) {
            $ratio = $width / $height;
            if ($width > $height) {
                $targetHeight = \floor($targetWidth / $ratio);
            } else {
                $targetHeight = $targetWidth;
                $targetWidth = \floor($targetWidth * $ratio);
            }
        }
        $thumbnail = \imagecreatetruecolor($targetWidth, $targetHeight);
        if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {
            \imagecolortransparent(
                $thumbnail,
                \imagecolorallocate($thumbnail, 0, 0, 0)
            );
            if ($type == IMAGETYPE_PNG) {
                \imagealphablending($thumbnail, false);
                \imagesavealpha($thumbnail, true);
            }
        }
        \imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        return \call_user_func(
            self::IMAGE_HANDLERS[$type]["save"], $thumbnail, $dest, self::IMAGE_HANDLERS[$type]["quality"]);
    }
}
