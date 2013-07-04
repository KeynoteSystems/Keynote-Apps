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
	 * indexAction
	 *
	 */
	public function indexAction()
	{
		$this->_helper->layout->disableLayout();

		$this->_helper->viewRenderer->setNoRender();

		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		$api_key = $this->_getParam('api_key');

		$this->_session->apiKey = $api_key;

		$this->_redirect($this->_session->url);

	}

}
