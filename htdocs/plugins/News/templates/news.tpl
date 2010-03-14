<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == ViewManager::CONF_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />
<input type="hidden" name="plug_id" value="<?=$plug_id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Display style</th>							<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
	<tr><th>Date format </th>								<td><input type="text" name="date_format" value="<?=$date_format;?>" size="15" /> <em>(use <a href="http://nl.php.net/strftime" target="_blank">strftime</a> format)</em></td></tr>
	<tr><th>Items per page</th>				<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th>Caption back link</th>					<td><input type="text" name="cap_back" value="<?=$cap_back;?>" size="75" /></td></tr>
	<tr><th>Caption detail link</th>				<td><input type="text" name="cap_detail" value="<?=$cap_detail;?>" size="75" /></td></tr>
	<tr><th class="title" colspan="2">Comments</th></tr>
	<tr><th>Enable comment</th>  						<td><input type="checkbox" name="comment" <?=($comment)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Notify on new comments</th>  		<td><input type="checkbox" name="comment_notify" <?=($comment_notify)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Order posts ascending</th>  		<td><input type="checkbox" name="comment_order_asc" <?=($comment_order_asc)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Display style</th>							<td><select size="1" name="comment_display"><?=$cbo_comment_display;?></select></td></tr>
	<tr><th>Field width</th>								<td><input type="text" name="comment_width" value="<?=$comment_width;?>" size="15" /></td></tr>
	<tr><th>Comment title</th>							<td><input type="text" name="comment_title" value="<?=$comment_title;?>" size="75" /></td></tr>
	<tr><th>Caption name field</th>					<td><input type="text" name="cap_name" value="<?=$cap_name;?>" size="75" /></td></tr>
	<tr><th>Caption email field (optional)</th>					<td><input type="text" name="cap_email" value="<?=$cap_email;?>" size="75" /></td></tr>
	<tr><th>Caption description field</th>	<td><input type="text" name="cap_desc" value="<?=$cap_desc;?>" size="75" /></td></tr>
	<tr><th>Caption submit button</th>			<td><input type="text" name="cap_submit" value="<?=$cap_submit;?>" size="75" /></td></tr>
	<tr><th class="title" colspan="2">Image</th></tr>
	<tr><th>Show thumbnail in detail view</th>  						<td><input type="checkbox" name="detail_img" <?=($detail_img)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Image width</th>	<td><input type="text" name="image_width" value="<?=$image_width;?>" size="5" /></td></tr>
	<tr><th>Image height</th>	<td><input type="text" name="image_height" value="<?=$image_height;?>" size="5" /></td></tr>
	<tr><th>Maximum image width</th>	<td><input type="text" name="image_max_width" value="<?=$image_max_width;?>" size="5" /></td></tr>
	<tr><th class="title" colspan="2">PHP Template</th></tr>
	<tr><td colspan="2"><textarea id="area1" name="template" rows="20" cols="150"><?=$template;?></textarea></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>
