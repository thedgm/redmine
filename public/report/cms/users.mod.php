<?php
/**
 * API за работа с провилата. Използва де активно от BASIC_USERS когато му е подаден 
 * $permition_manager .
 * 
 * @author Evgeni Baldzisky
 * @version 0.1 
 * @since 01-10-2009
 * @package BASIC.USERS
 */
interface PermitionInterface {
	/**
	 * Извличане на правата за дадения потребител.
	 * 
	 * @param int $user_id
	 * @param string/InterfaseForm $cmp_owner
	 * @param string $perm_name
	 * @param boolen $default_mode
	 * @param int [$row_id]
	 */
	function getPermission($user_id, $cmp_owner, $perm_name, $row_id = 0, $default_mode = true);
	/**
	 * Добавяне на право на дадения потребител.
	 *
	 * @param int $user_id
	 * @param string/InterfaseForm $cmp_owner
	 * @param string $perm_name
	 * @param фддвех $status
	 * @param int $row_id
	 */
	function setPermission($user_id, $cmp_owner, $perm_name, $status, $row_id = 0);
	/**
	 * Изтриване на правила по зададени критерий.
	 * 
	 * @param int $row_id
	 * @param int $user_id
	 * @param string/InterfaseForm $cmp_owner
	 * @param string $action
	 * @return void
	 */
	function cleanPermission($row_id, $user_id = 0, $cmp_owner = '', $action = '');
}
/**
 * @var BASIC_USERS
 */
$GLOBALS['BASIC_USER'] = null;
/**
 * Сервис за работа с потребителите.
 *
 * @author Evgeni Baldzisky
 * @version 0.5 
 * @since 27-01-2007
 * @package BASIC.USERS
 */
class BASIC_USERS{
	/**
	 * Име на колоната за PRIMARY KEY
	 *
	 * @var string
	 */
	public $key_column   = 'id';
	/**
	 * Име на тавлицата с потребителите.
	 *
	 * @var string
	 */
	public $db_table     = 'users';
	/**
	 * Име на колоната която се ползва за името на потребителя.
	 *
	 * @var string
	 */
	public $name_column  = 'username';
	/**
	 * Име на колоната която се ползва за паролата на потребителя.
	 *
	 * @var string
	 */
	public $pass_column  = 'password';
	/**
	 * Име на колоната която се ползва за прово на достъп на потребителя.
	 * Ако не е посочена се игнорира.
	 * 
	 * @var string
	 */
	public $perm_column  = '';
	/**
	 * Име на колоната която се ползва за нивото на достъп на потребителя.
	 * Ако не е посочена се игнорира.
	 *
	 * @var string
	 * @todo Предстои промяна на тази функционалност.
	 */
	public $level_column = '';
	/**
	 * Име на колоната която се ползва от системата да записва времето в което потребителя
	 * последно е бил логнат.
	 *
	 * @var string
	 */
	public $last_log_column = 'last_log';
	/**
	 * Име на сесийната променливата по която системата се ориентира дали е вече
	 * логнат потребителя. 
	 *
	 * @var string
	 */
	public $userSysVar = 'UserID';
	public $logTime = 'logTime';
	/**
	 * Механизъм за допълнителна валидация на името и паролата.
	 *	<code>
	 * 		BASIC_USERS->init(array(
	 * 			'cleanVars' => array($myobject,'object_method_name')
	 * 		));
	 * 			// OR
	 * 		BASIC_USERS->init(array(
	 * 			'cleanVars' => 'my_function_name'
	 * 		));
	 * 	</code>
	 * @var array/string
	 */
	public $cleanVars = array('this','_cleanVars');
	
	public $rememberCookieName = 'remember_me';
	public $rememberCookiePass = 'remember_my_data';

	/**
	 * Позволява специален достъп. Ако е позволена системата не прави проверка
	 * в базата а проверява "devName" и "devPass" за коректност.
	 *
	 * @var boolen
	 */
	public $devUse   = true;
	/**
	 * Име на специалния потребител.
	 *
	 * @var string
	 */
	public $devName  = "developer";
	//public $devName  = "test";
	/**
	 * Парола на специалния потребител. Конвентирана с MD5.
	 *
	 * @var string
	 */
	public $devPass  = "4f5cec75c744bd39b5126debbb7cffb8";
	//public $devPass  = "1a1dc91c907325c69271ddf0c944bc72";
	/**
	 * Форматиране на изходните данни.
	 *
	 * @var array/string
	 */
	public $outCleaner = '';

	// Private Propertyes
	protected $userdata = array();
	protected $bufferData = array();
//	/**
//	 * Име на таблицата с права
//	 *
//	 * @var string
//	 */
//	public $perm_db 			= 'permissions';
//	/**
//	 * Име на колоната на потребителя собственик на дадено правило.
//	 *
//	 * @var string
//	 */
//	public $perm_user_column 	= 'user_id';
//	/**
//	 * Име на колоната за името на компонента собственик на правилото.
//	 *
//	 * @var string
//	 */
//	public $perm_owner_column 	= 'owner';
//	/**
//	 * Име на колоната за името на правилото.
//	 *
//	 * @var string
//	 */
//	public $perm_action_column 	= 'action';
//	/**
//	 * Име на колоната за състоянието на правилото
//	 *
//	 * @var unknown_type
//	 */
//	public $perm_state_column 	= 'state';
//	
//	protected $permissions = array();
	
	/**
	 * Enter description here...
	 *
	 * @var CMS,PermitionInterface
	 */
	public $permition_manager = null;
	
	/**
	 * @var BASIC_USERS
	 */
	function __construct(){
		$GLOBALS['BASIC']->imported('session.mod');
		$GLOBALS['BASIC_SESS']->start();
	}
	/**
	 * Сингълтон за достъп до сервиса и настроиката му.
	 * 	<code>
	 * 		BASIC_USERS::init(
	 * 			'db_table' 		=> 'my_custon_user_table',
	 * 			'name_column' 	=> 'user_name',
	 * 			'pass_column' 	=> 'user_password'
	 * 		)->checked();
	 * 	</code>
	 *
	 * @param array $config
	 * @return BASIC_USERS
	 */
	static function init($config=array()){
		if(!isset($GLOBALS['BASIC_USER'])){
			$GLOBALS['BASIC_USER'] = new BASIC_USERS();
		}
		foreach ($config as $k => $v){
			$GLOBALS['BASIC_USER']->$k = $v;
		}
		return $GLOBALS['BASIC_USER'];
	}
	/**
	 * Проверка за логнат потребител.
	 *
	 * @return boolen
	 */
	protected $_checked= false; // cash for performance
	function checked(){
		if($this->_checked){
			return $this->userdata ? $this->getUserId() : false;
		}
		
		$this->_checked = true;
		
		// ако има вече наличната дата се пропускат следващите търсения.
		if($this->userdata) return true;
		
		// ако е стартирана потдръжка на специален потребител.
		if($this->devUse){
			if($this->getUserId() == -1){
				$this->_developer();
				return true;
			}
		}
		
		if($GLOBALS['BASIC_URL']->cookie($this->rememberCookieName) && $GLOBALS['BASIC_URL']->cookie($this->rememberCookiePass)){
			$rdr = $GLOBALS['BASIC_SQL']->read_exec(" SELECT * FROM `".$this->db_table."` WHERE `".$this->key_column."` = " . (int)$GLOBALS['BASIC_URL']->cookie($this->rememberCookieName) ." ");
			$rdr->read();
			
			if(md5($rdr->field($this->key_column).$rdr->field($this->pass_column)) != $GLOBALS['BASIC_URL']->cookie($this->rememberCookiePass)){
				return false;
			}
		}else{
			$rdr = $GLOBALS['BASIC_SQL']->read_exec(" SELECT * FROM `".$this->db_table."` WHERE `".$this->key_column."` = " . $this->getUserId() ." ");
			$rdr->read();
		}
        $this->bufferData = $rdr->getItems();

		if($rdr->num_rows() != 0){
			$this->userdata = $this->cleanData($rdr->getItems());
			$this->saveLastLog();
			return $this->getUserId();
		}
		return false;
	}
	/**
	 * Логване на потребитела.
	 *
	 * @param string $user
	 * @param string(MD5) $pass
	 * @param boolen $remember
	 * @return boolen
	 */
	function login($user,$pass,$remember = false){
		if(
			(isset($this->bufferData[$this->name_column]) && $this->bufferData[$this->name_column] == $user) && 
			(isset($this->bufferData[$this->pass_column]) && $this->bufferData[$this->pass_column] == $pass)
		){
			return true;
		}
		// if user is developer
		if($this->devUse){
			if(($user==$this->devName) && (md5($pass)==$this->devPass)){
				$this->_developer();
				return true;
			}
		}
		if(is_array($this->cleanVars)){
			if($this->cleanVars[0] == 'this'){
				$this->cleanVars[0] = $this;
			}
			$class = $this->cleanVars[0];
			$method = $this->cleanVars[1];
			
			$user = $class->$method($user,'user');
			$pass = $class->$method($pass,'password');
		}else{
			$function = $this->cleanVars;
			$user = $function($user,'user');
			$pass = $function($pass,'password');
		}
	

		$query = " SELECT * FROM `".$this->db_table."` WHERE `".$this->name_column."` = '".$user."' AND `".$this->pass_column."` = '".$pass."' ";
		if($this->perm_column){
			$query .= " AND `".$this->perm_column."` = 1 ";
		}

		$rdr = $GLOBALS['BASIC_SQL']->read_exec($query);
		
		$rdr->read();
        $this->bufferData = $rdr->getItems();
        
		if($rdr->num_rows() != 0){

			$this->userdata = $this->cleanData($rdr->getItems());
			$this->saveLastLog();

			$GLOBALS['BASIC_SESS']->set($this->userSysVar,$this->field($this->key_column));
			
			if($remember){
				setcookie($this->rememberCookieName,$this->field($this->key_column),time()+(60*60*24*365),'/');
				setcookie($this->rememberCookiePass,
					md5($this->field($this->key_column).$this->field($this->pass_column)),
					time()+(60*60*24*365),
					'/'
				);
			}else{
				setcookie($this->rememberCookieName,'',time()-(60*60*24*365),'/');
				setcookie($this->rememberCookiePass,'',time()-(60*60*24*365),'/');
			}
		}
		return $this->getUserId();
	}
	/**
	 * Запазване на послидния момент но достъп до системата на потребителя.
	 */
	protected function saveLastLog(){
		$time = date('Y-m-d H:i:s',time());
		$GLOBALS['BASIC_SQL']->exec(" UPDATE `".$this->db_table."` SET `".$this->last_log_column."`= '".$time."' where `".$this->key_column."` = " . $this->getUserId() . " ");

				$GLOBALS['BASIC_ERROR']->reset();
		$res =  $GLOBALS['BASIC_ERROR']->error();

		if($res['code'] == 1054){
			$old = $GLOBALS['BASIC_SQL']->getSql();
			$GLOBALS['BASIC_SQL']->createColumn($this->db_table,' `'.$this->last_log_column.'` datetime' );
			$GLOBALS['BASIC_SQL']->exec($old);
			$GLOBALS['BASIC_ERROR']->clean();
		}
		$this->userdata[$this->last_log_column] = $time;
		$GLOBALS['BASIC_SESS']->set($this->logTime,strtotime(($this->get($this->last_log_column) ? $this->get($this->last_log_column) : time())));
	}
	/**
	 * Системно логване на даден потребител.
	 *
	 * @param int $id
	 * @return boolen
	 */
	function autoLogin($id){
		$rdr = $GLOBALS['BASIC_SQL']->read_exec(" SELECT * FROM `".$this->db_table."` WHERE `".$this->key_column."` = ".(int)$id." ");
		$rdr->read();

		if($rdr->num_rows() != 0){
			$this->userdata = $this->cleanData($rdr->getItems());
			$this->saveLastLog();

			$GLOBALS['BASIC_SESS']->set($this->userSysVar,$this->field($this->key_column));
			return true;
		}
		return false;
	}
	/**
	 * Почистване на наличните данни на потребителя.
	 *
	 * @param array $arr
	 * @return array
	 */
	function cleanData($arr){
		if($out = $this->outCleaner){
			if(is_array($this->outCleaner)){
				$class = $this->outCleaner[0];
				$method = $this->outCleaner[1];
				
				$arr = $class->$method($arr);
			}else{
				$method = $this->outCleaner;
				
				$arr = $method($arr);
			}
		}else{
			foreach ($arr as $k => $v){
				$arr[$k] = stripslashes($v);	
			}
		}
		return $arr;
	}
	/**
	 * Извличане на потребителския номер.
	 *
	 * @return int
	 */
	function getUserId(){
		return (int)BASIC_SESSION::init()->get($this->userSysVar);
	}
	/**
	 * Извличане на нивото но достъп.
	 *
	 * @todo предстои промяна
	 * @return int
	 */
	function level(){
		return isset($this->userdata[$this->level_column]) ? (int)$this->userdata[$this->level_column] : -2;
	}
	/**
	 * Извличане на информация за потребителя.
	 * Информоцията е константа на данните които се пазят в работната таблица определена от "$db_table"
	 * и добавените чрез "set".
	 *
	 * @param string $name
	 * @return string/mix
	 */
	function get($name){
		return isset($this->userdata[$name]) ? $this->userdata[$name] : '';
	}
	/**
	 * Шоркът на "get".
	 *
	 * @todo предстои да се премахне
	 * @param string $name
	 * @return string/mix
	 */
	function field($name){
		return isset($this->userdata[$name]) ? $this->userdata[$name] : '';
	}
	
	function exist(){
		return ($this->userdata ? true : false);
	}
	/**
	 * Добавяне на допълнителни данни към тези който ги е предоставила таблицата определена от "$db_table".
	 *
	 * @param sttring $name
	 * @param string/mix $value
	 */
	function set($name,$value){
		$this->userdata[$name] = $value;
	}
	/**
	 * Премахване на данни от списъка с налични от работната таблица плюс допълнително добавенита.
	 * 
	 * @param string $name
	 */
	function un($name){
		if(isset($this->userdata[$name])){
			unset($this->userdata[$name]);
		}
	}
	/**
	 * Изключванен на потребителя от системата.
	 * Унищожава както сесията така и кукитата от тип "remember me"
	 */
	function LogOut(){
		$this->userdata = array();
		$GLOBALS['BASIC_SESS']->distroy();
		
		setcookie($this->rememberCookieName,'',time()-(60*60*24*365),'/');
		setcookie($this->rememberCookiePass,'',time()-(60*60*24*365),'/');
	}
	/**
	 * Криптиране на подадената парола с опоменатия криптатор.
	 *
	 * @param string $pass
	 * @return string
	 */
	function passwordCripter($pass){
		if(is_array($this->cleanVars)){
			if($this->cleanVars[0] == 'this'){
				$this->cleanVars[0] = $this;
			}
			$class = $this->cleanVars[0];
			$method = $this->cleanVars[1];
			
			return $class->$method($pass,'password');
		}
		
		$function = $this->cleanVars;
		return $function($pass,'password');
	}
	/**
	 * Информация за достъп до дадено правило за определен потребител.
	 *
	 * @param string/InterfaseForm $cmp_owner [компонент собственик на правилото]
	 * @param string $perm_name [име на правилото]
	 * @param int [$row_id] [ID на ред. Правило на ниво ред]
	 * @param int [$user_id] [потребитяля за който се отнася правилото]
 	 * @return boolen
	 */
	function getPermission($cmp_owner, $perm_name, $row_id = 0, $user_id = null, $default_mode = true){
		if($user_id === null){
			$user_id = $this->getUserId();
		}
		
		if($this->userdata && $this->permition_manager){
			return $this->permition_manager->getPermission($user_id, $cmp_owner, $perm_name, $row_id, $default_mode);
		}
		return true;
	}
	/**
	 * Запис на информацията за достъп до дадено правило за определен потребител.
	 *
	 * @param string/InterfaseForm $cmp_owner [компонент собственик на правилото]
	 * @param string $perm_name [име на правилото]
	 * @param int [$row_id] [ID на ред. Правило на ниво ред]
	 * @param int [$user_id] [потребитяля за който се отнася правилото]
 	 * @return boolen
	 */
	function setPermission($cmp_owner, $perm_name, $status, $row_id = 0, $user_id = null){
		if($user_id === null){
			$user_id = $this->getUserId();
		}
		if($this->userdata && $this->permition_manager){
			$this->permition_manager->setPermission($user_id, $cmp_owner, $perm_name, $status, $row_id);
		}
	}
	
	function cleanPermission($row_id, $user_id = 0, $cmp_owner = '', $action = ''){
		if($this->userdata && $this->permition_manager){
			$this->permition_manager->cleanPermission($row_id, $user_id, $cmp_owner, $action);
		}
	}
	/**
	 * Извличане на информация за даден потребител.
	 * 
	 * @param int $id
	 * @return array
	 */
	function data($id = null){
		if($id !== null && $id != $this->getUserId()){
			$rdr = BASIC_SQL::init()->read_exec(" 
				SELECT 
					* 
				FROM 
					`".$this->db_table."` 
				WHERE 1=1
					AND `".$this->key_column."` = " . $id ." 
			");
				$rdr->read();
			return $rdr->getItems();
		}else{
			return $this->userdata;
		}	
	}
	
	/**
	 * Добавяне на данни на специалния патребител изисквани от системата.
	 */
	protected function _developer(){
		$this->userdata[$this->name_column] = $this->devName;
		$this->userdata[$this->level_column] = -1;
		$this->userdata[$this->key_column] = -1;
		$GLOBALS['BASIC_SESS']->set($this->userSysVar,-1);
	}
	/**
	 * Почиствощия механизъм на името и паролата по подразбиране.
	 * Паролата се криптира с MD5  по подразбиране.
	 *
	 * @param string $var
	 * @return string
	 */
	protected function _cleanVars($value,$type){
		if($type == 'user'){
			$symbols = 'a-zA-Z0-9_\.@ -';

			if(preg_match("/[^".$symbols."]/",$value)){
				return "";
			}
			return $value;
		}
		if($type == 'password') return md5($value);
		
		return '';
	}
}