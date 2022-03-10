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
use There4\Analytics\AnalyticsEvent;

/**
 * Home Presenter
 */
class HomePresenter extends APresenter
{
    /**
     * Main controller
     *
     * @return object Singleton instance
     */
    public function process()
    {
        // get current Presenter and View
        $presenter = $this->getPresenter();
        $view = $this->getView();
        if ((!$presenter) || (!$view)) {
            return $this;
        }

        // process rate limiting + set HTML header + expand current data model
        $data = $this->getData();
        $this->checkRateLimit()->setHeaderHtml()->dataExpander($data);

        // process advanced caching
        $use_cache = (bool) (DEBUG ? false : $data["use_cache"] ?? false);
        $cache_key = hash("sha256", join("_", [$data["host"], $data["request_path"], "htmlpage"]));
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            header("X-Cache: HIT");
            return $this->setData("output", $output);
        }

        // fix current locale
        foreach ($data["l"]??=[] as $k => $v) {
            StringFilters::convert_eolhyphen_to_brdot($data["l"][$k]);
            StringFilters::convert_eol_to_br($data["l"][$k]);
            StringFilters::correct_text_spacing($data["l"][$k], $data["lang"] ?? "en");
        }

        // output rendering
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);

        // strip comments
        StringFilters::trim_html_comment($output);

        // save to page cache
        if ($use_cache) {
            Cache::write($cache_key, $output, "page");
        }

        // save output to model
        return $this->setData("output", $output);
    }

    /**
     * Send Google Analytics events
     *
     * @return object Singleton instance.
     */
    public function SendAnalytics()
    {
        $dot = new \Adbar\Dot((array) $this->getData());
        if ($dot->has("google.ua") && (strlen($dot->get("google.ua"))) && (array_key_exists("HTTPS", $_SERVER)) && ($_SERVER["HTTPS"] == "on")) {
            ob_flush();
            $events = new AnalyticsEvent($dot->get("google.ua"), $dot->get("canonical_url") . $dot->get("request_path"));
            $country = (string) ($_SERVER["HTTP_CF_IPCOUNTRY"] ?? "N/A");
            @$events->trackEvent((string) ($this->getCfg("app") ?? "APP"), "country_code", $country);
        }
        return $this;
    }
}
