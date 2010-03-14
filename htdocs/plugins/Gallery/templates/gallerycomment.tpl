<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>
<?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == Gallery::VIEW_COMMENT_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete selection" title="delete selection" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="myform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="gal_id" value="<?=$gal_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="<?=ViewManager::getInstance()->getUrlId();?>" value="<?=Gallery::VIEW_COMMENT_DELETE;?>" />

<table class="overview">
<tr>
<th><a href="javascript:toggleCheckBoxes(document.myform);">toggle selection</a></th>
<th>Name</th>
<th>Email</th>
<th>Text</th>
<th>Ip</th>
<th>Date</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<input type="checkbox" name="id[]" value="<?=$item['id'];?>" class="noborder" />
	<!--a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a-->
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?=$item['name'];?></td>
<td><?=$item['email'];?></td>
<td><?=$item['text'];?></td>
<td><?=$item['ip'];?></td>
<td><?=strftime('%c', $item['date']);?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>
</form>

<?endif;//}}}?>

<?  /*-----tree edit {{{------*/
if($currentView == Gallery::VIEW_COMMENT_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="gal_id" value="<?=$gal_id;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Algemeen</th></tr>
	<tr><th>Active</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Date</th>	<td><input type="text" name="date" value="<?=$date;?>" size="25" /></td></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Email</th>	<td><input type="text" name="email" value="<?=$email;?>" size="75" /></td></tr>
	<tr><th>Text</th>	<td><textarea name="text" rows="10" cols="75"><?=$text;?></textarea></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == Gallery::VIEW_COMMENT_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="gal_id" value="<?=$gal_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Weet u zeker dat u de post <strong><?=$formatName;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == Gallery::VIEW_DETAIL || $currentView == ''):?>

<div id="commenttag">
<?if($gallerysettings['comment_display'] == GalleryComment::DISP_FORM_BOTTOM):?>
<?if($comment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$comment['totalItems'];?> posts</p><?endif;?>

<?foreach($comment['data'] as $item):?>
<h3 class="cmtname"><?=htmlentities($item['name']);?> <span class="cmtdate"><?=strftime('%c',$item['date']);?></span> </h3>
<div class="cmtitem">  
<?=nl2br(htmlentities($item['text']));?>
</div>
<?endforeach;?>
<?endif;?>

<?if($gallerysettings['comment_title']):?><h2 id="cmttitle"><?=$gallerysettings['comment_title'];?></h2><?endif;?>

<?if(isset($commentError)):?><p class="error"><?=$commentError;?></p><?endif;?>

<div id="cmtadd">
<!--form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="<?=GalleryComment::VIEW_KEY;?>" value="<?=GalleryComment::VIEW_ADD;?>" />
<input type="hidden" name="id" value="<?=$gallery['id'];?>" />
<input type="hidden" name="gallery_id" value="<?=$gallery['id'];?>" /-->


<table>
	<tr><th><?=$gallerysettings['cap_name'];?></th>				<td><input id="comm_name" type="text" name="name" value="<?=isset($cmtValues)?$cmtValues['name']:'';?>" size="<?=$gallerysettings['comment_width'];?>" /></td></tr>
<?if($gallerysettings['cap_email']):?>
	<tr><th><?=$gallerysettings['cap_email'];?></th>				<td><input id="comm_email" type="text" name="email" value="<?=isset($cmtValues)?$cmtValues['email']:'';?>" size="<?=$gallerysettings['comment_width'];?>" /></td></tr>
<?endif;?>
	<tr><th><?=$gallerysettings['cap_desc'];?></th>	<td><textarea id="comm_text" name="text" rows="4" cols="<?=$gallerysettings['comment_width'];?>"><?=isset($cmtValues)?$cmtValues['text']:'';?></textarea></td></tr>
</table>

<input type="button" value="<?=$gallerysettings['cap_submit'];?>" class="formbutton" onclick="addComment(<?=$gal_id;?>, document.getElementById('comm_name').value, document.getElementById('comm_text').value)" />
<!--/form-->
</div>

<?if($gallerysettings['comment_display'] == GalleryComment::DISP_FORM_TOP):?>
<?if($comment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$comment['totalItems'];?> posts</p><?endif;?>

<?foreach($comment['data'] as $item):?>
<h3 class="cmtname"><?=htmlentities($item['name']);?> <span class="cmtdate"><?=strftime('%c',$item['date']);?></span> </h3>
<div class="cmtitem">  
<?=nl2br(htmlentities($item['text']));?>
</div>
<?endforeach;?>
<?endif;?>
</div>
<?endif;//}}}?>
