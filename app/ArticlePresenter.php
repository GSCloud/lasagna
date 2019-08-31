<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
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
        $this->checkRateLimit();
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        // expand data model
        $this->dataExpander($data);
        $data["container_switch_article"] = true;

        // advanced caching
        $arr = [
            $data["host"],
            $data["request_path"],
        ];
        $cache_key = strtolower(join($arr, "_"));
        $use_cache = $data["use_cache"] ?? false;
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            $output .= "\n<script>console.log('(page cached)');</script>";
            return $this->setData($data, "output", $output);
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

        // render output & save to model and cache
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
        $output = StringFilters::trim_html_comment($output);
        Cache::write($cache_key, $output, "page");
        return $this->setData($data, "output", $output);
    }
}
