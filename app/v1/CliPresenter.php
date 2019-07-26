<?php

class CliPresenter extends \GSC\APresenter
{

    public function process()
    {
        return $this;
    }

    public function showConst()
    {
        print_r(get_defined_constants(true)["user"]);
        return $this;
    }
}
