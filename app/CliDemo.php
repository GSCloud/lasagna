<?php
/**
 * GSC Tesseract
 *
 * @author   Fred Brooker <git@gscloud.cz>
 * @category Framework
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

/**
 * CLI Demo class
 * 
 * @package GSC
 */
class CliDemo extends APresenter
{
    /**
     * Controller constructor
     */
    public function __construct() {
        echo "__contruct: foo bar\n";
    }

    /**
     * Controller processor
     * 
     * @return self
     */
    public function process()
    {
        echo "process: Hello World!\n";
        return $this;
    }
}
