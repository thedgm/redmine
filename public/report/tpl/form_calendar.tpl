<!-- if(${day}) -->
<form id="frm_calendar" name="frm_calendar" method="post" action="index.php">
	<div class="total-hours">Време за избрания период: <span class='hours'>${total_work_hours} h</span></div>
	
	<div class="period" id='datafield'>
		<!-- foreach(${day} as $week => $wday) -->
			<div class="weekbox">
				<div class="fl week">Week ${week}</div>
				<div class="fl">
				<!-- foreach(${wday} as $work_day) -->
					<!-- if(${work_day["day_class"]}) -->
						<div class='week-day fl ${work_day["day_class"]}'>						
					<!-- else -->
						<div class='week-day fl'>
					<!-- end -->
					
						<div class="fl full-width">
							<div class='fl'><h3>${work_day['weekday_name']} ${work_day['format_date']}</h3></div>
							<!-- if(${work_day['user_history']}) -->
								<div class='fr'>
									<span class='rep_hours ${work_day['user_history']['total_hours_class']}'>${work_day['user_history']['total_hours']}</span>
								</div>
							<!-- else -->
								<div class='fr'>
									<span class='rep_hours bg-red'>0</span>
								</div>
							<!-- end -->
						</div>
						<div class='clear'></div>
						<div class="fl full-width">
							<!-- foreach($field_data as $key=>$name) -->
							
								<!-- if(${work_day["day_class"]} == 'officialNotWork') -->
								
									<!-- if($key == 4) -->
										<div class="radioItem"><input type="radio" name="days[${work_day['day_number']}][]" value="${key}" checked="checked" />${name}</div>
									<!-- else -->
										<div class="radioItem"><input type="radio" name="days[${work_day['day_number']}][]" value="${key}" />${name}</div>
									<!-- end -->
									
								<!-- else -->
								
									<!-- if($key == 1) -->
										<div class="radioItem"><input type="radio" name="days[${work_day['day_number']}][]" value="${key}" checked="checked" />${name}</div>
									<!-- else -->
										<div class="radioItem"><input type="radio" name="days[${work_day['day_number']}][]" value="${key}" day_issue="${name}" />${name}</div>
									<!-- end -->
								<!-- end -->
								
							<!-- end -->
						</div>
						<div class='clear'></div>
						<!-- if(${work_day['user_history']['tasks']}) -->
							<div class='full-width'>
								<!-- foreach(${work_day['user_history']['tasks']} as $hist) -->
									<div class='tasks'>
										<!-- if(${hist['project_name']} != 'Misc') -->
												<span class='fl'>${hist['project_name']}</span>
										<!-- else -->
												<span class='fl'>${hist['project_name']} - ${hist['issue_name']}</span>
										<!-- end -->
										<span class='fr'>${hist['hours']}</span>
									</div>
									<div class='clear'></div>
								<!-- end -->
							</div>
						<!-- end -->
					</div>
					
				<!-- end -->
				</div>
				<div class='clear'></div>
			</div>
			<div class='clear'></div>
		<!-- end -->
		<input type="hidden" value="${year}" name="year" />
		<input type="hidden" value="${month}" name="month" />
		<input type="hidden" value="${activity}" name="activity" />
		<input type="hidden" value="${project}" name="project" />
		<input type="hidden" value="${task}" name="task" />
		<input type="hidden" value="${hours}" name="hours" />
		
		<input type="button" value="Запази" id="checkForm" onclick="confirmForm(); return false;" />
		
	</div>
	<div id="confirm_data" class='dn'>
		<table id="confirm_data_table" >
			<tr>
				<th>Ден</th>
				<th>Проект</th>
				<th>Таск</th>
				<th>Дейност</th>
				<th>Таск часове</th>
				<th>Ден часове</th>
			</tr>
		</table>
		<input type="button" value="Коригирай" onclick="showHideData('hide')"/>
	</div>
	
	<input type="submit" value="Запази" name="calendarcmdsave" id="ccmdsave" class='dn'/>
</form>
<!-- end -->