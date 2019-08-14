<?php
/**
 * GSC Tesseract LASAGNA
 *
 * @category Framework
 * @package  Unit tester
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

use Tester\Assert;
use League\CLImate\CLImate;

class UnitTester
{
    public function __construct()
    {
        $climate = new CLImate;
        $climate->out("<green><bold>Tesseract Unit Tester");
        Tester\Environment::setup();

        Assert::same('Hello John', "Hello John");
        Assert::same('Hi John', 'Yo John');
    }
}
