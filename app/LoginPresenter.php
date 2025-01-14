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
        if (($cfg["goauth_client_id"] ?? null) === null) {
            ErrorPresenter::getInstance()->process(403);
            exit;
        }

        if (($cfg["goauth_secret"] ?? null) === null) {
            ErrorPresenter::getInstance()->process(403);
            exit;
        }

        try {
            $provider = new \League\OAuth2\Client\Provider\Google(
                [
                "clientId" => $cfg["goauth_client_id"],
                "clientSecret" => $cfg["goauth_secret"],
                "redirectUri" => (LOCALHOST === true)
                    ? $cfg["local_goauth_redirect"] : $cfg["goauth_redirect"],
                ]
            );
        } finally {
        }

        $errors = [];
        if (!empty($_GET["error"])) {
            $errors[] = \htmlspecialchars($_GET["error"], ENT_QUOTES, "UTF-8");
        } elseif (empty($_GET["code"])) {
            $email = $_GET["login_hint"] ?? $_COOKIE["login_hint"] ?? null;
            $hint = $email ? \strtolower("&login_hint={$email}") : '';
            $authUrl = $provider->getAuthorizationUrl(
                [
                    "prompt" => "select_account",
                    "response_type" => "code",
                ]
            );
            \setcookie("oauth2state", $provider->getState());
            \header("Location: " . $authUrl . $hint, true, 303);
            exit;
        } elseif (empty($_GET["state"])
            || (!isset($_COOKIE["oauth2state"]))
        ) {
            $errors[] = "Invalid OAuth state";
        } else {
            try {
                $token = $provider->getAccessToken(
                    "authorization_code",
                    ["code" => $_GET["code"]]
                );
                $ownerDetails = $provider->getResourceOwner(
                    $token, 
                    ["useOidcMode" => true,]
                );
                $this->setIdentity(
                    [
                        "avatar" => $ownerDetails->getAvatar(),
                        "email" => $ownerDetails->getEmail(),
                        "id" => $ownerDetails->getId(),
                        "name" => $ownerDetails->getName(),
                    ]
                );
                $this->addMessage(
                    "OAuth: "
                    . $ownerDetails->getName()
                    . " "
                    . $ownerDetails->getEmail()
                );
                $this->addAuditMessage(
                    "OAuth login:<br>"
                    . $ownerDetails->getName()
                    . " "
                    . $ownerDetails->getEmail()
                );
                if ($this->getUserGroup() == "admin") {
                    if (\is_string($this->getCfg("DEBUG_COOKIE"))) {
                        \setcookie("tracy-debug", $this->getCfg("DEBUG_COOKIE"));
                    }
                }
                $this->clearCookie("oauth2state");
                if (\strlen($ownerDetails->getEmail())) {
                    \setcookie(
                        "login_hint",
                        $ownerDetails->getEmail() ?? "",
                        \time() + 86400 * 31,
                        "/",
                        DOMAIN,
                    );
                }
                $this->setLocation();
            } catch (\Exception $e) {
                $this->addError("Google OAuth: " . $e->getMessage());
                $this->addAuditMessage("Google OAuth error: " . $e->getMessage());
            }
        }
        ErrorPresenter::getInstance()->process(403);
        exit;
    }
}
