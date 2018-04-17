<?php
namespace FreePBX\modules\Recordings;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{ 
  public function runRestore($restoreid){
    $configs = $this->getConfigs();
    $files = $this->getFiles();
    foreach($files as $file){
        if($file['type'] == 'recording'){
          $filename = $file['pathto'].'/'.$file['filename'];
          $filename = $this->nameTest($filename,$file['base']);
          if(file_exists($filename)){
            copy($this->tmpdir.'/files/'.$file['pathto'].'/'.$file['filename'],$filename);
          }
        }
    }
    foreach($configs as $config){
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
  }
  public function nameTest($path,$var){
    $sysPath = $this->FreePBX->Config->get($var);
    if(!$sysPath){
      return $path;
    }
    $file = basename($path);
    $pathArr = explode($path,'/');
    $i = array_search('sounds',$pathArr);
    $pathArr = array_slice($pathArr,$i);
    return $sysPath.'/'.implode('/',$pathArr).'/'.$file;
  }
}