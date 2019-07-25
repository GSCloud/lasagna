<?php

class CorePresenter extends \GSC\APresenter
{
    public function process()
    {
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();
        $match = $this->getMatch();

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

                // core version
            case "ReadArticles":
                $hash = null;
                $profile = null;
                if (isset($match["params"]["profile"])) {
                    $profile = trim($match["params"]["profile"]);
                }
                if (isset($match["params"]["hash"])) {
                    $hash = trim($match["params"]["hash"]);
                }
                $data = @file_get_contents(DATA . "/summernote_" . $profile . "_" . $hash . ".json");
                if ($data === false) {
                    return $this->writeJsonData(403, ["name" => "LASAGNA core version", "fn" => "ReadArticles"]);
                }
                return $this->writeJsonData([
                    "html" => $data,
                ], ["name" => "LASAGNA core version", "fn" => "ReadArticles"]);
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
