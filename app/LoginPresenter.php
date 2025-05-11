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
 * Login Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class LoginPresenter extends APresenter
{
    /**
     * Controller processor
     *
     * @param mixed $param optional parameter
     * 
     * @return object Controller
     */
    public function process($param = null)
    {
        if (\ob_get_level()) {
            @\ob_end_clean();
        }
        $this->checkRateLimit()->setHeaderHtml();

        $cfg = $this->getCfg();
        if (($cfg['goauth_client_id'] ?? null) === null) {
            $this->addError('OAuth: missing [goauth_client_id]');
            $this->setLocation('/err/412');
        }
        if (($cfg['goauth_secret'] ?? null) === null) {
            $this->addError('OAuth: missing [goauth_secret]');
            $this->setLocation('/err/412');
        }

        // check if we are on the right origin
        $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore
        $scheme = parse_url($currentUrl, PHP_URL_SCHEME);
        $host = parse_url($currentUrl, PHP_URL_HOST);
        $port = parse_url($currentUrl, PHP_URL_PORT);
        $currentOrigin = $scheme . "://" . $host;

        if ($port) {
            $currentOrigin .= ":" . $port;
        }

        if (LOCALHOST) {
            if ($currentOrigin !== $cfg['local_goauth_origin']) {
                header("Location: " . $cfg['local_goauth_redirect']);
                exit;
            }
        } else {
            if ($currentOrigin !== $cfg['goauth_origin']) {
                header("Location: " . $cfg['goauth_redirect']);
                exit;
            }   
        }

        // check if user is logged in === redirect to the main/last page
        if (\strlen($this->getCurrentUser()['email'])) {
            if (isset($_GET['returnURL'])) {
                $url = $_GET['returnURL'];
                if (\strpos($url, '/') === 0) {
                    $this->setLocation($url . '?nonce=' . $this->getNonce());
                }
            }
            $this->setLocation();
        }

        // save return URL
        if (!empty($_GET['returnURL'])) {
            \setcookie(
                'returnURL',
                $_GET['returnURL'],
                \time() + 30,
                '/',
                DOMAIN,
                !LOCALHOST,
                true
            );
        }
        
        try {
            $provider = new \League\OAuth2\Client\Provider\Google(
                [
                'clientId' => $cfg['goauth_client_id'],
                'clientSecret' => $cfg['goauth_secret'],
                'redirectUri' => (LOCALHOST === true)
                    ? $cfg['local_goauth_redirect'] : $cfg['goauth_redirect'],
                ]
            );
        } finally {
        }

        if (!empty($_GET['error'])) {
            $err = \htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
            $this->addError("OAuth: failure.\nMessage: " . $err);
            ErrorPresenter::getInstance()->process(
                [
                    'code' => 403,
                    'message' => "OAuth failure. Message: " . $err,
                ]
            );
        } elseif (empty($_GET['code'])) {
            $email = $_GET['login_hint'] ?? $_COOKIE['login_hint'] ?? null;
            $hint = $email ? \strtolower("&login_hint={$email}") : '';
            $authUrl = $provider->getAuthorizationUrl(
                [
                    'prompt' => 'select_account',
                    'response_type' => 'code',
                ]
            );

            \setcookie(
                'oauth2state',
                $provider->getState(),
                \time() + 30,
                '/',
                DOMAIN,
                !LOCALHOST,
                true
            );

            \header('Location: ' . $authUrl . $hint, true, 303);
            exit;
        } elseif (empty($_GET['state']) || (!isset($_COOKIE['oauth2state'])) || $_GET['state'] !== $_COOKIE['oauth2state']) { // phpcs:ignore
            $this->addError('OAuth: invalid OAuth state');
            ErrorPresenter::getInstance()->process(
                [
                    'code' => 403,
                    'message' => 'OAuth failure: invalid state.',
                ]
            );
        } else {
            try {
                $token = $provider->getAccessToken(
                    'authorization_code',
                    ['code' => $_GET['code']]
                );
                $ownerDetails = $provider->getResourceOwner(
                    $token, 
                    ['useOidcMode' => true,]
                );
                $this->setIdentity(
                    [
                        'id' => $ownerDetails->getId(),
                        'name' => $ownerDetails->getName(),
                        'email' => $ownerDetails->getEmail(),
                        'avatar' => $ownerDetails->getAvatar(),
                        'provider' => 'google',
                    ]
                );

                $group = $this->getUserGroup();
                if (!empty($group)) {
                    $this->addMessage("OAuth login. User group: [{$group}]");
                }

                if ($group === 'admin') {
                    if (\is_string($this->getCfg('DEBUG_COOKIE'))) {
                        \setcookie('tracy-debug', $this->getCfg('DEBUG_COOKIE'));
                    }
                }

                $this->clearCookie('oauth2state');

                if (strlen($ownerDetails->getEmail())) {
                    \setcookie(
                        'login_hint',
                        $ownerDetails->getEmail() ?? '',
                        \time() + 86400 * 31,
                        '/',
                        DOMAIN,
                        !LOCALHOST,
                        true
                    );
                }

                if (isset($_COOKIE['returnURL'])) {
                    $url = \urldecode($_COOKIE['returnURL']);
                    if (\strpos($url, '/') === 0) {
                        $this->setLocation($url . '?nonce=' . $this->getNonce());
                    }
                }
                $this->setLocation();

            } catch (\Exception $e) {
                $err = $e->getMessage();
                $this->addError("OAuth: failure.\nException: " . $err);
                ErrorPresenter::getInstance()->process(
                    [
                        'code' => 403,
                        'message' => $err,
                    ]
                );
            }
        }
        $this->addError("OAuth: general error");
        ErrorPresenter::getInstance()->process(
            [
                'code' => 403,
                'message' => 'OAuth general error.',
            ]
        );
    }
}
