<?php

use Cake\Cache\Cache;

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
        if ($this->getUserGroup()) {
            $data["admin_group_" . $this->getUserGroup()] = true;
        }

        // set language and fetch locale
        $data["lang"] = $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $data["lang{$language}"] = true;
        $data["l"] = $this->getLocale($language);

        // advanced caching
        $arr = [
            $data["host"],
            $data["request_path"],
        ];
        $cache_key = strtolower(join($arr, "_"));
        if ($output = Cache::read($cache_key, "page")) {
            $output .= "\n<script>console.log('(page cached)');</script>";
            return $this->setData($data, "output", $output);
        }

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
        Cache::write($cache_key, $output, "page");
        return $this->setData($data, "output", $output);
    }

}
