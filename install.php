<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//for translation only
if (false) {
_("Recordings");
_("Save Recording");
_("Check Recording");
}

global $amp_conf;
global $db;

$recordings_astsnd_path = isset($amp_conf['ASTVARLIBDIR'])?$amp_conf['ASTVARLIBDIR']:'/var/lib/asterisk';
$recordings_astsnd_path .= "/sounds/";
$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT":"AUTO_INCREMENT";

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
$sql = "CREATE TABLE IF NOT EXISTS recordings (
	id INTEGER NOT NULL  PRIMARY KEY AUTO_INCREMENT,
	displayname VARCHAR(50) ,
	filename BLOB,
	description VARCHAR(254))
;";
$result = $db->query($sql);
if(DB::IsError($result)) {
	die_freepbx($result->getDebugInfo());
}

sql('DELETE FROM recordings WHERE displayname = "__invalid"');
$freepbx_conf =& freepbx_conf::create();
if($freepbx_conf->conf_setting_exists('AMPPLAYKEY')) {
	$freepbx_conf->remove_conf_setting('AMPPLAYKEY');
}

$dir = FreePBX::Config()->get("ASTVARLIBDIR")."/sounds";
$sql = "SELECT * FROM recordings";
$sth = FreePBX::Database()->prepare($sql);
$sth->execute();
$recordings = $sth->fetchAll(\PDO::FETCH_ASSOC);
foreach($recordings as $recording) {
	$files = explode("&",$recording['filename']);
	$filenames = array();
	foreach($files as $file) {
		//move all custom files first
		if(preg_match("/^custom\/(.*)/",$file,$matches)) {
			rename($dir."/".$recording['filename'],$dir."/en/".$matches[1]);
			$filenames[] = $matches[1];
		} elseif(preg_match("/^\w{2}\_\w{2}|\w{2}\//",$file)) {
			$filenames[] = preg_replace("/^\w{2}\_\w{2}|\w{2}\//", "", $file);
		} else {
			$filenames[] = $file;
		}
	}
	$sql = "UPDATE recordings SET filename = ? WHERE id = ?";
	$sth = FreePBX::Database()->prepare($sql);
	$sth->execute(array(implode('&',$filenames), $recording['id']));
}

if(file_exists($dir."/custom")) {
	$files = glob($dir."/custom/*");
	foreach($files as $file) {
		$parts = pathinfo($file);
		FreePBX::Recordings()->addRecording($parts['filename'],"Migrated file",$file);
	}
	if(empty($files)) {
		rmdir($dir."/custom");
	}
}
