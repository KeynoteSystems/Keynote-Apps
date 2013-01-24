function getData(widgetId, type) {
	rndTimer = Math.floor(Math.random() * 5000 + 60000);

	var url = '/dashboard/widget/type/' + type;
	$.getJSON(url, function(json) {
		$('#widget-id-' + widgetId + ' .alias').text(json.name);
		$('#widget-id-' + widgetId + ' .perf').text(json.perf + json.symbol)
				.attr('style', 'color:' + json.color);
		$('#widget-id-' + widgetId + ' .previous').removeClass(
				'up-arrow down-arrow');
        $('#widget-id-' + widgetId + ' .sub-info').text('Last 15 minutes');

		$('#widget-id-' + widgetId + ' .previous').text(
				json.previous + json.symbol).addClass(json.arrow + '-arrow');
	});

	timer = setTimeout(function() {
		getData(widgetId, type);
	}, rndTimer);
	//console.log('Stat - ' + type + ': ' + rndTimer);
}

function wClock() {
	// Create two variable with the names of the months and days in an array
	var monthNames = [ "January", "February", "March", "April", "May", "June",
			"July", "August", "September", "October", "November", "December" ];
	var dayNames = [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday",
			"Friday", "Saturday" ];

	// Create a newDate() object
	var newDate = new Date();
	// Extract the current date from Date object
	newDate.setDate(newDate.getDate());
	// Output the day, date, month and year
	$('#Date').html(
			'<div style="color:#444749;">' + dayNames[newDate.getDay()]
					+ '</div><div>' + newDate.getDate() + ' '
					+ monthNames[newDate.getMonth()] + ' '
					+ newDate.getFullYear() + '</div>');

	/*
	 * 
	 * setInterval( function() { // Create a newDate() object and extract the
	 * seconds of the current time on the visitor's var seconds = new
	 * Date().getSeconds(); // Add a leading zero to seconds value
	 * $("#sec").html(( seconds < 10 ? "0" : "" ) + seconds); },1000);
	 */
	setInterval(function() {
		// Create a newDate() object and extract the minutes of the current time
		// on the visitor's
		var minutes = new Date().getMinutes();
		// Add a leading zero to the minutes value
		$("#min").html((minutes < 10 ? "0" : "") + minutes);
	}, 1000);

	setInterval(function() {
		// Create a newDate() object and extract the hours of the current time
		// on the visitor's
		var hours = new Date().getHours();
		var ap = "AM";
		if (hours > 11) {
			ap = "PM";
		}
		;
		if (hours > 12) {
			hours = hours - 12;
		}
		if (hours == 0) {
			hours = 12;
		}
		$("#sec").html('<span style="font-size: 30px">' + ap + '</span>');

		// Add a leading zero to the hours value
		$("#hours").html((hours < 10 ? "0" : "") + hours);
	}, 1000);
}

function createGraph(placeHolder, widgetId, slotId) {
	rndTimer = Math.floor(Math.random() * 5000 + 60000);
	$.getJSON('/dashboard/graph/slotid/' + slotId, function(json) {
		$.ajaxSetup({ cache: false });
		$('#' + widgetId).replaceWith(
				'<div class="widget-title graph-title" id="' + widgetId + '"><i class="icon-signal icon-white"></i> '
						+ json.title + '</div>');

		chart = new Highcharts.Chart(
				{
					chart : {
						//renderTo : 'chart',
						renderTo : placeHolder,
						type : 'area',
						reflow : true,
						backgroundColor : '#232526'
					},
					legend : false,
					title : {
						style : {
							color : '#ffffff'
						},
						text : false
					},
					xAxis : {
						type : 'datetime',
						labels : {
							formatter : function() {
								return Highcharts.dateFormat('%d %b %H:%M',
										this.value);
							}
						}
					},
					yAxis : {
						min : 0,
						title : {
							text : false
						}
					},
					credits : {
						enabled : false
					},
					tooltip : {
						formatter : function() {
							return '<b>'
									+ Highcharts.dateFormat('%a %d %b %H:%M',
											this.x) + '</b><br/>'
									+ this.series.name + ': ' + this.y + ' s';
						}
					},
					plotOptions : {
						series : {
							color : '#77AB13'
						}
					},
					series : [ {
						data : json.data
					} ]
				});
		//console.log('Graph: ' + rndTimer + ' / SlotId: ' + slotId + ' / WidgetId: ' + widgetId + ' / Title: ' + json.title );
	});
	
	timer = setTimeout(function() {
		createGraph(placeHolder, widgetId, slotId);
	}, rndTimer);
	
	
};
