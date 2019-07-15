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

            // core version
            case "version_core":
                $d = [];
                $d["LASAGNA"]["core"]["version"] = $data["VERSION"];
                return $this->writeJsonData($d, ["name" => "LASAGNA core version", "fn" => "core"]);
                break;
        }
        // fetch locale
        $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $locale = $this->getLocale($language);
        $hash = hash('sha256', (string) json_encode($locale));

        switch ($view) {
            // data version
            case "en_version_data":
            case "cs_version_data":
                $d = [];
                $d["LASAGNA"]["data"]["version"] = $hash;
                return $this->writeJsonData($d, ["name" => "LASAGNA data version " . strtoupper($language), "fn" => "core"]);
                break;
        }
        return $this;
    }
}
