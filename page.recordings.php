<?php
if (!defined('FREEPBX_IS_AUTH')) {
	die('No direct script access allowed');
}
echo FreePBX::Recordings()->showPage();
function recordings_return_bytes($val)
{
	$val = trim((string) $val);
	$numericPart = rtrim($val, 'GgMmKk');
	$stringPart = substr($val, -1);

	$result = match (strtolower($stringPart)) {
		'g' => $numericPart * 1024,
		'm' => $numericPart * 1024 * 1024,
		'k' => $numericPart * 1024 * 1024 * 1024,
		default => $numericPart,
	};

	return $result;
}
?>
<script>
	var post_max_size = <?php echo recordings_return_bytes(ini_get('post_max_size')) ?>;
	var upload_max_filesize = <?php echo recordings_return_bytes(ini_get('upload_max_filesize')) ?>;
	var max_size = (upload_max_filesize < post_max_size) ? upload_max_filesize : post_max_size;
</script>