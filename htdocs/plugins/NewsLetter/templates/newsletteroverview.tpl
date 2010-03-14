<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == ViewManager::TREE_OVERVIEW):?>


<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_conf;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configure" title="configure" /></a>
<a href="<?=$href_user;?>"><img class="noborder" src="<?=$img_usr['src'];?>" width="<?=$img_usr['width'];?>" height="<?=$img_usr['height'];?>" alt="Users" title="Users" /></a>
<a href="<?=$href_group;?>"><img class="noborder" src="<?=$img_usrgrp['src'];?>" width="<?=$img_usrgrp['width'];?>" height="<?=$img_usrgrp['height'];?>" alt="User groups" title="User groups" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
</p>

<div class="search">
<form target="_self" action="<?=$urlPath;?>" method="get">
<?foreach($searchparam as $key=>$value):?>
<input type="hidden" name="<?=$key;?>" value="<?=$value;?>" />
<?endforeach;?>
<input type="text" size="50" name="search" value="<?=array_key_exists('search', $searchcriteria) ? $searchcriteria['search'] : '';?>" />
<input type="submit" value="search" class="formbutton" />
</form>
</div>


<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Name</th>
<th>Online</th>
<th>Offline</th>
<th>Image</th>
<th>Distributed</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configure" title="configure" /></a>
	<a href="<?=$item['href_plugin'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
	<a href="<?=$item['href_att'];?>"><img class="noborder" src="<?=$img_file['src'];?>" width="<?=$img_file['width'];?>" height="<?=$img_file['height'];?>" alt="Files" title="Files" /></a>
	<a href="<?=$item['href_preview'];?>"><img class="noborder" src="<?=$img_internet['src'];?>" width="<?=$img_internet['width'];?>" height="<?=$img_internet['height'];?>" alt="Preview" title="Preview" /></a>
	<a href="<?=$item['href_send'];?>"><img class="noborder" src="<?=$img_mail['src'];?>" width="<?=$img_mail['width'];?>" height="<?=$img_mail['height'];?>" alt="Send Newsletter" title="Send Newsletter" /></a>
</td>
<td><?=$item['name'];?></td>
<td><?=strftime('%d-%b-%Y', $item['online']);?></td>
<td><?=$item['offline']?strftime('%d-%b-%Y', $item['offline']):'';?></td>
<td>
	<?if($item['thumbnail']):?>
	<a href="<?=$item['href_edit'];?>"><img src="<?=$item['thumbnail']['src'];?>" width="<?=$item['thumbnail']['width'];?>" height="<?=$item['thumbnail']['height'];?>" alt="" /></a>
	<?endif;?>
</td>
<td><?=$item['count'] ? strftime('%c', $item['send_date'])." [{$item['count']}]" : '';?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == ViewManager::TREE_NEW || $currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<input type="hidden" name="img_x" value="<?=$img_x;?>" id="x1" />
<input type="hidden" name="img_y" value="<?=$img_y;?>" id="y1" />
<input type="hidden" name="x2" id="x2" />
<input type="hidden" name="y2" id="y2" />
<input type="hidden" name="img_width" value="<?=$img_width;?>" id="width" />
<input type="hidden" name="img_height" value="<?=$img_height;?>" id="height" />

<table id="form">
	<tr><th class="title" colspan="2">General</th></tr>
	<tr><th>Active</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Active period</th>	
		<td>
			from: <input type="text" class="date" id="online" name="online" value="<?=$online;?>" size="15" />
			till: <input type="text" class="date" id="offline" name="offline" value="<?=$offline;?>" size="15" />
		</td>
	</tr>
	<tr><th>Theme</th>	<td><select size="1" name="theme_id"><?=$cbo_theme;?></select></td></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Introduction</th>	<td><textarea name="intro" rows="3" cols="75"><?=$intro;?></textarea></td></tr>
	<tr><th colspan="2">Image</th></tr>
	<tr>
		<td colspan="2">
		<?if($image):?>

		<div id="imgWrap">
		<img id="imgsrc" src="<?=$image['src'];?>" width="<?=$image['width'];?>" height="<?=$image['height'];?>" alt="" />
		</div>

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

<?  /*-----delete {{{------*/
if($currentView == ViewManager::TREE_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Weet u zeker dat u het bericht <strong><?=$name;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
<?if(isset($newsLetterErrorMessage)):?><p class="error"><?=$newsLetterErrorMessage;?></p><?endif;?>

<form action="<?=$urlPath;?>" name="myform" method="post">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<table>
	<tr><th><?=$settings['cap_gender'];?></th>				<td><select size="1" name="gender"><?=$cbo_gender;?></select></td></tr>
	<tr><th><?=$settings['cap_name'];?></th>				<td><input type="text" name="name" value="<?=$name;?>" size="<?=$settings['field_width'];?>" /></td></tr>
	<tr><th><?=$settings['cap_email'];?></th>				<td><input type="text" name="email" value="<?=$email;?>" size="<?=$settings['field_width'];?>" /></td></tr>
	<?if($groupList['totalItems'] > 0):?>
	<?foreach($groupList['data'] as $item):?>
	<tr><td><input type="checkbox" name="group[<?=$item['id'];?>]" <?=in_array($item['id'], $group) ? 'checked' : '';?> class="noborder" /></td>				<td><?=$item['name'];?></td></tr>
	<?endforeach;?>
	<?endif;?>
</table>

<input type="submit" value="<?=$settings['cap_submit'];?>" class="formbutton" />
<!--onclick="subscribe(<?=$tree_id;?>, '<?=$tag;?>', $('name').getValue(), $('email').getValue());" /-->
</form>
<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == NewsLetter::VIEW_DETAIL):?>
	<em><?=strftime('%A %d %B %Y', $news['online']);?> &nbsp; views: <?=$news['count'];?></em>

	<?if($news['intro']):?><p class="newsintro"><?=nl2br(htmlentities($news['intro']));?></p><?endif;?>

	<?=$news['text'];?>

	<?=$newsattachment;?>

	<?=isset($newscomment)?$newscomment:'';?>

	<p><a href="<?=$href_back;?>"><?=$newssettings['cap_back'];?></a></p>
<?endif;//}}}?>

<?  /*-----preview {{{------*/
if($currentView == NewsLetter::VIEW_PREVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<form action="<?=$urlPath;?>" name="myform" method="post">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Generate newsletter preview</th></tr>
	<tr><th>Recipient address</th>				<td><input type="text" name="email" value="<?=$email;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Generate" onclick="document.myform.target='_blank';" class="formbutton" />
<input type="submit" name="send" value="Send to email" onclick="document.myform.target='_self';" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>

<?  /*-----send newsletter {{{------*/
if($currentView == NewsLetter::VIEW_SEND):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>


<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="nl_id" value="<?=$nl_id;?>" />

<?if($send_date):?>
<pre>
<strong>WARNING: This newsletter has already been sent!</strong><br />
Do you really want to send this newsletter again?
</pre>
<?endif;?>

<?if($grouplist['totalItems'] > 0):?>
<p>You are about to send the newsletter <strong><?=$name;?></strong> to all recipients in the selected groups below.</p>

<p><em>Leave groups unselected to send the mailing to everyone in the list</em></p>

<table id="form">
	<tr><th class="title" colspan="2">User groups</th></tr>
	<?foreach($grouplist['data'] as $item):?>
	<tr><th><input type="checkbox" name="groups[<?=$item['id'];?>]" <?=$item['selected'] ? 'checked' : '';?> /></th>	 <td><?=$item['name'];?></td></tr>
	<?endforeach;?>
</table>

<p>Are you sure to send the newsletter <strong><?=$name;?></strong> to all recipients in the selected groups?</p>
<?else:?>
<p>You are about to send the newsletter <strong><?=$name;?></strong> to all recipients.</p>
<p>Are you sure to send the newsletter <strong><?=$name;?></strong>?</p>
<?endif;?>

<p><strong>CAUTION: this action cannot be undone!</strong></p>

<input type="submit" value="Send newsletter" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>

<?endif;//}}}?>

<?  /*-----send newsletter success {{{------*/
if($currentView == NewsLetter::VIEW_SEND_SUCCESS):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<p>Newsletter successfully send to recipients.</p>


<?endif;//}}}?>

