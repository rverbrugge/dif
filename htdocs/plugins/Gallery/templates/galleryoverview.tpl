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
<a href="<?=$href_import;?>"><img class="noborder" src="<?=$img_import['src'];?>" width="<?=$img_import['width'];?>" height="<?=$img_import['height'];?>" alt="importeren" title="importeren" /></a>
<a href="<?=$href_resize;?>"><img class="noborder" src="<?=$img_resize['src'];?>" width="<?=$img_resize['width'];?>" height="<?=$img_resize['height'];?>" alt="afbeeldingen resizeeren" title="afbeeldingen resizeeren" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete selection" title="delete selection" /></a>
</p>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items found: <?=$list['links'];?></p><?endif;?>

<form action="<?=$urlPath;?>" method="post" name="myform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="<?=ViewManager::getInstance()->getUrlId();?>" value="<?=ViewManager::TREE_DELETE;?>" />

<table class="overview">
<tr>
<th><a href="javascript:toggleCheckBoxes(document.myform);">toggle selection</a></th>
<th>Naam</th>
<th>Afbeelding</th>
<th>Gewijzigd</th>
<th>Creatie</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<input type="checkbox" name="id[]" value="<?=$item['id'];?>" class="noborder" />
	<!--a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a-->
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
	<a href="<?=$item['href_com'];?>"><img class="noborder" src="<?=$img_list['src'];?>" width="<?=$img_list['width'];?>" height="<?=$img_list['height'];?>" alt="posts" title="posts" /></a>
</td>
<td><?=$item['name'];?></td>
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
</form>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items gevonden: <?=$list['links'];?></p><?endif;?>
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
	<tr><th class="title" colspan="2">Algemeen</th></tr>
	<tr><th>Actief</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Index</th>	<td><input type="text" name="weight" value="<?=$weight;?>" size="10" /></td></tr>
	<tr><th>Naam</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Omschrijving</th>	<td><textarea name="description" rows="3" cols="75"><?=$description;?></textarea></td></tr>
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
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*----- import {{{------*/
if($currentView == Gallery::VIEW_IMPORT):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p>Locatie afbeeldingen: <strong><?=$importPath;?></strong></p>

<p><strong>LET OP: Dit proces kan geruime tijd in beslag nemen.</strong><br />
Wanneer de webserver een te klein limiet aan uitvoertijd van scripts heeft gesteld, kan dit proces vroegtijdig beeindigd worden.
</p>
<p>
U kunt dit proces ook starten via de shell:
<pre>php index.php --tree_id=<?=$tree_id;?> --tag=<?=$tag;?> --class=Gallery --task=import --username=<?=$username;?> --password=&lt;password&gt;</pre>
</p>

<p>Weet u zeker dat u afbeeldingen wilt importeren?</p>

<input type="submit" value="Importeren" class="formbutton" />
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
if($currentView == Gallery::VIEW_RESIZE):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p><strong>LET OP: Dit proces kan geruime tijd in beslag nemen.</strong><br />
Wanneer de webserver een te klein limiet aan uitvoertijd van scripts heeft gesteld, kan dit proces vroegtijdig beeindigd worden.
</p>
<p>
U kunt dit proces ook starten via de shell:
<pre>php index.php --tree_id=<?=$tree_id;?> --tag=<?=$tag;?> --class=Gallery --task=resize --username=<?=$username;?> --password=&lt;password&gt;</pre>
</p>

<p>Weet u zeker dat u het formaat van afbeeldingen wilt aanpassen?</p>

<input type="submit" value="Resizen" class="formbutton" />
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

<p>Weet u zeker dat u de afbeelding <strong><?=$name;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?if($gallery['page_numbers']['total'] > 1):?><p class="pagenav"><?=$gallery['totalItems'];?> items: <?=$gallery['links'];?></p><?endif;?>

<?/*----- lightbox {{{-----*/?>
<?if($display == Gallery::DISP_LIGHTBOX):?>

<div class="galhidden">
<?foreach($pregallery as $item):?>
	<?if($item['thumbnail']):?>
	<a class="galimg" rel="lightbox[<?=$item['tag'];?>]" href="<?=$item['image']['src'];?>" title="<?=htmlentities(sprintf('%s <br />%s',$item['name'], nl2br($item['description'])));?>"></a>
	<?endif;?>
<?endforeach;?>
</div>

<?foreach($gallery['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<div class="<?=$settings['display_overview'] == Gallery::DISP_IMG ?  'galimg' : 'galcomment galimg';?>">
	
	<?if($settings['display_overview'] == Gallery::DISP_BRIEF_TOP || $settings['display_overview'] == Gallery::DISP_FULL_TOP ):?>
	<p class="title"><?=$item['name'];?></p>
	<?if($settings['display_overview'] == Gallery::DISP_FULL_TOP):?><p><?=nl2br($item['description']);?></p><?endif;?>
	<?endif;?>

	<a rel="lightbox[<?=$item['tag'];?>]" href="<?=$item['image']['src'];?>" title="<?=htmlentities(sprintf('%s <br />%s',$item['name'], nl2br($item['description'])));?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	
	<?if($settings['display_overview'] == Gallery::DISP_BRIEF_BOT || $settings['display_overview'] == Gallery::DISP_FULL_BOT ):?>
	<p class="title"><?=$item['name'];?></p>
	<?if($settings['display_overview'] == Gallery::DISP_FULL_BOT):?><p><?=nl2br($item['description']);?></p><?endif;?>
	<?endif;?>

	<?if($settings['comment']):?><p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p><?endif;?>

	</div>
	<?endif;?>
<?endforeach;?>

<div class="galhidden">
<?foreach($postgallery as $item):?>
	<?if($item['thumbnail']):?>
	<a class="galimg" rel="lightbox[<?=$item['tag'];?>]" href="<?=$item['image']['src'];?>" title="<?=htmlentities(sprintf('%s <br />%s',$item['name'], nl2br($item['description'])));?>"></a>
	<?endif;?>
<?endforeach;?>
</div>

<?//}}}?>

<?/*----- slideshow {{{-----*/?>
<?elseif($display == Gallery::DISP_SLIDESHOW):?>

<div id="thumbnails">
<?foreach($gallery['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<a class="galimg" href="<?=$item['image']['src'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	<?endif;?>
<?endforeach;?>
</div>

<div id="slideshow" class="slideshow"></div>

<script type="text/javascript">
window.addEvent('domready',function(){
	var obj = {
		wait: 3000, 
		effect: 'fade',
		duration: 1000, 
		loop: true, 
		thumbnails: true,
		backgroundSlider: true
	}
	show = new SlideShow('slideshow','galimg',obj);
	show.play();
});
</script>
<?//}}}?>

<?/*----- normal {{{-----*/?>
<?else:?>

<?foreach($gallery['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<div class="<?=$settings['display_overview'] == Gallery::DISP_IMG ?  'galimg' : 'galcomment galimg';?>">
	
	<?if($settings['display_overview'] == Gallery::DISP_BRIEF_TOP || $settings['display_overview'] == Gallery::DISP_FULL_TOP ):?>
	<p class="title"><?=$item['name'];?></p>
	<?if($settings['display_overview'] == Gallery::DISP_FULL_TOP):?><p><?=nl2br($item['description']);?></p><?endif;?>
	<?endif;?>

	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>

	<?if($settings['display_overview'] == Gallery::DISP_BRIEF_BOT || $settings['display_overview'] == Gallery::DISP_FULL_BOT ):?>
	<p class="title"><?=$item['name'];?></p>
	<?if($settings['display_overview'] == Gallery::DISP_FULL_BOT):?><p><?=nl2br($item['description']);?></p><?endif;?>
	<?endif;?>

	<?if($settings['comment']):?><p><a href="<?=$item['href_detail'];?>"><?=$settings['cap_detail'];?></a></p><?endif;?>

	</div>
	<?endif;?>
<?endforeach;?>

<?endif;//}}}?>

<?if($gallery['page_numbers']['total'] > 1):?><p class="clear pagenav"><?=$gallery['totalItems'];?> items: <?=$gallery['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == Gallery::VIEW_DETAIL):?>

<p class="center">
<?if(isset($href_previous)):?> <a href="<?=$href_previous;?>"><?=$gallerysettings['cap_previous'];?></a><?endif;?>
<?if(isset($href_next)):?> <?if(isset($href_previous)):?>|<?endif;?> <a href="<?=$href_next;?>"><?=$gallerysettings['cap_next'];?></a><?endif;?>
</p>

<div id="galdetail">
<img src="<?=$gallery['image']['src'];?>" height="<?=$gallery['image']['height'];?>" width="<?=$gallery['image']['width'];?>" alt="<?=htmlentities($gallery['name']);?>" />
<?if($gallery['description']):?><p><?=nl2br($gallery['description']);?></p><?endif;?>
</div>

<p class="center">
<?if(isset($href_previous)):?> <a href="<?=$href_previous;?>"><?=$gallerysettings['cap_previous'];?></a><?endif;?>
<?if(isset($href_next)):?> <?if(isset($href_previous)):?>|<?endif;?> <a href="<?=$href_next;?>"><?=$gallerysettings['cap_next'];?></a><?endif;?>
</p>

<?=isset($gallerycomment)?$gallerycomment:'';?>

<p><a href="<?=$href_back;?>"><?=$gallerysettings['cap_back'];?></a></p>
<?endif;//}}}?>
