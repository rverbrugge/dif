<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=$currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree edit {{{------*/
if($currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Aantal items</th>	<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th>Naam</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Node</th>	<td><?=$ref_tree_id;?></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
<h1><?=$name;?></h1>


<?/*----- full {{{-----*/?>
<?if($display == Calendar::DISP_FULL):?>

<?foreach($cal['data'] as $item):?>
<table class="calitem">  
<tr>
	<td class="calmain">
	  <h2><?=htmlentities($item['name']);?></h2>
	<em>
	<?=date('d-m-Y', $item['start']);?> <?=$item['start_time'];?>
	<?=$item['stop'] != $item['start'] ? sprintf(' - %s %s', date('d-m-Y', $item['stop']), $item['stop_time']) : ($item['stop_time'] ? ' - '.$item['stop_time'] : '');?> 
	</em><br />
		<?if($item['intro']):?><p><?=nl2br(htmlentities($item['intro']));?></p><?endif;?>
		<a href="<?=$item['href_detail'];?>">lees verder &gt;</a>
	</td>
	<?if($item['thumbnail']):?>
	<td>
	<a href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	</td>
	<?endif;?>
</tr>
</table>
<?endforeach;?>
<?//}}}?>

<?/*----- brief {{{-----*/?>
<?elseif($display == Calendar::DISP_BRIEF):?>

<ul>
<?foreach($cal['data'] as $item):?>
<li>
	<a href="<?=$item['href_detail'];?>"><?=htmlentities($item['name']);?></a>
</li>
<?endforeach;?>
</ul>

<?endif;?>
<?//}}}?>

<?endif;//}}}?>
