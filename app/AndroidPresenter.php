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
 * Android Presenter
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
            $data["l"] = $data["l"] ?? [];
            foreach ($data["l"] as $k => $v) {
                StringFilters::correct_text_spacing($data["l"][$k], $language);
            }
            $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
            StringFilters::trim_html_comment($output);
            @file_put_contents(WWW . "/android_${language}.html", $output);
        }
        return $this->setData("output", $output);
    }
}
