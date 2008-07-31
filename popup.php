<?php

/**
 * @file
 * popup window for playing recording
 */

include_once("crypt.php");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<TITLE>FreePBX Recording Review</TITLE>
		<link rel="stylesheet" href="modules/recordings/popup.css" type="text/css">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	</head>
	<body>

<?php


  $crypt = new Crypt();

	$REC_CRYPT_PASSWORD = (isset($amp_conf['AMPPLAYKEY']) && trim($amp_conf['AMPPLAYKEY']) != "")?trim($amp_conf['AMPPLAYKEY']):'moufdsuu3nma0';
  $file = $crypt->encrypt($_REQUEST['recording'],$REC_CRYPT_PASSWORD);
  $ufile = basename($_REQUEST['recording']);

  if (isset($file)) {
    echo("<br>");
    echo("<embed src='".$_SERVER['PHP_SELF']."?display=recordings&action=audio&recording=$file' width=300, height=20 autoplay=true loop=false></embed><br>");
    echo("<br><h1 class='popup_download'>playing: $ufile</h1><br>");
  }
?>
  </body>
</html>

