<?if($adminSection && !isset($pluginProviderRequest)):?>
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
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete selection" title="delete selection" /></a>
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


<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?>items found: <?=$list['links'];?></p><?endif;?>

<form action="<?=$urlPath;?>" method="post" name="myform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="<?=ViewManager::getInstance()->getUrlId();?>" value="<?=ViewManager::TREE_DELETE;?>" />

<table class="overview">
<tr>
<th><a href="javascript:toggleCheckBoxes(document.myform);">toggle selection</a></th>
<th>Name</th>
<th>Online</th>
<th>Offline</th>
<th>Image</th>
<th>Views</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<input type="checkbox" name="id[]" value="<?=$item['id'];?>" class="noborder" />
	<!--a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a-->
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
	<a href="<?=$item['href_att'];?>"><img class="noborder" src="<?=$img_file['src'];?>" width="<?=$img_file['width'];?>" height="<?=$img_file['height'];?>" alt="attachments" title="attachments" /></a>
	<a href="<?=$item['href_com'];?>"><img class="noborder" src="<?=$img_list['src'];?>" width="<?=$img_list['width'];?>" height="<?=$img_list['height'];?>" alt="posts" title="posts" /></a>
	<a href="<?=$item['href_img'];?>"><img class="noborder" src="<?=$img_image['src'];?>" width="<?=$img_image['width'];?>" height="<?=$img_image['height'];?>" alt="images" title="images" /></a>
</td>
<td><?=$item['name'];?></td>
<td><?=strftime('%d-%b-%Y', $item['online']);?></td>
<td><?=$item['offline']?strftime('%d-%b-%Y', $item['offline']):'';?></td>
<td>
	<?if($item['thumbnail']):?>
	<a href="<?=$item['href_edit'];?>"><img src="<?=$item['thumbnail']['src'];?>" width="<?=$item['thumbnail']['width'];?>" height="<?=$item['thumbnail']['height'];?>" alt="" /></a>
	<?endif;?>
</td>
<td><?=$item['count'];?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>
</form>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?>items found: <?=$list['links'];?></p><?endif;?>
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
			until: <input type="text" class="date" id="offline" name="offline" value="<?=$offline;?>" size="15" />
		</td>
	</tr>
	<tr><th>Display date</th>	<td><input type="text" class="date" id="date" name="date" value="<?=$date;?>" size="15" /></td></tr>
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
	<tr><th class="title" colspan="2">Content</th></tr>
	<tr><td colspan="2">
		<?=$fckBox;?>
	</td></tr>
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

<?  /*----- plugin select request {{{------*/
if($pluginProviderRequest == PluginProvider::TYPE_SELECT):?>

<!--div class="search">
<form target="_self" action="<?=$urlPath;?>" method="get">
<?foreach($searchparam as $key=>$value):?>
<input type="hidden" name="<?=$key;?>" value="<?=$value;?>" />
<?endforeach;?>
<input type="text" size="50" name="search" value="<?=array_key_exists('search', $searchcriteria) ? $searchcriteria['search'] : '';?>" />
<input type="submit" value="search" class="formbutton" />
</form>
</div-->

<?foreach($rangeKeys as $item):?>
<input type="hidden" name="<?=PluginProvider::KEY_RANGE;?>[]" value="<?=$item;?>" />
<?endforeach;?>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Name</th>
<th>Online</th>
<th>Offline</th>
<th>Image</th>
<th>Views</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<input type="checkbox" name="<?=PluginProvider::KEY_SELECT;?>[<?=$item['id'];?>]" <?=($item['selected'])? 'checked ':'';?> class="noborder" />
</td>
<td><?=$item['name'];?></td>
<td><?=strftime('%d-%b-%Y', $item['online']);?></td>
<td><?=$item['offline']?strftime('%d-%b-%Y', $item['offline']):'';?></td>
<td>
	<?if($item['thumbnail']):?>
	<img src="<?=$item['thumbnail']['src'];?>" width="<?=$item['thumbnail']['width'];?>" height="<?=$item['thumbnail']['height'];?>" alt="" />
	<?endif;?>
</td>
<td><?=$item['count'];?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*----- plugin list request {{{------*/
if($pluginProviderRequest == PluginProvider::TYPE_LIST):?>

<?/*----- full {{{-----*/?>
<?if($settings['display'] == News::DISP_FULL):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
	<p><em><?=strftime('%A %d %B %Y', $item['online']);?></em></p>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<?if($item['intro']):?><p class="newsintro"><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>

	<?=$item['text'];?>

	<?=$item['newsattachment'];?>

	<?if($settings['comment']):?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	<?endif;?>

</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- image {{{-----*/?>
<?elseif($settings['display'] == News::DISP_IMAGE):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<div class="newsmain">
	<em><?=strftime('%d-%B-%Y', $item['online']);?></em><br />
	<?if($item['intro']):?><?=nl2br(htmlentities($item['intro']));?><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	</div>
</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro {{{-----*/?>
<?elseif($settings['display'] == News::DISP_INTRO):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><?=htmlentities($item['name']);?></h2>
	<em><?=strftime('%d-%B-%Y', $item['online']);?></em>
	<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($settings['display'] == News::DISP_BRIEF):?>

<?foreach($news['data'] as $item):?>
<table class="newsitem">  
<tr>
	<td><em><?=strftime('%d-%B-%Y', $item['online']);?></em></td>
	<td><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></td>
</tr>
</table>
<?endforeach;?>

<?endif;?>
<?//}}}?>

<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?if($news['page_numbers']['total'] > 1):?><p class="pagenav"><?=$news['totalItems'];?> items found: <?=$news['links'];?></p><?endif;?>

<?/*----- full {{{-----*/?>
<?if($settings['display'] == News::DISP_FULL):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
	<p><em><?=($item['date']) ? strftime($settings['date_format'], $item['date']).' &nbsp; ' : '';?>views: <?=$item['count'];?></em></p>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<?if($item['intro']):?><p class="newsintro"><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>

	<?=$item['text'];?>

	<?=$item['newsattachment'];?>

	<?=$item['template_newsimage'];?>

	<?if($settings['comment']):?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	<?endif;?>

</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- image {{{-----*/?>
<?elseif($settings['display'] == News::DISP_IMAGE):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
	<p><em><?=($item['date']) ? strftime($settings['date_format'], $item['date']).' &nbsp; ' : '';?>views: <?=$item['count'];?></em></p>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro {{{-----*/?>
<?elseif($settings['display'] == News::DISP_INTRO):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><?=htmlentities($item['name']);?></h2>
	<p><em><?=($item['date']) ? strftime($settings['date_format'], $item['date']).' &nbsp; ' : '';?>views: <?=$item['count'];?></em></p>
	<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro & image {{{-----*/?>
<?elseif($settings['display'] == News::DISP_INTRO_IMAGE):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<div class="newsmain">
	<em><?=($item['date']) ? strftime($settings['date_format'], $item['date']).' &nbsp; ' : '';?>views: <?=$item['count'];?></em><br />
	<?if($item['intro']):?><?=nl2br(htmlentities($item['intro']));?><?endif;?>
	<p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p>
	</div>
</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($settings['display'] == News::DISP_BRIEF):?>

<table class="newsitem">  
<?foreach($news['data'] as $item):?>
<tr>
	<td><em><?=strftime($settings['date_format'], $item['online']);?></em></td>
	<td><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></td>
</tr>
<?endforeach;?>
</table>

<?endif;?>
<?//}}}?>

<?if($news['page_numbers']['total'] > 1):?><p class="pagenav"><?=$news['totalItems'];?> items found: <?=$news['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == News::VIEW_DETAIL):?>
	<p><em><?=($news['date']) ? strftime($newssettings['date_format'], $news['date']).' &nbsp; ' : '';?>views: <?=$news['count'];?></em></p>

	<?if($newssettings['detail_img'] && $news['thumbnail']):?>
	<div class="newsimg">
	<img src="<?=$news['thumbnail']['src'];?>" height="<?=$news['thumbnail']['height'];?>" width="<?=$news['thumbnail']['width'];?>" alt="<?=htmlentities($news['name']);?>" />
	</div>
	<?endif;?>

	<?if($news['intro']):?><p class="newsintro"><?=nl2br(htmlentities($news['intro']));?></p><?endif;?>

	<?=$news['text'];?>

	<br clear="all" />
	<?=$newsattachment;?>

	<?=$template_newsimage;?>

	<?if(isset($newscomment)):?>
	<p><a href="<?=$href_back;?>"><?=$newssettings['cap_back'];?></a></p>
	<?=$newscomment;?>
	<?endif;?>


	<p><a href="<?=$href_back;?>"><?=$newssettings['cap_back'];?></a></p>
<?endif;//}}}?>

