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

use Michelf\Markdown;
use Michelf\MarkdownExtra;

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
    // max. single short code iterations
    const ITERATIONS = 30;

    // SANITIZATION: IMAGE MASK for search
    const UPLOAD_SANITIZE = '/[^a-z0-9!@#+*=,;\-._]+/i';
    // SANITIZATION: allowed CHARACTERS
    const STRING_SANITIZE = '/[^a-z0-9\-._]+/i';

    // FLAGS: randomize galleries
    const GALLERY_RANDOM = 1;
    // FLAGS: use lazy loading
    const LAZY_LOADING = 2;
    // FLAGS: 160px thumbnails
    const THUMBS_160 = 4;
    // FLAGS: 320px thumbnails
    const THUMBS_320 = 8;
    // FLAGS: 640px thumbnails
    const THUMBS_640 = 16;

    // English lowercase characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_english = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    // English uppercase characters
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

    // Czech uppercase characters
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

    // Slovak uppercase characters
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
        
        " CZK " => " CZK&nbsp;",
        " EUR " => " EUR&nbsp;",
        " USD " => " USD&nbsp;",

        " id: " => " id:&nbsp;",
        " ID: " => " ID:&nbsp;",
        " Inc." => "&nbsp;Inc.",
        " INC." => "&nbsp;Inc.",
        " Ltd." => "&nbsp;Ltd.",
        " LTD." => "&nbsp;Ltd.",
        " X " => "&nbsp;X ",

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
        " He's " => " He&rsquo;s ",
        " It's " => " It&rsquo;s ",
        " She's " => " She&rsquo;s ",
        " The " => " The&nbsp;",
        " he's " => " he&rsquo;s ",
        " it's " => " it&rsquo;s ",
        " she's " => " she&rsquo;s ",
        " the " => " the&nbsp;",

        " A " => " A&nbsp;",
        " An " => " An&nbsp;",
        " AM" => "&nbsp;AM",
        " I " => " I&nbsp;",
        " Miss " => " Miss&nbsp;",
        " Mr " => " Mr&nbsp;",
        " Mr. " => " Mr.&nbsp;",
        " Mrs " => " Mrs&nbsp;",
        " Mrs. " => " Mrs.&nbsp;",
        " Ms " => " Ms&nbsp;",
        " Ms. " => " Ms.&nbsp;",
        " PM" => "&nbsp;PM",
        " a " => " a&nbsp;",
        " an " => " an&nbsp;",
        " pcs " => "&nbsp;pcs ",
        " pcs)" => "&nbsp;pcs)",
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
     * Set custom string replacements
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
                'Invalid argument: Expected an array.'
            );
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
                'Invalid argument: Expected an array.'
            );
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
                ), '<br>', $content
            );
        }
    }

    /**
     * Convert EOLs to breakline + non-breakable space (adjustable by CSS rules)
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
                ), '<br><span class="indentation"></span>', $content
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
                ), '<br>•&nbsp;', $content
            );
            if ((\substr($content, 0, 2) == "- ") || (\substr($content, 0, 2) == "* ")) { // phpcs:ignore
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
                ), '', $content
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
            if (\count($c) === 1) { // fix the whole string (= no <body)
                $content = \preg_replace($regex, "<!-- :) -->", $content);
            }
            if (\count($c) === 2) { // fix comments inside body
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
     * @param string $language (optional: "cs", "sk", default "en" - for now)
     * 
     * @return void
     */
    public static function correctTextSpacing(&$content, $language = "en")
    {
        if (!\is_string($content)) {
            return;
        }

        if (!\is_string($language)) {
            $language = 'en';
        }
        $language = \strtolower($language);
        switch ($language) {
        case "cs":
            $content = self::correctTextSpacingCs($content);
            break;
        case "sk":
            $content = self::correctTextSpacingSk($content);
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
                $x = \str_replace("\n---\n", "\n\n---\n\n", $x); // extra EOLs <hr>
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
                $x = \str_replace("\n---\n", "\n\n---\n\n", $x); // extra EOLs <hr>
                $content = MarkdownExtra::defaultTransform($x);
            }
        }
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

        $key = \trim($key);
        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderGoogleMapShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : ''; 
        }
        $counter = 0;
        $pattern = '#\[googlemap\s.*?(.*?)\]#is';
        while (\str_contains($content, '[googlemap ')) {
            $counter++;
            $replace = '<iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="100%" '
                . 'height="400" '
                . 'scrolling=no '
                . 'frameborder=no '
                . 'style="border:0" '
                . 'allowfullscreen '
                . "data-counter={$counter} "
                . 'src="https://www.google.com/maps/embed/v1/place?key='
                . $key . '&q=$1"></iframe>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
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

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : '';
        }
        $counter = 0;
        $pattern = '#\[image\s.*?(.*?)\]#is';
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
                $content = \preg_replace($pattern, $replace, $content);
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

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : '';
        }
        $counter = 0;
        $pattern = '#\[figure\s+([^\]]+?)\s+(.*?)\]#is';
        while (\str_contains($content, '[figure ')) {
            $counter++;
            $replace = '<span class="figure-container">'
                . '<figure><img '
                . $lazy
                . 'class=figuresc '
                . 'src="' . CDN . '/upload/$1.webp" '
                . "data-counter={$counter} "
                . 'data-name="$1" '
                . 'alt="$2"'
                . '>'
                . '<figcaption>$2</figcaption>'
                . '</figure></span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
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

        $x = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageLeftShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : '';
        }
        $counter = 0;
        $pattern = '#\[imageleft\s.*?(.*?)\]#is';
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
                $content = \preg_replace($pattern, $replace, $content);
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

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageRightShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : '';
        }
        $counter = 0;
        $pattern = '#\[imageright\s.*?(.*?)\]#is';
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
                $content = \preg_replace($pattern, $replace, $content);
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

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \InvalidArgumentException('renderImageRespShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : '';
        }
        $counter = 0;
        $pattern = '#\[imageresp\s.*?(.*?)\]#is';
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
                $content = \preg_replace($pattern, $replace, $content);
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
    public static function renderSoundcloudShortCode(&$content, $flags = null)
    {
        if (!\is_string($content)) {
            return;
        }

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \Exception('renderSoundcloudShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy' : '';
        }
        $counter = 0;
        $pattern = '#\[soundcloud\s.*?(.*?)\]#is';
        while (\str_contains($content, '[soundcloud ')) {
            $counter++;
            $replace = '<div '
                . 'class="audio-container center row soundcloud-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width="100%" '
                . 'height=300 '
                . 'scrolling=no '
                . 'frameborder=no '
                . 'style="border:0" '
                . 'allowfullscreen '
                . 'controls '
                . "data-counter={$counter} "
                . 'src="https://w.soundcloud.com/player/?url='
                . 'https%3A//api.soundcloud.com/tracks/$1&'
                . 'auto_play=false&hide_related=false&show_comments=true&'
                . 'show_user=true&show_reposts=false&show_teaser=true&visual=true">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
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

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \Exception('renderYouTubeShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy' : '';
        }
        $counter = 0;
        $pattern = '#\[youtube\s.*?(.*?)\]#is';
        while (\str_contains($content, '[youtube ')) {
            $counter++;
            $replace = '<div '
                . 'class="video-container center row youtube-container" '
                . "data-counter={$counter} "
                . '><iframe '
                . $lazy
                . 'referrerpolicy="no-referrer-when-downgrade" '
                . 'width=480 '
                . 'height=270 '
                . 'frameborder=no '
                . 'style="border:0" '
                . 'allowfullscreen '
                . 'controls '
                . 'src="https://www.youtube.com/embed/$1">'
                . '</iframe></div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
            if ($counter === self::ITERATIONS) {
                break;
            }
        }
    }

    /**
     * Render gallery short code(s)
     *
     * @param string $content string containing [gallery param]
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

        $content = \trim($content);
        if (!\is_integer($flags)) {
            throw new \Exception('renderGalleryShortCode: FLAGS!');
        } else {
            $lazy = (bool) ($flags & self::LAZY_LOADING);
            $lazy = $lazy ? 'loading=lazy ' : ''; 
            $shuffle = (bool) ($flags & self::GALLERY_RANDOM);
            $size = 160;
            if ($flags & self::THUMBS_160) {
                $size = 160;
            }
            if ($flags & self::THUMBS_320) {
                $size = 320;
            }
            if ($flags & self::THUMBS_640) {
                $size = 640;
            }
        }
        $counter = 0;
        $pattern = '#\[gallery\s.*?(.*?)\]#is';
        while (\str_contains($content, '[gallery ')) {
            \preg_match($pattern, $content, $m);
            if (\is_array($m) && isset($m[1])) {
                $gallery = $m[1];
                $counter++;
                $images = '';
                $files = self::findImagesByMask($gallery);
                if (\is_array($files)) {
                    if ($shuffle !== false) {
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
                        $n = \trim(\strtr($n, '+-_()[]', '       '));
                        $images .= "<a "
                            . "data-lightbox='{$gallery}' "
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
                $replace = "<div "
                    . "class='row center gallery-container' "
                    . "data-counter={$counter} "
                    . "data-gallery=\"{$gallery}\">"
                    . $images
                    . "</div>";
                if (\is_string($content)) {
                    $content = \str_replace(
                        "[gallery {$gallery}]",
                        $replace,
                        $content
                    );
                }
                if ($counter === self::ITERATIONS) {
                    break;
                }
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
        if (!UPLOAD) {
            return null;
        }

        if (!\is_string($mask)) {
            return null;
        }

        $mask = \trim($mask);
        $mask = \strtolower($mask);

        // hack to fix Markdown <em> markup
        $mask = \str_replace('<em>', '_', $mask);
        $mask = \str_replace('</em>', '_', $mask);
        $mask = \preg_replace(self::UPLOAD_SANITIZE, '', \trim($mask));
        if ($mask) {
            $mask = \str_replace('..', '.', $mask);
        }

        if (!\is_string($mask)) {
            return null;
        }

        $format = \strtolower($format);
        if (!\is_string($format) || !\strlen($format)) {
            $format = 'webp';
        }
        if (!\in_array($format, ['gif', 'jpg', 'png', 'webp'])) {
            $format = 'webp';
        }

        if (\is_string($mask) && \is_dir(UPLOAD)) {
            \chdir(UPLOAD);
            $data = \glob($mask . '*.' . $format) ?: null;
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
     * Find uploaded files by mask
     *
     * @param string $mask file mask
     * 
     * @return mixed
     */
    public static function findFilesByMask($mask = null)
    {
        if (!UPLOAD) {
            return null;
        }
        if (!\is_string($mask)) {
            return null;
        }

        $mask = \preg_replace(self::UPLOAD_SANITIZE, '', \trim($mask));
        if ($mask) {
            $mask = \str_replace('..', '.', $mask);
        }
        if (\is_string($mask) && \is_dir(UPLOAD)) {
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
     * @param array<mixed> $arr array to be sorted by reference
     *
     * @return void
     *
     * @throws \InvalidArgumentException if the input is not an array
     */
    public static function sort(&$arr)
    {
        if (!\is_array($arr)) {
            throw new \InvalidArgumentException('Input must be an array.');
        }

        $numbers = [];
        $strings = [];
        foreach ($arr as $a) {
            if (\is_numeric($a)) {
                $numbers[] = $a;
            } else {
                $strings[] = $a;
            }
        }
        \sort($numbers, SORT_NUMERIC);
        \sort($strings, SORT_LOCALE_STRING);
        $arr = \array_merge($numbers, $strings);
    }

    /**
     * Sort an array of mixed data types in descending order: numbers and strings.
     *
     * @param array<mixed> $arr array to be sorted by reference
     *
     * @return void
     *
     * @throws \InvalidArgumentException if the input is not an array
     */
    public static function rsort(&$arr)
    {
        if (!\is_array($arr)) {
            throw new \InvalidArgumentException('Input must be an array.');
        }

        $numbers = [];
        $strings = [];
        foreach ($arr as $a) {
            if (\is_numeric($a)) {
                $numbers[] = $a;
            } else {
                $strings[] = $a;
            }
        }
        \rsort($numbers, SORT_NUMERIC);
        \rsort($strings, SORT_LOCALE_STRING);
        $arr = \array_merge($numbers, $strings);
    }

    /**
     * Process short codes in a string (also process Markdown if starting with it)
     *
     * @param string $string input string containing short codes by reference
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

        if (\str_starts_with($string, '[markdown]')) {
            self::renderMarkdown($string);
        } elseif (\str_starts_with($string, '[markdownextra]')) {
            self::renderMarkdownExtra($string);
        }
        self::renderImageShortCode($string, $flags);
        self::renderImageLeftShortCode($string, $flags);
        self::renderImageRightShortCode($string, $flags);
        self::renderImageRespShortCode($string, $flags);
        self::renderFigureShortCode($string, $flags);
        self::renderGalleryShortCode($string, $flags);
        self::renderYouTubeShortCode($string, $flags);
        self::renderSoundcloudShortCode($string, $flags);
    }
}
