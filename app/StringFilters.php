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
 * String Filters interface
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
interface IStringFilters
{
    /**
     * Converts a string to lowercase
     *
     * @param string $string input string
     *
     * @return string lowercase version of the input string
     */
    public static function strtolower($string);

    /**
     * Converts a string to uppercase
     *
     * @param string $string input string
     *
     * @return string uppercase version of the input string
     */
    public static function strtoupper($string);

    /**
     * Convert EOLs to <br>
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolToBr(&$content);

    /**
     * Convert EOLs to breakline + non-breakable space (adjustable by CSS rules)
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolToBrNbsp(&$content);

    /**
     * Convert EOL + hyphen/star to HTML
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolHyphenToBrDot(&$content);

    /**
     * Correct text spacing
     * 
     * Correct the text spacing in passed content for various languages.
     *
     * @param string $content  content by reference
     * @param string $language (optional: "cs", "sk", "en")
     * 
     * @return void
     */
    public static function correctTextSpacing(&$content, $language);

    /**
     * Trim various EOL combinations
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function trimEol(&$content);

    /**
     * Trim THML comments inside the <body> tag
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function trimHtmlComment(&$content);

    /**
     * Render Markdown to HTML
     *
     * @param string $content text data
     * 
     * @return void
     */
    public static function renderMarkdown(&$content);

    /**
     * Render Markdown Extra to HTML
     *
     * @param string $content text data
     * 
     * @return void
     */
    public static function renderMarkdownExtra(&$content);

    /**
     * Render Image short code(s)
     *
     * @param string $content text data containing [image param]
     * 
     * @return void
     */
    public static function renderImageShortCode(&$content);

    /**
     * Render Image Left short code(s)
     *
     * @param string $content text data containing [imageleft param]
     * 
     * @return void
     */
    public static function renderImageLeftShortCode(&$content);

    /**
     * Render Image Right short code(s)
     *
     * @param string $content text data containing [imageright param]
     * 
     * @return void
     */
    public static function renderImageRightShortCode(&$content);

    /**
     * Render Image Responsive short code(s)
     *
     * @param string $content text data containing [imageresp param]
     * 
     * @return void
     */
    public static function renderImageRespShortCode(&$content);

    /**
     * Render Soundcloud short code(s)
     *
     * @param string $content text data containing [soundcloud param]
     * 
     * @return void
     */
    public static function renderSoundcloudShortCode(&$content);

    /**
     * Render YouTube short code(s)
     *
     * @param string $content text data containing [youtube param]
     * 
     * @return void
     */
    public static function renderYouTubeShortCode(&$content);

    /**
     * Render gallery short code(s)
     *
     * @param string $content text data containing [gallery param]
     * @param bool   $shuffle shuffle the gallery
     * @param int    $size    size of thumbnails in pixels
     * 
     * @return void
     */
    public static function renderGalleryShortCode(
        &$content, $shuffle = false, $size = 160
    );

    /**
     * Find uploaded images by a mask
     *
     * @param string $mask   file mask
     * @param string $format image format
     * 
     * @return mixed
     */
    public static function findImagesByMask($mask, $format = 'webp');

    /**
     * Find uploaded files by mask
     *
     * @param string $mask file mask
     * 
     * @return mixed
     */
    public static function findFilesByMask($mask = null);

    /**
     * Sanitize a string to a safe variant
     *
     * @param string $string string by reference
     * 
     * @return void
     */
    public static function sanitizeString(&$string);

    /**
     * Sanitize a string to a lowercase safe variant
     *
     * @param string $string string by reference
     * 
     * @return void
     */
    public static function sanitizeStringLC(&$string);

    /**
     * Transliterate a string to safe characters without accents
     *
     * @param string $string text data
     * 
     * @return string
     */
    public static function transliterate(&$string);
}

/**
 * String Filters class
 * 
 * Modify a string content passed by a reference to fix common problems.
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class StringFilters implements IStringFilters
{
    // maximum shortcode loop iterations
    const ITERATIONS = SF_ITERATIONS;

    // find images mask sanitization
    const UPLOAD_SANITIZE = '/[^a-z0-9!@#+\-=.,;_*]+/i';

    // general string sanitization
    const STRING_SANITIZE = '/[^a-z0-9\-._]+/i';

    // all possible English characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_english = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $up_chars_english = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    ];

    // all possible Czech characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_czech = [
        'a', 'á', 'b', 'c', 'č', 'd', 'ď', 'e', 'é', 'ě', 'f', 'g', 'h', 'i', 'í',
        'j', 'k', 'l', 'm', 'n', 'ň', 'o', 'ó', 'p', 'q', 'r', 'ř', 's', 'š', 't',
        'ť', 'u', 'ú', 'ů', 'v', 'w', 'x', 'y', 'ý', 'z', 'ž'
    ];
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $up_chars_czech = [
        'A', 'Á', 'B', 'C', 'Č', 'D', 'Ď', 'E', 'É', 'Ě', 'F', 'G', 'H', 'I', 'Í',
        'J', 'K', 'L', 'M', 'N', 'Ň', 'O', 'Ó', 'P', 'Q', 'R', 'Ř', 'S', 'Š', 'T',
        'Ť', 'U', 'Ú', 'Ů', 'V', 'W', 'X', 'Y', 'Ý', 'Z', 'Ž'
    ];

    // all possible Slovak characters
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $lo_chars_slovak = [
        'a', 'á', 'ä', 'b', 'c', 'č', 'd', 'ď', 'e', 'é', 'ě', 'f', 'g', 'h', 'i',
        'í', 'j', 'k', 'l', 'ľ', 'm', 'n', 'ň', 'o', 'ó', 'ô', 'p', 'q', 'r', 'ř',
        's', 'š', 't', 'ť', 'u', 'ú', 'ů', 'v', 'w', 'x', 'y', 'ý', 'z', 'ž',
    ];
    // phpcs:ignore
    /**
     * @var array<string>
     */
    public static $up_chars_slovak = [
        'A', 'Á', 'Ä', 'B', 'C', 'Č', 'D', 'Ď', 'E', 'É', 'Ě', 'F', 'G', 'H', 'I',
        'Í', 'J', 'K', 'L', 'Ľ', 'M', 'N', 'Ň', 'O', 'Ó', 'Ô', 'P', 'Q', 'R', 'Ř',
        'S', 'Š', 'T', 'Ť', 'U', 'Ú', 'Ů', 'V', 'W', 'X', 'Y', 'Ý', 'Z', 'Ž',
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $common = [
        "  " => " ",
        " ♥️ " => " ♥️&nbsp;",
        " ❤️ " => " ❤️&nbsp;",
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

        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",

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
        " id: " => " id:&nbsp;",
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
        " ID: " => " ID:&nbsp;",
        " INC." => "&nbsp;Inc.",
        " Inc." => "&nbsp;Inc.",
        " LTD." => "&nbsp;Ltd.",
        " Ltd." => "&nbsp;Ltd.",

        " % " => "&nbsp;% ",
        " ‰ " => "&nbsp;‰",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " / " => " /&nbsp;",
        " <<" => " «",
        ">> " => "» ",
        " - " => " — ",
        " – " => " —&nbsp;",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $english = [
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
    public static $czech = [
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
    public static $slovak = [
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
    public static $transliteration = [
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

    /**
     * Convert EOLs to HTML breakline
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolToBr(&$content)
    {
        $content = \str_replace(
            array(
            "\n",
            "\r\n",
            ), "<br>", (string) $content
        );
    }

    /**
     * Convert EOLs to breakline + non-breakable space (adjustable by CSS rules)
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolToBrNbsp(&$content)
    {
        $content = \str_replace(
            array(
            "\n",
            "\r\n",
            ), "<br><span class=indentation></span>", (string) $content
        );
    }

    /**
     * Convert EOL + hyphen/star to HTML
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolHyphenToBrDot(&$content)
    {
        $content = \str_replace(
            array(
            "\n- ",
            "\r\n- ",
            ), "<br>•&nbsp;", (string) $content
        );
        if ((\substr($content, 0, 2) == "- ") || (\substr($content, 0, 2) == "* ")) {
            $content = "•&nbsp;" . \substr($content, 2);
        }
    }

    /**
     * Trim various EOL combinations
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function trimEol(&$content)
    {
        $content = \str_replace(
            array(
            "\r\n",
            "\n",
            "\r",
            ), "", (string) $content
        );
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
        $body = "<body";
        $c = \explode($body, (string) $content, 2);
        $regex = '/<!--(.|\s)*?-->/';
        // fix the whole string (there is no <body)
        if (\count($c) == 1) {
            $content = \preg_replace($regex, "<!-- :) -->", $content);
        }
        // fix only comments inside body
        if (\count($c) == 2) {
            $c[1] = \preg_replace($regex, "<!-- :) -->", $c[1]);
            $content = $c[0] . $body . $c[1];
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
        if (!\is_string($language)) {
            $language = "en";
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
     * Correct text spacing - English
     *
     * @param string $content text data
     * 
     * @return string
     */
    public static function correctTextSpacingEn($content)
    {
        $merged = \array_merge(self::$common, self::$english);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Correct text spacing - Czech
     *
     * @param string $content text data
     * 
     * @return string
     */
    public static function correctTextSpacingCs($content)
    {
        $merged = \array_merge(self::$common, self::$czech);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Correct text spacing - Slovak
     *
     * @param string $content text data
     * 
     * @return string
     */
    public static function correctTextSpacingSk($content)
    {
        $merged = \array_merge(self::$common, self::$slovak);
        return \str_replace(\array_keys($merged), $merged, $content);
    }

    /**
     * Render Markdown to HTML
     *
     * @param string $content text data
     * 
     * @return void
     */
    public static function renderMarkdown(&$content)
    {
        $x = \trim($content);
        if (\str_starts_with($x, '[markdown]')) {
            $content = Markdown::defaultTransform(substr($x, 10));
        }
    }

    /**
     * Render Markdown Extra to HTML
     *
     * @param string $content text data
     * 
     * @return void
     */
    public static function renderMarkdownExtra(&$content)
    {
        $x = \trim($content);
        if (\str_starts_with($x, '[markdownextra]')) {
            $content = MarkdownExtra::defaultTransform(substr($x, 15));
        }
    }

    /**
     * Render Image short code(s)
     *
     * @param string $content text data containing [image param]
     * 
     * @return void
     */
    public static function renderImageShortCode(&$content)
    {
        $content = \trim($content);
        $counter = 0;
        $pattern = '#\[image\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[image ')
            ) {
            $counter++;
            $replace = '<span class="img-container">'
                . '<img '
                . 'class=imagesc '
                . 'src="' . CDN . '/upload/$1.webp" '
                . 'data-name="$1" '
                . 'data-counter=' . $counter . ' '
                . 'alt="$1"'
                . '>'
                . '</span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
        }
    }

    /**
     * Render Image Left short code(s)
     *
     * @param string $content text data containing [imageleft param]
     * 
     * @return void
     */
    public static function renderImageLeftShortCode(&$content)
    {
        $x = \trim($content);
        $counter = 0;
        $pattern = '#\[imageleft\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[imageleft ')
            ) {
            $counter++;
            $replace = '<span class="img-left-container">'
                . '<img '
                . 'class="left imageleftsc" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . 'data-name="$1" '
                . 'data-counter=' . $counter . ' '
                . 'alt="$1"'
                . '>'
                . '</span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
        }
    }

    /**
     * Render Image Right short code(s)
     *
     * @param string $content text data containing [imageright param]
     * 
     * @return void
     */
    public static function renderImageRightShortCode(&$content)
    {
        $content = \trim($content);
        $counter = 0;
        $pattern = '#\[imageright\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[imageright ')
            ) {
            $counter++;
            $replace = '<span class="img-right-container">'
                . '<img '
                . 'class="right imagerightsc" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . 'data-name="$1" '
                . 'data-counter=' . $counter . ' '
                . 'alt="$1"'
                . '>'
                . '</span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
        }
    }

    /**
     * Render Image Responsive short code(s)
     *
     * @param string $content text data containing [imageresp param]
     * 
     * @return void
     */
    public static function renderImageRespShortCode(&$content)
    {
        $content = \trim($content);
        $counter = 0;
        $pattern = '#\[imageresp\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[imageresp ')
            ) {
            $counter++;
            $replace = '<span class="img-responsive-container">'
                . '<img '
                . 'class="responsive-img imagerespsc" '
                . 'src="' . CDN . '/upload/$1.webp" '
                . 'data-name="$1" '
                . 'data-counter=' . $counter . ' '
                . 'alt="$1"'
                . '>'
                . '</span>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
        }
    }

    /**
     * Render Soundcloud short code(s)
     *
     * @param string $content text data containing [soundcloud param]
     * 
     * @return void
     */
    public static function renderSoundcloudShortCode(&$content)
    {
        $content = \trim($content);
        $counter = 0;
        $pattern = '#\[soundcloud\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[soundcloud ') && $counter < self::ITERATIONS
            ) {
            $counter++;
            $replace = '<div '
                . 'class="audio-container center row soundcloud-container" '
                . 'data-counter=' . $counter . '>'
                . '<iframe loading=lazy width="100%" height=300 '
                . 'scrolling=no frameborder=no controls '
                . 'src="https://w.soundcloud.com/player/?url='
                . 'https%3A//api.soundcloud.com/tracks/$1&'
                . 'auto_play=false&hide_related=false&show_comments=true&'
                . 'show_user=true&show_reposts=false&show_teaser=true&visual=true">'
                . '</iframe>'
                . '</div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
        }
    }

    /**
     * Render YouTube short code(s)
     *
     * @param string $content text data containing [youtube param]
     * 
     * @return void
     */
    public static function renderYouTubeShortCode(&$content)
    {
        $content = \trim($content);
        $counter = 0;
        $pattern = '#\[youtube\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[youtube ') && $counter < self::ITERATIONS
            ) {
            $counter++;
            $replace = '<div '
                . 'class="video-container center row youtube-container" '
                . 'data-counter=' . $counter . '>'
                . '<iframe loading=lazy width=426 height=240 controls '
                . 'src="https://www.youtube.com/embed/$1">'
                . '</iframe>'
                . '</div>';
            if (\is_string($content)) {
                $content = \preg_replace($pattern, $replace, $content);
            }
        }
    }

    /**
     * Render gallery short code(s)
     *
     * @param string $content text data containing [gallery param]
     * @param bool   $shuffle shuffle the gallery?
     * @param int    $size    selected width of thumbnails in pixels
     * 
     * @return void
     */
    public static function renderGalleryShortCode(
        &$content, $shuffle = false, $size = 160
    ) {
        $size = \intval($size);
        if (!$size) {
            $size = 160;
        }
        $content = \trim($content);
        $counter = 0;
        $pattern = '#\[gallery\s.*?(.*?)\]#is';
        while (
            \str_contains($content, '[gallery ') && $counter < self::ITERATIONS
            ) {
            \preg_match($pattern, $content, $m);
            if (\is_array($m) && isset($m[1])) {
                $gallery = $m[1];
                $counter++;
                $images = '';
                $files = self::findImagesByMask($gallery);

                // find all images
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
                        $images .=
                            "<a data-lightbox='{$gallery}' "
                            . "href='" . CDN . "/upload/{$f}'>"
                            . "<img loading=lazy class=gallery-img "
                            . "data-source='" . CDN . "/upload/{$f}' "
                            . "data-id={$id} "
                            . "data-thumb='{$t}' "
                            . "data-tooltip='{$n}' "
                            . "alt='$id. {$gallery}{$n}' src='{$t}'>"
                            . "</a>";
                    }
                }
                $replace = "<div "
                    . "class='row center gallery-container' "
                    . "data-gallery='$gallery'>$images"
                    . "</div>";
                if (\is_string($content)) {
                    $content = \str_replace(
                        "[gallery {$gallery}]",
                        $replace,
                        $content
                    );
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
        if (UPLOAD && \is_string($mask) && \is_dir(UPLOAD)) {
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
        if (!\is_string($mask)) {
            return null;
        }
        $mask = \preg_replace(self::UPLOAD_SANITIZE, '', \trim($mask));
        if ($mask) {
            $mask = \str_replace('..', '.', $mask);
        }
        if (UPLOAD && \is_string($mask) && \is_dir(UPLOAD)) {
            \chdir(UPLOAD);
            $data = \glob($mask) ?: null;
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
     * Sanitize a string to a safe variant
     *
     * @param string $string string by reference
     * 
     * @return void
     */
    public static function sanitizeString(&$string)
    {
        if ($string && \is_string($string)) {
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
        if ($string && \is_string($string)) {
            $string = \preg_replace(self::STRING_SANITIZE, '_', \trim($string));
            if ($string && \is_string($string)) {
                $string = \strtolower($string);
            }
        }
    }

    /**
     * Transliterate a string to safe characters without accents
     *
     * @param string $string text data
     * 
     * @return void
     */
    public static function transliterate(&$string)
    {
        $string = \str_replace(
            \array_keys(self::$transliteration),
            self::$transliteration,
            $string
        );
    }

    /**
     * Converts a string to lowercase
     *
     * @param string $string input string
     *
     * @return string lowercase version of the input string
     */
    public static function strtolower($string)
    {
        return \mb_strtolower($string, 'UTF-8');
    }

    /**
     * Converts a string to uppercase
     *
     * @param string $string input string
     *
     * @return string uppercase version of the input string
     */
    public static function strtoupper($string)
    {
        return \mb_strtoupper($string, 'UTF-8');
    }
}
