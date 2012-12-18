<?php

class DashboardController extends Zend_Controller_Action
{
    /**
    * Session
    *
    * @var array
    */
    private $_session = array();

	private $slotCount;

	public $dashboard;

	private $perfArray;

	private $availArray;

	public function init()
	{
		$config = Zend_Registry::get('config');

		$this->view->locale = Zend_Registry::get('locale');

		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		$this->_helper->layout()->setLayout('dashboard');

		$api = new Keynote_Client();

		if ($this->_session->apiKey) {
			$api->api_key = $this->_session->apiKey;
		} else {
            $this->_redirect('index');
			$api->api_key = $config->general->apiKey;
		}

		$api->format = 'json';

		$dashboardData = $api->getDashboardData();

		$slots = array();

		foreach ($dashboardData->product as $p) {

			foreach ($p->measurement as $m) {
				$slots[] = $m->id;
			}
		}

		$this->dashboard = $dashboardData;

		$this->view->dashboard = $dashboardData;


		$this->view->slots = $slots;
		$this->slotCount = $slots;

		$getSlots = count($this->slotCount);
		$this->slotId = $this->slotCount[rand(0,$getSlots-1)];

		$this->view->slotId = $this->slotId;

		$this->view->apiKey = $config->general->apiKey;

		foreach ($this->dashboard->product as $p) {

			foreach ($p->measurement as $m) {
				if ($m->perf_data[0]->value != 0) {
					$this->perfArray[$m->alias] = array('five' => $m->perf_data[0]->value, 'fifteen' => $m->perf_data[1]->value, 'slotId' => $m->id);
				}

				if ($m->avail_data[3]->value != 0) {
					$this->availArray[$m->alias] = array('day' => $m->avail_data[3]->value, 'fifteen' => $m->avail_data[1]->value, 'slotid' => $m->id);
				}

			}
		}


	}

	public function indexAction()
	{
	    $url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

	    $this->_session->url = $url;
	}

	public function graphAction()
	{
		header('Content-type: application/json');

		$this->_helper->layout->disableLayout();

		$this->_helper->viewRenderer->setNoRender();

		$api = new Keynote_Client();

		$config = Zend_Registry::get('config');

		if ($this->_session->apiKey) {
			$api->api_key = $this->_session->apiKey;
		} else {
			$api->api_key = $config->general->apiKey;
		}

		$api->format = 'json';

		if (!$this->_getParam('slotid')) {
		    $getSlots = count($this->slotCount);
			$slotId = $this->slotCount[rand(0,$getSlots-1)];
		} else {
			$slotId = $this->_getParam('slotid');
		}

		$graph = $api->getGraphData(array($slotId), "time", $config->general->timeZone, "relative", 43200, 3600, null);

		foreach ($graph->measurement as $slot) {
			$a = $slot->alias;

			foreach ($slot->bucket_data as $dp) {
				$y = date('d M Y h:i A', strtotime($dp->name));
				$perf[]= array(strtotime($y) * 1000, $dp->perf_data->value);
			}
		}

		echo json_encode(array('title' => $a, 'name' => 'Performance', 'data' => $perf), JSON_NUMERIC_CHECK);
	}

	public function widgetAction()
	{
		header('Content-type: application/json');
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		switch ($this->_getParam('type')) {

			case 'random':
				$keys = array_keys( $this->availArray );
				shuffle( $keys );
				$this->availArray =  array_merge( array_flip( $keys ) , $this->availArray );
				$name = key($this->availArray);
				$perf = $this->availArray[$name]['day'];
				$previous = $this->availArray[$name]['fifteen'];
				$color = ($perf < $previous) ? '#AE432E' : '#77AB13';
				$arrow = ($perf < $previous) ? 'down' : 'up';
				$symbol ='%';
				break;

			case 'avail':
				asort($this->availArray);
				$name = key($this->availArray);
				$perf = $this->availArray[$name]['day'];
				$previous = $this->availArray[$name]['fifteen'];
				$color = ($perf <= $previous) ? '#AE432E' : '#77AB13';
				$arrow = ($perf <= $previous) ? 'down' : 'up';
				$symbol ='%';
				break;

			case 'fastest':
				asort($this->perfArray);
				$name = key($this->perfArray);
				$perf = $this->perfArray[$name]['five'];
				$previous = $this->perfArray[$name]['fifteen'];
				$color = ($perf <= $previous) ? '#77AB13' : '#AE432E';
				$arrow = ($perf <= $previous) ? 'up' : 'down';
				$symbol = 's';
				break;

			case 'slowest':
				arsort($this->perfArray);
				$name = key($this->perfArray);
				$perf = $this->perfArray[$name]['five'];
				$previous = $this->perfArray[$name]['fifteen'];
				$color = ($perf >= $previous) ? '#AE432E' : '#77AB13';
				$arrow = ($perf >= $previous) ? 'down' : 'up';
				$symbol = 's';
				break;
		}

		echo json_encode(array('name' => $name, 'perf' => $perf, 'previous' => $previous, 'arrow' => $arrow, 'color' => $color, 'symbol' => $symbol), JSON_NUMERIC_CHECK);
	}
}
