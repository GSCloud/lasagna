<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

/**
 * Error Presenter
 */
class ErrorPresenter extends APresenter
{
    /** @const array Error codes */
    const CODESET = [
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        406 => "Not Acceptable",
        410 => "Gone",
        420 => "Enhance Your Calm",
        429 => "Too Many Requests",
        500 => "Internal Server Error",
    ];

    /**
     * Main controller
     *
     * @return object Singleton instance
     */
    public function process()
    {
        $data = $this->getData();
        $match = $this->getMatch();

        $params = $match["params"] ?? [];
        if (array_key_exists("code", $params)) {
            $code = (int) $params["code"];
        } else {
            $code = 404;
        }
        if (!isset(self::CODESET[$code])) {
            $code = 400;
        }
        $error = self::CODESET[$code];
        header("HTTP/1.1 ${code} ${error}");

        $data["lang"] = "en";
        $data["l"] = $l = $this->getLocale("en");
        if (is_null($l)) {
            $template = "<body><h1>HTTP Error $code</h1><h2>".self::CODESET[$code]."</h2></body>";
        } else {
            $template = "<body><h1>HTTP Error $code</h1><h2>{{ l.server_error_${code} }}</h2><p>{{ l.server_error_info_${code} }}</p></body>";
        }
        $output = $this->setData($data)->renderHTML($template);
        return $this->setData($data, "output", $output);
    }
}
