<?php
/**
 * GSC Tesseract LASAGNA
 *
 * @category Framework
 * @package  LASAGNA
 * @author   Fred Brooker <oscadal@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE
 * @link     https://lasagna.gscloud.cz
 */

 class LogoutPresenter extends \GSC\APresenter {

  public function process() {
    ob_end_flush();
    $this->logout();
  }

}
