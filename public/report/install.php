<<<<<<< HEAD
<?php
$path = __FILE__;
$path = str_replace("install.php", "", $path);

require_once('cms/basic.mod.php');

BASIC::init();

BASIC::init()->imported('generator.mod','cms/');
BASIC::init()->imported('form.mod','cms/');
BASIC::init()->imported('spam.mod','cms/');
BASIC::init()->imported('error.mod','cms/');
BASIC::init()->imported('url.mod','cms/');
BASIC::init()->imported('sql.mod','cms/');
BASIC::init()->imported('xml.mod','cms/');
BASIC::init()->imported('template.mod','cms/');
//BASIC::init()->imported('users.mod','cms/');
BASIC::init()->imported('session.mod','cms/');
BASIC::init()->imported('bars.mod','cms/');
BASIC::init()->imported('media.mod','cms/');
BASIC::init()->imported('upload.mod','cms/');

BASIC::init()->ini_set('root_path', $path);
BASIC::init()->ini_set('tpl_path', 'tpl'); 
BASIC::init()->ini_set('ttf_path','ttf/');
BASIC::init()->ini_set('upload_path','upload/');
BASIC::init()->ini_set('image_path','img/');
BASIC::init()->ini_set('component_path','cmp/');
BASIC::init()->ini_set('rewrite',false);
BASIC::init()->ini_set('character','utf-8');
BASIC::init()->ini_set('script_name','');
BASIC::init()->ini_set('error_level',6143);

BASIC::init()->ini_set('template_path','tpl/');
BASIC::init()->ini_set('temporary_path','tmp/');

BASIC::init()->imported('DropDownData.lib','cmp/');

BASIC::init()->ini_set('baseTemplate', 'base.tpl');
BASIC_TEMPLATE2::init()->set('VIRTUAL', BASIC::init()->ini_get('root_virtual'), BASIC::init()->ini_get('baseTemplate'));
=======
<?php
$path = __FILE__;
$path = str_replace("install.php", "", $path);

require_once('cms/basic.mod.php');

BASIC::init();

BASIC::init()->imported('generator.mod','cms/');
BASIC::init()->imported('form.mod','cms/');
BASIC::init()->imported('spam.mod','cms/');
BASIC::init()->imported('error.mod','cms/');
BASIC::init()->imported('url.mod','cms/');
BASIC::init()->imported('sql.mod','cms/');
BASIC::init()->imported('xml.mod','cms/');
BASIC::init()->imported('template.mod','cms/');
//BASIC::init()->imported('users.mod','cms/');
BASIC::init()->imported('session.mod','cms/');
BASIC::init()->imported('bars.mod','cms/');
BASIC::init()->imported('media.mod','cms/');
BASIC::init()->imported('upload.mod','cms/');

BASIC::init()->ini_set('root_path', $path);
BASIC::init()->ini_set('tpl_path', 'tpl'); 
BASIC::init()->ini_set('ttf_path','ttf/');
BASIC::init()->ini_set('upload_path','upload/');
BASIC::init()->ini_set('image_path','img/');
BASIC::init()->ini_set('component_path','cmp/');
BASIC::init()->ini_set('rewrite',false);
BASIC::init()->ini_set('character','utf-8');
BASIC::init()->ini_set('script_name','');
BASIC::init()->ini_set('error_level',6143); 

BASIC::init()->ini_set('template_path','tpl/');
BASIC::init()->ini_set('temporary_path','tmp/');

BASIC::init()->imported('DropDownData.lib','cmp/');

BASIC::init()->ini_set('baseTemplate', 'base.tpl');
BASIC_TEMPLATE2::init()->set('VIRTUAL', BASIC::init()->ini_get('root_virtual'), BASIC::init()->ini_get('baseTemplate'));
>>>>>>> release/0.0.1.1
BASIC_TEMPLATE2::init(array(
	'prefix_ctemplate' => 'app_'
));

BASIC_SESSION::init()->start();

<<<<<<< HEAD
BASIC_SQL::init()->connect("mysql://zz_redmine_u:xx_redmine_p@cc_localhost/vv_redmine",'utf8')
=======
BASIC_SQL::init()->connect("mysql://redmine_u:redmine_p@127.0.0.1:3306/redmine",'utf8');
#error_reporting(-1);
>>>>>>> release/0.0.1.1
