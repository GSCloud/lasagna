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
     * Render YouTube short code
     *
     * @param string $content text data containing [youtube param]
     * 
     * @return void
     */
    public static function renderYouTubeShortCode(&$content);

    /**
     * Render gallery short code
     *
     * @param string $content text data containing [gallery param]
     * 
     * @return void
     */
    public static function renderGalleryShortCode(&$content);

    /**
     * Find uploaded images by a mask
     *
     * @param string $mask file mask
     * 
     * @return mixed
     */
    public static function findImagesByMask($mask);

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
    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $slovak = [
        "  " => " ",
        " 1 " => " 1&nbsp;",
        " 2 " => " 2&nbsp;",
        " 3 " => " 3&nbsp;",
        " 4 " => " 4&nbsp;",
        " 5 " => " 5&nbsp;",
        " 6 " => " 6&nbsp;",
        " 7 " => " 7&nbsp;",
        " 8 " => " 8&nbsp;",
        " 9 " => " 9&nbsp;",
        " 0 " => " 0&nbsp;",
        " - " => " – ",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
        " % " => "&nbsp;%",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",
        " / " => " /&nbsp;",
        " – " => " –&nbsp;",
        " CZK" => "&nbsp;CZK",
        " Czk" => "&nbsp;CZK",
        " DIČ: " => " DIČ:&nbsp;",
        " EUR" => "&nbsp;EUR",
        " Eur " => "&nbsp;EUR ",
        " ID: " => " ID:&nbsp;",
        " Inc." => "&nbsp;Inc.",
        " IČ: " => " IČ:&nbsp;",
        " Kč" => "&nbsp;Kč",
        " Ltd." => "&nbsp;Ltd.",
        " USD" => "&nbsp;USD",
        " Usd" => "&nbsp;USD",
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
        " l " => "&nbsp;l ",
        " l, " => "&nbsp;l, ",
        " l. " => "&nbsp;l. ",
        " m " => "&nbsp;m ",
        " m, " => "&nbsp;m, ",
        " m. " => "&nbsp;m. ",
        " m2 " => "&nbsp;m² ",
        " m3 " => "&nbsp;m³ ",
        " mj. " => " mj.&nbsp;",
        " m² " => "&nbsp;m² ",
        " m³ " => "&nbsp;m³ ",
        " o " => " o&nbsp;",
        " s " => " s&nbsp;",
        " s, " => "&nbsp;s, ",
        " s. " => "&nbsp;s. ",
        " s.r.o." => "&nbsp;s.r.o.",
        " sec. " => "&nbsp;sec. ",
        " sl. " => " sl.&nbsp;",
        " spol. " => "&nbsp;spol.&nbsp;",
        " str. " => " str.&nbsp;",
        " sv. " => " sv.&nbsp;",
        " tj. " => "tj.&nbsp;",
        " tzn. " => " tzn.&nbsp;",
        " tzv. " => " tzv.&nbsp;",
        " u " => " u&nbsp;",
        " v " => " v&nbsp;",
        " viz " => " viz&nbsp;",
        " z " => " z&nbsp;",
        " z.s." => "&nbsp;z.s.",
        " z. s." => "&nbsp;z.&nbsp;s.",
        " zvl. " => " zvl.&nbsp;",
        " °C " => "&nbsp;°C ",
        " °F " => "&nbsp;°F ",
        " č. " => " č.&nbsp;",
        " č. j. " => " č.&nbsp;j.&nbsp;",
        " čj. " => " čj.&nbsp;",
        " čp. " => " čp.&nbsp;",
        " čís. " => " čís.&nbsp;",
        " ‰ " => "&nbsp;‰",
        "<<" => "«",
        ">>" => "»",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $czech = [
        "  " => " ",
        " 1 " => " 1&nbsp;",
        " 2 " => " 2&nbsp;",
        " 3 " => " 3&nbsp;",
        " 4 " => " 4&nbsp;",
        " 5 " => " 5&nbsp;",
        " 6 " => " 6&nbsp;",
        " 7 " => " 7&nbsp;",
        " 8 " => " 8&nbsp;",
        " 9 " => " 9&nbsp;",
        " 0 " => " 0&nbsp;",
        " - " => " – ",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
        " % " => "&nbsp;%",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",
        " / " => " /&nbsp;",
        " – " => " –&nbsp;",
        " CZK" => "&nbsp;CZK",
        " Czk" => "&nbsp;CZK",
        " DIČ: " => " DIČ:&nbsp;",
        " EUR" => "&nbsp;EUR",
        " Eur " => "&nbsp;EUR ",
        " ID: " => " ID:&nbsp;",
        " Inc." => "&nbsp;Inc.",
        " IČ: " => " IČ:&nbsp;",
        " Kč" => "&nbsp;Kč",
        " Ltd." => "&nbsp;Ltd.",
        " USD" => "&nbsp;USD",
        " Usd" => "&nbsp;USD",
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
        " l, " => "&nbsp;l, ",
        " l. " => "&nbsp;l. ",
        " m " => "&nbsp;m ",
        " m, " => "&nbsp;m, ",
        " m. " => "&nbsp;m. ",
        " m2 " => "&nbsp;m² ",
        " m3 " => "&nbsp;m³ ",
        " mj. " => " mj.&nbsp;",
        " m² " => "&nbsp;m² ",
        " m³ " => "&nbsp;m³ ",
        " např. " => " např.&nbsp;",
        " o " => " o&nbsp;",
        " popř. " => " popř.&nbsp;",
        " př. " => " př.&nbsp;",
        " přib. " => " přib.&nbsp;",
        " přibl. " => " přibl.&nbsp;",
        " s " => " s&nbsp;",
        " s, " => "&nbsp;s, ",
        " s. " => "&nbsp;s. ",
        " s.r.o." => "&nbsp;s.r.o.",
        " sec. " => "&nbsp;sec. ",
        " sl. " => " sl.&nbsp;",
        " spol. " => "&nbsp;spol.&nbsp;",
        " str. " => " str.&nbsp;",
        " sv. " => " sv.&nbsp;",
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
        " ‰ " => "&nbsp;‰",
        "<<" => "«",
        ">>" => "»",
    ];

    // phpcs:ignore
    /**
     * @var array<string,string>
     */
    public static $english = [
        "  " => " ",
        " 1 " => " 1&nbsp;",
        " 2 " => " 2&nbsp;",
        " 3 " => " 3&nbsp;",
        " 4 " => " 4&nbsp;",
        " 5 " => " 5&nbsp;",
        " 6 " => " 6&nbsp;",
        " 7 " => " 7&nbsp;",
        " 8 " => " 8&nbsp;",
        " 9 " => " 9&nbsp;",
        " 0 " => " 0&nbsp;",
        " - " => " – ",
        " — " => " —&nbsp;",
        " ― " => " ―&nbsp;",
        " % " => "&nbsp;%",
        " ... " => "&nbsp;… ",
        " ..." => "&nbsp;…",
        " :-(" => "&nbsp;😟",
        " :-)" => "&nbsp;🙂",
        " :-O" => "&nbsp;😮",
        " :-P" => "&nbsp;😋",
        " :-[" => "&nbsp;😕",
        " :-|" => "&nbsp;😐",
        " / " => " /&nbsp;",
        " – " => " –&nbsp;",
        " A " => " A&nbsp;",
        " AM" => "&nbsp;AM",
        " CZK " => " CZK&nbsp;",
        " Czk " => " CZK&nbsp;",
        " EUR " => " EUR&nbsp;",
        " Eur " => " EUR&nbsp;",
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
        " Usd " => " USD&nbsp;",
        " a " => " a&nbsp;",
        " h " => "&nbsp;h ",
        " h, " => "&nbsp;h, ",
        " h. " => "&nbsp;h. ",
        " id: " => " id:&nbsp;",
        " kg " => "&nbsp;kg ",
        " l " => "&nbsp;l ",
        " l, " => "&nbsp;l, ",
        " l. " => "&nbsp;l. ",
        " m " => "&nbsp;m ",
        " m, " => "&nbsp;m, ",
        " m. " => "&nbsp;m. ",
        " m2 " => "&nbsp;m² ",
        " m3 " => "&nbsp;m³ ",
        " m² " => "&nbsp;m² ",
        " m³ " => "&nbsp;m³ ",
        " pcs " => "&nbsp;pcs ",
        " pcs)" => "&nbsp;pcs)",
        " s " => "&nbsp;s ",
        " s, " => "&nbsp;s, ",
        " s. " => "&nbsp;s. ",
        " sec. " => "&nbsp;sec. ",
        " °C " => "&nbsp;°C ",
        " °F " => "&nbsp;°F ",
        " ‰ " => "&nbsp;‰",
        "<<" => "«",
        ">>" => "»",
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
        $content = str_replace(
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
        $content = str_replace(
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
        $content = str_replace(
            array(
            "\n- ",
            "\r\n- ",
            ), "<br>•&nbsp;", (string) $content
        );
        // fix for the beginning of the string
        if ((substr($content, 0, 2) == "- ") || (substr($content, 0, 2) == "* ")) {
            $content = "•&nbsp;" . substr($content, 2);
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
        $content = str_replace(
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
        $c = explode($body, (string) $content, 2);
        $regex = '/<!--(.|\s)*?-->/';
        // fix the whole string (there is no <body)
        if (count($c) == 1) {
            $content = preg_replace($regex, "<!-- :) -->", $content);
        }
        // fix only comments inside body
        if (count($c) == 2) {
            $c[1] = preg_replace($regex, "<!-- :) -->", $c[1]);
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
        $language = strtolower(trim((string) $language));
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
        return str_replace(
            array_keys(self::$english),
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
        return str_replace(
            array_keys(self::$czech),
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
        return str_replace(
            array_keys(self::$slovak),
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
        if (\str_starts_with($x, '[markdown]')) {
            $content = MarkdownExtra::defaultTransform(substr($x, 10));
        }
    }

    /**
     * Render YouTube short code
     *
     * @param string $content text data containing [youtube param]
     * 
     * @return void
     */
    public static function renderYouTubeShortCode(&$content)
    {
        $x = \trim($content);
        if (\str_contains($x, '[youtube ')) {
            $pattern = '#\[youtube\s.*?(.*?)\]#is';
            $replace = '<div class="video-container center row">'
                . '<iframe width="420" height="315" controls '
                . 'src="https://www.youtube.com/embed/$1"'
                . '></iframe></div>';
            $content = \preg_replace($pattern, $replace, $x);
        }
    }

    /**
     * Render gallery short code
     *
     * @param string $content text data containing [gallery param]
     * 
     * @return void
     */
    public static function renderGalleryShortCode(&$content)
    {
        $x = \trim($content);
        if (\str_contains($x, '[gallery ')) {
            $pattern = '#\[gallery\s.*?(.*?)\]#is';
            \preg_match($pattern, $x, $m);
            if (\is_array($m) && isset($m[1])) {
                $images = '';
                $files = self::findImagesByMask($m[1]);
                if (\is_array($files)) {
                    \shuffle($files);
                    foreach ($files as $f) {
                        $images .= '<div><img class="responsive-img" '
                            . "src='/upload/$f'></div>";
                    }
                }
                $replace = '<div class="center row">' . $images . '</div>';
                $content = \preg_replace($pattern, $replace, $x);
            }
        }
    }

    /**
     * Find uploaded images by a mask
     *
     * @param string $mask file mask
     * 
     * @return mixed
     */
    public static function findImagesByMask($mask)
    {
        $mask = \trim($mask);
        $mask = \trim($mask, './\\');
        if (\is_string($mask)) {
            \chdir(UPLOAD);
            $path = $mask . '*.webp';
            return \glob($path);
        }
        return null;
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
            $string = \preg_replace("/[^a-zA-Z0-9]+/i", '', \trim($string));
            if ($string && \is_string($string)) {
                $string = \strtolower($string);
            }
        }
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
            $string = \preg_replace("/[^a-zA-Z0-9]+/i", '', \trim($string));
        }
    }
}
