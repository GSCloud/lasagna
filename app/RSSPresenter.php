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
        $items = [
            [
                "title" => "foo #1",
                "link" => "bar #1",
                "description" => "foo bar #1",
            ],
            [
                "title" => "foo #2",
                "link" => "bar #2",
                "description" => "foo bar #2",
            ],
            [
                "title" => "foo #3",
                "link" => "bar #3",
                "description" => "foo bar #3",
            ],
        ];
        return $items;
    }
}
