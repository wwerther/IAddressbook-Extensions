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

function act_exportnokia_cat() {
    global $contactlist;
    global $conf;
    global $CAT;
    
    $contacts_selected = array();

    $vcard_list = '';
    
    foreach($_REQUEST as $key => $value) {
        if(strpos($key, 'ct_') === 0) {
            $contacts_selected[$value] = $contactlist[$value];
        }
    }
    if(count($contacts_selected) == 0) $contacts_selected = $contactlist;


    $zip = new ZipArchive();
    $zipfilename = tempnam(AB_INC.'_temp/','Nokia');

    if ($zip->open($zipfilename, ZIPARCHIVE::CREATE)!==TRUE) {
	msg("cannot open <$zipfilename>\n",-1);
    }

    $org_photo=$conf['photo_format'];
    $conf['vcard-version'] = '2.1';

    foreach ($contacts_selected as $contact) {
       $conf['photo_format'] = $org_photo; 
       $contact->image = img_load($contact->id);
       if ($contact->image) img_convert($contact_image,'jpg','80x96');
       $categories = $CAT->find($contact->id);
       
       $conf['photo_format'] = 'JPEG';
       $vcard = contact2vcard($contact, $categories);
       array_shift($vcard);
#	print_r($vcard);
       $vcard=join("\n",$vcard);
       $vcard=str_replace('TEL;TYPE=','TEL;',$vcard);
       $vcard=str_replace('EMAIL;TYPE=','EMAIL;',$vcard);
	$vcard=iconv('UTF-8','ISO-8859-1//IGNORE',$vcard);
	$zip->addFromString($contact->id.".vcf", $vcard);

    }

   $zip->close();

   // send the vcard
   $filename = "All Contacts (" . count($contacts_selected) . ")";
   $filename = trim($filename);
   $filename .= ".zip";
   
   header("Content-Type: application/zip");
   header("Content-Length: " . filesize($file));
   header("Content-Disposition: attachment; filename=\"$filename\"");
   readfile($zipfilename);

   unlink($zipfilename);
   exit();    

}

?>
