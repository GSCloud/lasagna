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
        404 => "Not found 😵",
        405 => "Method is not allowed 🤒",
        406 => "Not acceptable 🤒",
        409 => "Conflict 😒",
        410 => "Gone 🏃‍♀️",
        412 => "Precondition failed 🤒",
        415 => "Unsupported media type 🤒",
        416 => "Requested range not satisfiable 🙅",
        417 => "Expectation failed 🙅",
        420 => "Enhance your calm ⌛️",
        429 => "Enhance your calm ⌛️",
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
        // Model
        if (!\is_array($data = $this->getData())) {
            return $this;
        }

        $this->setHeaderHtml();
        
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

        // check validity
        if (!isset(self::CODESET[$code])) {
            $code = 404;
        }
        $error = self::CODESET[$code];

        // HTTP error code
        header("HTTP/1.1 {$code} {$error}");

        // find error image
        $image = "error.png";
        if (\file_exists(WWW . "/img/{$code}.png")) {
            $image = "{$code}.png";
        } elseif (\file_exists(WWW . "/img/{$code}.jpg")) {
            $image = "{$code}.jpg";
        } elseif (\file_exists(WWW . "/img/{$code}.webp")) {
            $image = "{$code}.webp";
        }

        // render error template
        $template = "error";
        $message = self::MESSAGE[$code];
        $data['code'] = $code;
        $data['error'] = $error;
        $data['image'] = $image;
        $data['message'] = $message;
        $output = $this->setData($data)->renderHTML($template);
        return $this->setData('output', $output);
    }
}
