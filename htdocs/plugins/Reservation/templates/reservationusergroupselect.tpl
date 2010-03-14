  	<table class="multibox">
		<tr>
			<td>
  		<h3>Available</h3>
  		<select multiple size="13" id="cbo_usr_free" name="usr_free[]"><?=$cbo_usr_free;?></select>
			</td>
  	<td class="nav">
  		<input type="button" class="formbutton" onclick="move('cbo_usr_free','cbo_usr_used')" value="&raquo;" />
  		<input type="button" class="formbutton" onclick="move('cbo_usr_used','cbo_usr_free')" value="&laquo;" />
  	</td>
  	<td>
			<h3>Used</h3>
  		<select multiple size="13" id="cbo_usr_used" name="usr_used[]"><?=$cbo_usr_used;?></select>
		</td></tr>
		</table>
