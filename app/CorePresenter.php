<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */

namespace GSC;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use GSC\StringFilters as SF;

/**
 * Core Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class CorePresenter extends APresenter
{
    /**
     * Controller processor
     * 
     * @param mixed $param optional parameter
     * 
     * @return object Controller
     */
    public function process($param = null)
    {
        \setlocale(LC_ALL, "cs_CZ.utf8");
        \error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

        // Model
        $data = $this->getData();
        if (!\is_array($data)) {
            return $this;
        }

        // View
        $view = $this->getView();
        if (!$view) {
            return $this;
        }

        // Presenter
        $presenter = $this->getPresenter();
        if (!\is_array($presenter)) {
            return $this;
        }
        
        // API parameters
        $match = $this->getMatch();

        $extras = [
            "name" => "Tesseract Core REST API",
            "fn" => $view,
            "endpoint" => \explode('?', $_SERVER['REQUEST_URI'])[0],
            "api_quota" => "unlimited",
            "cached" => false,
            "uuid" => $this->getUID(),
            "ip" => $this->getIP(),
        ];

        switch ($view) {
        case 'clearcache':
            $this->clearBrowserCache();

        case 'clearcookies':
            $this->clearBrowserCookies();

        case 'clearbrowser':
            $this->clearBrowserStorage();
    
        case "GetRobotsTxt":
            $this->setHeaderText();
            $file = APP . DS . 'badrobots.txt';
            $bots = [];
            if (\file_exists($file) && \is_readable($file)) {
                $bots = \file(
                    // bad bots
                    APP . DS . 'badrobots.txt',
                    FILE_SKIP_EMPTY_LINES
                );
                if (\is_array($bots)) {
                    $bots = \array_map('trim', $bots);
                }
            }
            return $this->setData(
                "output",
                $this->setData("robots_disabled", $bots)->renderHTML("robots")
            );

        case "GetWebManifest":
            $this->setHeaderJson();
            $lang = $this->getData('router.defaults.language');
            $lang = $this->_validateLanguage($lang);
            $l = $this->getLocale($lang);
            return $this->setData(
                "output",
                $this->setData("l", $l)->renderHTML("manifest")
            );

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

        case "GetRSSXML":
            $this->setHeaderXML();
            $lang = $this->getData('router.defaults.language');
            $lang = $this->_validateLanguage($lang);
            $l = $this->getLocale($lang);
            if (\class_exists("\\GSC\\RSSPresenter")) {
                $map = RSSPresenter::getInstance()->process();
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

        case "GetQR":
            $this->checkRateLimit();
            $x = 0;
            if (!\is_array($match)) {
                ErrorPresenter::getInstance()->process(404);
                exit;
            }
            $scale = 5;
            if (isset($match["params"]["size"])) {
                $size = \trim($match["params"]["size"]);
                switch ($size) {
                case "m":
                    $scale = 7;
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
            $text = 'Hello World!';
            if (isset($match["params"]["trailing"])) {
                $text = \trim($match["params"]["trailing"]);
                $x++;
            }
            if ($x !== 2) {
                return $this->writeJsonData(400, $extras);
            }
            $options = new QROptions(
                [
                    "eccLevel" => QRCode::ECC_L,
                    "imageBase64" => false,
                    "imageTransparent" => false,
                    "outputType" => QRCode::OUTPUT_IMAGE_PNG,
                    "scale" => $scale,
                    "version" => 7,
                ]
            );
            $hash = \hash("sha256", $text);
            $out = (new QRCode($options))->render(
                $text,
                CACHE . "/{$hash}.png"
            );
            if (\is_string($out)) {
                \header("Content-type: image/png");
                echo $out;
            }
            exit;

        case "GetServiceWorker":
            $this->setHeaderJavaScript();

            if (\is_array($data)) {
                $g = $this->getUserGroup();
                if (\is_string($g)) {
                    $data["admin_group_{$g}"] = true;
                    $this->setData("admin_group_{$g}", true);
                }
            }
    
            return $this->setData(
                "output", $this->renderHTML("sw.js")
            );

        case "GetCoreVersion":
            $this->checkRateLimit();
            if (!\is_array($data)) {
                ErrorPresenter::getInstance()->process(404);
                exit;
            }
            $d = [];
            $d["LASAGNA"]["core"]["date"] = (string) $data["VERSION_DATE"];
            $d["LASAGNA"]["core"]["revisions"] = (int) $data["REVISIONS"];
            $d["LASAGNA"]["core"]["timestamp"] = (int) $data["VERSION_TIMESTAMP"];
            $d["LASAGNA"]["core"]["version"] = (string) $data["VERSION"];
            $d["LASAGNA"]["core"]["cdn"] = CDN;
            return $this->writeJsonData($d, $extras);

        case "ReadArticles":
            if ($this->getCfg('disable_articles')) {
                $this->setLocation(); // fn disabled
            }
            $this->checkRateLimit();
            $x = 0;
            $hash = null;
            $profile = "default";
            if (!\is_array($match)) {
                ErrorPresenter::getInstance()->process(404);
                exit;
            }
            if (isset($match["params"]["profile"])) {
                $profile = \trim($match["params"]["profile"]);
                $x++;
            }
            if (isset($match["params"]["hash"])) {
                $hash = \trim($match["params"]["hash"]);
                $x++;
            }
            if ($x !== 2) {
                return $this->writeJsonData(400, $extras);
            }
            if (!$hash) {
                return $this->writeJsonData(400, $extras);
            }
            if (!$profile) {
                return $this->writeJsonData(400, $extras);
            }
            $data = "";
            $time = null;
            $f = DATA . "/summernote_{$profile}_{$hash}.json";
            if (\file_exists($f)) {
                $data = @\file_get_contents($f);
                $time = \filemtime($f);
            }
            if (!$data) {
                $data = '';
                $time = \time();
            }
            $crc = \hash("sha256", $data);
            if (isset($_GET["crc"])) {
                if ($_GET["crc"] == $crc) {
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
        }

        $language = $this->_validateLanguage($presenter[$view]["language"]);
        $locale = $this->getLocale($language);
        $hash = \hash("sha256", (string) \json_encode($locale));

        switch ($view) {
        case "GetEnDataVersion":
        case "GetCsDataVersion":
        case "GetSkDataVersion":
        case "GetDeDataVersion":
            $d = [];
            $d["LASAGNA"]["data"]["language"] = $language;
            $d["LASAGNA"]["data"]["version"] = $hash;
            return $this->writeJsonData($d, $extras);
        }

        // check $view starting with 'API'
        if (is_string($view) && substr(strtoupper($view), 0, 3) === "API") {
            $this->checkRateLimit();
            $this->setHeaderHTML();
            $map = [];
            foreach ($presenter as $p) {
                if (isset($p["api"]) && $p["api"]) {
                    $info = $p["api_info"] ?? "";
                    SF::convertEolToBr($info);
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
                        "deprecated" => (bool) $p["deprecated"],
                        "desc" => \htmlspecialchars($p["api_description"] ?? ""),
                        "exam" => $p["api_example"] ?: [],
                        "finished" => (bool) $p["finished"] ?: false,
                        "info" => $info
                            ? "<br><blockquote>{$info}</blockquote>" : "",
                        "key" => (bool) $p["use_key"] ?: false,
                        // do not link to path with parameters!
                        "linkit" => !(\strpos($p["path"], "[") ?: false),
                        "method" => \strtoupper($p["method"]),
                        "path" => \trim($p["path"], "/ \t\n\r\0\x0B"),
                        "private" => (bool) $p["private"] ?: false,
                    ];
                }
            }
            return $this->setData(
                "output",
                $this->setData("apis", $map)
                    ->setData("l", $this->getLocale("en"))
                    ->renderHTML("apis")
            );
        }
        ErrorPresenter::getInstance()->process(404);
        exit;
    }

    /**
     * Validate system language
     *
     * @param mixed $lang language 2-char code
     * 
     * @return string correct language code
     */
    private function _validateLanguage($lang = "en")
    {
        if (!$lang) {
            return "en";
        }
        if (!\is_string($lang)) {
            return "en";
        }
        $lang = \substr(\strtolower((string) $lang), 0, 2);
        if (!\in_array(
            $lang,
            [
                "en",
                "cs",
                "sk",
                //"de",
            ]
        )
        ) {
            $lang = "en";
        }
        return $lang;
    }

    /**
     * Clears the browser cache for the current site.
     *
     * @return void
     */
    public function clearBrowserCache()
    {
        \header('Clear-Site-Data: "cache"');
        $this->addMessage('Browser cache cleared');
        $this->setLocation();
    }

    /**
     * Clears the browser cookies for the current site.
     *
     * @return void
     */
    public function clearBrowserCookies()
    {
        \header('Clear-Site-Data: "cookies"');
        $this->addMessage('Browser cookies cleared');
        $this->setLocation();
    }
    /**
     * Clears the browser's cache, cookies, and storage.
     *
     * @return void
     */
    public function clearBrowserStorage()
    {
        \header('Clear-Site-Data: "cache", "cookies", "storage"');
        $this->addMessage('Browser storage cleared');
        $this->setLocation();
    }
}
