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
	
<?if($attachment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$attachment['totalItems'];?> items gevonden: <?=$attachment['links'];?></p><?endif;?>

<?/*----- intro {{{-----*/?>
<?if($display == News::DISP_INTRO):?>

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
<?elseif($display == News::DISP_BRIEF):?>

<ul class="attachmentitem">  
<?foreach($attachment['data'] as $item):?>
	<li><a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a></li>
<?endforeach;?>
</ul>

<?endif;?>
<?//}}}?>

<?if($attachment['page_numbers']['total'] > 1):?><p class="pagenav"><?=$attachment['totalItems'];?> items gevonden: <?=$attachment['links'];?></p><?endif;?>

<?endif;//}}}?>

<?  /*----- detail {{{------*/
if($currentView == ViewManager::DETAIL):?>
	<p class="intro"><?=$attachment['intro'];?></p>

	<?=$attachment['text'];?>

	<p><a href="<?=$href_back;?>">Back</a></p>
<?endif;//}}}?>
