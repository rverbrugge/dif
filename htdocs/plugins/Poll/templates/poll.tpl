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
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Display style</th>							<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>
