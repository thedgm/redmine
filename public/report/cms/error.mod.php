<?php
/**
 * Менаджер на грешки.
 * 	Сервис за маниполиране на глобалните грешки и съобщения.
 * Целта е чрез него по всяко едно време както да бъдат добавяни така и извлсчани съобщенията на грешки и др. 
 * 
 * @author Evgeny Baldzisky
 * @version 0.5
 * @package BASIC.ERRORS
 */
class BASIC_ERROR{
	/**
	 * @var array
	 */
	private $arrErr = array();
	/**
	 * @var array
	 */
	private $_arrErr = array();
	/**
	 * @var int
	 */
	private $increment = -1;
	/**
	 * Колекция на типовете
	 * 	С тази колекция се определя обхвата на типа.
	 *  Сравняването става по кода на съобщението
	 * 
	 * @var array
	 */
	protected $typeRegister = array(
		'message' => array(0, 499),
		'warning' => array(500, 999),
		'fatal'   => array(1000)
	);
	/**
	 * Конфигориране и глобален достъп до сервиза
	 * За котейнер на инстанцията се запазва $GLOBALS['BASIC_ERROR']
	 * 
	 * @param array [$config]
	 * @return BASIC_ERROR
	 */
	static public function init($config = array()){
		if(!isset($GLOBALS['BASIC_ERROR'])){
			$GLOBALS['BASIC_ERROR'] = new BASIC_ERROR();
		}
		foreach ($config as $k => $v){
			$GLOBALS['BASIC_ERROR']->$k = $v;
		}
		return $GLOBALS['BASIC_ERROR'];
	}
	/**
	 * Добавяне на тип
	 *
	 * @param string $type - кад на типа
	 * @param int $from    - от каде му започва обхвата
	 * @param int $to      - до каде му свършва обхвата
	 */
	function setTypeRegister($type, $from, $to){
		$this->typeRegister[$type] = array($from, $to);
	}
	/**
	 * Премахване на тип
	 *
	 * @param string $type
	 */
	function unsetTypeRegister($type){
		if($type != 'message' && $type != 'warning' && $type != 'fatal' && isset($this->typeRegister[$type])) unset($this->typeRegister[$type]);
	}
	/**
	 * Запазване на съобщение.
	 * 	<code>
	 * 		BASIC_ERROR::init()->append(1,'My first message');
	 * 		BASIC_ERROR::init()->append(502,'My warning');
	 *	</code>
	 * 
	 * @param int $code
	 * @param string $message
	 */
	function append($code, $message){
		$this->arrErr[$this->checkForType($code)][] = array('code' => $code, 'message' => $message);
	}
	function setError($txt){
		$this->arrErr['fatal'][] = array('code' => 1000, 'message' => $txt);
	}
	function setWarning($txt){
		$this->arrErr['warning'][] = array('code' => 500, 'message' => $txt);
	}
	function setMessage($txt){
		$this->arrErr['warning'][] = array('code' => 0, 'message' => $txt);
	}
	/**
	 * @param int $number
	 * @return string
	 */
	function checkForType($number){
		if(is_numeric($number)){
			foreach ($this->typeRegister as $k => $v){
				if($number >= $v[0] && (!isset($v[1]) || (isset($v[1]) && $number <= $v[1]))){
					return $k;
				}
			}
		}
		return 'fatal';
	}
	/**
	 * Проверка за налични записи от даден тип[типове]
	 *	<code>
	 * 		if(!BASIC_ERROR::init()->exist(array('warning','fatal'))){
	 * 			trow new Message('Error buffer is empty!');
	 * 		}
	 * 	</code>
	 * @param mix(array,string) $type
	 * @return int
	 */
	function exist($type = ''){
		if($type){
			if(is_array($type)){
				$total = 0;
				foreach ($type as $v){
					if(isset($this->arrErr[$v])){
						$total += count($this->arrErr[$v]);
					}
				}
				return $total;
			}else{
				if(isset($this->arrErr[$type])){
					return count($this->arrErr[$type]);
				}
			}
		}else{
			$total = 0;
			foreach ($this->arrErr as $v){
				$total += count($v);
			}
			return $total;
		}
		return 0;
	}
	/**
	 * Обхождане на записите от дадения тип.
	 * <code>
	 *		BASIC_ERROR::init()->reset()
	 * 		while($err = BASIC_ERROR::init()->error('fatal')){
	 * 			print 'Code : '.$err['code'].' / Message : '.$err['message']."\n";
	 * 		}
	 * </code>
	 * 
	 * @return array:false (array(code,message))
	 */
	function error($type = ''){

		if($type){
			$this->increment++;
			if(isset($this->arrErr[$type][$this->increment])){
				return $this->arrErr[$type][$this->increment];
			}
		}else{
			if($this->increment == -1){
				foreach ($this->arrErr as $v){
					foreach ($v as $V) $this->_arrErr[] = $V;
				}
			}
			$this->increment++;
			if(isset($this->_arrErr[$this->increment])){
				return $this->_arrErr[$this->increment];
			}
			$this->_arrErr = array();
		}

		$this->increment = -1;
		return false;
	}
	function reset(){
		$this->_arrErr = array();
		$this->increment = -1;
	}
	/**
	 * Дастъп до буфера със съобщения
	 *	<code>
	 *		BASIC_ERROR::init()->append(101,'Message 1');
	 *		BASIC_ERROR::init()->append(101,'Message 2');
	 *		BASIC_ERROR::init()->append(512,'Message 3');
	 * 	
	 * 
	 * 		BASIC_ERROR::init()->getData() == Array(
	 *		    [message] => Array(
	 *		        [0] => Array(
	 *		            [code] => 101
	 *		            [message] => Message 1
	 *		        )
	 *				[1] => Array (
	 *		            [code] => 101
	 *		            [message] => Message 2
	 *		        )
	 * 		    )
	 *		    [warning] => Array (
	 *		        [0] => Array(
	 *		           [code] => 512
	 *		           [message] => Message 3
	 *		        )
	 *		    )
	 *		)
	 * </code>
	 * @return array
	 */
	function getData($type = ''){
		if($type){
			if(isset($this->arrErr[$type])){
				return $this->arrErr[$type];
			}else{
				return array();
			}
		}
		return $this->arrErr;
	}
	function setData($data){
		$this->arrErr = $data;
	}
	/**
	 * Изчистване на целия буфер или само пределени типове
	 * 
	 * @param string $type
	 */
	function clean($type = ''){
		if($type){
			if(isset($this->arrErr[$type])){
				$this->arrErr[$type] = array();
			}
		}else{
			$this->arrErr = array();
		}
	}
	// End class BASIC_ERROR
}
BASIC_ERROR::init();