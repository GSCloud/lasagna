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
        $climate->out("<green><bold>Tesseract Doctor");

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
                $climate->out("<green><bold>[√]</bold></green> $message");
            } else {
                $climate->out("<red><bold>[!]</bold></red> $message");
            }
        }

        echo "\nFile System\n";
        validate("file\tCONFIG\tas " . CONFIG, check_exist(CONFIG));
        validate("directory\tAPP\tas " . APP, check_exist(APP));
        validate("file\trouter_defaults.neon\tin APP", check_exist(APP . "/router_defaults.neon"));
        validate("file\trouter_admin.neon\tin APP", check_exist(APP . "/router_admin.neon"));
        validate("file\trouter.neon\tin APP", check_exist(APP . "/router.neon"));
        validate("file\tVERSION\tas " . ROOT . "/VERSION", check_exist(ROOT . "/VERSION"));
        validate("directory\tCACHE\tas " . CACHE, check_exist(CACHE));
        validate("writable\tCACHE", check_write(CACHE));
        validate("directory\tDATA\tas " . DATA, check_exist(DATA));
        validate("writable\tDATA", check_write(DATA));
        validate("directory\tWWW\tas " . WWW, check_exist(WWW));
        validate("directory\tTEMPLATES\tas " . TEMPLATES, check_exist(TEMPLATES));
        validate("directory\tPARTIALS\tas " . PARTIALS, check_exist(PARTIALS));
        validate("directory\tTEMP\tas " . TEMP, check_exist(TEMP));
        validate("writable\tTEMP", check_write(TEMP));

        echo "\nPHP\n";
        validate("PHP 7.3+", (PHP_VERSION_ID >= 70300));
        validate("lib curl", (in_array("curl", get_loaded_extensions())));
        validate("lib json", (in_array("json", get_loaded_extensions())));
        validate("lib mbstring", (in_array("mbstring", get_loaded_extensions())));
        validate("lib sodium", (in_array("sodium", get_loaded_extensions())));

        echo "\n";
        exit;
    }
}
