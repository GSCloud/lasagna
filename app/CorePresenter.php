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
            case "GetQR":
                return $this->setData("output", $this->renderHTML("nasrat"));
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
            case "api":
                $this->setHeaderHTML();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["api"]) && $p["api"]) {
                        $info = $p["api_info"] ?? "";
                        StringFilters::convert_eol_to_br($info);
                        $info = \htmlspecialchars($info);
                        $info = preg_replace(
                            array('#href=&quot;(.*)&quot;#', '#&lt;(/?(?:pre|a|b|br|em|u|ul|li|ol)(\shref=".*")?/?)&gt;#'),
                            array('href="\1"', '<\1>'),
                            $info
                        );
                        $map[] = [
                            "count" => count($p["api_example"]),
                            "deprecated" => $p["deprecated"] ?? false,
                            "desc" => \htmlspecialchars($p["api_description"] ?? ""),
                            "exam" => $p["api_example"] ?? [],
                            "finished" => $p["finished"] ?? false,
                            "info" => $info ? "<br><blockquote>${info}</blockquote>" : "",
                            "key" => $p["use_key"] ?? false,
                            "linkit" => !(\strpos($p["path"], "[") ?? false), // do not link path with parameters
                            "method" => \strtoupper($p["method"]),
                            "path" => trim($p["path"], "/ \t\n\r\0\x0B"),
                            "private" => $p["private"] ?? false,
                        ];
                    }
                }
                usort($map, function ($a, $b) {
                    return strcmp($a["desc"], $b["desc"]);
                });
                return $this->setData("output", $this->setData("apis", $map)->setData("l", $this->getLocale("en"))->renderHTML("apis"));
                break;
            case "androidjs":
                $file = WWW . "/js/android-app.js";
                if (\file_exists($file)) {
                    $content = \file_get_contents($file);
                    $time = \filemtime(WWW . "/js/android-app.js") ?? null;
                    $version = hash("sha256", $content);
                } else {
                    $content = null;
                    $version = null;
                    $time = null;
                }
                $d = [
                    "js" => $content,
                    "timestamp" => $time,
                    "version" => $version,
                ];
                return $this->writeJsonData($d, $extras);
                break;
            case "androidcss":
                $file = WWW . "/css/android.css";
                if (\file_exists($file)) {
                    $content = \file_get_contents($file);
                    $time = \filemtime(WWW . "/css/android.css") ?? null;
                    $version = hash("sha256", $content);
                } else {
                    $content = null;
                    $version = null;
                    $time = null;
                }
                $d = [
                    "css" => $content,
                    "timestamp" => $time,
                    "version" => $version,
                ];
                return $this->writeJsonData($d, $extras);
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
                if ($x !== 2) { // ERROR
                    return $this->writeJsonData(400, $extras);
                }
                $file = DATA . "/summernote_" . $profile . "_" . $hash . ".json";
                $data = "";
                if (file_exists($file)) {
                    $data = @file_get_contents($file);
                }
                $crc = hash("sha256", $data);
                if (isset($_GET["crc"])) {
                    if ($_GET["crc"] == $crc) { // not modified
                        return $this->writeJsonData(304, $extras);
                    }
                }
                return $this->writeJsonData([
                    "crc" => $crc,
                    "hash" => $hash,
                    "html" => $data,
                    "profile" => $profile,
                ], $extras);
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
