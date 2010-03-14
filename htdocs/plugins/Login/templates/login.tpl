<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == ViewManager::CONF_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<p>No configuration options available.</p>

<?//}}}?>

<?  /*-----tree edit {{{------*/
elseif($currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Redirection on succesful login</th>	<td><select size="1" name="ref_tree_id"><?=$cbo_tree_id;?></select></td></tr>
	<tr><th>Caption username field</th>					<td><input type="text" name="cap_username" value="<?=$cap_username;?>" size="75" /></td></tr>
	<tr><th>Caption password field</th>					<td><input type="text" name="cap_password" value="<?=$cap_password;?>" size="75" /></td></tr>
	<tr><th>Caption submit button</th>			<td><input type="text" name="cap_submit" value="<?=$cap_submit;?>" size="75" /></td></tr>
	<tr><th>Input field width</th>			<td><input type="text" name="field_width" value="<?=$field_width;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?//}}}?>

<?  /*-----overview {{{------*/
//if($currentView == ViewManager::OVERVIEW):
else:?>

<?if(isset($formError)):?><p class="error"><?=$formError;?></p><?endif;?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="referer" value="<?=$referer;?>" />

<div id="login">
<table id="form">
	<tr><th><?=$settings['cap_username'];?></th>	<td><input type="text" name="username" value="" size="<?=$settings['field_width'];?>" /></td></tr>
	<tr><th><?=$settings['cap_password'];?></th>	<td><input type="password" name="password" value="" size="<?=$settings['field_width'];?>" /></td></tr>
</table>

<input type="submit" value="<?=$settings['cap_submit'];?>" class="formbutton" />
</div>

</form>
<?endif;//}}}?>
