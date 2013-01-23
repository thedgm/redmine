<?php
/**
 * @var BASIC_TEMPLATE2
 */
$GLOBALS['BASIC_TPL2'] = null;
/**
 * Интерфейс достъп до драйвърите. 
 */
interface BasicTemplateDriverInterface {
	function set($variable_name,$variable_value = array());
	function un_set($variable_name);
	function createTemplate($template_name, $source, $usecash = true);
	function parse($template_name, $scope = '', $vars = array());
	
	function getTemplateSource($template_name);
	function getCashTime($template_name);
	function getTemplateTime($template_name);
	
	function clearCash($name = '');
}
/**
 * Сервис реализиращ форматирането на показваната информация.
 *	Сервиза ползва интерфейса BasicTemplateDriverInterface за достъп до методите
 * 	на драйвъра на прикачената template система.
 * 
 * @author Evgeny Baldzisky
 * @version 0.1
 * @since 10.08.2009
 * @package BASIC.TEMPLATE 
 */
class BASIC_TEMPLATE2 implements BasicTemplateDriverInterface{
	/**
	 * Default driver object
	 *
	 * @var TemplateDriverBasic
	 */
	public $driver = null;
	/**
	 * Достъп до инстанцията на сервиза и настройка на драивъра.
	 * 	<code>
	 *		BASIC_TEMPLATE2::init(array(
	 *			'template_path' 	=> 'tpl/',
	 *			'prefix_ctemplate' 	=> 'cp_'
	 *		));
	 * 	</code>
	 * 
	 * @param array [$settings]
	 * @return BASIC_TEMPLATE2
	 */
	static function init($settings = array()){
		if(!$GLOBALS['BASIC_TPL2']){
			$GLOBALS['BASIC_TPL2'] = new BASIC_TEMPLATE2();
		}
		foreach ($settings as $k => $v){
			if($k == 'driver'){
				$GLOBALS['BASIC_TPL2']->driver = $v;
			}else{
				$GLOBALS['BASIC_TPL2']->driver->$k = $v;
			}
		}
		return $GLOBALS['BASIC_TPL2'];
	}
	/**
	 * @return BASIC_TEMPLATE2
	 */
	function __construct(){
		$this->driver = new TemplateDriverBasic();
	}
	/**
	 * Регистриране на променлива(и). 
	 *	<code>
	 * 		BASIC_TEMPLATE2->set('variable_name','variable_value');
	 * 			// OR
	 * 		BASIC_TEMPLATE2->set(array(
	 * 			'variable_name_1' => 'variable_value_1',
	 * 			'variable_name_2' => 'variable_value_2',
	 * 			'variable_name_3' => 'variable_value_3'
 	 * 		));
	 * 	</code>
	 *
	 * @param string $variable_name
	 * @param mix [$variable_value]
	 * @return BASIC_TEMPLATE2
	 */
	public function set($variable_name, $variable_value = '', $scope = ''){
		$this->driver->set($variable_name, $variable_value, $scope);
		return $this;
	}
	public function un_set($variable_name){
		$this->driver->un_set($variable_name);
	}	
	/**
	 * Взимане на форматираната информация.
	 * 	<code>
	 * 		print BASIC_TEMPLATE2->set('template_name.tpl');
	 * 	</code>
	 *
	 * @param string $template_name
	 * @return string
	 */
	public function parse($template_name, $scope = '', $vars = array()){
		return $this->driver->parse($template_name, $scope, $vars);
	}
	public function createTemplate($name, $source, $usecash = true){
		return $this->driver->createTemplate($name, $source, $usecash);
	}
	public function getTemplateSource($template_name){
		return $this->driver->getTemplateSource($template_name);
	}
	/**
	 * Clear db's(if the driver->method is 'db') template's list or the name element from this list.
	 * 
	 * @param string $name
	 */
	public function clearCash($name = ''){
		return $this->driver->clearCash($name);
	}
	function getCashTime($template_name){
		return $this->driver->getCashTime($template_name);
	}
	function getTemplateTime($template_name){
		return $this->driver->getTemplateTime($template_name);
	}	
}

/**
 * Итерфеис за достъп до плъгините.
 */
interface  BasicTemplatePluginInterface {
	/**
	 * convert source clauses
	 *
	 * @param string $source
	 * @return string
	 */
	function parse($source);	
}
/**
 * Плъгин обработващ "if" функционалност.
 */
class BasicTemplatePluginIf implements BasicTemplatePluginInterface{
	public function parse($source){
		return preg_replace_callback('/<!-- (elseif)\(([^\)]+)\) -->/',array($this,'_parse_ifelse'),
			preg_replace_callback('/<!-- (if)\(([^\)]+)\) -->/',array($this,'_parse_if'),
				str_replace('<!-- else -->','<?php }else{?>',$source)
			)
		);
	}
	
	private function _parse_if($match){
		return "<?php ".$match[1]."(".preg_replace_callback('/\$\{([^\}]+)\}/','TemplateDriverBasic::translate_collback', $match[2])."){ ?>";
	}
	private function _parse_ifelse($match){
		return "<?php }".$match[1]."(".preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::translate_collback', $match[2])."){ ?>";
	}
}
/**
 * Плъгин обработващ "for" функционалност.
 */
class BasicTemplatePluginFor implements BasicTemplatePluginInterface{
	public function parse($source){
		return preg_replace_callback('/<!-- (for)\(([^\)]+)\) -->/',array($this,'_parse'),$source);
	}
	
	private function _parse($match){
		return "<?php ".$match[1]."(".preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::translate_collback', $match[2])."){ ?>";
	}
}
/**
 * Плъгин обработващ "foreach" функционалност.
 * 	<code>
 * 		<!-- foreach(${array},variable_key,variable_value) -->
 * 
 * 		<!-- end -->
 * 			OR
 * 		<!-- foreach(${array} as $variable_key => $variable_value) -->
 * 
 * 		<!-- end -->
 * 	</code>
 */
class BasicTemplatePluginForeach implements BasicTemplatePluginInterface{
	public function parse($source){
		return preg_replace_callback('/<!-- (foreach)\(([^\)]+)\) -->/',array($this,'_parse'),$source);
	}
	
	private function _parse($match){
		$match[2] = preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::translate_collback', $match[2]);
		
		$declarations = preg_split('/[ ]*( as |,|=>)[ ]*/',$match[2]);
		
		$build = '';
		$build .= '(is_array('.$declarations[0].') ? '.$declarations[0].' : array()) as ';
		$build .= '$'.preg_replace('/[\$@]/','',$declarations[1]).' ';
		if(isset($declarations[2])){
			$build .= '=> $'.preg_replace('/[\$@]/','',$declarations[2]).' ';
		}
		
		return "<?php ".$match[1]."(".$build."){ ?>";
	}
}
/**
 * Плъгин обработващ "image" функционалност. Позволява на темблейта да задава карета в който да се 
 * разполагат картинките като ги оразмерява пропорционално.
 * 	<code>
 * 		template ::
 * 			<div><!-- image(${image_data},width=150|height=230|style=border:1px solid #AA0000;) --></div>
 * 
 * 		$image_data :: 'upload/mypictures_folder/mypicture.jpg'
 * 	</code>
 */
class BasicTemplatePluginImage implements BasicTemplatePluginInterface{
	public function parse($source){
		return preg_replace_callback('/<!-- (image)\((.+)\) -->/', array($this, '_parse'), $source);
	}
	
	private function _parse($match){
		$spl = explode(',',$match[2]);
		
		$build = '';
		if(!preg_match('/\$\{[^\}]+\}/', $spl[0])){
			$build = "'".str_replace("'", "", $spl[0])."'";
		}else{
			$build = preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::translate_collback', $spl[0]);
		}
		if(isset($spl[1])){
			$spl[1] = ", '".preg_replace('/[\'"]/', "\\'", $spl[1])."'";
			$build .= preg_replace_callback('/\$\{([^\}]+)\}/', array($this, 'translate_collback'), $spl[1]);
		}
		
		return "<?php print BasicTemplatePluginImage::parser(".$build."); ?>";
	}
	static public function translate_collback($match){
		
		return "'.".TemplateDriverBasic::translate_collback($match).".'";
	}
	/**
	 * Темплеитна функция. Тя ще се вика от компилирания темплейт.
	 *
	 * @param array/string $namePath
	 * @param string $attributes
	 * @return string
	 */
	static public function parser($namePath,$attributes = ''){
		$attributes = BASIC_GENERATOR::init()->convertStringAtt($attributes);
		if(!is_array($namePath)){
			$namePath = array('',$namePath);
		}
		if(!isset($attributes['folder'])){
			$attributes['folder'] = (isset($namePath[0]) ? $namePath[0] : '');
		}
		return BASIC_GENERATOR::init()->image((isset($namePath[1]) ? $namePath[1] : ''),$attributes);
	}
}
/**
 * 
 */
class BasicTemplatePluginTemplate implements BasicTemplatePluginInterface{
	public function parse($source){
		//return preg_replace('/<!-- template\(([^\)]+)\) -->/', "<?php echo BASIC_TEMPLATE2::init()->parse('$1','',\$__VARS__); ? >", $source);
		return preg_replace_callback('/<!-- template\(([^\)]+)\) -->/', array($this, '_parse'), $source);
	}
	private function _parse($match){
		$spl = explode(',',$match[1]);
		
		$build = '';
		if(!preg_match('/\$\{[^\}]+\}/', $spl[0])){
			$spl[0] = "'".str_replace("'", "", $spl[0])."'";
		}else{
			$spl[0] = preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::translate_collback', $spl[0]);
		}
		if(isset($spl[1])){
			if(!preg_match('/\$\{[^\}]+\}/', $spl[1])){
				//@TODO need to convert basic style parameters in vlaid php array
			}else{
				$spl[1] = preg_replace_callback('/\$\{([^\}]+)\}/', array($this, 'translate_collback'), $spl[1]);
			}
		}else{
			$spl[1] = '';
		}
		
		return "<?php \$__local_vars__ = \$__VARS__; ".($spl[1] ? "foreach(".$spl[1]." as \$tk => \$tv) \$__local_vars__[\$tk] = \$tv; ":"")."echo BASIC_TEMPLATE2::init()->parse(".$spl[0].",'',\$__local_vars__); unset(\$__local_vars__); ?>";
	}
	static public function translate_collback($match){
		
		return TemplateDriverBasic::translate_collback($match);
	}
}
/**
 * Плъгин обработващ обслузващ таг <!-- menu(име на менюто, име на темплеит) -->.
 * За всяко вложено ниво се вика показания темплйт, като му се подава масив в променлива с име "nodes".
 * 
 * Карта на елемент от масива "nodes":
 * 	title 		- текст на бутона
 * 	href 		- хипер линк на бутона
 * 	selected 	- марка дали е текущ бутон в контекста на навигацията
 * 	target 		- къде да се отвори при натискани бутона
 * 	onclick		- дали да се отвори в изкачащ прозорец (това своиство е свързано с "target" така, че само един от двата е предоставин)
 * 	childs		- HTML блок генериранй от по вътрешните нива.
 * 	... 		- други параметри в зависимост от наличните "fields" на "navigationManager" компонента.
 * 
 * Начин за ползване
 * 	!) Основния темплеит
 * 		<td valign="top">
 *			<!-- menu(${left_menu},regursion_menu.tpl) -->
 *		</td>
 *	!) Темплейт изпозван за рекурсията ( в случая се изработва списък )
 *		<ul class="menu vertical">
 *			<!-- foreach(${nodes},v) -->
 *				<li><a href="${v['href']}" <!-- if(${v['target']}) -->target="${v['target']}"<!-- elseif(${v['onclick']}) -->onclick="${v['onclick']}"<!-- end --><!-- if(${v['selected']}) --> style="color:#FF0000;"<!-- end -->>${v['title']}</a></li>
 *				${v['childs']}
 *			<!-- end -->
 *		</ul>
 * 
 * @author Evgeni Baldzisky
 * @version 0.1 final
 * @since 10.12.2009
 * @package BASIC.TEMPLATE
 */
class BasicTemplatePluginMenu implements BasicTemplatePluginInterface{
	/**
	 * Изпълнява се при компилирането. 
	 *
	 * @param string $source
	 * @return string
	 */
	public function parse($source){
		return preg_replace_callback('/<!-- (menu)\(([^\)]+),([^\)]+)\) -->/',array($this,'_parse'),$source);
	}
	/**
	 * Използва се от "preg_replace_callback".
	 * 
	 * @param array $match
	 */
	private function _parse($match){
		return "<?php print BasicTemplatePluginMenu::parser(".preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::translate_collback', $match[2]).",'".$match[3]."'); ?>";
	}	
	/**
	 * Темплеитна функция. Тя ще се вика от компилирания темплейт.
	 *
	 * @param int $name
	 * @return string
	 */
	static public function parser($rec, $template_name){
		return self::_parser(is_array($rec) ? $rec : array(), $template_name);
	}
	/**
	 * Рекурсивно обхождоне на подадения масив и генериране на HTML блоковете.
	 * 
	 * @param array $rec ( - колекция от елементи
	 * 		data array() 	- hesh от променливи
	 * 		childs array() 	- колекция от елементи на следващите нива.
	 * )
	 * @param string $template_name
	 */
	static protected function _parser($rec, $template_name){
		$tmp = array();
		foreach($rec as $v){
			$length = count($tmp);
			
			$tmp[$length] = $v['data'];
			$tmp[$length]['childs'] = array();
			
			if(isset($v['childs'])){
				$tmp[$length]['childs'] = self::_parser($v['childs'],$template_name);
			}
		}
		if($tmp){
			BASIC_TEMPLATE2::init()->set('nodes', $tmp, $template_name);
			return BASIC_TEMPLATE2::init()->parse($template_name);
		}
		return '';
	}
}
/**
 * 
 */
class BasicTemplatePluginLingual implements BasicTemplatePluginInterface{
	/**
	 * Изпълнява се при компилирането. 
	 *
	 * @param string $source
	 * @return string
	 */
	public function parse($source){
		return preg_replace('/<!-- (lingual)\(([^\)]+)\) -->/',"<?php print BasicTemplatePluginLingual::parser('$2'); ?>",$source);
	}
	/**
	 * Темплеитна функция. Тя ще се вика от компилирания темплейт.
	 *
	 * @param int $name
	 * @return string
	 */
	static public function parser($name){
		if(class_exists('BASIC_LANGUAGE')){
			return BASIC_LANGUAGE::init()->get($name);
		}
		return '';
	}
}
/**
 * Стандартния форматиращ механизъм на BASIC.
 * 	<code>
 * 		template ::
 * 	</code>
 * 
 * @author Evgeni Baldzhiyski
 * @version 2.0.0 (beta)
 */
class TemplateDriverBasic implements BasicTemplateDriverInterface{
	/**
	 * Flag that say where is the template's list.
	 * The options are:
	 * 		disk - on file system
	 * 		db - on data base system
	 */
	public $method = 'disk'; /*'db';*/
	protected $templatez_list_db_cash = array();
	/**
	 * Път до темплейтите
	 *
	 * @var string
	 */
	public $template_path = '';
	/**
	 * Път до папката за компилирани темплейти.
	 *
	 * @var string
	 */
	public $template_cpath = '';
	/**
	 * Представка за името на компилираните темплейти.
	 *
	 * @var string
	 */
	public $prefix_ctemplate = '';
	/**
	 * Ниво на валидиране на съдаржанието на емплейта. Нивата на валидация са:
	 * 	0 - не се прави валидация
	 * 	1 - не се позволява да се пише PHP код
	 * 
	 * @var int
	 */
	public $level_strict = 1;
	
	private $template = array();
	private $variables = array(
		'' => array()
	);
	private $plugins = array();
	
	private $dynamic_templates = array();
	/**
	 * @return TemplateDriverBasic
	 */
	function __construct(){
		$this->plugins = array(
			'BasicTemplatePluginIf' 	  => new BasicTemplatePluginIf(),
			'BasicTemplatePluginFor' 	  => new BasicTemplatePluginFor(),
			'BasicTemplatePluginForeach'  => new BasicTemplatePluginForeach(),
			'BasicTemplatePluginImage' 	  => new BasicTemplatePluginImage(),
			'BasicTemplatePluginMenu' 	  => new BasicTemplatePluginMenu(),
			'BasicTemplatePluginLingual'  => new BasicTemplatePluginLingual(),
			'BasicTemplatePluginTemplate' => new BasicTemplatePluginTemplate()
		);
		$this->template_path = BASIC::init()->ini_get('template_path');
		$this->template_cpath = BASIC::init()->ini_get('temporary_path');
	}
	/**
	 * Регистриране на плъгин.
	 *
	 * @param string $name
	 * @param object $object
	 */
	public function addPlugin($name, $object){
		$this->plugins[$name.(isset($this->plugins[$name]) ? '_2' : '')] = $object;
	}	
	/**
	 * Премахване на преди това регистреран плъгин.
	 *
	 * @param string $name
	 */
	public function delPlugins($name){
		if(isset($this->plugins[$name])) unset($this->plugins[$name]);
	}
	/**
	 * clear different cashes: used db templates, ... 
	 */
	function clearCash($name = ''){
		if(!$name){
			$this->templatez_list_db_cash = array();
		}else{
			unset($this->templatez_list_db_cash[$name]);
		}
	}
	/**
	 * Регистриране на променливи. Ако се окаже област тогава тези променливи са с по висок
	 * приоритет пред регистрираните без област.
	 *
	 * @param mix $variable_name
	 * @param mix [$variable_value]
	 * @param string [$scope]
	 */
	public function set($variable_name,$variable_value = '',$scope = ''){
	
		if(is_array($variable_name)){
			$scope = (string)$variable_value;
			foreach ($variable_name as $k => $v){
				$this->_set($k,$v,$scope);	
			}
		}else{
			$this->_set($variable_name,$variable_value,$scope);
		}	
	}
	/**
	 * Помощен метод на "set".
	 *
	 * @param string $variable_name
	 * @param mix $variable_value
	 * @param string $scope
	 */
	protected function _set($variable_name, $variable_value, $scope){
		if(!isset($this->variables[$scope])){
			$this->variables[$scope] = array();
		}
		$this->variables[$scope][$variable_name] = $variable_value;
	}
	/**
	 * Премахване на регистрирана променлива.
	 *
	 * @param string $variable_name
	 */
	public function un_set($variable_name,$scope = ''){
		if(is_array($variable_name)){
			foreach($variable_name as $v){
				$this->_un_set($v,$scope);
			}
		}else{
			$this->_un_set($variable_name,$scope);
		}
	}
	/**
	 * Помощен метод на "un_set".
	 *
	 * @param string $variable_name
	 */
	protected function _un_set($variable_name, $scope){
		if(isset($this->variables[$scope][$variable_name])){
			unset($this->variables[$scope][$variable_name]);
		}
	}
	public function createTemplate($template_name, $source, $usecash = true){
		if(!$usecash || ($usecash && !$this->checker($template_name))){
			$this->dynamic_templates[$template_name] = 1;
			
			$file = fopen(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$template_name.'.php', 'w');
			fwrite($file, $this->copiler($source));
			fclose($file);
		}
	}
	public function getTemplateSource($template_name){
		$buffer = '';		
		if($this->method == 'db'){
			if($res = BASIC_SQL::init()->read_exec(" SELECT * FROM `".$this->template_path."` WHERE `name` = '".$template_name."' ", true)){
				$buffer = $res['body'];
			}
		}else{
			if($file = @fopen(BASIC::init()->ini_get('root_path').$this->template_path.'/'.$template_name,'r')){
				while (!@feof($file)) {
					$buffer .= fread($file, 1024);
				}
				@fclose($file);
			}
		}
		if(!$buffer){
			throw new Exception("File ".$template_name."(".BASIC::init()->ini_get('root_path').$this->template_path.'/'.") no exist!"); return '';
		}
		return $buffer;
	}
	/**
	 * Взимане на обработения темплейт.
	 *
	 * @param string $template_name
	 * @return string
	 */
	private $_tmp_tpl_name = '';
	public function parse($template_name, $scope = '', $vars = array()){
		if(!$this->checker($template_name)){
			$buffer = '';
			
			if($this->method == 'db'){
				$buffer = $this->templatez_list_db_cash[$template_name]['body'];
			}else{
				if(!$file = @fopen(BASIC::init()->ini_get('root_path').$this->template_path.'/'.$template_name,'r')){
					throw new Exception("File ".$template_name."(".BASIC::init()->ini_get('root_path').$this->template_path.'/'.") no exist!"); return '';
				}
				while (!@feof($file)) {
					$buffer .= fread($file, 1024);
				}
				@fclose($file);
			}
			$file = fopen(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$template_name.'.php','w');
			fwrite($file, $this->copiler($buffer));
			fclose($file);
			
			unset($buffer);
			unset($file);
		}
		if(!$scope) $scope = $template_name;
		if(!isset($this->variables[$scope])){
			$this->variables[$scope] = array();
		}
	
		foreach ($this->variables[''] as $k => $v){
			$$k = $v;
		}
		foreach ($vars as $k => $v){
			$$k = $v;
		}
		foreach ($this->variables[$scope] as $k => $v){
			$$k = $v;
		}
		$this->_tmp_tpl_name = $template_name;
		
		// system variable for template's extend support
		$__VARS__ = $vars + $this->variables[$scope];		
		
		unset($scope);
		unset($template_name);
		
		$VIRTUAL = BASIC::init()->ini_get('root_virtual');
		$ROOT	 = BASIC::init()->ini_get('root_path');
		
		ob_start();
		
		require(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$this->_tmp_tpl_name.'.php');
		
		return ob_get_clean();
	}
	/**
	 * if exist template's cash return last modify tile or -1
	 * 
	 * @param String $template_name
	 * @return Integer
	 */
	function getCashTime($template_name){
		if(file_exists(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$template_name.'.php')){
			return @filemtime(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$template_name.'.php');
		}else{
			return -1;
		}
	}
	function getTemplateTime($template_name){
		if($this->method == 'db'){
			$this->checker($template_name);
			if(isset($this->templatez_list_db_cash[$template_name]['mdate'])) return $this->templatez_list_db_cash[$template_name]['mdate'];
		}else{
			if(file_exists(BASIC::init()->ini_get('root_path').$this->template_path.'/'.$template_name)){
				return @filemtime(BASIC::init()->ini_get('root_path').$this->template_path.'/'.$template_name);
			}
		}
		return -1;
	}
	/**
	 * @param string $template_name
	 * @return boolen
	 */
	private function checker($template_name){
		if($this->method == 'db'){
			if(!isset($this->templatez_list_db_cash[$template_name])){
				$this->template_path = str_replace("/", "_", $this->template_path);
				
				if($res = BASIC_SQL::init()->read_exec(" SELECT * FROM `".$this->template_path."` WHERE `name` = '".$template_name."' ", true)){
					$name = $res['name']; unset($res['name']);
					$this->templatez_list_db_cash[$template_name] = $res;
					unset($res);
					
					return false;
				}
				
				$err = BASIC_ERROR::init()->error();
				if($err['code'] == 1146){
					BASIC_SQL::init()->createTable('id', $this->container, "
						  `name` varchar(255) NOT NULL default '',
						  `body` longtext,
						  `mdate` int(15)',
						  UNIQUE KEY `name` (`name`)
					");
					BASIC_ERROR::init()->clean();
					return $this->checker($template_name);
				}else{
					if(!isset($this->dynamic_templates[$template_name])){
						throw new Exception("File ".$template_name."(".BASIC::init()->ini_get('root_path').$this->template_path.'/'.") no exist!");
						return false;
					}
				}
			}
			$mdate = 0;
			if(isset($this->templatez_list_db_cash[$template_name]['mdate'])){
				$mdate = $this->templatez_list_db_cash[$template_name]['mdate'];
			}
			if(@filemtime(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$template_name.'.php') <= $mdate) return false;
		}else{
			$ttime = @filemtime(BASIC::init()->ini_get('root_path').$this->template_path.'/'.$template_name);
			if(!isset($this->dynamic_templates[$template_name]) && $ttime === false){
				throw new Exception("File ".$template_name."(".BASIC::init()->ini_get('root_path').$this->template_path.'/'.") no exist!");
				return false;
			}
			if(@filemtime(BASIC::init()->ini_get('root_path').$this->template_cpath.'/'.$this->prefix_ctemplate.$template_name.'.php') <= $ttime) return false;
		}
		return true;
	}
	/**
	 * Компилиране на темплейта. Приоритет при компилирането:
	 * 		регистрираните плъгини
	 * 		<!-- end --> декларацийте
	 * 		променливи о тип: ${име} или ${масив.ниво.ниво.(...)
	 * 		При ниво на кеширане 0: парсват се <!-- template(име) -->. По-подразбирани нивото е 1 т.е. тези зеклараций се 
	 * 			парсват при парсвани на съдържащия ги темплеит. Тази операция е по-бавна. 
	 *
	 * @param string $source
	 * @return string
	 */
	private function copiler($source){
		if($this->level_strict == 1){
			$source = preg_replace('/<\?(php)?/i', '<?php /*', $source);
			$source = preg_replace('/\?>/', '*/ ?>', $source);
		}
		foreach ($this->plugins as $plugin){
			$source = $plugin->parse($source);
		}
		$source = str_replace('<!-- end -->','<?php }?>',$source);
		$source = preg_replace_callback('/\$\{([^\}]+)\}/', 'TemplateDriverBasic::php_translate_collback', $source);
		
		return $source;
	}
	/**
	 * Помощен метод на "template_collback"
	 *
	 * @param array $match
	 * @return string
	 */
	static public function php_translate_collback($match){
		return "<?php echo @$".self::varArraySupport($match[1]).";?>";
	}
	static public function translate_collback($match){
		return "@$".self::varArraySupport($match[1]);
	}
	static public function varArraySupport($var){
		$var_array_check = explode(".", $var);
		if(count($var_array_check) > 1){
			$var = '';
			foreach($var_array_check as $v){
				if(!$var){
					$var = $v; continue;
				}
				$var .= "['".$v."']";
			}
		}
		return $var;
	}
}