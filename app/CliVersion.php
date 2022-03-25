<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

/**
 * CLI Version
 */
class CliVersion extends APresenter
{
    public function __construct() {}

    public function process()
    {
        $data = $this->getData();
        $out = [
            "TESSERACT" => "Tesseract 2.0 beta",
            "ARGUMENTS" => $data["ARGV"],
            "REVISIONS" => $data["REVISIONS"],
            "VERSION" => $data["VERSION"],
            "VERSION_DATE" => $data["VERSION_DATE"],
            "VERSION_SHORT" => $data["VERSION_SHORT"],
            "VERSION_TIMESTAMP" => $data["VERSION_TIMESTAMP"],
        ];
        echo \json_encode($out, JSON_PRETTY_PRINT)."\n";
    }
}
