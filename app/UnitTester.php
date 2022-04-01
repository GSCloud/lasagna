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

            // getData()
            Assert::type('array', $app->getData());
            Assert::truthy(count($app->getData()));
            Assert::same($app->getData('just.null.testing'), null);

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

            // addCritical() and getCriticals()
            Assert::same($app->addCritical(), $app);
            Assert::same($app->addCritical(false), $app);
            Assert::same($app->addCritical(null), $app);
            Assert::same($app->addCritical([]), $app);
            Assert::same($app->getCriticals(), []);
            Assert::same($app->addCritical('test message'), $app);
            Assert::same($app->getCriticals(), ['test message']);

            // addError() and getErrors()
            Assert::same($app->addError(), $app);
            Assert::same($app->addError(false), $app);
            Assert::same($app->addError(null), $app);
            Assert::same($app->addError([]), $app);
            Assert::same($app->getErrors(), []);
            Assert::same($app->addError('test message'), $app);
            Assert::same($app->getErrors(), ['test message']);

            // addMessage() and getMessages()
            Assert::same($app->addMessage(), $app);
            Assert::same($app->addMessage(false), $app);
            Assert::same($app->addMessage(null), $app);
            Assert::same($app->addMessage([]), $app);
            Assert::same($app->getMessages(), []);
            Assert::same($app->addMessage('test message'), $app);
            Assert::same($app->getMessages(), ['test message']);

            // addAuditMessage()
            Assert::same($app->addAuditMessage(), $app);
            Assert::same($app->addAuditMessage(false), $app);
            Assert::same($app->addAuditMessage(null), $app);
            Assert::same($app->addAuditMessage([]), $app);
            Assert::same($app->addAuditMessage('test message'), $app);
        }

        echo "Unit testing finished in: " . round((float) \Tracy\Debugger::timer("UNIT") * 1000, 2) . " ms";
    }
}
