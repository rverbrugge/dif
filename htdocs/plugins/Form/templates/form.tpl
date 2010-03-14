<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----settings edit {{{------*/
if($currentView == Form::VIEW_CONFIG):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="Back" title="Back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Subject</th>	<td><input type="text" name="subject" value="<?=$subject;?>" size="75" /></td></tr>
	<tr><th>Default sender</th>	<td><input type="text" name="mailfrom" value="<?=$mailfrom;?>" size="75" /></td></tr>
	<tr><th>Recepient<br /><em class="normal">Define multiple recepients using , (comma)</em></th>	<td><input type="text" name="mailto" value="<?=$mailto;?>" size="75" /></td></tr>
	<tr><th>Caption submit button</th>	<td><input type="text" name="caption" value="<?=$caption;?>" size="75" /></td></tr>
	<tr><th>Mandatory symbol</th>	<td><input type="text" name="mandatorysign" value="<?=$mandatorysign;?>" size="75" /></td></tr>
	<tr><th>Success page</th>	<td><select size="1" name="ref_tree_id"><?=$cbo_tree_id;?></select></td></tr>
	<tr><th>Confirmation action</th>  	<td><select size="1" name="action"><?=$cbo_action;?></select></td></tr>
	<tr>
		<th>
				Opt-in success page
			 <br /><em class="normal">Only if confirmation action is 'send opt-in request'</em> 
		</th>	
		<td><select size="1" name="optin_tree_id"><?=$cbo_optin_tree_id;?></select></td>
	</tr>

	<tr><th class="title" colspan="2">User defined email message</th></tr>
	<tr>
		<td colspan="2">
			<textarea name="mailtext" rows="10" cols="120"><?=$mailtext;?></textarea>
			<p><em>You can use php tags in the email message text. In addition, the following special variables are available.</em></p>
				<ul>
				<li>Form field tags like <strong><?=htmlentities('<?=$i2;?>');?></strong>. (see the id of the defined form elements);</li>
				<li>All form elements and their values:  <strong><?=htmlentities('<?=$fields;?>');?></strong>;</li>
				<li>Automatic generated opt-in url when using the opt-in confirmation action: <strong><?=htmlentities('<?=$optin_url;?>');?></strong>.</li>
				</ul>
				<p><em><strong>Notice</strong></em></p>
			<ul>
			<li><em>When using a user defined email message, you must include the <strong>$fields</strong> tag to have the form elements displayed.</em></li>
			<li><em>You must include the <strong>$optin_url</strong> tag in the email message if you use <strong>Send Opt-in request</strong> as confirmation action.</em></li>
			</ul>
		</td>
	</tr>
	<tr><th class="title" colspan="2">Template (optional)</th></tr>
	<tr>
		<td colspan="2">
		<p><em class="normal">HTML code with field tags. Tagname is the id of the field.</em></p>
			<textarea name="templatefield" rows="20" cols="120"><?=$templatefield;?></textarea>
<h3>Example template</h3>
<p>
<pre>
<?=htmlentities('<table>
<tr>
 <td>
  <?=$i2[\'name\'];?>
  <?if($i2[\'description\'])?> <p><?=$i2[\'description\'];?></p><enfif;?>
 </td>
 <td><?=$i2[\'html\'];?></td>
</tr>
<table>');?>
</pre>
</p>
		</td>
	</tr>
</table>



<p>
<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />
</p>

</form>

<?endif;//}}}?>


<?  /*----- record delete all {{{------*/
if($currentView == Form::VIEW_RECORD_DEL_ALL):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p>Weet u zeker dat u <strong>alle</strong> records wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_up;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----conf edit {{{------*/
if($currentView == ViewManager::CONF_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<p>No configuration options available.</p>

<?endif;//}}}?>
