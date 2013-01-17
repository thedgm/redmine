<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Redmine Month Report</title>
<meta name="description" content="Redmine Month Report">
<link href="<?php echo @$VIRTUAL;?>css/application.css" media="all" rel="stylesheet" type="text/css"></link>
<script type="text/javascript">
	var ROOT_VIRTUAL = "<?php echo @$VIRTUAL;?>";
</script>

<script src="js/jquery-1.8.2.js"></script>

<?php if(@$ISADMIN){ ?>
<link href="<?php echo @$VIRTUAL;?>css/cupertino/jquery-ui-1.9.0.custom.css" rel="stylesheet">
<script src="js/jquery-ui-1.9.0.custom.min.js"></script>
<?php }?>

<script src="<?php echo @$VIRTUAL;?>js/servicesRequest.js" type="text/javascript"></script>
</head>

<body>
<div id="wrapper">
	<div id="top-menu">
	 	<?php if(@$USERNAME){ ?>
	 	<div id="account">
        	<ul><li><a class="logout" href="<?php echo @$VIRTUAL;?>index.php?logout=1">Изход</a></li></ul>
        </div>
    	<div id="loggedas">Логнат като <strong><?php echo @$USERNAME;?></strong></div>
    	<?php }?>
	</div>
	<div id="header">
	    <h1>Redmine Month Report</h1>
	    
	    <div id="main-menu">
	    	<?php if(@$ISADMIN){ ?>
	        	<p><a href='<?php echo @$VIRTUAL;?>index.php?holidays=1'>Задай почивни дни</a></p>
	        <?php }?>
	    </div>
	</div>

	<div class="nosidebar" id="main">
	    <div id="sidebar">        
	        
	    </div>
	    
	    <div id="content">
	    	<?php if(@$ERRORS){ ?>
	    		<div class="flash error">
	    			<?php foreach((is_array($ERRORS) ? $ERRORS : array()) as $ERROR ){ ?>
						<?php echo @$ERROR['message'];?><br />
					<?php }?>
				</div>
	    	<?php }?>
			<?php echo @$CONTENT;?>		
		</div>
	</div>
				
	<div id="footer">
	 	Powered by <a href="http://www.sbnd.net/" target="_blank">SBND Technologies</a>
	</div>
</div>

</html>