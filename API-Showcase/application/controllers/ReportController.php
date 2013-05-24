<?php
class ReportController extends Zend_Controller_Action
{
    /**
     * Session
     *
     * @var array
     */
    private $_session;

    public function init()
    {

    }

    public function indexAction()
    {
        $config = Zend_Registry::get('config');

        $this->_session = new Zend_Session_Namespace('DASHBOARD');

        $url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

        $this->_session->url = $url;

        $api = new Keynote_Client();

        if ($this->_session->apiKey) {
            $api->api_key = $this->_session->apiKey;
        } else {
            $this->_redirect('index');
        }

        $this->view->data = $api->getActiveSlotMetaData();

        $this->view->alarm = $api->getAlarmMetaData();

        $this->view->cDate = date('Y-m-d H:i:s');
    }
}
