<h2>Почивни и отработващи се дни</h2>
<fieldset id="filters"><legend>Настройки</legend>
	<span class='loading_container'>
	<img id="loader" class="dn" src="images/ajax-loader.gif" />
	</span>
	<p>
		<span class="field">Година<span class="required">${fields.year.perm}</span>: ${fields.year.ctrl}</span>
	</p>
	<div class="holidays">
		<div class='row_holiday dn'>
			<span class="field">
				Дата: <input type="text" class="datepicker" name='day[]' /></span>
				Статус: <select name="day_status[]">
					<!-- foreach(${day_statuses} as $status) -->
						<option value="${status}">${status}</option>
					<!-- end -->
				</select>
				Информаця: <textarea name='day_info[]' rows=1 cols="20" /></textarea>
				<a href="javascript:;" onclick="removeAddHolidayRow(this);">Х</a>
		</div>
	</div>
	<p>
		<span class="field"><a href="javascript:;" onclick="addHolidayRow(this);">Добави празниk/отработване</a></span>
		
		<a href='${VIRTUAL}index.php'>BACK</a>
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
		<!-- if(${table_data}) -->
			<!-- foreach(${table_data} as $holiday) -->
				<tr id="${holiday['id']}" class='holiday_row'>
					<td>${holiday['full_date']}</td>
					<td>${holiday['status_text']}</td>
					<td>${holiday['text']}</td>
					<td><a href="javascript:;" onclick="removeHolidayRow(this);">изтриване</a></td>
				</tr>
			<!-- end -->
		<!-- end -->
	</table>
</p>