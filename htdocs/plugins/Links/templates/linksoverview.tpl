<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == ViewManager::TREE_OVERVIEW):?>


<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
<a href="<?=$href_conf;?>"><img class="noborder" src="<?=$img_conf['src'];?>" width="<?=$img_conf['width'];?>" height="<?=$img_conf['height'];?>" alt="configure" title="configure" /></a>
<a href="<?=$href_import;?>"><img class="noborder" src="<?=$img_import['src'];?>" width="<?=$img_import['width'];?>" height="<?=$img_import['height'];?>" alt="import" title="import" /></a>
<a href="<?=$href_resize;?>"><img class="noborder" src="<?=$img_run['src'];?>" width="<?=$img_run['width'];?>" height="<?=$img_run['height'];?>" alt="image resize" title="image resize" /></a>
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


<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Index</th>
<th>Name</th>
<th>Target</th>
<th>Image</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
	<?if(isset($item['href_mv_prev'])):?>
	<a href="<?=$item['href_mv_prev'];?>"><img class="noborder" src="<?=$img_up1['src'];?>" width="<?=$img_up1['width'];?>" height="<?=$img_up1['height'];?>" alt="Move to previous" title="move page to previous item" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<?if(isset($item['href_mv_next'])):?>
	<a href="<?=$item['href_mv_next'];?>"><img class="noborder" src="<?=$img_down1['src'];?>" width="<?=$img_down1['width'];?>" height="<?=$img_down1['height'];?>" alt="Move to next" title="move page to next item" /></a>
	<?endif;?>
</td>
<td><?=$item['weight'];?></td>
<td><?=$item['name'];?></td>
<td><?=$item['link'];?></td>
<td>
	<?if($item['thumbnail']):?>
	<a href="<?=$item['href_edit'];?>"><img src="<?=$item['thumbnail']['src'];?>" width="<?=$item['thumbnail']['width'];?>" height="<?=$item['thumbnail']['height'];?>" alt="" /></a>
	<?endif;?>
</td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items: <?=$list['links'];?></p><?endif;?>
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
	<tr><th>Index</th>	<td><input type="text" name="weight" value="<?=$weight;?>" size="5" /></td></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Introduction</th>	<td><textarea name="intro" rows="3" cols="75"><?=$intro;?></textarea></td></tr>
	<tr><th colspan="2">Image</th></tr>
	<tr>
		<td colspan="2">
		<?if($image):?>

		<div id="imgWrap">
		<img id="imgsrc" src="<?=$image['src'];?>" width="<?=$image['width'];?>" height="<?=$image['height'];?>" alt="" />
		</div>

		<p>delete image <input type="checkbox" class="noborder" name="thumbnail_delete" /></p>

		<?endif;?>
		<input type="file" name="image" value="" size="60" />
		</td>
	</tr>
	<tr><th class="title" colspan="2">Destination</th></tr>
	<tr><th>Url</th>	<td><input type="text" name="url" value="<?=$url;?>" size="75" /></td></tr>
	<tr><th>Page</th>	<td><select size="1" name="ref_tree_id"><?=$cbo_tree_id;?></select></td></tr>
	<tr><th colspan="2">Text</th></tr>
	<tr><td colspan="2">
		<?=$fckBox;?>
	</td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- import {{{------*/
if($currentView == Links::VIEW_IMPORT):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p>Location images: <strong><?=$importPath;?></strong></p>

<p><strong>CAUTION: This process can take a while.</strong><br />
This process can be aborted when the execution time limit on your webserver is set too low.
</p>
<p>
You can also start this process at the shell prompt:
<pre>php index.php --tree_id=<?=$tree_id;?> --tag=<?=$tag;?> --class=Links --task=import --username=<?=$username;?> --password=&lt;password&gt;</pre>
</p>

<p>Are you sure you want to import images?</p>

<input type="submit" value="Import" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />
</form>
<?else:?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<?=join("<br />", $debug);?>
<?endif;?>

<?endif;//}}}?>

<?  /*----- resize {{{------*/
if($currentView == Links::VIEW_RESIZE):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p><strong>CAUTION: This process can take a while.</strong><br />
This process can be aborted when the execution time limit on your webserver is set too low.
</p>
<p>
You can also start this process at the shell prompt:
<pre>php index.php --tree_id=<?=$tree_id;?> --tag=<?=$tag;?> --class=Links --task=resize --username=<?=$username;?> --password=&lt;password&gt;</pre>
</p>

<p>Are you sure you want to resize the images?</p>

<input type="submit" value="Resize" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />
</form>
<?else:?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<?=join("<br />", $debug);?>
<?endif;?>

<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == ViewManager::TREE_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Are you sure you want to delete <strong><?=$name;?></strong></p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?if($links['page_numbers']['total'] > 1):?><p class="pagenav"><?=$links['totalItems'];?> items: <?=$links['links'];?></p><?endif;?>

<?/*----- text right from image {{{-----*/?>
<?if($settings['display'] == LinksSettings::DISP_FULL_RIGHT):?>

<?foreach($links['data'] as $item):?>
<table class="linksfull">  
<tr>
	<?if($item['thumbnail']):?>
	<td>
	<a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><img <?if(!$settings['image_border']) print('class="lnnoborder"');?> src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=$item['name'];?>" /></a>
	</td>
	<?endif;?>
	<td class="linksmain">
	<h3><a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><?=$item['name'];?></a></h3>
	<?if($item['intro']):?>
	<p><?=nl2br($item['intro']);?></p>
	<?endif;?>
	</td>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- text left from image {{{-----*/?>
<?elseif($settings['display'] == LinksSettings::DISP_FULL_LEFT):?>

<?foreach($links['data'] as $item):?>
<table class="linksfull">  
<tr>
	<td class="linksmain">
	<h3><a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><?=$item['name'];?></a></h3>
	<?if($item['intro']):?>
	<p><?=nl2br($item['intro']);?></p>
	<?endif;?>
	</td>
	<?if($item['thumbnail']):?>
	<td>
	<a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><img <?if(!$settings['image_border']) print('class="lnnoborder"');?> src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=$item['name'];?>" /></a>
	</td>
	<?endif;?>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- text above image {{{-----*/?>
<?elseif($settings['display'] == LinksSettings::DISP_FULL_TOP):?>

<?foreach($links['data'] as $item):?>
<div class="linksitem">  
	<h3><a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><?=$item['name'];?></a></h3>
	<?if($item['intro']):?><p><?=nl2br($item['intro']);?></p><?endif;?>
	<?if($item['thumbnail']):?>
	<a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><img <?if(!$settings['image_border']) print('class="lnnoborder"');?> src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=$item['name'];?>" /></a>
	<?endif;?>
</div>
<?endforeach;?>
<?//}}}?>

<?/*----- text under image {{{-----*/?>
<?elseif($settings['display'] == LinksSettings::DISP_FULL_BOTTOM):?>

<?foreach($links['data'] as $item):?>
<div class="linksitem">  
	<?if($item['thumbnail']):?>
	<a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><img <?if(!$settings['image_border']) print('class="lnnoborder"');?> src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=$item['name'];?>" /></a>
	<?endif;?>
	<h3><a href="<?=$item['href_detail'];?>"><?=$item['name'];?></a></h3>
	<?if($item['intro']):?><p><?=nl2br($item['intro']);?></p><?endif;?>
</div>
<?endforeach;?>
<?//}}}?>

<?/*----- name & intro {{{-----*/?>
<?elseif($settings['display'] == LinksSettings::DISP_TEXT):?>

<?foreach($links['data'] as $item):?>
	<h3><a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><?=$item['name'];?></a></h3>
	<?if($item['intro']):?><p><?=nl2br($item['intro']);?></p><?endif;?>
<?endforeach;?>

<?//}}}?>

<?/*----- list {{{-----*/?>
<?elseif($settings['display'] == LinksSettings::DISP_LIST):?>

<ul class="linksitem">
<?foreach($links['data'] as $item):?>
	<li><a <?if($active_id == $item['id']) echo 'class="linksel"';?> href="<?=$item['href_detail'];?>"><?=$item['name'];?></a></li>
<?endforeach;?>
</ul>

<?//}}}?>

<?/*----- image {{{-----*/?>
<?elseif($settings['display'] == LinksSettings::DISP_IMG):?>

<?foreach($links['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<a class="<?=$active_id == $item['id'] ? 'linksimg linksel' : 'linksimg';?>" href="<?=$item['href_detail'];?>"><img <?if(!$settings['image_border']) print('class="lnnoborder"');?> src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=$item['name'];?>" /></a>
	<?endif;?>
<?endforeach;?>

<?endif;?>
<?//}}}?>

<?if($links['page_numbers']['total'] > 1):?><p class="pagenav"><?=$links['totalItems'];?> items: <?=$links['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == Links::VIEW_DETAIL):?>
	<p class="linksintro"><?=$links['intro'];?></p>

	<?=$links['text'];?>
<p><a href="<?=$href_back;?>">Overzicht</a></p>

<?endif;//}}}?>
