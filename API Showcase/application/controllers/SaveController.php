<?php

class SaveController extends Zend_Controller_Action
{
    /**
     * Session
     *
     * @var array
     */
    private $_session = array();

    /**
     * Initialise.  Load registry items and database model
     *
     */
    public function init()
    {
        $this->_helper->layout->disableLayout();

        $this->_helper->viewRenderer->setNoRender();

        $this->_session = new Zend_Session_Namespace('DASHBOARD');
    }

    /**
     * indexAction
     *
     */
    public function indexAction()
    {
        $api_key = $this->_getParam('api_key');

        $this->_session->apiKey = $api_key;

        $this->_redirect($this->_session->url);
    }

}
