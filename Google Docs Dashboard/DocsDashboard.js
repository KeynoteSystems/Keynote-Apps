function KeynoteDashboard()
{
    /* Insert your API Key here*/
    var api_key = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
  
    var sheet = SpreadsheetApp.getActiveSpreadsheet();
  
    /* Setup spreadsheet headers */
    var headers = [['Slot Alias', '5 mins', '15 mins', '1 hr', '24 hrs', '5 mins', '15 mins', '1 hr', '24 hrs']];
    sheet.getRange('A1:I1').setValues(headers);

    /* Initialise counter */
    var row = 1;
    
    /* Base API URL request */
    var api_url = 'https://api.keynote.com/keynote/api/getdashboarddata';

    /* Build and issue the REST call to the API */
    var api_call = UrlFetchApp.fetch(api_url + '?api_key=' + api_key + '&format=xml').getContentText();
  
    /* Parse the returned XML */
    var xml = Xml.parse(api_call, true);
       
    /* Pull all products returned in the XML e.g. ApP, TxP etc. */    
    var prods = xml.getElement().getElements('product');
  
    /* Start loop through all products returned */
    for (var p in prods)
    {      
        /* Get product type */
        var type = prods[p].getAttribute('id').getValue();

        /* Loop through all measurements returned for current product */
        var measurements = prods[p].getElements('measurement');
    
        /* Extract from each measurement Slot Alias, Performance & Availability values */
        for (var i in measurements)
        {
            row++;

            var alias = measurements[i].alias.getText();
            var perf  = measurements[i].perf_data.getElements('data_cell');
            var avail = measurements[i].avail_data.getElements('data_cell');
            var rows   = [[alias + ' (' + type + ')',
                          perf[0].getAttribute('value').getValue(),
                          perf[1].getAttribute('value').getValue(),
                          perf[2].getAttribute('value').getValue(),
                          perf[3].getAttribute('value').getValue(),
                          avail[0].getAttribute('value').getValue(),
                          avail[1].getAttribute('value').getValue(),
                          avail[2].getAttribute('value').getValue(),
                          avail[3].getAttribute('value').getValue()]];
          
            /* Input data into spreadsheet */
            sheet.getRange('A' + row + ':I' + row).setValues(rows);
        }      
    }

    /* Sort data on second column.  Do not sort on first otherwise column headers will move */
    sheet.sort(2, false);
}