<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//
// iCal support for iAddressbook
// -----------------------------
//
//                                      written by Walter Werther <walter@wwerther.de>
//
// This part of code extracts all valid birthdates from the database and generates
// an ics-file that can be used e.g. in the Lightning-Plugin of Thunderbird to display
// all birthdays of all your contacts.
//
// supports user authentication in the same manner as it is done in iAddressbook.
// added a new authorisation group named 'ical_getcalendar'
// User has to have access to this group to use this component
//
// add these lines to conf/auth.php
/*---- 8< --- 8< ---  8< ---  8< ---  8< ---  8< ---  8< ---  8< ---  8< --- 
$auth['calendar']['password']    = '--- enter your md5 sum here  ---';
$auth['calendar']['permissions'] = array();
$auth['calendar']['groups']      = array('@ical_client');
$auth['@ical_client']['permissions']  = array('ical_getcalendar');
---- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 
*/
//
//      2008-09-03 <walter@wwerther.de>: rewrote pieces of the code, so it can 
//                 be used in iAddressbook (http://iaddressbook.org/) 
//                 I think it is not 100% compatible anymore, but works fine for
//                 this use-case
//      based on libs from http://idmi.poly.edu/pdlab/ical-with-fixes-2006-10-10.zip
//      based on iCal component by Michael Wimmer <flaimo@gmx.net>
//

if(!defined('AB_INC')) define('AB_INC',realpath(dirname(__FILE__)).'/');
require_once(AB_INC.'functions/init.php');
require_once(AB_INC.'functions/db.php');
require_once(AB_INC.'functions/module_auth.php');
require_once(AB_INC.'functions/common.php');

global $conf;

#
# First check if access to the system can be granted
#
if (! auth_login($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])) {
      header('WWW-Authenticate: Basic Realm="Ics-Access"');
      header('HTTP/1.0 401 Unauthorized');
      echo "Login Cancelled";
      exit;
};

#
# Now check if the user has access to the iCalendar function
#
if (! auth_verify_action($_SERVER['PHP_AUTH_USER'],'ical_getcalendar')) {
      header('WWW-Authenticate: Basic Realm="Ics-Access"');
      header('HTTP/1.0 401 Unauthorized');
      echo "User has no access to calendar function";
      exit;
};

db_init();
db_open();

//error_reporting(E_ALL);
include_once('ical/class.iCal.inc.php');

$iCal = (object) new iCal('', 1, ''); // (ProgrammID, Method (1 = Publish | 0 = Request), Download Directory)

$sql = "select firstname,lastname,birthdate from ".$db_config['dbtable_ab']." where birthdate != '0000-00-00'";

$result = $db->Execute($sql);

if($result) {
        // Loop through all results
        while ($row = $result->FetchRow()) {
            if($row) {
                # $deb=print_r($row,true);
                # print nl2br($deb);

                $iCal->addEvent(
                    array('',''), // $_REQUEST['email']), // Organizer
	    			$row['birthdate'],# _REQUEST['sts'], // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
                    'allday',  // $_REQUEST['ets'], // End Time (write 'allday' for an allday event instead of a timestamp)
			    	'', // $_REQUEST['loc'], // Location
				    1, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
    				array($lang['label_birthday']), // Array with Strings of Categories
	    			$lang['label_birthday'].' '.$row['firstname'].' '.$row['lastname'], // Description
	    			$lang['label_birthday'].' '.$row['firstname'].' '.$row['lastname'], // Title
			    	1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
    				array(NULL), // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
	    			0, // Priority = 0-9
		    		7, // frequency: 0 = once, secoundly - yearly = 1-7
			    	'', // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
				    1,// Interval for frequency (every 2,3,4 weeks...)
    				array(NULL), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	    			1, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
    				'', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	    			array(NULL),  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
		    		1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
			    	'', // $_REQUEST['website'], // optional URL for that event
				    'de', // Language of the Strings
                    '' // Optional UID for this event
			    ) ;
            } else {
                print "Row is empty?";
            }
        }
    } else {
        //query error
        print "DB error on remote add: ". $db->ErrorMsg() . "\n";
        return;
    }

//echo $iCal->countiCalObjects();
$iCal->outputFile('ics'); // output file as ics (xcs and rdf possible)

db_close();

?>
