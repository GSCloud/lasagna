<?php

class LogoutPresenter extends \GSC\APresenter {

  public function process() {
    ob_end_flush();
    $this->setForceCsvCheck();
    $this->logout();
  }

}
