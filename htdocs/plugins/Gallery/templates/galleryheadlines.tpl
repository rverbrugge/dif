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
	<tr><th class="title" colspan="2">Settings</th></tr>
	<tr><th>Display type</th>	<td><select size="1" name="display"><?=$cbo_display;?></select></td></tr>
	<tr><th>Display order</th>	<td><select size="1" name="display_order"><?=$cbo_display_order;?></select></td></tr>
	<tr><th>Item count</th>	<td><input type="text" name="rows" value="<?=$rows;?>" size="5" /></td></tr>
	<tr><th>Name</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Node</th>	<td><?=$ref_tree_id;?></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----overview {{{------*/
if($currentView == ViewManager::OVERVIEW):?>
<?if($name):?><h1><?=$name;?></h1><?endif;?>

<?/*----- lightbox {{{-----*/?>
<?if($display == GalleryHeadlines::DISP_LIGHTBOX):?>

<?foreach($gallery['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<a class="galimg" rel="lightbox[<?=$item['tag'];?>]" href="<?=$item['image']['src'];?>" title="<?=htmlentities(sprintf('%s <br />%',$item['name'], nl2br($item['description'])));?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	<?endif;?>
<?endforeach;?>

<?//}}}?>

<?/*----- normal {{{-----*/?>
<?else:?>

<?foreach($gallery['data'] as $item):?>
	<?if($item['thumbnail']):?>
	<a class="galimg" href="<?=$item['href_detail'];?>"><img src="<?=$item['thumbnail']['src'];?>" height="<?=$item['thumbnail']['height'];?>" width="<?=$item['thumbnail']['width'];?>" alt="<?=htmlentities($item['name']);?>" /></a>
	<?endif;?>
<?endforeach;?>

<?endif;//}}}?>

<?endif;//}}}?>
