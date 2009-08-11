<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//
// Fritz-Box support for iAddressbook
// ----------------------------------
//
//                                      written by Walter Werther <walter@wwerther.de>
//
// This part of code extracts all users and phonenumbers from the database and generates
// a phonebook-file that can be used within the Fritz-Box phonebook
//
// supports user authentication in the same manner as it is done in iAddressbook.
// added a new authorisation group named 'fbox_getphonebook'
// User has to have access to this group to use this component
//
// add these lines to conf/auth.php
/*---- 8< --- 8< ---  8< ---  8< ---  8< ---  8< ---  8< ---  8< ---  8< --- 
$auth['calendar']['password']    = '--- enter your md5 sum here  ---';
$auth['calendar']['permissions'] = array();
$auth['calendar']['groups']      = array('@fbox_client');
$auth['@ical_client']['permissions']  = array('fbox_getphonebook');
---- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 --- >8 
*/
//
//      2009-08-11 <walter@wwerther.de>: started the project
//      based on the idea from http://www.wehavemorefun.de/fritzbox/index.php/XML-Adressbuch
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
#      header('WWW-Authenticate: Basic Realm="Ics-Access"');
#     header('HTTP/1.0 401 Unauthorized');
#      echo "Login Cancelled";
#      exit;
};

#
# Now check if the user has access to the FBox-Phonebook function
#
if (! auth_verify_action($_SERVER['PHP_AUTH_USER'],'fbox_getphonebook')) {
#      header('WWW-Authenticate: Basic Realm="Ics-Access"');
#      header('HTTP/1.0 401 Unauthorized');
#      echo "User has no access to FBox-Phonebook function";
#      exit;
};

db_init();
db_open();

//error_reporting(E_ALL);
print <<<EOT
<?xml version="1.0" encoding="iso-8859-1"?>

<phonebook>
EOT;
$sql = "select firstname,lastname,phones from ".$db_config['dbtable_ab']." where phones != '' order by lastname,firstname";

$result = $db->Execute($sql);

if($result) {
        // Loop through all results
        while ($row = $result->FetchRow()) {
            if($row) {
                $row["phones"]=split("\n",$row["phones"]);
                $newarr=array();
                foreach ($row["phones"] as $ind=>$subrow) {
                    $components=split(";",$subrow);
                    if ($components[0] == "CELL") {
                        $components[0]="mobile";
                    } elseif ($componets[0] == "HOME") {
                        $components[0]="home";
                    } elseif ($componets[0] == "WORK") {
                        $components[0]="work";
                    } else {
                        continue; 
                    }
                    $newarr[]=$components;
                }
                $lname=$row["lastname"];
                $fname=$row["firstname"];
                print <<<EOT
    <category>0</category>                                     <!-- wichtige Person? 1=>ja, 0=>nein -->
    <services/><setup/>                                        <!--???-->
    <person><realName>$lname,$fname</realName></person>
    <telephony>
EOT;
        foreach ($newarr as $phoneentry) {
            print "        <number type=\"$phoneentry[0]\"   quickdial=\"11\" vanity=\"muster\" prio=\"1\">$phoneentry[1]</number>\n";
        }
print <<<EOT
    </telephony>
  </contact>

EOT;


           } else {
                print "Row is empty?";
            }
        }
    } else {
        //query error
        print "DB error on remote add: ". $db->ErrorMsg() . "\n";
        return;
    }

db_close();

print <<<EOT
</phonebook>
<phonebook owner="255">
  <contact>
    <category/><services/><setup/>
    <person><realName>Sunflower</realName></person>
    <telephony><number type="intern">610</number></telephony>
  </contact>

  <contact>
    <category/><services/><setup/>
    <person><realName>Anrufbeantworter 1</realName></person>
    <telephony><number type="intern">600</number></telephony>
  </contact>

  <contact>
    <category/><services/><setup/>
    <person><realName /></person>
    <telephony><number type="intern">51</number></telephony>
  </contact>

  <contact>
    <category/><services/><setup/>
    <person><realName>fax</realName></person>
    <telephony><number type="intern">1</number></telephony>
  </contact>

  <contact>
    <category/><services/><setup/>
    <person><realName /></person>
    <telephony><number type="intern">2</number></telephony>
  </contact>

  <contact>
    <category/><services/><setup/>
    <person><realName /></person>
    <telephony><number type="intern">3</number></telephony>
  </contact>

</phonebook>
EOT;

?>
