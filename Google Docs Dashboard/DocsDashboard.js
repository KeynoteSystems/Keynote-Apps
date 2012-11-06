function KeynoteDashboard()
{
    //Base API URL request
    var api_url = "https://api.keynote.com/keynote/api/getdashboarddata";
 
    /* Change this to your API key */
    var api_key = "xxxxx";
 
    /*Build and issue the Request URL, starts with base URL, and then adds your key and any optional parameters*/
    var api_call = UrlFetchApp.fetch(api_url + "?api_key=" + api_key + "&format=xml&type=list").getContentText();
   
    //Parse the XML and place the spreadsheet into a container
    var xml = Xml.parse(api_call, true), sheetData = SpreadsheetApp.getActiveSpreadsheet();
   
    //Call the function to create the spreadsheet headers
    setSheetHeaders(sheetData);
    
    //Retrieves all the measurements blocks for TxP, which is the first group in the 'product' aray, thus the index of 0
    var measurements = xml.getElement().getElements("product")[0].getElements("measurement");
    
    //Counter to store which row to write data into, starting at row 2 since row 1 contains headers
    var counter = 2;
   
    //Loop through the measurement container and extract the attributes of interest like performance, availability, and slot alias
    for (var i in measurements)
    {
        var alias = measurements[i].alias.getText();
        var perf  = measurements[i].perf_data.getElements("data_cell"); //each piece of measurement data is stored in an XML tag called 'data_cell'
        var avail = measurements[i].avail_data.getElements("data_cell");
        var row   = [[alias, perf[0].getAttribute("value").getValue(),
                    perf[1].getAttribute("value").getValue(),
                    perf[2].getAttribute("value").getValue(),
                    perf[3].getAttribute("value").getValue()]];
       
        var row2  = [[avail[0].getAttribute("value").getValue(),
                    avail[1].getAttribute("value").getValue(),
                    avail[2].getAttribute("value").getValue(),
                    avail[3].getAttribute("value").getValue()]];
        
        //Input data into the spreadsheet
        sheetData.getRange("A"+counter+":E"+counter).setValues(row);
        sheetData.getRange("F"+counter+":I"+counter).setValues(row2);
     
        counter = counter +1;
    }
   
    sheetData.sort(2, false); //sorts spreadsheet in order of slowest last 5 minutes performance to fastest
}
function setSheetHeaders(sheetData)
{
	//Setup spreadsheet headers
    var headers = [["Slot Alias", "5 mins", "15 mins", "1 hr", "24 hrs", "5 mins", "15 mins", "1 hr", "24 hrs"]];  //puts these headers in the first row of the spreadsheet
    sheetData.getRange("A1:I1").setValues(headers);
}