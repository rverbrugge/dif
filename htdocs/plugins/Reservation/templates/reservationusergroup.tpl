<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----overview {{{------*/
if($currentView == Reservation::VIEW_USER_GROUP_OVERVIEW):?>


<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
</p>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Name</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?printf('%s, %s', $item['name'], $item['firstname']);?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == Reservation::VIEW_USER_GROUP_NEW || $currentView == Reservation::VIEW_USER_GROUP_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:selectall('cbo_usr_used'); document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data" onsubmit="selectall('cbo_usr_used');">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">General</th></tr>
	<?if($currentView == Reservation::VIEW_USER_GROUP_NEW):?>
	<tr><th>Name</th>	<td><select onchange="getUserSelect($F('usr_id'));" size="1" id="usr_id" name="usr_id"><?=$cbo_user;?></select></td></tr>
	<?else:?>
	<tr><th>Name</th>	<td>
	<strong><?printf("%s, %s", $name, $firstname);?></strong>
<input type="hidden" name="usr_id" value="<?=$usr_id;?>" />
	</td></tr>
	<?endif;?>
  <tr><th colspan="2" class="title">Users</th></tr>
	<tr><td id="userselect" colspan="2">
		<?=$tpl_userselect;?>
  </td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == Reservation::VIEW_USER_GROUP_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Are you sure you want to delete <strong><?=$name;?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>
