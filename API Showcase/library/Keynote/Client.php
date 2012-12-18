<?php
class Keynote_Client
{
    /**
     *
     * Keynote Base URL
     * @var string
     */
    private $api_url = 'https://api.keynote.com/keynote/api/';

    /**
     *
     * Keynote API Key
     * @var string
     */
    public $api_key;

    /**
     *
     * Select either xml or json for data format
     * @var string
     */
    public $format = 'json';

    /**
     *
     * Perform the cURL reqest
     * @param string $request
     * @return array
     */
    protected function _getData($request)
    {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $start = $time;
        $ch = curl_init($request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);

        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $total_time = round(($finish - $start), 4);
        //echo '<script>console.log("' . $request . ' generated in '.$total_time.' seconds.")</script>';

        switch ($this->format) {
            case 'json':
                return json_decode($data);
                break;
            case 'xml':
                return new SimpleXMLElement($data);
                break;
        }
    }

    /**
     *
     * GetSlotMetaData - get slot details
     * @param string $login
     * @param string $usergroup
     * @param string $agreement
     * @param string $company
     * @return string
     */
    public function getSlotMetaData($login = null, $usergroup = null, $agreement = null, $company = null)
    {
        $request = $this->api_url . 'getslotmetadata' .
                   '?api_key='    . $this->api_key .
		           '&login='      . $login .
		           '&usergroup='  . $usergroup .
		           '&agreement='  . $agreement .
		           '&company='    . $company .
		           '&history=n'   .
				   '&format='     . $this->format;

        return $this->_getData($request);
    }

    /**
     *
     * GetDashBoardData - Dashboard request in list or grid format
     * @param string $type
     * @param string $format
     * @return string
     */
    public function getDashboardData($type = 'list')
    {
        $request = $this->api_url . 'getdashboarddata' .
                   '?api_key='    . $this->api_key .
		           '&type='       . $type .
		           '&format='     . $this->format;

        return $this->_getData($request);
    }

    /**
     *
     * GetAlarmMetaData - Get alarm details
     * @return string
     */
    public function getAlarmMetaData()
    {
        $request = $this->api_url . 'getalarmmetadata' .
                   '?api_key='    . $this->api_key .
		           '&format='     . $this->format;

        return $this->_getData($request);
    }

    /**
     *
     * GetGraphData
     * @param array $slotidlist
     * @param string $graphtype
     * @param string $timezone
     * @param string $timemode
     * @param int $relativehours
     * @param int $bucket
     * @param string $pagecomponent
     * return string
     */
    public function getGraphData($slotidlist, $graphtype='time', $timezone='GMT', $timemode='relative', $relativehours=86400, $bucket=1800, $pagecomponent = null, $averagemethod='AM')
    {
        $slots = implode(',', $slotidlist);

        $request = $this->api_url    . 'getgraphdata'.
                   '?api_key='       . $this->api_key .
                   '&slotidlist='    . $slots .
                   '&graphtype='     . $graphtype .
                   '&timezone='      . $timezone .
                   '&timemode='      . $timemode .
                   '&relativehours=' . $relativehours .
                   '&bucket='        . $bucket .
                   '&pagecomponent=' . $pagecomponent .
                   '&averagemethod=' . $averagemethod .
                   '&format='        . $this->format;

        return $this->_getData($request);

    }
}
