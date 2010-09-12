<?php

// Delete them all even if they should not exist just in case
//
$recordings = recordings_list();
foreach ($recordings as $item) {
	$fcc = new featurecode('recordings', 'edit-recording-'.$item['id']);
	$fcc->delete();
	unset($fcc);	
}

sql('DROP TABLE IF EXISTS recordings');

$fcc = new featurecode('recordings', 'record_save');
$fcc->delete();
unset($fcc);

$fcc = new featurecode('recordings', 'record_check');
$fcc->delete();
unset($fcc);


?>
