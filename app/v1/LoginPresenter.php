<?php

class LoginPresenter extends GSC\APresenter
{
    public function process()
    {
        session_start();
        $this->checkRateLimit()->setHeaderHtml();
        $cfg = $this->getCfg();
        $data = $this->getData();
        $presenter = $this->getPresenter();
        $view = $this->getView();

        $time = "?nonce=" . substr(hash("sha256", random_bytes(10) . time()), 0, 8);

        if (isset($_GET["return_uri"])) {
            $_SESSION["return_uri"] = $_GET["return_uri"];
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
            $_SESSION["oauth2state"] = $provider->getState();
            //echo $authUrl . $login_hint;
            header("Location: " . $authUrl . $login_hint, true, 303);
            exit;

        } elseif (empty($_GET["state"]) || ($_GET["state"] && !isset($_SESSION["oauth2state"]))) {
            unset($_SESSION["oauth2state"]);
            $errors[] = "INFO: Invalid OAuth state";
        } else {
            try {
                $token = $provider->getAccessToken("authorization_code", [
                    "code" => $_GET["code"],
                ]);
                $ownerDetails = $provider->getResourceOwner($token, [
                    "useOidcMode" => true,
                ]);

                $_SESSION["id"] = $id = $ownerDetails->getId();
                $_SESSION["name"] = $name = $ownerDetails->getName();
                $_SESSION["admin"] = $admin = $ownerDetails->getEmail();
                $_SESSION["avatar"] = $avatar = $ownerDetails->getAvatar();
                $this->setCookie("id", $id);
                $this->setCookie("name", $name);
                $this->setCookie("admin", $admin);
                $this->setCookie("avatar", $avatar);

                setcookie("login_hint", $_SESSION["admin"], time() + 86400 * 30, "/", DOMAIN, true);

                if (isset($_SESSION["return_uri"])) {
                    $uri = $_SESSION["return_uri"];
                    unset($_SESSION["return_uri"]);
                    $this->setLocation($uri . $time);
                    exit;
                }

                dump($ownerDetails);
                dump($_SESSION);
                dump($_COOKIE);
                dump(get_defined_constants(true)['user']);

                // comment next line to see the debug info
                $this->setLocation("/cs/$time");
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
