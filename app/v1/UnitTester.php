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

use Tester\Assert;

class UnitTester extends \GSC\APresenter
{
    /**
     * Void.
     *
     * @return void
     */
    public function process()
    {}

    public function test()
    {
        $climate = new League\CLImate\CLImate;
        $climate->out("<blue><bold>Tesseract Unit Tester");
        Tester\Environment::setup();

        Assert::same('Hello John', "Hello John");
        Assert::same('Hi John', 'Yo John');
    }
}
