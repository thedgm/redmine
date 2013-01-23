<?php
BASIC::init()->imported('FormProjects.cmp','cmp/');
BASIC::init()->imported('FormCalendar.cmp','cmp/');
class Report extends DysplayComponent {
    public $specialTest = 'validator';
	public $base = 'time_entries';
		
	private $templ =  array(
		'report.tpl',
		array(
			'form_projects' => 'form_projects',
			'form_calendar' => 'form_calendar'
		)
	);
    
    function main(){
    	$error = 1;
    	$this->prefix = 'calendar';
    }

    function startPanel() {
        $this->startManager();      
    	
        if(BASIC_SESSION::init()->get('UserID')){
        	$this->id = (int)BASIC_SESSION::init()->get('UserID');
        }
        
        return $this->createInterface();
       
    }
    
    function LIST_MANAGER($criteria = ''){
    	$form_projects = new FormProjects();
        $form_projects->main();
        $filter = $form_projects->startPanel();
        
       $calendar = '';
//       if(BASIC_URL::init()->request('cmdproSave') && !$form_projects->messages){ // click on button Clear       	
        	   $form_calendar = new FormCalendar();
        	   $form_calendar->filter_data = $form_projects->dataBuffer;
        	   $form_calendar->main();
        	   $calendar = $form_calendar->startPanel();
        
//      }
        BASIC_TEMPLATE2::init()->set(array(
        	$this->templ[1]['form_projects'] => $filter,
        	$this->templ[1]['form_calendar'] => $calendar,  
        ),$this->templ[0]);
        return BASIC_TEMPLATE2::init()->parse($this->templ[0]);
    }
    
    function validator(){        
		$error = false;
       
        return $error;
    }
    
    function ActionSave($id){

    	$calendar = new FormCalendar();
    	$calendar->ActionSave($_POST);
    	
    }
}