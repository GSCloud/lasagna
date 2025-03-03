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
            $this->addError('OAuth: Missing [goauth_client_id].');
            $this->setLocation('/err/412');
        }
        if (($cfg['goauth_secret'] ?? null) === null) {
            $this->addError('OAuth: Missing [goauth_secret].');
            $this->setLocation('/err/412');
        }

        // save return URL
        if (!empty($_GET['returnURL'])) {
            \setcookie(
                'returnURL',
                $_GET['returnURL'],
                \time() + 30,
                '/',
                DOMAIN,
                true,
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

        $errors = [];
        if (!empty($_GET['error'])) {
            $error = \htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
            $this->addError("OAuth failure. Message:\n" . $error);
        } elseif (empty($_GET['code'])) {
            $email = $_GET['login_hint'] ?? $_COOKIE['login_hint'] ?? null;
            $hint = $email ? \strtolower("&login_hint={$email}") : '';
            $authUrl = $provider->getAuthorizationUrl(
                [
                    'prompt' => 'select_account',
                    'response_type' => 'code',
                ]
            );
            \setcookie('oauth2state', $provider->getState());
            \header('Location: ' . $authUrl . $hint, true, 303);
            exit;
        } elseif (empty($_GET['state']) || (!isset($_COOKIE['oauth2state']))
        ) {
            $this->addError('OAuth: Invalid OAuth state.');
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
                        true,
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
                $this->addError("OAuth exception. Message:\n" . $err);
                ErrorPresenter::getInstance()->process(
                    [
                        'code' => 403,
                        'message' => $err,
                    ]
                );
            }
        }
        $this->addError("OAuth general error.");
        ErrorPresenter::getInstance()->process(403);
    }
}
