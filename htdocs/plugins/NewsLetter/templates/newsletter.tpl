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
	<tr><th class="title" colspan="2">General settings</th></tr>
	<tr><th>Default Theme</th>	<td><select size="1" name="theme_id"><?=$cbo_theme;?></select></td></tr>
	<tr><th>Items per page</th>				<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th class="title" colspan="2">Image settings</th></tr>
	<tr><th>Image width</th>	<td><input type="text" name="image_width" value="<?=$image_width;?>" size="5" /></td></tr>
	<tr><th>Image height</th>	<td><input type="text" name="image_height" value="<?=$image_height;?>" size="5" /></td></tr>
	<tr><th>Maximum image width</th>	<td><input type="text" name="image_max_width" value="<?=$image_max_width;?>" size="5" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>

