<?php

use Cake\Cache\Cache;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class AdminPresenter extends \GSC\APresenter
{
    /** @var string Administration token key filename. */
    const ADMIN_KEY = "admin.key";

    public function process()
    {
        $cfg = $this->getCfg();
        $data = $this->getData();
        $match = $this->getMatch();

        $data["user"] = $this->getCurrentUser();
        $data["admin"] = $this->getUserGroup();
        if ($this->getUserGroup()) {
            $data["admin_group_" . $this->getUserGroup()] = true;
        }

        function getFileLines($f)
        {
            try {
                $file = new \SplFileObject($f, "r");
                $file->seek(PHP_INT_MAX);
                return $file->key() + 1;
            } catch (Exception $e) {
                return -1;
            }
        }

        switch ($match["params"]["p"] ?? null) {

            // GetCsvInfo
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
                        "timestamp" => @filemtime(DATA . "/${k}.csv"),
                    ];
                    if ($arr[$k]["lines"] === -1) {
                        unset($arr[$k]);
                    }

                }
                return $this->writeJsonData($arr, ["name" => "LASAGNA Core", "fn" => "GetCsvInfo"]);
                break;

            // GetCfAnalytics - UNFINISHED -> TODO!!!
            case "GetCfAnalytics":
                $this->checkPermission("admin");
                $cf = $this->getCfg("cf");
                if (!is_array($cf)) {
                    return $this->writeJsonData(null, ["fn" => "GetCfAnalytics"]);
                }
                $email = $cf["email"] ?? null;
                $apikey = $cf["apikey"] ?? null;
                $zoneid = $cf["zoneid"] ?? null;
                $uri = "";
                if ($email && $apikey && $zoneid) {
                    $file = "Cloudflare_Analytics_$zoneid";
                    $results = Cache::read($file, "default");
                    if ($results === false) {
                        $results = @file_get_contents($uri);
                        if ($results !== false) {
                            Cache::write($file, $results, "default");
                        }
                    }
                    return $this->writeJsonData(json_decode($results), ["name" => "LASAGNA Core", "fn" => "GetCfAnalytics"]);
                } else {
                    return $this->writeJsonData(null, ["fn" => "GetCfAnalytics"]);
                }
                break;

            // GetPSInsights
            case "GetPSInsights":
                $this->checkPermission("admin");
                $base = urlencode($cfg["canonical_url"]);
                $key = $this->getCfg("google.pagespeedinsights_key") ?? "NA";
                $uri = "https://www.googleapis.com/pagespeedonline/v4/runPagespeed?url=${base}&key=${key}";
                $hash = hash("sha256", $base);
                $file = "PageSpeed_Insights_$hash";
                $results = Cache::read($file, "default");
                if ($results === false) {
                    $results = @file_get_contents($uri);
                    if ($results !== false) {
                        Cache::write($file, $results, "default");
                    } else {
                        return $this->writeJsonData(null, ["fn" => "GetPSInsights"]);
                    }
                }
                return $this->writeJsonData(json_decode($results), ["name" => "LASAGNA Core", "fn" => "GetPSInsights"]);
                break;

            // GetUpdateToken
            case "GetUpdateToken":
                $this->checkPermission("admin");
                $file = DATA . "/" . self::ADMIN_KEY;
                $key = trim(@file_get_contents($file));
                try {
                    if (!$key) {
                        file_put_contents($file, hash("sha256", random_bytes(256) . time()));
                        @chmod($file, 0660);
                        $this->addMessage("ADMIN: keyfile created");
                    }
                } catch (Exception $e) {
                    $this->addError("500: Internal Server Error");
                    $this->setLocation("/err/500");
                    exit;
                }
                $user = $this->getCurrentUser();
                $arr = "";
                if ($user["id"]) {
                    $hashid = hash("sha256", $user["id"]);
                    $arr = $data["base"] . "admin/CoreUpdateRemote?user=" . $hashid . "&token=" . hash("sha256", $key . $hashid);
                } else {
                    $this->unauthorized_access();
                }
                return $this->writeJsonData($arr, ["name" => "LASAGNA Core", "fn" => "GetUpdateToken"]);
                break;

            // FlushCache
            case "FlushCache":
                $this->checkPermission("admin");
                $this->flush_cache();
                return $this->writeJsonData(["status" => "OK"], ["name" => "LASAGNA Core", "fn" => "FlushCache"]);
                break;

            // CoreUpdate
            case "CoreUpdate":
                $this->checkPermission("admin");
                $this->setForceCsvCheck();
                $this->postloadAppData("app_data");
                $this->flush_cache();
                return $this->writeJsonData(["status" => "OK"], ["name" => "LASAGNA Core", "fn" => "CoreUpdate"]);
                break;

            // UpdateArticles
            case "UpdateArticles":
                $this->checkPermission("admin");
                $x = 0;
                if (isset($_POST["data"])) {
//                    $data = preg_replace('/\s\s+/', ' ', (string) $_POST["data"]); // remove whitespace
                    $data = (string) trim((string) $_POST["data"]);
                    $x++;
                }
                if (isset($_POST["profile"])) {
                    $profile = trim((string) $_POST["profile"]);
                    $profile = preg_replace('/[^a-z0-9]+/', '', strtolower($profile));  // only alphanumeric
                    if (strlen($profile)) {
                        $x++;
                    }
                }
                if (isset($_POST["hash"])) {
                    $hash = trim((string) $_POST["hash"]);
                    if (strlen($hash) == 64) {  // SHA256 hexadecimal
                        $x++;
                    }
                }
                if ($x != 3) {
                    return $this->writeJsonData(400, ["name" => "LASAGNA Core", "fn" => "UpdateArticles"]);
                }
                @copy(DATA . "/summernote_${profile}_${hash}.json", DATA . "/summernote_${profile}_${hash}.bak");
                if (@file_put_contents(DATA . "/summernote_${profile}_${hash}.json", $data, LOCK_EX) === false) {
                    return $this->writeJsonData([
                        "status" => "Data write failed.",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], ["name" => "LASAGNA Core", "fn" => "UpdateArticles", "code" => 500]);
                } else {
                    return $this->writeJsonData([
                        "status" => "OK",
                        "profile" => $profile,
                        "hash" => $hash,
                    ], ["name" => "LASAGNA Core", "fn" => "UpdateArticles"]);
                }
                break;

            // DELETE ARTICLES
            case "DeleteArticles":
                $this->checkPermission("admin");
                return $this->writeJsonData(500, ["name" => "LASAGNA Core", "fn" => "DeleteArticles"]);
                break;

            // FLUSH remote
            case "FlushCacheRemote":
                $user = $_GET["user"] ?? null;
                $token = $_GET["token"] ?? null;
                if ($user && $token) {
                    $file = DATA . "/" . self::ADMIN_KEY;
                    $key = trim(@file_get_contents($file));
                    if (!$key) {
                        $this->unauthorized_access();
                    }
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->flush_cache();
                        echo $_SERVER["HTTP_HOST"] . " FlushCacheRemote OK \n";
                        exit;
                    } else {
                        $this->unauthorized_access();
                    }
                }
                break;

            // UPDATE remote
            case "CoreUpdateRemote":
                $user = $_GET["user"] ?? null;
                $token = $_GET["token"] ?? null;
                if ($user && $token) {
                    $file = DATA . "/" . self::ADMIN_KEY;
                    $key = trim(@file_get_contents($file));
                    if (!$key) {
                        $this->unauthorized_access();
                    }
                    $code = hash("sha256", $key . $user);
                    if ($code == $token) {
                        $this->setForceCsvCheck();
                        $this->postloadAppData("app_data");
                        $this->flush_cache();
                        echo $_SERVER["HTTP_HOST"] . " CoreUpdateRemote OK \n";
                        exit;
                    } else {
                        $this->unauthorized_access();
                    }
                }
                break;

            default:
                $this->unauthorized_access();
                break;

        }
        return $this;
    }

    private function flush_cache()
    {
        $store = new FlockStore();
        $factory = new Factory($store);
        $lock = $factory->createLock("core-update", 10);
        if ($lock->acquire()) {
            try {
                ob_flush();
                Cache::clear(false);
                @array_map("unlink", glob(CACHE . "/*.php"));
                @array_map("unlink", glob(CACHE . "/*.tmp"));
                @array_map("unlink", glob(CACHE . "/" . CACHEPREFIX . "*"));
                clearstatcache();
                $this->CloudflarePurgeCache($this->getCfg("cf"));
                $this->checkLocales();
            } finally {
                $lock->release();
            }
        } else {
            $this->addError("429: RATE LIMITED");
            $this->setLocation("/err/429");
            exit;
        }
        return;
    }

    private function unauthorized_access()
    {
        $this->addError("401: UNAUTHORIZED ACCESS");
        $this->setLocation("/err/401");
        exit;
    }
}
