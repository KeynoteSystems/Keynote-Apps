<?php
class AbcController extends Zend_Controller_Action
{
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

        $this->view->locale = Zend_Registry::get('locale');

        $session = new Zend_Session_Namespace('DASHBOARD');

        $this->_api = new Keynote_Client();

        if ($session->apiKey) {
            $this->_api->api_key = $session->apiKey;
        } else {
            $this->_redirect('index');
        }
    }

    public function indexAction()
    {
        $slotData = $this->_api->getSlotMetaData();

        $cDate = date('Y-m-d H:i:s');

        $slotIds = array();

        foreach ($slotData->product as $a) {
            if ($a->name != 'ApP' && $a->name != 'MDP' && $a->name != 'STP') {
                foreach ($a->slot as $b) {
                    $endDate = $b->end_date;
                    $nPages = explode(',', $b->pages);
                    if ($endDate >= $cDate) {
                        $slotIds[$b->slot_alias] = array($b->slot_id, $a->name);
                    }
                }
            }
        }

        $this->view->slotIds = $slotIds;
    }

    public function generateAction()
    {
    	$this->abcContent($this->_request->getParams());

    	$this->_helper->layout->disableLayout();

    	$this->view->currentDate = date('Y-m-d');
    }

    public function sendmailAction()
    {
    	$this->abcContent($this->_request->getParams());

        $htmlString = $this->view->render('abc/generate.phtml');

        $this->view->message = "<div class='alert alert-success'>Your mail has been successfully sent!</div>";

        $mail = new Zend_Mail('UTF-8');
        $mail->addTo($this->_request->getParam('mailTo'), $this->_request->getParam('prospectName'));
        $mail->setFrom($this->_request->getParam('mailFrom'), $this->_request->getParam('fullName'));
        if ($this->_request->getParam('ccAddress')) {
            $mail->addCc($this->_request->getParam('ccAddress'));
        }
        $mail->addBcc($this->_request->getParam('mailFrom'));
        $mail->setSubject($this->_request->getParam('mailSubject'));
        $mail->setBodyHtml($htmlString);
        $mail->send();
    }

    public function abcContent($params)
    {
    	$slotId = explode(',', $params['slotId']);

    	switch ($slotId[1]) {
    		case 'MWP':
    			$this->view->product = 'WebKit Engine';
    			$transPageList = null;
    			break;
    		case 'TxP':
    			$this->view->product = 'Real Browser';
    			$transPageList = array($slotId[0] . ':1');
    			break;
    	}

    	$brick = $this->_api->getGraphData(array($slotId[0]), 'agent', $this->_config['graph']['timezone'], 'relative', $params['days'], 86400, 'U,Y,M', $params['am'], $transPageList);

    	foreach ($brick->measurement[0]->bucket_data as $m) {
    		$perfLocation[] = array('perf' => $m->perf_data->value . 's', 'location' => $m->name);
    		$availLocation[] = array('avail' => $m->avail_data->value . '%', 'location' => $m->name);
    	}

    	sort($perfLocation);
    	sort($availLocation);

    	$this->view->agentCount = count($perfLocation);

    	$nAgents = count($perfLocation) - 1;

    	$pgraph = array();

    	foreach ($perfLocation as $k => $v) {
    		$pgraph[$v['location']] = $v['perf'];
    	}

    	$agraph = array();

    	foreach ($availLocation as $k => $v) {
    		$agraph[$v['location']] = $v['avail'];
    	}

    	$this->view->perfGraph  = $pgraph;
    	$this->view->availGraph = $agraph;

    	$prospectFName = explode(" ", $params['prospectName']);

    	$this->view->url           = $params['url'];
    	$this->view->days          = $params['days'] / 86400;
    	$this->view->interval      = $params['interval'];
    	$this->view->fullName      = $params['fullName'];
    	$this->view->mailFrom      = $params['mailFrom'];
    	$this->view->browserDevice = $params['browserDevice'];
    	$this->view->prospectFName = $prospectFName[0];

    	$this->view->avgPerf  = $brick->measurement[0]->graph_option[7]->value;
    	$this->view->avgAvail = $brick->measurement[0]->graph_option[8]->value;

    	$this->view->avgBytes = number_format($brick->measurement[1]->graph_option[7]->value / 1024000, 2, '.', ',');
    	$this->view->avgObj   = number_format($brick->measurement[2]->graph_option[7]->value, 0);

    	$this->view->bytesGraph = array($this->view->translate('bytesdownloaded') => $this->view->avgBytes . 'Mb', $this->view->translate('recmaximum') => '0.5Mb');
    	$this->view->objGraph   = array($this->view->translate('numobjects') => $this->view->avgObj, $this->view->translate('recmaximum') => '50');

    	$this->view->perfCellColor  = ($this->view->avgPerf <= 2) ? '#5faa1a' : '#da542e';
    	$this->view->perfGraphColor = ($this->view->avgPerf <= 2) ? '#92D050' : '#da542e';
    	$this->view->perfGraphCellColor = ($this->view->avgPerf <= 2) ? '#528f31' : '#613cbd';

    	$this->view->fastSlow  = ($this->view->avgPerf <= 2) ? $this->view->translate('faster') : $this->view->translate('slower');

    	$this->view->perfArrow = ($this->view->avgPerf > 2) ? 'down-arrow.png' : 'up-arrow.png';

    	$this->view->availCellColor  = ($this->view->avgAvail > 99.5) ? '#5faa1a' : '#da542e';
    	$this->view->availGraphColor = ($this->view->avgAvail > 99.5) ? '#92D050' : '#da542e';
    	$this->view->availGraphCellColor = ($this->view->avgAvail > 99.5) ? '#528f31' : '#613cbd';

    	$this->view->betterWorse = ($this->view->avgAvail > 99.5) ? $this->view->translate('better') : $this->view->translate('worse');

    	$this->view->availArrow = ($this->view->avgAvail > 99.5) ? 'up-arrow.png' : 'down-arrow.png';
    	if ($this->view->avgAvail == 100) {
    		$this->view->availArrow = 'right-arrow.png';
    	}

    	$this->view->customHeadline = $this->_request->getParam('customHeadline');
    	$this->view->customMessage  = $this->_request->getParam('customMessage');

    	return;
    }
}