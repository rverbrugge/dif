<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----tree overview {{{------*/
if($currentView == Form::VIEW_ELEMENT_OVERVIEW):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="Back" title="Back" /></a>
<a href="<?=$href_new;?>"><img class="noborder" src="<?=$img_new['src'];?>" width="<?=$img_new['width'];?>" height="<?=$img_new['height'];?>" alt="New" title="New" /></a>
</p>

<table class="overview">
<tr>
<th></th>
<th>Id</th>
<th>Index</th>
<th>Size</th>
<th>Name</th>
<th>Type</th>
<th>Modified</th>
<th>Created</th>
</tr>
<?foreach($list['data'] as $item):?>
<tr<?=!$item['activated'] ? ' class="deactivated"':'';?>>
<td>
	<?if(isset($item['href_del'])):?>
	<a href="<?=$item['href_del'];?>"><img class="noborder" src="<?=$img_del['src'];?>" width="<?=$img_del['width'];?>" height="<?=$img_del['height'];?>" alt="plugin delete" title="plugin delete" /></a>
	<?endif;?>
	<?if(isset($item['href_edit'])):?>
	<a href="<?=$item['href_edit'];?>"><img class="noborder" src="<?=$img_edit['src'];?>" width="<?=$img_edit['width'];?>" height="<?=$img_edit['height'];?>" alt="plugin edit" title="plugin edit" /></a>
	<?endif;?>
	<?if(isset($item['href_mv_prev'])):?>
	<a href="<?=$item['href_mv_prev'];?>"><img class="noborder" src="<?=$img_up1['src'];?>" width="<?=$img_up1['width'];?>" height="<?=$img_up1['height'];?>" alt="Move to previous" title="move page to previous item" /></a>
	<?else:?>
	<img class="noborder" src="<?=$img_clear['src'];?>" width="<?=$img_clear['width'];?>" height="<?=$img_clear['height'];?>" alt="" />
	<?endif;?>
	<?if(isset($item['href_mv_next'])):?>
	<a href="<?=$item['href_mv_next'];?>"><img class="noborder" src="<?=$img_down1['src'];?>" width="<?=$img_down1['width'];?>" height="<?=$img_down1['height'];?>" alt="Move to next" title="move page to next item" /></a>
	<?endif;?>
</td>
<td><?=$item['type_id'];?></td>
<td><?=$item['weight'];?></td>
<td><?=$item['size'];?></td>
<td><?=$item['name'];?></td>
<td><?=$item['type_name'];?></td>
<td><?=strftime('%c', $item['ts']);?></td>
<td><?=strftime('%c', $item['createdate']);?></td>
</tr>
<?endforeach;?>
</table>

<?endif;//}}}?>

<?  /*-----tree new and edit {{{------*/
if($currentView == Form::VIEW_ELEMENT_NEW || $currentView == Form::VIEW_ELEMENT_EDIT):?>

<p class="options">
<a href="<?=$href_back;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Element</th></tr>
	<tr><th>Actief</th>  	<td><input type="checkbox" name="active" <?=($active)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Verplicht</th>  	<td><input type="checkbox" name="mandatory" <?=($mandatory)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Index</th>	<td><input type="text" name="weight" value="<?=$weight;?>" size="5" /></td></tr>
	<tr><th>Lengte</th>	<td><input type="text" name="size" value="<?=$size;?>" size="5" /></td></tr>
	<tr><th>Type</th>	<td><select size="1" name="type"><?=$cbo_type;?></select></td></tr>
	<tr><th>Naam</th>	<td><input type="text" name="name" value="<?=$name;?>" size="75" /></td></tr>
	<tr><th>Omschrijving</th>	<td><textarea name="description" rows="3" cols="75"><?=$description;?></textarea></td></tr>
	<tr><th>Standaard waarde</th>	<td><input type="text" name="def" value="<?=$def;?>" size="75" /></td></tr>
	<tr><th>Opties</th>	<td><textarea name="options" rows="7" cols="75"><?=$options;?></textarea></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="submit" value="Save + new" name="addnew" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>

<?  /*-----delete {{{------*/
if($currentView == Form::VIEW_ELEMENT_DELETE):?>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />
<input type="hidden" name="id" value="<?=$id;?>" />

<p>Are you sure you want to delete the element <strong><?=$name;?></strong>?</p>

<input type="submit" value="Delete" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?endif;//}}}?>


