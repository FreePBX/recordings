<?php
namespace FreePBX\modules\Recordings;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $configs = $this->FreePBX->Recordings->getAll();
    $files = [];
    $dirs = [];
    $base = $this->FreePBX->Config->get('ASTVARLIBDIR');
    foreach($configs as $config){
        foreach($config['files'] as $file){
            foreach($file as $key => $value){
                $path = $base.'/sounds/'.$key.'/custom';
                $dirs[$path] = $path; 
                foreach($value as $recordingfile){
                    if(!file_exists($recordingfile)){
                        continue;
                    }
                    $this->addFile(basename($recordingfile),$path,'ASTVARLIBDIR',"recording");
                }
            }
        }
    }
    $this->addDirectories($dirs);
    $this->addDependency('Soundlang');
    $this->addConfigs($config);
  }
}
