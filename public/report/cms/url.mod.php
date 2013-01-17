<?php
/**
 * Сервис за прихващане и обравотка на променливите на REQUES-а.
 * Само инсталира се при заредането на файла 
 *
 * @author Evgeni Baldzisky
 * @version 1.5
 * @package BASIC.URL
 */
class BASIC_URL{
    /**
     * Дефиниция на работната област
     *
     * @var array - колекция от масиви
     */
	var $arrays = array('_POST','_GET','_COOKIE');
	/**
	 * За конфигориране и глобален достъп до сервиза 
	 * За котейнер на инстанцията се запазва $GLOBALS['BASIC_URL']
	 * 
	 * @param array [$arr]
	 * @return BASIC_URL
	 */
	static public function init($arr=array()){
		if(!isset($GLOBALS['BASIC_URL'])){
			$GLOBALS['BASIC_URL'] = new BASIC_URL();
		}
		foreach ($arr as $k => $v){
			$GLOBALS['BASIC_URL']->$k = $v;
		}
		return $GLOBALS['BASIC_URL'];
	}
	/**
	 * @return BASIC_URL
	 */
	function __construct(){
	    if($GLOBALS['BASIC']->ini_get('rewrite')){
	        $tmp = $GLOBALS['BASIC']->ini_get('rewrite');
	        $tmp->decoder();
	    }
	}
	/**
	 * Презареждане на сервиз)
	 * <code>
	 * 	 <example> 
	 * 		BASIC::init()->ini_get('rewrite','BasicRewrite');
	 * 		BASIC_URL::init()->restart();
	 * </code>
	 * @return BASIC_URL
	 */
	function restart(){
	    $this->__construct();
	    return $this;
	}
	/**
	 * Ако съществува дадена променлива неиното съдаржание се връща след обработката
	 *
	 * @param array $array
	 * @param string $name
	 * @param string [$hand_clean]
	 * @param int [$long]
	 * @return string
	 */
	protected function _checked($array,$name,$hand_clean='',$long=0){
	    if(isset($array[$name.'_x'])){
	        $array[$name] = $array[$name.'_x'];
	    }
		if(isset($array[$name])){
			if($hand_clean){
				return $this->_clean($hand_clean,$array[$name],$long);
			}
			return $array[$name];
		}
		return '';
	}
	/**
	 * Проверса в POST::REQUEST
	 *
	 * @param string $name
	 * @param string [$hand_clean]
	 * @param string [$type]
	 * @param int [$long]
	 * @return string
	 */
	function post($name,$hand_clean='',$long=0){
		$tmp = $this->_checked($_POST,$name,$hand_clean,$long);
		return $tmp;
	}
	/**
	 * Проверса в GET::REQUEST
	 *
	 * @param string $name
	 * @param string [$hand_clean]
	 * @param string [$type]
	 * @param int [$long]
	 * @return string
	 */
	function get($name,$hand_clean='',$long=0){
		$tmp = $this->_checked($_GET,$name,$hand_clean,$long);
		return $tmp;
	}
	/**
	 * Проверса в COOKIE::REQUEST
	 *
	 * @param string $name
	 * @param string [$hand_clean]
	 * @param string [$type]
	 * @param int [$long]
	 * @return string
	 */
	function cookie($name,$hand_clean='',$long=0){
		$tmp = $this->_checked($_COOKIE,$name,$hand_clean,$long);
		return $tmp;
	}
	/**
	 * Проверса в работната овласт
	 * <code>
	 * 	<example/> 
	 * 		BASIC::init()->ini_set('rewrite','BasicRewite');
	 * 
	 * 		http://localhost/script/var1/1/_var2/2/var13/1
	 * 			--> BASIC_URL::init()->request('var2') == '2' 
	 * 			--> BASIC_URL::init()->request('var8') == ''
	 * 
	 *  		NEW 
	 *  		--> BASIC_URL::init()->request('var%') == array(
	 *  			var1 => 1,
	 *  			var13 => 1
	 *  		)
	 *  		--> BASIC_URL::init()->request('%var') == array(
	 *  			_var2 => 2
	 *  		)
	 * 
	 * 		<form method="post" action="http://localhost/script">
	 * 			<input name="var1" value="1" />
	 * 			<input name="_var2" value="3" />
	 * 			<input name="var4" value="3" />
	 * 			<input type="submit" />
	 * 		</form>
	 * 			--> BASIC_URL::init()->request('_var2') == '3' 
	 * 			--> BASIC_URL::init()->request('var8') == ''
	 * 
	 *  		NEW 
	 *  		--> BASIC_URL::init()->request('var%') == array(
	 *  			var1 => 1,
	 *  			var4 => 3
	 *  		)
	 *  		--> BASIC_URL::init()->request('%var') == array(
	 *  			_var2 => 3
	 *  		)
	 * </code>
	 * 
	 * @param string $name
	 * @param string [$hand_clean]
	 * @param string [$type]
	 * @param int [$long]
	 * @return string/array
	 */
	function request($name, $hand_clean='', $long=0){
		if(strpos($name,'%') === false){
			$tmp = '';
			//check post
			foreach($this->arrays as $v){
				global $$v;
				$tmp = $this->_checked($$v,$name,$hand_clean,$long);
				if($tmp !== 'undefined' && $tmp !== '') return $tmp;
			}
			return $tmp;
		}else{
		    $pattern = '';
	    	if($name[0] == '%'){
	    		$pattern .= '.*';
	    	}
	    	$pattern .= str_replace("%",'',$name);
	    	if($name[strlen($name)-1] == '%'){
	    		$pattern .= '.*';
	    	}
	    	return $this->preg_request("/".$pattern."/i", $hand_clean, $long);
		}
	}
	/**
	 * Откриване на елементи в рекуеста по дадено съвпадение на регулярен израз.
	 * <code>
	 * 		BASIC_URL->init()->set('test',1);
	 * 		BASIC_URL->init()->set('tova_e_test',1);
	 * 		BASIC_URL->init()->set('te__tova_st',1);
	 * 		BASIC_URL->init()->set('test_e_tova',1);
	 * 
	 * 		$arr = BASIC_URL->init()->preg_request("/[_]+(tova)/",array($cladd_cleaner,'method_name_cleaner'));
	 * 
	 * 		<result>
	 * 			array(
	 * 				'te__tova_st' => 1,
	 * 				'test_e_tova' => 1
	 * 			)
	 * 		</rewult>
	 * </code>
	 * 
	 * @param string $pattern
	 * @param array/string $hand_clean
	 * @param int $long
	 * @return array
	 */
	function preg_request($pattern, $hand_clean='', $long=0){
		$tmp = array();
		//check post
		foreach(array_reverse($this->arrays) as $v){
			global $$v;
			
		    foreach($$v as $ke => $el){
		    	$_tmp = '';
				if(preg_match($pattern,$ke)){				
					if($hand_clean){
						$_tmp = $this->_clean($hand_clean,$el,$long);
					}
					$_tmp = $el;
				}
				if($_tmp != 'undefined' && $_tmp != ''){
					$tmp[$ke] = $_tmp;
				}
		    }
		}
		return $tmp;
	}
	/**
	 * Проверка дали променливата присъства в равотната област
	 *
	 * @param string $name
	 * @return boolen
	 */
	function test($name){
		foreach($this->arrays as $v){
			global $$v;
				$arr = (array)$$v;
			if(isset($arr[$name])) return true;
		}
		return false;
	}
	/**
	 * Търсене или обработка на ресурс извън работната област
	 *
	 * @param array $array
	 * @param string $name
	 * @param string $hand_clean
	 * @param string [$type]
	 * @param int [$long]
	 * @return data
	 */
	function other($array,$name,$hand_clean,$long=0){
		if($name){
			$tmp = $this->_checked($array,$name,$hand_clean,$long);
		}else{
			$tmp = $this->_clean($hand_clean,$array,$long);
		}


		return $tmp;
	}
	/**
	 * Добавяне на промелива в работната област
	 *
	 * @param string $name
	 * @param string $value
	 * @param string [$array]
	 * @return void
	 */
	function set($name,$value,$array = 'post'){
		if($array == 'post') $_POST[$name] = $value;
		if($array == 'get') $_GET[$name] = $value;
		if($array == 'cookie') $_COOKIE[$name] = $value;
	}
	/**
	 * премахване на променлива от работната област
	 *
	 * @param string $name
	 * @return void
	 */
	function un($name){
		if(isset($_POST[$name])) unset($_POST[$name]);
		if(isset($_GET[$name])) unset($_GET[$name]);
		if(isset($_COOKIE[$name])) unset($_COOKIE[$name]);
	}
	/**
	 * нулиране на парчета от работната област
	 */
	function cleanRequest($type=null){
		switch ($type){
			case 'post': $_POST = array(); break;
			case 'get': $_GET = array(); break;
			case 'cookie': $_COOKIE = array(); break;
			default:
				$_POST = array();
				$_GET =  array();
				$_COOKIE = array();
		}
	}

	// ############ link menager ########### //

	/**
	 * Конвертиране на работната област в URL или FORM последователности
	 *	<code>
	 * 		URL : http://localhost/site_root/script_name/var1/1/var2/12/var3/21
	 * 			
	 * 			BASIC_URL::init()->serialize() == 'script_name.php?var1=1&var2=12&var3=21
	 * 			BASIC_URL::init()->serialize(array('var1',var3')) == 'script_name.php?var2=12
	 * 			BASIC_URL::init()->serialize(array('var1'),'post') == 
	 * 															<input type="hidden" name="var2" value="12"/>
	 * 															<input type="hidden" name="var3" value="21"/>
	 * </code>
	 * @param array [$arrMiss] - колекция ат имена на променливи които НЕ прябва да присъстват във отговора
	 * @param string [$metod]  - метод на конвертиране [post|get]
	 * @return string	
	 */
	function serialize($arrMiss=array(),$metod = 'get',$only = ''){
		if(!is_array($arrMiss)) $arrMiss = array($arrMiss);

		$serialize = '';
		if($only){
			if($only == 'get') $serialize .= $this->_serialize($_GET,$arrMiss,$metod);
			if($only == 'post') $serialize .= $this->_serialize($_POST,$arrMiss,$metod);	
		}else{
			$serialize .= $this->_serialize($_GET,$arrMiss,$metod);
			$serialize .= $this->_serialize($_POST,$arrMiss,$metod);
		}
		return $serialize;
	}
	/**
	 * Конвертиране на расурс извън работната област в URL или FORM последователности
	 * <code>
	 * 		$my_resource = array(
	 * 			'var1' => 12,
	 * 			'var2' => 5,
	 * 			'var5' => 'variable'
	 * 		);
	 * 
	 * 		BASIC_URL::init()->userSerialize($my_resource) == 'script_name.php?var1=12&var2=5&var5=variable'
	 * 		BASIC_URL::init()->userSerialize($my_resource,'post') == 
	 * 															<input type="hidden" name="var1" value="12" />
	 * 															<input type="hidden" name="var2" value="5" />
	 * 															<input type="hidden" name="var1" value="variable" />
	 * </code>
	 * @param array $arrRes
	 * @param string [$metod]
	 * @return string
	 */
	function userSerialize($arrRes,$metod = 'get'){
		return $this->_serialize($arrRes,array(),$metod);
	}
	/**
	 * Пренасочване на браузара
	 * <code>
	 * 		BASIC::init()->ini_set('rewrite','BasicRewrite');
	 * 
	 * 		BASIC_URL::init()->redirect('http://localhost/other_script.php','var1=1&var2=2') 
	 * 				== URL : 'http://localhost/other_script.php?var1=1&var2=2';
	 * 		BASIC_URL::init()->redirect('other_script.php','var1=1&var2=2',true) 
	 * 				== URL : 'http://localhost/other_script/var1/1/var2/2';
	 * </code>
	 * @param string $url
	 * @param string [$addvars]
	 * @param array [$context]
	 */
	function redirect($url='',$addvars='',$context = array(
		'ignore_rewrite' => false,
		'target' => '_self' /* _self|_blank */
	)){
		
		if(!is_array($context)){
			throw new Exception(' Parametar $context is must array type.');
		}
		if(!isset($context['ignore_rewrite'])) $context['ignore_rewrite'] = false;
		if(!isset($context['target'])) $context['target'] = '_self';
		
		if(!$url) $url = $GLOBALS['BASIC']->scriptName();
		if(!$url && !$addvars) $url = './';

		$tmp = '';
		if(BASIC::init()->ini_get('rewrite') && !$context['ignore_rewrite']){
		    $tmp = $GLOBALS['BASIC']->ini_get('rewrite');
            $tmp = $tmp->encoder($url . ($addvars ? "?" . $addvars : ""));
		}else{
			$tmp = $url . ($addvars ? "?" . $addvars : "");
		}
		if($context['target'] != '_self'){
			print '
				<html>
					<body>
						<form method="get" action="'.$tmp.'" target="'.$context['target'].'" id="form"></form>
						<script type="text/javascript">document.getElementById("form").submit();window.history.go(-1);</script>
					</body>
				</html>
			';
		}else{
			header("Location: ".$tmp);
		}
		exit();
	}
	/**
	 * При стартиран rewrite подава за конвертира подадения му адрес
	 * <code>
	 * 		BASIC::init()->ini_set('rewrite','BasicRewrite');
	 * 
	 * 		$my_link_tag = '<a href="'.BASIC_URL::init()->link(script_name.php?var1=1&var2=2).'" title="_blank" >This is my html link</a>
	 * </code> 
	 *
	 * @param string $url
	 * @param string $addvars
	 * @return string
	 */
	function link($url='', $addvars=''){
		if($GLOBALS['BASIC']->ini_get('rewrite')){
		    $tmp = $GLOBALS['BASIC']->ini_get('rewrite');
            return $tmp->encoder($url . ($addvars ? "?" . $addvars : ""));
		}else{
			return $url . ($addvars ? "?" . $addvars : "");
		}
	}
	/**
	 * Помощен метод 
	 * 	на serialize и userSerialize за намиране на променливите които се пропускат 
	 *
	 * @param array $arrMiss
	 * @param string $k
	 * @return boolen
	 */
	protected function _l_miss($arrMiss,$k){
		$check = false;
		if(is_array($arrMiss)){
			foreach ($arrMiss as $miss){
				if($miss == $k) $check = true;
			}
		}
		return $check;
	}
	/**
	 * Помощен метод 
	 *
	 * @param array $arrSearch
	 * @param array $arrMiss
	 * @param string $metod
	 * @return string
	 */
	protected function _serialize($arrSearch,$arrMiss,$metod){
		$serialize = '';
		foreach ($arrSearch as $k=>$v){
			if($this->_l_miss($arrMiss,$k)) continue;
			
			$v = str_replace('<','&lt;'  ,$v);
			$v = str_replace('>','&gt;'  ,$v);
			$v = str_replace('"','&quot;',$v);
			
			if($metod == 'get'){
				$k = urlencode($k);
				if(is_array($v)){
					foreach ($v as $arr_v){
						if($arr_v != '') $serialize .= $k . "[]=" . $arr_v . "&";
					}
				}else{
					if($v != '') $serialize .= $k . "=" . $v . "&";
				}
			}else{
				if(is_array($v)){
					foreach ($v as $arr_v){
						if($arr_v != '') $serialize .= '<input type="hidden" name="'.$k.'[]" value="'.$arr_v.'" />'."\n";
					}
				}else{
					if($v != '') $serialize .= '<input type="hidden" name="'.$k.'" value="'.$v.'" />'."\n";
				}
			}
		}
		return $serialize;
	}
	/**
	 * Предава на оказания $hand_clean за обработка.
	 * <code>
	 * 	!) $hand_clean == array(object,'method')
	 * 	!) $hand_clean == 'function name'
	 * при зададена максимална дължина на символите на данните ако те я надвишават данните се отрязват до 
	 * подадената дължина 
	 * </code>
	 * 
	 * @param string $hand_clean - име фукция или метод на които ще се предава
	 * @param array|string $post - данните които ще се предадат
	 * @param int $long 		 - максимална дължина на символите на данните като ако 
	 * @return array|string
	 */
	protected function _clean($hand_clean,$post,$long=0){
		//if($post != ''){
			if(is_array($post)){
				$arrtemp = array();
				foreach ($post as $k => $v){
					$arrtemp[$k] = $this->_clean($hand_clean,$v,$long);
				}
				return $arrtemp;
			}
			if($long != 0 && $long < strlen($post)){
				return substr($post,0,$long);
			}
			if($hand_clean){
 				if(is_array($hand_clean)){
					$class = $hand_clean[0]; 
					$method = $hand_clean[1];
					if($class === null){
						return $method($post);
					}
					return $class->$method($post);
				}else{
					return $hand_clean($post);
				}
			}
			return $post;
		//}
	  	//return '';
	}
	
	// End class BASIC_URL
}
BASIC_URL::init();



function cleanURLInjection($post){
	$post = addslashes($post);
	$post = str_replace("#","",$post);
	
	return $post;
}
/**
 * 
 * 
 *      REWRITE PLUGIN
 *          Standart access declaration is :
 * 
 *               RewriteEngine On
 *               
 *               RewriteRule ^/$ index/ [R]
 *               
 *               RewriteCond %{REQUEST_FILENAME} !/cp
 *               RewriteCond %{REQUEST_FILENAME} !/css
 *               RewriteCond %{REQUEST_FILENAME} !/img
 *               RewriteCond %{REQUEST_FILENAME} !/js
 *               RewriteCond %{REQUEST_FILENAME} !/cms
 *               RewriteCond %{REQUEST_FILENAME} !/upload
 *               
 *               RewriteRule ^([^/\.]+)/(.+)?$ $1.php?_rewrite_=$2 [L]
 *               
 *               RewriteCond %{REQUEST_FILENAME} !/cp
 *               RewriteCond %{REQUEST_FILENAME} !/css
 *               RewriteCond %{REQUEST_FILENAME} !/img
 *               RewriteCond %{REQUEST_FILENAME} !/js
 *               RewriteCond %{REQUEST_FILENAME} !/cms
 *               RewriteCond %{REQUEST_FILENAME} !/upload
 *               
 *               RewriteRule ^([^/\.]+)$ $1.php [L]
 */
class BasicRewrite{
    
    var $var_name = '_rewrite_';
    public $special_variable_name = 'url_var';
    
    function encoder($url,$save_state = null){
	    if(is_array($save_state)){
	        $expl = explode("?",$url);
	        $ser = $GLOBALS['BASIC_URL']->serialize($save_state);
	        
	        if(isset($expl[1])){
	           $url =  $expl[0].'?'.$ser.$expl[1];
	        }else{
	           $url = $url.'?'.$ser;
	        }
	    }    
	    $expl = explode("?",$url);
	    $link = str_replace(".php","",$expl[0]);
	    
	    $vars = ''; 
	    if(isset($expl[1])){
	        foreach (explode("&",$expl[1]) as $k => $v){
	            $vars .= str_replace("=","/",$v)."/";
	        }
	    }
	    return BASIC::init()->ini_get('root_virtual').str_replace(BASIC::init()->ini_get('root_virtual'),"",$link).($url && $vars ? '/' : '').($vars ? $vars : ''); 
    }
    /**
     * Url декодер. Разцепва URL стринга на части като ползва "/" за сепаратор. 
     * Ако последната част е име на променлива тогава се поставя като стойност на променлива с име "$special_variable_name"
     */
    function decoder(){
        $url = $GLOBALS['BASIC_URL']->request($this->var_name,'cleanURLInjection');
        
        $tmp = '';$incr = 0;
        foreach (explode("/",$url) as $v){
            if((string)$v=="") continue;
            
            if(!($incr % 2)){
                $tmp = $v;
            }else{
                $_GET[$tmp] = $v;
                $tmp = '';
            }
            $incr++;
        }
		if($tmp){
			$_GET[$this->special_variable_name] = $tmp;
		}
        unset($_GET[$this->var_name]); 
    }
    // End Class BasicRewrite
}