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

use League\CLImate\CLImate;

class CiTester
{
    /**
     * Main controller
     *
     * @param array $cfg Configuration.
     * @param array $presenter Presenter.
     * @param string $type Test type: testlocal, testprod.
     *
     * @return void
     */
    public function __construct($cfg, $presenter, $type)
    {
        $climate = new CLImate;
        $cfg = (array) $cfg;
        $presenter = (array) $presenter;
        $type = (string) $type;

        switch ($type) {
            case "local":
            case "testlocal":
                $case = "local";
                $target = $cfg["local_goauth_origin"] ?? "";
                break;

            case "prod":
            case "testprod":
            default:
                $case = "production";
                $target = $cfg["goauth_origin"] ?? "";
        }

        if (!strlen($target)) {
            $climate->out("<bold><green>${cfg['app']} ${case}");
            $climate->out("<red>FATAL ERROR: missing target URI!\n\007");
            exit;
        }

        $climate->out("testing: <bold><green>${cfg['app']} ${case}");

        $i = 0;
        $pages = [];
        $redirects = [];

        foreach ($presenter as $p) {
            if (strpos($p["path"], "[") !== false) {
                continue;
            }
            if (strpos($p["path"], "*") !== false) {
                continue;
            }
            if ($p["redirect"] ?? false) {
                $redirects[$i]["path"] = $p["path"];
                $redirects[$i]["site"] = $target;
                $redirects[$i]["assert_httpcode"] = 303;
                if (stripos($p["redirect"], "http") === false) {
                    $redirects[$i]["url"] = $target . $p["path"];
                } else {
                    $redirects[$i]["url"] = $p["redirect"];
                }
            } else {
                $pages[$i]["path"] = $p["path"];
                $pages[$i]["site"] = $target;
                $pages[$i]["assert_httpcode"] = $p["assert_httpcode"];
                $pages[$i]["url"] = $target . $p["path"];
            }
            $i++;
        }
        ksort($pages);
        ksort($redirects);
        foreach (array_merge($redirects, $pages) as $x) {
            $ch = curl_init($x["url"]);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $output = curl_exec($ch);
            @file_put_contents(ROOT . "/ci/" . date("Y-m-d") . strtr("_${target}_${x['path']}", '\/:.', '____') . ".curl.txt", $output);
            $length = strlen($output);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $u = "<bold>${x['site']}${x['path']}</bold>";
            if ($code == $x["assert_httpcode"]) {
                $climate->out("${u};length:<green>${length}</green>;code:<green>${code}</green>");
            } else {
                $climate->out("<red>${u};length:<bold>${length}</bold>;code:<bold>${code}</bold>;assert:<bold>${x['assert_httpcode']}</bold></red>\007");
                @file_put_contents(ROOT . "/ci/errors_" . date("Y-m") . strtr("_${target}", '\/:.', '____') . ".assert.txt",
                    "${u};length:${length};code:${code};assert:${x['assert_httpcode']}" . "\n", FILE_APPEND | LOCK_EX);
            }
        }
        exit;
    }
}
