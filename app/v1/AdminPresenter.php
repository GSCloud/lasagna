<?php

use Cake\Cache\Cache;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class AdminPresenter extends \GSC\APresenter
{

    public function process()
    {
        $cfg = $this->getCfg();
        $data = $this->getData();
        $match = $this->getMatch();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        $admin_key = "/admin.key";

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
            }
            catch(Exception $e) {
                return -1;
            }
        }

        switch ($match["params"]["p"] ?? null) {

            // CSV info
            case "GetCsvInfo":
                $this->checkAdmins("admin");
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
                    if ($arr[$k]["lines"] === -1) unset($arr[$k]);
                }
                return $this->writeJsonData($arr, ["name" => "LASAGNA CSV Information", "fn" => "GetCsvInfo"]);
                break;

            // Cloudflare Analytics - UNFINISHED -> TODO!!!
            case "GetCfAnalytics":
                $this->checkAdmins("admin");
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
                    return $this->writeJsonData(json_decode($results), ["name" => "LASAGNA Cloudflare Analytics", "fn" => "GetCfAnalytics"]);
                } else {
                    return $this->writeJsonData(null, ["fn" => "GetCfAnalytics"]);
                }
                break;

            // PageSpeed Insights
            case "GetPSInsights":
                $this->checkAdmins("admin");
                $base = urlencode($cfg["canonical_url"]);
                $key = $this->getCfg("google")["pagespeedinsights_key"] ?? "NA";
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
                return $this->writeJsonData(json_decode($results), ["name" => "LASAGNA PageSpeed Insights", "fn" => "GetPSInsights"]);
                break;

            // update token
            case "GetUpdateToken":
                $this->checkAdmins("admin");
                $file = DATA . $admin_key;
                $key = trim(@file_get_contents($file));
                try {
                    if (!$key) {
                        $key = hash("sha256", random_bytes(256) . time());
                        file_put_contents($file, $key);
                        @chmod($file, 0660);
                        $this->addMessage("ADMIN: new keyfile created");
                    }
                } catch(Exception $e) {
                    $this->unauthorized_access();
                }
                $user = $this->getCurrentUser();
                $arr = "";
                if ($user["id"]) {
                    $hashid = hash("sha256", $user["id"]);
                    $arr = $data["base"] . "admin/CoreUpdateRemote?user=" . $hashid . "&token=" . hash("sha256", $key . $hashid);
                } else {
                    $this->unauthorized_access();
                }
                return $this->writeJsonData($arr, ["name" => "LASAGNA Remote Update Token", "fn" => "GetUpdateToken"]);
                break;

            // FLUSH
            case "FlushCache":
                $this->checkAdmins("admin");
                $this->flush_cache();
                return $this->writeJsonData(["status" => "OK"], ["name" => "LASAGNA FlushCache", "fn" => "FlushCache"]);
                break;

            // UPDATE
            case "CoreUpdate":
                $this->checkAdmins("admin");
                $this->setForceCsvCheck();
                $this->postloadAppData("app_data");
                $this->flush_cache();
                return $this->writeJsonData(["status" => "OK"], ["name" => "LASAGNA CoreUpdate", "fn" => "CoreUpdate"]);
                break;

            // FLUSH remote
            case "FlushCacheRemote":
                $user = $_GET["user"] ?? null;
                $token = $_GET["token"] ?? null;
                if ($user && $token) {
                    $file = DATA . $admin_key;
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
                    $file = DATA . $admin_key;
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

        } // switch END
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
                @array_map("unlink", glob(CACHE . "/" . CACHEPREFIX. "*"));
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
