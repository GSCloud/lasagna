<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
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
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
interface IStringFilters
{
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
     * Transliterate a string to safe characters without accents
     *
     * @param string $string text data
     * 
     * @return string
     */
    public static function transliterate(&$string);

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
}

/**
 * String Filters class
 * 
 * Modify a string content passed by a reference to fix common problems.
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class StringFilters implements IStringFilters
{
    // max. shortcode iterations in the loop
    const ITERATIONS = 10;

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $slovak = [
        "  " => " ",

        " % " => "&nbsp;% ",
        " - " => " — ",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " / " => " /&nbsp;",
        " <<" => " «",
        " – " => " —&nbsp;",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
        " ‰ " => "&nbsp;‰",
        ">> " => "» ",

        " 0 " => " 0&nbsp;",
        " 1 " => " 1&nbsp;",
        " 2 " => " 2&nbsp;",
        " 3 " => " 3&nbsp;",
        " 4 " => " 4&nbsp;",
        " 5 " => " 5&nbsp;",
        " 6 " => " 6&nbsp;",
        " 7 " => " 7&nbsp;",
        " 8 " => " 8&nbsp;",
        " 9 " => " 9&nbsp;",

        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",

        " A " => " A&nbsp;",
        " CZK" => "&nbsp;CZK",
        " DIČ: " => " DIČ:&nbsp;",
        " EUR" => "&nbsp;EUR",
        " I " => " I&nbsp;",
        " ID: " => " ID:&nbsp;",
        " Inc." => "&nbsp;Inc.",
        " IČ: " => " IČ:&nbsp;",
        " K " => " K&nbsp;",
        " Kč" => "&nbsp;Kč",
        " Ltd." => "&nbsp;Ltd.",
        " S " => " S&nbsp;",
        " U " => " U&nbsp;",
        " USD" => "&nbsp;USD",
        " V " => " V&nbsp;",
        " Z " => " Z&nbsp;",
        " a " => " a&nbsp;",
        " a. s. " => "&nbsp;a.&nbsp;s. ",
        " a.s. " => "&nbsp;a.s. ",
        " cca. " => " cca.&nbsp;",
        " h " => "&nbsp;h ",
        " h) " => "&nbsp;h) ",
        " h, " => "&nbsp;h, ",
        " h. " => "&nbsp;h. ",
        " hod. " => "&nbsp;hod. ",
        " hod.)" => "&nbsp;hod.)",
        " i " => " i&nbsp;",
        " id: " => " id:&nbsp;",
        " k " => " k&nbsp;",
        " kg " => "&nbsp;kg ",
        " kg)" => "&nbsp;kg)",
        " ks " => "&nbsp;ks ",
        " ks)" => "&nbsp;ks)",
        " ks, " => "&nbsp;ks, ",
        " ks." => "&nbsp;ks.",
        " l " => "&nbsp;l ",
        " m " => "&nbsp;m ",
        " m) " => "&nbsp;m) ",
        " m, " => "&nbsp;m, ",
        " m. " => "&nbsp;m. ",
        " m2 " => "&nbsp;m² ",
        " m3 " => "&nbsp;m³ ",
        " m² " => "&nbsp;m² ",
        " m³ " => "&nbsp;m³ ",
        " o " => " o&nbsp;",
        " s " => " s&nbsp;",
        " s. " => "&nbsp;s. ",
        " s.r.o." => "&nbsp;s.r.o.",
        " sec. " => "&nbsp;sec. ",
        " spol. " => "&nbsp;spol.&nbsp;",
        " tj. " => "tj.&nbsp;",
        " tzn. " => " tzn.&nbsp;",
        " tzv. " => " tzv.&nbsp;",
        " u " => " u&nbsp;",
        " v " => " v&nbsp;",
        " viz " => " viz&nbsp;",
        " z " => " z&nbsp;",
        " z. s." => "&nbsp;z.&nbsp;s.",
        " z.s." => "&nbsp;z.s.",
        " zvl. " => " zvl.&nbsp;",
        " °C " => "&nbsp;°C ",
        " °F " => "&nbsp;°F ",
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
        " i&nbsp;k " => " i&nbsp;k&nbsp;",
        " i&nbsp;o " => " i&nbsp;o&nbsp;",
        " i&nbsp;s " => " i&nbsp;s&nbsp;",
        " i&nbsp;u " => " i&nbsp;u&nbsp;",
        " i&nbsp;v " => " i&nbsp;v&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $czech = [
        "  " => " ",

        " % " => "&nbsp;% ",
        " - " => " — ",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " / " => " /&nbsp;",
        " <<" => " «",
        " – " => " —&nbsp;",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
        " ‰ " => "&nbsp;‰",
        ">> " => "» ",

        " 0 " => " 0&nbsp;",
        " 1 " => " 1&nbsp;",
        " 2 " => " 2&nbsp;",
        " 3 " => " 3&nbsp;",
        " 4 " => " 4&nbsp;",
        " 5 " => " 5&nbsp;",
        " 6 " => " 6&nbsp;",
        " 7 " => " 7&nbsp;",
        " 8 " => " 8&nbsp;",
        " 9 " => " 9&nbsp;",

        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",

        " A " => " A&nbsp;",
        " CZK" => "&nbsp;CZK",
        " DIČ: " => " DIČ:&nbsp;",
        " EUR" => "&nbsp;EUR",
        " I " => " I&nbsp;",
        " ID: " => " ID:&nbsp;",
        " Inc." => "&nbsp;Inc.",
        " IČ: " => " IČ:&nbsp;",
        " K " => " K&nbsp;",
        " Kč" => "&nbsp;Kč",
        " Ltd." => "&nbsp;Ltd.",
        " S " => " S&nbsp;",
        " U " => " U&nbsp;",
        " USD" => "&nbsp;USD",
        " V " => " V&nbsp;",
        " Z " => " Z&nbsp;",
        " a " => " a&nbsp;",
        " a. s. " => "&nbsp;a.&nbsp;s. ",
        " a.s. " => "&nbsp;a.s. ",
        " cca. " => " cca.&nbsp;",
        " h " => "&nbsp;h ",
        " h, " => "&nbsp;h, ",
        " h. " => "&nbsp;h. ",
        " hod. " => "&nbsp;hod. ",
        " hod.)" => "&nbsp;hod.)",
        " i " => " i&nbsp;",
        " id: " => " id:&nbsp;",
        " k " => " k&nbsp;",
        " kg " => "&nbsp;kg ",
        " kg)" => "&nbsp;kg)",
        " ks " => "&nbsp;ks ",
        " ks)" => "&nbsp;ks)",
        " ks, " => "&nbsp;ks, ",
        " ks." => "&nbsp;ks.",
        " kupř. " => " kupř.&nbsp;",
        " l " => "&nbsp;l ",
        " m " => "&nbsp;m ",
        " m, " => "&nbsp;m, ",
        " m. " => "&nbsp;m. ",
        " m2 " => "&nbsp;m² ",
        " m3 " => "&nbsp;m³ ",
        " m² " => "&nbsp;m² ",
        " m³ " => "&nbsp;m³ ",
        " např. " => " např.&nbsp;",
        " o " => " o&nbsp;",
        " popř. " => " popř.&nbsp;",
        " př. " => " př.&nbsp;",
        " přib. " => " přib.&nbsp;",
        " přibl. " => " přibl.&nbsp;",
        " s " => " s&nbsp;",
        " s. " => "&nbsp;s. ",
        " s.r.o." => "&nbsp;s.r.o.",
        " sec. " => "&nbsp;sec. ",
        " spol. " => "&nbsp;spol.&nbsp;",
        " tj. " => "tj.&nbsp;",
        " tzn. " => " tzn.&nbsp;",
        " tzv. " => " tzv.&nbsp;",
        " tř. " => "tř.&nbsp;",
        " u " => " u&nbsp;",
        " v " => " v&nbsp;",
        " viz " => " viz&nbsp;",
        " z " => " z&nbsp;",
        " z. s." => "&nbsp;z.&nbsp;s.",
        " z.s." => "&nbsp;z.s.",
        " zvl. " => " zvl.&nbsp;",
        " °C " => "&nbsp;°C ",
        " °F " => "&nbsp;°F ",
        " č. " => " č.&nbsp;",
        " č. j. " => " č.&nbsp;j.&nbsp;",
        " čj. " => " čj.&nbsp;",
        " čp. " => " čp.&nbsp;",
        " čís. " => " čís.&nbsp;",

        " a&nbsp;i " => " a&nbsp;i&nbsp;",
        " a&nbsp;v " => " a&nbsp;v&nbsp;",
        " i&nbsp;s " => " i&nbsp;s&nbsp;",
        " i&nbsp;v " => " i&nbsp;v&nbsp;",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $english = [
        "  " => " ",

        " % " => "&nbsp;% ",
        " - " => " — ",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " / " => " /&nbsp;",
        " <<" => " «",
        " – " => " —&nbsp;",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
        " ‰ " => "&nbsp;‰",
        ">> " => "» ",

        " He's " => " He&rsquo;s ",
        " It's " => " It&rsquo;s ",
        " She's " => " She&rsquo;s ",
        " he's " => " he&rsquo;s ",
        " it's " => " it&rsquo;s ",
        " she's " => " she&rsquo;s ",

        " 0 " => " 0&nbsp;",
        " 1 " => " 1&nbsp;",
        " 2 " => " 2&nbsp;",
        " 3 " => " 3&nbsp;",
        " 4 " => " 4&nbsp;",
        " 5 " => " 5&nbsp;",
        " 6 " => " 6&nbsp;",
        " 7 " => " 7&nbsp;",
        " 8 " => " 8&nbsp;",
        " 9 " => " 9&nbsp;",

        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",

        " A " => " A&nbsp;",
        " AM" => "&nbsp;AM",
        " CZK " => " CZK&nbsp;",
        " EUR " => " EUR&nbsp;",
        " I " => " I&nbsp;",
        " ID: " => " ID:&nbsp;",
        " INC." => "&nbsp;Inc.",
        " Inc." => "&nbsp;Inc.",
        " LTD." => "&nbsp;Ltd.",
        " Ltd." => "&nbsp;Ltd.",
        " Miss " => " Miss&nbsp;",
        " Mr " => " Mr&nbsp;",
        " Mr. " => " Mr.&nbsp;",
        " Mrs " => " Mrs&nbsp;",
        " Mrs. " => " Mrs.&nbsp;",
        " Ms " => " Ms&nbsp;",
        " Ms. " => " Ms.&nbsp;",
        " PM" => "&nbsp;PM",
        " USD " => " USD&nbsp;",
        " a " => " a&nbsp;",
        " h " => "&nbsp;h ",
        " h, " => "&nbsp;h, ",
        " h. " => "&nbsp;h. ",
        " id: " => " id:&nbsp;",
        " kg " => "&nbsp;kg ",
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
        " pcs " => "&nbsp;pcs ",
        " pcs)" => "&nbsp;pcs)",
        " s " => "&nbsp;s ",
        " s) " => "&nbsp;s) ",
        " s, " => "&nbsp;s, ",
        " s. " => "&nbsp;s. ",
        " sec. " => "&nbsp;sec. ",
        " °C " => "&nbsp;°C ",
        " °F " => "&nbsp;°F ",
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
        'à' => 'a',
        'á' => 'a',
        'ä' => 'a',
        'č' => 'c',
        'ć' => 'c',
        'č' => 'c',
        'ď' => 'd',
        'é' => 'e',
        'ě' => 'e',
        'è' => 'e',
        'é' => 'e',
        'ë' => 'e',
        'ě' => 'e',
        'í' => 'i',
        'í' => 'i',
        'ĺ' => 'l',
        'ľ' => 'l',
        'ň' => 'n',
        'ń' => 'n',
        'ó' => 'o',
        'ö' => 'o',
        'ø' => 'o',
        'ř' => 'r',
        'ŕ' => 'r',
        'ř' => 'r',
        'š' => 's',
        'š' => 's',
        'ť' => 't',
        'ú' => 'u',
        'ú' => 'u',
        'ü' => 'u',
        'ů' => 'u',
        'ý' => 'y',
        'ý' => 'y',
        'ž' => 'z',
        'ž' => 'z',

        'Á' => 'a',
        'À' => 'a',
        'Á' => 'a',
        'Ä' => 'a',
        'Č' => 'c',
        'Ć' => 'c',
        'Č' => 'c',
        'Ď' => 'd',
        'É' => 'e',
        'Ě' => 'e',
        'È' => 'e',
        'É' => 'e',
        'Ë' => 'e',
        'Ě' => 'e',
        'Í' => 'i',
        'Í' => 'i',
        'Ĺ' => 'l',
        'Ľ' => 'l',
        'Ň' => 'n',
        'Ń' => 'n',
        'Ó' => 'o',
        'Ö' => 'o',
        'Ø' => 'o',
        'Ř' => 'r',
        'Ŕ' => 'r',
        'Ř' => 'r',
        'Š' => 's',
        'Š' => 's',
        'Ť' => 't',
        'Ú' => 'u',
        'Ú' => 'u',
        'Ü' => 'u',
        'Ů' => 'u',
        'Ý' => 'y',
        'Ý' => 'y',
        'Ž' => 'z',
        'Ž' => 'z',
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
        $language = \strtolower(\trim((string) $language));
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
        return \str_replace(
            \array_keys(self::$english),
            self::$english, $content
        );
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
        return \str_replace(
            \array_keys(self::$czech),
            self::$czech,
            $content
        );
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
        return \str_replace(
            \array_keys(self::$slovak),
            self::$slovak,
            $content
        );
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
        $x = \trim($content);
        $counter = 0;
        while (\str_contains($x, '[image ')) {
            if (!\is_string($x)) {
                break;
            }
            $counter++;
            $pattern = '#\[image\s.*?(.*?)\]#is';
            $replace = '<span class="img-container">'
                . '<img data-name="$1" '
                . 'data-counter=' . $counter
                . ' src="'
                . CDN
                . '/upload/'
                . '$1.webp" '
                . 'alt="$1"></span>';
            if (\is_string($x)) {
                $x = $content = \preg_replace($pattern, $replace, $x);
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
        while (\str_contains($x, '[imageleft ')) {
            if (!\is_string($x)) {
                break;
            }
            $counter++;
            $pattern = '#\[imageleft\s.*?(.*?)\]#is';
            $replace = '<span class="img-left-container">'
                . '<img data-name="$1" '
                . 'data-counter=' . $counter
                . ' src="'
                . CDN
                . '/upload/'
                . '$1.webp" '
                . 'alt="$1"></span>';
            if (\is_string($x)) {
                $x = $content = \preg_replace($pattern, $replace, $x);
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
        $x = \trim($content);
        $counter = 0;
        while (\str_contains($x, '[imageright ')) {
            if (!\is_string($x)) {
                break;
            }
            $counter++;
            $pattern = '#\[imageright\s.*?(.*?)\]#is';
            $replace = '<span class="img-right-container">'
                . '<img data-name="$1" '
                . 'data-counter=' . $counter
                . ' src="'
                . CDN
                . '/upload/'
                . '$1.webp" '
                . 'alt="$1"></span>';
            if (\is_string($x)) {
                $x = $content = \preg_replace($pattern, $replace, $x);
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
        $x = \trim($content);
        $counter = 0;
        while (\str_contains($x, '[imageresp ')) {
            if (!\is_string($x)) {
                break;
            }
            $counter++;
            $pattern = '#\[imageresp\s.*?(.*?)\]#is';
            $replace = '<span class="img-responsive-container">'
                . '<img data-name="$1" data-counter=' . $counter
                . ' class="responsive-img" src="'
                . CDN
                . '/upload/'
                . '$1.webp" '
                . 'alt="$1"></span>';
            if (\is_string($x)) {
                $x = $content = \preg_replace($pattern, $replace, $x);
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
        $counter = 0;
        $x = \trim($content);
        while (\str_contains($x, '[soundcloud ') && $counter < self::ITERATIONS) {
            if (!\is_string($x)) {
                break;
            }
            $counter++;
            $pattern = '#\[soundcloud\s.*?(.*?)\]#is';
            $replace = '<div '
                . 'class="audio-container center row soundcloud-container" '
                . 'data-counter='
                . $counter
                . '><iframe loading="lazy" width="100%" height="300" '
                . 'scrolling="no" frameborder="no" controls '
                . 'src="https://w.soundcloud.com/player/'
                . '?url=https%3A//api.soundcloud.com/tracks/'
                . '$1&auto_play=false&hide_related=false&show_comments=true'
                . '&show_user=true&show_reposts=false&show_teaser=true&visual=true">'
                . '</iframe></div>';
            if (\is_string($x)) {
                $x = $content = \preg_replace($pattern, $replace, $x);
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
        $counter = 0;
        $x = \trim($content);
        while (\str_contains($x, '[youtube ') && $counter < self::ITERATIONS) {
            if (!\is_string($x)) {
                break;
            }
            $counter++;
            $pattern = '#\[youtube\s.*?(.*?)\]#is';
            $replace = '<div class="video-container center row youtube-container" '
                . 'data-counter='
                . $counter
                . '><iframe loading="lazy" width=426 height=240 controls '
                . 'src="https://www.youtube.com/embed/$1"></iframe></div>';
            if (\is_string($x)) {
                $x = $content = \preg_replace($pattern, $replace, $x);
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
        $counter = 0;
        $size = \intval($size);
        if (!$size) {
            $size = 160;
        }
        $x = \trim($content);
        while (\str_contains($x, '[gallery ') && $counter < self::ITERATIONS) {
            if (!\is_string($x)) {
                break;
            }
            $pattern = '#\[gallery\s.*?(.*?)\]#is';
            $counter++;
            \preg_match($pattern, $x, $m);
            if (\is_array($m) && isset($m[1])) {
                $gallery = $m[1];
                $images = '';
                $files = self::findImagesByMask($gallery);
                if (\is_array($files)) {
                    if ($shuffle !== false) {
                        \shuffle($files);
                    }
                    $counter = 0;
                    foreach ($files as $f) {
                        $counter++;
                        $n = \pathinfo(
                            \strtoupper(
                                \str_ireplace($gallery, '', $f)
                            ), PATHINFO_FILENAME
                        );
                        $n = \trim(\strtr($n, '-_()', '    '));
                        $t = CDN . "/upload/.thumb_{$size}px_" . $f;
                        $images .=
                            "<a data-lightbox='$gallery' "
                            . "href=" . CDN . '/upload/' . $f
                            . "><img loading=lazy class=gallery-img "
                            . " data-source=" . CDN . '/upload/' . $f
                            . " data-thumb=" . $t
                            . " alt='$n' src=$t></a>";
                    }
                }
                $replace = "<div class='row center gallery-container'"
                    . " data-gallery='$gallery'>$images</div>";
                if (\is_string($x)) {
                    $x = $content = \preg_replace($pattern, $replace, $x);
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
    public static function findImagesByMask($mask, $format = 'webp')
    {
        $mask = \trim($mask);
        $mask = \trim($mask, './\\');
        $mask = \strtolower($mask);
        $format = \trim($format);
        $format = \trim($format, './\\');
        $format = \strtolower($format);
        if (!\is_string($format) || \strlen($format)) {
            $format = 'webp';
        }
        if (\is_string($mask)) {
            \chdir(UPLOAD);
            $path = $mask . '*.' . $format;
            return \glob($path);
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
            $string = \preg_replace("/[^a-zA-Z0-9\-\.]+/i", '_', \trim($string));
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
            $string = \preg_replace("/[^a-zA-Z0-9\-\.]+/i", '_', \trim($string));
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
            self::$transliteration, $string
        );
    }
}
