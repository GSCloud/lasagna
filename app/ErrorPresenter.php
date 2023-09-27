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

declare (strict_types = 1);

namespace GSC;

/**
 * Error Presenter class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
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
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        600 => "Unsupported Browser",
    ];

    /**
     * Controller processor
     *
     * @param int $err error code (optional)
     * 
     * @return object Controller
     */
    public function process($err = null)
    {
        $this->setHeaderHtml();

        // get the error code as a parameter or from URL
        $code = 404;
        if (\is_int($err)) {
            $code = $err;
        } else {
            $match = $this->getMatch();
            if (\is_array($match)) {
                $params = (array) ($match["params"] ?? []);
                if (\array_key_exists("code", $params)) {
                    $code = (int) $params["code"];
                }
            }
        }

        // check validity of the code
        if (!isset(self::CODESET[$code])) {
            $code = 404;
        }
        // error message
        $error = self::CODESET[$code];

        // set HTTP error code
        header("HTTP/1.1 {$code} {$error}");

        // find error image by extension
        $img = "error.png";
        if (\file_exists(WWW . "/img/{$code}.png")) {
            $img = "{$code}.png";
        } elseif (\file_exists(WWW . "/img/{$code}.jpg")) {
            $img = "{$code}.jpg";
        } elseif (\file_exists(WWW . "/img/{$code}.webp")) {
            $img = "{$code}.webp";
        }

        // HTML5 template
        $template = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $template .= '<meta http-equiv="x-ua-compatible" content="IE=edge"><body>';
        $template .= "<center><h1><br>🤔 Error {$code}</h1>";
        $template .= '<h2>' . self::CODESET[$code] . '</h2>';
        $template .= '<h2><center><a rel=nofollow '
            . 'style="color:red;text-decoration:none" href="/?nonce='
            . $this->getNonce()
            . '">click here to go the main page ↻</a></center></h2>';
        $template .= "<img style='border:10px solid #000;' height='100%'"
            . " alt='$error' src=/img/$img></center></body></html>";

        // export
        return $this->setData("output", $this->renderHTML($template));
    }
}
