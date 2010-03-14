<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>
<?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == Poll::VIEW_ITEM_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
</p>

<table class="overview">
<tr>
<th></th>
<th>Index</th>
<th>Name</th>
<th>Votes</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?=$item['weight'];?></td>
<td><?=htmlentities($item['name']);?></td>
<td><?=$item['votes'];?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?endif;//}}}?>

<?  /*-----tree new and edit{{{------*/
if($currentView == Poll::VIEW_ITEM_NEW || $currentView == Poll::VIEW_ITEM_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="poll_id" value="<?=$poll_id;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Item</th></tr>
	<tr><th>Active</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Index</th>	<td><input type="text" name="weight" value="<?=$weight;?>" size="5" /></td></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="submit" value="Save + new" name="addnew" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == Poll::VIEW_ITEM_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="poll_id" value="<?=$poll_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Do you realy want to delete the item <strong><?=htmlentities($formatName);?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == Poll::VIEW_DETAIL):?>

<?if($comment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$comment['totalItems'];?> posts</p><?endif;?>

<?foreach($comment['data'] as $item):?>
<h3 class="cmtname"><?=$item['name'];?> <span class="cmtdate"><?=strftime('%c',$item['createdate']);?></span> </h3>
<div class="cmtitem">  
<?=nl2br(htmlentities($item['text']));?>
</div>
<?endforeach;?>

<?if($poll_settings['comment_title']):?><h2 id="cmttitle"><?=$poll_settings['comment_title'];?></h2><?endif;?>

<?if(isset($commentError)):?><p class="error"><?=$commentError;?></p><?endif;?>

<div id="cmtadd">
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="<?=PollComment::VIEW_KEY;?>" value="<?=PollComment::VIEW_ADD;?>" />
<input type="hidden" name="id" value="<?=$poll_['id'];?>" />
<input type="hidden" name="poll_id" value="<?=$poll_['id'];?>" />


<table>
	<tr><th><?=$poll_settings['cap_name'];?></th>				<td><input type="text" name="name" value="<?=isset($cmtValues)?$cmtValues['name']:'';?>" size="50" /></td></tr>
	<tr><th><?=$poll_settings['cap_desc'];?></th>	<td><textarea name="text" rows="4" cols="50"><?=isset($cmtValues)?$cmtValues['text']:'';?></textarea></td></tr>
</table>

<input type="submit" value="<?=$poll_settings['cap_submit'];?>" class="formbutton" />
</form>
</div>

<?endif;//}}}?>
