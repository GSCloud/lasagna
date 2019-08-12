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
    /**
     * Display Tesseract heading.
     *
     * @return object Singleton instance.
     */
    public function process()
    {
        $climate = new League\CLImate\CLImate;
        $climate->out("\n<bold><green>Tesseract CLI</green></bold>\tapp: "
            . $this->getData("VERSION_SHORT")
            . " (" . str_replace(" ", "", $this->getData("VERSION_DATE"))
            . ") \n");
        return $this;
    }

    /**
     * Display user defined constants.
     *
     * @return object Singleton instance.
     */
    public function showConst()
    {
        print_r(get_defined_constants(true)["user"]);
        return $this;
    }

    /**
     * Display CLI syntax help.
     *
     * @return void
     */
    public function help()
    {
        $climate = new League\CLImate\CLImate;
        $climate->out("Usage: php -f Bootstrap.php <command> [<parameters>...] \n");
        $climate->out("\t <bold>app</bold> '<code>' \t - run inline code");
        $climate->out("\t <bold>doctor</bold> \t - check system requirements");
        $climate->out("\t <bold>testunit</bold> \t - Unit tests");
        $climate->out("\t <bold>testlocal</bold> \t - CI local tests");
        $climate->out("\t <bold>testprod</bold> \t - CI production tests");
        echo "\n";
        exit;
    }

    /**
     * Evaluate input string.
     *
     * @param object $app Singleton.
     * @param int $argc ARGC count.
     * @param array $argv ARGV array.
     * @return void
     */
    public function evaler($app, $argc, $argv)
    {
        $climate = new League\CLImate\CLImate;
        if ($argc != 3) {
            $climate->out("Examples:\n");
            $climate->out('<bold>app</bold> \'$app->showConst()\'');
            $climate->out('<bold>app</bold> \'print_r($app->getIdentity())\'');
            $climate->out('<bold>app</bold> \'print_r($app->getLocale())\'');
        } else {
            try {
                eval(trim($argv[2]) . ";");
            } catch (Exception $e) {}
        }
        echo "\n";
        exit;
    }

    /**
     * Select CLI module.
     *
     * @param string $module CLI parameter.
     * @param int $argc ARGC count.
     * @param array $argv ARGV array.
     * @return void
     */
    public function selectModule($module, $argc, $argv)
    {
        switch ($module) {
            case "testlocal":
            case "testprod":
                require_once "CiTester.php";
                $type = $module;
                CiTester::getInstance()->setData($this->getData())->test($type);
                break;

            case "testunit":
                require_once "UnitTester.php";
                UnitTester::getInstance()->test();
                break;

            case "doctor":
                require_once "Doctor.php";
                Doctor::getInstance()->setData($this->getData())->check();
                break;

            case "app":
                $this->evaler($this, $argc, $argv);
                break;

            default:
                $this->help();
                break;
        }
    }
}
