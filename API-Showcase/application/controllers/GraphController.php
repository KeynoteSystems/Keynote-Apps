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
        $this_config = Zend_Registry::get('config');

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
        $slotData = $this->_api->getActiveSlotMetaData();

        $cDate = date('Y-m-d H:i:s');

        foreach ($slotData->product as $a) {
            foreach ($a->slot as $b) {
                $endDate = $b->end_date;
                if ($endDate > $cDate) {
                    $slotIds[$b->slot_alias] = $b->slot_id;
                }
            }
        }

        $this->view->slotIds = $slotIds;
    }

    public function generateAction()
    {
        if ($this->_request->getParam('graphType') == 'scatter') {
            $nDays = 3600;
            $bSize = 300;
        } else {
            $nDays = $this->_request->getParam('Days');
            $bSize = 3600;
        }

        $this->view->currentDate = date('Y-m-d');

        $this->_api->format = 'xml';

        $this->view->graphType = ucfirst($this->_request->getParam('graphType'));

        $graphData = $this->_api->getGraphData(array($this->_request->getParam('slotId')), $this->_request->getParam('graphType'), $this->_config['graph']['timezone'], 'relative', $nDays, $bSize, $this->_request->getParam('pageComponent'), $this->_request->getParam('am'), array($this->_request->getParam('slotId')));

        switch ($this->_request->getParam('pageComponent')) {
        	case 'U':
        		$this->view->pageComponent = 'User Time (seconds)';
        		$sp = "delta__user_msec";
        		$this->view->gUnit = 's';
        		break;

        	case 'T':
        		$this->view->pageComponent = 'Total Time (seconds)';
        		$sp= "delta__msec";
        		$this->view->gUnit = 's';
        		break;

        	case 'Y':
        		$this->view->pageComponent = 'Bytes Downloaded (Kb)';
        		$sp = "resp__bytes";
        		$this->view->gUnit = 'Kb';
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
                        $perfData[] = array($t, (string)$dp->txn__summary->$sp / 1000);
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
                            $perfDataValue = $dp->perf_data['value'];
                        }
                        $perfData[] = array($t, (string)$perfDataValue);

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
                        $perfData[] = array($t, (string)$dp->perf_data['value']);
                        $availData[] = array($t, (string)$dp->avail_data['value']);

                    }
                    break;
                }
        }

        $this->view->xTime = json_encode($time);
        $this->view->perfDataPoints = json_encode($perfData, JSON_NUMERIC_CHECK);

        if ($this->view->graphType != 'Scatter') {
            $this->view->availDataPoints = json_encode($availData, JSON_NUMERIC_CHECK);
        }
    }

}
