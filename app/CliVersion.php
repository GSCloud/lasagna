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

/**
 * CLI Version class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
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
     * Controller - show version information
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
            "ARGUMENTS" => $data["ARGV"],
        ];
        foreach ($out as $x => $y) {
            if (\is_numeric($y)) {
                echo "\e[0;1m$x\e[0m: $y\n";
            }
            if (\is_string($y)) {
                echo "\e[0;1m$x\e[0m: $y\n";
            }
            if (\is_array($y)) {
                $y = join(" ", $y);
                echo "\e[0;1m$x\e[0m: $y\n";
            }
        }
        exit;
    }

    /**
     * Help information for the CLI command
     *
     * @return string information for the CLI command
     */
    public function help()
    {
        return "app version";
    }
}
