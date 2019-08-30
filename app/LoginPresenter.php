<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

class LoginPresenter extends APresenter
{
    public function process()
    {
        $this->checkRateLimit()->setHeaderHtml();
        $cfg = $this->getCfg();
        $time = "?nonce=" . substr(hash("sha256", random_bytes(10) . time()), 0, 8);
        if (isset($_GET["return_uri"])) {
            \setcookie("return_uri", $_GET["return_uri"]);
        }
        $provider = new \League\OAuth2\Client\Provider\Google([
            "clientId" => $cfg["goauth_client_id"],
            "clientSecret" => $cfg["goauth_secret"],
            "redirectUri" => (LOCALHOST === true) ? $cfg["local_goauth_redirect"] : $cfg["goauth_redirect"],
        ]);
        $errors = [];
        if (!empty($_GET["error"])) {
            $errors[] = "INFO: " . htmlspecialchars($_GET["error"], ENT_QUOTES, "UTF-8");
        } elseif (empty($_GET["code"])) {
            $email = $_COOKIE["login_hint"] ?? null;
            $email = $_GET["login_hint"] ?? null;
            $hint = $email ? strtolower("&login_hint=$email") : "";
            if (isset($_GET["relogin"]) && $_GET["relogin"] == true) {
                $authUrl = $provider->getAuthorizationUrl([
                    "prompt" => "select_account consent",
                    "response_type" => "code",
                ]);
            } else {
                $authUrl = $provider->getAuthorizationUrl([
                    "response_type" => "code",
                ]);
            }
            ob_end_flush();
            \setcookie("oauth2state", $provider->getState());
            header("Location: " . $authUrl . $hint, true, 303);
            exit;
/*
            echo $authUrl . $hint;
            exit;
*/

        } elseif (empty($_GET["state"]) || ($_GET["state"] && !isset($_COOKIE["oauth2state"]))) {
            $errors[] = "Invalid OAuth state";
        } else {
            try {
                $token = $provider->getAccessToken("authorization_code", [
                    "code" => $_GET["code"],
                ]);
                $ownerDetails = $provider->getResourceOwner($token, [
                    "useOidcMode" => true,
                ]);
                $this->setIdentity([
                    "avatar" => $ownerDetails->getAvatar(),
                    "email" => $ownerDetails->getEmail(),
                    "id" => $ownerDetails->getId(),
                    "name" => $ownerDetails->getName(),
                ]);
                $a = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"];
                if ($this->getUserGroup() == "admin") {
                    if ($this->getCfg("DEBUG_COOKIE")) {
                        \setcookie("tracy-debug", $this->getCfg("DEBUG_COOKIE"));
                    }
                }
                \setcookie("oauth2state", "", 0);
                unset($_COOKIE["oauth2state"]);
                if (strlen($ownerDetails->getEmail())) {
                    \setcookie("login_hint", $ownerDetails->getEmail() ?? "", time() + 86400 * 10, "/", DOMAIN);
                }
/*
                echo "IP ADDRESS: $a <br>";
                echo "USER GROUP: " . $this->getUserGroup() ."<br>";
                dump($ownerDetails);
                dump(get_defined_constants(true)['user']);
                exit;
*/
                if (isset($_COOKIE["return_uri"])) {
                    $c = $_COOKIE["return_uri"];
                    \setcookie("return_uri", "", 0);
                    unset($_COOKIE["return_uri"]);
                    $this->setLocation($c);
                } else {
                    $this->setLocation("/$time");
                }
                exit;
            } catch (Exception $e) {
                $errors[] = "INFO: " . $e->getMessage();
            }
        }
        ob_end_flush();
        $this->addError("HTTP/1.1 400 Bad Request");
        header("HTTP/1.1 400 Bad Request");
        echo "<html><body><center><h1>😐 AUTHENTICATION ERROR 😐</h1>";
        echo join("<br>", $errors);
        echo "<h3><a href=\"/login?nonce=" . substr(hash("sha256", random_bytes(10) . (string) time()), 0, 8)
            . "\">RETRY ↻</a></h3></center></html></body>";
        exit;
    }
}
