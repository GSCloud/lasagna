<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://mini.gscloud.cz
 */

namespace GSC;

class CorePresenter extends APresenter
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
                $output = $this->setData("l", $this->getLocale($lang))->renderHTML("site.webmanifest");
                return $this->setData("output", $output);
                break;

            // sitemap
            case "sitemap":
                $this->setHeaderText();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                $output = $this->setData("sitemap", $map)->renderHTML("sitemap.txt");
                return $this->setData("output", $output);
                break;

            // sw.js
            case "swjs":
                $this->setHeaderJavaScript();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                $output = $this->setData("sitemap", $map)->renderHTML("sw.js");
                return $this->setData("output", $output);
                break;

            // core version
            case "version_core":
                $d = [];
                $d["LASAGNA"]["core"]["version"] = $data["VERSION"];
                $d["LASAGNA"]["core"]["revisions"] = (int) $data["REVISIONS"];
                return $this->writeJsonData($d, ["name" => "LASAGNA Core", "fn" => "core version"]);
                break;

            // fix lang CS
            case "FixLangDataCs":
                $d = [];
                return $this->writeJsonData(500, ["name" => "LASAGNA Core", "fn" => "FixLangDataCs"]);
                break;

            // fix lang EN
            case "FixLangDataEn":
                $d = [];
                return $this->writeJsonData(500, ["name" => "LASAGNA Core", "fn" => "FixLangDataEn"]);
                break;

            // core version
            case "ReadArticles":
                $x = 0;
                if (isset($match["params"]["profile"])) {
                    $profile = trim($match["params"]["profile"]);
                    $x++;
                }
                if (isset($match["params"]["hash"])) {
                    $hash = trim($match["params"]["hash"]);
                    $x++;
                }
                if ($x !== 2) {
                    // Bad Request
                    return $this->writeJsonData(400, ["name" => "LASAGNA Core", "fn" => "ReadArticles"]);
                }
                $file = DATA . "/summernote_" . $profile . "_" . $hash . ".json";
                if (file_exists($file)) {
                    $data = @file_get_contents($file);
                    $crc = hash("sha256", $data);
                    if (isset($_GET["crc"])) {
                        if ($_GET["crc"] == $crc) {
                            // Not Modified
                            return $this->writeJsonData(304, ["name" => "LASAGNA Core", "fn" => "ReadArticles"]);
                        }
                    }
                    // OK
                    return $this->writeJsonData(["html" => $data, "crc" => $crc], ["name" => "LASAGNA Core", "fn" => "ReadArticles"]);
                } else {
                    // Not Found
                    return $this->writeJsonData(404, ["name" => "LASAGNA Core", "fn" => "ReadArticles"]);
                }
                break;
        }

        // fetch locale
        $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $locale = $this->getLocale($language);
        $hash = hash('sha256', (string) json_encode($locale));

        switch ($view) {
            // data version
            case "cs_version_data":
            case "en_version_data":
                $d = [];
                $d["LASAGNA"]["data"]["version"] = $hash;
                $d["LASAGNA"]["data"]["language"] = $language;
                return $this->writeJsonData($d, ["name" => "LASAGNA Core", "fn" => "$language data version"]);
                break;
        }
        return $this;
    }
}
