<?php
class FormProjects extends DysplayComponent {
//    public $specialTest = 'validator';
	public $base = ''; 
	
    function main(){
		
    	if(!BASIC_SESSION::init()->get('UserID')){
    		//redirect to index for login
    		BASIC_SESSION::init()->distroy();
    		BASIC_URL::init()->redirect('index.php');
    	}
    	
    	$this->updateAction("list", 'ActionFormAdd');
    	$this->updateAction('save',null,'Вход »');
	   	$this->errorAction = 'add';
		
    	$this->template_form ='form_projects.tpl';
    	$this->template_list = 'base.tpl';

        //Year
        $this->setField('year',array(
			'text' => '',
        	'perm' 		=> '*',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> DropDownData::getYears(),
        		'onchange' => "changeWorkDays(this)"
			),
			'messages' 	=> array(
        		1 => 'Required'
        	),
        	'default' => date('Y')
		));
		
		//Month
        $this->setField('month',array(
			'text' => '',
        	'perm' 		=> '*',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> DropDownData::getMonths(),
        		'onchange' => "changeWorkDays(this)"
			),
			'messages' 	=> array(
        		1 => 'Required'
        	),
        	'default' => date('n')
		));
		
		//from date
        $this->setField('from_day',array(
			'text' => '',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> DropDownData::getWorkDays(date('n'), date('Y')),
        		'onchange' => 'fixDaysTo(this);'
			)
		));
		
		//to date
		$this->setField('to_day',array(
			'text' => '',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> DropDownData::getWorkDays(date('n'), date('Y'))
			)
		));
		
		//show weeekends
		$this->setField('weekend', array(
            'formtype' => 'checkbox',
            'attributes' => array(
            	'data' => array(
        			1 => 'weekend'
        		)
            )
        ));
        
		
		//Project
        $this->setField('prjct',array(
			'text' => '',
        	'perm' 		=> '*',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> $this->getProjectsByUserID(BASIC_SESSION::init()->get('UserID')),
      	 		'onchange' => "return servicesRequest('GetTasks', this.value, {'task' : 'data_field_1'});"
			),
			'messages' 	=> array(
        		1 => 'Required'
        	),
		));
		
		//Task
        $this->setField('task',array(
			'text' => '',
        	'perm' 		=> '*',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> array()
			),
			'messages' 	=> array(
        		1 => 'Required'
        	),
		));
		
		//Activity
        $this->setField('activity',array(
			'text' => '',
        	'perm' 		=> '*',
			'formtype' 	=> 'select',
			'attributes'=> array(
				'data' 	=> $this->getActivities()
			),
			'messages' 	=> array(
        		1 => 'Required'
        	),
        	'default' => 9 //Development
		));
        
		//Hours
        $this->setField('hours',array(
			'text' => '',
        	'perm' 		=> '*',
			'messages' 	=> array(
        		1 => 'Required'
        	),
        	'default' => 8 //Development
		));
		                
    	$this->prefix = 'pro';
    	$this->specialTest = 'validator';
    }

    function startPanel() {
        $this->startManager();
        $this->setMessage('year', 1);
    	
        
        return $this->createInterface();
    }
    
    function validator(){ 
    	
		$error = false;
	//	die('validator');
        if(!(int)$this->getDataBuffer('hours')){
			$this->setMessage('hours', 1);
		}

        return $error;
    }
    
    
    function ActionSave($id){
    	die('action save');
    	if(!$this->test()){
    		die('tova e ok');
    		$this->updateField('task', array(
    			'attributes' => array(
    				'data' => $this->getTasksByProjectID($this->getDataBuffer('prjct'))
    			)
    		));

    		return parent::ActionFormAdd($id);
    	}
    	else{
    		die('tova e test');
    		BASIC_ERROR::init()->append(11, "Попълнете всички задължителни полета");
    	}
    	return true;
    }
    
    function ActionError($id){ 	
    	if($this->getDataBuffer('prjct')){
    		//set field task
    		$this->updateField('task', array(
    			'attributes'=> array(
					'data' 	=> $this->getTasksByProjectID($this->getDataBuffer('prjct'))
				),
    		));
    		
    	}
    	return parent::ActionError($id);
    }
    
    //Project Field Data
    function getProjectsByUserID($user_id = 0){
		$result = array('' => ' --- Изберете ---');
		$query = 
				"SELECT 
					`projects`.`id` as `project_id`,
					`projects`.`name` as `project_name`
				FROM `projects`, `members`
				WHERE
					`projects`.`id` = `members`.`project_id` AND
					`projects`.`status` = 1 AND
					`members`.`user_id` = ".$user_id;
		
		$rdr = BASIC_SQL::init()->read_exec($query);
		while($rdr->read()){
			$result[$rdr->item('project_id')] = $rdr->item('project_name');
		}
		
		return $result;
	}
	
	//Task Field Data
	function getTasksByProjectID($project_id = 0){
		$result = array('' => ' --- Изберете ---');
		$query = 
				"SELECT `id`, `subject`
				FROM `issues`
				WHERE `status_id` NOT IN (5,12) AND `project_id` = ".$project_id." 
				ORDER BY `id` DESC";
		
		$rdr = BASIC_SQL::init()->read_exec($query);
		while($rdr->read()){
			$result[$rdr->item('id')] = $rdr->item('id').':  '.$rdr->item('subject');
		}
		
		return $result;
	}
	
	//Activity Field Data
	function getActivities(){
		$result = array();
		$query = 
			"SELECT `id`, `name`
			FROM `enumerations`
<<<<<<< HEAD
			WHERE `opt` = 'ACTI'
=======
			WHERE `active`
>>>>>>> release/0.0.1.1
			ORDER BY `position`";
		
		$rdr = BASIC_SQL::init()->read_exec($query);
		while($rdr->read()){
			$result[$rdr->item('id')] = $rdr->item('name');
		}
		
		return $result;
	}
	
	//get user data by days
	function getUserHistoryInfo($year, $month, $days) {
		
		$all_data = array();
		
		if (!empty($year) && !empty($month) && !empty($days)) {
			
			$user_id = BASIC_SESSION::init()->get('UserID');
//			$user_id = 175;

			//format dates
			foreach ($days as $key=>$day) {
				
				if ($key == 0) continue;

				$db_dates[] = "'".date("Y-m-d", mktime(0, 0, 0, $month, $day, $year))."'";
			}
			
			//format as string for db query
			$db_dates = implode(',', $db_dates);

			$query = sprintf("SELECT tm.id, p.name as project_name, SUM(tm.hours) as hours, tm.spent_on, isu.subject as issue_name
								FROM `time_entries` as tm
								LEFT JOIN `projects` as p
								ON tm.project_id = p.id
								LEFT JOIN `issues` as isu
								ON tm.issue_id = isu.id AND p.id = %d
								WHERE tm.`user_id` = %d AND tm.tyear = %d AND tm.tmonth = %d AND tm.spent_on IN (%s)
								GROUP BY tm.spent_on, tm.project_id", $this->misc_project_id, $user_id, $year, $month, $db_dates);

			$rdr = BASIC_SQL::init()->read_exec($query);
			$hours = array();
			
			while($rdr->read()) {
				$row = $rdr->getItems();
				$month_day = date('j', strtotime($row['spent_on']));
				
				if (isset($hours[$month_day])) {
					$hours[$month_day] = $hours[$month_day] + $row['hours'];					
				} else {
					$hours[$month_day] = $row['hours'];
				}
				
				$all_data[$month_day]['tasks'][] = $row;
				
			}
			
			//set total hours
			if (!empty($hours)) {
				foreach ($hours as $day=>$hours) {
					//day total hours
					$all_data[$day]['total_hours'] = $hours;
					if ($hours <= 0 ) {
						$class = 'bg-red';
					} else if ($hours < 8) {
						$class= 'bg-orange';
					} else if ($hours == 8) {
						$class= 'bg-grey';
					} else {
						$class= 'bg-green';
					}
					
					$all_data[$day]['total_hours_class'] = $class;
				}
			}
				
			
		}
		
		return $all_data;
		
	}
<<<<<<< HEAD
}
=======
}
>>>>>>> release/0.0.1.1
