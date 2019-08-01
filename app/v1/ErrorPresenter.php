<?php

class ErrorPresenter extends GSC\APresenter
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
        $data["l"] = $this->getLocale("en");
        $template = "<body><h1>HTTP Error $code</h1><h2>{{ l.server_error_${code} }}</h2><p>{{ l.server_error_info_${code} }}</p></body>";
        $output = $this->setData($data)->renderHTML($template);
        return $this->setData($data, "output", $output);
    }
}
