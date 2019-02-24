<?php

class HomePresenter extends \GSC\APresenter
{

    public function process()
    {
        $this->checkRateLimit();
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        // check user
        $data["user"] = $this->getCurrentUser();
        $data["admin"] = $this->getUserGroup();

        // set language and fetch locale
        $data["lang"] = $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $data["lang{$language}"] = true;
        $data["l"] = $this->getLocale($language);

        // fix text data
        if (is_array($data["l"])) {
            foreach ($data["l"] as $k => $v) {
                $v = GSC\StringFilters::convert_eolhyphen_to_brdot($v);
                $v = GSC\StringFilters::correct_text_spacing($v, $language);
                $v = GSC\StringFilters::convert_eol_to_br($v);
                $data["l"][$k] = $v;
            }
        }

        $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
        $output = GSC\StringFilters::trim_html_comment($output);
        return $this->setData($data, "output", $output);
    }

}
