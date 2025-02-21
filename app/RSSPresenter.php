<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://github.com/GSCloud/lasagna
 */

namespace GSC;

/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://github.com/GSCloud/lasagna
 */
class RSSPresenter extends APresenter
{
    /**
     * Main controller
     *
     * @param mixed $param optional parameter
     * 
     * @return array|mixed array of data
     */
    public function process($param = null)
    {
        // RSS links base
        $base = 'https://hmcgames.com/';

        // @phpstan-ignore-next-line
        $data = HmcPresenter::getInstance()->readBlogCSV();
        unset($data['allhashtags']);

        $items = [];
        foreach ($data as $k => $v) {
            $t = \explode('/', $v['pubdate']);
            $date = new \DateTime();
            $date->setDate((int) $t[0], (int) $t[1], (int) $t[2]);
            $t = $date->getTimestamp();
            $v['cs'] = \str_replace('&nbsp;', ' ', $v['cs']);
            $v['en'] = \str_replace('&nbsp;', ' ', $v['en']);
            $items[] = [
                "title" => \strip_tags(\htmlspecialchars($v['cs'])),
                "description" => \htmlspecialchars($v['cs_perex']),
                "link" => $base . 'cs/blog/' . $v['cs_stub'],
                "pubdate" => \date(DATE_RFC2822, $t),
            ];
            $items[] = [
                "title" => \strip_tags(\htmlspecialchars($v['en'])),
                "description" => \htmlspecialchars($v['en_perex']),
                "link" => $base . 'en/blog/' . $v['en_stub'],
                "pubdate" => \date(DATE_RFC2822, $t),
            ];
        }
        return $items;
    }
}
