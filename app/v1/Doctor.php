<?php
/**
 * GSC Tesseract LASAGNA
 *
 * @category Framework
 * @package  LASAGNA
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

class Doctor extends \GSC\APresenter
{
    /**
     * Void.
     *
     * @return void
     */
    public function process()
    {}

    /**
     * Tesseract Doctor
     *
     * @return void
     */
    public function check()
    {
        $climate = new League\CLImate\CLImate;
        $climate->out("<green><blue>Tesseract Doctor");

        function check_exist($f)
        {
            if (!file_exists($f) || !is_readable($f)) {
                return false;
            }
            return true;
        }

        function check_write($f)
        {
            if (!is_writable($f)) {
                return false;
            }
            return true;
        }

        function validate($message, $result)
        {
            $climate = new League\CLImate\CLImate;
            $result = (bool) $result;
            if ($result) {
                $climate->out("<green><bold>[√]</bold> $message");
            } else {
                $climate->out("<red><bold>[!]</bold> $message");
            }
        }

        echo "\nFilesystem\n";
        validate("file:\t" . CONFIG, check_exist(CONFIG));
        validate("folder:\t" . ROOT . "/VERSION", check_exist(ROOT . "/VERSION"));
        validate("folder:\t" . CACHE, check_exist(CACHE));
        validate("writable:\t" . CACHE, check_write(CACHE));
        validate("folder:\t" . DATA, check_exist(DATA));
        validate("writable:\t" . DATA, check_write(DATA));
        validate("folder:\t" . WWW, check_exist(WWW));
        validate("folder:\t" . PARTIALS, check_exist(PARTIALS));
        validate("folder:\t" . TEMP, check_exist(TEMP));
        validate("writable:\t" . TEMP, check_write(TEMP));
        validate("folder:\t" . TEMPLATES, check_exist(TEMPLATES));

        echo "\nPHP\n";
        validate("PHP version 7.3+", (PHP_VERSION_ID >= 70300));

        echo "\n";
        exit;
    }
}
