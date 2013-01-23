<?php
/**
 * Менаджер за работа с бази данни.
 * 	Сервис който зарежда подходящия драйвър на оказания SQL сървър.
 * 
 * @author Evgeni Baldzisky
 * @version 1.8
 * @package BASIC.SQL
 */
class BASIC_SQL{

	protected $obj = null;
	protected $connect = 0;
	protected $buffer = '';
	protected $runtime = 0;
	
	public $type_res = 'ASSOC';//'BOTH';
	public $url = '';
	public $host = '';
	public $user = '';
	public $password = '';
	public $database = '';
	public $collation = '';
	public $server = '';
	public $log 	  = false;
	public $result = 'array';//'object';
	public $history = array();

	/**
	 * Конфигориране и глобален достъп до сервиза
	 * За котейнер на инстанцията се запазва $GLOBALS['BASIC_SQL']
	 * 
	 * @param array [$config]
	 * @return BASIC_SQL
	 */
	static public function init($config = array()){
		if(!isset($GLOBALS['BASIC_SQL'])){
		    $GLOBALS['BASIC_SQL'] = new BASIC_SQL();
		}
		foreach ($config as $k => $v){
		    if(isset($config['connect'])){
		        $GLOBALS['BASIC_SQL']->connect();
		        continue;
		    }
			$GLOBALS['BASIC_SQL']->$k = $v;
		}
		return $GLOBALS['BASIC_SQL'];
	}
	/**
	 * Кънектване към SQL сървъра. Данните за достъп се водават в URL формат.
	 * 	Тип_сървър://Име:Парола@Хост/Име_на_база
	 * 	<code>
	 * 		try{
	 *			BASIC_SQL::init()->connect("mysql://user_name:password@host_name/table_name",'utf8');
	 *		}catch (Exception $e){
	 *			die(BASIC_GENERATOR::init()->element('div',
	 * 				'style=color:#FF0000;font-size:12pt;',$e->getMessage()
	 * 			));
	 *		}
	 *	</code>
	 * 
	 * @param string [$url]
	 * @param string [$collation]
	 * @return BASIC_SQL
	 */
	function connect($url='',$collation = ''){
		$this->runtime = microtime(true);
		if(!$url && !$this->url){
			throw new Exception('Fatal error: NO EXIST SQL URL DATA!');
		}else if($url){
			$this->url = $url;
		}
		preg_match("/^(.+):\/\/([^:]+):?(.+)?@(.+)\/(.+)$/",$this->url,$arr_data);
		$this->server   = $arr_data[1];
		$this->user 	= $arr_data[2];
		$this->password = $arr_data[3];
		$this->host 	= $arr_data[4];
		$this->database = $arr_data[5];
		$this->collation = $collation;
		switch ($arr_data[1]){
			case "mysql":
				$this->obj = new _MySql(); break;
			case "mysqli":
				$this->obj = new _MySqli(); break;
			case "mssql":
				$this->obj = new _MsSql(); break;
			default:
				throw new Exception("(".$arr_data[1].") This type server is no suported !");
		}
		$this->connect = @$this->obj->connect($this->host,$this->user,$this->password,$this->database);
		if(!$this->connect){
			throw new Exception($this->obj->error());
		}else{
			if($this->obj->errno() == 1049){
				if($this->createDatabase($this->database,$this->collation)){
					$this->exec(" USE `".$this->database."` ");
				}
			}
		}
		if($this->collation != ''){
			$this->setName($this->collation);
		}
		return $this->collation;
	}
	/**
	 * Буфериране на заявката.
	 *	<code>
	 * 		BASIC_SQL::init()->append('SELECT * FROM `table_name` WHERE 1=1');
	 * 			// or
	 * 		BASIC_SQL::init()->append('SELECT * FROM `table_name` WHERE 1=1',true);
	 * 		BASIC_SQL::init()->append(' AND `test_2` = 1 ');
	 * 		BASIC_SQL::init()->append(' AND `test_3` = 231 ');
	 * 	</code>
	 * @param string $sql
	 * @param boolen [$clean]
	 */
	function append($sql,$clean=false){
		if($clean) $this->clean();

		$this->buffer .= $sql;
	}
	/**
	 * Cзчистване на буфера.
	 */
	function clean(){
		$this->buffer = '';
	}
	/**
	 * Изпълнение на не резултатви заявки.
	 *	<code>
	 * 		if(BASIC_SQL::init()->exec(" Inset into `table_name` (`column_1`,`column_2`) values('1','2') ")){
	 * 			die('Изпълнението е успешно');
	 * 		}
	 * 	</code>
	 * @param string [$query]
	 * @return int
	 */
	function exec($query = ''){

		if(!$this->obj) throw new Exception('No connection!');
		
		if($query) $this->append($query,true);
		if(!$this->buffer) return false;

		$runtime = $this->runTimeQuery();
		$exectime_start = microtime(true);
		
        if(trim($this->buffer)){
		    if(!$tmp = @$this->obj->query($this->buffer)){
			      $GLOBALS['BASIC_ERROR']->append($this->obj->errno(),$this->obj->error());
		    }
			 
			 if($this->log) $this->setHistory($runtime,(microtime(true)-$exectime_start),$this->buffer);
			 return $tmp;
        }
		return null;
	}
	/**
	 * Изпълнение на заявки които връщат резултат.
	 *	<code>
	 * 		$rdr = BASIC_SQL::init()->read_exec(" 
	 * 			SELECT 
	 * 				`column_1` as `column_11`,
	 * 				`column_2
	 * 			FROM 
	 * 				`table_name`
	 * 			WHERE 1=1
	 * 				AND `column_1` IN ('12','14','70')
	 * 		");
	 * 		while($rdr->read()){
	 * 			BASIC_ERROR->append(1,$rdr->item('column_11'));
	 * 			BASIC_ERROR->append(1001,$rdr->item('column_2'));
	 * 		}
	 * 	</code>
	 * @param string [$query]
	 * @param boolen [$returnArray]
	 * @return SqlReader
	 */
	function read_exec($query = '',$returnArray = false){
		if(!$this->obj) throw new Exception('No connection!');
		
		if($query){
			$this->append($query,true);
		}
		$runtime = $this->runTimeQuery();
		$exectime_start = microtime(true);
        
		$read = $this->obj->query($this->buffer);
		if(!$read){
			$GLOBALS['BASIC_ERROR']->append($this->obj->errno(),$this->obj->error());
		}
//		if($arr){
//			print_r(@$this->obj->fetch_array($read,$this->type_res));die();
//		}
		if($this->log){
			$this->setHistory($runtime,(microtime(true)-$exectime_start),$this->buffer);
		}
		if($returnArray){
			return @$this->obj->fetch_array($read,$this->type_res);
		}
		return (new SqlReader($read,$this->obj,$this->type_res));
	}
	/**
	 * Изпълнение на резултатни заявки като върнантия резултат е форматиран. 
	 *	<code>
	 * 		function formater($array_data){
	 * 			foreach($array_data as $k => $v){
	 * 				if($k == 8){
	 * 					unset($array_data[$k]);
	 * 				}else{
	 * 					$array_data[$k] = '<div>'.$v.'</div>';
	 *				}
	 * 			}
	 * 			return $array_data;
	 * 		}
	 * 		
	 * 		$formated_array = BASIC_SQL::init()->read_feach(" 
	 * 			SELECT 
	 * 				`column_1` as `column_11`,
	 * 				`column_2
	 * 			FROM 
	 * 				`table_name`
	 * 			WHERE 1=1
	 * 				AND `column_1` IN ('12','14','70')
	 * 		","formater");
	 * 
	 * 		foreach($formated_array as $record){
	 * 			printr($record)
	 * 		}
	 * 	</code>
	 * @param string [$query]
	 * @param string/array [$render]
	 * @return array
	 */
	function read_feach($query='',$render=''){
	    $tmp = array();
	    $rdr = $this->read_exec($query);
	    while($rdr->read()){
	        if($render){
	            if(is_array($render)){
	                $tmp[] = $render[0]->$render[1]($rdr->getItems());
	            }else{
	                $tmp[] = $render($rdr->getItems());
	            }
	        }else{
	           $tmp[] = $rdr->getItems();
	        }
	    }
	    return $tmp;
	}
	/**
	 * Изпълтение на резултатна заявки директно в цикъл.
	 * 	<code>
	 * 		while($record = BASIC_SQL::init()->short_exec("
	 * 			SELECT 
	 * 				`column_1` as `column_11`,
	 * 				`column_2
	 * 			FROM 
	 * 				`table_name`
	 * 			WHERE 1=1
	 * 				AND `column_1` IN ('12','14','70')
	 * 		")){
	 * 			print_r($record);
	 * 		}
	 * 	</code>
	 *
	 * @param string [$query] - SQL заявка
	 * @param int [$num] - ниво на вложеност когато има извикване на базата на предишно такова.
	 * @return array/false
	 */
	
	protected $ex = array();
	function short_exec($query='',$num=0){
		if(!$this->obj) throw new Exception('No connection!');
		
		if(!isset($this->ex[$num])){

			if($query){
				$this->ex[$num] = $this->read_exec($query);
			}else{
				$this->ex[$num] = $this->read_exec();
			}
		}

		$rdr = $this->ex[$num]->read();
		if($rdr){
			return $rdr;
		}else{
			unset($this->ex[$num]);
			return false;
		}
	}

	/**
	 * Изпълнение на повече от една заявка.
	 * Внимание: Текстовия режим ни работи добре затова трябва да се внимава с него.
	 * <code>
	 * 		BASIC_SQL::init()->multi_exec(array(
	 * 			" INSERT INTO `table_name` ('value 1','value 2') ",
	 * 			" INSERT INTO `table_name` ('value 3','value 4') ",
	 * 			" INSERT INTO `table_name` ('value 5','value 6') "
	 * 		));
	 * </code>
	 *
	 * @param array:string $query
	 * @return array:true
	 */
	function multi_exec($query){
		if(!$this->obj) throw new Exception('No connection!');
		
		$style = 'array';

		if(!is_array($query)){

			$style = 'string';

			preg_match_all('/[\'"][^\']+[\'"]/',$query,$reg);
			foreach ($reg as $v){
				$query = str_replace($v,str_replace(";",'%3B',$v),$query);
			}
			$query = explode(";",$query);
		}

		$obj_r = null;
		foreach($query as $v){

			$runtime = $this->runTimeQuery();
			$exectime_start = microtime(true);

			$this->append(($style == 'string' ? str_replace(";",'%3B',$v) : $v),true);

//			$read = $this->obj->query($this->buffer);

			$read = @$this->obj->query($this->buffer)
						or $GLOBALS['BASIC_ERROR']->append($this->obj->errno(),$this->obj->error());

			$obj_r = new SqlReader($read,$this->obj,$this->type_res);

			if($this->log){
				$this->setHistory($runtime,(microtime(true)-$exectime_start),$this->buffer);
			}
		}

		if($GLOBALS['BASIC_ERROR']->exist()){
			return false;
		}
		if($obj_r != null) return $obj_r;

		return true;
	}

	/**
	 * Инструмент за листване на таблиците на работната или оказаната база.
	 * 
	 * @param string [$base]
	 * @return SqlReader
	 */
	function showTable($base = ''){
		if(!$base) $base = $this->database;

		$read = $this->obj->showTable($base);
		$r = new SqlReader($read,$this->obj);
		return $r;
	}

	/**
	 * Инструмент за извличане на структората на таблица от работната или оказаната база.
	 * 
	 * @param string $table
	 * @param string [$base]
	 * @return SqlReader
	 */
	function showFields($table,$base = ''){
		if(!$base) $base = $this->database;

		$read = $this->obj->showFields($base,$table);

		$read = new SqlReader($read,$this->obj,$this->type_res); return $read;
	}
	/**
	 * Извличане на буфериранята заявка.
	 *
	 * @return string
	 */
	function getSql(){
		return $this->buffer;
	}
	/**
	 * Извличане на последното "id" след "insert" заявка
	 *
	 * @return int
	 */
	function getLastId(){
		return $this->obj->lastId();
	}
	/**
	 * Задаване на колация.
	 *
	 * @param string $charset
	 * @return boolen
	 */
	function setName($charset){
		return $this->exec($this->obj->setName($charset));
	}
	/**
	 * Инструмент за създаване на база.
	 *
	 * @param string $name
	 * @param string $charset
	 * @return boolen
	 */
	function createDatabase($name,$charset){
		return $this->exec($this->obj->createDatabase($name,$charset));
	}

	/**
	 * Инструмент за създаване на дъмп на дадена база.
	 * Не работи за сега.
	 * 
	 * @return string
	 */
	function createDumpTable($table,$base = ''){
	    if(!$base) $base = $this->database;
	    
	    return '';//$this->obj->createDumpTable($table,$base);
	}
	/**
	 * Инструмент за създаване на таблица в работната база.
	 * 
	 * @param string $idname [име на PRIMARY KEY колоната]
	 * @param string $name [име на таблицата]
	 * @param steing $data [структара на таблицата]
	 * @return boolen
	 */
	function createTable($idname,$name,$data = ''){
		return $this->exec($this->obj->createTable($idname,$name,$data));
	}

	function createColumn($tbl,$data){
		return $this->exec($this->obj->createColumn($tbl,$data));
	}
	function drobColumn($tbl, $name){
		return $this->exec($this->obj->drobColumn($tbl, $name));
	}
	function createForeignKey($tblChild,$fieldChild,$tblParent,$fieldParent){
		return $this->exec($this->obj->createForeignKey($tblChild,$fieldChild,$tblParent,$fieldParent));
	}
	function getLimit($query,$from,$to,$SortField,$SortDirection='ASC'){
		return $this->obj->limit($query,$from,$to,$SortField,$SortDirection);
	}

	function getCurDate(){
		return $this->obj->getCurDate();
	}
	/**
	 * @return  int
	 */
	protected function runTimeQuery(){
		return (microtime(true)-$this->runtime);
	}
	/**
	 * @param int $runtime
	 * @param int $exectime
	 * @param string $query
	 */
	protected function setHistory($runtime,$exectime,$query){
		$this->history[] = array(
			"runtime"=>$runtime,
			"execution"=>$exectime,
			"query"=>$query
		);
	}
	/**
	 * Read Info Query for specific format dispaly
	 *
	 * @return hesh array
	 */
	function getArrHistory(){
		return $this->history;
	}
	/**
	 * Помощен инструмент за емулиране на резултат от заявка който има 0 редове. 
	 *
	 * @return SqlReader
	 */
	function getEmptyReader(){
	    return new SqlReader(false,$this->obj);
	}
	// End Class BASIC_SQL
}
BASIC_SQL::init();

/**
 * Клас които се връща от read_exec, който съдържа резултата от базата. 
 * 
 * @author Evgeni Baldzisky
 * @version 1.2
 * @package BASIC.SQL
 */
class SqlReader{

	var $resource = '';
	var $obj = 0;
	var $SQLR_items = array();
	var $type_res = '';

	/**
	 *  Constructor metod
	 *
	 * @param sesource $res - резултатен хендлар
	 * @param BASIC_SQL $sqlobj
	 * @param string $type_res - тип на връщания масив
	 */
	function SqlReader($res,$sqlobj,$type_res = 'BOTH'){
		$this->resource = $res;
		$this->obj = $sqlobj;
		$this->type_res = $type_res;
	}
	/**
	 * Извличане на текущия ред от резултата.
	 *
	 * @return array
	 */
	function getItems(){
		return $this->SQLR_items;
	}
	/**
	 * Заместване на текущия ред от резултата.
	 *
	 * @param ьииьщ $arr
	 */
	function setItems($arr){
		$this->SQLR_items = $arr;
	}
	/**
	 * Заместване на елемент от текущия ред на резултата.
	 *
	 * @param string $name
	 * @param mix $value
	 */
	function setItem($name,$value){
		$this->SQLR_items[$name] = $value;
	}

	/**
	 * Прочитане и буфериране на следващия ред на резултата.
	 *	<code>
	 * 		function formater($array_data){
	 * 			foreach($array_data as $k => $v){
	 * 				if($k == 8){
	 * 					unset($array_data[$k]);
	 * 				}else{
	 * 					$array_data[$k] = '<div>'.$v.'</div>';
	 *				}
	 * 			}
	 * 			return $array_data;
	 * 		}
	 * 		$rdr = BASIC_SQL::init()->read_exec("
	 * 			SELECT * FROM `table_name` WHERE 1=1
	 * 		");
	 * 		while($rdr->read('formater')){
	 * 			print $rdr->item('column_1');
	 * 		}
	 * 	</code>
	 * @param array [$cleanCall] - почистващ механизъм
	 * @return array
	 */
	function read($cleanCall = null){
		$tmp = $this->resource;
		if ($tmp){
		    $this->SQLR_items = @$this->obj->fetch_array($this->resource,$this->type_res);
		    if($cleanCall != null && $this->SQLR_items){
		    	if(is_array($cleanCall)){
		    		$class = &$cleanCall[0];
		    		$metod = $cleanCall[1];
		    		$this->SQLR_items = $class->$metod($this->SQLR_items);
		    	}else{
		    		$this->SQLR_items = $cleanCall($this->SQLR_items);
		    	}
		    }
		    if($tmp = $this->SQLR_items){
			    foreach($tmp as $k => $v){
			    	if(!is_numeric($k)) $this->$k = $v;
			    }
		    }
		}
		return $tmp;
	}
	/**
	 * Извличане на елемент от текущия ред на резултата.
	 *	<code>
	 * 		function formater($string){
	 * 			if($string == 'test 1'){
	 * 				$string = 'test';
	 * 			}
	 * 			return $string;
	 * 		}
	 * 		$rdr = BASIC_SQL::init()->read_exec("
	 * 			SELECT * FROM `table_name` WHERE 1=1
	 * 		");
	 * 		while($rdr->read('formater')){
	 * 			print $rdr->item('column_1','formater');
	 * 		}
	 * 	</code>
	 * @param string $name
	 * @param string/array [$callback]
	 * @return string
	 */
	function field($name,$callback = null){
		$tmp = '';
		if(isset($this->SQLR_items[$name])){
			$tmp = $this->SQLR_items[$name];
		}else{
			$tmp = '';
		}
		if($callback != null){
			if(is_array($callback)){
				$class = &$callback[0];
				$metod = $callback[1];
				return $class->$metod($tmp);
			}else{
				return $callback($tmp);
			}
		}else{
			return $tmp;
		}
	}
	/**
	 * Шоркът на "field"
	 *
	 * @param string $name
	 * @param string/array [$callback]
	 * @return string
	 */
	function item($name,$colback = null){
		return $this->field($name,$colback);
	}
	/**
	 * Проверка за наличност на даден елемент в текущия ред на резултата.
	 *
	 * @param string $name
	 * @return boolen
	 */
	function test($name){
		return isset($this->SQLR_items[$name]);
	}
	/**
	 * Извличане на броя редове в резултата.
	 *
	 * @return int
	 */
	function num_rows(){
		if($this->resource){
			return @$this->obj->num_rows($this->resource);
		}
		return 0;
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
		
		while($this->read()){
			$before[$this->item($id)] = $this->item($text); 
		}
		return $before;
	}
	/**
	 * Извличане на цялата информация във формата на масив.
	 * @return array
	 */
	function getArrayData(){
		$tmp = array();
		while($this->read()){
			$tmp[] = $this->getItems();
		}
		return $tmp;
	}
	// End class sqlReader
}

/**
 * Help modul class for create dump data of DB Tables 
 *
 * @author Evgeni Baldzisky
 * @access public
 * @package BASIC.SQL
 * @since 27.09.2008
 */
class BASIC_DumpSql {

    var $table;
    var $buffer;
    var $buffering = true;
    
	/**
	 * Clas constructor
	 *
	 * @param string $table
	 * @param string $buffering
	 */
	function BASIC_DumpSql($table,$buffering = true) {
        $this->table = $table;
        $this->buffering = $buffering;
	}
	
	/**
	 * Create structore dump
	 *
	 * @param boolen [$drop_if_exist]
	 * @return string
	 */
	function getStructore($drop_if_exist = true){
        $tmp = "#\n".
               "# Table structure for table ".$this->table."\n".
               "#\n";
        if($drop_if_exist){
             $tmp .= "DROP TABLE IF EXISTS `".$this->table."`;;\n";
        }
        $rdr = $GLOBALS['BASIC_SQL']->showFields($this->table);
        
        $id = '';
        $tmp_s = "\n";
        while($rdr->read()){
            if($rdr->item('Key') == 'PRI'){
                $id = $rdr->item('Field');
            }else{
                $tmp_s .= " `".$rdr->item('Field')."` ".$rdr->item('Type')." ".($rdr->item('Null') == 'NO' ? 'NOT NULL' : '')." ".(!$rdr->item('Default') ? '' : "default '".$rdr->item('Default')."'").",\n";
            }
        }
        //$tmp_s = substr($tmp_s,0,-2)."\n";
        $tmp_s .= " PRIMARY KEY  (`".$id."`)\n";
        
        $tmp .= $GLOBALS['BASIC_SQL']->createDumpTable($id,$this->table,$tmp_s);
        $tmp = substr($tmp,0,-1).";;";
        if($this->buffering){
            $this->buffer .= $tmp."\n";
        }
        return $tmp."\n";
	}
	
	/**
	 * Create data dump
	 *
	 * @return string
	 */
	function getData(){
	    $tmp = "#\n".
               "# Dumping data for table ".$this->table."\n".
               "#\n";
	    
	    $rdr = $GLOBALS['BASIC_SQL']->read_exec(" select * from `".$this->table."` ");
	    while($rdr->read()){
	        $tmp_s = "INSERT INTO `".$this->table."` VALUES (";
	        foreach ($rdr->getItems() as $k => $v){
	            $tmp_s .= "'".str_replace("'","\\'",$v)."',";
	        }
	        $tmp .= substr($tmp_s,0,-1).");;\n";
	    }
        if($this->buffering){
            $this->buffer .= $tmp."\n";
        }
        return $tmp."\n";
	}
	
	/**
	 * Create structore and data dump
	 *
	 * @param boolen [$drop_if_exist]
	 * @return string
	 */
	function getDump($drop_if_exist = true){
	    return (
            $this->getStructore().
            $this->getData()
	    );
	}
	// End Class DumpSql
}

/**
 * MYSQL server driver
 *
 * @version 0.1 [02-12-2006]
 * @package BASIC.SBND.SQL
 */
class _MySql{

	var $connect = 0;

	function connect($host,$user,$pwd,$db){
		$this->connect = mysql_connect($host,$user,$pwd);
		if($this->connect){
			 mysql_select_db($db,$this->connect);
		}
		return $this->connect;
	}
	function query($sql){
		return mysql_query($sql,$this->connect);
	}
	function fetch_array($res,$type='BOTH'){
		$type_res = array('BOTH'=>MYSQL_BOTH,'ASSOC'=>MYSQL_ASSOC,'NUM'=>MYSQL_NUM);
		return mysql_fetch_array($res,isset($type_res[$type]) ? $type_res[$type] : $type_res['BOTH']);
	}
	function num_rows($res){
		return mysql_num_rows($res);
	}
	function lastId(){
		return mysql_insert_id($this->connect);
	}
	function error(){
		return mysql_error();
	}
	function errno(){
		return mysql_errno();
	}
	function close(){
		return mysql_close($this->connect);
	}
	function showTable($base){
		return mysql_list_tables($base,$this->connect);
	}
	function showFields($base,$table){
		return mysql_query(" SHOW COLUMNS FROM `".$base."`.`".$table."` ",$this->connect);
	}
	function setName($charset){
		$query = " SET NAMES ".$charset;
		return $query;
	}
	function createDatabase($name,$charset){
		$query = "CREATE DATABASE `".$name."`";
		if($charset != ''){
			$query .= " default character set ".$charset;
		}
		return $query;
	}
	/**
	 * @copyright
	 *   fix [06-11-2007] fixed special error ";" ,cleaned if.
	 */
	function createTable($idname,$name,$data,$type = ''){
		$query = "CREATE TABLE `".$name."`(\n";
		if($idname){
			$query .= " `".$idname."` int(11) NOT NULL auto_increment,";
		}
		$query .= ereg_replace(',[ ]*$','',$data);
		if($idname){
			$query .= ",PRIMARY KEY  (`".$idname."`)\n";
		}
		$query .= ")";
		
		if($type){
			$query .= " ENGINE=".$type."";
		}
		$query .= "\n";

		return $query;
	}
	function createColumn($tbl,$data){
		return "ALTER TABLE `".$tbl."` ADD ".$data." ";
	}
	function drobColumn($tbl, $name){
		return "ALTER TABLE `".$tbl."` DROP COLUMN `".$name."` "; 
	}

	function createForeignKey($tblChild,$fieldChild,$tblParent,$fieldParent){
		return "ALTER TABLE `".$tblChild."`
			ADD CONSTRAINT `".$tblChild.'_'.$tblParent."` FOREIGN KEY (`".$fieldChild."`)
			REFERENCES `".$tblParent."` (`".$fieldParent."`) ON DELETE CASCADE ";
	}

	function limit($query,$from,$to,$SortField,$sortdirection){
		$query = $query." limit ".$from.",".$to;
		//die('test'.$query);
		return $query;
	}
	function getCurDate(){
		return "now()";
	}
	// End class _MySql
}

/**
 * MYSQL server driver
 *
 * @author Evgeni Baldzisky
 * @version 0.1 [22-01-2007]
 * @package BASIC.SBND.SQL
 */
class _MySqli{

	var $connect = 0;

	function connect($host,$user,$pwd,$db){
		$this->connect = mysqli_connect($host,$user,$pwd,$db);
		return $this->connect;
	}
	function query($sql){
		return mysqli_query($this->connect,$sql);
	}
	function fetch_array($res,$type='BOTH'){
		$type_res = array('BOTH'=>MYSQLI_BOTH,'ASSOC'=>MYSQLI_ASSOC,'NUM'=>MYSQLI_NUM);
		return mysqli_fetch_array($res,isset($type_res[$type]) ? $type_res[$type] : $type_res['BOTH']);
	}
	function num_rows($res){
		return mysqli_num_rows($res);
	}
	function lastId(){
		return mysqli_insert_id($this->connect);
	}
	function error(){
		return mysqli_error($this->connect);
	}
	function errno(){
		return mysqli_errno($this->connect);
	}
	function close(){
		return mysqli_close($this->connect);
	}
	function showTable($base){
		return mysqli_query($this->connect," SHOW tables FROM `".$base."` ");
	}
	function showFields($base,$table){
		return mysqli_query($this->connect," SHOW COLUMNS FROM `".$base."`.`".$table."` ");
	}
	function createDatabase($name,$charset){
		$query = "CREATE DATABASE `".$name."`";
		if($charset != ''){
			$query .= " default character set ".$charset;
		}
		return $query;
	}
	function createTable($idname,$name,$data,$type=''){
		$query = "CREATE TABLE `".$name."`(\n";
		if($idname){
			$query .= " `".$idname."` int(11) NOT NULL auto_increment,";
		}
		$query .= $data;
		$query .= ")";
		if($type){
			$query .= " ENGINE=".$type."";
		}
		$query .= "\n";

		return $query;
	}
	function createColumn($tbl,$data){
		return "ALTER TABLE `".$tbl."` ADD ".$data." ";
	}
	function createForeignKey($tblChild,$fieldChild,$tblParent,$fieldParent){
		die("No supported yet");
	}
	function limit($query,$from,$to,$SortField,$sortdirection){
		$query = $query." limit ".$from.",".$to;
		//die('test'.$query);
		return $query;
	}
	function getCurDate(){
		return "now()";
	}
	// End class _MySqli
}

/**
 * Microsoft SQL Server driver
 *
 * @author Evgeny Baldzisky
 * @version 0.1 alfa [30-04-2007]
 * @package BASIC.SBND.SQL
 */
class _MsSql{

	var $connect = 0;
	var $last_m_error = '';

	function connect($host,$user,$pwd,$db){
		$this->connect = mssql_connect($host,$user,$pwd);
		if($this->connect){
			 mssql_select_db($db,$this->connect);
		}
		return $this->connect;
	}
	function query($sql){
		$sql = preg_replace('/`([^`]+)`/','[$1]',$sql);
		//print($sql."< /br>");
		$res = @mssql_query($sql,$this->connect) or $this->last_m_error = mssql_get_last_message();

		return $res;
	}
	function fetch_array($res,$type='BOTH'){
		$type_res = array('BOTH'=>MYSQLI_BOTH,'ASSOC'=>MYSQLI_ASSOC,'NUM'=>MYSQLI_NUM);
		return mssql_fetch_array($res,isset($type_res[$type]) ? $type_res[$type] : $type_res['BOTH']);
	}
	function num_rows($res){
		return mssql_num_rows($res);
	}
	function lastId(){

	}
	function error(){
		return $this->last_m_error;
	}
	function errno(){
		$result = mssql_query("select @@ERROR as [error]",$this->connect);
		$err = '';
		while($row = mssql_fetch_array($result)){
			$err = $row['error'];
			break;
		}
		if($err == 208) $err = 1146; // table not existing
		if($err == 207) $err = 1054; // field not existing
		if($err == 911) $err = 1049;

		return $err;
	}
	function close(){
		return mssql_close($this->connect);
	}
	function showTable($base){
		return mssql_query("sp_help",$this->connect);
	}
	function showFields($base,$table){
		return mssql_query("sp_columns ".$table." ",$this->connect);
	}
	function setName($charset){
		return '';
	}
	function createDatabase($name,$charset){
		return " CREATE DATABASE [".$name."] ";
	}
	function createTable($idname,$name,$data = ''){
		$query = "CREATE TABLE ".$name."(\n";
		$query .= " ".$idname." int identity(1,1) NOT NULL,\n";

		$query .= $data;
		$query .= ");";

		return $query;
	}
	function createColumn($tbl,$data){
		die('No support yet');
	}
	function createForeignKey($tblChild,$fieldChild,$tblParent,$fieldParent){
		die("No supported yet");
	}
	/**
	 * Enter description here...
	 *
	 * @param string $query
	 * @param unknown_type $SortField
	 * @param unknown_type $SortDirection
	 * @return string
	 */
	function limit($query,$from,$to,$SortField,$SortDirection){
		$query = str_replace("select",'SELECT',$query);
		$query = str_replace("from",'FROM',$query);

		$order = '';
		preg_match('/(.+)(order.+)/',$query,$match);
		if(isset($match[1])){
			$query = $match[1];
			$order = $match[2];
		}

		$query = str_replace("SELECT", "SELECT TOP ".$from,$query);
		$query = "SELECT * FROM (
			SELECT TOP ($to) * FROM (
				$query ORDER BY [$SortField] ".($SortDirection=="ASC"?"ASC":"DESC")."
			) as tbl1 ORDER BY [$SortField] ".($SortDirection!="ASC"?"ASC":"DESC")."
		) as tbl2 ".$order;//ORDER BY $SortField ".($SortDirection=="ASC"?"ASC":"DESC");

		return $query;
	}
	function getCurTime(){
		return 'getdate()';
	}
	// End class _MsSql
}

/**
 * Oracle server driver
 *
 * @author ...
 * @version 0.1 [..-..-....]
 * @package BASIC.SBND.SQL
 */
class _Oracle{

	var $connect = 0;

	function connect($host,$user,$pwd,$db){}
	function query($sql){}
	function fetch_array($res,$type='BOTH'){}
	function num_rows($res){}
	function lastId(){}
	function error(){}
	function errno(){}
	function close(){}
	function showTable($base){}
	function showFields($base,$table){}

	// End class
}

/**
 * mSQL server driver
 *
 * @author ...
 * @version 0.1 [..-..-....]
 * @package BASIC.SBND.SQL
 */
class _mSql{

	var $connect = 0;

	function connect($host,$user,$pwd,$db){}
	function query($sql){}
	function fetch_array($res,$type='BOTH'){}
	function num_rows($res){}
	function lastId(){}
	function error(){}
	function errno(){}
	function close(){}
	function showTable($base){}
	function showFields($base,$table){}

	// End class
}