<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

use Cake\Cache\Cache;
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
        $climate->out("\t <bold>clearcache</bold> \t - clear cache");
        $climate->out("\t <bold>clearci</bold> \t - clear CI logs");
        $climate->out("\t <bold>clearlogs</bold> \t - clear logs");
        $climate->out("\t <bold>cleartemp</bold> \t - clear temp");
        $climate->out("\t <bold>doctor</bold> \t - check system requirements");
        $climate->out("\t <bold>local</bold> \t\t - local CI test");
        $climate->out("\t <bold>prod</bold> \t\t - production CI test");
        $climate->out("\t <bold>unit</bold> \t\t - run Unit test");
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
        $climate = new CLImate;
        switch ($module) {
            case "local":
            case "prod":
            case "testlocal":
            case "testprod":
                require_once "CiTester.php";
                new CiTester($this->getCfg(), $this->getPresenter(), $module);
                break;

            case "clearcache":
                foreach ($this->getData("cache_profiles") as $k => $v) { // clear all cache profiles
                    Cache::clear($k);
                    Cache::clear("${k}_file");
                }
                $c = count(glob(CACHE . "/*"));
                @array_map("unlink", glob(CACHE . "/*.php"));
                @array_map("unlink", glob(CACHE . "/*.tmp"));
                @array_map("unlink", glob(CACHE . "/" . CACHEPREFIX . "*"));
                clearstatcache();
                $climate->out("Cleaner: <bold>$c files - cache + Redis cleaned</bold>\n");
                break;

            case "clearci":
                $c = count(glob(ROOT . "/ci/*"));
                @array_map("unlink", glob(ROOT . "/ci/*"));
                $climate->out("Cleaner: <bold>$c files - CI logs cleaned</bold>\n");
                break;

            case "clearlogs":
                $c = count(glob(LOGS . "/*"));
                @array_map("unlink", glob(LOGS . "/*"));
                $climate->out("Cleaner: <bold>$c files - logs cleaned</bold>\n");
                break;

            case "cleartemp":
                $c = count(glob(TEMP . "/*"));
                @array_map("unlink", glob(TEMP . "/*"));
                $climate->out("Cleaner: <bold>$c files - temp cleaned</bold>\n");
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
