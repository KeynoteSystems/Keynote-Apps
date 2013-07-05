<?php
class ReportController extends Zend_Controller_Action
{
	/**
	 * Session
	 *
	 * @var array
	 */
	private $_session = array();

	public function indexAction()
	{
		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

		$this->_session->url = $url;

		$api = new Keynote_Client();

		if ($this->_session->apiKey) {
			$api->api_key = $this->_session->apiKey;
		} else {
			$this->_redirect('index');
		}

		$this->view->data = $this->_session->slotData;

		$this->view->alarm = $api->getAlarmMetaData();
	}
}
