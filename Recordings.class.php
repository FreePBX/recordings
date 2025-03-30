<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules;
use featurecode;
use Media\Media;
use ForceUTF8\Encoding;
use BMO;
use PDO;
use Exception;
#[\AllowDynamicProperties]
class Recordings extends \DB_Helper implements BMO {
	private bool $initialized = false;
	private $full_list = null;
	private array $filter_list = [];
	private readonly string $temp;
	private readonly string $path;
	private string $fcbase = "*29";
	/** Extensions to show in the convert to section
	 * Limited on purpose because there are far too many,
	 * Most of which are not supported by asterisk
	 */
	private array $convert = ["sln16", "sln48", "alaw", "g729", "wav", "sln", "g722", "ulaw", "gsm"];

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception('Not given a FreePBX Object');
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->temp = $this->FreePBX->Config->get("ASTSPOOLDIR") . "/tmp";
		if(!file_exists($this->temp)) {
			mkdir($this->temp,0777,true);
		}
		$this->path = $this->FreePBX->Config->get("ASTVARLIBDIR")."/sounds";
	}

	public function getPath() {
		return $this->path;
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
	
	/**
	 * getAllDriversInfo
	 *
	 * @return array
	 */
	public function getAllDriversInfo()
	{
		$drivers = [];
		foreach (glob(__DIR__ . "/drivers/*.php") as $driver) {
			$name 	= basename($driver);
			$name 	= explode(".", $name);
			$name 	= $name[0];
			$name 	= ucfirst(strtolower($name));
			$class  = $this->prepareDriverClass($name);
			$drivers[strtolower($name)] = $class::getInfo();
		}
		return $drivers;
	}
	
	/**
	 * prepareDriverClass
	 *
	 * @param  string $driver
	 * @return string
	 */
	private function prepareDriverClass($driver)
	{
		$driver = basename($driver);
		$driver = ucfirst(strtolower($driver));
		if (!file_exists(__DIR__ . "/drivers/" . $driver . ".php")) {
			throw new \Exception( sprintf(_("Driver %s does not exist!"), $driver));
		}
		return "\FreePBX\modules\Recordings\drivers\\" . $driver;
	}
	
	/**
	 * getTTSAIFrom
	 *
	 * @param  string $engine
	 * @return string
	 */
	public function getTTSAIFrom($engine){
		switch($engine){			
			case "none":
				$result = "";
				break;
			case "Elevenlabs":
				$apiKey 	= $this->getConfig($engine);
				if(!empty($apiKey)){
					$converter 	= new \FreePBX\modules\Recordings\drivers\Elevenlabs($apiKey);
					$voices 	= $converter->getAvailableVoices();
					if(!empty($voices)){
						$vars 	 	= ["voices" => $voices];
						$result 	= load_view(__DIR__."/views/form-".$engine.".php",$vars);
					}
					else{
						$vars 	 	= ["engine" => $engine, "apikey" => $apiKey, "error" => _("Invalid API Key.")];
						$result 	= load_view(__DIR__."/views/form-apikey.php",$vars);
					}
				}
				else{
					$vars 	 	= ["engine" => $engine, "apikey" => ""];
					$result 	= load_view(__DIR__."/views/form-apikey.php",$vars);
				}
				break;
			case "OpenAI":
				$apiKey 	= $this->getConfig($engine);
				if(!empty($apiKey)){
					$converter 	= new \FreePBX\modules\Recordings\drivers\Openai($apiKey);
					$voices 	= $converter->getAvailableVoices();
					$vars 	 	= ["voices" => $voices];
					$result 	= load_view(__DIR__."/views/form-".$engine.".php",$vars);
				}
				else{
					$vars 	 	= ["engine" => $engine];
					$result 	= load_view(__DIR__."/views/form-apikey.php",$vars);
				}
				break;
		}
		return $result;
	}

	public function getRightNav($request) {
		if(isset($request['action']) && ($request['action'] == 'edit' || $request['action'] == 'add')) {
			return load_view(__DIR__."/views/bnav.php",[]);
		}
		return '';
	}

	public function getActionBar($request){
		$buttons = [];
		if(isset($request['action']) && ($request['action'] == "add" || $request['action'] == "edit")) {
			$buttons = ['submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _("Submit")], 'delete' => ['name' => 'delete', 'id' => 'delete', 'value' => _("Delete")], 'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _("Reset")]];
			if($request['action'] == "add") {
				unset($buttons['delete']);
			}
		}
		return $buttons;
	}

	public function showPage() {
		$media = $this->FreePBX->Media();
		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : "";
		$langs = $this->FreePBX->Soundlang->getLanguages();
		$missingLangs = [];
		switch($action) {
			case "edit":
				$data = $this->getRecordingById($_REQUEST['id']);
				$fcc = new featurecode("recordings", 'edit-recording-'.$_REQUEST['id']);
				$rec_code = $fcc->getCode();
				$data['rec_code'] = ($rec_code != '') ? $rec_code : $this->fcbase.$_REQUEST['id'];
				$usedLangs = [];
				foreach($data['soundlist'] as $l) {
					foreach($l['languages'] as $lan) {
						if(!in_array($lan,$usedLangs)) {
							$usedLangs[] = $lan;
						}
					}
				}
				foreach($usedLangs as $l) {
					if(!isset($langs[$l])) {
						$missingLangs[] = $l;
					}
				}
			case "add":
				$all_records = $this->getAllRecordingsList();
				$tmp_id = '';
				if(isset($_REQUEST['id'])){
					$tmp_id = $_REQUEST['id'];
				}
				$record_names = [];
				foreach($all_records as $tmp_record) {
					if($tmp_record['id'] != $tmp_id){
						$record_names[] = $tmp_record['displayname'];
					}
				}
				$data ??= [];
				$supported = $media->getSupportedFormats();
				ksort($supported['in']);
				ksort($supported['out']);
				$default = $this->FreePBX->Soundlang->getLanguage();
				$sysrecs = $this->getSystemRecordings();
				$jsonsysrecs = json_encode($sysrecs);
				$message = '';
				if(json_last_error() !== JSON_ERROR_NONE) {
					$message = sprintf(_("There was an error reading system recordings (%s)"),json_last_error_msg());
					freepbx_log(FPBX_LOG_WARNING,_("JSON decode error: ").json_last_error_msg());
					$jsonsysrecs = [];
					$sysrecs = [];
				}
				$supportedHTML5 = $media->getSupportedHTML5Formats();
				$convertto = array_intersect($supported['out'], $this->convert);
				$recformat = $this->FreePBX->Config->get("MIXMON_FORMAT");
				$recformat = empty($recformat) || !in_array($recformat,$this->convert) ? "wav" : $recformat;
				$drivers   		= $this->getAllDriversInfo();
				$html = load_view(__DIR__."/views/form.php",[
					"missingLangs" 		=> $missingLangs, 
					"langs" 			=> $langs, 
					"recformat" 		=> $recformat, 
					"message" 			=> $message, 
					"jsonsysrecs" 		=> $jsonsysrecs, 
					"convertto" 		=> $convertto, 
					"supportedHTML5" 	=> implode(",",$supportedHTML5), 
					"data" 				=> $data, 
					"default" 			=> $default, 
					"supported" 		=> $supported, 
					"langs" 			=> $langs, 
					"sysrecs" 			=> $sysrecs, 
					"record_names" 		=> $record_names,
					"drivers" 			=> $drivers, 
				]);
			break;
			case "delete":
				$this->delRecording($_REQUEST['id']);
			default:
				$html = load_view(__DIR__."/views/grid.php",["langs" => $langs]);
			break;
		}
		return $html;
	}

	public function ajaxRequest($req, &$setting) {
		switch($req) {
			case "savebrowserrecording":
			case "deleterecording":
			case "checkrecording":
			case "dialrecording":
			case "saverecording":
			case "gethtml5byid":
			case "gethtml5":
			case "playback":
			case "download":
			case "convert":
			case "record":
			case "upload":
			case "save":
			case "grid":
			case "ttsform":
			case "setapikey":
			case "getapikey":
			case "ttsConvert":
				return true;
			break;
		}
		return false;
	}

	public function ajaxCustomHandler() {
		switch($_REQUEST['command']) {
			case "playback":
			case "download":
				$media = $this->FreePBX->Media();
				$media->getHTML5File($_REQUEST['file']);
			break;
		}
	}

	public function ajaxHandler() {
		$requests = freepbxGetSanitizedRequest();
		switch($requests['command']) {
			case "ttsform":
				return $this->getTTSAIFrom($requests['engine']);
			case "setapikey":
				if(!empty($requests['key']) && !empty($requests['engine'])){
					$apikey = $requests['key'];
					$engine = $requests['engine'];
					$this->setConfig($engine, $apikey);
					return $this->getTTSAIFrom($engine);
				}
				return "";
			case "getapikey":
				if(!empty($requests['engine'])){
					$engine = $requests['engine'];
					return $this->getConfig($engine);
				}
				return "";
			case "ttsConvert":
				$engine 	= ucfirst(strtolower($requests['engine'])) ?? '';
				$filename 	= $requests['file_name'] ?? '';
				$text 		= $requests['text'] ?? '';
				$voiceId 	= $requests['voiceId'] ?? '';
				$lang 		= $requests['langCode'] ?? 'en';
				$stability 	= floatval($requests['stability'] ?? 0.5);
				$similarity = floatval($requests['similarity'] ?? 0.5);
			
				if(empty($engine) || empty($filename) || empty($text) || empty($voiceId)){
					return ["status" => false, "error" => _("Missing parameters")];					
				}

				$apiKey = $this->getConfig($engine);
				if (!$apiKey) {
					return ["success" => false, "error" => _("Invalid API Key")];
				}

				try {
					$className  = "\\FreePBX\\modules\\Recordings\\drivers\\$engine";
					$converter  = new $className($apiKey);
					$audioFile 	= $converter->convertToAudio($filename, $text, $voiceId, $lang, $stability, $similarity);
					if(!empty($audioFile["status"])){
						return ["status" => false, "message" => $audioFile["message"]];
					}
				}
				catch (Exception $e) {
					return ["status" => false, "error" => sprintf(_("Error Exception: %s."), $e->getMessage())];
				}				



				if($audioFile) {
					return $audioFile;
				} 
				else{
					return ["status" => false, "message" => _("Error While Convertion")];
				}

				return false;
			case "gethtml5byid":
				$media = $this->FreePBX->Media();
				$file = "";
				switch($_POST['type']) {
					case "system":
						$path = $this->FreePBX->Config->get("ASTVARLIBDIR")."/sounds";
						$rec = $this->getRecordingById($_REQUEST['id']);
						if(!empty($rec) && !empty($rec['soundlist'])) {
							//if there are multiple files (compound) then just play the first
							//This probably needs to be changed to combine them but
							//the code for a combine doesnt exist yet *sigh*
							$fileInfo = reset($rec['soundlist']);
							$files = $this->fileStatus($fileInfo['name']);
							if(!empty($files['en'])) {
								$file = $path."/en/".reset($files['en']);
							} else {
								$lang = key($files);
								$data = reset($files);
								$file = $path."/".$lang."/".reset($data);
							}
						}
					break;
					case "temp":
						$file = basename((string) $_REQUEST['id']);
						$file = $this->temp . "/" . $file;
					break;
					default:
					break;
				}
				if(!empty($file)) {
					if(file_exists($file)) {
						$media->load($file);
						$files = $media->generateHTML5();
						$final = [];
						foreach($files as $format => $name) {
							$final[$format] = "ajax.php?module=recordings&command=playback&file=".urlencode((string) $name);
						}
						return ["status" => true, "files" => $final];
					}
				}
				return ["status" => false, "message" => _("File does not exist")];
			break;
			case "gethtml5":
				$media = $this->FreePBX->Media();
				$lang = basename((string) $_POST['language']);
				$info = pathinfo((string) $_POST['filenames'][$lang]);
				$path = ($_POST['temporary'][$lang]) ? $this->temp : $this->path;
				if(empty($info['extension'])) {
					$file = preg_replace("/^".$lang."\//i", "", (string) $_POST['filenames'][$lang]);
					$status = $this->fileStatus($file, !($_POST['temporary'][$lang]));
					if(!empty($status[$lang])) {
						$filename = $path . "/" . $lang . "/" . reset($status[$lang]);
					}
				} else {
					$filename = $path . "/" . $_POST['filenames'][$lang];
				}

				// Check if the audio file comes from AI or not RIFF.
				$this->fixeRIFF($filename);

				$media->load($filename);
				$files = $media->generateHTML5();
				$final = [];
				foreach($files as $format => $name) {
					$final[$format] = "ajax.php?module=recordings&command=playback&file=".$name;
				}
				return ["status" => true, "files" => $final];
			break;
			case "convert":
				return $this->convertFiles($_POST);
			break;
			case "save":
				$data = $_POST;
				$data['name'] = filter_var($data['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				$data['description'] = filter_var($data['description'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				if($data['id'] == "0" || !empty($data['id'])) {
					$this->updateRecording($data['id'],$data['name'],$data['description'],implode("&",$data['playback']),$data['fcode'],$data['fcode_pass'],$data['language']);
				} else {
					$this->addRecording($data['name'],$data['description'],implode("&",$data['playback']),$data['fcode'],$data['fcode_pass'],$data['language']);
				}
				if(!empty($data['remove'])) {
					foreach($data['remove'] as $file) {
						$file = basename((string) $file);
						if(file_exists($this->temp."/".$file)) {
							unlink($this->temp."/".$file);
						}
					}
				}
				return ["status" => true];
			break;
			case "savebrowserrecording":
				if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
					$time = time().random_int(1,1000);
					$filename = basename((string) $_REQUEST['filename'])."-".$time.".wav";
					move_uploaded_file($_FILES["file"]["tmp_name"], $this->temp."/".$filename);
					return ["status" => true, "filename" => $_REQUEST['filename'], "localfilename" => $filename];
				}	else {
					return ["status" => false, "message" => _("Unknown Error")];
				}
			break;
			case "deleterecording":
				$files = json_decode(html_entity_decode((string) $_POST['filenames']),true, 512, JSON_THROW_ON_ERROR);
				foreach($files as $lang => $file) {
					$file = basename((string) $file);
					if(file_exists($this->temp."/".$file)) {
						unlink($this->temp."/".$file);
					}
				}
				return ["status" => true];
			break;
			case "dialrecording":
				$astman = $this->FreePBX->astman;
				$status = $astman->originate(["Channel" => "Local/".$_POST['extension']."@from-internal", "Exten" => "dorecord", "Context" => "systemrecording-gui", "Priority" => 1, "Async" => "no", "CallerID" => _("System Recordings"), "Variable" => "RECFILE=".$this->temp."/".basename((string) $_POST['filename'])]);
				if($status['Response'] == "Success") {
					return ["status" => true];
				} else {
					return ["status" => false, "message" => $status['Message']];
				}
			break;
			case "checkrecording":
				$this->FreePBX->Media;
				$filename = !empty($_POST['filename']) ? basename((string) $_POST['filename']) : '';
				$filename = Media::cleanFileName($filename);
				if(file_exists($this->temp."/".$filename.".finished")) {
					unlink($this->temp."/".$filename.".finished");
					return ["finished" => true, "filename" => $filename, "localfilename" => $filename.".wav", "recording" => false];
				} elseif(file_exists($this->temp."/".$filename.".wav")) {
					return ["finished" => false, "recording" => true];
				} else {
					return ["finished" => false, "recording" => false];
				}
			break;
			case "saverecording":
				$this->FreePBX->Media;
				$name = !empty($_POST['name']) ? basename((string) $_POST['name']) : '';
				$filename = !empty($_POST['filename']) ? basename((string) $_POST['filename']) : '';
				$filename = Media::cleanFileName($filename);
				$time = time().random_int(1,1000);
				$fname = Media::cleanFileName($name)."-".$time.".wav";
				if(file_exists($this->temp."/".$filename.".wav")) {
					rename($this->temp."/".$filename.".wav", $this->temp."/".$fname);
					return ["status" => true, "filename" => $name, "localfilename" => $fname];
				} else {
					return ["status" => false, "message" => _("File does not exist")];
				}
			break;
			case "grid";
				$all = $this->getAllRecordingsList();
				$languageNames = $this->getLanguages();
				foreach($all as &$recs) {
					foreach($recs['languages'] as &$lang) {
						$lang = $languageNames[$lang] ?? $lang;
					}
					$recs['languages'] = implode(", ", $recs['languages']);
				}
				return $all;
			break;
			case "upload":
				foreach ($_FILES["files"]["error"] as $key => $error) {
					switch($error) {
						case UPLOAD_ERR_OK:
							$extension = pathinfo((string) $_FILES["files"]["name"][$key], PATHINFO_EXTENSION);
							$extension = strtolower($extension);
							$supported = $this->FreePBX->Media->getSupportedFormats();
							if(in_array($extension,$supported['in'])) {
								$tmp_name = $_FILES["files"]["tmp_name"][$key];
								$dname = Media::cleanFileName($_FILES["files"]["name"][$key]);
								$dname = basename((string) $dname);
								$dname = pathinfo($dname,PATHINFO_FILENAME);
								$id = time().random_int(1,1000);
								$name = $dname . '-' . $id . '.' . $extension;
								move_uploaded_file($tmp_name, $this->temp."/".$name);
								return ["status" => true, "filename" => pathinfo($dname,PATHINFO_FILENAME), "localfilename" => $name, "id" => $id];
							} else {
								return ["status" => false, "message" => _("Unsupported file format")];
								break;
							}
						break;
						case UPLOAD_ERR_INI_SIZE:
							return ["status" => false, "message" => _("The uploaded file exceeds the upload_max_filesize directive in php.ini")];
						break;
						case UPLOAD_ERR_FORM_SIZE:
							return ["status" => false, "message" => _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form")];
						break;
						case UPLOAD_ERR_PARTIAL:
							return ["status" => false, "message" => _("The uploaded file was only partially uploaded")];
						break;
						case UPLOAD_ERR_NO_FILE:
							return ["status" => false, "message" => _("No file was uploaded")];
						break;
						case UPLOAD_ERR_NO_TMP_DIR:
							return ["status" => false, "message" => _("Missing a temporary folder")];
						break;
						case UPLOAD_ERR_CANT_WRITE:
							return ["status" => false, "message" => _("Failed to write file to disk")];
						break;
						case UPLOAD_ERR_EXTENSION:
							return ["status" => false, "message" => _("A PHP extension stopped the file upload")];
						break;
					}
				}
				return ["status" => false, "message" => _("Can Not Find Uploaded Files")];
			break;
		}
	}

	/**
	 * Check if the audio file comes from AI and doesn't include RIFF data.
	 * @param string  $filename File Name
	 */
	public function fixeRIFF($filename){
		exec("file -b $filename | grep 'RIFF' ", $out, $ret);
		if($ret === 0 ){
			dbug(_("An error is occured on RIFF detection."));
		}
		if(empty($out[0])){
			$f 		= str_replace("custom/", "", $_POST["file"]);			
			$cmd 	= "mv ".$this->temp."/$f.wav $filename";
			exec($cmd, $out, $ret);
		}
	}

	public function getLanguages(){
		$languageNames = $this->FreePBX->Soundlang->getLanguages();
		return is_array($languageNames)?$languageNames:[];
	}

	/**
	 * Add New Recording
	 * @param string  $name        The recording short name
	 * @param string  $description The recording long name
	 * @param string  $files       & separated list of files to playback
	 * @param integer $fcode       Feature Code number_format
	 * @param string  $fcode_pass  Feature code password
	 */
	public function addRecording($name,$description,$files,$fcode=0,$fcode_pass='',$fcode_lang='') {
		$sql = "INSERT INTO recordings (displayname, description, filename, fcode, fcode_pass, fcode_lang) VALUES(?,?,?,?,?,?)";
		$sth = $this->db->prepare($sql);
		$sth->execute([$name, $description, $files, $fcode, $fcode_pass, $fcode_lang]);
		$id = $this->db->lastInsertId();
		if ($fcode == 1) {
			// Add the feature code if it is needed
			//
			$fcc = new featurecode('recordings', 'edit-recording-'.$id);
			$fcc->setDescription("Edit Recording: $name");
			$fcc->setDefault('*29'.$id);
			$fcc->setProvideDest();
			$fcc->update();
			unset($fcc);
		}
		needreload();
		return $id;
	}

	/**
	 * Add New Recording
	 * @param string  $name        The recording short name
	 * @param string  $description The recording long name
	 * @param string  $files       & separated list of files to playback
	 * @param integer $fcode       Feature Code number_format
	 * @param string  $fcode_pass  Feature code password
	 */
	public function addRecordingWithId($id,$name,$description,$files,$fcode=0,$fcode_pass='', $fcode_lang=''){
		$sql = "INSERT INTO recordings (id,displayname, description, filename, fcode, fcode_pass, fcode_lang) VALUES(?,?,?,?,?,?,?)";
		$sth = $this->db->prepare($sql);
		$sth->execute([$id, $name, $description, $files, $fcode, $fcode_pass, $fcode_lang]);
		$id = $this->db->lastInsertId();
		if ($fcode == 1) {
			// Add the feature code if it is needed
			//
			$fcc = new featurecode('recordings', 'edit-recording-'.$id);
			$fcc->setDescription("Edit Recording: $name");
			$fcc->setDefault('*29'.$id);
			$fcc->setProvideDest();
			$fcc->update();
			unset($fcc);
		}
		needreload();
		return $id;
	}

	/**
	 * Update Recording by ID
	 * @param integer $id          The recording ID
	 * @param string  $name        The recording short name
	 * @param string  $description The recording long name
	 * @param string  $files       & separated list of files to playback
	 * @param integer $fcode       Feature Code number_format
	 * @param string  $fcode_pass  Feature code password
	 */
	public function updateRecording($id,$name,$description,$files,$fcode=0,$fcode_pass='',$fcode_lang='') {
		$sql = "UPDATE recordings SET displayname = ?, description = ?, filename = ?, fcode = ?, fcode_pass = ?, fcode_lang = ? WHERE id = ?";
		$sth = $this->db->prepare($sql);
		$res = $sth->execute([$name, $description, $files, $fcode, $fcode_pass, $fcode_lang, $id]);
		if ($fcode != 1) {
			// delete the feature code if it existed
			$fcc = new featurecode('recordings', 'edit-recording-'.$id);
			$fcc->delete();
			unset($fcc);
		} else {
			// Add the feature code if it is needed
			$fcc = new featurecode('recordings', 'edit-recording-'.$id);
			$fcc->setDescription("Edit Recording: $name");
			$fcc->setDefault('*29'.$id);
			$fcc->setProvideDest();
			$fcc->update();
			unset($fcc);
		}
		needreload();
		return $res;
	}

	/**
	 * Delete a recording by ID
	 * @param  integer $id The recording ID
	 */
	public function delRecording($id) {
		//Need to delete the featurecode belong to this recording
		//FREEPBX-14870 Removed System Recording does not remove Feature Code
		$sqlfc = "DELETE FROM featurecodes WHERE modulename = 'recordings' and defaultcode = ?";
		$sthi = $this->db->prepare($sqlfc);
		$fcid = '*29'.$id;
		$sthi->execute([$fcid]);
		// Fc delettion over ...
		$sql = "DELETE FROM recordings WHERE id = ?";
		$sth = $this->db->prepare($sql);
		$sth->execute([$id]);
		needreload();
	}

	/**
	 * Alias of getRecordingById
	 * @param  integer $id The recording ID
	 * @return array     Array of information about the recording
	 */
	public function getRecordingsById($id) {
		return $this->getRecordingById($id);
	}

	/**
	 * Get a recording by it's ID
	 * @param  integer $id The recording ID
	 * @return array     Array of information about the recording
	 */
	public function getRecordingById($id) {
		$sql = "SELECT * FROM recordings where id= ?";
		$sth = $this->db->prepare($sql);
		if ($sth->execute([$id])) {
			$data = $sth->fetch(PDO::FETCH_ASSOC);
		}
		if (empty($data)) {
			return [];
		}
		$data['soundlist'] = [];
		$data['playbacklist'] = [];
		$langs = [];
		$files = explode("&",(string) $data['filename']);
		$codecs = [];
		foreach($files as $file) {
			$status = $this->fileStatus($file);
			$data['soundlist'][$file] = ["name" => $file, "temporary" => [], "languages" => [], "filenames" => [], "codecs" => []];
			foreach($status as $lang => $formats) {
				foreach($formats as $format => $filename) {
					$data['soundlist'][$file]['filenames'][$lang] = $lang."/".$file;
					$data['soundlist'][$file]['codecs'][$lang][] = $format;
					if(!in_array($format,$codecs)) {
						$codecs[] = $format;
					}
				}
				$data['soundlist'][$file]['languages'][] = $lang;
				$data['soundlist'][$file]['temporary'][$lang] = 0;
				$data['codecs'] = $codecs;
			}
			$data['playbacklist'][] = $file;
		}
		return $data;
	}

	/**
	 * Get filename(s) by recording ID
	 * @param  integer $id The recording ID
	 * @return array     Array of filenames
	 */
	public function getFilenameById($id) {
		$res = $this->getRecordingsById($id);
		if (empty($res)) {
			return '';
		}
		return $res['filename'];
	}

	/**
	 * Get all system recordings
	 * @return array Array of system recordings
	 */
	public function getSystemRecordings() {
		$files = $this->getdir($this->path);
		$final = [];
		foreach($files as &$file) {
			$file = str_replace($this->path."/","",(string) $file);
			if(preg_match("/^(\w{2}_\w{2}|\w{2}|\w{2}_\d{3})\/(.*)\.([a-z0-9]{2,})/i",$file,$matches)) {
				$lang = $matches[1];
				$name = Encoding::fixUTF8($matches[2]);
				if(str_starts_with((string) $name, ".") || preg_match("/^(?:CHANGES|CREDITS|LICENSE)-asterisk-(?:core|extra)-(?:\w\w\_\w\w|\w\w)-(?:\d|\.)*$/i", (string) $name)) {
					continue;
				}
				$format = $matches[3];
				if(!isset($final[$name])) {
					$final[$name] = ["name" => $name, "languages" => [$lang => $lang], "formats" => [$format => $format], "paths" => [$lang => $lang."/".$name]];
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

	/**
	 * Get all recordings in said Directory
	 * @param  string $snddir The directory to scan
	 * @return array         Array of files
	 */
	private function getdir($snddir) {
		$dir = opendir($snddir);
		$files = [];
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

	/**
	 * Get all recordings
	 * @return array Array of recordings
	 */
	public function getAllRecordingsList() {
		$sql = "SELECT * FROM recordings ORDER BY displayname";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$full_list = $sth->fetchAll(PDO::FETCH_ASSOC);
		foreach($full_list as &$item) {
			$files = explode("&",(string) $item['filename']);
			$item['files'] = [];
			$langs = [];
			foreach($files as $file) {
				$item['files'][$file] = $this->fileStatus($file);
				foreach(array_keys($item['files'][$file]) as $l) {
					if(!in_array($l,$langs)) {
						$langs[] = $l;
					}
				}
			}
			$item['languages'] = $langs;
			$item['missing']['languages'] = [];
			$item['missing']['formats'] = [];
			foreach($files as $file) {
				$diff = array_diff($langs,array_keys($item['files'][$file]));
				if(!empty($diff)) {
					$item['missing']['languages'][$file] = $diff;
				}
			}

		}
		return $full_list;
	}

	/**
	 * Status of filename
	 * EG are there multiple formats or languages
	 * @param  string $file   The filename
	 * @param  boolean $system Is this a system file or a temp file
	 * @return array         Array of file information
	 */
	public function fileStatus($file, $system = true) {
		$data = [];
		$path = ($system) ? $this->path : $this->temp;
		foreach(glob($path."/*",GLOB_ONLYDIR) as $langdir) {
			$lang = basename((string) $langdir);
			foreach(glob($langdir."/".$file.".*") as $f) {
				$parts = pathinfo((string) $f);
				if(empty($parts['extension'])) {
					continue; //wtf is this file?
				} elseif($parts['extension'] == "finished") {
					unlink($f);
					continue;
				}
				$data[$lang][$parts['extension']] = str_replace($langdir."/","",(string) $f);
			}
		}
		return $data;
	}

	/**
	 * Get all of the recordings (the old way)
	 * @param  boolean $compound Whether to show compounded recordings or not
	 * @return array           Array of recordings
	 */
	public function getAllRecordings($compound = true) {
		if ($this->initialized) {
			return ($compound ? $this->full_list : $this->filter_list);
		}
		$this->initialized = true;

		$sql = "SELECT * FROM recordings where displayname <> '__invalid' ORDER BY displayname";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$this->full_list = $sth->fetchAll(PDO::FETCH_ASSOC);
		foreach($this->full_list as &$item) {
			//TODO: Find instances of this and remove it!
			// Make array backward compatible, put first 4 columns as numeric
			$item[0] = $item['id'];
			$item[1] = $item['displayname'];
			$item[2] = $item['filename'];
			$item[3] = $item['description'];
			if (!str_contains((string) $item['filename'],'&')) {
				$this->filter_list[] = $item;
			}
		}
		return ($compound ? $this->full_list : $this->filter_list);
	}

	public function convertFiles($input)
	{
		/*
			[file] => en/1-for-am-2-for-pm
		    [name] => custom/ivr-okkkk-recording-1456170709
		    [codec] => wav
		    [lang] => en
		    [temporary] => 0
		    [command] => convert
		    [module] => recordings
		*/
		set_time_limit(0);
		$media = $this->FreePBX->Media;
		$file = $input['file'];
		$this->fixeRIFF($file);
		$name = $input['name'];
		$codec = $input['codec'];
		$lang = $input['lang'];
		$temporary = $input['temporary'];

		$codec = basename((string) $codec);
		$lang = basename((string) $lang);
		if(!file_exists($this->path."/".$lang."/custom")) {
			mkdir($this->path."/".basename($lang)."/custom",0777,true);
		}

		$name = preg_replace("/\s+|'+|`+|\"+|<+|>+|\?+|\*|\.+|&+/","-",(string) $name);

		if(!empty($codec)) {
			if($temporary) {
				$media->load($this->temp."/".$file);
			} else {
				$f = ($file == $name) ? $name : str_replace($lang."/","",(string) $file);
				$status = $this->fileStatus($f);
				if(!empty($status[$lang])) {
					if(!empty($status[$lang][$codec])) {
						$file = $lang."/".$status[$lang][$codec];
					} else {
						$file = $lang."/".reset($status[$lang]);
					}
				} else {
					return ["status" => false, "message" => _("Can not find suitable file in this language")];
				}
				$media->load($this->path."/".$file);
			}
			if(!$temporary && file_exists($this->path."/".$lang."/".$name.".".$codec) && $file == $name) {
				return ["status" => true, "name" => $name];
			}
			try {
				$media->convert($this->path."/".$lang."/".$name.".".$codec);
			} catch(Exception $e) {
				return ["status" => false, "message" => $e->getMessage()." [".$this->path."/".$file.".".$codec."]"];
			}
			return ["status" => true, "name" => $name];
		} else {
			$ext = pathinfo((string) $file,PATHINFO_EXTENSION);
			if($temporary && file_exists($this->temp."/".$file)) {
				rename($this->temp."/".$file, $this->path."/".basename($lang)."/".$name.".".$ext);
				return ["status" => true, "name" => $name];
			} else {
				return ["status" => true, "name" => $name];
			}
		}
	}
}
