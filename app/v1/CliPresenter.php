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

class CliPresenter extends \GSC\APresenter
{

    public function process()
    {
        return $this;
    }

    public function showConst()
    {
        print_r(get_defined_constants(true)["user"]);
        return $this;
    }

    public function evaler($app, $argc, $argv)
    {
        if ($argc != 3) {
            echo 'Example: app \'$app->showConst()\'' . "\n";
            echo 'Example: app \'echo $app->getLocale()["\$lasagna"]\'' . "\n";
            echo 'Example: app \'print_r($app->getIdentity())\'' . "\n";
            echo 'Example: app \'print_r($app->getLocale())\'' . "\n";
            return $this;
        }
        try {
            eval(trim($argv[2]) . ";");
            echo "\n";
        } catch(Exception $e) {
            print_r($e);
        }
        return $this;
    }
}
