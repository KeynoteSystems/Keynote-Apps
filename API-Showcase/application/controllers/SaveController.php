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

		$this->_session->apiKey = $this->_getParam('api_key');

		$api = new Keynote_Client();

		$api->api_key = $this->_session->apiKey;

		$this->_session->slotData = $api->getActiveSlotMetaData();

		$frontendOptions = array();

		$backendOptions = array('cache_dir' => '../data/cache');

		$cache = Zend_Cache::factory('Page', 'File', $frontendOptions, $backendOptions);

		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);

		//$this->_redirect($this->_session->url);
	}

}
