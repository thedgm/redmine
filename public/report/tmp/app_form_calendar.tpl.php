<?php if(@$day){ ?>
<form id="frm_calendar" name="frm_calendar" method="post" action="index.php">
	<div class="total-hours">Време за избрания период: <span class='hours'><?php echo @$total_work_hours;?> h</span></div>
	
	<div class="period" id='datafield'>
		<?php foreach((is_array(@$day) ? @$day : array()) as $week => $wday ){ ?>
			<div class="weekbox">
				<div class="fl week">Week <?php echo @$week;?></div>
				<div class="fl">
				<?php foreach((is_array(@$wday) ? @$wday : array()) as $work_day ){ ?>
					<?php if(@$work_day["day_class"]){ ?>
						<div class='week-day fl <?php echo @$work_day["day_class"];?>'>						
					<?php }else{?>
						<div class='week-day fl'>
					<?php }?>
					
						<div class="fl full-width">
							<div class='fl'><h3><?php echo @$work_day['weekday_name'];?> <?php echo @$work_day['format_date'];?></h3></div>
							<?php if(@$work_day['user_history']){ ?>
								<div class='fr'>
									<span class='rep_hours <?php echo @$work_day['user_history']['total_hours_class'];?>'><?php echo @$work_day['user_history']['total_hours'];?></span>
								</div>
							<?php }else{?>
								<div class='fr'>
									<span class='rep_hours bg-red'>0</span>
								</div>
							<?php }?>
						</div>
						<div class='clear'></div>
						<div class="fl full-width">
							<?php foreach((is_array($field_data) ? $field_data : array()) as $key => $name ){ ?>
							
								<?php if(@$work_day["day_class"] == 'officialNotWork'){ ?>
								
									<?php if($key == 4){ ?>
										<div class="radioItem"><input type="radio" name="days[<?php echo @$work_day['day_number'];?>][]" value="<?php echo @$key;?>" checked="checked" /><?php echo @$name;?></div>
									<?php }else{?>
										<div class="radioItem"><input type="radio" name="days[<?php echo @$work_day['day_number'];?>][]" value="<?php echo @$key;?>" /><?php echo @$name;?></div>
									<?php }?>
									
								<?php }else{?>
								
									<?php if($key == 1){ ?>
										<div class="radioItem"><input type="radio" name="days[<?php echo @$work_day['day_number'];?>][]" value="<?php echo @$key;?>" checked="checked" /><?php echo @$name;?></div>
									<?php }else{?>
										<div class="radioItem"><input type="radio" name="days[<?php echo @$work_day['day_number'];?>][]" value="<?php echo @$key;?>" day_issue="<?php echo @$name;?>" /><?php echo @$name;?></div>
									<?php }?>
								<?php }?>
								
							<?php }?>
						</div>
						<div class='clear'></div>
						<?php if(@$work_day['user_history']['tasks']){ ?>
							<div class='full-width'>
								<?php foreach((is_array(@$work_day['user_history']['tasks']) ? @$work_day['user_history']['tasks'] : array()) as $hist ){ ?>
									<div class='tasks'>
										<?php if(@$hist['project_name'] != 'Misc'){ ?>
												<span class='fl'><?php echo @$hist['project_name'];?></span>
										<?php }else{?>
												<span class='fl'><?php echo @$hist['project_name'];?> - <?php echo @$hist['issue_name'];?></span>
										<?php }?>
										<span class='fr'><?php echo @$hist['hours'];?></span>
									</div>
									<div class='clear'></div>
								<?php }?>
							</div>
						<?php }?>
					</div>
					
				<?php }?>
				</div>
				<div class='clear'></div>
			</div>
			<div class='clear'></div>
		<?php }?>
		<input type="hidden" value="<?php echo @$year;?>" name="year" />
		<input type="hidden" value="<?php echo @$month;?>" name="month" />
		<input type="hidden" value="<?php echo @$activity;?>" name="activity" />
		<input type="hidden" value="<?php echo @$project;?>" name="project" />
		<input type="hidden" value="<?php echo @$task;?>" name="task" />
		<input type="hidden" value="<?php echo @$hours;?>" name="hours" />
		
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
<?php }?>