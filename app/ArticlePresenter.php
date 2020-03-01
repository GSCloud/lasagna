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

/**
 * Article Presenter
 */
class ArticlePresenter extends APresenter
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
        $data["container_switch_article"] = true;

        // advanced caching
        $use_cache = $data["use_cache"] ?? false;
        $cache_key = strtolower(join("_", [$data["host"], $data["request_path"]]));
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            $output .= "\n<script>console.log('*** page content cached');</script>";
            return $this->setData("output", $output);
        }

        // fix locales
        $data["l"] = $data["l"] ?? [];
        foreach ($data["l"] as $k => $v) {
            StringFilters::correct_text_spacing($data["l"][$k], $data["lang"]);
        }

        // render output & save to model & cache
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
        StringFilters::trim_html_comment($output);
        Cache::write($cache_key, $output, "page");
        return $this->setData("output", $output);
    }
}
