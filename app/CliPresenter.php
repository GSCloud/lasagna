<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

use League\CLImate\CLImate;

/**
 * CLI Presenter
 */
class CliPresenter extends APresenter
{
    /**
     * Main controller
     *
     * @return object Singleton instance
     */
    public function process()
    {
        $climate = new CLImate;
        $climate->out("\n<bold><green>Tesseract CLI</green></bold>\tapp: "
            . $this->getData("VERSION_SHORT")
            . " (" . str_replace(" ", "", $this->getData("VERSION_DATE")) . ")");
        return $this;
    }

    /**
     * Display user defined constants
     *
     * @return object Singleton instance
     */
    public function showConst()
    {
        dump(get_defined_constants(true)["user"]);
        return $this;
    }

    /**
     * Display CLI help
     *
     * @return object Singleton instance
     */
    public function help()
    {
        $climate = new CLImate;

        $climate->out("Usage: php -f Bootstrap.php <command> [<parameters>...] \n");
        $climate->out("\t <bold>app</bold> '<code>' \t - run inline code");
        $climate->out("\t <bold>doctor</bold> \t - check system requirements");
        $climate->out("\t <bold>unit</bold> \t\t - Unit test");
        $climate->out("\t <bold>local</bold> \t\t - local CI test");
        $climate->out("\t <bold>prod</bold> \t\t - production CI test\n");

        return $this;
    }

    /**
     * Evaluate input string
     *
     * @param object $app this :)
     * @param int $argc ARGC
     * @param array $argv ARGV
     * @return object Singleton instance
     */
    public function evaler($app, $argc, $argv)
    {
        $climate = new CLImate;
        if ($argc != 3) {
            $climate->out("Examples:\n");
            $climate->out('<bold>app</bold> \'$app->showConst()\'');
            $climate->out('<bold>app</bold> \'dump($app->getIdentity())\'');
        } else {
            try {
                eval(trim($argv[2]) . ";");
            } catch (Exception $e) {}
        }
        echo "\n";
        return $this;
    }

    /**
     * Select CLI module
     *
     * @param string $module CLI parameter
     * @param int $argc ARGC
     * @param array $argv ARGV
     * @return void
     */
    public function selectModule($module, $argc, $argv)
    {
        switch ($module) {
            case "local":
            case "prod":
            case "testlocal":
            case "testprod":
                require_once "CiTester.php";
                new CiTester($this->getCfg(), $this->getPresenter(), $module);
                break;

            case "unit":
                require_once "UnitTester.php";
                new UnitTester;
                break;

            case "doctor":
                require_once "Doctor.php";
                new Doctor;
                break;

            case "app":
                $this->evaler($this, $argc, $argv);
                break;

            default:
                $this->help();
                break;
        }
        exit;
    }
}