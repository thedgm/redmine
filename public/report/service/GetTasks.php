<?php

#die("1");
//require_once(dirname(__FILE__)."/..install.php");
$tasks = array(array(
	'value' => '',
	'text' => '--- Изберете ---'
));

$project_id = (int)$_REQUEST['id'];

if($project_id){
	$query = 
		"SELECT `id`, `subject`
		FROM `issues`
		WHERE `status_id` NOT IN (5,12) AND `project_id` = ".$project_id." 
		ORDER BY `id` DESC";
	 

	$rdr = BASIC_SQL::init()->read_exec($query);
	while($rdr->read()){
		$tasks[] = array(
			'value' => $rdr->item('id'),
			'text' =>  $rdr->item('id').':  '.$rdr->item('subject')
		);
	}
	
}


die("[".json_encode(array(
	'data_field_1' => $tasks
))."]");
