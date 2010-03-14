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

<?//}}}?>

<?  /*-----settings edit {{{------*/
elseif($currentView == ViewManager::TREE_EDIT):?>

<p class="options">
<a href="<?=$href_up;?>"><img class="noborder" src="<?=$img_up['src'];?>" width="<?=$img_up['width'];?>" height="<?=$img_up['height'];?>" alt="back" title="back" /></a>
</p>

<form action="<?=$urlPath;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Type</th>						<td><select size="1" name="type"><?=$cbo_type;?></select></td></tr>
	<tr><th>Scheidingsteken</th>			<td><input type="text" name="delimiter" value="<?=$delimiter;?>" size="75" /></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?//}}}?>

<?  /*----- detail (overview) {{{------*/
else:?>

<?if(isset($menu) && $menu):
$first=true;?>
<ul id="<?=$cssid;?>">
<?foreach($menu as $item):?>
<li <?=($item['selected']) ? 'class="selected"' : '';?>><a href="<?=$item['path'];?>" title="<?=$item['name'];?>"><?if($settings['delimiter'] && !$first) printf('<span class="delim">%s</span>', $settings['delimiter']); $first=false;?><?=$item['name'];?></a> 
<?if($item['child']):?>
<ul>
<?foreach($item['child'] as $subitem):?>
<li <?=($subitem['selected']) ? 'class="selected"' : '';?>><a href="<?=$subitem['path'];?>" title="<?=$subitem['name'];?>"><?=$subitem['name'];?></a></li>
<?endforeach;?>
</ul><?endif;?>
</li>
<?endforeach;?>
</ul>
<?endif;?>

<?endif;//}}}?>
