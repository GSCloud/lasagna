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
        $this->checkRateLimit()->setHeaderHtml();

        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        // expand data model
        $this->dataExpander($data);

        // advanced caching
        $arr = [
            $data["host"],
            $data["request_path"],
        ];
        $cache_key = strtolower(join($arr, "_"));
        $use_cache = $data["use_cache"] ?? false;
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            $output .= "\n<script>console.log('*** page content cached');</script>";
            return $this->setData("output", $output);
        }

        // fix text data
        if (is_array($data["l"])) {
            foreach ($data["l"] as $k => $v) {
                $v = StringFilters::convert_eolhyphen_to_brdot($v);
                $v = StringFilters::correct_text_spacing($v, $data["lang"]);
                $v = StringFilters::convert_eol_to_br($v);
                $data["l"][$k] = $v;
            }
        }

        // render output & save to model & cache
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
        $output = StringFilters::trim_html_comment($output);
        Cache::write($cache_key, $output, "page");
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
