<?php

class AbcController extends Zend_Controller_Action
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
        $this->_helper->layout()->setLayout('abc');

        $slotData = $this->_api->getSlotMetaData();

        $cDate = date('Y-m-d H:i:s');

        foreach ($slotData->product as $a) {
            if ($a->name != 'ApP') {
                foreach ($a->slot as $b) {
                    $endDate = $b->end_date;
                    if ($endDate > $cDate) {
                        $slotIds[$b->slot_alias] = $b->slot_id;
                    }
                }
            }
        }

        $this->view->slotIds = $slotIds;
    }

    public function generateAction()
    {
        $this->_helper->layout->disableLayout();

        $this->view->currentDate = date('Y-m-d');

        $brick = $this->_api->getGraphData(array($this->_request->getParam('slotId')), 'agent', $this->_config->general->timeZone, 'relative', $this->_request->getParam('Days'), 86400, 'U,Y,M', $this->_request->getParam('am'));

        foreach ($brick->measurement[0]->bucket_data as $m) {
            $perfLocation[] = array('perf' => $m->perf_data->value . 's', 'location' => $m->name);
            $availLocation[] = array('avail' => $m->avail_data->value . '%', 'location' => $m->name);
        }

        sort($perfLocation);
        sort($availLocation);

        $this->view->agentCount = count($perfLocation);

        $nAgents = count($perfLocation) - 1;

        $this->view->urlMonitored = $this->_request->getParam('Url');
        $this->view->locations = $this->_request->getParam('Locations');
        $this->view->days = $this->_request->getParam('Days') / 86400;

        $this->view->fastest = $perfLocation[0];
        $this->view->slowest = $perfLocation[$nAgents];

        $this->view->perfGraph = array($perfLocation[0]['location'] => $perfLocation[0]['perf'] . ' (Fastest)',
        $perfLocation[$nAgents]['location'] => $perfLocation[$nAgents]['perf'] . ' (Slowest)');

        $pgraph = array();

        foreach ($perfLocation as $k => $v) {
            $pgraph[$v['location']] = $v['perf'];
        }

        $agraph = array();

        foreach ($availLocation as $k => $v) {
            $agraph[$v['location']] = $v['avail'];
        }

        $this->view->availGraph = array($availLocation[0]['location'] => $availLocation[0]['avail'] . ' (Lowest)',
        $availLocation[$nAgents]['location'] => $availLocation[$nAgents]['avail'] . ' (Highest)');

        $this->view->perfGraph = $pgraph;
        $this->view->availGraph = $agraph;

        $this->view->lowest = $availLocation[0];
        $this->view->highest = $availLocation[$nAgents];

        $this->view->alias = str_replace(' - Total Time (seconds)', '', $brick->measurement[0]->alias);
        $this->view->avgPerf = $brick->measurement[0]->graph_option[7]->value;
        $this->view->avgAvail = $brick->measurement[0]->graph_option[8]->value;
        $this->view->avgBytes = number_format($brick->measurement[1]->graph_option[7]->value / 1024000, 2, '.', ',');
        $this->view->recBytes = number_format(1, 2, '.', ',');
        $this->view->avgObj = number_format($brick->measurement[2]->graph_option[7]->value, 1, '.', '');

        $this->view->bytesGraph = array('Bytes Downloaded' => $this->view->avgBytes . 'Mb', 'Recommended' => $this->view->recBytes . 'Mb');
        $this->view->objGraph = array('No. of Objects' => $this->view->avgObj, 'Recommended' => '50');

        $this->view->perfFontColor = ($this->view->avgPerf <= 2) ? '#77AB13' : '#AE432E';
        $this->view->perfArrow = ($this->view->avgPerf > 2) ? 'down' : 'up';

        $this->view->availFontColor = ($this->view->avgAvail > 99.5) ? '#77AB13' : '#AE432E';
        $this->view->availArrow = ($this->view->avgAvail > 99.5) ? 'up' : 'down';
    }

    public function sendmailAction()
    {

        $this->_helper->layout->disableLayout();

        $this->_helper->viewRenderer->setNoRender();

        $crl = curl_init();
        $timeout = 60;
        curl_setopt ($crl, CURLOPT_URL,'http://192.168.1.10/abc/generate');
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $str = curl_exec($crl);
        curl_close($crl);

        $to = "robert.castley@gmail.com";
        $from = "robert.castley@keynote.com";
        $subject = "Example Daily Report created using the API (incl. Objects & Bytes Downloaded)";

        $message = $str;

        // To send the HTML mail we need to set the Content-type header.
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $headers  .= "From: $from\r\n";
        //$headers .= "Bcc: [email]email@maaking.cXom[/email]";

        // now lets send the email.
        mail($to, $subject, $message, $headers);

        echo "Message has been sent....!" . $to;
    }
}