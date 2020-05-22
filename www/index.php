<?php

define("ROOT", __DIR__ . "/..");

#define("DEBUG", true);

/** @const DIRECTORY_SEPARATOR */
defined("DS") || define("DS", DIRECTORY_SEPARATOR);
#defined("ROOT") || define("ROOT", __DIR__);
#defined("APP") || define("APP", ROOT . DS . "app");
#defined("CACHE") || define("CACHE", ROOT . DS . "temp");
#defined("DATA") || define("DATA", ROOT . DS . "data");
#defined("WWW") || define("WWW", ROOT . DS . "www");
#defined("CONFIG") || define("CONFIG", APP . DS . "config.neon");
#defined("CONFIG_PRIVATE") || define("CONFIG_PRIVATE", APP . DS . "config_private.neon");
#defined("TEMPLATES") || define("TEMPLATES", WWW . DS . "templates");
#defined("PARTIALS") || define("PARTIALS", WWW . DS . "partials");
#defined("DOWNLOAD") || define("DOWNLOAD", WWW . DS . "download");
#defined("UPLOAD") || define("UPLOAD", WWW . DS . "upload");
#defined("LOGS") || define("LOGS", ROOT . DS . "logs");
#defined("TEMP") || define("TEMP", ROOT . DS . "temp");

require_once ROOT . "/Bootstrap.php";
