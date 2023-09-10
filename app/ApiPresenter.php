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
use RedisClient\RedisClient;

/**
 * API Presenter
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class ApiPresenter extends APresenter
{
    /*@var int API max. access hits */
    const MAX_API_HITS = 1000;

    /* @var string API time limit range in seconds. */
    const ACCESS_TIME_LIMIT = 3599;

    /* @var string API cache profile */
    const API_CACHE = "tenminutes";

    /**
     * Main controller
     * 
     * @return self
     */
    public function process()
    {
        setlocale(LC_ALL, "cs_CZ.utf8");

        $view = $this->getView();
        $use_cache = true;
        //$use_cache = false;

        // general API properties
        error_reporting(E_ALL ^ E_DEPRECATED);
        // temp. fix for broken Redis lib @ PHP 8.0
        $extras = [
            "access_time_limit" => self::ACCESS_TIME_LIMIT,
            "api_quota" => (int) self::MAX_API_HITS,
            "api_usage" => $this->getData("redis.port") > 0
                ? (int) $this->accessLimiter() : null,
            "cache_time_limit" => $this->getData("cache_profiles")[self::API_CACHE],
            "cached" => (bool) $use_cache,
            "fn" => $view,
            "name" => "TESSERACT",
            "uuid" => $this->getUID(),
        ];
        error_reporting(E_ALL);

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
    public function accessLimiter()
    {
        $hour = date("H");
        $uid = $this->getUID();
        $key = "access_limiter_"
            . SERVER
            . "_"
            . PROJECT
            . "_"
            . APPNAME
            . "_{$hour}_{$uid}";
        $host = $this->getData("redis.host") ?? "127.0.0.1";
        $port = $this->getData("redis.port") ?? "6379";
        $redis = new RedisClient(
            [
            "server" => "$host:$port",
            "timeout" => 1,
            ]
        );
        try {
            $val = (int) @$redis->get($key);
        } catch (\Exception $e) {
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
            $val = (int) @$redis->get($key);
        } catch (\Exception $e) {
            return 0;
        }
        return $val;
    }
}
