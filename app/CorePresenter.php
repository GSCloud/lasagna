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

/**
 * Core Presenter
 */
class CorePresenter extends APresenter
{
    /**
     * Main controller
     *
     * @return void
     */
    public function process()
    {
        if (isset($_GET["api"])) {
            $api = (string) $_GET["api"];
            $key = $this->getCfg("ci_tester.api_key") ?? null;
            if ($key !== $api) {
                $this->checkRateLimit();
            }
        } else {
            $this->checkRateLimit();
        }

        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();
        $match = $this->getMatch();

        $extras = ["name" => "LASAGNA Core", "fn" => $view];
        switch ($view) {

            case "manifest":
                $this->setHeaderJson();
                $lang = $_GET["lang"] ?? "cs";
                if (!in_array($lang, ["cs", "en"])) {
                    $lang = "cs";
                }
                return $this->setData("output", $this->setData("l", $this->getLocale($lang))->renderHTML("manifest"));
                break;

            case "sitemap":
                $this->setHeaderText();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                return $this->setData("output", $this->setData("sitemap", $map)->renderHTML("sitemap.txt"));
                break;

            case "swjs":
                $this->setHeaderJavaScript();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                return $this->setData("output", $this->setData("sitemap", $map)->renderHTML("sw.js"));
                break;

            case "ShowAPIs":
                $this->setHeaderHTML();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["api"]) && $p["api"]) {
                        $info = $p["api_info"] ?? "";
                        StringFilters::convert_eol_to_br($info);
                        $map[] = [
                            "path" => trim($p["path"], "/ \t\n\r\0\x0B"),
                            "desc" => $p["api_description"] ?? "",
                            "exam" => $p["api_example"] ?? [],
                            "info" => $info ? "<br><blockquote>${info}</blockquote>" : "",
                            "count" => count($p["api_example"]),
                        ];
                    }
                }
                usort($map, function ($a, $b) {
                    return strcmp($a["desc"], $b["desc"]);
                });
                return $this->setData("output", $this->setData("apis", $map)->setData("l", $this->getLocale("en"))->renderHTML("apis"));
                break;

            case "GetCoreVersion":
                $d = [];
                $d["LASAGNA"]["core"]["version"] = (string) $data["VERSION"];
                $d["LASAGNA"]["core"]["revisions"] = (int) $data["REVISIONS"];
                return $this->writeJsonData($d, $extras);
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
                    // ERROR
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
                    // fail - Not Found
                    return $this->writeJsonData(404, $extras);
                }
                break;
        }

        $language = strtolower($presenter[$view]["language"]) ?? "cs";
        $locale = $this->getLocale($language);
        $hash = hash('sha256', (string) json_encode($locale));

        switch ($view) {
            case "GetCsDataVersion":
            case "GetEnDataVersion":
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
