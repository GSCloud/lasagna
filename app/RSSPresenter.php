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
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class RSSPresenter extends APresenter
{
    /**
     * Main controller
     *
     * @param mixed $param optional parameter
     * 
     * @return array data
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
