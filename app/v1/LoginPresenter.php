<?php

class LoginPresenter extends GSC\APresenter
{
    public function process()
    {
        $this->checkRateLimit()->setHeaderHtml();
        $cfg = $this->getCfg();
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();
        $time = "?nonce=" . substr(hash("sha256", random_bytes(10) . time()), 0, 8);
        if (isset($_GET["return_uri"])) {
            \setcookie("return_uri", $_GET["return_uri"]);
        }
        $provider = new \League\OAuth2\Client\Provider\Google([
            "clientId" => $cfg["goauth_client_id"],
            "clientSecret" => $cfg["goauth_secret"],
            "redirectUri" => (SERVER == "localhost") ? $cfg["local_goauth_redirect"] : $cfg["goauth_redirect"],
        ]);
        $errors = [];
        if (!empty($_GET["error"])) {
            $errors[] = "INFO: " . htmlspecialchars($_GET["error"], ENT_QUOTES, "UTF-8");
        } elseif (empty($_GET["code"])) {
            $c = $_COOKIE["login_hint"] ?? null;
            $login_hint = $c ? "&login_hint=$c" : "";
            $relogin = isset($_GET["relogin"]) ? true : false;
            if ($relogin) {
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
            header("Location: " . $authUrl . $login_hint, true, 303);
//            echo $authUrl . $login_hint;
            exit;
        } elseif (empty($_GET["state"]) || ($_GET["state"] && !isset($_COOKIE["oauth2state"]))) {
            $errors[] = "INFO: Invalid OAuth state";
        } else {
            try {
                $token = $provider->getAccessToken("authorization_code", [
                    "code" => $_GET["code"],
                ]);
                $ownerDetails = $provider->getResourceOwner($token, [
                    "useOidcMode" => true,
                ]);
                // TODO: NEEDS WORK!!!
                $this->setCookie("id", $ownerDetails->getId());
                $this->setCookie("name", $ownerDetails->getName());
                $this->setCookie("admin", $ownerDetails->getEmail());
                $this->setCookie("avatar", $ownerDetails->getAvatar());
                unset($_COOKIE["oauth2state"]);
                \setcookie("oauth2state", "", time() - 3600, "/");
                \setcookie("login_hint", $ownerDetails->getEmail(), time() + 86400 * 10, "/", DOMAIN, true);
/*
                dump($ownerDetails);
                dump($_COOKIE);
                dump(get_defined_constants(true)['user']);
*/
                if (isset($_COOKIE["return_uri"])) {
                    $c = $_COOKIE["return_uri"];
                    unset($_COOKIE["return_uri"]);
                    \setcookie("return_uri", "", time() - 3600, "/");
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
        echo "<html><body><h1>HTTP Error 400</h1><center><h2>Bad Request</h2>";
        echo join("<br>", $errors);
        echo "<h3><a href=\"/login\">LOGIN Retry ↻</a></h3></center></html></body>";
        exit;
    }
}
