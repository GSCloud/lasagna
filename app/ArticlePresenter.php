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

        // check user & set caching
        $use_cache = true;
        $data["user"] = $this->getCurrentUser();
        if ($data["admin"] = $a = $this->getUserGroup()) {
            $data["admin_group_$a"] = true;
            $use_cache = false;
        }

        // set language and fetch locale
        $data["lang"] = $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $data["lang{$language}"] = true;
        $data["l"] = $this->getLocale($language);
        $data["DATA_VERSION"] = hash('sha256', (string) json_encode($data["l"]));
        if (($pos = strpos($data["request_path"], $language)) !== false) {
            $data["request_path_slug"] = substr_replace($data["request_path"], "", $pos, strlen($language));
        } else {
            $data["request_path_slug"] = $data["request_path"];
        }

        // advanced caching
        $arr = [
            $data["host"],
            $data["request_path"],
        ];
        $cache_key = strtolower(join($arr, "_"));
        if ($use_cache && $output = Cache::read($cache_key, "page")) {
            $output .= "\n<script>console.log('(page cached)');</script>";
            return $this->setData($data, "output", $output);
        }

        $data["container_switch_article"] = true;

        // fix text data
        if (is_array($data["l"])) {
            foreach ($data["l"] as $k => $v) {
                $v = StringFilters::convert_eolhyphen_to_brdot($v);
                $v = StringFilters::correct_text_spacing($v, $language);
                $v = StringFilters::convert_eol_to_br($v);
                $data["l"][$k] = $v;
            }
        }

        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
        $output = StringFilters::trim_html_comment($output);
        Cache::write($cache_key, $output, "page");
        return $this->setData($data, "output", $output);
    }
}
