<?php
/**
 * AddressBook VCard Import
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Clemens Wacha <clemens.wacha@gmx.net>
 */

    if(!defined('AB_CONF')) define('AB_CONF',AB_INC.'conf/');
    require_once(AB_CONF.'defaults.php');

    if(!defined('AB_INC')) define('AB_INC',realpath(dirname(__FILE__).'/../').'/');
    require_once(AB_INC.'functions/addressbook.php');
    require_once(AB_INC.'functions/Contact_Vcard_Parse.php');
    require_once(AB_INC.'functions/Contact_Vcard_Build.php');
    require_once(AB_INC.'functions/image.php');
    require_once(AB_INC.'functions/category.php');

function act_exportfbox_cat() {
    global $contactlist;
    global $conf;
    global $CAT;

    global $contact_counter;
    
    $contacts_selected = array();

    $vcard_list = '';
    
    foreach($_REQUEST as $key => $value) {
        if(strpos($key, 'ct_') === 0) {
            $contacts_selected[$value] = $contactlist[$value];
        }
    }
    if(count($contacts_selected) == 0) $contacts_selected = $contactlist;

    $contact_counter=1;

    $result = <<<EOT
<?xml version="1.0" encoding="iso-8859-1"?>
<phonebooks>
	<phonebook>
EOT;
    $org_photo=$conf['photo_format'];

    foreach ($contacts_selected as $contact) {
       $conf['photo_format'] = $org_photo; 
       $categories = $CAT->find($contact->id);
       
	$xml = contact2xml($contact, $categories);
	$result.=$xml;

    }

   $result.= <<<EOT
	</phonebook>
</phonebooks>
EOT;

   // send the vcard
   $filename = "All Contacts (" . count($contacts_selected) . ")";
   $filename = trim($filename);
   $filename .= ".xml";
   
   header("Content-Type: text/xml");
   header("Content-Length: " . filesize($file));
   header("Content-Disposition: attachment; filename=\"$filename\"");

   print $result;
   exit();    

}

function contact2xml($contact,$categories) {
	global $contact_counter;
	global $conf;
	$xmlcard=<<<EOT
		<contact>
			<category>0</category>
EOT;

	$xmlcard.='<person><realName>'.$contact->firstname.' '.$contact->lastname.'</realName></person>'."\n";
	$xmlcard=iconv('UTF-8','ISO-8859-1//IGNORE',$xmlcard);
	$xmlcard.=<<<EOT
			<telephony>
EOT;

	$prio=1;
	$count=3;
	foreach($contact->phones as $item) {
		if ($count<=0) break;
		$type=$item['label']; # Convert to "home", "mobile" or "work"
		$phone=$item['phone']; # Convert from +xxx to 00
		$phone=str_replace('+','00',$phone);
		$phone=str_replace('0049','0',$phone);
		$phone=str_replace(' ','',$phone);
		$type=str_replace('CELL','mobile',$type);
		$type=str_replace('HOME','home',$type);
		$type=str_replace('WORK','work',$type);
		if ( ! in_array($type,array('mobile','home','work'))) continue;	
		$xmlcard.='<number type="'.$type.'" vanity="" prio="'.$prio.'" quickdial="'.$contact_counter.'">'.$phone.'</number>';
		if ($prio==1) $prio=0;
		$contact_counter++;
		$count --;
	}
	if ($count==3) return '';

#<contact><category>0</category>
#	<telephony>
#		<number type="home" vanity="" prio="0">00498920354472</number>
#		<number type="mobile" quickdial="1" vanity="" prio="1">004915116249030</number>
#		<number type="work" vanity="" prio="0">00498911120323</number>
#	</telephony>
#	</contact>
#	
	$xmlcard.=<<<EOT
			</telephony>
			<services />
			<setup />
		</contact>
EOT;
	return $xmlcard;
}

?>
