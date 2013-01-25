<?php
class DebugController extends Zend_Controller_Action
{
    public function init()
    {

    }

    public function langAction()
    {

    }

    public function phpinfoAction()
    {
        $this->_helper->layout->disableLayout();

        $this->_helper->viewRenderer->setNoRender();

        phpinfo();
    }
}
