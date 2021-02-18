<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

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
        $data = $this->getData();
        $match = $this->getMatch();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        $extras = [
            "fn" => $view,
            "ip" => $this->getIP(),
            "name" => "Tesseract LASAGNA Core Module",
        ];

        // API calls
        switch ($view) {
            case "PingBack":
                $this->checkRateLimit();
                $x = file_get_contents("/proc/meminfo") ?? "";
                $meminfo = explode("\n", $x);
                $meminfo = array_map("trim", $meminfo);
                $meminfo = array_filter($meminfo, "strlen");
                foreach ($meminfo as $k => $v) {
                    if (!strpos($v, ':')) {
                        continue;
                    }
                    $x = explode(':', $v);
                    unset($meminfo[$k]);
                    $meminfo[$x[0]] = trim($x[1]);
                }
                $data = [
                    "system_load" => function_exists("sys_getloadavg") ? \sys_getloadavg() : null,
                    "memory_info" => $meminfo ?? null,
                ];
                return $this->writeJsonData($data, $extras);
                break;

            case "GetWebManifest":
                // do NOT rate limit this call!
                //$this->checkRateLimit();
                $this->setHeaderJson();
                $lang = $this->validateLanguage($_GET["lang"] ?? "en"); // language set by GET parameter
                return $this->setData("output", $this->setData("l", $this->getLocale($lang))->renderHTML("manifest"));
                break;

            case "ReadEpubBook1":
            case "ReadEpubBook2":
                $this->checkRateLimit();
                $epub = null;
                if (isset($match["params"]["trailing"])) {
                    $epub = \urldecode(\trim($match["params"]["trailing"]));
                    // tweaks
                    $epub = \str_replace("..", "", $epub);
                    $epub = \str_replace("\\", "", $epub);
                    $epub = \str_ireplace(".epub", "", $epub);
                    $file = WWW . "/${epub}.epub";
                }
                if ($epub && \file_exists($file) && \is_readable($file)) {
                    $this->setHeaderHTML();
                    $data["epub"] = "/${epub}.epub";
                    $output = $this->setData($data)->renderHTML($presenter[$view]["template"]);
                    return $this->setData("output", $output);
                }
                return $this->writeJsonData(400, $extras);
                break;

            case "GetTXTSitemap":
                // do NOT rate limit this call!
                //$this->checkRateLimit();
                $this->setHeaderText();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = \trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                return $this->setData("output", $this->setData("sitemap", $map)->renderHTML("sitemap.txt"));
                break;

            case "GetXMLSitemap":
                // do NOT rate limit this call!
                //$this->checkRateLimit();
                $this->setHeaderXML();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = \trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                return $this->setData("output", $this->setData("sitemap", $map)->renderHTML("sitemap.xml"));
                break;

            case "GetRSSXML":
                // do NOT rate limit this call!
                //$this->checkRateLimit();
                $this->setHeaderXML();
                $language = "en"; // set to English
                $l = $this->getLocale($language);
                if (class_exists("\\GSC\\RSSPresenter")) {
                    $map = RSSPresenter::getInstance()->process() ?? []; // get items map from RSSPresenter
                } else {
                    $map = [];
                }
                $this->setData("rss_channel_description", $l["meta_description"] ?? "");
                $this->setData("rss_channel_link", $l['$canonical_url'] ?? "");
                $this->setData("rss_channel_title", $l["title"] ?? "");
                return $this->setData("output", $this->setData("rss_items", (array) $map)->renderHTML("rss.xml"));
                break;

            case "GetArticleHTMLExport":
                // special rate limit value!
                $this->checkRateLimit(100);
                $nofetch = $_COOKIE["NOFETCH"] ?? false; // extra check
                $x = 0;
                if (isset($match["params"]["lang"])) {
                    $language = strtolower(substr(trim($match["params"]["lang"]), 0, 2));
                    $x++;
                }
                if (isset($match["params"]["profile"])) {
                    $profile = trim($match["params"]["profile"]);
                    $x++;
                }
                if (isset($match["params"]["trailing"])) {
                    $path = trim($match["params"]["trailing"]);
                    $x++;
                }
                if ($x !== 3) {
                    return $this->setHeaderHTML()->setData("output", ""); // ERROR
                }
                if ($path == "!") { // homepage
                    $path = $language;
                } else {
                    $path = $language . "/" . $path;
                }
                $html = "";
                $hash = \hash("sha256", $path);
                $f = DATA . "/summernote_${profile}_${hash}.json";
                if (\file_exists($f)) {
                    $html = \json_decode(@\file_get_contents($f), true);
                    if (\is_array($html)) {
                        $html = \join("\n", $html);
                    }
                } else { // ERROR - file not found!
                    return ErrorPresenter::getInstance()->process(404);
                }
                \preg_match_all('/\[remote_content url="([^]\"\n]*)"\]/', $html, $matches);
                $c = 0;
                $codes = [];
                $remotes = [];
                foreach ($matches[0]??=[] as $match) {
                    if ($match) {
                        $codes[$c] = $match;
                    }
                    $c++;
                }
                $c = 0;
                foreach ($matches[1]??=[] as $match) {
                    if ($match && strpos($codes[$c], $match)) {
                        $remotes[$c] = $match;
                    }
                    $c++;
                }
                $cache = []; // in-RAM cache
                foreach ($remotes as $key => $uri) {
                    if ($nofetch) {
                        $out = "";
                    } else {
                        if (isset($cache[$uri])) {
                            $out = $cache[$uri];
                        } else {
                            $ch = \curl_init();
                            \curl_setopt_array($ch, array(
                                CURLOPT_URL => $uri,
                                CURLOPT_CONNECTTIMEOUT => 5,
                                CURLOPT_COOKIE => "NOFETCH=true",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 10,
                            ));
                            $out = \curl_exec($ch);
                            // find <body> content if possible
                            \preg_match("/<body.*\/body>/s", $out, $m);
                            if (count($m) != 0) {
                                $out = "{$m[0]}";
                            }
                            $cache[$uri] = $out;
                            \curl_close($ch);
                        }
                    }
                    $html = \str_replace($codes[$key], $out, $html);
                }
                $html = \preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html); // remove script tags
                $html = \preg_replace('/<!--(.*)-->/Uis', '', $html); // remove HTML comments
                return $this->setHeaderHTML()->setData("output", $html);
                break;

            case "GetQR":
                $this->checkRateLimit();
                $x = 0;
                if (isset($match["params"]["size"])) {
                    $size = \trim($match["params"]["size"]);
                    switch ($size) {
                        case "m":
                            $scale = 8;
                            break;
                        case "l":
                            $scale = 10;
                            break;
                        case "x":
                            $scale = 15;
                            break;
                        case "s":
                        default:
                            $scale = 5;
                    }
                    $x++;
                }
                if (isset($match["params"]["trailing"])) {
                    $text = \trim($match["params"]["trailing"]);
                    $x++;
                }
                if ($x !== 2) { // ERROR
                    return $this->writeJsonData(400, $extras);
                }
                $options = new QROptions([
                    "version" => 7,
                    "outputType" => QRCode::OUTPUT_IMAGE_PNG,
                    "eccLevel" => QRCode::ECC_L,
                    "scale" => $scale,
                    "imageBase64" => false,
                    "imageTransparent" => false,
                ]);
                \header("Content-type: image/png");
                echo (new QRCode($options))->render($text ?? "", CACHE . "/" . hash("sha256", $text) . ".png");
                exit; // finish here - just not to mangle the PNG!
                break;

            case "GetServiceWorker":
                // do NOT rate limit this call!
                $this->setHeaderJavaScript();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["sitemap"]) && $p["sitemap"]) {
                        $map[] = \trim($p["path"], "/ \t\n\r\0\x0B");
                    }
                }
                return $this->setData("output", $this->setData("sitemap", $map)->renderHTML("sw.js"));
                break;

            case "API":
                $this->checkRateLimit();
                $this->setHeaderHTML();
                $map = [];
                foreach ($presenter as $p) {
                    if (isset($p["api"]) && $p["api"]) {
                        $info = $p["api_info"] ?? "";
                        StringFilters::convert_eol_to_br($info);
                        $info = \htmlspecialchars($info);
                        $info = \preg_replace(
                            array('#href=&quot;(.*)&quot;#', '#&lt;(/?(?:pre|a|b|br|em|u|ul|li|ol)(\shref=".*")?/?)&gt;#'),
                            array('href="\1"', '<\1>'),
                            $info
                        );
                        $map[] = [
                            "count" => \count($p["api_example"]),
                            "deprecated" => $p["deprecated"] ?? false,
                            "desc" => \htmlspecialchars($p["api_description"] ?? ""),
                            "exam" => $p["api_example"] ?? [],
                            "finished" => $p["finished"] ?? false,
                            "info" => $info ? "<br><blockquote>${info}</blockquote>" : "",
                            "key" => $p["use_key"] ?? false,
                            "linkit" => !(\strpos($p["path"], "[") ?? false), // do not link to path with parameters!
                            "method" => \strtoupper($p["method"]),
                            "path" => \trim($p["path"], "/ \t\n\r\0\x0B"),
                            "private" => $p["private"] ?? false,
                        ];
                    }
                }
                \usort($map, function ($a, $b) {
                    return \strcmp($a["desc"], $b["desc"]);
                });
                return $this->setData("output", $this->setData("apis", $map)->setData("l", $this->getLocale("en"))->renderHTML("apis"));
                break;

            case "GetAndroidJs":
                $this->checkRateLimit();
                $f = WWW . "/js/android-app.js";
                if (\file_exists($f)) {
                    $content = @\file_get_contents($f);
                    $time = \filemtime($f) ?? null;
                    $version = \hash("sha256", $content);
                } else {
                    $content = null;
                    $version = null;
                    $time = null;
                }
                return $this->writeJsonData([
                    "js" => $content,
                    "timestamp" => $time,
                    "version" => $version,
                ], $extras);
                break;

            case "GetAndroidCss":
                $this->checkRateLimit();
                $f = WWW . "/css/android.css";
                if (\file_exists($f)) {
                    $content = @\file_get_contents($f);
                    $time = \filemtime($f) ?? null;
                    $version = \hash("sha256", $content);
                } else {
                    $content = null;
                    $version = null;
                    $time = null;
                }
                return $this->writeJsonData([
                    "css" => $content,
                    "timestamp" => $time,
                    "version" => $version,
                ], $extras);
                break;

            case "GetCoreVersion":
                $this->checkRateLimit();
                $d = [];
                $d["LASAGNA"]["core"]["date"] = (string) $data["VERSION_DATE"];
                $d["LASAGNA"]["core"]["revisions"] = (int) $data["REVISIONS"];
                $d["LASAGNA"]["core"]["timestamp"] = (int) $data["VERSION_TIMESTAMP"];
                $d["LASAGNA"]["core"]["version"] = (string) $data["VERSION"];
                return $this->writeJsonData($d, $extras);
                break;

            case "ReadArticles":
                $this->checkRateLimit();
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
                $f = DATA . "/summernote_${profile}_${hash}.json";
                $data = "";
                $time = null;
                if (\file_exists($f)) {
                    $data = @\file_get_contents($f);
                    $time = \filemtime($f);
                }
                $crc = hash("sha256", $data);
                if (isset($_GET["crc"])) {
                    if ($_GET["crc"] == $crc) { // NOT MODIFIED
                        return $this->writeJsonData(304, $extras);
                    }
                }
                return $this->writeJsonData([
                    "crc" => $crc,
                    "hash" => $hash,
                    "html" => $data,
                    "profile" => $profile,
                    "timestamp" => $time,
                ], $extras);
                break;
        }

        // get language and locale
        $language = \strtolower($presenter[$view]["language"]) ?? "cs";
        $locale = $this->getLocale($language);
        $hash = \hash("sha256", (string) \json_encode($locale));

        switch ($view) {
            case "GetCsDataVersion":
            case "GetEnDataVersion":
                $d = [];
                $d["LASAGNA"]["data"]["language"] = $language;
                $d["LASAGNA"]["data"]["version"] = $hash;
                return $this->writeJsonData($d, $extras);
                break;

            default:
                ErrorPresenter::getInstance()->process(404);
        }
        return $this;
    }

    /**
     * Validate system language
     *
     * @param string $lang language 2-char code
     * @return string correct language code
     */
    private function validateLanguage($lang = "en")
    {
        $lang = \substr(\strtolower((string) $lang), 0, 2);
        if (!\in_array($lang, [
            "cs",
            //"de",
            "en",
            "sk",
        ])) {
            $lang = "en";
        }
        return $lang;
    }
}
