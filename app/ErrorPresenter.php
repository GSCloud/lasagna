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
 * Error Presenter
 */
class ErrorPresenter extends APresenter
{
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
     * @param int $error error code (optional)
     */
    public function process($err = null)
    {
        $this->setHeaderHtml();
        if (is_int($err)) {
            $code = $err;
        } else {
            $match = $this->getMatch();
            $params = (array) ($match["params"] ?? []);
            if (array_key_exists("code", $params)) {
                $code = (int) $params["code"];
            } else {
                $code = 404;
            }
        }
        if (!isset(self::CODESET[$code])) {
            $code = 400;
        }
        $error = self::CODESET[$code];
        header("HTTP/1.1 ${code} ${error}");
        $template = "<body><center><h1>🤔 HTTP Error $code 💣</h1><h2>" . self::CODESET[$code] . "</h2><h3><a href='/'>↻</a></h3></body>";
        return $this->setData("output", $this->renderHTML($template));
    }
}
