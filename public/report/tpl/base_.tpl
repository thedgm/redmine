<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="${VIRTUAL}css/app_styles_2.css" rel="stylesheet" type="text/css" />

<script language="javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
<script src=" https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.js"></script>
<script type="text/javascript" src="${VIRTUAL}js/jquery-ui-1.8.5.custom.min.js"></script> 
<script type="text/javascript" src="${VIRTUAL}js/jquery-ui-1.8.2.custom.css"></script>

<script type="text/javascript">
	var ROOT_VIRTUAL = "${VIRTUAL}../../../../";
</script>
<script src="${VIRTUAL}../../js/servicesRequest.js" type="text/javascript"></script>

<script type="text/javascript" src="http://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>

<!-- Wrapping the Recaptcha create method in a javascript function -->
<script type="text/javascript">
	function showRecaptcha(element) {
           Recaptcha.create("${public_key}", element, {
             theme: "red",
             callback: Recaptcha.focus_response_field});
    }
</script>

<script>
	$(function() {
		$( "#birthday" ).datepicker({
			showOn: "button",
			dateFormat: 'dd-mm-yy',
			appendText: '(DD-MM-YYYY)',
			changeMonth: true,
			changeYear: true,
			yearRange: '-60:-15',
			buttonImage: "images/date_icon.gif",
			buttonImageOnly: true
		});
	});
	$(document).ready(function(){
		showRecaptcha('recaptcha_div');
	});
	$(document).ready(function(){//debugger;
		var form = document.forms[0];
		//step 1
		$(form['learn_diff']).change(function(){//debugger;
			if(this.value == '1'){
				$('#learn_diff_text').show();
			}
			else{
				$('#learn_diff_text').hide();
			}
		});

		$(form['alt_address']).change(function(){//debugger;
			if(this.value == '1'){
				$('#alt_address_box').show();
			}
			else{
				$('#alt_address_box').hide();
			}
		});
		$(form['alt_address']).each(function (order, obj){//debugger;
			if(obj.checked){
				$('#alt_address_box')[obj.value == '1' ? 'show' : 'hide']();
			}
		});
		//step 3
		$(form['ed2_exam_taken']).change(function(){//debugger;
			if(this.value == '3'){
				$('#ed2_exam_taken_other').show();
			}
			else{
				$('#ed2_exam_taken_other').hide();
			}
		});

		$(form['ed3_educ_level']).change(function(){//debugger;
			if(this.value == '1'){
				$('#ed3_educ_level_box').show();
			}
			else{
				$('#ed3_educ_level_box').hide();
			}
		});
		$(form['ed3_educ_level']).each(function (order, obj){//debugger;
			if(obj.checked){
				$('#ed3_educ_level_box')[obj.value == '1' ? 'show' : 'hide']();
			}
		});

		$(form['ed3_educ_level2']).change(function(){//debugger;
			if(this.value == '1'){
				$('#ed3_educ_level_box2').show();
			}
			else{
				$('#ed3_educ_level_box2').hide();
			}
		});
		$(form['ed3_educ_level2']).each(function (order, obj){//debugger;
			if(obj.checked){
				$('#ed3_educ_level_box2')[obj.value == '1' ? 'show' : 'hide']();
			}
		});
	})
	function remove_course(id){
		var form = $('#del_course').get(0);
		if(form){
			form.module_id.value = id;
			form.submit();
		}
	}

	function confirmation(prev_step) {
	
		var answer = confirm("Please press the OK button if you made any changes.")
		debugger;
		if (answer){
			//submit
			debugger;
			var form = document.forms[0];
			form['action'].name = 'cmdCancel';
			form['action'].value = 'Cancel';
			form['step'].value = prev_step;
	        form.submit();
	        return true;
		}
		else{
			//else redirect
			window.location = "${VIRTUAL}app_step_"+prev_step+".php";
			return false;
		}
	}
	
</script>
<!-- if(${ERRORS}) -->
<script>
	$(function() {
		$( "#dialog" ).dialog();
	});
	</script>
<!-- end -->
</head>
<body>
<!-- if(${ERRORS}) -->
	<div id="dialog" title="">
		<p>
		<!-- foreach($ERRORS as $ERROR) -->
			${ERROR['message']}<br />
		<!-- end -->
		</p>
	</div>
<!-- end -->
	${CONTENT}
	<div style="display:none;">
		<form id="del_course" name="del_course" method="post" action="${VIRTUAL}app_step_2.php">
			<input id="module_id" name="module_id" type="hidden">
			<input id="cmdDelete" type="hidden" value="delete" name="cmdDelete">
		</form>
	</div>
</form>
</body>
</html>