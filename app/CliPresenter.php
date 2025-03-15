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

use Cake\Cache\Cache;
use League\CLImate\CLImate;

/**
 * CLI Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class CliPresenter extends APresenter
{
    /**
     * Controller processor
     * 
     * @param mixed $param optional parameter
     * 
     * @return self
     */
    public function process($param = null)
    {
        $cli = new CLImate;
        $cli->out(
            "<bold><green>Tesseract CLI</green></bold>\tapp: "
            . $this->getData("VERSION_SHORT")
            . " (" . $this->getData("VERSION_DATE") . ")\n"
        );
        return $this;
    }

    /**
     * Show presenter output
     * 
     * @param mixed $p presenter name
     * 
     * @return void
     */
    public function show($p = "home")
    {
        $p = \trim($p);
        if (empty($p) || !\strlen($p)) {
            // no presenter
            die("FATAL ERROR: No presenter is set!\n");
        }

        $data = $this->getData();
        $this->dataExpander($data);
        $router = $this->getRouter();
        $presenter = $this->getPresenter();
        $route = $router[$p];
        $pres = $route["presenter"] ?? "home";
        $data["view"] = $route["view"] ?? "home";
        $data["controller"] = $c = \ucfirst(\strtolower($pres)) . "Presenter";
        $controller = "\\GSC\\{$c}";
        echo $controller::getInstance()
            ->setData($data)->process()->getData()["output"] ?? "";
        exit;
    }

    /**
     * Show CORE presenter output
     * 
     * @param string $v   view name inside CORE presenter
     * @param array  $arg arguments (optional)
     * 
     * @return void
     */
    public function showCore($v = "Home", $arg = null)
    {
        $v = \trim($v);
        if (empty($v) || !\strlen($v)) { // no view
            die("FATAL ERROR: No view is set!\n");
        }
        $data = $this->getData();
        $this->dataExpander($data);
        $router = $this->getRouter();
        $presenter = $this->getPresenter();
        $data["controller"] = $c = "CorePresenter";
        $controller = "\\GSC\\{$c}";
        $data["view"] = $v;
        $data["base"] = $arg["base"] ?? "https://example.com/";
        $data["match"] = $arg["match"] ?? null;
        echo $controller::getInstance()
            ->setData($data)->process()->getData()["output"] ?? "";
        exit;
    }

    /**
     * Display user defined constants
     *
     * @return self
     */
    public function showConst()
    {
        $arr = \array_filter(
            \get_defined_constants(true)["user"], function ($key) {
                // filter out Sodium constants
                return !(\stripos($key, "sodium") === 0);
            }, ARRAY_FILTER_USE_KEY
        );
        \dump($arr);
        return $this;
    }

    /**
     * Display CLI help
     * 
     * @return self
     */
    public function help()
    {
        $cli = new CLImate;
        $cli->out(
            "Usage: \t<bold>php -f Bootstrap.php"
            . " <command> [<param> ...]</bold>\n"
        );
        $cli->out("<bold>app</bold> '<code>'\t- run inline code");
        $cli->out("<bold>clearcache</bold>\t- clear cache");
        $cli->out("<bold>clearci</bold>\t\t- clear CI logs");
        $cli->out("<bold>clearlogs</bold>\t- clear runtime logs");
        $cli->out("<bold>cleartemp</bold>\t- clear temp files");
        $cli->out("<bold>doctor</bold>\t\t- run config Doctor");
        $cli->out("<bold>refresh</bold>\t\t- refresh cloud data");
        $cli->out("<bold>local</bold>\t\t- local tests");
        $cli->out("<bold>prod</bold>\t\t- production tests");
        $cli->out("<bold>unit</bold>\t\t- run Unit tests");
        $cli->out("");
        \chdir(APP);
        foreach (\glob("Cli*.php") as $class) {
            if ($class) {
                $class = \str_replace('.php', '', $class);
                if ($class === 'CliPresenter') {
                    continue;
                }
                $name = \str_replace('Cli', '', $class);
                $name = \strtolower($name);
                $class = "\\GSC\\{$class}";
                if (\class_exists($class)) {
                    if (\method_exists($class, 'help')) {
                        $help = $class::getInstance()->help();
                        $t = '';
                        if (\strlen($name) < 8) {
                            $t = "\t";
                        }
                        $cli->out("<bold>{$name}</bold>\t{$t}- {$help}");
                    }
                }
            }
        }
        exit(0);
    }

    /**
     * Evaluate input string
     *
     * @param $app  object this object
     * @param $argc int ARGC
     * @param $argv array ARGV
     * 
     * @return self
     */
    public function evaler($app, $argc, $argv)
    {
        $cli = new CLImate;
        if ($argc != 3) {
            $cli->out("Tesseract app examples:\n");
            $cli->out('<bold>app</bold> \'$app->showConst()\'');
            $cli->out('<bold>app</bold> \'dump($app->getIdentity())\'');
            $cli->out('<bold>app</bold> \'dump($app->show("home"))\'');
        } else {
            $code = \trim($argv[2]) . ';';
            $data = $this->getData();
            $this->dataExpander($data);
            try {
                eval($code);
            } catch (ParseError $e) {
                echo 'Caught exception: ' . $e->getMessage() . "\n";
            }
            \error_reporting(E_ALL);
        }
        echo "\n";
        return $this;
    }

    /**
     * Select CLI module
     *
     * @param $module string CLI parameter
     * @param $argc   int ARGC number of arguments
     * @param $argv   array ARGV array of arguments
     * 
     * @return void
     */
    public function selectModule($module, $argc = null, $argv = null)
    {
        $cli = new CLImate;
        $module = \trim($module);

        switch ($module) {
        case "refresh":
            $this->setForceCsvCheck();
            $this->postloadAppData('app_data');
            break;
        case "local":
        case "prod":
        case "testlocal":
        case "testprod":
            include_once "CiTester.php";
            new CiTester($this->getCfg(), $this->getPresenter(), $module);
            break;
        case "clearcache":
            foreach ($this->getData("cache_profiles") as $k => $v) {
                Cache::clear($k);
                Cache::clear("{$k}_file");
                echo '.';
            }
            \array_map('unlink', glob(CACHE . DS . "*.php") ?: []);
            \array_map('unlink', glob(CACHE . DS . "*.tmp") ?: []);
            \array_map('unlink', glob(CACHE . DS . CACHEPREFIX . "*") ?: []);
            \clearstatcache();
            $data = $this->getData();
            $admin = AdminPresenter::getInstance()->setData($data);
            $admin->flushCache();
            $cli->out("🧹 cache");
            break;
        case "clearci":
            $files = \glob(ROOT . DS . "ci" . DS . "*") ?: [];
            $c = \count($files);
            if ($c) {
                \array_map('unlink', $files);
                $cli->out("🧹 CI logs <bold>$c file(s)</bold>");
            }
            break;
        case "clearlogs":
            $files = \glob(LOGS . DS . "*") ?: [];
            $c = \count($files);
            if ($c) {
                \array_map('unlink', $files);
                $cli->out("🧹 other logs <bold>$c file(s)</bold>");
            }
            break;
        case "cleartemp":
            $files = \glob(TEMP . DS . "*") ?: [];
            $c = \count($files);
            if ($c) {
                \array_map('unlink', $files);
                $cli->out("🧹 temp <bold>$c file(s)</bold>");
            }
            break;
        case "test":
        case "unit":
            include_once "UnitTester.php";
            new UnitTester;
            break;
        case "doctor":
            include_once "Doctor.php";
            new Doctor;
            break;
        case "app":
            $this->evaler($this, $argc, $argv);
            break;
        default:
            $class = \ucfirst(\strtolower($module));
            $class = "\\GSC\\Cli{$class}";
            if (\class_exists($class)) {
                $class::getInstance()->setData($this->getData())->process();
                break;
            }
            $this->help();
        }
        exit(0);
    }
}
