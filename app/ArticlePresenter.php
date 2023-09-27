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

/**
 * Article Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class ArticlePresenter extends APresenter
{
    /**
     * Controller processor
     *
     * @param mixed $param optional parameter
     * 
     * @return object Controller
     */
    public function process($param = null)
    {
        // get current Presenter and View
        $presenter = $this->getPresenter();
        $view = $this->getView();

        // process rate limiting + set HTML header + expand current data model
        $data = $this->getData();
        $this->checkRateLimit()->setHeaderHtml()->dataExpander($data);

        // process advanced caching
        $use_cache = (bool) (DEBUG ? false : $data["use_cache"] ?? false);
        $cache_key = hash(
            "sha256",
            join(
                "_", [$data["host"], $data["request_path"],
                "htmlpage"]
            )
        );
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            return $this->setData(
                "output",
                $output .= "\n<script>console.log("
                . "'*** page content cached');</script>"
            );
        }

        // fix current locale
        foreach ($data["l"]??=[] as $k => $v) {
            StringFilters::convert_eolhyphen_to_brdot($data["l"][$k]);
            StringFilters::convert_eol_to_br($data["l"][$k]);
            StringFilters::correct_text_spacing(
                $data["l"][$k], $data["lang"] ?? "en"
            );
        }

        // turn ON article view in content switcher
        $data["container_switch_article"] = true;

        // output rendering
        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);

        // strip comments
        StringFilters::trim_html_comment($output);

        // save to page cache
        if ($use_cache) {
            Cache::write($cache_key, $output, "page");
        }

        return $this->setData("output", $output);
    }
}
