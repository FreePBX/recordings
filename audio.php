<?php

/**
 * @file
 * plays recording file
 */

if (isset($_GET['recording'])) {

  include_once("crypt.php");

  $REC_CRYPT_PASSWORD = (isset($_REQUEST['cryptpass']) && trim($_REQUEST['cryptpass']) != "")?trim($_REQUEST['cryptpass']):'moufdsuu3nma0';

  $crypt = new Crypt();

  $opath = $_GET['recording'];
  $path = $crypt->decrypt($opath,urldecode($REC_CRYPT_PASSWORD));
  $path=$opath;

  // strip ".." from path for security
  $path = preg_replace('/\.\./','',$path);
  
  // See if the file exists, otherwise check for extensions
  if (is_file("$path.wav")) { $path="$path.wav"; }
  elseif (is_file("$path.Wav")) { $path="$path.Wav"; }
  elseif (is_file("$path.WAV")) { $path="$path.WAV"; }
  elseif (is_file("$path.mp3")) { $path="$path.mp3"; }
  elseif (is_file("$path.gsm")) { $path="$path.gsm"; }
  elseif (!is_file($path)) { die("<b>404 File not found!: $opath </b>"); }

  // Gather relevent info about file
  $size = filesize($path);
  $name = basename($path);
  $extension = strtolower(substr(strrchr($name,"."),1));

  // This will set the Content-Type to the appropriate setting for the file
  $ctype ='';
  switch( $extension ) {
    case "mp3": $ctype="audio/mpeg"; break;
    case "wav": $ctype="audio/x-wav"; break;
    case "Wav": $ctype="audio/x-wav"; break;
    case "WAV": $ctype="audio/x-wav"; break;
    case "gsm": $ctype="audio/x-gsm"; break;

    // not downloadable
    default: die_freepbx("<b>404 File not found! foo</b>"); break ;
  }

  // need to check if file is mislabeled or a liar.
  $fp=fopen($path, "rb");
  if ($size && $ctype && $fp) {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: wav file");
    header("Content-Type: " . $ctype);
    header("Content-Disposition: attachment; filename=" . $name);
    header("Content-Transfer-Encoding: binary");
    header("Content-length: " . $size);
    fpassthru($fp);
  } 
}

?>
