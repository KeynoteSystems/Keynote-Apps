<?php
/**
 * This file is located at <strong>library/Keynote/Client.php</strong>
 */

/**
 * The Keynote_Client class provides functions to access to the Keynote api methods.
 *
 * This class provides a convenient interface to obtain the Keynote measurement
 * data using the the Keynote RESTful API.
 *
 * The client connects to the API using an API Key that you provide and can be configured
 * to request data in either json, or xml formats; json is the default format.
 *
 * The code fragment below illustrates how to use this class:
 *
 * <code>
 * $api = new Keynote_Client();
 * $api->api_key = '{your_api_key}'; // must be set;
 * $api->format = '{json|xml}';      // if not set defaults to json
 * </code>
 *
 * The core of the class is the method `_getData` an internal curl library based method
 * that connects to the API, executes the required API method, and parses
 * the the response data.
 *
 * The public functions of the class correspond to the api methods and
 * provide a parameter list that can be used to set API parameters.
 * Each public function is simply responsible for creating the API request
 * based on the passed parameters and calling the _getData method.
 *
 * The class can be used as it quite easily but you can also as easily extend it
 * to add method based on your requirements.
 *
 *
 * For details the API methods refer to the API documentation.
 *
 * @link http://api.keynote.com/apiconsole/apistatus.aspx?page=docs
 * @package APIShowcase\Library
 * @author Robert Castley
 *
 */
class Keynote_Client
{
	/**
	 * Keynote API Base URL.
	 * @var string
	 */
	private $api_url = 'http://api.keynote.com/keynote/api/';

	/**
	 * The Keynote API Key to be used to access the API - client classes / programs must set this to
	 * a valid API Key before calling any of the API methods.
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * The format, <var>xml</var> or <var>json</var> that the API should use for the response,
	 * setting the default value to json.
	 *
	 * @var string
	 */
	public $format = 'json';
	
	public $basepageonly = false;

	/**
	 * Processes a request to the Keynote API and returns an array or xml object.
	 *
	 * This function uses the curl library to make a call to the URL passed in
	 * the request parameter. The response returned by the curl call is the
	 * parsed based on the format and converted into either an array or a
	 *
	 *
	 * @param string $request The request URL - must be a valid URL
	 * @return array a decoded json array or a PHP SimpleXML object as the case may be
	 */
	protected function _getData($method, $request)
	{
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		$url = $this->api_url . $method . '?api_key=' . $this->api_key . $request;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		// return the response string as a return value of curl_exec
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");			// request a gzip encoding
		curl_setopt($ch, CURLOPT_HEADER, 0);				// do not include a header in the response
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	// do not check status of the SSL license
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);		// number of seconds to wait for the connection
		//curl_setopt($ch, CURLOPT_VERBOSE, true);

		/*
		 * Set the time limit to wait for the response to 90 seconds
		 * so that long running queries can complete and the reset it back
		 * to 30 seconds (the default value).
		 */
		set_time_limit(90);
		$data = curl_exec($ch);
		set_time_limit(30);

		/*
		 * Calculate the time taken to obtain a response
		 */
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 4);

		/*
		 * Log the request URL and the time taken to process the response
		 */
		if ('development' == APPLICATION_ENV) {
			$logger = Zend_Registry::get('logger');
			$logger->log($url . ' generated in '.$total_time.' seconds.', 7);
		}

		/*
		 * Check and log the exception
		 */
		if (curl_errno($ch))
		{
			throw new Exception(curl_error($ch));
		}
		curl_close($ch);

		/**
		 * The response return by the API is processed to return an object that
		 * can be easily accessed by the rest of the application. Client functions
		 * therefore do not have to deal with parsing the response.
		 */
		switch ($this->format) {
			case 'json':
				$result = json_decode($data);  // construct and return an array from the json string
				if (isset($result->code)) {
					throw new Exception($result->message);
				} else {
					return $result;
				}
				break;
			case 'xml':
				$result = new SimpleXMLElement($data); // construct and return a SimpleXMLElement
				if (isset($result->code)) {
					throw new Exception($result->message);
				} else {
					return $result;
				}
				break;
		}

	}

	/**
	 * Uses the API to fetch details of active slots (no historical view).
	 *
	 * This method constructs the API call to get the Slot Details of all
	 * active slots associated with the login API key.
	 *
	 * @return array an XML Object or an array containing the data
	 */
	public function getActiveSlotMetaData($history = 'n')
	{
		/*
		 * Construct the API call to getslotmetadata.
		 *
		 * NOTE: The following code will work when no parameters are passed but does not
		 * seem to match the API spec for the case when parameters are passed. It is possible
		 * that the API does not require the scope parameter but infers it from the
		 * additional parameters
		 *
		 */
		$method = 'getslotmetadata';

		$request = '&history='    . $history .
    			'&format='     . $this->format;

		/*
		 * Calls the internal getData method and return the object returned by that function.
		 */
		return $this->_getData($method, $request);
	}

	/**
	 * Calls the API getSlotMetaData method and returns the retrieved slots details.
	 *
	 * This method constructs the API call to get the Slot Details based on the scope
	 * parameters passed to the function. The method does not retrieve any history.
	 *
	 * The method parameters are optional and if passed are used to set the
	 * scope of the SlotMetaData call as described below. Scopes can be combined if
	 * needed. The method uses the set API key and format for the request.
	 *
	 *
	 * @param string $login	Set the login scope to the login associated with the API Key by passing any non null value
	 * @param string $usergroup Pass the <b>name of user group</b> for limiting scope to the slots assigned to the group
	 * @param string $agreement Pass the <b>agreement id</b> for limiting scope to the slots assigned to the group
	 * @param string $company Pass a not null value to set the scope to the entire company
	 * @return array an XML Object or an array containing the data
	 */
	protected function getSlotMetaData($history = 'n', $login = null, $usergroup = null, $agreement = null, $company = null)
	{
		/*
		 * Construct the API call to getslotmetadata.
		 *
		 * NOTE: The following code will work when no parameters are passed but does not
		 * seem to match the API spec for the case when parameters are passed. It is possible
		 * that the API does not require the scope parameter but infers it from the
		 * additional parameters
		 *
		 */

		$method = 'getslotmetadata';

		$request = '&login='  . $login .
				'&usergroup=' . $usergroup .
				'&agreement=' . $agreement .
				'&company='   . $company .
				'&history='   . $history .
				'&format='    . $this->format;

		/*
		 * Calls the internal getData method and return the object returned by that function.
		 */
		return $this->_getData($method, $request);
	}

	/**
	 * Calls the API getdashboarddata method and returns an the retrieved list or grid data.
	 *
	 * This method constructs the API call to get dashboard data for either the
	 * list view or the dashboard view based on the value passed to the type parameter
	 * and the set API key and format
	 *
	 * Pass `list` to retrieve list view data and `grid` to retrieve grid view data.
	 * If no value is passed the default is list.
	 *
	 *
	 * @param string $type the type of view data to retrieve.
	 * @return array an XML Object or an array containing the data
	 */
	public function getDashboardData($type = 'list')
	{
		/*
		 * Construct the API call to getdashboarddata.
		 */

		$method = 'getdashboarddata';

		$request = '&type=' . $type .
				'&format='  . $this->format;

		/*
		 * Calls the internal getData method and return the object returned by that function.
		 */
		return $this->_getData($method, $request);
	}

	/**
	 * Calls the API getalarmmetadata method and returns the
	 * retrieved alarms details for a given user.
	 *
	 * This method constructs the API call to get alarm meta data using
	 * the set API key and format.
	 *
	 *
	 * @return array an XML Object or an array containing the data
	 */
	public function getAlarmMetaData()
	{
		$method = 'getalarmmetadata';

		$request = '&format=' . $this->format;

		return $this->_getData($method, $request);
	}

	/**
	 * Calls the API getgraphdata method using relative times and returns graphing data for a set of slots.
	 *
	 * This method constructs the API call to get graph data for the
	 * set of passed slot id. The type and other details of the data to be retrieved
	 * can be set by the other parameters. The ste API Key and the format are used to
	 * for the request.
	 *
	 * Note: This interface does not allow you to make an absolute time period request.
	 *
	 * @param array $slotidlist Slot Ids to retrieve graphs.
	 * @param string $graphtype Type of graph data to obtain.
	 * @param string $timezone Time Zone to use - defaults to GMT.
	 * @param string $timemode Whether the time period is <i>relative</i> or <i>absolute</i>, defaults to relative.
	 * @param int $relativehours The period to "look-back", in seconds.
	 * @param int $bucket The size of the data bucket, in seconds.
	 * @param string $pagecomponent A comma separated list of page components.
	 * @param string $averagemethod	The averaging method to use.
	 * @param array $transpagelist The list of transaction pages to fetch.
	 * @return array an XML Object or an array containing the data
	 */
	public function getGraphDataRelative($slotidlist, $graphtype='time', $timezone='GMT', $timemode='relative', $relativehours=86400, $bucket=1800, $pagecomponent = null, $averagemethod='AM', $transpagelist=null)
	{
		/*
		 * Convert the slotids in the array into a comma separated string as expected by the API.
		 */
		$slots = implode(',', $slotidlist);

		$method = 'getgraphdata';

		$request = '&slotidlist=' . $slots .
				'&graphtype='     . $graphtype .
				'&timezone='      . $timezone .
				'&timemode='      . $timemode .
				'&relativehours=' . $relativehours .
				'&bucket='        . $bucket .
				'&pagecomponent=' . $pagecomponent .
				'&averagemethod=' . $averagemethod .
				'&basepageonly='  . $this->basepageonly . 
				'&format='        . $this->format;

		/*
		 * If a transpagelist has been provided then add the transpagelist to the request.
		 * This list also needs to be converted into a comma separated list.
		 */
		if ($transpagelist != null) {
			$trans = implode(',', $transpagelist);
			$request .= '&transpagelist=' . $trans;
		}

		return $this->_getData($method, $request);

	}

	public function getGraphDataAbsolute($slotidlist, $graphtype='time', $timezone='GMT', $timemode='absolute', $absolutetimestart = null, $absolutetimeend = null, $bucket=1800, $pagecomponent = null, $averagemethod='AM', $transpagelist=null)
	{
		/*
		 * Convert the slotids in the array into a comma separated string as expected by the API.
		 */
		$slots = implode(',', $slotidlist);

		$method = 'getgraphdata';

		$request = '&slotidlist='     . $slots .
				'&graphtype='         . $graphtype .
				'&timezone='          . $timezone .
				'&timemode='          . $timemode .
				'&absolutetimestart=' . $absolutetimestart .
				'&absolutetimeend='   . $absolutetimeend .
				'&bucket='            . $bucket .
				'&pagecomponent='     . $pagecomponent .
				'&averagemethod='     . $averagemethod .
				'&format='            . $this->format;

		/*
		 * If a transpagelist has been provided then add the transpagelist to the request.
		 * This list also needs to be converted into a comma separated list.
		 */
		if ($transpagelist != null) {
			$trans = implode(',', $transpagelist);
			$request .= '&transpagelist=' . $trans;
		}

		return $this->_getData($method, $request);

	}
}
