<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Redmine Month Report</title>
<meta name="description" content="Redmine Month Report">
<link href="${VIRTUAL}css/application.css" media="all" rel="stylesheet" type="text/css"></link>
<script type="text/javascript">
	var ROOT_VIRTUAL = "${VIRTUAL}";
</script>

<script src="js/jquery-1.8.2.js"></script>

<!-- if(${ISADMIN}) -->
<link href="${VIRTUAL}css/cupertino/jquery-ui-1.9.0.custom.css" rel="stylesheet">
<script src="js/jquery-ui-1.9.0.custom.min.js"></script>
<!-- end -->

<script src="${VIRTUAL}js/servicesRequest.js" type="text/javascript"></script>
</head>

<body>
<div id="wrapper">
	<div id="top-menu">
	 	<!-- if(${USERNAME}) -->
	 	<div id="account">
        	<ul><li><a class="logout" href="${VIRTUAL}index.php?logout=1">Изход</a></li></ul>
        </div>
    	<div id="loggedas">Логнат като <strong>${USERNAME}</strong></div>
    	<!-- end -->
	</div>
	<div id="header">
	    <h1>Redmine Month Report</h1>
	    
	    <div id="main-menu">
	    	<!-- if(${ISADMIN}) -->
	        	<p><a href='${VIRTUAL}index.php?holidays=1'>Задай почивни дни</a></p>
	        <!-- end -->
	    </div>
	</div>

	<div class="nosidebar" id="main">
	    <div id="sidebar">        
	        
	    </div>
	    
	    <div id="content">
	    	<!-- if(${ERRORS}) -->
	    		<div class="flash error">
	    			<!-- foreach($ERRORS as $ERROR) -->
						${ERROR['message']}<br />
					<!-- end -->
				</div>
	    	<!-- end -->
			${CONTENT}		
		</div>
	</div>
				
	<div id="footer">
	 	Powered by <a href="http://www.sbnd.net/" target="_blank">SBND Technologies</a>
		 | <a href='/'>Redmine SBND</a>
	</div>
</div>

</html>
