<?php
/**
 * Клас обработващ качени фаилове.
 * 
 * 
 * error code number
 *	Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
 *   Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
 *   Value: 3; The uploaded file was only partially uploaded.
 *   Value: 4; No file was uploaded.
 *   ... in PHP5 exist over error ...
 *   Value: 10; No success reamed uploaded file.
 *   Value: 11; The uploaded file exceeds the max field directive
 *   Value: 12; The uploaded file type is no permited
 *   Value: 13; The uploaded file name is olready exist
 *   Value: 14; The uploaded file can't copied in destination directory
 *   Value: 15; Can't remuved file.
 *   Value: 16; Upload folder does't exist and can't create it.
 *   Value: 17; Can not create temporary file.
 *
 * @author Evgeni Baldzisky
 * @package BASIC.UPLOAD
 * @version 1.5
 * @since [22-01-2007]
 * @example
 *
 *	$GLOBALS['BASIC']->imported('upload.mod');
 *
 *	$file = new BASIC_Upload('file');
 *
 * 	ex.1:Use Settings manager
 *
 *	$file->set('rand',false);
 *	$file->set('AsFile','PIC');
 *
 *	$file->setType(array('sql','css','php'));
 *  \\ ooo false :)
 *  $file->unsetType('php');
 *
 *  $old = $GLOBALS['BASIC_URL']->request('old');
 *
 *  ex.2:Use upload metods
 *
 *	if($GLOBALS['BASIC_URL']->request('add')){
 *		$old = $file->add();
 *	}
 *	if($GLOBALS['BASIC_URL']->request('edit')){
 *		$old = $file->edit($old);
 *	}
 *	if($GLOBALS['BASIC_URL']->request('del')){
 *		$old = $file->delete($old);
 *	}
 *
 *	while ($res = $GLOBALS['BASIC_ERROR']->error()){
 *		print implode("::",$res);
 *	}
 *
 */
class BASIC_UPLOAD extends BASIC_CLASS {

    var $permType = array(); //if count == 0 every type is ok
	var $prop 	  = array();

	var $size = 0;
	var $upDir = 'upload/';
	var $maxSize = 100;	// Size is in K (1000 == 1M) (1000*1000 == 1G)
	var $AsFile = 'RES';
	var $rand = 'true';
	var $autoCreateDir = true;

	var $tmpName = '';
	var $fullName = '';
	var $oldName = '';

	var $FileCtnType = '';
	var $farr = '';
	var $name = '';
	var $type = '';

	var $returnName = '';

	var $error = 0;
	// Events
	var $onComplete = '';
	var $onError = '';
	var $onDelete = '';
    /**
     * Load system variables for $file
     *
     * @param string $file
     */
    function BASIC_UPLOAD($file,$empty=false){
        if(isset($_FILES[$file])){
            $this->set('tmpName',$_FILES[$file]['tmp_name']);
            $this->set('fullName',$_FILES[$file]['name']);
            $this->set('size',$_FILES[$file]['size']);
            $this->set('FileCtnType',$_FILES[$file]['type']);
            $this->set('farr',$_FILES[$file]['error']);

            ereg("(.+)\.([^\.]+)$",$this->get('fullName'),$exp);
            $this->set('name',$exp[1]);
            $this->set('type',strtolower($exp[2]));

            if($empty && !$this->get('tmpName')){
            	$this->error = 4; $this->onError();
            }
        }
    }

    // ###################### SETTINGS MENAGER #################### //

    /**
     * Add perm type
     *
     * @param array $arr
     */
    function setType($arr){
    	if(is_array($arr)){
            foreach($arr as $v){
                $this->permType[$v] = true;
            }
            return;
    	}
    	$this->permType[$arr] = true;
    }
    /**
     * Delete perm type
     *
     * @param array $arr
     */
    function unsetType($arr){
    	if(is_array($arr)){
            foreach($arr as $v){
                if(isset($this->permType[$v])) $this->permType[$v] = false;
            }
            return ;
    	}
    	if(isset($this->permType[$arr])) $this->permType[$arr] = false;
    }

    // ############################################################## //

    /**
     * Rename uploaded file
     *
     * @param string $NewName
     * @return string
     */
    function _rename($NewName){
        if($this->get('rand') == 'true' || $this->get('rand') == true || $this->get('rand') == 1){
            $num = rand(100000, 999999);
        	$NewName = $NewName . round($num);
        }
        $NewName .=  "." . $this->get('type');

        if(@rename($this->_path() . $this->get('fullName'),$this->_path() . $NewName)){
            return $NewName;
        }
        $this->error = 10;
        $this->onError();
        return false;
    }
    /**
     * Проверка за валидност на качения фаил. Има ли грешка.
     *
     * @return boolen
     */
    function test(){
        $cSize = $this->get('maxSize');
	    if($cSize != 0 && $this->get('size') > $cSize){
           	$this->error = 11;  $this->onError(); return true;
        }
    	if(count($this->permType) != 0){
        	if(!array_key_exists($this->get('type'),$this->permType) || !$this->permType[$this->get('type')]){
        		$this->error = 12;  $this->onError(); return true;
        	}
        }
        if($this->get('farr') && $this->get('fullName')){
        	$this->error = $this->get('farr');  $this->onError(); return true;
	    }
	    return false;
    }
    /**
     * Write new file
     *
     * @return string
     */
    function add(){ 
        if(!$this->get('fullName') || $this->test()){
    		return '';
    	}
    	 
	    if($this->autoCreateDir) $this->createDir();

        if(file_exists($this->_path() . $this->get('name') . "." . $this->get('type'))){
        	if($this->get('rand') == 'true' || $this->get('rand') == true || $this->get('rand') == 1){
        		$this->set('fullName',time() . ".tmp");
        	}else{
				$this->error = 13;  $this->onError(); return '';
        	}
        }
        $copyfile = $this->_path().$this->get('fullName');
        if(copy($this->get('tmpName'),$copyfile)){
            if($this->get('rand') == 'true' || $this->get('rand') == true || $this->get('rand') == 1){
            	$this->returnName = $this->_rename($this->get('AsFile'));
            	$this->onComplete();
                return $this->returnName;
            }
            $this->returnName = $this->get('name') . "." . $this->get('type');
            $this->onComplete();
            return $this->returnName;
        }else{
        	$this->error = 14;  $this->onError(); return '';
        }
    }
    /**
     * Delete file
     *
     * @param string $file
     * @return boolen
     */
    function delete($file){
        if($file && file_exists($this->_path() . $file)){
            if(unlink($this->_path() . $file)){
          		$this->onDelete($file);
                return true;
            }
        }
        $this->error = 15;
        $this->onError();
        return false;
    }
    /**
     * Edit file
     *
     * @param string $oldFile
     * @return string
     */
    function edit($oldFile = ''){
    	if(!$this->get('fullName')){
    		return '';
    	}
    	if($oldFile) $this->oldName = $oldFile;

    	$file_new_name = $this->add();
        if($this->oldName && !$this->error){
    		$this->delete($this->oldName);
        }
	    return $file_new_name;
    }

    function preview($clean_folder = false){
    	// POLY FIX ME : delete all files in folder _path() with name _temporary and any extenssion if $clean_folder==true//
    	@unlink($this->_path().'_temporary.'.$this->get('type'));
 		if($clean_folder){
 			$arrdel=array();
 			if ($handle = opendir($this->_path())) {
	    		//echo "Directory handle: $handle\n";
	    		//echo "Files:\n";
	
	    		/* This is the correct way to loop over the directory. */
	    		while (false !== ($file = readdir($handle)) ) {
	        		if($file=='..')
	        			continue;
	        		if(ereg('_temporary\..+', $file)){
	        		@unlink($this->_path().$file);
	    			//echo "$file\n</br>";
	        		}
	    		}
	
	    		closedir($handle);
			}
 		}
		if(copy($this->get('tmpName'),$this->_path().'_temporary.'.$this->get('type'))){
			//die($this->_path().'_temporary.'.$this->get('type'));
            $this->returnName = '_temporary.'.$this->get('type');
            $this->onComplete();
            
            return '_temporary.'.$this->get('type');
        }else{
        	$this->error = 17;  $this->onError(); return '';
        }
        //die('OK');
    }
    
    function move($folder,$root=''){
    	if(!$root){
    		$root = $this->_path();
    	}else{
    		$root = $GLOBALS['BASIC']->validPath($root);
    	}
		$folder = $GLOBALS['BASIC']->validPath($folder);

    	if(file_exists($this->_path().$this->get('returnName'))){

    		if($this->autoCreateDir) $this->createDir($folder,$root);

    		copy($this->_path().$this->get('returnName'),$root.$folder.$this->get('returnName'));
    		unlink($this->_path().$this->get('returnName'));
    	}
    }
    /**
     * Create filder
     *
     * @param string $name
     */
    function createDir($name = '',$root='',$perm = 0777){
    	if(!$root){
    		$root = $this->_path();
    	}else{
    		$root = $GLOBALS['BASIC']->validPath($root);
    	}

    	if(!is_dir($root.$name)){
    		if(!@mkdir($GLOBALS['BASIC']->ini_get('root_path').$this->get('upDir').$name . "/",$perm)){
    			$this->error = 16;
    			$this->onError();
    		}
    	}
    }

    /**
     * Create path
     *
     * @return $string
     */
    function _path(){
    	if(!ereg("/$",$this->get('upDir'))){
    		$this->set('upDir',$this->get('upDir') . "/");
    	}
    	return $GLOBALS['BASIC']->ini_get('root_path') . $this->get('upDir');
    }

    function getPath(){
    	return $this->_path();
    }

    function onComplete(){
    	if($this->onComplete){
    		if(is_array($this->onComplete)){
    			$c = $this->onComplete[0];
    			$m = $this->onComplete[1];
    			$c->$m($this);
    		}else{
    			$c = $this->onComplete;
    			$c($this);
    		}
    	}
    }
    function onError(){
    	if($this->onError){
    		if(is_array($this->onError)){
    			$c = $this->onError[0];
    			$m = $this->onError[1];
    			$c->$m($this->error,$this);
    		}else{
    			$c = $this->onError;
    			$c($this->error,$this);
    		}
    	}
    }
    function onDelete($file){
     	if($this->onDelete){
     		$this->set('fullName',$file);
    		if(is_array($this->onDelete)){
    			$c = $this->onDelete[0];
    			$m = $this->onDelete[1];

    			$c->$m($this);
    		}else{
    			$c = $this->onDelete;
    			$c($this);
    		}
    	}
    }

    function getTextExections($code){
    	switch ($code){
    		case 1: return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
		    case 2: return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
		    case 3: return 'The uploaded file was only partially uploaded.';
		    case 4: return 'No file was uploaded.';

		    case 10: return 'No success reamed uploaded file.';
		    case 11: return 'The uploaded file exceeds the max field directive';
		    case 12: return 'The uploaded file type is no permited';
		    case 13: return 'The uploaded file name is olready exist';
		    case 14: return "The uploaded file can't copied in destination directory";
		    case 15: return "Can't removed file.";
		    case 16: return "Upload folder does't exist and can't create it.";

		     //... in PHP5 exist over error ...
		    default: return "Code exeption (".$code.")";
    	}
    }

    function get($name){
    	if($name == 'maxSize'){
    		return $GLOBALS['BASIC']->stringToBite(parent::get($name));
    	}
    	return parent::get($name);
    }

    // End class BASIC_Upload
}

/**
 * Class for download files.This File use settings and mettods of BASIC_Upload class
 *
 * @name BASIC_Download
 * @author Evgeni Baldzisky
 * @package BASIC.SBND.UPLOAD
 * @version 0.2 [28-02-2007]
 * @copyright
 * 		fix [03-05-2007] fix problem with max buffering and corectly count download
 */
class BASIC_Download extends BASIC_Upload {

	function BASIC_Download(){
		$this->BASIC_Upload('',false);
	}

    /**
     * Static metod for download file
     *
     * @param string $SysName
     * @param string $PublicName
     * @return boolen
     */
    function download($SysName,$PublicName,$data='',$ftype = ''){
    	if(!$SysName || !file_exists($this->_path().$SysName)) return false;
    	if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")){
    		$PublicName = rawurlencode($PublicName);
    	}

    	ereg("[^\.]+$",$SysName,$exp);
    	$ftype = $exp[0];

		$handle = @fopen($this->_path() . $SysName,"rb");

		header("Content-Type: " . $ftype);
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=" . str_replace(" ","_",$PublicName) . "." . $ftype);
		header("Content-Length: ".filesize($this->_path().$SysName));

		while (!feof($handle)) {
		   print fread($handle,(1024*1024));
		}

		@fclose($handle);

		return true;
    }

    function downloadSource($PublicName,$ftype,$data){
    	if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")){
    		$PublicName = rawurlencode($PublicName);
    	}
		header("Content-Type: " . $ftype);
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=" . str_replace(" ","_",$PublicName) . "." . $ftype);
		header("Content-Length: ".strlen($data));

		print $data;

		return true;
    }
    
	function downloadCSV($PublicName,$data){
 		if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")){
    		$PublicName = rawurlencode($PublicName);
    	}

 		header('Content-Description: File Transfer');
	    header('Content-Type: application/vnd.ms-excel; charset=ISO-8859-1');
	    header('Content-Disposition: attachment; filename='.$PublicName.'.csv');
	    header('Content-Transfer-Encoding: binary');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	    header('Pragma: public');
	    header('Content-Length: '. strlen($data)+48);
	    
	    die("\xEF\xBB\xBF".$data);
 	}

    // End class BASIC_Download
}