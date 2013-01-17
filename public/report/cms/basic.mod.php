<?php
/**
 * Основни инструменти на класовете
 *
 * @package BASIC
 */
class BASIC_CLASS {
	/**
	 * WARNING : if phpversion() < 5 con't used this method if add objects.
	 * 		if used method with value == is_object() ,refference isn't existed.
	 *
	 * @param string $name
	 * @param * $value
	 */
	function set($name,$value){
		$this->$name = $value;
	}
	function sets($paramArray){
		if($paramArray){
			foreach ($paramArray as $k => $v){
				$this->$k = $v;
			}
		}
	}
	function get($name,$retur_false = 'undefined'){
		if(is_array($name)){
			$tmp = array();
			foreach ($name as $v){
				if(isset($this->$name)) $tmp[$name] = $this->$name;
				$tmp[$name] =  $retur_false;
			}
			return $tmp;
		}
		if(isset($this->$name)) return $this->$name;
		return $retur_false;
	}
	function un($name){
		if(isset($this->$name)) unset($this->$name);
	}
	function getType($name){
		return $this instanceof $name;
	}
	// End Class BASIC_CLASS
}
/**
 * Интерфейс за достъп до сервизите на системата.
 * 
 * @package BASIC
 */
interface BASIC_SERVISES {
	static function init();
}
/**
 * Основен клас които играе ролята на склад с инструменти.
 * Само исталира се при отваряне на фаила в който се съдържа като това трябва да стане 
 * преди останалине сервизи защото те разчитат на неговите инструменти.
 *
 * @author Evgeni Baldzisky
 * @version 1.4
 * @package BASIC
 */
final class BASIC extends BASIC_CLASS implements BASIC_SERVISES{

	/**
	 * Колекция от настроики на средата
	 * 
	 * 	basic_path 	- име на директорията на ядрото
	 *	root_path   - име на директорията в която се намира ядрата(обиктовенно там е оснавата на прилоението)
	 *	root_virtual- когато се работи във WEB среда пътя до основата на саита
	 *	error_level - ниво на грешките(препоръва се 6143 за разработка и 0 за публикуване)
	 *  script_name - име на текущия скрипт(ползва де от ReWrite ендина)
	 * 
	 * @var array $ini
	 */
	private $ini = array(
		'version' => 1.4,
		
		// basic
		'basic_path' => '',
		'root_path' => '',
		'root_virtual' => '',
		'error_level' => 0,
		'script_name' => ''
	);
	/**
	 * Constructor
	 *  
	 * Създават се пътищата на средата
	 * @example E:\projects\docs\info.php
	 */
	function __construct(){
		$dir = strtolower(preg_replace("#[^/]+$#","",str_replace("\\","/",__FILE__)));

		$_SERVER['SCRIPT_NAME'] = strtolower(str_replace("\\","/",$_SERVER['SCRIPT_NAME']));
		$_SERVER['SCRIPT_FILENAME'] = strtolower(str_replace("\\","/",$_SERVER['SCRIPT_FILENAME']));

		$this->ini_set("root_path",preg_replace("#([^/]+/)$#","\\2",$dir));

		$this->ini_set('basic_path',str_replace($this->ini_get("root_path"),"",$dir));

		preg_match("#^/?[^/]+#",$this->ini_get("root_path"),$ex);
		preg_match("#^/?[^/]+#",$_SERVER['SCRIPT_FILENAME'],$ex1);

		if(isset($ex[0]) && isset($ex1[0])){
			if($ex[0] != $ex1[0]){
				$_SERVER['SCRIPT_FILENAME'] = ereg_replace("^.*".$ex[0],$ex[0],$_SERVER['SCRIPT_FILENAME']);
			}
		}

		if(isset($_SERVER['HTTP_HOST'])){
			$doc_root = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']);
	
			$dir = $this->validPath(str_replace($doc_root,'',$this->ini_get("root_path")));
			$protocol = explode('/',$_SERVER['SERVER_PROTOCOL']);
			$this->ini_set('root_virtual', strtolower($protocol[0]) . "://".$_SERVER['HTTP_HOST'].$dir);
		}
	}
	
	/**
	 * Глобален достап до сервиза
	 * За котейнер на инстанцията се запазва $GLOBALS['BASIC']
	 * @return BASIC
	 */
	static public function init(){
	    if(!isset($GLOBALS['BASIC'])){
	        $GLOBALS['BASIC'] = new BASIC();
	    }
	    return $GLOBALS['BASIC'];
	}
	/**
	 * Добавяне настроика на средата
	 *
	 * @param string $name - име на настроиката
	 * @param mix $val - всики типове
	 */
	function ini_set($name,$val){
		if($name == 'error_level'){
			 error_reporting((int)$val);
		}
		$this->ini[$name] = $val;
	}
	/**
	 * Достъп до настроика на средата
	 * 
	 * @return mix - ако не сйществува NULL
	 */
	function ini_get($name){
		if(isset($this->ini[$name])){
			return $this->ini[$name];
		}
		return null;
	}
	/**
	 * Премахване на настроика на средата
	 * 
	 * Основните настроики са запазени от изтриване
	 *		version
	 *		basic_path
	 *		root_path
	 *		root_virtual
	 *		error_level
	 * 		script_name
	 * @param string $name
	 */
	function ini_unset($name){
		switch ($name){
			case 'version' :
			case 'basic_path' :
			case 'root_path' :
			case 'root_virtual' :
			case 'error_level' :
				throw new Exception('Var '.$name.' is saved for unset!');
				break;
			default:
				unset($this->ini[$name]);
		}
	}
	/**
	 * Зареждане на ресурси като пътя до осовната директория наало.
	 *
	 * @param string $resource - име на ресурса
	 * @param string $package  - пътя до него
	 */
	function imported($resource, $package=''){
		if(!$package) $package = $this->ini_get('basic_path');
		if((include_once $this->ini_get('root_path').$this->validPath($package).$resource.'.php') === false){
			throw new Exception('Can not open resource "'.$resource.'" in this ['.$this->ini_get('root_path').$package.'] address');
		}
	}

	// ############## Extras Manager ############# //

	/**
	 * change URL protocol ex: https,ftp
	 *
	 * @param string $new
	 * @param boolen $global
	 */
	function changeProtocol($new,$global = true){

		$protocol = explode('/',$_SERVER['SERVER_PROTOCOL']);
		$change = str_replace(strtolower($protocol[0]),$new,$this->ini_get('root_virtual'));
		if($global){
			$this->ini_set('root_virtual',$change);
		}

		return $change;
	}
	/**
	 * преобразуване на "\" в "/" и поставяне на "/" накрая ако няма такава
	 *
	 * @param string $path - фаилов или URL път
	 */
	function validPath($path){
		$path = str_replace("\\","/",$path);
		$path = str_replace("//","/",$path);

		if(!preg_match("#/$#",$path)) $path .= '/';

		return $path;
	}
	/**
	 * Името на текущия скрипт
	 *
	 * <code>
	 * 		BASIC::init()->ini_get('root_virtual') -> 'http://localhost/mysite/'	
	 * 
	 * 		Зареден е адреса : http://localhost/mysite/info.php
	 * 			BASIC::init()->scriptName() == 'info.php'
	 * 
	 * 		при BASIC::init()->ini_set('rewrite','BasicRewrite');
	 * 
	 * 		Зареден е адреса : http://localhost/mysite/info
	 * 			BASIC::init()->scriptName() == 'info.php'
	 * </code>
	 * 
	 * @return string
	 */
	function scriptName(){
	    if($this->ini_get('script_name')){
	        return $this->ini_get('script_name');
	    }
		return basename($_SERVER["PHP_SELF"]);
	}
	/**
	 * Име на директорията от дадения път
	 *
	 * @param яшисхж $path
	 * @return яшисхж
	 */
	function dirName($path = ''){
		$ex = explode("/",str_replace("\\","/",($path ? $path : $_SERVER["PHP_SELF"])));
		if(count($ex) > 1){
			return $ex[count($ex) - 2].'/';
		}
		return '';
	}

	/**
	 * Конвертиране на байтове в текст
	 *
	 * <code>
	 * 	BASIC::init()->biteToString(1024) == '1.00KB'
	 * </code>
	 * @version 0.2
	 * @param string/number $num
	 * @return string
	 */
	function biteToString($num){
		 $s = 1024;
		 $num = (int)$num;

		 $convert = $num . " Byte";

		 if($num >= pow($s,1))
		  	$convert = sprintf('%.2f',$num/pow($s,1))."KB";
		 if($num >= pow($s,2))
		  	$convert = sprintf('%.2f',$num/pow($s,2))."MB";
		 if($num >= pow($s,3))
		 	$convert = sprintf('%.2f',$num/pow($s,3))."GB";
		 if($num >= pow($s,4))
		 	$convert = sprintf('%.2f',$num/pow($s,3))."TB";

		 return $convert;
	}
	/**
	 * Конвертиране на текст в байтове
	 *
	 * <code>
	 * 		BASIC::init()->stringToBite('1KB') == 1024
	 *</code>
	 * 
	 * @param string $str
	 * @return int
	 */
	function stringToBite($str){
		$tmp = str_replace("B","",$str);
		$tmp = substr($tmp,strlen($tmp)-1);

		if($tmp == 'T') return ((int)$str) * 1024*1024*1024*1024;
		if($tmp == 'G') return ((int)$str) * 1024*1024*1024;
		if($tmp == 'M') return ((int)$str) * 1024*1024;
		if($tmp == 'K') return ((int)$str) * 1024;

		return (int)$str;
	}
	/**
	 * Проверка за валидност на e-mail
	 *
	 * <code>
	 *		BASIC::init()->validEmail('name@dom.ext') == 'name@dom.ext'
	 * 		BASIC::init()->validEmail('name.dom.ext') == ''
	 * </code>
	 * 
	 * @param string $email
	 * @return string
	 */
	function validEmail($email){
		if(!preg_match('/^.+@.+\..+$/',$email)) return '';
		return $email;
	}
	/**
	 * Задаване на колекция от хедари за прекратяване на кеширането и при желание оказване че това е XML
	 *
	 * @param boolen [$xml]
	 */
	function SetXmlHeaders($xml = true){
		// Prevent the browser from caching the result.
		// Date in the past
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT') ;
		// always modified
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT') ;
		// HTTP/1.1
		header('Cache-Control: no-store, no-cache, must-revalidate') ;
		header('Cache-Control: post-check=0, pre-check=0', false) ;
		// HTTP/1.0
		header('Pragma: no-cache') ;

		// Set the response format.
		if($xml) header( 'Content-Type:text/xml; charset=utf-8' ) ;
	}
	/**
	 * 
	 * @param string $type
	 * @return string
	 */
	public function getMimeType($type){
	   $mimes = array(
	      'hqx'   =>  'application/mac-binhex40',
	      'cpt'   =>  'application/mac-compactpro',
	      'doc'   =>  'application/msword',
	      'bin'   =>  'application/macbinary',
	      'dms'   =>  'application/octet-stream',
	      'lha'   =>  'application/octet-stream',
	      'lzh'   =>  'application/octet-stream',
	      'exe'   =>  'application/octet-stream',
	      'class' =>  'application/octet-stream',
	      'psd'   =>  'application/octet-stream',
	      'so'    =>  'application/octet-stream',
	      'sea'   =>  'application/octet-stream',
	      'dll'   =>  'application/octet-stream',
	      'oda'   =>  'application/oda',
	      'pdf'   =>  'application/pdf',
	      'ai'    =>  'application/postscript',
	      'eps'   =>  'application/postscript',
	      'ps'    =>  'application/postscript',
	      'smi'   =>  'application/smil',
	      'smil'  =>  'application/smil',
	      'mif'   =>  'application/vnd.mif',
	      'xls'   =>  'application/vnd.ms-excel',
	      'csv'   =>  'application/vnd.ms-excel',
	      'ppt'   =>  'application/vnd.ms-powerpoint',
	      'wbxml' =>  'application/vnd.wap.wbxml',
	      'wmlc'  =>  'application/vnd.wap.wmlc',
	      'dcr'   =>  'application/x-director',
	      'dir'   =>  'application/x-director',
	      'dxr'   =>  'application/x-director',
	      'dvi'   =>  'application/x-dvi',
	      'gtar'  =>  'application/x-gtar',
	      'php'   =>  'application/x-httpd-php',
	      'php4'  =>  'application/x-httpd-php',
	      'php3'  =>  'application/x-httpd-php',
	      'phtml' =>  'application/x-httpd-php',
	      'phps'  =>  'application/x-httpd-php-source',
	      'js'    =>  'application/x-javascript',
	      'swf'   =>  'application/x-shockwave-flash',
	      'sit'   =>  'application/x-stuffit',
	      'tar'   =>  'application/x-tar',
	      'tgz'   =>  'application/x-tar',
	      'xhtml' =>  'application/xhtml+xml',
	      'xht'   =>  'application/xhtml+xml',
	      'zip'   =>  'application/zip',
	      'mid'   =>  'audio/midi',
	      'midi'  =>  'audio/midi',
	      'mpga'  =>  'audio/mpeg',
	      'mp2'   =>  'audio/mpeg',
	      'mp3'   =>  'audio/mpeg',
	      'aif'   =>  'audio/x-aiff',
	      'aiff'  =>  'audio/x-aiff',
	      'aifc'  =>  'audio/x-aiff',
	      'ram'   =>  'audio/x-pn-realaudio',
	      'rm'    =>  'audio/x-pn-realaudio',
	      'rpm'   =>  'audio/x-pn-realaudio-plugin',
	      'ra'    =>  'audio/x-realaudio',
	      'rv'    =>  'video/vnd.rn-realvideo',
	      'wav'   =>  'audio/x-wav',
	      'bmp'   =>  'image/bmp',
	      'gif'   =>  'image/gif',
	      'jpeg'  =>  'image/jpeg',
	      'jpg'   =>  'image/jpeg',
	      'jpe'   =>  'image/jpeg',
	      'png'   =>  'image/png',
	      'tiff'  =>  'image/tiff',
	      'tif'   =>  'image/tiff',
	      'css'   =>  'text/css',
	      'html'  =>  'text/html',
	      'htm'   =>  'text/html',
	      'shtml' =>  'text/html',
	      'txt'   =>  'text/plain',
	      'text'  =>  'text/plain',
	      'log'   =>  'text/plain',
	      'rtx'   =>  'text/richtext',
	      'rtf'   =>  'text/rtf',
	      'xml'   =>  'text/xml',
	      'xsl'   =>  'text/xml',
	      'mpeg'  =>  'video/mpeg',
	      'mpg'   =>  'video/mpeg',
	      'mpe'   =>  'video/mpeg',
	      'qt'    =>  'video/quicktime',
	      'mov'   =>  'video/quicktime',
	      'avi'   =>  'video/x-msvideo',
	      'movie' =>  'video/x-sgi-movie',
	      'doc'   =>  'application/msword',
	      'word'  =>  'application/msword',
	      'xl'    =>  'application/excel',
	      'eml'   =>  'message/rfc822'
	    );
	    return isset($mimes[$type]) ? $mimes[$type] : '';
	}
}
/**
 * Каства към integer
 *	
 * <code>
 * 		BASIC_URL::init()->request('myvar','Int',21)		
 * </code>
 * 
 * @package BASIC
 * @param mix $number
 * @return int
 */
function Int($number){
	return (int)$number;
}
/**
 * Кастване към float
 * 
 * <code>
 * 		BASIC_URL::init()->request('myvar','Float',5)		
 * </code>
 * 
 * @package BASIC
 * @param псй $float
 * @return овдьш
 */
function Float($float){
	return (float)$float;
}
/**
 * 	HTML SECURITY.
 *	@todo 
 * 		[30-07-2007] fix ...="javascript:..."
 *  prohibit tag section "script","iframe" and "style"
 *  prohibit properties : every javascript event property
 *  prohibit use in style property url construction
 *  prohibit images's src parameter use url parameters 
 *  clean javascript executor "javascript[ ]*:"
 *
 *  @author Evgeni Baldzisky
 *  @version 0.5 beta
 *
 */
function htmlSecurity($str){
    
	$str = stripslashes($str);
	$str = preg_replace("/[\t]*<(script|style)[^>]*>[^<]*<\/(script|style)[^>]*>[\n\r]*/i","&nbsp;",$str);
	$str = preg_replace('/on[^= ]+[ ]*=[ ]*["\']?[^>]+/i','onerror="bed text"',$str);
	
	$str = preg_replace('/javascript[ ]*:/i', '#', $str);
	
	$str = preg_replace('/(style[ ]*=[ ]*["\']?.*)url[^;"]+;?(.*["\']?)/i', "$1$2", $str);

	$str = preg_replace_callback('/src=["\']?[^"\' ]*/i', "clearSrc", $str);
	//$str = preg_replace('/(<img.*src=["\']?[^\?]*)\?*[^"\' ]*(["\' ]?[^>]*>)/i',"$1$2",$str);
	$str = preg_replace("/[\t]*<(iframe|\.\.\.)[^>]*>[^<]*<\/(iframe|\.\.\.)[^>]*>[\n\r]*/i","&nbsp;",$str);

	return addslashes($str);
}
function clearSrc($match){
	$tmp = explode("?",$match[0],2);
	return $tmp[0];
}
function cleanHTMLT($longtext){
    return BASIC_GENERATOR::init()->getControl('html')->convertOut($longtext);
}

function charAdd($post){
	$post = charStrip($post);
	$post = strip_tags($post);
	$post = addslashes($post);

	return $post;
}
function charStrip($post){
	$post = str_replace('\\', '', $post);
	return $post;
}
function formatText($post){
	$post = stripcslashes($post);
	$post = nl2br($post);
	$post = smilesSopport($post);
	return $post;
}