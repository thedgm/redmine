<h2>Отделено време</h2>
<fieldset id="filters"><legend>Проект и Период</legend>
<span class='loading_container'>
<img id="loader" class="dn" src="images/ajax-loader.gif" />
</span>
<p>
<!-- 
<span class="field">Година<span class="required">{PERM_YEAR}</span>: {VALUE_YEAR}</span>
<span class="field">Месец<span class="required">{PERM_MONTH}</span>: {VALUE_MONTH}</span>
<span class="field">Дейност<span class="required">{PERM_ACTIVITY}</span>: {VALUE_ACTIVITY}</span>
<span class="field">Часове<span class="required">{PERM_HOURS}</span>: {VALUE_HOURS}</span>
</p>
<p class="report_task">
<span class="field">Проект<span class="required">{PERM_PRJCT}</span>: {VALUE_PRJCT}</span>
<span class="field">Таск<span class="required">{PERM_TASK}</span>: {VALUE_TASK}</span>
<input  id="cmdproSave" type="submit" name="cmdproSave" value="Приложи" />
</p>
-->
<span class="field">Година<span class="required"><?php echo @$fields['year']['perm'];?></span>: <?php echo @$fields['year']['ctrl'];?></span>
<span class="field">Месец<span class="required"><?php echo @$fields['month']['perm'];?></span>: <?php echo @$fields['month']['ctrl'];?></span>
<span class="field">от ден: <?php echo @$fields['from_day']['ctrl'];?> - до ден: <?php echo @$fields['to_day']['ctrl'];?></span>
<span class="field">Weekends: <?php echo @$fields['weekend']['ctrl'];?></span>
<span class="field">Дейност<span class="required"><?php echo @$fields['activity']['perm'];?></span>: <?php echo @$fields['activity']['ctrl'];?></span>
<span class="field">Часове<span class="required"><?php echo @$fields['hours']['perm'];?></span>: <?php echo @$fields['hours']['ctrl'];?></span>
</p>
<p class="report_task">
<span class="field">Проект<span class="required"><?php echo @$fields['prjct']['perm'];?></span>: <?php echo @$fields['prjct']['ctrl'];?></span>
<span class="field">Таск<span class="required"><?php echo @$fields['task']['perm'];?></span>: <?php echo @$fields['task']['ctrl'];?></span>
<input  id="procmdsave" type="submit" name="procmdsave" value="Приложи" />
</p>
</fieldset>