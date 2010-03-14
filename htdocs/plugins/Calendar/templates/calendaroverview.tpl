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
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete selection" title="delete selection" /></a>
</p>

<div class="search">
<form target="_self" action="<?=$urlPath;?>" method="get">
<?foreach($searchparam as $key=>$value):?>
<input type="hidden" name="<?=$key;?>" value="<?=$value;?>" />
<?endforeach;?>
<input type="text" size="50" name="search" value="<?=array_key_exists('search', $searchcriteria) ? $searchcriteria['search'] : '';?>" />
<input type="submit" value="zoeken" class="formbutton" />
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
<th>Start</th>
<th>End</th>
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
<td><?=strftime('%d-%b-%Y', $item['start']);?></td>
<td><?=$item['stop']?strftime('%d-%b-%Y', $item['stop']):'';?></td>
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
</form>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?>items found: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == ViewManager::TREE_NEW || $currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.editform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="editform" enctype="multipart/form-data">
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
	<tr><th class="title" colspan="2">Algemeen</th></tr>
	<tr><th>Actief</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Aanvang</th>	<td>datum: <input type="text" class="date" id="start" name="start" value="<?=$start;?>" size="15" /> tijd: <input type="text" name="start_time" value="<?=$start_time;?>" size="10" /></td></tr>
	<tr><th>Einde</th>		
		<td>
			datum: <input type="text" class="date" id="stop" name="stop" value="<?=$stop;?>" size="15" onfocus="syncname(this, getName('start'));" /> 
			tijd: <input type="text" name="stop_time" value="<?=$stop_time;?>" size="10" />
		</td>
	</tr>
	</tr>
	<tr><th>Naam</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Intro</th>	<td><textarea name="intro" rows="3" cols="75"><?=$intro;?></textarea></td></tr>
	<tr><th colspan="2">Afbeelding</th></tr>
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
	<tr><th class="title" colspan="2">Inhoud</th></tr>
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
<td><?=strftime('%d-%b-%Y', $item['start']);?></td>
<td><?=$item['stop']?strftime('%d-%b-%Y', $item['stop']):'';?></td>
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

<?=$tpl_list;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == Calendar::VIEW_DETAIL):?>
	<p><em>
	<?=strftime($settings['date_format'], $cal['start']);?> <?=$cal['start_time'];?>
	<?=$cal['stop'] != $cal['start'] ? sprintf(' - %s %s', strftime($settings['date_format'], $cal['stop']), $cal['stop_time']) : ($cal['stop_time'] ? ' - '.$cal['stop_time'] : '');?> 
 &nbsp; views: <?=$cal['count'];?></em></p>

	<?if($calsettings['detail_img'] && $cal['thumbnail']):?>
	<div class="calimg">
	<img src="<?=$cal['thumbnail']['src'];?>" height="<?=$cal['thumbnail']['height'];?>" width="<?=$cal['thumbnail']['width'];?>" alt="<?=htmlentities($cal['name']);?>" />
	</div>
	<?endif;?>

	<?if($cal['intro']):?><p class="calintro"><?=nl2br(htmlentities($cal['intro']));?></p><?endif;?>

	<?=$cal['text'];?>

	<br clear="all" />
	<?=$calattachment;?>

	<?=$template_calimage;?>

	<?if(isset($calendarcomment)):?>
	<p><a href="<?=$href_back;?>"><?=$calsettings['cap_back'];?></a></p>
	<?=$calendarcomment;?>
	<?endif;?>


	<p><a href="<?=$href_back;?>"><?=$calsettings['cap_back'];?></a></p>
<?endif;//}}}?>
