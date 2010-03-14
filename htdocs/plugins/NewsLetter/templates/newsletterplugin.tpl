<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>
<?endif;?>

<?  /*----- overview {{{------*/
if($currentView == NewsLetter::VIEW_PLUGIN_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_config;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configure" title="configure" /></a>
<a href="<?=$href_att;?>"><img class="noborder" src="<?=$img_file['src'];?>" width="<?=$img_file['width'];?>" height="<?=$img_file['height'];?>" alt="attachments" title="attachments" /></a>
<a href="<?=$href_preview;?>"><img class="noborder" src="<?=$img_internet['src'];?>" width="<?=$img_internet['width'];?>" height="<?=$img_internet['height'];?>" alt="preview" title="preview" /></a>
<a href="<?=$href_send;?>"><img class="noborder" src="<?=$img_mail['src'];?>" width="<?=$img_mail['width'];?>" height="<?=$img_mail['height'];?>" alt="Send Newsletter" title="Send Newsletter" /></a>
</p>

<table class="overview">
<tr>
<th></th>
<th>Tag</th>
<th>Plugin</th>
</tr>
<?foreach($taglist as $item):?>
<tr>
<td>
	<?if(isset($item['href_conf'])):?>
	<a href="<?=$item['href_conf'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?elseif(isset($item['href_move'])):?>
	<a href="<?=$item['href_move'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?endif;?>
	<?if($item['type'] == NewsLetterTag::TYPE_THEME):?>
	<a href="<?=$item['href_tag'];?>"><img class="noborder" src="<?=$img_list['src'];?>" width="<?=$img_list['width'];?>" height="<?=$img_list['height'];?>" alt="split tag" title="split tag" /></a>
	<?endif;?>
	<?if(isset($item['href_del'])):?>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<?if(isset($item['href_edit'])):?>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
	<?endif;?>
</td>
<td><?=$item['name'];?></td>
<td><?=($item['plugin_name']) ? "{$item['plugin_name']}" : '';?></td>
</tr>
<?endforeach;?>
</table>

<?if($splitTaglist):?>
<h3>Splitted tags</h3>
<table class="overview">
<tr>
<th></th>
<th>Tag</th>
<th>Plugin</th>
<th>Split up tags</th>
</tr>
<?foreach($splitTaglist as $item):?>
<tr>
<td>
	<?if(isset($item['href_move'])):?>
	<a href="<?=$item['href_move'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<a href="<?=$item['href_tag'];?>"><img class="noborder" src="<?=$img_list['src'];?>" width="<?=$img_list['width'];?>" height="<?=$img_list['height'];?>" alt="split tag" title="split tag" /></a>
	<a href="<?=$item['href_tag_del'];?>"><img class="noborder" src="<?=$img_list_del['src'];?>" width="<?=$img_list_del['width'];?>" height="<?=$img_list_del['height'];?>" alt="delete split tag" title="delete split tag" /></a>
	<?if(isset($item['href_del'])):?>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<?if(isset($item['href_edit'])):?>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
	<?endif;?>
</td>
<td><?=$item['name'];?></td>
<td><?=$item['plugin_name'];?></td>
<td><?=join(',', $item['child_tags']);?></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>

<?if(isset($hiddentaglist) && $hiddentaglist):?>
<h3>Hidden plugins</h3>
<table class="overview">
<tr>
<th></th>
<th>Tag</th>
<th>Plugin</th>
</tr>
<?foreach($hiddentaglist as $item):?>
<tr>
<td>
	<?if(isset($item['href_move'])):?>
	<a href="<?=$item['href_move'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configureren" title="configureren" /></a>
	<?endif;?>
	<?if(isset($item['href_del'])):?>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
	<?endif;?>
	<?if(isset($item['href_edit'])):?>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
	<?endif;?>
</td>
<td><?=$item['name'];?></td>
<td><?=$item['plugin_name'];?></td>
</tr>
<?endforeach;?>
</table>
<?endif;?>

<?endif;//}}}?>

<?  /*----- config {{{------*/
if($currentView == NewsLetter::VIEW_PLUGIN_CONFIG):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_tag" value="<?=$nl_tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />

<table id="form">
	<tr><th class="title" colspan="2"><?=$tag_description;?> Tag configuration</th></tr>
	<tr><th>Tag name</th>	<td><strong><?=$tag_description;?></strong></td></tr>
	<tr><th>Type of tag</th>	<td><select size="1" name="type" onchange="setPlugins(getSelectValue(this), 'plugins');"><?=$cbo_type;?></select></td></tr>
</table>

<div id="plugins">
<table class="form">
<tr><th class="title" colspan="2">Plugin settings</th></tr>
<tr><th>Plugin</th>	<td><select size="1" name="plugin_id" onchange="getTypeList(getSelectValue(this), 'type');"><?=$cbo_plugin;?></select></td></tr>
<tr><th>Type</th>	<td><select size="1" name="plugin_type" id="type"><?=$cbo_plugin_type;?></select></td></tr>
</table>
</div>

<script type="text/javascript">Element.hide('plugins');</script>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == NewsLetter::VIEW_PLUGIN_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_tag" value="<?=$nl_tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />

<p>Are you sure you want to delete the plugin for <strong><?=$formatName;?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- edit plugin {{{------*/
if($currentView == NewsLetter::VIEW_PLUGIN_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<?if($type != NewsLetterPlugin::TYPE_CODE && $type != NewsLetterPlugin::TYPE_CODE_HEADER && $type != NewsLetterPlugin::TYPE_CODE_FOOTER):?>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
<?endif;?>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_tag" value="<?=$nl_tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />

<?if($type == NewsLetterPlugin::TYPE_TEXT):?>
<table id="form">
	<tr><th class="title" colspan="2">Content</th></tr>
	<tr><td colspan="2">
		<?=$fckBox;?>
	</td></tr>
</table>
<?elseif($type == NewsLetterPlugin::TYPE_CODE || $type == NewsLetterPlugin::TYPE_CODE_HEADER || $type == NewsLetterPlugin::TYPE_CODE_FOOTER):?>
<table id="form">
	<tr><th class="title" colspan="2">Content</th></tr>
	<tr><td colspan="2">
		<textarea id="area1" name="text" rows="20" cols="150"><?=$text;?></textarea>
	</td></tr>
	<tr><th class="title" colspan="2">Available variables</th></tr>
	<tr>
	<td colspan="2">
			<ul>
			<?foreach($filevars as $key=>$value):?>
			<?if(is_array($value)):?>
				<?foreach($value as $subkey=>$item):?>
					<?if($item):?>
					<li><?printf("$%s['%s'] = %s", $key, $subkey, $item);?></li>
					<?else:?>
					<li><?printf("$%s['%s']", $key, $subkey);?></li>
					<?endif;?>
				<?endforeach;?>
			<?else:?>
			<li><?="\$$key = $value";?></li>
			<?endif;?>
			<?endforeach;?>
			</ul>
	</td>
	</tr>
</table>
<?elseif($type == NewsLetterPlugin::TYPE_PLUGIN):?>
<?=$tpl_plugin;?>
<?endif;?>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----move plugin {{{------*/
if($currentView == NewsLetter::VIEW_PLUGIN_MOVE):?>

<form action="<?=$urlPath;?>" method="post" name="editform">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_tag" value="<?=$nl_tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Move plugin</th></tr>
	<tr><th>Plugin name</th>	<td><?=$plugin_name;?> (<?=$plugin_type;?> )</td></tr>
	<tr><th>Current tag</th>	<td><?=$nl_tag;?></td></tr>
	<tr><th>Destination tag</th>	<td><select size="1" name="newtag"><?=$cbo_tag;?></select></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

