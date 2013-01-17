<?php

class HolidaysForm extends DysplayComponent {
	
    public $specialTest = 'validator';
	public $base = 'holidays';
    
	private $day_statuses = array('Почивен', 'Отработва');
	
    function main(){
		if(!BASIC_SESSION::init()->get('isadmin')){
			//redirect to index for login
			BASIC_SESSION::init()->distroy();
			BASIC_URL::init()->redirect('index.php');
    	}
    	
    	BASIC::init()->ini_set('script_name', 'index.php?holidays=1');
    	
    	$this->updateAction("list", 'ActionFormAdd');
//    	$this->updateAction('save','ActionSave');
//	   	$this->errorAction = 'add';
	   	
	   	$this->template_form ='holidays.tpl';
//    	$this->template_list = 'base.tpl';
    	
    	
    	//Year
        $this->setField('year',array(
			'text' => '',
        	'perm' 		=> '*',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> DropDownData::getYears(),
        		'onchange' => "getHolidaysByYear(this)"
			),
			'messages' 	=> array(
        		1 => 'Required'
        	),
        	'default' => date('Y')
		));
		
		$this->prefix = 'holiday';
		
    }

    function startPanel() {
        $this->startManager();      
		$this->setMessage('year', 1);
		
		$template_data = array();
		
		$holidays = $this->getHolidaysByYear(date('Y'));
		
		if (!empty($holidays)) {
			$template_data['table_data'] = $holidays;			
		}
		
		$template_data['day_statuses'] = $this->day_statuses;
		
		BASIC_TEMPLATE2::init()->set($template_data);
		
        return $this->createInterface();
       
    }
    
    function validator(){        
		$error = false;
       
        return $error;
    }
    
	public function getHolidaysByYear($year, $month = false, $day_key = false) {
		
		if(!BASIC_SESSION::init()->get('UserID')){
			//redirect to index for login
			BASIC_SESSION::init()->distroy();
			BASIC_URL::init()->redirect('index.php');
    	}
    	
		$holidays = array();
	
		//set year
		$year = (!empty($year) && preg_match('/^[0-9]+$/', $year)) ? $year : 0;
		
		//if there is month passed - validate
		if ($month) $month = (!empty($month) && preg_match('/^[0-9]+$/', $month)) ? $month : 0;

		if(!empty($year)){
			
			$query = array();
			
			//set query data
			
			$query[] = sprintf("SELECT * FROM `%s`", $this->base);
			
			if (!empty($month)) {
				$query[] = sprintf("WHERE year = %d AND month = %d", $year, $month);				
			} else {
				$query[] = sprintf("WHERE year = %d", $year);
			}
			
			$query[] = "ORDER BY `full_date` ASC";
			
			//build query
			$query = implode(" ", $query);
			
			
			//execute query
			$rdr = BASIC_SQL::init()->read_exec($query);
			
			while($rdr->read()){
				
				$day_number = date("j", strtotime($rdr->item('full_date')));
				
				if ($day_key) {
					
					$holidays[$day_number] = array(				
						'id' => $rdr->item('id'),
						'year' => $rdr->item('year'),
						'month' => $rdr->item('month'),
						'status' => $rdr->item('status'),
						'status_text' => $this->day_statuses[$rdr->item('status')],
						'text' => $rdr->item('text'),
						'full_date' => $rdr->item('full_date'),
						'date_created' => $rdr->item('date_created'),
						'by_user' => $rdr->item('by_user'),
						'day_number' => $day_number
					);
					
				} else {
					$holidays[] = array(				
						'id' => $rdr->item('id'),
						'year' => $rdr->item('year'),
						'month' => $rdr->item('month'),
						'status' => $rdr->item('status'),
						'status_text' => $this->day_statuses[$rdr->item('status')],
						'text' => $rdr->item('text'),
						'full_date' => $rdr->item('full_date'),
						'date_created' => $rdr->item('date_created'),
						'by_user' => $rdr->item('by_user'),
						'day_number' => $day_number
					);
				}
				
			}
		}

		return $holidays;
	}
	
	function ActionAdd($data) {

		if(!BASIC_SESSION::init()->get('isadmin')){
			//redirect to index for login
			BASIC_SESSION::init()->distroy();
			BASIC_URL::init()->redirect('index.php');
    	}
    	
		$admin_user = BASIC_SESSION::init()->get('UserID');
		
		if (!empty($data['day']) && !empty($data['day_status']) && !empty($data['day_info']) && !empty($data['year'])) {
			
			$queries = array();

			foreach ($data['day'] as $key=>$day_date) {
				
				$day_timestamp = strtotime($day_date);
				$day_year = date('Y', $day_timestamp);
				$day_month = date('m', $day_timestamp);
				$day_text = $data['day_info'][$key];
				$day_status = $data['day_status'][$key];
				$day_status_key = array_search($day_status, $this->day_statuses);
				$day_full_date = date('Y-m-d', $day_timestamp);
				$created_date = date('Y-m-d G:i:s');
				
				
				if ( $day_status_key !== false) {
					$day_status = $day_status_key; 
				} else {
					$day_status = 0;	 
				}
				
				$query = sprintf("INSERT INTO `%s` (`year`, `month`, `status`, `text`, `full_date`, `date_created`, `by_user`) 
									VALUES (%d, %d, %d, '%s', '%s', '%s', %d)", $this->base, $day_year, $day_month, $day_status, $day_text, $day_full_date, $created_date, $admin_user);
				
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
	
	function deleteHolidayById($holiday_id) {
		
		if(!BASIC_SESSION::init()->get('isadmin')){
			//redirect to index for login
			BASIC_SESSION::init()->distroy();
			BASIC_URL::init()->redirect('index.php');
    	}
    	
    	$holiday_id = (!empty($holiday_id) && preg_match('/^[0-9]+$/', $holiday_id)) ? $holiday_id : 0;
    	
		if (!empty($holiday_id)) {
			$query = sprintf("DELETE FROM `%s` WHERE id=%d", $this->base, $holiday_id);
			
			BASIC_SQL::init()->exec($query);
			
			return 1;
		}
		
		return 0;
	}
    
}