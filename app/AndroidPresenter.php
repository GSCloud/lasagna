<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

/**
 * Home Presenter
 */
class AndroidPresenter extends APresenter
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

        foreach ([
            "en",
            "cs",
        ] as $language) {
            $data["cdn"] = "";
            $data["home_url"] = "android_${language}.html";
            $data["lang"] = $language;
            unset($data["langcs"]);
            unset($data["langen"]);
            $data["lang${language}"] = true;
            $data["l"] = $l = $this->getLocale($language);
            if (is_array($data["l"])) {
                foreach ($data["l"] as $k => $v) {
                    $v = StringFilters::convert_eolhyphen_to_brdot($v);
                    $v = StringFilters::correct_text_spacing($v, $data["lang"]);
                    $v = StringFilters::convert_eol_to_br($v);
                    $data["l"][$k] = $v;
                }
            }
            $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
            $output = StringFilters::trim_html_comment($output);
            file_put_contents(WWW . "/android_${language}.html", $output);
        }
        //$output = '<a href="android_cs.html">ANDROID CS</a> <a href="android_en.html">ANDROID EN</a>';
        return $this->setData("output", $output);
    }
}
