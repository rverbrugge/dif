<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----overview {{{------*/
if($currentView == NewsLetter::VIEW_GROUP_OVERVIEW):?>


<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
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
<th>Newsletters sent</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
	<a href="<?=$item['href_user'];?>"><img class="noborder" src="<?=$img_usr['src'];?>" width="<?=$img_usr['width'];?>" height="<?=$img_usr['height'];?>" alt="users" title="users" /></a>
</td>
<td><?=$item['name'];?></td>
<td><?=$item['count'];?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == NewsLetter::VIEW_GROUP_NEW || $currentView == NewsLetter::VIEW_GROUP_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">General</th></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == NewsLetter::VIEW_GROUP_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Are you sure you want to delete <strong><?=$name;?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- user {{{------*/
if($currentView == NewsLetter::VIEW_GROUP_USER):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:selectall('cbo_usr_used');document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form name="myform" action="<?=$urlPath;?>" method="post" enctype="multipart/form-data" onsubmit="selectall('cbo_usr_used');">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
  <tr><th colspan="2" class="title"><?=$title;?></th></tr>
	<tr><td colspan="2">
  	<table class="multibox">
		<tr>
			<td>
  		<h3>Available</h3>
  		<select multiple size="13" id="cbo_usr_free" name="usr_free[]"><?=$cbo_usr_free;?></select>
			</td>
  	<td class="nav">
  		<input type="button" class="formbutton" onclick="move('cbo_usr_free','cbo_usr_used')" value="&raquo;" />
  		<input type="button" class="formbutton" onclick="move('cbo_usr_used','cbo_usr_free')" value="&laquo;" />
  	</td>
  	<td>
			<h3>Used</h3>
  		<select multiple size="13" id="cbo_usr_used" name="usr_used[]"><?=$cbo_usr_used;?></select>
		</td></tr>
		</table>
  </td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>
