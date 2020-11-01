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
        // basic setup
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();
        $this->checkRateLimit()->setHeaderHtml()->dataExpander($data); // Model

        // advanced caching
        $use_cache = (DEBUG === true) ? false : $data["use_cache"] ?? false;
        $cache_key = hash("sha256", join("_", [$data["host"], $data["request_path"], "htmlpage"]));
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            return $this->setData("output", $output .= "\n<script>console.log('*** page content cached');</script>");
        }

        // fix locale text
        foreach ($data["l"] ??= [] as $k => $v) {
            StringFilters::correct_text_spacing($data["l"][$k], $data["lang"]);
        }

        $data["container_switch_article"] = true; // turn ON article view in content switcher

        // output rendering
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
        StringFilters::trim_html_comment($output); // strip comments
        Cache::write($cache_key, $output, "page"); // cache
        return $this->setData("output", $output); // save model
    }
}
