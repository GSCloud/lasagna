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

declare (strict_types = 1);

namespace GSC;

/**
 * Error Presenter class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class ErrorPresenter extends APresenter
{
    // known HTTP status codes
    const CODESET = [
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        409 => "Conflict",
        410 => "Gone",
        412 => "Precondition Failed",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        420 => "Enhance Your Calm",
        429 => "Too Many Requests",
        500 => "Internal Server Error",
        503 => "Service Unavailable",
        600 => "Unsupported Browser",
    ];

    // custom messages
    const MESSAGE = [
        400 => "Bad request received 🪲",
        401 => "You are not unauthorized 👾",
        402 => "Payment is required 🤑",
        403 => "Access is forbidden ⛔️",
        404 => "Not found! 😵",
        405 => "Method is not allowed 🤒",
        406 => "Not acceptable 🤒",
        409 => "Conflict 😒",
        410 => "Gone 🏃‍♀️",
        412 => "Precondition failed 🤒",
        415 => "Unsupported media type 🤒",
        416 => "Requested range not satisfiable 🙅",
        417 => "Expectation failed 🙅",
        420 => "Enhance your calm ⌛️",
        429 => "You are banned for 30 minutes 🤯",
        500 => "Internal server error 👾",
        503 => "Service is currently unavailable 👾",
        600 => "This is an unsupported browser 🎠",
    ];

    /**
     * Controller processor
     *
     * @param int $err HTTP status code (optional)
     * 
     * @return object Controller
     */
    public function process($err = null)
    {
        $this->setHeaderHtml();

        // get the error code as a parameter or from the URL
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

        // check code validity
        if (!isset(self::CODESET[$code])) {
            $code = 404;
        }
        $error = self::CODESET[$code];

        // set HTTP error code
        header("HTTP/1.1 {$code} {$error}");

        // find error image extension
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
        $template .= '<h2>' . self::MESSAGE[$code] . '</h2>';
        $template .= '<h2><center><a rel=nofollow '
            . 'style="color:red;text-decoration:none" href="/?nonce='
            . $this->getNonce()
            . '">click here to go the main page ↻</a></center></h2>';
        $template .= "<img style='border:10px solid #000;' height='100%'"
            . " alt='$error' "
            . "src='https://cdn.gscloud.cz/img/$img'></center></body></html>";
        return $this->setData("output", $this->renderHTML($template));
    }
}
