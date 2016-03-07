NCCGroup-monitoringToMySQL
==========================

Description:
PHP command line program to save data from the NCC Group Web Performance API to a database.

Technologies:
- PHP, MySQL

Installation instructions:
- Download the file and run it from the command line, or cron, using the PHP CLI

Installation Pre-Requisites:
- PHP 5.3+ with PHP CURL and MySQL installed
- update the MySQL account declaration in the runquery() function (line 420-421), if the user has creation rights the code will setup the database and tables 

Operation instructions:
- run the code with "php monitoringToMYSQL.March2016.php user password account days", where 
- 		user: 		the NCC Group Portal username
- 		password: 	the NCC Group Portal password
- 		account: 	a NCC Group monitoring account to filter (default is all)
- 		days: 		number of days of data, for example 7 would store the previous seven days worth of data (default is today)

Copyright (c) 2016 NCC Group

    Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.