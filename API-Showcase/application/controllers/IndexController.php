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

		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		if (!isset($this->_session->apiKey)) {
			if($this->view->config['apikey'] != null) {
				$this->_session->apiKey = $this->view->config['apikey'];
			}
		}

		$this->view->api_key = $this->_session->apiKey;
	}
}
