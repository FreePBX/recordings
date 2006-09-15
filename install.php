<?php

global $amp_conf;
global $asterisk_conf;
global $db;
$recordings_astsnd_path = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
$recordings_astsnd_path .= "/sounds/";


require_once($amp_conf['AMPWEBROOT'] . '/admin/modules/recordings/functions.inc.php');

$fcc = new featurecode('recordings', 'record_save');
$fcc->setDescription('Save Recording');
$fcc->setDefault('*77');
$fcc->update();
unset($fcc);

$fcc = new featurecode('recordings', 'record_check');
$fcc->setDescription('Check Recording');
$fcc->setDefault('*99');
$fcc->update();
unset($fcc);

// Make sure table exists
$sql = "CREATE TABLE IF NOT EXISTS recordings ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, displayname VARCHAR(50) , filename BLOB, description VARCHAR(254));";
$result = $db->query($sql);
if(DB::IsError($result)) {
        die($result->getDebugInfo());
}

// load up any recordings that might be in the directory
$recordings_directory = $recordings_astsnd_path."custom/";

if (!file_exists($recordings_directory)) { 
	mkdir ($recordings_directory);
}
if (!is_writable($recordings_directory)) {
	print "<h2>Error</h2><br />I can not access the directory $recordings_directory. ";
	print "Please make sure that it exists, and is writable by the web server.";
	die;
}
$sql = "SELECT * FROM recordings where displayname = '__invalid'";
$results = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if (!isset($results['filename'])) {
	sql("INSERT INTO recordings values ('', '__invalid', 'install done', '')");
	$dh = opendir($recordings_directory);
	while (false !== ($file = readdir($dh))) { // http://au3.php.net/readdir 
		if ($file[0] != "." && $file != "CVS" && $file != "svn" && !is_dir("$recordings_directory/$file")) {
			// Ignore the suffix..
			$fname = ereg_replace('.wav', '', $file);
			$fname = ereg_replace('.gsm', '', $fname);
			if (recordings_get_id("custom/$fname") == null)
				recordings_add($fname, "custom/$file");
		}
	}
}

global $db;

// Upgrade to recordings 3.0
// Change filename from VARCHAR(80) to BLOB
$sql = 'ALTER TABLE recordings CHANGE filename filename BLOB';
$result = $db->query($sql);
if(DB::IsError($result)) {
	die($result->getDebugInfo());
}

?>
