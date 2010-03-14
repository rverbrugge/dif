<h1><?=$pageTitle;?></h1>
<em class="subtitle"><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----admin overview {{{------*/
if($currentView == ViewManager::ADMIN_OVERVIEW):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
</p>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items: <?=$list['links'];?></p><?endif;?>

<?if($list['data']):?>
<table class="overview">
<tr>
<th></th>
<th>Root node identifier</th>
<th>Name</th>
<th>Language</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
<?if(isset($item['startpage']) && $item['startpage']):?>
	<img class="noborder" src="<?=$img_ok['src'];?>" width="<?=$img_ok['width'];?>" height="<?=$img_ok['height'];?>" alt="Standaard website" title="Standaard website" />
<?endif;?>
</td>
<td><?=$item['tree_root_id'];?></td>
<td><?=$item['name'];?></td>
<td><?=$item['language_name'];?></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----new and edit {{{------*/
if($currentView == ViewManager::ADMIN_NEW || $currentView == ViewManager::ADMIN_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.editform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<?if(isset($newsite) && $newsite):?>
<p><strong>Congratulations!</strong></p>
<p>You now have succesfully installed DIF.</p>
<p>The next step is to define an environment.<br />
Specify a name and default language for the website you are deploying.</p>
<?endif;?>

<form action="<?=$urlPath;?>" name="editform" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Active</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Default website</th>  	<td><input type="checkbox" name="startpage" <?=($startpage)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Root Node id</th>	<td><input type="text" name="tree_root_id" value="<?=$tree_root_id;?>" size="3" /> <em>(equal or smaller than 0)</em></td></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Title</th>	<td><input type="text" name="title" value="<?=$title;?>" size="75" onfocus="syncname(this, getName('name'));" /></td></tr>
	<tr><th>Language</th>	<td><select size="1" name="language"><?=$cbo_language;?></select></td></tr>
	<tr><th>Description</th> <td><textarea name="description" rows="3" cols="75"><?=$description;?></textarea></td></tr>
	<tr><th>Keywords</th> <td><textarea name="keywords" rows="3" cols="75"><?=$keywords;?></textarea></td></tr>
</table>

<?if(isset($newsite) && $newsite):?>
<input type="submit" value="Create" class="formbutton" />
<?else:?>
<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />
<?endif;?>

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == ViewManager::ADMIN_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Weet u zeker dat u de website <?=$name;?> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----newsite success {{{------*/
if($currentView == SiteGroup::NEWSITE_SUCCESS):?>
<p>Your environment is successfully set up.</p>
<p>Please choose one of the following:</p>
<p class="options">
<a href="<?=$href_theme;?>"><img class="noborder" src="<?=$img_import['src'];?>" width="<?=$img_import['width'];?>" height="<?=$img_import['height'];?>" alt="Add graphical theme" title="Add graphical theme" /></a>
Add a graphical theme. 
</p>

<p class="options">
<a href="<?=$href_site;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="Create site structure" title="Create site structure" /></a>
Start creating a site structure. 
</p>
<p><strong>Have fun!</strong></p>

<?endif;//}}}?>
