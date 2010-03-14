<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == ViewManager::CONF_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<p>No configuration options available.</p>

<?endif;//}}}?>

<?  /*-----settings edit {{{------*/
if($currentView == Banner::VIEW_SETTINGS):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Weergave url</th>  			<td><input type="checkbox" name="url" <?=($url)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Weergave</th>						<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
	<tr><th>Volgorde weergave</th>	<td><select size="1" name="display_order"><?=$cbo_display_order;?></select></td></tr>
	<tr><th>Wissel snelheid <em>(sec)</em></th>				<td><input type="text" name="transition_speed" value="<?=$transition_speed;?>" size="5" /></td></tr>
	<tr><th>Afbeelding breedte</th>	<td><input type="text" name="image_width" value="<?=$image_width;?>" size="5" /></td></tr>
	<tr><th>Afbeelding hoogte</th>	<td><input type="text" name="image_height" value="<?=$image_height;?>" size="5" /></td></tr>
	<tr><th>Maximale breedte afbeelding</th>	<td><input type="text" name="image_max_width" value="<?=$image_max_width;?>" size="5" /></td></tr>
	<tr><th colspan="2">Afbeelding</th></tr>
	<tr>
		<td colspan="2">
		<?if($image):?>
		<img id="imgsrc" src="<?=$image['src'];?>" width="<?=$image['width'];?>" height="<?=$image['height'];?>" alt="" />
		<p>delete <input type="checkbox" class="noborder" name="thumbnail_delete" /></p>
		<?endif;?>
		<input type="file" name="image" value="" size="60" />
		</td>
	</tr>
</table>


<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>
