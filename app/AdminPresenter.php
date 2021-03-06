<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

use Cake\Cache\Cache;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * Admin Presenter
 */
class AdminPresenter extends APresenter
{
    /** @var string administration token key filename */
    const ADMIN_KEY = "admin.key";

    /** @var string thumbnail prefix */
    const THUMB_PREFIX50 = ".thumb_50px_";
    const THUMB_PREFIX150 = ".thumb_150px_";
    const THUMB_PREFIX320 = ".thumb_320px_";

    /** @var array image constants */
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
     * Main controller
     *
     * @return object Singleton instance
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
            "name" => "Tesseract LASAGNA Admin Module",
            "override" => (bool) $this->isLocalAdmin(),
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
            } catch (\Exception$e) {
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
                    if (\strpos($b, self::THUMB_PREFIX50) === 0) { // don't allow thumbnail filenames
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX150) === 0) { // don't allow thumbnail filenames
                        continue;
                    }
                    if (\strpos($b, self::THUMB_PREFIX320) === 0) { // don't allow thumbnail filenames
                        continue;
                    }
                    if (@\move_uploaded_file($file["tmp_name"], UPLOAD . DS . $b)) {
                        $x[$b] = \urlencode($b);
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX50 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX50 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX150 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX150 . $b);
                        }
                        if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX320 . $b)) {
                            \unlink(UPLOAD . DS . self::THUMB_PREFIX320 . $b);
                        }
                        if (stripos($b, ".epub")) {
                            continue;
                        }
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX50 . $b, 50);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX150 . $b, 150);
                        $this->createThumbnail(UPLOAD . DS . $b, UPLOAD . DS . self::THUMB_PREFIX320 . $b, 320);
                    }
                }
                $c = \count($x);
                $this->addMessage("FILES UPLOADED: $c");
                return $this->writeJsonData($x, $extras);
                break;

            case "UploadedFileDelete":
                $this->checkPermission("admin,editor");
                if (isset($_POST["name"])) {
                    $f1 = UPLOAD . DS . \trim($_POST["name"]); // original file
                    $f2 = UPLOAD . DS . self::THUMB_PREFIX50 . \trim($_POST["name"]); // thumbnail 50 px
                    $f3 = UPLOAD . DS . self::THUMB_PREFIX150 . \trim($_POST["name"]); // thumbnail 150 px
                    $f4 = UPLOAD . DS . self::THUMB_PREFIX320 . \trim($_POST["name"]); // thumbnail 320 px
                    if (\file_exists($f1)) {
                        @\unlink($f1);
                        @\unlink($f2);
                        @\unlink($f3);
                        @\unlink($f4);
                        $this->addMessage("FILE DELETED: $f1");
                        return $this->writeJsonData($f1, $extras);
                    }
                }
                break;

            case "clearcache":
                \header('Clear-Site-Data: "cache"');
                $this->addMessage("BROWSER CACHE CLEARED");
                $this->setLocation();
                break;

            case "clearcookies":
                \header('Clear-Site-Data: "cookies"');
                $this->addMessage("BROWSER COOKIES CLEARED");
                $this->setLocation();
                break;

            case "clearbrowser":
                \header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
                $this->addMessage("BROWSER DATA CLEARED");
                $this->setLocation();
                break;

            case "AuditLog":
                $this->checkPermission("admin");
                $this->setHeaderHTML();
                $f = DATA . "/AuditLog.txt";
                $logs = \file($f);
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
                            if (\strpos($entry, self::THUMB_PREFIX50) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX150) === 0) {
                                continue;
                            }
                            if (\strpos($entry, self::THUMB_PREFIX320) === 0) {
                                continue;
                            }
                            $thumbnail = null;
                            $thumbnail150 = null;
                            $thumbnail320 = null;
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX50 . $entry) && is_readable(UPLOAD . DS . self::THUMB_PREFIX50 . $entry)) {
                                $thumbnail = "/upload/" . self::THUMB_PREFIX50 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX150 . $entry) && is_readable(UPLOAD . DS . self::THUMB_PREFIX150 . $entry)) {
                                $thumbnail150 = "/upload/" . self::THUMB_PREFIX150 . $entry;
                            }
                            if (\file_exists(UPLOAD . DS . self::THUMB_PREFIX320 . $entry) && is_readable(UPLOAD . DS . self::THUMB_PREFIX320 . $entry)) {
                                $thumbnail320 = "/upload/" . self::THUMB_PREFIX320 . $entry;
                            }
                            $files[$entry] = [
                                "name" => $entry,
                                "size" => \filesize(UPLOAD . DS . $entry),
                                "thumbnail" => $thumbnail,
                                "thumbnail150" => $thumbnail150,
                                "thumbnail320" => $thumbnail320,
                                "timestamp" => \filemtime(UPLOAD . DS . $entry),
                            ];
                        }
                    }
                    closedir($handle);
                }
                ksort($files);
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
                    $this->addMessage("GET UPDATE TOKEN");
                    $this->addAuditMessage("GET UPDATE TOKEN");
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
                        $this->addMessage("REBUILD ADMIN KEY REMOTE [$user]");
                        $this->addAuditMessage("REBUILD ADMIN KEY REMOTE [$user]");
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
                        $this->addMessage("FLUSH CACHE REMOTE [$user]");
                        $this->addAuditMessage("FLUSH CACHE REMOTE [$user]");
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
                        $this->addAuditMessage("CORE UPDATE REMOTE [$user]");
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
                        $this->addMessage("REBUILD NONCE REMOTE [$user]");
                        $this->addAuditMessage("REBUILD NONCE REMOTE [$user]");
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
                        $this->addMessage("REBUILD SECURE KEY REMOTE [$user]");
                        $this->addAuditMessage("REBUILD SECURE KEY REMOTE [$user]");
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

            case "CreateAuthCode":
                $this->checkPermission("admin");
                $user = $this->getCurrentUser();
                $hash = null;
                if ($user["id"] ?? null) {
                    $f = DATA . "/identity_" . $user["id"] . ".json";
                    $user["entropy"] = \hash("sha256", \random_bytes(8) . (string) \time()); // random entropy
                    $json = \json_encode($user);
                    $hash = \substr(\hash("sha256", $json), 0, 8); // return 8 chars of SHA256
                    @\file_put_contents($f, $json, LOCK_EX); // @todo check write fail!
                    $this->addMessage("CREATE AUTH CODE");
                    return $this->writeJsonData(["hash" => $hash, "status" => "OK"], $extras);
                }
                return $this->writeJsonData(401, $extras); // error
                break;

            case "DeleteAuthCode":
                $this->checkPermission("admin");
                $user = $this->getCurrentUser();
                if ($user["id"] ?? null) {
                    $f = DATA . "/identity_" . $user["id"] . ".json";
                    if (\file_exists($f)) {
                        @\unlink($f); // delete identity
                    }
                    $this->addMessage("DELETE AUTH CODE");
                    return $this->writeJsonData(["status" => "OK"], $extras);
                }
                return $this->writeJsonData(401, $extras); // error
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
                        $this->addError("Articles copy to backup file failed.");
                        return $this->writeJsonData([ // error
                            "code" => 401,
                            "status" => "Articles copy to backup file failed.",
                            "profile" => $profile,
                            "hash" => $hash,
                        ], $extras);
                    };
                }
                if (@\file_put_contents(DATA . "/summernote_${profile}_${hash}.db", $data_nows . "\n", LOCK_EX | FILE_APPEND) === false) {
                    $this->addError("Articles write to history file failed.");
                    return $this->writeJsonData([ // error
                        "code" => 401,
                        "status" => "Articles write to history file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras);
                };
                if (@\file_put_contents(DATA . "/summernote_${profile}_${hash}.json", $data, LOCK_EX) === false) {
                    $this->addError("Articles write to file failed.");
                    return $this->writeJsonData([ // error
                        "code" => 500,
                        "status" => "Articles write to file failed.",
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
                $this->addMessage("UPDATE ARTICLE $profile - $hash");
                $this->addAuditMessage("UPDATE ARTICLE $profile - $hash");
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
     * Rebuild identity nonce
     *
     * @return object Singleton instance
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
     * Check if we are a local administrator
     *
     * @return boolean are we?
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
            return true;
        }
        return false;
    }

    /**
     * Rebuild admin key
     *
     * @return object Singleton instance
     */
    private function rebuildAdminKey()
    {
        if (\file_exists(DATA . "/" . self::ADMIN_KEY)) {
            @\unlink(DATA . "/" . self::ADMIN_KEY);
        }
        return $this->createAdminKey();
    }

    /**
     * Rebuild secure key
     *
     * @return object Singleton instance
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
     * Flush cache
     *
     * @return object Singleton instance
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
     * Unauthorized access
     *
     * @return void
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
            $z = preg_replace("/<td>/", "<td class='alogcol${i}'>", $z, 1);
        }
        $val = $z;
    }

    /**
     * Create admin key
     *
     * @return object Singleton instance
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
     * Read admin key
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
     * Create thumbnail from source image if possible
     *
     * @param $src file source
     * @param $dest file target
     * @param $targetWidth output width
     * @param $targetHeight output height or null
     */
    private function createThumbnail($src, $dest, $targetWidth, $targetHeight = null)
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
