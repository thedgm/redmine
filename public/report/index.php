<?php
require_once('install.php');

//check for Logout
if((int)BASIC_URL::init()->request('logout')){
	BASIC_SESSION::init()->distroy();
    BASIC_URL::init()->redirect('index.php');
}

if(BASIC_SESSION::init()->get("UserID")){
	BASIC::init()->imported('Report.cmp','cmp/');
	$cmp = new Report();
}
else{
	BASIC::init()->imported('Login.cmp','cmp/');
	$cmp = new Login();
}

if (BASIC_SESSION::init()->get('isadmin') && (int)BASIC_URL::init()->request('holidays')) {

	BASIC::init()->imported('Holidays.cmp','cmp/');
	$cmp = new HolidaysForm();

	if (BASIC_URL::init()->request('holidaycmdsave')) {
		
		$cmp->ActionAdd($_POST);
		
	} else if(BASIC_URL::init()->request('removeHoliday')) {
		
		$responce = $cmp->deleteHolidayById(BASIC_URL::init()->request('holiday_id'));
		die(json_encode($responce));
		
	} else if (BASIC_URL::init()->request('getYearHolidays')) {
		
		$holidays = $cmp->getHolidaysByYear(BASIC_URL::init()->request('year'));

		die(json_encode($holidays));
		
	}

}

//check for Service
if (BASIC_URL::init()->request('service')) {

	define('SERVICE_OPEN',true);
	
	$service_name = BASIC_URL::init()->request('service');

	if ($service_name == "getWorkDays") {
		//set year
		$year = (!empty($_POST["year"]) && preg_match('/^[0-9]+$/', $_POST["year"])) ? $_POST["year"] : 0;
		//set month
		$month = (!empty($_POST["month"]) && preg_match('/^[0-9]+$/', $_POST["month"])) ? $_POST["month"] : 0;;

		if (!empty($year) && !empty($month)) {
			BASIC::init()->imported('DropDownData.lib', 'cmp/');
			
			$work_days_obj = new DropDownData();
			$work_days = $work_days_obj->getWorkDays($month, $year);
			
			die("[".json_encode(array(
				'workdays' => $work_days
			))."]");
		}
		
	} else if ($service_name == "GetTasks"){
		 BASIC::init()->imported($service_name,'service/');	
	}
	
	die();
}

$cmp->main();

$content = $cmp->startPanel();

$errors = array();
if(BASIC_ERROR::init()->exist()){
	BASIC_ERROR::init()->reset();
	while($err = BASIC_ERROR::init()->error()){
		$err['message'] = addslashes($err['message']);
		$errors[] = $err;
	}
}

BASIC_TEMPLATE2::init()->set(array(
	'USERNAME'  => BASIC_SESSION::init()->get('username'),
	'CONTENT' 	=> $content,
	'ERRORS' 	=> $errors,
	'ISADMIN'	=> BASIC_SESSION::init()->get('isadmin')
), BASIC::init()->ini_get('baseTemplate'));

print(BASIC_TEMPLATE2::init()->parse('base.tpl'));
