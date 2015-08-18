<?php
// vim: set ai ts=4 sw=4 ft=php:

class Recordings implements BMO {
	private $initialized = false;
	private $full_list = null;
	private $filter_list = array();

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
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
			case "add":
				$supported = $media->getSupportedFormats();
				ksort($supported['in']);
				ksort($supported['out']);
				$langs = $this->FreePBX->Soundlang->getLanguages();
				$html = load_view(__DIR__."/views/form.php",array("supported" => $supported, "langs" => $langs));
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
			case "grid";
				return $this->getAll();
			break;
			case "record":
				if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
					$tmp_path = sys_get_temp_dir() . "/recordings";
					if(!file_exists($tmp_path)) {
						if(!mkdir($tmp_path)) {
							return array("status" => false, "message" => sprintf(_("Cant Create Temp Directory: %s"),$tmp_path));
						}
					}

					$tmp_name = $_FILES["file"]["tmp_name"];
					$name = $_FILES["file"]["name"];

					move_uploaded_file($tmp_name, $tmp_path."/".$name);
				}	else {
					$return = array("status" => false, "message" => _("Unknown Error"));
					break;
				}
				$return = array("status" => true, "message" => "");
			break;
			case "upload":
				$temp = sys_get_temp_dir() . "/recordings";
				if(!file_exists($temp)) {
					if(!mkdir($temp)) {
						return array("status" => false, "message" => sprintf(_("Cant Create Temp Directory: %s"),$temp));
					}
				}
				foreach ($_FILES["files"]["error"] as $key => $error) {
					switch($error) {
						case UPLOAD_ERR_OK:
							$extension = pathinfo($_FILES["files"]["name"][$key], PATHINFO_EXTENSION);
							$extension = strtolower($extension);
							if($extension == 'pdf' || $extension == 'tiff' || $extension == 'tif') {
								$tmp_name = $_FILES["files"]["tmp_name"][$key];
								$dname = $_FILES["files"]["name"][$key];
								$id = time();
								$name = pathinfo($_FILES["files"]["name"][$key],PATHINFO_FILENAME) . '-' . $id . '.' . $extension;
								move_uploaded_file($tmp_name, $temp."/".$name);
								return array("status" => true, "filename" => $dname, "localfilename" => $name, "id" => $id);
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

	public function getRecordingsById($id) {
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

	public function getAll() {
		$sql = "SELECT * FROM recordings where displayname <> '__invalid' ORDER BY displayname";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$full_list = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $full_list;
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
