<?php

class CorePresenter extends \GSC\APresenter
{

    public function process()
    {
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        switch ($view) {

            // webmanifest
            case "webmanifest":
                $this->setHeaderJson();
                $lang = $_GET["lang"] ?? "cs";
                if (!in_array($lang, ["cs", "en"])) {
                    $lang = "cs";
                }
                $data["l"] = $this->getLocale($lang);
                $output = $this->setData($data)->renderHTML("site.webmanifest");
                return $this->setData($data, "output", $output);
                break;

            // sitemap
            case "sitemap":
                $this->setHeaderText();
                $a = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $a[] = trim($p["path"], "/ ");
                    }
                }
                $data["sitemap"] = $a;
                $output = $this->setData($data)->renderHTML("sitemap.txt");
                return $this->setData($data, "output", $output);
                break;

            // sw.js
            case "swjs":
                $this->setHeaderJavaScript();
                $output = $this->setData($data)->renderHTML("sw.js");
                return $this->setData($data, "output", $output);
                break;

            // core version as JSON
            case "version_core":
                $d = [];
                $d["LASAGNA"]["core"]["version"] = $data["VERSION"];
                return $this->writeJsonData($d, ["name" => "LASAGNA Core Version", "fn" => "core"]);
                break;

            // core version as JavaScript
            case "version_core_js":
                $this->setHeaderJavaScript();
                $output = "";
                $output .= ";(function(w){";
                $output .= "if(w.GSC.LASAGNA)w.GSC.LASAGNA.core.version=\"" . $data["VERSION"] . "\";";
                $output .= "if(w.GSC.LASAGNA)w.GSC.LASAGNA.core.timestamp=\"" . time() . "\";})(window);";
                return $this->setData($data, "output", $output);
                break;
        }

        // set language and fetch locale
        $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $data["lang"] = $language;
        $data["lang{$language}"] = true;
        $data["l"] = $this->getLocale($language);

        // remove differences to calculate hash
        unset($data["match"]);
        unset($data["nonce"]);
        unset($data["utm"]);
        unset($data["view"]);

        switch ($view) {

            // data version as JSON
            case "en_version_data":
            case "cs_version_data":
                $d = [];
                $d["LASAGNA"]["data"]["version"] = hash('sha256', (string) json_encode($data));
                return $this->writeJsonData($d, ["name" => "LASAGNA Data Version " . strtoupper($language), "fn" => "data"]);
                break;

            // data version as JavaScript
            case "en_version_data_js":
            case "cs_version_data_js":
                $this->setHeaderJavaScript();
                $output = "";
                $output .= ";(function(w){";
                $output .= "if(w.GSC.LASAGNA)w.GSC.LASAGNA.data.version=\"" . hash('sha256', (string) json_encode($data)) . "\";";
                $output .= "if(w.GSC.LASAGNA)w.GSC.LASAGNA.data.timestamp=\"" . time() . "\";})(window);";
                return $this->setData($data, "output", $output);
                break;
        }
        return $this;
    }

}

