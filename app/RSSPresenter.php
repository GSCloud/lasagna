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
 * RSS Presenter
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
     * @return array data
     */
    public function process()
    {
        $items = [
            [
                "title" => "title #1",
                "link" => "link #1",
                "description" => "description #1",
            ],
            [
                "title" => "title #2",
                "link" => "link #2",
                "description" => "description #2",
            ],
        ];
        return $items;
    }
}
