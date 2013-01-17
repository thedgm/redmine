<h2>Почивни и отработващи се дни</h2>
<fieldset id="filters"><legend>Настройки</legend>
	<span class='loading_container'>
	<img id="loader" class="dn" src="images/ajax-loader.gif" />
	</span>
	<p>
		<span class="field">Година<span class="required"><?php echo @$fields['year']['perm'];?></span>: <?php echo @$fields['year']['ctrl'];?></span>
	</p>
	<div class="holidays">
		<div class='row_holiday dn'>
			<span class="field">
				Дата: <input type="text" class="datepicker" name='day[]' /></span>
				Статус: <select name="day_status[]">
					<?php foreach((is_array(@$day_statuses) ? @$day_statuses : array()) as $status ){ ?>
						<option value="<?php echo @$status;?>"><?php echo @$status;?></option>
					<?php }?>
				</select>
				Информаця: <textarea name='day_info[]' rows=1 cols="20" /></textarea>
				<a href="javascript:;" onclick="removeAddHolidayRow(this);">Х</a>
		</div>
	</div>
	<p>
		<span class="field"><a href="javascript:;" onclick="addHolidayRow(this);">Добави празниk/отработване</a></span>
		
		<a href='<?php echo @$VIRTUAL;?>index.php'>BACK</a>
		<input type="submit" value="Запиши" name="holidaycmdsave" id="holidaycmdsave" class='dn'/>
	</p>
</fieldset>
<p>
	<table>
		<tr class='header'>
			<th>ден</th>
			<th>статус</th>
			<th>информация</th>
			<th>manage</th>
		</tr>
		<?php if(@$table_data){ ?>
			<?php foreach((is_array(@$table_data) ? @$table_data : array()) as $holiday ){ ?>
				<tr id="<?php echo @$holiday['id'];?>" class='holiday_row'>
					<td><?php echo @$holiday['full_date'];?></td>
					<td><?php echo @$holiday['status_text'];?></td>
					<td><?php echo @$holiday['text'];?></td>
					<td><a href="javascript:;" onclick="removeHolidayRow(this);">изтриване</a></td>
				</tr>
			<?php }?>
		<?php }?>
	</table>
</p>