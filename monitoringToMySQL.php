<?php
//******************************************************************************************************************************************************
//
// Run this code from the command line (or cron):  monitoringToMYSQL.March2016.php user password account days
//	user: 		the siteconfidence user
//	password: 	the siteconfidence user's password
//	account: 	if the siteconfidence account has more than one monitoring account then you can specify (default is all)
//	days: 		how many days of data do you want go back for (default is today), for example 7 would store the previous seven days worth of data
//
// Set the database user in the runquery() function.
// First run through will create the database and tables - if the database user has got CREATE privileges.
// Suggest a user with lesser privileges used for BAU
//
//******************************************************************************************************************************************************

//Process command line parameters
if (count($argv) > 2 )
 {

	//we are not checking the vailidity of the passport, though the api call will fail if incorrect
	$username = $argv[1];
        $password = $argv[2];

        //get today's date
	$today = date("Y-m-d");

	//initially set the default data range
	$dateRange = $today;

	//if you have only three parameters, we need to work out which filter is in play
	if (count($argv) == 4 )
	{

		//if the third parm is a number then we'll guess it is the days filter
		if (is_numeric($argv[3]))
		{
			//overwrite the range start date by taking the days parameter - 1 day from today
			$dateRange = date("Y-m-d", strtotime("-".($argv[3]-1)." day", time()));

		}
		//not a number, then this must be an account
		else
		{

	    		$AccountName = $argv[3];

		}

	}

	//We have both an account filter and days, set both
	if (count($argv) == 5 )
	{

	    	//We have 5 parms so save the account filter first
		$AccountName = $argv[3];

		//get the range start date by taking the days parameter - 1 day from today 
		$dateRange = date("Y-m-d", strtotime("-".($argv[4]-1)." day", time()));

	}

	//remember the start so we can reset as we loop
	$dateRangeStart = $dateRange;

	//get the api key
	RequestAPIData('apiKey');

        //store the monitors in this account
	RequestAPIData('accounts');

        //store the monitoring metadata
	foreach ($Account As $Monitor)
        {
             RequestAPIData('monitors', $Monitor["AccountId"]);
        }

        //store the object level data for page monitor tests
	foreach ($PageMonitors As $PageM)
        {

		$dateRange = $dateRangeStart;

		//we need to combat the api timeouts by breaking the data collection into hour units
		while (strtotime($dateRange) <= strtotime($today))
		{

			$hourIndex = 0;

			while ($hourIndex < 24)
			{

				RequestAPIData('PageObjects', $PageM);


				$hourIndex++;

			}


			//add a day to the start daterange
			$dateRange = date('Y-m-d', strtotime($dateRange." +1 days"));


		}

        }

        //get the object level data for the UJ monitor tests
	foreach ($Journeys As $JourneyPageM)
        {

		$dateRange = $dateRangeStart;

		//we need to combat the api timeouts by breaking the data collection into hour units
		while (strtotime($dateRange) <= strtotime($today))
		{

			$hourIndex = 0;

			while ($hourIndex < 24)
			{

            			 RequestAPIData('UJObjects', $JourneyPageM);


				$hourIndex++;

			}


			//add a day to the daterange
			$dateRange = date('Y-m-d', strtotime($dateRange." +1 days"));


		}

        }


 }
 //we need at least, an account name and password
 else
 {

    echo "Please enter your Account and Password \n";
    exit();

}

// function to get the data - defaults to getting the monitors
function RequestAPIData($requestType, $monitorID = null)
{
        global $apiKey, $username, $password, $dateRange, $hourIndex;

	//set up the curl request dependent on the type
	if ($requestType == "apiKey")
	{

            //create array of data to be posted
            $post_data['username'] = urlencode($username);
            $post_data['password'] = urlencode($password);
            $post_data['Format'] = 'JSON';

            //traverse array and prepare data for posting (key1=value1)
            foreach ( $post_data as $key => $value)
            {
    		$post_items[] = $key . '=' . $value;
            }

            //create the final string to be posted using implode()
            $post_string = implode ('&', $post_items);

   	   //create cURL connection
            $curl_connection = curl_init('https://api.siteconfidence.co.uk/current/auth');

            //set cURL options
            curl_setopt($curl_connection, CURLOPT_POST, true);
            curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

	}
	elseif ($requestType == "accounts")
	{

            // setup curl
            $curl_connection = curl_init("https://api.siteconfidence.co.uk/current/".$apiKey."/Format/json");

            //set cURL options
            curl_setopt($curl_connection, CURLOPT_POST, false);

	}
        elseif  ($requestType == "monitors")
	{

		// setup curl
		$curl_connection = curl_init("https://api.siteconfidence.co.uk/current/".$apiKey."/AccountId/".$monitorID."/Format/json");

            //set cURL options
            curl_setopt($curl_connection, CURLOPT_POST, false);

	}
	elseif  ($requestType == "PageObjects")
	{

            // setup curl
            $curl_connection = curl_init("https://api.siteconfidence.co.uk/current/".$apiKey."/Return/[Account[Pages[Page[TestResults[TestResult[Id,ResultId,TestType,TestServer,TestServerIp,GmtDateTime,GmtTimestamp,UncompressedBytes,DnsSeconds,ConnectSeconds,DataStartSeconds,TotalSeconds,StatusCode,Status,ResultCode,Result,TestResultDetails[ResultDetail[ObjectNo,ObjectUrl,DnsSeconds,ConnectSeconds,SslSeconds,RequestSeconds,RequestHeaderBytes,RequestContentBytes,DataStartSeconds,ResponseHeaderBytes,TransferredBytes,UncompressedBytes,ContentSeconds,TotalSeconds,GzipSavingPercentage,StatusCode,Offset]]]]]]]]".$monitorID."StartDate/".$dateRange."/StartTime/".str_pad($hourIndex, 2, "0", STR_PAD_LEFT).":00:00/EndDate/".$dateRange."/EndTime/".str_pad($hourIndex+1, 2, "0", STR_PAD_LEFT).":59:59/LimitTestResults/7000/Format/json");

            //set cURL options
            curl_setopt($curl_connection, CURLOPT_POST, false);

	    //Wait for 10 minutes before timing out
	    curl_setopt($curl_connection, CURLOPT_TIMEOUT, 3600); //timeout in seconds

	}
	elseif  ($requestType == "UJObjects")
	{

            // setup curl
	    $curl_connection = curl_init("https://api.siteconfidence.co.uk/current/".$apiKey."/Return/[Account[UserJourneys[UserJourney[TestResults,Steps[Step[Id,ResultId,TestType,TestServer,TestServerIp,GmtDateTime,GmtTimestamp,UncompressedBytes,DnsSeconds,ConnectSeconds,DataStartSeconds,TotalSeconds,StatusCode,Status,ResultCode,Result,TestResults[TestResult[Id,RunID,ResultId,TestType,TestServer,TestServerIp,LocalDateTime,GmtDateTime,LocalTimestamp,DataStartSeconds,TotalSeconds,StatusCode,Status,ResultCode,Result,TestResultDetails[ResultDetail[ObjectNo,ObjectUrl,DnsSeconds,ConnectSeconds,SslSeconds,RequestSeconds,RequestHeaderBytes,RequestContentBytes,DataStartSeconds,ResponseHeaderBytes,TransferredBytes,UncompressedBytes,ContentSeconds,TotalSeconds,GzipSavingPercentage,StatusCode,Offset]]]]]]]]]]".$monitorID."StartDate/".$dateRange."/StartTime/".str_pad($hourIndex, 2, "0", STR_PAD_LEFT).":00:00/EndDate/".$dateRange."/EndTime/".str_pad($hourIndex+1, 2, "0",STR_PAD_LEFT).":59:59/LimitTestResults/7000/Format/json");

            //set cURL options
            curl_setopt($curl_connection, CURLOPT_POST, false);

	    //Wait for 10 minutes before timing out
	    curl_setopt($curl_connection, CURLOPT_TIMEOUT, 3600); //timeout in seconds

	}

	//set options
	curl_setopt($curl_connection, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_connection, CURLOPT_SSLVERSION,5);
	curl_setopt($curl_connection, CURLOPT_SSL_CIPHER_LIST,'SSLv3');
	curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl_connection, CURLOPT_SSL_VERIFYHOST,  2);
	curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);

	//perform cURL request
	$result = curl_exec($curl_connection);

	//close the connection
	curl_close($curl_connection);

        parseResponse($requestType, $result);

} // end function RequestAPIData

function parseResponse($requestType, $result)
{

        global $apiKey,$Account,$PageMonitors,$Journeys, $dbc, $AccountName;

	$jsonResult = json_decode($result, false);

        //parse the response from the ap according to type
	if ($requestType == "apiKey")
	{

            // extract and return the api key
            $apiKey = (string)$jsonResult->Response->ApiKey->Value;


	}
	elseif ($requestType == "accounts")
	{

		// save the monitoring accountid and name
		foreach ($jsonResult->Response->Account as $account)
		{

			//If an account name was offered on the command line - use it here
			if(isset($AccountName))
			{
				if($account->Name == $AccountName)
				{
					$Account[] = ['AccountId' => (string)$account->AccountId];
				}

			}
			else
			{

				$Account[] = ['AccountId' => (string)$account->AccountId];
			}

			$AccountKey = runquery("INSERT IGNORE INTO `Monitors`(`MonitoringAccountiD`,`MonitorName`) VALUES ('".(string)$account->AccountId."','".(string)$account->Name."')");
			runquery("UPDATE  `Monitors` set `StartDate` = now() where `idMonitors` = '".$AccountKey ."' and `StartDate` is null");

		}


	}
	elseif  ($requestType == "monitors")
	{

                if(isset($jsonResult->Response->Account->Pages))
		{

                     // save the monitoring accountid and name
   		     foreach ($jsonResult->Response->Account->Pages->Page as $Page)
		     {

			if(isset($Page->Id))
			{

				$PageMonitors[] = "/AccountId/".(string)$jsonResult->Response->Account->AccountId."/Id/".(string)$Page->Id."/";


        	                $PageKey = runquery("INSERT IGNORE INTO `Pages` (`Monitors_idMonitors`,`PageiD`,`PageURL`,`PageLabel`) SELECT `idMonitors`,'".(string)$Page->Id."','".mysqli_real_escape_string($dbc, (string)$Page->Url)."','".(string)$Page->Label."' FROM `Monitors` where `MonitoringAccountiD` = '".(string)$jsonResult->Response->Account->AccountId."'");
	        	        runquery("UPDATE `Pages` set `StartDate` = now() where  idPages = '".$PageKey."' and `StartDate` is null");

			}

		    }
                }

                if(isset($jsonResult->Response->Account->UserJourneys))
		{
                        // save the monitoring accountid and name
	 		foreach ($jsonResult->Response->Account->UserJourneys->UserJourney as $UserJourney)
			{

				if(isset($UserJourney->Id))
				{
					$Journeys[] = "/AccountId/".(string)$jsonResult->Response->Account->AccountId."/Id/".(string)$UserJourney->Id."/";

			                $UJKey = runquery("INSERT IGNORE INTO `UserJourneys` (`Monitors_idMonitors`,`UserJourneyiD`,`UserJourneyName`,`UserJourneySteps`) SELECT `idMonitors`,'".(string)$UserJourney->Id."','".(string)$UserJourney->Label."','".(string)$UserJourney->Steps->Count."' FROM `Monitors`where `MonitoringAccountiD` = '".(string)$jsonResult->Response->Account->AccountId."'");
			                runquery("UPDATE  `UserJourneys` set `StartDate` = now() where  idUserJourneys = '".$UJKey."' and `StartDate` is null");

				        foreach ($UserJourney->Steps->Step as $Page)
				        {


						if(isset($Page->Id))
						{
		                	             $PageKey = runquery("INSERT IGNORE INTO `Pages` (`UserJourneys_idUserJourneys`,`Monitors_idMonitors`,`PageiD`,`PageURL`,`PageLabel`,`StepNumber`) SELECT '".$UJKey."',`idMonitors`,'".(string)$Page->Id."','".mysqli_real_escape_string($dbc, (string)$Page->Url)."','".(string)$Page->Label."','".(string)$Page->Number."' FROM `Monitors` where `MonitoringAccountiD` = '".(string)$jsonResult->Response->Account->AccountId."'");
			                	     runquery("UPDATE `Pages` set `StartDate` = now() where  idPages = '".$PageKey."' and `StartDate` is null");

		                        	}
					}
				}
			}
		}

	}
	elseif  ($requestType == "PageObjects")
	{

                if(isset($jsonResult->Response->Account->Pages->Page->TestResults->TestResult))
		{

                     // save the monitoring accountid and name
   		     foreach ($jsonResult->Response->Account->Pages->Page->TestResults->TestResult as $Test)
		     {


			$TestKey = runquery("INSERT IGNORE INTO `Tests` (`Pages_idPages`,`TestiD`,`ResultId`,`TestType`,`TestServer`,`TestServerIP`,`GMTDateTime`,`UnCompressedBytes`,`DNSSeconds`,`ConnectSeconds`,`DataStartSeconds`,`TotalSeconds`,`ResultCode`) SELECT `idPages`,'".(string)$Test->Id."','".(string)$Test->ResultId."','".(string)$Test->TestType."','".(string)$Test->TestServer."','".(string)$Test->TestServerIp."','".(string)$Test->GmtDateTime."','".(string)$Test->UncompressedBytes."','".(string)$Test->DnsSeconds."','".(string)$Test->ConnectSeconds."','".(string)$Test->DataStartSeconds."','".(string)$Test->TotalSeconds."','".(string)$Test->ResultCode."' FROM `Pages` where `pageID` = '".(string)$jsonResult->Request->Id."'");

	                if(isset($Test->TestResultDetails->ResultDetail))
			{
	                     // save the monitoring accountid and name
   			     foreach ($Test->TestResultDetails->ResultDetail as $Object)
			     {

				if(isset($Object->ObjectNo))
				{

					runquery("INSERT IGNORE INTO `Objects` (`Tests_idTests`,`ObjectiD`,`ObjectNo`,`ObjectURL`,`DNSSeconds`,`ConnectSeconds`,`SslSeconds`,`RequestSeconds`,`DataStartSeconds`,`ContentSeconds`,`TotalSeconds`,`RequestHeaderBytes`,`RequestContentBytes`,`ResponseHeaderBytes`,`TransferredBytes`,`UncompressedBytes`,`GzipSavingPercentage`,`StatusCode`,`Offset`) VALUES ('".$TestKey."','".$Test->Id.$Object->ObjectNo."','".$Object->ObjectNo."','".mysqli_real_escape_string($dbc, $Object->ObjectUrl)."','".$Object->DnsSeconds."','".$Object->ConnectSeconds."','".$Object->SslSeconds."','".$Object->RequestSeconds."','".$Object->DataStartSeconds."','".$Object->ContentSeconds."','".$Object->TotalSeconds."','".$Object->RequestHeaderBytes."','".$Object->RequestContentBytes."','".$Object->ResponseHeaderBytes."','".$Object->TransferredBytes."','".$Object->UncompressedBytes."','".$Object->GzipSavingPercentage."','".$Object->StatusCode."','".$Object->Offset."')");

				}

    			     }
			}
		     }
		}

	}
	elseif  ($requestType == "UJObjects")
	{

                if(isset($jsonResult->Response->Account->UserJourneys->UserJourney->Steps->Step[0]->TestResults->TestResult))
		{

                     // save the monitoring accountid and name
   		     foreach ($jsonResult->Response->Account->UserJourneys->UserJourney->Steps->Step[0]->TestResults->TestResult as $Test)
		     {


			$TestKey = runquery("INSERT IGNORE INTO `Tests` (`Pages_idPages`,`TestiD`,`ResultId`,`TestType`,`TestServer`,`TestServerIP`,`GMTDateTime`,`DataStartSeconds`,`TotalSeconds`,`ResultCode`) SELECT `idPages`,'".(string)$Test->Id."','".(string)$Test->ResultId."','".(string)$Test->TestType."','".(string)$Test->TestServer."','".(string)$Test->TestServerIp."','".(string)$Test->GmtDateTime."','".(string)$Test->DataStartSeconds."','".(string)$Test->TotalSeconds."','".(string)$Test->ResultCode."' FROM `Pages` where `pageID` = '".(string)$jsonResult->Response->Account->UserJourneys->UserJourney->Steps->Step[0]->Id."'");

	                if(isset($Test->TestResultDetails->ResultDetail))
			{
	                     // save the monitoring accountid and name
   			     foreach ($Test->TestResultDetails->ResultDetail as $Object)
			     {

				if(isset($Object->ObjectNo))
				{

					runquery("INSERT IGNORE INTO `Objects` (`Tests_idTests`,`ObjectiD`,`ObjectNo`,`ObjectURL`,`DNSSeconds`,`ConnectSeconds`,`SslSeconds`,`RequestSeconds`,`DataStartSeconds`,`ContentSeconds`,`TotalSeconds`,`RequestHeaderBytes`,`RequestContentBytes`,`ResponseHeaderBytes`,`TransferredBytes`,`UncompressedBytes`,`GzipSavingPercentage`,`StatusCode`,`Offset`) VALUES ('".$TestKey."','".$Test->Id.$Object->ObjectNo."','".$Object->ObjectNo."','".mysqli_real_escape_string($dbc, $Object->ObjectUrl)."','".$Object->DnsSeconds."','".$Object->ConnectSeconds."','".$Object->SslSeconds."','".$Object->RequestSeconds."','".$Object->DataStartSeconds."','".$Object->ContentSeconds."','".$Object->TotalSeconds."','".$Object->RequestHeaderBytes."','".$Object->RequestContentBytes."','".$Object->ResponseHeaderBytes."','".$Object->TransferredBytes."','".$Object->UncompressedBytes."','".$Object->GzipSavingPercentage."','".$Object->StatusCode."','".$Object->Offset."')");

				}


    			     }
			}
		     }
		}

	}
}

function runquery($query = null)
{

        global $dbc;

	//this user should have database create permissions (at least on the first run)
	$DB_USER = 'foo';
	$DB_PASSWORD = 'bar';
	$DB_HOST = 'localhost';
	$DB_NAME = 'NCCMonitor';

	//connect to the database without linking to the database
	if (!$dbc = mysqli_connect($DB_HOST, $DB_USER, $DB_PASSWORD)) {

	            // If it couldn't connect to mysqli send message and kill the script.
	            trigger_error("Could not connect to mysqli!\n<br />mysqli Error: " . mysqli_connect_error($dbc));

	            exit();

	}
	else
	{

		//test to see if the database exists and create it if need be
		if (!mysqli_select_db ($dbc, $DB_NAME))
		{

			$DatabaseCreatequery = "CREATE DATABASE IF NOT EXISTS `NCCMonitor`";
			$result = mysqli_query($dbc, $DatabaseCreatequery) or trigger_error("ERROR: $DatabaseCreatequery\n CODE: " . mysqli_error($dbc)."\n");

			//select the database
			$result = mysqli_select_db($dbc, $DB_NAME) or trigger_error("ERROR: " . mysqli_error($dbc)."\n");

			//build the Monitors table
			$TableCreatequery = "CREATE TABLE `Monitors` (`idMonitors` int(11) NOT NULL AUTO_INCREMENT,`MonitoringAccountiD` varchar(10) DEFAULT NULL,`MonitorName` varchar(200) DEFAULT NULL,`StartDate` datetime DEFAULT NULL,PRIMARY KEY (`idMonitors`),UNIQUE KEY`idxMonitoringAccountiD` (`MonitoringAccountiD`),KEY `idxMonitorName` (`MonitorName`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
			$result = mysqli_query($dbc, $TableCreatequery) or trigger_error("ERROR: $TableCreatequery\n CODE: " . mysqli_error($dbc)."\n");

			//build the UserJourneys table
			$TableCreatequery = "CREATE TABLE `UserJourneys` (`idUserJourneys` int(11) NOT NULL AUTO_INCREMENT,`Monitors_idMonitors` int(11) DEFAULT NULL,`UserJourneyiD` varchar(10) DEFAULT NULL,`UserJourneyName` varchar(200) DEFAULT NULL,`UserJourneySteps` int(3) DEFAULT NULL,`StartDate` datetime DEFAULT NULL,PRIMARY KEY (`idUserJourneys`),UNIQUE KEY `idxUserJourneyiD` (`UserJourneyiD`),KEY`idxMonitors_idMonitors` (`Monitors_idMonitors`),KEY `idxUserJourneyName` (`UserJourneyName`))ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
			$result = mysqli_query($dbc, $TableCreatequery) or trigger_error("ERROR: $TableCreatequery\n CODE: " . mysqli_error($dbc)."\n");

			//build the Pages table
			$TableCreatequery = "CREATE TABLE `Pages` (  `idPages` int(11) NOT NULL AUTO_INCREMENT,  `UserJourneys_idUserJourneys` int(11) DEFAULT NULL, `Monitors_idMonitors` int(11) DEFAULT NULL,  `PageiD` varchar(20) DEFAULT NULL,  `PageURL` varchar(5000) DEFAULT NULL, `PageLabel` varchar(500) DEFAULT NULL,  `StepNumber` int(3) DEFAULT NULL,  `StartDate` datetime DEFAULT NULL,  PRIMARY KEY(`idPages`),  UNIQUE KEY `idxPageiD` (`PageiD`),  KEY `idxUserJourneys_idUserJourneys` (`UserJourneys_idUserJourneys`),  KEY`idxMonitors_idMonitors` (`Monitors_idMonitors`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
			$result = mysqli_query($dbc, $TableCreatequery) or trigger_error("ERROR: $TableCreatequery\n CODE: " . mysqli_error($dbc)."\n");

			//build the Tests table
			$TableCreatequery = "CREATE TABLE `Tests` (  `idTests` int(11) NOT NULL AUTO_INCREMENT,  `Pages_idPages` int(11) DEFAULT NULL,  `TestiD` varchar(40)DEFAULT NULL,  `ResultId` varchar(40) DEFAULT NULL,  `TestType` varchar(40) DEFAULT NULL,  `TestServer` varchar(40)DEFAULT NULL,  `TestServerIP` varchar(40) DEFAULT NULL,  `GMTDateTime` datetime DEFAULT NULL,  `UnCompressedBytes`int(8) DEFAULT NULL,  `DNSSeconds` double(8,3) DEFAULT NULL,  `ConnectSeconds` double(8,3) DEFAULT NULL, `DataStartSeconds` double(8,3) DEFAULT NULL,  `TotalSeconds` double(8,3) DEFAULT NULL,  `ResultCode` int(1) DEFAULT NULL, PRIMARY KEY (`idTests`),  UNIQUE KEY `idxTestiD` (`TestiD`),  KEY `idxPages_idPages` (`Pages_idPages`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
			$result = mysqli_query($dbc, $TableCreatequery) or trigger_error("ERROR: $TableCreatequery\n CODE: " . mysqli_error($dbc)."\n");

			//build the Objects table
			$TableCreatequery = "CREATE TABLE `Objects` (`idObjects` int(11) NOT NULL AUTO_INCREMENT,`Tests_idTests` int(11) DEFAULT NULL,`ObjectiD` varchar(40) DEFAULT NULL,`ObjectNo` int(3) DEFAULT NULL,`ObjectURL` varchar(5000) DEFAULT NULL,`Offset` double(8,3), `DNSSeconds` double(8,3) DEFAULT NULL,`ConnectSeconds` double(8,3) DEFAULT NULL,`SslSeconds` double(8,3) DEFAULT NULL,`RequestSeconds` double(8,3) DEFAULT NULL,`DataStartSeconds` double(8,3) DEFAULT NULL,`ContentSeconds` double(8,3) DEFAULT NULL,`TotalSeconds` double(8,3) DEFAULT NULL,`RequestHeaderBytes` int(20) DEFAULT NULL,`RequestContentBytes` int(20) DEFAULT NULL,`ResponseHeaderBytes` int(20) DEFAULT NULL,`TransferredBytes` int(20) DEFAULT NULL,`UncompressedBytes` int(20) DEFAULT NULL,`GzipSavingPercentage` double(5,2) DEFAULT NULL,`StatusCode` varchar(4) DEFAULT NULL DEFAULT NULL,PRIMARY KEY (`idObjects`),UNIQUE KEY `idxObjectiD` (`ObjectiD`),KEY `idxTests_idTests` (`Tests_idTests`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
			$result = mysqli_query($dbc, $TableCreatequery) or trigger_error("ERROR: $TableCreatequery\n CODE: " . mysqli_error($dbc)."\n");

		}

	}

	$result = mysqli_query($dbc, $query) or trigger_error("ERROR: $query\n CODE: " . mysqli_error($dbc)."\n");

	return mysqli_insert_id($dbc);

	mysqli_close ($dbc);

}
?>
