<?php
class DropDownData {

	public static function getYears(){
		$res = array();
		$cur_year = date('Y');
		
		$from_year = date('Y', strtotime("-3 years", strtotime($cur_year)));
		$to_year = date('Y', strtotime("+3 years", strtotime($cur_year)));
		
		for($i = $from_year; $i <= $to_year; $i++){
			$res[$i] = $i;
		}

		return $res;
	}
	
	public static function getMonths(){
		$res = array(
			1  => 'Януари',
			2  => 'Февруари',
			3  => 'Март',
			4  => 'Април',
			5  => 'Май',
			6  => 'Юни',
			7  => 'Юли',
			8  => 'Август',
			9  => 'Септември',
			10 => 'Октомври',
			11 => 'Ноември',
			12 => 'Декември'
		);
		return $res;
	}
	
	public static function getWorkDays($month, $year, $from_day = false, $to_day = false , $include_weekends = false) {
		//get days in month
		$number_month_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		
		BASIC::init()->imported('Holidays.cmp','cmp/');
		$obj = new HolidaysForm();
		
		$holidays = $obj->getHolidaysByYear($year, $month, true); 
		
		$work_days = array(0 => '---'); // set default value
		
		if (!empty($number_month_days)) {
			for ($day = 1; $day <= $number_month_days; $day++) {
				
				//if there is from day and the day is < go to next
				if ($from_day && ($day < $from_day)) continue;
				
				//if there is to day and the day is > go to next
				if ($to_day && ($day > $to_day)) continue;
				
				//get timestamp of the day
				$day_timestamp = mktime(0, 0, 0, $month, $day, $year);
				
				/*
				$workout_day = false;
				
				if (isset($holidays[$day])) {
					
					if ($holidays[$day]['status'] == 0) { //not working day
						continue;						
					} else if ($holidays[$day]['status'] == 1)  { //else otrabotvasht
						$workout_day = true;
					}
					
				}
				
				if (!$include_weekends && !$workout_day) {
				*/

				if (!$include_weekends) {
					
					$workout_day = false;
					
					if (isset($holidays[$day])) {
						
						if ($holidays[$day]['status'] == 1)  { //otrabotvasht
							$workout_day = true;
						} else {
							$workout_day = false;
						}
						
					}
					
					//exclude sundays and saturdays - always are not working days
					if ( date('w', $day_timestamp) % 6 == 0 && !$workout_day) {
						continue;
					}
					
				}
				
				//add to the workdays
				$work_days[$day] = $day;
			}
		}
		
        return $work_days;
	}
}