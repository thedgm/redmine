<script type="text/javascript">
<!-- if(${current}) -->
$(document).ready(function(){
	var form = Svincs.LangManager({
		current: "${current}",
		target : $('#${cmd}').get(0)
	}),
	btns = $('.btn_lang').click(function (){
		btns.removeClass('btn_selected');
		$(this).addClass('btn_selected');

		form.langchange(this.lang);
	});
})();
<!-- end -->
	function formCmd${prefix}(obj,action,e){var cmd = document.getElementById('${cmd}');cmd.value = action;
<!-- foreach(${rules},rule) -->
	<!-- if(${rule.type} == 'confirm') -->
		if(action=='${rule.key}'&&!confirm('${rule.text}',e,{yes:function(e){if(cmd.form.onsubmit) cmd.form.onsubmit(); cmd.form.submit();},no:function(e){cmd.value='';}})){return false;};
	<!-- elseif(${rule.type} == 'message') -->
		if(action=='${rule.key}'&&!alert('${rule.text}',e,{yes:function(e){if(cmd.form.onsubmit) cmd.form.onsubmit(); cmd.form.submit();}})){return false;};
	<!-- else -->
		if(action=='${rule.key}'){${rule.text};return false;};
	<!-- end -->
<!-- end -->
	if(cmd.form.onsubmit) cmd.form.onsubmit(); cmd.form.submit();return false;
	}
</script>
<div class="btnBar">
	<!-- foreach(${actions},action) -->
	<input class="btn ${action.key}" id="btn_${action.key}" type="submit" value="${action.text}" onclick="return formCmd${prefix}(this,'${action.key}',event)"/>
	<!-- end -->
	<!-- foreach(${linguals},l) -->
	<input type="button" class="btn_lang btn ${l.key}<!-- if(${l.key} == ${current}) --> btn_selected<!-- end -->" value="${l.text}" lang="${l.key}"/>
	<!-- end -->
	<input id="${cmd}" name="${cmd}" style="display:none;"/>
</div>