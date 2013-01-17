<script type="text/javascript">
<?php if(@$current){ ?>
$(document).ready(function(){
	var form = Svincs.LangManager({
		current: "<?php echo @$current;?>",
		target : $('#<?php echo @$cmd;?>').get(0)
	}),
	btns = $('.btn_lang').click(function (){
		btns.removeClass('btn_selected');
		$(this).addClass('btn_selected');

		form.langchange(this.lang);
	});
})();
<?php }?>
	function formCmd<?php echo @$prefix;?>(obj,action,e){var cmd = document.getElementById('<?php echo @$cmd;?>');cmd.value = action;
<?php foreach((is_array(@$rules) ? @$rules : array()) as $rule ){ ?>
	<?php if(@$rule['type'] == 'confirm'){ ?>
		if(action=='<?php echo @$rule['key'];?>'&&!confirm('<?php echo @$rule['text'];?>',e,{yes:function(e){if(cmd.form.onsubmit) cmd.form.onsubmit(); cmd.form.submit();},no:function(e){cmd.value='';}})){return false;};
	<?php }elseif(@$rule['type'] == 'message'){ ?>
		if(action=='<?php echo @$rule['key'];?>'&&!alert('<?php echo @$rule['text'];?>',e,{yes:function(e){if(cmd.form.onsubmit) cmd.form.onsubmit(); cmd.form.submit();}})){return false;};
	<?php }else{?>
		if(action=='<?php echo @$rule['key'];?>'){<?php echo @$rule['text'];?>;return false;};
	<?php }?>
<?php }?>
	if(cmd.form.onsubmit) cmd.form.onsubmit(); cmd.form.submit();return false;
	}
</script>
<div class="btnBar">
	<?php foreach((is_array(@$actions) ? @$actions : array()) as $action ){ ?>
	<input class="btn <?php echo @$action['key'];?>" id="btn_<?php echo @$action['key'];?>" type="submit" value="<?php echo @$action['text'];?>" onclick="return formCmd<?php echo @$prefix;?>(this,'<?php echo @$action['key'];?>',event)"/>
	<?php }?>
	<?php foreach((is_array(@$linguals) ? @$linguals : array()) as $l ){ ?>
	<input type="button" class="btn_lang btn <?php echo @$l['key'];?><?php if(@$l['key'] == @$current){ ?> btn_selected<?php }?>" value="<?php echo @$l['text'];?>" lang="<?php echo @$l['key'];?>"/>
	<?php }?>
	<input id="<?php echo @$cmd;?>" name="<?php echo @$cmd;?>" style="display:none;"/>
</div>