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
<<<<<<< HEAD
        $rdr = BASIC_SQL::init()->read_exec("SELECT `id`, `login`, `admin` FROM `".$this->base."` WHERE `login`= '".$this->getDataBuffer('username')."' AND `hashed_password` = SHA1('".$this->getDataBuffer('password')."') LIMIT 1");
        $rdr->read();
=======
        $rdr = BASIC_SQL::init()->read_exec("SELECT `id`, `login`, `admin`, `salt`, `hashed_password` FROM `".$this->base."` WHERE `login`= '".$this->getDataBuffer('username')."' LIMIT 1");
##	AND `hashed_password` = SHA1('".$this->getDataBuffer('password')."') LIMIT 1");
	$rdr->read();
>>>>>>> release/0.0.1.1
        if(!$rdr->num_rows()) {
        	BASIC_ERROR::init()->append(10, "Невалиден потребител или парола.");
        	$error = true;
        }
        else {
<<<<<<< HEAD
        	$this->id = $rdr->item('id');
        	BASIC_SESSION::init()->set('UserID', $this->id);
        	BASIC_SESSION::init()->set('username', $rdr->item('login'));
        	BASIC_SESSION::init()->set('isadmin', $rdr->item('admin'));
        }
=======
		$entered = $this->getDataBuffer('password');
		$password = $rdr->item('hashed_password');
		$salt = $rdr->item('salt');
		$encrypted = sha1($salt . sha1($entered));
// print_r($encrypted);
		if ( $encrypted == $password ) {
	        	$this->id = $rdr->item('id');
        		BASIC_SESSION::init()->set('UserID', $this->id);
        		BASIC_SESSION::init()->set('username', $rdr->item('login'));
        		BASIC_SESSION::init()->set('isadmin', $rdr->item('admin'));
		} else {
			BASIC_ERROR::init()->append(10, "Greshen user/pass");
			$error = true;
		}
        }
# $s=BASIC_SQL::init()->read_exec("SHOW TABLES;");
# $s->read();
# print_r($this->base);
# echo "<pre>";
# while ($s->read()){
# 	print_r($s->item('Tables_in_redmine'));
#         echo "<br />";
# }
# echo "</pre>";

>>>>>>> release/0.0.1.1
        return $error;
    }
    
    function ActionSave($id){
    	if(!$this->test()){
    		BASIC_URL::init()->redirect('index.php');
    	}
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> release/0.0.1.1
