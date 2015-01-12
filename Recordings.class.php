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
