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
        $this->view->config = Zend_Registry::get('config');

        /**
         * Initialise session
         */
        $this->_session = new Zend_Session_Namespace('DASHBOARD');

        $this->view->locale = Zend_Registry::get('locale');

        $this->view->api_key = $this->_session->apiKey;
    }
}
