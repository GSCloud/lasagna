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
    /** @var string Administration token key filename */
    const ADMIN_KEY = "admin.key";

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
        $data["user"] = $this->getCurrentUser();
        $data["admin"] = $g = $this->getUserGroup();
        if ($g) {
            $data["admin_group_${g}"] = true; // for templating
        }
        $view = $match["params"]["p"] ?? null;
        $extras = [
            "name" => "Tesseract LASAGNA Core",
            "fn" => $view,
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
            } catch (Exception $e) {
                return -1;
            }
        }

        // modules
        switch ($view) {
            case "clearbrowserdata":
                header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
                $this->setLocation();
                break;

            case "AuditLog":
                $this->checkPermission("admin");
                $this->setHeaderHTML();
                $f = DATA . "/AuditLog.txt";
                $logs = file($f);
                \array_walk($logs, array($this, "decorateLogs"));
                $data["content"] = \array_reverse($logs);
                return $this->setData("output", $this->setData($data)->renderHTML("auditlog"));
                break;

            case "GetCsvInfo":
                $this->checkPermission("admin");
                $arr = array_merge($cfg["locales"] ?? [], $cfg["app_data"] ?? []);
                foreach ($arr as $k => $v) {
                    if (!$k || !$v) {
                        continue;
                    }
                    $arr[$k] = [
                        "csv" => $v,
                        "lines" => getFileLines(DATA . "/${k}.csv"),
                        "sheet" => $cfg["lasagna_sheets"][$k] ?? null,
                        "timestamp" => \file_exists(DATA . "/${k}.csv") ? @filemtime(DATA . "/${k}.csv") : null,
                    ];
                    if ($arr[$k]["lines"] === -1) {
                        unset($arr[$k]);
                    }
                }
                return $this->writeJsonData($arr, $extras);
                break;

            case "GetPSInsights":
                $this->checkPermission("admin");
                $base = urlencode($cfg["canonical_url"] ?? "https://" . $_SERVER["SERVER_NAME"]);
                $key = $this->getCfg("google.pagespeedinsights_key") ?? "";
                if (!$key) {
                    return $this->writeJsonData(401, $extras); // unauthorized
                }
                $hash = hash("sha256", $base);
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
                $code = "";
                $key = $this->readAdminKey();
                if (!$key) {
                    return $this->writeJsonData(500, $extras); // unauthorized
                }
                $user = $this->getCurrentUser();
                if ($user["id"] ?? null && $key) {
                    $hashid = hash("sha256", $user["id"]);
                    $code = $data["base"] . "admin/CoreUpdateRemote?user=" . $hashid . "&token=" . hash("sha256", $key . $hashid);
                    $this->addAuditMessage("GET UPDATE TOKEN");
                    return $this->writeJsonData($code, $extras);
                }
                $this->unauthorizedAccess();
                break;

            case "RebuildAdminKeyRemote":
                $key = $this->readAdminKey();
                if (!$key) {
                    return $this->writeJsonData(500, $extras); // unauthorized
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key) {
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->rebuildAdminKey();
                        $this->addAuditMessage("REBUILD ADMIN KEY REMOTE [$user]");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => "RebuildAdminKeyRemote",
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "FlushCacheRemote":
                $key = $this->readAdminKey();
                if (!$key) {
                    return $this->writeJsonData(500, $extras); // unauthorized
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key) {
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->flushCache();
                        $this->addAuditMessage("FLUSH CACHE REMOTE [$user]");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => "FlushCacheRemote",
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "CoreUpdateRemote":
                $key = $this->readAdminKey();
                if (!$key) {
                    return $this->writeJsonData(500, $extras); // unauthorized
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key) {
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->setForceCsvCheck();
                        $this->postloadAppData("app_data");
                        $this->flushCache();
                        $this->addAuditMessage("CORE UPDATE REMOTE [$user]");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => "CoreUpdateRemote",
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "RebuildNonceRemote":
                $key = $this->readAdminKey();
                if (!$key) {
                    return $this->writeJsonData(500, $extras); // unauthorized
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key) {
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->rebuildNonce();
                        $this->addAuditMessage("REBUILD NONCE REMOTE [$user]");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => "RebuildNonceRemote",
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "RebuildSecureKeyRemote":
                $key = $this->readAdminKey();
                if (!$key) {
                    return $this->writeJsonData(500, $extras); // unauthorized
                }
                $token = $_GET["token"] ?? null;
                $user = $_GET["user"] ?? null;
                if ($user && $token && $key) {
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->rebuildSecureKey();
                        $this->addAuditMessage("REBUILD SECURE KEY REMOTE [$user]");
                        return $this->writeJsonData([
                            "host" => $_SERVER["HTTP_HOST"],
                            "function" => "RebuildSecureKeyRemote",
                            "message" => "OK",
                        ], $extras);
                    }
                }
                $this->unauthorizedAccess();
                break;

            case "FlushCache":
                $this->checkPermission("admin");
                $this->flushCache();
                $this->addAuditMessage("FLUSH CACHE");
                return $this->writeJsonData(["status" => "OK"], $extras);
                break;

            case "CoreUpdate":
                $this->checkPermission("admin");
                $this->setForceCsvCheck();
                $this->postloadAppData("app_data");
                $this->flushCache();
                $this->addAuditMessage("CORE UPDATE");
                return $this->writeJsonData(["status" => "OK"], $extras);
                break;

            case "CreateAuthCode":
                $this->checkPermission("admin");
                $user = $this->getCurrentUser();
                $hash = null;
                if ($user["id"] ?? null) {
                    $file = DATA . "/identity_" . $user["id"] . ".json";
                    $user["entropy"] = hash("sha256", random_bytes(8) . (string) time()); // random entropy
                    $json = json_encode($user);
                    $hash = substr(hash("sha256", $json), 0, 8); // return 8 chars of SHA256
                    \file_put_contents($file, $json, LOCK_EX); // @todo check write fail!
                    $this->addAuditMessage("CREATE AUTH CODE");
                    return $this->writeJsonData(["hash" => $hash, "status" => "OK"], $extras);
                }
                return $this->writeJsonData(401, $extras); // error
                break;

            case "DeleteAuthCode":
                $this->checkPermission("admin");
                $user = $this->getCurrentUser();
                if ($user["id"] ?? null) {
                    $file = DATA . "/identity_" . $user["id"] . ".json";
                    if (\file_exists($file)) { // delete identity if exists
                        \unlink($file);
                    }
                    $this->addAuditMessage("DELETE AUTH CODE");
                    return $this->writeJsonData(["status" => "OK"], $extras);
                }
                return $this->writeJsonData(401, $extras); // error
                break;

            case "UpdateArticles":
                $this->checkPermission("admin");
                $x = 0;
                if (isset($_POST["data"])) {
                    $data = (string) trim((string) $_POST["data"]);
                    $data_nows = preg_replace('/\s\s+/', ' ', (string) $_POST["data"]); // remove all whitespace
                    $x++;
                }
                if (isset($_POST["profile"])) {
                    $profile = trim((string) $_POST["profile"]);
                    $profile = preg_replace('/[^a-z0-9]+/', '', strtolower($profile)); // allow only alphanumeric
                    if (strlen($profile)) {
                        $x++;
                    }
                }
                if (isset($_POST["hash"])) {
                    $hash = trim((string) $_POST["hash"]);
                    if (strlen($hash) == 64) { // SHA256 hexadecimal
                        $x++;
                    }
                }
                if ($x != 3) {
                    return $this->writeJsonData(400, $extras); // error
                }
                if (\file_exists(DATA . "/summernote_${profile}_${hash}.json")) {
                    if (@\copy(DATA . "/summernote_${profile}_${hash}.json", DATA . "/summernote_${profile}_${hash}.bak") === false) {
                        return $this->writeJsonData([ // error
                            "code" => 500,
                            "status" => "Data copy to backup file failed.",
                            "profile" => $profile,
                            "hash" => $hash,
                        ], $extras);
                    };
                }
                if (@\file_put_contents(DATA . "/summernote_${profile}_${hash}.db", $data_nows . "\n", LOCK_EX | FILE_APPEND) === false) {
                    return $this->writeJsonData([ // error
                        "code" => 500,
                        "status" => "Data write to history file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras);
                };
                if (@\file_put_contents(DATA . "/summernote_${profile}_${hash}.json", $data, LOCK_EX) === false) {
                    return $this->writeJsonData([ // error
                        "code" => 500,
                        "status" => "Data write to file failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], $extras);
                }
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
            unlink(DATA . "/" . self::IDENTITY_NONCE);
        }
        clearstatcache();
        return $this->setIdentity();
    }

    /**
     * Rebuild admin key
     *
     * @return object Singleton instance
     */
    private function rebuildAdminKey()
    {
        if (\file_exists(DATA . "/" . self::ADMIN_KEY)) {
            unlink(DATA . "/" . self::ADMIN_KEY);
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
        $key = trim($key, "/.");
        if (!$key) {
            return $this->writeJsonData(500, $extras); // error
        }
        if (\file_exists(DATA . "/${key}")) {
            unlink(DATA . "/${key}");
        }
        clearstatcache();
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
                @ob_flush();
                foreach ($this->getData("cache_profiles") as $k => $v) { // clear all cache profiles
                    Cache::clear($k);
                    Cache::clear("${k}_file");
                }
                clearstatcache();
                array_map("unlink", glob(CACHE . "/*.php"));
                array_map("unlink", glob(CACHE . "/*.tmp"));
                array_map("unlink", glob(CACHE . "/" . CACHEPREFIX . "*"));
                if (!LOCALHOST) {
                    // purge cache if run on server only
                    $this->CloudflarePurgeCache($this->getCfg("cf"));
                }
                $this->checkLocales();
            } finally {
                $lock->release();
            }
        } else {
            $this->setLocation("/err/429"); // error
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
        $x = explode(";", $val);
        array_walk($x, function (&$value, &$key) {
            $value = str_replace("EMAIL:", "", $value);
            $value = str_replace("NAME:", "", $value);
        });
        unset($x[5]);
        $y = implode("</td><td>", $x);
        $val = "<td>$y</td>";
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
            if (!\file_put_contents($f, hash("sha256", random_bytes(256) . time()))) {
                $this->addError("500: Internal Server Error");
                $this->setLocation("/err/500");
                exit;
            }
            @\chmod($f, 0660);
            $this->addMessage("ADMIN: keyfile created");
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
        if (\file_exists($f)) {
            $key = trim(@\file_get_contents($f));
        } else {
            $this->createAdminKey();
            $key = trim(@\file_get_contents($f));
        }
        return $key ?? null;
    }
}
