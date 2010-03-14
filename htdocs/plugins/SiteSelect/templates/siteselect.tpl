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

<form action="<?=$urlPath;?>" method="post">
<input type="hidden" name="tree_id" value="<?=$tree_id;?>" />
<input type="hidden" name="tag" value="<?=$tag;?>" />

<table id="form">
	<tr><th class="title" colspan="2">Instellingen</th></tr>
	<tr><th>Type</th>											<td><select size="1" name="type"><?=$cbo_type;?></select></td></tr>
</table>

<input type="submit" value="Save" class="formbutton" />
<input type="button" value="Cancel" onclick="window.location='<?=$href_back;?>';" class="formbutton" />

</form>
<?//}}}?>

<?  /*----- detail (overview) {{{------*/
else:?>

<?if(isset($sitegroup) && $sitegroup):?>
<?if($detail['type'] == SiteSelect::VIEW_COMBO):?>
<form action="/" name="siteselect" method="get">
	<select size="1" name="<?=SystemSiteGroup::CURRENT_ID_KEY;?>" onchange="siteselect.submit();"><?=Utils::getHtmlCombo($sitegroup['data'], $sitegroupId);?></select>
</form>
<?elseif($detail['type'] == SiteSelect::VIEW_IMAGE):?>
<div id="siteselect">
<?foreach($sitegroup['data'] as $item):?>
<a href="<?=$item['path'];?>" title="<?=$item['name'];?>"><img src="<?=$item['img']['src'];?>" width="<?=$item['img']['width'];?>" height="<?=$item['img']['height'];?>" alt="<?=$item['name'];?>" /></a>
<?endforeach;?>
</div>
<?else:?>
<ul>
<?foreach($sitegroup['data'] as $item):?>
<li><a href="<?=$item['path'];?>" title="<?=$item['name'];?>"><?=$item['name'];?></a></li>
<?endforeach;?>
</ul>
<?endif;?>
<?endif;?>

<?endif;//}}}?>
