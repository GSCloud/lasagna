<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

/**
 * CLI Demo
 */
class CliDemo extends APresenter
{
    public function __construct() {
        echo "contruct: Foo Bar\n";
    }

    public function process()
    {
        echo "process: Hello World!\n";
    }
}
