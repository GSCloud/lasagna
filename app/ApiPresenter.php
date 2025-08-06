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
use RedisClient\RedisClient;

/**
 * API Presenter
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class ApiPresenter extends APresenter
{
    const API_CACHE = "minute";
    const ACCESS_TIME_LIMIT = 3599;
    const MAX_API_HITS = 1000;
    const USE_CACHE = true;

    /**
     * Main controller
     * 
     * @param mixed $param optional parameter
     * 
     * @return object Controller
     */
    public function process($param = null)
    {
        \setlocale(LC_ALL, "cs_CZ.utf8");
        \error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        if (!\is_array($data = $this->getData())) {
            return $this;
        }
        if (!\is_string($view = $this->getView())) {
            return $this;
        }
        if (!\is_array($presenter = $this->getPresenter())) {
            return $this;
        }
        $this->setHeaderHtml();
        $match = $this->getMatch();

        // API usage
        $api_usage = $this->accessLimiter();
        if (!\is_int($api_usage)) {
            $api_usage = 0;
        }

        // general API properties
        $extras = [
            "name" => "DEMO REST API",
            "fn" => $view,
            "endpoint" => \explode('?', $_SERVER['REQUEST_URI'])[0],
            "api_usage" => $api_usage,
            "api_quota" => (int) self::MAX_API_HITS,
            "access_time_limit" => (int) self::ACCESS_TIME_LIMIT,
            "cached" => (bool) self::USE_CACHE,
            "cache_time_limit" => $data["cache_profiles"][self::API_CACHE],
            "uuid" => $this->getUID(),
        ];

        // API calls
        switch ($view) {

        case "APIDemo":
            if (self::USE_CACHE) {
                $data = Cache::read($view, self::API_CACHE);
                if (\is_array($data)) {
                    $data['cache-hit'] = true;
                    return $this->writeJsonData($data, $extras);
                }
            }
            $data = [];
            $data['example'] = "Hello World!";
            Cache::write($view, $data, self::API_CACHE);
            return $this->writeJsonData($data, $extras);

        default:
            return $this->writeJsonData(404, $extras);
        }
    }

    /**
     * Redis access limiter
     *
     * @return mixed access count or 0
     */
    public function accessLimiter()
    {
        $hour = \date('H');
        $uid = $this->getUID();
        $key = 'access_limiter_' . SERVER . SS . PROJECT . "_{$hour}_{$uid}";
        \error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        $host = $this->getData('redis.host');
        if (!\is_string($host)) {
            $host = 'localhost';
        }
        $port = $this->getData('redis.port');
        if (!\is_string($port) || !\is_numeric($port)) {
            $port = 6377;
        }
        $redis = new RedisClient(
            [
            'server' => "$host:$port",
            'timeout' => 1,
            ]
        );
        try {
            $val = (int) @$redis->get($key);
        } catch (\Throwable $e) {
            return 0;
        }
        if ($val > self::MAX_API_HITS) {
            $this->addError('API: too many requests. Value > ' . self::MAX_API_HITS);
            $this->setLocation('/err/420');
        }
        try {
            @$redis->multi();
            @$redis->incr($key);
            @$redis->expire($key, self::ACCESS_TIME_LIMIT);
            @$redis->exec();
        } catch (\Throwable $e) {
            return 0;
        }
        $val++;
        return $val;
    }
}
