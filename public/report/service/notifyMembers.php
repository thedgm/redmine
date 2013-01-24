<?php
    
	require_once('class.phpmailer.php');
	
//	$db_host = 'localhost';
//	$db_user = 'root';
//	$db_pass = '1234';
//	$db_name = 'redmine';
	
	//server
	$db_host = 'localhost';
	$db_user = 'redmine_u';
	$db_pass = 'redmine_p';
	$db_name = 'redmine';

	
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
	
	$query = sprintf("SELECT tm.user_id, tm.spent_on, SUM(tm.hours) as hours, tm.project_id
							FROM time_entries as tm
							WHERE tm.spent_on BETWEEN '%s' AND '%s'
							GROUP BY tm.user_id, tm.spent_on", $end_date, $cur_date);
	$results = mysql_query($query);
	
	$user_time_entries = array();
	
	if ($results) {
		while($row = mysql_fetch_assoc($results)) {
			$users_time_entries[$row['user_id']][$row['spent_on']] = array(
																		'user_id' => $row['user_id'],
																		'date' => $row['spent_on'], 
																		'hours' => $row['hours'],
																		'project_id' => $row['project_id']
																	);
			
		}
	}
	
	
	
	return $users_time_entries;
}

function getSBNDUsers() {
	
	$users = array();
	
	$query = sprintf("SELECT u.id, u.login, u.firstname, u.lastname, u.mail, m.project_id, pr.name as project_name
							FROM users as u
							LEFT JOIN `members` as m 
							ON m.user_id = u.id
							INNER JOIN projects as pr
							ON pr.id = m.project_id AND pr.status = 1
							WHERE u.mail LIKE '%%@sbnd.net' AND u.id != 1 AND u.status=1");
	
	$results = mysql_query($query);
	
	if ($results) {
		while($row = mysql_fetch_assoc($results)) {
			
			
			if (isset($users[$row['id']])) {
				$users[$row['id']]['projects'][] = $row['project_id'];
//				$users[$row['id']]['projects'][] = array(
//														'project_id' => $row['project_id'],
//														'project_name' => $row['project_name']
//													);
			} else {
				$users[$row['id']] = array(
										'user_id' => $row['id'],
										'user_login' => $row['login'],
										'firstname' => $row['firstname'],
										'lastname' => $row['lastname'],
										'mail' => $row['mail'],
										'projects' => array($row['project_id'])
//										'projects' => array( 0 => array(
//																'project_id' => $row['project_id'],
//																'project_name' => $row['project_name']
//															)
//														)
									);
													
			}
			
		}
		
	}
	
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
	$numDays = round(($eTime - $sTime) / $day);	     
	$days = array();

	for ($d = 0; $d <= $numDays; $d++) {
	    	
		$day_timestamp = $sTime + ($d * $day);
		$day_number_of_week = date('N', $day_timestamp);
		
		$day_month_number = date('d', $day_timestamp);
		
		$days[] = date('Y-m-d', ($sTime + ($d * $day)));
	}
	     
	return $days;
}

function getManagers() {
	
	$managers = array();
	
	$query = "SELECT m.project_id, u.id as user_id, u.login, u.firstname, u.lastname, u.mail, pr.name as project_name, mr.role_id
				FROM members AS m
				INNER JOIN users AS u 
				ON u.id = m.user_id AND u.mail LIKE '%%@sbnd.net' AND u.id != 1 AND u.status=1
				INNER JOIN member_roles AS mr 
				ON mr.member_id = m.id AND mr.role_id=3
				INNER JOIN projects as pr
				ON pr.id = m.project_id AND pr.status = 1";
	
	$results = mysql_query($query);
	
	if ($results) {
		while($row = mysql_fetch_assoc($results)) {
			
			if (!empty($row['project_id'])) {
				$managers[$row['project_id']][] = array(
//										'manager_id' => $row['user_id'],
//										'manager_login' => $row['login'],
										'manager_firstname' => $row['firstname'],
										'manager_lastname' => $row['lastname'],
										'manager_mail' => $row['mail'],
//										'project_id' => $row['project_id'],
//										'role_id' => $row['role_id'],
//										'project_name' => $row['project_name']
									);
			}
			
		}
	}
	
	return $managers;
}

function setManagers($user_projects, $managers, $cur_user) {
	
	$project_managers_mails = array();
	
	if (!empty($user_projects) && !empty($managers)) {
		foreach ($user_projects as $project) {
			
			if (isset($managers[$project])) {
				foreach ($managers[$project] as $manager) {
					if (!in_array($manager['manager_mail'], $project_managers_mails) && $manager['manager_mail'] != $cur_user) {
						array_push($project_managers_mails, $manager['manager_mail']);
					}
				}
			}
			
		}
	}
	
	return $project_managers_mails;
}


	$holidaysData = getOfficialHolidaysData($cur_date, $end_date);
	$users_time_entries = getUsersTimeEntries($cur_date, $end_date);
	$sbnd_users = getSBNDUsers();
	$managers = getManagers();
	
	$period_days = geteriodDays($end_date, $cur_date);
	//add times
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
								'hours' => $u_data['user_time'][$p_day]['hours'],
								'projects' => $u_data['user_info']['projects']
							);
						}
						
					} else { //else 0 hours for this day

						//check if this is working day
						if ($working_day) {
							
							$notify_not_correct_day[$user_id][] = array(
								'user_name' => $u_data["user_info"]['firstname']." ".$u_data["user_info"]['lastname'],
								'mail' => $u_data["user_info"]['mail'],
								'day' => $p_day,
								'hours' => 0,
								'projects' => $u_data['user_info']['projects']
							);
														
						}
						 
					}
				}
			} else { // else the user has no any time -> notify him
				$notify_empty_for_all_period[] = array(
								'user_name' => $u_data["user_info"]['firstname']." ".$u_data["user_info"]['lastname'],
								'mail' => $u_data["user_info"]['mail'],
								'projects' => $u_data['user_info']['projects']
							);
			}
			
		}
		
	}

	$days_hours_body = '';

	$mail = new PHPMailer();
	$mail->mailer = "smtp";
	$mail->From = 'redmine_test@sbnd.net';
	$mail->FromName = 'REDMINE TEST';
	$mail->Host = "smtp.sbnd.net";
	$mail->SMTPDebug  = false;
	$mail->CharSet = 'UTF-8';
	
	$mail->IsHTML(true);
	$mail->IsSMTP();
	
	
	//check for not correct work days
	if (!empty($notify_not_correct_day)) {
		
		echo "NOT CORRECT DAYS <br />";
		
		foreach ($notify_not_correct_day as $user_data) {
			
			if (count($user_data) >= 3) { // notify the user
				
				$mail_to = $user_data[0]['mail'];
				$mail_subject = "Непопълнени дни в redmine";
				$mail_body = "Здравейте {$user_data[0]['user_name']}, <br /> Имате непопълени часове за следните дни: <br />";
				$days_hours_body = '';
				
				foreach ($user_data as $day_data) {
					$days_hours_body .= "<p>{$day_data['day']} - {$day_data['hours']} часа</p>";
				}
				
				$mail_body .= $days_hours_body;
				
				$project_managers_mails = setManagers($user_data[0]['projects'], $managers, $mail_to);
				
					$mail->Subject = $mail_subject;
					$mail->MsgHTML($mail_body);
					$mail->AddAddress($mail_to, $user_data[0]['user_name']);
					
					
					/*
					 * Uncomment on production
					if(!$mail->Send()) {
					  echo "Mailer Error: " . $mail->ErrorInfo . "<br />";
					  die();
					}
					*/
					
					echo "<hr>";
					echo "MAIL TO: ".$mail_to."<br />";
					echo "MAIL SUBJECT: ".$mail_subject."<br />";
					echo "MAIL BODY: ".$mail_body."<br />";
				
			}
			
			
			if (count($user_data) >=5 && !empty($project_managers_mails)) { //notify the project owner and the redmine@sbnd.net
				
				$mail_subject = "Непопълнени часове и дни за ".$user_data[0]['user_name'];
				$mail_body = $user_data[0]['user_name']." има ".count($user_data)." дни с непопълнени часове";
				$mail_body .= $days_hours_body;
				
				$mail->Subject = $mail_subject;
				$mail->MsgHTML($mail_body);
					
				$mail->AddAddress('redmine@sbnd.net', $user_data[0]['user_name']);
				
				$managers_mails = '';
				
				foreach ($project_managers_mails as $k=>$manager_mail) {
//					$manager_mail = 'popivanov.lubomir@gmail.com';
					$mail->AddBCC($manager_mail);
					$managers_mails .= " {$manager_mail} ";
				}
				
				/*
				 * Uncomment on production
				if(!$mail->Send()) {
					echo "Mailer Error: " . $mail->ErrorInfo . "<br />";
					die();
				}
				*/
					
				
				echo "---------------------------------------------- <br />";
				echo "MAIL TO MANAGERS FOR USER: ".$user_data[0]['user_name']."<br />";
				echo "MAIL TO: redmine@sbnd.net <br />";
				echo "MAIL TO MANAGERS: $managers_mails <br />";
				echo "MAIL SUBJECT: ".$mail_subject."<br />";
				echo "MAIL BODY: ".$mail_body."<br />";
					
			}
			
		}
		
	}
	 
	
	
	//check for empty all period data
	if (!empty($notify_empty_for_all_period)) {
		
		echo "============================================= <br />";
		echo "NOT CORRECT FOR ALL PERIOD (-1 month) <br />";
		
		$mail_subject = "Непопълнени дни в redmine";
		
		foreach ($notify_empty_for_all_period as $user_data) {
			
			$mail_to = $user_data['mail'];
			$mail_body = "Здравейте {$user_data['user_name']}, <br /> Имате непопълени дни за периода {$end_date} - {$cur_date} <br />";
			
			$mail->Subject = $mail_subject;
			$mail->MsgHTML($mail_body);
			
			$mail->AddAddress($mail_to, $user_data['user_name']);
			
			echo "<hr>";
			echo "MAIL TO: ".$mail_to."<br />";
			echo "MAIL SUBJECT: ".$mail_subject."<br />";
			echo "MAIL BODY: ".$mail_body."<br />";
			
			/**
			 * 
			 * Uncomment on production
			if(!$mail->Send()) {
			  echo "Mailer Error: " . $mail->ErrorInfo . "<br />";
			  die();
			}
			*/
			
			$mail_subject = "Непопълнени часове и дни за периода {$end_date} - {$cur_date}";
			$mail_body = $user_data['user_name']." има непопълнени часове и дни за периода {$end_date} - {$cur_date}";
				
			$mail->Subject = $mail_subject;
			$mail->MsgHTML($mail_body);
				
			$mail->AddAddress('redmine@sbnd.net', $user_data['user_name']);
			
			
			/*
			 * Uncomment of produktion
			 
			if(!$mail->Send()) {
				echo "Mailer Error: " . $mail->ErrorInfo . "<br />";
				die();
			}
			*/
				
			$project_managers_mails = setManagers($user_data['projects'], $managers, $mail_to);

			if (!empty($project_managers_mails)) {
				
				
				$managers_mails = '';
				
				foreach ($project_managers_mails as $k=>$manager_mail) {
						
//					$manager_mail = 'popivanov.lubomir@gmail.com';
					$mail->AddBCC($manager_mail);
					$managers_mails .= " {$manager_mail} ";
				}
					
			}
			
			echo "---------------------------------------------- <br />";
			echo "MAIL TO MANAGERS FOR USER: ".$user_data['user_name']."<br />";
			echo "MAIL TO: redmine@sbnd.net <br />";
			echo "MAIL TO MANAGERS: $managers_mails <br />";
			echo "MAIL SUBJECT: ".$mail_subject."<br />";
			echo "MAIL BODY: ".$mail_body."<br />";
			
				
				
		}
	}
	
	mysql_close($connection);
