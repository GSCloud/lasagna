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
    public function process()
    {
      $climate = new League\CLImate\CLImate;
      $climate->out("<green><blue>Tesseract Doctor");

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

        echo "\nPHP:\n";
        validate("shit", true);
        validate("foo bar", false);
        echo "\nFilesystem:\n";
        validate("shit", true);
        validate("foo bar", false);

        echo "\n";
        exit;

    }
}
