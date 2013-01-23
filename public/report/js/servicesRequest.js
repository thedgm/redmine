/**
 * 
 * 'onchange' => "return servicesRequest('service_name_file', this.value, {
 * 		'ime_na_1_pole' : 'data_na_1_pole',
 * 		'id_na_2_pole'  : 'data_na_2_pole'
 * });"
 * 
 * service return
		die(json_encode(array(
			'data_na_1_pole' => array(
				array(
					'value' => 'id1',
					'text' => 'val2',
					'selected' => '1'
				),array(
					'value' => 'id1',
					'text' => 'val2',
					'selected' => '1'
				)
			),
			
			'data_na_2_pole' => array(
				array(
					'value' => 'id1',
					'text' => 'val2',
					'selected' => '1'
				),array(
					'value' => 'id1',
					'text' => 'val2',
					'selected' => '1'
				)
			)
		)));
 * 
 * @param string services
 * @param int id
 * @param object modifications [ime na poleto za modificirane , ime na klu4a na reda ot vru6taniq obekt]
 * @return void
 */

function servicesRequest(services, id, modifications, owner, params){//debugger;
	if(!params) params = {};
	var ajax_loader = $('#loader');
	
	params.id = id;
	params.service = services;
	
	if(owner){
		owner.disabled = true;
	}
	
	
	ajax_loader.show();
	$.post(ROOT_VIRTUAL+"index.php", params, function(data){//debugger;
		ajax_loader.hide();
		var res = eval(data)[0];

		for(p in modifications){
			var target = $('#'+p).get(0);
			if(target.type == "select-one" || target.type == "select-multiple"){
				removeChild(target);
				
				$.each(res[modifications[p]], function (i,option){//debugger;
	        		var op = document.createElement('option');
	    				op.value = option.value;
	    				op.text = option.text;
	    				if(option.title){
	    					op.title = option.title;
	    				}
	    				/*op.selected = false;*/
	    				if(option.selected == "1"){
	    					op.selected = 'selected';
	    				}
	    				
	    			if(document.all){
	    				target.add(op);
	    			}else{
	    				target.appendChild(op);
	    			}    			
				});		
   			}else{//debugger;
   				//if(target.type == "input")
   				var target = $('#'+p).get(0);
   				$('#'+p).attr('value',res[modifications[p]]['value']);
			}
		}
		if(owner){
			owner.disabled = false;
		}
	});
	return false;
}

function removeChild(obj){
	obj.backupValue = obj.value;
	
	if(obj.options && obj.options.length){
		$.each(obj.options,function (index,el){
			obj.removeChild(obj.options[0]);
		});
	}
}

/*
 * set to_day options disabled, so user can`t be able to select past day
 * 
 */
function fixDaysTo(el) {
	var ajax_loader = $('#loader');
	ajax_loader.show();
	
	from_day = parseInt($(el).val());
	
	$('#to_day option').attr("disabled", false);
	
	if (from_day == 0) {
		$('#to_day').val(0);
		ajax_loader.hide();
		return false;
	}
	
	$('#to_day option').each(function(){
		
		option = $(this);
		
		if (parseInt(option.val()) <= from_day) {
			option.attr('disabled', "disabled");
		} else {
			$('#to_day').val(option.val());
		}
	})
	
	ajax_loader.hide();
}

/*
 * update available month work days by year and by month
 * 
 */
function changeWorkDays(el) {
	
	var year = $('#year').val();
	var month = $('#month').val();
	var ajax_loader = $('#loader');
	
	ajax_loader.show();

	$.post(ROOT_VIRTUAL+"index.php", {service: 'getWorkDays', year : year, month : month}, function(data){
		ajax_loader.hide();
		
		var res = eval(data)[0];
		
		//clear options
		$('#from_day').empty();
		//clear options
		$('#to_day').empty();
		
		$.each(res['workdays'] , function(i, option) {
			//add from_day options
			$('#from_day').append($("<option></option>").attr("value", i).text(option));
			
			//add to_day options
			$('#to_day').append($("<option></option>").attr("value", i).text(option));
			
		});
		
	});
	
}


/*
 * show confirm data
 */
function confirmForm() {
	
	var project_name = $('#prjct option:selected').html();
	var task_name = $('#task option:selected').html();
	var activity = $('#activity option:selected').html();
	
	var task_hours = parseInt($('#hours').val());
	
	table_obj = $('#confirm_data_table'); 
	
	//remove all trs except first
	table_obj.find("tr:gt(0)").remove();
	
	$('.week-day').each(function() {
		
		var day = $(this).find('h3').html();
		
		var issue_el = $(this).find('input:checked');
		
		var value = issue_el.val();
		
		var day_hours =  parseInt($(this).find('.rep_hours').html());
		
		//if the value if issues is different from the default set orange color
		if (value != 1) {
			issue_text = issue_el.attr('day_issue');
			display_activity = issue_text;
			display_project_name = issue_text;
			display_task = issue_text;
		} else { 
			//else set default - grey
			day_hours = day_hours + task_hours;
			display_project_name = project_name;
			display_activity = activity;
			display_task = task_name;
		}
		
		if (day_hours <= 0 ) {
			tr_class = 'bg-red';
		} else if (day_hours < 8) {
			tr_class = 'bg-orange';
		} else if (day_hours == 8) {
			tr_class = 'bg-grey';
		} else {
			tr_class = 'bg-green';
		}
		
		table_obj.append('<tr class="'+ tr_class +'" ><td style="width: 10%">'+ day +'</td><td style="width: 30%">'+ display_project_name +'</td><td style="width: 45%">'+ display_task +'</td><td style="width: 10%">'+ display_activity +'</td><td style="width: 5%">'+ task_hours +'</td><td>'+ day_hours +'</td></tr>');
		
	})
	
	showHideData('show');
}

function showHideData(mode) {
	
	if (mode == 'show') {
		$('#datafield').hide();
		$('#confirm_data').fadeIn();
		$('#ccmdsave').removeClass("dn");
	} else if (mode == 'hide') {
		$('#confirm_data').hide();
		$('#datafield').fadeIn();
		$('#ccmdsave').addClass("dn");
	}
	
}


/*
 * 
 * Get holidays by year and set the holidays table 
 * 
 */
function getHolidaysByYear(el) {
	var ajax_loader = $('#loader');
	var year = $(el).val();
	var table = $(el).closest('form').find('table');
	
	if (year) {
		
		ajax_loader.show();
		
		$.post(ROOT_VIRTUAL+"index.php?holidays=1", {getYearHolidays: 1, year : year}, function(data){

			var res = eval(data);
			
			var table_trs =  table.find('tr.holiday_row');
			
			tr_obj = table_trs.filter(':last').clone();
			
			table_trs.remove();

			if (res.length > 0) {
				
				$.each(res, function() {
					//set row id
					tr_obj.attr('id', this.id);
					
					//set date html
					tr_obj.find('td').eq(0).html(this.full_date);
					
					//set status html
					tr_obj.find('td').eq(1).html(this.status_text);
					
					//set text html
					tr_obj.find('td').eq(2).html(this.text);
					
					tr_obj.appendTo(table);
					
					tr_obj = tr_obj.clone();
				})
				
				tr_obj.remove();
			}
			
			ajax_loader.hide();
		});
		
	}
	
	return false;
}

/*
 * 
 * add holiday row data to add holidays form
 * 
 * */
function addHolidayRow(el) {
	
	var parent = $(el).closest("div").find('.holidays');
	var day_row = parent.find('.row_holiday:last')
	
	if (day_row.hasClass('dn')) {
		day_row.slideDown();
		day_row.removeClass('dn');
	} else {
		
		new_day = day_row.clone(true);
		new_day.hide();
		new_day.appendTo(parent).slideDown();
		day_row = new_day;
	}
	
	parent.find('.datepicker').each(function() {
		$(this).removeAttr('id').removeClass('hasDatepicker');
	})
	
	parent.find('.datepicker').datepicker({
		dateFormat: "dd-mm-yy",
		changeYear: true,
		firstDay: 1
	});
	
	$('#holidaycmdsave').removeClass('dn');
}

/*
*
* remove row from addHoliday form
* 
*/
function removeAddHolidayRow(el) {
	
	var parent = $(el).closest("div[class=holidays]");
	
	if (parent.find('.row_holiday').size() <= 1) return false;
	
	$(el).closest('div').remove();
	
}


/*
 *
 * remove holiday from holidays table
 * 
 */
function removeHolidayRow(el) {
	
	var parent = $(el).closest("tr");
	
	var holiday_id = parent.attr('id');
	
	var ajax_loader = $('#loader');

	if (holiday_id) {
		
		ajax_loader.show();
		$.post(ROOT_VIRTUAL+"index.php?holidays=1", {removeHoliday: 1, holiday_id : holiday_id}, function(data){
			ajax_loader.hide();
			if (data == 1) {
				parent.fadeOut('fast', function() {
					parent.remove();
				})
			} else {
				alert('error');
			}
		}, 'json');
	}
	
}




