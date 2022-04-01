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

use League\CLImate\CLImate;
use Tester\Assert;

/**
 * Unit Tester CLI class
 *
 * @package GSC
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
        \Tracy\Debugger::timer("UNIT");
        \Tester\Environment::setup();

        $climate = new CLImate;
        $climate->out("<green><bold>Tesseract Unit Tester");

        // all testable controllers
        $controllers = [
            'AdminPresenter',
            'ApiPresenter',
            'ArticlePresenter',
            'CliPresenter',
            'CliDemo',
            'CliVersion',
            'ErrorPresenter',
            'HomePresenter',
            'LoginPresenter',
            'LogoutPresenter',
            'RSSPresenter',
        ];

        // check controllers
        foreach ($controllers as $c) {
            $controller = "\\GSC\\${c}";
            $app = $controller::getInstance();

            // instance of APresenter
            Assert::type('\\GSC\\APresenter', $app);

            // getData(), setData()
            Assert::type('array', $app->getData());
            Assert::truthy(count($app->getData()));
            Assert::same($app->getData('just.null.testing'), null);
            $app->setData("animal.farm", ["dog", "cat", "bird"]);
            Assert::same($app->getData("animal"), ["farm" => ["dog", "cat", "bird"]]);

            // getCfg()
            Assert::same($app->getData('cfg'), $app->getCfg());

            // magic __toString()
            Assert::type('string', $app->__toString());
            Assert::truthy(strlen($app->__toString()));

            // getIP()
            Assert::same('127.0.0.1', $app->getIP());

            // getIdentity()
            Assert::same([
                'country' => 'XX',
                'email' => 'john.doe@example.com',
                'id' => 1,
                'ip' => '127.0.0.1',
                'name' => 'John Doe',
            ], $app->getIdentity());

            // getCurrentUser()
            Assert::same([
                'avatar' => '',
                'country' => 'XX',
                'email' => 'john.doe@example.com',
                'id' => 1,
                'name' => 'John Doe',
                'ip' => '127.0.0.1',
                'uid' => '5d93b9f0de6d0b30934db74b6d877154d704f562ad5bb88002409d51db5345c1',
                'uidstring' => 'CLI__127.0.0.1',
            ], $app->getCurrentUser());

            // addCritical(), getCriticals() - fluent interface
            Assert::same($app->addCritical(), $app);
            Assert::same($app->addCritical(false), $app);
            Assert::same($app->addCritical(null), $app);
            Assert::same($app->addCritical([]), $app);
            Assert::same($app->getCriticals(), []);
            // value test
            Assert::same($app->addCritical('test message'), $app);
            Assert::same($app->getCriticals(), ['test message']);

            // addError(), getErrors() - fluent interface
            Assert::same($app->addError(), $app);
            Assert::same($app->addError(false), $app);
            Assert::same($app->addError(null), $app);
            Assert::same($app->addError([]), $app);
            Assert::same($app->getErrors(), []);
            // value test
            Assert::same($app->addError('test message'), $app);
            Assert::same($app->getErrors(), ['test message']);

            // addMessage(), getMessages() - fluent interface
            Assert::same($app->addMessage(), $app);
            Assert::same($app->addMessage(false), $app);
            Assert::same($app->addMessage(null), $app);
            Assert::same($app->addMessage([]), $app);
            Assert::same($app->getMessages(), []);
            // value test
            Assert::same($app->addMessage('test message'), $app);
            Assert::same($app->getMessages(), ['test message']);

            // addAuditMessage() - fluent interface
            Assert::same($app->addAuditMessage(), $app);
            Assert::same($app->addAuditMessage(false), $app);
            Assert::same($app->addAuditMessage(null), $app);
            Assert::same($app->addAuditMessage([]), $app);
            Assert::same($app->addAuditMessage('test message'), $app);

            // getRateLimit()
            Assert::same($app->getRateLimit(), null);

            // getView()
            Assert::same($app->getView(), null);

            // getUserGroup()
            Assert::same($app->getUserGroup(), null);

            // getUID()
            Assert::same($app->getUID(), '5d93b9f0de6d0b30934db74b6d877154d704f562ad5bb88002409d51db5345c1');

            // getUIDstring()
            Assert::same($app->getUIDstring(), 'CLI__127.0.0.1');

            // checkLocales() - fluent interface
            Assert::same($app->checkLocales(), $app);

            // checkPermission() - fluent interface
            Assert::same($app->checkPermission(), $app);

            // checkRateLimit() - fluent interface
            Assert::same($app->checkRateLimit(), $app);

            // renderHTML()
            Assert::same($app->renderHTML("<title>{{notitle}}</title>"), "<title></title>");
            Assert::same($app->setData("title", "foo bar")->renderHTML("<title>{{title}}</title>"), "<title>foo bar</title>");
            Assert::same($app->renderHTML("<b>{{animal.farm.0}}</b>"), "<b>dog</b>");
            Assert::same($app->renderHTML("<b>{{animal.farm.1}}</b>"), "<b>cat</b>");
            Assert::same($app->renderHTML('{{#animal.farm}}{{.}}{{/animal.farm}}'), "dogcatbird");
        }

        echo "Unit testing finished in: " . round((float) \Tracy\Debugger::timer("UNIT") * 1000, 2) . " ms";
    }
}
