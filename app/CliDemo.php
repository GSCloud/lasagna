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
 * CLI Demo class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class CliDemo extends APresenter
{
    /**
     * Controller constructor
     */
    public function __construct()
    {
    }

    /**
     * Controller processor
     * 
     * @param mixed $param optional parameter
     * 
     * @return object instance
     */
    public function process($param = null)
    {
        echo "Hello World! 🌎️\n";
        $data = $this->getData();
        return $this;
    }

    /**
     * Help information for the CLI command
     *
     * @return string information for the CLI command
     */
    public function help()
    {
        return "Hello World 🌎️ demo";
    }
}
