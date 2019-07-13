<?php

namespace GSC;

interface IStringFilters
{
    public static function convert_eol_to_br($content);
    public static function convert_eolhyphen_to_brdot($content);
    public static function correct_text_spacing($content, $language);
    public static function trim_eol($content);
    public static function trim_html_comment($content);
}

class StringFilters implements IStringFilters
{
    public static function convert_eol_to_br($content)
    {
        $content = str_replace(array(
            "\n",
            "\r\n",
        ), "<br>", $content);

        return (string) $content;
    }

    public static function convert_eolhyphen_to_brdot($content)
    {
        $content = str_replace(array(
            "<br>- ",
            "\n- ",
            "<br>* ",
            "\n* ",
        ), "<br>•&nbsp;", $content);

        return (string) $content;
    }

    public static function trim_eol($content)
    {
        $content = str_replace(array(
            "\r\n",
            "\n",
            "\r",
        ), "", $content);

        return (string) $content;
    }

    public static function trim_html_comment($content)
    {
        $body = "<body";
        $c = explode($body, $content, 2);
        $regex = '/<!--(.|\s)*?-->/';
        $c[1] = preg_replace($regex, "<!-- comment -->", $c[1]);
        $content = $c[0] . $body . $c[1];

        return (string) $content;
    }

    public static function correct_text_spacing($content, $language = "cs")
    {
        $language = strtolower((string) $language);
        switch ($language) {
            case "en":
                $content = self::correct_text_spacing_en($content);
                return (string) $content;
                break;

            default:
                $content = self::correct_text_spacing_cs($content);
                return (string) $content;
                break;
        }
    }

    private static function correct_text_spacing_en($content)
    {
        $replace = array(
            "  " => " ",
            ">>" => "»",
            "<<" => "«",
            " % " => "&nbsp;% ",
            " - " => " – ",
            " ... " => "&nbsp;… ",
            " ..." => "&nbsp;…",
            " :-(" => "&nbsp;😟",
            " :-)" => "&nbsp;🙂",
            " :-O" => "&nbsp;😮",
            " :-|" => "&nbsp;😐",
            " A " => " A&nbsp;",
            " AM" => "&nbsp;AM",
            " CZK " => " CZK&nbsp;",
            " Czk " => " CZK&nbsp;",
            " EUR " => " EUR&nbsp;",
            " Eur " => " EUR&nbsp;",
            " I " => " I&nbsp;",
            " ID: " => " ID:&nbsp;",
            " Inc." => "&nbsp;Inc.",
            " Ltd." => "&nbsp;Ltd.",
            " Mr " => " Mr&nbsp;",
            " Mr. " => " Mr.&nbsp;",
            " Ms " => " Ms&nbsp;",
            " Miss " => " Miss&nbsp;",
            " Ms. " => " Ms.&nbsp;",
            " PM" => "&nbsp;PM",
            " USD " => " USD&nbsp;",
            " Usd " => " USD&nbsp;",
            " a " => " a&nbsp;",
            " h " => "&nbsp;h ",
            " h" => "&nbsp;h",
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
            " pcs" => "&nbsp;pcs",
            " pcs)" => "&nbsp;pcs)",
            " s " => "&nbsp;s ",
            " s, " => "&nbsp;s, ",
            " s. " => "&nbsp;s. ",
            " sec. " => "&nbsp;sec. ",
            " z. s." => "&nbsp;z.&nbsp;s.",
            " °C " => "&nbsp;°C ",
            " °F " => "&nbsp;°F ",
            " ‰ " => "&nbsp;‰",
        );
        $content = str_replace(array_keys($replace), $replace, $content);

        return (string) $content;
    }

    private static function correct_text_spacing_cs($content)
    {
        $replace = array(
            "  " => " ",
            ">>" => "»",
            "<<" => "«",
            " % " => "&nbsp;%",
            " - " => " – ",
            " ... " => "&nbsp;… ",
            " ..." => "&nbsp;…",
            " :-(" => "&nbsp;😟",
            " :-)" => "&nbsp;🙂",
            " :-O" => "&nbsp;😮",
            " :-|" => "&nbsp;😐",
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
            " cca. " => " cca.&nbsp;",
            " h " => "&nbsp;h ",
            " h" => "&nbsp;h",
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
            " p. " => " p.&nbsp;",
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
            " zvl. " => " zvl.&nbsp;",
            " °C " => "&nbsp;°C ",
            " °F " => "&nbsp;°F ",
            " č. " => " č.&nbsp;",
            " čj. " => " čj.&nbsp;",
            " čp. " => " čp.&nbsp;",
            " čís. " => " čís.&nbsp;",
            " ‰ " => "&nbsp;‰",
        );
        $content = str_replace(array_keys($replace), $replace, $content);

        return (string) $content;
    }

}
