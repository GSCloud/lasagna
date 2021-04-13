<?php
/**
 * GSC Tesseract
 *
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

use Cake\Cache\Cache;
use RedisClient\RedisClient;

/**
 * API Presenter
 */
class ApiPresenter extends APresenter
{
    /** @var int API max. access hits */
    const MAX_API_HITS = 1000;

    /** @var string API time limit range in seconds. */
    const ACCESS_TIME_LIMIT = 3599;

    /** @var string API cache profile */
    const API_CACHE = "tenminutes";

    /**
     * Main controller
     */
    public function process()
    {
        $view = $this->getView();
        $use_cache = true;
        $use_cache = false;

        // general API properties
        $extras = [
            "access_time_limit" => self::ACCESS_TIME_LIMIT,
            "api_quota" => (int) self::MAX_API_HITS,
            "api_usage" => $this->getData("redis.port") > 0 ? $this->accessLimiter() : 0,
            "cache_time_limit" => $this->getData("cache_profiles")[self::API_CACHE],
            "fn" => $view,
            "name" => "TESSERACT",
            "uuid" => $this->getUID(),
        ];

        //setlocale(LC_ALL, "cs_CZ.utf8");
        // API calls
        switch ($view) {
            case "APIDemo":
                if ($use_cache && $data = Cache::read($view, self::API_CACHE)) {
                    return $this->writeJsonData($data, $extras);
                }

                // populate data model
                $data = [];
                $data[] = "Hello!";
                $data[] = "Hello Europe!";
                $data[] = "Hello World!";

                // save model to cache
                if ($use_cache) {
                    Cache::write($view, $data, self::API_CACHE);
                }
                return $this->writeJsonData($data, $extras);
                break;

            default:
                return ErrorPresenter::getInstance()->process(404);
        }
        return $this;
    }

    /**
     * Access limiter
     *
     * @return int access count
     */
    private function accessLimiter()
    {
        $hour = date("H");
        $uid = $this->getUID();
        $key = "access_limiter_" . SERVER . "_" . PROJECT . "_" . APPNAME . "_{$hour}_{$uid}";
        $host = $this->getData("redis.host") ?? "127.0.0.1";
        $port = $this->getData("redis.port") ?? "6379";
        $redis = new RedisClient([
            "server" => "$host:$port",
            "timeout" => 1,
        ]);
        try {
            $val = (int) @$redis->get($key);
        } catch (Exception $e) {
            return 0;
        }
        if ($val > self::MAX_API_HITS) { // over limit!
            $this->setLocation("/err/420");
        }
        try {
            @$redis->multi();
            @$redis->incr($key);
            @$redis->expire($key, self::ACCESS_TIME_LIMIT);
            @$redis->exec();
        } catch (Exception $e) {
            return 0;
        }
        return $val++;
    }
}
