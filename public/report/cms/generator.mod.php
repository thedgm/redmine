<?php
BASIC::init()->imported('xml.mod');

interface PageControlInterface{
	/**
	 * @param String/Integer $value
	 * @return String/Integer
	 */
	function convertIn($value);
	/**
	 * @param String/Integer $value
	 * @return String/Integer
	 */
	function convertOut($value);
	/**
	 * @param String $name
	 * @param String/Integger $value
	 * @param HashMap $attributes
	 * @return String
	 */
	function generate($name, $value, $attributes = array());
	/**
	 * @return Boolean
	 */
	function isMultiple();
	function isFileUpload();
}
class BasicControl implements PageControlInterface{
	/**
	 * @var HashMap
	 */
	protected $attributes = array();
	/**
	 * @var HashMap
	 */
	protected $data = array();
	
	protected function init($name, $value, $attributes = array()){
		$this->attributes = BASIC_GENERATOR::init()->convertStringAtt($attributes);
		$name = BasicControl::uId($name);

		if($name){
			$this->attributes['name'] = $name;
		}
		if(!isset($this->attributes['id']) && $name){
			$this->attributes['id'] = $name;	
		}
		$this->attributes['value'] = $value;
		
		if(isset($this->attributes['data'])){
			$this->data = $this->attributes['data'];
			unset($this->attributes['data']);
		}
	}
	
	function generate($name, $value, $attributes = array()){
		$this->init($name, $value, $attributes);

		return BASIC_GENERATOR::init()->createCloseTag('input', $this->attributes)."\n";
	}
	function convertIn($value){
		return $value;
	}
	function convertOut($value){
		return $value;
	}
	function isMultiple(){
		return false;
	}
	function isFileUpload(){
		return false;
	}
	
	static private $_uid = 0;
	static public function uId($name = ''){
		if($name == '' || $name == null){
			$name .= "basecontrol_".(self::$_uid++);
		}
		return $name;
	}
}
class PasswordControl extends BasicControl implements PageControlInterface{
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);
		
		$this->attributes['type'] = 'password';
		$this->attributes['value'] = '';
		
		return BASIC_GENERATOR::init()->createCloseTag('input', $this->attributes)."\n";
	}
}
class CheckBoxControl extends BasicControl implements PageControlInterface{
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);
		
		$this->attributes['type'] = 'checkbox';
		$this->attributes['value'] = 1;
		
		if($value) $this->attributes['checked'] = 'checked';
		
		return BASIC_GENERATOR::init()->createCloseTag('input', $this->attributes)."\n";
	}
}
class TextareaControl extends BasicControl implements PageControlInterface{
	/* (non-PHPdoc)
	 * @see PageControlInterface::generate()
	 */
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);

		if(isset($this->attributes['maxlength']) && (int)$this->attributes['maxlength']){
		    $length = (int)$this->attributes['maxlength'];
		    if(!isset($this->attributes['id'])){
		        $name = $this->attributes['id'] = uniqid();
		    }
		}
		return BASIC_GENERATOR::init()->createTag('textarea', $this->attributes, $value);
	}
}
class HtmlControl extends BasicControl implements PageControlInterface{
	/* (non-PHPdoc)
	 * @see PageControlInterface::convertOut()
	 */
	public function convertOut($value) {
		$tmp = $value;
		$tmp = stripslashes($value);
		$tmp = str_replace("[HOST]", BASIC::init()->ini_get('root_virtual'), $tmp);

		return $tmp;
	}
	/**
	 * Special attribute :
	 * 	rows -> height textarea
	 * 	cols -> weight textarea
	 *  css  -> extermal style file for textarea
	 *  skin -> folder with formating declaration and images
	 *  save ->
	 *  buttons = [name1,name2,name3,nameN] open buttons
	 *  valid
 	 * 		btnPreview,btnFullScreen,btnPrint,btnSearch,btnSpellCheck,btnTextFormatting,,btnXHTMLSource
	 *	    btnListFormatting,btnBoxFormatting,btnParagraphFormatting,btnCssText,btnStyles,btnParagraph,btnClearAll,
	 *	    btnFontName,btnFontSize,btnCut,btnCopy,btnPaste,btnUndo,btnRedo,btnBold,btnItalic,btnUnderline,
	 *	    btnStrikethrough,btnSuperscript,btnSubscript,btnJustifyLeft,btnJustifyCenter,btnJustifyRight,
	 *	    btnJustifyFull,btnNumbering,btnBullets,btnIndent,btnOutdent,btnLTR,btnRTL,btnForeColor,btnBackColor,
	 *	    btnHyperlink,btnBookmark,btnCharacters,btnCustomTag,btnImage,btnFlash,btnMedia,btnTable,btnGuidelines,
	 *	    btnAbsolute,btnPasteWord,btnLine,btnForm,btnClean,btnHTMLFullSource,btnHTMLSource,btnXHTMLFullSource
	 *	disabled
	 * 	assetmanager tool for uploaded images
	 * 
	 * @see PageControlInterface::generate()
	 */
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);

		unset($this->attributes['id']);
		unset($this->attributes['name']);
		unset($this->attributes['value']);
		unset($this->attributes['cols']);
		unset($this->attributes['rows']);
		
		$css = ''; if(isset($this->attributes['css'])){ 
			$css = $GLOBALS['BASIC']->ini_get('root_virtual') . $this->attributes['css'];
			unset($this->attributes['css']);
		}

		$value = stripslashes($value);
		$value = preg_replace("/[\r\n\t]/", "", $value);
		//$value = str_replace(">", "&gt;", $value);
		//$value = str_replace("<", "&lt;", $value);
		$value = str_replace("'", "\\'", $value);

		$tmp = '';

		BASIC_GENERATOR::init()->head('HTMLTextarea', 'script', array('src' => BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'scripts/htmltextarea/bin.js'), ' ');
		
		$tmp .= "\n".BASIC_GENERATOR::init()->createCloseTag('input', array('name' => $name, 'type' => 'hidden', 'id' => $name));

		$manager = BASIC::init()->ini_get('root_virtual').BASIC::init()->dirName().BASIC::init()->scriptName().'?editor=assetmanager';
		
		if(isset($this->attributes['assetManager'])){
			$manager = $this->attributes['assetManager'];
			unset($this->attributes['assetManager']);
		}
		$browser = strpos($_SERVER["HTTP_USER_AGENT"], "MSIE");
		if(isset($this->attributes['width'])){
			if(strpos($this->attributes['width'], '%') === false){
				$this->attributes['width'] -= ($browser ? 10 : 0);
			}
		} 
		if(isset($this->attributes['height'])){
			if(strpos($this->attributes['height'], '%') === false){
				$this->attributes['height'] -= ($browser ? 70 : 0);
			}
		} 
		
		$attrs = '';
		foreach($this->attributes as $k => $v){
			if($attrs) $attrs .= ',';
			
			$attrs .= '"'.$k.'":"'.$v.'"';
		}
		$scr = '
			var target = $("#'.$name.'"),
				editor = oUtil.initHTMLTextarea({
					cmdAssetManagerPath: "'.BASIC::init()->ini_get('basic_path').'scripts/assetmanager/assetmanager.php",
					attrs: {'.$attrs.'},
					publishingPath: "'.BASIC::init()->ini_get('root_virtual').'",
					cmdAssetManager: "oUtil.modalDialogShow(\''.$manager.'\', 640, 445)",
					btnStyles: true,
					css: "'.$css.'"
				});';
		if(isset($this->attributes['buttons'])){
			foreach (explode(";",$this->attributes['buttons']) as $v){
				if(!$v) continue;
				
				$ex = explode(":", $v);
				$scr .= '	editor.'.$ex[0].'='.$ex[1].';';
			}
		}
		if(isset($this->attributes['save'])){
			$scr .= '	
				editor.btnSave=true;
				editor.onSave=function(){
					'.$this->attributes['save'].'
					thisForm.form.submit();;
				};';
		}
		$scr .= '	
			$(target.after(editor.MARKUP()).get(0).form).submit(function (){
				target.attr("value", editor.getXHTMLBody());
			});
			editor.RENDER(\''.$value.'\');';
		
		if(isset($this->attributes['disabled'])){
			$scr .= '
			editor.disabledElement(1);'."\n";
		}
		BASIC_GENERATOR::init()->head("HTMLTextarea_ctrl_".$name, 'script', null, '$(document).ready(function (){'.$scr.'});');
	
		return $tmp;
	}
}
class DateControl extends BasicControl implements PageControlInterface{
	/**
	 * form element date.Used ISO time standart {yyyy-mm-dd hh:mm:ss}
	 * new attribute
	 * 	disabled = [true|1]
	 *  dkey = true[false] view key checkbox
	 * 	format - [%Y-%m-%d %H:%M %p]
	 *
	 *	 ("inputField",null);
	 *	 ("displayArea",null);
	 *	 ("button",null);
	 *	 ("eventName","click");
	 *	 ("ifFormat","%Y/%m/%d");
	 *	 ("daFormat","%Y/%m/%d");
	 *	 ("singleClick",true);
	 *	 ("disableFunc",null);
	 *	 ("dateStatusFunc",params["disableFunc"]);
	 *	 ("dateText",null);
	 *	 ("firstDay",null);
	 *	 ("align","Br");
	 *	 ("range",[1900,2999]);
	 *	 ("weekNumbers",true);
	 *	 ("flat",null);
	 *	 ("flatCallback",null);
	 *	 ("onSelect",null);
	 *	 ("onClose",null);
	 *	 ("onUpdate",null);
	 *	 ("date",null);
	 *	 ("showsTime",false);
	 *	 ("timeFormat","24");
	 *	 ("electric",true);
	 *	 ("step",2);
	 *	 ("position",null);
	 *	 ("cache",false);
	 *	 ("showOthers",false);
	 *	 ("multiple",null);
	 *
	 * 	skin [default is basic_path/scripts/calendar/skins/]
	 * @see PageControlInterface::generate()
	 */
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);
		
		$tmp = '';

		$this->attributes['type'] = 'text';
		
		$oName = $name;
		$oName = str_replace("#", '', $oName);
		$oName = str_replace("[]", self::uId(), $oName);
		
		if(!isset($this->attributes['format'])){
			$format = '%Y-%m-%d %H:%M %p';
		}else{
			$format = $this->attributes['format'];
			unset($this->attributes['format']);
		}
		if(!$value || !(int)preg_replace('/[^0-9]*/','',$value)){
			$value = strftime($format);
		}else{
			$value = strftime($format,strtotime($value));
		}
		if(!isset($this->attributes['class'])){
			$this->attributes['class'] = 'formDate '.$oName;
		}else{
			$this->attributes['class'] = $this->attributes['class'].' formDate '.$oName;
		}
		if(isset($this->attributes['skin'])){
			$this->head('scin','link',"media=all|href=".$this->attributes['skin']);
			unset($this->attributes['skin']);
		}else{
			BASIC_GENERATOR::init()->head('scin', 'link', "media=all|href=".BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path')."scripts/calendar/skins/theme.css");
		}		
		if(isset($this->attributes['disabled'])){
		    if($this->attributes['disabled'] && !BASIC_URL::init()->request($name)){
			     $tmp .= 'd.lock(true);'."\n";
		    }
			unset($this->attributes['disabled']);
		}
		if(isset($this->attributes['dkey'])){
			$tmp .= 'd.openKeyBtn('.($this->attributes['dkey'] ? 'true' : 'false').');'."\n";
			unset($this->attributes['dkey']);
		}

		if(!isset($this->loadscripts['formDate'])){
			BASIC_GENERATOR::init()->head('calendar', 'script',array("type"=>"text/javascript", "src"=>BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path')."scripts/calendar/calendar.js")," ");
			BASIC_GENERATOR::init()->head('calendar_d', 'script',array("type"=>"text/javascript", "src"=>BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path')."scripts/calendar/setup.js")," ");

			BASIC_GENERATOR::init()->head('calendar_l','script',array("type"=>"text/javascript", "src"=>BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path')."scripts/calendar/formdate.js")," ");
			$this->loadscripts['formDate'] = true;
		}
		if(isset($this->attributes['id'])){
			unset($this->attributes['id']);
		}		
		BASIC_GENERATOR::init()->head('calendar_c_'.$name, 'script', array("type"=>"text/javascript"),"
			$(document).ready(function (){
				var d = formDate('".$name."','".$value."','".$format."','".BASIC_GENERATOR::init()->convertAtrribute($this->attributes)."');
				".$tmp."
			});	
		");
			
		return BASIC_GENERATOR::init()->createCloseTag('input', $this->attributes+array('value' => $value, 'id' => $name))."\n";
	}
}
class SelectControl extends BasicControl implements PageControlInterface{
	/* (non-PHPdoc)
	 * @see BasicControl::generate()
	 */
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);
		
		if($name){
			$this->attributes['name'] = (isset($this->attributes['multiple']) ? $name.'[]' : $name);
			if(!isset($this->attributes['id'])) $this->attributes['id'] = $name;
		}
		
		if(isset($this->attributes['multiple'])){
			if(isset($this->attributes['class'])){
				$this->attributes['class'] .= " multiple";
			}else{
				$this->attributes['class'] = "multiple";
			}
		}
		unset($this->attributes['value']);
		unset($this->attributes['maxlength']);
		
		$tmp = '';
		$tmp .= BASIC_GENERATOR::init()->createOpen('select', $this->attributes);
		foreach ($this->data as $key => $txt){
			
			if(is_array($txt)){
				$txt = $txt[0];
			}			
			
			$att['value'] = $key;
			if(preg_match("/^%/",$key)){
				$att['style'] = 'color:#C6C5C4;';
			}else if(preg_match("/^GROUP::(.+)$/",$txt,$ext)){
				$tmp .= BASIC_GENERATOR::init()->createOpen('optgroup', 'label='.$ext[1]);
				continue;
			}else if($txt == "ENDGROUP"){
				$tmp .= BASIC_GENERATOR::init()->createClose('optgroup');
				continue;
			}else{
				if(isset($att['style'])) unset($att['style']);
			}
			if(isset($this->attributes['multiple'])){
				if(!is_array($value)){
					$value = array($value);
				}
				foreach($value as $v){
					if(
    				    ($key && $key == $v) || 
    				    (!$key && !$v && is_numeric($key) && is_numeric($v)) || 
    				    (!$key && !$v && is_string($key) && is_string($v))
					){
						$att['selected'] = 'selected';
						break;
					}
				}
			}else{
				if(
				    ($key && $key == $value) || 
				    (!$key && !$value && is_numeric($key) && is_numeric($value)) || 
				    (!$key && !$value && is_string($key) && is_string($value))
				){
				    $att['selected'] = 'selected';
				}
			}
			$tmp .= BASIC_GENERATOR::init()->createTag('option', $att, $txt);
			unset($att['selected']);
		}
		$tmp .= BASIC_GENERATOR::init()->createClose('select');

		return $tmp."\n";
	}
	function isMultiple(){
		if(isset($this->attributes['multiple']) && $this->attributes['multiple']){
			return true;
		}
		return false;
	}
}
class MultySelectControl extends SelectControl implements PageControlInterface{
	public function generate($name, $value, $attributes = array()) {
		$attributes['multiple'] = 'multiple';
		
		return parent::generate($name, $value, $attributes);
	}
	function isMultiple(){
		return true;
	}
}
class RadioBoxGroupControl extends SelectControl implements PageControlInterface{
	/**
	 * special attributes
	 * 	vmode => true|false [false]
	 * 
	 * @see BasicControl::generate()
	 */
	public function generate($name, $value, $attributes = array()) {
		$this->init($name, $value, $attributes);
		
		$vmode = false;
		if(isset($this->attributes['vmode'])){
			$vmode = $this->attributes['vmode'];
			unset($this->attributes['vmode']);
		}

		$attTmp = array();
		$attTmp['name'] = $name;
		$attTmp['type'] = 'radio';
		
		if(isset($this->attributes['disabled']) && $this->attributes['disabled']){
			$attTmp['disabled'] = 'disabled';	
		}
		// @TODO this not work corectlly in IE
		if(isset($this->attributes['readonly']) && $this->attributes['readonly']){
			$attTmp['onclick'] = 'return false';
		}

		$tmp = '';
		$check = false;
		foreach ($this->data as $val => $txt){
		    $attTmp['id'] = uniqid();
			$attTmp['value'] = $val;
			
			if($value == $val || (!$value && !$check)){
				$attTmp['checked'] = 'checked';
				$check = true;
			}
			$tmp .= BASIC_GENERATOR::init()->element('div', 'style='.(!$vmode ? '' : '').'|class=radioBox_Item',//float:left|
				BASIC_GENERATOR::init()->createCloseTag('input', $attTmp).' <label for="'.$attTmp['id'].'" class="radioBox_label">'.$txt.'</label>'
			);
			unset($attTmp['checked']);
		}
		$this->attributes = BASIC_GENERATOR::init()->convertStringAtt($this->attributes);
		if(isset($this->attributes['id'])){
			$name = $this->attributes['id'];
		}
		if(isset($this->attributes['class'])){
			$this->attributes['class'] .= ' radioBox '.$name;
		}else{
			$this->attributes['class'] = 'radioBox '.$name;
		}
		return BASIC_GENERATOR::init()->element('div', $this->attributes, $tmp);
	}
}
class CheckBoxGroupControl extends SelectControl implements PageControlInterface{
	/* (non-PHPdoc)
	 * @see BasicControl::generate()
	 */
	public function generate($name, $value, $attributes = array()){
		$this->init($name, $value, $attributes);
		
		if(!is_array($value)){
			$value = array($value);
		}
		$value = array_flip($value);
		
		$attTmp = array();
		$attTmp['name'] = $name.'[]';
		$attTmp['type'] = 'checkbox';

		$tmp = '';
		foreach ($this->data as $val => $txt){
			$attTmp['id'] = uniqid();
			$attTmp['value'] = $val;
			
			if(isset($value[$val])) $attTmp['checked'] = 'checked';

			$tmp .= BASIC_GENERATOR::init()->element('div','style=float:left|class=box', 
				BASIC_GENERATOR::init()->createCloseTag('input', $attTmp).' <label for="'.$attTmp['id'].'" class="multyBox_label">'.$txt.'</label>'
			);
			unset($attTmp['checked']);
		}
		if(isset($this->attributes['id'])){
			$name = $this->attributes['id'];
		}
		if(isset($this->attributes['class'])){
			$this->attributes['class'] .= ' multyBox '.$name;
		}else{
			$this->attributes['class'] = 'multyBox '.$name;
		}
		return BASIC_GENERATOR::init()->element('div', $this->attributes, $tmp);
	}
	function isMultiple(){
		return true;
	}
}
class ManageComboControl extends SelectControl implements PageControlInterface{
	/**
	 * @see BasicControl::generate()
 	 *
 	 * special attribute
 	 * 		select : parameters on select element
 	 * 		text   : parameters on text element on work fields
 	 * 		work   : parameters on work element
 	 * 		cont   : parameters on  button container
 	 * 		button : parameters on button elements
 	 *
 	 * 		add  : text for add button
 	 * 		edit : text for edit button
 	 * 		del  : text for del button
	 */
	public function generate($name, $value, $attributes = array()) {
 		$this->init($name, $value, $attributes);

 		$value = (!is_array($value) ? unserialize($value) : $value);
 		$value = (count($value) > 0 && is_array($value) ? "['".implode("','", $value)."']" : "null" );
 		
 		$arrBtnText = array();
 		if(isset($this->attributes['buttons'])){
 		    $buttons = explode(";", $this->attributes['buttons']);
 		    foreach ($buttons as $v){
 		        $ex = explode(":", $v);
 		        $this->attributes[$ex[0]] = $ex[1];
 		    }
 		}
 		if(isset($this->attributes['add'])){
 			$arrBtnText[0] = $this->attributes['add'];
 			unset($this->attributes['add']);
 		}else{
 		    $arrBtnText[0] = '+';
 		}
 		if(isset($this->attributes['edit'])){
 			$arrBtnText[1] = $this->attributes['edit'];
 			unset($this->attributes['edit']);
 		}else{
 		    $arrBtnText[1] = '/';
 		}
 		if(isset($this->attributes['del'])){
 			$arrBtnText[2] = $this->attributes['del'];
 			unset($this->attributes['del']);
 		}else{
 		    $arrBtnText[2] = '-';
 		}
 		$arrBtnText = (count($arrBtnText) > 0 ? "['".implode("','", $arrBtnText)."']" : "null" );

  		if(!isset($this->attributes['cellspacing'])) $this->attributes['cellspacing'] = 0;
 		if(!isset($this->attributes['cellpadding'])) $this->attributes['cellpadding'] = 0;
 		if(!isset($this->attributes['style'])) $this->attributes['width'] = '100%';

 		if(!isset($this->attributes['class'])){
 			$this->attributes['class'] = 'changeSelect '.$name;
 		}else{
 			$this->attributes['class'] .= ' changeSelect '.$name;
 		}
 		if(isset($this->attributes['skin'])){
 			BASIC_GENERATOR::init()->head('select_skin', 'link', 'href='.$this->attributes['skin']);
 			unset($this->attributes['skin']);
 		}else{
			BASIC_GENERATOR::init()->head('select_skin', 'link', "href=".BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'/scripts/svincs/controls/select/style.css');
 		}
		BASIC_GENERATOR::init()->head('Svincs', 'script', array('type'=>'text/javascript', 'src'=>BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'scripts/svincs/svincs.js'), ' ');
		BASIC_GENERATOR::init()->head('select_scr', 'script', array('type'=>'text/javascript'), 'Svincs.include("controls/select/script")');
        
		$add_att = '';
        foreach ($this->attributes as $k => $v){
            $k = str_replace("class", 'className', $k);
            $add_att .= $k.":'".str_replace("'", "\\'", $v)."',";
        }
		return BASIC_GENERATOR::init()->create('script', array('type'=>'text/javascript'),
			"new Svincs.Select.changeSelect('".$name."',".$value.",".(count($this->data) > 0 ? "['".implode("','", $this->data)."']" : "null" ).",".$arrBtnText.",{".substr($add_att, 0, -1)."});");
 	}
	/**
	 * @see BasicControl::convertOut()
	 */
	public function convertOut($value) {
 		foreach (explode(':', $value) as $k => $v){
 			$arr[$k] = str_replace('&#58;', ':', $v);
 		}
 		return $arr;
	}
	function isMultiple(){
		return true;
	}
}
class MoveComboControl extends SelectControl implements PageControlInterface{
	/**
	 * @param unknown_type $name
	 * @param unknown_type $value
	 * @param unknown_type $attributes
	 * @return string
	 */
	public function generate($name, $value, $attributes = array()) {
 		$this->init($name, $value, $attributes);

 		if(!isset($this->attributes['cellspacing'])) $this->attributes['cellspacing'] = 0;
 		if(!isset($this->attributes['cellpadding'])) $this->attributes['cellpadding'] = 0;
 		if(!isset($this->attributes['style'])) $this->attributes['width'] = '100%';

 		if(!isset($this->attributes['class'])){
 			$this->attributes['class'] = 'moveSelect '.$name;
 		}else{
 			$this->attributes['class'] .= ' moveSelect '.$name;
 		}
 		if(isset($this->attributes['skin'])){
 			BASIC_GENERATOR::init()->head('select_skin', 'link', 'href='.$this->attributes['skin']);
 			unset($this->attributes['skin']);
 		}else{
			BASIC_GENERATOR::init()->head('select_skin', 'link', "href=".BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'/scripts/svincs/controls/select/style.css');
 		}
		
 		$all_cl = '';
 		foreach ($this->data as $k => $v){
 			$all_cl .= "'".$k.":".$v."',";
 		}
 		$all_cl = "[".substr($all_cl, 0, -1)."]";

 		$value = (!is_array($value) ? array($value) : $value);
 		$value = (count($value) > 0 && is_array($value) ? "['".implode("','",$value)."']" : "[]" );

		BASIC_GENERATOR::init()->head('Svincs','script','src='.BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'/scripts/svincs/svincs.js',' ');
		BASIC_GENERATOR::init()->head('select_scr', 'script', array('type'=>'text/javascript'), 'Svincs.include("controls/select/script")');

		return BASIC_GENERATOR::init()->create('script',array('type'=>'text/javascript'),
			"new Svincs.Select.actionsSelect('".$name."',".$all_cl.",".$value.",'".BASIC_GENERATOR::init()->convertAtrribute($this->attributes)."');".
			(isset($this->attributes['disabled']) ? "$".$name.".disabledElement(1);" : '')
		);
	}
	function isMultiple(){
		return true;
	}
}
class UploadControl extends BasicControl implements PageControlInterface{
	/**
 	 * special attributes
 	 * 	dir - file uploaded folder
 	 * 	max - max file size
 	 * 	perm - [type1,type2,...,typeN] permit file types
 	 *  template - css styles [default basic_path/scripts/svincs/controls/upload/skin.css]
 	 * 	preview - [width,height] activate preview window
 	 * 	error - text view if is wonted runtime error
 	 * 	delete_btn - basic style or array [ex. text=this is test on link|class=link_class|id=link_id|...]
 	 * 	upload_btn - basic style or array [ex. text=this is test on link|class=link_class|id=link_id|...]
 	 */
 	function generate($name, $value, $attributes = array()){
 		$this->init($name, $value, $attributes);

 		if(!isset($this->attributes['class'])){
			$this->attributes['class'] = 'file_upload '.$name;
		}else{
			$this->attributes['class'] .= ' file_upload '.$name;
		}
		$perm = '';
		if(isset($this->attributes['perm'])){
			$perm = $this->attributes['perm']; unset($this->attributes['perm']);
		}
		$max = '';
		if(isset($this->attributes['max'])){
			$max = $this->attributes['max']; unset($this->attributes['max']);
		}
		$dir = '';
		if(isset($this->attributes['dir'])){
			$dir = $this->attributes['dir']; unset($this->attributes['dir']);
 		}
		$tpl = BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'scripts/svincs/controls/upload/skin.css';
		if(isset($this->attributes['template'])){
			$tpl = $this->attributes['template'];
			unset($this->attributes['template']);
		}
		$size = '';
		if(isset($this->attributes['size'])){
			$size = $this->attributes['size'];
			unset($this->attributes['size']);
		}
 		$disabled = false;
		if(isset($this->attributes['disabled'])){
			$disabled = $this->attributes['disabled'];
			unset($this->attributes['disabled']);
		}
		BASIC_GENERATOR::init()->head('file_control','link','href='.$tpl);

		if(isset($this->attributes['rand'])) unset($this->attributes['rand']);
		if(isset($this->attributes['as'])) unset($this->attributes['as']);

		if(isset($this->attributes['onComplete'])) unset($this->attributes['onComplete']);
		if(isset($this->attributes['onDelete'])) unset($this->attributes['onDelete']);
		if(isset($this->attributes['onError'])) unset($this->attributes['onError']);

 		$tmp = BASIC_GENERATOR::init()->createCloseTag('input', 'type=file|class=control|name='.$name.'|id='.$name.'|'.($disabled ? 'disabled=disabled|' : '').'title=types:'.$perm.' max:'.$max.($size ? '|size='.$size : ''));
		
		if(isset($this->attributes['delete_btn']) && isset($this->attributes['delete_btn']['text'])  && $value){
		    $delete_btn = BASIC_GENERATOR::init()->convertStringAtt($this->attributes['delete_btn']);
		    $text = $delete_btn['text']; 
		    
		    unset($delete_btn['text']);
		    unset($this->attributes['delete_btn']);
		    
			if($disabled){
		    	$delete_btn['disabled'] = 'disabled';
		    	$delete_btn['href'] = '#';
		    }
		    $tmp .= BASIC_GENERATOR::init()->createTag('a', $delete_btn, $text);
		}
 	 	if(isset($this->attributes['upload_btn']) && $value){
		    $btn = BASIC_GENERATOR::init()->convertStringAtt($this->attributes['upload_btn']);
		    $text = $btn['text']; 
		    
		    unset($btn['text']);
		    unset($this->attributes['upload_btn']);
		    
		    if($disabled){
		    	$btn['disabled'] = 'disabled';
		    	$btn['href'] = '#';
		    }else{
		    	$btn['href'] = BASIC::init()->ini_get('root_virtual').$dir.$value;
		    }
		    
		    $tmp .= BASIC_GENERATOR::init()->createTag('a', $btn, $text);
		}
		if(isset($this->attributes['preview']) && $this->attributes['preview'] && $value){
			$ex = explode(",",$this->attributes['preview']);
			$t = BASIC_GENERATOR::init()->image($value, ($dir ? '|folder='.$dir : '').
				(isset($ex[0]) ? '|width='.$ex[0] : '').
				(isset($ex[1]) ? '|height='.$ex[1] : ''));
				
			if($t) $tmp .= BASIC_GENERATOR::init()->create('div', 'class=window', $t);
			unset($this->attributes['preview']);
		}
		//$tmp .= $GLOBALS['BASIC_PAGE']->input("hidden",$name."_old",$value,'id='.$name.'_old');

		return BASIC_GENERATOR::init()->create('div', $this->attributes, $tmp);
 	}
 	function isFileUpload(){
 		return true;
 	}
}
/**
 * @name BASIC_PAGEGenerator
 * @author Evgeni Baldzisky
 * @version 1.1 
 * @since 24.01.2007
 */
class BASIC_GENERATOR extends BASIC_XMLGenerator {
	static private $_uid = 0;
	static public function uId($name = ''){
		if($name == '' || $name == null){
			$name .= "autogen".(self::$_uid++);
		}
		return $name;
	}
	
	protected $Head 	= array();
	protected $ctrls = array();
	
	/**
	 * @param array $arr
	 * @return BASIC_GENERATOR
	 */
	static public function init($arr = array()){
		if(!isset($GLOBALS['BASIC_PAGE'])){
			$GLOBALS['BASIC_PAGE'] = new BASIC_GENERATOR();
		}
		foreach ($arr as $k => $v){
			$GLOBALS['BASIC_PAGE']->$k = $v;
		}
		return $GLOBALS['BASIC_PAGE'];
	}	
	/**
	 * Bufering the tags for HEAD section on the page.WARNING:Test for empty $inner and if true create close tag
	 *
	 * @param string $coment - if == null system will put "sys_"+next head's number
	 * @param string $tag
	 * @param string/array $attribute
	 * @param string $inner
	 */
	function head($coment, $tag, $attribute, $inner=''){
		if(!$tag){
			$this->Head[$coment] = '';
			return;
		}
		$n = '';
		$attribute = $this->convertStringAtt($attribute);
		
		if($coment === null || $coment === '') $coment = 'sys_'.count($this->Head);
		
		if($tag == 'title' && !$inner){
			$inner = ' ';
		}
		if($tag == 'script'){
			$attribute['type'] = 'text/javascript';
			if($inner == '') $inner = ' ';
			
			if(isset($attribute['src'])){
			    $attribute['src'] = str_replace('ROOT_VIRTUAL',$GLOBALS['BASIC']->ini_get('root_virtual'),$attribute['src']);
			}
			if($inner && $inner != ' ' && isset($attribute['src'])){
				$att = $attribute; unset($att['src']);
				$this->Head[$coment.'_inner'] = $n.$this->create($tag,$att,$inner);
				$inner = '';
			}
		}
		if($tag == 'style'){
			if(isset($attribute['href'])) $tag = 'link';
		}
		if($tag == 'link'){
			$attribute['rel'] = 'stylesheet';
			$attribute['type'] = 'text/css';
			
			if(isset($attribute['href'])){
			    $attribute['href'] = str_replace('ROOT_VIRTUAL',$GLOBALS['BASIC']->ini_get('root_virtual'),$attribute['href']);
			}
			$n = "\n";
		}
		
		$this->Head[$coment] = $n.$this->create($tag,$attribute,$inner);
	}
	function registerHead($id){
		$this->Head[$id] = "";
	}
	/**
	 * Shorcut for head function.
	 *
	 * @param string $tagName
	 * @param mix (array/string) $attributes
	 * @param string $inner
	 * @param string $index
	 */
	function setHead($tagName,$attributes,$inner = '',$index = ''){
		$this->head($index,$tagName,$attributes,$inner);
	}
	/**
	 * Get 1 head tag from buffer
	 *
	 * @param string $coment
	 * @return string
	 */
	function getHead($coment){
		if(isset($this->Head[$coment])) return $this->Head[$coment];
		return '';
	}
	/**
	 * set hedar of type special ex : <!--[if IE]--><!--[end]-->
	 *
	 * @param string $body
	 * @param string $coment
	 */
	function headSpecial($body,$coment){
		$this->Head[$coment] = $body;
	}
	/**
	 * Test for exist tag on the buffer
	 *
	 * @param string $coment
	 * @return boolen
	 */
	function existHead($coment){
		return isset($this->Head[$coment]);
	}
	/**
	 * Get all headers.
	 * Style string you nead set flag true
	 * Style array you nead set flag false.It is default value
	 *
	 * @param boolen [$string]
	 * @return string/array
	 */
	function getHeadAll($string = false){
		if($string){
			$string = '';
			foreach ($this->Head as $v){
				$string .= $v;
			}
			return $string;
		}
		return $this->Head;
	}
	/**
	 * Delete head tag
	 *
	 * @param unknown_type $coment
	 */
	function delHead($coment){
		if(isset($this->Head[$coment])) unset($this->Head[$coment]);
	}
	/**
	 * 
	 * @param String $name
	 * @param PageControlInterface $ctrl
	 * @return void
	 */
	public function registrateControle($name, $ctrl){
		$this->ctrls[$name] = $ctrl;
	}
	/**
	 * @param String $name
	 * @return void
	 */
	public function removeControle($name){
		unset($this->ctrls[$name]);
	}
	/**
	 * 
	 * @param String $name
	 * @param String $ctrlName
	 * @param Object $ctrlValue
	 * @param HashMAp $ctrlAttributes
	 * @remove String
	 */
	public function controle($name, $ctrlName, $ctrlValue, $ctrlAttributes = array()){
		if(isset($this->ctrls[$name])){
			return $this->ctrls[$name]->generate($ctrlName, $ctrlValue, $ctrlAttributes);
		}
		throw new Exception("Can't find controle '".$name."'.");
		return null;
	}
	/**
	 * @param String $name
	 * @return PageControlInterface
	 */
	public function getControl($name){
		if(isset($this->ctrls[$name])) return $this->ctrls[$name];
		
		return null;
	}
	public function convertControle($value){
		if(isset($this->ctrls[$name])){
			return $this->ctrls[$name]->convertOut($value);
		}
		return $value;
	}
	/**
	 * Create XHTML tag script.
	 *	valid attributes: 
	 *		All standart HTML attributes
	 *		head - put script in html's head
	 *
	 * @version 0.3
	 * @since 12.07.2007
	 *
	 * @param string $body
	 * @param array [$attribute]
	 * @return string
	 */
	function script($body, $attribute = array()){
		$attribute = $this->convertStringAtt($attribute);
		$attribute['type'] = "text/javascript";
		
		$head = false;
		if(isset($attribute['head'])){
			$head = true;
			unset($attribute['head']);
		}
		if(isset($attribute['src'])){
            $attribute['src'] = str_replace('{ROOT_VIRTUAL}', BASIC::init()->ini_get('root_virtual'), $attribute['src']);
		}
		if($head){
			$this->head(null, 'script', $attribute, $body); return '';
		}
		$tmp = '';
		if($body && $body != ' ' && isset($attribute['src'])){
			$att = $attribute; unset($att['src']);
			$tmp .= $this->createTag('script',$att,$body);
			$body = ' ';
		}
		$tmp .= $this->createTag('script', $attribute, $body);
		
		return $tmp;
	}
	/**
	 * Greate XTHML tag style and link.
	 * if isset(attribute['href']) tag == 'link' else tag == 'style'
	 *
	 * @version 0.2
	 * @copyright
	 * 	update [12-09-2007] add new attribute "path" for add current domain path
	 *
	 * @param string $name
	 * @param string $body
	 * @param array $attribute
	 */
	function style($name,$body,$attribute = array()){
		$attribute = $this->convertStringAtt($attribute);

		$attribute['type'] = "text/css";

		if(isset($attribute['href'])){
			if(isset($attribute['path'])){
				$tmp = $GLOBALS['BASIC']->pathFile(
					array(
						$GLOBALS['BASIC']->ini_get('root_virtual'),
						$attribute['href']
					)
				);
				$attribute['href'] = $tmp[0].$tmp[1];
				unset($attribute['path']);
			}
			$attribute['rel'] = 'stylesheet';
			$this->head($name,'link',$attribute);
		}else{
			$this->head($name,'style',$attribute,$body);
		}
	}
	/**
	 * Special attribute
	 * 		['default'] -> default image 	 ex: images\def.jpg 	def[]
	 * 		['folder'] -> container images 	 ex: images 			def[upload]
	 * 		['absolute'] -> kill w\h size    ex: true 				def[false]
	 * 		['fullpath'] -> activate display full path   ex: true   def[true]
 	 *
 	 * @version 0.5 update [09-03-2007]
	 * @param string $img
	 * @param array $attribute
	 */
	function image($img,$attribute = array()){
		$tmp = '';
		$attribute = $this->convertStringAtt($attribute);

		$default = '';  $width = 0;
		$folder  = '';  $height = 0;

		$att = array();
		if(isset($attribute['default'])){
			$att['default'] = $attribute['default'];
			unset($attribute['default']);
		}
		if(isset($attribute['folder'])){
			$att['folder'] = $attribute['folder'];
			unset($attribute['folder']);
		}
		if(isset($attribute['fixed'])){
			$att['fixed'] = $attribute['fixed'];
			unset($attribute['fixed']);
		}
		if(isset($attribute['absolute'])){
			$att['absolute'] = $attribute['absolute'];
			unset($attribute['absolute']);
		}
		if(isset($attribute['fullpath'])){
			$att['fullpath'] = $attribute['fullpath'];
			unset($attribute['fullpath']);
		}else{
			$att['fullpath'] = 'true';
		}

		BASIC::init()->imported('media.mod');
		$media = new BASIC_MEDIA($img, $att);

		if(isset($attribute['width'])) $width = $attribute['width'];
		if(isset($attribute['height'])) $height = $attribute['height'];

		if($media->info['type'] == 13 || $media->info['type'] == 4){
			$this->headSpecial('<!--[if IE]><script type="text/javascript" src="'.BASIC::init()->ini_get('root_virtual').BASIC::init()->ini_get('basic_path').'scripts/flash/flash.js" defer="defer"></script><![endif]-->','Flash');
		}
		return $media->view($width,$height,$attribute) . $tmp;
	}

	function createTab($number = 1){
		$tmp = '';
		for($i=0;$i<$number+1;$i++){
			$tmp .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		return $tmp;
	}
	/**
	 * Warning:
	 * 		http;\\myhost.com?var=val&var2=va&lue  -> bad
	 * 		http;\\myhost.com?var=val&var2=value   -> good
	 *
	 * 		update [21-07-2007] add new attribute "state" auto create state
	 * 		update [12-09-2007] add new attribute "path" auto create current domain path #start of root site folder
	 * 		fix    [20-04-2008] fix regular expression if doesn't exist file name
	 * @version 0.5 [08-03-2007]
	 *
	 * @param sting $text
	 * @param string $url
	 * @param array/string $attribute
	 * @return string
	 */
	function link($text,$url = '#',$attribute = array()){ 
		$attribute = $this->convertStringAtt($attribute);
		if($url){
			preg_match("/([^\?]+)?(\?)?(.+)?/", $url, $reg);
			if(isset($reg[3])){
				preg_match_all("/&(amp;)?([^&]+)/","&".$reg[3], $exp);
	
				for ($i=0;$i<count($exp[2]);$i++){
					$var = explode("=",$exp[2][$i]);
					$exp[2][$i] = $var[0]."=".urlencode(isset($var[1]) ? $var[1] : '');
				}
	
				$reg[3] = implode("&",$exp[2]);
			}else{
				$reg[3] = '';
			}
			$stat = '';
			if(isset($attribute['state'])){
				if($attribute['state'] == "*"){
					$ex = array();
				}else{
					$ex = explode(",",$attribute['state']);
				}
				$stat = $GLOBALS['BASIC_URL']->serialize($ex);
				unset($attribute['state']);
			}
			$tmp = $stat.$reg[3];
			$attribute['href'] = $reg[1].($tmp ? '?' : '').$tmp;
			if(isset($attribute['path'])){
				$tmp = $GLOBALS['BASIC']->pathFile(
					array(
						$GLOBALS['BASIC']->ini_get('root_virtual'),
						$attribute['href']
					)
				);

				$attribute['href'] = $tmp[0].$tmp[1];
				unset($attribute['path']);
			}
			$attribute['href'] = $GLOBALS['BASIC_URL']->link($attribute['href']);
		}else{
			$attribute['href'] = '#';
		}
		return $this->createTag('a',$attribute,$text);
	}
	function element($tag,$attribute = array(),$inner='undefined',$flag=false){
		$tag = strtolower($tag);

		$attribute = $this->convertStringAtt($attribute);

		if($flag && $inner == '') return '';

		if($tag == 'br' || $tag == 'hr' || $tag == 'img' || $tag == 'input' || $tag == 'meta' || $tag == 'link'){
			if($tag == 'img' && !isset($attribute['alt'])) $attribute['alt'] = '';
			$tmp = $this->createCloseTag($tag,$attribute);
		}else{
			$tmp = $this->createTag($tag,$attribute,($inner != 'undefined' ? $inner : '&nbsp;'));
		}
		return $tmp;
	}
	/**
	 * @version 0.2 [19-10-2007]
	 * Special parameters
	 * 	state -> save state programme
	 *
	 * @param string $action
	 * @param array/string $attribute
	 * @param string $inner
	 * @return string
	 */
	function form($attribute = array(), $inner = ''){
		$attribute = $this->convertStringAtt($attribute);
		if(!isset($attribute['action'])){
			$attribute['action'] = BASIC::init()->scriptName();
		}
		
		$name = '';
		if(isset($attribute['name'])) $name = $attribute['name'];
		if(isset($attribute['id'])) $name = $attribute['id'];
		
		$name = self::uId($name);

		$attribute['name'] = $name;
		$attribute['id'] = $name;

		if(isset($attribute['state'])){
			if($attribute['state'] == "*"){
				$ex = array();
			}else{
				$ex = explode(",", $attribute['state']);
			}
			$inner .= BASIC_URL::init()->serialize($ex, 'post');
			unset($attribute['state']);
		}
		$tmp = BASIC_GENERATOR::init()->create('form', $attribute, "\n".$inner);
//		$tmp .= $GLOBALS['BASIC_PAGE']->script("
//			var frm".$name." = document.getElementById('".$name."');
//			var col".$name." = frm".$name.".getElementsByTagName('a');
//			for(var i=0;i<col".$name.".length;i++) col".$name.".item(i).form = frm".$name.";
//		");
		return $tmp;
	}
	/**
	 * Dynamic form element
	 * 	support tags
	 *		select
	 *		radioBox
	 *		multyBox
	 *		moveSelect
	 *		changeSelect
	 *
	 * 	special attribute
	 *		data || base%name table;name value column;name text column;sql criteria;default value[,:]default text
	 * 			 || static%value1:text1;value2:text2;...;valueN:textN
	 * 			 || query%select [key column],[text column] from ... [;key column][;text column]
	 *           || PHP hash array
	 *
	 *  NEW : 
	 *     base and query data's optionmns support lingual functionality
	 * 
	 * @param string $tag
	 * @param string $name
	 * @param string $value
	 * @param arrau $attribute
	 * @return string
	 */
 	function dynamic($tag, $name, $value, $attribute){
 		$attribute = $this->convertStringAtt($attribute);
 		$tmp = '';
		$optionArray = array();

		if(
			$tag != "select" &&
			$tag != "multySelect" &&
			$tag != "radioBox" &&
			$tag != "multyBox" &&
			$tag != "moveSelect" &&
			$tag != "changeSelect"
		) return '';
		
		if($tag == "multySelect"){
			$tag = 'select';
			$attribute['multiple'] = 'multiple';
		}

 		if(isset($attribute['data'])){

			if(is_array($attribute['data'])){
				foreach($attribute['data'] as $k => $v){
					$optionArray[$k] = (is_array($v) ? $v[0] : $v);
				}
			}else{
				$tmp_arr = explode("%",$attribute['data']);
				
				if($tmp_arr[0] == 'base'){
					
					$infoArray = explode(";", $tmp_arr[1]);
					$optionArray = array();
					if(isset($infoArray[4]) && ($tag == 'select' || $tag == 'radioBox')){
						for($i=4;isset($infoArray[$i]);$i++){
							$ex_def = split("[,:]", $infoArray[$i]); // save old functionality
							if(isset($ex_def[1])){
								$optionArray[$ex_def[0]] = $ex_def[1];
							}else{
								$optionArray[] = $infoArray[$i];
							}
						}
					}
					$preg_ex = "/^((.+) as )?[^a-zA-Z_]*([a-zA-Z_]+)[^a-zA-Z_]*$/i";
					$var_1 = $infoArray[1];
					preg_match($preg_ex,$infoArray[1],$ex);
					if($ex[1]){
						$var_1 = $ex[3];
					}else{
						$infoArray[1] = "`".$infoArray[1]."`";
					}
					$var_2 = $infoArray[2];
					preg_match($preg_ex, $infoArray[2], $ex);
					if($ex[1]){
						$var_2 = $ex[3];
					}else{
						$infoArray[2] = "`".$infoArray[2]."`";
					}
					$criteria = '';//(isset($infoArray[3]) ? $infoArray[3] : ' order by `'.$var_2.'` ');
					$rdr = BASIC_SQL::init()->read_exec("select * from `".$infoArray[0]."` where 1=1 ".$criteria." ");

					for($i=0;$rdr->read();$i++){
					    if(!$rdr->test($var_2)){
					        if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && $rdr->test($var_2.'_'.$GLOBALS['BASIC_LANG']->current())){
					            $optionArray[$rdr->field($var_1)] = $rdr->field($var_2.'_'.BASIC_LANGUAGE::init()->current());
					        }else{
					            throw new Exception('Column '.$infoArray[0].'.'.$var_2.' no exist.');
					        }
					    }else{
						    $optionArray[$rdr->field($var_1)] = $rdr->field($var_2);
					    }
					}
				}else if($tmp_arr[0] == 'query'){ // NEW
				    
					$infoArray = explode(";", $tmp_arr[1]);
					
					if(!isset($infoArray[1])){
					    $query = $infoArray[0];
                        $query = preg_replace("[\n\t\r]","",$query);
                         
                        preg_match('/select[ ]+(.+)[ ]+from/i',$query,$ex);
                        if(isset($ex[1])){
                            $ex[1] = preg_replace("/[` ]+/","",$ex[1]);
                            
                            $tmp = explode(',',$ex[1]);
                            if(isset($tmp[1])){
                                $infoArray[2] = $tmp[1];
                            }else{
                                $infoArray[2] = $tmp[0];
                            }
                            $infoArray[1] = $tmp[0];
                            
                            $infoArray[1] = preg_replace("/^[^\.]+\./", "", $infoArray[1]);
                            $infoArray[2] = preg_replace("/^[^\.]+\./", "", $infoArray[2]);
                            
                            $infoArray[0] = preg_replace('/select[ ]+(.+)[ ]+from/i','select * from',$query);
                        }else{
                            throw new Exception('In query ['.$query.'] no declare id and text columns.');
                        }				    
					}
					BASIC_SQL::init()->read_exec($infoArray[0]);
					while($rdr->read()){
				        if(!$rdr->test($infoArray[2])){
					        if(isset($GLOBALS['BASIC_LANG']) && is_object($GLOBALS['BASIC_LANG']) && $rdr->test($infoArray[2].'_'.$GLOBALS['BASIC_LANG']->current())){
					            $optionArray[$rdr->field($infoArray[1])] = $rdr->field($infoArray[2].'_'.BASIC_LANGUAGE::init()->current());
					        }else{
					             throw new Exception(500,'Column '.$infoArray[0].'.'.$infoArray[2].' no exist.');
					        }
					    }else{
						    $optionArray[$rdr->field($infoArray[1])] = $rdr->field($infoArray[2]);
					    }
					}
				}else{
					$ex = explode(";", $tmp_arr[1]);
					foreach($ex as $V){
						$spl = explode(":", $V);
						if(isset($spl[1])){
							$optionArray[$spl[0]] = $spl[1];
						}
					}
				}
			}
			$attribute['data'] = $optionArray;
		}
 		return $this->controle($tag, $name, $value, $attribute);
 	}
}
BASIC_GENERATOR::init()->registrateControle('input', 		new BasicControl());
BASIC_GENERATOR::init()->registrateControle('password', 	new PasswordControl());
BASIC_GENERATOR::init()->registrateControle('textarea', 	new TextareaControl());
BASIC_GENERATOR::init()->registrateControle('date', 		new DateControl());
BASIC_GENERATOR::init()->registrateControle('html', 		new HtmlControl());
BASIC_GENERATOR::init()->registrateControle('file', 		new UploadControl());
BASIC_GENERATOR::init()->registrateControle('radio', 		new RadioBoxGroupControl());
BASIC_GENERATOR::init()->registrateControle('check', 		new CheckBoxGroupControl());
BASIC_GENERATOR::init()->registrateControle('checkbox', 	new CheckBoxControl());
BASIC_GENERATOR::init()->registrateControle('select', 		new SelectControl());
BASIC_GENERATOR::init()->registrateControle('multiple', 	new MultySelectControl());
BASIC_GENERATOR::init()->registrateControle('selectmove', 	new MoveComboControl());
BASIC_GENERATOR::init()->registrateControle('selectmanage', new ManageComboControl());