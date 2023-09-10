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
 * Logout Presenter class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */
class LogoutPresenter extends APresenter
{
    /**
     * Controller processor
     *
     * @return void
     */
    public function process()
    {
        if (\ob_get_level()) {
            @\ob_end_clean();
        }
        $this->logout();
        exit;
    }
}
