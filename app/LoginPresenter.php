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

        // CHECK ENGINE
        if (isset($_COOKIE['ENGINE']) && $_COOKIE['ENGINE'] !== ENGINE) {
            $cookie_options = [
                'expires' => time() - 86400,
                'path' => '/',
                'domain' => DOMAIN,
                'secure' => !LOCALHOST,
                'httponly' => true,
                'samesite' => 'Lax',
            ];
            setcookie(APPNAME, '', $cookie_options);
            setcookie('ENGINE', '', $cookie_options);
            header('Location: /?');
            exit;
        }

        if (!\is_array($data = $this->getData())) {
            $err = 'Model: invalid data';
            $this->addCritical($err);
            ErrorPresenter::getInstance()->process(['code' => 500, 'message' => $err]); // phpcs:ignore
        }
        $this->checkRateLimit()->dataExpander($data);

        if (!\is_array($cfg = $this->getCfg())) {
            $err = 'Config: invalid data';
            $this->addCritical($err);
            ErrorPresenter::getInstance()->process(['code' => 500, 'message' => $err]); // phpcs:ignore
        }
        if (!($cfg['goauth_client_id'] ?? null) || !($cfg['goauth_secret'] ?? null)) { // phpcs:ignore
            $this->addError('OAuth: missing [goauth_client_id] or [goauth_secret]');
            $this->setLocation('/err/412');
        }

        // check if we are on the correct origin
        $currentOrigin = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}";
        $expectedOrigin = LOCALHOST ? $cfg['local_goauth_origin'] : $cfg['goauth_origin']; // phpcs:ignore
        if ($currentOrigin !== $expectedOrigin) {
            $redirectUrl = LOCALHOST ? $cfg['local_goauth_redirect'] : $cfg['goauth_redirect']; // phpcs:ignore
            $ret = null;
            if (!empty($_GET['returnURL'])) {
                $ret = $_GET['returnURL'];
            }
            $this->setLocation($ret ? "{$redirectUrl}?returnURL={$ret}" : $redirectUrl); // phpcs:ignore
        }

        // check if user is logged in, if so redirect to the last/main page
        if ($this->getCurrentUser()['id']) {
            $url = '/';
            $returnUrl = $_GET['returnURL'] ?? '/';
            if (\str_starts_with($returnUrl, '/')) {
                $url = $returnUrl;
            }
            $this->setLocation($url);
        }

        // save return URL
        if (!empty($_GET['returnURL'])) {
            \setcookie(
                'returnURL',
                $_GET['returnURL'],
                [
                    'expires' => \time() + 60,
                    'path' => '/',
                    'domain' => '',
                    'secure' => !LOCALHOST,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }        

        try {
            $provider = new \League\OAuth2\Client\Provider\Google(
                [
                    'clientId' => $cfg['goauth_client_id'],
                    'clientSecret' => $cfg['goauth_secret'],
                    'redirectUri' => LOCALHOST ? $cfg['local_goauth_redirect'] : $cfg['goauth_redirect'], // phpcs:ignore
                ]
            );
        } catch (\Throwable $e) {
            $err = "OAuth: failure. Exception: " . $e->getMessage();
            $this->addError($err);
            ErrorPresenter::getInstance()->process(['code' => 403, 'message' => $err]); // phpcs:ignore
        }
        if (!empty($_GET['error'])) {
            $err = \htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
            $err = "OAuth: failure.\nMessage: $err";
            $this->addError($err);
            ErrorPresenter::getInstance()->process(['code' => 403, 'message' => $err]); // phpcs:ignore
        } elseif (empty($_GET['code'])) {
            $authUrl = $provider->getAuthorizationUrl(
                [
                    'prompt' => 'select_account',
                    'response_type' => 'code',
                ]
            );
            \setcookie(
                'oauth2state',
                $provider->getState(),
                [
                    'expires' => \time() + 60,
                    'path' => '/',
                    'domain' => '',
                    'secure' => !LOCALHOST,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
            \header("Location: {$authUrl}", true, 303);
            exit;
        } elseif (empty($_GET['state']) || (!isset($_COOKIE['oauth2state'])) || $_GET['state'] !== $_COOKIE['oauth2state']) { // phpcs:ignore
            $err = 'OAuth: invalid OAuth state';
            $this->addError($err);
            ErrorPresenter::getInstance()->process(['code' => 403, 'message' => $err]); // phpcs:ignore
        } else {
            try {
                $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]); // phpcs:ignore
                $ownerDetails = $provider->getResourceOwner($token, ['useOidcMode' => true]); // phpcs:ignore
                $i = [
                    'id' => $ownerDetails->getId(),
                    'name' => $ownerDetails->getName(),
                    'email' => $ownerDetails->getEmail(),
                    'avatar' => $ownerDetails->getAvatar(),
                    'provider' => 'google',
                ];
                $this->clearCookie('oauth2state');
                $this->setIdentity($i)->addMessage(["OAuthIdentity" => $i]);
                $cookie_options = [
                    'expires' => \time() + 2592000,
                    'path' => '/',
                    'domain' => DOMAIN,
                    'secure' => !LOCALHOST,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ];
                setcookie('ENGINE', ENGINE, $cookie_options);
                if (!empty($group = $this->getUserGroup())) {
                    $this->addMessage("OAuth login. User group: [{$group}]");
                }
                $returnUrl = \urldecode($_COOKIE['returnURL'] ?? '/');
                $url = '/';
                if (\strpos($returnUrl, '/') === 0) {
                    $url = $returnUrl;
                }
                $this->setLocation("{$url}?nonce=" . $this->getNonce());
            } catch (\Throwable $e) {
                $err = "OAuth: failure. Exception: " . $e->getMessage();
                $this->addError($err);
                ErrorPresenter::getInstance()->process(['code' => 403, 'message' => $err]); // phpcs:ignore
            }
        }
        /* should not happend */
        $err = "OAuth: general error";
        $this->addError($err);
        ErrorPresenter::getInstance()->process(['code' => 403, 'message' => $err]);
    }
}
