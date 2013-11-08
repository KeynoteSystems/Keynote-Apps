<?php
class DashboardController extends Zend_Controller_Action
{
	/**
	 * Session
	 *
	 * @var array
	 */
	private $_session = array();

	private $_config = array();

	private $slotCount;

	public $dashboard;

	private $perfArray;

	private $availArray;

	private $_api;

	public function init()
	{
		$this->_config = Zend_Registry::get('config');

		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		$this->_helper->layout()->setLayout('dashboard');

		$this->_api = new Keynote_Client();

		if ($this->_session->apiKey) {
			$this->_api->api_key = $this->_session->apiKey;
		} else {
			$this->_redirect('index');
			$this->_api->api_key = $this->_config['apikey'];
		}

	}

	public function indexAction()
	{
		$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

		$this->_session->url = $url;

		/*
		$dashboardData = $this->_api->getDashboardData();

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
		*/
	}

	public function graphAction()
	{
		header('Content-type: application/json');

		$this->_helper->layout->disableLayout();

		$this->_helper->viewRenderer->setNoRender();

		$dashboardData = $this->_api->getDashboardData();

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

		if (!$this->_getParam('slotid')) {
			$getSlots = count($this->slotCount);
			$slotId = $this->slotCount[rand(0,$getSlots-1)];
		} else {
			$slotId = $this->_getParam('slotid');
		}

		$graph = $this->_api->getGraphDataRelative(array($slotId), "time", $this->_config['graph']['timezone'], "relative", 43200, 3600, null, 'GM');

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

		$dashboardData = $this->_api->getDashboardData();

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

	public function tableAction()
	{
		$this->_helper->layout()->setLayout('table');

		$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

		$this->_session->url = $url;
	}

	public function tabledataAction()
	{
		header('Content-type: application/json');

		$this->_helper->layout->disableLayout();

		$this->_helper->viewRenderer->setNoRender();

		$data = $this->_api->getDashboardData();

		$i = 0;

		foreach ($data->product as $a) {

			foreach ($a->measurement as $b) {
				$dataArray[] = array(
                '<a href="/graph/generate?slotId=' . $b->id. '&graphType=time&Days=86400&pageComponent=U&am=GM">' . $b->alias . '</a>',
				$this->parsePerfData($b->alias,$b->perf_data[0]->value, $b->threshold_data[0]->value, $crit = $b->threshold_data[1]->value),
				$this->parsePerfData($b->alias,$b->perf_data[1]->value, $b->threshold_data[0]->value, $crit = $b->threshold_data[1]->value),
				$this->parsePerfData($b->alias,$b->perf_data[2]->value, $b->threshold_data[0]->value, $crit = $b->threshold_data[1]->value),
				$this->parsePerfData($b->alias,$b->perf_data[3]->value, $b->threshold_data[0]->value, $crit = $b->threshold_data[1]->value),
				$this->parseAvailData($b->alias,$b->avail_data[0]->value, $b->threshold_data[2]->value, $crit = $b->threshold_data[3]->value),
				$this->parseAvailData($b->alias,$b->avail_data[1]->value, $b->threshold_data[2]->value, $crit = $b->threshold_data[3]->value),
				$this->parseAvailData($b->alias,$b->avail_data[2]->value, $b->threshold_data[2]->value, $crit = $b->threshold_data[3]->value),
				$this->parseAvailData($b->alias,$b->avail_data[3]->value, $b->threshold_data[2]->value, $crit = $b->threshold_data[3]->value)
				);
			}
			$i++;

		}

		$v = array("iTotalRecords" => $i, "iTotalDisplayRecords" => $i, "aaData" => $dataArray);
		echo json_encode($v);
	}

	private function parsePerfData($alias, $data, $warn, $crit) {
		switch ($data) {
			case '-':
			case '':
				$css = '';
				$data = '-';
				$dataType = '';
				break;
			case ($data > 0 && $data < $warn):
				$css = '-success';
				$dataType = 's';
				break;
			case ($data >= $warn && $data > 0 && !($data >= $crit)):
				$css = '-warning';
				$dataType = 's';
				break;
			case 0:
			case ($data >= $crit && $data > 0):
				$css = '-danger';
				$dataType = 's';
				break;
			default:
				$css = '';
				$dataType = 's';
		}

		if ($warn == -0.0010) {
			$css = '';
			$perfTip = '<b>No thresholds set!</b>';
		} else {
			$perfTip = "Performance: <b>" . $data . "s</b>" .
                   "<br>Warning: <b style='color:orange'>" . $warn . "s</b>" .
                   "<br>Critical: <b style='color:red'>" . $crit . "s</b>";
		}

		return "<button class='btn-small btn-block btn$css' rel='tooltip' data-html='true' title=\"" . htmlentities($perfTip) . "\">" . $data . "" . $dataType . "</button>";

	}

	private function parseAvailData($alias, $data, $warn, $crit) {
		switch ($data) {
			case '-':
				$css = '';
				$dataType = '';
				$data = '-';
				break;
			case ($data == 100 || $data > $warn):
				$css = '-success';
				$dataType = '%';
				break;
			case ($data <= $warn && $data > 0 && !($data <= $crit)):
				$css = '-warning';
				$dataType = '%';
				break;
			case 0:
			case ($data <= $crit && $data > 0):
				$css = '-danger';
				$dataType = '%';
				break;
			default:
				$css = '';
				$dataType = '%';
		}

		if ($warn == -1.0) {
			$css = '';
			$availTip = '<b>No thresholds set!</b>';
		} else {

			$availTip = "Availability: <b>" . $data . "%</b>" .
                    "<br>Warning: <b style='color:orange'>" . $warn . "%</b>" .
                    "<br>Critical: <b style='color:red'>" . $crit . "%</b>";
		}

		return "<button class=\"btn-small btn-block btn" . $css . "\" rel='tooltip' data-html='true' title=\"" . htmlentities($availTip) . "\">" . $data . "" .  $dataType . "</button>";

	}
}