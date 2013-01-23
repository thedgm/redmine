<?php
<<<<<<< HEAD
    
=======

>>>>>>> release/0.0.1.1
if (!empty($_GET['type'])) {
    	
//	$db_host = 'localhost';
//	$db_user = 'root';
//	$db_pass = '1234';
//	$db_name = 'redmine';
	
	//server
<<<<<<< HEAD
	$db_host = 'support.sbnd.net';
	$db_user = 'redminetestlubo';
	$db_pass = '123';
	$db_name = 'redminetestlubo';
=======
	$db_host = 'localhost';
	$db_user = 'redmine_u';
	$db_pass = 'redmine_p';
	$db_name = 'redmine';
>>>>>>> release/0.0.1.1

	
	$connection = mysql_connect($db_host, $db_user, $db_pass);
	mysql_select_db($db_name, $connection);

	$cur_date = date('Y-m-d');
	$end_date = date('Y-m-d', strtotime('- 1 month', strtotime($cur_date)));
	
function getOfficialHolidaysData($cur_date, $end_date) {
	
	$holidays = array();
	$query = sprintf("SELECT full_date, status FROM holidays where full_date BETWEEN '%s' AND '%s'", $end_date, $cur_date);
	$results = mysql_query($query);
	
	if ($results) {
		$num = mysql_num_rows($results);
		
		if ($num) {
			while($row = mysql_fetch_assoc($results)) {
				$holidays[$row['full_date']] = $row['status'];
			}
		}			
	}
	
	return $holidays;
}

function getUsersTimeEntries($cur_date, $end_date) {
	
	$users_time_entries = array();
	
	$query = sprintf("SELECT tm.user_id, tm.spent_on, SUM(tm.hours) as hours
							FROM time_entries as tm
							WHERE tm.spent_on BETWEEN '%s' AND '%s'
							GROUP BY tm.user_id, tm.spent_on", $end_date, $cur_date);
	
	$results = mysql_query($query);
	
	$user_time_entries = array();
	
	if ($results) {
		while($row = mysql_fetch_assoc($results)) {
			$users_time_entries[$row['user_id']][$row['spent_on']] = array('user_id' => $row['user_id'],'date' => $row['spent_on'], 'hours' => $row['hours']);
			
		}
	}
	
	
	
	return $users_time_entries;
}

function getSBNDUsers() {
	
	$users = array();
	
	$query = sprintf("SELECT id, login, firstname, lastname, mail
							FROM users
							WHERE mail LIKE '%%@sbnd.net%%' AND id != 1 AND status!=3");
<<<<<<< HEAD

	$results = mysql_query($query);
=======
	
	
	$query = sprintf("SELECT 
					`projects`.`id` as `project_id`,
					`projects`.`name` as `project_name`,
				FROM `projects`, `members`
				WHERE
					`projects`.`id` = `members`.`project_id` AND
					`projects`.`status` = 1 AND
					`members`.`user_id` = %d", 181);
	
	//manager role id = 3
	
	$query = "SELECT * FROM member_roles WHERE role_id = 3";
	
//	echo $query;
	
	$results = mysql_query($query);
	$data = array('empty');
>>>>>>> release/0.0.1.1
	
	if ($results) {
		while($row = mysql_fetch_assoc($results)) {
			
<<<<<<< HEAD
			$users[$row['id']] = array(
=======
			$data[$row['id']] = $row;
			
			/*			
			$users[$row['id']] = array(
										'user_id' => $row['id'],
>>>>>>> release/0.0.1.1
										'user_login' => $row['login'],
										'firstname' => $row['firstname'],
										'lastname' => $row['lastname'],
										'mail' => $row['mail']
									);
<<<<<<< HEAD
=======
			*/
>>>>>>> release/0.0.1.1
		}
		
	}
	
<<<<<<< HEAD
=======
	echo "<pre>";
	var_dump($data);
	die();
	
>>>>>>> release/0.0.1.1
	return $users;
}

function mergeUserTimes($users, $users_times) {
	
	$users_full_data = array();

	foreach($users as $id=>$user) {
		
		if (isset($users_times[$id])) {
			$users_full_data[$id] = array('user_info' => $users[$id], 'user_time' => $users_times[$id]);
		} else {
			$users_full_data[$id] = array('user_info' => $users[$id]);
		}
		
	}
	
	return $users_full_data;
}

function geteriodDays($startDate, $endDate) {
     
	$day = 86400; // Day in seconds
	     
	$sTime = strtotime($startDate); // Start as time
	$eTime = strtotime($endDate); // End as time
	$numDays = round(($eTime - $sTime) / $day) + 1;	     
	$days = array();
	
	for ($d = 0; $d <= $numDays; $d++) {
	    	
		$day_timestamp = $sTime + ($d * $day);
		$day_number_of_week = date('N', $day_timestamp);
		
		$day_month_number = date('d', $day_timestamp);
		
		$days[] = date('Y-m-d', ($sTime + ($d * $day)));
	}
	     
	return $days;
}


	if ($_GET['type'] == 'daily') {
		$type = 1;		
	} else if ($_GET['type'] == '5days') {
		$type = 2;
	} else {
		die();
	}
	
	$holidaysData = getOfficialHolidaysData($cur_date, $end_date);
	$users_time_entries = getUsersTimeEntries($cur_date, $end_date);
	$sbnd_users = getSBNDUsers();
	
	$period_days = geteriodDays($end_date, $cur_date);
	$users_full_data = mergeUserTimes($sbnd_users, $users_time_entries);
	
	//empty or less than 8 hours for day
	$notify_not_correct_day = array();
	
	//empty for all period
	$notify_empty_for_all_period = array();

	if (!empty($users_full_data)) {
		
		foreach ($users_full_data as $user_id=>$u_data) {
			
			//if the user has time
			if (isset($u_data['user_time'])) {
				
				//check each day hours
				foreach ($period_days as $p_day) {
					
					//day number of week
					$day_number = date('w', strtotime($p_day));

					//set default working day
					$working_day = 1;
					
					//if the day is saturday or sunday -> non working day
					if ( ($day_number % 6) == 0) {
						$working_day = 0;
					}
					
					//if there is any official holidays data for this day
					if(isset($holidaysData[$p_day])) {
						
						if ($holidaysData[$p_day]) { //if this is working day
							$working_day = $holidaysData[$p_day];
						} else {
							$working_day = 0;
						}
						
					}
					
					//if there is any data for this day
					if (isset($u_data['user_time'][$p_day])) {
						
						//check hours
						if ($u_data['user_time'][$p_day]['hours'] < 8) {
							$notify_not_correct_day[$user_id][] = array(
								'user_name' => $u_data["user_info"]['firstname']." ".$u_data["user_info"]['lastname'],
								'mail' => $u_data["user_info"]['mail'],
								'day' => $p_day,
								'hours' => $u_data['user_time'][$p_day]['hours']
							);
						}
						
					} else { //else 0 hours for this day

						//check if this is working day
						if ($working_day) {
							
							$notify_not_correct_day[$user_id][] = array(
								'user_name' => $u_data["user_info"]['firstname']." ".$u_data["user_info"]['lastname'],
								'mail' => $u_data["user_info"]['mail'],
								'day' => $p_day,
								'hours' => 0
							);
														
						}
						 
					}
				}
			} else { // else the user has no any time -> notify him
				$notify_empty_for_all_period[] = array(
								'user_name' => $u_data["user_info"]['firstname']." ".$u_data["user_info"]['lastname'],
								'mail' => $u_data["user_info"]['mail']
							);
			}
			
		}
		
	}
<<<<<<< HEAD
	
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
=======

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$days_hours_body = '';
>>>>>>> release/0.0.1.1
	
	//check for not correct work days
	if (!empty($notify_not_correct_day)) {
		
		foreach ($notify_not_correct_day as $user_data) {
			
<<<<<<< HEAD
			if (count($user_data) >= 3) {
=======
			if (count($user_data) >= 3) { // notify the user
>>>>>>> release/0.0.1.1
				
				$mail_to = $user_data[0]['mail'];
				$mail_subject = "Непопълнени дни в redmine";
				$mail_body = "Здравейте {$user_data[0]['user_name']}, <br /> Имате непопълени часове за следните дни: <br />";
				
				foreach ($user_data as $day_data) {
<<<<<<< HEAD
					$mail_body .= "<p>{$day_data['day']} - {$day_data['hours']} часа</p>";
				}
				
				if ($mail_to == 'lpopivanov@sbnd.net') {
					mail($mail_to, $mail_subject, $mail_body, $headers);
				}
=======
					$days_hours_body .= "<p>{$day_data['day']} - {$day_data['hours']} часа</p>";
				}
				
				$mail_body .= $days_hours_body;
				
				
				echo "SEND TO $mail_to <br>";
				echo $mail_body."<br />";
				echo "<hr>";
				
//				if ($mail_to == 'lpopivanov@sbnd.net') {
//					mail($mail_to, $mail_subject, $mail_body, $headers);
//				}
			}
			
			if (count($user_data) >=5) { //notify the project owner and the redmine@sbnd.net
					$mail_subject = "Непопълнени часове и дни за $user_data[0]['user_name']";
					$mail_body = $user_data[0]['user_name']." има ".count($user_data)." дни с непопълнени часове";
					$mail_body .= $days_hours_body;

					echo "ADMIN SEND <br />";
					echo "SEND TO $mail_to <br>";
					echo $mail_body."<br />";
					echo "<hr>";
>>>>>>> release/0.0.1.1
			}
			
		}
		
	}

<<<<<<< HEAD
=======
	die('DONE');
	
>>>>>>> release/0.0.1.1
	//check for empty all period data
	if (!empty($notify_empty_for_all_period)) {
		
		$mail_subject = "Непопълнени дни в redmine";
		
		foreach ($notify_empty_for_all_period as $user_data) {
			
			$mail_to = $user_data['mail'];
			$mail_body = "Здравейте {$user_data['user_name']}, <br /> Имате непопълени дни за периода {$end_date} - {$cur_date} <br />";
			
			if ($mail_to == 'boyko@sbnd.net') {
				
				echo "<pre>";
				var_dump($mail_to, $mail_body);
				die();
				
				mail($mail_to, $mail_subject, $mail_body, $headers);				
			}
			
		}
	}
	
	echo "<pre>";
	var_dump($notify_not_correct_day);
	die();
	
	mysql_close($connection);
	
}
