<?php
interface BasicPagingInterface {
	/**
	 * @param Integer $total_number
	 * @param Integer $num_to_show
	 * @param String $prefix
	 * @return void
	 */
	function init($total_number, $num_to_show = 10, $prefix = '');
	/**
	 * @return HashMap
	 */
	function getBar();
	/**
	 * @return String
	 **/
	function getSql();
}
class BasicComponentPaging extends BasicPaging implements BasicPagingInterface{
	
	function __construct($prefix = ''){
		$this->prefix = $prefix;
	}
	public function init($total_number, $num_to_show = 10, $prefix = ''){
		parent::__construct($total_number, $num_to_show, $prefix);
	}
	public function getBar(){
		return $this->getTemplateVars();
	}
	public function getSql(){
		return $this->limitCriteria();
	}
}
/**
 * Изработка на бар и контрол на броя на показваните резултати. 
 * 	<code>
 * 		$rdr = BASIC_SQL::init()->read_exec(" SELECT * FROM `table_name` WHERE 1=1 ");
 * 		if($rdr->num_rows()){
 * 			$paging = new Paging($rdr->num_rows(),20,'second_paging_in_this_htmp_page');
 * 			
 * 			$rdr = BASIC_SQL::init()->read_exec(" SELECT * FROM `table_name` WHERE 1=1 ".$paging->limitCriteria());
 * 		}
 * 		$res = array();
 * 		while($rdr->read()){
 * 			$res[] = $rdr->getItems();
 * 		}
 * 		BASIC_TEMPLATE2::init()->set(array(
 * 			'results' => $res,
 * 			'paging_bar' => $paging->show_page(array(
 * 				'template' => 'paging_bar.tpl'
 * 			))
 * 		));
 *	</code>
 * 
 * @author Tsvetomir Velichkov,Evgeni Baldzisky
 * @version 2.0
 * @since 30-01-2007
 * @package BASIC.BARS
 */
class BasicPaging{
	/**
	 * Номера на текущата страница.
	 *
	 * @var int
	 */
	protected $page = 1;
	/**
	 * Името на скрипта към които да водят линковете.
	 * По подразбиране ако не се въведе нищо ще се приравни с текущия скрипт.
	 *
	 * @var string
	 */
	public $script = '';
	/**
	 * Максималния брой показвани резултати.
	 *
	 * @var int
	 */
	protected $total_number = 0;
	/**
	 * Максималния брой показвани резултати на страница.
	 *
	 * @var int
	 */
	public $num_to_show = 10;
	/**
	 * Броя страници.
	 *
	 * @var int
	 */
	protected $num_pages = 1;
	/**
	 * Остаряло !!! Използва се ако не е подаден темплейт на show_page е стартиран текст мод.
	 *
	 * @var string
	 */
	public $page_string = 'Page';
	/**
	 * Остаряло !!! Използва се ако не е подаден темплейт на show_page.
	 *
	 * @var string
	 */
	public $text_mode = false;
	/**
	 * Поставя представка на линковете. Използва се когато се изграждат два или повече
	 * бара на една и съща страница.
	 *
	 * @var string
	 */
	public $prefix = '';
	/**
	 * Име на URL променливата.
	 *
	 * @var string
	 */
	public $page_url_var = 'page';
	/**
	 * Остаряло !!! Използва се ако не е подаден темплейт на show_page.
	 *
	 * @var string
	 */
	public $min = '&#171';
	/**
	 * Остаряло !!! Използва се ако не е подаден темплейт на show_page.
	 *
	 * @var string
	 */
	public $max = '&#187';
	/**
	 * Остаряло !!! Използва се ако не е подаден темплейт на show_page.
	 *
	 * @var string
	 */	
	public $next = '&#8250';
	/**
	 * Остаряло !!! Използва се ако не е подаден темплейт на show_page.
	 *
	 * @var string
	 */	
	public $prev = '&#8249';
	/**
	 * Име на темплейта които ще се ползва за изработката на бара.
	 *
	 * @var string
	 */
	public $template = '';
	/**
	 * Карта на подаваните на темплейта променливи.
	 *
	 * @var array
	 */
	public $template_var_map = array(
		'min-link'  		=> 'min_link', 			// линк на бутона за първа страница
		'prev-link' 		=> 'prev_link',			// линк на бутона за предишна страница
		
		'max-link' 			=> 'max_link',			// линк на бутона за последна страница
		'next-link' 		=> 'next_link',			// линк на бутона за следваща страница

		'current-page'		=> 'current_page',
		'max-pages' 		=> 'max_pages',		  	// броя страници 
	
		'show-all-items'	=> 'show_all_items',
		'show-from-items' 	=> 'show_from_items',
		'show-to-items' 	=> 'show_to_items',
	
		'prefix' 			=> 'prefix',			// използваната представка
		
		'paging' 			=> 'pages',			// списък с страници
		'paging-current' 	=> 'current',
		'paging-link' 		=> 'link',
		'paging-number' 	=> 'number'
	);
	/**
	 * @param int $total_number
	 * @param int [$num_to_show]
	 * @param string [$prefix]
	 */
	function __construct($total_number, $num_to_show = 10, $prefix = ''){

		if($prefix) $this->prefix = $prefix;
		
		$this->total_number = ($total_number > 0 ? $total_number : 1);
		$this->num_to_show = ($num_to_show > 0 ? $num_to_show : $this->total_number);
		
		$this->num_pages = ceil($this->total_number / $this->num_to_show);
		$this->script = BASIC::init()->scriptName();
		
		if($page = BASIC_URL::init()->request($this->prefix.$this->page_url_var)){
			if($page == 'min') $page = 1;
			if($page == 'max') $page = $this->num_pages;

			$this->page = (int)$page;
		}else{ // reset page if it modificated
			$this->page = 1;
		}
		if($this->num_pages > 0 && $this->page > $this->num_pages){
			$this->page = $this->num_pages;
		}
	}
	/**
	 * Промяня на конфигорацията на обекта. 
	 *
	 * @param string $name
	 * @param mix $value
	 */
	function change($name, $value = ''){
		if(!$value){
			foreach ($name as $k => $v){
				$this->$k = $v;
			}
		}else{
			$this->$name = $value;
		}
		$this->__construct($this->total_number, $this->num_to_show, $this->prefix);
	}
	
	function getPage(){
		return $this->page;
	}
	function getNumAllPages(){
		return $this->num_pages;
	}
	/**
	 * Бар генератор.
	 *
	 * @param array $attribute
	 * @return string
	 */
	function show_page($attribute = array()){
		if($this->num_pages > 1){
			$attribute = BASIC_GENERATOR::init()->convertStringAtt($attribute);
			
			$ajax = array();
			if(isset($attribute['ajax'])){
				$ajax['ajax'] = $attribute['ajax'];
				unset($attribute['ajax']);
			}
			if(isset($attribute['bin'])){
				if(!isset($attribute['bin'])) die('No render !');
				$ajax['bin'] = $attribute['bin'];
				unset($attribute['bin']);
			}
			if(isset($attribute['group'])){
				$ajax['group'] = $attribute['group'];
				unset($attribute['group']);
			}
			if(isset($attribute['clean'])){
				$ajax['clean'] = $attribute['clean'];
				unset($attribute['clean']);
			}
			if(isset($attribute['state'])){
				$ajax['state'] = $attribute['state'].','.$this->prefix.$this->page_url_var;
			}else{
				$ajax['state'] = $this->prefix.$this->page_url_var;
			}
			$template = null;
			if(isset($attribute['template'])){
			    $template = $attribute['template'];
			    unset($attribute['template']);
			}
			
			if($template){
     		    return BASIC_TEMPLATE2::init()->set($this->getTemplateVars(), $template)->parse($template);
			}else{
    			$tmp = "<tr>\n";
    			if($this->page == 1){
    				$tmp .=  '<td><span class="paging_arrows paging_disabled">'.$this->min.'</span></td>'."\n";
    				$tmp .=  '<td><span class="paging_arrows paging_disabled">'.$this->prev.'</span></td>'."\n";
    			}else{
    				$tmp .= BASIC_GENERATOR::init()->element('td',null,
    					BASIC_GENERATOR::init()->link('<span class="paging_arrows">'.$this->min.'</span>',$this->script."?".$this->prefix.$this->page_url_var."=1",$ajax)
    				);
    				$tmp .= BASIC_GENERATOR::init()->element('td',null,
    					BASIC_GENERATOR::init()->link('<span class="paging_arrows">'.$this->prev.'</span>',$this->script."?".$this->prefix.$this->page_url_var."=".($this->page-1),$ajax)
    				);
    			}
    			$tmp .=  '<td align="center" nowrap="nowrap">';
    			if ($this->text_mode == true) {
    				$tmp .= sprintf("%s <b>%d</b>/<b>%d</b> :: ", $this->page_string, $this->page, $this->num_pages);
    			}
    			$start = 1;
    			if ( ($this->page >= 6) && ($this->page <= $this->num_pages-6) ) {
    				$start = $this->page-3;
    			}elseif ($this->page >= $this->num_pages-6){
    				$start = $this->num_pages-6;
    			}
    			for ($i = $start; $i <=$start+6; $i++) { 
    				if ($i <= 0) continue;
    				if ($i == $this->page) {
    					$tmp .= BASIC_GENERATOR::init()->element('b',null,$i);
    				}else{
    					$tmp .= BASIC_GENERATOR::init()->link("[".$i."]",$this->script."?".$this->prefix.$this->page_url_var."=".($i),$ajax);
    				}
    			}
    			$tmp .= "</td>\n";
    			if ($this->num_pages <= $this->page) {
    				$tmp .=  '<td><span class="paging_arrows paging_disabled">'.$this->next.'</span></td>'."\n";
    				$tmp .=  '<td><span class="paging_arrows paging_disabled">'.$this->max.'</span></td>'."\n";
    			}else{
    				$tmp .= BASIC_GENERATOR::init()->element('td',null,
    					BASIC_GENERATOR::init()->link('<span class="paging_arrows">'.$this->next.'</span>',$this->script."?".$this->prefix.$this->page_url_var."=".($this->page+1),$ajax)
    				);
    				$tmp .= BASIC_GENERATOR::init()->element('td',null,
    					BASIC_GENERATOR::init()->link('<span class="paging_arrows">'.$this->max.'</span>',$this->script."?".$this->prefix.$this->page_url_var."=".($this->num_pages),$ajax)
    				);
    			}
    			$tmp .=  "</tr>\n";
    			return ($tmp ? BASIC_GENERATOR::init()->element('table',$attribute,$tmp) : '');
			}
		}
		return '';
	}
	/**
	 * @return HashMap
	 */
	public function getTemplateVars(){
		$arr = array();
		if($this->num_pages > 1){
			$start = 1;
	    	if ( ($this->page >= 6) && ($this->page <= $this->num_pages-6) ) {
	    		$start = $this->page-3;
	    	}elseif ($this->page >= $this->num_pages-6){
	    		$start = $this->num_pages-6;
	    	}	
	    	$pages = array();
	    	$sclink = $this->script."?".BASIC_URL::init()->serialize(array($this->prefix.$this->page_url_var));
	    	for ($i = $start; $i <= $start+6; $i++) { 
	    		if ($i <= 0) continue;
	    		$pages[] = array(
	    			$this->template_var_map['paging-current'] => ($i == $this->page),
	    			$this->template_var_map['paging-number'] => $i,
	    			$this->template_var_map['paging-link'] => BASIC_URL::init()->link($sclink.$this->prefix.$this->page_url_var."=".($i))
	    		);
	    	}
	    	$from = (($this->page-1)*$this->num_to_show)+1;
	    	$to = (($this->page-1)*$this->num_to_show)+$this->num_to_show;
	    	if($to > $this->total_number){
	    		$to = $this->total_number;
	    	}
			$arr = array(
	    		$this->template_var_map['min-link'] 	  => ( $this->page == 1 ? '' : BASIC_URL::init()->link($sclink.$this->prefix.$this->page_url_var."=1")),
	    		$this->template_var_map['prev-link'] 	  => ( $this->page == 1 ? '' : BASIC_URL::init()->link($sclink.$this->prefix.$this->page_url_var."=".($this->page-1))),
	    			
	    		$this->template_var_map['max-link'] 	  => ($this->num_pages <= $this->page ? '' : BASIC_URL::init()->link($sclink.$this->prefix.$this->page_url_var."=".($this->num_pages))),
	    		$this->template_var_map['next-link'] 	  => ($this->num_pages <= $this->page ? '' : BASIC_URL::init()->link($sclink.$this->prefix.$this->page_url_var."=".($this->page+1))),
	    				
	    	    $this->template_var_map['current-page']   => $this->page,
	    	    $this->template_var_map['max-pages'] 	  => $this->num_pages,
	    	        
	    	    $this->template_var_map['show-all-items'] => $this->total_number,
	    	    $this->template_var_map['show-from-items']=> $from,
	    	    $this->template_var_map['show-to-items']  => $to,
	    	    
	    	    $this->template_var_map['prefix'] 		  => $this->prefix,    
	    		$this->template_var_map['paging']		  => $pages
	    	);
		}
    	return $arr;
	}
	
	/**
	 * Създаване на лимитираща заявка. Най-вече се ползва когато има вероятност да се смени типа на SQL сървъра.
	 * <code>
	 * 		$query = " SELECT * FROM `table_name` WHWRE 1=1 AND `id` > 2021 ";
	 * 		$rdr = BASIC_SQL::init()->read_exec($query);
	 * 		
	 * 		if($rdr->num_rows()){
	 * 			$page = paging(150,21,'_');
	 * 			$rdr = BASIC_SQL::init()->read_exec($paging->SQLLimit($query,'id'));
	 * 		}
	 * 		while($rdr->read()){
	 * 			// results
	 * 		}
	 * 		
	 * </code>
	 *
	 * @author Kiril Keranov
	 * @version 0.2 beta[29-05-2007]
	 *
	 * @param string $query
	 * @param string [$field]
	 * @param string [$desc]
	 * 
	 * @return string
	 */
	function SQLLimit($query,$field = 0,$desc = 'ASC') {
		$num_show = 0;
		if($GLOBALS['BASIC_SQL']->server == 'mssql'){
			$num_show = $this->num_to_show;
		}

		return $GLOBALS['BASIC_SQL']->getLimit(
			$query,
			(($this->page-1)*$this->num_to_show+$num_show),
			$this->num_to_show,$field,
			$desc
		);
	}
	/**
	 * Генератора на лимитиращо допълнение към SQL заявка.
	 *
	 * @return string
	 */
	function limitCriteria(){
		return " limit ".(($this->page-1)*$this->num_to_show).",".$this->num_to_show;
	}
	/**
	 * Извличане на краищата на пространството от резултати което трябва да се покаже в даден момент.
	 *
	 * @param string $range [max|min]
	 * @return int/hash map
	 */
	function getSpace($range = ''){
		$num_show = 0;
		if($GLOBALS['BASIC_SQL']->server == 'mssql'){
			$num_show = $this->num_to_show;
		}
		
		$min = (($this->page-1)*$this->num_to_show+$num_show);
		$max = $min + $this->num_to_show;
		
		if($range == 'min'){
			return $min;
		}else if($range == 'max'){
			return $max;
		}else{
			return array(
				'from' => $min,
				'to' => $max		
			);
		}
	}
	/**
	 * Извличане на пространство от  даден масив което трябва да се покаже в даден момент.
	 *
	 * @param array $array
	 * @param boolean $isHash - filter's algorithm
	 * @return array
	 * @version 0.2
	 */
	function filterArray($array, $isHash = false){
		$space = $this->getSpace();
		
		$max = $space['to'];
		$min = $space['from'];
		$count = count($array);
		
		$tmp = array();
		
		if($min >= $count || !$count) return $tmp;
		
		if($isHash){
			$i = 0; foreach($array as $k => $v){
				if($i >= $min && $i < $max){
					$tmp[$k] = $v;
				}
				$i++;
				if($i == $max) break;
			}
		}else{
			for ($i = $min;$i < $count; $i++){
				if($i >= $min && $i < $max){
					$tmp[] = $array[$i];
				}
				if($i >= $max) break;
			}
		}
		return $tmp;
	}
	
	// Checkers 
	
	protected function _testRange($num,$type = ''){
	    $range = $this->getSpace();
	    if($type == 'from'){
	        if($num < $range['from']) return false;
	    }else if($type == 'to'){
	        if($num >= $range['to']) return false;
	    }else{
	       if($num < $range['from'] || $num >= $range['to']) return false;
	    }
	    return true;
	}
	/**
	 * Тестване дали подадения номер е по малък от долната граница на пространството.
	 *
	 * @param int $num
	 * @return boolen
	 */
	function getIsFrom($num){
	    return $this->__testRange($num,'from');
	}
	/**
	 * Тестване дали подадения номер е по голям от горната граница на пространството.
	 *
	 * @param int $num
	 * @return boolen
	 */
	function getIsTo($num){
	    return $this->_testRange($num,'to');
	}
	/**
	 * Тестване дали подадения номер е в границите на пространството.
	 *
	 * @param int $num
	 * @return boolen
	 */
	function getIsRange($num){
	    return $this->_testRange($num);
	}
	function getNumberPages(){
		return $this->num_pages;
	}
}

interface BasicSortingInterface{
	function sortlink($column, $text, $attribute = array(), $miss = array());
	function getPrefix();
	function createUrlForLink($column, $miss = array());
	function getsql();
}
/**
 * @author Evgeni Baldzisky
 * @version 0.7 
 * @since 10-02-2007
 * @package BASIC.BARS
 *	<code>
 * 		$sorting = new sorting('start_sortable_column_name','prefix_string');
 * 		
 * 		$sort_links = array(
 * 			$sorting->sortlink('column_1','Column TExt 1','style=width:150px;');
 * 			$sorting->sortlink('column_2','Column TExt 2','style=width:100px;');
 * 			$sorting->sortlink('column_3','Column TExt 3','style=width:150px;');
 * 			$sorting->sortlink('column_4','Column TExt 4','style=width:140px;');
 * 		)
 * 
 * 		BASIC_TEMPLATE2::init()->set('sort_bar',$sort_links);
 *	</code>
 */
class BasicSorting implements BasicSortingInterface{
	/**
	 * представка към променливите на бара при два или повече на една страница едновременно.
	 *
	 * @var string
	 */
	public $prefix = '';
	/**
	 * Оказва посоката на сортиране
	 *
	 * @var int
	 */
	protected $dir = -1;
	/**
	 * Оказва името на колоната по която се сортира.
	 *
	 * @var string
	 */
	protected $column = '';
	/**
	 * Оказва колоната по която да се сортира ако не е натиснат все още линк от бара.
	 *
	 * @var string
	 */
	protected $default = '';
	/**
	 * Оказва посоката на сортиране ако не е натиснат все още линк от бара.
	 *
	 * @var int
	 */
	protected $default_dir = 0;
	/**
	 * @param array|string $default
	 * @return obj
	 */
	function __construct($default = '', $prefix = ''){
		if($prefix){
			$this->prefix = $prefix."_" ;
		}
		if(BASIC_URL::init()->test($this->prefix.'dir')){
			$this->dir = (int)BASIC_URL::init()->request($this->prefix.'dir');
		}else{
			$this->dir = $this->default_dir;
		}
		if(BASIC_URL::init()->test($this->prefix.'column')){
			$this->column = BASIC_URL::init()->request($this->prefix.'column','addslashes');
		}else{
			$this->column = $this->default;
		}
		if($default != ''){
			$this->default = $default;
		}
	}
	public function getPrefix(){
		return $this->prefix;
	}
	/**
	 * Настроиване на подразбиращите се колона и посока на сортиране.
	 *
	 * @param string $column	- Оказва колоната по която ще се сортира
	 * @param int $dir 		- Оказва посоката на сортиране ( 0 - низходящ | 1 - възхадящ )
	 */
	function setDefailts($column, $dir = 0){
		$this->default = $column;
		$this->default_dir = $dir;
		$this->__construct();
	}
	public function createUrlForLink($column, $miss = array()){
		$miss[] = $this->prefix.'column';
		$miss[] = $this->prefix.'dir';		
		
		return BASIC_URL::init()->link(BASIC::init()->scriptName(), BASIC_URL::init()->serialize($miss)."&".
			$this->prefix."column=".$column."&".$this->prefix."dir=".($this->column == $column ? (int)(!$this->dir) : $this->default_dir)
		);
	}
	/**
	 * Линк генератор. Генерира HTML тог "а" с нужния "href".
	 *	<code>
	 * 		$sorting = new sorting('start_sortable_column_name','prefix_string');
	 * 		
	 * 		$sort_links = array(
	 * 			$sorting->sortlink('column_1','Column TExt 1','style=width:150px;');
	 * 			$sorting->sortlink('column_2','Column TExt 2','style=width:100px;');
	 * 			$sorting->sortlink('column_3','Column TExt 3','style=width:150px;');
	 * 			$sorting->sortlink('column_4','Column TExt 4','style=width:140px;');
	 * 		)
	 * 
	 * 		BASIC_TEMPLATE2::init()->set('sort_bar',$sort_links);
	 *	</code>
	 * 
	 * @param string $column
	 * @param string $text
	 * @param array [$attribute] 	[атрибути на тага]
	 * @param array [$miss] 		[променливи които не трябва да се предадат в състоянието]
	 * @return string
	 */
	public function sortlink($column, $text, $attribute = array(), $miss = array()){
		$attribute = BASIC_GENERATOR::init()->convertStringAtt($attribute);
		
		$attribute['href'] = $this->createUrlForLink($column, $miss);
		
		return BASIC_GENERATOR::init()->element("a", $attribute, $text);
	}
	/**
	 * Генератор на лимитиращото допълнение на SQL заявката.
	 *	<code>
	 * 		$sorting = new sorting('start_sortable_column_name','prefix_string');
	 * 
	 * 		$rdr = BASIC_SQL::init()->read_exec(" 
	 * 			SELECT * FROM `table_name` WHERE 1=1 ".$sorting->getsql()." 
	 * 		");
	 * 	</code>
	 * 
	 * @return string
	 */
	public function getsql(){
		if($column = $this->_getColumn()){
			return " ORDER BY ".$column.($this->dir ? ' DESC' : ' ');
		}
		return '';
	}
	/**
	 * version 0.2 support sort by multy properties.
	 * 
	 * @author Evgeni Baldziyski
	 * @version 0.2
	 * @since 15.12.2011
	 * 
	 * @param arrayCollection $coll
	 * @throws Exception
	 * @return arrayCollection
	 */
	public function sortCollection($coll){
		if(!$coll) return array();
		
		$sort_prop = str_replace("`", "", $this->_getColumn());
		$sort_prop = explode(",", $sort_prop);
		
		$sortable = array();
		$tmp_hash = array();
		
		$err = '';
		foreach($sort_prop as $v){
			if(!isset($coll[0][$v])){ 
				$err .= " ".$v;
			}
		}
		if($err){
			throw new Exception('Invalid property name: "'.$err.'". ', 1001); return null;
		}
		
		foreach ($coll as $i => $row){

			$key = '';
			foreach($sort_prop as $v){
				$key .= $row[$v];
			}
			
			if(isset($tmp_hash[$key])){
				$key .= $i;
			}
			
			$sortable[] = $key;
			$tmp_hash[$key] = $row;
		}
		$coll = array();
		
		if($this->dir){
			rsort($sortable);
		}else{
			sort($sortable);
		}
		
		foreach($sortable as $v){
			$coll[] = $tmp_hash[$v];
		}
		return $coll;
	}
	/**
	 * @param string $column
	 * @return string
	 */
	public function selected($column){
		return ($column == $this->column);
	}
	public function isDown(){
		return !!$this->dir;
	}
	
	/**
	 * @return string
	 */
	protected function _getColumn(){
		$column = '';
		if(!$this->column){
			if(is_array($this->default)){
				$tmp = array();
				foreach ($this->default as $v){
					$tmp[] = $this->_cleancolumn($v);
				}
				$column = implode(',',$tmp);
			}else{
				$column = $this->_cleancolumn($this->default);
			}
		}else{
			$column = $this->_cleancolumn($this->column);
		}
		return $column;
	}
	/**
	 * @param string|array $column
	 * @return string
	 */
	protected function _cleancolumn($column){
		if($column){
			$tmp = '';
			foreach(explode(',',$column) as $k => $v){
				preg_match("/^(([^\.]+)\.)?`?([a-zA-Z_0-9]+)`?$/", $v, $reg);
	
				if(isset($reg[2]) && $reg[2] != ''){
					$tmp .= $reg[2].".`".$reg[3]."`,";
				}else{
					$tmp .= "`".$reg[3]."`,";
				}	
			}
			return substr($tmp,0,-1);
		}
		return '';
	}
}