<h1><?=$pageTitle;?></h1>
<em class="subtitle"><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----admin overview {{{------*/
if($currentView == ViewManager::ADMIN_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
<a href="<?=$href_update;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="Upgrade" title="Upgrade" /></a>
</p>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> found: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Name</th>
<th>Version</th>
<th>Created</th>
<th>Modified</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?if($item['activated']):?>
	<a href="<?=$item['href_plugin'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
	<?endif;?>
</td>
<td><strong><?=$item['name'];?></strong>
<?if($item['description']):?><br /><em><?=$item['description'];?></em><?endif;?>
<?if(!$item['activated']):?><br /><em class="error" >The version of this plugin is not compatible with your current DIF system. Please upgrade this plugin.</em><?endif;?>
</td>
<td><?=$item['version'];?></td>
<td><?=strftime('%c',$item['createdate']);?></td>
<td><?=strftime('%c',$item['ts']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> found: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----new and edit {{{------*/
if($currentView == ViewManager::ADMIN_NEW || $currentView == ViewManager::ADMIN_EDIT):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Algemeen</th></tr>
	<?if($name):?><tr><th>Pluginnaam</th>  	<td><strong><?=$name;?></strong></td></tr><?endif;?>
	<tr><th>Actief</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<!--tr><th>Overwrite</th>  	<td><input type="checkbox" name="overwrite" <?=($overwrite)? 'checked ':'';?> class="noborder" /></td></tr-->
	<tr><th>Installatie pakket (.tar.gz)</th>	<td><input type="file" name="pluginfile" value="" size="50" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == ViewManager::ADMIN_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Weet u zeker dat u de plugin <?=$name;?> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----update plugins {{{------*/
if($currentView == PluginHandler::VIEW_UPDATE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">

<p>Weet u zeker dat u de plugins in <?=$import_path;?> wilt installeren / upgraden?</p>

<input type="submit" value="Installeren" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- success page {{{------*/
if($currentView == PluginHandler::VIEW_SUCCESS):?>
<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<p>Plugin insert / update completed succesfully.</p>

<?if($debug):?>
<h2><strong>Modified files</strong></h2>
<p>Below are modified files that have been backed up.<br />
Please review the differences. Maybe some site specific changes are lost.</p>

<pre>
<?=$debug;?>
</pre>
<?endif;?>

<?endif;//}}}?>
