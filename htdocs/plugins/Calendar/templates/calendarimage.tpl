<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == Calendar::VIEW_IMAGE_OVERVIEW):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="new" title="new" /></a>
<a href="<?=$href_import;?>"><img class="noborder" src="<?=$img_import['src'];?>" width="<?=$img_import['width'];?>" height="<?=$img_import['height'];?>" alt="importeren" title="importeren" /></a>
<a href="<?=$href_resize;?>"><img class="noborder" src="<?=$img_resize['src'];?>" width="<?=$img_resize['width'];?>" height="<?=$img_resize['height'];?>" alt="afbeeldingen resizeeren" title="afbeeldingen resizeeren" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete selection" title="delete selection" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" name="myform" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="cal_id" value="<?=$cal_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="<?=ViewManager::getInstance()->getUrlId();?>" value="<?=Calendar::VIEW_IMAGE_DELETE;?>" />

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
	<input type="checkbox" name="id[]" value="<?=$item['id'];?>" />
	<!--a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a-->
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
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

<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == Calendar::VIEW_IMAGE_NEW || $currentView == Calendar::VIEW_IMAGE_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
<a href="javascript:document.myform.submit();"><img class="noborder" src="<?=$img_save['src'];?>" width="<?=$img_save['width'];?>" height="<?=$img_save['height'];?>" alt="save" title="save" /></a>
</p>

<form action="<?=$urlPath;?>" name="myform" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />
<input type="hidden" name="cal_id" value="<?=$cal_id;?>" />

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
if($currentView == Calendar::VIEW_IMAGE_IMPORT):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="cal_id" value="<?=$cal_id;?>" />

<p>Locatie afbeeldingen: <strong><?=$importPath;?></strong></p>

<p><strong>LET OP: Dit proces kan geruime tijd in beslag nemen.</strong><br />
Wanneer de webserver een te klein limiet aan uitvoertijd van scripts heeft gesteld, kan dit proces vroegtijdig beeindigd worden.
</p>
<p>
U kunt dit proces ook starten via de shell:
<pre>php index.php --tree_id=<?=$tree_id;?> --tag=<?=$tag;?> --cal_id=<?=$cal_id;?> --class=Calendar --task=import --username=<?=$username;?> --password=&lt;password&gt;</pre>
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
if($currentView == Calendar::VIEW_IMAGE_RESIZE):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="cal_id" value="<?=$cal_id;?>" />

<p><strong>LET OP: Dit proces kan geruime tijd in beslag nemen.</strong><br />
Wanneer de webserver een te klein limiet aan uitvoertijd van scripts heeft gesteld, kan dit proces vroegtijdig beeindigd worden.
</p>
<p>
U kunt dit proces ook starten via de shell:
<pre>php index.php --tree_id=<?=$tree_id;?> --tag=<?=$tag;?> --cal_id=<?=$cal_id;?> --class=Calendar --task=resize --username=<?=$username;?> --password=&lt;password&gt;</pre>
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
if($currentView == Calendar::VIEW_IMAGE_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />
<input type="hidden" name="cal_id" value="<?=$cal_id;?>" />

<p>Weet u zeker dat u de afbeelding <strong><?=$name;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW || $currentView == Calendar::VIEW_DETAIL || $currentView == Calendar::VIEW_ARCHIVE):?>
	
<div class="calimage">
<?foreach($calendarimage['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<a class="calimg" rel="lightbox[<?=$item['tag'];?>]" href="<?=$item['image']['src'];?>" title="<?=htmlentities($item['name']);?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	<?endif;?>
<?endforeach;?>
</div>

<?endif;//}}}?>

