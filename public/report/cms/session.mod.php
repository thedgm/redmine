<?php
/**
 * @var BASIC_SESSION
 */
$GLOBALS['BASIC_SESS'] = null;
/**
 * Сервис обслузващ сесиините променливи.
 * Чрез обвивката на този сервис се контролира сесията на по високо ниво отколкото е стандартната на PHP.
 *
 * @author Evgeni Baldzisky
 * @version 0.7 
 * @since 22-01-2007
 * @package BASIC.SESSION
 */
class BASIC_SESSION{
	/**
	 * Време на живот но сесията.
	 * @var int
	 */
	public $TimeSess = 1800;
	/**
	 * метод на предаване на сесията [[cookie] | url]
	 * @var string
	 */
	public $motod = 'cookie';
	/**
	 * Име на идентификационната променлива.
	 * @var string
	 */
	public $nameID = 'passHesh';
	/**
	 * Място на което да се записват сесийните променливи [db | [disk]]
	 * @var string
	 */
	public $container = "disk";
	/**
	 * @var string
	 */
	public $passHesh = '';
	/**
	 * @var boolen
	 */
	private $starting = false;
	/**
	 * @var array
	 */
	private $arrVar = array();
	/**
	 * @var SessionDriversDb|SessionDriversDisk
	 */
	private $modObj = null;

	function BASIC_SESSION(){}

	/**
	 * Сингълтон реализация за достъпване на инстанцията на сервиза както и за 
	 * настроиванито му.$GLOBALS['BASIC_SESS'] се запазва за инстанцията на сервиза.
	 *
	 * @param array $config
	 * @return BASIC_SESSION
	 */
	static public function init($config=array()){
		if(!isset($GLOBALS['BASIC_SESS'])){
			$GLOBALS['BASIC_SESS'] = new BASIC_SESSION();
		}
		foreach ($config as $k => $v){
		    if(isset($config['started'])){
		        $GLOBALS['BASIC_SESS']->start();
		    }else{
				$GLOBALS['BASIC_SESS']->$k = $v;
		    }
		}
		return $GLOBALS['BASIC_SESS'];
	}

	/**
	 * Стартиране на сесиината потдръжка.
	 *	<code>
	 * 		BASIC::init()->imported('session.mod');
	 *		BASIC_SESSION::init(array(
	 *			'TimeSess' => 7200
	 *		))->start();
	 * 	</code>
	 * 
	 * @return boolen
	 */
	function start(){
		if($this->starting){
			return false;
		}else{
			$this->starting = true;
		}

		switch ($this->container){
			case 'db':
				$this->modObj = new SessionDriversDb($this);
				break;
			case 'disk':
				$this->modObj = new SessionDriversDisk($this);
				break;
		}

		$this->send();
		register_shutdown_function(array($this, "write"));
		return true;
	}
	/**
	 * Pзаписване на сесиините проенливи.
	 */
	function write(){
		$this->modObj->write();
	}
	/**
	 * Унищожаванен на сесията.
	 * Внимание: когато контейнера е "db" променливите престават да са налични.
	 */
	function distroy(){
		$this->modObj->distroy();
	}
	/**
	 * Изпращани на идентификационната променлива.
	 */
	function send(){
		if($this->motod == "cookie"){
			// clean global cookie :(
			//setcookie($this->nameID,'',time() + $this->TimeSess,'/');
			// add local cookie :)
			//$protocol = explode('/',$_SERVER['SERVER_PROTOCOL']);
			//setcookie('s_V','SVINCS',null,'/','svinc.postfolio.org');
		}else if($this->motod == "url"){
			$_GET[$this->getName()] = $this->getID();
		}
	}

	/**
	 * Извличане на стоиноста на променлива.
	 *
	 * @param string $name
	 * @return string
	 */
	function get($name){
		if($name && isset($_SESSION[$name]))
			return $_SESSION[$name];
		return '';
	}

	/**
	 * Променяне на стойноста на променлива.
	 * Ако не съществува ще се създаде.
	 *
	 * @param string $name
	 * @param string|int $value
	 */
	function set($name,$value){
		if($name)  $_SESSION[$name] = $value;
	}
	/**
	 * Премахване на променлива.
	 *
	 * @param string $name
	 */
	function un($name){
		if($name && isset($_SESSION[$name]))
			unset($_SESSION[$name]);
	}
	/**
	 * Избличанен на цялата сесия.
	 *
	 * @return unknown
	 */
	function all(){
		return $_SESSION;
	}
	/**
	 * Достъп до идентификатора на сесията.
	 *
	 * @return string
	 */
	function getID(){
		return $this->passHesh;
	}
	/**
	 * Достъп до името на сесията.
	 *
	 * @return unknown
	 */
	function getName(){
		return $this->nameID;
	}
	/**
	 * Създаванен на URL за добавяне във линк.
	 *
	 * @return unknown
	 */
	function createUrl(){
		return $this->getName().'='.$this->getID();
	}
}
BASIC_SESSION::init();
/**
 * Интерфейс за достъп до драйвърите.
 * 
 * @package BASIC.SESSION
 */
interface SessionDrivers {
	function write();
	function distroy();	
}
/**
 * Session modul fom data base session
 *
 * @author Evgeni Baldzisky
 * @version 0.1 
 * @since 03-05-2007
 * @package BASIC.SESSION
 */
class SessionDriversDisk implements SessionDrivers{

	var $obj = null;

	function __construct(&$obj){

		$this->obj = &$obj;

		ini_set("session.gc_maxlifetime", $obj->TimeSess); 
		
		//session_name($obj->nameID);
		@session_start();

		//$obj->arrVar = $_SESSION ;
		if(time() > ((int)$obj->get('lastLog') + $obj->TimeSess)){
			//$obj->arrVar = array();
			$_SESSION = array();
			if(isset($_GET[$obj->getName()])){
				unset($_GET[$obj->getName()]);
			}
			@session_destroy();

			//session_name($obj->nameID);
			@session_start();
		}
		$obj->set('lastLog',time());
		
		$obj->passHesh = session_id();
		$obj->nameID = session_name();
	}

	function write(){
		session_write_close();
	}

	function distroy(){
		@session_destroy();
		$_SESSION = array();
	}
}
/**
 * Session modul fom disk session
 *
 * @author Evgeni Baldzisky
 * @version 0.1 
 * @since 03-05-2007
 * @package BASIC.SESSION
 */
class SessionDriversDb implements SessionDrivers{

	var $obj = null;

	function __construct(&$obj){

		$this->obj = &$obj;

		$GLOBALS['BASIC_SQL']->exec(" delete from `session` where `lastLog` < ".(time()-$obj->TimeSess)." ");

		$res = $GLOBALS['BASIC_ERROR']->error();

		if($res['code'] == '1146'){
			$GLOBALS['BASIC_SQL']->exec("
				CREATE TABLE `session` (
				 	`passhesh` varchar(32) NOT NULL default '0',
					`variables` text,
					`lastLog` int(15) NOT NULL default '0',
					UNIQUE KEY `key` (`passhesh`)
				 );
			");
		}

		if($obj->motod == "cookie"){
			$obj->passHesh = isset($_COOKIE[$obj->nameID])?addslashes($_COOKIE[$obj->nameID]):'';
		}else if($this->motod == "url"){
			if($_GET[$obj->nameID]){
				$obj->passHesh = isset($_GET[$obj->nameID])?addslashes($_GET[$obj->nameID]):'';
			}else{
				$obj->passHesh = isset($_POST[$obj->nameID])?addslashes($_POST[$obj->nameID]):'';
			}
		}
		//die(" SELECT `variables` FROM `session` WHERE `passhesh` = '".$obj->passHesh."' limit 1 ");
		$rdr = $GLOBALS['BASIC_SQL']->read_exec(" SELECT `variables` FROM `session` WHERE `passhesh` = '".$obj->passHesh."' limit 1 ",true);
		if(count($rdr) > 0){
			//$obj->arrVar =  unserialize($rdr['variables']);
			$_SESSION =  unserialize($rdr['variables']);
			//if(!is_array($obj->arrVar)) $obj->arrVar = array();
			if(!is_array($_SESSION)) $_SESSION = array();

			$GLOBALS['BASIC_SQL']->exec(" UPDATE session SET lastLog = " . time() . " WHERE passhesh = '".$obj->passHesh."' ");
		}else{
			$seed = (float) microtime( ) * 100000000 ;
			srand($seed);
			$obj->passHesh = md5(rand());

			$GLOBALS['BASIC_SQL']->exec(" INSERT INTO `session` SET `passhesh` = '".$obj->passHesh."',`lastLog` = ".time()." ");
		}
	}

	function write(){
		//$GLOBALS['BASIC_SQL']->exec(" UPDATE `session` SET `variables` = '".serialize($this->obj->arrVar)."' WHERE `passhesh` = '".$obj->passHesh."' ");
		$GLOBALS['BASIC_SQL']->exec(" UPDATE `session` SET `variables` = '".serialize($_SESSION)."' WHERE `passhesh` = '".$obj->passHesh."' ");
	}

	function distroy(){
		$GLOBALS['BASIC_SQL']->exec(" DELETE FROM `session` WHERE `passhesh` = '".$this->obj->passHesh."' ");
		setcookie($this->obj->nameID);
		//$this->obj->arrVar = array();
		$_SESSION = array();
	}
}