<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree edit {{{------*/
if($currentView == ViewManager::TREE_EDIT):?>

<p>
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Periode</th>	
		<td>
			van: <input type="text" class="date" id="online" name="online" value="<?=$online;?>" size="15" />
			tot: <input type="text" class="date" id="offline" name="offline" value="<?=$offline;?>" size="15" />
		</td>
	</tr>
	<tr><th>Node</th>	<td><?=$ref_tree_id;?></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
	
<?if($news['page_numbers']['total'] > 1):?><p class="pagenav"><?=$news['totalItems'];?> items gevonden: <?=$news['links'];?></p><?endif;?>

<?/*----- full {{{-----*/?>
<?if($display == NewsLetter::DISP_FULL):?>

<?foreach($news['data'] as $item):?>
<div class="newsitem">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>

	<?if($item['thumbnail']):?>
	<div class="newsimg">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<div class="newsmain">
	<em><?=date('d-m-Y', $item['online']);?></em><br />
	<?if($item['intro']):?><?=nl2br(htmlentities($item['intro']));?><?endif;?>
	<p><a href="<?=$item['href_detail'];?>">lees verder &raquo;</a></p>
	</div>
</div>
<div class="newsbreak"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro {{{-----*/?>
<?elseif($display == NewsLetter::DISP_INTRO):?>

<?foreach($news['data'] as $item):?>
<table class="newsitem">  
<tr>
	<td class="newsmain">
	  <em><?=date('d-m-Y', $item['online']);?></em>
	  <h2><?=htmlentities($item['name']);?></h2>
		<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
		<a href="<?=$item['href_detail'];?>">lees verder &gt;</a>
	</td>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($display == NewsLetter::DISP_BRIEF):?>

<?foreach($news['data'] as $item):?>
<table class="newsitem">  
<tr>
	<td><em><?=date('d-m-Y', $item['online']);?></em></td>
	<td><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></td>
</tr>
</table>
<?endforeach;?>

<?endif;?>
<?//}}}?>

<?if($news['page_numbers']['total'] > 1):?><p class="pagenav"><?=$news['totalItems'];?> items gevonden: <?=$news['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == NewsLetter::VIEW_DETAIL):?>
	<em><?=strftime('%A %d %B %Y', $news['online']);?> &nbsp; views: <?=$news['count'];?></em>

	<p class="newsintro"><?=$news['intro'];?></p>

	<?=$news['text'];?>

	<?=$newsattachment;?>

	<p><a href="<?=$href_back;?>">Back</a></p>
<?endif;//}}}?>
