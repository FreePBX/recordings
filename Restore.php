<?php
namespace FreePBX\modules\Recordings;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($restoreid){
		$configs = $this->getConfigs();
		$files = $this->getFiles();
		foreach($files as $file){
			if($file->getType() == 'recording'){
				$filename = $file->getPathTo().'/'.$file->getFilename();
				$filename = $this->nameTest($filename,$file->getBase());
				if(!file_exists($filename)){
					copy($this->tmpdir.'/files/'.$file->getPathTo().'/'.$file->getFilename(),$filename);
					 $this->log(sprintf(_("File Restore %s"), $filename),'WARNING');
				} else {
					$this->log(sprintf(_("Same file exists  %s"), $filename),'WARNING');
				}
			}
		}
		foreach($configs['data'] as $config) {
			$recording = $this->FreePBX->Recordings->getRecordingById($config['id']);
			$files = array_keys($config['files']);
			$files = implode($files,'&');
			if(empty($recording)){
				$this->FreePBX->Recordings->addRecordingWithId($config['id'],$config['displayname'],$config['description'],$files,$config['fcode'],$config['fcode_pass']);
			}
			if(!empty($recording)){
				$this->FreePBX->Recordings->updateRecording($config['id'],$config['displayname'],$config['description'],$files,$config['fcode'],$config['fcode_pass']);
			}
		}
		$this->importFeatureCodes($config['features']);
	}
	public function nameTest($path,$var){
		$sysPath = $this->FreePBX->Config->get($var);
		if(!$sysPath){
			return $path;
		}
		$file = basename($path);
		$pathArr = explode('/',$path);
		$i = array_search('sounds',$pathArr,true);
		$pathArr = array_slice($pathArr,$i);
		return $sysPath.'/'.implode('/',$pathArr);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables) {
		$this->restoreLegacyDatabase($pdo);
		$this->restoreLegacyFeatureCodes($pdo);
	}
}
