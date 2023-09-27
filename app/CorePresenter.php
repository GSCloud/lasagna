<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Core Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class CorePresenter extends APresenter
{
    /**
     * Controller processor
     * 
     * @param mixed $view  (optional)
     * @param mixed $match (optional)
     * 
     * @return self
     */
    public function process($view = null, $match = null)
    {
        $data = $this->getData();
        $presenter = $this->getPresenter();
        // can be passed as an optional parameter
        $match = $match ?? $this->getMatch();
        // can be passed as an optional parameter
        $view = $view ?? $this->getView(); 

        // JSON extras
        $extras = [
            "fn" => $view,
            "ip" => $this->getIP(),
            "name" => "Tesseract Core",
        ];

        // API calls
        switch ($view) {
        case "GetWebManifest":
            $this->setHeaderJson();
            // language set by GET parameter
            $lang = $this->validateLanguage($_GET["lang"] ?? "en");
            return $this->setData(
                "output",
                $this->setData("l", $this->getLocale($lang))->renderHTML("manifest")
            );
            break;

        case "GetTXTSitemap":
            $this->setHeaderText();
            $map = [];
            foreach ($presenter as $p) {
                if (isset($p["sitemap"]) && $p["sitemap"]) {
                    $map[] = \trim($p["path"], "/ \t\n\r\0\x0B");
                }
            }
            return $this->setData(
                "output",
                $this->setData("sitemap", $map)->renderHTML("sitemap.txt")
            );
            break;

        case "GetXMLSitemap":
            $this->setHeaderXML();
            $map = [];
            foreach ($presenter as $p) {
                if (isset($p["sitemap"]) && $p["sitemap"]) {
                    $map[] = \trim($p["path"], "/ \t\n\r\0\x0B");
                }
            }
            return $this->setData(
                "output",
                $this->setData("sitemap", $map)->renderHTML("sitemap.xml")
            );
            break;

        case "GetRSSXML":
            $this->setHeaderXML();
            $language = "en"; // set to English
            $l = $this->getLocale($language);
            if (class_exists("\\GSC\\RSSPresenter")) {
                // get items map from RSSPresenter
                $map = RSSPresenter::getInstance()->process() ?? [];
            } else {
                $map = [];
            }
            $this->setData("rss_channel_description", $l["meta_description"] ?? "");
            $this->setData("rss_channel_link", $l['$canonical_url'] ?? "");
            $this->setData("rss_channel_title", $l["title"] ?? "");
            return $this->setData(
                "output",
                $this->setData("rss_items", (array) $map)->renderHTML("rss.xml")
            );
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
            if ($x !== 2) {
                // error
                return $this->writeJsonData(400, $extras);
            }
            $options = new QROptions(
                [
                "version" => 7,
                "outputType" => QRCode::OUTPUT_IMAGE_PNG,
                "eccLevel" => QRCode::ECC_L,
                "scale" => $scale,
                "imageBase64" => false,
                "imageTransparent" => false,
                    ]
            );
            \header("Content-type: image/png");
            echo (new QRCode($options))->render(
                $text ?? "",
                CACHE . "/" . \hash("sha256", $text) . ".png"
            );
            exit;
            break;

        case "GetServiceWorker":
            $this->setHeaderJavaScript();
            $map = [];
            foreach ($presenter as $p) {
                if (isset($p["sitemap"]) && $p["sitemap"]) {
                    $map[] = \trim($p["path"], "/ \t\n\r\0\x0B");
                }
            }
            return $this->setData(
                "output",
                $this->setData("sitemap", $map)->renderHTML("sw.js")
            );
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
            if ($x !== 2) {
                // error
                return $this->writeJsonData(400, $extras);
            }
            $f = DATA . "/summernote_{$profile}_{$hash}.json";
            $data = "";
            $time = null;
            if (\file_exists($f)) {
                $data = @\file_get_contents($f);
                $time = \filemtime($f);
            }
            $crc = hash("sha256", $data);
            if (isset($_GET["crc"])) {
                if ($_GET["crc"] == $crc) {
                    // NOT MODIFIED
                    return $this->writeJsonData(304, $extras);
                }
            }
            return $this->writeJsonData(
                [
                    "crc" => $crc,
                    "hash" => $hash,
                    "html" => $data,
                    "profile" => $profile,
                    "timestamp" => $time,
                    ], $extras
            );
                break;
        }

        // get language and locale
        $language = \strtolower($presenter[$view]["language"]) ?? "en";
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
        }

        // check $view starting with API
        if (substr(strtoupper($view), 0, 3) === "API") {
            $this->checkRateLimit();
            $this->setHeaderHTML();
            $map = [];
            foreach ($presenter as $p) {
                if (isset($p["api"]) && $p["api"]) {
                    $info = $p["api_info"] ?? "";
                    StringFilters::convert_eol_to_br($info);
                    $info = \htmlspecialchars($info);
                    $info = \preg_replace(
                        array(
                            '#href=&quot;(.*)&quot;#',
                            '#&lt;(/?(?:pre|a|b|br|em|u|ul|li|ol)'
                            . '(\shref=".*")?/?)&gt;#'
                        ),
                        array('href="\1"', '<\1>'),
                        $info
                    );
                    $map[] = [
                        "count" => \count($p["api_example"]),
                        "deprecated" => (bool) $p["deprecated"] ?? false,
                        "desc" => \htmlspecialchars($p["api_description"] ?? ""),
                        "exam" => $p["api_example"] ?? [],
                        "finished" => (bool) $p["finished"] ?? false,
                        "info" => $info
                            ? "<br><blockquote>{$info}</blockquote>" : "",
                        "key" => (bool) $p["use_key"] ?? false,
                        // do not link to path with parameters!
                        "linkit" => !(\strpos($p["path"], "[") ?? false),
                        "method" => \strtoupper($p["method"]),
                        "path" => \trim($p["path"], "/ \t\n\r\0\x0B"),
                        "private" => (bool) $p["private"] ?? false,
                    ];
                }
            }
            \usort(
                $map, function ($a, $b) {
                    return \strcmp($a["desc"], $b["desc"]);
                }
            );
            return $this->setData(
                "output",
                $this->setData("apis", $map)
                    ->setData("l", $this->getLocale("en"))
                    ->renderHTML("apis")
            );
        }

        // no luck...
        ErrorPresenter::getInstance()->process(404);
        exit;
    }

    /**
     * Validate system language
     *
     * @param string $lang language 2-char code
     * 
     * @return string correct language code
     */
    public function validateLanguage($lang = "en")
    {
        $lang = \substr(\strtolower((string) $lang), 0, 2);
        if (!\in_array(
            $lang, [
            "cs",
            "en",
            "sk",
            ]
        )
        ) {
            $lang = "en";
        }
        return $lang;
    }
}
