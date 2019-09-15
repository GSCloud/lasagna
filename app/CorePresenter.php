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
        if (isset($_GET["api"])) {
            $api = (string) $_GET["api"];
            $key = $this->getCfg("ci_tester.api_key") ?? null;
            if ($key !== $api) $this->checkRateLimit();
        } else {
            $this->checkRateLimit();
        }

        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();
        $match = $this->getMatch();

        $extras = ["name" => "LASAGNA Core", "fn" => $view];
        switch ($view) {

            case "webmanifest":
                $this->setHeaderJson();
                $lang = $_GET["lang"] ?? "cs";
                if (!in_array($lang, ["cs", "en"])) {
                    $lang = "cs";
                }
                $output = $this->setData("l", $this->getLocale($lang))->renderHTML("site.webmanifest");
                return $this->setData("output", $output);
                break;

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

            case "version_core":
                $d = [];
                $d["LASAGNA"]["core"]["version"] = $data["VERSION"];
                $d["LASAGNA"]["core"]["revisions"] = (int) $data["REVISIONS"];
                return $this->writeJsonData($d, $extras);
                break;

            case "FixLangDataCs":
                $d = [];
                return $this->writeJsonData(500, $extras);
                break;

            case "FixLangDataEn":
                $d = [];
                return $this->writeJsonData(500, $extras);
                break;

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
                    return $this->writeJsonData(400, $extras);
                }
                $file = DATA . "/summernote_" . $profile . "_" . $hash . ".json";
                if (file_exists($file)) {
                    $data = @file_get_contents($file);
                    $crc = hash("sha256", $data);
                    if (isset($_GET["crc"])) {
                        if ($_GET["crc"] == $crc) {
                            // Not Modified
                            return $this->writeJsonData(304, $extras);
                        }
                    }
                    // OK
                    return $this->writeJsonData(["html" => $data, "crc" => $crc], $extras);
                } else {
                    // Not Found
                    return $this->writeJsonData(404, $extras);
                }
                break;
        }

        // locale
        $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $locale = $this->getLocale($language);
        $hash = hash('sha256', (string) json_encode($locale));

        switch ($view) {

            case "cs_version_data":
            case "en_version_data":
                $d = [];
                $d["LASAGNA"]["data"]["version"] = $hash;
                $d["LASAGNA"]["data"]["language"] = $language;
                return $this->writeJsonData($d, $extras);
                break;

            default:
                ErrorPresenter::getInstance()->process(404);
        }
        return $this;
    }
}
