<h1><?=$pageTitle;?></h1>
<em class="subtitle"><?=$currentViewName;?></em>

<table id="site">
<tr>

<?if(isset($tpl_sitemenu) || isset($tpl_sitegroup)):?>
<td id="dtree">

<?if(isset($tpl_sitemenu)):?>
<div class="pane">
<?=$tpl_sitemenu;?>
</div>
<?endif;?>

<?if(isset($tpl_sitegroup)):?>
<div class="pane">
<?=$tpl_sitegroup;?>
</div>
<?endif;?>

<?if(isset($groupList) && $groupList):?>
<a href="#" onclick="Effect.BlindDown('legend');">
<img class="noborder" src="<?=$img_info['src'];?>" width="<?=$img_info['width'];?>" height="<?=$img_info['height'];?>" alt="show ACL legend" title="show ACL legend" />
</a>

<div id="legend">
<h3>Acl legend</h3>
<table class="form">
<?foreach($rightsList as $item):?>
<tr><th><?=$item['name'];?></th>	<td><?=$item['description'];?></td></tr>
<?endforeach;?>
</table>
</div>
<script type="text/javascript">Element.hide('legend');</script>
<?endif;?>
</td>
<?endif;?>


<td id="sitemain">

<h2><?=$nodeName;?></h2>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----admin overview {{{------*/
if($currentView == ViewManager::ADMIN_OVERVIEW):?>

<?if(isset($root) && $root):?>
<p>Welcome to the starting point of your website.</p>
<p>You can add new top level web pages from this location.</p>
<?endif;?>

<?if(isset($root) && $root):?>
<table class="overview">
<tr><th colspan="2">Main menu</th></tr>
<?if(isset($href_sub_new)):?>
<tr>
	<td><a href="<?=$href_sub_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a></td>
	<td>Create a new page</td>
</tr>
<?endif;?>
<?if(isset($href_acl)):?>
<tr>
	<td><a href="<?=$href_acl;?>"><img class="noborder" src="<?=$img_security['src'];?>" width="<?=$img_security['width'];?>" height="<?=$img_security['height'];?>" alt="acl" title="acl" /></a></td>
	<td>Configure default security</td>
</tr>
<?endif;?>
<?if(isset($href_conf)):?>
<tr>
	<td><a href="<?=$href_acl;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="acl" title="acl" /></a></td>
	<td>Configure site</td>
</tr>
<?endif;?>
<?if(isset($href_theme_admin) || isset($href_theme)):?>
<tr>
<td>
<?if(isset($href_theme_admin)):?>
<a href="<?=$href_theme_admin;?>"><img class="noborder" src="<?=$img_warn['src'];?>" width="<?=$img_warn['width'];?>" height="<?=$img_warn['height'];?>" alt="Upgrade theme" title="Upgrade theme" /></a>
<?elseif(isset($href_theme)):?>
<a href="<?=$href_theme;?>"><img class="noborder" src="<?=$img_theme['src'];?>" width="<?=$img_theme['width'];?>" height="<?=$img_theme['height'];?>" alt="edit" title="edit" /></a>
<?endif;?>
</td>
<td>Theme: <strong><?=$theme_name;?></strong></td>
</tr>
<?endif;?>
<tr>
	<td><a href="<?=$href_preview;?>" target="_blank"><img class="noborder" src="<?=$img_internet['src'];?>" width="<?=$img_internet['width'];?>" height="<?=$img_internet['height'];?>" alt="View site" title="View site" /></a></td>
	<td>View this site</td>
</tr>
</table>
<?else:?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<?if(isset($startpage) && $startpage):?>
	<img class="noborder" src="<?=$img_ok['src'];?>" width="<?=$img_ok['width'];?>" height="<?=$img_ok['height'];?>" alt="startpagina" title="Starting point" />
<?endif;?>
<?if(isset($href_new)):?>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new page" /></a>
<?endif;?>
<?if(isset($href_sub_new)):?>
<a href="<?=$href_sub_new;?>"><img class="noborder" src="<?=$img_sub_new['src'];?>" width="<?=$img_sub_new['width'];?>" height="<?=$img_sub_new['height'];?>" alt="sub_new" title="new sub page" /></a>
<?endif;?>
<?if(isset($href_edit)):?>
<a href="<?=$href_edit;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="edit" title="edit page" /></a>
<?endif;?>
<?if(!isset($root) || !$root):?>
<?if(isset($href_theme_admin)):?>
<a href="<?=$href_theme_admin;?>"><img class="noborder" src="<?=$img_warn['src'];?>" width="<?=$img_warn['width'];?>" height="<?=$img_warn['height'];?>" alt="Upgrade <?=$theme_name;?>" title="Upgrade <?=$theme_name;?>" /></a>
<?elseif(isset($href_theme)):?>
<a href="<?=$href_theme;?>"><img class="noborder" src="<?=$img_theme['src'];?>" width="<?=$img_theme['width'];?>" height="<?=$img_theme['height'];?>" alt="Edit <?=$theme_name;?>" title="Edit <?=$theme_name;?>" /></a>
<?endif;?>
<?endif;?>
<?if(isset($href_del)):?>
<a href="<?=$href_del;?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete page" /></a>
<?endif;?>
<?if(isset($href_preview)):?>
<a href="<?=$href_preview;?>" target="_blank"><img class="noborder" src="<?=$img_internet['src'];?>" width="<?=$img_internet['width'];?>" height="<?=$img_internet['height'];?>" alt="Preview page" title="Preview page" /></a>
<?endif;?>
<?if(isset($href_mv_prev)):?>
<a href="<?=$href_mv_prev;?>"><img class="noborder" src="<?=$img_up1['src'];?>" width="<?=$img_up1['width'];?>" height="<?=$img_up1['height'];?>" alt="Move to previous" title="move page to previous item" /></a>
<?endif;?>
<?if(isset($href_mv_next)):?>
<a href="<?=$href_mv_next;?>"><img class="noborder" src="<?=$img_down1['src'];?>" width="<?=$img_down1['width'];?>" height="<?=$img_down1['height'];?>" alt="Move to next" title="move page to next item" /></a>
<?endif;?>
<?if(isset($href_mv_up)):?>
<a href="<?=$href_mv_up;?>"><img class="noborder" src="<?=$img_left1['src'];?>" width="<?=$img_left1['width'];?>" height="<?=$img_left1['height'];?>" alt="Move up" title="move 1 level up" /></a>
<?endif;?>
<?if(isset($href_mv_down)):?>
<a href="<?=$href_mv_down;?>"><img class="noborder" src="<?=$img_right1['src'];?>" width="<?=$img_right1['width'];?>" height="<?=$img_right1['height'];?>" alt="Move down" title="move 1 level down" /></a>
<?endif;?>
</p>
<?endif;?>

<?if(isset($root) && $root):?>
<h1>Default settings</h1>
<p>Changing settings below will affect all pages in your website.</p>
<?endif;?>

<?if(isset($external) && $external):?>
<p class="option">This page refers to another url.</p>
<?endif;?>


<?if(isset($taglist)):?>
<h3><?=(isset($root) && $root) ? 'Default tag assignment' : 'Tag assignment'?></h3>
<table class="overview">
<tr>
<th></th>
<th>Tag</th>
<th>Plugin</th>
</tr>
<?foreach($taglist as $item):
if($item['virtual_type'] != SystemSitePlugin::TYPE_NORMAL) continue;?>
<tr>
<td>
	<?if($item['has_plugin'] && !$item['activated']):?>
	<a href="<?=$item['href_plugin_admin'];?>"><img class="noborder" src="<?=$img_warn['src'];?>" width="<?=$img_warn['width'];?>" height="<?=$img_warn['height'];?>" alt="incompatible version" title="incompatible version" /></a>
	<?else:?>
		<?if(isset($item['href_conf'])):?>
		<a href="<?=$item['href_conf'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
		<?elseif(isset($item['href_move'])):?>
		<a href="<?=$item['href_move'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
		<?else:?>
		<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
		<?endif;?>
		<?if(isset($item['href_del'])):?>
		<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
		<?else:?>
		<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
		<?endif;?>
		<?if(isset($item['href_new'])):?>
		<a href="<?=$item['href_new'];?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="plugin edit" title="plugin edit" /></a>
		<?endif;?>
		<?if(isset($item['href_edit'])):?>
		<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
		<?endif;?>
	<?endif;?>
</td>
<td><?=$item['name'];?></td>
<td><?=($item['plugin_name']) ? "{$item['plugin_name']} ({$item['plugin_type']})" : '';?></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>

<?if(isset($hiddentaglist) && $hiddentaglist):?>
<h3><?=(isset($root) && $root) ? 'Default hidden plugins' : 'Hidden plugins'?></h3>
<table class="overview">
<tr>
<th></th>
<th>Tag</th>
<th>Plugin</th>
</tr>
<?foreach($hiddentaglist as $item):?>
<tr>
<td>
	<?if(!$item['activated']):?>
	<a href="<?=$item['href_plugin_admin'];?>"><img class="noborder" src="<?=$img_warn['src'];?>" width="<?=$img_warn['width'];?>" height="<?=$img_warn['height'];?>" alt="incompatible version" title="incompatible version" /></a>
	<?else:?>
		<?if(isset($item['href_move'])):?>
		<a href="<?=$item['href_move'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
		<?endif;?>
		<?if(isset($item['href_del'])):?>
		<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
		<?endif;?>
		<?if(isset($item['href_edit'])):?>
		<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
		<?endif;?>
	<?endif;?>
</td>
<td><?=$item['name'];?></td>
<td><?=($item['plugin_name']) ? "{$item['plugin_name']} ({$item['plugin_type']})" : '';?></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>

<?if(isset($virtualTaglist) && $virtualTaglist):?>
<h3>Default tag definitions</h3>
<table class="overview">
<tr>
<th></th>
<th>Tag</th>
<th>Plugin</th>
<th>Data source</th>
</tr>
<?foreach($virtualTaglist as $item):?>
<tr>
<td>
	<?if(isset($item['href_conf'])):?>
	<a href="<?=$item['href_conf'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?elseif(isset($item['href_move'])):?>
	<a href="<?=$item['href_move'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<?if(isset($item['href_del'])):?>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<?if(isset($item['href_new'])):?>
	<a href="<?=$item['href_new'];?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="plugin edit" title="plugin edit" /></a>
	<?endif;?>
	<?if(isset($item['href_edit'])):?>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
	<?endif;?>
</td>
<td><?=$item['name'];?></td>
<td><?=($item['plugin_name']) ? "{$item['plugin_name']} ({$item['plugin_type']})" : '';?></td>
<td><a href="<?=$item['url_data_source'];?>"><?=$item['data_source'];?></a></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>

<?  /*-----details {{{------*/?>

<?if(isset($name)):?>
<table class="overview">
	<tr><th colspan="2">Properties</th></tr>
	<tr><td>Accesible</td>  			<td><?=($active)? 'yes ':'no';?></td></tr>
	<tr><td>Visible in menu</td>  	<td><?=($visible)? 'yes ':'no';?></td></tr>
	<?if($hide_name):?><tr><td>Disable page</td>		<td><?=$hide_name;?></td></tr><?endif;?>
	<?if($online || $offline):?>
	<tr><td>Accesible period</td>	
		<td>
			<?=$online ? "from: ".strftime('%d-%b-%Y',$online) : '';?>
			<?=$offline ? "to: ".strftime('%d-%b-%Y',$offline) : '';?>
		</td>
	</tr>
	<?endif;?>
	<tr><td>Ordering index</td>	<td><?=$weight;?></td></tr>
	<tr><td>Created</td>	<td><?=strftime("%c", $createdate);?></td></tr>
	<tr><td>Modified</td>	<td><?=strftime("%c", $ts);?></td></tr>
	<tr><th colspan="2">Naming</th></tr>
	<tr><td>Name</td>	<td><?=$name;?></td></tr>
	<tr><td>Page title</td>	<td><?=$title;?></td></tr>
	<tr><td>Url</td>	<td><?=$url;?></td></tr>
	<?if(isset($acl) && $acl):?>
	<tr><th colspan="2">Acl</th></tr>
	<?foreach($acl['data'] as $item):?>
	<tr><td><?=$item['name'];?></td>	<td><?=$item['description'];?></td></tr>
	<?endforeach;?>
	<?endif;?>
</table>
<?endif;//}}}?>

<?if(isset($list)):?>
<h3>Subrubieken</h3>
<table class="overview">
<tr>
<th></th>
<th>Index</th>
<th>Name</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?=$item['weight'];?></td>
<td><?=$item['name'];?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>
<?endif;//}}}?>

<?  /*-----new and edit {{{------*/
if($currentView == ViewManager::ADMIN_NEW || $currentView == ViewManager::ADMIN_EDIT):?>


<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.editform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />
<?if($currentView == ViewManager::ADMIN_NEW):?>
<input type="hidden" name="parent" value="<?=$parent;?>" />
<?endif;?>

<table id="form">
	<tr><th class="title" colspan="2">General</th></tr>
	<tr><th>Accesible</th>  			<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Visible in menu</th>  	<td><input type="checkbox" name="visible" <?=($visible)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Disable page</th>		<td><select size="1" name="hide"><?=$cbo_hide;?></select></td></tr>
	<tr>
		<th>Accesible period</th>	
		<td>
			from: <input type="text" class="date" id="online" name="online" value="<?=$online;?>" size="15" />
			to: <input type="text" class="date" id="offline" name="offline" value="<?=$offline;?>" size="15" />
		</td>
	</tr>
	<tr>
	<th>Use this page as main page for this site</th>  	<td><input type="checkbox" name="startpage" <?=($startpage)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Ordering index <em class="normal">(ascending)</em></th>	<td><input type="text" name="weight" value="<?=$weight;?>" size="5" /></td></tr>
	<?if($currentView == ViewManager::ADMIN_EDIT):?>
	<tr><th>Location</th>	<td><select size="1" name="parent"><?=$cbo_parent;?></select></td></tr>
	<?endif;?>
	<tr><th class="title" colspan="2">Naming</th></tr>
	<tr><th>Name <em class="normal">(Used by menus)</em></th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Page title</th>	<td><input type="text" name="title" value="<?=$title;?>" size="75" onfocus="syncname(this, getName('name'));" /></td></tr>
	<tr><th>Url <em class="normal">(Only use characters and numbers)</em></th>	<td><input type="text" name="url" value="<?=$url;?>" size="75" onfocus="syncname(this, stripName(getName('name')));" /></td></tr>
	<?if(isset($groupList) && $groupList):?>
	<tr><th class="title" colspan="2">Acl (Access Control Lists)</th></tr>
	<?foreach($groupList['data'] as $group):?>
	<tr><th><?=$group['name'];?></th>	<td><?=Utils::getHtmlCheckbox($rightsList, (array_key_exists($group['id'], $groupSelect)) ? $groupSelect[$group['id']] : '', "acl[{$group['id']}][]");?></td></tr>
	<?endforeach;?>
	<?endif;?>
</table>


<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----root acl {{{------*/
if($currentView == Site::VIEW_ROOT_ACL):?>


<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.editform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<?if(isset($groupList)):?>
	<tr><th class="title" colspan="2">Acl</th></tr>
	<?foreach($groupList['data'] as $group):?>
	<tr><th><?=$group['name'];?></th>	<td><?=Utils::getHtmlCheckbox($rightsList, (array_key_exists($group['id'], $groupSelect)) ? $groupSelect[$group['id']] : '', "acl[{$group['id']}][]");?></td></tr>
	<?endforeach;?>
	<?endif;?>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == ViewManager::ADMIN_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Are you sure you want to delete <strong><?=$name;?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----edit theme {{{------*/
if($currentView == Site::VIEW_THEME):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />


<table id="form">
	<tr><th class="title">Theme</th></tr>
	<tr><td><select size="1" name="theme_id" onchange="getTagList(getSelectValue(this), <?=$tree_id;?>,'taglist','notaglist');"><?=$cbo_theme;?></select></td></tr>
	<tr><th class="title">Tags</th></tr>
	<tr><td>
	<table id="taglist">
	<thead>
	<tr>
	<th></th><th>Name</th><th>Split up into</th>
	</tr>
	</thead>
	<tbody>
	<?if(isset($tags)):?>
	<?foreach($tags as $item):?>
	<tr>
		<td>
		<?if($item['href_del']):?>
		<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="Tags delete" title="Tags delete" /></a>
		<?endif;?>
		<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
		</td>
		
		<td><?=$item['name'];?></td>
		<td><?if(isset($item['userdefined'])) print($item['userdefined']);?></td>
	</tr>
	<?endforeach;?>
	<?endif;?>
	</tbody>
	</table>
	</td></tr>
	<tr><th class="title">Unused tags</th></tr>
	<tr><td>
	<table id="notaglist">
	<?if(isset($unusedTags)):?>
	<?if($unusedTags['data']):?>
	<thead>
	<tr>
	<th></th><th>Name</th><th>Split up into</th>
	</tr>
	</thead>
	<tbody>
	<?foreach($unusedTags['data'] as $item):?>
	<tr>
		<td><a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
		<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a></td>
		<td><?=$item['parent_tag'];?></td>
		<td><?if(isset($item['userdefined'])) print($item['userdefined']);?></td>
	</tr>
	<?endforeach;?>
	<?endif;?>
	</tbody>
	<?endif;?>
	</table>
	</td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----edit tag {{{------*/
if($currentView == Site::VIEW_TAG):?>

<h3>Modified Tag: <?=$parent_tag;?></h3>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="parent_tag" value="<?=$parent_tag;?>" />


<table id="form">
	<tr><th class="title" colspan="2">Parent container template</th></tr>
	<tr>
		<td colspan="2">
			<input type="checkbox" name="remove_container" <?=($remove_container)? 'checked ':'';?> class="noborder" />
			Remove container template from parent tag.</td>
	</tr>
	<tr><th class="title" colspan="2">User defined tags</th></tr>
	<tr>
	<td colspan="2">
		<p><em><strong>Enter one tag per line and only use characters and numbers</strong><br />
		HINT: Add a colon <strong>:</strong> at the end of a tag to disable inheritance of a container template from the parent tag.
</em></p>
		<textarea name="tags" rows="3" cols="75"><?=$tags;?></textarea>
	</td>
	</tr>
	<tr>
		<th colspan="2" class="title">
			Html template to render user defined tags above
		</th>
	</tr>
	<tr> 
		<td colspan="2">
			<p>A php tag has the following form: &lt;?=$foobar;?&gt;<br /><strong><em>Use the file load button to generate php tags.</em></strong></p>
			<textarea id="area1" name="template" rows="20" cols="150"><?=$template;?></textarea>
		</td>
	</tr>
	<tr>
		<th colspan="2" class="title">
			CSS Stylesheet
		</th></tr>
		<tr><td colspan="2"><textarea id="area2" name="stylesheet" rows="20" cols="150"><?=$stylesheet;?></textarea></td>
	</tr>
	<tr><th class="title" colspan="2">Available variables</th></tr>
	<tr>
	<td colspan="2">
			<ul>
			<?foreach($filevars as $key=>$value):?>
			<?if(is_array($value)):?>
				<?foreach($value as $subkey=>$item):?>
					<li><?printf('$%s[%s] = %s', $key, $subkey, $item);?></li>
				<?endforeach;?>
			<?else:?>
			<li><?="\$$key = $value";?></li>
			<?endif;?>
			<?endforeach;?>
			</ul>
	</td>
	</tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete tag {{{------*/
if($currentView == Site::VIEW_TAG_DEL):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="parent_tag" value="<?=$parent_tag;?>" />

<p>Are you sure you want to delete the user defined tag '<?=$name;?>'?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----configure plugin {{{------*/
if($currentView == Site::VIEW_PLUGIN_CONF):?>

<form action="<?=$urlPath;?>" method="post" name="editform">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Select Plugin</th></tr>
	<tr><th>Plugin</th>	<td><select size="1" name="plugin_id" onchange="getTypeList(getSelectValue(this), 'type');"><?=$cbo_plugin;?></select></td></tr>
	<tr><th>Type</th>	<td><select size="1" name="plugin_type" id="type"><?=$cbo_plugin_type;?></select></td></tr>
	<tr><th>Display settings</th>	<td><select size="1" name="plugin_view" id="view"><?=$cbo_plugin_view;?></select></td></tr>
	<tr><th>Display recursive</th>	<td><input type="checkbox" name="recursive" <?=($recursive)? 'checked ':'';?> class="noborder" /></select></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----move plugin {{{------*/
if($currentView == Site::VIEW_PLUGIN_MOVE):?>

<form action="<?=$urlPath;?>" method="post" name="editform">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Move plugin</th></tr>
	<tr><th>Plugin name</th>	<td><?=$plugin_name;?> (<?=$plugin_type;?> )</td></tr>
	<tr><th>Current tag</th>	<td><?=$tag;?></td></tr>
	<tr><th>Destination node</th>	<td><select size="1" name="new_tree_id" onchange="getAvailableTagList(getSelectValue(this), '<?=$tag;?>', 'tagdest');"><?=$cbo_tree_id;?></select></td></tr>
	<tr><th>Destination tag</th>	<td><select size="1" id="tagdest" name="new_tag"><?=$cbo_tag;?></select></td></tr>
	<tr><th>Display settings</th>	<td><select size="1" name="plugin_view" id="view"><?=$cbo_plugin_view;?></select></td></tr>
	<tr><th>Display recursive</th>	<td><input type="checkbox" name="recursive" <?=($recursive)? 'checked ':'';?> class="noborder" /></select></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete plugin {{{------*/
if($currentView == Site::VIEW_PLUGIN_DEL):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p>Are you sure you want to delete the plugin connected to the tag '<?=$name;?>'?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

</td>
</tr>
</table>
