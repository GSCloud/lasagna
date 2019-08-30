<?php
/**
 * GSC Tesseract
 *
 * @category Framework
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

namespace GSC;

/**
 * Logout Presenter
 */
class LogoutPresenter extends APresenter
{

    /**
     * Process logout
     *
     * @return void
     */
    public function process()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $this->logout();
        exit;
    }

}
