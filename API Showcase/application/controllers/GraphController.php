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
        $slotData = $this->_api->getSlotMetaData();

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

        //$this->_helper->layout->disableLayout();

        $this->view->currentDate = date('Y-m-d');

        $this->_api->format = 'xml';

        $this->view->graphType = $this->_request->getParam('graphType');
        $this->view->graphData = $this->_api->getGraphData(array($this->_request->getParam('slotId')), $this->_request->getParam('graphType'), $this->_config->general->timeZone, 'relative', $nDays, $bSize, null, $this->_request->getParam('am'));
    }

}