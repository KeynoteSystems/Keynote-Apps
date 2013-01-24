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

		$product = $this->_request->getParam('product');

		$cDate = date('Y-m-d H:i:s');

		$slotIds = array();

		foreach ($slotData->product as $a) {
			if ($a->name == $product) {
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

		}

		if (empty($slotIds)) {
			throw new Exception('No ' . $product . ' slots running!');
		}

		$pageComponents = 'U,Y,M';

		$this->view->vPageCompoments = 'User Time';

		switch ($product) {
			case 'ApP':
				$pageComponents = 'T,Y,M';
				$this->view->vPageCompoments = 'Total Time';
				break;
		}

		$this->view->currentDay = date('Y-m-d');

		$week = $api->getGraphData($slotIds, 'time', $config['graph']['timezone'], 'relative', 604800, 86400, $pageComponents, 'GM', $slotIds);

		$month = $api->getGraphData($slotIds, 'time', $config['graph']['timezone'], 'relative', 2419200, 604800, $pageComponents, 'GM', $slotIds);

		foreach ($week->measurement as $wm) {
			$alias = $wm->alias;

			$perfData[$alias] = array();
			if (strstr($wm->alias, 'Total Time') || strstr($wm->alias, 'User Time')) {
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
			if (strstr($mm->alias, 'Total Time') || strstr($mm->alias, 'User Time')) {
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

		$this->view->avail_warn = $config['availability']['warning'];
		$this->view->avail_crit = $config['availability']['critical'];
		$this->view->perfData = $perfData;

	}

	public function indexAction()
	{
		//$this->_helper->layout()->setLayout('daily');
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
		curl_setopt ($crl, CURLOPT_URL,'http://192.168.1.10/daily/createmail');
		curl_setopt ($crl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($crl, CURLOPT_VERBOSE, true); // Display communication with server

		curl_setopt ($crl, CURLOPT_HEADER, 0);

		$str = curl_exec($crl);

		if(curl_exec($crl) === false)
		{
			echo 'Curl error: ' . curl_error($crl);
		}
		else
		{
			echo 'Operation completed without any errors';
		}
		curl_close($crl);

		//$config = array('auth' => 'login',
		//		'username' => 'myusername',
		//		'password' => 'password');

		//$transport = new Zend_Mail_Transport_Smtp('mail.server.com', $config);

		$mail = new Zend_Mail();
		$mail->addTo('robert.castley@keynote.com', 'Robert Castley');
		$mail->setFrom('noreply@keynote.com', 'Keynote Daily Report');
		$mail->setSubject('Daily Report');
		$mail->setBodyHtml($str);
		$mail->send();
		//$mail->send($transport);

		echo "Report has been sent....!";
	}
}
