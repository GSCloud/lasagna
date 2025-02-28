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

use League\CLImate\CLImate;
use Tester\Assert;

/**
 * Unit Tester CLI class
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class UnitTester
{
    /**
     * Unit Test constructor
     *
     * @return void
     */
    public function __construct()
    {
        $climate = new CLImate;
        $climate->out('<green><bold>Tesseract Unit Tester');

        \Tracy\Debugger::timer('UNIT');
        \Tester\Environment::setup();

        // testable controllers
        $controllers = [
            'AdminPresenter',
            'ApiPresenter',
            'ArticlePresenter',
            'CliPresenter',
            'CliDemo',
            'CliVersion',
            'CorePresenter',
            'ErrorPresenter',
            'HomePresenter',
            'LoginPresenter',
            'LogoutPresenter',
            'RSSPresenter',
        ];

        // test controllers
        foreach ($controllers as $c) {
            $controller = "\\GSC\\$c";

            // get Singletons
            $app = $controller::getInstance();
            $app2 = $controller::getInstance();

            // compare Singletons
            Assert::same($app, $app2);

            // check instance type
            Assert::type('\\GSC\\APresenter', $app);

            // getData(), setData(), getCfg()
            Assert::same($app->getData('cfg'), $app->getCfg());
            Assert::same(null, $app->getData('foo'));
            Assert::same(null, $app->getData('foo.bar'));
            Assert::same(null, $app->getData('foo.bar.testing'));
            Assert::same(null, $app->getData('just.null.testing'));
            Assert::type('array', $app->getData());

            $app->setData('foo.bar.testing', 'just_a_test');
            Assert::same(['testing' => 'just_a_test'], $app->getData('foo.bar'));

            $app->setData('animal.farm', ['dog', 'cat', 'bird']);
            Assert::same(['farm' => ['dog', 'cat', 'bird']], $app->getData('animal')); // phpcs:ignore

            // magic __toString()
            Assert::truthy(strlen($app->__toString()));
            Assert::type('string', $app->__toString());

            // getIP()
            Assert::same('127.0.0.1', $app->getIP());

            // fluent interface
            Assert::same($app, $app->checkLocales());
            Assert::same($app, $app->checkPermission());
            Assert::same($app, $app->checkRateLimit());

            // these methods should return null when invoked from CLI
            Assert::same(null, $app->getRateLimit());
            Assert::same(null, $app->getUserGroup());
            Assert::same(null, $app->getView());

            // getUID()
            Assert::same('24c2188ba4b928341a0a24e95bbaf8631498ef931df81860d7784ceb814fd6b3', $app->getUID()); // phpcs:ignore

            // getUIDstring()
            Assert::same('CLI_127.0.0.1', $app->getUIDstring());

            // renderHTML()
            Assert::same('<b>cat</b>', $app->renderHTML('<b>{{animal.farm.1}}</b>'));
            Assert::same('<b>dog</b>', $app->renderHTML('<b>{{animal.farm.0}}</b>'));
            Assert::same('<title></title>', $app->renderHTML('<title>{{notitle}}</title>')); // phpcs:ignore
            Assert::same('<title>foo bar</title>', $app->setData('title', 'foo bar')->renderHTML('<title>{{title}}</title>')); // phpcs:ignore
            Assert::same('dogcatbird', $app->renderHTML('{{#animal.farm}}{{.}}{{/animal.farm}}')); // phpcs:ignore
        }
        echo 'Unit test finished in ' . round((float) \Tracy\Debugger::timer('UNIT') * 1000, 2) . ' ms'; // phpcs:ignore
    }
}
