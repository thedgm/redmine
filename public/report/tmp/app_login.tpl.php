<div id="login-form">
	<table>
		<tbody>
		<tr>
		    <td align="right"><label for="username">Потребител:</label></td>
		    <td align="left"><p><?php echo @$fields['username']['ctrl'];?></p></td>
		</tr>
		<tr>
		    <td align="right"><label for="password">Парола:</label></td>
		    <td align="left"><?php echo @$fields['password']['ctrl'];?></td>
		</tr>
		<tr>
		    <td></td>
		</tr>
		<tr>
		    <td align="left">
		    	<a href="http://support.sbnd.net:3000/account/lost_password">Забравена парола</a> 
		    </td>
		    <td align="right">
		       	<?php $__local_vars__ = $__VARS__; foreach(@$buttons_bar as $tk => $tv) $__local_vars__[$tk] = $tv; echo BASIC_TEMPLATE2::init()->parse('cmp-form-action-bar.tpl','',$__local_vars__); unset($__local_vars__); ?>
		    </td>
		</tr>
		</tbody>
	</table>
</div>