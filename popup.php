<?php

/**
 * @file
 * popup window for playing recording
 */

chdir("..");
include_once("crypt.php");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <TITLE>ARI</TITLE>
    <link rel="stylesheet" href="popup.css" type="text/css">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  </head>
  <body>

<?php


  global $amp_conf;


  //$REC_CRYPT_PASSWORD="moufdsuu3nma0";
  $REC_CRYPT_PASSWORD= (isset($amp_conf['AMPPLAYKEY']) && trim($amp_conf['AMPPLAYKEY']) != "")?trim($amp_conf['AMPPLAYKEY]']):'moufdsuu3nma0';


  $crypt = new Crypt();

  $file = $crypt->encrypt($_GET['recording'],$REC_CRYPT_PASSWORD);
  $ufile = $_GET['recording'];
  $file = $_GET['recording'];

  //echo("<text>$file</text>");
  if (isset($file)) {
    echo("<br>");
    echo("<embed src='audio.php?recording=" . $file . "' width=300, height=20 autoplay=true loop=false></embed><br>");
    echo("<br><a class='popup_download' href=/admin/modules/recordings/audio.php?recording="  . $ufile . ">" . _("download: $ufile") . "</a><br>");
  }

?>

  </body>
</html>

