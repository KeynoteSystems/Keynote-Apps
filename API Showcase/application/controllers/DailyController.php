<?php
class DailyController extends Zend_Controller_Action
{
    /**
     * Session
     *
     * @var array
     */
    private $_session = array();

    public function init()
    {
        $config = Zend_Registry::get('config');

        $this->view->locale = Zend_Registry::get('locale');

        $this->_session = new Zend_Session_Namespace('DASHBOARD');

        $api = new Keynote_Client();

        if ($this->_session->apiKey) {
            $api->api_key = $this->_session->apiKey;
        } else {
            $this->_redirect('index');
        }

        $slotData = $api->getSlotMetaData();

        $cDate = date('Y-m-d H:i:s');

        foreach ($slotData->product as $a) {
            foreach ($a->slot as $b) {
                if ($b->slot_id != 1091870) {
                    $endDate = $b->end_date;
                    if ($endDate > $cDate) {
                        $nPages = explode(',', $b->pages);
                        $daysLeft = strtotime($endDate) - strtotime($cDate);
                        $slotIds[] = $b->slot_id;
                        for ($i = 0; $i < count($nPages); $i++) {
                            $r = $i + 1;
                            $transPage = $b->slot_id . ":" . $r;
                            $pageList[$b->slot_id][] = array($transPage);
                        }
                    }
                }
            }
        }

        $this->view->currentDay = date('Y-m-d');

        $week = $api->getGraphData($slotIds, 'time', $config->general->timeZone, 'relative', 604800, 86400, 'T,Y,M', 'GM');

        $month = $api->getGraphData($slotIds, 'time', $config->general->timeZone, 'relative', 2419200, 604800, 'T,Y,M', 'GM');

        foreach ($week->measurement as $wm) {
            $alias = $wm->alias;

            $perfData[$alias] = array();
            if (strstr($wm->alias, 'Total Time')) {
                foreach ($wm->bucket_data as $wb) {
                    if ($wb->perf_data->value != '-' || $wb->avail_data->value != '-') {
                        $pValue = number_format($wb->perf_data->value, 2, '.', '');
                        $aValue = number_format($wb->avail_data->value, 1, '.', '');
                    } else {
                        $pValue = $wb->perf_data->value;
                        $aValue = $wb->avail_data->value;
                    }
                    array_push($perfData[$alias], array('perf' => $pValue, 'avail' => $aValue));

                }
            }

            if (strstr($wm->alias, 'Object Count')) {
                foreach ($wm->bucket_data as $wb) {
                    if ($wb->perf_data->value != '-' || $wb->avail_data->value != '-') {
                        $pValue = number_format($wb->perf_data->value, 1, '.', '');
                    } else {
                        $pValue = $wb->perf_data->value;
                    }
                    array_push($perfData[$alias], array('objects' => $pValue));

                }
            }

            if (strstr($wm->alias, 'Average Bytes')) {
                foreach ($wm->bucket_data as $wb) {
                    if ($wb->perf_data->value != '-' || $wb->avail_data->value != '-') {
                        $pValue = $wb->perf_data->value;
                    } else {
                        $pValue = $wb->perf_data->value;
                    }
                    array_push($perfData[$alias], array('bytes' => $pValue));

                }
            }

        }

        foreach ($month->measurement as $mm) {
            $alias = $mm->alias;
            if (strstr($mm->alias, 'Total Time')) {
                foreach ($mm->bucket_data as $mb) {
                    if ($mb->perf_data->value != '-' || $mb->avail_data->value != '-') {
                        $pmValue = number_format($mb->perf_data->value, 2, '.', '');
                        $amValue = number_format($mb->avail_data->value, 1, '.', '');
                    } else {
                        $pmValue = $mb->perf_data->value;
                        $amValue = $mb->avail_data->value;
                    }
                    array_push($perfData[$alias], array('perf' => $pmValue, 'avail' => $amValue));
                }
            }

            if (strstr($mm->alias, 'Object Count')) {
                foreach ($mm->bucket_data as $mb) {
                    if ($mb->perf_data->value != '-') {
                        $pmValue = number_format($mb->perf_data->value, 1, '.', '');
                    } else {
                        $pmValue = $mb->perf_data->value;
                    }
                    array_push($perfData[$alias], array('objects' => $pmValue));
                }
            }

            if (strstr($mm->alias, 'Average Bytes')) {
                foreach ($mm->bucket_data as $mb) {
                    if ($mb->perf_data->value != '-') {
                        $pmValue = $mb->perf_data->value;
                    } else {
                        $pmValue = $mb->perf_data->value;
                    }
                    array_push($perfData[$alias], array('bytes' => $pmValue));
                }
            }
        }

        $this->view->avail_warn = $config->general->avail_warn;
        $this->view->avail_crit = $config->general->avail_crit;
        $this->view->perfData = $perfData;

    }

    public function indexAction()
    {
        $this->_helper->layout()->setLayout('daily');
    }

    public function createmailAction()
    {
        $this->_helper->layout()->setLayout('mail');
    }

    public function sendmailAction()
    {

        $this->_helper->layout->disableLayout();

        $this->_helper->viewRenderer->setNoRender();

        $crl = curl_init();
        $timeout = 60;
        curl_setopt ($crl, CURLOPT_URL,'http://192.168.1.10/daily/createmail');
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $str = curl_exec($crl);
        curl_close($crl);

        $to = "robert.castley@keynote.com";
        $from = "robert.castley@keynote.com";
        $subject = "Example Daily Report created using the API (incl. Objects & Bytes Downloaded)";

        //begin of HTML message
        $message = $str;
        //end of message

        // To send the HTML mail we need to set the Content-type header.
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $headers  .= "From: $from\r\n";
        //options to send to cc+bcc
        //$headers .= "Cc: [email]maa@p-i-s.cXom[/email]";
        //$headers .= "Bcc: [email]email@maaking.cXom[/email]";

        // now lets send the email.
        mail($to, $subject, $message, $headers);

        echo "Message has been sent....!" . $to;
    }
}