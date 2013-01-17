<?php
class FormCalendar extends DysplayComponent {
    public $specialTest = 'validator';
	public $base = 'time_entries';
	public $misc_project_id = 41;
	public $misc_illness_issue_id = 2288;
	public $misc_holiday_issue_id = 2187;
	
	
	public $filter_data = array(
		'year' 		=> 'year',
		'month' 	=> 'month',
		'prjct' 	=> 'prjct',
		'task' 		=> 'task',
		'activity' 	=> 'activity',
		'hours' 	=> 'hours',
		'from_day'  => 'from_day',
		'to_day'  => 'to_day',
		'weekend' => 'weekend'
	);
	
	public $week_days = array(
		1 => 'Понеделник',
		2 => 'Вторник',
		3 => 'Сряда',
		4 => 'Четвъртък',
		5 => 'Петък',
		6 => 'Събота',
		7 => 'Неделя'
	);
	
	public $field_data = array(
		1 => 'Проект',
		2 => 'Болничен',
		3 => 'Отпуск',
		4 => 'Неработен'
	);
	
	public  $work_days = array();
    
    function main(){
		
    	$this->template_list = 'form_calendar.tpl';
    	$this->template_form ='form_calendar.tpl';

        $this->prefix = 'c';
        $this->updateAction("List", 'ActionFormAdd');       
        
        $tmp = '';
    	
//        $this->createInterface();
    }

   function startPanel() {
   		
		$this->startManager();
		
		$from_day = (!empty($this->filter_data['from_day'])) ? $this->filter_data['from_day'] : 0;
		$to_day = (!empty($this->filter_data['to_day'])) ? $this->filter_data['to_day'] : 0;
		$include_weekends = (!empty($this->filter_data['weekend'])) ? $this->filter_data['weekend'] : 0;
		
		$year = $this->filter_data['year'];
		$month = $this->filter_data['month'];
		
		$month_work_days = DropDownData::getWorkDays($month, $year, $from_day, $to_day, $include_weekends);
		
		BASIC::init()->imported('Holidays.cmp','cmp/');
		$holidays_obj = new HolidaysForm();
		$holidays_full = $holidays_obj->getHolidaysByYear($year, $month);
		
		if (!empty($holidays_full)) {
			foreach ($holidays_full as $holiday) {
				$holidays[$holiday['day_number']] = $holiday['status']; 
			}
		}
		
		$officialWork = false;
		
		//if there are empty values in the filter don`t set workdays 
		if (!empty($month_work_days) && !empty($this->filter_data['prjct']) && !empty($this->filter_data['activity']) && !empty($this->filter_data['hours']) && !empty($this->filter_data['task'])) {
			foreach ($month_work_days as $key => $day) {
				if (empty($key)) continue;

//				$this->setField('day_'.$day, array(
//	        		'text' => '',
//	        		'formtype' => 'radio',
//					'attributes' => array(
//						'data' 	=> $this->field_data
//					)
//	        	));

				$date_timestamp = strtotime($this->filter_data['year']."-".$this->filter_data['month']."-".$day);
	        	$day_of_week = date('N', $date_timestamp);
				$formated_date = date('d-m-Y', $date_timestamp);
				$week = date('W', $date_timestamp);
				
				
				if (isset($holidays[$day])) {
					
					if ($holidays[$day] == 0) {
						$officialWork = 'officialNotWork';
					} else if ($holidays[$day] == 1) {
						$officialWork = 'officialWork';
					}

				} else {
					$officialWork = false;
				}
				
	        	$this->work_days[$week][] = array(
								        		'day_number' => $day, 
								        		'weekday_name' => $this->week_days[$day_of_week], 
								        		'week_day' => $day_of_week,
	        									'format_date' => $formated_date,
	        									'day_class' => $officialWork
								        	); 
			}
		}
		
		$template_data[] = $this->template_list;
		
		if (!empty($this->work_days)) {

			$user_month_data = FormProjects::getUserHistoryInfo($year, $month, $month_work_days);

			//if there is any user history add it to array
			if (!empty($user_month_data)) {
				$period_total_hours = 0;
				foreach($this->work_days as &$wday) {
					
					foreach ($wday as &$day) {
						
						if (!empty($user_month_data[$day['day_number']])) {
							$day['user_history'] = $user_month_data[$day['day_number']];
							
							$period_total_hours = $period_total_hours + $user_month_data[$day['day_number']]['total_hours'];
						}						
					}
				}
			}
			
			$template_data['day'] = $this->work_days;
		}
		
		$template_data['total_work_hours'] = (!empty($period_total_hours)) ? $period_total_hours : 0;
		$template_data['field_data'] = $this->field_data;
		$template_data['month'] = $month;
		$template_data['year'] = $year;
		$template_data['activity'] = $this->filter_data['activity'];
		$template_data['project'] = (!empty($this->filter_data['prjct'])) ? $this->filter_data['prjct'] : 0;
		$template_data['task'] = (!empty($this->filter_data['task'])) ? $this->filter_data['task'] : 0;
		$template_data['hours'] = $this->filter_data['hours'];
		
		BASIC_TEMPLATE2::init ()->set($template_data);
		
		return BASIC_TEMPLATE2::init()->parse($this->template_list);
    }

    function ActionSave($data){
    	
    	if (!empty($data['year']) && !empty($data['month']) && !empty($data['activity']) && !empty($data['project']) && !empty($data['task']) && !empty($data['hours']) && !empty($data['days'])) {
    		
    		$pattern = '/^[0-9]+/';
    		
    		//some validation
    		$year = (preg_match($pattern, $data['year'])) ? $data['year'] : die();
    		$month = (preg_match($pattern, $data['month'])) ? $data['month'] : die();
    		$activity = (preg_match($pattern, $data['activity'])) ? $data['activity'] : die();
    		$project = (preg_match($pattern, $data['project'])) ? $data['project'] : die();
    		$task = (preg_match($pattern, $data['task'])) ? $data['task'] : die();
    		$hours = (preg_match($pattern, $data['hours'])) ? $data['hours'] : die();

    		$user_id = BASIC_SESSION::init()->get('UserID');
    		
    		$queries = array();
    		
    		//generate the queries
    		foreach ($data['days'] as $day=>$issue) {
    			
    			//day validation
    			if (!preg_match($pattern, $day)) die();

    			//issue validation
    			if (!isset($this->field_data[$issue[0]])) die();
    			
    			switch ($issue[0]) {
    				case 2 : //if the day issue is illness add illnes query
    					$query_project = $this->misc_project_id;
    					$query_task = $this->misc_illness_issue_id;
    					
    				break;
    				case 3 : //if the day issue is holiday add holiday query
    					$query_project = $this->misc_project_id;
    					$query_task = $this->misc_illness_issue_id;
    					
    				break;
    				case 4 : //neraboten skip the query
    					continue 2;
    				break;
    				default:
    					$query_project = $project;
    					$query_task = $task;
    				break;
    			}

    			$timestamp = mktime(0, 0, 0, $month, $day, $year);
    			$spent_date = date('Y-m-d', $timestamp);
    			$week_number = date('W', $timestamp);
    			$now = date('Y-m-d G:i:s');

    			$query = sprintf("INSERT into `time_entries` (`project_id`, `user_id`, `issue_id`, `hours`, `activity_id`, `spent_on`, `tyear`, `tmonth`, `tweek`, `created_on`, `updated_on`)
    								VALUES (%d, %d, %d, %d, %d, '%s', %d, %d, %d, '%s', '%s')", $query_project, $user_id, $query_task, $hours, $activity, $spent_date, $year, $month, $week_number, $now, $now);
    			
    			$queries[] = $query;    			
    		}
    		
    		if (!empty($queries)) {
				//execute queries
				BASIC_SQL::init()->multi_exec($queries);
    		}
	    		
    	}
    	
    	//redirect
    	BASIC_URL::init()->link("/"); 
    	
    }
    
}