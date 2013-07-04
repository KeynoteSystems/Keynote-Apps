<?php
/**
 * This file is located at <strong>application/controllers/DailyController.php</strong>.
 *
 */

/**
 * This Zend controller class implements actions that handle requests to
 * produce Daily Report for the requested Keynote product (perspectives) type.
 *
 * <h2>Features</h2>
 *
 * The Daily Report presents Measurement Data for the different measurement products
 * offered by Keynote Systems. The report showcases a few key statistics that are
 * captured for active slots over the last 28 days. It can easily be extended for
 * different measurements and time periods.
 *
 * <h3>Measurement Data</h3>
 * The measurement data is presented in four sections, each of which displays
 * measurement data associated with a slot level measurement component.
 *
 * * Performance: For ApP product slots, the `perf_data` value from the
 * <strong>Total Measurement Time [T]</strong> component, for all else the
 * `perf_data` value from the <strong>User Time [U]</strong> component.
 *
 * * Availability: For ApP product slots, the `avail_data` value from the
 * <strong>Total Measurement Time [T]</strong> component, for other products
 * `avail_data` value from the <strong>User Time [U]</strong> component.
 *
 * * Objects: The `perf_data` value from the <strong>Object Count [M]</strong> component.
 *
 * * Bytes Downloaded: The `perf_data` value from the <strong>Average Bytes Downloaded [Y]</strong> component.
 *
 * <h3>Reporting Period</h3>
 * The statistics are reported for buckets that contain the Geo Mean average:
 *
 * * Daily Averages of the last seven days, and
 * * Weekly Averages for the last four weeks.
 *
 * <h3>Critical / Warning Flags</h3>
 *
 * Displayed data is flagged as <strong>warning</strong> or <strong>critical</strong> by comparing it
 * to defined performance levels:
 *
 * * Performance Times: Variance from 28 Day (4 Week) average based on levels set by the views.
 * * Availability: Variance from percentages levels set in the `application.ini` configuration file.
 *
 * <h2>Using the Keynote API </h2>
 *
 * The Daily Report relies on two API methods:
 *
 * * The `getslotmetadata` method to get a list of all the active
 * slots for a product; and
 * * The `getgraphdata` to get reporting data for all the active slots.
 *
 * The controller does not call the Keynote API directly - the library class
 * {@link library.Keynote.Client.html#Keynote_Client library\Keynote\Client} hides
 * the complexity of making the actual call - but it is responsible for creating
 * the method requests and reading the returned data.
 *
 * The controller calls the API's `getslotmetadata` method to get a
 * list of active slots and then uses the `getgraphdata` method to
 * get the data needed for the report.
 *
 * Refer to the {@link http://api.keynote.com/apiconsole/apistatus.aspx?page=docs Keynote API Docs}
 * for full details of the API.
 *
 * <h3>getslotmetadata</h3>
 *
 * The call to retrieve all active slots associated with an API key takes the following form:
 *
 * <code>
 * http://api.keynote.com/keynote/api/getslotmetadata?api_key=[api_key]&amp;scope=history&amp;history=N
 * </code>
 *
 * The query parameters `scope=history` and `history=N` instructs
 * the API to exclude historical or expired slots. The API also provides additional parameters
 * that scope - see the API documentation for details.
 *
 * This method returns the slots for all products, grouped by product code; the slots associated
 * with the product will need to be filtered out from the complete slot list. The following is
 * an example fragment of the `json` response returned by this call.
 *
 * <code>
 * {
 * "product" : [
 *   {
 *     "name" : "TxP",
 *     "id" : "TxP",
 *     "slot" : [
 *     	 {
 *         "url" : "http://agentcomm.keynote.com/keepalive.htm",
 *         "pages" : "Keepalive page",
 *         "slot_alias" : "CTxP Keepalive Page_49228 (CTxP)[IE]",
 *         "slot_id" : 1126201,
 *         "shared_script_id" : -1,
 *         "agent_id" : 49228,
 *         "agent_name" : "CTxP-Running Demo",
 *         "target_id" : 1397915,
 *         "start_date" : "2012-08-03 00:00:00",
 *         "end_date" : "2013-08-03 00:00:00",
 *         "target_or_group" : 0,
 *         "target_type" : "TRANSACTION",
 *         "trans_type" : "Premium",
 *         "index_id" : -1
 *        },
 *        {
 *           --- details of next slot ---
 *        }
 *     ]
 *   },
 *   {
 *   	"name" : "",
 *   	"id" : "",
 *   	"slot" : [
 *   		 .... next array of slots ......
 *   	]
 *   }
 * }
 * </code>
 *
 * <h3>getgraphdata - method call</h3>
 *
 * The controller uses this API method to get the data needed to prepare the report. The
 * API method provides a number of query parameters to specify what data to return.
 *
 * The report displays two sets of values: daily averages for the last seven weeks and
 * weekly averages for the last seven weeks. This requires making two relative calls to the
 * `getgraphdata` using the `relativehours` and `bucket` query parameters to
 * the appropriate values.
 *
 * The method parameters used to obtain the data needed for the reports are documented
 * below.
 *
 * <h4>basic parameters</h4>
 *
 * 1. `api_key=[your API key]`
 * 	-	this is mandatory.
 * 2. `format=json`
 * 	- 	either json / xml, the `Keynote.Client` component currently only supports json
 *
 * <h4>controlling the type of data</h4>
 *
 * 1. `graphtype=time`
 * 	- 	returns time series data
 * 2. `timemode=relative`
 * 	-	since the report shows data relative to its run date
 * 3. `timezone=GMT`
 * 	-	used to set the date/time timezone - can be any valid timezone
 * 4. `pagecomponent=[U,Y,M]||[T,Y,M]`
 * 	-	the components reported on based on the product
 * 5. `averagemethod=GM`
 * 	-	data averaged using  Geometric Mean
 *
 * <h4>identifying the data needed</h4>
 *
 * 1. `slotidlist=[comma separated list of slot ids]`
 * 	- 	as obtained from the `getslotmetadata` call.
 * 2. `relativehours=604800|419200`
 * 	-	time period in seconds for which to get data.
 * 	-	-		604800 = 7 days (daily data)
 * 	-	-		419200 = 28 days (weekly data)
 * 3. `bucket=86400|604800`
 * 	-	length of each data point in seconds.
 * 	-	-		86400 = 24 hrs (daily) buckets for weekly data and
 *  -	-	   604800 = 7 days (weekly) buckets for 28 day data
 *
 *
 * <code>
 * https://api.keynote.com/keynote/api/getgraphdata?api_key=[your_api_key]&amp;[additional parameters as shown above]
 * </code>
 *
 * <h3>getgraphdata - result processing</h3>
 *
 * The data returned by the `getgraphdata` method contains the following sections:
 *
 * 1. `graph_property`
 * 	-	metadata that describes the measurement data as name/value pairs. Ignored by the controller
 * 2. `measurement`
 * 	-	the measurement data requested; this is the data used by the report and is described in detail below.
 * 3. `link`
 * 	-	metadata about the request; returned by all the API calls.
 *
 * The measurement section is a slot and page component array of data readings corresponding to the requested
 * time period (relativehours) and bucket size. For example a request on slots numbers S1, S2 and S2
 * for page components U,Y,M would contain <b>9</b> measurement sections (S1-U,S1-Y,S1-M,S2-U,S2-Y,S2-M,S13-U,S3-Y,S3-M).
 *
 * The following is an example json fragment of the some of the key elements of the measurement data
 *
 * <code>
 * 	{
 *  	"graph_property" : [
 *  		// name value pairs of graph properties
 *  	],
 *  	"measurement" : [
 *  		{
 *  			"id" : "1126201",
 *  			"alias" : "CTxP Keepalive Page_49228 (CTxP)[IE] - Total Time (seconds)[Geo Mean]",
 *  			"bucket_data" : [
 *  			 {
 *  			  "name" : "2013-APR-15 09:59 AM",
 *		          "id" : 1,
 *		          "perf_data" : {
 *		            "value" : "0.200",
 *		            "unit" : "seconds"
 *		          },
 *		          "avail_data" : {
 *		            "value" : "99.43",
 *		            "unit" : "percent"
 *		          },
 *		          "data_count" : {
 *		            "value" : "3529",
 *		            "unit" : "#"
 *		          }
 * 					---- other buckets ----
 * 				  {
 * 					--- last bucket ---
 * 				  }
 *  			],
 *  			"graph_option" : [
 *  				// various graph option values including the following
 *          		{
 *  			        "name" : "pagecomponent",
 *         				"value" : "Average Bytes Downloaded",
 *         				"unit" : ""
 *       			},
 *	         		{
 *         				"name" : "avg_perf",
 *        				"value" : "2444.125",
 *       				"unit" : ""
 *    				},
 *   				{
 *    					"name" : "avg_avail",
 *      				"value" : "99.35",
 *         				"unit" : "percent"
 *       			},
 *  				--- other elements ---
 *  			]
 *  		},
 *  		{
 *  			--- data for the next slot ---
 *  		}
 *  	],
 *  	"link" : {
 *  		// request meta data
 *  	}
 * </code>
 *
 * The controller uses the data returned as follows:
 *
 * 1. The measurement `alias` is parsed to determine the page component. <i>The pagecomponent
 * element in the graph_option section can also be used and is preferable.</i>
 * 2. The `bucket_data` section provides the values used to create the report.
 * 3. The `avg_perf` element in graph_option section returned in the Weekly average request
 * is the 28 average used to set the critical/warning ranges.
 *
 * <h2>Controller Interfaces</h2>
 *
 * The controller provides action interfaces for:
 *
 * * Preparing a richly formatted report that displays within the main application display container
 * * Preparing a lightly formatted 'printer-friendly' style report
 * * Emails the 'printer-friendly' report to an address 'hard-coded' in this controller
 *
 * These actions are available through the `index`, `createmail`,
 * and `sendmail` actions respectively.
 *
 * <b>Invoking the controller</b>
 *
 * This controller is invoked using a standard HTTP request for the `controller_action`.
 * The request must also contain a `product` parameter as follows
 *
 * * /daily/{controller_action}/product/{product_code}
 *
 * where
 *
 * * {controller_action} is one of the implemented action: `index`, `createmail`, `sendmail`
 * and
 *
 * * {product_code} is one of the API supported product codes:
 * 			<strong>MDP</strong>, <strong>STR</strong>, <strong>LastMile</strong>, <strong>CApP</strong>,
 * 			<strong>MWP</strong>, <strong>ApP</strong>, or <strong>TxP</strong>
 *
 * A `NO SLOT RUNNING EXCEPTION`' is thrown if the request does not contain the product parameter
 *
 * <b>Report Design</b>
 *
 * The controller has a simple design that is implemented as follows:
 *
 * 1. The standard zend_controller init() function makes the API calls to get the data.
 *    As this function is invoked for all actions, the same data is available to all actions.
 *
 * 2. The init() function parses the data returned by the API and stores it in a convenient to use array.
 *
 * 3. To improve performance the data returned by the API is stored in a Zend Cache
 *
 * <b>Limitations</b>
 * <blockquote>
 * The class provides examples of how to use the Keynote API and is not intended for use in
 * a production environment. It has the following known issues:
 * </blockquote>
 * 1. Displays the default Zend Controller error if the product parameter is not provided
 * 2. Displays the default Zend Controller error if the an unsupported product code is passed
 * 3. The `createMail` and `sendMail` are debugging functions and will need
 *    to be modified before they can be used
 *
 * @link http://api.keynote.com/apiconsole/apistatus.aspx?page=docs
 * @package APIShowcase\Report
 * @author Robert Castley
 *
 */

class DailyController extends Zend_Controller_Action
{
	/**
	 * Accessor variable for data stored in the applications Zend Session Namespace. For
	 * details on how session data is managed and a basics of Zend_Sessions:
	 *
	 * @link https://github.com/KeynoteSystems/Keynote-Apps/wiki/API-Showcase-Technical-Docs
	 *
	 * @var array
	 */
	private $_session = array();


	/**
	 *
	 * Accessor variable for the Zend_Cache used to store slot and graph data.For
	 * details on data cached by the application and basics of Zend_Cache:
	 *
	 * @link https://github.com/KeynoteSystems/Keynote-Apps/wiki/API-Showcase-Technical-Docs
	 *
	 * @link http://framework.zend.com/manual/1.12/en/zend.cache.introduction.html
	 *
	 * @var unknown
	 */
	private $_cache;

	/**
	 * Initializes the DailyController.
	 *
	 * The function makes API calls to get report data; parses the returned data
	 * and stores it into an array that is passed to the views. A zend_cache is
	 * used to reduce the time needed to process the report by caching the API response.
	 *
	 *
	 * <b>Processing</b>
	 *
	 * The process of producing the report follows the following steps:
	 *
	 * 1. Initialize / Fetch the zend_cache; the lifetime of the cache is set
	 *    to 2 hours (86400 seconds).
	 * 2. Read the product code passed as a parameter to the controller.
	 * 3. Retrieve all the product slots associated with the API key being used by making
	 *    the `getslotmetadata` API call. This information is cached
	 *    to reduce the overhead of running reports.
	 * 4. Find active slots that match the passed product code (ignoring the default Keynote Business 40 index
	 *    slot 1091870).
	 * 5. Retrieve performance data for the matching slots by making the `getdashboarddata`.
	 *    This data is also cached in the `Zend_Cache`.
	 * 6. Parse the API response and extract the attributes needed into a multidimensional array
	 * 7. Obtain the report configuration data from application configuration file
	 * 8. Pass the data array and the report configuration to the view.
	 *
	 * @throws Exception If the request does not contain a `product` parameter
	 */
	public function init()
	{
		/**
		 * Setups the Frontend options for the cache.
		 *
		 * @link http://framework.zend.com/manual/1.12/en/zend.cache.frontends.html
		 */
		$frontendOptions = array(
				'lifetime' => 86400, // cache lifetime of 2 hours
				'automatic_serialization' => false
		);

		/**
		 * Setups the Backend options for the cache
		 *
		 * @link http://framework.zend.com/manual/1.12/en/zend.cache.backends.html
		 */
		$backendOptions = array(
				'cache_dir' => '../data/cache' // Directory where to put the cache files
		);

		/**
		 * Requests a Zend_Cache object from the framework - creates the cache the first time
		 * it is requested and gets it subsequently.
		 */
		$this->_cache = Zend_Cache::factory('Page',
				'File',
		$frontendOptions,
		$backendOptions);

		/**
		 * Get the config object from the Zend Registry - this object is added to the registry
		 * during bootstrap and contains keynote.* entries from application.ini file.
		 * See the file application/Bootstrap.php to see how this object is set.
		 */
		$config = Zend_Registry::get('config');


		/**
		 * Creates or gets the session namespace. 'DASHBOARD' is the name of this application.
		 */
		$this->_session = new Zend_Session_Namespace('DASHBOARD');

		/**
		 * Create a new instance of the Keynote_Client.
		 */
		$api = new Keynote_Client();

		/**
		 * Check is the session holds an API key - If it does set the client's public member api_key
		 * to this value otherwise redirect to the application's 'index' page which provides the
		 * UI for entering the key.
		 *
		 * Note: the user will need to re-invoke the report from the menu after entering the key/
		 */
		if ($this->_session->apiKey) {
			$api->api_key = $this->_session->apiKey;
		} else {
			$this->_redirect('index');
		}

		/**
		 * Load previously retrieved slotmetadata from the cache, if the cache is empty
		 * (never set or timed out) then fetch the data by invoking the API and cache the result.
		 */
		if (($slotData = $this->_cache->load('slotData_' . Zend_Session::getId())) === false ) {

			$slotData = $api->getActiveSlotMetaData();

			$this->_cache->save($slotData, 'slotData_' . Zend_Session::getId());
		}

		/**
		 *  Get the value of the 'product' parameter from the Http request object
		 */
		$product = $this->_request->getParam('product');

		/**
		 * create a variable that holds the slot ids to be reported
		 */
		$slotIds = array();

		/**
		 * Read the slotmetadata by looping through the response,
		 * and finding all the slots that match the passed product.
		 *
		 * slotmetadata has the following structure (only relevant fields
		 * shown below - see API documentation for details).
		 *
		 * 	- product
		 * 		- id
		 * 		- slot
		 * 			- slot_id
		 * 			- end_date
		 * 			- pages
		 */

		foreach ($slotData->product as $a) {
			if ($a->name == $product) {
				foreach ($a->slot as $b) {

					if ($b->slot_id != 1091870 && $b->slot_id != 508374) {   			// ignore the slot 1091970, the slot for Keynote Business 40 index

						$slotIds[] = $b->slot_id; 			// add this slot_id to the array of selected slotIds

					}
				}
			}

		}

		/**
		 * Throw an exception is the slotIds array is empty:
		 * This condition can occur if
		 *
		 * * There are no slot IDs to report.
		 * * A PRODUCT parameter was not passed
		 */
		if (empty($slotIds)) {
			throw new Exception('No ' . $product . ' slots running!');
		}

		/**
		 * Set the default page components to be requested to User Time, Bytes Downloaded & Object Counts
		 * See API documentation for list of available components and their codes
		 */
		$pageComponents = 'U,Y,M';

		$this->view->vPageCompoments = 'User Time'; // Set the View Page Component Label

		switch ($product) {
			/**
			 * The ApP product report is configured differently from the other products.
			 * The key measurement is Total Time and not User Time.
			 */
			case 'ApP':
				$pageComponents = 'T,Y,M';
				$this->view->vPageCompoments = 'Total Time';
				break;
		}

		$this->view->currentDay = date('Y-m-d');  // set the View objects currentDay


		/**
		 * Load previously retrieved graph data from the cache, if the cache is empty
		 * (never set or timed out) then fetch the data by invoking the API and cache the result.
		 */
		if (($week = $this->_cache->load($product . '_week_'  . Zend_Session::getId())) === false ) {

			/**
			 * Get last seven days daily average data for the selected slots
			 * graphtype 		: 'time'; 			data for a timeseries graph
			 * timezone 		:  $config['graph']['timezone']; value of keynote.graph.timezone in application.ini
			 * timemode 		: 'relative';  		from todays date
			 * relativehours 	: 604800; 			in seconds -  7 days
			 * bucket 			:  86400; 			in seconds - 24 hrs
			 * pageComponents 	: $pageComponents; 	as set above
			 * averageMethod	: 'GM';				geometric mean
			 * transpagelist	: $slotIds; 		the transaction page list build from the slots
			 */

			$week = $api->getGraphData($slotIds, 'time', $config['graph']['timezone'], 'relative', 604800, 86400, $pageComponents, 'GM');

			$this->_cache->save($week, $product . '_week_'  . Zend_Session::getId());
		}

		/**
		 * Load previously retrieved graph data from the cache, if the cache is empty
		 * (never set or timed out) then fetch the data by invoking the API and cache the result.
		 */
		if (($month = $this->_cache->load($product . '_month_' . Zend_Session::getId())) === false ) {

			/**
			 * Get last fours weeks daily average data for the selected slots
			 * graphtype 		: 'time'; 			data for a timeseries graph
			 * timezone 		:  $config['graph']['timezone']; value of keynote.graph.timezone in application.ini
			 * timemode 		: 'relative';  		from todays date
			 * relativehours 	: 2419200;			in seconds - 28 days
			 * bucket 			:  604800;			in seconds -  7 days
			 * pageComponents 	: $pageComponents; 	as set above
			 * averageMethod	: 'GM';				geometric mean
			 * transpagelist	: $slotIds; 		the transaction page list build from the slots
			 */

			$month = $api->getGraphData($slotIds, 'time', $config['graph']['timezone'], 'relative', 2419200, 604800, $pageComponents, 'GM');

			$this->_cache->save($month, $product . '_month_'  . Zend_Session::getId());
		}

		/**
		 * Extract the values from the 'measurement' section of the xml/json returned by the API.
		 * This is the daily averages for the last seven days.
		 */
		foreach ($week->measurement as $wm) {
			/**
			 * Read the Alias attribute of the Measurement element/object
			 * The Measurement alias value has the following structure:
			 *		{slot alias} - {page component}[{average method}]
			 * for example for:
			 * 		Slot Alias = Keynote Business 40 TxP,
			 * 		Page Component = U - User Time
			 * 		Average Method = Geometric Mean
			 * measurement alias is:
			 *	 	Keynote Business 40 TxP - User Time (Seconds)[Geo Mean]
			 */
			$alias = $wm->alias;

			/**
			 * Create an array to hold the performance data indexed on the measurement alias.
			 *
			 * perfData is a multi-dimensional array with the following structure:
			 *
			 * * [Alias][Measurement][Values]
			 *
			 * where:
			 *
			 * * alias: The First index of the array is the alias returned by the API call
			 * * measurement: is a numeric index corresponding to the 7 daily values and 3 weekly values
			 * * values: an array of values corresponding to each measurement
			 *
			 * For a TxP report for a slot named "Slot 1", with page components U,Y and M the array will be as follows:
			 *
			 * Array(3)
			 * (
			 * 		[Slot 1 - User Time - GeoMean]
			 * 			=> Array(11) // contains 7 daily and 4 weekly values
			 * 				(
			 * 					[0] => Array(2)( [perf]=> 10, [avail]=98),  // values for day 1
			 * 					[1] =  Array(2)( [perf]=> 9.9, [avail]=98), // values for day 2
			 * 					... // 4 more daily values
			 * 					[6] =  Array(2)( [perf]=> 9.8, [avail]=98), // values for day 7
			 *
			 * 					[7] => Array(2)( [perf]=> 9.7, [avail]=98),  // values for week 1
			 * 					.. // 2 more weekly values
			 * 					[10] => Array(2)( [perf]=> 9.7, [avail]=98),  // values for week 4
			 * 				),
			 *
			 * 		[Slot 1 - Average Bytes Downloaded - GeoMean] =
			 * 			=> Array(11) // contains 7 daily and 4 weekly values
			 * 				(
			 * 					[0] => Array(2)( [bytes]=> 123456),  // values for day 1
			 * 					[1] =  Array(2)( [bytes]=> 123457), // values for day 2
			 * 					... // 4 more daily values
			 * 					[6] =  Array(2)( [bytes]=> 123450), // values for day 7
			 *
			 * 					[7] => Array(2)( [bytes]=> 123458),  // values for week 1
			 * 					.. // 2 more weekly values
			 * 					[10] => Array(2)([bytes]=> 123459),  // values for week 4
			 * 				),
			 *
			 * 		[Slot 1 - Object Count - GeoMean] =
			 * 			=> Array(11) // contains 7 daily and 4 weekly values
			 * 				(
			 * 					[0] => Array(2)( [objects]=> 123),  // values for day 1
			 * 					[1] =  Array(2)( [objects]=> 124), // values for day 2
			 * 					... // 4 more daily values
			 * 					[6] =  Array(2)( [objects]=> 125), // values for day 7
			 *
			 * 					[7] => Array(2)( [objects]=> 126),  // values for week 1
			 * 					.. // 2 more weekly values
			 * 					[10] => Array(2)([objects]=> 128),  // values for week 4
			 * 				),
			 * )
			 *
			 */
			$perfData[$alias] = array();  // set the index value to a new array, this array will hold a stacked array of values as above

			/**
			 * The primary statistic presented in the Daily Reports is the slot's performance.
			 * 		For Application Perspective report this is contained in the pagecomponent 'Total Time'
			 * 		For all other perspectives this is contained in the  'User Time' page component.
			 * Check is this measurement alias the required performance measurement and if so
			 * extract data from the buckets
			 */
			if (strstr($wm->alias, 'Total Time') || strstr($wm->alias, 'User Time')) {
				/**
				 * loop through each bucket_data object/element and extract the performance and availability values:
				 * 		User Time/Total Time Performance value:: bucket_data.perf_data.value
				 *		User Time/Total Time Availability value:: bucket_data.avail_data.value
				 * note: The following measurement attributes are ignored:
				 *		1. bucket_data.name: 	This contains the date/time of the bucket;
				 *								The UI sets the date based on the run date
				 * 		1. unit:				Each measurement has standard units
				 *								The UI assumes this
				 */
				foreach ($wm->bucket_data as $wb) {
					// check if there is value in the slot and format the value as a number
					if ($wb->perf_data->value != '-' || $wb->avail_data->value != '-') {
						// format perf_data with 2 decimals places '.' decimal separator and no thousand separator
						$pValue = number_format($wb->perf_data->value, 2, '.', '');
						// format avail_data with 1 decimals places '.' decimal separator and no thousand separator
						$aValue = number_format($wb->avail_data->value, 1, '.', '');
					} else {
						$pValue = $wb->perf_data->value;
						$aValue = $wb->avail_data->value;
					}
					/**
					 * Stack the performance and availability values onto the perfData array
					 * The API returns the oldest value first and stacking arranges the data
					 * so that the most recent is on top
					 */
					array_push($perfData[$alias], array('perf' => $pValue, 'avail' => $aValue));
				}
			}

			/**
			 * This section extracts the Object Count values in a manner similar
			 * to how the preceding section extracts the 'Total Time'/'User Time' values
			 */
			if (strstr($wm->alias, 'Object Count')) {
				// loop through the object count measurement and extract the perf_data.value
				foreach ($wm->bucket_data as $wb) {
					if ($wb->perf_data->value != '-' || $wb->avail_data->value != '-') {
						// format with a single decimal place, '.' decimal symbol and no thousand separator
						$pValue = number_format($wb->perf_data->value, 1, '.', '');
					} else {
						$pValue = $wb->perf_data->value;
					}
					// stack the objects
					array_push($perfData[$alias], array('objects' => $pValue));
				}
			}

			/**
			 * This section extracts the Average Byte values in a manner similar
			 * to how the 'Total Time'/'User Time' values are extracted above
			 */
			if (strstr($wm->alias, 'Average Bytes')) {
				// loop through the object count measurement and extract the perf_data.value
				foreach ($wm->bucket_data as $wb) {
					if ($wb->perf_data->value != '-' || $wb->avail_data->value != '-') {
						$pValue = $wb->perf_data->value;
					} else {
						$pValue = $wb->perf_data->value;
					}
					// stack the objects
					array_push($perfData[$alias], array('bytes' => $pValue));
				}
			}

		}

		/**
		 * Extract the data from the weekly `getgraphdata` response.
		 *
		 * The API request was for for weekly buckets for 28 days - this means
		 * that the response should contain four buckets and each bucket should
		 * contain the average value (GEO MEAN, as requested) for one week.
		 *
		 * The processing of this section is similar to the processing of
		 * the AVERAGE DAILY values described in detail above, with one extra
		 * step as described below.
		 *
		 * The avg_perf section is read to extract the 28 day average used
		 * to set the critical/warning ranges.
		 *
		 */
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

			// Loop through the graph option section and read the value of the avg_perf element
			foreach ($mm->graph_option as $gOpt) {
				if (isset ($gOpt->name)) {
					if (strstr($gOpt->name, 'avg_perf') ) {
						// the performance average for the alias to the value element
						$perfAverage[$alias] = $gOpt->value;
					}
				}
			}

		}

		// Set the view variables

		// Set the perfData variable to the perfData array that now contains the values extracted from the API response
		$this->view->perfData = $perfData;

		// Set the availability WARNING threshold to the value set in the applicaiton.ini file
		$this->view->avail_warn = $config['availability']['warning'];
		// Set the availability CRITICAL threshold to the value set in the applicaiton.ini file
		$this->view->avail_crit = $config['availability']['critical'];

		// Set the 28 days performance average array
		$this->view->perfAverage = $perfAverage;
	}

	/**
	 * Index Action Handler that handles the request /daily/index/product/{product code}.
	 *
	 * Empty handler for the index action - the report is processed by the init() function above
	 */
	public function indexAction()
	{

		$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();

		$this->_session->url = $url;
	}
}
