<?if($adminSection):?>
<h1><?=$pageTitle;?></h1>
<em><?=isset($tagName) ? "$tagName $currentViewName" : $currentViewName;?></em>
<?endif;?>

<?if(isset($errorMessage)):?><p class="error"><?=$errorMessage;?></p><?endif;?>

<?  /*-----conf edit {{{------*/
if($currentView == ViewManager::CONF_EDIT):?>
<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<p>No configuration options available.</p>

<?endif;//}}}?>

<?  /*-----settings edit {{{------*/
if($currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Rubrieknaam weergeven</th>  	<td><input type="checkbox" name="show_name" <?=($show_name)? 'checked ':'';?> class="noborder" /></td></tr>
	<tr><th>Type</th>											<td><select size="1" name="type"><?=$cbo_type;?></select></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?//}}}?>

<?  /*----- detail (overview) {{{------*/
else:?>

<?if(isset($currentmenu) && $currentmenu):?>
<?if(isset($currentmenuname)):?><h1><?=$currentmenuname;?></h1><?endif;?>
<ul>
<?foreach($currentmenu as $item):?>
<?if($item['id'] == $currentId):?>
<li class="selected"><?=$item['name'];?></li>
<?else:?>
<li><a href="<?=$item['path'];?>" title="<?=$item['name'];?>"><?=$item['name'];?></a></li>
<?endif;?>
<?endforeach;?>
</ul>
<?endif;?>

<?endif;//}}}?>
