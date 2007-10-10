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
		<link rel="stylesheet" href="popup.css" type="text/css">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	</head>
	<body>

<?php

  $REC_CRYPT_PASSWORD = urlencode((isset($_REQUEST['cryptpass']) && trim($_REQUEST['cryptpass']) != "")?trim($_REQUEST['cryptpass']):'moufdsuu3nma0');

  $crypt = new Crypt();

  $file = $crypt->encrypt($_REQUEST['recording'],$REC_CRYPT_PASSWORD);
  $ufile = $_REQUEST['recording'];
  $file = $_REQUEST['recording'];

  if (isset($file)) {
    echo("<br>");
    echo("<embed src='audio.php?cryptpass=$REC_CRYPT_PASSWORD&recording=$file' width=300, height=20 autoplay=true loop=false></embed><br>");
    echo("<br><h1 class='popup_download'>playing: $file</h1><br>");
  }
?>
  </body>
</html>

