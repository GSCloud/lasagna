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

declare (strict_types = 1);
namespace GSC;

use Michelf\Markdown;
use Michelf\MarkdownExtra;
use Tracy\Debugger;

/**
 * String Filters class
 * 
 * Modify string contents passed by a reference to fix common problems.
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class StringFilters
{
    // single shortcode maximum iterations
    const ITERATIONS = 100;

    // file cache time-to-live
    const CACHE_TTL = 3600;

    // file cache ON/OFF
    const USE_CACHE = true;

    // SANITIZATION: IMAGE MASK for search
    const UPLOAD_SANITIZE = '/[^a-z0-9!@#+*=,;\-._]+/i';

    // SANITIZATION: allowed CHARACTERS
    const STRING_SANITIZE = '/[^a-z0-9\-._]+/i';

    // FLAGS: randomize galleries
    const GALLERY_RANDOM = 1;

    // FLAGS: use lazy loading
    const LAZY_LOADING = 2;

    // FLAGS: 80px thumbnails
    const THUMBS_80 = 4;

    // FLAGS: 160px thumbnails
    const THUMBS_160 = 8;

    // FLAGS: 320px thumbnails
    const THUMBS_320 = 16;

    // FLAGS: 640px thumbnails
    const THUMBS_640 = 32;

    // FLAGS: 1280px thumbnails
    const THUMBS_1280 = 64;

    // shortcodes for tokenization
    const ALL_SHORTCODES = [
        'css',
        'figure',
        'gallery',
        'galleryspan',
        'googlemap',
        'image',
        'imageleft',
        'imageresp',
        'imageright',
        'mastodon',
        'qrcode',
        'soundcloud',
        'twitch',
        'twitchvid',
        'vimeo',
        'youtube',
    ];

    // shortcodes cache during tokenization
    // phpcs:ignore
    /**
     * @var array<string>
     */
    private static array $_shortCodeCache = [];

    // English lowercase characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_english = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    // English UPPERCASE characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $up_chars_english = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    ];

    // Czech lowercase characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_czech = [
        'a', 'á', 'b', 'c', 'č', 'd', 'ď', 'e', 'é', 'ě', 'f', 'g', 'h',
        'i', 'í', 'j', 'k', 'l', 'm', 'n', 'ň', 'o', 'ó', 'p', 'q', 'r',
        'ř', 's', 'š', 't', 'ť', 'u', 'ú', 'ů', 'v', 'w', 'x', 'y', 'ý',
        'z', 'ž'
    ];

    // Czech UPPERCASE characters
    // // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $up_chars_czech = [
        'A', 'Á', 'B', 'C', 'Č', 'D', 'Ď', 'E', 'É', 'Ě', 'F', 'G', 'H',
        'I', 'Í', 'J', 'K', 'L', 'M', 'N', 'Ň', 'O', 'Ó', 'P', 'Q', 'R',
        'Ř', 'S', 'Š', 'T', 'Ť', 'U', 'Ú', 'Ů', 'V', 'W', 'X', 'Y', 'Ý',
        'Z', 'Ž'
    ];

    // Slovak lowercase characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_slovak = [
        'a', 'á', 'ä', 'b', 'c', 'č', 'd', 'ď', 'e', 'é', 'ě', 'f', 'g',
        'h', 'i', 'í', 'j', 'k', 'l', 'ľ', 'm', 'n', 'ň', 'o', 'ó', 'ô',
        'p', 'q', 'r', 'ř', 's', 'š', 't', 'ť', 'u', 'ú', 'ů', 'v', 'w',
        'x', 'y', 'ý', 'z', 'ž',
    ];

    // Slovak UPPERCASE characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $up_chars_slovak = [
        'A', 'Á', 'Ä', 'B', 'C', 'Č', 'D', 'Ď', 'E', 'É', 'Ě', 'F', 'G',
        'H', 'I', 'Í', 'J', 'K', 'L', 'Ľ', 'M', 'N', 'Ň', 'O', 'Ó', 'Ô',
        'P', 'Q', 'R', 'Ř', 'S', 'Š', 'T', 'Ť', 'U', 'Ú', 'Ů', 'V', 'W',
        'X', 'Y', 'Ý', 'Z', 'Ž',
    ];

    // common string replacements
    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_common = [
        "  " => " ",
        " ❤️ " => " ❤️&nbsp;",
        " ♥️ " => " ♥️&nbsp;",
        " 💗 " => " 💗&nbsp;",
        " 💙 " => " 💙&nbsp;",
        " 💚 " => " 💚&nbsp;",
        " 🖤 " => " 🖤&nbsp;",
        " 🤍 " => " 🤍&nbsp;",
        " 🧡 " => " 🧡&nbsp;",

        "0. " => "0.&nbsp;",
        "1. " => "1.&nbsp;",
        "2. " => "2.&nbsp;",
        "3. " => "3.&nbsp;",
        "4. " => "4.&nbsp;",
        "5. " => "5.&nbsp;",
        "6. " => "6.&nbsp;",
        "7. " => "7.&nbsp;",
        "8. " => "8.&nbsp;",
        "9. " => "9.&nbsp;",

        " 0 " => " 0 ",
        " 1 " => " 1 ",
        " 2 " => " 2 ",
        " 3 " => " 3 ",
        " 4 " => " 4 ",
        " 5 " => " 5 ",
        " 6 " => " 6 ",
        " 7 " => " 7 ",
        " 8 " => " 8 ",
        " 9 " => " 9 ",
        
        " :-)" => "&nbsp;🙂",
        " :-))" => "&nbsp;😆",
        " :-D" => "&nbsp;😄",
        " ;-D" => "&nbsp;😂",
        " :-P" => "&nbsp;😋",
        " :-*" => "&nbsp;😘",
        " :-x" => "&nbsp;😘",
        " :-X" => "&nbsp;😍",
        " ;-)" => "&nbsp;😉",
        " 3:-)" => "&nbsp;😎",
        " O:-)" => "&nbsp;😇",
        " :-|" => "&nbsp;😐",
        " :-O" => "&nbsp;😮",
        " :-(" => "&nbsp;😟",
        " :'(" => "&nbsp;😥",
        " :'-(" => "&nbsp;😥",
        " :-/" => "&nbsp;😒",
        " :-[" => "&nbsp;😕",
        " >:-(" => "&nbsp;😡",

        " °C " => " °C ",
        " °De " => " °De ",
        " °F " => " °F ",
        " °N " => " °N ",
        " °Ra " => " °Ra ",
        " °Ré " => " °Ré ",
        " °Rø " => " °Rø ",
        
        " & " => " &amp;&nbsp;",
        " &amp; " => " &amp;&nbsp;",

        " h " => "&nbsp;h ",
        " h, " => "&nbsp;h, ",
        " h. " => "&nbsp;h. ",
        " kg " => "&nbsp;kg ",
        " kg)" => "&nbsp;kg)",
        " l " => "&nbsp;l ",
        " l) " => "&nbsp;l) ",
        " l, " => "&nbsp;l, ",
        " l. " => "&nbsp;l. ",
        " m " => "&nbsp;m ",
        " m) " => "&nbsp;m) ",
        " m, " => "&nbsp;m, ",
        " m. " => "&nbsp;m. ",
        " m2 " => "&nbsp;m² ",
        " m3 " => "&nbsp;m³ ",
        " m² " => "&nbsp;m² ",
        " m³ " => "&nbsp;m³ ",
        " s " => "&nbsp;s&nbsp;",
        " s) " => "&nbsp;s) ",
        " s, " => "&nbsp;s, ",
        " s. " => "&nbsp;s. ",
        " sec. " => "&nbsp;sec. ",
        
        // Currencies with non-breaking spaces
        " EUR " => "&nbsp;€",
        " GBP " => "&nbsp;£",
        " CZK " => " CZK&nbsp;",
        " USD " => " USD&nbsp;",

        " deja vu " => " déjà&nbsp;vu ",
        " facade " => " façade ",
        " naive " => " naïve ",
        " voila " => " voilà ",

        " (c) " => " &copy; ",
        " (r) " => " &reg; ",
        " (tm) " => " &trade; ",

        " id: " => " id:&nbsp;",
        " Id: " => " Id:&nbsp;",
        " ID: " => " ID:&nbsp;",
        " Inc. " => "&nbsp;Inc. ",
        " INC. " => "&nbsp;Inc. ",
        " Ltd. " => "&nbsp;Ltd. ",
        " LTD. " => "&nbsp;Ltd. ",

        " % " => "&nbsp;% ",
        " ‰ " => "&nbsp;‰",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " / " => " /&nbsp;",
        " << " => " « ",
        " >> " => " » ",
        " - " => " — ",
        " – " => " —&nbsp;",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_english = [
        // Contractions with proper apostrophe
        " He's " => " He&rsquo;s ",
        " It's " => " It&rsquo;s ",
        " She's " => " She&rsquo;s ",
        " he's " => " he&rsquo;s ",
        " it's " => " it&rsquo;s ",
        " she's " => " she&rsquo;s ",

        " don't " => " don&rsquo;t ",
        " isn't " => " isn&rsquo;t ",
        " aren't " => " aren&rsquo;t ",
        " didn't " => " didn&rsquo;t ",
        " wasn't " => " wasn&rsquo;t ",
        " weren't " => " weren&rsquo;t ",

        " A " => " A&nbsp;",
        " AM " => "&nbsp;AM ",
        " An " => " An&nbsp;",
        " Chap. " => " Chap.&nbsp;",
        " Co. " => " Co.&nbsp;",
        " Dr. " => " Dr.&nbsp;",
        " Fig. " => " Fig.&nbsp;",
        " I " => " I&nbsp;",
        " Inc. " => "&nbsp;Inc. ",
        " Jr. " => " Jr.&nbsp;",
        " Ltd. " => "&nbsp;Ltd. ",
        " Miss " => " Miss&nbsp;",
        " Mr " => " Mr&nbsp;",
        " Mr. " => " Mr.&nbsp;",
        " Mrs " => " Mrs&nbsp;",
        " Mrs. " => " Mrs.&nbsp;",
        " Ms " => " Ms&nbsp;",
        " Ms. " => " Ms.&nbsp;",
        " No. " => " No.&nbsp;",
        " PM " => "&nbsp;PM ",
        " Sr. " => " Sr.&nbsp;",
        " The " => " The&nbsp;",
        " Vol. " => " Vol.&nbsp;",
        " a.m. " => "&nbsp;a.m. ",
        " a " => " a&nbsp;",
        " an " => " an&nbsp;",
        " approx. " => " approx.&nbsp;",
        " c/o " => " c/o&nbsp;",
        " w/o " => " w/o&nbsp;",
        " e.g. " => " e.g.&nbsp;",
        " etc. " => " etc.&nbsp;",
        " i.e. " => " i.e.&nbsp;",
        " p. " => " p.&nbsp;",
        " p.m. " => "&nbsp;p.m. ",
        " pcs " => "&nbsp;pcs ",
        " pcs)" => "&nbsp;pcs)",
        " pp. " => " pp.&nbsp;",
        " the " => " the&nbsp;",
        " vs. " => " vs.&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_deutsch = [
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_czech = [
        " DIČ: " => " DIČ:&nbsp;",
        " IČ: " => " IČ:&nbsp;",
        " Kč" => "&nbsp;Kč",

        " A " => " A&nbsp;",
        " I " => " I&nbsp;",
        " K " => " K&nbsp;",
        " O " => " O&nbsp;",
        " S " => " S&nbsp;",
        " U " => " U&nbsp;",
        " V " => " V&nbsp;",
        " Z " => " Z&nbsp;",

        " a " => " a&nbsp;",
        " a. s. " => "&nbsp;a.&nbsp;s. ",
        " a.s. " => "&nbsp;a.s. ",
        " cca. " => " cca.&nbsp;",
        " hod. " => "&nbsp;hod. ",
        " hod.)" => "&nbsp;hod.)",
        " i " => " i&nbsp;",
        " k " => " k&nbsp;",
        " ks " => "&nbsp;ks ",
        " ks)" => "&nbsp;ks)",
        " ks, " => "&nbsp;ks, ",
        " ks." => "&nbsp;ks.",
        " kupř. " => " kupř.&nbsp;",
        " na " => " na&nbsp;",
        " např. " => " např.&nbsp;",
        " o " => " o&nbsp;",
        " od " => " od&nbsp;",
        " po " => " po&nbsp;",
        " popř. " => " popř.&nbsp;",
        " př. " => " př.&nbsp;",
        " přib. " => " přib.&nbsp;",
        " přibl. " => " přibl.&nbsp;",
        " s.r.o." => "&nbsp;s.r.o.",
        " spol. " => "&nbsp;spol.&nbsp;",
        " str. " => " str.&nbsp;",
        " tj. " => "tj.&nbsp;",
        " tzn. " => " tzn.&nbsp;",
        " tzv. " => " tzv.&nbsp;",
        " tř. " => "tř.&nbsp;",
        " u " => " u&nbsp;",
        " v " => " v&nbsp;",
        " ve " => " ve&nbsp;",
        " viz " => " viz&nbsp;",
        " z " => " z&nbsp;",
        " ze " => " ze&nbsp;",
        " z. s." => "&nbsp;z.&nbsp;s.",
        " z.s." => "&nbsp;z.s.",
        " zvl. " => " zvl.&nbsp;",
        " č. " => " č.&nbsp;",
        " č. j. " => " č.&nbsp;j.&nbsp;",
        " čj. " => " čj.&nbsp;",
        " čp. " => " čp.&nbsp;",
        " čís. " => " čís.&nbsp;",
        " v.o.s." => "&nbsp;v.o.s.",

        " a&nbsp;i " => " a&nbsp;i&nbsp;",
        " a&nbsp;k " => " a&nbsp;k&nbsp;",
        " a&nbsp;o " => " a&nbsp;o&nbsp;",
        " a&nbsp;s " => " a&nbsp;s&nbsp;",
        " a&nbsp;u " => " a&nbsp;u&nbsp;",
        " a&nbsp;v " => " a&nbsp;v&nbsp;",
        " a&nbsp;z " => " a&nbsp;z&nbsp;",
        " i&nbsp;k " => " i&nbsp;k&nbsp;",
        " i&nbsp;o " => " i&nbsp;o&nbsp;",
        " i&nbsp;s " => " i&nbsp;s&nbsp;",
        " i&nbsp;u " => " i&nbsp;u&nbsp;",
        " i&nbsp;v " => " i&nbsp;v&nbsp;",
        " i&nbsp;z " => " i&nbsp;z&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_slovak = [
        " DIČ: " => " DIČ:&nbsp;",
        " IČ: " => " IČ:&nbsp;",
        " Kč" => "&nbsp;Kč",

        " A " => " A&nbsp;",
        " I " => " I&nbsp;",
        " K " => " K&nbsp;",
        " S " => " S&nbsp;",
        " U " => " U&nbsp;",
        " V " => " V&nbsp;",
        " Z " => " Z&nbsp;",

        " a " => " a&nbsp;",
        " a. s. " => "&nbsp;a.&nbsp;s. ",
        " a.s. " => "&nbsp;a.s. ",
        " cca. " => " cca.&nbsp;",
        " hod. " => "&nbsp;hod. ",
        " hod.)" => "&nbsp;hod.)",
        " i " => " i&nbsp;",
        " k " => " k&nbsp;",
        " ks " => "&nbsp;ks ",
        " ks)" => "&nbsp;ks)",
        " ks, " => "&nbsp;ks, ",
        " ks." => "&nbsp;ks.",
        " na " => " na&nbsp;",
        " napr. " => " napr.&nbsp;",
        " o " => " o&nbsp;",
        " od " => " od&nbsp;",
        " po " => " po&nbsp;",
        " s.r.o." => "&nbsp;s.r.o.",
        " spol. " => "&nbsp;spol.&nbsp;",
        " str. " => " str.&nbsp;",
        " tj. " => "tj.&nbsp;",
        " tzn. " => " tzn.&nbsp;",
        " tzv. " => " tzv.&nbsp;",
        " u " => " u&nbsp;",
        " v " => " v&nbsp;",
        " viz " => " viz&nbsp;",
        " vo " => " vo&nbsp;",
        " z " => " z&nbsp;",
        " z. s." => "&nbsp;z.&nbsp;s.",
        " z.s." => "&nbsp;z.s.",
        " zo " => " zo&nbsp;",
        " zvl. " => " zvl.&nbsp;",
        
        " č. " => " č.&nbsp;",
        " č. j. " => " č.&nbsp;j.&nbsp;",
        " čj. " => " čj.&nbsp;",
        " čp. " => " čp.&nbsp;",
        " čís. " => " čís.&nbsp;",

        " a&nbsp;i " => " a&nbsp;i&nbsp;",
        " a&nbsp;k " => " a&nbsp;k&nbsp;",
        " a&nbsp;o " => " a&nbsp;o&nbsp;",
        " a&nbsp;s " => " a&nbsp;s&nbsp;",
        " a&nbsp;u " => " a&nbsp;u&nbsp;",
        " a&nbsp;v " => " a&nbsp;v&nbsp;",
        " a&nbsp;z " => " a&nbsp;z&nbsp;",
        " i&nbsp;k " => " i&nbsp;k&nbsp;",
        " i&nbsp;o " => " i&nbsp;o&nbsp;",
        " i&nbsp;s " => " i&nbsp;s&nbsp;",
        " i&nbsp;u " => " i&nbsp;u&nbsp;",
        " i&nbsp;v " => " i&nbsp;v&nbsp;",
        " i&nbsp;z " => " i&nbsp;z&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_transliteration = [
        ' ' => '_',
        '--' => '-',
        '__' => '_',
        '-.' => '.',
        '_.' => '.',
        '..' => '.',
        
        'á' => 'a',
        'á' => 'a',
        'à' => 'a',
        'ä' => 'a',
        'ą' => 'a',

        'č' => 'c',
        'č' => 'c',
        'ć' => 'c',
        'ç' => 'c',

        'ď' => 'd',

        'ě' => 'e',
        'ě' => 'e',
        'é' => 'e',
        'é' => 'e',
        'è' => 'e',
        'ë' => 'e',
        'ę' => 'e',

        'í' => 'i',
        'í' => 'i',
        'ï' => 'i',

        'ĺ' => 'l',
        'ľ' => 'l',

        'ḿ' => 'm',

        'ň' => 'n',
        'ń' => 'n',
        'ñ' => 'n',

        'ó' => 'o',
        'ö' => 'o',
        'ô' => 'o',
        'ø' => 'o',
        'õ' => 'o',

        'ř' => 'r',
        'ř' => 'r',
        'ŕ' => 'r',

        'š' => 's',
        'š' => 's',
        'ś' => 's',

        'ť' => 't',

        'ů' => 'u',
        'ü' => 'u',
        'ú' => 'u',
        'ú' => 'u',

        'ý' => 'y',
        'ý' => 'y',

        'ź' => 'z',
        'ž' => 'z',
        'ž' => 'z',
        'ż' => 'z',

        'Á' => 'a',
        'Á' => 'a',
        'À' => 'a',
        'Ä' => 'a',
        'Ą' => 'a',

        'Č' => 'c',
        'Č' => 'c',
        'Ć' => 'c',
        'Ç' => 'c',

        'Ď' => 'd',

        'Ě' => 'e',
        'Ě' => 'e',
        'É' => 'e',
        'É' => 'e',
        'È' => 'e',
        'Ë' => 'e',
        'Ę' => 'e',

        'Í' => 'i',
        'Í' => 'i',
        'Ï' => 'i',

        'Ĺ' => 'l',
        'Ľ' => 'l',

        'Ḿ' => 'M',

        'Ň' => 'n',
        'Ń' => 'n',
        'Ñ' => 'n',

        'Ó' => 'o',
        'Ö' => 'o',
        'Ô' => 'o',
        'Ø' => 'o',
        'Õ' => 'o',

        'Ř' => 'r',
        'Ř' => 'r',
        'Ŕ' => 'r',

        'Š' => 's',
        'Š' => 's',
        'Ś' => 's',

        'Ť' => 't',

        'Ů' => 'u',
        'Ü' => 'u',
        'Ú' => 'u',
        'Ú' => 'u',

        'Ý' => 'y',
        'Ý' => 'y',

        'Ž' => 'z',
        'Ž' => 'z',
        'Ź' => 'z',
        'Ż' => 'z',
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    private static $_custom = [];

    /**
     * Set custom string replacements, can set an empty array
     *
     * @param array<string,string> $array associative array of custom replacements
     *
     * @return void
     * 
     * @throws \InvalidArgumentException invalid input array passed
     */
    public static function setCustomReplacements($array)
    {
        if (!\is_array($array)) {
            throw new \InvalidArgumentException(
                'Invalid argument: expected an array.'
            );
        }
        if (empty($array)) {
            self::$_custom = [];
            return;
        }
        foreach ($array as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                throw new \InvalidArgumentException(
                    'Keys and values must be strings.'
                );
            }
        }
        self::$_custom = $array;
    }

    /**
     * Add custom string replacements
     *
     * @param array<string,string> $array associative array of custom replacements
     *
     * @return void
     * 
     * @throws \InvalidArgumentException invalid input array passed
     */
    public static function addCustomReplacements($array)
    {
        if (!\is_array($array)) {
            throw new \InvalidArgumentException(
                'Invalid argument: expected an array.'
            );
        }
        if (empty($array)) {
            return;
        }
        foreach ($array as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                throw new \InvalidArgumentException(
                    'Keys and values must be strings.'
                );
            }
        }
        self::$_custom = \array_merge(
            self::$_custom,
            $array
        );
    }

    /**
     * Convert EOLs to HTML breakline
     *
     * @param string $content string content by reference
     * 
     * @return void
     */
    public static function convertEolToBr(&$content)
    {
        if (\is_string($content)) {
            $content = \str_replace(
                array(
                "\n",
                "\r\n",
                ),
                '<br>',
                $content
            );
        }
    }

    /**
     * Convert EOLs to space
     *
     * @param string $content string content by reference
     * 
     * @return void
     */
    public static function convertEolToSpace(&$content)
    {
        if (\is_string($content)) {
            $content = \str_replace(
                array(
                "\n",
                "\r\n",
                ),
                ' ',
                $content
            );
        }
    }

    /**
     * Convert EOLs to breakline + non-breaking space (adjustable by CSS rules)
     *
     * @param string $content string content by reference
     * 
     * @return void
     */
    public static function convertEolToBrNbsp(&$content)
    {
        if (\is_string($content)) {
            $content = \str_replace(
                array(
                "\n",
                "\r\n",
                ),
                '<br><span class="indentation"></span>',
                $content
            );
        }
    }

    /**
     * Convert EOL + hyphen/star to HTML
     *
     * @param string $content string content by reference
     * 
     * @return void
     */
    public static function convertEolHyphenToBrDot(&$content)
    {
        if (\is_string($content)) {
            $content = \str_replace(
                array(
                "\n- ",
                "\r\n- ",
                ),
                '<br>•&nbsp;',
                $content
            );
            if ((\substr($content, 0, 2) === "- ") || (\substr($content, 0, 2) === "* ")) { // phpcs:ignore
                $content = '•&nbsp;' . \substr($content, 2);
            }
        }
    }

    /**
     * Trim various EOL combinations
     *
     * @param string $content string content by reference
     * 
     * @return void
     */
    public static function trimEol(&$content)
    {
        if (\is_string($content)) {
            $content = \str_replace(
                array(
                "\r\n",
                "\n",
                "\r",
                ),
                '',
                $content
            );
        }
    }

    /**
     * Trim THML comments inside the <body> tag
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function trimHtmlComment(&$content)
    {
        if (\is_string($content)) {
            $body = "<body";
            $c = \explode($body, (string) $content, 2);
            $regex = '/<!--(.|\s)*?-->/';
            if (\count($c) === 1) {
                // fix the whole string (= no <body)
                $content = \preg_replace($regex, "<!-- :) -->", $content);
            }
            if (\count($c) === 2) {
                // fix comments inside body
                $c[1] = \preg_replace($regex, "<!-- :) -->", $c[1]);
                $content = $c[0] . $body . $c[1];
            }
        }
    }

    /**
     * Correct text spacing
     * 
     * Correct the text spacing in passed content for various languages.
     *
     * @param string $content  content by reference
     * @param string $language (optional: "cs", "sk", "de", "en" = default)
     * 
     * @return void
     */
    public static function correctTextSpacing(&$content, $language = "en")
    {
        if (!\is_string($content)) {
            return;
        }

        if (!\is_string($language)) {
            $language = 'en'; // default language
        }
        switch (\trim(\strtolower(substr($language, 0, 2)))) {
        case "cs":
            $content = self::correctTextSpacingCs($content);
            break;
        case "sk":
            $content = self::correctTextSpacingSk($content);
            break;
        case "de":
            $content = self::correctTextSpacingDe($content);
            break;
        default:
            $content = self::correctTextSpacingEn($content);
        }
    }

    /**
     * Correct text spacing for English
     *
     * @param string $content string
     * 
     * @return string
     */
    public static function correctTextSpacingEn($content)
    {
        if (!\is_string($content)) {
            return $content;
        }

        $merged = \array_merge(self::$_custom, self::$_common, self::$_english);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Correct text spacing for Deutsch
     *
     * @param string $content string
     * 
     * @return string
     */
    public static function correctTextSpacingDe($content)
    {
        if (!\is_string($content)) {
            return $content;
        }

        $merged = \array_merge(self::$_custom, self::$_common, self::$_deutsch);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Correct text spacing for Czech
     *
     * @param string $content string
     * 
     * @return string
     */
    public static function correctTextSpacingCs($content)
    {
        if (!\is_string($content)) {
            return $content;
        }

        $merged = \array_merge(self::$_custom, self::$_common, self::$_czech);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Correct text spacing for Slovak
     *
     * @param string $content string
     * 
     * @return string
     */
    public static function correctTextSpacingSk($content)
    {
        if (!\is_string($content)) {
            return $content;
        }

        $merged = \array_merge(self::$_custom, self::$_common, self::$_slovak);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Render Markdown to HTML
     *
     * @param string $content string
     * 
     * @return void
     */
    public static function renderMarkdown(&$content)
    {
        if (\is_string($content)) {
            $x = \trim($content);
            if (\str_starts_with($x, '[markdown]')) {
                $x = \substr($x, 10);
                // add extra EOLs for <hr>
                $x = \str_replace("\n---\n", "\n\n---\n\n", $x);
                $content = Markdown::defaultTransform($x);
            }
        }
    }
    
    /**
     * Render Markdown Extra to HTML
     *
     * @param string $content string
     * 
     * @return void
     */
    public static function renderMarkdownExtra(&$content)
    {
        if (\is_string($content)) {
            $x = \trim($content);
            if (\str_starts_with($x, '[markdownextra]')) {
                $x = \substr($x, 15);
                // add extra EOLs for <hr>
                $x = \str_replace("\n---\n", "\n\n---\n\n", $x);
                $content = MarkdownExtra::defaultTransform($x);
            }
        }
    }

    /**
     * Render CSS short code(s) for CSS classes
     *
     * Format: [css class1 class2 ...]CONTENT[/css]
     *
     * @param string $content string containing [css ...]
     * 
     * @return void
     */
    public static function renderCssShortCode(&$content): void
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[css ')) {
            return;
        }

        $pattern = '#\[css\s+(.*?)\](.*?)\[\/css\]#is';

        /**
         * Callback for a single instance
         *
         * @param array $matches matches
         * 
         * @return string HTML <div> section
         */
        $callback = function (array $matches): string {
            $classes = $matches[1] ?? '';
            $inner_content = $matches[2] ?? '';
            $classes = \str_replace(['"', "'"], ' ', $classes);
            $classes = \trim($classes);

            $inner_content = \trim($inner_content);
            if (\str_contains($inner_content, '[') && \str_contains($inner_content, ']') || \str_contains($inner_content, '](')) { // phpcs:ignore
                $inner_content = Markdown::defaultTransform($inner_content);
                $inner_content = \preg_replace('#^<p>(.*?)</p>$#is', '$1', \trim($inner_content)); // phpcs:ignore
            }
            return '<div class="' . $classes . '">' . \trim($inner_content) . '</div>'; // phpcs:ignore
        };

        $newContent = \preg_replace_callback($pattern, $callback, $content);
        if (\is_string($newContent)) {
            $content = $newContent;
        }
        $content = \preg_replace('#^<p>(.*?)</p>$#is', '$1', \trim($content)); // phpcs:ignore
    }

    /**
     * Render Google Map short code(s)
     *
     * @param string $content string containing [googlemap param]
     * @param mixed  $key     Google Maps API key
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderGoogleMapShortCode(&$content, $key = null, $flags = null) // phpcs:ignore
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\is_string($key)) {
            return;
        }
        if (!\str_contains($content, '[googlemap ')) {
            return;
        }

        $key = \trim($key);
        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderGoogleMapSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : ''; 
        }

        $counter = 0;
        $pattern = '#\[googlemap\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[googlemap ')) {
            $counter++;
            $replace = '<iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="100%" '
                . 'height="400" '
                . 'scrolling="no" '
                . 'frameborder="0" '
                . 'style="border:0;" '
                . 'allow="fullscreen" '
                . "data-counter={$counter} "
                . 'src="https://www.google.com/maps/embed/v1/place?key='
                . $key . '&q=$1"></iframe>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Image short code(s)
     *
     * @param string $content string containing [image param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderImageShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[image ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[image\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[image ')) {
            $counter++;
            $replace = '<span class="img-container">'
                . '<img '
                . $lazy
                . 'class=imagesc '
                . 'src="' . CDN . '/upload/$1.webp" '
                . "data-counter={$counter} "
                . 'data-name="$1" '
                . 'alt="$1"'
                . '></span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Figure short code(s)
     *
     * @param string $content string containing [figure param description]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderFigureShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[figure ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderFigureSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[figure\s+([^\]]+?)\s+(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[figure ')) {
            $counter++;
            $replace = '<span class="figure-container">'
                . '<figure><img '
                . $lazy
                . 'class="figuresc responsive-img" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . "data-counter={$counter} "
                . 'data-name="$1" '
                . 'alt="$2"'
                . '>'
                . '<figcaption>$2</figcaption>'
                . '</figure></span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Image Left short code(s)
     *
     * @param string $content string containing [imageleft param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderImageLeftShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[imageleft ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageLeftSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[imageleft\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }
        

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[imageleft ')) {
            $counter++;
            $replace = '<span class="img-left-container">'
                . '<img '
                . $lazy
                . 'class="left imageleftsc" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . "data-counter={$counter} "
                . 'data-name="$1" '
                . 'alt="$1"'
                . '></span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Image Right short code(s)
     *
     * @param string $content string containing [imageright param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderImageRightShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[imageright ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageRightSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[imageright\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[imageright ')) {
            $counter++;
            $replace = '<span class="img-right-container">'
                . '<img '
                . $lazy
                . 'class="right imagerightsc" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . "data-counter={$counter} "
                . 'data-name="$1" '
                . 'alt="$1"'
                . '></span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Image Responsive short code(s)
     *
     * @param string $content string containing [imageresp param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderImageRespShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[imageresp ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageRespSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[imageresp\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[imageresp ')) {
            $counter++;
            $replace = '<span class="img-responsive-container">'
                . '<img '
                . $lazy
                . 'class="responsive-img imagerespsc" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . "data-counter={$counter} "
                . 'data-name="$1" '
                . 'alt="$1"'
                . '></span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Soundcloud short code(s)
     *
     * @param string $content string containing [soundcloud param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderSoundCloudShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[soundcloud ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderSoundcloudSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[soundcloud\s+([^\]]+)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[soundcloud ')) {
            $counter++;
            $replace = '<div '
                . 'class="audio-container center row soundcloud-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="100%" '
                . 'height="300" '
                . 'scrolling="no" '
                . 'frameborder="0" '
                . 'style="border:0;" '
                . 'allow="autoplay;fullscreen;picture-in-picture" '
                . 'src="https://w.soundcloud.com/player/?url='
                . 'https%3A//api.soundcloud.com/tracks/$1&'
                . 'auto_play=false&hide_related=false&show_comments=true&'
                . 'show_user=true&show_reposts=false&show_teaser=true&visual=true">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render YouTube short code(s)
     *
     * @param string $content string containing [youtube param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderYouTubeShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[youtube ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderYouTubeSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[youtube\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[youtube ')) {
            $counter++;
            $replace = '<div '
                . 'class="video-container center row youtube-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="480" '
                . 'height="270" '
                . 'scrolling="no" '
                . 'frameborder="0" '
                . 'style="border:0;" '
                . 'allow="autoplay;fullscreen;picture-in-picture" '
                . 'controls '
                . 'src="https://www.youtube.com/embed/$1">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Vimeo short code(s)
     *
     * @param string $content string containing [vimeo param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderVimeoShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[vimeo ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderVimeoSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[vimeo\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[vimeo ')) {
            $counter++;
            $replace = '<div '
                . 'class="video-container center row vimeo-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="640" '
                . 'height="360" '
                . 'scrolling="no" '
                . 'frameborder="0" '
                . 'style="border:0;" '
                . 'allow="autoplay;fullscreen;picture-in-picture" '
                . 'src="https://player.vimeo.com/video/$1">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Twitch (channel) short code(s)
     *
     * @param string $content string containing [twitch param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderTwitchChannellShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[twitch ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderTwitchSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[twitch\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[twitch ')) {
            $counter++;
            $replace = '<div '
                . 'class="video-container center row twitch-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="480" '
                . 'height="270" '
                . 'scrolling="no" '
                . 'frameborder="0" '
                . 'style="border:0;" '
                . 'allow="autoplay;fullscreen;picture-in-picture" '
                . 'src="https://player.twitch.tv/?channel=$1&parent='
                . DOMAIN
                . '&autoplay=false">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Twitch (video) short code(s)
     *
     * @param string $content string containing [twitchvid param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderTwitchVidShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[twitchvid ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderTwitchvidSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[twitchvid\s.*?(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[twitchvid ')) {
            $counter++;
            $replace = '<div '
                . 'class="video-container center row twitchvid-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="480" '
                . 'height="270" '
                . 'scrolling="no" '
                . 'frameborder="0" '
                . 'style="border:0;" '
                . 'controls '
                . 'allow="autoplay;fullscreen;picture-in-picture" '
                . 'src="https://player.twitch.tv/?video=$1&parent='
                . DOMAIN
                . '&autoplay=false">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render Mastodon (post) short code(s)
     *
     * @param string $content string containing [mastodon param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderMastodonShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[mastodon ')) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderMastodonSC: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : '';
        }

        $counter = 0;
        $pattern = '#\[mastodon\s+(https:\/\/[^\s]+)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[mastodon ')) {
            $counter++;
            $replace = '<div '
                . 'class="social-container center row mastodon-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="400" '
                . 'height="auto" '
                . 'frameborder="0" '
                . 'scrolling="no" '
                . 'style="border:0;max-width:100%;" '
                . 'src="$1/embed">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render gallery short code(s)
     *
     * @param string $content string containing [gallery mask [order|random]]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderGalleryShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[gallery ')) {
            return;
        }

        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('Incorrect SC flag!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : ''; 
            $shuffle_global = (bool) ($flags & self::GALLERY_RANDOM);
            $size = 160;
            if ($flags & self::THUMBS_80) {
                $size = 80;
            }
            if ($flags & self::THUMBS_160) {
                $size = 160;
            }
            if ($flags & self::THUMBS_320) {
                $size = 320;
            }
            if ($flags & self::THUMBS_640) {
                $size = 640;
            }
            if ($flags & self::THUMBS_1280) {
                $size = 1280;
            }
        }

        $counter = 0;
        $pattern = '#\[gallery\s(.*?)\s*?\]#is';

        $hash = sha1($content);
        $original_content = $content;
        $timer = "gallery_shortcode_{$hash}";
        Debugger::timer($timer);
        $cache = CACHE . DS . "gallery_shortcode_{$hash}.html";
        if (self::USE_CACHE && \file_exists($cache) && \is_readable($cache)) {
            if ((\time() - \filemtime($cache)) < self::CACHE_TTL) {
                $content = \file_get_contents($cache);
                return;
            }
        }

        while (\is_string($content) && \str_contains($content, '[gallery ')) {  
            \preg_match($pattern, $content, $m);
            if (isset($m[1])) {
                $mask = $full_param_string = $m[1];
                $params = \preg_split('/\s+/', $full_param_string);
                $order_param = null;
                if (\is_array($params) && \count($params)) {
                    $mask = $params[0];
                    if (\count($params) > 1) {
                        $order_param = \strtolower($params[1]);
                    }
                }
                $gallery = $mask;
                $gname = \trim($gallery, '+-_()[]');
                $gname = \strtr($gallery, '+-_()[]', '       ');
                $counter++;
                $images = '';
                $files = self::findImagesByMask($gallery);
                $shuffle = $shuffle_global;
                if (\is_array($files)) {
                    if ($order_param === 'random') {
                        $shuffle = true;
                    } elseif ($order_param === 'order') {
                        $shuffle = false;
                    }
                    if ($shuffle) {
                        \shuffle($files);
                    }
                    $id = 0;
                    foreach ($files as $f) {
                        $id++;
                        $t = CDN . "/upload/.thumb_{$size}px_{$f}";
                        $n = \pathinfo(
                            \strtoupper(
                                \str_ireplace($gallery, '', $f)
                            ), PATHINFO_FILENAME
                        );
                        $n = \trim($n, '+-_()[]');
                        $n = \trim(\strtr($n, '_', ' '));
                        $images .= "<a "
                            . "data-lightbox='{$gname}' "
                            . "class='gallery-link' "
                            . 'href="' . CDN . "/upload/{$f}\""
                            . '><img '
                            . "src=\"{$t}\" "
                            . $lazy
                            . 'class="gallery-img" '
                            . 'data-source="' . CDN . "/upload/{$f}" . '" '
                            . "data-id={$id} "
                            . "data-thumb=\"{$t}\" "
                            . "data-tooltip=\"{$n}\" "
                            . "alt=\"{$id}. {$gallery} [{$n}]\" "
                            . '></a>';
                    }
                }
                $shuffle = $shuffle ? "true" : "false";
                $replace = "<div "
                    . "class='row center gallery-container gallery-{$mask}' "
                    . "data-shuffle={$shuffle} "
                    . "data-counter={$counter} "
                    . "data-gallery='{$gname}'>"
                    . $images
                    . "</div>";
                if (\is_string($content)) {
                    $content = \preg_replace($pattern, $replace, $content, 1);
                }
                if ($counter === self::ITERATIONS) {
                    break;
                }
            }
        }

        $elapsed = round(Debugger::timer($timer) * 1000000);
        if (self::USE_CACHE && $elapsed > 100) {
            file_put_contents($cache, $content, LOCK_EX);
            self::profiler('cached-renderGalleryShortCode', $hash, $elapsed, $original_content); // phpcs:ignore
        } else {
            self::profiler('renderGalleryShortCode', $hash, $elapsed, $original_content);  // phpcs:ignore
        }
    }

    /**
     * Render gallery short code(s) as spans
     *
     * @param string $content string containing [galleryspan mask [order|random]]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderGallerySpanShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[galleryspan ')) {
            return;
        }

        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('Incorrect SC flag!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? ' loading="lazy" ' : ''; 
            $shuffle_global = (bool) ($flags & self::GALLERY_RANDOM);
            $size = 160;
            if ($flags & self::THUMBS_80) {
                $size = 80;
            }
            if ($flags & self::THUMBS_160) {
                $size = 160;
            }
            if ($flags & self::THUMBS_320) {
                $size = 320;
            }
            if ($flags & self::THUMBS_640) {
                $size = 640;
            }
            if ($flags & self::THUMBS_1280) {
                $size = 1280;
            }
        }

        $counter = 0;
        $pattern = '#\[galleryspan\s(.*?)\s*?\]#is';

        $hash = sha1($content);
        $original_content = $content;
        $timer = "galleryspan_shortcode_{$hash}";
        Debugger::timer($timer);
        $cache = CACHE . DS . "galleryspan_shortcode_{$hash}.html";
        if (self::USE_CACHE && \file_exists($cache) && \is_readable($cache)) {
            if ((\time() - \filemtime($cache)) < self::CACHE_TTL) {
                $content = \file_get_contents($cache);
                return;
            }
        }

        while (\is_string($content) && \str_contains($content, '[galleryspan ')) {
            \preg_match($pattern, $content, $m);
            if (isset($m[1])) {
                $mask = $full_param_string = $m[1];
                $params = \preg_split('/\s+/', $full_param_string);
                $order_param = null;
                if (\is_array($params) && \count($params)) {
                    $mask = $params[0];
                    if (\count($params) > 1) {
                        $order_param = \strtolower($params[1]);
                    }
                }
                $gallery = $mask;
                $gname = \trim($gallery, '+-_()[]');
                $gname = \strtr($gallery, '+-_()[]', '       ');
                $counter++;
                $images = '';
                $files = self::findImagesByMask($gallery);
                $shuffle = $shuffle_global;
                if (\is_array($files)) {
                    if ($order_param === 'random') {
                        $shuffle = true;
                    } elseif ($order_param === 'order') {
                        $shuffle = false;
                    }
                    if ($shuffle) {
                        \shuffle($files);
                    }
                    $id = 0;
                    foreach ($files as $f) {
                        $id++;
                        $t = CDN . "/upload/.thumb_{$size}px_{$f}";
                        $n = \pathinfo(
                            \strtoupper(
                                \str_ireplace($gallery, '', $f)
                            ), PATHINFO_FILENAME
                        );
                        $n = \trim($n, '+-_()[]');
                        $n = \trim(\strtr($n, '_', ' '));
                        $images .= "<span "
                            . "class='galleryspan' "
                            . 'href="' . CDN . "/upload/{$f}\""
                            . '><img '
                            . "src=\"{$t}\" "
                            . $lazy
                            . 'class="galleryspan-img" '
                            . 'data-source="' . CDN . "/upload/{$f}" . '" '
                            . "data-id={$id} "
                            . "data-thumb=\"{$t}\" "
                            . "data-tooltip=\"{$n}\" "
                            . "alt=\"{$id}. {$gallery} [{$n}]\" "
                            . '></span>';
                    }
                }
                $shuffle = $shuffle ? "true" : "false";
                $replace = "<div "
                    . "class='row center galleryspan-container gallery-{$mask}' "
                    . "data-shuffle={$shuffle} "
                    . "data-counter={$counter} "
                    . "data-gallery='{$gname}'>"
                    . $images
                    . "</div>";
                if (\is_string($content)) {
                    $content = \preg_replace($pattern, $replace, $content, 1);
                }
                if ($counter === self::ITERATIONS) {
                    break;
                }
            }
        }

        $elapsed = round(Debugger::timer($timer) * 1000000);
        if (self::USE_CACHE && $elapsed > 100) {
            file_put_contents($cache, $content, LOCK_EX);
            self::profiler('cached-renderGallerySpanShortCode', $hash, $elapsed, $original_content); // phpcs:ignore
        } else {
            self::profiler('renderGallerySpanShortCode', $hash, $elapsed, $original_content);  // phpcs:ignore
        }
    }

    /**
     * Render QR short code(s)
     *
     * @param string $content string containing [qrcode param]
     * @param mixed  $flags   flags
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function renderQRShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }
        if (!\str_contains($content, '[qrcode ')) {
            return;
        }

        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderQRSC: FLAGS!');
        }
        
        $lazy = (bool) ($flags & self::LAZY_LOADING);
        $lazy_attr = $lazy ? ' loading="lazy" ' : '';

        $counter = 0;
        $pattern = '#\[qrcode\s*(.*?)\]#is';

        if (!\is_string($content)) {
            return;
        }

        // @phpstan-ignore-next-line
        while (\str_contains($content, '[qrcode ')) {
            $counter++;
            $replace = '<div '
                    . 'class="qr-container" '
                    . "data-counter={$counter}>"
                    . '<img '
                    . $lazy_attr
                    . 'class="qrcode-sc" '
                    . 'src="$1" '
                    . 'alt="QR"'
                    . '></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content, 1);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Find uploaded images by a mask
     *
     * @param string $mask   file mask
     * @param string $format image format
     * 
     * @return mixed
     */
    public static function findImagesByMask($mask = null, $format = 'webp')
    {
        if (!defined('UPLOAD') || !UPLOAD || !\is_dir(UPLOAD)) {
            return null;
        }
        if (!\is_string($mask)) {
            return null;
        }
        
        // mask cleaning
        $mask = \trim($mask);
        $mask = \strtolower($mask);
        $mask = \preg_replace(self::UPLOAD_SANITIZE, '', \trim($mask));
        if ($mask) {
            $mask = \str_replace('..', '.', $mask);
        }
        if (!\is_string($mask)) {
            return null;
        }

        // mask always ends with '*'
        if (!\str_ends_with($mask, '*')) {
            $mask .= '*';
        }

        // format sanitization
        $format = \strtolower($format);
        $allowed_extensions = ['.webp', '.png', '.jpg', '.jpeg', '.gif'];
        $has_known_extension = false;
        foreach ($allowed_extensions as $ext) {
            if (\str_ends_with($mask, $ext)) {
                $has_known_extension = true;
                break;
            }
        }
        if (!\is_string($format) || !\strlen($format)) {
            $format = 'webp';
        }
        if ($has_known_extension) {
            $format = 'webp';
        }

        // glob search
        if (\is_string($mask)) {
            \chdir(UPLOAD);
            $data = \glob($mask . ".{$format}") ?: null;
            if ($data) {
                \usort(
                    $data, function ($a, $b) {
                        return \strnatcmp($a, $b);
                    }
                );
            }
            return $data;
        }
        return null;
    }

    /**
     * Find uploaded files by a mask
     *
     * @param string $mask file mask
     * 
     * @return mixed
     */
    public static function findFilesByMask($mask = null)
    {
        if (!defined('UPLOAD') || !UPLOAD || !\is_dir(UPLOAD)) {
            return null;
        }
        if (!\is_string($mask)) {
            return null;
        }

        // mask sanitization
        $mask = \preg_replace(self::UPLOAD_SANITIZE, '', \trim($mask));
        if ($mask) {
            $mask = \str_replace('..', '.', $mask);
        }
        if (!\is_string($mask)) {
            return null;
        }

        // glob search
        if (\is_string($mask)) {
            \chdir(UPLOAD);
            if ($data = \glob($mask) ?: null) {
                \usort(
                    $data, function ($a, $b) {
                        return \strnatcmp($a, $b);
                    }
                );
            }
            return $data;
        }
        return null;
    }

    /**
     * Sanitize a string to a safe variant
     *
     * @param string $string string by reference
     * 
     * @return void
     */
    public static function sanitizeString(&$string)
    {
        if (\is_string($string)) {
            $string = \preg_replace(self::STRING_SANITIZE, '_', \trim($string));
            if ($string && \is_string($string)) {
                $string = \preg_replace('/_+/', '_', $string);
                if ($string && \is_string($string)) {
                    $string = \trim($string, '_');
                    $string = \trim($string);
                }
            }
        }
    }

    /**
     * Sanitize a string to a lowercase safe variant
     *
     * @param string $string string by reference
     * 
     * @return void
     */
    public static function sanitizeStringLC(&$string)
    {
        if (\is_string($string)) {
            if ($string && \is_string($string)) {
                $string = \preg_replace(self::STRING_SANITIZE, '_', \trim($string));
                if ($string && \is_string($string)) {
                    $string = \strtolower($string);
                    $string = \preg_replace('/_+/', '_', $string);
                    if ($string && \is_string($string)) {
                        $string = \trim($string, '_');
                        $string = \trim($string);
                    }
                }
            }
        }
    }

    /**
     * Transliterate a string to safe characters without accents
     *
     * @param string $string string by reference
     * 
     * @return void
     */
    public static function transliterate(&$string)
    {
        if (\is_string($string)) {
            $string = \str_replace(
                \array_keys(self::$_transliteration),
                self::$_transliteration,
                $string
            );
        }
    }

    /**
     * Converts a Unicode string to lowercase
     *
     * @param string $string input string
     *
     * @return void
     */
    public static function strtolower(&$string)
    {
        if (\is_string($string)) {
            $string = \mb_strtolower($string, 'UTF-8');
        }
    }

    /**
     * Converts a Unicode string to uppercase
     *
     * @param string $string input string
     *
     * @return void
     */
    public static function strtoupper(&$string)
    {
        if (\is_string($string)) {
            $string = \mb_strtoupper($string, 'UTF-8');
        }
    }

    /**
     * Sort an array of mixed data types in ascending order: numbers and strings.
     *
     * @param array<mixed> $array array to be sorted by reference
     *
     * @return void
     *
     * @throws \InvalidArgumentException if the input is not an array
     */
    public static function sort(&$array)
    {
        if (!\is_array($array)) {
            throw new \InvalidArgumentException('Input must be an array.');
        }

        $numbers = [];
        $strings = [];
        foreach ($array as $a) {
            if (\is_numeric($a)) {
                $numbers[] = $a;
            } else {
                $strings[] = $a;
            }
        }
        \sort($numbers, SORT_NUMERIC);
        \sort($strings, SORT_LOCALE_STRING);
        $array = \array_merge($numbers, $strings);
    }

    /**
     * Sort an array of mixed data types in descending order: numbers and strings.
     *
     * @param array<mixed> $array array to be sorted by reference
     *
     * @return void
     *
     * @throws \InvalidArgumentException if the input is not an array
     */
    public static function rsort(&$array)
    {
        if (!\is_array($array)) {
            throw new \InvalidArgumentException('Input must be an array.');
        }

        $numbers = [];
        $strings = [];
        foreach ($array as $a) {
            if (\is_numeric($a)) {
                $numbers[] = $a;
            } else {
                $strings[] = $a;
            }
        }
        \rsort($numbers, SORT_NUMERIC);
        \rsort($strings, SORT_LOCALE_STRING);
        $array = \array_merge($numbers, $strings);
    }

    /**
     * Process shortcodes in a string
     *
     * @param string $string input string containing shortcodes by reference
     * @param mixed  $flags  flags
     *
     * @return void
     * 
     * @throws \InvalidArgumentException for incorrect flags
     */
    public static function shortCodesProcessor(&$string, $flags)
    {
        if (!\is_string($string) || empty($string)) {
            return;
        }
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('shortCodesProcessor: incorrect flags'); // phpcs:ignore
        }

        // tokenize shortcodes
        self::tokenize($string);

        $md = false;
        if (\str_starts_with($string, '[markdown]')) {
            self::renderMarkdown($string);
        } elseif (\str_starts_with($string, '[markdownextra]')) {
            self::renderMarkdownExtra($string);
        }

        // detokenize shortcodes
        self::detokenize($string);

        // render shortcodes
        self::renderFigureShortCode($string, $flags);
        self::renderGalleryShortCode($string, $flags);
        self::renderGallerySpanShortCode($string, $flags);
        self::renderImageLeftShortCode($string, $flags);
        self::renderImageRespShortCode($string, $flags);
        self::renderImageRightShortCode($string, $flags);
        self::renderImageShortCode($string, $flags);
        self::renderMastodonShortCode($string, $flags);
        self::renderCssShortCode($string);
        self::renderQRShortCode($string, $flags);
        self::renderSoundCloudShortCode($string, $flags);
        self::renderTwitchChannellShortCode($string, $flags);
        self::renderTwitchVidShortCode($string, $flags);
        self::renderVimeoShortCode($string, $flags);
        self::renderYouTubeShortCode($string, $flags);

        //bdump(self::$_shortCodeCache);
    }

    /**
     * Extract shortcodes and replace them with unique tokens (~sc#~)
     *
     * @param string $string input string containing shortcodes by reference
     * 
     * @return void
     */
    public static function tokenize(&$string): void
    {
        self::$_shortCodeCache = [];
        $names = \implode('|', self::ALL_SHORTCODES);
        $pattern = '/\[(?P<name>' . $names . ')(?:\s+(?P<args>[^\]]*))?\](?:(?P<content>.*?)\[\/\1\])?/si'; // phpcs:ignore
        
        if (\preg_match_all($pattern, $string, $matches)) {
            $tokens = [];
            $replacements = [];
            $i = 1;
            foreach ($matches[0] as $match) {
                if (\preg_match('/^\[(\w+)/', $match, $name_match)) {
                    $shortcode_name = $name_match[1];
                    $lower_name = \strtolower($shortcode_name);
                    $normalized_match = \str_replace('[' . $shortcode_name, '[' . $lower_name, $match); // phpcs:ignore
                } else {
                    $normalized_match = $match;
                }

                // create a unique nonce
                do {
                    $nonce = "~sc_" . bin2hex(random_bytes(2));
                } while (\strpos($string, $nonce) !== false);

                // nonce based token
                $token = $nonce . $i++ . '~';

                self::$_shortCodeCache[$token] = $match;
                $tokens[] = $match;
                $replacements[] = $token;
            }
            $string = \str_replace($tokens, $replacements, $string);
        }
    }

    /**
     * Replace all shortcode tokens with the original content
     *
     * @param string $string input string containing shortcode tokens by reference
     * 
     * @return void
     */
    public static function detokenize(&$string): void
    {
        if (empty(self::$_shortCodeCache)) {
            return;
        }

        //\bdump(self::$_shortCodeCache);

        $string = \str_replace(\array_keys(self::$_shortCodeCache), \array_values(self::$_shortCodeCache), $string); // phpcs:ignore
        self::$_shortCodeCache = [];
    }

    /**
     * Profiler
     * 
     * @param string $type    profiler type
     * @param string $hash    data hash
     * @param float  $elapsed time in microseconds
     * @param string $data    original content
     * 
     * @return void
     */
    public static function profiler(string $type, string $hash, float $elapsed, string $data): void // phpcs:ignore
    {
        if (empty($elapsed) || empty($hash) || empty($data)) {
            return;
        }

        if (\is_string($hash) && \is_string($data)) {
            $file = CACHE . DS . "cacheprofiler_{$type}_{$hash}.json";
            $payload = [
                'hash'     => $hash,
                'duration' => $elapsed,
                'timestamp'=> \date('Y-m-d H:i:s'),
                'data'     => $data
            ];
            \file_put_contents(
                $file,
                \json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                LOCK_EX
            );
        }
    }

}