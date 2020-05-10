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
        // basic setup
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();
        $this->checkRateLimit()->setHeaderHtml()->dataExpander($data); // data = Model

        // advanced caching
        $use_cache = (DEBUG === true) ? false : $data["use_cache"] ?? false;
        $cache_key = strtolower(join("_", [$data["host"], $data["request_path"]])) . "_htmlpage";
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            return $this->setData("output", $output .= "\n<script>console.log('*** page content cached');</script>");
        }

        foreach ($data["l"] ??= [] as $k => $v) { // fix locale text
            StringFilters::convert_eolhyphen_to_brdot($data["l"][$k]);
            StringFilters::convert_eol_to_br($data["l"][$k]);
            StringFilters::correct_text_spacing($data["l"][$k], $data["lang"]);
        }

        // output
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]); // render
        StringFilters::trim_html_comment($output); // fix content
        Cache::write($cache_key, $output, "page"); // save cache
        return $this->setData("output", $output); // save model
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
