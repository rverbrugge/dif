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
	
<?if($poll_['page_numbers']['total'] > 1):?><p class="pagenav"><?=$poll_['totalItems'];?> items gevonden: <?=$poll_['links'];?></p><?endif;?>

<?/*----- full {{{-----*/?>
<?if($display == Poll::DISP_FULL):?>

<?foreach($poll_['data'] as $item):?>
<div class="poll_item">  
	<h2><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></h2>

	<?if($item['thumbnail']):?>
	<div class="poll_img">
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</div>
	<?endif;?>

	<div class="poll_main">
	<em><?=date('d-m-Y', $item['online']);?></em><br />
	<?if($item['intro']):?><?=nl2br(htmlentities($item['intro']));?><?endif;?>
	<p><a href="<?=$item['href_detail'];?>">lees verder &raquo;</a></p>
	</div>
</div>
<div class="poll_break"><hr /></div>
<?endforeach;?>
<?//}}}?>

<?/*----- intro {{{-----*/?>
<?elseif($display == Poll::DISP_INTRO):?>

<?foreach($poll_['data'] as $item):?>
<table class="poll_item">  
<tr>
	<td class="poll_main">
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
<?elseif($display == Poll::DISP_BRIEF):?>

<?foreach($poll_['data'] as $item):?>
<table class="poll_item">  
<tr>
	<td><em><?=date('d-m-Y', $item['online']);?></em></td>
	<td><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></td>
</tr>
</table>
<?endforeach;?>

<?endif;?>
<?//}}}?>

<?if($poll_['page_numbers']['total'] > 1):?><p class="pagenav"><?=$poll_['totalItems'];?> items gevonden: <?=$poll_['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == Poll::VIEW_DETAIL):?>
	<em><?=strftime('%A %d %B %Y', $poll_['online']);?> &nbsp; views: <?=$poll_['count'];?></em>

	<p class="poll_intro"><?=$poll_['intro'];?></p>

	<?=$poll_['text'];?>

	<?=$poll_attachment;?>

	<p><a href="<?=$href_back;?>">Back</a></p>
<?endif;//}}}?>
