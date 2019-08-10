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

ob_end_clean();
$climate = new League\CLImate\CLImate;
$climate->out("<green>Welcome to <bold>Tesseract LASAGNA Unit Tester\n");

Tester\Environment::setup();

$o = new Greeting;
Assert::same('Hello John', $o->say('John')); # we expect the same
Assert::same('Hi John', $o->say('John'));

Assert::exception(function () use ($o) { # we expect an exception
$o->say('');
}, InvalidArgumentException::class, 'Invalid name');

class Greeting
{
    public function say($name)
    {
        if (!$name) {
            throw new InvalidArgumentException('Invalid name');
        }
        return "Hello $name";
    }
}