<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 */

namespace GSC;

/**
 * Login Presenter
 */
class LoginPresenter extends APresenter
{
    /**
     * Main controller
     *
     * @return void
     */
    public function process()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $this->checkRateLimit()->setHeaderHtml();

        $cfg = $this->getCfg();
        $nonce = "?nonce=" . substr(hash("sha256", random_bytes(8) . (string) time()), 0, 8);

        // set return URI
        $refhost = parse_url($_SERVER["HTTP_REFERER"] ?? "", PHP_URL_HOST);
        $uri = "/${nonce}";
        if ($refhost ?? null) {
            if (in_array($refhost, $this->getData("multisite_profiles.default"))) {
                $uri = $_SERVER["HTTP_REFERER"];
            }
        }
        \setcookie("return_uri", $uri, 0, "/", DOMAIN);
        try {
            $provider = new \League\OAuth2\Client\Provider\Google([
                // set OAuth 2.0 credentials
                "clientId" => $cfg["goauth_client_id"] ?? null,
                "clientSecret" => $cfg["goauth_secret"] ?? null,
                "redirectUri" => (LOCALHOST === true) ? $cfg["local_goauth_redirect"] ?? null : $cfg["goauth_redirect"] ?? null,
            ]);
        } finally {}

        // check for errors
        $errors = [];
        if (!empty($_GET["error"])) {
            $errors[] = \htmlspecialchars($_GET["error"], ENT_QUOTES, "UTF-8");
        } elseif (empty($_GET["code"])) {
            $email = $_GET["login_hint"] ?? $_COOKIE["login_hint"] ?? null;
            $hint = $email ? \strtolower("&login_hint=${email}") : "";

            // check URL for relogin parameter
            if (isset($_GET["relogin"])) {
                $hint = "";
                $authUrl = $provider->getAuthorizationUrl([
                    "prompt" => "select_account consent",
                    "response_type" => "code",
                ]);
            } else {
                $authUrl = $provider->getAuthorizationUrl([
                    "response_type" => "code",
                ]);
            }
            \setcookie("oauth2state", $provider->getState());
            \header("Location: " . $authUrl . $hint, true, 303);
            exit;
        } elseif (empty($_GET["state"]) || ($_GET["state"] && !isset($_COOKIE["oauth2state"]))) {
            $errors[] = "Invalid OAuth state";
        } else {
            try {
                // get access token
                $token = $provider->getAccessToken("authorization_code", [
                    "code" => $_GET["code"],
                ]);
                // get owner details
                $ownerDetails = $provider->getResourceOwner($token, [
                    "useOidcMode" => true,
                ]);
                $this->setIdentity([
                    "avatar" => $ownerDetails->getAvatar(),
                    "email" => $ownerDetails->getEmail(),
                    "id" => $ownerDetails->getId(),
                    "name" => $ownerDetails->getName(),
                ]);
                $this->addMessage("Google login: " . $ownerDetails->getName() . " " . $ownerDetails->getEmail());
                $this->addAuditMessage("GOOGLE OAUTH LOGIN");
/*
dump("NEW IDENTITY:");
dump($this->getIdentity());
dump("OAuth IDENTITY:");
dump($ownerDetails);
exit;
 */
                if ($this->getUserGroup() == "admin") {
                    if ($this->getCfg("DEBUG_COOKIE")) { // set Tracy debug cookie
                        \setcookie("tracy-debug", $this->getCfg("DEBUG_COOKIE"));
                    }
                }
                $this->clearCookie("oauth2state");
                if (\strlen($ownerDetails->getEmail())) { // store email for the next run
                    \setcookie("login_hint", $ownerDetails->getEmail() ?? "", time() + 86400 * 31, "/", DOMAIN);
                }
                $this->clearCookie("oauth2state");
                $this->setLocation();
                exit;
            } catch (Exception $e) {
                $this->addError("Google OAuth: " . $e->getMessage());
            }
        }
        // error
        $this->setLocation("/err/403");
        exit;
    }
}
