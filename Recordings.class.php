<?php
// vim: set ai ts=4 sw=4 ft=php:

class Recordings implements BMO {
	private $initialized = false;
	private $full_list = null;
	private $filter_list = array();
	private $temp;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->temp = $this->FreePBX->Config->get("ASTVARLIBDIR")."/sounds";
	}

	public function doConfigPageInit($page) {

	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {

	}

	public function showPage() {
		$media = $this->FreePBX->Media();
		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : "";
		switch($action) {
			case "edit":
			case "add":
				$supported = $media->getSupportedFormats();
				ksort($supported['in']);
				ksort($supported['out']);
				$langs = $this->FreePBX->Soundlang->getLanguages();
				$default = $this->FreePBX->Soundlang->getLanguage();
				$sysrecs = $this->getSystemRecordings();
				$html = load_view(__DIR__."/views/form.php",array("default" => $default, "supported" => $supported, "langs" => $langs, "sysrecs" => $sysrecs));
			break;
			default:
				$html = load_view(__DIR__."/views/grid.php",array());
			break;
		}
		return $html;
	}

	public function ajaxRequest($req, &$setting) {
		$setting['authenticate'] = false;
		$setting['allowremote'] = false;
		switch($req) {
			case "dialrecording":
			case "checkrecording":
			case "savebrowserrecording":
			case "saverecording":
			case "deleterecording":
			case "record":
			case "upload":
			case "grid":
				return true;
			break;
		}
		return false;
	}

	public function ajaxHandler() {
		switch($_REQUEST['command']) {
			case "savebrowserrecording":
				if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
					$time = time().rand(1,1000);
					$filename = $_REQUEST['filename']."-".$time.".wav";
					move_uploaded_file($_FILES["file"]["tmp_name"], $this->temp."/".$filename);
					return array("status" => true, "filename" => $_REQUEST['filename'], "localfilename" => $filename);
				}	else {
					return array("status" => false, "message" => _("Unknown Error"));
				}
			break;
			case "deleterecording":
				$filename = !empty($_POST['filename']) ? basename($_POST['filename']) : '';
				if(file_exists($this->temp."/".$filename.".wav")) {
					unlink($this->temp."/".$filename.".wav");
				}
				return array("status" => true);
			break;
			case "dialrecording":
				$astman = $this->FreePBX->astman;
				$status = $astman->originate(array(
					"Channel" => "Local/".$_POST['extension']."@from-internal",
					"Exten" => "dorecord",
					"Context" => "macro-systemrecording",
					"Priority" => 1,
					"Async" => "no",
					"CallerID" => _("System Recordings") . " <*77>",
					"Variable" => "RECFILE=".$_POST['filename']
				));
				if($status['Response'] == "Success") {
					return array("status" => true);
				} else {
					return array("status" => false, "message" => $status['Message']);
				}
			break;
			case "checkrecording":
				$filename = !empty($_POST['filename']) ? basename($_POST['filename']) : '';
				if(file_exists($this->temp."/".$filename.".finished")) {
					unlink($this->temp."/".$filename.".finished");
					return array("finished" => true, "filename" => $filename, "localfilename" => $filename.".wav", "recording" => false);
				} elseif(file_exists($this->temp."/".$filename.".wav")) {
					return array("finished" => false, "recording" => true);
				} else {
					return array("finished" => false, "recording" => false);
				}
			break;
			case "saverecording":
				$name = !empty($_POST['name']) ? basename($_POST['name']) : '';
				$filename = !empty($_POST['filename']) ? basename($_POST['filename']) : '';
				$time = time().rand(1,1000);
				$fname = $name."-".$time.".wav";
				if(file_exists($this->temp."/".$filename.".wav")) {
					rename($this->temp."/".$filename.".wav", $this->temp."/".$fname);
					return array("status" => true, "filename" => $name, "localfilename" => $fname);
				} else {
					return array("status" => false, "message" => _("File does not exist"));
				}
			break;
			case "grid";
				$all = $this->getAll();
				$languageNames = $this->FreePBX->Soundlang->getLanguages();
				foreach($all as &$recs) {
					foreach($recs['languages'] as &$lang) {
						$lang = isset($languageNames[$lang]) ? $languageNames[$lang] : $lang;
					}
					$recs['languages'] = implode(", ", $recs['languages']);
				}
				return $all;
			break;
			case "upload":
				foreach ($_FILES["files"]["error"] as $key => $error) {
					switch($error) {
						case UPLOAD_ERR_OK:
							$extension = pathinfo($_FILES["files"]["name"][$key], PATHINFO_EXTENSION);
							$extension = strtolower($extension);
							$supported = $this->FreePBX->Media->getSupportedFormats();
							if(in_array($extension,$supported['in'])) {
								$tmp_name = $_FILES["files"]["tmp_name"][$key];
								$dname = $_FILES["files"]["name"][$key];
								$id = time().rand(1,1000);
								$name = pathinfo($_FILES["files"]["name"][$key],PATHINFO_FILENAME) . '-' . $id . '.' . $extension;
								move_uploaded_file($tmp_name, $this->temp."/".$name);
								return array("status" => true, "filename" => pathinfo($dname,PATHINFO_FILENAME), "localfilename" => $name, "id" => $id);
							} else {
								return array("status" => false, "message" => _("Unsupported file format"));
								break;
							}
						break;
						case UPLOAD_ERR_INI_SIZE:
							return array("status" => false, "message" => _("The uploaded file exceeds the upload_max_filesize directive in php.ini"));
						break;
						case UPLOAD_ERR_FORM_SIZE:
							return array("status" => false, "message" => _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"));
						break;
						case UPLOAD_ERR_PARTIAL:
							return array("status" => false, "message" => _("The uploaded file was only partially uploaded"));
						break;
						case UPLOAD_ERR_NO_FILE:
							return array("status" => false, "message" => _("No file was uploaded"));
						break;
						case UPLOAD_ERR_NO_TMP_DIR:
							return array("status" => false, "message" => _("Missing a temporary folder"));
						break;
						case UPLOAD_ERR_CANT_WRITE:
							return array("status" => false, "message" => _("Failed to write file to disk"));
						break;
						case UPLOAD_ERR_EXTENSION:
							return array("status" => false, "message" => _("A PHP extension stopped the file upload"));
						break;
					}
				}
				return array("status" => false, "message" => _("Can Not Find Uploaded Files"));
			break;
		}
	}

	public function addRecording($name,$description,$files,$fcode=0,$fcode_pass='') {
		$sql = "INSERT INTO displayname, description, filename, fcode, fcode_pass VALUES(?,?,?,?,?)";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($name, $description, $files, $fcode, $fcode_pass));
	}

	public function delRecording($id) {
		$sql = "DELETE FROM recordings WHERE id = ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($id));
	}

	public function getRecordingsById($id) {
		return $this->getRecordingById($id);
	}

	public function getRecordingById($id) {
		$sql = "SELECT * FROM recordings where id= ?";
		$sth = $this->db->prepare($sql);
		$sth->execute(array($id));
		return $sth->fetch(\PDO::FETCH_ASSOC);
	}

	public function getFilenameById($id) {
		$res = $this->getRecordingsById($id);
		if (empty($res)) {
			return '';
		}
		return $res['filename'];
	}

	public function getSystemRecordings() {
		$files = $this->getdir($this->temp);
		$final = array();
		foreach($files as &$file) {
			$file = str_replace($this->temp."/","",$file);
			if(preg_match("/^(\w{2}_\w{2}|\w{2})\/(.*)\.([a-z0-9]{2,})/i",$file,$matches)) {
				$lang = $matches[1];
				$name = $matches[2];
				if(substr($name, 0, 1) == ".") {
					continue;
				}
				$format = $matches[3];
				if(!isset($final[$name])) {
					$final[$name] = array(
						"name" => $name,
						"languages" => array(
							$lang => $lang
						),
						"formats" => array(
							$format => $format
						),
						"paths" => array(
							$lang => $lang."/".$name
						)
					);
				} else {
					$final[$name]['languages'][$lang] = $lang;
					$final[$name]['formats'][$format] = $format;
					$final[$name]['paths'][$lang] = $lang."/".$name;
				}
			}
		}
		ksort($final);
		return $final;
	}

	private function getdir($snddir) {
		$dir = opendir($snddir);
		$files = Array();
		while ($fn = readdir($dir)) {
			if ($fn == '.' || $fn == '..') { continue; }
			if (is_dir($snddir.'/'.$fn)) {
				$files = array_merge($this->getdir($snddir.'/'.$fn), $files);
				continue;
			}
			$files[] = $snddir.'/'.$fn;
		}
		return $files;
	}

	public function getAll() {
		$sql = "SELECT * FROM recordings ORDER BY displayname";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$full_list = $sth->fetchAll(\PDO::FETCH_ASSOC);

		foreach($full_list as &$item) {
			$files = explode("&",$item['filename']);
			$item['files'] = array();
			$langs = array();
			foreach($files as $file) {
				$item['files'][$file] = $this->fileStatus($file);
				$langs = array_merge(array_keys($item['files'][$file]),$langs);
			}
			$item['languages'] = $langs;
			$item['missing']['languages'] = array();
			$item['missing']['formats'] = array();
			foreach($files as $file) {
				$diff = array_diff($langs,array_keys($item['files'][$file]));
				if(!empty($diff)) {
					$item['missing']['languages'][$file] = $diff;
				}
			}

		}
		return $full_list;
	}

	public function fileStatus($file) {
		$data = array();
		foreach(glob($this->temp."/*",GLOB_ONLYDIR) as $langdir) {
			$lang = basename($langdir);
			foreach(glob($langdir."/".$file."*") as $f) {
				$parts = pathinfo($f);
				$data[$lang][$parts['extension']] = basename($f);
			}
		}
		return $data;
	}

	public function getAllRecordings($compound = true) {
		if ($this->initialized) {
			return ($compound ? $this->full_list : $this->filter_list);
		}
		$this->initialized = true;

		$sql = "SELECT * FROM recordings where displayname <> '__invalid' ORDER BY displayname";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$this->full_list = $sth->fetchAll(\PDO::FETCH_ASSOC);
		foreach($this->full_list as &$item) {
			//TODO: Find instances of this and remove it!
			// Make array backward compatible, put first 4 columns as numeric
			$item[0] = $item['id'];
			$item[1] = $item['displayname'];
			$item[2] = $item['filename'];
			$item[3] = $item['description'];
			if (strstr($item['filename'],'&') === false) {
				$this->filter_list[] = $item;
			}
		}
		return ($compound ? $this->full_list : $this->filter_list);
	}
}
