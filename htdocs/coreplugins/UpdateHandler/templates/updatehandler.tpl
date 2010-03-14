<h1><?=$pageTitle;?></h1>
<em class="subtitle"><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----admin overview {{{------*/
if($currentView == ViewManager::ADMIN_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">

<p><strong>Current version: <?=DIF_VERSION;?></strong></p>

<table id="form">
	<tr><th class="title" colspan="2">System update</th></tr>
	<tr><th>Installation package (.tar.gz)</th>	<td><input type="file" name="diffile" value="" size="50" /></td></tr>
</table>

<input type="submit" value="Submit" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_up;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----Success page {{{------*/
if($currentView == UpdateHandler::VIEW_SUCCESS):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<p>Update completed succesfully.</p>
<?endif;//}}}?>
