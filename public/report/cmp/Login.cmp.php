<?php
class Login extends DysplayComponent {
    public $parent;
   	public $specialTest = 'validator';
	public $base = 'users';

    function main(){


    	$this->template_form = 'login.tpl';
    	$this->updateAction("list", 'ActionFormAdd');
    	$this->updateAction('save',null,'Вход »');
    	$this->errorAction = 'add';
		
		$this->delAction("cancel");
		$this->delAction("delete");
		
		$this->template_list = 'base.tpl';

        $this->updateAction('List', 'ActionOpen');      
       
        $this->setField('username',array(
            'text' 		=> '',
        	'perm' 		=> '*',
        ));
        $this->setField('password',array(
            'text' 		=> '',
        	'perm' 		=> '*',
        	'formtype' => 'password',
        ));
        
    }
   
    function startPanel() {
        $this->startManager();      
    	
        return $this->createInterface();
    }
    
    function validator(){        
        $error = false;      
        $rdr = BASIC_SQL::init()->read_exec("SELECT `id`, `login`, `admin` FROM `".$this->base."` WHERE `login`= '".$this->getDataBuffer('username')."' AND `hashed_password` = SHA1('".$this->getDataBuffer('password')."') LIMIT 1");
        $rdr->read();
        if(!$rdr->num_rows()) {
        	BASIC_ERROR::init()->append(10, "Невалиден потребител или парола.");
        	$error = true;
        }
        else {
        	$this->id = $rdr->item('id');
        	BASIC_SESSION::init()->set('UserID', $this->id);
        	BASIC_SESSION::init()->set('username', $rdr->item('login'));
        	BASIC_SESSION::init()->set('isadmin', $rdr->item('admin'));
        }
        return $error;
    }
    
    function ActionSave($id){
    	if(!$this->test()){
    		BASIC_URL::init()->redirect('index.php');
    	}
    }
}