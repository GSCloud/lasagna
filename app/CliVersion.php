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

/**
 * CLI Version class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class CliVersion extends APresenter
{
    /**
     * Controller constructor
     */
    public function __construct()
    {
    }

    /**
     * Controller processor - Show version information as a JSON formatted string.
     * 
     * @param mixed $param optional parameter
     *
     * @return mixed null
     */
    public function process($param = null)
    {
        $data = $this->getData();
        if (!\is_array($data)) {
            return null;
        }
        $out = [
            "ENGINE" => $data["ENGINE"],
            "REVISIONS" => $data["REVISIONS"],
            "VERSION" => $data["VERSION"],
            "VERSION_SHORT" => $data["VERSION_SHORT"],
            "VERSION_DATE" => $data["VERSION_DATE"],
            "VERSION_TIMESTAMP" => $data["VERSION_TIMESTAMP"],
        ];
        foreach ($out as $x => $y) {
            if (\is_string($x)) {
                echo "\e[0;1m$x\e[0m: $y\n";
            }
        }
        echo "\n";
        exit;
    }
}
