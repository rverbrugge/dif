<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == NewsLetter::VIEW_CONFIG):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.editform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>


<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">General settings</th></tr>
	<tr><th>Display style</th>							<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
	<tr><th>Caption title field</th>					<td><input type="text" name="cap_gender" value="<?=$cap_gender;?>" size="75" /></td></tr>
	<tr><th>Caption name field</th>					<td><input type="text" name="cap_name" value="<?=$cap_name;?>" size="75" /></td></tr>
	<tr><th>Caption email field</th>					<td><input type="text" name="cap_email" value="<?=$cap_email;?>" size="75" /></td></tr>
	<tr><th>Caption submit button</th>			<td><input type="text" name="cap_submit" value="<?=$cap_submit;?>" size="75" /></td></tr>
	<tr><th>Field width</th>								<td><input type="text" name="field_width" value="<?=$field_width;?>" size="15" /></td></tr>
	<tr><th class="title" colspan="2">Subscription email settings</th></tr>
	<tr><th>Subject</th>	<td><input type="text" name="subject" value="<?=$subject;?>" size="75" /></td></tr>
	<tr><th>Sender</th>	<td><input type="text" name="mailfrom" value="<?=$mailfrom;?>" size="75" /></td></tr>
	<tr><th>Success page</th>	<td><select size="1" name="ref_tree_id"><?=$cbo_tree_id;?></select></td></tr>
	<tr><th>Confirmation action</th>  	<td><select size="1" name="action"><?=$cbo_action;?></select></td></tr>
	<tr>
		<th>
				Opt-in success page
			 <br /><em class="normal">Only if confirmation action is 'send opt-in request'</em> 
		</th>	
		<td><select size="1" name="optin_tree_id"><?=$cbo_optin_tree_id;?></select></td>
	</tr>
	<tr><th>Unsubscribe success page</th>	<td><select size="1" name="del_tree_id"><?=$cbo_del_tree_id;?></select></td></tr>

	<tr><th class="title" colspan="2">Confirmation text for email message</th></tr>
	<tr>
		<td colspan="2">
			<textarea name="mailtext" rows="10" cols="120"><?=$mailtext;?></textarea>
			<p>
				<em>You can use php tags, subscriber information tags (<strong><?=htmlentities('<?=$name;?>');?></strong>, <strong><?=htmlentities('<?=$email;?>');?></strong>, <strong><?=htmlentities('<?=$gender_description;?>');?></strong>
				and the <strong><?=htmlentities('<?=$optin_url;?>');?></strong> tag for the confirmation link if you choose opt-in confirmation action.</em>
			</p>
			<p>
				<em><strong>Notice</strong><br />
			You must include the <strong>$optin_url</strong> tag in the text if you use <strong>Send Opt-in request</strong> as confirmation action.</em>
			</p>
		</td>
	</tr>

</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>
