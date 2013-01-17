<?php
/**
 * Помощен клас които се ползва от BaseDysplayComponentClass за изработване на допълнителни-свързани 
 * таблици на работната. Такива таблици се създават за обслужване на контроли 
 * "moveSelect","changeSelect","multyBox","select::multiple"
 *	
 * @author Evgeni Baldzisky
 * @version 0.1
 * @access protected
 * @since 23-02-2007
 * @package BASIC.FORM
 */
class ForeignElements extends BASIC_CLASS {
	/**
	 * Parent table name
	 * @var string
	 * @access private
	 */
	var $baseTableName = '';
	/**
	 * Current table name : i.e parent table name + element name
	 * @var string
	 * @access protected
	 */
	var $base = '';
	/**
	 * Foreign key colomn name
	 * @var string
	 * @access protected
	 */
	var $field_id = '';
	/**
	 * Field colomn configoration
	 * @var array
	 * @access private
	 */
	var $field_el = array();
	/**
	 * Work data buffer
	 * @var array
	 * @access public
	 */
	var $dataBuffer = array();

	/**
	 * Constructor
	 *
	 * @param string $baseTableName
	 * @param string $foreignElementName
	 * @param string [$dataType]
	 * @param string [$dataLight]
	 *
	 * @return ForeignElements
	 */
	function ForeignElements($baseTableName, $foreignElementName, $dataType = 'Int', $dataLight = '11'){

		$this->baseTableName = $baseTableName;

		$this->base = $this->baseTableName.'_'.$foreignElementName;
		$this->field_id = $this->baseTableName.'_id';

		$this->field_el = array($foreignElementName, $dataType, $dataLight);
	}
	/**
	 * Table's creator
	 * @return boolen
	 */
	function SQL(){
		$err = '';
		$data = "";

			$data .= "`".$this->field_id."` int(11),\n";
			$data .= "`".$this->field_el[0]."` ".$this->field_el[1]."(".$this->field_el[2].")\n";

		$err = BASIC_SQL::init()->createTable(null, $this->base, $data, 'InnoDB');

		return $err;
	}
	function remove($id){
		$GLOBALS['BASIC_SQL']->exec(" DELETE FROM `".$this->base."` WHERE `".$this->field_id."` = ".(int)$id." ");
	}
	function update($id){
		$this->remove($id);
		if(is_array($this->dataBuffer)){
			foreach ($this->dataBuffer as $v){
				BASIC_SQL::init()->exec("
					INSERT INTO `".$this->base."` (
						`".$this->field_id."`,`".$this->field_el[0]."`
					)VALUES(
						'".$id."','".$v."'
					)
				");
			};
		}
	}
	function load($id){
	    $dataBuffer = array();
		$rdr = BASIC_SQL::init()->read_exec(" SELECT `".$this->field_el[0]."` FROM `".$this->base."` WHERE `".$this->field_id."` = ".(int)$id."  ORDER BY `".$this->field_el[0]."` ");
			   
		       BASIC_ERROR::init()->reset();
		$err = BASIC_ERROR::init()->error();
		if($err['code'] == 1146){
			$tmp = $this->SQL();
			if($tmp){
				BASIC_ERROR::init()->clean();
				return $this->load($id);
			}
			return array();
		}
		while ($rdr->read()){
			$dataBuffer[] = $rdr->field($this->field_el[0]);
		}
		return $dataBuffer;
	}
}
/**
 * Class contenting base method and properties API for package BASIC.FORM
 *
 * @author Ecgeni Baldzisky
 * @version 0.1
 * @since 23.02.2007]
 * @package BASIC.FORM
 */
class BaseDysplayComponentClass extends BASIC_CLASS{
	/**
	 * Fields registri
	 * @var array
	 * @access protected
	 */
	var $fields  	= array();
	/**
	 * Fields File registry
	 * @var array
	 * @access protected
	 */
	var $fieldsFile = array();
	/**
	 * foreign field elements registry
	 * @var array
	 * @access protected
	 */
	var $fieldsFireign = array();
	/**
	 * Work data buffer
	 * @var array
	 * @access protected
	 */
	var $dataBuffer  	= array();
	/**
	 * Cleaners registry
	 * @var multiArray
	 * @access protected
	 */
	var $globalCleaner = array(
		'varchar' 	=> array('addslashes','stripcslashes'),
		'ini' 		=> array('Int'),
		'float' 	=> array('Float')
	);
	/**
	 * Worked table's name
	 * @var string
	 */
	var $base 		= '';
	/**
	 * Primary key's name
	 * @var string
	 */
	var $field_id   = 'id';
	
	var $id  = 0;

	# Cleanet API{

	function setCleaner($type,$add,$strip){
		$this->globalCleaner[$type] = array($add,$strip);
	}
	function unsetCleaner($type){
		if(isset($this->globalCleaner[$type])) unset($this->globalCleaner[$type]);
	}
	function cleanerDesition($type,$is_in = true,$owner = null){
		if($owner){
			if(!$is_in && isset($owner[1]) && $owner[1]){
				if($owner[1] != '#') return $owner[1];
			}else{
				return $owner[0];
			}
		}else{
			if(isset($this->globalCleaner[$type])){
				if(!$is_in && isset($this->globalCleaner[$type][1]) && $this->globalCleaner[$type][1]){
					if($this->globalCleaner[$type][1] != '#') return $this->globalCleaner[$type][1];
				}else{
					return $this->globalCleaner[$type][0];
				}
			}
		}
		return null;
	}
	
	/**
	 * $direction == false is action load $direction == true is action save
	 *
	 * @param array $array
	 * @param booled $direction
	 * @return array
	 */
	function cleanerDesitionArray($array,$direction = false){
		foreach ($array as $k => $v){
			if(isset($this->fields[$k])){
				$array[$k] = $GLOBALS['BASIC_URL']->other($v,null,
					$this->cleanerDesition($this->fields[$k][3],$direction,$this->fields[$k][7])
				);
			}
		}
		return $array;
	}

	function SQL(){
		$data = '';
		foreach ($this->fields as $v){
			if($v[0][0] == '#' && $v[0] == $this->field_id) continue;

			$data .= $this->columnProp($v).",";
		}
		return $GLOBALS['BASIC_SQL']->createTable($this->field_id,$this->base,$data);
	}
	function addColumn($message){
		preg_match("/column( name)? '([^']+)'/",$message,$math);

		foreach ($this->fields as $v){
			if($v[0] == $math[2]){
				return $GLOBALS['BASIC_SQL']->createColumn($this->base,$this->columnProp($v));
			}
		}
	}
	function columnProp($v){
		$sql = '';
		$sql .= "	`".$v[0]."` ";
		if($v[3] == '' || $v[3] == null || $v[3] == 'text'){
			$sql .= "text NOT NULL DEFAULT '' ";
		}else if($v[3] == 'text' || $v[3] == 'longtext' || $v[3] == 'mediumtext'){
			$sql .= $v[3];//." NOT NULL DEFAULT '' ";
		}else if($v[3] == 'date' || $v[3] == 'datetime'){
			$v[3] = 'datetime';
			$sql .= $v[3]." NOT NULL DEFAULT '0000-00-00 00:00:00' ";
		//}else if($v[3] == 'int'){
			//$sql .= $v[3];
		}else{
			$sql .= $v[3]."(".$v[2].") NOT NULL DEFAULT '".($v[3] == 'int' ? '0' : '')."' ";
		}
		
		return $sql;
	}
	function getDataBuffer($name){
		if(isset($this->dataBuffer[$name])) return $this->dataBuffer[$name];
		return '';
	}
	function setDataBuffer($name,$value){
		$this->dataBuffer[$name] = $value;
	}
	function unsetDataBuffer($name){
		if(isset($this->dataBuffer[$name])) unset($this->dataBuffer[$name]);
	}
	function getBuffer(){
		return $this->dataBuffer;
	}
	function setBuffer($array){
		foreach ($array as $k => $v) $this->setDataBuffer($k,$v);
	}
	function cleanBuffer(){
	    $this->dataBuffer = array();
	}
	/**
	 * @param string $name
	 * @param array $context
	 */
	function setField($name,$context = array()){

		if(!isset($context['text'])) 		$context['text'] 		= $name;
		if(!isset($context['dbtype'])) 		$context['dbtype'] 		= 'varchar';
		if(!isset($context['length'])) 		$context['length'] 		= 255;
		if(!isset($context['perm'])) 		$context['perm'] 		= '';
		if(!isset($context['attributes'])) 	$context['attributes'] 	= array();
		if(!isset($context['cleaners'])) 	$context['cleaners'] 	= array();
		if(!isset($context['formtype'])) 	$context['formtype'] 	= 'input';

		$ctrl = BASIC_GENERATOR::init()->getControl($context['formtype']);
		if(!$ctrl && $context['formtype'] != 'hidden'){
			throw new Exception(" The type '".$context['formtype']."' is not supported.", 500);
		}
		
		if($name[0] != '#' && $context['formtype'] != 'hidden' && (
			(isset($context['attributes']['multiple']) && $context['attributes']['multiple']) ||
			$ctrl->isMultiple() 
		)){
			$this->fieldsFireign[$name] = new ForeignElements($this->base, $name, $context['dbtype'], $context['length']);
		}
		$tmpArr = array();

		$tmpArr['perm'] 		= $context['perm']; 	  unset($context['perm']);
		$tmpArr['length'] 		= $context['length']; 	  unset($context['length']);
		$tmpArr['dbtype'] 		= $context['dbtype']; 	  unset($context['dbtype']);
		$tmpArr['text'] 		= $context['text']; 	  unset($context['text']);
		$tmpArr['formtype'] 	= $context['formtype'];   unset($context['formtype']);
		$tmpArr['attributes'] 	= $context['attributes']; unset($context['attributes']);
		$tmpArr['cleaners'] 	= $context['cleaners'];   unset($context['cleaners']);

		$this->fields[$name] = array(
			$name,
			$tmpArr['perm'],
			$tmpArr['length'],
			$tmpArr['dbtype'],
			$tmpArr['text'],
			$tmpArr['formtype'],
			$tmpArr['attributes'],
			$tmpArr['cleaners']
		);
		foreach ($context as $k => $v){
			$this->fields[$name][$k] = $v;
		}
	}
	function getField($name,$acs = true){
		if(isset($this->fields[$name])){
			$arr_tmp = $this->fields[$name];
			
			if($acs){
				$arr_tmp['perm'] 		= $arr_tmp[1]; unset($arr_tmp[1]);
				$arr_tmp['length'] 		= $arr_tmp[2]; unset($arr_tmp[2]);
				$arr_tmp['dbtype'] 		= $arr_tmp[3]; unset($arr_tmp[3]);
				$arr_tmp['text'] 		= $arr_tmp[4]; unset($arr_tmp[4]);
				$arr_tmp['formtype'] 	= $arr_tmp[5]; unset($arr_tmp[5]);
				$arr_tmp['attributes'] 	= $arr_tmp[6]; unset($arr_tmp[6]);
				$arr_tmp['cleaners'] 	= $arr_tmp[7]; unset($arr_tmp[7]);
				unset($arr_tmp[0]);
			}
			return $arr_tmp;
		}
		return null;
	}
	function unsetField($name){
		if(isset($this->fields[$name])){
			unset($this->fields[$name]);
		}
	}
	/**
	 * Обновяване на настройките на колоната. Обновяват се само опоменатите настроики 
	 * ако ги няма се добаят, останалите настройки се запазват непокътнати.
	 *
	 * @param string $name
	 * @param array $context
	 * @return void
	 */
	function updateField($name,$context){
	    if($arrFil = $this->getField($name)){
				
		    foreach ($context as $k => $v){
		    	if(is_array($v)){
		    		if(!is_array($arrFil[$k])){
		    			$arrFil[$k] = array();
		    		}
		    		foreach($v as $kk => $vv){
		    			$arrFil[$k][$kk] = $vv;
		    		}
		    	}else{
		        	$arrFil[$k] = $v;
		    	}
		    }
		    $this->setField($name,$arrFil);
		    return $arrFil;
	    }
	    return null;
	}
	function setFieldsFireign($tbl,$name,$typedata = 'int',$lengthdata = 11){
		$this->fieldsFireign[$name] = new ForeignElements($tbl,$name,$typedata,$lengthdata);
	}
	function unsetFieldsFireign($name){
		if(isset($this->fieldsFireign[$name])) unset($this->fieldsFireign[$name]);
	}
	function test(){
		$err = false;
		foreach ($this->fields as $k => $v){
			if(!$this->getDataBuffer($v[0])){
				$var_url = $GLOBALS['BASIC_URL']->request($v[0],
					$this->cleanerDesition($v[3],true,$v[7])
				);
				$this->setDataBuffer($v[0],$var_url);
				if(($v[1] == 1 && (string)$this->dataBuffer[$v[0]] == '')){

					$GLOBALS['BASIC_ERROR']->append(500,$v[4]);
					$err = true;
				}
			}
		}
		return $err;
	}
    /**
	 * Add new db-tables's row
	 * 
	 * @return boolean
     */
	function ActionAdd(){
		$cleanedArray = $this->cleanBedVar();

		$fields = '';
		$values = '';
		foreach($cleanedArray as $k => $v){
			if(isset($this->fieldsFile[$k])){
				$value = $this->fieldsFile[$k]->add();
				if($value){
					$value = str_replace("//", "/", $this->fieldsFile[$k]->upDir."/".$value);
					$this->setDataBuffer($k, $value);
					if($values) $values .= ","; $values .= "'".$value."'";
					if($fields) $fields .= ","; $fields .= "`".$k."`";
				}else{
					$this->setDataBuffer($k, '');
				}
			}else{
				$value = (is_array($v)?serialize($v):$v);
				if($values) $values .= ","; $values .= "'".$value."'";
				if($fields) $fields .= ","; $fields .= "`".$k."`";
			}
		}

		if(!$this->messages && !BASIC_ERROR::init()->exist(array('warning', 'fatal'))){
			BASIC_SQL::init()->exec(" INSERT INTO `".$this->base."` (".$fields.") VALUES (".$values.")");
			
			   BASIC_ERROR::init()->reset();
			$err = BASIC_ERROR::init()->error();
			if($err['code'] == 1054){
				$tmp = $this->addColumn($err['message']);
				if($tmp){
					BASIC_ERROR::init()->clean();
					return $this->ActionAdd();
				}
			}			
			
			$last = BASIC_SQL::init()->getLastId();
			$this->updateForeignStructore($last);
			
			return $last;
		}
		return false;
	}
    /**
	 * Update new db-tables's row
	 * 
	 * @param int $id
	 * @return boolean
     */
	function ActionEdit($id,$action = null,$rules = ''){
		$cleanedArray = $this->cleanBedVar();
		$query = "";
		foreach($cleanedArray as $k => $v){
			if(isset($this->fieldsFile[$k])){
				if(!$this->fieldsFile[$k]->test()){
					$res = BASIC_SQL::init()->read_exec(" SELECT `".$k."` FROM `".$this->base."` WHERE `".$this->field_id."` = ".$id." ", true);
				   		
							BASIC_ERROR::init()->reset();
					$err = BASIC_ERROR::init()->error();
					if($err['code'] == 1054){
						$tmp = $this->addColumn($err['message']);
						if($tmp){
							BASIC_ERROR::init()->clean();
							return $this->ActionEdit($id);
						}
					}	
					$value = $this->fieldsFile[$k]->edit($res[$k]);
					if($value){
						$value = str_replace("//", "/", $this->fieldsFile[$k]->upDir."/".$value);
						$this->setDataBuffer($k, $value);
						if($query) $query .= ", \n"; $query .= "\t `" . $k . "` = '".$value."'";
					}else{
						$this->setDataBuffer($k, '');	
					}
				}
			}else{
				$value = (is_array($v)?serialize($v):$v);
				if($query) $query .= ", \n"; $query .= "\t `" . $k . "` = '".$value."'";
			}
		}
		if(!$this->messages && !BASIC_ERROR::init()->exist(array('warning', 'fatal'))){
			BASIC_SQL::init()->exec(" UPDATE `".$this->base."` SET \n".$query." WHERE `".$this->field_id . "` = ".$id." ".$rules);
				   
				   BASIC_ERROR::init()->reset();
			$err = BASIC_ERROR::init()->error();
			if($err['code'] == 1054){
				$tmp = $this->addColumn($err['message']);
				if($tmp){
					BASIC_ERROR::init()->clean();
					return $this->ActionEdit($id);
				}
			}			
			$this->updateForeignStructore($id);

			return $id;
		}
		return false;
	}
	function ActionLoad($id = 0){
	    if(!$id) $id = (int)$this->id;
	    
		$column = '';
		foreach($this->fields as $v){
			if($v[0][0] == '#') continue;

			$column .= '`'.$v[0].'`,';
		}
		$column .= '`'.$this->field_id."`";

		$rdr = BASIC_SQL::init()->read_exec(" SELECT ".$column." FROM `".$this->base."` WHERE `".$this->field_id."` = ".(int)$id." ");

			   BASIC_ERROR::init()->reset();
		$err = BASIC_ERROR::init()->error();
		if($err['code'] == 1146){
			$tmp = $this->SQL();
			if($tmp){
				BASIC_ERROR::init()->clean();
			}
		}else if($err['code'] == 1054){
			$tmp = $this->addColumn($err['message']);
			if($tmp){
				BASIC_ERROR::init()->clean();
				return $this->ActionLoad($id);
			}
		}

		$rdr->read();
		if($rdr->num_rows() > 0){
			$tmp = $rdr->getItems();
			foreach($this->fields as $v){

				// test for special fields and load ower data

				if(!isset($tmp[$v[0]]) || $v[0][0] == '#'){
					$this->dataBuffer[str_replace("#",'',$v[0])] = '';
					continue;
				}else{
					$this->dataBuffer[$v[0]] = BASIC_URL::init()->other($tmp[$v[0]],null,
						$this->cleanerDesition($v[3],false,$v[7])
					);
				}
			}
			$this->dataBuffer[$this->field_id] = $tmp[$this->field_id];
		}
	}
	/**
	 * Delete action hendlar
	 *
	 * @param array $id
	 * @param string [$action]
	 * @param string [$rules]
	 * @return boolen
	 */
	function ActionRemove($id=0,$action = '',$rules = ''){
		if($id){
    		if(!is_array($id)) $id = array($id);
    		
    		if(count($id) > 0){
    			$file = array();
    			$criteria = " WHERE `".$this->field_id."` IN (".implode(",",$id).") ".$rules;
    			foreach ($this->fields as $v){
    				if($v[5] == "file"){
    					$file[$v[0]] = BASIC_GENERATOR::init()->convertStringAtt($v[6]);
    				}
    			}
    			BASIC::init()->imported('upload.mod');
    			$rdr = BASIC_SQL::init()->read_exec(" SELECT * FROM `".$this->base."`".$criteria);
    			while($rdr->read()){
    				foreach ($file as $k => $v){
    					$fl = new BasicUpload('');
    					$fl->upDir = $v['dir'];
    					if(isset($v['onDelete'])){
    						$fl->onDelete = $v['onDelete'];
    					}
    					$fl->delete($rdr->field($k));
    				}
    				$this->updateForeignStructore($rdr->field($this->field_id),true);
    			}
    			BASIC_SQL::init()->exec(" DELETE FROM `".$this->base."`".$criteria);
    		}
    		return true;
		}
		return false;
	}
	/**
	 * Updater Foreign Structore
	 *
	 * @param int $keyId
	 * @param boolen $onlyremove
	 */
	function updateForeignStructore($keyId,$onlyremove=false){
		if(!BASIC_ERROR::init()->exist()){
			foreach ($this->fieldsFireign as $k => $v){
				if($onlyremove){
					$v->remove($keyId);
				}else{
					$v->dataBuffer = $this->getDataBuffer($k);
					$v->update($keyId);
				}
			}
		}
	}
	/**
	 * Cleaner work buffer
	 *
	 * @return array
	 */
	function cleanBedVar(){
		$tmp = $this->dataBuffer;
		foreach($tmp as $k => $v){
			if(substr($k,0,1) == '#' || isset($this->fieldsFireign[$k]) || isset($this->nasional[$k])){
				unset($tmp[$k]);
			}
		}
		return $tmp;
	}
	/**
	 * Creator of default settings
	 */
	function createDefaultSettings(){
		$this->base = $this->prefix = get_class($this);
	}
}
/**
 * Object extend sqlReader functionality for read components data
 * 
 * @author Evgeni Baldzisky
 * @version 0.1
 * @since 15.01.2009
 */
class ComponentReader {
	/**
	 * @var sqlReader
	 */
	protected $rdr = null;
	/**
	 * @var BaseDysplayComponentClass
	 */
	protected $target = null;
	
	protected $tmp_buffer = array();
	protected $buffer = array();
	protected $index_position = -1;
	/**
	 * object Constructor
	 *
	 * @param sqlReader $rdr
	 * @param BaseDysplayComponentClass [target]
	 * @return ComponentReader
	 */
	function __construct($rdr, $target = null){
		
		$this->rdr = $rdr;

		while($this->rdr->read()){
			$perm = true;
			
			if($perm !== false){
	            foreach ($this->rdr->getItems() as $k => $v){
	                if($k == $target->field_id) continue;
	                
	              	if(isset($target->fieldsFireign[$k])){
	              	    $this->rdr->setItem($k, $target->fieldsFireign[$k]->load($this->rdr->item($target->field_id)));
					}else{
						if(!isset($target->fields[$k])){
							$this->rdr->setItem($k,$v);
						}else{
							$this->rdr->setItem($k, BASIC_URL::init()->other($v, null,
								$target->cleanerDesition($target->fields[$k][3], false, $target->fields[$k][7])
							));
						}
					}
	            }
	            $this->buffer[] = $this->rdr->getItems();
			}
		}
	}
	/**
	 * @param array $row
	 * @return void
	 */
	function addRow($row){
		$this->buffer[] = $row;
	}
	/**
	 * @param array $row
	 * @return void
	 */
	function addRows($rows){
		foreach($rows as $row) $this->buffer[] = $row;
	}
	/**
	 * Get current record
	 *
	 * @return array
	 */
	function getItems(){
		return $this->tmp_buffer;
	}
	/**
	 * append/edit elements of current recoerd
	 *
	 * @param array $arr
	 */
	function setItems($arr){
		foreach ($arr as $k => $v){
			$this->setItem($k,$v);
		}
	}
	/**
	 * append/edit element of current recoerd
	 *
	 * @param string $name
	 * @param mix $value
	 */
	function setItem($name,$value){
		$this->tmp_buffer[$name] = $value;
		//$this->buffer[$this->index_position+1][$name] = $value;
		$this->buffer[$this->index_position][$name] = $value;
	}
	/**
	 * read and return next record
	 *
	 * @param mix $cleanCall
	 * @return array
	 */
	function read($cleanCall = null){
		$this->index_position++;
		if(isset($this->buffer[$this->index_position])){
			$this->tmp_buffer = $this->buffer[$this->index_position];
			
			return $this->tmp_buffer;
		}
		$this->index_position = -1;
		return null;
	}
	/**
	 * Reset index
	 */
	function reset(){
		$this->index_position = -1;
	}
	/**
	 * Get different index
	 *
	 * @param int $index
	 * @return array
	 */
	function readIndex($index){
		return (isset($this->buffer[$index]) ? $this->buffer[$index] : '');
	}
	/**
	 * Get element of current record
	 *
	 * @param string $name
	 * @param string $colback
	 * @return mix
	 */
	function item($name,$colback = null){
		return (isset($this->tmp_buffer[$name]) ? $this->tmp_buffer[$name] : ''); 
	}
	/**
	 * Get number of records
	 *
	 * @return int
	 */
	function num_rows(){
		return count($this->buffer);
	}
	/**
	 * Извличане на масив поднодящ за ползване от контроли от тип select
	 * 
	 * @param string $id
	 * @param string $text
	 * @param array $before
	 * @return array
	 */
	function getSelectData($id = 'id', $text = 'title', $before = array()){
		if(!is_array($before)) $before = array();
		
		$this->reset();
		while($this->read()){
			$before[$this->item($id)] = $this->item($text); 
		}
		$this->reset();
		
		return $before;
	}
	/**
	 * Извличане на цялата информация във формата на масив.
	 * @return array
	 */
	function getArrayData(){
		return $this->buffer;
	}
	
	static public function getEmptyReader(){
		return new ComponentReader(BASIC_SQL::init()->getEmptyReader());
	}
}
/**
 * Class API for create and work with object-component
 *
 *  ex.1: Used API interfase in steps.
 * 		1) Create child{component} object
 * 			class child exetends InterfaceForm{
 *
 * 				2) Create construct.This is compulsory condition
 * 				function child(){
 *
 * 					3) Declare default settings : prefix and dbname
 * 						this step is optionality.It is create $this->base = $this->prefix = Current Clas Name or you must create this property
 * 					$this->createDefaultSettings()
 *
 * 					4) Declare primary key field name.Def: 'id' : optionality
 * 					$this->field_id = 'primary key name';
 *
 * 					5) registrate worked fields.
 * 						$this->setFields(field name,
 * 							array(
 * 								'perm' => ['string' string for mark that is mondatory],
 * 								'length' => ['int' max length of field def 255],
 * 								'dbtype' => [valid SQL types def varchar]',
 * 								'text' => 'text for form manager',
 * 								'formtype' => ['type form manager' def text],
 * 								'attributes' => ['str or array attribute form elements on form manager'],
 * 								'cleaners' => array(
 * 									'name function or method for clean of add edit ',
 * 									'name function or method for clean of load'
 * 								)
 *                              'default' => ['default value if no exist this.id'],
 * 								'lingual' => [default true]|false
 * 								
 * 								// new 
 * 								'messages' => array( // colection of errors
 * 									1 => ['if no set value system use and exist value of perm field and no exist url value "Field [name] id mondatory!"'])
 * 									... custom messages 
 * 									N => 'custom text ...')
 * 								)
 *							)
 *						);
 *
 * 						!) Valid types for form elements
 * 							render{ - for call to class method or function.
 *                                      if used array('','name_function') -> call name_function()  
 *                                      if used array('name_object','name_method') -> call name_object->name_method() 
 *                                      if used 'name_method' -> call this->name_method() 
 * 								attribute['name'] - is name class'a methos
 * 								prototype method declaration
 * 									function name(name field element,attributes){
 *
 * 										return HTML code
 * 									}
 *							}
 * 							radioBox
 * 							multyBox
 * 							select
 * 							multySelect
 * 							moveSelect
 * 							changeSelect
 * 							file
 * 							HTMLTextarea
 * 							date
 * 							### and all valid form elements ###
 *
 * 					6) Create special cleaner
 * 					   Sintax henlar: $this->specialTest = (
 * 							'name' - call $this->name() ||
 * 							array(null,'name') - call function 'name' ||
 * 							array(&$obj,'name') - call $obj->name()
 * 						)
 *
 * 						!) prototype special validation:
 * 						   Warning:Default method ADD and EDIT for actions Add and Edit used $this->dataBuffer for query insert and update
 *
 * 							fuction validation_tmp_arr(&$cur_obj[for PHP 4+ < 5 and used array value]){
 * 								ex.1 Validate logic
 * 									!) for PHP 5+
 * 										if($this->dataBuffer['pass'] != $this->dataBuffer['pass2']){
 * 											$GLOBALS['BASIC_ERROR']->append(500,'you message');
 * 											return true;
 * 										}else{
 * 											unset($this->dataBuffer['pass2']);
 * 										}
 * 									!) for PHP 4+ <5
 * 										if($cur_obj->dataBuffer['pass'] != $cur_obj->dataBuffer['pass2']){
 * 											$GLOBALS['BASIC_ERROR']->append('you code','you message');
 * 											return true;
 * 										}else{
 * 											unset($cur_obj->dataBuffer['pass2']);
 * 										}
 * 								return (false)|(true if exist error);
 * 							}
 * 							ex :
 * 								!) if use == array(&$obj,'mymethod') and PHP < 5
 * 									function mymethod(&$obj){
 * 										// logic $obj->getDataBuffer('name');
 * 									}
 * 								!) if use == 'mymethod' or PHP >= 5
 * 									function mymethod(){
 * 										// logic $this->getDataBuffer('name');
 * 									}
 * 					7) Work with global cleaners
 * 						!) Added default cleaners are varchar,ini,float . You can change this.
 * 						  	$this->setCleaner('ini',array(&$this,'myinion'),array(&$this,'myinioff'));
 * 						!) Or add new
 * 							$this->setCleaner('char',array(&$this,'mycharon'),array(&$this,'mycharoff'));
 *						!) Or remove exist cleaner
 * 							$this->unsetCleaner('float');
 *
 * 					8) Work with actions
 * 						!) You have default actions but you can change,update or delete it.
 * 							Default API can work with default actions.The used new actions is optionality.
 * 							Actions are typicaly.They use 3 default types.
 *								// 1 footerActionsBas
 *								// 2 rowActionsBar
 *								// 3 buttonActionsBar
 * 							You can create custom types which can use.
 *
 * 					9) Work with maping
 * 						!) Map column auto create sort link if is used property "sorting", you can kill this functionality
 * 						   that set fifth = false [default = true]
 *
 * 						!) Work with hendlars
 * 							hendlar == 'name' - call $this->name();
 * 							hendlar == array(null,'name') call name();
 * 							hendlar == array(&$obj,'name') call $obj->name();
 *
 * 							hendlar prototype
 * 								function name($value,$name,$row_array){
 * 									// logic modify value
 * 									return $modify_value
 * 								}
 *
 * 							hendlar parameters
 * 								1 - value
 * 								2 - name
 * 								3 - row array
 *
 * 					// End constructor
 * 				}
 * 
 * 	Form template conception 
 * 		dynamic case : 
 * 			<!-- dynamic NAME -->
 * 				<ul>
 * 					<li>{TEXT} : value of text property on setField</li>				
 * 					<li>{VALUE} : form's contril used value of formtype,length,attributes property</li>				
 * 					<li>{PERM} : symbol for mark will require this field used value of perm property</li>				
 * 					<li>{MESSAGE} : text message if exist.used value of messages property</li>				
 * 				</ul>
 * 			<!-- end dynamic NAME -->
 * 
 * 		no dynamic case :
 * 				<ul>
 * 					<li>{TEXT_NAME_FIELD} : value of text property on setField</li>				
 * 					<li>{VALUE_NAME_FIELD} : form's contril used value of formtype,length,attributes property</li>				
 * 					<li>{PERM_NAME_FIELD} : symbol for mark will require this field used value of perm property</li>				
 * 					<li>{MESSAGE_NAME_FIELD} : text message if exist.used value of messages property</li>				
 * 				</ul>
 * 
 * 	System generate errors code=100 message=name element for permitted and empty fields
 *
 * @author Evgeni Baldziyski
 * @version 3.0
 * @since [02-02-2008]
 * @name DysplayComponent
 * @package BASIC.FORM
 */
class DysplayComponent extends BaseDysplayComponentClass{
	/**
	 * Register default actions
	 *
	 * @var array
	 */
	var $actions = array(
			'add'   => array('ActionFormAdd', 1, 'Add new records'),
			'edit'  => array('ActionFormEdit', 2, 'Edit/View'),

			'delete'	=> array('ActionRemove', 1, 'Remove checked'),

			'save'		=> array('ActionSave', 3, 'Save'),
			'cancel'	=> array('ActionBack', 3, 'Back'),

			// System Actions
			'load'		=> array('ActionLoad',  0),
			'error'		=> array('ActionError', 0),
	
			//'order_up'	=> array('ActionOrder', 0),
			//'order_down'=> array('ActionOrder', 0),
			
			'filter' 	=> array('', 0, 'Filter'),
	
			'fileRemove'=> array('ActionFileRemove', 0),

			// Default Call Action
			'list' => array('ActionList',0)
	);
	/**
	 * Error Action Name.If exist error system will redirect to errorAction hendlar.
	 *
	 * @var string
	 */
	var $errorAction = 'edit';
	/**
	 * Use of test method for set if create error.
	 * if this property have value != '' when createInterface execute errorAction.
	 *
	 * @var string
	 */
	var $messages = array();

	public $prefix = '';
	/**
	 * Container for component's actions name
	 * 
	 * @var string
	 */
	var $cmd = '';
	/**
	 * key for field order_id on table if(no exist) avto created
	 * 
	 * @var boolean
	 */
	private $_ordering = false;

	var $miss = array(); 					# container missing variables
	var $hidden_el = ''; 					# container hidden elements

	var $nasional = array(); 			    #container declare language fields

	// List manager property //
	var $system = array();
	var $maxrow = 20;
	/**
	 * 
	 * @var BasicSorting 
	 */
	var $sorting = null;
	/**
	 * @var BasicComponentPaging
	 */
	var $paging = null;
	/**
	 * 
	 * @var BasicFilterInterface
	 */
	var $filter = null;
	
	var $map = array();

	var $useJSLang = true;
	/**
	 * Flag for lock form save state
	 */
	var $useSaveState = true;
	/**
	 * pointer to the method that will be used for additional validation of the data
	 *	if tispointer is string will be call function else will be call array[0]->array[1]
	 * value = array(&$obj,'method') === $obj->method($this,$id,$action)
	 * value = 'function' === function($this,$id,$action)
	 *
	 * NEW :: value = array('this','method) === $this->method($id,$action)
	 *
	 * @var string/array
	 */
	var $specialTest = '';
	var $autoTest 	 = true;
	
	/**
	 * The template for form view (ActionFormAdd, ActionFormEdit, ...)
	 * @var String
	 */
	var $template_form 	  = 'cmp-form.tpl';
	/**
	 * The template for list view (ActionList, ...)
	 * @var String
	 */	
	var $template_list 	  = 'cmp-list.tpl';
	/**
	 * The template for list filter (when this->filter != null)
	 * @var String
	 */	
	var $template_filter  = 'cmp-filter.tpl';
	
	var $templates = array(
		// form template info
		'form-dynamic' => 'fields',
		'form-vars' => array(
			'perm' 	  => 'perm',
			'label'   => 'label',
			'ctrl' 	  => 'ctrl',
			'message' => 'message',
			'buttons_bar' => 'buttons_bar'
		),
		'list-vars' => array(
			'head-check' 		 => 'use_checkbox',
			'head-order' 		 => 'use_order',
			'head-dynamic' 		 => 'headers',
			'head-length' 		 => 'column_length',
			'head-dynamic-attr'  => 'attr',
			'head-dynamic-label' => 'label',
			'head-dynamic-selected' => 'selected',
			'head-dynamic-isdown' => 'isdown',
		
			'body-dynamic' 			 => 'rows',
			'body-dynamic-evenclass' => 'even_class',
			
			'body-dynamic-rownumber' 	=> 'row_number',
			'body-dynamic-rowlevel' 	=> 'row_level',
			'body-dynamic-columns' 		=> 'columns',
			'body-dynamic-columns-attr' => 'attr',
			'body-dynamic-columns-label'=> 'label',
			'body-dynamic-id' 			=> 'id',
			'body-dynamic-actionbar' 	=> 'action_bar',
		
			'action-bar' => 'action_bar',
			'paging-bar' => 'paging_bar',
			
			'prefix' => 'prefix',
			'cmd' 	 => 'cmd',
			'idcmd'  => 'idcmd'
		),
		'action-bar-vars' => array(
			'rules' 		  => 'rules',
			'rules-type' 	  => 'type',
			'rules-key' 	  => 'key',
			'rules-text' 	  => 'text',
			'actions' 		  => 'actions',
			'actions-key'     => 'key',
			'actions-pkey'    => 'pkey',
			'actions-text' 	  => 'text',
			'actions-link' 	  => 'link',
			'actions-disable' => 'disable',
			'prefix' 		  => 'prefix',
			'cmd' 		  	  => 'cmd'
		),
		'row-action-bar-vars' => array(
			'function' 		  => 'function',
			'level' 		  => 'level',
			'id' 			  => 'id',
			'rownumber' 	  => 'row_number',
			'orderbar' 		  => 'order_bar',
			'orderbar-key' 	  => 'key',
			'orderbar-link'   => 'link',
			'actions' 		  => 'actions',
			'actions-key' 	  => 'key',
			'actions-pkey' 	  => 'pkey',
			'actions-text' 	  => 'text',
			'actions-link' 	  => 'link',
			'actions-disable' => 'disable',
			'rules' 		  => 'rules',
			'rules-key' 	  => 'key',
			'rules-text' 	  => 'text',
			'rules-type' 	  => 'type',
			'prefix' 		  => 'prefix',
			'idcmd' 		  => 'idcmd'
		),
		'form-action-bar-vars' => array(
			'rules' 		 => 'rules',
			'rules-type' 	 => 'type',
			'rules-key' 	 => 'key',
			'rules-text' 	 => 'text',
			
			'actions'	 	 => 'actions',
			'actions-key' 	 => 'key',
			'actions-pkey' 	 => 'kpey',
			'actions-text' 	 => 'text',
			'actions-disable'=> 'disable',
		
			'prefix' 		 => 'prefix',
			'cmd' 		 	 => 'cmd',
			
			// this vars supported and in "form-vars" array
			'linguals' 		 => 'linguals',
			'linguals-key' 	 => 'key',
			'linguals-text'  => 'text',
			'linguals-flag'  => 'flag',
			'lingual-current'=> 'current'
		)
	);
	function __construct(){
		//$this->paging = new BasicComponentPaging();
	}
	function getSystemVars($state = true){
		$tmp = $this->system;
		if($this->sorting && !$state){
			$tmp[] = $this->sorting->getPrefix().'dir';
			$tmp[] = $this->sorting->getPrefix().'column';
		}
		return $tmp;
	}
	function getMessage($name_field){
		if(isset($this->messages[$name_field])){
			return $this->messages[$name_field];
		}
		return 0;
	}
	/**
	 * set field message
	 *
	 * @param string $name_field
	 * @param int/string $code
	 * @return boolen
	 */
	function setMessage($name_field,$code){
		$this->messages[$name_field] = $code;
		return true;
	}
	function unsetMessage($name_field){
		unset($this->messages[$name_field]);
	}
	function cleanMessages(){
		$this->messages = array();
	}
	/**
	 * Created SQL data base code
	 * @return boolen
	 */
	function SQL(){
		$data = '';
		foreach ($this->fields as $key => $val){
			if($val[0][0] == '#' || isset($this->fieldsFireign[$key])) continue;

			if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && isset($this->nasional[$val[0]])){
				foreach(BASIC_LANGUAGE::init()->language as $k => $l){
					$multi = $val; $multi[0] = $multi[0]."_".$k;
					$data .= $this->columnProp($multi).",";
				}
			}else{
				$data .= $this->columnProp($val).",";
			}
		}
		return BASIC_SQL::init()->createTable($this->field_id, $this->base, $data);
	}
	function addColumn($message){
		preg_match("/column( name)? '([^']+)'/",$message, $math);
		foreach ($this->fields as $v){

			if($v[0][0] == '#' || isset($this->fieldsFireign[$v[0]])) continue;

			if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && isset($this->nasional[$v[0]])){
				foreach($GLOBALS['BASIC_LANG']->language as $k => $l){
					if(preg_replace("/_".$k."$/", "", $math[2]) == $v[0]){
						$v[0] = $v[0]."_".$k;
						return BASIC_SQL::init()->createColumn($this->base,$this->columnProp($v));
					}
				}
			}
			if($v[0] == $math[2]){
				return BASIC_SQL::init()->createColumn($this->base,$this->columnProp($v));
			}
		}
	}
	/**
	 * Добавя опис на елемент от компонента които се използва за генериране на таблицата в базата,
	 * WEB интеруфеисите и валидацията на URL променливите
	 * 
	 * oficial support context attribute {
	 * 		String text			[name element] 	- the name for db colun and the HTML control
	 * 		String formtype		[text] 			- the component's supported html control. The valud values are:
	 * 				all valid HTML form controls, 
	 * 				HTMLTextarea, 
	 * 				moveSelect, 
	 * 				changeSelect, 
	 * 				dynamicBox, 
	 * 				RadioBox, 
	 * 				render
	 * 		String dbtype		[varchar] 	- all valid type's for db column 
	 * 		Number length		[255]		- max characters for dbcolumn and if support HTML control
	 * 		String perm			[]			- mark this field for required. Contains symbol for HTML interface.
	 * 		String/Number default ''		- set default value if interface had runned in add mode 
	 * 		Array  attributes	[]			- all valid HTML or special attributtes
	 * 		Array  cleaners		[]			- the value's clean handlers. add 2 handlers fore type  
	 * 		Array  messages		[]			- the error messages list.
	 * 		String filter 		null		- SQL for field's sql criteria
	 * 		Array  filterFunction mull		- the class and method's handler for generate of field's sql criteria
	 * 		Boolean lingual     false		- make language versions for HTML control and db column. 
	 * }
	 * this are standart attributes that are suppported from system but if you need more attributes you can add  
	 * and after used it.
	 * 
	 * @param string $name - име на елементаn
	 * @param array $context - описание на елемента 
	 */
	function setField($name,$context = array()){
		if(!isset($context['default'])) $context['default'] = '';
		if(!isset($context['lingual'])) $context['lingual'] = false;
		
		if($context['lingual'] == 'true' || $context['lingual'] == true){
			$this->nasional[$name] = 1;			
		}else if($context['lingual'] == 'false' || $context['lingual'] == false){
			unset($this->nasional[$name]);			
		}
		
		if(!isset($context['messages'])) $context['messages'] = array();
		
		if(!isset($context['messages'][0])) $context['messages'][0] = '';
		if(!isset($context['messages'][1])) $context['messages'][1] = 'Mandatory Field!';
		
		parent::setField($name,$context);
	}
	function unsetField($name){
		if(isset($this->fields[$name])){
			unset($this->nasional[$name]);
		}
		if(isset($this->fieldsFireign[$name])){
			unset($this->fieldsFireign[$name]);	
		}
		parent::unsetField($name);
	}
	/**
	 * Get uploaded file from request. Check for errors. Support multylanguage file upload.
	 * Inside use from method "test".
	 * 
	 * @param field $v
	 * @param string [$lang]
	 */
	protected function test_file($v, $lang = ''){
		BASIC::init()->imported('upload.mod');

		$_lang = '';
		if($lang) $_lang = "_".$lang;		
	
		$this->fieldsFile[$v[0].$_lang] = new BasicUpload($v[0].$_lang);

//		if($lang && $lang == $GLOBALS['BASIC_LANG']->current()){
//			$this->setDataBuffer($v[0], $this->fieldsFile[$v[0].$_lang]);
//		}
		
		$att = BASIC_GENERATOR::init()->convertStringAtt($v[6]);
		if(isset($att['folders']) && $att['folders'] == 'true'){
			if(isset($att['dir'])){
				$path = BASIC_URL::init()->request($v[0].$_lang."_path",
					$this->cleanerDesition($v[3], true, $v[7])
				);
				$multi = explode(";", $att['dir']);
				$test_path = true;
				foreach($multi as $f){
					if(preg_match("#".$path."#", $f)){
						$this->fieldsFile[$v[0].$_lang]->upDir = $path;
						$test_path = false;
						break;
					}
				}
			}
		}else{
			isset($att['dir']) ? $this->fieldsFile[$v[0].$_lang]->upDir = $att['dir'] : '';
		}
		if(isset($att['rand'])) $this->fieldsFile[$v[0].$_lang]->rand = $att['rand'];
		if(isset($att['max']))  $this->fieldsFile[$v[0].$_lang]->maxSize = $att['max'];
		if(isset($att['as']))   $this->fieldsFile[$v[0].$_lang]->AsFile = $att['as'];
		if(isset($att['perm'])) $this->fieldsFile[$v[0].$_lang]->setType(explode(",", $att['perm']));
		// Add Events
		if(isset($att['onComplete'])) $this->fieldsFile[$v[0].$_lang]->onComplete = $att['onComplete'];
		if(isset($att['onError'])) $this->fieldsFile[$v[0].$_lang]->onError = $att['onError'];
		if(isset($att['onDelete'])) $this->fieldsFile[$v[0].$_lang]->onDelete = $att['onDelete'];
		
		$this->fieldsFile[$v[0].$_lang]->test();
		if($ferr = $this->fieldsFile[$v[0].$_lang]->test()){
			if($ferr == 4 || $ferr == 5){
				if($v[1]) $this->setMessage($v[0].$_lang, 1);
			}else{
				$this->setMessage($v[0].$_lang, $ferr);
			}
		}
		$this->setDataBuffer($v[0].$_lang, $this->fieldsFile[$v[0].$_lang]);		
	}
	/**
	 * Testing for empty binding fields and load var of system array $dataBuffer
	 * Last update is moving on the spetial test in the end and if($this->fields[][0] == '') miss
	 * Effect: create array $this->dataBuffer
	 *
	 * @version 0.3 [01-04-2007]
	 * @return boolen
	 */
	 function test(){
		if(!$this->autoTest) return false;

		foreach($this->fields as $v){
			if($this->_ordering && $v[0] == 'order_id'){
				continue;	
			}
			
			$v[2] = (int)$v[2];
			
			$ctrl = BASIC_GENERATOR::init()->getControl($v[5]);
			if($ctrl !== null && $ctrl->isFileUpload()){				
				if(isset($GLOBALS['BASIC_LANG']) && $GLOBALS['BASIC_LANG']->language && isset($this->nasional[$v[0]])){
					foreach($GLOBALS['BASIC_LANG']->language as $k => $l){
						$this->test_file($v, $k);
					}
				} else {
					$this->test_file($v);
				}
			}else{
				if(isset($GLOBALS['BASIC_LANG']) && $GLOBALS['BASIC_LANG']->language && isset($this->nasional[$v[0]])){
					foreach($GLOBALS['BASIC_LANG']->language as $k => $l){
						$var_url = $GLOBALS['BASIC_URL']->request($v[0]."_".$k,
							$this->cleanerDesition($v[3],true,$v[7])
						);
				
//						if($k == $GLOBALS['BASIC_LANG']->current()){
//							$this->setDataBuffer($v[0],$var_url);
//						}
						$this->setDataBuffer($v[0]."_".$k,$var_url);
						if($v[1] && (string)$this->dataBuffer[$v[0]."_".$k] == ''){
							$this->setMessage($v[0],1);
						}
					}
				}else{
					$var_url = BASIC_URL::init()->request($v[0],
						$this->cleanerDesition($v[3],true,$v[7])
					);
					$this->setDataBuffer($v[0],$var_url);
					if(($v[1] && (string)$this->dataBuffer[$v[0]] == '')){
						$this->setMessage($v[0],1);
					}
				}
			}
		}
		if($this->specialTest != ''){
			if(is_array($this->specialTest)){
				$obj = &$this->specialTest[0];
				$method = $this->specialTest[1];
				$err = false;
				if($obj != null){
					$err = $obj->$method($this,$id,$action);
				}else{
					$err = $method($id,$action);
				}
			}else{
				$special = $this->specialTest;
			//	$err = $this->$special($this,$id,$action);
				$err = $this->$special();
			}
			if($err && !$this->messages){
			    $this->messages = array(-1);
			}
		}
		return ($this->messages ? true : false);
	}
	/**
	 * Call commponent's actions. Support valid action, form type action (3). If called action is forbidden or not exist
	 * append to BASIC_ERROR service message and change errorAction value to 'list'. 
	 *
	 * @param string $action
	 * @param array:int [$id]
	 * @param boolean [$useTest]
	 */
	function action($action, $id = null, $useTest = true){
		$tmp = '';
		try{
			if(isset($this->actions[$action])){
				
				if($this->actions[$action][1] >= 0){
					$caller = $this->actions[$action][0];
					
					//@FIX need think for cancel action and test!!!
					if($action != 'cancel' && $useTest && $this->actions[$action][1] == 3){
						if(!$this->test()){
							$tmp = $this->$caller($id,$action);
						}else{
							$tmp = false;
						}
					}else{
						$tmp = $this->$caller($id,$action);
					}
				}else{
					throw new Exception("Action '".$action."' is forbidden. ");	
				}
			}else{
				throw new Exception("Action '".$action."' is not supported. ");	
			}
		}catch (Exception $e){
			BASIC_ERROR::init()->setError($e->getMessage());	
			$this->errorAction = 'list';
			$tmp = '';
		}
		return $tmp;
	}
	
	private $is_url_loaded = false;
	function loadURLActions(){
		if(!$this->id){
			if($this->id = BASIC_URL::init()->request($this->prefix.'id', 'Int')){
				$this->system[] = $this->prefix.'id';
				if(is_array($this->id) && count($this->id) == 1){
					$this->id = $this->id[0];
					BASIC_URL::init()->set($this->prefix.'id', $this->id);
				}
			}
		}
		foreach($this->actions as $k => $v){
			if(BASIC_URL::init()->request($this->prefix.'cmd'.$k)){
				$this->cmd = $k; 
				$this->system[] = $this->miss[] = $this->prefix.'cmd'.$k;
				break;
			}
		}
		if(!$this->cmd){
			if($this->cmd = BASIC_URL::init()->request($this->prefix.'cmd')){
				$this->system[] = $this->miss[] = $this->prefix.'cmd';
			}
		}
		$this->is_url_loaded = true;

	}
	function listenerActions(){
		if($this->cmd){
			if($tmp = $this->action($this->cmd,$this->id)){
				return $tmp;
			}
		}
		return '';
	}
	function chackForActions($n){
		$action = false;
		foreach ($this->actions as $v){
			if($v[1] == $n || $v[1] == ($n*(-1))) $action = true;
		}
		return $action;
	}
	/**
	 * Add new action. 
	 *	The param $activate is flag for button action's location. The type locations are: 
	 *		1 - action manager in list interface
	 *		2 - row action manager in list interface
	 *		3 - buttons bar in form interface
	 *
	 *	The param $rule is javascript rules. The type rules are:
	 *		javascript:(avascript code) - any javascript code
	 *		message:(text) - open alert dialog with content (text)
	 *		confirm:(text) - open confirm dialog with content text
	 *
	 * @param string $action
	 * @param string $method
	 * @param string [$text]
	 * @param int [$activate] 
	 * @param string [$rule]
	 */
	public function addAction($action, $method, $text = '', $activate = 1, $rule = ''){
		$this->actions[$action] = array($method, $activate, $text, $rule);
	}
	/**
	 * Edit exist action
	 *
	 * @param string $action
	 * @param string [$method]
	 * @param string [$text]
	 * @param int [$activate]
	 * @param int [$activate]
	 */
	public function updateAction($action, $method = null, $text = null, $activate = 0, $rule = ''){
		if(isset($this->actions[$action])){
			$this->actions[$action] = array(
				($method != null ? $method : $this->actions[$action][0]),
				($activate != null ? $activate : $this->actions[$action][1]),
				($text != null ? $text : (isset($this->actions[$action][2]) ? $this->actions[$action][2] : '') ),
				($rule != null ? $rule : (isset($this->actions[$action][3]) ? $this->actions[$action][3] : '') )
			);
		}
	}
	/**
	 * Delete exist action
	 *
	 * @param string $action
	 */
	public function delAction($action){
		if(isset($this->actions[$action])){
			unset($this->actions[$action]);
		}
	}
	/**
	 * Get action's list
	 * 
	 * @return array
	 */
	public function getActions(){
		return $this->actions;
	}
	function ActionFormAdd(){
		return $this->FORM_MANAGER();
	}
	function ActionFormEdit($id = 0){
		if($id && !$this->messages){
			$this->ActionLoad($id);
		}
		return $this->FORM_MANAGER();
	}
	function ActionSave($id = 0){
		if($id){
			return $this->ActionEdit($id);
		}else{
			return $this->ActionAdd();
		}
	}
	function ActionAdd(){
		if($this->_ordering){
			$rdr = BASIC_SQL::init()->read_exec(" SELECT MAX(`order_id`)+1 AS `max` FROM `".$this->base."` "); 
			$rdr->read();
			$this->setDataBuffer("order_id", (int)$rdr->field('max'));
		}
		return parent::ActionAdd();
	}
	/**
	 * Load data row
	 * WARNING : this method is wanting optimization ...
	 *
	 * @access protected
	 * @param int $id
	 * @return array
	 */
	function ActionLoad($id = 0){
		if(!$id) $id = (int)$this->id;

		$row = $this->getRecord($id);
		
		foreach($this->fields as $v){
			if(isset($this->fieldsFireign[$v[0]])){
				$this->dataBuffer[$v[0]] = $this->fieldsFireign[$v[0]]->load($id);
			}else if(!isset($row[$v[0]]) || $v[0][0] == '#'){
				//$this->dataBuffer[str_replace("#",'',$v[0])] = '';
			}else{
				$this->dataBuffer[$v[0]] = BASIC_URL::init()->other($row[$v[0]], null,
					$this->cleanerDesition($v[3], false, $v[7])
				);
			}
		}
		$this->dataBuffer[$this->field_id] = $row[$this->field_id];
	
    	if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG'])){
    		foreach($this->nasional as $n => $v){
    
    			if(!isset($this->fields[$n])) continue;
    
    			foreach(BASIC_LANGUAGE::init()->language as $k => $l){
    				if(isset($row[$n.'_'.$k])){
	    				$this->setDataBuffer($n.'_'.$k, BASIC_URL::init()->other($row[$n.'_'.$k], null,
	    					$this->cleanerDesition($this->fields[$n][3],false,$this->fields[$n][7])
	    				));
    				}else{
    					$this->setDataBuffer($n.'_'.$k, '');
    				}
    				if($k == BASIC_LANGUAGE::init()->current()){
    					$this->setDataBuffer($n, $this->getDataBuffer($n.'_'.$k));
    				}
    			}
    		}
    	}
	}
	function ActionError($id){
		$class_inst = 'BasicUpload';
		
	    foreach ($this->dataBuffer as $k => $v){
			$fname = $k;
	   		if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG'])){
	   			$tmp = '';
	   			foreach($GLOBALS['BASIC_LANG']->language as $lk => $l){
	   				$tmp = str_replace("_".$lk, '', $k);
	   				if(isset($this->nasional[$tmp])){
	   					$fname = $tmp; break;
	   				}
	   			}
	   		}
	   		
        	if($v instanceof $class_inst){
        		if($id && isset($this->fields[$fname])){
        			$res = BASIC_SQL::init()->read_exec(" SELECT `".$k."` FROM `".$this->base."` WHERE `".$this->field_id."` = ".$id." ", true);
        			
        			$this->dataBuffer[$k] = BASIC_URL::init()->other($res[$k], null,
	    	           $this->cleanerDesition($this->fields[$fname][3], false, $this->fields[$fname][7])
	    	        );
        		}else{
        			$this->dataBuffer[$k] = '';
        		}
        	}else{
        		if(isset($this->fields[$fname])){
					$this->dataBuffer[$k] = BASIC_URL::init()->other($v, null,
	    	           $this->cleanerDesition($this->fields[$fname][3], false, $this->fields[$fname][7])
	    	        );
        		}else{
        			$this->dataBuffer[$k] = $v;
        		}
        	}  
	    }
	    return $this->action($this->errorAction, $id, false);
	}
	/**
	 * Extras method for change boolen field
	 * Sintax action (Un)(Action)
	 * (Un) is key for off state
	 * strtolower(Action) is name changed field
	 *
	 * @param int [$id]
	 * @param string [$action]
	 * @version 0.3
	 */
	function ActionBoolen($id, $action){
		$key = 1;
		preg_match("/^(Un)?(.+)$/", $action, $reg);

		if($reg[1]) $key = 0;

		if(!$id){
			$id = (int)BASIC_URL::init()->request($this->prefix.'id');
		}else{
			if(!is_array($id)) $id = array($id);
		}
		BASIC_SQL::init()->exec(" UPDATE `".$this->base."` SET `".strtolower($reg[2])."` = ".$key." WHERE `".$this->field_id."` IN ".($id?"(".implode(",",$id).")":"(0)")." ");
	}
	/**
	 * Change order rows records
	 * WARNING :: This functionality no work on MsSql serrver !!!
	 *
	 * @access protected
	 * @param int [$id]
	 * @param string [$action]
	 * @version 0.3 beta
	 */
	function ActionOrder($id,$action){
		if(!$id) return;
		
		BASIC_SQL::init()->exec(" SET @order_id = 0 , @new_id = 0 ,@new_order = 0 , @mx = 0 , @cnt = 0 , @id_num = ".$id."; ");
		$rdr = BASIC_SQL::init()->read_exec("
				SELECT @order_id:=c.`order_id` AS `ord`,
				       @mx:= max(d.`order_id`) AS `max`,
				       @cnt:= count(d.id)      AS `rows`
				FROM `".$this->base."` d LEFT JOIN `".$this->base."` c ON c.`".$this->field_id."` = @id_num
			    GROUP BY c.`order_id`;
		"); 
		$rdr->read();
		
		$err = BASIC_ERROR::init()->error();
		if($err['code'] == 1054){
			BASIC_SQL::init()->exec("ALTER TABLE `".$this->base."` ADD COLUMN `order_id` int(11) NOT NULL DEFAULT 0 ");
			BASIC_ERROR::init()->clean();
			$this->ActionOrder($id, $action);
			return ;
		}
		$flag = 1;
		if($action == 'order_up') $flag = -1;
		if($rdr->field('ord') > -1 && ($rdr->field('ord') < $rdr->field('max') || $flag < 0)){
			BASIC_SQL::init()->exec(" SET @i = @order_id; ");
			BASIC_SQL::init()->exec(" UPDATE `".$this->base."` SET order_id = @i:= @i + 1 WHERE `order_id` = @order_id AND `".$this->field_id."` != @id_num; ");
			if($flag > 0){
				BASIC_SQL::init()->exec(" SELECT @new_order:=order_id,@new_id:=`".$this->field_id."` FROM `".$this->base."`  WHERE `order_id` > @order_id ORDER BY `order_id` LIMIT 1; ");
			}else{
				BASIC_SQL::init()->exec(" SELECT @new_order:=order_id,@new_id:=`".$this->field_id."` FROM `".$this->base."`  WHERE `order_id` < @order_id ORDER BY `order_id` DESC LIMIT 1; ");
			}
			BASIC_SQL::init()->exec(" UPDATE `".$this->base."` SET `order_id` = @order_id WHERE `".$this->field_id."` = @new_id; ");
			BASIC_SQL::init()->exec(" UPDATE `".$this->base."` SET `order_id` = @new_order WHERE `".$this->field_id."` = @id_num; ");
		}
	}
	/**
	 * @TODO NEED REVIEW THIS ACTIONS. IN NEXT VERSION THIS ACTION WILL BE HIDE BY DEFAULT.
	 *
	 * @param int $id
	 * @param string $action
	 */
	function ActionFileRemove($id, $action, $is_not_url_column_name = ""){
		if(!$is_not_url_column_name){
			$column_name = BASIC_URL::init()->request('fname', 'addslashes', 255);
		}else{
			$column_name = $is_not_url_column_name;
		}
		$file_name = BASIC_SQL::init()->read_exec(" SELECT `".$column_name."` as `file_name`FROM `".$this->base."` WHERE 1=1 AND `".$this->field_id."` = ".(int)$id." ",true);
		
		/**
		 * Find real field name.
		 */
		$field_column = $column_name;
		if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG'])){
			foreach($GLOBALS['BASIC_LANG']->language as $k => $l){
				$field_column = str_replace("_".$k,'',$field_column);
			}
		}
		
		$file_settings = $this->getField($field_column);
		$file_settings['attributes'] = BASIC_GENERATOR::init()->convertStringAtt($file_settings['attributes']);
		
		BASIC_SQL::init()->exec(" UPDATE `".$this->base."` SET `".$column_name."` = '' WHERE 1=1 AND `".$this->field_id."` = ".(int)$id." ");
		BASIC::init()->imported('upload.mod');
		$fl = new BasicUpload(null);
		$fl->upDir = $file_settings['attributes']['dir'];
		if(isset($file_settings['attributes']['onDelete'])){
			$fl->onDelete = $file_settings['attributes']['onDelete'];
		}
		$fl->delete($file_name['file_name']);

		$this->system[] = 'fname';
		$this->system[] = 'oldcmd';
		
		if(!$is_not_url_column_name){
			$old_cmd = BASIC_URL::init()->request('oldcmd','addslashes',255);
			BASIC_URL::init()->redirect(BASIC::init()->scriptName(), BASIC_URL::init()->serialize($this->system).
				($old_cmd ? $this->prefix.'cmd='.$old_cmd.'&' : '').
				$this->prefix.'id='.$id
			);
		}
		return $id;
	}
	function ActionList(){
		return $this->LIST_MANAGER();
	}
	/**
	 * Created HTML form manager
	 *
	 * @version 1.2 [27-02-2007]
	 * @param string [$tplname]
	 * @param string [$dynamic]
	 * @return string
	 * @example
	 *
	 * ex.1: Template syntax
	 *
	 * 		 Sintax name variables
	 * 			PERM  -> replace with * for permition field.
	 * 			TEXT  -> for text field
	 * 			VALUE -> for form element
	 *
	 * 		 Syntax template no exist dynamic template used name variable.'_'.name field
	 * 		!) Use template stayle
	 * 		     $html_manager = $this->HTML('tpl name');
	 *
	 * 		!) Use template with dynamic template style
	 *           $htmp_dynamic_manager = $this->HTML('tpl name','tpl dynamic name',
	 * 				array(array element tpl variables and them values)
	 * 			)
	 *
	 * 		!) Use standart
	 * 			 $html_standart_manager = $this->HTML();
	 */
	function FORM_MANAGER($form_attribute = array()){
		
		$tplname = $this->template_form;
		$dynamic = $this->templates['form-dynamic'];

		BASIC::init()->imported('template.mod');

		$fields = array();
		$file = false;
		
		$att = BASIC_GENERATOR::init()->convertStringAtt($form_attribute);
		
		foreach($this->fields as $v){
			$tag = $v[5];

			$attribute = array();
			if(isset($v[6])) $attribute = $v[6];
			

			if($v[1] && !isset($attribute['lang'])) $attribute['lang'] = 'on';
 			
			$length = (int)$v[2];
			if($length && !isset($attribute['maxlength'])){
			   $attribute['maxlength'] = $length;
			}
			
			$tagPHP = '';
			if($tag == 'hidden'){
				$this->hidden_el .= BASIC_GENERATOR::init()->controle('input', $v[0], $this->getDataBuffer($v[0]), array('type' => 'hidden'));
				$this->miss[] = $v[0];
			}else{
				if(BASIC_GENERATOR::init()->getControl($tag)->isFileUpload()) $att["enctype"] = "multipart/form-data";
				
				if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && isset($this->nasional[$v[0]])){
					if(BASIC_LANGUAGE::init()->number() > 1){
						if(isset($attribute['class'])){
							$attribute['class'] .= ' form_lingual_field';
			 			}else{
			 				$attribute['class'] = 'form_lingual_field';
			 			}
					}
					foreach(BASIC_LANGUAGE::init()->language as $k => $l){
						
						if($k != BASIC_LANGUAGE::init()->current()){
							if(isset($attribute['style']))
								$attribute['style'] .= 'display:none;';
							else
								$attribute['style'] = 'display:none;';								
						}else{
							if(isset($attribute['style']))
								$attribute['style'] .= 'display:block;';
							else
								$attribute['style'] = 'display:block;';		
						}
						$attribute['lang'] = $k;
						if(BASIC_GENERATOR::init()->getControl($tag)->isFileUpload()){
							$attribute = $this->_createFileUploadButton($v[0].'_'.$k, $attribute);
						}
						if($v['default'] && !$this->id && !isset($this->dataBuffer[$v[0].'_'.$k])){
						    $this->setDataBuffer($v[0].'_'.$k, $v['default']);
						}
						$tagPHP .= BASIC_GENERATOR::init()->controle($tag, $v[0].'_'.$k, $this->getDataBuffer($v[0].'_'.$k), $attribute);
						$this->miss[] = $v[0].'_'.$k;
					}
				}else{
					if(BASIC_GENERATOR::init()->getControl($tag)->isFileUpload()){
						$attribute = $this->_createFileUploadButton($v[0], $attribute);
					}	
					if($v['default'] && !$this->id && !isset($this->dataBuffer[$v[0]])){
						$this->setDataBuffer($v[0], $v['default']);
					}				
					$tagPHP .= BASIC_GENERATOR::init()->controle($tag, $v[0], $this->getDataBuffer($v[0]), $attribute);
					$this->miss[] = $v[0];
				}
			}
			if(!$tagPHP) continue;

		    $message = (isset($v['messages'][(int)$this->getMessage($v[0])]) ? $v['messages'][(int)$this->getMessage($v[0])] : $v['messages'][0]);
			
			$fields[$v[0]] = array(
				$this->templates['form-vars']['perm'] 	 => ($v[1] ? $v[1] : ""),
				$this->templates['form-vars']['label']	 => $v[4],
				$this->templates['form-vars']['ctrl']	 => $tagPHP,
				$this->templates['form-vars']['message'] => $message
			);
		}
		$this->miss[] = 'APC_UPLOAD_PROGRESS'; // clean standart progress variable ... 

		if(!isset($att['action'])) $att['action'] = BASIC_URL::init()->link(BASIC::init()->scriptName());
		if(!isset($att['method'])) $att['method'] = 'post';
		if(!isset($att['name']) && $this->prefix){
			$att['name'] = $this->prefix;
		}
		
		BASIC_TEMPLATE2::init()->set($this->dynamicLingualFormSupport(), $tplname);	
		BASIC_TEMPLATE2::init()->set(array(
			$this->templates['form-dynamic'] => $fields,
			$this->templates['form-vars']['buttons_bar'] => $this->buttonActionsBar()
		), $tplname);
		return BASIC_GENERATOR::init()->form($att, 
			BASIC_TEMPLATE2::init()->parse($tplname).
			"\n<!-- hidden elements -->\n".
			$this->hidden_el.
			"\n<!-- form state -->\n".
			($this->useSaveState ? BASIC_URL::init()->serialize($this->miss, 'post') : '')
		);
	}
	protected function _createFileUploadButton($name, $attribute){
		$attribute = BASIC_GENERATOR::init()->convertStringAtt($attribute);
		if(isset($attribute['delete_btn'])){
			$delete_btn = BASIC_GENERATOR::init()->convertStringAtt($attribute['delete_btn']);
			$delete_btn['href'] = BASIC_URL::init()->link(BASIC::init()->scriptName(), BASIC_URL::init()->serialize($this->system).$this->prefix.'cmd=fileRemove&fname='.$name.'&'.$this->prefix.'id='.$this->id.($this->cmd ? '&oldcmd='.$this->cmd : ''));
			if(isset($delete_btn['class'])){
				$delete_btn['class'] .= ' FileRemove';
			}else{
				$delete_btn['class'] = 'FileRemove';
			}
			if(!isset($delete_btn['id'])){
				$delete_btn['id'] = 'cmdFileRemove';
			}
			$attribute['delete_btn'] = $delete_btn;
		}
		if(!$this->getDataBuffer($name)){
			unset($attribute['delete_btn']);	
		}
		return $attribute;	
	}
    /**
     * Create system variables
     */
	function startManager(){
		if(!$this->is_url_loaded) $this->loadURLActions();
		
		foreach($this->fields as $v){
			if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && isset($this->nasional[$v[0]])){
				foreach($GLOBALS['BASIC_LANG']->language as $k => $l) $this->system[] = $v[0].'_'.$k;
			}
			if(isset($v[0])) $this->system[] = $v[0];
		}
		if($this->_ordering && !$this->sorting){
			BASIC::init()->imported('bars.mod');
			$this->sorting = new BasicSorting('order_id', $this->prefix);
		}
	}
	/**
	 * Create element for to map declaration
	 * if($field == '' && $colback != '') call user function with param table id key
	 *
	 * proto type user function
	 * 		function protoTypeUser($id){
	 * 			# definitions ...
	 * 		}
	 */
	function _map(){
		$tmp = '#'.microtime();
		if(isset($this->map[$tmp])){
			$tmp = $this->_map();
		}
		return $tmp;
	}
	/**
	 * Контрол на подреждането.
	 *
	 * @param boolen $status
	 * @return boolen _ordering
	 */
	 function ordering($status = null){
		if($status === false){
			$this->_ordering = false;
			$this->unsetField('order_id');
			
			$this->delAction('order_up');
			$this->delAction('order_down');
		}elseif($status === true){
			$this->_ordering = true;
			$this->setField("order_id",array(
				'dbtype' => 'int',
				'length' => 11,
				'formtype' => 'hidden'
			));
			$this->addAction('order_up', 'ActionOrder', '', 0);
			$this->addAction('order_down', 'ActionOrder', '', 0);
		}
		return $this->_ordering;
	}
	/**
	 * Опис и настроика на лист информацията.
	 *
	 * @param string $field	[име на колоната ако започва с # оказва да не се търси в базата]
	 * @param string $header [текст за хедара на колоната]
	 * @param string/array $colback [име на метод или функция за форматиране на съдаржанието на кутията]
	 * @param string/array $attribute [специфични настройки на колоната]
	 * @param boolen $sort [розрешено ли е съртиране по тази колона]
	 */
	function map($field, $header, $colback='', $attribute='', $sort=true){

		if(!is_numeric($field) && ($field == '' || $field == '#')){
			$field = $this->_map();
		}else if($this->sorting && $sort){
			$header = $this->sorting->sortlink($field, $header);
		}
		$this->map[$field] = array($header, $colback, $attribute, true);
	}
	function unmap($name){
		if(is_array($name)){
			foreach($name as $v){
				unset($this->map[$v]);
			}
		}else{
			unset($this->map[$name]);
		}
	}
	function addMapElement($name){
		if(is_array($name)){
			foreach ($name as $v){
				$this->map[$v] = array(null,null,null,false);
			}		
		}else{
			$this->map[$name] = array(null,null,null,false);
		}
	}
	function createInterface(){
		if($this->cmd){
					
			// Support use component exeptions 
			try{
				$t = $this->listenerActions();
			}catch(Exception $e){
				BASIC_ERROR::init()->append($e->getCode(), $e->getMessage());
			}
			
			if($t && is_string($t)){
				return $t;
			}else{
				if(!$this->messages && !BASIC_ERROR::init()->exist(array('fatal', 'warning'))){
					$this->ActionBack();
				}else{
					if($this->errorAction){
						return $this->action('error', $this->id);
					}
				}
			}
		}
		if(isset($this->actions['list'])){
			return $this->action("list", $this->id);
		}else{
			throw new Exception("Action 'list' is requare to exist!");
		}
	}
	function select($criteria = ''){
		$tmp = " SELECT ";
		foreach ($this->fields as $k => $v){
			if(substr($k,0,1) != '#'){
				if(isset($this->fieldsFireign[$k])){
					$tmp .= " '' AS `".$k."`,\n";
				}else if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && isset($this->nasional[$k])){
					$tmp .= " `".$k."_".$GLOBALS['BASIC_LANG']->current()."` AS `".$k."`, ";
				}else{
					if(strpos($k,' ') !== false){
						$tmp .= " ".$k.", "; // is sub query
					}else{
						$tmp .= "`".$k."`, ";
					}
				}
			}
		}
		$tmp .= " `".$this->base."`.* FROM `".$this->base."` WHERE 1=1 ";
		if($criteria) $tmp .= $criteria;
		
//		$tmp = str_replace("\n",'',$tmp);
		
		if($this->_ordering && !preg_match("/[\n\t\r ]+order by/i",$tmp)){
			if(preg_match("/[\n\t\r ]+limit /i",$tmp)){
				$tmp = preg_replace("/[\t\n\r ]+limit /i"," ORDER BY `order_id` LIMIT ",$tmp);
			}else{
				$tmp .= " ORDER BY `order_id` ";
			}
		}
		return $tmp;
	}
	
	   // foreing list functions 
	       var $_FOREING_LIST_CONTAINER = array();
	       function load_foreing_list_firld($name,$id){
	           if(isset($this->fieldsFireign[$name])){
	               if(!isset($_FOREING_LIST_CONTAINER[$id])){
	                   $_FOREING_LIST_CONTAINER[$id] = $this->fieldsFireign[$name]->load($id);
	               }
	               return $_FOREING_LIST_CONTAINER[$id];
	           }
	       }
	       

	// Selections //
	function getRecord($id,$type = 'row'){ // for return readobject use second parameter value 'object'
		$rdr = $this->getRecords(array($id));
		if($type == 'row'){
			$rdr->read();
			return $rdr->getItems();
		}
		return $rdr;
	}
   /**
     * Full record's data loader
     *
     * @param array $ids
     * @param string $criteria
     * @return ComponentReader
     */
	function getRecords($ids = array(),$criteria = ''){
		if($ids){
		    if(!is_array($ids)) $ids = array($ids);
			$criteria = " AND `".$this->field_id."` IN (".implode(",",$ids).") ".$criteria;
		}
		
		$rdr = BASIC_SQL::init()->read_exec($this->select($criteria));
		
				BASIC_ERROR::init()->reset();
		$err = 	BASIC_ERROR::init()->error();
		if($err['code'] == 1146){
			if($tmp = $this->SQL()){
			    //FIX - run only if create parent table
			    foreach ($this->fieldsFireign as $fkey => $fval){
			        $fval->load(0);
			    }
				BASIC_ERROR::init()->clean();
			}
		}else if($err['code'] == 1054){
			if($tmp = $this->addColumn($err['message'])){
				//FIX - run only if create parent table
			    foreach ($this->fieldsFireign as $fkey => $fval){
			        $fval->load(0);
			    }
				BASIC_ERROR::init()->clean();
				return $this->getRecords($ids, $criteria);
			}
		}
		return new ComponentReader($rdr, $this);
	}
	/**
	 * "getRecords"'s shorcut function. Miss useful first parametar $ids on original method.
	 * 
	 * @param string $criteria
     * @return ComponentReader
	 */
	function read($criteria = ''){
		return $this->getRecords(null, $criteria);
	}
	/**
	 * Разширение на "getRecords" с което се извличат резултатите във формата на масив.
	 *
	 * @param array $ids
	 * @param string $criteria
	 * @return array
	 */
	function getRecordsArray($ids = array(),$criteria = ''){
		$rdr = $this->getRecords($ids, $criteria);
		$tmp = array();
		while($rdr->read()){
			$tmp[] = $rdr->getItems();
		}
		return $tmp;
	}
	/**
	 * redirect page and send exist variables
	 *
	 */
	function managerRefresh(){
		$this->ActionBack();
	}
	function ActionBack(){
		BASIC_URL::init()->redirect(BASIC::init()->scriptName(), BASIC_URL::init()->serialize($this->system));
	}
	/**
	 * Create log url request
	 *
	 * @return string
	 */
	function managerSaveVar($method = 'get'){
		return $this->useSaveState ? BASIC_URL::init()->serialize($this->system, $method) : '';
	}
	/**
	 * Create Header row
	 *
	 * @param boolen $manager
	 * @return string
	 */
	function cmpHeaders($manager = true){
		$columns = array(); $len = 0;
		
		if(!$this->map){
			foreach($this->fields as $k => $v)
				$this->map($k, $v[4]);
		}
		foreach ($this->map as $k => $v){
			if($v[3]){ 
				$columns[] = array(
					$this->templates['list-vars']['head-dynamic-label'] => $v[0],
					$this->templates['list-vars']['head-dynamic-attr'] => ($v[2] ? BASIC_GENERATOR::init()->convertAtrribute($v[2]) : null),
					$this->templates['list-vars']['head-dynamic-selected'] => ($this->sorting && $this->sorting->selected($k)),
					$this->templates['list-vars']['head-dynamic-isdown'] => ($this->sorting && $this->sorting->isDown()) 
				);
				$len++;
			}
		}
		BASIC_TEMPLATE2::init()->set(array(
			$this->templates['list-vars']['head-check'] => ($manager && $this->chackForActions(1)),
			$this->templates['list-vars']['head-order'] => ($manager && $this->chackForActions(2) && $this->_ordering == true && $this->sorting ? $this->sorting->createUrlForLink('order_id') : ''),
			$this->templates['list-vars']['head-dynamic'] => $columns,
			$this->templates['list-vars']['head-length'] => $len
		), $this->template_list);
	}
	/**
	 * Create row
	 * 
	 * @param array $array
	 * @param array $manBar
	 * @param string/array $attribute
	 * @return string
	 */
	function cmpRows($array, $attribute = ''){
		
		$rows = array();
		$rl = 0;
		$class = '';
		$attribute = BASIC_GENERATOR::init()->convertStringAtt($attribute);
		
		$action_bar_settings = array();
		if(isset($attribute['action_bar'])){
			$action_bar_settings = $attribute['action_bar'];
			unset($attribute['action_bar']);
		}
		
		foreach($array as $_key_ => $val) {
			$row_level = (isset($val['__level']))? $val['__level'] : 0;
			$even_class = false;
			if($rl == 0){
				$even_class = true;
			}

			$columns = array();
			foreach($this->map as $k => $v){
				if(!$v[3]) continue;

				if(is_array($v[1])){
					$class = &$v[1][0];
					$method = $v[1][1];
				}else{
					$method = $v[1];
				}

				// foreing extension 
				if(isset($this->fieldsFireign[$k])){
				    $val[$k] = $this->load_foreing_list_firld($k,$val[$this->field_id]);
				}
				
				$column_body = '';
				if($k[0] == '#' && $v[1] != ''){ // create specifick field
					if(is_array($v[1])){
						$class = &$v[1][0];$method = $v[1][1];
						
						$column_body = ($class != null ? $class->$method(null,$k,$val) : $method(null,$k,$val));
					}else{
						$column_body = $this->$v[1](null,$k,$val);
					}
				}else if($k != '' && $v[1] != ''){ // formated information field
					if(is_array($v[1])){
						$class = &$v[1][0];$method = $v[1][1];

						$column_body = ($class != null ? $class->$method($val[$k],$k,$val) : $method($val[$k],$k,$val));
					}else{
						$column_body = $this->$v[1]($val[$k],$k,$val);
					}
				}else{
					$column_body = (isset($val[$k]) ? $val[$k] : '');
				}
				
				$columns[] = array(
					$this->templates['list-vars']['body-dynamic-columns-label'] => $column_body,
					$this->templates['list-vars']['body-dynamic-columns-attr'] => ($v[2] ? BASIC_GENERATOR::init()->convertAtrribute($v[2]) : '')
				);
			}
			
			// start permissions test
			$mark = true;
			$_action_bar_settings = $action_bar_settings;

			foreach ($this->actions as $a_key => $a_val){
				if($a_val[1] == -2){
					$_action_bar_settings['actions'][$a_key] = 'disable';	
				}
				if(
					($a_val[1] == 1 || $a_val[1] == -1) && 
					!($a_key[0] == '_' && $a_key[1] == '_') && 
					$a_key != 'cancel' && 
					$a_key != 'add'
				){
					$mark = false;
				}
			}
			$_action_bar_settings['mark']['disabled'] = $mark;

			$val['row_number'] = $_key_;
			$rows[] = array(
				$this->templates['list-vars']['body-dynamic-rowlevel'] => $row_level,
				$this->templates['list-vars']['body-dynamic-columns'] => $columns,
				$this->templates['list-vars']['body-dynamic-evenclass'] => $even_class,
				$this->templates['list-vars']['body-dynamic-actionbar'] => $this->rowActionsBar($val, $_action_bar_settings),
				$this->templates['list-vars']['body-dynamic-rownumber'] => $_key_,
				$this->templates['list-vars']['body-dynamic-id'] => $val['id']
			);
			if($rl == 0){
				$rl = 1;
			}else{
				$rl = 0;
			}
		}
		BASIC_TEMPLATE2::init()->set(array(
			$this->templates['list-vars']['body-dynamic'] => $rows
		), $this->template_list);
	}
	/**
	 * Concat action manager and return listing manager
	 *
	 * @param string hendlar $actionsBar
	 * @param object $paging
	 * @param string/array $attribute
	 * @return string
	 */
	function footerBar(){
		$pbar = ($this->paging ? $this->paging->getBar() : '');
		
		BASIC_TEMPLATE2::init()->set(array(
			$this->templates['list-vars']['action-bar'] => $this->footerActionsBar(),
			$this->templates['list-vars']['paging-bar'] => $pbar
		), $this->template_list);
	}
	/**
	 * Create listing manager.He exec select query for info for creting listing manager
	 *
	 * @param string $criteria
	 * @param int $maxRow
	 * @return string
	 */
	function LIST_MANAGER($criteria = ''){
		$arr = array();
		if($this->base){
			$_map = true; if(!$this->map) $_map = false;
			
			foreach($this->fields as $k => $v){
				if(isset($v['filter']) || isset($v['filterFunction'])){
					if(!$this->filter){
						$this->filter = new BasicFilter();
						$this->filter->prefix($this->prefix.'f');
						$this->filter->template($this->template_filter, $this->template_filter_default);
						if(isset($this->actions['filter'])){
							$this->filter->button($this->actions['filter'][2]);
						}
					}
					if(isset($v['filter']) && $v['filter'] == 'auto'){
						if($v[2] == 'int'){
							$tmp = $this->getField($k);
							$tmp['filter'] = " AND (`{1}` >= {V1} OR `{2}` <= {V2}) ";
							$this->filter->rangeField($k, $tmp);
						}else{
							$tmp = $this->getField($k);
							$tmp['filter'] = " AND `".$k."` LIKE '%{V}%' ";
							$this->filter->field($k, $tmp);		
						}
					}else{
						$this->filter->field($k,$v);
					}
				}
				if(!$_map && $v[3] != 'none') $this->map($k, $v[4]); 
			}
			
			if($this->filter){
				$this->filter->init();
				$criteria .= $this->filter->sql();
			}
			if($this->sorting) $criteria .= $this->sorting->getsql();
		
			$rdr = $this->read($criteria);
			if($this->maxrow != 0 && $rdr->num_rows() > $this->maxrow){
				
	        	if(!$this->paging){
					BASIC::init()->imported('bars.mod');
					$this->paging = new BasicComponentPaging($this->prefix);
	        	}
				$this->paging->init($rdr->num_rows(), $this->maxrow);
				
				$rdr = $this->read($criteria.$this->paging->getSql());
			}
			while($rdr->read()){
				$arr[$rdr->item('id')] = $rdr->getItems();
			}
		}
		return $this->compile($arr);
	}
	/**
	 * Поставяне на променливите и парсване на темплейта.
	 * 
	 * @param array $arr
	 */
	public function compile($arr){
		$this->cmpHeaders();
		$this->cmpRows($arr);
		$this->footerBar();
		
		BASIC_TEMPLATE2::init()->set(array(
			$this->templates['list-vars']['prefix'] => $this->prefix,
			$this->templates['list-vars']['cmd'] => $this->prefix.'id'
		), $this->template_list);
		
		return ($this->filter ? $this->filter->form() : '').
			BASIC_GENERATOR::init()->form("enctype=multipart/form-data|method=post|name=".$this->prefix, 
				BASIC_TEMPLATE2::init()->parse($this->template_list).
				"\n<!-- form state -->\n".
				$this->managerSaveVar('post')
			);
	}
	/**
	 * Create action's button's bars.
	 *
	 * @return string
	 */
	function buttonActionsBar($type = 3){
		$arr = $this->buttonActionsBarOnly($type)+$this->dynamicLingualFormSupport();
		return $arr;
	}
	function dynamicLingualFormSupport(){
		$tpl_vars = array();
		if($this->nasional){
			BASIC_GENERATOR::init()->head('lang mod', 'script', null, "Svincs.include('lang');");		
			$tpl_vars[$this->templates['form-action-bar-vars']['lingual-current']] = $GLOBALS['BASIC_LANG']->current();
		}
		if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && count($this->nasional) > 0 && $this->useJSLang){
			if(BASIC_LANGUAGE::init()->number() > 1){
				$linguals = array(); while($lang = BASIC_LANGUAGE::init()->listing()){
					$linguals[] = array(
						$this->templates['form-action-bar-vars']['linguals-text'] => $lang['text'],
						$this->templates['form-action-bar-vars']['linguals-key']  => $lang['code'],
						$this->templates['form-action-bar-vars']['linguals-flag'] => $lang['flag']
					);
				}
				$tpl_vars[$this->templates['form-action-bar-vars']['linguals']] = $linguals;
			}
		}
		return $tpl_vars;
	}
	/**
	 * @param array $type
	 */
	function buttonActionsBarOnly($type){
		
		$actions = array();
		$rules = array();
		$act = false;
		
		foreach ($this->actions as $k => $v){
			if($v[1] == $type){
				if(isset($v[3]) && $v[3]){
					if(preg_match("/^javascript:(.+)$/", $v[3],$ex)){
						$rules[] = array(
							$this->templates['form-action-bar-vars']['rules-text'] => $ex[1],
							$this->templates['form-action-bar-vars']['rules-key']  => $k,
							$this->templates['form-action-bar-vars']['rules-type'] => 'rule'
						);
					}else if(preg_match("/^message:(.+)$/", $v[3],$ex)){
						$rules[] = array(
							$this->templates['form-action-bar-vars']['rules-text'] => $ex[1],
							$this->templates['form-action-bar-vars']['rules-key']  => $k,
							$this->templates['form-action-bar-vars']['rules-type'] => 'message'
						);
					}else{
						$rules[] = array(
							$this->templates['form-action-bar-vars']['rules-text'] => $v[3],
							$this->templates['form-action-bar-vars']['rules-key']  => $k,
							$this->templates['form-action-bar-vars']['rules-type'] => 'confirm'
						);
					}
				}
				$actions[] = array(
					$this->templates['form-action-bar-vars']['actions-key'] => $k,
					$this->templates['form-action-bar-vars']['actions-pkey'] => $this->prefix.$k,
					$this->templates['form-action-bar-vars']['actions-text'] => $v[2],
					$this->templates['form-action-bar-vars']['actions-disable'] => true
				);
				$this->miss[] = $this->prefix.'cmd'.$k;
			}
			if($v[1] == ($type*-1)){
				$actions[] = array(
					$this->templates['form-action-bar-vars']['actions-key'] => $k,
					$this->templates['form-action-bar-vars']['actions-text'] => $v[2],
					$this->templates['form-action-bar-vars']['actions-disable'] => false
				);
			}
			if($v[1] == $type || $v[1] == ($type*-1)) $act = true;
		}
		return array(
			$this->templates['form-action-bar-vars']['actions'] => $actions,
			$this->templates['form-action-bar-vars']['rules']   => $rules,
			$this->templates['form-action-bar-vars']['prefix']  => $this->prefix,
			$this->templates['form-action-bar-vars']['cmd']  => $this->prefix.'cmd'
		);
	}
	/**
	 * Create manager bar for listing manager
	 *	<code>
	 * 		<b> $settings signature</b>
	 * 		array(
	 * 			'mark' => array(
	 * 				// style settings
	 * 			),
	 * 			'ordering' => true, //false // view order bar
	 * 			'actions' => array(
	 * 				'add' => 'disable', //hide|enable
 	 * 			)
	 *  	)
	 * 	</code>
	 * @param int $row
	 * @param $settings
	 * 
	 * @return void
	 */
	function rowActionsBar($row, $settings = array()){
		
		if(!isset($settings['mark'])) 	  $settings['mark'] = array();
		if(!isset($settings['ordering'])) $settings['ordering'] = true;
		if(!isset($settings['actions']))  $settings['actions'] = array();
		
		$id = $row[$this->field_id];
		$level = (isset($row['__level']) ? $row['__level'] : 0);
		$actions = array();
		$rules = array();
		$act = false;
		
		foreach ($this->actions as $k => $v){
			if(isset($settings['actions'][$k])){
				if($settings['actions'][$k] == 'hide') continue;
				if($settings['actions'][$k] == 'disable'){
					$v[1] = ($v[1]*-1);	
				}else if($settings['actions'][$k] == 'enable'){
					if($v[1] < 0) $v[1] = ($v[1]*-1);	
				}
			}
			if($v[1] == 2){
				if(isset($v[3]) && $v[3]){
					if(preg_match("/^javascript:(.+)$/",$v[3],$ex)){
						$rules[] = array(
							$this->templates['row-action-bar-vars']['rules-text'] => $ex[1],
							$this->templates['row-action-bar-vars']['rules-key']  => $k,
							$this->templates['row-action-bar-vars']['rules-type'] => 'rule'
						);
					}else if(preg_match("/^message:(.+)$/",$v[3],$ex)){
						$rules[] = array(
							$this->templates['row-action-bar-vars']['rules-text'] => $ex[1],
							$this->templates['row-action-bar-vars']['rules-key']  => $k,
							$this->templates['row-action-bar-vars']['rules-type'] => 'message'
						);	
					}else{
						$rules[] = array(
							$this->templates['row-action-bar-vars']['rules-text'] => $v[3],
							$this->templates['row-action-bar-vars']['rules-key'] => $k,
							$this->templates['row-action-bar-vars']['rules-type'] => 'confirm'
						);
					}
				}
				$actions[] = array(
					$this->templates['row-action-bar-vars']['actions-key'] => $k,
					$this->templates['row-action-bar-vars']['actions-pkey'] => $this->prefix.$k,
					$this->templates['row-action-bar-vars']['actions-text'] => $v[2],
					$this->templates['row-action-bar-vars']['actions-link'] => $this->createActionLink($k, $id),
					$this->templates['row-action-bar-vars']['actions-disable'] => true
				);
			}
			if($v[1] == -2){
				$actions[] = array(
					$this->templates['row-action-bar-vars']['actions-key'] => $k,
					$this->templates['row-action-bar-vars']['actions-text'] => $v[2],
					$this->templates['row-action-bar-vars']['actions-link'] => $this->createActionLink($k, $id),
					$this->templates['row-action-bar-vars']['actions-disable'] => false
				);
			}
			if($v[1] == 1 || $v[1] == -1) $act = true;
		}
		
		$order_bar = array();
		if($this->_ordering && $settings['ordering']){
			$order_bar = array(
				array(
					$this->templates['row-action-bar-vars']['orderbar-key'] => 'order_up',
					$this->templates['row-action-bar-vars']['orderbar-link'] => $this->createActionLink('order_up', $id)
				),
				array(
					$this->templates['row-action-bar-vars']['orderbar-key'] => 'order_down',
					$this->templates['row-action-bar-vars']['orderbar-link'] => $this->createActionLink('order_down', $id)
				)
			);
		}
	
		return array(
			$this->templates['row-action-bar-vars']['level'] => $level,
			$this->templates['row-action-bar-vars']['rownumber'] => $row['row_number'],
			$this->templates['row-action-bar-vars']['id'] => str_replace('-','_',$id),
			$this->templates['row-action-bar-vars']['actions'] => $actions,
			$this->templates['row-action-bar-vars']['rules'] => $rules,
			$this->templates['row-action-bar-vars']['orderbar'] => $order_bar,
			$this->templates['row-action-bar-vars']['function'] => $act ? " ".BASIC_GENERATOR::init()->convertAtrribute($settings['mark']) : '',
			$this->templates['row-action-bar-vars']['prefix'] => $this->prefix,
			$this->templates['row-action-bar-vars']['idcmd'] => $this->prefix.'id'
		);
	}

	/**
	 * Create action bar for listing manager
	 * @return html string
	 */
	function footerActionsBar(){
		$key = false;
		$actions = array();
		$rules = array();
			
		foreach ($this->actions as $k => $v){
			if($v[1] == 1){
				$actions[] = array(
					$this->templates['action-bar-vars']['actions-key'] => $k,
					$this->templates['action-bar-vars']['actions-pkey'] => $this->prefix.$k,
					$this->templates['action-bar-vars']['actions-text'] => $v[2],							
					$this->templates['action-bar-vars']['actions-link'] => $this->createActionLink($k),							
					$this->templates['action-bar-vars']['actions-disable'] => false
				);
				$key = true;
				if(isset($v[3]) && $v[3]){
					if(preg_match("/^javascript:(.+)$/",$v[3],$ex)){
						$rules[] = array(
							$this->templates['action-bar-vars']['rules-key'] => $k,
							$this->templates['action-bar-vars']['rules-text'] => $ex[1],
							$this->templates['action-bar-vars']['rules-type'] => 'rule'
						);
					}else if(preg_match("/^message:(.+)$/",$v[3],$ex)){
						$rules[] = array(
							$this->templates['action-bar-vars']['rules-key'] => $k,
							$this->templates['action-bar-vars']['rules-text'] => $ex[1],
							$this->templates['action-bar-vars']['rules-type'] => 'message'
						);
					}else{
						$rules[] = array(
							$this->templates['action-bar-vars']['rules-key'] => $k,
							$this->templates['action-bar-vars']['rules-text'] => $v[3],
							$this->templates['action-bar-vars']['rules-type'] => 'confirm'
						);
					}
				}
			}
			if($v[1] == -1){
				$key = true;
				$actions[] = array(
					$this->templates['action-bar-vars']['actions-key'] => '%'.$k,
					$this->templates['action-bar-vars']['actions-text'] => $v[2],
					$this->templates['action-bar-vars']['actions-link'] => $this->createActionLink($k),						
					$this->templates['action-bar-vars']['actions-disable'] => true
				);
			}
		}
		if(!$key) return array();
		
		return array(
			$this->templates['action-bar-vars']['actions'] => $actions,
			$this->templates['action-bar-vars']['rules'] => $rules,
			$this->templates['action-bar-vars']['prefix'] => $this->prefix,		
			$this->templates['action-bar-vars']['cmd'] => $this->prefix.'cmd'		
		);
	}

	function lang($name,$as=''){
	    return isset($this->nasional[$name]) ? $name."_".$GLOBALS['BASIC_LANG']->current() : $name;
	}

	/**
	 * Standart Action creator
	 *
	 * @param string $name
	 */
	function createActionLink($action, $id = '', $miss = array(), $script = ''){
		if(!$miss && $miss !== null) $miss = $this->system;
		
	    return BASIC_URL::init()->link(($script ? $script : BASIC::init()->scriptName()),
	    	($this->useSaveState ? BASIC_URL::init()->serialize($miss) : '').
	    	$this->prefix.'cmd='.$action.
	    	($id ? "&".$this->prefix."id=".$id : '')
	    );
	}
}

interface BasicFilterInterface{
	function prefix($text);
	function template($name);
	function button($text);
	function field($name, $context);
	function rangeField($name, $context);
	function init();
	function form($fprm_attr = '');
	function sql();
	/**
	 * @return hashmap[$field key ] => (
	 * 		string filter
	 * 		string data
	 * 		string type - valid values[match, start, middle, end] 
	 * )
	 */
	function buffer();
}
/**
 * Generate filter form and create sql criteria.
 * 
 * Usage : 
 * 		<code>
 * 			$filter = new BasicFilter('uid');
 * 
 * 				// set filter's fields
 * 				$filter->field('fname1', array(
 * 					'text' => 'filter text'
 * 					'formtype' => 'the valid componet support control type',
 * 					'filter' => ' AND `fname1` like "%{V}%" '
 * 					'attributes' => array(
 * 						...
 * 					)
 * 				));
 * 				$filter->field('fname2', array(
 * 					'text' => 'filter text'
 * 					'formtype' => 'the valid componet support control type',
 * 					'filter' => ' AND `fname2` in ({V}) '
 * 					'attributes' => array(
 * 						...
 * 					)
 * 				));
 * 				
 * 				// get values from the request
 * 				$filter->init()
 * 				
 * 				// get html code
 * 				$html = $filter->form();
 * 				// get sql code
 * 				$sql = $filter->sql();
 * 		</code>
 * 
 * @author Evgeni Baldziyski
 * @version 2.1.0 
 * @since 28.02.2007 update 15.12.2011
 */
class BasicFilter implements BasicFilterInterface{
	/**
	 * @var DysplayComponent
	 */
	protected $filter = null;
	protected $button = 'Filter';
	/**
	 * Constructor
	 *
	 * @param string $name
	 * @return object
	 */
	function __construct($prefix = '', $button = 'Filter', $template = ''){
		$this->filter = new DysplayComponent();
		$this->filter->prefix = $prefix;
		if($template) $this->filter->template_form = $template;
		
		$this->button = $button;
	}
	function prefix($text){
		$this->filter->prefix = $text;
	}
	function template($name){
		$this->filter->template_form = $name;
	}
	function button($text){
		$this->button = $text;
	}
	function field($name, $context){
		if(isset($context['lingual']) && $context['lingual']){
			if(class_exists('BASIC_LANGUAGE')){
				if(isset($context['filter'])) $context['filter'] = preg_replace('/[ ]`?'.$name.'`?[ ]/', ' `'.$name.'_'.BASIC_LANGUAGE::init()->current().'` ', $context['filter']);
			}
		}
		$context['real_name'] = $name;
		$this->filter->setField($this->filter->prefix.$name, $context);
	}
	function rangeField($name, $context){
		$this->field($name.'_from', $context);
		$this->field($name.'_to', $context);
	}
	/**
	 * check request and set values to system buffer.
	 * 
	 * @return boolen - if exist error retrn true
	 */
	public function init(){
		return $this->filter->test();
	}
	/**
	 * Create HTML filter's form.
	 * 
	 * @param hesh:array [$arr]
	 * @return string
	 */
	function form($arr = array()){
		BASIC_TEMPLATE2::init()->set('button', $this->button, $this->filter->template_form);
		return $this->filter->FORM_MANAGER($arr);
	}
	/**
	 * Create sql sql filter criteria.
	 * Check for special field's attributes
	 * 		"filter" - filter pattern in this format:
	 * 			for single url this sintax
	 * 				" (AND|OR) `name field` = '{V}'" rezultate : " (AND|OR) `name field` = 'url el value'"
	 *
	 * 			for multiple url element this sintax field1,field2,fieldN...
	 * 				' AND `code` in ({V})' rezultate : ' AND `code` in (5,43,20,...)'
	 * 					OR
	 * 				' AND `{V}` = 1' rezultate : 'AND `arr el 1` = 1 AND `arr el 2` = 1 .... AND `arr el N` = 1'
	 * 		
	 * 		"filterFunction" - fonction for generate filter's sql code. Use 2 case: 
	 * 			Array(class, 'the class's metthod'), Array('', 'function name') or String('the current's class method')
	 * 			
	 * 			filterFunction's signature - function (String|Integer(request value), String(the filter field's name))
	 * @return string
	 */
	function sql(){
		$tmp = '';
		
		foreach ($this->filter->fields as $v){
			if(!$v[0] || $v[0] == '#') continue;

//			$this->dataBuffer[$v[0]] = $GLOBALS['BASIC_URL']->request($v[0],
//				$this->cleanerDesition($v[3],true,$v[7]),$v[2]
//			);
			if($this->filter->getDataBuffer($v[0]) !== ''){
			    if(isset($v['filterFunction'])){
			        if(is_array($v['filterFunction']) && count($v['filterFunction']) == 2){
			        	
			            if($v['filterFunction'][0] == ''){
			            	// object model
			               	$tmp .= $v['filterFunction']($this->filter->getDataBuffer($v[0]),$v[0]);
			            }else{
			            	// function model
			                $tmp .= $v['filterFunction'][0]->$v['filterFunction'][1]($this->filter->getDataBuffer($v[0]),$v[0]); 
			            }
			        }else{
			            $tmp .= $this->$v['filterFunction']($this->filter->getDataBuffer($v[0]), $v[0]);
			        }
			    }else if(isset($v['filter'])){
				    $tmp .= $this->_strategy($this->filter->getDataBuffer($v[0]), $v['filter']);
			    }else{
			        throw new Exception('Can not find filter or filterFunction catcher.');
			    }
			}
		}
		return $tmp;
	}
	function buffer(){
		$tmp = array();
		foreach($this->filter->getBuffer() as $key => $val){
			if($val){
				$tmp[$this->filter->fields[$key]['real_name']] = array(
					'data' => $val,
					'type' => $this->typeMatch($key),
					'filter' => $this->filter->fields[$key]['filter']
				);
			}
		}
		return $tmp;
	}
	function typeMatch($name){
		if(!isset($this->filter->fields[$name])) return null;
		
		if(strpos($this->filter->fields[$name]['filter'], '=') !== false) return 'match';
			
		$spl = preg_split("/like/i", $this->filter->fields[$name]['filter']);
		if(isset($spl[1])){
			$spl[1] = preg_replace("/['\" ]+/", "", $spl[1]);
			
			if(preg_match("/^%[^%]+%$/", $spl[1])) return 'middle';
			if(preg_match("/^%/", $spl[1])) return 'start';
			if(preg_match("/%$/", $spl[1])) return 'end';
			
			return 'match';
		}
	}
	/**
	 * @param $post    - request value
	 * @param $filter  - filter declaration
	 */
	protected function _strategy($post, $filter){
		$tmp = '';
		if(is_array($post)){
			if(count($post) > 0){
				if(count($post) == 1 && $post[0] == '') return '';
				if(preg_match("/\{[^\}]+\}[ ]?=/", $filter)){
					foreach($post as $arr_v){
						if($arr_v != '') $tmp .= preg_replace("/(\{[^\}]+\})/", $arr_v, $filter);
					}
				}else{
					foreach($post as $arr_v){
						//if($arr_v != '') $filter = preg_replace("/(\{[^\}]+\})/",$arr_v.",$1",$filter);
						if($arr_v !== ''){
							$filter = preg_replace("/(['\"])?(\{[^\}]+\})(['\"])?/", "$1#_#_#$3,$1$2$3", $filter);
							$filter = preg_replace("/#_#_#/", $arr_v, $filter);
						}
					}
					$tmp .= preg_replace("/\,?['\"]?{[^\}]+\}['\"]?/", '', $filter);
				}
			}
		}else{
			if($post !== '') $tmp .= preg_replace("/\{[^\}]+\}/", $post, $filter);
		}
		return $tmp;
	}
}