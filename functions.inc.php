<?php

// Source and Destination Dirctories for recording
global $recordings_astsnd_path; // PHP5 needs extra convincing of a global
$recordings_save_path = "/tmp/";
$recordings_astsnd_path = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
$recordings_astsnd_path .= "/sounds/";

function recordings_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $recordings_save_path;
	
	$modulename = "recordings";
	$appcontext = "app-recordings";
	$contextname = 'ext-recordings';
	
	switch($engine) {
		case "asterisk":
			// FeatureCodes for save / check
			$fcc = new featurecode($modulename, 'record_save');
			$fc_save = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'record_check');
			$fc_check = $fcc->getCodeActive();
			unset($fcc);

			if ($fc_save != '' || $fc_check != '') {
				$ext->addInclude('from-internal-additional', 'app-recordings'); // Add the include from from-internal
				
				if ($fc_save != '') {
					$ext->add($appcontext, $fc_save, '', new ext_macro('user-callerid'));
					$ext->add($appcontext, $fc_save, '', new ext_wait('2'));
					$ext->add($appcontext, $fc_save, '', new ext_macro('systemrecording', 'dorecord'));
					//$ext->add($appcontext, $fc_save, '', new ext_goto('1', 'dorecord'));
				}

				if ($fc_check != '') {
					$ext->add($appcontext, $fc_check, '', new ext_macro('user-callerid'));
					$ext->add($appcontext, $fc_check, '', new ext_wait('2'));
					$ext->add($appcontext, $fc_check, '', new ext_macro('systemrecording', 'docheck'));
					//$ext->add($appcontext, $fc_check, '', new ext_goto('1', 'docheck'));
				}
			}

		/* Create a context for recordings as destinations */
		$recordings =  recordings_list();
		if (is_array($recordings)) {
			foreach ($recordings as $r) {
				$ext->add($contextname, 'recording-'.$r[0], '', new ext_answer());
				$ext->add($contextname, 'recording-'.$r[0], '', new ext_playback($r[2]));
				$ext->add($contextname, 'recording-'.$r[0], '', new ext_hangup());
			}
		}
		break;
	}
}			

function recordings_get_id($fn) {
	global $db;
	
	$sql = "SELECT id FROM recordings WHERE filename='$fn'";
        $results = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if (isset($results['id'])) {
		return $results['id'];
	} else {
		return null;
	}
}

function recordings_get_file($id) {
	$res = recordings_get($id);
	return $res['filename'];
}
	

function recordings_list() {
	global $db;

	// I'm not clued on how 'Department's' work. There obviously should be 
	// somee checking in here for it.

        $sql = "SELECT * FROM recordings where displayname <> '__invalid' ORDER BY displayname";
        $results = $db->getAll($sql);
        if(DB::IsError($results)) {
                $results = null;
        }
        return $results;
}

function recordings_get($id) {
	global $db;
        $sql = "SELECT * FROM recordings where id='$id'";
        $results = $db->getRow($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($results)) {
                $results = null;
        }
	return $results;
}

function recordings_add($displayname, $filename) {
	global $db;
	global $recordings_astsnd_path;

	// Check to make sure we can actually read the file if it has an extension (if it doesn't, 
	// it was put here by system recordings, so we know it's there.
	if (preg_match("/\.(au|g723|g723sf|g726-\d\d|g729|gsm|h263|ilbc|mp3|ogg|pcm|[au]law|[au]l|mu|sln|raw|vox|WAV|wav|wav49)$/", $filename)) {
		if (!is_readable($recordings_astsnd_path.$filename)) {
			print "<p>Unable to add ".$recordings_astsnd_path.$filename." - Can not read file!</p>";
			return false;
		}
		$fname = preg_replace("/\.(au|g723|g723sf|g726-\d\d|g729|gsm|h263|ilbc|mp3|ogg|pcm|[au]law|[au]l|mu|sln|raw|vox|WAV|wav|wav49)$/", "", $filename);

	} else {
		$fname = $filename;
	}
	sql("INSERT INTO recordings values ('', '$displayname', '$fname', 'No long description available')");
	return true;
	
}

function recordings_update($id, $rname, $descr, $_REQUEST) {

	// Update the descriptive fields
	$results = sql("UPDATE recordings SET displayname = \"$rname\", description = \"$descr\" WHERE id = \"$id\"");
	
	// Build the file list from _REQUEST
        $astsnd = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
        $astsnd .= "/sounds/";
	$recordings = Array();

	// Set the file names from the submitted page, sysrec[N]
	foreach ($_REQUEST as $key => $val) {
		$res = strpos($key, 'sysrec');
		if ($res !== false) {
			// strip out any relative paths, since this is coming from a URL
			str_replace('..','',$val);

			$recordings[substr($key,6)]=$val;
		}
	}

	// Stick the filename in the database
	recordings_set_file($id, implode('&', $recordings));

	// In _REQUEST there are also various actions (possibly) 
	// up[N] - Move file id N up one place
	// down[N] - Move fid N down one place
	// del[N] - Delete fid N
	
	foreach ($_REQUEST as $key => $val) {
		$up = strpos($key, "up");
		$down = strpos($key, "down");
		$del = strpos($key, "del");
		if ( $up !== false ) {
			$up = substr($key, 2);
			recordings_move_file_up($id, $up);
		}
		if ($del !== false ) {
			$del = substr($key,3);
			recordings_delete_file($id, $del);
		}
		if ($down !== false ) {
			$down = substr($key,4);
			recordings_move_file_down($id, $down);
		}
	}
}

function recordings_move_file_up($id, $src) {
	$files = recordings_get_file($id);
	if ($src === 0 || $src < 0) { return false; } // Should never happen, up shouldn't appear whten fid=0
	$tmparr = explode('&', $files);
	$tmp = $tmparr[$src-1];
	$tmparr[$src-1] = $tmparr[$src];
	$tmparr[$src] = $tmp;
	recordings_set_file($id, implode('&', $tmparr));
}
function recordings_move_file_down($id, $src) {
	$files = recordings_get_file($id);
	$tmparr = explode('&', $files);
	$tmp = $tmparr[$src+1];
	$tmparr[$src+1] = $tmparr[$src];
	$tmparr[$src] = $tmp;
	recordings_set_file($id, implode('&', $tmparr));
}
function recordings_delete_file($id, $src) {
	$files = recordings_get_file($id);
	$tmparr = explode('&', $files);
	$tmp = Array();
	$counter = 0;
	foreach ($tmparr as $file) {
		if ($counter != $src) { $tmp[] = $file; }
		$counter++;
	}
	recordings_set_file($id, implode('&', $tmp));
}
	

function recordings_del($id) {
	$results = sql("DELETE FROM recordings WHERE id = \"$id\"");
}

function recordings_set_file($id, $filename) {
	// Strip off any dangling &'s on the end:
	$filename = rtrim($filename, '&');
	$results = sql("UPDATE recordings SET filename = \"$filename\" WHERE id = \"$id\"");
}



function recordings_readdir($snddir) {
	$files = recordings_getdir($snddir);
	$ptr = 0;
	foreach ($files as $fnam) {
		$files[$ptr] = substr($fnam, strlen($snddir)+1);
		$ptr++;
	}
	// Strip off every possible file extension
	$flist = preg_replace("/\.(au|g723|g723sf|g726-\d\d|g729|gsm|h263|ilbc|mp3|ogg|pcm|[au]law|[au]l|mu|sln|raw|vox|WAV|wav|wav49)$/", "", $files);
	sort($flist);
	return array_unique($flist);
}
	
function recordings_getdir($snddir) {
	$dir = opendir($snddir);
	$files = Array();
	while ($fn = readdir($dir)) {
		if ($fn == '.' || $fn == '..') { continue; }
		if (is_dir($snddir.'/'.$fn)) {
			$files = array_merge(recordings_getdir($snddir.'/'.$fn), $files);
			continue;
		}
		$files[] = $snddir.'/'.$fn;
	}
	return $files;
}
	
	


// returns a associative arrays with keys 'destination' and 'description'
// it allows system recording to be chosen as destinations
function recordings_destinations() {
	$recordings =  recordings_list();
	if (is_array($recordings)) {
		foreach ($recordings as $r) {
			$extens[] = array('destination' => 'ext-recordings,recording-'.$r[0].',1', 'description' => $r[1]);
		}
	}

	return $extens;
}

?>
