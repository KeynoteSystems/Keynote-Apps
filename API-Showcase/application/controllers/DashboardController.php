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

    public function init()
    {
        $this->_config = Zend_Registry::get('config');

        $this->_session = new Zend_Session_Namespace('DASHBOARD');

        $this->_helper->layout()->setLayout('dashboard');

        $api = new Keynote_Client();

        if ($this->_session->apiKey) {
            $api->api_key = $this->_session->apiKey;
        } else {
            $this->_redirect('index');
            $api->api_key = $this->_config['apikey'];
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

        $graph = $api->getGraphData(array($slotId), "time", $this->_config['graph']['timezone'], "relative", 43200, 3600, null, 'GM', array($slotId));

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

    public function tableAction()
    {
        $this->_helper->layout()->setLayout('table');
    }

    public function tabledataAction()
    {
        header('Content-type: application/json');
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $api = new Keynote_Client();

        if ($this->_session->apiKey) {
            $api->api_key = $this->_session->apiKey;
        } else {
            $api->api_key = $config->general->apiKey;
        }

        $data = $api->getDashboardData();

        $i = 0;
        $t = array();

        foreach ($data->product as $a) {


            foreach ($a->measurement as $b) {
                $icon = '';
                $icon1 = '';
                $t[][] = '<a href="/graph/generate?slotId=' . $b->id. '&graphType=time&Days=86400&pageComponent=U&am=GM">' . $b->alias . '</a>';

                $dataArray[] = array(
                '<a href="/graph/generate?slotId=' . $b->id. '&graphType=time&Days=86400&pageComponent=U&am=GM">' . $b->alias . '</a>',
                $b->perf_data[0]->value,
                $b->perf_data[1]->value,
                $b->perf_data[2]->value,
                $b->perf_data[3]->value,
                $b->avail_data[0]->value,
                $b->avail_data[1]->value,
                $b->avail_data[2]->value,
                $b->avail_data[3]->value
                );

                foreach ($b->perf_data as $c) {
                    $warn = $b->threshold_data[0]->value;
                    $crit = $b->threshold_data[1]->value;
                    if ($c->value != '') {
                        $val = (float) $c->value;
                    } else {
                        $val = '-';
                    }

                    if ($warn == -1.0) $warn = '-';
                    if ($crit == -1.0) $crit = '-';

                    if ($val === '' || $val === '-') {
                        $icon = '';
                    }

                    if ($val != '' && $val > 0 && $val < $warn) {
                        $icon = '-success';
                    }

                    if ($val != '' &&  $val >= $warn) {
                        $icon ='-warning';
                    }

                    if ($val != '-' && $val > 0 && $val > $crit) {
                        $icon ='-danger';
                    }


                    if ($val != '' && $val > 0 && $warn == '-' && $crit == '-') {
                        $icon = '-success';
                    }


                    if ($val != '-' && $val == 0) {
                        $icon = '-danger';
                    }

                    $tip = "<b>" . $b->alias . "</b>" .
                   "<br/>Performance: <b>" . $val .
                   "s</b><br/>Warning: <b style='color:orange'>" . $warn .
                   "s</b><br/>Critical: <b style='color:red'>" . $crit . "s</b>";

                    array_push ($t[$i],"<button class=\"btn-small btn-block btn" . $icon . "\" rel='tooltip' data-html='true' title=\"" . $tip . "\" />" . $val . "</button");
                }

                foreach ($b->avail_data as $e) {
                    $warn = $b->threshold_data[2]->value;
                    $crit = $b->threshold_data[3]->value;
                    if ($e->value != '-' && $e->value != '0') {
                        $val1 = (float) $e->value;
                    } else {
                        $val1 = $e->value;
                    }

                    if ($warn == -1.0) $warn = '-';
                    if ($crit == -1.0) $crit = '-';

                    if ($val1 == '-' && $val1 != 0) {
                        $icon1 = '';
                    }

                    if ($val1 != '-' && $val1 === 100) {
                        $icon1 = '-success';
                    }

                    if ($val1 != '-' && $val1 > $warn) {
                        $icon1 = '-success';
                    }

                    if ($val1 != '-' && $val1 <= $warn && $val1 > 0) {
                        $icon1 ='-warning';
                    }

                    if ($val1 == '0') {
                        $icon1 = '-danger';
                    }

                    if ($val1 != '-' && $val1 <= $crit && $val > 0) {
                        $icon1 = '-danger';
                    }
                    $tip2 = "<b>" . $b->alias . "</b><br/>Availability: <b>" . $val1 . "%</b>" .
                    "<br/>Warning: <b style='color:orange'>" . $warn .
                    "%</b><br/>Critical: <b style='color:red'>" . $crit . "%</b>";
                    array_push($t[$i], "<button class=\"btn-small btn-block btn" . $icon1 . "\" rel='tooltip' data-html='true' title=\"" . $tip2 . "\" />" . $val1 . "</button>");


                }
                $i++;
            }
        }

                        //print_r ($dataArray);
        $v = array("iTotalRecords" => $i, "iTotalDisplayRecords" => $i, "aaData" => $dataArray);
        echo json_encode($v);
    }
}
