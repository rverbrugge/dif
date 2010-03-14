<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == Poll::VIEW_CONFIG):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Captions</th></tr>
	<tr><th>Width of form (px)</th>				<td><input type="text" name="width" value="<?=$width;?>" size="5" /></td></tr>
	<tr><th>Detail link</th>				<td><input type="text" name="cap_detail" value="<?=$cap_detail;?>" size="75" /></td></tr>
	<tr><th>Back link</th>					<td><input type="text" name="cap_back" value="<?=$cap_back;?>" size="75" /></td></tr>
	<tr><th>Submit button</th>			<td><input type="text" name="cap_submit" value="<?=$cap_submit;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>
