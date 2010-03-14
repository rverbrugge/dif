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
<a href="<?=$href_import;?>"><img class="noborder" src="<?=$img_import['src'];?>" width="<?=$img_import['width'];?>" height="<?=$img_import['height'];?>" alt="importeren" title="importeren" /></a>
</p>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items gevonden: <?=$list['links'];?></p><?endif;?>

<table class="overview">
<tr>
<th></th>
<th>Naam</th>
<th>Online</th>
<th>Offline</th>
<th>Bestand</th>
<th>Gewijzigd</th>
<th>Creatie</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="delete" title="delete" /></a>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="edit" title="edit" /></a>
</td>
<td><?=$item['name'];?></td>
<td><?=strftime('%d-%b-%Y', $item['online']);?></td>
<td><?=$item['offline']?strftime('%d-%b-%Y', $item['offline']):'';?></td>
<td>
	<?if($item['file']):?>
	<a href="<?=$item['file_url'];?>" alt="<?=$item['name'];?>"><?=$item['name'];?></a>
	<?endif;?>
</td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?if($list['page_numbers']['total'] > 1):?><p class="pagenav"><?=$list['totalItems'];?> items gevonden: <?=$list['links'];?></p><?endif;?>
<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == ViewManager::TREE_NEW || $currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="ref_id" value="<?=$ref_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Algemeen</th></tr>
	<tr><th>Actief</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Actieve periode</th>	
		<td>
			van: <input type="text" class="date" id="online" name="online" value="<?=$online;?>" size="15" />
			tot: <input type="text" class="date" id="offline" name="offline" value="<?=$offline;?>" size="15" />
		</td>
	</tr>
	<tr><th>Index</th>	<td><input type="text" name="weight" value="<?=$weight;?>" size="5" /></td></tr>
	<tr><th>Naam</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Intro</th>	<td><textarea name="intro" rows="3" cols="75"><?=$intro;?></textarea></td></tr>
	<tr><th>Bestand</th>	
		<td>
		<?if(isset($file) && $file):?>
		<p><a href="<?=$file_url;?>" alt="<?=$name;?>" /><?=$name;?></a></p>
		<p>delete <input type="checkbox" class="noborder" name="file_delete" /></p>
		<?endif;?>
		<input type="file" name="file" value="" size="60" />
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
<input type="hidden" name="ref_id" value="<?=$ref_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Weet u zeker dat u het bestand <strong><?=$name;?></strong> wilt delete?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?if($attachment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$attachment['totalItems'];?> items gevonden: <?=$attachment['links'];?></p><?endif;?>

<?/*----- intro {{{-----*/?>
<?if($display == Attachment::DISP_INTRO):?>

<?foreach($attachment['data'] as $item):?>
<table class="attachmentitem">  
<tr>
	<td>
	  <h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>
		<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
	</td>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($display == Attachment::DISP_BRIEF):?>

<ul class="attachmentitem">  
<?foreach($attachment['data'] as $item):?>
	<li><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></li>
<?endforeach;?>
</ul>

<?endif;?>
<?//}}}?>

<?if($attachment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$attachment['totalItems'];?> items gevonden: <?=$attachment['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- import {{{------*/
if($currentView == Attachment::VIEW_FILE_IMPORT):?>

<?if(!isset($debug)):?>
<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<p>Locatie bijlagen: <strong><?=$importPath;?></strong></p>
<p>Weet u zeker dat u bestanden wilt importeren?</p>

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
