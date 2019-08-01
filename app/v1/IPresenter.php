<?php

namespace GSC;

interface IPresenter
{
    public function CFPurgeCache();
    public function addError($error);
    public function addMessage($message);
    public function checkLocales($force);
    public function checkPermission($role);
    public function checkRateLimit($user, $maximum);
    public function clearCookie($name);
    public function getAdminGroup($email);
    public function getCfg();
    public function getCookie($name);
    public function getData();
    public function getErrors();
    public function getLocale($locale, $key);
    public function getMatch();
    public function getMessages();
    public function getPresenter();
    public function getRouter();
    public function getUID();
    public function getView();
    public function logout();
    public function process();
    public function renderHTML($template);
    public function setCookie($name, $data);
    public function setData($data);
    public function setHeaderCsv();
    public function setHeaderFile();
    public function setHeaderHtml();
    public function setHeaderJavaScript();
    public function setHeaderJson();
    public function setHeaderPdf();
    public function setHeaderText();
    public function setLocation($location);
}
