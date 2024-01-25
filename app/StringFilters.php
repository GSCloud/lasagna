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
     * Convert EOLs to <br>
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function convertEolToBr(&$content);

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
     * @param string $language (optional: "cs", "sk", "en" - for now)
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
     * Trim THML comments
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
        " - " => " – ",
        " — " => " —&nbsp;",
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
        " - " => " – ",
        " — " => " —&nbsp;",
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
        " - " => " – ",
        " — " => " —&nbsp;",
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
        if (!is_string($content)) {
            return;
        }
        $content = str_replace(
            array(
            "\n",
            "\r\n",
            ), "<br>", (string) $content
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
        if (!is_string($content)) {
            return;
        }
        $content = str_replace(
            array(
            "\n* ",
            "\n- ",
            "\r\n* ",
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
        if (!is_string($content)) {
            return;
        }
        $content = str_replace(
            array(
            "\r\n",
            "\n",
            "\r",
            ), "", (string) $content
        );
    }

    /**
     * Trim THML comments
     *
     * @param string $content content by reference
     * 
     * @return void
     */
    public static function trimHtmlComment(&$content)
    {
        if (!is_string($content)) {
            return;
        }
        $body = "<body";
        $c = explode($body, (string) $content, 2);
        $regex = '/<!--(.|\s)*?-->/';
        // fix only comments inside body
        if (count($c) == 2) {
            $c[1] = preg_replace($regex, "<!-- comment removed -->", $c[1]);
            $content = $c[0] . $body . $c[1];
        }
        // fix the whole string (there is no <body)
        if (count($c) == 1) {
            $content = preg_replace($regex, "<!-- comment removed -->", $content);
        }
    }

    /**
     * Correct text spacing
     * 
     * Correct the text spacing in passed content for various languages.
     *
     * @param string $content  content by reference
     * @param string $language (optional: "cs", "sk", "en" - for now)
     * 
     * @return null
     */
    public static function correctTextSpacing(&$content, $language = "en")
    {
        if (!is_string($content)) {
            return null;
        }
        $language = strtolower(trim((string) $language));
        switch ($language) {
        case "sk":
            $content = self::correctTextSpacingSk($content);
            break;
        case "cs":
            $content = self::correctTextSpacingCs($content);
            break;
        default:
            $content = self::correctTextSpacingEn($content);
        }
        return null;
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
        if (!is_string($content)) {
            return '';
        }
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
        if (!is_string($content)) {
            return '';
        }
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
        if (!is_string($content)) {
            return '';
        }
        return str_replace(
            array_keys(self::$slovak),
            self::$slovak,
            $content
        );
    }
}
