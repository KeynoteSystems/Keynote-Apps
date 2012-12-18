<?php

class IndexController extends Zend_Controller_Action
{
    /**
     * Session
     *
     * @var array
     */
    private $_session = array();

    public function indexAction()
    {
        /**
         * Initialise session
         */
        $this->_session = new Zend_Session_Namespace('DASHBOARD');

        /**
         * Load in configuration from dashboard.ini
         */
        $config = Zend_Registry::get('config');

        $this->view->locale = Zend_Registry::get('locale');
    }

    public function switchmodeAction()
    {

    }
}
