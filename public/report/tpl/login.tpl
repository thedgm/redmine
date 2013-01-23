<div id="login-form">
	<table>
		<tbody>
		<tr>
		    <td align="right"><label for="username">Потребител:</label></td>
		    <td align="left"><p>${fields.username.ctrl}</p></td>
		</tr>
		<tr>
		    <td align="right"><label for="password">Парола:</label></td>
		    <td align="left">${fields.password.ctrl}</td>
		</tr>
		<tr>
		    <td></td>
		</tr>
		<tr>
		    <td align="left">
		    	<a href="http://support.sbnd.net:3000/account/lost_password">Забравена парола</a> 
		    </td>
		    <td align="right">
		       	<!-- template(cmp-form-action-bar.tpl,${buttons_bar}) -->
		    </td>
		</tr>
		</tbody>
	</table>
</div>