<?php
/**
 * Class HTML/XML generator
 *
 *		$html = new XMLGenerator();
 *
 * 		ex.1:Generate XHTML code
 *
 *		print(
 *			$html->create("table",array('border'=>1),
 *			  	 $html->create('tr',null,
 *					  $html->create('td',array("style"=>'color:#FF0000;'),"My dynamic test").
 *					  $html->create('td',null,
 *						   $html->createCloseTag('img',array("src"=>'icon/folder_off.gif ')).
 *						   $html->createCloseTag('br').
 *						   "testing my class"
 *					  )
 *				 ).
 *			  	 $html->create('tr',null,
 *					  $html->create('td',array("style"=>'color:#FF0000;'),"My dynamic test").
 *					  $html->create('td',null,
 *						   $html->createCloseTag('img',array("src"=>'icon/folder_off.gif ')).
 *	 					   $html->createCloseTag('br').
 *						   "testing my class"
 *					  )
 *				 )
 *			)
 *		);
 *
 * 		ex.2:Generate XML code
 *
 * 		print{
 * 			$html->create('root',array('att1'=>1,'att2'=>2,
 * 				$html->create('fields',null,my field_1 XML text').
 * 				$html->create('fields',null,
 * 					$thml->create('field_2',array('field_2_level'=>2),'test 2 level XML Element')
 * 				).
 * 				$html->create('fields',null,my field_3 XML text').
 * 			)
 * 		);
 *
 * @name TagGenerator
 * @author Evgeni Baldzisky
 * @version 0.2 [24-01-2007]
 * @package BASIC.SBND.XML
 */
class BASIC_XMLGenerator{

	var $XML = false; 			// create true only call constructor
	var $XMLbuffer = '';		// buffer generate XML
	var $cleanAttribute = true;

	function XMLGenerator($createHeader = true){
		$this->XML = true;
		if($createHeader){
			$this->createHeader();
		}
	}
	/**
	 * Confert arrtibute array in valid HTML Sintax
	 *
	 * @param array $attribute
	 * @return string
	 */
	function convertAtrribute($attribute){

		$tmp = '';
		if(!is_array($attribute)){
			$attribute = $this->convertStringAtt($attribute);
		}
		
		foreach ($attribute as $k => $v){
			
			if($k == 'readonly' || $k == 'disabled' || $k == 'checked' || $k == 'selected'){
				if($v === false || $v === 'false') continue;
				if($v === true || $v === 'true'){
					$v = $k;
				}
			}
			
			if($this->cleanAttribute){
				//$v = ereg_replace("&(amp;)?","&amp;",$v);
			}
			
			$v = @ereg_replace("<","&lt;",$v);
			$v = @ereg_replace(">","&gt;",$v);
			$v = @str_replace('"',"&quot;",$v);

			$tmp .= ' '.strtolower($k).'="'.$v.'"';
		}
		
		return $tmp;
	}

	/**
	 * Convert string attribute in array
	 * valid sintax 'name attribute 1=value attribute 1|name attribute 2=value attribute 2|...|name attribute N=value attribute N'
	 * if you nead special separators can add the second and the third function parametars with array declarations.
	 * array declaration must contain this elements (separator,alternativ symbol visioalisation).
	 * example : 
	 * 	$arr = BASIC.XML->convertStringAtt('att1=val&#62ue1|att2=val2|att3=v&#124al'); // default declaration
	 * 	result : 
	 * 		$arr = array(
	 * 			att1 => val=ue1
	 * 			att2 => val2
	 * 			att3 => v&|al
	 * 		)
	 * 
	 * 	$arr = BASIC.XML->convertStringAtt('att1:val=ue1;att2:va|l2;att3:v&#59a&#58l;',array(';',"&#59"),array(":","&#58")); // special symbol separators
	 * 	result : 
	 * 		$arr = array(
	 * 			att1 => val=ue1
	 * 			att2 => val2
	 * 			att3 => v;|a:l
	 * 		)
	 * 
	 * @param string $attribute
	 * @param array $separatopAttributes
	 * @param array $separatopValues

	 * @return array
	 */
	function convertStringAtt($attribute,$separatopAttributes=array('|','&#124;'),$separatopValues=array("=","&#62;")){
		if(!is_array($attribute) && $attribute != null && $attribute != ''){
			$arr = explode($separatopAttributes[0],$attribute);

			$attribute = array();
			foreach($arr as $v){
				$arr2 = explode($separatopValues[0],$v,2);
				if(isset($arr2[1])){
					
					$arr2[1] = str_replace($separatopAttributes[1],$separatopAttributes[0],$arr2[1]);
					$arr2[1] = str_replace($separatopValues[1],$separatopValues[0],$arr2[1]);
					
					$attribute[$arr2[0]] = $arr2[1];
				}
			}
		}
		return is_array($attribute) ? $attribute : array();
	}

	/**
	 * Generate closed tags
	 *
	 * @param string $tagname
	 * @param array/string $attribute
	 * @return string
	 */
	function createCloseTag($tagname,$attribute=''){
		if($this->XML) $attribute = $this->convertStringAtt($attribute);
		//print($tagname);
		$tmp  = '';
		$tmp .= '<'.strtolower($tagname);
		$tmp .= $this->convertAtrribute($attribute);
		$tmp .= ' />';
		if($this->XML) $this->XMLbuffer .= $tmp;
		return $tmp;
	}

	/**
	 * Generate open tags
	 *
	 * @param string $tagname
	 * @param array/string $attribute
	 * @return string
	 */
	function createOpen($tagname,$attribute=''){
		$tagname = strtolower($tagname);
		if($this->XML) $attribute = $this->convertStringAtt($attribute);
		$tmp  = "\n";
		$tmp .= '<'.$tagname;
		$tmp .= $this->convertAtrribute($attribute);
		$tmp .= '>';
		if($this->XML) $this->XMLbuffer .= $tmp;
		return $tmp;
	}

	/**
	 * Generate close tags
	 *
	 * @param string $tagname
	 * @param array/string $attribute
	 * @return string
	 */
	function createClose($tagname){
		$tmp = "</".strtolower($tagname).">";
		if($this->XML) $this->XMLbuffer .= $tmp;
		return $tmp;
	}

	/**
	 * Generate coment
	 *
	 * @param string $text
	 * @return string
	 */
	function createComment($text){
		$tmp = "\n<!--".$text."-->\n";
		if($this->XML) $this->XMLbuffer .= $tmp;
		return $tmp;
	}

	/**
	 * Generate valid tags.WARNING:No test for empty $inner
	 *
	 * @param string $tagname
	 * @param array/string $attribute
	 * @param string $inner
	 * @return string
	 */
	function createTag($tagname,$attribute='',$inner=''){
		$tmp  = '';
		$tmp .= $this->createOpen($tagname,$attribute);
		$tmp .= $inner;
		$tmp .= $this->createClose($tagname);

		return $tmp;
	}

	/**
	 * Generate valid tags.WARNING:Test for empty $inner end if true generate close tag
	 *
	 * @param string $tagname
	 * @param array/string $attribute
	 * @param string $inner
	 * @return string
	 */
	function create($tagname,$attribute=array(),$inner=''){
		$check = false;
		if(!$inner) $check = true;
		if($check){
			return $this->createCloseTag($tagname,$attribute);
		}
		return $this->createTag($tagname,$attribute,$inner);
	}

	function createHeader(){
		header('Content-Type: text/xml');
	}

	function createVersion($attribute=array()){
		$attribute = $this->convertStringAtt($attribute);
		if(!isset($attribute['version'])){
			$attribute['version'] = '1.0';
		}
		return '<?xml '.$this->convertAtrribute($attribute).' ?>' . "\n";
	}

	// End class XMLGenerator
}

/**
 * Class for read XML resourses.
 * 	ex.1
 * 		$xml = new XMLReader();
 *
 * 		$xmlResourses = '
 * 			<root>
 * 				<t1>
 * 					<t1:1>value text </t1:1>
 * 				</t1>
 * 				<t1>
 * 					<t1:1>value text </t1:1>
 * 				</t1>
 * 			</root>
 * 		';
 * 		print_r($xml->loadData($xmlResourses));
 *****************************************************
 * 		return array(
 * 			[0] => array(
 * 				[nodeType] => 1,
 * 				[nodeName] => '...',
 * 				[attributes] => array(
 * 					[name_1] => 'value_1',
 * 					[name_N] => 'value_N'
 * 				)
 * 				[choldNodes] => array(
 * 					[0] => array(
 * 						[nodeType] => 3,
 * 						[nodeName] => '#text#',
 * 						[nodeValue] => '...'
 * 					)
 * 				)
 * 			)
 * 			[1] => array(
 * 				[nodeType] => 3,
 * 				[nodeName] => '#text#',
 * 				[nodeValue] => '...'
 * 			)
 * 		)
 *
 * 	@name XMLReader
 * 	@author Evgeni Baldziski
 * 	@version 0.3 [02-09-2007]
 *  @copyright 
 * 		update [14-09-2007] add 2 new methods for create easy xhtml 
 * 		update [02-10-2007] correct returned array
 * 	@package BASIC.SBND.XML
 * 	@copyright
 *
 */
class BASIC_XMLReader{

	/** @access protected */
	var $arrOutput = array();
	var $doctype = '';
	var $resParser = '';
	var $strXmlData = '';

	/**
	 * Constructor
	 *
	 * @return XMLReader
	 */
	function BASIC_XMLReader(){
		$this->resParser=xml_parser_create();
		xml_set_object($this->resParser,$this);
		xml_set_element_handler($this->resParser,"tagOpen","tagClosed");
		xml_set_character_data_handler($this->resParser,"tagData");
	}

	/**
	 * Method for load XML of file
	 *
	 * @param string $tfile
	 * @return array
	 */
	function loadFile($tfile,$doc=false){
		$this->thefile = $tfile;
		if(!file_exists($tfile)){
			die(" File ".$tfile." no exist.");
		}
		$th = file($tfile);
		$tdata = implode("\n",$th);
		return $this->loadData($tdata,$doc);
	}

	/**
	 * Method for load costom XML
	 *
	 * @param string $data
	 * @return array
	 */
	function loadData($data,$doc=false){	
		if($doc){
			ereg("^[ \n\t\r]*(<!DOCTYPE[^>]+>)",$data,$r);
			$this->doctype = (isset($r[1]) ? $r[1] : '');
		}
		return $this->parse($data);
	}

	/**
	 * Create XML array
	 *
	 * @param swtring $strInputXML
	 * @return array
	 */
	function parse($strInputXML){
		$this->strXmlData = xml_parse($this->resParser,$strInputXML);
		if(!$this->strXmlData){
			die(
				sprintf("XMLerror: %sat line %d",
					xml_error_string(xml_get_error_code($this->resParser)),
					xml_get_current_line_number($this->resParser)
				)
			);
		}
		xml_parser_free($this->resParser);
		return $this->arrOutput;
	}

	/** XML HENDLARS **/

	function tagOpen($parser,$name,$attrs){
		$tmp = array();
		foreach ($attrs as $k => $v){
			$tmp[strtolower($k)] = $v;
		}
		$tag = array(
			"nodeType" => 1,
			"nodeName"=>strtolower($name),
			"attributes"=>$tmp
		);
		array_push($this->arrOutput,$tag);
	}

	function tagData($parser,$tagData){
		if(trim($tagData)){
			if(!isset($this->arrOutput[count($this->arrOutput)-1]['childNodes'])){
				$this->arrOutput[count($this->arrOutput)-1]['childNodes'] = array();
			}
			$this->arrOutput[count($this->arrOutput)-1]['childNodes'][] = array(
				"nodeType" => 3,
				"nodeName"=>'#text#',
				'nodeValue'=>$this->_parseXMLValue($tagData)
			);
		}
	}

	function _parseXMLValue($tvalue){
		$tvalue=htmlentities($tvalue);
		return $tvalue;
	}

	function tagClosed($parser,$name){
		$this->arrOutput[count($this->arrOutput)-2]['childNodes'][] = $this->arrOutput[count($this->arrOutput)-1];
		array_pop($this->arrOutput);
	}
	
	function _toXML($array,$xhtml){
			$data = '';
			foreach($array as $k => $v){
				if(isset($v['childNodes'])){
					$data .= $this->_toXML($v['childNodes'],$xhtml);
				}else{
					$data .= $xhtml->create($v['nodeName'],$v['attributes'],$v['nodeValue']);
				}
			}
			return $data;
	}
	/**
	 * Method XHTML generator.$array use currene sintax.
	 * array(
	 * 		[0] => array(
	 * 			[nodeName] => '...',
	 * 			[attribute] => array(
	 * 				[att1] => '...',
	 * 				[attN] => '...'
	 * 			)
	 * 			[childNodes] => array(
	 * 
	 * 			)
	 * 			[nodeValue] => ''
	 * 		)
	 * } 
	 *
	 * @param array $array
	 * @return string
	 */
	function toXML($array){
		$xhtml = new BASIC_XMLGenerator();
		return $this->_toXML($array,$xhtml);
	}
	// End Class XMLReader
}

class BASIC_DOM{
	
	var $arrXML = array();
	var $collection = array();
	
	function BASIC_DOM($arrXML){
		$this->arrXML = $arrXML;
		if(!isset($arrXML['nodeName'])){
			$this->arrXML = $arrXML[0];
		}
	}
	/**
	 * Search by tag name.
	 * Style all==true :: search by all levels
	 * Style all==false[def] :: search by first level
	 *
	 * @param string $name 
	 * @param boolen $all
	 * @param array $arr
	 */
	function getElementsByTagName($name,$all=false,$arr = array()){
		if(!$arr) $arr = $this->arrXML;
		foreach ($arr['childNodes'] as $v){
			if($v['nodeType'] == 1){
				
				if(isset($v['nodeName']) && $v['nodeName'] == $name){
					$this->collection[] = $v;
				}
				if(isset($v['childNodes']) && $all){
					$this->getElementsByTagName($name,$all,$v);
				}
			}
		}
		
		return $this->collection;
	}
	
	function delElenemtsByTagName($name,$all=false,$arr = array()){
		if(!$arr) $arr = $this->arrXML;
		foreach ($arr['childNodes'] as $k => $v){
			if($v['nodeType'] == 1){
			
				if(isset($v['nodeName']) && $v['nodeName'] == $name){
					$this->collection[] = $v;
					unset($arr['childNodes'][$k]); continue;
				}
				if(isset($v['childNodes']) && $all){
					$this->getElementsByTagName($name,$all,$v);
				}	
			}
		}
		$this->arrXML = $arr;
		return $this->collection;
	}
	
	function getElementById($id,$arr = array()){
		if(!$arr) $arr = $this->arrXML;
		$tmp = array();
		foreach ($arr['childNodes'] as $v){
			if($v['nodeType'] == 1){
				if(isset($v['attributes']['id']) && $v['attributes']['id'] == $id){
					$tmp = $v;
				}else if(isset($v['childNodes'])){
					$tmp = $this->getElementById($id,$v);
				}
			}
			if($tmp) break;
		}
		return $tmp;
	}
	
	function delElementById($id,$arr = array()){
		if(!$arr) $arr = $this->arrXML;
		$tmp = array();
		foreach ($arr['childNodes'] as $k => $v){
			if($v['nodeType'] == 1){
				if(isset($v['attributes']['id']) && $v['attributes']['id'] == $id){
					$tmp = $v;
					unset($arr['childNodes'][$k]);
				}else if(isset($v['childNodes'])){
					$tmp = $this->getElementById($id,$v);
				}
			}
			if($tmp) break;
		}
		$this->arrXML = $arr;
		return $tmp;
	}
	// End Class
}
?>