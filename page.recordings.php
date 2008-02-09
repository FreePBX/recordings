<?php 
/* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//Re-written by Rob Thomas <xrobau@gmail.com> 20060318.
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
$notes = isset($_REQUEST['notes'])?$_REQUEST['notes']:'';
$rname = isset($_REQUEST['rname'])?$_REQUEST['rname']:'';
$usersnum = isset($_REQUEST['usersnum'])?$_REQUEST['usersnum']:'';
$sysrec = isset($_REQUEST['sysrec'])?$_REQUEST['sysrec']:'';
$suffix = isset($_REQUEST['suffix']) && trim($_REQUEST['suffix'] != "") ? $_REQUEST['suffix'] : 'wav';

$astsnd = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
$astsnd .= "/sounds/";

if (empty($usersnum)) {
	$dest = "unnumbered-";
} else {
	$dest = "{$usersnum}-";
}

// get feature codes for diplay purposes
$fcc = new featurecode('recordings', 'record_save');
$fc_save = $fcc->getCodeActive();
unset($fcc);
$fcc = new featurecode('recordings', 'record_check');
$fc_check = $fcc->getCodeActive();
unset($fcc);
$fc_save = ($fc_save != '' ? $fc_save : _('** MISSING FEATURE CODE **'));
$fc_check = ($fc_check != '' ? $fc_check : _('** MISSING FEATURE CODE **'));

switch ($action) {
	
	case "system":
		recording_sidebar(-1, null);
		recording_sysfiles();
		break;
	case "newsysrec":
		$sysrecs = recordings_readdir($astsnd, strlen($astsnd)+1);
		if (recordings_add($sysrecs[$sysrec], $sysrecs[$sysrec])) {
			$id = recordings_get_id($sysrecs[$sysrec]);
		} else {
			$id = 0;
		}
		recording_sidebar($id, null);
		recording_editpage($id, null);
		needreload();
		break;
	case "recorded":
		// Clean up the filename, take out any nasty characters
		$filename = escapeshellcmd(strtr($rname, '/ ', '__'));
		if (!file_exists($astsnd."custom")) {
			if (!mkdir($astsnd."custom", 0775)) {
				echo '<div class="content"><h5>'._("Failed to create").' '.$astsnd.'custom'.'</h5>';			
			}		
		} else {
			// can't rename a file from one partition to another, must use mv or cp
			// rename($recordings_save_path."{$dest}ivrrecording.wav",$recordings_astsnd_path."custom/{$filename}.wav");
			if (!file_exists($recordings_save_path."{$dest}ivrrecording.$suffix")) {
				echo "<hr><h5>"._("[ERROR] The Recorded File Does Not exists:")."</h5>";
				echo $recordings_save_path."{$dest}ivrrecording.$suffix<br><br>";
				echo "make sure you uploaded or recorded a file with the entered extension<hr>";
			} else {
				exec("cp " . $recordings_save_path . "{$dest}ivrrecording.$suffix " . $astsnd."custom/{$filename}.$suffix 2>&1", $outarray, $ret);
				if (!$ret) {
					$isok = recordings_add($rname, "custom/{$filename}.$suffix");
				} else {
					echo "<hr><h5>"._("[ERROR] SAVING RECORDING:")."</h5>";
					foreach ($outarray as $line) {
						echo "$line<br>";
					}
					echo _("Make sure you have entered a proper name");
					echo "<hr>";
				}
				exec("rm " . $recordings_save_path . "{$dest}ivrrecording.$suffix ", $outarray, $ret);
				if ($ret) {
					echo "<hr><h5>"._("[ERROR] REMOVING TEMPORARY RECORDING:")."</h5>";
					foreach ($outarray as $line) {
						echo "$line<br>";
					}
					echo _("Make sure Asterisk is not running as root ");
					echo "<hr>";
				}
			}

			recording_sidebar(null, $usersnum);
			recording_addpage($usersnum);
			if ($isok) 
				echo '<div class="content"><h5>'._("System Recording").' "'.$rname.'" '._("Saved").'!</h5>';
		}
		break;
		
	case "edit":
		$arr = recordings_get($id);
		$filename=$arr['filename'];
		// Check all possibilities of uploaded file types.
		$valid = Array("au","g723","g723sf","g729","gsm","h263","ilbc","mp3","ogg","pcm","alaw","ulaw","al","ul","mu","sln","raw","vox","WAV","wav","wav49");
		$fileexists = false;
		if (strpos($filename, '&') === false) {
			foreach ($valid as $xtn) {
				$checkfile = $recordings_astsnd_path.$filename.".".$xtn;
				if (file_exists($checkfile)) {
					$suffix = substr(strrchr($filename, "."), 1);
					copy($checkfile, $recordings_save_path."{$dest}ivrrecording.".$suffix);
					$fileexists = true;
				}
			}
			if ($fileexists === false) {
				echo '<div class="content"><h5>'._("Unable to locate").' '.$recordings_astsnd_path.$filename.' '._("with a a valid suffix").'</h5>';
			}
		}
		
		recording_sidebar($id, $usersnum);	
		recording_editpage($id, $usersnum);
		break;
		
	case "edited":
		recordings_update($id, $rname, $notes, $_REQUEST);
		recording_sidebar($id, $usersnum);
		recording_editpage($id, $usersnum);
		echo '<div class="content"><h5>'._("System Recording").' "'.$rname.'" '._("Updated").'!</h5></div>';
		needreload();
		break;
		
	case "delete";
		recordings_del($id);
		needreload();
		
	default:
		recording_sidebar($id, $usersnum);
		recording_addpage($usersnum);
		break;
		
}
	
function recording_addpage($usersnum) {
	global $fc_save;
	global $fc_check;
	global $recordings_save_path;
	
	?>
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h3><?php echo _("Add Recording") ?></h3>
	<h5><?php echo _("Step 1: Record or upload")?></h5>
	<p> <?php if (!empty($usersnum)) {
		echo _("Using your phone,")."<a href=\"#\" class=\"info\">"._("dial")."&nbsp;".$fc_save." <span>";
		echo _("Start speaking at the tone. Hangup when finished.")."</span></a>";
		echo _("and speak the message you wish to record.")."\n";
	} else { ?>
		<form name="xtnprompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
		<input type="hidden" name="display" value="recordings">
		<?php
		echo _("If you wish to make and verify recordings from your phone, please enter your extension number here:"); ?>
		<input type="text" size="6" name="usersnum"> <input name="Submit" type="submit" value="<?php echo _("Go"); ?>">
		</form>
	<?php } ?>
	</p>
	<p>
	<form enctype="multipart/form-data" name="upload" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST"/>
		<?php echo _("Alternatively, upload a recording in")?> <?php echo _("any supported asterisk format.")?> <?php echo _("Note that if you're using .wav, (eg, recorded with Microsoft Recorder) the file <b>must</b> be PCM Encoded, 16 Bits, at 8000Hz")?></span></a>:<br>
		<input type="hidden" name="display" value="recordings">
		<input type="hidden" name="action" value="recordings_start">
                <input type="hidden" name="usersnum" value="<?php echo $usersnum ?>">
		<input type="file" name="ivrfile"/>
		<input type="button" value="<?php echo _("Upload")?>" onclick="document.upload.submit(upload);alert('<?php echo addslashes(_("Please wait until the page reloads."))?>');"/>
	</form>
	<?php
	if (isset($_FILES['ivrfile']['tmp_name']) && is_uploaded_file($_FILES['ivrfile']['tmp_name'])) {
		if (empty($usersnum)) {
			$dest = "unnumbered-";
		} else {
			$dest = "{$usersnum}-";
		}
		$suffix = substr(strrchr($_FILES['ivrfile']['name'], "."), 1);
		$destfilename = $recordings_save_path.$dest."ivrrecording.".$suffix;
		move_uploaded_file($_FILES['ivrfile']['tmp_name'], $destfilename);
		system("chgrp asterisk ".$destfilename);
		system("chmod g+rw ".$destfilename);
		echo "<h6>"._("Successfully uploaded")." ".$_FILES['ivrfile']['name']."</h6>";
		$rname = rtrim(basename($_FILES['ivrfile']['name'], $suffix), '.');
	} ?>
	</p>
	<form name="prompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return rec_onsubmit();">
	<input type="hidden" name="action" value="recorded">
	<input type="hidden" name="display" value="recordings">
	<input type="hidden" name="usersnum" value="<?php echo $usersnum ?>">
	<?php
	if (!empty($usersnum)) { ?>
		<h5><?php echo _("Step 2: Verify")?></h5>
		<p> <?php echo _("After recording or uploading,")."&nbsp;<em>"._("dial")."&nbsp;".$fc_check."</em> "._("to listen to your recording.")?> </p>
		<p> <?php echo _("If you wish to re-record your message, dial")."&nbsp;".$fc_save; ?></p>
		<h5><?php echo _("Step 3: Name")?> </h5> <?php
	} else { 
		echo "<h5>"._("Step 2: Name")."</h5>";
	} ?>
	<table style="text-align:right;">
		<tr valign="top">
			<td valign="top"><?php echo _("Name this Recording")?>: </td>
			<td style="text-align:left"><input type="text" name="rname" value="<?php echo $rname; ?>"></td>
		</tr>
	</table>
	
	<h6><?php 
	echo _("Click \"SAVE\" when you are satisfied with your recording");
	echo "<input type=\"hidden\" name=\"suffix\" value=\"$suffix\">\n"; ?>
	<input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6> 
	<?php recordings_form_jscript(); ?>
	</form>
	</div>
<?php
}

function recording_editpage($id, $num) { ?>
	
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h3><?php echo _("Edit Recording") ?></h3>
	<?php
	$this_recording = recordings_get($id);
	if (!$this_recording) {
		echo "<tr><td colspan=2><h2>Error reading Recording ID $id - Aborting</h2></td></tr></table>";
		return;
	}?>
	<?php 
	echo "<a href=config.php?display=recordings&amp;action=delete&amp;usersnum=".urlencode($num);
	echo "&amp;id=$id>";
	echo _("Remove Recording");
	echo "</a> <i style='font-size: x-small'>(";
	echo _("Note, does not delete file from computer");
	echo ")</i>";
	?>
	<form name="prompt"  action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return rec_onsubmit();">
	<input type="hidden" name="action" value="edited">
	<input type="hidden" name="display" value="recordings">
	<input type="hidden" name="usersnum" value="<?php echo $num ?>">
	<input type="hidden" name="id" value="<?php echo $id ?>">
	<table>
	<tr><td colspan=2><hr></td></tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Change Name");?><span><?php echo _("This changes the short name, visible on the right, of this recording");?></span></a></td>
		<td><input type="text" name="rname" value="<?php echo $this_recording['displayname'] ?>"></td>
	</tr>
	<tr>
	    	<td><a href="#" class="info"><?php echo _("Descriptive Name");?><span><?php echo _("This is displayed, as a hint, when selecting this recording in Queues, Digital Receptionist, etc");?></span></a></td>
	    	<td>&nbsp;<textarea name="notes" rows="3" cols="40"><?php echo $this_recording['description'] ?></textarea></td>
	</tr>
	</table>
	<hr />
	<?php echo _("Files");?>:<br />
	<table>
	<?php 
	$rec = recordings_get($id);
	$fn = $rec['filename'];
	$files = explode('&', $fn);
	$counter = 0;
	$arraymax = count($files)-1;
	// globals seem to busted in PHP5 define here for now
	$recordings_astsnd_path = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
	$recordings_astsnd_path .= "/sounds/";

	foreach ($files as $item) {
		recordings_display_sndfile($item, $counter, $arraymax, $recordings_astsnd_path);
		$counter++;
	}	
	recordings_display_sndfile('', $counter, $arraymax, $recordings_astsnd_path);
	?>
	</table>
	<input name="Submit" type="submit" value="<?php echo _("Save")?>"></h6>
	<?php recordings_popup_jscript(); ?>	
	<?php recordings_form_jscript(); ?>	
	</form>
	</div>
<?php
}

function recording_sidebar($id, $num) {
?>
        <div class="rnav"><ul>
        <li><a id="<?php echo empty($id)?'current':'nul' ?>" href="config.php?display=recordings&amp;usersnum=<?php echo urlencode($num) ?>"><?php echo _("Add Recording")?></a></li>
        <li><a id="<?php echo ($id===-1)?'current':'nul' ?>" href="config.php?display=recordings&amp;action=system"><?php echo _("Built-in Recordings")?></a></li>
<?php
		$wrapat = 18;
        $tresults = recordings_list();
        if (isset($tresults)){
                foreach ($tresults as $tresult) {
                        echo "<li>";
                        echo "<a id=\"".($id==$tresult[0] ? 'current':'nul')."\" href=\"config.php?display=recordings&amp;";
                        echo "action=edit&amp;";
                        echo "usersnum=".urlencode($num)."&amp;";
//                        echo "filename=".urlencode($tresult[2])."&amp;";
                        echo "id={$tresult[0]}\">";
                        $dispname = $tresult[1];
                        while (strlen($dispname) > (1+$wrapat)) {
                        	$part = substr($dispname, 0, $wrapat);
	                        echo htmlspecialchars($part);
                        	$dispname = substr($dispname, $wrapat);
                        	if ($dispname != '')
                        		echo "<br>";
                        }
                        echo htmlspecialchars($dispname);
                        echo "</a>";
                        echo "</li>\n";
                }
        }
        echo "</ul></div>\n";
}

function recordings_popup_jscript() {
?>
        <script language="javascript">
	<!-- Begin
	function popUp(URL,optionId) {
		var selIndex=optionId.selectedIndex
		var file=optionId.options[selIndex].value

		/*alert(selIndex);*/
		if (file != "")
			popup = window.open(URL+file, 'play', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=1,width=320,height=110');
	}
	// End -->
	</script>
<?php
}

function recordings_form_jscript() {
?>
	<script language="javascript">
	<!--

	var theForm = document.prompt;
	
	function rec_onsubmit() {
		var msgInvalidFilename = "<?php echo _("Please enter a valid Name for this System Recording"); ?>";
		
		defaultEmptyOK = false;
		if (!isFilename(theForm.rname.value))
			return warnInvalid(theForm.rname, msgInvalidFilename);
			
		return true;
	}

	//-->
	</script>

<?php
}

function recording_sysfiles() {
	$astsnd = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
	$astsnd .= "/sounds/";
	$sysrecs = recordings_readdir($astsnd, strlen($astsnd)+1);
?>
	<div class="content">
	<h2><?php echo _("System Recordings")?></h2>
	<h3><?php echo _("Built-in Recordings") ?></h3>
	<h5><?php echo _("Select System Recording:")?></h5>
	<form name="xtnprompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
	<input type="hidden" name="action" value="newsysrec">
	<input type="hidden" name="display" value="recordings">
	<select name="sysrec"/>
<?php
	foreach ($sysrecs as $srcount => $sr) {
		// echo '<option value="'.$vmc.'"'.($vmc == $ivr_details['dircontext'] ? ' SELECTED' : '').'>'.$vmc."</option>\n";
		echo "<option value=\"$srcount\">$sr</option>\n";
		}
	?>
	</select>
	<input name="Submit" type="submit" value="<?php echo _("Go"); ?>">
	<p />
	</div>
<?php
}

function recordings_display_sndfile($item, $count, $max, $astpath) {
	global $amp_conf;
	// Note that when using this, it needs a <table> definition around it.
	$astsnd = isset($asterisk_conf['astvarlibdir'])?$asterisk_conf['astvarlibdir']:'/var/lib/asterisk';
	$astsnd .= "/sounds/";
	$sysrecs = recordings_readdir($astsnd, strlen($astsnd)+1);
	print "<tr><td><select id='sysrec$count' name='sysrec$count'>\n";
	echo '<option value=""'.($item == '' ? ' SELECTED' : '')."></option>\n";
	foreach ($sysrecs as $sr) {
		echo '<option value="'.$sr.'"'.($sr == $item ? ' SELECTED' : '').">$sr</option>\n";
	}
	print "</select></td>\n";

	echo "<td>";
	$audio=$astpath;

	$REC_CRYPT_PASSWORD = urlencode((isset($amp_conf['AMPPLAYKEY']) && trim($amp_conf['AMPPLAYKEY']) != "")?trim($amp_conf['AMPPLAYKEY']):'moufdsuu3nma0');
	$recurl="modules/recordings/popup.php?cryptpass=$REC_CRYPT_PASSWORD&recording=$audio";

	echo "<a href='#' type='submit' onClick=\"javascript:popUp('$recurl',document.prompt.sysrec$count); return false;\" input='foo'  >";
        echo "<img border='0' width='20'  height='20' src='images/play.png' title='Click here to play this recording' />";
        echo "</img></td>";

	if ($count==0) {
		 print "<td></td>\n"; 
	} else {
		echo "<img border='0' width='3' height='11' style='float: none; margin-left: 0px; margin-bottom: 0px;' src='images/blank.gif' />";
		echo '<td><input name="up'.$count.'" width=10 height=20 border=5  title="Move Up" type="image" src="images/scrollup.gif"  value="'._("Move Up").'"/>';
		print "</td>\n"; 
	} if ($count > $max) {
		print "<td></td>\n"; 
	} else {
		echo "<img border='0' width='3' height='11' style='float: none; margin-left: 0px; margin-bottom: 0px;' src='images/blank.gif' />";
		echo '<td><input name="down'.$count.'" width=10 height=20 border=0 title="Move Down" type="image" src="images/scrolldown.gif"  value="'._("Move Down")."\">\n";
		echo "<img border='0' width='3' height='11' style='float: none; margin-left: 0px; margin-bottom: 0px;' src='images/blank.gif' />";
		print "</td>\n"; 
	}
	echo '<td><input name="del'.$count.'" type="image" border=0 title="Delete" src="images/trash.png" value="'._("Delete")."\">\n";
	echo "<img border='0' width='9' height='11' style='float: none; margin-left: 0px; margin-bottom: 0px;' src='images/blank.gif' />";
	echo "<img border='0' width='9' height='11' style='float: none; margin-left: 0px; margin-bottom: 0px;' src='images/blank.gif' />";
	print "</td>\n"; 

	print "</tr>\n";
}

?>
