<?php
class GraphController extends Zend_Controller_Action
{
	/**
	 * Session
	 *
	 * @var array
	 */
	private $_session = array();

	/**
	 * API
	 *
	 */
	private $_api;

	/**
	 * Config
	 *
	 */
	private $_config;

	public function init()
	{
		$this->_config = Zend_Registry::get('config');

		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		$this->_api = new Keynote_Client();

		if ($this->_session->apiKey) {
			$this->_api->api_key = $this->_session->apiKey;
		} else {
			$this->_redirect('index');
		}
	}

	public function indexAction()
	{
		$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

		$this->_session->url = $url;

		foreach ($this->_session->slotData->product as $a) {
			foreach ($a->slot as $b) {
				if ($b->slot_id != 1091870 && $b->slot_id != 508374) {
					$slotIds[$b->slot_alias] = $b->slot_id;
				}
			}
		}

		$this->view->slotIds = $slotIds;
	}

	public function generateAction()
	{
		/*
		header('Content-type: application/json');
	    
	    $this->_helper->layout->disableLayout();

		$this->_helper->viewRenderer->setNoRender();
		*/

		if ($this->_request->getParam('graphType') == 'scatter') {
			//$nDays = 3600;
			$nDays = $this->_request->getParam('Days');
			$bSize = 300;
			$basePageOnly = 'true';
		} else {
			$nDays = $this->_request->getParam('Days');
			$bSize = 3600;
			$basePageOnly = 'false';
		}

		$this->view->currentDate = date('Y-m-d');

		$this->_api->format = 'xml';

		$this->view->graphType = ucfirst($this->_request->getParam('graphType'));

		$graphData = $this->_api->getGraphDataRelative(array($this->_request->getParam('slotId')), $this->_request->getParam('graphType'), $this->_config['graph']['timezone'], 'relative', $nDays, $bSize, $this->_request->getParam('pageComponent'), $this->_request->getParam('am'));

		switch ($this->_request->getParam('pageComponent')) {
			case 'U':
				$this->view->pageComponent = 'User Time (seconds)';
				$sp = "delta__user__msec";
				$this->view->gUnit = 's';
				$spdivideby = 1000;
				break;

			case 'T':
				$this->view->pageComponent = 'Total Time (seconds)';
				$sp= "delta__msec";
				$this->view->gUnit = 's';
				$spdivideby = 1000;
				break;

			case 'Y':
				$this->view->pageComponent = 'Bytes Downloaded (Mb)';
				$sp = "resp__bytes";
				$this->view->gUnit = 'Mb';
				$divideby = 1024000;
				$spdivideby = 1024000;
				break;

			case 'M':
				$this->view->pageComponent = 'Object Count';
				$sp = "element__count";
				$this->view->gUnit = null;
				break;
		}

		switch ($this->view->graphType) {
			case 'Scatter':
				$this->view->hcGraphType = 'scatter';
				$this->view->step = 8;
				$this->view->y = 15;
				$this->view->title = 'Scatter Plot';
				foreach ($graphData->list as $datapoint) {
					foreach ($datapoint->children() as $dp) {
						$t = date('d/m H:i:s', strtotime($dp->datetime));
						$time[] = $t;
						switch ($dp->txn__error->code) {
							case '12002':
							case '-99200':
							case '-99100':
							case '4004':
								$fillColor = '#FF0000';
								$symbol = 'triangle';
								$radius = '5';
								$errorCode = (string)$dp->txn__error->code;
								break;
							default:
								$fillColor = '#006600';
								$symbol = 'circle';
								$radius = '2';
								$errorCode = 'Success';
						}

						if ($dp->txn__summary->content__errors == 1 && !$dp->txn__error->code) {
							$fillColor = '#FFDF00';
							$symbol = 'circle';
							$radius = '7';
						}

						$dataValue = $dp->txn__summary->$sp;

						if (isset($spdivideby)) {
							$dataValue = $dataValue / $spdivideby;
						}
						$perfData[] = array('y' => (string)$dataValue, 'errorCode' => $errorCode, 'marker' => array('radius' => $radius, 'symbol' => $symbol, 'fillColor' => $fillColor));
					}
				}
				break;

			case 'Time':
				$this->view->hcGraphType = 'area';
				$this->view->step = (3 * $nDays) / 86400;
				$this->view->y = 15;
				$this->view->title = $graphData->measurement->alias;
				foreach ($graphData->measurement->bucket_data as $datapoint) {
					foreach ($datapoint as $dp) {
						$t = date('d/m H:i:s', strtotime($dp['name']));
						$time[] = $t;
						if ($dp->perf_data['value'] == '-') {
							$perfDataValue = 0;
						} else {
							$perfDataValue = (float)$dp->perf_data['value'];
						}
						if (isset($divideby)) {
							$perfDataValue = $perfDataValue / $divideby;
						}
						
						$perfData[] = array($t, (string)number_format($perfDataValue,2));
						//$perfData[] = array($t, 'y' => (string)number_format($perfDataValue,2), 'errorCode' => 0);

						if ($dp->avail_data['value'] == '-') {
							$availDataValue = 0;
						} else {
							$availDataValue = $dp->avail_data['value'];
						}
						$availData[] = array($t, (string)$availDataValue);
					}
				}
				break;

			case 'Agent':
			case 'Backbone':
			case 'City':
			case 'Country':
			case 'Region':
				$this->view->hcGraphType = 'bar';
				$this->view->step = 0;
				$this->view->y = 0;
				$this->view->title = $graphData->measurement->alias;
				foreach ($graphData->measurement->bucket_data as $datapoint) {
					foreach ($datapoint as $dp) {
						$t = (string)$dp['name'];
						$time[] = $t;
						if ($dp->perf_data['value'] == '-') {
							$perfDataValue = 0;
						} else {
							$perfDataValue = (float)$dp->perf_data['value'];
						}
						if (isset($divideby)) {
							$perfDataValue = $perfDataValue / $divideby;
						}
						$perfData[] = array($t, (string)number_format($perfDataValue,2));
						$availData[] = array($t, (string)$dp->avail_data['value']);

					}
					break;
				}
		}

		/*
		echo json_encode(array('step' => $this->view->step,
		'title' => (string)$this->view->title,
		'pagecomponent' => $this->view->pageComponent,
		'unit' => $this->view->gUnit,
		'graphtype' => $this->view->graphType,
		'hcgraphtype' => $this->view->hcGraphType, 'x' =>$time, 'data' => $perfData, 'y' => $this->view->y), JSON_NUMERIC_CHECK);
		*/

		$this->view->xTime = json_encode($time);
		$this->view->perfDataPoints = json_encode($perfData, JSON_NUMERIC_CHECK);

		if ($this->view->graphType != 'Scatter') {
			$this->view->availDataPoints = json_encode($availData, JSON_NUMERIC_CHECK);
		}
	}

}
